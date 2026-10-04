<?php $movement = $cashMovements ?? ['rows' => [], 'totals' => [], 'returns' => []]; ?>
<div class="section-title">Receipts and Refund Payouts</div>
<p class="section-note">Sales use the sale date; payouts use the refund date, including older invoices. Values reflect corrections and exclude cash change. All amounts in PHP.</p>
<table class="data">
    <thead><tr><th style="width:20%">Date</th><th style="width:23%">Payment Method</th><th class="num" style="width:19%">Receipts</th><th class="num" style="width:19%">Refund Payouts</th><th class="num" style="width:19%">Net Movement</th></tr></thead>
    <tbody>
        <?php foreach ($movement['rows'] as $row): ?>
            <tr><td><?= esc($row['date']) ?></td><td><?= esc($row['method'] === 'unknown' ? 'Unknown (legacy)' : strtoupper($row['method'])) ?></td>
                <?php foreach (['receipts', 'refunds', 'net'] as $key): ?><td class="num"><?= number_format((float) $row[$key], 2) ?></td><?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
        <?php if (!$movement['rows']): ?><tr><td colspan="5" class="empty">No receipts or refund payouts in this period.</td></tr><?php endif; ?>
        <tr class="total"><td colspan="2">Total</td><?php foreach (['receipts', 'refunds', 'net'] as $key): ?><td class="num"><?= number_format((float) ($movement['totals'][$key] ?? 0), 2) ?></td><?php endforeach; ?></tr>
    </tbody>
</table>
<p class="section-note"><strong>Recorded cash movement:</strong> PHP <?= number_format((float) ($movement['totals']['cash_net'] ?? 0), 2) ?>.
    <?php if (($movement['totals']['unknown_refunds'] ?? 0) > 0): ?>Refunds with no recorded payout method: PHP <?= number_format((float) $movement['totals']['unknown_refunds'], 2) ?>. Review these before reconciling the cash drawer.<?php endif; ?>
</p>
<?php if ($movement['returns']): ?>
    <p class="section-note"><strong>Returned units:</strong>
        <?php foreach ($movement['returns'] as $row): ?><?= esc(ucfirst($row['return_condition'])) ?>: <?= (int) $row['quantity'] ?> &nbsp; <?php endforeach; ?>
        <br>Only resellable returns increase available stock.
    </p>
<?php endif; ?>
