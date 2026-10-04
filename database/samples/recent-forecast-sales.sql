-- PHARXMACO: SYNTHETIC FORECAST DEMONSTRATION DATA. TEST DATABASE ONLY.
-- SQLyog Community: select your TEST COPY database, open this file in a query
-- tab, then use Edit > Execute Query > Execute All Queries (Shift+F9 by default).
-- phpMyAdmin: select the TEST COPY database and import the complete SQL file.
-- Run the WHOLE script in one connection; individual queries depend on earlier
-- session variables and temporary tables. No DELIMITER or stored routine needed.
-- These are NOT real sales or Kaggle research observations. They affect reports.
-- No application schema, stock quantity, user, discount, or refund is changed.
-- One sale/item per eligible low-stock product/branch/day for the latest 30 days.
-- Stable synthetic demand: 2-5 units daily. This tests operation, not accuracy.
-- Same dates can be imported again without duplicating these invoices.
-- Run one import at a time; stop on errors. InnoDB is required.

-- Confirm the selected server/database and required columns before inserting.
SELECT DATABASE() AS selected_database, VERSION() AS mysql_version, CURDATE() AS sample_end_date;
SELECT invoice_no, user_id, branch_id, discount_id, total_amount, discount_amount,
       final_total, amount_paid, change_amount, payment_method, reference_no, status, notes, sale_date
FROM sales LIMIT 0;
SELECT sale_id, product_id, product_name_snapshot, cost_price_at_sale, quantity,
       price, subtotal, discount_applied, profit FROM sale_items LIMIT 0;

SET @phx_demo_end = CURDATE();
-- To use a fixed end date, replace CURDATE() above with '2026-09-27'.
SET @phx_demo_note = 'PHX-DEMO-FORECAST-V1: SYNTHETIC history only; not a real payment or stock movement.';
SET @phx_demo_transactional = (
    SELECT COUNT(*) = 2 FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('sales', 'sale_items') AND ENGINE = 'InnoDB'
);

DROP TEMPORARY TABLE IF EXISTS phx_demo_forecast_rows;
CREATE TEMPORARY TABLE phx_demo_forecast_rows ENGINE=InnoDB AS
SELECT
    CONCAT('DEMO-FC-', DATE_FORMAT(DATE_SUB(@phx_demo_end, INTERVAL (t.n * 10 + d.n) DAY), '%Y%m%d'), '-B', bp.branch_id, '-P', bp.product_id) AS invoice_no,
    bp.branch_id, bp.product_id, p.product_name,
    bp.price, bp.cost_price, (2 + MOD(bp.product_id + bp.branch_id, 4)) AS quantity,
    TIMESTAMP(DATE_SUB(@phx_demo_end, INTERVAL (t.n * 10 + d.n) DAY), '00:00:00') AS sale_date
FROM branch_products bp
JOIN products p ON p.id = bp.product_id
JOIN branches b ON b.id = bp.branch_id
CROSS JOIN (SELECT 0 AS n UNION ALL SELECT 1 UNION ALL SELECT 2) t
CROSS JOIN (SELECT 0 AS n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) d
WHERE p.status = 'active' AND p.deleted_at IS NULL
  AND bp.status = 'active' AND b.status = 'active'
  AND bp.stock <= bp.reorder_level
  AND (bp.expiration_date IS NULL OR bp.expiration_date >= @phx_demo_end)
  AND bp.price BETWEEN 0 AND 19999999.99
  AND bp.cost_price BETWEEN 0 AND 19999999.99
  AND NOT EXISTS (
      SELECT 1 FROM sales s
      WHERE s.invoice_no = CONCAT('DEMO-FC-', DATE_FORMAT(DATE_SUB(@phx_demo_end, INTERVAL (t.n * 10 + d.n) DAY), '%Y%m%d'), '-B', bp.branch_id, '-P', bp.product_id)
  );

SET @phx_demo_expected = (SELECT COUNT(*) FROM phx_demo_forecast_rows);
START TRANSACTION;

INSERT INTO sales
    (invoice_no, user_id, branch_id, discount_id, total_amount, discount_amount,
     final_total, amount_paid, change_amount, payment_method, reference_no, status, notes, sale_date)
SELECT invoice_no, NULL, branch_id, NULL, ROUND(price * quantity, 2), 0,
       ROUND(price * quantity, 2), ROUND(price * quantity, 2), 0,
       'cash', NULL, 'completed', @phx_demo_note, sale_date
FROM phx_demo_forecast_rows
WHERE @phx_demo_transactional = 1;
SET @phx_demo_sales_inserted = ROW_COUNT();

INSERT INTO sale_items
    (sale_id, product_id, product_name_snapshot, cost_price_at_sale,
     quantity, price, subtotal, discount_applied, profit)
SELECT s.id, r.product_id, r.product_name, r.cost_price,
       r.quantity, r.price, ROUND(r.price * r.quantity, 2), 0,
       ROUND((r.price - r.cost_price) * r.quantity, 2)
FROM phx_demo_forecast_rows r
JOIN sales s ON s.invoice_no = r.invoice_no AND s.notes = @phx_demo_note
WHERE @phx_demo_transactional = 1;
SET @phx_demo_items_inserted = ROW_COUNT();

-- Do not commit incomplete history if the client continued after an insert error.
SET @phx_demo_ok = (@phx_demo_transactional = 1
    AND @phx_demo_sales_inserted = @phx_demo_expected
    AND @phx_demo_items_inserted = @phx_demo_expected);
SET @phx_demo_sql = IF(@phx_demo_ok, 'COMMIT', 'ROLLBACK');
PREPARE phx_demo_statement FROM @phx_demo_sql;
EXECUTE phx_demo_statement;
DEALLOCATE PREPARE phx_demo_statement;

SELECT IF(@phx_demo_ok, 'Demo import complete', 'ROLLED BACK: check errors and InnoDB tables') AS result,
       IF(@phx_demo_ok, @phx_demo_sales_inserted, 0) AS new_demo_sales,
       DATE_SUB(@phx_demo_end, INTERVAL 29 DAY) AS forecast_from,
       @phx_demo_end AS forecast_to;
SELECT COUNT(*) AS all_demo_sales, MIN(sale_date) AS earliest_demo_sale, MAX(sale_date) AS latest_demo_sale
FROM sales WHERE invoice_no LIKE 'DEMO-FC-%' AND notes = @phx_demo_note;

DROP TEMPORARY TABLE phx_demo_forecast_rows;
SET @phx_demo_end = NULL, @phx_demo_note = NULL, @phx_demo_transactional = NULL,
    @phx_demo_expected = NULL, @phx_demo_sales_inserted = NULL, @phx_demo_items_inserted = NULL,
    @phx_demo_ok = NULL, @phx_demo_sql = NULL;
