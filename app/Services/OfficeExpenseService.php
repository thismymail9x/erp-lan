<?php

namespace App\Services;

use App\Models\OfficeExpenseModel;
use Config\AppConstants;

/**
 * Service xử lý chi phí vận hành công ty.
 *
 * Chi phí vận hành được tách khỏi chi phí vụ việc để kế toán nhập các khoản không
 * gắn khách hàng cụ thể, nhưng dashboard công ty vẫn có thể cộng hai nguồn khi cần.
 */
class OfficeExpenseService extends BaseService
{
    public const CATEGORY_LABELS = [
        'electricity' => 'Điện',
        'water'       => 'Nước',
        'internet'    => 'Internet / điện thoại',
        'rent'        => 'Mặt bằng / văn phòng',
        'stationery'  => 'Văn phòng phẩm',
        'maintenance' => 'Sửa chữa / bảo trì',
        'software'    => 'Phần mềm / dịch vụ',
        'tax_fee'     => 'Thuế / lệ phí',
        'salary_misc' => 'Phụ cấp / nhân sự',
        'other'       => 'Khác',
    ];

    public const PAYMENT_METHOD_LABELS = [
        'cash'     => 'Tiền mặt',
        'transfer' => 'Chuyển khoản',
        'card'     => 'Thẻ',
        'other'    => 'Khác',
    ];

    protected $model;
    protected $db;
    private array $allowedReceiptExtensions = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
    private int $maxReceiptBytes = 10485760;

    public function __construct(?OfficeExpenseModel $model = null)
    {
        parent::__construct();
        $this->model = $model ?? new OfficeExpenseModel();
        $this->db = \Config\Database::connect();
    }

    public function canView(): bool
    {
        return has_permission('sys.admin')
            || has_permission('office_expense.view')
            || has_permission('office_expense.manage')
            || session()->get('role_name') === AppConstants::ROLE_ADMIN
            || (int)session()->get('department_id') === AppConstants::DEPT_HANH_CHINH;
    }

    public function canManage(): bool
    {
        return has_permission('sys.admin')
            || has_permission('office_expense.manage')
            || session()->get('role_name') === AppConstants::ROLE_ADMIN
            || (int)session()->get('department_id') === AppConstants::DEPT_HANH_CHINH;
    }

    public function getDashboardData(array $filters = [], int $perPage = 20): array
    {
        $year = (int)($filters['year'] ?? date('Y'));
        $month = (int)($filters['month'] ?? 0);
        $category = $filters['category'] ?? '';
        $search = trim($filters['search'] ?? '');

        $builder = $this->model
            ->select('office_expenses.*, employees.full_name as creator_name')
            ->join('employees', 'employees.id = office_expenses.created_by', 'left')
            ->where('office_expenses.deleted_at', null);

        $this->applyFilters($builder, compact('year', 'month', 'category', 'search'));

        $rows = $builder->orderBy('office_expenses.expense_date', 'DESC')
            ->orderBy('office_expenses.id', 'DESC')
            ->paginate($perPage);

        return [
            'rows' => $rows,
            'pager' => $this->model->pager,
            'summary' => $this->getSummary($year, $month, $category, $search),
            'monthly' => $this->getMonthlySeries($year, $category),
            'previous_monthly' => $this->getMonthlySeries($year - 1, $category),
            'categories' => $this->getCategoryBreakdown($year, $month),
            'top_expenses' => $this->getTopExpenses($year, $month),
        ];
    }

    public function create(array $data, array $files = []): array
    {
        if (!$this->canManage()) {
            return $this->fail('Bạn chưa có quyền nhập chi phí vận hành.');
        }

        $amount = $this->parseMoney($data['amount'] ?? 0);
        if ($amount <= 0) {
            return $this->fail('Số tiền chi phí phải lớn hơn 0.');
        }

        $category = $data['category'] ?? '';
        if (!array_key_exists($category, self::CATEGORY_LABELS)) {
            return $this->fail('Loại chi phí không hợp lệ.');
        }

        $payload = [
            'expense_date' => $data['expense_date'] ?? date('Y-m-d'),
            'category' => $category,
            'vendor' => $data['vendor'] ?? null,
            'amount' => $amount,
            'payment_method' => $data['payment_method'] ?? 'cash',
            'note' => $data['note'] ?? null,
            'created_by' => (int)session()->get('employee_id'),
        ];

        $receiptFile = $files['receipt'] ?? null;
        $receiptValidation = $this->validateReceipt($receiptFile);
        if ($receiptValidation['status'] !== 'success') {
            return $receiptValidation;
        }

        $receipt = $this->storeReceipt($receiptFile);
        if ($receipt) {
            $payload = array_merge($payload, $receipt);
        }

        if (!$this->model->insert($payload)) {
            return $this->fail('Không thể lưu chi phí vận hành.');
        }

        return $this->success(['id' => (int)$this->model->getInsertID()], 'Đã ghi nhận chi phí vận hành.');
    }

    public function delete(int $id): array
    {
        if (!$this->canManage()) {
            return $this->fail('Bạn chưa có quyền xóa chi phí vận hành.');
        }

        $expense = $this->model->find($id);
        if (!$expense) {
            return $this->fail('Không tìm thấy chi phí vận hành.');
        }

        $this->model->delete($id);
        return $this->success(null, 'Đã xóa chi phí vận hành.');
    }

