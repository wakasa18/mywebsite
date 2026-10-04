<?= $this->extend('layouts/staff') ?>
<?= $this->section('content') ?>

<div class="page-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:20px;">
    <div>
        <h1>🗑 Supplier Trash</h1>
        <p>Restore a supplier or delete it permanently from this system. Database records and history are retained.</p>
    </div>
    <a href="<?= site_url('admin/suppliers') ?>" class="btn btn-secondary">← Back to Suppliers</a>
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
        placeholder="Search name, contact, email…" value="<?= esc($keyword) ?>">
    <button type="submit" class="btn btn-primary">Search</button>
    <a href="<?= site_url('admin/suppliers/trash') ?>" class="btn btn-secondary">Reset</a>
</form>

<div class="card">
    <div class="table-wrap">
        <table class="mobile-record-table" data-record-title="1" aria-label="Deleted suppliers">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Supplier Name</th>
                    <th>Contact Person</th>
                    <th>Contact Number</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Moved to Trash</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($suppliers)): ?>
                    <?php foreach ($suppliers as $i => $s): ?>
                    <tr>
                        <td class="muted" style="font-size:13px;"><?= $i + 1 ?></td>
                        <td style="font-weight:700;font-size:15px;"><?= esc($s['supplier_name']) ?></td>
                        <td><?= esc($s['contact_person'] ?: '—') ?></td>
                        <td style="font-family:'DM Mono',monospace;"><?= esc($s['contact_number'] ?: '—') ?></td>
                        <td style="max-width:220px;overflow-wrap:anywhere;">
                            <?php if (!empty($s['email'])): ?>
                                <a href="mailto:<?= esc($s['email'], 'attr') ?>"><?= esc($s['email']) ?></a>
                            <?php else: ?>
                                <span class="muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($s['status'] === 'active'): ?>
                                <span class="badge badge-success">Active</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:13px;color:var(--muted);"><?= esc(\App\Libraries\DisplayDate::dateTime($s['deleted_at'])) ?></td>
                        <td>
                            <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                <form method="post" action="<?= site_url('admin/suppliers/restore/' . $s['id']) ?>" class="inline-action-form" onsubmit="return confirm('Restore this supplier?');">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-secondary" data-busy-label="Restoring…">Restore</button>
                                </form>
                                <form method="post" action="<?= site_url('admin/suppliers/force-delete/' . $s['id']) ?>" class="inline-action-form" onsubmit="return confirm('Delete this supplier permanently from the system and Trash? Its database record and history are retained. You cannot restore it here.');">
                                    <?= csrf_field() ?><button type="submit" class="btn btn-sm btn-danger" data-busy-label="Removing…">Delete permanently</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--muted);">Trash is empty.</td></tr>
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
