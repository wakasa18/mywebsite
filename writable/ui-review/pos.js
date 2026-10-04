
    (function(){
        var defaults={theme:'system',accent:'blue',textSize:'normal',density:'comfortable',largeControls:false,reduceMotion:false,highContrast:false,stickyHeaders:false};
        var settings={};
        try{settings=Object.assign({},defaults,JSON.parse(localStorage.getItem('pharxmaco-ui-settings')||'{}'));}catch(e){settings=defaults;}
        if(!settings.theme){
            var legacy=localStorage.getItem('pharxmaco-theme');
            settings.theme=(legacy==='dark'||legacy==='light')?legacy:'system';
        }
        var dark=settings.theme==='dark'||(settings.theme==='system'&&window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches);
        var html=document.documentElement;
        html.classList.toggle('dark',dark);
        html.dataset.themePreference=settings.theme;
        html.dataset.accent=settings.accent||'blue';
        html.dataset.textSize=settings.textSize||'normal';
        html.dataset.density=settings.density||'comfortable';
        html.classList.toggle('large-controls',Boolean(settings.largeControls));
        html.classList.toggle('reduce-motion',Boolean(settings.reduceMotion));
        html.classList.toggle('high-contrast',Boolean(settings.highContrast));
        html.classList.toggle('sticky-table-headers',Boolean(settings.stickyHeaders));
    })();
    

