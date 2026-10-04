<?= $this->extend('layouts/staff') ?>
<?= $this->section('content') ?>

<?php
$formatBytes = static function (int $bytes): string {
    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024) return number_format($bytes / 1024, 1) . ' KB';
    return $bytes . ' B';
};
?>

<style>
.backup-page { width: min(1120px, 100%); margin: 0 auto; min-width: 0; }
.backup-page * { box-sizing: border-box; }
.backup-page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
    margin-bottom: 18px;
}
.backup-page-header h1 { margin: 0; }
.backup-page-header p { margin: 5px 0 0; color: var(--muted); line-height: 1.5; }
.backup-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.05fr) minmax(330px, .95fr);
    gap: 18px;
    align-items: start;
}
.backup-card { overflow: hidden; }
.backup-card-head {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 18px 20px;
    border-bottom: 1px solid var(--line);
}
.backup-card-head .icon {
    width: 42px;
    height: 42px;
    flex: 0 0 auto;
    border-radius: 12px;
    display: grid;
    place-items: center;
    color: var(--primary);
    background: var(--primary-light);
}
.backup-card-head h2 { margin: 0; font-size: 17px; }
.backup-card-head p { margin: 4px 0 0; color: var(--muted); font-size: 13px; line-height: 1.45; }
.backup-card-body { padding: 20px; }
.backup-create-box {
    border: 1px solid var(--line);
    background: var(--surface-2);
    border-radius: 12px;
    padding: 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
}
.backup-create-box strong { display: block; margin-bottom: 4px; }
.backup-create-box span { color: var(--muted); font-size: 12px; line-height: 1.45; }
.backup-list { margin-top: 16px; display: grid; gap: 10px; }
.backup-item {
    border: 1px solid var(--line);
    border-radius: 12px;
    background: var(--surface);
    padding: 14px;
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 12px;
    align-items: center;
}
.backup-item-name { font-weight: 750; overflow-wrap: anywhere; }
.backup-item-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 7px 12px;
    margin-top: 5px;
    color: var(--muted);
    font-size: 12px;
}
.backup-item-actions { display: flex; align-items: center; gap: 7px; }
.backup-item-actions form { margin: 0; }
.backup-empty {
    padding: 22px;
    text-align: center;
    border: 1px dashed var(--line-2);
    border-radius: 12px;
    color: var(--muted);
}
.restore-warning {
    border: 1px solid #f0b86a;
    background: #fff8e8;
    color: #744600;
    border-radius: 11px;
    padding: 13px 14px;
    font-size: 13px;
    line-height: 1.5;
    margin-bottom: 16px;
}
html.dark .restore-warning { background: rgba(180,111,0,.18); color: #ffd98b; border-color: rgba(240,184,106,.45); }
.restore-source-tabs {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    margin-bottom: 14px;
}
.restore-source-option { position: relative; }
.restore-source-option input { position: absolute; opacity: 0; pointer-events: none; }
.restore-source-option span {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 43px;
    padding: 9px 12px;
    border: 1px solid var(--line-2);
    border-radius: 9px;
    font-weight: 700;
    color: var(--text-2);
    background: var(--surface);
    cursor: pointer;
}
.restore-source-option input:checked + span {
    color: var(--primary);
    border-color: var(--primary);
    background: var(--primary-light);
}
.restore-source-panel[hidden] { display: none !important; }
.restore-fields { display: grid; gap: 13px; }
.restore-field label { display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px; }
.restore-field .form-control { width: 100%; }
.restore-help { color: var(--muted); font-size: 11px; line-height: 1.45; margin-top: 5px; }
.restore-submit { width: 100%; min-height: 46px; margin-top: 3px; }
.restore-submit:disabled { opacity: .55; cursor: not-allowed; }
.backup-info-strip {
    margin-top: 16px;
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 9px;
}
.backup-info-strip div {
    border-radius: 10px;
    padding: 11px;
    background: var(--surface-2);
    border: 1px solid var(--line);
}
.backup-info-strip strong { display: block; font-size: 13px; }
.backup-info-strip span { display: block; color: var(--muted); font-size: 11px; margin-top: 3px; }
@media (max-width: 900px) {
    .backup-grid { grid-template-columns: 1fr; }
}
@media (max-width: 620px) {
    .backup-page-header { display: block; }
    .backup-card-head, .backup-card-body { padding: 16px; }
    .backup-create-box { align-items: stretch; flex-direction: column; }
    .backup-create-box .btn { width: 100%; min-height: 44px; }
    .backup-item { grid-template-columns: 1fr; }
    .backup-item-actions { display: grid; grid-template-columns: 1fr 1fr; }
    .backup-item-actions .btn, .backup-item-actions form, .backup-item-actions form button { width: 100%; }
    .restore-source-tabs, .backup-info-strip { grid-template-columns: 1fr; }
}
</style>

<div class="backup-page">
    <div class="backup-page-header">
        <div>
            <h1>Backup &amp; Restore</h1>
            <p>Create a safe copy of the database or restore a compatible backup when recovery is needed.</p>
        </div>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert error"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <?php if (!$storageReady): ?>
        <div class="alert error">Backup storage is unavailable: <?= esc($storageError ?? 'Unknown error') ?></div>
    <?php endif; ?>

    <div class="backup-grid">
        <section class="card backup-card">
            <div class="backup-card-head">
                <div class="icon" aria-hidden="true">
                    <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M4 7V4h12l4 4v12H4V7z"/><path d="M8 4v6h8V4M8 20v-6h8v6"/></svg>
                </div>
                <div>
                    <h2>Database backups</h2>
                    <p>Backups include products, branch stock, sales, users, refunds, reports, and activity records.</p>
                </div>
            </div>
            <div class="backup-card-body">
                <div class="backup-create-box">
                    <div>
                        <strong>Create a new backup</strong>
                        <span>The file is stored outside the public website folder and can be downloaded afterward.</span>
                    </div>
                    <form method="post" action="<?= site_url('admin/backup-restore/create') ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-primary" data-busy-label="Creating…" <?= !$storageReady ? 'disabled' : '' ?>>Create Backup</button>
                    </form>
                </div>

                <div class="backup-list">
                    <?php if (empty($backups)): ?>
                        <div class="backup-empty">No stored backup yet. Create one before major updates or data changes.</div>
                    <?php else: ?>
                        <?php foreach ($backups as $backup): ?>
                            <article class="backup-item">
                                <div>
                                    <div class="backup-item-name"><?= esc($backup['filename']) ?></div>
                                    <div class="backup-item-meta">
                                        <span><?= esc(\App\Libraries\DisplayDate::dateTime($backup['created_at'])) ?></span>
                                        <span><?= number_format((int) $backup['row_count']) ?> rows</span>
                                        <span><?= number_format((int) $backup['table_count']) ?> tables</span>
                                        <span><?= esc($formatBytes((int) $backup['size'])) ?></span>
                                        <?php if (($backup['label'] ?? '') === 'pre-restore'): ?><span>Safety copy</span><?php endif; ?>
                                    </div>
                                </div>
                                <div class="backup-item-actions">
                                    <a class="btn btn-secondary" href="<?= site_url('admin/backup-restore/download/' . rawurlencode($backup['filename'])) ?>">Download</a>
                                    <form method="post" action="<?= site_url('admin/backup-restore/delete/' . rawurlencode($backup['filename'])) ?>" onsubmit="return confirm('Delete this stored backup? Download a copy first if you may need it later.')">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-danger" data-busy-label="Deleting…">Delete</button>
                                    </form>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="card backup-card">
            <div class="backup-card-head">
                <div class="icon" aria-hidden="true">
                    <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l3 2"/></svg>
                </div>
                <div>
                    <h2>Restore database</h2>
                    <p>Replace current data with a stored or uploaded Pharxmaco backup.</p>
                </div>
            </div>
            <div class="backup-card-body">
                <div class="restore-warning">
                    <strong>Important:</strong> Restore replaces the current database data. The system automatically creates a safety backup first and rolls back if any row fails.
                </div>

                <form method="post" action="<?= site_url('admin/backup-restore/restore') ?>" enctype="multipart/form-data" id="restoreForm">
                    <?= csrf_field() ?>

                    <div class="restore-source-tabs">
                        <label class="restore-source-option">
                            <input type="radio" name="restore_source" value="stored" <?= old('restore_source', 'stored') === 'stored' ? 'checked' : '' ?>>
                            <span>Stored backup</span>
                        </label>
                        <label class="restore-source-option">
                            <input type="radio" name="restore_source" value="upload" <?= old('restore_source') === 'upload' ? 'checked' : '' ?>>
                            <span>Upload file</span>
                        </label>
                    </div>

                    <div class="restore-fields">
                        <div class="restore-source-panel" data-source-panel="stored">
                            <div class="restore-field">
                                <label for="storedBackup">Choose stored backup</label>
                                <select class="form-control" id="storedBackup" name="stored_backup">
                                    <option value="">Select a backup</option>
                                    <?php foreach ($backups as $backup): ?>
                                        <option value="<?= esc($backup['filename']) ?>" <?= old('stored_backup') === $backup['filename'] ? 'selected' : '' ?>>
                                            <?= esc($backup['filename']) ?> — <?= esc(\App\Libraries\DisplayDate::dateTime($backup['created_at'])) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="restore-source-panel" data-source-panel="upload" hidden>
                            <div class="restore-field">
                                <label for="backupFile">Pharxmaco backup file</label>
                                <input class="form-control" type="file" id="backupFile" name="backup_file" accept=".pmbak,application/octet-stream">
                                <div class="restore-help">Maximum accepted upload on this server: <?= (int) $uploadLimitMb ?> MB. Only .pmbak files created by this system are accepted.</div>
                            </div>
                        </div>

                        <div class="restore-field">
                            <label for="adminPassword">Current administrator password</label>
                            <input class="form-control" type="password" id="adminPassword" name="admin_password" autocomplete="current-password" required>
                        </div>

                        <div class="restore-field">
                            <label for="restoreConfirmation">Type RESTORE to confirm</label>
                            <input class="form-control" type="text" id="restoreConfirmation" name="confirmation" value="<?= esc(old('confirmation')) ?>" autocomplete="off" required>
                            <div class="restore-help">Do not close the page while restoration is running.</div>
                        </div>

                        <button type="submit" class="btn btn-danger restore-submit" id="restoreSubmit" data-no-submit-lock="true" disabled>Restore Database</button>
                    </div>
                </form>

                <div class="backup-info-strip">
                    <div><strong>Transactional</strong><span>Failed data restoration is rolled back.</span></div>
                    <div><strong>Compatible only</strong><span>Table structure must match the backup.</span></div>
                    <div><strong>Admin protected</strong><span>Password and confirmation are required.</span></div>
                </div>
            </div>
        </section>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('restoreForm');
    const sourceInputs = Array.from(document.querySelectorAll('input[name="restore_source"]'));
    const panels = Array.from(document.querySelectorAll('[data-source-panel]'));
    const confirmation = document.getElementById('restoreConfirmation');
    const password = document.getElementById('adminPassword');
    const storedBackup = document.getElementById('storedBackup');
    const backupFile = document.getElementById('backupFile');
    const submit = document.getElementById('restoreSubmit');

    function selectedSource() {
        return sourceInputs.find(input => input.checked)?.value || 'stored';
    }

    function refreshSourcePanels() {
        const source = selectedSource();
        panels.forEach(panel => { panel.hidden = panel.dataset.sourcePanel !== source; });
        if (storedBackup) storedBackup.required = source === 'stored';
        if (backupFile) backupFile.required = source === 'upload';
        refreshSubmit();
    }

    function refreshSubmit() {
        const source = selectedSource();
        const sourceReady = source === 'stored' ? Boolean(storedBackup?.value) : Boolean(backupFile?.files?.length);
        const confirmed = (confirmation?.value || '').trim().toUpperCase() === 'RESTORE';
        const passwordReady = Boolean(password?.value);
        if (submit) submit.disabled = !(sourceReady && confirmed && passwordReady);
    }

    sourceInputs.forEach(input => input.addEventListener('change', refreshSourcePanels));
    [confirmation, password, storedBackup, backupFile].forEach(element => {
        element?.addEventListener('input', refreshSubmit);
        element?.addEventListener('change', refreshSubmit);
    });

    form?.addEventListener('submit', function (event) {
        if (!window.confirm('Restore this backup now? Current data will be replaced after a safety backup is created.')) {
            event.preventDefault();
            return;
        }
        if (submit) {
            submit.disabled = true;
            submit.textContent = 'Restoring… Do not close this page';
        }
    });

    refreshSourcePanels();
});
</script>

<?= $this->endSection() ?>
