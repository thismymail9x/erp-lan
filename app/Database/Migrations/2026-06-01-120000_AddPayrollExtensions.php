<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration: AddPayrollExtensions
 * 
 * Bổ sung các cột hỗ trợ nghiệp vụ bảng lương nâng cao:
 * 
 * Bảng `employees`:
 *   - probation_rate:     Hệ số lương hiện tại (% so với lương CB). Ví dụ: 85 = 85% lương CB (thử việc)
 *   - probation_end_date: Ngày kết thúc giai đoạn thử việc/thực tập/học việc.
 *                         Khi ngày này rơi vào tháng đang tính lương → hệ thống tự chia 2 mức.
 *                         NULL = không có chuyển hạng trong kỳ tính.
 *   - new_rate_after:     Hệ số % lương áp dụng SAU ngày probation_end_date.
 * 
 * Bảng `payrolls`:
 *   - manual_adjust_days:   Số ngày công cộng thêm thủ công (Admin bù delay chấm công nhân viên mới).
 *   - probation_rate_snapshot: Snapshot hệ số % lương tại thời điểm tính lương (phục vụ tra cứu lịch sử).
 */
class AddPayrollExtensions extends Migration
{
    public function up()
    {
        // ===== BẢNG EMPLOYEES: Hệ số lương và thông tin chuyển hạng =====

        // Hệ số lương hiện tại (% lương cơ bản)
        // Mặc định 100 vì nhân viên chính thức được hưởng 100% lương cơ bản
        if (!$this->db->fieldExists('probation_rate', 'employees')) {
            $this->forge->addColumn('employees', [
                'probation_rate' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '5,2',
                    'default'    => 100.00,
                    'null'       => false,
                    'comment'    => 'Hệ số lương hiện tại (% lương cơ bản): 85=Thử việc, 40=Thực tập, 60=Học việc, 100=Chính thức',
                    'after'      => 'allowance_base',
                ]
            ]);
        }

        // Ngày kết thúc giai đoạn thử việc/thực tập/học việc
        // NULL = nhân viên đã là chính thức hoặc không có chuyển hạng trong kỳ tính
        if (!$this->db->fieldExists('probation_end_date', 'employees')) {
            $this->forge->addColumn('employees', [
                'probation_end_date' => [
                    'type'    => 'DATE',
                    'null'    => true,
                    'default' => null,
                    'comment' => 'Ngày kết thúc giai đoạn thử việc/thực tập. NULL = chính thức hoặc không chuyển hạng trong kỳ.',
                    'after'   => 'probation_rate',
                ]
            ]);
        }

        // Hệ số % lương áp dụng SAU ngày probation_end_date
        // Thường là 100 (chính thức), nhưng có thể là mức trung gian (ví dụ: hết thực tập → học việc)
        if (!$this->db->fieldExists('new_rate_after', 'employees')) {
            $this->forge->addColumn('employees', [
                'new_rate_after' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '5,2',
                    'default'    => 100.00,
                    'null'       => false,
                    'comment'    => 'Hệ số % lương áp dụng SAU ngày probation_end_date (thường là 100 khi chuyển sang chính thức)',
                    'after'      => 'probation_end_date',
                ]
            ]);
        }

        // ===== BẢNG PAYROLLS: Ngày công bù và snapshot hệ số =====
        
        // Cột salary_other để lưu các khoản truy lĩnh tự động hoặc điều chỉnh khác
        if (!$this->db->fieldExists('salary_other', 'payrolls')) {
            $this->forge->addColumn('payrolls', [
                'salary_other' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '15,2',
                    'default'    => 0.00,
                    'null'       => false,
                    'comment'    => 'Khoản điều chỉnh khác / truy lĩnh tự động',
                    'after'      => 'salary_bonus',
                ]
            ]);
        }

        // Số ngày công cộng thêm thủ công
        // Admin dùng để bù delay chấm công của nhân viên mới (thường mất 1 ngày chờ cấp quyền app)
        if (!$this->db->fieldExists('manual_adjust_days', 'payrolls')) {
            $this->forge->addColumn('payrolls', [
                'manual_adjust_days' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '5,2',
                    'default'    => 0.00,
                    'null'       => false,
                    'comment'    => 'Số ngày công cộng thêm thủ công (Admin bù delay chấm công nhân viên mới)',
                    'after'      => 'actual_working_days',
                ]
            ]);
        }

        // Snapshot hệ số lương tại thời điểm tính lương
        // Lưu lại để Admin có thể tra cứu lịch sử: "Tháng đó NV này đang ở mức hệ số bao nhiêu?"
        if (!$this->db->fieldExists('probation_rate_snapshot', 'payrolls')) {
            $this->forge->addColumn('payrolls', [
                'probation_rate_snapshot' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '5,2',
                    'default'    => 100.00,
                    'null'       => false,
                    'comment'    => 'Snapshot hệ số % lương tại thời điểm tính lương (phục vụ tra cứu lịch sử, không dùng để tính lại)',
                    'after'      => 'manual_adjust_days',
                ]
            ]);
        }
    }

    public function down()
    {
        // Rollback: Xóa các cột đã thêm vào employees
        $this->forge->dropColumn('employees', ['probation_rate', 'probation_end_date', 'new_rate_after']);

        // Rollback: Xóa các cột đã thêm vào payrolls
        $this->forge->dropColumn('payrolls', ['manual_adjust_days', 'probation_rate_snapshot', 'salary_other']);
    }
}
