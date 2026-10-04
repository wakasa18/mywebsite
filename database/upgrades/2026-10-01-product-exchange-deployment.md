# Product exchanges

Package: `build/hostinger-product-exchange-2026-10-01.zip`.

## Staff workflow

1. Open Sales History and select **Exchange Product** on a completed or partially refunded sale. The action is also available in Sale Details.
2. Enter the returned quantities and each item's condition. Quarantine is the default; only items explicitly marked Resellable return to available stock. Expired inventory cannot be restocked as resellable.
3. Choose replacements from current, active, unexpired stock in the original selling branch. Search keeps selected products visible. The regular POS cart is preserved.
4. Select an optional current discount for the replacement items, give a reason, and select Cash, GCash or Card for settlement. A reference is required for a nonzero GCash/card difference.
5. Click **Review exchange**. Check the returned value, replacement total and exact amount to collect or pay back. Even exchanges show zero money changing hands.
6. Settle the displayed difference, check the confirmation box, and click **Confirm exchange**. The exchange receipt links to the original receipt and the exchange return slip; the return slip links back to the replacement receipt.

Return credit uses the original item amounts after discounts, less prior returns, with centavo rounding. Original discounts are not automatically copied to replacements: current replacement prices and the separately selected eligible discount apply. The exchange screen records the exact settlement difference; it does not calculate change for an overpayment or connect to payment-provider APIs.

Cashiers can exchange sales only in their assigned branch. Administrators can exchange a sale in its original branch. Exchanges require that branch to be active. Products must have available stock before the exchange; a returned unit is not offered as replacement stock before inspection and confirmation.

## Records and safeguards

- The original transaction is retained and marked partially or fully refunded according to returned quantities.
- The return, replacement sale, inventory changes and activity log are saved in one database transaction. Failure rolls back the exchange.
- The original sale and affected inventory are locked during confirmation. Prices, discounts, stock availability and remaining return quantities are checked again. Changed monetary values require a new review.
- A repeated confirmation for the same reviewed exchange returns the existing receipt rather than creating another sale.
- Exchange invoice numbers use `EXC-{original sale ID}-{return event ID}`. Existing invoice and return-event fields preserve the link without new database columns. Keep these identifiers intact.
- Replacement invoices include both return credit and any additional payment in their settled total. Receipts and Sale Details show the actual additional payment and payout separately. Cash-movement reports subtract applied exchange credit from both receipts and refund payouts, so only money actually changing hands appears in those totals. Sales and return reports retain the full merchandise values.
- Exchange replacement invoices cannot use Sale Correction, which could break their settlement links. They can be refunded or exchanged again using their remaining quantities.

## Upload

Back up and replace all 13 runtime files together, retaining their paths:

1. `app/Config/Routes.php`
2. `app/Controllers/Cashier/ExchangeController.php` (new)
3. `app/Controllers/Cashier/SalesController.php`
4. `app/Controllers/Admin/SaleCorrection.php`
5. `app/Libraries/ProductExchange.php` (new)
6. `app/Libraries/ExchangeRecord.php` (new)
7. `app/Libraries/CashMovementReport.php`
8. `app/Views/cashier/sales/exchange.php` (new)
9. `app/Views/cashier/sales/history.php`
10. `app/Views/cashier/sales/details.php`
11. `app/Views/cashier/sales/receipt.php`
12. `app/Views/cashier/sales/refund_slip.php`
13. `app/Views/admin/reports/cash_movements.php`

No database migration or SQL import is required. This update assumes the existing refund safeguard fields (`return_condition`, `refund_method`, `refund_event_id`), discount libraries, readable dates and thermal receipt update are already installed. The affected database tables must use a transactional engine such as InnoDB, as required by the existing sale/refund logic. Upload the libraries before the views and controllers, or pause use while replacing the complete package.

This package has not been uploaded to Hostinger. It includes application code only; local test fixtures and test data are excluded.

## Verification

- 17 targeted PHP tests passed (108 assertions): different-product exchange, partial and full returns, original discount rounding, replacement discounts, even/cheaper/more expensive replacements, quarantine, expired inventory, branch restrictions, invalid quantities, stale prices, rollback after a simulated write failure, repeated confirmation, receipt links and ordinary refund/checkout/correction behavior.
- 40 exchange layout checks passed across 320, 390, 768 and 1440 px widths in light and dark themes, including search, CSRF fields and 58/80 mm receipt layout checks.
- Existing receipts passed 66 screen/print checks. Existing table actions passed 56 layout/interaction checks, including the new Sales History button.
- New and changed runtime PHP files passed syntax checks. Mobile selection and desktop dark-mode review screens were visually reviewed.

Tests use in-memory SQLite and local browser fixtures. The locking tests simulate changed records; they do not verify MySQL's concurrent scheduling. No production database was modified, and physical printer output was not tested. Before using real transactions, try one exchange in a test copy of the Hostinger installation and check both stock movements, receipts and the day's settlement totals.
