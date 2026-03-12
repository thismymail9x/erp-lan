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
        <div class="mobile-toggle-btn" id="mobile-toggle">
            <i class="fas fa-bars"></i>
        </div>
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="brand">
                    <h2>L.A.N <span class="text-blue">ERP</span></h2>
                </div>
                <div class="user-mini-profile">
                    <div class="user-avatar" title="<?= esc(session()->get('full_name')) ?>">
                        <?= strtoupper(substr(session()->get('full_name') ?? 'U', 0, 1)) ?>
                    </div>
                    <a href="<?= base_url('logout') ?>" class="logout-mini" title="Đăng xuất">
                        <i class="fas fa-power-off"></i>
                    </a>
                </div>
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
            <div class="sidebar-footer">
                &copy; 2026 L.A.N
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
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
    </script>
    <!-- Page specific scripts -->
    <?= $this->renderSection('scripts') ?>
</body>
</html>
