<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePartnerCommissions extends Migration
{
    public function up()
    {
        $this->ensurePartnerPermissions();

        if (!$this->db->tableExists('partners')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'user_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                ],
                'name' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                ],
                'partner_type' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'default' => 'individual',
                ],
                'phone' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => true,
                ],
                'email' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                ],
                'tax_code' => [
                    'type' => 'VARCHAR',
                    'constraint' => 100,
                    'null' => true,
                ],
                'bank_name' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                ],
                'bank_account' => [
                    'type' => 'VARCHAR',
                    'constraint' => 100,
                    'null' => true,
                ],
                'bank_owner' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                ],
                'status' => [
                    'type' => 'VARCHAR',
                    'constraint' => 30,
                    'default' => 'active',
                ],
                'notes' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'deleted_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('user_id');
            $this->forge->addKey('status');
            $this->forge->addForeignKey('user_id', 'users', 'id', 'SET NULL', 'CASCADE');
            $this->forge->createTable('partners', true);
        }

        if (!$this->db->tableExists('case_partners')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'case_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                ],
                'partner_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                ],
                'role_label' => [
                    'type' => 'VARCHAR',
                    'constraint' => 100,
                    'default' => 'referrer',
                ],
                'calculation_base' => [
                    'type' => 'VARCHAR',
                    'constraint' => 30,
                    'default' => 'paid',
                ],
                'percentage' => [
                    'type' => 'DECIMAL',
                    'constraint' => '8,4',
                    'default' => 0,
                ],
                'fixed_amount' => [
                    'type' => 'BIGINT',
                    'default' => 0,
                ],
                'status' => [
                    'type' => 'VARCHAR',
                    'constraint' => 30,
                    'default' => 'active',
                ],
                'notes' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'deleted_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['case_id', 'partner_id']);
            $this->forge->addKey('status');
            $this->forge->addForeignKey('case_id', 'cases', 'id', 'CASCADE', 'CASCADE');
            $this->forge->addForeignKey('partner_id', 'partners', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('case_partners', true);
        }

        if (!$this->db->tableExists('partner_commission_entries')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'case_partner_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                ],
                'partner_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                ],
                'case_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                ],
                'payment_index' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 0,
                ],
                'payment_title' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                ],
                'payment_date' => [
                    'type' => 'DATE',
                    'null' => true,
                ],
                'calculation_base' => [
                    'type' => 'VARCHAR',
                    'constraint' => 30,
                    'default' => 'paid',
                ],
                'base_amount' => [
                    'type' => 'BIGINT',
                    'default' => 0,
                ],
                'percentage' => [
                    'type' => 'DECIMAL',
                    'constraint' => '8,4',
                    'default' => 0,
                ],
                'fixed_amount' => [
                    'type' => 'BIGINT',
                    'default' => 0,
                ],
                'commission_amount' => [
                    'type' => 'BIGINT',
                    'default' => 0,
                ],
                'status' => [
                    'type' => 'VARCHAR',
                    'constraint' => 30,
                    'default' => 'accrued',
                ],
                'request_note' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'admin_note' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'requested_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'approved_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'paid_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'deleted_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey(['case_partner_id', 'payment_index']);
            $this->forge->addKey(['partner_id', 'status']);
            $this->forge->addKey(['case_id', 'status']);
            $this->forge->addForeignKey('case_partner_id', 'case_partners', 'id', 'CASCADE', 'CASCADE');
            $this->forge->addForeignKey('partner_id', 'partners', 'id', 'CASCADE', 'CASCADE');
            $this->forge->addForeignKey('case_id', 'cases', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('partner_commission_entries', true);
        }
    }

    public function down()
    {
        $this->forge->dropTable('partner_commission_entries', true);
        $this->forge->dropTable('case_partners', true);
        $this->forge->dropTable('partners', true);
    }

    private function ensurePartnerPermissions(): void
    {
        $now = date('Y-m-d H:i:s');
        $permissions = [
            'partner.portal' => 'Doi tac xem cong no va gui yeu cau thanh toan',
            'partner.manage' => 'Quan ly doi tac va ty le hoa hong',
            'partner.payout' => 'Duyet va cap nhat chi tra hoa hong doi tac',
        ];

        foreach ($permissions as $name => $description) {
            $perm = $this->db->table('permissions')->where('name', $name)->get()->getRowArray();
            if (!$perm) {
                $data = [
                    'name' => $name,
                    'description' => $description,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                if ($this->db->fieldExists('module_group', 'permissions')) {
                    $data['module_group'] = 'Doi tac';
                }
                $this->db->table('permissions')->insert($data);
                $permId = (int)$this->db->insertID();
            } else {
                $permId = (int)$perm['id'];
            }

            $targetRoles = $name === 'partner.portal' ? [] : [1, 2];
            foreach ($targetRoles as $targetRoleId) {
                $exists = $this->db->table('roles_permissions')
                    ->where('role_id', $targetRoleId)
                    ->where('permission_id', $permId)
                    ->countAllResults();
                if ($exists == 0) {
                    $this->db->table('roles_permissions')->insert([
                        'role_id' => $targetRoleId,
                        'permission_id' => $permId,
                    ]);
                }
            }
        }
    }
}
