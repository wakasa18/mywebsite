<?php if (!empty($products)): ?>
    <?php foreach ($products as $index => $p): ?>
        <tr class="product-result-row" data-index="<?= $index ?>">
            <td><?= esc($p['product_name']) ?></td>
            <td><?= esc($p['sku'] ?: '-') ?></td>
            <td>₱<?= number_format($p['price'], 2) ?></td>
            <td><?= esc($p['stock']) ?></td>
            <td>
                <form method="post" action="<?= site_url('cashier/sales/add-to-cart') ?>" class="add-to-cart-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="product_id" value="<?= (int)$p['product_id'] ?>">
                    <input type="number" name="quantity" value="1" min="1" max="<?= $p['stock'] ?>" style="width:70px;"
                        class="qty-input">
                    <button type="submit" class="btn add-btn">Add</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
<?php else: ?>
    <tr>
        <td colspan="5">No products found.</td>
    </tr>
<?php endif; ?>
