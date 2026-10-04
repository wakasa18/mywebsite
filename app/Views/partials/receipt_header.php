<header>
    <div class="receipt-brand-row">
        <div>
            <p class="receipt-brand"><?= esc($businessName) ?></p>
            <div class="receipt-business-type"><?= esc($businessType) ?></div>
        </div>
    </div>
    <div class="receipt-branch">
        <strong><?= esc($sale['branch_name'] ?? 'Main Branch') ?></strong>
        <?php if (!empty($sale['branch_address'])): ?><br><?= esc($sale['branch_address']) ?><?php endif; ?>
        <?php if (!empty($sale['branch_contact'])): ?><br>Tel: <?= esc($sale['branch_contact']) ?><?php endif; ?>
    </div>
    <div class="receipt-document-type"><?= esc($documentType) ?></div>
</header>
