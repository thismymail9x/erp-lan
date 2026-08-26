<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Bổ sung dữ liệu CRM quan hệ khách hàng giai đoạn 1.
 */
class AddCustomerRelationshipCrm extends Migration
{
    public function up()
    {
        $fields = [];

        if (!$this->db->fieldExists('relationship_level', 'customers')) {
            $fields['relationship_level'] = [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false,
                'default' => 'lead',
                'comment' => 'Cấp độ quan hệ: lead, active, loyal, strategic',
                'after' => 'care_status',
            ];
        }

        if (!$this->db->fieldExists('relationship_score', 'customers')) {
            $fields['relationship_score'] = [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
                'default' => 0,
                'comment' => 'Điểm quan hệ khách hàng từ 0 đến 100',
                'after' => 'relationship_level',
            ];
        }

        if (!$this->db->fieldExists('relationship_status', 'customers')) {
            $fields['relationship_status'] = [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'null' => false,
                'default' => 'healthy',
                'comment' => 'Trạng thái quan hệ: healthy, watch, risk, critical',
                'after' => 'relationship_score',
            ];
        }

        if (!$this->db->fieldExists('health_score', 'customers')) {
            $fields['health_score'] = [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
                'default' => 0,
                'comment' => 'Điểm sức khỏe khách hàng từ 0 đến 100',
                'after' => 'relationship_status',
            ];
        }

        if (!$this->db->fieldExists('next_interaction_date', 'customers')) {
            $fields['next_interaction_date'] = [
                'type' => 'DATE',
                'null' => true,
                'comment' => 'Ngày dự kiến tương tác kế tiếp với khách hàng',
                'after' => 'last_contact_date',
            ];
        }

        if (!$this->db->fieldExists('relationship_manager_id', 'customers')) {
            $fields['relationship_manager_id'] = [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'comment' => 'Nhân sự quản lý quan hệ khách hàng',
                'after' => 'assigned_care_staff_id',
            ];
        }

        if (!$this->db->fieldExists('referred_by_customer_id', 'customers')) {
            $fields['referred_by_customer_id'] = [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'comment' => 'Khách hàng đã giới thiệu khách hàng này',
                'after' => 'referred_by',
            ];
        }

        if (!$this->db->fieldExists('referral_score', 'customers')) {
            $fields['referral_score'] = [
                'type' => 'TINYINT',
                'constraint' => 3,
                'null' => false,
                'default' => 0,
                'comment' => 'Điểm tiềm năng giới thiệu từ 0 đến 100',
                'after' => 'referral_count',
            ];
        }

        if (!$this->db->fieldExists('interests', 'customers')) {
            $fields['interests'] = [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Mối quan tâm, sở thích, nhu cầu thường gặp của khách hàng',
                'after' => 'occupation',
            ];
        }

        if (!$this->db->fieldExists('identified_issues', 'customers')) {
            $fields['identified_issues'] = [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Vấn đề pháp lý hoặc nhu cầu đã được nhận diện',
                'after' => 'interests',
            ];
        }

        if (!empty($fields)) {
            $this->forge->addColumn('customers', $fields);
        }

        if (!$this->db->fieldExists('interaction_result', 'customer_interactions')) {
            $this->forge->addColumn('customer_interactions', [
                'interaction_result' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => true,
                    'comment' => 'Kết quả tương tác: positive, neutral, negative, no_response',
                    'after' => 'summary',
                ],
                'importance_level' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => false,
                    'default' => 'normal',
                    'comment' => 'Mức độ quan trọng: low, normal, high, urgent',
                    'after' => 'interaction_result',
                ],
                'requires_follow_up' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'null' => false,
                    'default' => 0,
                    'comment' => 'Đánh dấu tương tác cần theo dõi lại',
                    'after' => 'importance_level',
                ],
            ]);
        }

        if (!$this->db->tableExists('customer_opportunities')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                    'comment' => 'Khóa chính cơ hội phát triển dịch vụ',
                ],
                'customer_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'comment' => 'Khách hàng sở hữu cơ hội',
                ],
                'issue_title' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'comment' => 'Tên vấn đề hoặc nhu cầu khách hàng',
                ],
                'issue_description' => [
                    'type' => 'TEXT',
                    'null' => true,
                    'comment' => 'Mô tả chi tiết vấn đề đã phát hiện',
                ],
                'service_suggestion' => [
                    'type' => 'TEXT',
                    'null' => true,
                    'comment' => 'Dịch vụ hoặc giải pháp đề xuất',
                ],
                'estimated_value' => [
                    'type' => 'DECIMAL',
                    'constraint' => '15,2',
                    'default' => 0,
                    'comment' => 'Giá trị doanh thu dự kiến của cơ hội',
                ],
                'probability' => [
                    'type' => 'TINYINT',
                    'constraint' => 3,
                    'default' => 0,
                    'comment' => 'Xác suất chuyển đổi phần trăm',
                ],
                'assigned_staff_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                    'comment' => 'Nhân sự phụ trách theo dõi cơ hội',
                ],
                'discovered_at' => [
                    'type' => 'DATE',
                    'null' => true,
                    'comment' => 'Ngày phát hiện cơ hội',
                ],
                'follow_up_date' => [
                    'type' => 'DATE',
                    'null' => true,
                    'comment' => 'Ngày cần theo dõi cơ hội',
                ],
                'stage' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'default' => 'detected',
                    'comment' => 'Giai đoạn cơ hội: detected, consulting, quoted, won, lost',
                ],
                'status' => [
                    'type' => 'VARCHAR',
                    'constraint' => 30,
                    'default' => 'active',
                    'comment' => 'Trạng thái cơ hội: active, won, lost, paused',
                ],
                'source_type' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'default' => 'manual',
                    'comment' => 'Nguồn phát hiện cơ hội: manual, interaction, referral, case',
                ],
                'created_by' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                    'comment' => 'Nhân sự tạo cơ hội',
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'comment' => 'Thời điểm tạo bản ghi',
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'comment' => 'Thời điểm cập nhật bản ghi',
                ],
                'deleted_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'comment' => 'Thời điểm xóa mềm',
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['customer_id', 'status'], false, false, 'idx_customer_opportunities_customer_status');
            $this->forge->addKey('follow_up_date', false, false, 'idx_customer_opportunities_follow_up');
            $this->forge->addForeignKey('customer_id', 'customers', 'id', 'CASCADE', 'CASCADE');
            $this->forge->addForeignKey('assigned_staff_id', 'employees', 'id', 'SET NULL', 'CASCADE');
            $this->forge->createTable('customer_opportunities', true);
        }
    }

    public function down()
    {
        $this->forge->dropTable('customer_opportunities', true);

        foreach (['interaction_result', 'importance_level', 'requires_follow_up'] as $field) {
            if ($this->db->fieldExists($field, 'customer_interactions')) {
                $this->forge->dropColumn('customer_interactions', $field);
            }
        }

        $customerFields = [
            'relationship_level',
            'relationship_score',
            'relationship_status',
            'health_score',
            'next_interaction_date',
            'relationship_manager_id',
            'referred_by_customer_id',
            'referral_score',
            'interests',
            'identified_issues',
        ];

        foreach ($customerFields as $field) {
            if ($this->db->fieldExists($field, 'customers')) {
                $this->forge->dropColumn('customers', $field);
            }
        }
    }
}
