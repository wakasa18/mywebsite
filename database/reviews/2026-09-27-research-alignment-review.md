# Research paper and implementation review

Reviewed: 27 September 2026

**Implementation update, 27 September 2026:** Findings **2, 5 and 8** were subsequently addressed at the user's request. Update Forecast now saves the seven revenue points alongside separately labelled product estimates; cashiers have a read-only other-branch stock lookup; authentication events are recorded in the activity log. See the [implementation and deployment notes](../upgrades/2026-09-27-research-fixes-deployment.md). The original review below records the state before those changes. Other findings remain open.

**Verdict: partially aligned.** The core POS/inventory workflows and Holt calculations are implemented, but the inspected project does not yet establish all the forecasting, audit, and evaluation claims in the paper. Correct formulas alone do not establish forecast accuracy.

## Scope and evidence

Reference: `C:\Users\warre\Downloads\Docu12 1 (3).docx`.

Paper references below use section names and paragraph IDs from the [extracted paper](../../writable/receipt-review/research-paper-extracted.txt). IDs identify extracted paragraphs, not Word page numbers. Embedded forecasting, cleaning, MAPE, and use-case diagrams were also inspected.

This is a source review with local, isolated checks. No application code or production records were changed. Hostinger behavior, the live database's provenance, external research notebooks, actual respondent results, and concurrent MySQL behavior were not verified.

## What aligns

