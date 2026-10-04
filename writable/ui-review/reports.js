
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
    

(function () {
    if (typeof Chart === 'undefined') {
        document.querySelectorAll('.chart-wrap').forEach(function (wrap) {
            wrap.innerHTML = '<p class="muted" style="text-align:center; padding:80px 12px;">Charts could not be loaded. The report tables and exports are still available.</p>';
        });
        return;
    }

    const reportLabels = [];
    const reportNetSales = [];
    const reportCogs = [];
    const reportGrossProfit = [];

    const forecastLabels = [];
    const forecastActualSales = [];
    const fittedForecast = [];

    const futureLabels = [];
    const futureValues = [];

    const topLabels = [];
    const topQty = [];

    // ── Theming helpers (matches dashboard) ──
    const isDark = () => document.documentElement.classList.contains('dark');
    const gridColor = () => isDark() ? 'rgba(255,255,255,0.07)' : 'rgba(0,0,0,0.06)';
    const tickColor = () => isDark() ? '#6b7e96' : '#6b82a0';
    const labelColor = () => isDark() ? '#b0bdce' : '#2c3e5a';

    const BLUE = '#3b82f6';
    const AMBER = '#f59e0b';
    const GREEN = '#22c55e';
    const PURPLE = '#8b5cf6';
    const PALETTE = ['#3b82f6', '#22c55e', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#f97316', '#84cc16', '#ec4899', '#14b8a6'];

    const pesoTick = v => '₱' + (v >= 1000 ? (v / 1000).toFixed(1) + 'k' : v);

    const baseOptions = (extra = {}) => ({
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: { labels: { color: labelColor(), font: { size: 12 }, boxWidth: 12, padding: 14 } },
            tooltip: {
                backgroundColor: isDark() ? '#1c2639' : '#fff',
                borderColor: isDark() ? '#2e3f58' : '#dce7f5',
                borderWidth: 1,
                titleColor: labelColor(),
                bodyColor: tickColor(),
                callbacks: {
                    label: ctx => ' ' + ctx.dataset.label + ': ₱' + ctx.parsed.y.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                }
            }
        },
        ...extra
    });

    const baseScales = () => ({
        x: { grid: { color: gridColor() }, ticks: { color: tickColor(), font: { size: 11 }, maxTicksLimit: 10 } },
        y: { grid: { color: gridColor() }, ticks: { color: tickColor(), font: { size: 11 }, callback: pesoTick } }
    });

    // ── Sales and profit chart ──
    const compositionChart = new Chart(document.getElementById('compositionChart'), {
        type: 'line',
        data: {
            labels: reportLabels,
            datasets: [
                { label: 'Net Sales', data: reportNetSales, borderColor: BLUE, backgroundColor: 'rgba(59,130,246,0.08)', fill: true, tension: 0.35, pointRadius: 2, pointHoverRadius: 5 },
                { label: 'Product Cost', data: reportCogs, borderColor: AMBER, backgroundColor: 'rgba(245,158,11,0.08)', fill: false, tension: 0.35, pointRadius: 2, pointHoverRadius: 5 },
                { label: 'Gross Profit', data: reportGrossProfit, borderColor: GREEN, backgroundColor: 'rgba(34,197,94,0.08)', fill: false, tension: 0.35, pointRadius: 2, pointHoverRadius: 5 }
            ]
        },
        options: { ...baseOptions(), scales: baseScales() }
    });

    // ── Top Products (horizontal bar) ──
    const topProductsChart = new Chart(document.getElementById('topProductsChart'), {
        type: 'bar',
        data: {
            labels: topLabels,
            datasets: [{ label: 'Qty Sold', data: topQty, backgroundColor: PALETTE.map(c => c + 'cc'), borderColor: PALETTE, borderWidth: 1.5, borderRadius: 5 }]
        },
        options: {
            ...baseOptions(),
            indexAxis: 'y',
            plugins: { ...baseOptions().plugins, tooltip: { ...baseOptions().plugins.tooltip, callbacks: { label: ctx => ' ' + ctx.parsed.x + ' units' } } },
            scales: {
                x: { grid: { color: gridColor() }, ticks: { color: tickColor(), font: { size: 11 } } },
                y: { grid: { color: gridColor() }, ticks: { color: tickColor(), font: { size: 11 } } }
            }
        }
    });

    // ── Sales forecast ──
    const forecastChart = new Chart(document.getElementById('forecastChart'), {
        type: 'line',
        data: {
            labels: forecastLabels.concat(futureLabels),
            datasets: [
                { label: 'Actual Sales', data: forecastActualSales.concat(Array(futureLabels.length).fill(null)), borderColor: BLUE, backgroundColor: 'rgba(59,130,246,0.08)', fill: true, tension: 0.3, pointRadius: 2, pointHoverRadius: 5 },
                { label: 'Estimated Sales', data: fittedForecast.concat(futureValues), borderColor: PURPLE, borderDash: [6, 6], fill: false, tension: 0.3, pointRadius: 2, pointHoverRadius: 5 }
            ]
        },
        options: { ...baseOptions(), scales: baseScales() }
    });

    // ── Re-theme on dark mode toggle ──
    const observer = new MutationObserver(() => {
        [compositionChart, topProductsChart, forecastChart].forEach(c => {
            Object.values(c.options.scales).forEach(ax => {
                if (ax.grid) ax.grid.color = gridColor();
                if (ax.ticks) ax.ticks.color = tickColor();
            });
            c.options.plugins.legend.labels.color = labelColor();
            c.update('none');
        });
    });
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
})();

(function () {
    var trigger = document.getElementById('forecastModalTrigger');
    var overlay = document.getElementById('forecastModalOverlay');
    var closeBtn = document.getElementById('forecastModalClose');
    if (!trigger || !overlay || !closeBtn) return;

    var lastFocused = null;
    function open() {
        lastFocused = document.activeElement;
        overlay.classList.add('show');
        overlay.setAttribute('aria-hidden', 'false');
        trigger.setAttribute('aria-expanded', 'true');
        document.body.style.overflow = 'hidden';
        closeBtn.focus();
    }
    function close() {
        overlay.classList.remove('show');
        overlay.setAttribute('aria-hidden', 'true');
        trigger.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
        if (lastFocused) lastFocused.focus();
    }

    trigger.addEventListener('click', open);
    closeBtn.addEventListener('click', close);
    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) close();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && overlay.classList.contains('show')) close();
    });
})();

(function () {
    function connectRange(fromId, toId, maxMonths) {
        var from = document.getElementById(fromId);
        var to = document.getElementById(toId);
        if (!from || !to) return;

        function dateMinusMonths(value, months) {
            var parts = value.split('-').map(Number);
            if (parts.length !== 3) return '';
            var d = new Date(parts[0], parts[1] - 1, parts[2]);
            d.setMonth(d.getMonth() - months);
            var y = d.getFullYear();
            var m = String(d.getMonth() + 1).padStart(2, '0');
            var day = String(d.getDate()).padStart(2, '0');
            return y + '-' + m + '-' + day;
        }

        function sync() {
            from.max = to.value || from.getAttribute('max') || '';
            to.min = from.value || '';
            if (maxMonths && to.value) {
                from.min = dateMinusMonths(to.value, maxMonths);
            }
        }

        from.addEventListener('change', sync);
        to.addEventListener('change', sync);
        sync();
    }

    connectRange('reportDateFrom', 'reportDateTo', null);
    connectRange('forecastDateFrom', 'forecastDateTo', 12);
})();


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
