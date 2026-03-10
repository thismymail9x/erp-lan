<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'LawFirm ERP' ?></title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Core styles -->
    <link rel="stylesheet" href="<?= base_url('css/dashboard.css') ?>">
    <!-- Page specific styles -->
    <?= $this->renderSection('styles') ?>
</head>
<body>
    <div class="app-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>LawFirm ERP</h2>
            </div>
            <nav class="nav-menu">
                <?php 
                $accessControl = new \App\Services\AccessControlService();
                $menu = $accessControl->getSidebarMenu(session()->get('department_id'), session()->get('role_name'));
                
                foreach ($menu as $item): 
                    $isActive = (current_url() == base_url($item['url'])) ? 'active' : '';
                ?>
                <li class="nav-item">
                    <a href="<?= base_url($item['url']) ?>" class="nav-link <?= $isActive ?>">
                        <i class="<?= $item['icon'] ?>"></i> <?= $item['title'] ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="header">
                <div>
                    <div class="mobile-toggle" id="mobile-toggle">
                        <i class="fas fa-bars"></i>
                    </div>
                    <div class="header-text">
                        <h1 style="margin:0; font-size: 1.5rem;"><?= $page_title ?? 'Xin chào!' ?></h1>
                        <p style="color: var(--apple-text-muted); margin-top: 5px; font-size: 0.9rem;">Hệ thống quản trị LawFirm chuyên nghiệp.</p>
                    </div>
                </div>
                <div class="user-profile">
                    <div class="user-info-text" style="text-align: right">
                        <div style="font-weight: 600"><?= session()->get('full_name') ?? 'Người dùng' ?></div>
                        <div style="font-size: 0.8rem; color: var(--apple-text-muted)">
                            <?= session()->get('role_name') ?? 'Nhân viên' ?>
                            <?php if(session()->get('department_name')): ?>
                                - <?= session()->get('department_name') ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="user-avatar" title="<?= session()->get('email') ?>">
                        <?= strtoupper(substr(session()->get('full_name') ?? 'U', 0, 1)) ?>
                    </div>
                    <a href="<?= base_url('logout') ?>" class="btn-logout" title="Đăng xuất">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </header>

            <section class="content-body">
                <?= $this->renderSection('content') ?>
            </section>
        </main>
    </div>

    <!-- Core scripts -->
    <script src="<?= base_url('js/dashboard.js') ?>"></script>
    <!-- Page specific scripts -->
    <?= $this->renderSection('scripts') ?>
</body>
</html>