    private function getSummary(int $year, int $month, string $category, string $search): array
    {
        $current = $this->sumFor($year, $month, $category, $search);
        $previousYear = $this->sumFor($year - 1, $month, $category, $search);
        $currentMonth = $month > 0 ? $current : $this->sumFor($year, (int)date('n'), $category, $search);
        $previousMonth = $month > 1 ? $this->sumFor($year, $month - 1, $category, $search) : $this->sumFor($year - 1, 12, $category, $search);

        return [
            'total' => $current['total'],
            'count' => $current['count'],
            'average' => $current['count'] > 0 ? (int)round($current['total'] / $current['count']) : 0,
            'previous_year_total' => $previousYear['total'],
            'year_change_percent' => $this->percentChange($current['total'], $previousYear['total']),
            'current_month_total' => $currentMonth['total'],
            'previous_month_total' => $previousMonth['total'],
            'month_change_percent' => $this->percentChange($currentMonth['total'], $previousMonth['total']),
        ];
    }

    private function sumFor(int $year, int $month, string $category, string $search): array
    {
        $builder = $this->db->table('office_expenses')
            ->select('COALESCE(SUM(amount), 0) as total')
            ->select('COUNT(*) as count')
            ->where('deleted_at', null);

        $this->applyFilters($builder, compact('year', 'month', 'category', 'search'));
        $row = $builder->get()->getRowArray();

        return [
            'total' => (int)($row['total'] ?? 0),
            'count' => (int)($row['count'] ?? 0),
        ];
    }

    private function getMonthlySeries(int $year, string $category = ''): array
    {
        $rows = array_fill(1, 12, 0);
        $builder = $this->db->table('office_expenses')
            ->select('MONTH(expense_date) as month')
            ->select('COALESCE(SUM(amount), 0) as total')
            ->where('deleted_at', null)
            ->where('YEAR(expense_date)', $year)
            ->groupBy('MONTH(expense_date)')
            ->orderBy('MONTH(expense_date)', 'ASC');

        if ($category !== '') {
            $builder->where('category', $category);
        }

        foreach ($builder->get()->getResultArray() as $row) {
            $rows[(int)$row['month']] = (int)$row['total'];
        }

        return array_values($rows);
    }

    private function getCategoryBreakdown(int $year, int $month): array
    {
        $builder = $this->db->table('office_expenses')
            ->select('category')
            ->select('COALESCE(SUM(amount), 0) as total')
            ->where('deleted_at', null)
            ->where('YEAR(expense_date)', $year)
            ->groupBy('category')
            ->orderBy('total', 'DESC');

        if ($month > 0) {
            $builder->where('MONTH(expense_date)', $month);
        }

        return $builder->get()->getResultArray();
    }

    private function getTopExpenses(int $year, int $month): array
    {
        $builder = $this->db->table('office_expenses')
            ->select('office_expenses.*, employees.full_name as creator_name')
            ->join('employees', 'employees.id = office_expenses.created_by', 'left')
            ->where('office_expenses.deleted_at', null)
            ->where('YEAR(office_expenses.expense_date)', $year)
            ->orderBy('office_expenses.amount', 'DESC')
            ->limit(5);

        if ($month > 0) {
            $builder->where('MONTH(office_expenses.expense_date)', $month);
        }

        return $builder->get()->getResultArray();
    }

    private function applyFilters($builder, array $filters): void
    {
        if (!empty($filters['year'])) {
            $builder->where('YEAR(office_expenses.expense_date)', (int)$filters['year']);
        }
        if (!empty($filters['month'])) {
            $builder->where('MONTH(office_expenses.expense_date)', (int)$filters['month']);
        }
        if (!empty($filters['category'])) {
            $builder->where('office_expenses.category', $filters['category']);
        }
        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $builder->groupStart()
                ->like('office_expenses.vendor', $search)
                ->orLike('office_expenses.note', $search)
                ->groupEnd();
        }
    }

    private function percentChange(int $current, int $previous): ?float
    {
        if ($previous <= 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function storeReceipt($file): ?array
    {
        if (!$file || !$file->isValid() || $file->hasMoved()) {
            return null;
        }

        $targetDir = WRITEPATH . 'uploads/office_expenses';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }

        $newName = $file->getRandomName();
        $file->move($targetDir, $newName);

        return [
            'receipt_file_name' => $file->getClientName(),
            'receipt_file_path' => 'office_expenses/' . $newName,
            'receipt_file_type' => $file->getMimeType(),
        ];
    }

    private function parseMoney($value): int
    {
        return (int)preg_replace('/[^\d]/', '', (string)$value);
    }

    private function validateReceipt($file): array
    {
        if (!$file || (method_exists($file, 'getError') && $file->getError() === UPLOAD_ERR_NO_FILE)) {
            return $this->success();
        }

        if (!$file->isValid()) {
            return $this->fail('Chứng từ tải lên không hợp lệ.');
        }

        $extension = strtolower((string)$file->getClientExtension());
        if (!in_array($extension, $this->allowedReceiptExtensions, true)) {
            return $this->fail('Chứng từ chỉ nhận ảnh JPG, PNG, WEBP hoặc PDF.');
        }

        if ((int)$file->getSize() > $this->maxReceiptBytes) {
            return $this->fail('Chứng từ không được vượt quá 10MB.');
        }

        return $this->success();
    }
}
