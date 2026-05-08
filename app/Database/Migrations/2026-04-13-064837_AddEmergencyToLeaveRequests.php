<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEmergencyToLeaveRequests extends Migration
{
    public function up()
    {
        // ---------------------------------------------------------
        // 1. MODULE NGHỈ PHÉP (Đồng nhất hoàn toàn với mysql.sql)
        // ---------------------------------------------------------
        $leaveFields = [
            'is_emergency' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'after'      => 'leave_type',
                'comment'    => 'Trạng thái nghỉ khẩn cấp: 1-Có, 0-Không'
            ],
            'handover_to' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'reason',
                'comment'    => 'ID nhân viên nhận bàn giao (Liên kết bảng employees)'
            ],
            'handover_content' => [
                'type'       => 'TEXT',
                'null'       => true,
                'after'      => 'handover_to',
                'comment'    => 'Chi tiết các nội dung cần bàn giao'
            ],
            'approval_note' => [
                'type'       => 'TEXT',
                'null'       => true,
                'after'      => 'status',
                'comment'    => 'Ghi chú phê duyệt hoặc lý do chỉnh sửa của quản trị viên'
            ]
        ];

        foreach ($leaveFields as $name => $conf) {
            if (!$this->db->fieldExists($name, 'leave_requests')) {
                $this->forge->addColumn('leave_requests', [$name => $conf]);
            } else {
                // Nếu đã tồn tại nhưng có thể chưa có comment/type chuẩn, cập nhật lại
                unset($conf['after']); 
                $this->forge->modifyColumn('leave_requests', [$name => $conf]);
            }
        }

        // ---------------------------------------------------------
        // 2. RÀ SOÁT & ĐỒNG BỘ CẤU TRÚC (Từ nhật ký mysql.sql)
        // ---------------------------------------------------------
        
        // --- BẢNG cases ---
        // Sửa status sang VARCHAR(50) để linh hoạt (Line 665 mysql.sql)
        $this->forge->modifyColumn('cases', [
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'comment'    => 'Trạng thái vụ việc linh hoạt'
            ]
        ]);

        // Xóa cột type (Line 587 mysql.sql)
        if ($this->db->fieldExists('type', 'cases')) {
            $this->forge->dropColumn('cases', 'type');
        }

        // --- BẢNG employees ---
        // Thêm manager_id (Line 509 mysql.sql)
        if (!$this->db->fieldExists('manager_id', 'employees')) {
            $this->forge->addColumn('employees', [
                'manager_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                    'after'      => 'department_id',
                    'comment'    => 'ID của quản lý trực tiếp (ID sếp)'
                ]
            ]);
            // Khóa ngoại
            $this->db->query("ALTER TABLE employees ADD CONSTRAINT fk_emp_manager FOREIGN KEY (manager_id) REFERENCES employees(id) ON DELETE SET NULL");
        }

        // --- BẢNG knowledge_base ---
        // Thêm các trường cấu trúc (Line 656 mysql.sql)
        $kbFields = [
            'summary'   => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true, 'after' => 'title', 'comment' => 'Tóm tắt nhanh'],
            'problem'   => ['type' => 'TEXT', 'null' => true, 'after' => 'summary', 'comment' => 'Vấn đề'],
            'solution'  => ['type' => 'TEXT', 'null' => true, 'after' => 'problem', 'comment' => 'Cách giải quyết'],
            'red_flags' => ['type' => 'TEXT', 'null' => true, 'after' => 'solution', 'comment' => 'Lưu ý rủi ro']
        ];
        foreach ($kbFields as $name => $conf) {
            if (!$this->db->fieldExists($name, 'knowledge_base')) {
                $this->forge->addColumn('knowledge_base', [$name => $conf]);
            }
        }

        // --- BẢNG workflow_templates ---
        // Sửa lỗi chính tả created_at? & code, drop case_type (Lines 575-581 mysql.sql)
        if ($this->db->fieldExists('created_at?', 'workflow_templates')) {
            $this->forge->modifyColumn('workflow_templates', [
                'created_at?' => [
                    'name' => 'created_at',
                    'type' => 'DATETIME',
                    'null' => true
                ]
            ]);
        }
        if ($this->db->fieldExists('case_type', 'workflow_templates')) {
            $this->forge->dropColumn('workflow_templates', 'case_type');
        }
        $this->forge->modifyColumn('workflow_templates', [
            'code' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => false
            ]
        ]);

        // --- BẢNG workflow_template_steps ---
        // Link NULL (Line 584 mysql.sql)
        $this->forge->modifyColumn('workflow_template_steps', [
            'responsible_role' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true
            ]
        ]);

        // ---------------------------------------------------------
        // 3. ĐỒNG BỘ DỮ LIỆU & QUYỀN HẠN (Từ seeding mysql.sql)
        // ---------------------------------------------------------
        
        // Roles: Thử việc & Học việc (Line 516 mysql.sql)
        $this->db->query("INSERT INTO roles (id, name, description, created_at) VALUES (6, 'Thử việc', 'Nhân sự đang thử việc', NOW()) ON DUPLICATE KEY UPDATE name=name");
        $this->db->query("INSERT INTO roles (id, name, description, created_at) VALUES (7, 'Học việc', 'Nhân sự đang học việc', NOW()) ON DUPLICATE KEY UPDATE name=name");

        // Đồng bộ quyền cho Role mới (Copy từ ID 5 - Thực tập sinh) bằng INSERT IGNORE để tránh lỗi trùng lặp/ambiguous
        $this->db->query("INSERT IGNORE INTO roles_permissions (role_id, permission_id) 
                          SELECT 6, permission_id FROM roles_permissions WHERE role_id = 5");
        $this->db->query("INSERT IGNORE INTO roles_permissions (role_id, permission_id) 
                          SELECT 7, permission_id FROM roles_permissions WHERE role_id = 5");

        // Data Fixes (Lines 663-676 mysql.sql)
        // Move content to problem in Knowledge Base
        $this->db->query("UPDATE knowledge_base SET problem = content WHERE problem IS NULL AND content IS NOT NULL");
        
        // Normalize Case Statuses
        $this->db->query("UPDATE cases SET status = 'cho_tiep_nhan' WHERE status IN ('moi_tiep_nhan', 'open', 'pending', '')");
        $this->db->query("UPDATE cases SET status = 'dang_xu_ly' WHERE status IN ('in_progress', 'cho_tham_tam')");
        $this->db->query("UPDATE cases SET status = 'da_hoan_thanh' WHERE status IN ('da_giai_quyet', 'dong_ho_so', 'closed')");
        $this->db->query("UPDATE cases SET status = 'huy' WHERE status IN ('cancelled')");
    }

    public function down()
    {
        // Tránh rollback gây mất dữ liệu thực tế
    }
}
