<?= $this->extend('layouts/staff') ?>
<?= $this->section('content') ?>

<div class="card">
    <div class="card-head">
        <div>
            <h2>Stock Transfer</h2>
            <p>Transfer inventory between branches.</p>
        </div>

        <a href="<?= site_url('admin/stock-transfer/create') ?>" class="btn btn-primary">+ New Transfer</a>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert success">
            <?= esc(session()->getFlashdata('success')) ?>
        </div>
        <div class="sp"></div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert danger">
            <?= esc(session()->getFlashdata('error')) ?>
        </div>
        <div class="sp"></div>
    <?php endif; ?>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>From Branch</th>
                    <th>To Branch</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Created By</th>
                    <th width="120">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($transfers)): ?>
                    <?php foreach ($transfers as $t): ?>
                        <tr>
                            <td>
                                <?= esc($t['id']) ?>
                            </td>
                            <td>
                                <?= esc($t['from_branch_name']) ?>
                            </td>
                            <td>
                                <?= esc($t['to_branch_name']) ?>
                            </td>
                            <td>
                                <?= esc(\App\Libraries\DisplayDate::date($t['transfer_date'])) ?>
                            </td>
                            <td>
                                <?php if ($t['status'] === 'approved'): ?>
                                    <span class="badge badge-success">Approved</span>
                                <?php elseif ($t['status'] === 'pending'): ?>
                                    <span class="badge badge-warning">Pending</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">
                                        <?= esc(ucfirst($t['status'])) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= esc($t['created_by_name'] ?: '-') ?>
                            </td>
                            <td>
                                <a href="<?= site_url('admin/stock-transfer/view/' . $t['id']) ?>"
                                    class="btn secondary">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="muted" style="text-align:center;">No transfers found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="paginationWrap"><?= $pager->links() ?></div>
</div>

<?= $this->endSection() ?>
