<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ConvertCustomerMonitoringStatusToMulti extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        if (!$db->fieldExists('monitoring_status', 'customers')) {
            return;
        }

        $this->forge->modifyColumn('customers', [
            'monitoring_status' => [
                'name' => 'monitoring_status',
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'JSON array cac trang thai giam sat chat luong tu van CSKH',
            ],
        ]);

        $rows = $db->table('customers')
            ->select('id, monitoring_status')
            ->where('monitoring_status IS NOT NULL')
            ->where('monitoring_status !=', '')
            ->get()
            ->getResultArray();

        foreach ($rows as $row) {
            $raw = trim((string) $row['monitoring_status']);
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                continue;
            }

            $db->table('customers')
                ->where('id', $row['id'])
                ->update(['monitoring_status' => json_encode([$raw], JSON_UNESCAPED_UNICODE)]);
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();

        if (!$db->fieldExists('monitoring_status', 'customers')) {
            return;
        }

        $rows = $db->table('customers')
            ->select('id, monitoring_status')
            ->where('monitoring_status IS NOT NULL')
            ->where('monitoring_status !=', '')
            ->get()
            ->getResultArray();

        foreach ($rows as $row) {
            $decoded = json_decode((string) $row['monitoring_status'], true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                continue;
            }

            $db->table('customers')
                ->where('id', $row['id'])
                ->update(['monitoring_status' => (string) ($decoded[0] ?? 'good')]);
        }

        $this->forge->modifyColumn('customers', [
            'monitoring_status' => [
                'name' => 'monitoring_status',
                'type' => 'VARCHAR',
                'constraint' => 80,
                'default' => 'good',
                'comment' => 'Trang thai giam sat chat luong tu van CSKH',
            ],
        ]);
    }
}
