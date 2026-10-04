<?= $this->extend('layouts/staff') ?>
<?= $this->section('content') ?>

<style>
/* ═══════════════════════════════════════════════════════════════
   PHARXMACO POS — RESPONSIVE WORKSPACE
   Desktop: Products | Cart | Payment
   Small laptop/tablet: Products | stacked Cart + Payment
   Phone: tabbed single-panel workspace
═══════════════════════════════════════════════════════════════ */

/* ── Page heading and live status ── */
.pos-page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 14px;
}
.pos-page-header h1 {
    margin: 0;
    font-size: clamp(20px, 2vw, 25px);
    font-weight: 800;
    letter-spacing: -.55px;
    line-height: 1.1;
}
.pos-page-meta {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 5px;
    font-size: 12.5px;
    color: var(--muted);
}
.pos-page-meta .meta-dot { opacity: .45; }
.pos-header-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    flex-wrap: wrap;
    gap: 8px;
}
.pos-live-stat {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 38px;
    padding: 7px 11px;
    border: 1.5px solid var(--line);
    border-radius: 10px;
    background: var(--surface);
    box-shadow: 0 1px 3px rgba(0,40,100,.04);
}
.pos-live-stat span {
    font-size: 10px;
    font-weight: 800;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: .55px;
}
.pos-live-stat strong {
    font-size: 14px;
    color: var(--text);
    font-variant-numeric: tabular-nums;
}
.held-sales-trigger {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 42px;
    padding: 9px 14px;
    background: var(--surface);
    border: 1.5px solid var(--line-2);
    border-radius: 10px;
    font-family: inherit; font-size: 13px; font-weight: 750; line-height: 1;
    color: var(--text-2);
    cursor: pointer;
    transition: border-color .15s, background .15s, color .15s, transform .1s;
}
.held-sales-trigger:hover {
    background: var(--primary-light);
    border-color: var(--primary);
    color: var(--primary);
}
.held-sales-trigger:active { transform: translateY(1px); }

/* ── Alerts ── */
.pos-alert { margin-bottom: 10px; }

/* ── Held-sales modal ── */
.held-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 500;
    align-items: flex-start;
    justify-content: center;
    padding: 40px 20px;
    overflow-y: auto;
    background: rgba(13,27,46,.54);
    backdrop-filter: blur(3px);
}
.held-modal-overlay.show { display: flex; }
.held-modal {
    width: min(100%, 640px);
    max-height: calc(100dvh - 80px);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    border: 1px solid var(--line);
    border-radius: 16px;
    background: var(--bg);
    box-shadow: 0 24px 70px rgba(0,20,50,.26);
}
.held-modal-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 16px 18px;
    flex-shrink: 0;
    border-bottom: 1.5px solid var(--line);
    background: var(--surface);
}
.held-modal-head h2 { margin: 0; font-size: 17px; }
.held-modal-close {
    width: 34px;
    height: 34px;
    display: grid;
    place-items: center;
    border: 1.5px solid var(--line-2);
    border-radius: 9px;
    background: var(--surface);
    color: var(--muted);
    cursor: pointer;
    transition: .15s;
}
.held-modal-close:hover { background: var(--danger-bg); border-color: #fecaca; color: var(--danger); }
.held-modal-body { padding: 8px 18px 18px; overflow-y: auto; }
.held-sale-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 13px 14px;
    margin-top: 10px;
    border: 1.5px solid var(--line);
    border-radius: 11px;
    background: var(--surface);
}
.held-sale-info { min-width: 0; }
.held-sale-label { font-size: 13.5px; font-weight: 750; color: var(--text); }
.held-sale-meta { margin-top: 3px; font-size: 11.5px; color: var(--muted); }
.held-sale-actions { display: flex; gap: 6px; flex-shrink: 0; }
.held-sale-actions .btn { padding: 7px 11px; font-size: 12px; }

/* ── Mobile workspace navigation ── */
.pos-mobile-nav {
    display: none;
    grid-template-columns: repeat(3, 1fr);
    gap: 6px;
    padding: 5px;
    margin-bottom: 9px;
    border: 1.5px solid var(--line);
    border-radius: 12px;
    background: var(--surface);
    box-shadow: var(--shadow);
}
.pos-mobile-tab {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    min-width: 0;
    min-height: 42px;
    padding: 11px 8px;
    border: 0;
    border-radius: 8px;
    background: transparent;
    color: var(--muted);
    font-family: inherit; font-size: 12.5px; font-weight: 750; line-height: 1;
    cursor: pointer;
}
.pos-mobile-tab.active { background: var(--primary); color: var(--on-primary); box-shadow: 0 3px 10px rgba(43,127,255,.24); }
.pos-mobile-tab .tab-count {
    min-width: 18px;
    height: 18px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 5px;
    border-radius: 999px;
    background: var(--surface-2);
    color: var(--text-2);
    font-size: 10px;
    font-weight: 800;
}
.pos-mobile-tab.active .tab-count { background: rgba(255,255,255,.2); color: var(--on-primary); }

/* ═══════════════════════════════════
   MAIN POS WORKSPACE
═══════════════════════════════════ */
.pos-root {
    display: grid;
    grid-template-columns: minmax(420px, 1fr) minmax(290px, 330px) minmax(300px, 340px);
    grid-template-areas: "products cart payment";
    height: clamp(560px, calc(100dvh - var(--topbar-h) - 128px), 860px);
    min-height: 560px;
    overflow: hidden;
    border: 1.5px solid var(--line);
    border-radius: 15px;
    background: var(--surface);
    box-shadow: var(--shadow);
}
.pos-products,
.pos-cart,
.pos-pay {
    min-width: 0;
    min-height: 0;
}

/* ─── PRODUCTS ─── */
.pos-products {
    grid-area: products;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    border-right: 1.5px solid var(--line);
    background: var(--bg);
}
.pos-products-head {
    padding: 13px 14px 11px;
    flex-shrink: 0;
    border-bottom: 1.5px solid var(--line);
    background: var(--surface);
}
.pos-head-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 10px;
}
.pos-zone-title,
.zone-head-title {
    display: flex;
    align-items: center;
    gap: 7px;
    font-size: 12px;
    font-weight: 850;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: .65px;
}
.pos-count-pill,
.zone-badge {
    min-width: 20px;
    height: 20px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 6px;
    border: 1px solid var(--line);
    border-radius: 999px;
    background: var(--surface-2);
    color: var(--text-2);
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0;
}
.zone-badge { border-color: transparent; background: var(--primary-light); color: var(--primary); }
.pos-head-hint { font-size: 10.5px; color: var(--muted); white-space: nowrap; }

