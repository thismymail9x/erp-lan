<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddGiftStatusToCustomers extends Migration
{
    public function up()
    {
        $fields = [
            'has_received_gift' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'null'       => false,
                'after'      => 'care_status',
                'comment'    => 'Trang thai qua tang cua khach hang: 0 chua tang, 1 da tang',
            ],
        ];

        $this->forge->addColumn('customers', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('customers', 'has_received_gift');
    }
}