window._siteUrl = "http://localhost/pharxmacoo/public/";
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
        quickAddQty.focus(); quickAddQty.select();
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

    quickAddCancel?.addEventListener('click', deselect);
    quickAddQty.addEventListener('input', function () {
        const max = parseInt(this.max || '1', 10);
        let qty = parseInt(this.value || '1', 10);
        if (!Number.isFinite(qty) || qty < 1) qty = 1;
        if (qty > max) qty = max;
        this.value = qty;
        quickAddQtyH.value = qty;
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
            const show = inCat && inSearch;
            card.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        document.getElementById('prodCount').textContent = visible;
        const mobileProductCount = document.getElementById('mobileProductCount');
        if (mobileProductCount) mobileProductCount.textContent = visible;
        const emptyState = document.getElementById('productFilterEmpty');
        if (emptyState) emptyState.hidden = visible !== 0 || prodCards.length === 0;
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

    function getVisible() { return Array.from(prodCards).filter(c => c.style.display !== 'none'); }

    function highlightKb(idx) {
        getVisible().forEach(c => c.classList.remove('selected'));
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
        if (document.getElementById('heldModalOverlay')?.classList.contains('show') || document.getElementById('sidebar')?.classList.contains('open')) return;
        const tag = document.activeElement?.tagName?.toLowerCase();
        const isTyping = ['input', 'textarea', 'select'].includes(tag);
        if (event.key === '/' && !isTyping) {
            event.preventDefault();
            setActivePosPanel('products');
            searchInput.focus();
        }
        if (event.key === 'Escape' && selectedCard) deselect();
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

        const eligibleSubtotal = lines.reduce((sum, line) => {
            let eligible = scope === 'all';

            if (scope === 'category') {
                eligible = categoryId > 0 && line.categoryId === categoryId;
            } else if (scope === 'product') {
                eligible = productId > 0 && line.productId === productId;
            }

            return eligible ? sum + line.subtotal : sum;
        }, 0);

        if (eligibleSubtotal <= 0) {
            if (discountWarning) {
                discountWarning.textContent = 'This discount does not apply to any item in the cart.';
                discountWarning.classList.add('show');
            }
            return 0;
        }

        if (eligibleSubtotal < min) {
            if (discountWarning) {
                discountWarning.textContent = 'Add ' + fmt(min - eligibleSubtotal)
                    + ' more in matching items to qualify (min. ' + fmt(min) + ').';
                discountWarning.classList.add('show');
            }
            return 0;
        }

        let disc = type === 'percentage' ? eligibleSubtotal * (val / 100) : val;
        if (max > 0 && disc > max) disc = max;

        // Never let a scoped discount reduce non-matching cart items.
        return Math.min(disc, eligibleSubtotal, subtotal);
    }

    function refreshAll() {
        let total = 0, count = 0;
        const lines = [];

        document.querySelectorAll('.cart-row').forEach(row => {
            const price = parseFloat(row.getAttribute('data-price') || 0);
            const qty = parseInt(row.querySelector('.cart-qty-input')?.value || 0) || 0;
            const sub = price * qty;
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
        const finalTot = Math.max(0, total - disc);
        const paid     = parseFloat(amountPaidInput?.value || 0);
        const change   = Math.max(0, paid - finalTot);

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
        if (completeSaleBtn && !checkoutBusy) completeSaleBtn.disabled = count === 0 || !navigator.onLine;
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
            completeSaleBtn.disabled = currentCartLineCount === 0 || !navigator.onLine;
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
            if (document.getElementById('heldModalOverlay')?.classList.contains('show') || document.getElementById('sidebar')?.classList.contains('open')) return;
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

        let previousOverflow = '';
        function openHeldModal() {
            previousOverflow = document.body.style.overflow;
            overlay.classList.add('show');
            document.body.style.overflow = 'hidden';
            closeBtn.focus();
        }
        function closeHeldModal() {
            overlay.classList.remove('show');
            document.body.style.overflow = previousOverflow;
            trigger.focus();
        }

        trigger.addEventListener('click', openHeldModal);
        closeBtn.addEventListener('click', closeHeldModal);
        overlay.addEventListener('click', function (e) { if (e.target === overlay) closeHeldModal(); });
        document.addEventListener('keydown', function (e) {
            if (!overlay.classList.contains('show')) return;
            if (e.key === 'Escape') closeHeldModal();
            if (e.key === 'Tab') {
                const controls = Array.from(overlay.querySelectorAll('button:not([disabled]), a[href], input:not([type="hidden"]):not([disabled])'));
                const first = controls[0];
                const last = controls[controls.length - 1];
                if (e.shiftKey && document.activeElement === first) {
                    e.preventDefault(); last.focus();
                } else if (!e.shiftKey && document.activeElement === last) {
                    e.preventDefault(); first.focus();
                }
            }
        });
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


(function(){

    /* ── Connection awareness ── */
    const globalConnectionBanner = document.getElementById('globalConnectionBanner');
    const globalConnectionText = document.getElementById('globalConnectionText');
    let connectionRestoreTimer = null;

    function updateGlobalConnectionState(showRestored = false) {
        if (!globalConnectionBanner || !globalConnectionText) return;
        window.clearTimeout(connectionRestoreTimer);

        if (!navigator.onLine) {
            globalConnectionBanner.classList.remove('restored');
            globalConnectionBanner.classList.add('show');
            globalConnectionText.textContent = 'No internet connection. Unsaved actions have not been sent to the server.';
            return;
        }

        if (showRestored) {
            globalConnectionBanner.classList.add('restored', 'show');
            globalConnectionText.textContent = 'Connection restored. You may continue.';
            connectionRestoreTimer = window.setTimeout(function () {
                globalConnectionBanner.classList.remove('show', 'restored');
            }, 2800);
        } else {
            globalConnectionBanner.classList.remove('show', 'restored');
        }
    }

    window.addEventListener('offline', function () { updateGlobalConnectionState(false); });
    window.addEventListener('online', function () { updateGlobalConnectionState(true); });
    updateGlobalConnectionState(false);

    /* ── Live clock ── */
    function updateClock(){
        const now = new Date();
        const d = now.toLocaleDateString('en-PH', { month:'short', day:'numeric', year:'numeric' });
        const t = now.toLocaleTimeString('en-PH', { hour:'2-digit', minute:'2-digit' });
        const el = document.getElementById('clockDisplay');
        if (el) el.textContent = d + '  ' + t;
    }
    updateClock();
    setInterval(updateClock, 30000);

    /* ── Topbar title sync from active nav ── */
    (function(){
        const active = document.querySelector('.nav-item.active .nav-text');
        const titleEl = document.getElementById('topbarTitle');
        if (active && titleEl) titleEl.textContent = active.textContent.trim();
        if (active) active.closest('a').setAttribute('aria-current', 'page');
        const heading = document.querySelector('#pageContent h1');
        if (heading) document.title = heading.textContent.trim() + ' | ' + document.title;

        const activeIcon = document.querySelector('.nav-item.active .nav-icon');
        const iconBox = document.getElementById('topbarIcon');
        if (activeIcon && iconBox) iconBox.innerHTML = activeIcon.innerHTML;
    })();

    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    const toggle  = document.getElementById('sidebarToggle');
    const sidebarClose = document.getElementById('sidebarClose');
    const mainContent = document.getElementById('mainContent');
    let sidebarPreviousOverflow = '';

    // Hidden by default at every screen width (see .sidebar's base CSS) —
    // the toggle button is the only way in, no hover-to-peek, no
    // breakpoint-dependent branching. That's what makes this immune to
    // the resize/breakpoint bugs the old mini+hover+mobile system had.
    function openSidebar() {
        sidebarPreviousOverflow = document.body.style.overflow;
        sidebar.inert = false;
        sidebar.classList.add('open');
        overlay.classList.add('show');
        toggle.setAttribute('aria-expanded', 'true');
        sidebarClose.focus();
        mainContent.inert = true;
        document.body.style.overflow = 'hidden';
    }
    function closeSidebar() {
        sidebar.classList.remove('open');
        overlay.classList.remove('show');
        mainContent.inert = false;
        toggle.setAttribute('aria-expanded', 'false');
        toggle.focus();
        sidebar.inert = true;
        document.body.style.overflow = sidebarPreviousOverflow;
    }

    sidebarClose.addEventListener('click', closeSidebar);
    sidebar.addEventListener('keydown', function (event) {
        if (event.key !== 'Tab') return;
        const links = Array.from(sidebar.querySelectorAll('a[href], button:not([disabled])'));
        const first = links[0];
        const last = links[links.length - 1];
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault(); last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault(); first.focus();
        }
    });

    toggle.addEventListener('click', function () {
        if (sidebar.classList.contains('open')) {
            closeSidebar();
        } else {
            openSidebar();
        }
    });

    overlay.addEventListener('click', closeSidebar);

    /* ── Low-stock product popup ── */
    const lowStockModal = document.getElementById('lowStockModal');
    const lowStockSearch = document.getElementById('lowStockSearch');
    const lowStockRows = Array.from(document.querySelectorAll('[data-low-stock-row]'));
    const lowStockVisibleCount = document.getElementById('lowStockVisibleCount');
    const lowStockEmptySearch = document.getElementById('lowStockEmptySearch');
    let lowStockLastFocus = null;

    function filterLowStockRows(){
        if (!lowStockSearch) return;
        const query = lowStockSearch.value.trim().toLowerCase();
        let visible = 0;

        lowStockRows.forEach(function(row){
            const matches = query === '' || (row.dataset.search || '').includes(query);
            row.hidden = !matches;
            if (matches) visible += 1;
        });

        if (lowStockVisibleCount) lowStockVisibleCount.textContent = String(visible);
        if (lowStockEmptySearch) lowStockEmptySearch.style.display = visible === 0 ? 'block' : 'none';
    }

    function openLowStockModal(trigger){
        if (!lowStockModal) return;
        lowStockLastFocus = trigger || document.activeElement;
        closeNotifications();
        lowStockModal.classList.add('open');
        lowStockModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('inventory-modal-open');
        if (lowStockSearch) {
            lowStockSearch.value = '';
            filterLowStockRows();
            window.setTimeout(function(){ lowStockSearch.focus(); }, 0);
        } else {
            const closeButton = lowStockModal.querySelector('[data-close-low-stock]');
            if (closeButton) window.setTimeout(function(){ closeButton.focus(); }, 0);
        }
    }

    function closeLowStockModal(){
        if (!lowStockModal || !lowStockModal.classList.contains('open')) return;
        lowStockModal.classList.remove('open');
        lowStockModal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('inventory-modal-open');
        if (lowStockLastFocus && typeof lowStockLastFocus.focus === 'function') {
            lowStockLastFocus.focus();
        }
    }

    document.querySelectorAll('[data-open-low-stock]').forEach(function(button){
        button.addEventListener('click', function(){ openLowStockModal(button); });
    });
    document.querySelectorAll('[data-close-low-stock]').forEach(function(button){
        button.addEventListener('click', closeLowStockModal);
    });
    if (lowStockSearch) lowStockSearch.addEventListener('input', filterLowStockRows);
    if (lowStockModal) {
        lowStockModal.addEventListener('click', function(event){
            if (event.target === lowStockModal) closeLowStockModal();
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            if (lowStockModal && lowStockModal.classList.contains('open')) {
                closeLowStockModal();
                return;
            }
            if (sidebar.classList.contains('open')) closeSidebar();
            closeNotifications();
        }
    });

    /* ── Notification dropdown ── */
    const notificationWrap = document.getElementById('notificationWrap');
    const notificationButton = document.getElementById('notificationButton');
    const notificationMenu = document.getElementById('notificationMenu');

    function closeNotifications(){
        if (!notificationMenu || !notificationButton) return;
        notificationMenu.classList.remove('open');
        notificationButton.setAttribute('aria-expanded', 'false');
    }

    if (notificationButton && notificationMenu) {
        notificationButton.addEventListener('click', function(event){
            event.stopPropagation();
            const willOpen = !notificationMenu.classList.contains('open');
            closeNotifications();
            if (willOpen) {
                notificationMenu.classList.add('open');
                notificationButton.setAttribute('aria-expanded', 'true');
            }
        });

        notificationMenu.addEventListener('click', function(event){ event.stopPropagation(); });
        document.addEventListener('click', function(event){
            if (notificationWrap && !notificationWrap.contains(event.target)) closeNotifications();
        });
    }

    /* ── Theme toggle and browser-only UI preferences ── */
    const themeToggle = document.getElementById('themeToggle');
    const html = document.documentElement;
    const uiSettingsKey = 'pharxmaco-ui-settings';

    function readUiSettings(){
        try {
            return Object.assign({
                theme: 'system', accent: 'blue', textSize: 'normal', density: 'comfortable',
                largeControls: false, reduceMotion: false, highContrast: false, stickyHeaders: false
            }, JSON.parse(localStorage.getItem(uiSettingsKey) || '{}'));
        } catch (error) {
            return {theme:'system', accent:'blue', textSize:'normal', density:'comfortable', largeControls:false, reduceMotion:false, highContrast:false, stickyHeaders:false};
        }
    }

    function applyUiThemePreference(settings){
        const systemDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        const isDark = settings.theme === 'dark' || (settings.theme === 'system' && systemDark);
        html.classList.toggle('dark', isDark);
        html.dataset.themePreference = settings.theme;
        html.dataset.accent = settings.accent || 'blue';
        html.dataset.textSize = settings.textSize || 'normal';
        html.dataset.density = settings.density || 'comfortable';
        html.classList.toggle('large-controls', Boolean(settings.largeControls));
        html.classList.toggle('reduce-motion', Boolean(settings.reduceMotion));
        html.classList.toggle('high-contrast', Boolean(settings.highContrast));
        html.classList.toggle('sticky-table-headers', Boolean(settings.stickyHeaders));
        try { localStorage.setItem('pharxmaco-theme', isDark ? 'dark' : 'light'); } catch (error) {}
        window.dispatchEvent(new CustomEvent('pharxmaco:ui-settings-applied', {detail: settings}));
    }

    if (themeToggle) {
        themeToggle.addEventListener('click', function(){
            const settings = readUiSettings();
            settings.theme = html.classList.contains('dark') ? 'light' : 'dark';
            localStorage.setItem(uiSettingsKey, JSON.stringify(settings));
            applyUiThemePreference(settings);
        });
    }

    if (window.matchMedia) {
        const colorScheme = window.matchMedia('(prefers-color-scheme: dark)');
        const onColorSchemeChange = function(){
            const settings = readUiSettings();
            if (settings.theme === 'system') applyUiThemePreference(settings);
        };
        if (typeof colorScheme.addEventListener === 'function') colorScheme.addEventListener('change', onColorSchemeChange);
        else if (typeof colorScheme.addListener === 'function') colorScheme.addListener(onColorSchemeChange);
    }

    window.addEventListener('storage', function(event){
        if (event.key === uiSettingsKey || event.key === 'pharxmaco-theme') {
            applyUiThemePreference(readUiSettings());
        }
    });


    /* ── Automatic search and filters ──
       Every GET filter form refreshes automatically. Text searches wait
       briefly after typing; dropdowns, dates, radio buttons, and checkboxes
       refresh almost immediately. Existing submit buttons stay available as
       a keyboard/no-JavaScript fallback. Add data-auto-filter="off" to opt out. */
    (function setupAutomaticFilters(){
        const forms = Array.from(document.querySelectorAll('form')).filter(function(form){
            return form.method.toLowerCase() === 'get' && form.dataset.autoFilter !== 'off';
        });
        if (!forms.length) return;

        const timers = new WeakMap();
        const composing = new WeakSet();

        function getStatus(form){
            let status = form.querySelector(':scope > .auto-filter-status');
            if (!status) {
                status = document.createElement('span');
                status.className = 'auto-filter-status';
                status.setAttribute('role', 'status');
                status.setAttribute('aria-live', 'polite');
                status.setAttribute('aria-atomic', 'true');
                form.appendChild(status);
            }
            return status;
        }

        function setStatus(form, message, type){
            const status = getStatus(form);
            status.textContent = message || '';
            status.className = 'auto-filter-status' + (message ? ' show' : '') + (type ? ' ' + type : '');
        }

        function clearInvalid(form){
            form.querySelectorAll('.auto-filter-invalid').forEach(function(control){
                control.classList.remove('auto-filter-invalid');
            });
        }

        function validateDateRanges(form){
            clearInvalid(form);
            const controls = Array.from(form.elements).filter(function(control){
                return control && control.name && control.name.endsWith('_from');
            });

            for (const from of controls) {
                const toName = from.name.slice(0, -5) + '_to';
                const to = form.elements.namedItem(toName);
                if (!to || !from.value || !to.value) continue;
                if (from.value > to.value) {
                    from.classList.add('auto-filter-invalid');
                    to.classList.add('auto-filter-invalid');
                    setStatus(form, 'Start date must be on or before the end date.', 'invalid');
                    return false;
                }
            }
            return true;
        }

        function submitFilter(form){
            timers.delete(form);
            if (form.classList.contains('auto-filter-submitting')) return;

            if (!validateDateRanges(form)) return;
            if (!form.checkValidity()) {
                setStatus(form, 'Complete or correct the highlighted filter before results can update.', 'invalid');
                return;
            }
            if (!navigator.onLine) {
                setStatus(form, 'You are offline. Reconnect, then change a filter or press the filter button.', 'offline');
                return;
            }

            form.classList.add('auto-filter-submitting');
            setStatus(form, 'Updating results…', '');
            if (typeof form.requestSubmit === 'function') form.requestSubmit();
            else form.submit();
        }

        function schedule(form, delay){
            const existing = timers.get(form);
            if (existing) window.clearTimeout(existing);
            clearInvalid(form);
            setStatus(form, 'Waiting for filter changes…', '');
            const timer = window.setTimeout(function(){ submitFilter(form); }, delay);
            timers.set(form, timer);
        }

        forms.forEach(function(form){
            const controls = Array.from(form.elements).filter(function(control){
                if (!control || !control.name || control.disabled) return false;
                if (control.dataset.autoFilterIgnore === 'true') return false;
                return !['hidden', 'submit', 'button', 'reset', 'file', 'image'].includes((control.type || '').toLowerCase());
            });
            if (!controls.length) return;

            form.classList.add('auto-filter-enabled');

            controls.forEach(function(control){
                const type = (control.type || '').toLowerCase();
                const isTypingControl = control.tagName === 'TEXTAREA' || ['text', 'search', 'email', 'tel', 'url'].includes(type);

                control.addEventListener('compositionstart', function(){ composing.add(control); });
                control.addEventListener('compositionend', function(){
                    composing.delete(control);
                    schedule(form, 550);
                });

                if (isTypingControl) {
                    control.addEventListener('input', function(event){
                        if (event.isComposing || composing.has(control)) return;
                        schedule(form, 550);
                    });
                    control.addEventListener('keydown', function(event){
                        if (event.key === 'Enter' && !event.isComposing) {
                            const pending = timers.get(form);
                            if (pending) window.clearTimeout(pending);
                        }
                    });
                } else {
                    control.addEventListener('change', function(){ schedule(form, 280); });
                }
            });

            form.addEventListener('submit', function(){
                const pending = timers.get(form);
                if (pending) window.clearTimeout(pending);
                timers.delete(form);
                form.classList.add('auto-filter-submitting');
                setStatus(form, 'Updating results…', '');
            });
        });
    })();

    /* Prevent accidental duplicate saves on CRUD forms. */
    document.querySelectorAll('form[method="post"]').forEach(function(form){
        form.addEventListener('submit', function(event){
            if (!form.checkValidity()) return;
            const button = form.querySelector('button[type="submit"]');
            if (!button || button.dataset.noSubmitLock === 'true') return;
            window.setTimeout(function(){
                if (event.defaultPrevented) return;
                button.disabled = true;
                if (!button.dataset.originalLabel) button.dataset.originalLabel = button.textContent.trim();
                button.textContent = button.dataset.busyLabel || 'Saving…';
            }, 0);
        });
    });

})();
