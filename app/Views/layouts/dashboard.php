<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'L.A.N ERP' ?></title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Core styles -->
    <link rel="stylesheet" href="<?= base_url('css/dashboard.css') ?>">
    <link rel="stylesheet" href="<?= base_url('vendor/select2/select2.min.css') ?>">
    <!-- Page specific styles -->
    <?= $this->renderSection('styles') ?>
</head>
<body>
    <div class="app-wrapper">
        <div class="mobile-toggle-btn" id="mobile-toggle" title="Mở/Đóng menu điều hướng">
            <i class="fas fa-bars"></i>
        </div>
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="brand">
                    <h2>L.A.N <span class="text-blue">ERP</span></h2>
                </div>
                <div class="user-mini-profile">
                    <a href="<?= base_url('employees/edit/' . session()->get('employee_id')) ?>" class="user-avatar" title="Xem hồ sơ cá nhân: <?= esc(session()->get('full_name')) ?> (<?= esc(session()->get('role_name')) ?>)" style="text-decoration: none;">
                        <?= strtoupper(substr(session()->get('full_name') ?? 'U', 0, 1)) ?>
                    </a>
                    <a href="<?= base_url('logout') ?>" class="logout-mini" title="Kết thúc phiên làm việc và đăng xuất">
                        <i class="fas fa-power-off"></i>
                    </a>
                </div>
            </div>
            <nav class="nav-menu">
                <?php 
                $accessControl = new \App\Services\AccessControlService();
                $menu = $accessControl->getSidebarMenu(session()->get('department_id'), session()->get('role_name'));
                
                foreach ($menu as $item) { 
                    $isActive = (current_url() == base_url($item['url'])) ? 'active' : '';
                ?>
                <li class="nav-item">
                    <a href="<?= base_url($item['url']) ?>" class="nav-link <?= $isActive ?>" title="Truy cập <?= $item['title'] ?>">
                        <i class="<?= $item['icon'] ?>"></i> <?= $item['title'] ?>
                    </a>
                </li>
                <?php } ?>
            </nav>
            <div class="sidebar-footer">
                &copy; 2026 L.A.N
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Navbar for Notifications -->
            <header class="top-navbar">
                <div class="notification-dropdown">
                    <a href="#" id="notifDropdownToggle" class="notif-dropdown-toggle">
                        <i class="fas fa-bell"></i>
                        <span id="notifBadge" class="notif-badge">0</span>
                    </a>
                    <div id="notifDropdownMenu" class="notif-dropdown-menu">
                        <div class="notif-menu-header">
                            <strong>Thông báo mới</strong>
                            <a href="#" id="markAllRead" class="notif-mark-all">Đánh dấu đã đọc</a>
                        </div>
                        <div id="notifList">
                            <div style="padding: 15px; text-align: center; color: var(--muted-dark); font-size: 0.85rem;">Đang tải...</div>
                        </div>
                        <a href="<?= base_url('notifications') ?>" class="notif-footer-link">Xem tất cả</a>
                    </div>
                </div>
            </header>

            <?php if (session()->get('is_impersonating')) { ?>
                <div class="impersonation-banner">
                    <div>
                        <i class="fas fa-user-secret"></i> 
                        Bạn đang đăng nhập dưới quyền: <strong><?= esc(session()->get('full_name')) ?></strong> (<?= esc(session()->get('email')) ?>)
                    </div>
                    <a href="<?= base_url('stop-impersonating') ?>" class="btn-stop-impersonate" title="Thoát chế độ đăng nhập hộ để quay về tài khoản Admin">
                        Quay lại Admin <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            <?php } ?>
            
            <section class="content-body">
                <?= $this->renderSection('content') ?>
            </section>
        </main>
    </div>

    <!-- Common Image Modal -->
    <div id="imgModal" class="img-modal" onclick="closeImgModal()">
        <span class="img-modal-close">&times;</span>
        <img class="img-modal-content" id="imgFull">
    </div>

    <!-- Core scripts -->
    <script src="<?= base_url('vendor/jquery/jquery.min.js') ?>"></script>
    <script src="<?= base_url('vendor/select2/select2.min.js') ?>"></script>
    <script src="<?= base_url('js/dashboard.js') ?>"></script>
    <script>
    function previewImage(src) {
        document.getElementById('imgModal').style.display = "block";
        document.getElementById('imgFull').src = src;
    }
    function closeImgModal() {
        document.getElementById('imgModal').style.display = "none";
    }

    // Notifications Script
    $(document).ready(function() {
        function fetchUnreadCount() {
            $.get('<?= base_url("notifications/unread-count") ?>', function(resp) {
                if(resp.status === 'success') {
                    if(resp.count > 0) {
                        $('#notifBadge').text(resp.count).show();
                    } else {
                        $('#notifBadge').hide();
                    }
                }
            });
        }
        
        function fetchNotifications() {
            $.get('<?= base_url("notifications/unread") ?>', function(resp) {
                if(resp.status === 'success') {
                    let html = '';
                    if(resp.data.length === 0) {
                        html = '<div style="padding: 15px; text-align: center; color: #888; font-size: 0.85rem;">Không có thông báo mới.</div>';
                    } else {
                        resp.data.forEach(n => {
                            let icon = n.type === 'approval' ? 'fa-check-circle' : 'fa-info-circle';
                            let color = n.type === 'approval' ? '#34C759' : '#007AFF';
                            html += `
                                <div class="notif-item" data-id="${n.id}" data-link="${n.link}" style="padding: 10px 15px; border-bottom: 1px solid #eee; display: flex; gap: 10px; cursor: pointer;">
                                    <div style="color: ${color}; font-size: 1.2rem; flex-shrink: 0;"><i class="fas ${icon}"></i></div>
                                    <div>
                                        <div style="font-weight: 600; font-size: 0.85rem; color: #333; margin-bottom: 3px;">${n.title}</div>
                                        <div style="font-size: 0.8rem; color: #666; line-height: 1.3;">${n.message}</div>
                                    </div>
                                </div>
                            `;
                        });
                    }
                    $('#notifList').html(html);
                }
            });
        }

        $('#notifDropdownToggle').click(function(e) {
            e.preventDefault();
            $('#notifDropdownMenu').toggle();
            if($('#notifDropdownMenu').is(':visible')) {
                fetchNotifications();
            }
        });

        // Đóng dropdown khi click ra ngoài
        $(document).click(function(e) {
            if(!$(e.target).closest('.notification-dropdown').length) {
                $('#notifDropdownMenu').hide();
            }
        });

        $('#notifList').on('click', '.notif-item', function() {
            let id = $(this).data('id');
            let link = $(this).data('link');
            $.post('<?= base_url("notifications/read/") ?>' + id, function() {
                if(link) window.location.href = link;
                else location.reload();
            });
        });

        $('#markAllRead').click(function(e) {
            e.preventDefault();
            $.post('<?= base_url("notifications/read-all") ?>', function() {
                fetchUnreadCount();
                $('#notifDropdownMenu').hide();
            });
        });

        fetchUnreadCount();
        setInterval(fetchUnreadCount, 30000); // refresh every 30s
    });
    </script>
    <!-- Page specific scripts -->
    <?= $this->renderSection('scripts') ?>
</body>
</html>
