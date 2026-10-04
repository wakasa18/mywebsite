# Cashier catalog layout correction

Package: `build/hostinger-cashier-catalog-ui-2026-10-03.zip`.

This updates the Add from Existing Products page from the cashier catalog feature package.

- The search input, Search button and optional Reset button share a row and equal heights on wider screens. Mobile controls stack with full-width input and accessible action buttons.
- The Back to Products button keeps a compact height instead of stretching to the page heading.
- The search area uses the existing theme's secondary surface and a divider.
- Empty catalog and empty search results have distinct explanations and visible actions. Create New Product is a button; filtered empty results also offer Clear Search.
- Catalog results have a separate footer for creating a missing product. Empty states avoid repeating that footer.
- Colors follow the selected accent and light/dark mode. Larger text and compact/spacious density settings are supported.

## Upload

Back up and replace only `app/Views/products/catalog.php` in the application's matching Hostinger folder. If the catalog feature has not yet been uploaded, install `hostinger-cashier-product-catalog-2026-10-03.zip` first, then upload this corrected view.

No database import, controller update, route update or new assets are needed for this UI correction. The documentation file in the ZIP does not need to be uploaded to the website.

Refresh Products → Add from Existing Products after upload. This update has not been deployed to Hostinger.

## Verification

The view passed PHP syntax checking. Forty-eight browser layout and preference cases passed, covering catalog results, branch setup, empty catalog and empty search results at 320, 390, 768 and 1440 px in light/dark themes. Additional cases covered extra-large text with compact/spacious density and the purple accent. Checks verified aligned search controls, compact header actions, visible empty-state actions and no horizontal overflow.

Desktop empty-state and mobile dark search-result screenshots were visually reviewed. Browser checks used local rendered fixtures and did not submit inventory changes.
