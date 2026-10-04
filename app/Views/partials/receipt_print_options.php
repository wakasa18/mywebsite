<aside class="receipt-help" aria-label="Print and navigation options">
    <h2>Keep a copy</h2>
    <p>Print this document or choose “Save as PDF” in the print dialog.</p>
    <fieldset class="receipt-print-options">
        <legend>Thermal paper width</legend>
        <label><input type="radio" name="receipt-paper-width" value="80" checked> 80 mm</label>
        <label><input type="radio" name="receipt-paper-width" value="58"> 58 mm</label>
    </fieldset>
    <p>Choose the matching paper size in your printer settings. Turn off browser headers and footers.</p>
    <button type="button" class="receipt-button receipt-button--primary" data-print-receipt><?= view('partials/icon', ['name' => 'print']) ?> <?= esc($printLabel) ?></button>
    <a href="<?= site_url('cashier/sales') ?>" class="receipt-button">New sale</a>
    <a href="<?= site_url('cashier/sales/view/' . (int) $sale['id']) ?>" class="receipt-button">Sale details</a>
    <?php foreach (($extraActions ?? []) as $action): ?>
        <a href="<?= esc($action['url']) ?>" class="receipt-button"><?= esc($action['label']) ?></a>
    <?php endforeach; ?>
</aside>
