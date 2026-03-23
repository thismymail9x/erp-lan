<?php

namespace App\Controllers;

use App\Models\WorkflowTemplateModel;
use App\Models\WorkflowStepModel;
use Exception;

class WorkflowSeeder extends BaseController
{
    public function seed()
    {
        $templateModel = new WorkflowTemplateModel();
        $stepModel = new WorkflowStepModel();

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // 1. Template Xóa án tích
            $templateId = $templateModel->insert([
                'code' => 'XOA_AN_TICH_2026',
                'name' => 'Quy trình Xóa án tích (Tiêu chuẩn)',
                'case_type' => 'xoa_an_tich',
                'version' => 1,
                'is_active' => 1,
                'total_estimated_days' => 48,
                'created_by' => 1
            ]);

            $steps = [
                [
                    'step_order' => 1,
                    'step_name' => 'Xin bản án',
                    'duration_days' => 7,
                    'is_working_day_only' => 1,
                    'required_documents' => json_encode(['ban_sao_cccd', 'giay_uy_quyen']),
                    'responsible_role' => 'nhan_vien'
                ],
                [
                    'step_order' => 2,
                    'step_name' => 'Nộp án phí',
                    'duration_days' => 7,
                    'is_working_day_only' => 1,
                    'required_documents' => json_encode(['bien_lai_nop_an_phi']),
                    'responsible_role' => 'nhan_vien'
                ],
                [
                    'step_order' => 3,
                    'step_name' => 'Xác nhận chấp hành án',
                    'duration_days' => 7,
                    'is_working_day_only' => 1,
                    'required_documents' => json_encode(['giay_xac_nhan_thi_hanh_an']),
                    'responsible_role' => 'nhan_vien'
                ],
                [
                    'step_order' => 4,
                    'step_name' => 'Nộp hồ sơ CA thành phố',
                    'duration_days' => 2,
                    'is_working_day_only' => 1,
                    'required_documents' => json_encode(['don_yeu_cau_cap_phieu_lltp']),
                    'responsible_role' => 'nhan_vien'
                ],
                [
                    'step_order' => 5,
                    'step_name' => 'Xác nhận tại Xã/Phường',
                    'duration_days' => 10,
                    'is_working_day_only' => 1,
                    'required_documents' => json_encode(['giay_xac_nhan_hanh_kiem']),
                    'responsible_role' => 'nhan_vien'
                ],
                [
                    'step_order' => 6,
                    'step_name' => 'Bổ sung hồ sơ CA thành phố',
                    'duration_days' => 5,
                    'is_working_day_only' => 1,
                    'required_documents' => json_encode(['ho_so_bo_sung']),
                    'responsible_role' => 'nhan_vien'
                ],
                [
                    'step_order' => 7,
                    'step_name' => 'Nhận kết quả',
                    'duration_days' => 3,
                    'is_working_day_only' => 1,
                    'required_documents' => json_encode(['phiieu_ly_lich_tu_phap_so_2']),
                    'responsible_role' => 'nhan_vien'
                ]
            ];

            foreach ($steps as $step) {
                $step['template_id'] = $templateId;
                $stepModel->insert($step);
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new Exception("Transaction failed.");
            }

            echo "<h1>Seeding Success!</h1>";
        } catch (Exception $e) {
            $db->transRollback();
            echo "<h1>Seeding Failed!</h1>";
            echo "<pre>" . $e->getMessage() . "</pre>";
        }
    }
}
