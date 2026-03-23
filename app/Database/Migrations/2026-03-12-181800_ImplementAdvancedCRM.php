<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ImplementAdvancedCRM extends Migration
{
    public function up()
    {
        // 1. Alter Customers Table
        // CodeIgniter Forge doesn't support adding many columns with AFTER easily in one call 
        // while preserving comments and specific types as cleanly as raw SQL, 
        // but we'll use forge as much as possible for standard CI4 practice.
        
        $fields = [
            'code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'unique'     => true,
                'after'      => 'id'
            ],
            'date_of_birth' => [
                'type' => 'DATE',
                'null' => true,
                'after' => 'name'
            ],
            'gender' => [
                'type'       => 'ENUM',
                'values'     => ['nam', 'nu', 'khac'],
                'default'    => 'khac',
                'after'      => 'date_of_birth'
            ],
            'identity_type' => [
                'type'       => 'ENUM',
                'values'     => ['cccd', 'cmnd', 'passport'],
                'default'    => 'cccd',
                'after'      => 'gender'
            ],
            'issue_date' => [
                'type' => 'DATE',
                'null' => true,
                'after' => 'identity_number'
            ],
            'expiry_date' => [
                'type' => 'DATE',
                'null' => true,
                'after' => 'issue_date'
            ],
            'issued_by' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'expiry_date'
            ],
            'phone_secondary' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
                'after'      => 'phone'
            ],
            'email_secondary' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'email'
            ],
            'address_json' => [
                'type' => 'JSON',
                'null' => true,
                'after' => 'address'
            ],
            'company_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after' => 'address_json'
            ],
            'biz_registration_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after' => 'tax_code'
            ],
            'rep_position' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after' => 'representative'
            ],
            'tags' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'rep_position'
            ],
            'source' => [
                'type'       => 'ENUM',
                'values'     => ['facebook', 'zalo', 'google', 'gioi_thieu', 'website', 'khac'],
                'default'    => 'khac',
                'after'      => 'tags'
            ],
            'referred_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'source'
            ],
            'is_blacklist' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'after'      => 'referred_by'
            ],
            'blacklist_reason' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'is_blacklist'
            ],
            'total_revenue' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'default'    => 0,
                'after'      => 'blacklist_reason'
            ],
            'total_cases' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
                'after'      => 'total_revenue'
            ],
            'success_rate' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'default'    => 0,
                'after'      => 'total_cases'
            ],
            'last_contact_date' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'success_rate'
            ],
            'notes_internal' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'last_contact_date'
            ]
        ];
        
        // Identity number might already exist if previously added by a smaller migration
        // We'll use raw query or check existence for safety if needed, 
        // but since this is a clean implementation of the new module, we'll assume it's part of the forge.
        $this->forge->addColumn('customers', $fields);

        // 2. Customer Interactions
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'customer_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'channel' => [
                'type'       => 'ENUM',
                'values'     => ['call', 'zalo', 'email', 'meeting', 'facebook', 'khac'],
            ],
            'interaction_date' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'summary' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'detailed_content' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'next_follow_up' => [
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
        $this->forge->addForeignKey('customer_id', 'customers', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('customer_interactions');

        // 3. Customer Documents
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'customer_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'document_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'file_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'file_path' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'uploaded_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
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
        $this->forge->addForeignKey('customer_id', 'customers', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('customer_documents');

        // 4. Customer Payments
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'customer_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'case_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
            ],
            'payment_date' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'method' => [
                'type'       => 'ENUM',
                'values'     => ['transfer', 'cash', 'card', 'khac'],
                'default'    => 'transfer',
            ],
            'description' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
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
        $this->forge->addForeignKey('customer_id', 'customers', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('customer_payments');
    }

    public function down()
    {
        $this->forge->dropTable('customer_payments');
        $this->forge->dropTable('customer_documents');
        $this->forge->dropTable('customer_interactions');
        
        $this->forge->dropColumn('customers', [
            'code', 'date_of_birth', 'gender', 'identity_type', 'issue_date', 'expiry_date', 'issued_by',
            'phone_secondary', 'email_secondary', 'address_json', 'company_name', 'biz_registration_number',
            'rep_position', 'tags', 'source', 'referred_by', 'is_blacklist', 'blacklist_reason',
            'total_revenue', 'total_cases', 'success_rate', 'last_contact_date', 'notes_internal'
        ]);
    }
}