| Paper requirement | Evidence and assessment |
| --- | --- |
| POS selection, discounts, totals, payment records, receipts ([0068]) | Implemented in [SalesController](../../app/Controllers/Cashier/SalesController.php). Checkout includes transactional stock updates and stock/expiry checks. Recording a payment is not independent verification of an external payment provider. |
| Branch inventory, stock movements, low-stock and expiry alerts ([0069]) | Branch inventory models, stock logs, dashboard/topbar notifications and expiry reports are present. See FEFO qualification below. |
| Holt level and trend equations, alpha 0.3, beta 0.2 ([0215]–[0223]) | Both [ReportsController](../../app/Controllers/Admin/ReportsController.php#L1418) and [ReorderForecastService](../../app/Libraries/ReorderForecastService.php#L63) implement the stated equations. Six mathematical checks passed. |
| Daily, weekly and monthly sales aggregation ([0211]–[0213]) | Reports controller groups revenue by the selected interval and produces seven future periods. Product restock demand uses a separate daily quantity model. |
| Maximum 24-month history ([0074]) | Reports controller enforces this and the interface describes it. Boundary checks passed. The CLI exception is below. This is a history limit, not a 24-month prediction horizon. |
| Save forecasting results ([0224], [0233]–[0235]) | Update Forecast saves product snapshots, with repeat-save and rollback behavior covered by passing tests. It does not save the exact revenue chart series; see finding 2. |

## Findings requiring changes or clarification

### 1. Forecast accuracy evaluation is missing from the inspected app — high priority

The paper's system flow, methodology and evaluation sections explicitly include MAPE ([0094], [0147], [0571], [0575], [0589]–[0607]). No MAPE evaluator, holdout evaluation, accuracy output, or reproducible Kaggle evaluation results were found in the inspected app/tests/database documentation.

The chart's fitted values are not sufficient accuracy evidence. Implement a reproducible chronological evaluation using unseen observations, document the horizon and training window, and report the results with a simple baseline. MAPE is undefined when an actual value is zero; disclose the zero-handling policy and evaluation coverage, and include an additional metric such as MAE. Do not silently report undefined MAPE as zero. These evaluation distinctions are explained in [Forecasting: Principles and Practice, section 5.8](https://otexts.com/fpp3/accuracy.html).

### 2. Saved records work, but represent a different forecast from the chart — high priority

[ReportsController::updateForecast](../../app/Controllers/Admin/ReportsController.php#L439) calls [saveManualSnapshot](../../app/Libraries/ReorderForecastService.php#L284). It saves estimates for active low-stock products, not the seven daily/weekly/monthly revenue predictions shown in the sales chart.

The restock service forecasts 30 daily quantity values. Its saved quantity is their average multiplied by the number of days in the current calendar month; the saved month is also the current month. The selected daily/weekly/monthly setting is recorded in the snapshot description, but does not change the service's daily modeling interval. Selecting an older training window therefore still produces a record labelled with the current month.

This is a semantic mismatch to resolve, not a failed INSERT. Define whether the research result being saved is sales revenue by period, product demand, or both. Label the existing product estimates accurately. For reproducible evaluation, retain the exact target dates, forecast origin, values, branch, input window, interval and smoothing constants for the forecast being evaluated. Any storage redesign should be considered separately; this review makes no database changes.

### 3. Kaggle provenance and the promised cleaning workflow are unverified — high priority

The paper says research forecasting uses Kaggle sample data, not Pharxmaco's actual historical sales ([0071], [0073], [0577]). It promises filtering, deduplication, imputation, validation and chronological ordering ([0575]–[0587]).

The current forecast paths read application sales and refunds. They aggregate chronologically and fill absent periods with zero, but this does not establish where the records came from or prove the full raw-data cleaning process. A missing observation and a verified day with no sales are different cases.

Supply the exact dataset/version, original files, field mapping, selected 24-month period, cleaning decisions and evaluation output in an isolated research workflow. Verify the paper's claimed medicine/price/revenue fields against the actual downloaded file. The dataset contents were not verified in this review. Keep the removed production Import Past Sales feature removed; research preparation does not require restoring it.

### 4. Cashier access is inconsistent with the paper's prose — clarify the paper first

Paragraphs [0240]–[0241] exclude cashier product management and report generation. However, Figure 10's use-case connections give the cashier product and inventory functions; the inventory narrative also describes staff maintaining products. The paper is internally inconsistent.

The [shared routes](../../app/Config/Routes.php#L19) currently allow cashiers to create products for their branch, update stock, and access stock/expiry reports. Administrative sales forecasting and user management remain restricted.

Agree on one explicit role/permission matrix, update the paper and diagram, then align routes and controller checks. Do not remove working cashier capabilities merely to follow one conflicting paragraph.

### 5. Cross-branch customer referral is only partially implemented

The scope describes staff finding availability at another branch when an item is unavailable locally ([0070]). Administrators can inspect multiple branches, but [Products::index](../../app/Controllers/Products.php#L53) and the [cashier POS query](../../app/Controllers/Cashier/SalesController.php#L67) restrict cashiers to their assigned branch. No cashier-facing other-branch availability lookup was found.

If cashiers are the intended referral users, add a read-only availability lookup showing branch name, address and available quantity, while retaining branch restrictions on transactions and stock changes. Clarify what “nearest branch” means; branch visibility alone does not calculate distance.

### 6. Expiration protection is present; full batch-level FEFO is not

The data-dictionary narrative claims FEFO support ([0564]). The [branch product model](../../app/Models/BranchProductModel.php) stores one stock quantity and expiration date per product/branch. Checkout locks and decrements that aggregate row, rather than allocating quantities between lots with different expiry dates.

This can block expired inventory and sort products by expiration. It cannot automatically choose the earliest-expiring lot when the same SKU has several lots. Either narrow the paper's claim to the implemented expiry monitoring behavior, or scope a proper lot/batch inventory and allocation design. The latter would require a separate data-model change, not just a UI adjustment.

### 7. The 24-month limit and fixed constants are not enforced across all entry points

The Reports page matches the paper. [GenerateReorderForecast](../../app/Commands/GenerateReorderForecast.php#L40), however, only applies a minimum to `--days`; it permits histories exceeding 24 months. It also permits overriding alpha and beta. The service does not independently enforce the maximum.

Enforce the research history policy in the common service/CLI path, or explicitly document experimental CLI overrides as outside the fixed research configuration. The default command uses the paper's 0.3/0.2 values.

### 8. Login audit coverage does not match the evaluation questionnaire

The questionnaire says all logins and user activities are recorded ([0768]). [Auth](../../app/Controllers/Auth.php#L21) implements password checking, throttling and session setup, but does not persist successful/failed login or logout events to the activity log. Temporary throttle counters are not a durable login audit trail.

Add suitable authentication audit events if retaining that claim. Record event/time/outcome and appropriate account context; never log passwords or session secrets. Other activity logging exists, but this review does not establish complete coverage of every action.

## Paper and evaluation evidence

- Describe Holt as a level-and-trend model. Claims about reliably predicting seasonal peaks or outperforming other models need measured evidence. Standard Holt does not include a seasonal component; see [Holt's linear trend method](https://otexts.com/fpp3/holt.html).
- Distinguish the revenue chart, product-unit forecast, and 30-day restock policy. These use different targets and horizons; one cannot stand in for the other's accuracy result.
- Reconcile the conflicting cashier permissions in prose and diagrams before changing access.
- Completed ISO/IEC 25010 questionnaires, usability results, load/recovery tests and browser results are evaluation evidence, not facts established merely by the presence of code or questionnaire items. This review is not a standards or legal compliance certification.

## Checks run for this review

| Check | Result |
| --- | --- |
| Paper example in both Holt functions | Passed: series `[850, 900, 1000]`, alpha 0.3, beta 0.2 gives final level 965, trend 53, next forecast **1018**. |
| Constant and linear series in both functions | Passed. Six mathematical cases total, using [check-paper-holt.php](../../writable/receipt-review/check-paper-holt.php). |
| Reports history boundary checks | Passed: 18 and 24 months accepted; 25 months limited; ordinary reports remain uncapped by the forecasting limit. |
| `BusinessLogicTest.php` | **25 passed, 1 errored**, 20,415 assertions reported. The migration test errors at construction with `loadForge() on null` (`tests/unit/BusinessLogicTest.php:467`), before its migration assertions execute. |

Passing business tests cover checkout/discount/stock/refund behavior and manual/scheduled forecast saving, repeat saves, rollback, and saved-log retrieval. They use isolated SQLite with simulated locking hooks, not a production MySQL concurrency test. The migration test setup needs repair and a rerun before calling that check verified; its error alone does not prove the production migration is broken.

## Recommended order

1. Agree on the forecast target/horizon and the role matrix.
2. Add reproducible dataset preparation, unseen-data evaluation and MAPE reporting with zero handling.
3. Make saved records accurately represent the selected forecast and its target dates.
4. Close the cross-branch lookup, CLI limit and authentication audit gaps.
5. Decide whether to narrow the FEFO claim or separately implement batch inventory.
6. Repair the migration test setup and collect the documented user/quality evaluation results.

No deployment package is needed for this review. Only this report and local review artifacts were added.
