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
    <link rel="stylesheet" href="<?= base_url('css/dashboard.css') ?>?v=1.2">
    <link rel="stylesheet" href="<?= base_url('css/notifications.css') ?>">
    <link rel="stylesheet" href="<?= base_url('vendor/select2/select2.min.css') ?>">
    <script src="<?= base_url('vendor/jquery/jquery.min.js') ?>"></script>
    <script src="<?= base_url('vendor/select2/select2.min.js') ?>"></script>
    <!-- Page specific styles -->
    <?= $this->renderSection('styles') ?>
    <script>
        const baseUrl = '<?= base_url() ?>';
        const csrfToken = '<?= csrf_token() ?>';
        const csrfHash = '<?= csrf_hash() ?>';
    </script>
</head>
<body>
    <div class="app-wrapper">
        <div class="mobile-toggle-btn" id="mobile-toggle" title="M&#7903;/&#272;&#243;ng menu &#273;i&#7873;u h&#432;&#7899;ng">
            <i class="fas fa-bars"></i>
        </div>
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="brand">
                    <h2>L.A.N <span class="text-blue">ERP</span></h2>
                </div>
                <div class="user-mini-profile">
                    <a href="<?= base_url('employees/edit/' . session()->get('employee_id')) ?>" class="user-avatar" title="Xem h&#7891; s&#417; c&#225; nh&#226;n: <?= esc(session()->get('full_name')) ?> (<?= esc(session()->get('role_name')) ?>)" style="text-decoration: none;">
                        <?= mb_strtoupper(mb_substr(session()->get('full_name') ?? 'U', 0, 1)) ?>
                    </a>
                    <a href="<?= base_url('logout') ?>" class="logout-mini" title="K&#7871;t th&#250;c phi&#234;n l&#224;m vi&#7879;c v&#224; &#273;&#259;ng xu&#7845;t">
                        <i class="fas fa-power-off"></i>
                    </a>
                </div>
                <button type="button" class="sidebar-collapse-toggle" id="sidebarCollapseToggle" title="Thu g&#7885;n menu" aria-label="Thu g&#7885;n menu" aria-expanded="true">
                    <i class="fas fa-angles-left"></i>
                </button>
            </div>
            <nav class="nav-menu" aria-label="Menu ch&#237;nh">
                <?php 
                $accessControl = new \App\Services\AccessControlService();
                $menu = $accessControl->getSidebarMenu(session()->get('department_id'), session()->get('role_name'));
                
                $activeGuidance = null;
                foreach ($menu as $item) { 
                    $currentUri = service('request')->getUri();
                    $firstSegment = $currentUri->getTotalSegments() > 0 ? $currentUri->getSegment(1) : '';
                    
                    $menuUrl = isset($item['url']) ? trim($item['url'], '/') : '';
                    $menuSegment = $menuUrl ? explode('/', $menuUrl)[0] : '';

                    $isActive = ($firstSegment == $menuSegment && $menuSegment != '') ? 'active' : '';
                    
                    // Chỉ hiển thị hướng dẫn ở màn hình danh sách chính.
                    $currentPath = trim($currentUri->getPath(), '/');
                    $menuPath = $menuUrl ? trim(explode('?', $item['url'])[0], '/') : ''; // Bỏ query string nếu có
                    
                    if ($isActive && $currentPath == $menuPath && isset($item['guidance'])) {
                        $activeGuidance = $item['guidance'];
                    }

                    $hasSubmenu = isset($item['submenu']) && is_array($item['submenu']);
                    $isReportMenu = $hasSubmenu && strpos($item['title'], 'B&#225;o c&#225;o') !== false;
                    $isParentOpen = false;

                    if ($hasSubmenu) {
                        foreach ($item['submenu'] as $subForState) {
                            $subStateUrl = isset($subForState['url']) ? trim($subForState['url'], '/') : '';
                            if ($subStateUrl === $currentPath) {
                                $isParentOpen = true;
                                break;
                            }
                        }
                    }
                ?>

                <li class="nav-item <?= $hasSubmenu ? 'has-submenu' : '' ?> <?= $isReportMenu ? 'is-report-menu' : '' ?>">
                    <?php if ($hasSubmenu) { ?>
                        <a href="javascript:void(0)" class="nav-link dropdown-toggle" title="Xem th&#234;m <?= $item['title'] ?>" aria-expanded="<?= $isParentOpen ? 'true' : 'false' ?>">
                            <i class="<?= $item['icon'] ?>"></i>
                            <span class="nav-text"><?= $item['title'] ?></span>
                            <i class="fas fa-chevron-right arrow"></i>
                        </a>
                        <ul class="submenu">
                            <?php foreach ($item['submenu'] as $sub) {
                                $subUrl = isset($sub['url']) ? trim($sub['url'], '/') : '';
                                $currentPath = trim(service('request')->getUri()->getPath(), '/');
                                $isSubActive = ($subUrl == $currentPath) ? 'active' : '';
                            ?>
                                <li class="submenu-item">
                                    <a href="<?= isset($sub['url']) ? base_url($sub['url']) : 'javascript:void(0)' ?>" class="submenu-link <?= $isSubActive ?>">
                                        <i class="<?= $sub['icon'] ?>"></i>
                                        <span class="nav-text"><?= $sub['title'] ?></span>
                                        <?php if (isset($sub['is_new']) && $sub['is_new']): ?>
                                            <span class="badge-new-sidebar">New</span>
                                        <?php endif; ?>
                                    </a>
                                </li>
                            <?php } ?>
                        </ul>
                    <?php } else { ?>
                        <a href="<?= base_url($item['url']) ?>" class="nav-link <?= $isActive ?>" title="Truy c&#7853;p <?= $item['title'] ?>">
                            <i class="<?= $item['icon'] ?>"></i>
                            <span class="nav-text"><?= $item['title'] ?></span>
                            <?php if (isset($item['is_new']) && $item['is_new']): ?>
                                <span class="badge-new-sidebar">New</span>
                            <?php endif; ?>
                        </a>
                    <?php } ?>
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
                <div class="notif-ticker-container" id="notifTickerContainer" style="display: none;">
                    <div class="notif-ticker-content" id="notifTicker">
                        <!-- Ticker contents injected by JS -->
                    </div>
                </div>
                <div class="notification-dropdown">
                    <a href="#" id="notifDropdownToggle" class="notif-dropdown-toggle">
                        <i class="fas fa-bell"></i>
                        <span id="notifBadge" class="notif-badge">0</span>
                    </a>
                    <div id="notifDropdownMenu" class="notif-dropdown-menu">
                        <div class="notif-menu-header">
                            <strong>Th&#244;ng b&#225;o m&#7899;i</strong>
                            <a href="#" id="markAllRead" class="notif-mark-all">&#272;&#225;nh d&#7845;u &#273;&#227; &#273;&#7885;c</a>
                        </div>
                        <div id="notifList">
                            <div style="padding: 15px; text-align: center; color: var(--muted-dark); font-size: 0.85rem;">&#272;ang t&#7843;i...</div>
                        </div>
                        <a href="<?= base_url('notifications') ?>" class="notif-footer-link">Xem t&#7845;t c&#7843;</a>
                    </div>
                </div>
            </header>

            <?php if (session()->get('is_impersonating')) { ?>
                <div class="impersonation-banner">
                    <div>
                        <i class="fas fa-user-secret"></i> 
                        B&#7841;n &#273;ang &#273;&#259;ng nh&#7853;p d&#432;&#7899;i quy&#7873;n: <strong><?= esc(session()->get('full_name')) ?></strong> (<?= esc(session()->get('email')) ?>)
                    </div>
                    <a href="<?= base_url('stop-impersonating') ?>" class="btn-stop-impersonate" title="Tho&#225;t ch&#7871; &#273;&#7897; &#273;&#259;ng nh&#7853;p h&#7897; &#273;&#7875; quay v&#7873; t&#224;i kho&#7843;n Admin">
                        Quay l&#7841;i Admin <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            <?php } ?>
            
            <section class="content-body">
                <?php
                    $flashSuccess = session()->getFlashdata('success');
                    $flashError = session()->getFlashdata('error');
                    $flashErrors = session()->getFlashdata('errors');
                    $flashWarning = session()->getFlashdata('warning');
                ?>

                <!-- SYSTEM ALERTS (Flash Data) -->
                <?php if ($flashSuccess) : ?>
                    <div class="alert-premium-success alert-auto-hide" data-toast data-toast-type="success">
                        <i class="fas fa-check-circle"></i> <?= esc($flashSuccess) ?>
                    </div>
                <?php endif; ?>

                <?php if ($flashError) : ?>
                    <div class="alert-premium-danger alert-auto-hide" data-toast data-toast-type="error">
                        <i class="fas fa-exclamation-circle"></i> <?= esc($flashError) ?>
                    </div>
                <?php endif; ?>

                <?php if ($flashErrors) : ?>
                    <div class="alert-premium-danger alert-auto-hide" data-toast data-toast-type="error">
                        <i class="fas fa-exclamation-triangle"></i> <strong>L&#7895;i d&#7919; li&#7879;u:</strong>
                        <ul>
                            <?php foreach ($flashErrors as $error) : ?>
                                <li><?= esc($error) ?></li>
                            <?php endforeach ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($flashWarning) : ?>
                    <div class="alert-premium-warning alert-auto-hide" data-toast data-toast-type="warning">
                        <i class="fas fa-exclamation-triangle"></i> <?= esc($flashWarning) ?>
                    </div>
                <?php endif; ?>

                <?= $this->renderSection('content') ?>
            </section>
        </main>
    </div>

    <!-- Common Image Modal -->
    <div id="imgModal" class="img-modal" onclick="closeImgModal()">
        <span class="img-modal-close">&times;</span>
        <img class="img-modal-content" id="imgFull">
    </div>

    <!-- Guidance Modal -->
    <?php if ($activeGuidance): ?>
    <div id="guidanceModal" class="modal-overlay">
        <div class="modal-content-premium guidance-modal-content">
            <div class="flex-row justify-between align-center m-b-20">
                <h3 class="section-header-title"><?= esc($activeGuidance['title']) ?></h3>
                <span class="close-btn-minimal" onclick="closeGuidanceModal()">&times;</span>
            </div>
            <div class="guidance-text">
                <?= nl2br(esc($activeGuidance['content'])) ?>
            </div>
            <div class="form-actions-row m-t-25">
                <button type="button" class="btn-premium" onclick="closeGuidanceModal()">&#272;&#227; hi&#7875;u</button>
            </div>
        </div>
    </div>
    <?php endif; ?>


    <!-- Core scripts -->
    <script src="<?= base_url('js/dashboard.js') ?>?v=1.4"></script>
    <script src="<?= base_url('js/bulk_actions.js') ?>"></script>
    <script>
    function previewImage(src) {
        document.getElementById('imgModal').style.display = "block";
        document.getElementById('imgFull').src = src;
    }
    function closeImgModal() {
        document.getElementById('imgModal').style.display = "none";
    }

    function openGuidanceModal() {
        const modal = document.getElementById('guidanceModal');
        if (modal) modal.style.display = "flex";
    }
    function closeGuidanceModal() {
        const modal = document.getElementById('guidanceModal');
        if (modal) modal.style.display = "none";
    }

    $(document).ready(function() {
        $('.dropdown-toggle').on('click', function(e) {
            e.preventDefault();
            const $parent = $(this).parent('.has-submenu');
            $parent.toggleClass('open');
            $(this).attr('aria-expanded', $parent.hasClass('open') ? 'true' : 'false');
            $(this).next('.submenu').slideToggle(300);
        });

        var $activeSubmenu = $('.submenu-link.active').closest('.has-submenu');
        $activeSubmenu.addClass('open');
        $activeSubmenu.find('.submenu').show();
        $activeSubmenu.find('> .dropdown-toggle').attr('aria-expanded', 'true');

        <?php if ($activeGuidance): ?>
            const $title = $('.content-title').first();
            if ($title.length) {
                $title.append('<span class="guidance-trigger" onclick="openGuidanceModal()" title="Xem h&#432;&#7899;ng d&#7851;n s&#7917; d&#7909;ng t&#237;nh n&#259;ng n&#224;y">!</span>');
            }
        <?php endif; ?>
    });

    $(document).ready(function() {
        function escapeHtml(value) {
            return $('<div>').text(value || '').html();
        }

        function updateTickerSpeed() {
            const ticker = document.getElementById('notifTicker');
            const container = document.getElementById('notifTickerContainer');
            if (!ticker || !container) return;

            const distance = ticker.scrollWidth + container.clientWidth;
            const duration = Math.min(120, Math.max(45, Math.round(distance / 24)));
            ticker.style.setProperty('--ticker-duration', duration + 's');
        }

        function fetchUnreadCount() {
            $.get('<?= base_url("notifications/unread-count") ?>', function(resp) {
                if (resp.status !== 'success') return;

                if (resp.count > 0) {
                    $('#notifBadge').text(resp.count).show();

                    if (resp.latest && resp.latest.length > 0) {
                        let tickerHtml = '';
                        const colors = [
                            { bg: 'rgba(0, 113, 227, 0.1)', text: '#0071e3' },
                            { bg: 'rgba(52, 199, 89, 0.1)', text: '#34c759' },
                            { bg: 'rgba(255, 149, 0, 0.1)', text: '#ff9500' },
                            { bg: 'rgba(255, 59, 48, 0.1)', text: '#ff3b30' },
                            { bg: 'rgba(175, 82, 222, 0.1)', text: '#af52de' }
                        ];

                        resp.latest.forEach((n, idx) => {
                            let c = colors[idx % colors.length];
                            let sender = escapeHtml(n.sender_name ? n.sender_name : 'H\u1ec7 th\u1ed1ng');
                            let title = escapeHtml(n.title);
                            let message = escapeHtml(n.message);
                            let link = escapeHtml(n.link);
                            tickerHtml += `
                                <a href="javascript:void(0)"
                                   class="ticker-item notif-item"
                                   data-id="${n.id}"
                                   data-link="${link}"
                                   style="background: ${c.bg}; color: ${c.text}; border: 1px solid ${c.bg};">
                                   <i class="fas fa-bell"></i> <strong>[${sender}] ${title}</strong>: ${message}
                                </a>`;
                        });

                        $('#notifTicker').html(tickerHtml);
                        $('#notifTickerContainer').css('display', 'flex').hide().fadeIn();
                        window.requestAnimationFrame(updateTickerSpeed);
                    }
                } else {
                    $('#notifBadge').hide();
                    $('#notifTickerContainer').fadeOut();
                }
            });
        }

        function fetchNotifications() {
            $.get('<?= base_url("notifications/unread") ?>', function(resp) {
                if (resp.status !== 'success') return;

                let html = '';
                if (resp.data.length === 0) {
                    html = '<div style="padding: 15px; text-align: center; color: #888; font-size: 0.85rem;">Kh&#244;ng c&#243; th&#244;ng b&#225;o m&#7899;i.</div>';
                } else {
                    resp.data.forEach(n => {
                        let icon = n.type === 'approval' ? 'fa-check-circle' : 'fa-info-circle';
                        let color = n.type === 'approval' ? '#34C759' : '#007AFF';
                        let sender = escapeHtml(n.sender_name ? n.sender_name : 'H\u1ec7 th\u1ed1ng');
                        let title = escapeHtml(n.title);
                        let message = escapeHtml(n.message);
                        let link = escapeHtml(n.link);
                        html += `
                            <div class="notif-item" data-id="${n.id}" data-link="${link}" style="padding: 10px 15px; border-bottom: 1px solid #eee; display: flex; gap: 10px; cursor: pointer;">
                                <div style="color: ${color}; font-size: 1.2rem; flex-shrink: 0;"><i class="fas ${icon}"></i></div>
                                <div>
                                    <div style="font-weight: 600; font-size: 0.85rem; color: #333; margin-bottom: 3px;">
                                        <span style="color: #8e8e93;">[${sender}]</span> ${title}
                                    </div>
                                    <div style="font-size: 0.8rem; color: #666; line-height: 1.3;">${message}</div>
                                </div>
                            </div>`;
                    });
                }

                $('#notifList').html(html);
            });
        }

        $('#notifDropdownToggle').click(function(e) {
            e.preventDefault();
            $('#notifDropdownMenu').toggle();
            if ($('#notifDropdownMenu').is(':visible')) fetchNotifications();
        });

        $(document).click(function(e) {
            if (!$(e.target).closest('.notification-dropdown').length) {
                $('#notifDropdownMenu').hide();
            }
        });

        $(document).on('click', '.notif-item', function() {
            let id = $(this).data('id');
            let link = ($(this).data('link') || '').toString().trim();
            let fallbackLink = '<?= base_url("notifications/show/") ?>' + id;
            $.post('<?= base_url("notifications/read/") ?>' + id, function() {
                if (link && link !== 'null' && link !== 'undefined') window.location.href = link;
                else window.location.href = fallbackLink;
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
        setInterval(fetchUnreadCount, 30000);
        $(window).on('resize', updateTickerSpeed);
    });
    </script>
    <!-- Page specific scripts -->
    <?= $this->renderSection('scripts') ?>
</body>
</html>
