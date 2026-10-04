<?php $movement = $cashMovements ?? ['rows' => [], 'totals' => [], 'returns' => []]; ?>
<div class="card" style="margin:20px 0;">
    <div class="card-head">
        <div>
            <h2 id="cashMovementsTitle">Receipts and refund payouts by date</h2>
            <p>Sales use the sale date; payouts use the refund date, including refunds for older invoices. Amounts exclude cash change and exchange credits; exchanges show only additional payments or payouts.</p>
        </div>
    </div>
    <div class="rp-cash-summary">
        <div><span>Receipts</span><strong>₱<?= number_format($movement['totals']['receipts'] ?? 0, 2) ?></strong></div>
        <div><span>Refund payouts</span><strong>₱<?= number_format($movement['totals']['refunds'] ?? 0, 2) ?></strong></div>
        <div><span>Net movement</span><strong>₱<?= number_format($movement['totals']['net'] ?? 0, 2) ?></strong></div>
        <div><span>Recorded cash movement</span><strong>₱<?= number_format($movement['totals']['cash_net'] ?? 0, 2) ?></strong></div>
    </div>
        <?php if (($movement['totals']['unknown_refunds'] ?? 0) > 0): ?>
            <p class="forecast-note">Older refunds totaling ₱<?= number_format($movement['totals']['unknown_refunds'], 2) ?> have no recorded payout method. Review these before reconciling the cash drawer.</p>
        <?php endif; ?>
    <div class="table-wrap rp-cash-scroll" role="region" aria-labelledby="cashMovementsTitle" tabindex="0">
        <table class="data small mobile-record-table">
            <thead><tr><th>Date</th><th>Payment method</th><th>Receipts</th><th>Refund payouts</th><th>Net movement</th></tr></thead>
            <tbody>
                <?php foreach ($movement['rows'] as $row): ?>
                    <tr>
                        <td><?= esc(\App\Libraries\DisplayDate::date($row['date'])) ?></td>
                        <td><?= esc($row['method'] === 'unknown' ? 'Unknown (legacy refund)' : strtoupper($row['method'])) ?></td>
                        <td>₱<?= number_format($row['receipts'], 2) ?></td>
                        <td>₱<?= number_format($row['refunds'], 2) ?></td>
                        <td>₱<?= number_format($row['net'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$movement['rows']): ?><tr><td colspan="5">No receipts or refund payouts in this period.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($movement['returns']): ?>
        <div style="padding:16px;">
            <strong>Returned units recorded in this period:</strong>
            <?php foreach ($movement['returns'] as $row): ?>
                <span><?= esc(ucfirst($row['return_condition'])) ?>: <?= (int) $row['quantity'] ?> &nbsp;</span>
            <?php endforeach; ?>
            <p>Only resellable returns increase available stock. Other conditions remain in the return records.</p>
        </div>
    <?php endif; ?>
</div>
