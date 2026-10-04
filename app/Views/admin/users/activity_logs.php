<?= $this->extend('layouts/staff') ?>
<?= $this->section('content') ?>

<div class="page-header" style="margin-bottom:20px;">
    <h1 style="margin:0;">Activity Logs</h1>
    <p class="muted" style="margin:6px 0 0;">View system activity history and track user actions.</p>
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

<div class="card">
    <div class="card-head">
        <div>
            <h2 style="margin:0;">Log Records</h2>
            <p class="muted" style="margin:6px 0 0;">Search and filter recorded system activities.</p>
        </div>

        <a class="btn secondary" href="<?= site_url('admin/users') ?>">← Back to Users</a>
    </div>

    <div class="card-body" style="padding:14px 20px;border-bottom:1.5px solid var(--line);">
    <!-- FILTER -->
    <form method="get" action="<?= site_url('admin/activity-logs') ?>" class="row"
        style="gap:10px; align-items:end; margin-bottom:20px; flex-wrap:wrap;">

        <div class="field" style="flex:1; min-width:240px; margin-bottom:0;">
            <label class="label">Search</label>
            <input type="text" name="search" class="input" value="<?= esc($search ?? '') ?>"
                placeholder="Search activity, full name, username">
        </div>

        <div class="field" style="min-width:180px; margin-bottom:0;">
            <label class="label">Date From</label>
            <input type="date" name="date_from" class="input" value="<?= esc($dateFrom ?? '') ?>">
        </div>

        <div class="field" style="min-width:180px; margin-bottom:0;">
            <label class="label">Date To</label>
            <input type="date" name="date_to" class="input" value="<?= esc($dateTo ?? '') ?>">
        </div>

        <div class="row" style="gap:10px;">
            <button type="submit" class="btn btn-secondary">Filter</button>
            <a href="<?= site_url('admin/activity-logs') ?>" class="btn secondary">Reset</a>
        </div>
    </form>
    </div>

    <!-- TABLE -->
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="width:90px;">ID</th>
                    <th style="min-width:180px;">User</th>
                    <th style="min-width:160px;">Username</th>
                    <th style="min-width:320px;">Activity</th>
                    <th style="min-width:180px;">Log Time</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($logs)): ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?= esc($log['id']) ?></td>
                            <td><strong><?= esc($log['full_name'] ?? 'Unknown User') ?></strong></td>
                            <td><?= esc($log['username'] ?? '-') ?></td>
                            <td><?= esc($log['activity']) ?></td>
                            <td><?= esc(\App\Libraries\DisplayDate::dateTime($log['log_time'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="muted" style="text-align:center; padding:20px;">
                            No activity logs found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- PAGINATION -->
    <?php if (!empty($pager)): ?>
        <div class="paginationWrap">
            <?= $pager->links() ?>
        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>