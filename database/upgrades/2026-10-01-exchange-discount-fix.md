# Exchange discount eligibility fix

Package: `build/hostinger-exchange-discount-fix-2026-10-01.zip`.

Install after the Product Exchange update. This patch replaces its discount eligibility behavior; no database change is required.

## What changed

Previously, the minimum-purchase check considered only replacement products. A customer exchanging one item from a qualifying three-product order could therefore lose the discount even while keeping the other two products.

When reusing the same discount recorded on the original sale, the check now includes eligible products the customer keeps plus the eligible replacements. Kept products retain their recorded discounts and are not charged or discounted again. Prior returned quantities do not count. Linked replacements under the same discount are included, so another exchange does not lose the order context or receive a second full fixed discount.

For example, an original order of PHP 100 + PHP 110 + PHP 55 has a 20% discount with a PHP 200 minimum. Exchanging the PHP 55 item for another eligible PHP 55 item counts the PHP 210 of kept products toward the minimum. Return credit is PHP 44, replacement total is PHP 44, and the payment difference is zero.

- The original discount is preselected if still active and within its valid dates. An explicit No discount selection is preserved.
- Current product/category restrictions still apply. A different discount must qualify using the replacements themselves.
- Fixed discounts and percentage caps share their allowance with the discounts already retained on kept items. The full order discount is not granted a second time.
- If the updated eligible order falls below the minimum, the error states the required amount and the qualifying amount. A product/category mismatch has a separate explanation.
- The review shows how much kept merchandise counts toward the minimum. Eligibility and remaining discount allowance are rechecked during confirmation.
- Existing prices, return credits, original item records and completed exchanges are not rewritten by this patch.

## Upload

Back up and replace all three files in the same project paths:

1. `app/Libraries/ProductExchange.php`
2. `app/Controllers/Cashier/ExchangeController.php`
3. `app/Views/cashier/sales/exchange.php`

Reopen the exchange page and review it again after uploading. A review created before this patch may require a fresh review before confirmation. The existing Product Exchange routes and supporting libraries are required. The package has not been uploaded to Hostinger.

## Validation

22 focused tests passed with 128 assertions, including the three-product minimum-purchase case, a repeated exchange, fixed allowance across earlier replacement invoices, capped percentage discounts, scope/minimum error messages, and original-discount preselection. Existing exchange, refund, checkout and correction checks also passed.

All three changed PHP files passed syntax checks. The browser passed 40 exchange layouts across mobile, tablet and desktop widths in light and dark themes, plus search, form and receipt checks. Tests used local fixtures and in-memory SQLite, not production transactions or native MySQL concurrency.
