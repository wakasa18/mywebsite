# Shared pagination update

Package: `build/hostinger-pagination-2026-09-24.zip`.

No database migration, SQL import, or controller change is required. Existing page sizes, filters, and database queries are retained.

## Upload

Back up the existing files. Upload the stylesheet and new pager views first, then the other views, and upload `app/Config/Pager.php` last so the configured templates are already present.

- `public/assets/css/pagination.css` — new
- `app/Views/pagers/full.php` — new
- `app/Views/pagers/simple.php` — new
- `app/Views/layouts/staff.php`
- `app/Views/products/index.php`
- `app/Views/products/trash.php`
- `app/Views/categories/trash.php`
- `app/Views/admin/suppliers/index.php`
- `app/Views/admin/suppliers/trash.php`
- `app/Views/admin/stock_transfer/index.php`
- `app/Config/Pager.php`

If `public_html` contains the contents of `public/`, put the stylesheet at `public_html/assets/css/pagination.css`. PHP files go in the existing application's matching `app/` directories. Preserve the lowercase `pagers` folder name. Reload the page after uploading; the stylesheet URL uses version `20260924-1`.

## Behavior

All existing lists using the default pager now share the same controls: products, categories, suppliers, branches, users, discounts, sales history, stock logs, inventory, transfers, activity logs, expiry reports, and paginated trash lists.

- Result range and total, plus the current page and page count.
- Previous and Next move exactly one page. The former default arrows moved past the displayed range of page numbers, which could skip multiple pages.
- First/last shortcuts, clear unavailable states, and a marked current page.
- Controls at least 44px tall and wide, visible keyboard focus, and theme-aware colors.
- On phones, the page-number row is replaced by the page summary and four navigation controls to avoid a cramped row. The simple pager retains just Previous and Next.
- Single-page and empty results show a summary without unnecessary navigation links.
- Filter parameters, allowed-filter lists, named page groups, and URL-segment pagination remain supported.

Footer wrappers on products, suppliers, trash, and stock transfers use consistent spacing. No pagination JavaScript is needed.

## Validation

Six PHP tests passed with 25 assertions: adjacent navigation, first/last boundaries, partial final pages, empty/single-page results, filter preservation, named groups, URL segments, full/simple templates, and allowed-filter lists.

Forty-eight browser scenarios passed using the actual categories layout: first/middle/last pages, single/empty results, and large page counts, at 320, 390, 768, and 1440 pixels in both themes. Checks covered overflow, touch targets, mobile controls, filter URLs, keyboard focus, and extra-large text/larger controls. Desktop and mobile screenshots were visually reviewed. All changed PHP files passed syntax checks.

The checks used synthetic data. This task did not change database records or upload the package to Hostinger. After uploading, filter a list, use Next and Previous, and check that the filters and correct adjacent page are retained.