.pos-search {
    display: flex;
    align-items: center;
    gap: 8px;
    min-height: 40px;
    padding: 8px 11px;
    margin-bottom: 9px;
    border: 1.5px solid var(--line-2);
    border-radius: 10px;
    background: var(--surface-2);
    transition: border-color .15s, box-shadow .15s, background .15s;
}
.pos-search:focus-within {
    border-color: var(--primary);
    background: var(--surface);
    box-shadow: 0 0 0 3px rgba(43,127,255,.10);
}
.pos-search svg { flex-shrink: 0; color: var(--muted); }
.pos-search input {
    width: 100%;
    min-width: 0;
    border: 0;
    padding: 2px 0;
    outline: 0;
    background: transparent;
    color: var(--text);
    font-family: inherit; font-size: 13px; font-weight: 600; line-height: 1.2;
}
.pos-search input::placeholder { color: var(--muted); font-weight: 450; }
.search-shortcut {
    flex-shrink: 0;
    padding: 2px 6px;
    border: 1px solid var(--line-2);
    border-radius: 5px;
    color: var(--muted);
    background: var(--surface);
    font-size: 9px;
    font-weight: 800;
}
.cat-chips {
    display: flex;
    gap: 6px;
    overflow-x: auto;
    padding: 3px 2px 6px;
    scrollbar-width: none;
}
.cat-chips::-webkit-scrollbar { display: none; }
.cat-chip {
    flex-shrink: 0;
    padding: 5px 10px;
    min-height: 32px;
    border: 1.5px solid var(--line-2);
    border-radius: 999px;
    background: var(--surface);
    color: var(--muted);
    font-family: inherit; font-size: 11px; font-weight: 750; line-height: 1;
    cursor: pointer;
    transition: .12s;
    white-space: nowrap;
}
.cat-chip:hover { border-color: var(--primary); color: var(--primary); }
.cat-chip.active { border-color: var(--primary); background: var(--primary); color: #fff; }

.prod-scroll {
    flex: 1;
    min-height: 0;
    overflow-y: auto;
    padding: 11px;
    scrollbar-width: thin;
    scrollbar-color: var(--line-2) transparent;
}
.prod-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(155px, 1fr));
    gap: 9px;
}
.prod-card {
    position: relative;
    min-height: 118px;
    display: flex;
    flex-direction: column;
    gap: 7px;
    padding: 12px;
    border: 1.5px solid var(--line);
    border-radius: 11px;
    background: var(--surface);
    cursor: pointer;
    user-select: none;
    transition: border-color .14s, box-shadow .14s, background .14s, transform .1s;
}
.prod-card:hover:not(.disabled),
.prod-card:focus-visible:not(.disabled) {
    border-color: var(--primary);
    background: var(--primary-lighter);
    box-shadow: 0 0 0 3px rgba(43,127,255,.09), 0 5px 14px rgba(0,40,100,.07);
    outline: none;
    transform: translateY(-1px);
}
.prod-card.selected {
    border-color: var(--primary);
    background: var(--primary-light);
    box-shadow: 0 0 0 3px rgba(43,127,255,.14);
}
.prod-card.disabled { opacity: .48; cursor: not-allowed; }
.prod-card-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 7px;
    min-width: 0;
    font-size: 10.5px;
    font-weight: 750;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: .35px;
}
.prod-card-meta span { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.prod-card-meta .sku { font-family: 'DM Mono', monospace; text-transform: none; letter-spacing: 0; }
.prod-card-name {
    min-height: 34px;
    display: -webkit-box;
    overflow: hidden;
    color: var(--text);
    font-size: 13px;
    font-weight: 750;
    line-height: 1.34;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}
.prod-card-price {
    color: var(--primary-dark);
    font-size: 16px;
    font-weight: 900;
    font-variant-numeric: tabular-nums;
    letter-spacing: -.35px;
}
.prod-card-footer { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 4px; margin-top: auto; }
.stock-pill,
.expiry-pill {
    display: inline-flex;
    align-items: center;
    min-height: 20px;
    padding: 2px 6px;
    border-radius: 999px;
    font-size: 9.5px;
    font-weight: 800;
}
.stock-pill.ok { background: var(--success-bg); color: var(--success-text); }
.stock-pill.low { background: var(--warning-bg); color: var(--warning-text); }
.stock-pill.out { background: var(--danger-bg); color: var(--danger-text); }
.expiry-pill { border-radius: 6px; background: var(--danger-bg); color: var(--danger-text); }
.expiry-pill.near { background: var(--warning-bg); color: var(--warning-text); }
.prod-card-check {
    position: absolute;
    top: 7px;
    right: 7px;
    width: 18px;
    height: 18px;
    display: grid;
    place-items: center;
    border-radius: 50%;
    background: var(--primary);
    opacity: 0;
    transform: scale(.8);
    transition: opacity .12s, transform .12s;
}
.prod-card.selected .prod-card-check { opacity: 1; transform: scale(1); }
.no-results,
.product-filter-empty {
    grid-column: 1 / -1;
    padding: 54px 20px;
    text-align: center;
    color: var(--muted);
    font-size: 13px;
}
.product-filter-empty strong { display: block; margin-bottom: 4px; color: var(--text-2); font-size: 14px; }

.quickadd-bar {
    display: none;
    align-items: center;
    gap: 8px;
    padding: 9px 12px;
    flex-shrink: 0;
    border-top: 1.5px solid rgba(43,127,255,.28);
    background: var(--primary-light);
}
.quickadd-bar.show { display: flex; }
.quickadd-name {
    flex: 1;
    min-width: 0;
    overflow: hidden;
    color: var(--primary-dark);
    font-size: 12px;
    font-weight: 750;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.quickadd-qty {
    width: 56px;
    padding: 6px;
    border: 1.5px solid var(--primary);
    border-radius: 8px;
    outline: 0;
    background: var(--surface);
    color: var(--text);
    font-family: inherit; font-size: 13px; font-weight: 800; line-height: 1;
    text-align: center;
}
.quickadd-qty:focus { box-shadow: 0 0 0 3px rgba(43,127,255,.13); }
.quickadd-add-btn {
    min-height: 40px;
    padding: 9px 16px;
    border: 0;
    border-radius: 8px;
    background: var(--primary);
    color: #fff;
    font-family: inherit; font-size: 12.5px; font-weight: 800; line-height: 1;
    white-space: nowrap;
    cursor: pointer;
    transition: .12s;
}
.quickadd-add-btn:hover { background: var(--primary-dark); }
.quickadd-cancel-btn {
    width: 34px;
    height: 34px;
    display: grid;
    place-items: center;
    border: 0;
    border-radius: 7px;
    background: transparent;
    color: var(--muted);
    cursor: pointer;
}
.quickadd-cancel-btn:hover { background: rgba(0,0,0,.07); }

/* ─── CART ─── */
.pos-cart {
    grid-area: cart;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    border-right: 1.5px solid var(--line);
    background: var(--surface);
}
.zone-head {
    min-height: 48px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    padding: 11px 13px;
    flex-shrink: 0;
    border-bottom: 1.5px solid var(--line);
    background: var(--surface);
}
.cart-head-actions { display: flex; align-items: center; gap: 4px; }
.clear-cart-btn {
    min-width: 48px;
    min-height: 34px;
    padding: 8px 10px;
    border: 0;
    border-radius: 6px;
    background: transparent;
    color: var(--danger);
    font-family: inherit; font-size: 11.5px; font-weight: 750; line-height: 1;
    cursor: pointer;
    transition: background .1s;
}
.clear-cart-btn:hover { background: var(--danger-bg); }
.hold-cart-btn { color: var(--primary); }
.hold-cart-btn:hover { background: var(--primary-light); }
.cart-empty {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 30px 18px;
    color: var(--muted);
    text-align: center;
}
.cart-empty-icon {
    width: 54px;
    height: 54px;
    display: grid;
    place-items: center;
    margin-bottom: 3px;
    border-radius: 15px;
    background: var(--surface-2);
    color: var(--primary);
}
.cart-empty-text { color: var(--text-2); font-size: 13px; font-weight: 750; }
.cart-empty-sub { font-size: 11.5px; }
.cart-item-list {
    flex: 1;
    min-height: 0;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: var(--line-2) transparent;
}
.cart-item {
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding: 11px 12px;
    border-bottom: 1px solid var(--line);
    transition: background .1s;
}
.cart-item:last-child { border-bottom: 0; }
.cart-item:hover { background: var(--primary-lighter); }
.cart-item-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; }
.cart-item-info { flex: 1; min-width: 0; }
.cart-item-name { color: var(--text); font-size: 12.5px; font-weight: 750; line-height: 1.35; word-break: break-word; }
.cart-item-unit { margin-top: 2px; color: var(--muted); font-size: 10.5px; font-variant-numeric: tabular-nums; }
.cart-item-bottom { display: flex; align-items: center; gap: 7px; }
.cart-qty-wrap { display: flex; align-items: center; gap: 3px; }
.qty-btn {
    width: 32px;
    height: 32px;
    display: grid;
    place-items: center;
    border: 1.5px solid var(--line-2);
    border-radius: 7px;
    background: var(--surface-2);
    color: var(--text-2);
    font-family: inherit; font-size: 16px; font-weight: 800; line-height: 1;
    cursor: pointer;
    transition: .1s;
}
.qty-btn:hover { border-color: var(--primary); background: var(--primary-light); color: var(--primary); }
.cart-qty-input {
    width: 44px;
    min-height: 32px;
    padding: 6px 3px;
    border: 1.5px solid var(--line-2);
    border-radius: 7px;
    outline: 0;
    background: var(--surface);
    color: var(--text);
    font-family: inherit; font-size: 12px; font-weight: 800; line-height: 1;
    text-align: center;
}
.cart-qty-input:focus { border-color: var(--primary); box-shadow: 0 0 0 2px rgba(43,127,255,.09); }
.cart-item-sub {
    margin-left: auto;
    flex-shrink: 0;
    color: var(--primary-dark);
    font-size: 13.5px;
    font-weight: 900;
    font-variant-numeric: tabular-nums;
}
.cart-remove-btn {
    width: 30px;
    height: 30px;
    display: grid;
    place-items: center;
    flex-shrink: 0;
    border: 1px solid #fecaca;
    border-radius: 7px;
    background: var(--danger-bg);
    color: var(--danger);
    font-size: 12px;
    cursor: pointer;
}
.cart-remove-btn:hover { background: #fee2e2; }
.cart-qty-warning { display: none; margin-top: -3px; color: var(--danger); font-size: 10.5px; font-weight: 700; }
.cart-qty-warning.show { display: block; }
.cart-total-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 11px 13px;
    flex-shrink: 0;
    border-top: 1.5px solid var(--line);
    background: var(--surface-2);
}
.cart-total-label { color: var(--muted); font-size: 9.5px; font-weight: 850; text-transform: uppercase; letter-spacing: .55px; }
.cart-total-val { margin-top: 2px; color: var(--text); font-size: 20px; font-weight: 900; font-variant-numeric: tabular-nums; letter-spacing: -.6px; }

/* ─── PAYMENT ─── */
.pos-pay {
    grid-area: payment;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    background: var(--surface);
}
.pay-scroll {
    flex: 1;
    min-height: 0;
    overflow-y: auto;
    padding: 12px 13px;
    scrollbar-width: thin;
    scrollbar-color: var(--line-2) transparent;
}
.pay-summary {
    margin-bottom: 12px;
    overflow: hidden;
    border: 1.5px solid var(--line);
    border-radius: 11px;
}
.pay-summary-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 7px 10px;
    border-bottom: 1px solid var(--line);
    color: var(--text-2);
    font-size: 12px;
}
.pay-summary-row:last-child { border-bottom: 0; }
.pay-summary-row.grand { padding: 14px 12px; background: var(--primary-light); border-left: 3px solid var(--primary); color: var(--text); font-size: 13px; font-weight: 800; }
.pay-summary-row.grand .val { color: var(--text); font-size: 26px; font-weight: 800; font-variant-numeric: tabular-nums; letter-spacing: -.55px; }
#checkoutForm {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.pay-label {
    margin-bottom: 6px;
    color: var(--muted);
    font-size: 9.5px;
    font-weight: 850;
    text-transform: uppercase;
    letter-spacing: .6px;
}
.pay-label .optional { font-weight: 500; text-transform: none; letter-spacing: 0; }
.pay-methods { display: grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap: 6px; }
.pay-method {
    min-width: 0;
    min-height: 60px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 4px;
    padding: 9px 5px;
    border: 1.5px solid var(--line-2);
    border-radius: 9px;
    background: var(--surface-2);
    color: var(--muted);
    font-family: inherit; font-size: 11px; font-weight: 750; line-height: 1;
    cursor: pointer;
    transition: .12s;
}
.pay-method:hover { border-color: var(--primary); background: var(--primary-light); color: var(--primary); }
.pay-method .ico { display: grid; place-items: center; height: 18px; font-size: 16px; }
.pay-method.active.cash { border-color: #16a34a; background: #dcfce7; color: #166534; }
.pay-method.active.gcash { border-color: #2563eb; background: #dbeafe; color: #1d4ed8; }
.pay-method.active.card { border-color: #7c3aed; background: #ede9fe; color: #6d28d9; }
.pay-row-2 { display: grid; grid-template-columns: minmax(0,1fr) minmax(0,1fr); gap: 8px; }
.pay-input {
    width: 100%;
    min-width: 0;
    padding: 9px 10px;
    border: 1.5px solid var(--line-2);
    border-radius: 9px;
    outline: 0;
    background: var(--surface);
    color: var(--text);
    font-family: inherit; font-size: 12.5px; font-weight: 600; line-height: 1.25;
    transition: border-color .15s, box-shadow .15s;
}
.pay-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(43,127,255,.10); }
.pay-input[readonly] { background: var(--surface-2); color: var(--muted); }
.quick-cash-row { display: grid; grid-template-columns: repeat(5, minmax(0,1fr)); gap: 5px; margin-top: -4px; }
.quick-cash-row.hide { display: none; }
.quick-cash-btn {
    min-width: 0;
    min-height: 34px;
    padding: 8px 3px;
    border: 1.5px solid var(--line-2);
    border-radius: 8px;
    background: var(--surface);
    color: var(--text-2);
    font-family: inherit; font-size: 10.5px; font-weight: 750; line-height: 1;
    cursor: pointer;
    transition: .12s;
}
.quick-cash-btn:hover { border-color: var(--primary); background: var(--primary-light); color: var(--primary); }
.quick-cash-btn:active { transform: scale(.97); }
.discount-warning { display: none; margin-top: 5px; color: var(--danger); font-size: 10.5px; font-weight: 650; }
.discount-warning.show { display: block; }
.change-badge {
    display: none;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 8px 10px;
    border: 1.5px solid #bbf7d0;
    border-radius: 9px;
    background: #dcfce7;
}
.change-badge.show { display: flex; }
.change-badge-label { color: #166534; font-size: 10px; font-weight: 850; text-transform: uppercase; letter-spacing: .45px; }
.change-badge-val { color: #15803d; font-size: 18px; font-weight: 900; font-variant-numeric: tabular-nums; }
.select-wrap { position: relative; }
.select-wrap select { appearance: none; padding-right: 29px; }
.select-wrap::after { content: '▾'; position: absolute; right: 10px; top: 50%; transform: translateY(-50%); color: var(--muted); font-size: 11px; pointer-events: none; }
.complete-btn {
    width: calc(100% - 26px);
    min-height: 52px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    margin: 0 13px 13px;
    padding: 14px 12px;
    flex-shrink: 0;
    border: 0;
    border-radius: 10px;
    background: #15803d;
    color: #fff;
    font-family: inherit; font-size: 14.5px; font-weight: 850; line-height: 1;
    letter-spacing: -.1px;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(22,163,74,.25);
    transition: background .15s, box-shadow .15s, transform .1s;
}
.complete-btn:hover { background: #15803d; box-shadow: 0 5px 16px rgba(22,163,74,.34); }
.complete-btn:active { transform: translateY(1px); }
.complete-btn:disabled { background: var(--line-2); color: var(--muted); box-shadow: none; cursor: not-allowed; }
.complete-btn-kbd {
    margin-left: auto;
    padding: 2px 5px;
    border: 1px solid rgba(255,255,255,.48);
    border-radius: 5px;
    font-size: 9px;
    font-weight: 800;
    opacity: .72;
}
.complete-btn:disabled .complete-btn-kbd { display: none; }
@keyframes spin { to { transform: rotate(360deg); } }

/* ── Medium screens: product browser on the left; cart/payment stacked right ── */
@media (max-width: 1280px) and (min-width: 1101px) {
    .pos-root {
        grid-template-columns: minmax(420px, 1fr) minmax(340px, 390px);
        grid-template-rows: minmax(245px, .82fr) minmax(330px, 1.18fr);
        grid-template-areas:
            "products cart"
            "products payment";
        min-height: 640px;
        height: clamp(640px, calc(100dvh - var(--topbar-h) - 128px), 820px);
    }
    .pos-products { border-right: 1.5px solid var(--line); }
    .pos-cart { border-right: 0; border-bottom: 1.5px solid var(--line); }
    .prod-grid { grid-template-columns: repeat(auto-fill, minmax(145px, 1fr)); }
}

/* ── Tablet/phone: one panel at a time ── */
@media (max-width: 1100px) {
    .pos-page-header { margin-bottom: 10px; }
    .pos-live-stat { display: none; }
    .pos-mobile-nav { display: grid; position: sticky; top: calc(var(--topbar-h) + 8px); z-index: 90; }
    .pos-root {
        position: relative;
        grid-template-columns: minmax(0,1fr);
        grid-template-rows: minmax(0,1fr);
        grid-template-areas: "panel";
        height: max(440px, calc(100dvh - var(--topbar-h) - 168px));
        min-height: 440px;
        border-radius: 13px;
    }
    .pos-products,
    .pos-cart,
    .pos-pay {
        grid-area: panel;
        border: 0;
    }
    .pos-panel-hidden { display: none !important; }
    .pos-products-head, .zone-head { position: sticky; top: 0; z-index: 2; }
    .prod-grid { grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); }
    .complete-btn-kbd, .search-shortcut, .pos-head-hint { display: none; }
}

@media (max-width: 600px) {
    .pos-page-header h1 { font-size: 20px; }
    .pos-page-meta { font-size: 11.5px; }
    .pos-header-actions { width: 100%; justify-content: flex-start; }
    .held-sales-trigger { width: 100%; justify-content: center; }
    .pos-root { height: max(420px, calc(100dvh - var(--topbar-h) - 195px)); min-height: 420px; }
    .pos-mobile-nav { top: calc(var(--topbar-h) + 4px); }
    .prod-scroll { padding: 9px; }
    .prod-grid { grid-template-columns: repeat(2, minmax(0,1fr)); gap: 8px; }
    .prod-card { min-height: 114px; padding: 10px; }
    .prod-card-meta { font-size: 10px; }
    .prod-card-name { font-size: 13px; }
    .prod-card-price { font-size: 15px; }
    .quickadd-bar { flex-wrap: wrap; }
    .quickadd-name { flex-basis: calc(100% - 38px); }
    .quickadd-bar form { flex: 1; }
    .quickadd-add-btn { width: 100%; }
    .pay-row-2 { grid-template-columns: 1fr; }
    .quick-cash-row { grid-template-columns: repeat(3, minmax(0,1fr)); }
    .quick-cash-btn:first-child { grid-column: span 3; }
    .held-sale-row { align-items: flex-start; flex-direction: column; }
    .held-sale-actions { width: 100%; }
    .held-sale-actions form { flex: 1; }
    .held-sale-actions .btn { width: 100%; }
}

/* ── Touch targets ── */
@media (pointer: coarse) {
    .pos-search input, .cart-qty-input, .quickadd-qty, .pay-input { font-size: 16px; }
    .cat-chip { min-height: 44px; padding: 8px 13px; font-size: 12px; }
    .qty-btn { width: 44px; height: 44px; font-size: 17px; }
    .cart-qty-input { width: 48px; padding: 8px 3px; }
    .cart-remove-btn { width: 44px; height: 44px; }
    .clear-cart-btn { min-height: 38px; padding: 9px 11px; font-size: 12px; }
    .quickadd-add-btn { min-height: 44px; font-size: 13px; }
    .quick-cash-btn { min-height: 44px; font-size: 12px; }
    .pay-method { min-height: 68px; font-size: 11.5px; }
    .complete-btn { min-height: 56px; font-size: 15px; }
}

/* ── Dark mode fixed-status colors ── */
html.dark .stock-pill.ok { background: rgba(52,194,106,.16); color: #a7f3c4; }
html.dark .stock-pill.low,
html.dark .expiry-pill.near { background: rgba(245,158,11,.16); color: #fcd68a; }
html.dark .stock-pill.out,
html.dark .expiry-pill { background: rgba(240,96,96,.16); color: #ffb3b3; }
html.dark .cart-remove-btn { border-color: rgba(240,96,96,.34); }
html.dark .cart-remove-btn:hover { background: rgba(240,96,96,.22); }
html.dark .pay-method.active.cash { border-color: #34c26a; background: rgba(52,194,106,.16); color: #a7f3c4; }
html.dark .pay-method.active.gcash { border-color: #4d9fff; background: rgba(77,159,255,.16); color: #93c5fd; }
html.dark .pay-method.active.card { border-color: #a78bfa; background: rgba(167,139,250,.16); color: #ddd6fe; }
html.dark .change-badge { border-color: rgba(52,194,106,.35); background: rgba(52,194,106,.14); }
html.dark .change-badge-label { color: #a7f3c4; }
html.dark .change-badge-val { color: #4ade80; }


/* Network and interrupted-checkout recovery */
.pos-network-status {
    display: none;
    align-items: center;
    gap: 12px;
    margin: 0 0 14px;
    padding: 12px 14px;
    border: 1px solid #f1b44c;
    border-radius: 11px;
    background: #fff8e8;
    color: #744600;
    box-shadow: 0 4px 14px rgba(116, 70, 0, .08);
}
.pos-network-status.show { display: flex; }
.pos-network-status.error {
    border-color: #ef9a9a;
    background: #fff1f1;
    color: #8f1d1d;
}
.pos-network-status.success {
    border-color: #86d5a5;
    background: #edfff4;
    color: #126332;
}
.pos-network-status-icon {
    width: 34px;
    height: 34px;
    flex: 0 0 auto;
    display: grid;
    place-items: center;
    border-radius: 50%;
    background: rgba(255,255,255,.75);
    font-size: 18px;
}
.pos-network-status-copy { min-width: 0; flex: 1; }
.pos-network-status-copy strong { display: block; margin-bottom: 2px; }
.pos-network-status-copy span { display: block; font-size: 12px; line-height: 1.45; }
.pos-network-status .btn { min-height: 38px; white-space: nowrap; }
html.dark .pos-network-status { background: rgba(180, 111, 0, .18); color: #ffd98b; border-color: rgba(241,180,76,.55); }
html.dark .pos-network-status.error { background: rgba(180, 35, 35, .18); color: #ffc0c0; border-color: rgba(239,154,154,.5); }
html.dark .pos-network-status.success { background: rgba(18, 99, 50, .22); color: #adf1c6; border-color: rgba(134,213,165,.45); }
@media (max-width: 620px) {
    .pos-network-status { align-items: flex-start; flex-wrap: wrap; }
    .pos-network-status-copy { flex-basis: calc(100% - 48px); }
    .pos-network-status .btn { width: 100%; }
}
/* Distinct panels follow the three tasks at the pharmacy counter. */
.pos-root {
    gap: 14px;
    border: 0;
    background: transparent;
    box-shadow: none;
    overflow: visible;
}
.pos-root > [data-pos-panel] {
    border: 1px solid var(--line);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow);
}
.pos-products { background: var(--surface-2); }
.pos-products-head, .zone-head { border-bottom-width: 1px; }
.pos-zone-title, .zone-head-title {
    font-family: var(--heading-font);
    font-size: 14px;
    font-weight: 800;
    text-transform: none;
    letter-spacing: -.2px;
    color: var(--text);
}
.pos-live-stat { border-width: 1px; box-shadow: none; }
.pos-live-stat span { text-transform: none; font-size: 11px; letter-spacing: 0; }
.held-sales-trigger { border-width: 1px; }
.pos-search { background: var(--surface); border-width: 1px; }
.prod-card { border-width: 1px; border-radius: 12px; }
.prod-card-meta { text-transform: none; letter-spacing: 0; }
.prod-card.selected .prod-card-meta { padding-right: 18px; }
.prod-card-price { color: var(--text); font-weight: 800; }
.prod-card:hover:not(.disabled) { transform: none; box-shadow: 0 0 0 2px var(--primary-light); }
.prod-card.disabled { opacity: .7; }
.cat-chip { border-width: 1px; }
.cat-chip.active { background: var(--primary-light); color: var(--primary); }
.cart-total-bar { background: var(--surface); border-top-width: 1px; }
.cart-total-label { font-size: 11px; text-transform: none; letter-spacing: 0; }
.cart-total-val { font-family: var(--heading-font); }
.pos-root .pay-summary { border-width: 1px; }
.pos-root .pay-summary-row.grand { flex-wrap: wrap; gap: 8px; padding: 16px 12px; border: 0; background: #132b3b; color: #fff; }
.pos-root .pay-summary-row.grand .val { color: #fff; font-family: var(--heading-font); font-size: 28px; }
.pay-method { border-width: 1px; background: var(--surface); }
.pay-method .ico { height: 22px; }
.pay-method.active.cash, .pay-method.active.gcash, .pay-method.active.card {
    border-color: var(--primary);
    background: var(--primary-light);
    color: var(--primary);
    box-shadow: inset 0 0 0 1px var(--primary);
}
.complete-btn { box-shadow: none; border-radius: 10px; }
.quickadd-add-btn { color: var(--on-primary); }
.pos-mobile-action { display: none; }
.pos-root input.quickadd-qty { width: 56px; }
.pos-root input.cart-qty-input { width: 44px; }
.pos-root .pos-search input { padding: 2px 0; border: 0; background: transparent; }
.pos-root .pos-search input:focus { box-shadow: none; }
.prod-scroll, .cart-item-list, .pay-scroll { overscroll-behavior: contain; }

@media (max-width: 1100px) {
    .pos-mobile-action { display: inline-flex; }
    .pos-mobile-nav { scroll-margin-top: calc(var(--topbar-h) + 8px); }
    .pos-mobile-tab { min-height: 48px; font-size: 14px; }
    .pos-products-head { padding: 14px; }
    .pos-search { min-height: 48px; }
    .pos-search input { font-size: 16px; }
    .cat-chip { min-height: 44px; padding: 10px 14px; font-size: 13px; }
    .prod-card { min-height: 144px; }
    .prod-card-name { font-size: 14px; }
    .stock-pill, .expiry-pill { font-size: 11px; }
    .cart-item { padding: 16px; gap: 12px; }
    .cart-item-name { font-size: 15px; }
    .cart-item-unit { font-size: 12px; }
    .cart-item-sub { font-size: 16px; }
    .cart-item-bottom { flex-wrap: wrap; }
    .qty-btn, .cart-remove-btn, .quickadd-cancel-btn { width: 44px; height: 44px; flex-shrink: 0; }
    .pos-root input.cart-qty-input { width: 52px; min-height: 44px; font-size: 16px; }
    .pos-root input.quickadd-qty { width: 64px; min-height: 44px; font-size: 16px; }
    .quickadd-add-btn, .clear-cart-btn, .quick-cash-btn { min-height: 44px; }
    .pos-root .pay-input { min-height: 48px; font-size: 16px; }
    .pay-method { min-height: 68px; font-size: 13px; }
    .pay-scroll { padding: 16px; }
    .complete-btn { min-height: 54px; margin-bottom: max(13px, env(safe-area-inset-bottom)); }
    .cart-total-bar { flex-wrap: wrap; }
    .held-modal-overlay { padding: 16px; }
    .held-modal { max-height: calc(100dvh - 32px); }
    .held-modal-close { width: 44px; height: 44px; }
}
@media (max-width: 600px) {
    .pos-page-header { gap: 8px; }
    .pos-page-meta { font-size: 12px; }
    .pos-products-head { padding: 12px; }
    .pos-head-hint { display: none; }
    .prod-card-meta { flex-wrap: wrap; gap: 3px; }
    .prod-card-meta .sku { max-width: 100%; }
    .prod-card-name { display: block; min-height: 38px; overflow-wrap: anywhere; }
    .quickadd-bar { display: none; gap: 8px; }
    .quickadd-bar.show { display: grid; grid-template-columns: 64px minmax(0, 1fr) 44px; }
    .quickadd-name { grid-column: 1 / -1; white-space: normal; font-size: 14px; }
    .quickadd-add-btn { padding-inline: 8px; }
    .cart-total-bar .pos-mobile-action { flex: 1 0 100%; min-height: 48px; }
    .held-sale-actions { flex-wrap: wrap; }
}
@media (max-width: 1100px) and (max-height: 540px) {
    .pos-root { height: auto; min-height: 420px; }
    .prod-scroll { max-height: 360px; }
    .pay-scroll { overflow: visible; }
}
</style>
<link rel="stylesheet" href="<?= base_url('assets/css/pos.css') ?>?v=20260923">


<?php
$grandTotal = 0; $cartCount = 0;
if (!empty($cart)) { foreach ($cart as $item) { $grandTotal += $item['subtotal']; $cartCount++; } }

// Recovery token: generated once for the current cart. The controller stores
// only its SHA-256 hash so an interrupted checkout can be checked safely.
if (!session()->has('checkout_token')) {
    session()->set('checkout_token', bin2hex(random_bytes(16)));
}
$checkoutToken = session()->get('checkout_token');

?>

<!-- Page header -->
<div class="pos-page-header">
    <div>
        <h1>Point of Sale</h1>
        <div class="pos-page-meta">
            <span><?= date('l, F d, Y') ?></span>
            <span class="meta-dot">•</span>
            <span><?= esc(session('full_name')) ?></span>
        </div>
    </div>
    <div class="pos-header-actions">
        <div class="pos-live-stat" aria-label="Cart item count">
            <span>Products in cart</span>
            <strong id="headerCartCount"><?= $cartCount ?></strong>
        </div>
        <div class="pos-live-stat" aria-label="Current amount due">
            <span>Amount Due</span>
            <strong id="headerTotal">₱<?= number_format($grandTotal, 2) ?></strong>
        </div>
        <?php if (!empty($heldSales)): ?>
        <button type="button" class="held-sales-trigger" id="heldSalesTrigger" aria-controls="heldModalOverlay" aria-expanded="false">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
            Held Sales
            <span class="badge badge-warning"><?= count($heldSales) ?></span>
        </button>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($heldSales)): ?>
<div class="held-modal-overlay" id="heldModalOverlay" aria-hidden="true">
    <div class="held-modal" role="dialog" aria-modal="true" aria-labelledby="heldModalTitle">
        <div class="held-modal-head">
            <h2 id="heldModalTitle">Held Sales</h2>
            <button type="button" class="held-modal-close" id="heldModalClose" aria-label="Close">✕</button>
        </div>
        <div class="held-modal-body">
            <?php foreach ($heldSales as $i => $held): ?>
                <?php
                    $heldItemCount = 0;
                    $heldTotal = 0.0;
                    foreach (($held['cart'] ?? []) as $hi) {
                        $heldItemCount += (int) ($hi['quantity'] ?? 0);
                        $heldTotal += (float) ($hi['subtotal'] ?? 0);
                    }
                ?>
                <div class="held-sale-row">
                    <div class="held-sale-info">
                        <div class="held-sale-label"><?= !empty($held['label']) ? esc($held['label']) : 'Held sale #' . ($i + 1) ?></div>
                        <div class="held-sale-meta">
                            <?= $heldItemCount ?> item<?= $heldItemCount === 1 ? '' : 's' ?> · ₱<?= number_format($heldTotal, 2) ?>
                            · <?= esc(\App\Libraries\DisplayDate::time($held['held_at'] ?? 'now')) ?>
                        </div>
                    </div>
                    <div class="held-sale-actions">
                        <form method="post" action="<?= site_url('cashier/sales/resume-held-sale/' . $i) ?>" style="display:inline;">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-primary">Resume</button>
                        </form>
                        <form method="post" action="<?= site_url('cashier/sales/discard-held-sale/' . $i) ?>" style="display:inline;" onsubmit="return confirm('Discard this held sale? This can\'t be undone.')">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-secondary">Discard</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert success pos-alert"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert error pos-alert"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="pos-network-status" id="posNetworkStatus" role="status" aria-live="polite">
    <div class="pos-network-status-icon" id="posNetworkStatusIcon" aria-hidden="true">⚠</div>
    <div class="pos-network-status-copy">
        <strong id="posNetworkStatusTitle">Connection interrupted</strong>
        <span id="posNetworkStatusMessage">Checking whether the sale was completed before allowing another submission.</span>
    </div>
    <button type="button" class="btn btn-secondary" id="checkCheckoutStatusBtn" hidden>Check Sale Status</button>
</div>

<div class="pos-mobile-nav" id="posMobileNav" role="group" aria-label="POS sections">
    <button type="button" class="pos-mobile-tab active" data-pos-target="products" aria-pressed="true" aria-controls="posProducts">
        Products <span class="tab-count" id="mobileProductCount"><?= count($products ?? []) ?></span>
    </button>
    <button type="button" class="pos-mobile-tab" data-pos-target="cart" aria-pressed="false" aria-controls="posCart">
        Cart <span class="tab-count" id="mobileCartCount"><?= $cartCount ?></span>
    </button>
    <button type="button" class="pos-mobile-tab" data-pos-target="payment" aria-pressed="false" aria-controls="posPayment">
        Payment
    </button>
</div>

<div class="pos-root">

    <!-- ZONE A: PRODUCTS -->
    <div class="pos-products" id="posProducts" data-pos-panel="products">
        <div class="pos-products-head">
            <div class="pos-head-row">
                <div class="pos-zone-title">
                    Products
                    <span class="pos-count-pill" id="prodCount" role="status" aria-label="Matching products"><?= count($products ?? []) ?></span>
                </div>
                <a class="btn btn-secondary" href="<?= site_url('cashier/sales/branch-availability') ?>">Check other branches</a>
            </div>
            <div class="pos-search">
                <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                </svg>
                <input type="text" id="productSearch" placeholder="Search product name or SKU…" autocomplete="off" aria-label="Search products">
                <button type="button" class="pos-search-clear" id="clearProductSearch" aria-label="Clear product search" hidden>&times;</button>
                <span class="search-shortcut">/</span>
            </div>
            <?php
            $categories = [];
            foreach (($products ?? []) as $p) {
                $cn = $p['category_name'] ?? 'Other';
                if (!in_array($cn, $categories)) $categories[] = $cn;
            }
            sort($categories);
            ?>
            <?php if (count($categories) > 1): ?>
            <div class="cat-chips" id="catChips">
                <button type="button" class="cat-chip active" data-cat="all" aria-pressed="true">All products</button>
                <?php foreach ($categories as $cat): ?>
                    <button type="button" class="cat-chip" data-cat="<?= esc(strtolower($cat)) ?>" aria-pressed="false"><?= esc($cat) ?></button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <div class="pos-catalog-tools">
                <label class="pos-available-filter"><input type="checkbox" id="availableProductsOnly"> Available to sell</label>
                <button type="button" class="pos-reset-filters" id="resetProductFilters" hidden>Reset filters</button>
            </div>
        </div>

        <div class="prod-scroll">
            <div class="prod-grid" id="prodGrid">
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $p): ?>
                    <?php
                        $expiry       = $p['expiration_date'] ?? null;
                        $isExpired    = !empty($expiry) && strtotime($expiry) < strtotime(date('Y-m-d'));
                        $isNearExpiry = !empty($expiry) && !$isExpired && strtotime($expiry) <= strtotime(date('Y-m-d', strtotime('+30 days')));
                        $stock        = (int)$p['stock'];
                        $outOfStock   = $stock <= 0;
                        $lowStock     = !$outOfStock && $stock <= 10;
                        $disabled     = $outOfStock || $isExpired;
                        $catSlug      = strtolower($p['category_name'] ?? 'other');
                    ?>
                    <div class="prod-card <?= $disabled ? 'disabled' : '' ?>"
                         role="button"
                         tabindex="<?= $disabled ? '-1' : '0' ?>"
                         aria-disabled="<?= $disabled ? 'true' : 'false' ?>"
                         aria-pressed="false"
                         data-pid="<?= esc($p['product_id']) ?>"
                         data-name="<?= esc(strtolower($p['product_name'])) ?>"
                         data-sku="<?= esc(strtolower($p['sku'] ?? '')) ?>"
                         data-cat="<?= esc($catSlug) ?>"
                         data-price="<?= (float)$p['price'] ?>"
                         data-stock="<?= $stock ?>"
                         data-label="<?= esc($p['product_name']) ?>"
                         title="<?= esc($p['product_name']) ?> — ₱<?= number_format((float)$p['price'],2) ?>">
                        <div class="prod-card-check">
                            <svg width="8" height="8" fill="none" viewBox="0 0 24 24" stroke="#fff" stroke-width="3.5"><path d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <div class="prod-card-meta">
                            <span><?= esc($p['category_name'] ?? 'Other') ?></span>
                            <?php if (!empty($p['sku'])): ?><span class="sku"><?= esc($p['sku']) ?></span><?php endif; ?>
                        </div>
                        <div class="prod-card-name"><?= esc($p['product_name']) ?></div>
                        <div class="prod-card-price">₱<?= number_format((float)$p['price'], 2) ?><?php if (!empty($p['unit'])): ?><span class="prod-price-unit"> / <?= esc($p['unit']) ?></span><?php endif; ?></div>
                        <div class="prod-card-footer">
                            <?php if ($outOfStock): ?>
                                <span class="stock-pill out">Out of stock</span>
                            <?php elseif ($lowStock): ?>
                                <span class="stock-pill low"><?= $stock ?> left · Low</span>
                            <?php else: ?>
                                <span class="stock-pill ok"><?= $stock ?> in stock</span>
                            <?php endif; ?>
                            <?php if ($isExpired): ?>
                                <span class="expiry-pill">Expired</span>
                            <?php elseif ($isNearExpiry): ?>
                                <span class="expiry-pill near">Expires soon</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-results">No products available.</div>
                <?php endif; ?>
                <div class="product-filter-empty" id="productFilterEmpty" hidden>
                    <strong>No matching products</strong>
                    Try a different name, SKU, or category.
                </div>
            </div>
        </div>

        <!-- Quick-add bar -->
        <div class="quickadd-bar" id="quickAddBar">
            <span class="quickadd-name" id="quickAddName">—</span>
            <div class="quickadd-stepper">
                <button type="button" class="quickadd-step" data-quickadd-step="-1" aria-label="Decrease quantity to add">−</button>
                <input type="number" class="quickadd-qty" id="quickAddQty" value="1" min="1" inputmode="numeric" aria-label="Quantity to add">
                <button type="button" class="quickadd-step" data-quickadd-step="1" aria-label="Increase quantity to add">+</button>
            </div>
            <form method="post" action="<?= site_url('cashier/sales/add-to-cart') ?>" id="quickAddForm">
                <?= csrf_field() ?>
                <input type="hidden" name="product_id" id="quickAddPid">
                <input type="hidden" name="quantity"   id="quickAddQtyHidden" value="1">
                <button type="submit" class="quickadd-add-btn">+ Add to Cart</button>
            </form>
            <button type="button" class="quickadd-cancel-btn" id="quickAddCancel" title="Cancel" aria-label="Cancel product selection">✕</button>
        </div>
    </div>

    <!-- ZONE B: CART -->
    <div class="pos-cart" id="posCart" data-pos-panel="cart">
        <div class="zone-head">
            <div class="zone-head-title">
                Cart
                <span class="zone-badge" id="cartBadge"><?= $cartCount ?></span>
            </div>
            <?php if ($cartCount > 0): ?>
            <div class="cart-head-actions">
                <form method="post" action="<?= site_url('cashier/sales/hold-sale') ?>" style="display:inline;" id="holdSaleForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="label" id="holdSaleLabel">
                    <button type="button" class="clear-cart-btn hold-cart-btn" id="holdSaleBtn">Hold</button>
                </form>
                <form method="post" action="<?= site_url('cashier/sales/clear-cart') ?>" style="display:inline;">
                    <?= csrf_field() ?>
                    <button type="submit" class="clear-cart-btn">Clear</button>
                </form>
            </div>
            <?php endif; ?>
        </div>

        <?php if (empty($cart)): ?>
        <div class="cart-empty">
            <div class="cart-empty-icon" aria-hidden="true">
                <svg width="27" height="27" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path d="M3 4h2l2.2 10.1a2 2 0 0 0 2 1.6h7.6a2 2 0 0 0 2-1.6L20 8H6"/>
                    <circle cx="10" cy="19" r="1.4"/><circle cx="17" cy="19" r="1.4"/>
                </svg>
            </div>
            <div class="cart-empty-text">Cart is empty</div>
            <div class="cart-empty-sub">Tap a product to add it</div>
            <button type="button" class="btn btn-primary pos-mobile-action" data-pos-go="products">Browse products</button>
        </div>
        <?php else: ?>
        <div class="cart-item-list" id="cartItemList">
            <?php foreach ($cart as $item): ?>
            <div class="cart-item cart-row"
                 data-product-id="<?= esc($item['product_id']) ?>"
                 data-category-id="<?= esc($item['category_id'] ?? '') ?>"
                 data-price="<?= (float)$item['price'] ?>">
                <!-- Row 1: name + remove -->
                <div class="cart-item-top">
                    <div class="cart-item-info">
                        <div class="cart-item-name"><?= esc($item['product_name']) ?></div>
                        <div class="cart-item-unit">₱<?= number_format((float)$item['price'], 2) ?> each</div>
                    </div>
                    <form method="post" action="<?= site_url('cashier/sales/remove-cart-item') ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                        <button type="submit" class="cart-remove-btn" title="Remove" aria-label="Remove <?= esc($item['product_name'], 'attr') ?> from cart">✕</button>
                    </form>
                </div>
                <!-- Row 2: qty controls + subtotal -->
                <div class="cart-item-bottom">
                    <div class="cart-qty-wrap">
                        <button class="qty-btn qty-dec" type="button" aria-label="Decrease quantity for <?= esc($item['product_name'], 'attr') ?>">−</button>
                        <input type="number" class="cart-qty-input" value="<?= (int)$item['quantity'] ?>" min="1" max="<?= (int)($item['stock'] ?? 999999) ?>" aria-label="Quantity for <?= esc($item['product_name']) ?>">
                        <button class="qty-btn qty-inc" type="button" aria-label="Increase quantity for <?= esc($item['product_name'], 'attr') ?>">+</button>
                    </div>
                    <div class="cart-item-sub cart-subtotal-cell">₱<?= number_format((float)$item['subtotal'], 2) ?></div>
                </div>
                <div class="cart-qty-warning"></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="cart-total-bar">
            <div>
                <div class="cart-total-label">Amount due</div>
                <div class="cart-total-val" id="cartTotalDisplay">₱<?= number_format($grandTotal, 2) ?></div>
            </div>
            <button type="button" class="btn btn-primary pos-mobile-action" data-pos-go="payment" <?= $cartCount === 0 ? 'disabled' : '' ?>>Continue to payment &rarr;</button>
        </div>
    </div>

    <!-- ZONE C: PAYMENT -->
    <div class="pos-pay" id="posPayment" data-pos-panel="payment">
        <div class="zone-head">
            <div class="zone-head-title">Payment</div>
            <button type="button" class="btn btn-secondary btn-sm pos-mobile-action" data-pos-go="cart">&larr; Edit cart</button>
        </div>

        <div class="pay-scroll">
            <!-- Order summary -->
            <div class="pay-summary">
                <div class="pay-summary-row">
                    <span>Items</span>
                    <span id="coItemCount"><?= $cartCount ?> item<?= $cartCount !== 1 ? 's' : '' ?></span>
                </div>
                <div class="pay-summary-row">
                    <span>Subtotal</span>
                    <span id="coSubtotal">₱<?= number_format($grandTotal, 2) ?></span>
                </div>
                <div class="pay-summary-row" id="coDiscountRow" style="display:none;">
                    <span>Discount</span>
                    <span id="coDiscountAmt" style="color:#16a34a;">−₱0.00</span>
                </div>
                <div class="pay-summary-row grand">
                    <span>Total</span>
                    <span class="val" id="coFinalTotal">₱<?= number_format($grandTotal, 2) ?></span>
                </div>
            </div>

            <form method="post" action="<?= site_url('cashier/sales/checkout') ?>" id="checkoutForm">
                <?= csrf_field() ?>
                <input type="hidden" name="_checkout_token" value="<?= esc($checkoutToken) ?>">
                <div id="checkoutItemsContainer"></div>
                <input type="hidden" id="grandTotalValue" value="<?= (float)$grandTotal ?>">

                <!-- Payment method -->
                <div>
                    <div class="pay-label">Payment Method</div>
                    <div class="pay-methods">
                        <button type="button" class="pay-method cash active" data-method="cash" aria-pressed="true"><span class="ico"><?= view('partials/icon', ['name' => 'wallet']) ?></span>Cash</button>
                        <button type="button" class="pay-method gcash" data-method="gcash" aria-pressed="false"><span class="ico"><?= view('partials/icon', ['name' => 'phone']) ?></span>GCash</button>
                        <button type="button" class="pay-method card" data-method="card" aria-pressed="false"><span class="ico"><?= view('partials/icon', ['name' => 'card']) ?></span>Card</button>
                    </div>
                    <input type="hidden" name="payment_method" id="paymentMethodInput" value="cash">
                </div>

                <!-- Reference and discount -->
                <div class="pay-row-2">
                    <div>
                        <label class="pay-label" id="referenceNoLabel" for="referenceNoInput">Reference No. <span class="optional">(optional)</span></label>
                        <input type="text" name="reference_no" id="referenceNoInput" class="pay-input" placeholder="Optional for cash">
                    </div>

                    <!-- Select the discount before entering payment so the total updates first. -->
                    <div>
                        <label class="pay-label" for="discountSelect">Discount</label>
                        <div class="select-wrap">
                            <select name="discount_id" id="discountSelect" class="pay-input">
                                <option value="">No Discount</option>
                                <?php foreach (($discounts ?? []) as $d): ?>
                                    <?php
                                        $minPurchase = (float) ($d['minimum_purchase'] ?? 0);
                                        $scope = $d['applies_to'] ?? 'all';
                                        $scopeLabel = 'All products';
                                        if ($scope === 'category') {
                                            $scopeLabel = !empty($d['category_name'])
                                                ? 'Category: ' . $d['category_name']
                                                : 'Selected category';
                                        } elseif ($scope === 'product') {
                                            $scopeLabel = !empty($d['target_product_name'])
                                                ? 'Product: ' . $d['target_product_name']
                                                : 'Selected product';
                                        }
                                    ?>
                                    <option value="<?= $d['id'] ?>"
                                        data-type="<?= esc($d['discount_type']) ?>"
                                        data-value="<?= esc($d['discount_value']) ?>"
                                        data-minimum="<?= esc($d['minimum_purchase'] ?? 0) ?>"
                                        data-max="<?= esc($d['max_discount_amount'] ?? '') ?>"
                                        data-applies-to="<?= esc($scope) ?>"
                                        data-category-id="<?= esc($d['category_id'] ?? '') ?>"
                                        data-product-id="<?= esc($d['product_id'] ?? '') ?>">
                                        <?= esc($d['discount_name']) ?>
                                        (<?= $d['discount_type'] === 'percentage'
                                            ? rtrim(rtrim(number_format((float)$d['discount_value'],2),'0'),'.').'%'
                                            : '₱'.number_format((float)$d['discount_value'],2) ?>)
                                        — <?= esc($scopeLabel) ?><?php if ($minPurchase > 0): ?>, min. ₱<?= number_format($minPurchase, 2) ?><?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="discount-warning" id="discountWarning"></div>
                    </div>
                </div>

                <!-- Amount paid -->
                <div>
                    <label class="pay-label" for="amountPaidInput">Amount paid <span style="color:var(--danger);">*</span></label>
                    <input type="number" step="0.01" min="0" name="amount_paid" id="amountPaidInput" class="pay-input" placeholder="0.00" inputmode="decimal" required>
                </div>

                <div class="quick-cash-row" id="quickCashRow">
                    <button type="button" class="quick-cash-btn" data-tender="exact">Exact</button>
                    <button type="button" class="quick-cash-btn" data-tender="100">₱100</button>
                    <button type="button" class="quick-cash-btn" data-tender="200">₱200</button>
                    <button type="button" class="quick-cash-btn" data-tender="500">₱500</button>
                    <button type="button" class="quick-cash-btn" data-tender="1000">₱1000</button>
                </div>

                <!-- Change -->
                <div class="change-badge" id="changeRow">
                    <span class="change-badge-label">Change</span>
                    <span class="change-badge-val" id="changeDisplay">₱0.00</span>
                </div>

                <!-- Notes -->
                <div>
                    <label class="pay-label" for="saleNotes">Notes <span class="optional">(optional)</span></label>
                    <textarea name="notes" id="saleNotes" class="pay-input" rows="2" placeholder="Add a note to this sale" style="resize:none;"></textarea>
                </div>
            </form>
        </div>

        <button type="submit" form="checkoutForm" class="complete-btn" id="completeSaleBtn" <?= $cartCount === 0 ? 'disabled' : '' ?>>
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.8"><path d="M5 13l4 4L19 7"/></svg>
            Complete Sale
            <span class="complete-btn-kbd">Ctrl+Enter</span>
        </button>
    </div>

</div>

<script src="<?= base_url('assets/js/discount-math.js') ?>?v=20260920"></script>
<script>
window._siteUrl = "<?= site_url() ?>";
document.addEventListener('DOMContentLoaded', function () {

    /* ── RESPONSIVE PANEL NAVIGATION ── */
    const mobileMedia = window.matchMedia('(max-width: 1100px)');
    const posPanels = Array.from(document.querySelectorAll('[data-pos-panel]'));
    const posTabs = Array.from(document.querySelectorAll('[data-pos-target]'));
    const hasCartItems = document.querySelectorAll('.cart-row').length > 0;

    function setActivePosPanel(name, persist = true) {
        const safeName = ['products', 'cart', 'payment'].includes(name) ? name : 'products';
        if (!mobileMedia.matches) {
            posPanels.forEach(panel => panel.classList.remove('pos-panel-hidden'));
            return;
        }
        posPanels.forEach(panel => {
            panel.classList.toggle('pos-panel-hidden', panel.dataset.posPanel !== safeName);
        });
        posTabs.forEach(tab => {
            const active = tab.dataset.posTarget === safeName;
            tab.classList.toggle('active', active);
            tab.setAttribute('aria-pressed', String(active));
        });
        if (persist) sessionStorage.setItem('pharxmaco-pos-panel', safeName);
    }

    const savedPanel = sessionStorage.getItem('pharxmaco-pos-panel');
    setActivePosPanel(hasCartItems ? (savedPanel || 'products') : 'products', false);
    posTabs.forEach(tab => tab.addEventListener('click', () => setActivePosPanel(tab.dataset.posTarget)));
    document.querySelectorAll('[data-pos-go]').forEach(button => {
        button.addEventListener('click', () => {
            setActivePosPanel(button.dataset.posGo);
            document.getElementById('posMobileNav').scrollIntoView({ block: 'start' });
            document.querySelector('[data-pos-target="' + button.dataset.posGo + '"]')?.focus({ preventScroll: true });
        });
    });
    if (typeof mobileMedia.addEventListener === 'function') {
        mobileMedia.addEventListener('change', () => setActivePosPanel(sessionStorage.getItem('pharxmaco-pos-panel') || 'products', false));
    } else if (typeof mobileMedia.addListener === 'function') {
        mobileMedia.addListener(() => setActivePosPanel(sessionStorage.getItem('pharxmaco-pos-panel') || 'products', false));
    }

    /* ── PRODUCT CARD SELECT / QUICK ADD ── */
    const quickAddBar  = document.getElementById('quickAddBar');
    const quickAddName = document.getElementById('quickAddName');
    const quickAddQty  = document.getElementById('quickAddQty');
    const quickAddPid  = document.getElementById('quickAddPid');
    const quickAddQtyH = document.getElementById('quickAddQtyHidden');
    const quickAddCancel = document.getElementById('quickAddCancel');
    const prodCards    = document.querySelectorAll('.prod-card');
    let selectedCard   = null;

    function selectCard(card) {
        if (selectedCard) {
            selectedCard.classList.remove('selected');
            selectedCard.setAttribute('aria-pressed', 'false');
        }
        selectedCard = card;
        card.classList.add('selected');
        card.setAttribute('aria-pressed', 'true');
        quickAddName.textContent = card.dataset.label;
        quickAddPid.value  = card.dataset.pid;
        quickAddQty.max    = card.dataset.stock;
        quickAddQty.value  = 1;
        quickAddQtyH.value = 1;
        quickAddBar.classList.add('show');
        // Touch users can adjust quantity without opening the on-screen keyboard.
        if (!window.matchMedia('(pointer: coarse)').matches) {
            quickAddQty.focus(); quickAddQty.select();
        }
    }
    function deselect() {
        if (selectedCard) {
            selectedCard.classList.remove('selected');
            selectedCard.setAttribute('aria-pressed', 'false');
            selectedCard = null;
        }
        quickAddBar.classList.remove('show');
    }

    prodCards.forEach(card => {
        card.addEventListener('click', () => {
            if (!card.classList.contains('disabled')) {
                selectedCard === card ? deselect() : selectCard(card);
            }
        });
        card.addEventListener('keydown', (event) => {
            if ((event.key === 'Enter' || event.key === ' ') && !card.classList.contains('disabled')) {
                event.preventDefault();
                card.click();
            }
        });
    });

    function cancelProductSelection() {
        const card = selectedCard;
        deselect();
        if (card?.getClientRects().length) card.focus({preventScroll: true});
    }
    quickAddCancel?.addEventListener('click', cancelProductSelection);
    quickAddQty.addEventListener('input', function () {
        const max = parseInt(this.max || '1', 10);
        let qty = parseInt(this.value || '1', 10);
        if (!Number.isFinite(qty) || qty < 1) qty = 1;
        if (qty > max) qty = max;
        this.value = qty;
        quickAddQtyH.value = qty;
    });
    document.querySelectorAll('[data-quickadd-step]').forEach(button => {
        button.addEventListener('click', () => {
            quickAddQty.value = (parseInt(quickAddQty.value, 10) || 1) + Number(button.dataset.quickaddStep);
            quickAddQty.dispatchEvent(new Event('input', {bubbles: true}));
        });
    });

    /* ── SYNC CART TO SERVER BEFORE ANY PAGE-RELOAD ACTION ── */
    function getCurrentCartItems() {
        const items = {};
        document.querySelectorAll('.cart-row').forEach(row => {
            const pid = row.getAttribute('data-product-id');
            const qty = parseInt(row.querySelector('.cart-qty-input')?.value || 0) || 0;
            if (pid && qty > 0) items[pid] = qty;
        });
        return items;
    }

    // Refresh every csrf_ hidden input on the page with a new token.
    // Called after each AJAX POST so the next full-page form submission
    // uses the current token (CI4 regenerates on every POST).
    function refreshCsrfTokens(name, hash) {
        document.querySelectorAll('input[name^="csrf_"]').forEach(function (el) {
            el.name  = name;
            el.value = hash;
        });
    }

    async function syncCartToServer() {
        const items = getCurrentCartItems();
        if (!Object.keys(items).length) return true;
        const csrfInput = document.querySelector('input[name^="csrf_"]');
        const fd = new FormData();
        if (csrfInput) fd.append(csrfInput.name, csrfInput.value);
        Object.entries(items).forEach(([pid, qty]) => fd.append('items[' + pid + ']', qty));
        try {
            const res = await fetch(window._siteUrl + 'cashier/sales/sync-cart', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: fd
            });
            if (!res.ok) return false;
            const data = await res.json();
            // Update all CSRF inputs so subsequent form POSTs use the fresh token.
            if (data.csrf_name && data.csrf_hash) {
                refreshCsrfTokens(data.csrf_name, data.csrf_hash);
            }
            return data.success !== false;
        } catch (e) {
            return false;
        }
    }

    // Intercept quick-add form submit — sync first, then submit
    document.getElementById('quickAddForm')?.addEventListener('submit', async function (e) {
        e.preventDefault();
        sessionStorage.setItem('pharxmaco-pos-panel', 'cart');
        const synced = await syncCartToServer();
        if (!synced) {
            alert('Could not save the latest cart quantities. Please check your connection and try again.');
            return;
        }
        this.submit();
    });

    // Intercept remove-item forms — sync first, then submit
    document.querySelectorAll('.cart-item form').forEach(form => {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            sessionStorage.setItem('pharxmaco-pos-panel', 'cart');
            const synced = await syncCartToServer();
            if (!synced) {
                alert('Could not save the latest cart quantities. Please check your connection and try again.');
                return;
            }
            this.submit();
        });
    });

    // Intercept clear-cart form
    document.querySelector('form[action*="clear-cart"]')?.addEventListener('submit', async function (e) {
        if (!confirm('Clear the current cart?')) { e.preventDefault(); return; }
        e.preventDefault();
        sessionStorage.setItem('pharxmaco-pos-panel', 'products');
        const synced = await syncCartToServer();
        if (!synced) {
            alert('Could not save the latest cart quantities. Please check your connection and try again.');
            return;
        }
        this.submit();
    });


    /* ── CATEGORY CHIPS + SEARCH (combined) ── */
    let activeCat = 'all';

    function applyFilters() {
        const q = (searchInput.value || '').toLowerCase().trim();
        let visible = 0;
        prodCards.forEach(card => {
            const inCat = activeCat === 'all' || card.dataset.cat === activeCat;
            const inSearch = !q || card.dataset.name.includes(q) || card.dataset.sku.includes(q);
            const inAvailability = !document.getElementById('availableProductsOnly').checked || !card.classList.contains('disabled');
            const show = inCat && inSearch && inAvailability;
            card.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        document.getElementById('prodCount').textContent = visible;
        const mobileProductCount = document.getElementById('mobileProductCount');
        if (mobileProductCount) mobileProductCount.textContent = visible;
        const emptyState = document.getElementById('productFilterEmpty');
        if (emptyState) emptyState.hidden = visible !== 0 || prodCards.length === 0;
        document.getElementById('clearProductSearch').hidden = searchInput.value.length === 0;
        document.getElementById('resetProductFilters').hidden = !q && activeCat === 'all' && !document.getElementById('availableProductsOnly').checked;
        kbIndex = -1;
        deselect();
    }

    document.querySelectorAll('.cat-chip').forEach(chip => {
        chip.addEventListener('click', function () {
            document.querySelectorAll('.cat-chip').forEach(c => {
                c.classList.remove('active');
                c.setAttribute('aria-pressed', 'false');
            });
            this.classList.add('active');
            this.setAttribute('aria-pressed', 'true');
            activeCat = this.dataset.cat;
            applyFilters();
        });
    });

    /* ── SEARCH ── */
    const searchInput = document.getElementById('productSearch');
    let searchTimer, kbIndex = -1;
    document.getElementById('clearProductSearch').addEventListener('click', () => {
        searchInput.value = '';
        applyFilters();
        searchInput.focus();
    });
    document.getElementById('availableProductsOnly').addEventListener('change', applyFilters);
    document.getElementById('resetProductFilters').addEventListener('click', () => {
        searchInput.value = '';
        activeCat = 'all';
        document.getElementById('availableProductsOnly').checked = false;
        document.querySelectorAll('.cat-chip').forEach(chip => {
            const active = chip.dataset.cat === 'all';
            chip.classList.toggle('active', active);
            chip.setAttribute('aria-pressed', String(active));
        });
        applyFilters();
        searchInput.focus();
    });

    function getVisible() { return Array.from(prodCards).filter(c => c.style.display !== 'none' && !c.classList.contains('disabled')); }

    function highlightKb(idx) {
        getVisible().forEach(c => { c.classList.remove('selected'); c.setAttribute('aria-pressed', 'false'); });
        selectedCard = null;
        quickAddBar.classList.remove('show');
        const vis = getVisible();
        if (!vis.length) return;
        idx = Math.max(0, Math.min(idx, vis.length - 1));
        kbIndex = idx;
        vis[idx].scrollIntoView({ block: 'nearest' });
        vis[idx].classList.add('selected');
    }

    searchInput.addEventListener('keydown', function (e) {
        const vis = getVisible();
        if (e.key === 'ArrowDown') { e.preventDefault(); highlightKb(kbIndex < vis.length - 1 ? kbIndex + 1 : vis.length - 1); }
        if (e.key === 'ArrowUp')   { e.preventDefault(); highlightKb(kbIndex > 0 ? kbIndex - 1 : 0); }
        if (e.key === 'Enter' && kbIndex >= 0 && vis[kbIndex] && !vis[kbIndex].classList.contains('disabled')) {
            e.preventDefault(); selectCard(vis[kbIndex]);
        }
    });

    searchInput.addEventListener('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(applyFilters, 150);
    });

    document.addEventListener('keydown', function (event) {
        if (window.PharxmacoModal?.isOpen() || document.getElementById('sidebar')?.classList.contains('open')) return;
        const tag = document.activeElement?.tagName?.toLowerCase();
        const isTyping = ['input', 'textarea', 'select'].includes(tag);
        if (event.key === '/' && !isTyping) {
            event.preventDefault();
            setActivePosPanel('products');
            searchInput.focus();
        }
        if (event.key === 'Escape' && selectedCard) cancelProductSelection();
    });

    /* ── PAYMENT METHOD ── */
    const referenceNoInput = document.getElementById('referenceNoInput');
    const referenceNoLabel = document.getElementById('referenceNoLabel');

    document.querySelectorAll('.pay-method').forEach(btn => {
        btn.addEventListener('click', function () {
            const method = this.dataset.method;
            document.querySelectorAll('.pay-method').forEach(b => {
                b.classList.remove('active');
                b.setAttribute('aria-pressed', 'false');
            });
            this.classList.add('active');
            this.setAttribute('aria-pressed', 'true');
            document.getElementById('paymentMethodInput').value = method;
            document.querySelectorAll('.quick-cash-btn[data-tender]:not([data-tender="exact"])').forEach(b => {
                b.style.display = method === 'cash' ? '' : 'none';
            });
            if (referenceNoInput) {
                referenceNoInput.placeholder = method === 'gcash'
                    ? 'Optional GCash reference'
                    : method === 'card'
                        ? 'Optional approval/reference no.'
                        : 'Optional for cash';
            }
            if (referenceNoLabel) {
                const title = method === 'gcash' ? 'GCash Reference' : method === 'card' ? 'Card Reference' : 'Reference No.';
                referenceNoLabel.innerHTML = title + ' <span class="optional">(optional)</span>';
            }
        });
    });

    document.querySelectorAll('.quick-cash-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const tender = this.dataset.tender;
            const value = tender === 'exact' ? currentFinalTotal : parseFloat(tender);
            if (amountPaidInput) {
                amountPaidInput.value = value.toFixed(2);
                refreshAll();
            }
        });
    });

    /* ── TOTALS & CHANGE ── */
    const grandTotalInput  = document.getElementById('grandTotalValue');
    let currentFinalTotal  = 0;
    let currentDiscountValid = true;
    const cartTotalDisplay = document.getElementById('cartTotalDisplay');
    const coSubtotal       = document.getElementById('coSubtotal');
    const coDiscountAmt    = document.getElementById('coDiscountAmt');
    const coDiscountRow    = document.getElementById('coDiscountRow');
    const coFinalTotal     = document.getElementById('coFinalTotal');
    const cartBadge        = document.getElementById('cartBadge');
    const coItemCount      = document.getElementById('coItemCount');
    const discountSelect   = document.getElementById('discountSelect');
    const discountWarning  = document.getElementById('discountWarning');
    const checkoutItemsCon = document.getElementById('checkoutItemsContainer');
    const amountPaidInput  = document.getElementById('amountPaidInput');
    const changeRow        = document.getElementById('changeRow');
    const changeDisplay    = document.getElementById('changeDisplay');
    const completeSaleBtn  = document.getElementById('completeSaleBtn');
    const headerCartCount  = document.getElementById('headerCartCount');
    const headerTotal      = document.getElementById('headerTotal');
    const mobileCartCount  = document.getElementById('mobileCartCount');

    const pesoFormatter = new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
    function fmt(v) { return pesoFormatter.format(Number(v) || 0); }

    function rebuildHiddenItems() {
        if (!checkoutItemsCon) return;
        checkoutItemsCon.innerHTML = '';
        document.querySelectorAll('.cart-row').forEach(row => {
            const pid = row.getAttribute('data-product-id');
            const qty = parseInt(row.querySelector('.cart-qty-input')?.value || 0) || 0;
            if (pid && qty > 0) {
                const h = document.createElement('input');
                h.type = 'hidden'; h.name = 'items[' + pid + ']'; h.value = qty;
                checkoutItemsCon.appendChild(h);
            }
        });
    }

    function calcDiscount(lines, subtotal) {
        currentDiscountValid = true;
        const sel = discountSelect?.options[discountSelect.selectedIndex];

        if (discountWarning) {
            discountWarning.textContent = '';
            discountWarning.classList.remove('show');
        }

        if (!sel || !sel.value) return 0;

        const type = sel.getAttribute('data-type');
        const val = parseFloat(sel.getAttribute('data-value') || 0);
        const min = parseFloat(sel.getAttribute('data-minimum') || 0);
        const max = parseFloat(sel.getAttribute('data-max') || 0);
        const scope = sel.getAttribute('data-applies-to') || 'all';
        const categoryId = parseInt(sel.getAttribute('data-category-id') || 0, 10);
        const productId = parseInt(sel.getAttribute('data-product-id') || 0, 10);

        const eligibleCents = lines.reduce((sum, line) => {
            let eligible = scope === 'all';

            if (scope === 'category') {
                eligible = categoryId > 0 && line.categoryId === categoryId;
            } else if (scope === 'product') {
                eligible = productId > 0 && line.productId === productId;
            }

            return eligible ? sum + window.PharxmacoDiscount.cents(line.subtotal) : sum;
        }, 0);
        const eligibleSubtotal = eligibleCents / 100;

        if (eligibleSubtotal <= 0) {
            currentDiscountValid = false;
            if (discountWarning) {
                discountWarning.textContent = 'This discount does not apply to any item in the cart.';
                discountWarning.classList.add('show');
            }
            return 0;
        }

        if (eligibleCents < window.PharxmacoDiscount.cents(min)) {
            currentDiscountValid = false;
            if (discountWarning) {
                discountWarning.textContent = 'Add ' + fmt(min - eligibleSubtotal)
                    + ' more in matching items to qualify (min. ' + fmt(min) + ').';
                discountWarning.classList.add('show');
            }
            return 0;
        }

        return window.PharxmacoDiscount.calculate(eligibleSubtotal, {
            type, value: val, minimum: min, maximum: max > 0 ? max : null
        });
    }

    function refreshAll() {
        let total = 0, count = 0;
        const lines = [];

        document.querySelectorAll('.cart-row').forEach(row => {
            const price = parseFloat(row.getAttribute('data-price') || 0);
            const qty = parseInt(row.querySelector('.cart-qty-input')?.value || 0) || 0;
            const sub = window.PharxmacoDiscount.cents(price) * qty / 100;
            const cell = row.querySelector('.cart-subtotal-cell');
            const productId = parseInt(row.getAttribute('data-product-id') || 0, 10);
            const categoryId = parseInt(row.getAttribute('data-category-id') || 0, 10);

            if (cell) cell.textContent = fmt(sub);

            total += sub;
            if (qty > 0) {
                count++;
                lines.push({
                    productId,
                    categoryId,
                    subtotal: sub
                });
            }
        });

        const disc     = calcDiscount(lines, total);
        const finalTot = Math.max(0, window.PharxmacoDiscount.cents(total) - window.PharxmacoDiscount.cents(disc)) / 100;
        const paid     = parseFloat(amountPaidInput?.value || 0);
        const change   = Math.max(0, window.PharxmacoDiscount.cents(paid) - window.PharxmacoDiscount.cents(finalTot)) / 100;

        if (grandTotalInput)  grandTotalInput.value = total.toFixed(2);
        if (amountPaidInput)  amountPaidInput.min = finalTot.toFixed(2);
        if (cartTotalDisplay) cartTotalDisplay.textContent = fmt(finalTot);
        if (cartBadge)        cartBadge.textContent = count;
        if (headerCartCount)  headerCartCount.textContent = count;
        if (mobileCartCount)  mobileCartCount.textContent = count;
        if (headerTotal)      headerTotal.textContent = fmt(finalTot);
        if (coItemCount)      coItemCount.textContent = count + ' item' + (count !== 1 ? 's' : '');
        if (coSubtotal)       coSubtotal.textContent = fmt(total);
        if (coDiscountAmt)    coDiscountAmt.textContent = '−' + fmt(disc);
        if (coDiscountRow)    coDiscountRow.style.display = disc > 0 ? '' : 'none';
        if (coFinalTotal)     coFinalTotal.textContent = fmt(finalTot);
        currentFinalTotal = finalTot;

        if (changeRow) {
            if (paid > 0 && paid >= finalTot) {
                changeRow.classList.add('show');
                if (changeDisplay) changeDisplay.textContent = fmt(change);
            } else {
                changeRow.classList.remove('show');
            }
        }

        currentCartLineCount = count;
        if (completeSaleBtn && !checkoutBusy) completeSaleBtn.disabled = count === 0 || !navigator.onLine || !currentDiscountValid;
        rebuildHiddenItems();
    }

    /* ── NETWORK-SAFE CHECKOUT AND RECOVERY ── */
    const checkoutForm = document.getElementById('checkoutForm');
    const checkoutTokenInput = checkoutForm?.querySelector('input[name="_checkout_token"]');
    const networkStatus = document.getElementById('posNetworkStatus');
    const networkStatusIcon = document.getElementById('posNetworkStatusIcon');
    const networkStatusTitle = document.getElementById('posNetworkStatusTitle');
    const networkStatusMessage = document.getElementById('posNetworkStatusMessage');
    const checkStatusButton = document.getElementById('checkCheckoutStatusBtn');
    const pendingCheckoutKey = 'pharxmaco-pending-checkout';
    const completeButtonDefault = completeSaleBtn?.innerHTML || 'Complete Sale';
    let checkoutBusy = false;
    let currentCartLineCount = document.querySelectorAll('.cart-row').length;
    let recoveryCheckRunning = false;

    function setNetworkMessage(type, title, message, canCheck = false) {
        if (!networkStatus) return;
        networkStatus.classList.remove('error', 'success');
        if (type) networkStatus.classList.add(type);
        networkStatus.classList.add('show');
        if (networkStatusIcon) networkStatusIcon.textContent = type === 'success' ? '✓' : (type === 'error' ? '!' : '⚠');
        if (networkStatusTitle) networkStatusTitle.textContent = title;
        if (networkStatusMessage) networkStatusMessage.textContent = message;
        if (checkStatusButton) checkStatusButton.hidden = !canCheck;
    }

    function hideNetworkMessage() {
        if (networkStatus && navigator.onLine) networkStatus.classList.remove('show', 'error', 'success');
        if (checkStatusButton) checkStatusButton.hidden = true;
    }

    function storePendingCheckout(token) {
        try {
            localStorage.setItem(pendingCheckoutKey, JSON.stringify({ token, createdAt: Date.now() }));
        } catch (error) {
            console.warn('Could not save checkout recovery information.', error);
        }
    }

    function readPendingCheckout() {
        try {
            const parsed = JSON.parse(localStorage.getItem(pendingCheckoutKey) || 'null');
            if (!parsed || !/^[a-f0-9]{32}$/i.test(parsed.token || '')) return null;
            return parsed;
        } catch (error) {
            return null;
        }
    }

    function clearPendingCheckout() {
        try { localStorage.removeItem(pendingCheckoutKey); } catch (error) {}
    }

    function setCheckoutBusy(busy, message = 'Processing…') {
        checkoutBusy = busy;
        if (!completeSaleBtn) return;
        if (busy) {
            completeSaleBtn.disabled = true;
            completeSaleBtn.innerHTML = '<svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" style="animation:spin .7s linear infinite"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg> ' + message;
        } else {
            completeSaleBtn.innerHTML = completeButtonDefault;
            completeSaleBtn.disabled = currentCartLineCount === 0 || !navigator.onLine || !currentDiscountValid;
        }
    }

    async function checkInterruptedCheckout(options = {}) {
        const pending = readPendingCheckout();
        if (!pending || recoveryCheckRunning) return false;
        if (!navigator.onLine) {
            setNetworkMessage('error', 'No internet connection', 'Reconnect first. The system will then check whether the sale was completed.', true);
            return false;
        }

        recoveryCheckRunning = true;
        setCheckoutBusy(true, 'Checking sale…');
        setNetworkMessage('', 'Checking sale status', 'Please wait. Do not submit the payment again yet.', false);

        const attempts = Math.max(1, Number(options.attempts || 1));
        try {
            for (let attempt = 0; attempt < attempts; attempt++) {
                const url = window._siteUrl + 'cashier/sales/checkout-status?token=' + encodeURIComponent(pending.token);
                const response = await fetch(url, {
                    method: 'GET',
                    credentials: 'same-origin',
                    cache: 'no-store',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const result = await response.json().catch(() => ({}));

                if (response.status === 503 && result.migration_required) {
                    setNetworkMessage('error', 'Recovery setup is incomplete', result.message || 'Run the latest database migration.', false);
                    setCheckoutBusy(false);
                    return false;
                }

                if (response.ok && result.found && result.redirect_url) {
                    clearPendingCheckout();
                    setNetworkMessage('success', 'Sale completed', 'Opening the completed sale. No duplicate was created.', false);
                    window.location.assign(result.redirect_url);
                    return true;
                }

                if (attempt < attempts - 1) {
                    await new Promise(resolve => setTimeout(resolve, 1800));
                }
            }

            clearPendingCheckout();
            setCheckoutBusy(false);
            setNetworkMessage('success', 'No completed sale found', 'Your cart is still available. You may review it and submit the sale again safely.', false);
            window.setTimeout(hideNetworkMessage, 6500);
            return false;
        } catch (error) {
            setCheckoutBusy(true, 'Waiting for connection…');
            setNetworkMessage('error', 'Could not confirm the sale', 'Keep this page open. Reconnect, then use Check Sale Status before trying again.', true);
            return false;
        } finally {
            recoveryCheckRunning = false;
        }
    }

    checkoutForm?.addEventListener('submit', async function (event) {
        event.preventDefault();
        if (checkoutBusy) return;
        if (!currentDiscountValid) return;
        if (!this.reportValidity()) return;
        rebuildHiddenItems();

        if (!navigator.onLine) {
            setNetworkMessage('error', 'No internet connection', 'Reconnect before completing the sale. Your cart has not been submitted.', false);
            setCheckoutBusy(false);
            return;
        }

        const token = checkoutTokenInput?.value || '';
        if (!/^[a-f0-9]{32}$/i.test(token)) {
            setNetworkMessage('error', 'Checkout needs to be refreshed', 'Reload the POS page before submitting this sale.', false);
            return;
        }

        storePendingCheckout(token);
        setCheckoutBusy(true);
        setNetworkMessage('', 'Processing sale', 'Please wait and do not close this page.', false);

        try {
            const response = await fetch(this.action, {
                method: 'POST',
                body: new FormData(this),
                credentials: 'same-origin',
                redirect: 'follow',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            // A normal controller response ends in a redirect to either
            // the completed-sale page or back to POS with validation feedback.
            if (response.redirected) {
                clearPendingCheckout();
                window.location.assign(response.url);
                return;
            }

            // A direct HTTP error means the request reached the server, so it
            // is not an uncertain network state. Keep the cart and show a
            // useful message instead of navigating to the POST-only URL.
            clearPendingCheckout();
            setCheckoutBusy(false);
            setNetworkMessage(
                'error',
                'The server could not process the sale',
                response.status === 419 || response.status === 403
                    ? 'Your security session may have expired. Refresh the POS page and try again.'
                    : 'Your cart was kept. Refresh the page or try again after checking the connection.',
                false
            );
        } catch (error) {
            setNetworkMessage('error', 'Connection interrupted during checkout', 'The system is checking whether the sale was saved. Do not submit it again yet.', true);
            await checkInterruptedCheckout({ attempts: 6 });
        }
    });

    checkStatusButton?.addEventListener('click', function () {
        checkInterruptedCheckout({ attempts: 4 });
    });

    window.addEventListener('offline', function () {
        if (checkoutBusy || readPendingCheckout()) {
            setNetworkMessage('error', 'Connection lost during checkout', 'Keep this page open. The sale status will be checked after reconnection.', false);
        } else {
            setNetworkMessage('error', 'You are offline', 'Checkout is paused until the connection returns. Your cart remains on this device and session.', false);
        }
        if (completeSaleBtn) completeSaleBtn.disabled = true;
    });

    window.addEventListener('online', function () {
        if (readPendingCheckout()) {
            checkInterruptedCheckout({ attempts: 5 });
        } else {
            setNetworkMessage('success', 'Connection restored', 'You can continue using the POS.', false);
            setCheckoutBusy(false);
            window.setTimeout(hideNetworkMessage, 3000);
        }
    });

    if (!navigator.onLine) {
        setNetworkMessage('error', 'You are offline', 'Checkout is paused until the connection returns. Your cart remains available.', false);
        if (completeSaleBtn) completeSaleBtn.disabled = true;
    } else if (readPendingCheckout()) {
        checkInterruptedCheckout({ attempts: 3 });
    }

    /* ── Ctrl+Enter completes the sale from anywhere on the page ── */
    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            if (window.PharxmacoModal?.isOpen() || document.getElementById('sidebar')?.classList.contains('open')) return;
            if (completeSaleBtn && !completeSaleBtn.disabled) {
                e.preventDefault();
                setActivePosPanel('payment');
                completeSaleBtn.click();
            }
        }
    });

    document.getElementById('holdSaleBtn')?.addEventListener('click', async function () {
        const label = prompt('Label this held sale (for example, customer name) — optional:', '');
        if (label === null) return;
        this.disabled = true;
        document.getElementById('holdSaleLabel').value = label;
        sessionStorage.setItem('pharxmaco-pos-panel', 'products');
        const synced = await syncCartToServer();
        if (!synced) {
            this.disabled = false;
            alert('Could not save the latest cart quantities. Please check your connection and try again.');
            return;
        }
        document.getElementById('holdSaleForm').submit();
    });

    (function () {
        const trigger = document.getElementById('heldSalesTrigger');
        const overlay = document.getElementById('heldModalOverlay');
        const closeBtn = document.getElementById('heldModalClose');
        if (!trigger || !overlay || !closeBtn) return;

        function openHeldModal() {
            window.PharxmacoModal.open(overlay, {trigger: trigger, initialFocus: closeBtn});
        }
        function closeHeldModal() {
            window.PharxmacoModal.close(overlay);
        }

        trigger.addEventListener('click', openHeldModal);
        closeBtn.addEventListener('click', closeHeldModal);
        overlay.addEventListener('click', function (e) { if (e.target === overlay) closeHeldModal(); });
    })();

    document.querySelectorAll('.cart-row').forEach(row => {
        const input = row.querySelector('.cart-qty-input');
        const warning = row.querySelector('.cart-qty-warning');
        const max = parseInt(input?.getAttribute('max') || '999999');

        function flashMax() {
            if (!warning) return;
            warning.textContent = 'Only ' + max + ' in stock';
            warning.classList.add('show');
            clearTimeout(warning._t);
            warning._t = setTimeout(() => warning.classList.remove('show'), 2200);
        }

        row.querySelector('.qty-dec')?.addEventListener('click', () => {
            const v = parseInt(input.value || 1);
            if (v > 1) { input.value = v - 1; refreshAll(); }
        });
        row.querySelector('.qty-inc')?.addEventListener('click', () => {
            const v = parseInt(input.value || 1);
            if (v >= max) { input.value = max; flashMax(); return; }
            input.value = v + 1; refreshAll();
        });
        input?.addEventListener('input', function () {
            const v = parseInt(this.value || 0);
            if (v > max) { this.value = max; flashMax(); }
            refreshAll();
        });
    });

    discountSelect?.addEventListener('change', refreshAll);
    amountPaidInput?.addEventListener('input', refreshAll);
    refreshAll();
});
</script>

<?= $this->endSection() ?>
