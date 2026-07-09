<div class="table-responsive">
    <table class="premium-table">
        <thead>
            <tr>
                <th class="finance-col-code">M&#227; s&#7889;</th>
                <th class="finance-col-case">V&#7909; vi&#7879;c / Kh&#225;ch h&#224;ng</th>
                <th class="finance-col-contract">Gi&#225; tr&#7883; H&#272;</th>
                <th class="finance-col-payment">&#272;&#7907;t thanh to&#225;n</th>
                <th class="finance-col-deadline">H&#7841;n thanh to&#225;n</th>
                <th class="finance-col-status">T&#236;nh tr&#7841;ng thu</th>
                <th class="finance-col-vat">Xu&#7845;t VAT &amp; Ghi ch&#250;</th>
                <th class="table-cell-right finance-col-action">Thao t&#225;c</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($cases)): ?>
                <tr><td colspan="8" class="empty-state-container">Kh&#244;ng c&#243; d&#7919; li&#7879;u t&#224;i ch&#237;nh h&#7891; s&#417; n&#224;o.</td></tr>
            <?php else: ?>
                <?php
                foreach ($cases as $case):
                    $payments = !empty($case['payment_progress']) ? json_decode($case['payment_progress'], true) : [];
                    if (json_last_error() !== JSON_ERROR_NONE || !is_array($payments)) {
                        $payments = [];
                    }
                    $rowCount = max(1, count($payments));
                ?>
                <tr>
                    <td rowspan="<?= $rowCount ?>"><span class="badge-secondary-minimal"><?= esc($case['code']) ?></span></td>
                    <td rowspan="<?= $rowCount ?>">
                        <div class="finance-case-title"><?= esc($case['title']) ?></div>
                        <div class="text-xs text-muted-dark"><i class="fas fa-user-circle m-r-4"></i> <?= esc($case['customer_name'] ?: 'N/A') ?></div>
                    </td>
                    <td rowspan="<?= $rowCount ?>">
                        <div class="font-weight-700 finance-contract-value">
                            <?= !empty($case['contract_value']) ? number_format($case['contract_value'], 0, ',', '.') . '&#273;' : '<span class="text-muted-dark font-weight-400 italic text-xs">Ch&#432;a ch&#7889;t</span>' ?>
                        </div>
                    </td>

                    <?php if (!empty($payments)):
                        $p = $payments[0];
                        $amt = !empty($p['amount']) ? number_format($p['amount'], 0, ',', '.') . '&#273;' : '';
                        $warnHtml = '';
                        $isPaid = (!empty($p['is_paid']) && $p['is_paid'] == 1);
                        if (!$isPaid && !empty($p['deadline']) && strtotime($p['deadline']) < time()) {
                            $warnHtml = ' <i class="fas fa-exclamation-triangle text-apple-red" title="&#272;&#227; tr&#7877; h&#7841;n"></i>';
                        }
                        $paidBadge = $isPaid
                            ? '<span class="badge-success-minimal text-xs"><i class="fas fa-check-circle"></i> &#272;&#227; thu</span>'
                            : '<span class="badge-secondary-minimal text-xs"><i class="fas fa-clock"></i> Ch&#7901; thu</span>';
                        $isVat = (!empty($p['is_vat']) && $p['is_vat'] == 1);
                        $vatBadge = $isVat
                            ? '<span class="badge-success-minimal text-xs finance-vat-issued"><i class="fas fa-file-invoice-dollar"></i> &#272;&#227; xu&#7845;t VAT</span>'
                            : '<span class="badge-warning-minimal text-xs"><i class="fas fa-file-invoice"></i> Ch&#432;a xu&#7845;t VAT</span>';
                        $noteHtml = !empty($p['note'])
                            ? '<div class="text-xs text-muted-dark italic m-t-4 finance-payment-note"><i class="fas fa-comment-dots finance-note-icon"></i>' . esc($p['note']) . '</div>'
                            : '';
                    ?>
                        <td><strong><?= esc($p['title']) ?>:</strong> <span class="finance-payment-amount"><?= $amt ?></span></td>
                        <td><?= !empty($p['deadline']) ? date('d/m/Y', strtotime($p['deadline'])) : '--' ?><?= $warnHtml ?></td>
                        <td><?= $paidBadge ?></td>
                        <td>
                            <?= $vatBadge ?>
                            <?= $noteHtml ?>
                        </td>
                    <?php else: ?>
                        <td colspan="4" class="text-muted-dark text-xs italic">-- Ch&#432;a c&#243; &#273;&#7907;t thanh to&#225;n --</td>
                    <?php endif; ?>

                    <td rowspan="<?= $rowCount ?>" class="table-cell-right">
                        <a href="<?= base_url('cases/edit/' . $case['id']) ?>#payment-progress-container" class="btn-premium btn-sm finance-update-button" title="V&#224;o trang s&#7917;a h&#7891; s&#417; &#273;&#7875; c&#7853;p nh&#7853;t t&#224;i ch&#237;nh">
                            <i class="fas fa-edit m-r-4"></i> C&#7853;p nh&#7853;t
                        </a>
                    </td>
                </tr>

                <?php for ($i = 1; $i < $rowCount; $i++):
                    $p = $payments[$i];
                    $amt = !empty($p['amount']) ? number_format($p['amount'], 0, ',', '.') . '&#273;' : '';
                    $warnHtml = '';
                    $isPaid = (!empty($p['is_paid']) && $p['is_paid'] == 1);
                    if (!$isPaid && !empty($p['deadline']) && strtotime($p['deadline']) < time()) {
                        $warnHtml = ' <i class="fas fa-exclamation-triangle text-apple-red" title="&#272;&#227; tr&#7877; h&#7841;n"></i>';
                    }
                    $paidBadge = $isPaid
                        ? '<span class="badge-success-minimal text-xs"><i class="fas fa-check-circle"></i> &#272;&#227; thu</span>'
                        : '<span class="badge-secondary-minimal text-xs"><i class="fas fa-clock"></i> Ch&#7901; thu</span>';
                    $isVat = (!empty($p['is_vat']) && $p['is_vat'] == 1);
                    $vatBadge = $isVat
                        ? '<span class="badge-success-minimal text-xs finance-vat-issued"><i class="fas fa-file-invoice-dollar"></i> &#272;&#227; xu&#7845;t VAT</span>'
                        : '<span class="badge-warning-minimal text-xs"><i class="fas fa-file-invoice"></i> Ch&#432;a xu&#7845;t VAT</span>';
                    $noteHtml = !empty($p['note'])
                        ? '<div class="text-xs text-muted-dark italic m-t-4 finance-payment-note"><i class="fas fa-comment-dots finance-note-icon"></i>' . esc($p['note']) . '</div>'
                        : '';
                ?>
                    <tr>
                        <td><strong><?= esc($p['title']) ?>:</strong> <span class="finance-payment-amount"><?= $amt ?></span></td>
                        <td><?= !empty($p['deadline']) ? date('d/m/Y', strtotime($p['deadline'])) : '--' ?><?= $warnHtml ?></td>
                        <td><?= $paidBadge ?></td>
                        <td>
                            <?= $vatBadge ?>
                            <?= $noteHtml ?>
                        </td>
                    </tr>
                <?php endfor; ?>

                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="finance-mobile-list">
    <?php if (empty($cases)): ?>
        <div class="finance-mobile-empty empty-state-container">Kh&#244;ng c&#243; d&#7919; li&#7879;u t&#224;i ch&#237;nh h&#7891; s&#417; n&#224;o.</div>
    <?php else: ?>
        <?php foreach ($cases as $case): ?>
            <?php
                $payments = !empty($case['payment_progress']) ? json_decode($case['payment_progress'], true) : [];
                if (json_last_error() !== JSON_ERROR_NONE || !is_array($payments)) {
                    $payments = [];
                }
            ?>
            <article class="finance-mobile-card">
                <div class="finance-mobile-card-header">
                    <div>
                        <span class="badge-secondary-minimal"><?= esc($case['code']) ?></span>
                        <h3 class="finance-mobile-title"><?= esc($case['title']) ?></h3>
                        <div class="text-xs text-muted-dark">
                            <i class="fas fa-user-circle m-r-4"></i> <?= esc($case['customer_name'] ?: 'N/A') ?>
                        </div>
                    </div>
                    <a href="<?= base_url('cases/edit/' . $case['id']) ?>#payment-progress-container" class="btn-premium btn-sm finance-update-button" title="V&#224;o trang s&#7917;a h&#7891; s&#417; &#273;&#7875; c&#7853;p nh&#7853;t t&#224;i ch&#237;nh">
                        <i class="fas fa-edit"></i>
                    </a>
                </div>

                <div class="finance-mobile-summary">
                    <span>Gi&#225; tr&#7883; H&#272;</span>
                    <strong>
                        <?= !empty($case['contract_value']) ? number_format($case['contract_value'], 0, ',', '.') . '&#273;' : '<span class="text-muted-dark font-weight-400 italic text-xs">Ch&#432;a ch&#7889;t</span>' ?>
                    </strong>
                </div>

                <?php if (!empty($payments)): ?>
                    <div class="finance-mobile-payments">
                        <?php foreach ($payments as $p): ?>
                            <?php
                                $amt = !empty($p['amount']) ? number_format($p['amount'], 0, ',', '.') . '&#273;' : '--';
                                $isPaid = (!empty($p['is_paid']) && $p['is_paid'] == 1);
                                $isOverdue = (!$isPaid && !empty($p['deadline']) && strtotime($p['deadline']) < time());
                                $isVat = (!empty($p['is_vat']) && $p['is_vat'] == 1);
                            ?>
                            <div class="finance-mobile-payment">
                                <div class="finance-mobile-payment-top">
                                    <strong><?= !empty($p['title']) ? esc($p['title']) : '&#272;&#7907;t thanh to&#225;n' ?></strong>
                                    <span class="finance-payment-amount"><?= $amt ?></span>
                                </div>
                                <div class="finance-mobile-meta">
                                    <span>
                                        <i class="fas fa-calendar-alt"></i>
                                        <?= !empty($p['deadline']) ? date('d/m/Y', strtotime($p['deadline'])) : '--' ?>
                                        <?php if ($isOverdue): ?>
                                            <i class="fas fa-exclamation-triangle text-apple-red" title="&#272;&#227; tr&#7877; h&#7841;n"></i>
                                        <?php endif; ?>
                                    </span>
                                    <?= $isPaid
                                        ? '<span class="badge-success-minimal text-xs"><i class="fas fa-check-circle"></i> &#272;&#227; thu</span>'
                                        : '<span class="badge-secondary-minimal text-xs"><i class="fas fa-clock"></i> Ch&#7901; thu</span>' ?>
                                    <?= $isVat
                                        ? '<span class="badge-success-minimal text-xs finance-vat-issued"><i class="fas fa-file-invoice-dollar"></i> &#272;&#227; xu&#7845;t VAT</span>'
                                        : '<span class="badge-warning-minimal text-xs"><i class="fas fa-file-invoice"></i> Ch&#432;a xu&#7845;t VAT</span>' ?>
                                </div>
                                <?php if (!empty($p['note'])): ?>
                                    <div class="text-xs text-muted-dark italic m-t-4 finance-payment-note">
                                        <i class="fas fa-comment-dots finance-note-icon"></i><?= esc($p['note']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-muted-dark text-xs italic finance-mobile-no-payment">-- Ch&#432;a c&#243; &#273;&#7907;t thanh to&#225;n --</div>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php if (!empty($pager)) : ?>
    <div class="pagination-wrapper">
        <?= $pager->links() ?>
    </div>
<?php endif ?>
