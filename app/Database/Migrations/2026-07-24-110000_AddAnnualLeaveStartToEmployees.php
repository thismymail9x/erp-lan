<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Add annual leave accrual start date to employee profiles.
 */
class AddAnnualLeaveStartToEmployees extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('annual_leave_start_date', 'employees')) {
            $this->forge->addColumn('employees', [
                'annual_leave_start_date' => [
                    'type'    => 'DATE',
                    'null'    => true,
                    'after'   => 'join_date',
                    'comment' => 'Ngay bat dau tinh phep nam cho vai tro Truong phong hoac Nhan vien chinh thuc',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('annual_leave_start_date', 'employees')) {
            $this->forge->dropColumn('employees', 'annual_leave_start_date');
        }
    }
}
