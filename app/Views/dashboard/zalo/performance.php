<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('css/zalo.css') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="zalo-page-container" style="padding: 24px; margin: 0 auto;">
    <div class="dashboard-header-wrapper" style="margin-bottom: 24px;">
        <div class="header-title-container">
            <h2 class="content-title">Qu&#7843;n l&#253; hi&#7879;u su&#7845;t t&#432; v&#7845;n Zalo</h2>
            <p class="content-subtitle hide-mobile">Th&#7889;ng k&#234; th&#7901;i gian ph&#7843;n h&#7891;i &amp; Kh&#7843;o s&#225;t ch&#7845;t l&#432;&#7907;ng 5 sao</p>
        </div>
        <div class="header-controls">
            <a href="<?= base_url('zalo') ?>" class="btn-filter-secondary">
                <i class="fas fa-arrow-left"></i> Tr&#7903; v&#7873; Qu&#7843;n l&#253; Zalo
            </a>
        </div>
    </div>

    <div class="stats-grid-premium" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 24px;">
        <div class="stat-card-premium" style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);">
            <div style="font-size: 14px; color: #64748b; font-weight: 600; margin-bottom: 8px;">Th&#7901;i gian ph&#7843;n h&#7891;i trung b&#236;nh</div>
            <div style="font-size: 32px; font-weight: 700; color: #0f172a; margin-bottom: 8px;">2.5 <span style="font-size: 16px; color: #64748b; font-weight: 500;">ph&#250;t</span></div>
            <div style="font-size: 13px; color: #10b981;"><i class="fas fa-arrow-down"></i> 12% so v&#7899;i th&#225;ng tr&#432;&#7899;c</div>
        </div>

        <div class="stat-card-premium" style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border: 1px solid #fde68a;">
            <div style="font-size: 14px; color: #92400e; font-weight: 600; margin-bottom: 8px;">&#272;i&#7875;m &#273;&#225;nh gi&#225; trung b&#236;nh</div>
            <div style="font-size: 32px; font-weight: 700; color: #b45309; margin-bottom: 8px;">4.8 <i class="fas fa-star" style="color: #fbbf24; font-size: 24px;"></i></div>
            <div style="font-size: 13px; color: #92400e;">D&#7921;a tr&#234;n 1,250 l&#432;&#7907;t kh&#7843;o s&#225;t</div>
        </div>

        <div class="stat-card-premium" style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border: 1px solid #bbf7d0;">
            <div style="font-size: 14px; color: #166534; font-weight: 600; margin-bottom: 8px;">T&#7927; l&#7879; gi&#7843;i quy&#7871;t (First Contact Resolution)</div>
            <div style="font-size: 32px; font-weight: 700; color: #15803d; margin-bottom: 8px;">85%</div>
            <div style="font-size: 13px; color: #166534;"><i class="fas fa-arrow-up"></i> 5% so v&#7899;i th&#225;ng tr&#432;&#7899;c</div>
        </div>
    </div>

    <div class="premium-card premium-card-full" style="margin-bottom: 24px;">
        <div class="card-header" style="padding: 20px; border-bottom: 1px solid #f0f2f5;">
            <h3 style="font-size: 16px; font-weight: 600; margin: 0;">B&#7843;ng x&#7871;p h&#7841;ng hi&#7879;u su&#7845;t nh&#226;n s&#7921;</h3>
        </div>
        <div class="table-responsive">
            <table class="table-premium">
                <thead>
                    <tr>
                        <th>H&#7841;ng</th>
                        <th>Nh&#226;n s&#7921; t&#432; v&#7845;n</th>
                        <th>S&#7889; l&#432;&#7907;ng chat</th>
                        <th>Th&#7901;i gian ph&#7843;n h&#7891;i</th>
                        <th>&#272;&#225;nh gi&#225; sao</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="font-weight: 700; color: #fbbf24; font-size: 18px;">#1</td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <img src="https://ui-avatars.com/api/?name=Tran+Van+T&background=random" style="width: 32px; height: 32px; border-radius: 50%;">
                                <span style="font-weight: 600;">Tr&#7847;n V&#259;n T</span>
                            </div>
                        </td>
                        <td>450</td>
                        <td><span style="color: #10b981; font-weight: 600;">1.2 ph&#250;t</span></td>
                        <td>
                            <div style="color: #fbbf24; font-size: 14px;">
                                <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                                <span style="color: #64748b; font-weight: 600; margin-left: 5px;">(5.0)</span>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600; color: #94a3b8;">#2</td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <img src="https://ui-avatars.com/api/?name=Le+Thi+H&background=random" style="width: 32px; height: 32px; border-radius: 50%;">
                                <span style="font-weight: 600;">L&#234; Th&#7883; H</span>
                            </div>
                        </td>
                        <td>380</td>
                        <td><span style="color: #10b981; font-weight: 600;">1.5 ph&#250;t</span></td>
                        <td>
                            <div style="color: #fbbf24; font-size: 14px;">
                                <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i>
                                <span style="color: #64748b; font-weight: 600; margin-left: 5px;">(4.8)</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
