<?php

namespace App\Services;

use App\Models\CustomerModel;
use App\Models\CustomerMonitoringStatusSettingModel;
use Config\Services;

class CustomerMonitoringStatusService extends BaseService
{
    protected CustomerModel $customerModel;
    protected CustomerMonitoringStatusSettingModel $settingModel;

    public function __construct()
    {
        parent::__construct();
        $this->customerModel = new CustomerModel();
        $this->settingModel = new CustomerMonitoringStatusSettingModel();
    }

    public function getSettings(bool $activeOnly = false): array
    {
        $builder = $this->settingModel
            ->where('deleted_at', null)
            ->orderBy('sort_order', 'ASC');

        if ($activeOnly) {
            $builder->where('is_active', 1);
        }

        return $builder->findAll();
    }

    public function saveSetting(array $data): array
    {
        $validation = Services::validation();
        $validation->setRules([
            'id'          => 'permit_empty|numeric',
            'status_key'  => 'required|alpha_dash|max_length[80]',
            'status_name' => 'required|min_length[3]|max_length[150]',
            'color'       => 'required|max_length[20]',
            'sort_order'  => 'permit_empty|numeric',
            'is_active'   => 'permit_empty|in_list[0,1]',
        ]);

        if (!$validation->run($data)) {
            return $this->fail('Du lieu cau hinh trang thai giam sat khong hop le: ' . implode(' ', $validation->getErrors()));
        }

        if (empty($data['id'])) {
            unset($data['id']);
            $data['created_at'] = date('Y-m-d H:i:s');
        }

        $data['updated_at'] = date('Y-m-d H:i:s');
        $data['is_active']  = isset($data['is_active']) ? (int) $data['is_active'] : 1;
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        if ($this->settingModel->save($data)) {
            return $this->success(null, 'Da luu cau hinh trang thai giam sat thanh cong.');
        }

        return $this->fail('Khong the luu cau hinh trang thai giam sat: ' . implode(' ', $this->settingModel->errors()));
    }

    public function deleteSetting(int $id): array
    {
        $setting = $this->settingModel->find($id);
        if (!$setting) {
            return $this->fail('Trang thai giam sat khong ton tai.');
        }

        if ($this->settingModel->delete($id)) {
            return $this->success(null, 'Da xoa trang thai giam sat thanh cong.');
        }

        return $this->fail('Khong the xoa trang thai giam sat.');
    }

    public function normalizeStatusKeys($statusKeys): array
    {
        if (is_string($statusKeys)) {
            $statusKeys = trim($statusKeys);
            $decoded = json_decode($statusKeys, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $statusKeys = $decoded;
            } elseif (strpos($statusKeys, ',') !== false) {
                $statusKeys = explode(',', $statusKeys);
            } elseif ($statusKeys !== '') {
                $statusKeys = [$statusKeys];
            } else {
                $statusKeys = [];
            }
        }

        if (!is_array($statusKeys)) {
            $statusKeys = [];
        }

        $normalized = [];
        foreach ($statusKeys as $key) {
            $key = trim((string) $key);
            if ($key !== '' && preg_match('/^[A-Za-z0-9_-]{1,80}$/', $key) && !in_array($key, $normalized, true)) {
                $normalized[] = $key;
            }
        }

        if (empty($normalized)) {
            return ['good'];
        }

        if (count($normalized) > 1 && in_array('good', $normalized, true)) {
            $normalized = array_values(array_filter($normalized, static fn ($key) => $key !== 'good'));
        }

        return empty($normalized) ? ['good'] : $normalized;
    }

    public function updateCustomerStatus(int $customerId, $statusKeys): array
    {
        $statusKeys = $this->normalizeStatusKeys($statusKeys);

        $settings = $this->settingModel
            ->whereIn('status_key', $statusKeys)
            ->where('is_active', 1)
            ->where('deleted_at', null)
            ->orderBy('sort_order', 'ASC')
            ->findAll();

        if (count($settings) !== count($statusKeys)) {
            return $this->fail('Trang thai giam sat khong hop le hoac da bi khoa.');
        }

        $updated = $this->customerModel->update($customerId, [
            'monitoring_status' => json_encode($statusKeys, JSON_UNESCAPED_UNICODE),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        if (!$updated) {
            return $this->fail('Khong the cap nhat trang thai giam sat.');
        }

        return $this->success([
            'status_keys' => $statusKeys,
            'statuses'    => array_map(static fn ($setting) => [
                'status_key'  => $setting['status_key'],
                'status_name' => $setting['status_name'],
                'color'       => $setting['color'],
            ], $settings),
            'status_key'  => $settings[0]['status_key'] ?? 'good',
            'status_name' => $settings[0]['status_name'] ?? 'Good',
            'color'       => $settings[0]['color'] ?? '#34c759',
        ], 'Da cap nhat trang thai giam sat.');
    }
}
