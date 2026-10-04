<?= $this->extend('layouts/staff') ?>
<?= $this->section('content') ?>

<style>
    .category-filters { padding: 0 20px 16px; }
    .category-search-form { display: grid; grid-template-columns: minmax(0, 1fr) auto; align-items: end; gap: 10px; }
    .category-search-form .field { min-width: 0; margin: 0; }
    .category-search-actions, .category-card-actions { display: flex; flex-wrap: wrap; gap: 10px; }
    .category-search-form .auto-filter-status { grid-column: 1 / -1; }
    .cat-products-hint {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
        margin-top: 5px;
    }
    .cat-pill {
        display: inline-flex;
        align-items: center;
        padding: 2px 9px;
        border-radius: 999px;
        font-size: 11.5px;
        font-weight: 500;
        background: var(--primary-light);
        color: var(--primary);
        border: 1px solid rgba(43,127,255,.15);
        max-width: 100%;
        min-width: 0;
        white-space: normal;
        overflow-wrap: anywhere;
    }
    .cat-pill.more {
        background: var(--surface-2);
        color: var(--muted);
        border-color: var(--line);
    }
    .cat-count-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
    }
    .cat-count-badge.has-products {
        background: var(--success-bg);
        color: var(--success-text);
    }
    .cat-count-badge.no-products {
        background: var(--surface-2);
        color: var(--muted);
        border: 1px solid var(--line);
    }
    .category-count { text-align: center; }
    @media (max-width: 700px) {
        .categories-table.mobile-record-ready .category-id { grid-column: 1; grid-row: 2; }
        .categories-table.mobile-record-ready .category-count { grid-column: 2; grid-row: 2; text-align: left; }
        .categories-table.mobile-record-ready .category-products { grid-column: 1 / -1; grid-row: 3; }
        .categories-table.mobile-record-ready .category-created { grid-column: 1 / -1; grid-row: 4; }
        .categories-table .cat-products-hint { gap: 6px; }
        .categories-table .cat-pill { padding: 4px 9px; font-size: 12px; line-height: 1.5; }
        .categories-table .record-actions > .row { align-items: stretch; }
        .categories-table .record-actions .row > .btn,
        .categories-table .record-actions .inline-action-form { flex: 1 1 100px; }
        .categories-table .inline-action-form .btn { width: 100%; height: 100%; }
    }
    @media (max-width: 600px) {
        .category-filters { padding: 0 16px 16px; }
        .category-search-form { grid-template-columns: minmax(0, 1fr); gap: 12px; }
        .category-search-actions .btn, .category-card-actions .btn { flex: 1; min-height: 44px; }
        .category-card-actions { width: 100%; }
    }
</style>

<div class="page-header">
    <h1>Categories</h1>
    <p>Manage product categories for Pharxmaco Drugstore.</p>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert success"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-head">
        <div>
            <h2>Category List</h2>
            <p>Each category groups related products. The pills below show which products belong to it.</p>
        </div>
        <div class="category-card-actions">
            <a class="btn btn-primary" href="<?= site_url('categories/create') ?>">+ Add Category</a>
            <a class="btn btn-secondary" href="<?= site_url('categories/trash') ?>"><?= view('partials/icon', ['name' => 'trash']) ?> Trash</a>
        </div>
    </div>

    <div class="category-filters">
        <form method="get" action="<?= site_url('categories') ?>" class="category-search-form">
            <div class="field">
                <label class="label" for="categorySearch">Search category</label>
                <input type="search" id="categorySearch" name="keyword" class="input" value="<?= esc($keyword ?? '') ?>"
                    placeholder="Enter category name">
            </div>
            <div class="category-search-actions">
                <button type="submit" class="btn btn-secondary">Search</button>
                <a href="<?= site_url('categories') ?>" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>

    <div class="table-wrap">
        <table class="mobile-record-table categories-table" data-record-title="1" aria-label="Categories">
            <thead>
                <tr>
                    <th style="width:60px;">ID</th>
                    <th>Category Name</th>
                    <th>Products in this Category</th>
                    <th style="width:90px; text-align:center;">Count</th>
                    <th style="min-width:160px;">Created At</th>
                    <th style="min-width:130px;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($categories)): ?>
                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td class="muted category-id"><?= esc($category['id']) ?></td>
                            <td>
                                <strong style="font-size:14.5px;"><?= esc($category['category_name']) ?></strong>
                            </td>
                            <td class="category-products">
                                <?php if (!empty($category['sample_products'])): ?>
                                    <div class="cat-products-hint">
                                        <?php foreach ($category['sample_products'] as $pname): ?>
                                            <span class="cat-pill"><?= esc($pname) ?></span>
                                        <?php endforeach; ?>
                                        <?php $more = $category['product_count'] - count($category['sample_products']); ?>
                                        <?php if ($more > 0): ?>
                                            <span class="cat-pill more">+<?= $more ?> more</span>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="muted" style="font-size:12.5px; font-style:italic;">No products assigned yet</span>
                                <?php endif; ?>
                            </td>
                            <td class="category-count">
                                <span class="cat-count-badge <?= $category['product_count'] > 0 ? 'has-products' : 'no-products' ?>">
                                    <?= $category['product_count'] ?>
                                </span>
                            </td>
                            <td class="muted category-created" style="font-size:12.5px;"><?= esc(\App\Libraries\DisplayDate::dateTime($category['created_at'] ?? '—')) ?></td>
                            <td>
                                <div class="row" style="gap:7px; flex-wrap:wrap;">
                                    <a href="<?= site_url('categories/edit/' . $category['id']) ?>"
                                        class="btn btn-secondary btn-sm">Edit</a>
                                    <form method="post" action="<?= site_url('categories/delete/' . $category['id']) ?>" class="inline-action-form"
                                        onsubmit="return confirm('Move <?= esc(addslashes($category['category_name'])) ?> to trash?');">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-danger btn-sm" data-busy-label="Moving…">Move to Trash</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="muted" style="text-align:center; padding:24px;">
                            No categories found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if (!empty($pager)): ?>
        <div class="paginationWrap"><?= $pager->links() ?></div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
