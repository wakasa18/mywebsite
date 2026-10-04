(() => {
    const root = document.querySelector('.reports-workspace');
    if (!root) return;
    const forms = [...root.querySelectorAll('#salesReportFilters, #salesForecastFilters')];
    const serialize = form => new URLSearchParams(new FormData(form)).toString();
    const original = forms.map(serialize);
    const notice = document.getElementById('reportPendingFilters');
    const forecastNotice = document.getElementById('forecastPendingFilters');
    const updatePending = () => {
        notice.hidden = forms.every((form, index) => serialize(form) === original[index]);
        const index = forms.findIndex(form => form.id === 'salesForecastFilters');
        if (forecastNotice && index !== -1) forecastNotice.hidden = serialize(forms[index]) === original[index];
    };
    forms.forEach(form => {
        form.addEventListener('input', updatePending);
        form.addEventListener('change', updatePending);
    });

    // Keep the forecast section at its previous viewport position after POST/redirect/GET.
    const forecastForm = document.getElementById('salesForecastFilters');
    const scrollKey = 'reports:forecast-scroll';
    const sortedQuery = value => {
        const params = new URLSearchParams(value);
        const filters = ['branch_id', 'report_type', 'report_date_from', 'report_date_to', 'forecast_type', 'forecast_date_from', 'forecast_date_to'];
        [...params.keys()].forEach(key => { if (!filters.includes(key)) params.delete(key); });
        params.sort();
        return params.toString();
    };
    if (forecastForm) {
        window.addEventListener('pageshow', () => {
            const button = forecastForm.querySelector('button[type="submit"]');
            if (button && button.dataset.originalLabel) {
                button.disabled = false;
                button.textContent = button.dataset.originalLabel;
            }
        });
        forecastForm.addEventListener('submit', () => {
            try {
                sessionStorage.setItem(scrollKey, JSON.stringify({
                    path: new URL(forecastForm.dataset.returnUrl || forecastForm.action, location.href).pathname,
                    query: sortedQuery(new FormData(forecastForm)),
                    top: forecastForm.getBoundingClientRect().top,
                    x: window.scrollX,
                    time: Date.now()
                }));
            } catch (_) { /* Submission still works when storage is unavailable. */ }
        });
        try {
            const saved = JSON.parse(sessionStorage.getItem(scrollKey) || 'null');
            sessionStorage.removeItem(scrollKey);
            if (saved && saved.path === location.pathname && saved.query === sortedQuery(location.search)
                && Date.now() - saved.time < 300000 && Number.isFinite(saved.top) && Number.isFinite(saved.x)) {
                const restore = async () => {
                    if (document.fonts) await document.fonts.ready;
                    requestAnimationFrame(() => {
                        window.scrollTo({
                            left: saved.x,
                            top: window.scrollY + forecastForm.getBoundingClientRect().top - saved.top,
                            behavior: 'instant'
                        });
                    });
                };
                if (document.readyState === 'complete') restore();
                else window.addEventListener('load', restore, {once: true});
            }
        } catch (_) { /* Ignore unavailable storage or an outdated saved position. */ }
    }

    const restock = document.getElementById('forecastModalOverlay');
    if (restock) {
        const tabs = [...restock.querySelectorAll('[role="tab"]')];
        const panels = tabs.map(tab => document.getElementById(tab.getAttribute('aria-controls')));
        const search = document.getElementById('restockSearch');
        const priority = document.getElementById('restockPriority');
        const count = document.getElementById('restockResultCount');
        const empty = document.getElementById('restockEmpty');
        const body = restock.querySelector('.forecast-modal-body');
        let selected = 0;
        const filter = () => {
            const term = search.value.trim().toLocaleLowerCase();
            const rows = [...panels[selected].querySelectorAll('[data-restock-row]')];
            let shown = 0;
            rows.forEach(row => {
                row.hidden = !row.textContent.toLocaleLowerCase().includes(term)
                    || (selected === 0 && priority.value !== '' && row.dataset.priority !== priority.value);
                if (!row.hidden) shown++;
            });
            count.textContent = `${shown} of ${rows.length} ${selected === 0 ? 'listed suggestions' : 'saved records'} shown`;
            empty.hidden = rows.length === 0 || shown > 0;
            const table = panels[selected].querySelector('.table-wrap');
            if (table) table.hidden = shown === 0;
            body.scrollTop = 0;
        };
        const originalContext = document.getElementById('restockContext').textContent;
        const select = index => {
            document.getElementById('forecastModalTitle').textContent = index === 0 ? 'Restock Suggestions' : 'Saved Forecasts';
            restock.querySelector('.restock-eyebrow').textContent = index === 0 ? 'Inventory planning' : 'Forecast history';
            document.getElementById('restockContext').textContent = index === 0 ? originalContext : 'Latest records across all branches. Each row shows its sales-date basis and target period.';
            restock.querySelector('.restock-footer p').textContent = index === 0 ? 'Estimates guide ordering. Check stock and supplier lead times.' : 'Saved sales forecasts and separate product demand estimates.';
            selected = index;
            tabs.forEach((tab, position) => {
                tab.setAttribute('aria-selected', String(position === index));
                tab.tabIndex = position === index ? 0 : -1;
                panels[position].hidden = position !== index;
            });
            document.getElementById('restockPriorityLabel').hidden = index !== 0;
            filter();
        };
        tabs.forEach((tab, index) => {
            panels[index].setAttribute('role', 'tabpanel');
            panels[index].setAttribute('aria-labelledby', tab.id);
            panels[index].tabIndex = 0;
            tab.addEventListener('click', () => select(index));
            tab.addEventListener('keydown', event => {
                let next;
                if (event.key === 'ArrowRight') next = (index + 1) % tabs.length;
                else if (event.key === 'ArrowLeft') next = (index + tabs.length - 1) % tabs.length;
                else if (event.key === 'Home') next = 0;
                else if (event.key === 'End') next = tabs.length - 1;
                else return;
                event.preventDefault();
                select(next);
                tabs[next].focus();
            });
        });
        search.addEventListener('input', filter);
        priority.addEventListener('change', filter);
        const reset = () => { search.value = ''; priority.value = ''; filter(); search.focus(); };
        document.getElementById('restockReset').addEventListener('click', reset);
        document.getElementById('restockEmptyReset').addEventListener('click', reset);
        document.getElementById('restockDone').addEventListener('click', () => window.PharxmacoModal.close(restock));
        document.getElementById('restockEditFilters').addEventListener('click', () => {
            window.PharxmacoModal.close(restock);
            forecastForm.scrollIntoView({block: 'start', behavior: 'instant'});
            document.getElementById('forecastGrouping').focus({preventScroll: true});
        });
        select(0);
        const savedTrigger = document.getElementById('savedSalesForecastTrigger');
        savedTrigger?.addEventListener('click', () => {
            search.value = 'Sales revenue';
            select(1);
            window.PharxmacoModal.open(restock, {trigger: savedTrigger, initialFocus: search});
        });
        document.getElementById('restockControls').hidden = false;
    }

    const from = document.getElementById('reportDateFrom');
    const to = document.getElementById('reportDateTo');
    const presets = root.querySelector('.rp-date-presets');
    const buttons = [...presets.querySelectorAll('button')];
    const formatDate = date => `${date.getFullYear()}-${String(date.getMonth()+1).padStart(2,'0')}-${String(date.getDate()).padStart(2,'0')}`;
    const range = type => {
        const end = new Date(presets.dataset.today + 'T12:00:00');
        const start = new Date(end);
        if (type === 'month') start.setDate(1);
        else if (type !== 'today') start.setDate(start.getDate() - Number(type) + 1);
        return [formatDate(start), formatDate(end)];
    };
    const updatePresets = () => buttons.forEach(button => {
        const [start, end] = range(button.dataset.reportRange);
        button.setAttribute('aria-pressed', String(from.value === start && to.value === end));
    });
    buttons.forEach(button => button.addEventListener('click', () => {
        [from.value, to.value] = range(button.dataset.reportRange);
        [from, to].forEach(input => input.dispatchEvent(new Event('change', {bubbles:true})));
        updatePresets();
    }));
    [from, to].forEach(input => input.addEventListener('change', updatePresets));
    updatePresets();
})();
