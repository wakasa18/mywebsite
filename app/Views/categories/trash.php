<?= $this->extend('layouts/staff') ?>
<?= $this->section('content') ?>

<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:20px;">
    <div>
        <h1>🗑 Category Trash</h1>
        <p>Restore a category or delete it permanently from this system. Database records are retained.</p>
    </div>
    <a href="<?= site_url('categories') ?>" class="btn btn-secondary">← Back to Categories</a>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert success"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert error"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<!-- Search bar -->
<form method="get" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:18px;">
    <input type="text" name="keyword" class="input" style="max-width:300px;"
        placeholder="Search trashed categories…" value="<?= esc($keyword) ?>">
    <button type="submit" class="btn btn-primary">Search</button>
    <a href="<?= site_url('categories/trash') ?>" class="btn btn-secondary">Reset</a>
</form>

<div class="card">
    <div class="table-wrap">
        <table class="mobile-record-table" data-record-title="1" aria-label="Deleted categories">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Category Name</th>
                    <th>Moved to Trash</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($categories)): ?>
                    <?php foreach ($categories as $i => $cat): ?>
                    <tr>
                        <td class="muted" style="font-size:13px;"><?= $i + 1 ?></td>
                        <td style="font-weight:700;font-size:15px;"><?= esc($cat['category_name']) ?></td>
                        <td style="font-size:13px;color:var(--muted);"><?= esc(\App\Libraries\DisplayDate::dateTime($cat['deleted_at'])) ?></td>
                        <td>
                            <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                <form method="post" action="<?= site_url('categories/restore/' . $cat['id']) ?>" class="inline-action-form" onsubmit="return confirm('Restore this category?');">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-secondary" data-busy-label="Restoring…">Restore</button>
                                </form>
                                <form method="post" action="<?= site_url('categories/force-delete/' . $cat['id']) ?>" class="inline-action-form" onsubmit="return confirm('Delete this category permanently from the system and Trash? Its database record is retained. You cannot restore it here.');">
                                    <?= csrf_field() ?><button type="submit" class="btn btn-sm btn-danger" data-busy-label="Removing…">Delete permanently</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" style="text-align:center;padding:40px;color:var(--muted);">Trash is empty.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if (!empty($pager)): ?>
    <div class="paginationWrap">
        <?= $pager->links() ?>
    </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
