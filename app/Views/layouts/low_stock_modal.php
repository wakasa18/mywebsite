<?php if ((int) ($notificationData['low_stock_total'] ?? 0) > 0): ?>
    <?php
        $lowStockProducts = is_array($notificationData['low_stock_products'] ?? null)
            ? $notificationData['low_stock_products']
            : [];
        $lowStockShown = count($lowStockProducts);
        $lowStockTotal = (int) ($notificationData['low_stock_total'] ?? $lowStockShown);
    ?>
    <div class="inventory-modal" id="lowStockModal" aria-hidden="true">
        <div class="inventory-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="lowStockModalTitle" aria-describedby="lowStockModalDescription">
            <div class="inventory-modal-header">
                <div class="inventory-modal-title-wrap">
                    <div class="inventory-modal-title" id="lowStockModalTitle">
                        <span class="inventory-modal-title-icon" aria-hidden="true">
                            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        </span>
                        Low-stock products
                    </div>
                    <div class="inventory-modal-subtitle" id="lowStockModalDescription">
                        These products are already at or below their reorder level. The reorder forecast suggests how much to order for the next 30 days based on recent sales. <?= esc($notificationData['scope_label'] ?? 'Inventory') ?>.
                    </div>
                </div>
                <button type="button" class="inventory-modal-close" data-close-low-stock aria-label="Close low-stock products popup">
                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <div class="inventory-modal-tools">
                <div class="inventory-modal-count" role="status" aria-live="polite">
                    <span id="lowStockVisibleCount"><?= $lowStockShown ?></span> of <?= $lowStockTotal ?> product<?= $lowStockTotal === 1 ? '' : 's' ?> shown
                </div>
                <label class="inventory-search-wrap">
                    <span class="sr-only">Search low-stock products</span>
                    <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="search" class="inventory-search" id="lowStockSearch" placeholder="Search product, SKU, or branch" autocomplete="off">
                </label>
            </div>

            <div class="low-stock-list" id="lowStockList">
                <div class="low-stock-row low-stock-row-head" aria-hidden="true">
                    <span>Product</span>
                    <span>Branch</span>
                    <span>Stock</span>
                    <span>Reorder level</span>
                    <span>Reorder forecast</span>
                    <span>Status</span>
                </div>

                <?php foreach ($lowStockProducts as $product): ?>
                    <?php
                        $stock = max(0, (int) ($product['stock'] ?? 0));
                        $reorderLevel = max(0, (int) ($product['reorder_level'] ?? 0));
                        $unit = trim((string) ($product['unit'] ?? ''));
                        $searchText = strtolower(implode(' ', [
                            (string) ($product['product_name'] ?? ''),
                            (string) ($product['sku'] ?? ''),
                            (string) ($product['branch_name'] ?? ''),
                        ]));
                    ?>
                    <div class="low-stock-row" data-low-stock-row data-search="<?= esc($searchText, 'attr') ?>">
                        <div class="low-stock-product">
                            <strong><?= esc($product['product_name'] ?? 'Unnamed product') ?></strong>
                            <small>SKU: <?= esc($product['sku'] ?: 'Not set') ?></small>
                        </div>
                        <div class="low-stock-cell" data-label="Branch"><?= esc($product['branch_name'] ?? 'Unknown branch') ?></div>
                        <div class="low-stock-cell low-stock-number" data-label="Current stock">
                            <?= $stock ?><?= $unit !== '' ? ' ' . esc($unit) : '' ?>
                        </div>
                        <div class="low-stock-cell low-stock-number" data-label="Reorder level">
                            <?= $reorderLevel ?><?= $unit !== '' ? ' ' . esc($unit) : '' ?>
                        </div>
                        <div class="low-stock-cell" data-label="Reorder forecast">
                            <?php if (!empty($product['forecast_has_demand']) && $product['reorder_forecast'] !== null): ?>
                                <span class="low-stock-forecast" title="Suggested quantity to order for the next 30 days">
                                    <?= (int) $product['reorder_forecast'] ?><?= $unit !== '' ? ' ' . esc($unit) : '' ?>
                                </span>
                            <?php else: ?>
                                <span class="low-stock-forecast no-demand" title="There are not enough recent sales to calculate a useful reorder forecast">Review manually</span>
                            <?php endif; ?>
                        </div>
                        <div class="low-stock-cell" data-label="Status">
                            <span class="low-stock-badge<?= $stock === 0 ? ' out' : '' ?>">
                                <?= $stock === 0 ? 'Out of stock' : 'Low stock' ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="low-stock-empty-search" id="lowStockEmptySearch">
                    No low-stock product matches your search.
                </div>
            </div>

            <?php if ($lowStockTotal > $lowStockShown): ?>
                <div class="inventory-modal-note">
                    Showing the first <?= $lowStockShown ?> products to keep the page fast. Open the Products page to view the complete list.
                </div>
            <?php endif; ?>

            <div class="inventory-modal-footer">
                <button type="button" class="inventory-modal-done" data-close-low-stock>Close</button>
                <?php if (!empty($notificationData['low_stock_url'])): ?>
                    <a class="inventory-modal-link" href="<?= esc($notificationData['low_stock_url']) ?>">
                        Open Products
                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>
