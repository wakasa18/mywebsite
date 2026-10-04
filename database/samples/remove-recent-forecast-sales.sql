-- TEST DATABASE ONLY: remove unused PHX-DEMO-FORECAST-V1 sales and their items.
-- SQLyog Community: select the test database and Execute All Queries (Shift+F9).
-- phpMyAdmin: import the complete file into the same test database.
-- Keep all statements in the same connection. Stop on any SQL error.
-- Does not change stock or delete real sales, users, or saved forecast snapshots.
-- Refunded sales or sales with matching stock logs are excluded for manual review.
-- Do not process refunds/corrections against demo history; restore your test backup
-- instead if it has been used to create additional transactions.

SELECT DATABASE() AS selected_database;

SET @phx_demo_note = 'PHX-DEMO-FORECAST-V1: SYNTHETIC history only; not a real payment or stock movement.';
DROP TEMPORARY TABLE IF EXISTS phx_demo_remove_ids;
CREATE TEMPORARY TABLE phx_demo_remove_ids (id INT PRIMARY KEY) ENGINE=InnoDB;
START TRANSACTION;
INSERT INTO phx_demo_remove_ids (id)
SELECT s.id FROM sales s
WHERE s.invoice_no LIKE 'DEMO-FC-%' AND s.notes = @phx_demo_note AND s.status = 'completed'
  AND NOT EXISTS (SELECT 1 FROM refund_items r WHERE r.sale_id = s.id)
  AND NOT EXISTS (SELECT 1 FROM stock_logs l WHERE l.remarks LIKE CONCAT('%', s.invoice_no, '%'));

DELETE si FROM sale_items si JOIN phx_demo_remove_ids d ON d.id = si.sale_id;
DELETE s FROM sales s JOIN phx_demo_remove_ids d ON d.id = s.id;
COMMIT;

SELECT COUNT(*) AS removed_demo_sales FROM phx_demo_remove_ids;
SELECT COUNT(*) AS demo_sales_remaining_for_review
FROM sales WHERE invoice_no LIKE 'DEMO-FC-%' AND notes = @phx_demo_note;
DROP TEMPORARY TABLE phx_demo_remove_ids;
SET @phx_demo_note = NULL;
