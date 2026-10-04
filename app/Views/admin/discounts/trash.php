<?= $this->extend('layouts/staff') ?>
<?= $this->section('content') ?>

<style>
    .trash-head { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; flex-wrap:wrap; }
    .trash-search { display:flex; gap:9px; flex-wrap:wrap; padding:15px 18px; border-bottom:1px solid var(--line); }
    .trash-search .input { flex:1 1 240px; max-width:380px; }
    .trash-actions { display:flex; gap:6px; flex-wrap:wrap; }
    .trash-table { min-width:850px; }
    .trash-note { padding:12px 14px; margin-bottom:16px; border:1px solid var(--line); border-radius:10px; background:var(--surface-2); font-size:12.5px; line-height:1.5; color:var(--muted); }
    @media(max-width:600px){ .trash-search .btn { flex:1; justify-content:center; } .trash-head>a { width:100%; justify-content:center; } }
</style>

<div class="page-header trash-head">
    <div><h1>Discount Trash</h1><p>Restore a discount or delete it permanently from this system. Database records and past usage are retained.</p></div>
    <a href="<?= site_url('admin/discounts') ?>" class="btn btn-secondary">← Back to Discounts</a>
</div>

<?php if (session()->getFlashdata('success')): ?><div class="alert success"><?= esc(session()->getFlashdata('success')) ?></div><?php endif; ?>
<?php if (session()->getFlashdata('error')): ?><div class="alert danger"><?= esc(session()->getFlashdata('error')) ?></div><?php endif; ?>

<div class="trash-note">Delete permanently also removes the discount from Trash and prevents restoring it here. Past sales and their discount values remain unchanged.</div>

<div class="card">
    <form method="get" action="<?= site_url('admin/discounts/trash') ?>" class="trash-search">
        <input type="search" name="keyword" class="input" placeholder="Search trashed discounts" value="<?= esc($keyword) ?>">
        <button type="submit" class="btn btn-primary">Search</button>
        <a href="<?= site_url('admin/discounts/trash') ?>" class="btn btn-secondary">Reset</a>
    </form>

    <div class="table-wrap">
        <table class="trash-table">
            <thead><tr><th>Discount</th><th>Amount</th><th>Products Included</th><th>Used in Sales</th><th>Deleted</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (!empty($discounts)): ?>
                <?php foreach ($discounts as $discount): ?>
                <tr>
                    <td><strong><?= esc($discount['discount_name']) ?></strong><?php if (!empty($discount['description'])): ?><div class="muted" style="font-size:11.5px;margin-top:3px;"><?= esc($discount['description']) ?></div><?php endif; ?></td>
                    <td><strong><?= $discount['discount_type'] === 'percentage' ? rtrim(rtrim(number_format((float) $discount['discount_value'],2),'0'),'.') . '%' : '₱' . number_format((float) $discount['discount_value'],2) ?></strong></td>
                    <td><?= $discount['applies_to'] === 'all' ? 'All products' : ($discount['applies_to'] === 'category' ? 'One category' : 'One product') ?></td>
                    <td><strong><?= (int) ($discount['usage_count'] ?? 0) ?></strong></td>
                    <td><?= !empty($discount['deleted_at']) ? esc(\App\Libraries\DisplayDate::dateTime($discount['deleted_at'])) : '—' ?></td>
                    <td>
                        <div class="trash-actions">
                            <form method="post" action="<?= site_url('admin/discounts/restore/' . (int) $discount['id']) ?>" class="inline-action-form" onsubmit="return confirm('Restore this discount? Review its status and dates before using it.');">
                                <?= csrf_field() ?><button type="submit" class="btn btn-sm btn-secondary" data-busy-label="Restoring…">Restore</button>
                            </form>
                            <form method="post" action="<?= site_url('admin/discounts/force-delete/' . (int)$discount['id']) ?>" class="inline-action-form" onsubmit="return confirm('Delete this discount permanently from the system and Trash? Its database record and past sales are retained. You cannot restore it here.');">
                                <?= csrf_field() ?><button type="submit" class="btn btn-sm btn-danger" data-busy-label="Removing…">Delete permanently</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--muted);">Trash is empty.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if (!empty($pager)): ?><div class="paginationWrap"><?= $pager->only(['keyword'])->links() ?></div><?php endif; ?>
</div>

<?= $this->endSection() ?>
