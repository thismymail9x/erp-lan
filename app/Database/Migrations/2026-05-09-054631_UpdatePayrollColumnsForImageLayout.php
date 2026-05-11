<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdatePayrollColumnsForImageLayout extends Migration
{
    public function up()
    {
        // 1. Thêm cột vào bảng employees để cấu hình cho từng nhân viên
        $this->forge->addColumn('employees', [
            'insurance_salary' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'default'    => 0,
                'after'      => 'salary_base',
                'comment'    => 'Lương đóng bảo hiểm'
            ],
            'diligence_allowance' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'default'    => 0,
                'after'      => 'allowance_base',
                'comment'    => 'Phụ cấp chuyên cần'
            ],
            'petrol_allowance' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'default'    => 0,
                'after'      => 'diligence_allowance',
                'comment'    => 'Phụ cấp xăng xe'
            ],
            'dependent_count' => [
                'type'       => 'INT',
                'default'    => 0,
                'after'      => 'petrol_allowance',
                'comment'    => 'Số người phụ thuộc'
            ],
        ]);

        // 2. Thêm cột vào bảng payrolls để lưu vết tính toán hàng tháng
        $this->forge->addColumn('payrolls', [
            'insurance_salary' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'default'    => 0,
                'after'      => 'salary_base',
                'comment'    => 'Lương đóng bảo hiểm'
            ],
            'salary_per_day' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'default'    => 0,
                'after'      => 'total_standard_days',
                'comment'    => 'Lương 1 ngày công'
            ],
            'taxable_income' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'default'    => 0,
                'after'      => 'actual_working_days',
                'comment'    => 'Lương theo ngày công làm việc (TNCT)'
            ],
            'diligence_allowance' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'default'    => 0,
                'after'      => 'salary_allowance',
                'comment'    => 'Phụ cấp chuyên cần'
            ],
            'petrol_allowance' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'default'    => 0,
                'after'      => 'diligence_allowance',
                'comment'    => 'Phụ cấp xăng xe'
            ],
            'si_employer' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'default'    => 0,
                'after'      => 'salary_bonus',
                'comment'    => 'BHXH vào chi phí (21.5%)'
            ],
            'si_employee' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'default'    => 0,
                'after'      => 'si_employer',
                'comment'    => 'BHXH trừ vào lương (10.5%)'
            ],
            'dependent_deduction' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'default'    => 0,
                'after'      => 'si_employee',
                'comment'    => 'Giảm trừ phụ thuộc'
            ],
            'pit_tax' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'default'    => 0,
                'after'      => 'dependent_deduction',
                'comment'    => 'Thuế TNCN'
            ],
            'total_deductions' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'default'    => 0,
                'after'      => 'pit_tax',
                'comment'    => 'Tổng cộng các khoản giảm trừ'
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('employees', ['insurance_salary', 'diligence_allowance', 'petrol_allowance', 'dependent_count']);
        $this->forge->dropColumn('payrolls', [
            'insurance_salary', 'salary_per_day', 'taxable_income', 
            'diligence_allowance', 'petrol_allowance', 'si_employer', 
            'si_employee', 'dependent_deduction', 'pit_tax', 'total_deductions'
        ]);
    }
}
