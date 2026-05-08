<?php

namespace App\Controllers;

use App\Models\KnowledgeModel;
use App\Models\CaseModel;
use App\Services\SystemLogService;
use App\Services\TagService;
use App\Services\NotificationService;

/**
 * KnowledgeController
 * 
 * Quản lý tính năng Cẩm nang tri thức nội bộ. Cung cấp không gian cho nhân sự chia sẻ
 * kinh nghiệm độc lập hoặc bài học từ các bộ hồ sơ/vụ việc pháp lý thực tế.
 */
class KnowledgeController extends BaseController
{
    /**
     * Khai báo metadata cho hệ thống Tự động Đồng bộ (Auto-Sync Permissions).
     * Dùng cho cỗ máy quét tại: /perm-fix/sync
     */
    public static $modulePermissions = [
        'group' => 'Hệ thống',
        'permissions' => [
            'knowledge.view'   => 'Xem và tham khảo Cẩm nang tri thức nội bộ',
            'knowledge.manage' => 'Tư cách Tác giả: Tạo và quản trị bài học kinh nghiệm'
        ]
    ];

    /**
     * Khai báo danh mục thuộc thể loại Nhãn dán (Smart Tags).
     * Dùng cho cỗ máy quét tại: /perm-fix/sync
     */
    public static $taggable = [
        'type'  => 'knowledge',
        'label' => 'Cẩm nang tri thức'
    ];

    protected $knowledgeModel;
    protected $tagService;
    protected $logService;
    protected $notifyService;

    public function __construct()
    {
        $this->knowledgeModel = new KnowledgeModel();
        $this->tagService = new TagService();
        $this->logService = new SystemLogService();
        $this->notifyService = new NotificationService();
    }

    /**
     * Màn hình chính của Cẩm nang (News Feed dạng Diễn đàn).
     * Mọi nhân sự đều xem được.
     */
    public function index()
    {
        if (!session()->has('isLoggedIn')) { return redirect()->to('/login'); }

        $category = $this->request->getGet('category');
        $tagId = $this->request->getGet('tag_id');
        $search = $this->request->getGet('search');

        $db = \Config\Database::connect();
        $builder = $db->table('knowledge_base kb');
        $builder->select('kb.*, e.full_name as author_name, e.position as author_position, c.title as case_title, c.code as case_code');
        $builder->join('employees e', 'e.id = kb.author_id', 'inner');
        $builder->join('cases c', 'c.id = kb.case_id', 'left');
        $builder->where('kb.deleted_at IS NULL');

        if (!empty($category)) {
            $builder->where('kb.category', $category);
        }

        if (!empty($search)) {
            $builder->groupStart()
                ->like('kb.title', $search)
                ->orLike('kb.content', $search)
            ->groupEnd();
        }

        // Lọc theo cấu trúc Bridge của Tag
        if (!empty($tagId)) {
            $builder->join('entity_tags et', 'et.entity_id = kb.id', 'inner');
            $builder->where('et.entity_type', 'knowledge');
            $builder->where('et.tag_id', $tagId);
        }

        $builder->orderBy('kb.is_pinned', 'DESC');
        $builder->orderBy('kb.created_at', 'DESC');

        // Phân trang Pagination
        $perPage = 20;
        $page = $this->request->getGet('page') ?? 1;
        $total = $builder->countAllResults(false);
        $articles = $builder->limit($perPage, ($page - 1) * $perPage)->get()->getResultArray();

        // Gắn danh sách tags cho từng bài viết để hiển thị
        foreach ($articles as &$article) {
            $article['tags'] = $this->tagService->getTagsByEntity($article['id'], 'knowledge');
        }

        // Bảng xếp hạng: Chuyên gia chia sẻ của tháng (Dựa trên số lượt Helpful trong tháng)
        $leaderboard = $db->table('knowledge_base kb')
            ->select('e.full_name, e.position, SUM(kb.helpful_count) as total_helpful')
            ->join('employees e', 'e.id = kb.author_id', 'inner')
            ->where('kb.created_at >=', date('Y-m-01 00:00:00'))
            ->groupBy('kb.author_id')
            ->orderBy('total_helpful', 'DESC')
            ->limit(5)
            ->get()->getResultArray();

        $data = [
            'title'        => 'Cẩm nang nghiệp vụ nội bộ | L.A.N ERP',
            'articles'     => $articles,
            'total'        => $total,
            'pager'        => \Config\Services::pager(),
            'currentPage'  => $page,
            'leaderboard'  => $leaderboard,
            'perPage'      => $perPage,
            'availableTags'=> get_available_tags('knowledge') // Nạp danh mục tag từ Core Function
        ];

        if ($this->request->isAJAX()) {
            return view('dashboard/knowledge/index_feed', $data);
        }

        return view('dashboard/knowledge/index', $data);
    }

