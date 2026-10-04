# Recent forecast demonstration sales

Use **`recent-forecast-sales.sql`** in a **test copy** of the Pharxmaco database, through **SQLyog Community or phpMyAdmin**. Both use the same MySQL script. This inserts synthetic sales and sale items; it does not add columns or change application code.

These records will count toward sales, profit and cash reports. They are not real pharmacy transactions or Kaggle research data, and must not be presented as evidence of forecasting accuracy.

## What it generates

- The latest **30 calendar days**, ending on the MySQL server's current date (`CURDATE()`). For a fixed window, replace the first date assignment with, for example, `SET @phx_demo_end = '2026-09-27';`.
- One completed cash sale with one line item per eligible product/branch/day, using existing IDs, names, prices and costs. No customer or cashier is invented; the nullable cashier ID is left empty.
- Products must be active, not deleted, at or below their branch reorder level, and unexpired at the selected end date. Branches and branch inventory records must also be active. Prices/costs must fit the generated decimal totals.
- Each product/branch gets **2–5 units per day**, held constant across the window. This deliberate simple pattern makes it easy to check that the forecasting path produces a usable estimate.
- Invoices start with `DEMO-FC-`. Notes start with `PHX-DEMO-FORECAST-V1` and explicitly identify synthetic history.
- Existing invoice IDs are not overwritten. Re-importing the same dates skips those invoices. Importing on a later day adds newly eligible dates/products; older samples remain until removed.

Current stock is left unchanged so the low-stock scenario remains available for testing. These are historical training fixtures, not a complete stock-movement ledger: the script does not create deliveries, stock deductions, refunds, payment confirmations, or forecast snapshots. Do not refund or correct the demo invoices through the app.

## SQLyog Community (including 64-bit)

1. Connect to your MySQL server and select the **test database copy** in the database selector/Object Browser. The connection name alone does not select the intended database.
2. Open `recent-forecast-sales.sql` in a query tab, or paste the **entire file** into the editor.
3. Use **Edit > Execute Query > Execute All Queries** (default **Shift+F9**, the double green arrow). Ordinary F9 runs only the current statement. SQLyog documents both modes in [Executing SQL Queries](https://sqlyogkb.webyog.com/article/46-executing-sql-queries).
4. Inspect the first result for `selected_database`. The required-column checks run before inserts. If a table/column is missing, stop; that installation needs schema review rather than partial execution of this script.
5. Look for the final `Demo import complete` result and its date range. In the test app, use those dates in Reports and click Update Forecast.

Keep the whole run in **one connection**. The script intentionally uses session variables and temporary tables; do not reconnect or execute only the highlighted INSERT. If execution stops with an error, run `ROLLBACK;` in that same connection before investigating. Begin in a connection without other pending edits/transactions.

No SQLyog Enterprise feature, custom delimiter, stored procedure, hard-coded database name, or disabled foreign-key checks are required. The script uses the tables in the database you selected; it does not automatically target `pharxmaco_drugstore` or create a test database for you.

To remove fixtures, open `remove-recent-forecast-sales.sql` and execute the entire file the same way. Do not run the import and removal scripts together.

## phpMyAdmin

1. Export a backup and create/select a **test database copy** containing your existing products, branches and tables. Point a test installation at that copy.
2. In phpMyAdmin, click the test database name in the left sidebar. Do not select `information_schema`.
3. Choose **Import**, select `recent-forecast-sales.sql`, keep the format as **SQL**, and run it. Alternatively, paste the entire file into that database's SQL tab.
4. Check the final result: `Demo import complete`, the inserted count, and the `forecast_from` / `forecast_to` dates. A repeat import on the same date can correctly show zero new rows. Stop and investigate any SQL error.
5. In the test app, open Reports, select **All branches**, and set **Use sales from/to** to the dates returned by the script. Click **Update Forecast**.
6. Check Restock Suggestions. Eligible sampled products should now have demand-based estimates. Expired/ineligible products are intentionally not given artificial recent sales and may still require manual review. Existing non-demo history in the selected window can also affect predictions.

phpMyAdmin is an interface to MySQL/MariaDB; there is no separate phpMyAdmin data format. The same SQL can be executed in a MySQL client with the intended test database selected. No database name, password, server path, routine creation, or `USE` statement is embedded in the file.

## Remove the fixtures

Import **`remove-recent-forecast-sales.sql`** into the same test database. It deletes only matching demo sale items and their marked sales. Unrelated records and stock quantities are retained.

Sales that have refunds or matching stock logs are excluded for review. If you used demo receipts for further transactions, restore the test backup instead of treating this cleanup as a reversal of those actions.

Saved forecast snapshots are **not** removed: their rows may mix real and synthetic inputs and cannot safely be identified as disposable from the existing schema alone. Restore the test copy after the demonstration if you need a completely clean research or reporting state.

## Verification performed

The import and removal SQL were executed on local MySQL **8.2.0**, with temporary tables shadowing sales, items, refunds and stock logs. Actual application tables were not written.

- Generated **570 sales and 570 line items** across **19 eligible product/branch combinations**, dated **August 29–September 27, 2026**.
- All 19 sampled combinations produced non-null demand estimates and positive suggested orders in the real reorder service.
- Re-import produced no duplicate sales or items.
- Sale totals, line totals and profit calculations matched.
- Cleanup removed the samples and preserved an unrelated control sale, even though that control used the same invoice prefix.
- Existing stock quantities remained unchanged.

Your inserted count depends on your database's eligible products and the import date. This verification did not access Hostinger or run through its phpMyAdmin installation.

The SQLyog-compatible revision adds database/server diagnostics and required-column preflight reads. Its import, re-import and cleanup were also checked by executing statements individually on the same MySQL connection, matching SQLyog's documented batch execution model. The SQLyog desktop application itself was not automated.
