<?php

namespace App\Services;

use Config\AppConstants;

class CaseAccountingAccess
{
    public const PERMISSION = 'case.accounting_own';

    public static function creatorSql(): string
    {
        return "(SELECT ch.user_id FROM case_history ch WHERE ch.case_id = cases.id AND ch.action = 'tiep_nhan' ORDER BY ch.id ASC LIMIT 1)";
    }

    public function canManage(?array $case = null): bool
    {
        if (has_permission('sys.admin') || session()->get('role_name') === AppConstants::ROLE_ADMIN
            || (int) session()->get('department_id') === AppConstants::DEPT_HANH_CHINH) {
            return true;
        }
        if (!has_permission(self::PERMISSION)) {
            return false;
        }
        if ($case === null) {
            return (int) session()->get('user_id') > 0;
        }
        $creator = $case['creator_user_id'] ?? null;
        if ($creator === null) {
            $history = (new \App\Models\CaseHistoryModel())->where('case_id', $case['id'])
                ->where('action', 'tiep_nhan')->orderBy('id', 'ASC')->first();
            $creator = $history['user_id'] ?? null;
        }
        return $this->ownsCase(true, (int) session()->get('user_id'), (int) $creator);
    }

    public function ownsCase(bool $granted, int $userId, int $creatorId): bool
    {
        return $granted && $userId > 0 && $creatorId > 0 && $userId === $creatorId;
    }

    public function normalizeInput(array $input): array
    {
        $data = [];
        if (array_key_exists('contract_value', $input)) {
            $value = str_replace([',', '.'], '', $input['contract_value']);
            $data['contract_value'] = $value === '' ? null : $value;
        }
        if (isset($input['payments']) && is_array($input['payments'])) {
            $payments = [];
            foreach ($input['payments'] as $payment) {
                $amount = str_replace(['.', ','], '', $payment['amount'] ?? '');
                if ($amount === '') continue;
                $hasDate = !empty($payment['paid_at']);
                $payment['amount'] = $amount;
                $payment['is_paid'] = (isset($payment['is_paid']) || $hasDate) ? 1 : 0;
                $payment['paid_at'] = $payment['is_paid'] && $hasDate ? date('Y-m-d H:i:s', strtotime($payment['paid_at'])) : null;
                $payment['is_vat'] = isset($payment['is_vat']) ? 1 : 0;
                $payments[] = $payment;
            }
            $data['payment_progress'] = $payments ? json_encode($payments, JSON_UNESCAPED_UNICODE) : null;
        } elseif (!empty($input['accounting_section'])) {
            $data['payment_progress'] = null;
        }
        return $data;
    }
}