    /**
     * Giao diện tạo bài viết / chia sẻ kinh nghiệm mới.
     */
    public function create()
    {
        if (!session()->has('isLoggedIn')) { return redirect()->to('/login'); }

        $caseId = $this->request->getGet('case_id');
        $caseInfo = null;
        
        if ($caseId) {
            $caseModel = new CaseModel();
            $caseInfo = $caseModel->find($caseId);
        }

        $data = [
            'title'         => 'Đóng góp Tri thức/Kinh nghiệm mới',
            'caseInfo'      => $caseInfo,
            'availableTags' => get_available_tags('knowledge') // Sử dụng Core function cho đồng bộ
        ];

        return view('dashboard/knowledge/create', $data);
    }

    /**
     * Bắt dữ liệu và Push vào Database.
     */
    public function store()
    {
        if (!session()->has('isLoggedIn')) { return redirect()->to('/login'); }

        $input = $this->request->getPost();
        
        $input['author_id'] = session()->get('employee_id');
        $input['case_id'] = !empty($input['case_id']) ? $input['case_id'] : null;
        
        // Tự động gộp 3 phần thành content (để phục vụ tìm kiếm/legacy)
        $input['content'] = "<h3>Vấn đề:</h3>" . ($input['problem'] ?? '') . 
                            "<h3>Cách giải quyết:</h3>" . ($input['solution'] ?? '') . 
                            "<h3>Lưu ý:</h3>" . ($input['red_flags'] ?? '');

        // Cơ chế chặn Spam hoặc SQL Injection được bảo trợ qua Model validation
        if ($this->knowledgeModel->save($input)) {
            $knowledgeId = $this->knowledgeModel->getInsertID();

            // Link Tags (Multi-modal)
            $tags = $this->request->getPost('tags');
            if (is_array($tags) && !empty($tags)) {
                $this->tagService->syncTags($knowledgeId, 'knowledge', $tags);
            }

            // [NEW] Thông báo cho toàn thể nhân viên khi có kinh nghiệm mới
            $this->notifyService->notifyAllEmployees(
                "Kinh nghiệm mới: " . $input['title'],
                session()->get('full_name') . " vừa chia sẻ một kinh nghiệm mới: " . ($input['summary'] ?? $input['title']),
                'system',
                base_url('knowledge/show/' . $knowledgeId)
            );

            // Ghi nhận Audit Log
            $this->logService->log('CREATE', 'KnowledgeBase', $knowledgeId, ['title' => $input['title']]);

            return redirect()->to(base_url('knowledge'))->with('success', 'Tuyệt vời! Bạn đã chia sẻ thành công một bài học kinh nghiệm giá trị.');
        }

        return redirect()->back()->withInput()->with('errors', $this->knowledgeModel->errors());
    }

    /**
     * Xem hiển thị đầy đủ của bài Kinh Nghiệm
     */
    public function show($id)
    {
        if (!session()->has('isLoggedIn')) { return redirect()->to('/login'); }

        $article = $this->knowledgeModel->find($id);
        if (!$article) {
             return redirect()->to(base_url('knowledge'))->with('error', 'Bài viết không tồn tại hoặc đã bị gỡ.');
        }

        // Tăng View Count (Chặn tăng view cho chính tác giả để lấy views công bằng)
        if ($article['author_id'] != session()->get('employee_id')) {
            $this->knowledgeModel->update($id, [
                'view_count' => $article['view_count'] + 1
            ]);
        }

        // Truy xuất thông tin nâng cao
        $db = \Config\Database::connect();
        $author = $db->table('employees')->where('id', $article['author_id'])->get()->getRowArray();
        
        $caseInfo = null;
        if ($article['case_id']) {
            $caseModel = new CaseModel();
            $caseInfo = $caseModel->find($article['case_id']);
        }

        $data = [
            'title' => esc($article['title']) . ' | Tri thức L.A.N',
            'article' => $article,
            'author' => $author,
            'caseInfo' => $caseInfo,
            'tags' => $this->tagService->getTagsByEntity($id, 'knowledge'),
        ];

        return view('dashboard/knowledge/show', $data);
    }

    /**
     * Xóa bài viết nội bộ (Bảo mật nghiêm ngặt)
     */
    public function delete($id)
    {
        if (!session()->has('isLoggedIn')) { return redirect()->to('/login'); }

        $article = $this->knowledgeModel->find($id);
        if (!$article) {
            return redirect()->back()->with('error', 'Không tìm thấy dữ liệu bài viết.');
        }

        // Quyền thu hồi: User là Tác giả bài đó, HOẶC user là Admin
        // Đảm bảo tuân thủ Rule 2: Quyền sở hữu và Quản trị
        if ($article['author_id'] != session()->get('employee_id') && !has_permission('sys.admin')) {
             return redirect()->back()->with('error', 'Cảnh báo bảo mật: Chỉ tác giả bài viết hoặc Admin mới được phép gỡ.');
        }

        if ($this->knowledgeModel->delete($id)) {
            $this->logService->log('DELETE', 'KnowledgeBase', $id, ['title_revoked' => $article['title']]);
            return redirect()->to(base_url('knowledge'))->with('success', 'Đã gỡ bài chia sẻ khỏi hệ thống.');
        }

        return redirect()->back()->with('error', 'Lỗi khi gỡ tin.');
    }

