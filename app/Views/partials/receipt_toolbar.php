<header class="receipt-toolbar">
    <div class="receipt-heading">
        <span class="receipt-heading-icon"><?= view('partials/icon', ['name' => 'receipt']) ?></span>
        <div><h1><?= esc($heading) ?></h1><p><?= esc($subtitle) ?></p></div>
    </div>
    <nav class="receipt-toolbar-actions" aria-label="Receipt actions">
        <a href="<?= site_url('cashier/sales/history') ?>" class="receipt-button">Sales history</a>
        <button type="button" class="receipt-button receipt-button--primary" data-print-receipt><?= view('partials/icon', ['name' => 'print']) ?> Print</button>
    </nav>
</header>
