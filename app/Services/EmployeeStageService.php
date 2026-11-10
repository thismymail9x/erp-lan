<?php

namespace App\Services;

use App\Models\EmployeeModel;
use Config\AppConstants;

class EmployeeStageService
{
    public function resolve(array $employee, string $date): array
    {
        $initial = trim((string) ($employee['initial_position'] ?? ''));
        if ($initial === '') {
            $initial = trim((string) ($employee['role_name'] ?? $employee['position'] ?? ''));
        }
        $rates = AppConstants::PROBATION_RATE_DEFAULT;
        if (!array_key_exists($initial, $rates)) {
            // An unknown title is not evidence that an employee has become official.
            return $employee;
        }
        $rate = (float) $rates[$initial];
        $start = $employee['join_date'] ?? null;
        if (!$start) {
            return $employee;
        }
        foreach (AppConstants::PROBATION_STAGE_POLICY as $stage) {
            if ((float) $stage['rate'] < $rate) {
                continue;
            }
            $month = (new \DateTimeImmutable(substr($start, 0, 7) . '-01'))
                ->modify('+' . $stage['duration_months'] . ' months');
            $day = (int) substr($start, 8, 2);
            $lastDay = (int) $month->format('t');
            $boundary = $month->setDate((int) $month->format('Y'), (int) $month->format('m'), min($day, $lastDay));
            $end = ($day <= $lastDay ? $boundary->modify('-1 day') : $boundary)->format('Y-m-d');
            if ($date <= $end) {
                return array_merge($employee, [
                    'probation_rate' => (float) $stage['rate'],
                    'probation_end_date' => $end,
                    'new_rate_after' => (float) $stage['next_rate'],
                ]);
            }
            $start = (new \DateTimeImmutable($end))->modify('+1 day')->format('Y-m-d');
        }
        return array_merge($employee, [
            'probation_rate' => 100.0, 'probation_end_date' => null, 'new_rate_after' => 100.0,
        ]);
    }

    public function synchronize(?int $employeeId = null): void
    {
        $db = \Config\Database::connect();
        $query = $db->table('employees e')->select('e.*, r.name as role_name, u.role_id')
            ->join('users u', 'u.id = e.user_id')->join('roles r', 'r.id = u.role_id')
            ->where('e.deleted_at', null)->where('u.deleted_at', null)->where('u.active_status', 1);
        if ($employeeId !== null) {
            $query->where('e.id', $employeeId);
        }
        foreach ($query->get()->getResultArray() as $employee) {
            if (empty($employee['join_date']) || $employee['join_date'] > date('Y-m-d')) {
                continue;
            }
            // Retain the entry role before changing the current role, so the clock never restarts.
            $initial = $employee['initial_position'] ?: $this->findEntryPosition($employee);
            $resolved = $this->resolve(array_merge($employee, ['initial_position' => $initial]), date('Y-m-d'));
            if (!array_key_exists($initial, AppConstants::PROBATION_RATE_DEFAULT)) {
                continue;
            }
            $data = array_intersect_key($resolved, array_flip(['probation_rate', 'probation_end_date', 'new_rate_after']));
            $data['initial_position'] = $initial;
            $stageRoles = [AppConstants::ROLE_THUC_TAP_SINH, AppConstants::ROLE_HOC_VIEC, AppConstants::ROLE_THU_VIEC, AppConstants::ROLE_NHAN_VIEN_CHINH];
            $target = array_search($resolved['probation_rate'], AppConstants::PROBATION_RATE_DEFAULT);
            if (in_array($employee['position'], $stageRoles, true)) {
                $data['position'] = $target;
            }
            $role = in_array($employee['role_name'], $stageRoles, true)
                ? $db->table('roles')->where('name', $target)->where('deleted_at', null)->get()->getRowArray() : null;
            $changed = array_diff_assoc($data, $employee);
            $roleChanged = $role && (int) $role['id'] !== (int) $employee['role_id'];
            if (!$changed && !$roleChanged) {
                continue;
            }
            $db->transStart();
            if (!(new EmployeeModel())->update($employee['id'], $data)) {
                $db->transRollback();
                throw new \RuntimeException('Unable to synchronize employee stage.');
            }
            if ($roleChanged) {
                $db->table('users')->where('id', $employee['user_id'])->update(['role_id' => $role['id']]);
            }
            (new SystemLogService())->log('AUTO_STAGE', 'Employees', (int) $employee['id'], [
                'initial_position' => $initial, 'join_date' => $employee['join_date'],
                'before' => array_intersect_key($employee, $data), 'after' => $data,
                'role_before' => $employee['role_name'], 'role_after' => $role['name'] ?? $employee['role_name'],
            ]);
            $db->transComplete();
            if (!$db->transStatus()) {
                throw new \RuntimeException('Unable to synchronize employee stage.');
            }
        }
    }

    public function findEntryPosition(array $employee): string
    {
        $db = \Config\Database::connect();
        // The earliest recorded previous title is stronger evidence than today's title.
        $logs = $db->table('system_logs')->where('module', 'Employees')->where('entity_id', $employee['id'])
            ->orderBy('id', 'ASC')->get()->getResultArray();
        foreach ($logs as $log) {
            $details = json_decode($log['details'] ?? '[]', true) ?: [];
            $position = $details['initial_position'] ?? $details['before']['initial_position']
                ?? $details['before']['position'] ?? $details['position'] ?? null;
            if ($position && array_key_exists($position, AppConstants::PROBATION_RATE_DEFAULT)) {
                return $position;
            }
        }
        if (array_key_exists($employee['position'], AppConstants::PROBATION_RATE_DEFAULT)) {
            return $employee['position'];
        }
        $userRole = $db->table('users u')->select('r.name')->join('roles r', 'r.id = u.role_id')
            ->where('u.id', $employee['user_id'])->get()->getRowArray();
        return $employee['role_name'] ?? $userRole['name'] ?? $employee['position'];
    }
}