    /**
     * Giao diện chỉnh sửa Bài viết (Sửa Knowledge)
     */
    public function edit($id)
    {
        if (!session()->has('isLoggedIn')) { return redirect()->to('/login'); }

        $article = $this->knowledgeModel->find($id);
        if (!$article || ($article['author_id'] != session()->get('employee_id') && !has_permission('sys.admin'))) {
            return redirect()->to(base_url('knowledge'))->with('error', 'Không tìm thấy bài viết hoặc bạn không có quyền sửa bài này.');
        }

        $caseInfo = null;
        if ($article['case_id']) {
            $caseModel = new CaseModel();
            $caseInfo = $caseModel->find($article['case_id']);
        }

        $data = [
            'title'         => 'Chỉnh sửa Cẩm Nang | L.A.N',
            'article'       => $article,
            'caseInfo'      => $caseInfo,
            'availableTags' => get_available_tags('knowledge'), // Đồng bộ Core logic
            'currentTags'   => array_column($this->tagService->getTagsByEntity($id, 'knowledge'), 'id')
        ];

        return view('dashboard/knowledge/edit', $data);
    }

    /**
     * Cập nhật bài viết
     */
    public function update($id)
    {
        if (!session()->has('isLoggedIn')) { return redirect()->to('/login'); }

        $article = $this->knowledgeModel->find($id);
        if (!$article || ($article['author_id'] != session()->get('employee_id') && !has_permission('sys.admin'))) {
            return redirect()->back()->with('error', 'Từ chối quyền truy cập.');
        }

        $input = $this->request->getPost();
        $input['case_id'] = !empty($input['case_id']) ? $input['case_id'] : null;

        // Chặn Ghi đè metrics và Bảo toàn tác giả
        // Tự động gộp 3 phần thành content
        $input['content'] = "<h3>Vấn đề:</h3>" . ($input['problem'] ?? '') . 
                            "<h3>Cách giải quyết:</h3>" . ($input['solution'] ?? '') . 
                            "<h3>Lưu ý:</h3>" . ($input['red_flags'] ?? '');

        if ($this->knowledgeModel->update($id, $input)) {
            $tags = $this->request->getPost('tags');
            $this->tagService->syncTags($id, 'knowledge', is_array($tags) ? $tags : []);
            
            // [NEW] Thông báo cho Admin khi có chỉnh sửa kinh nghiệm
            $this->notifyService->notifyAdmins(
                "Chỉnh sửa kinh nghiệm: " . $article['title'],
                session()->get('full_name') . " vừa cập nhật nội dung bài chia sẻ.",
                'system',
                base_url('knowledge/show/' . $id)
            );

            return redirect()->to(base_url('knowledge/show/' . $id))->with('success', 'Đã lưu thay đổi Cẩm nang thành công!');
        }

        return redirect()->back()->withInput()->with('errors', $this->knowledgeModel->errors());
    }

    /**
     * Tính năng "Bình chọn/Thả Like" Hữu Ích
     */
    public function vote($id)
    {
        if (!session()->has('isLoggedIn')) { return redirect()->to('/login'); }

        $article = $this->knowledgeModel->find($id);
        if (!$article) {
             return redirect()->back()->with('error', 'Bài viết không tồn tại.');
        }

        if ($article['author_id'] == session()->get('employee_id')) {
            return redirect()->back()->with('error', 'Bạn không thể tự vote Hữu ích cho bài của chính mình!');
        }

        $db = \Config\Database::connect();
        $empId = session()->get('employee_id');

        // Check if already voted
        $vote = $db->table('knowledge_votes')->where(['knowledge_id' => $id, 'employee_id' => $empId])->get()->getRow();
        if ($vote) {
            return redirect()->back()->with('error', 'Bạn đã bình chọn sự Hữu ích cho bài này rồi.');
        }

        $db->transStart();
        $db->table('knowledge_votes')->insert([
            'knowledge_id' => $id,
            'employee_id' => $empId,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        $this->knowledgeModel->update($id, [
            'helpful_count' => $article['helpful_count'] + 1
        ]);
        $db->transComplete();

        return redirect()->back()->with('success', 'Đã thả sao Hữu ích để khích lệ Tác giả!');
    }
}
