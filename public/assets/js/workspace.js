/* Keep native tables on desktop; label each field for the narrow-screen cards. */
document.querySelectorAll('.mobile-record-table').forEach(table => {
    const headings = Array.from(table.querySelectorAll('thead th'));
    const numericHeading = /^(?:qty|quantity|stock|reorder(?: level)?|price|cost|cost price|subtotal|total|final total|amount|gross|profit|receipts|refund payouts|net movement|count|products|prev|new)$/i;
    const titleColumn = Number(table.dataset.recordTitle || 0);
    table.setAttribute('role', 'table');
    table.querySelectorAll('thead, tbody').forEach(group => group.setAttribute('role', 'rowgroup'));
    table.querySelectorAll('tr').forEach(row => row.setAttribute('role', 'row'));
    headings.forEach(heading => {
        heading.setAttribute('scope', 'col');
        heading.setAttribute('role', 'columnheader');
        if (numericHeading.test(heading.textContent.trim())) heading.classList.add('table-number');
    });
    table.querySelectorAll('tbody tr').forEach(row => {
        Array.from(row.cells).forEach((cell, index) => {
            cell.setAttribute('role', 'cell');
            if (cell.colSpan > 1) {
                cell.classList.add('record-empty');
                return;
            }
            cell.dataset.label = headings[index]?.textContent.trim() || '';
            if (numericHeading.test(cell.dataset.label)) cell.classList.add('table-number');
            if (/^(?:address|email|remarks|activity|products in this category)$/i.test(cell.dataset.label)) cell.classList.add('record-wide');
            if (index === titleColumn) cell.classList.add('record-title');
            if (/^actions?$/i.test(cell.dataset.label)) cell.classList.add('record-actions');
        });
    });
    table.classList.add('mobile-record-ready');
});

/* Visual enhancement only: native links/forms, confirmations and CSRF remain intact. */
(() => {
    const actions = {
        'edit': 'edit', 'correct': 'edit', 'view': 'view', 'view details': 'view',
        'view inventory': 'inventory', 'view sales': 'sales',
        'reprint': 'print', 'print': 'print', 'password': 'password',
        'restore': 'restore', 'activate': 'activate', 'deactivate': 'pause', 'turn off': 'pause',
        'trash': 'trash', 'move to trash': 'trash', 'delete forever': 'delete', 'delete': 'delete',
        'refund': 'refund', 'save': 'save',
    };
    const paths = {
        edit: 'M16 3l5 5M4 20l4-1L21 6a2.8 2.8 0 0 0-4-4L4 15v5Z',
        view: 'M2 12s3-7 10-7 10 7 10 7-3 7-10 7S2 12 2 12Zm10-3a3 3 0 1 0 0 6 3 3 0 0 0 0-6Z',
        inventory: 'm3 7 9-4 9 4v10l-9 4-9-4V7Zm0 0 9 4 9-4M12 11v10M7.5 5l9 4v4',
        sales: 'M4 3v18h17M8 16v-5M13 16V7M18 16v-8',
        print: 'M6 9V3h12v6M6 17H3V9h18v8h-3M6 14h12v7H6v-7M17 12h1',
        password: 'M8 11V7a4 4 0 0 1 8 0v4M5 11h14v10H5V11Zm7 4v2',
        restore: 'M3 4v6h6M3 10a9 9 0 1 1 1 8',
        activate: 'm5 12 4 4L19 6',
        pause: 'M8 5v14M16 5v14',
        trash: 'M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7M14 10v7',
        delete: 'M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15m-9 4 4 7m0-7-4 7',
        refund: 'M3 5v6h6M3 11a9 9 0 1 1 2 8M14 8h-3a2 2 0 0 0 0 4h2a2 2 0 0 1 0 4h-3m2-10v2m0 8v2',
        save: 'M5 3h12l4 4v14H3V3h2Zm2 0v6h10V3M7 21v-7h10v7',
    };
    document.querySelectorAll('.content table').forEach(table => {
        const headers = [...table.querySelectorAll('thead th')];
        const actionColumn = headers.findIndex(header => /^actions?$/i.test(header.textContent.trim()));
        table.querySelectorAll('tbody tr').forEach(row => {
            const cell = actionColumn >= 0 ? row.cells[actionColumn] : null;
            if (cell && cell.colSpan === 1) {
                cell.classList.add('record-actions');
                const group = cell.firstElementChild;
                if (group?.tagName === 'DIV') {
                    group.classList.add('table-action-group');
                    group.setAttribute('role', 'group');
                    const title = row.querySelector('.record-title')?.textContent.trim();
                    group.setAttribute('aria-label', title ? `Actions for ${title.slice(0, 120)}` : 'Row actions');
                }
            }
        });
        table.querySelectorAll('td .btn').forEach(button => {
            const label = button.textContent.trim().replace(/^[^\p{L}\p{N}]+/u, '').replace(/\s+/g, ' ').toLowerCase();
            const action = actions[label];
            if (!action || button.querySelector('svg, img')) return;
            button.dataset.tableAction = action;
            const icon = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            icon.classList.add('table-action-icon');
            for (const [name, value] of Object.entries({viewBox:'0 0 24 24',fill:'none',stroke:'currentColor','stroke-width':'1.7','stroke-linecap':'round','stroke-linejoin':'round','aria-hidden':'true',focusable:'false'})) icon.setAttribute(name, value);
            const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            path.setAttribute('d', paths[action]);
            icon.append(path);
            button.prepend(icon);
        });
    });
})();

/* A visible scroll hint for other wide tables, including reports. */
document.querySelectorAll('.table-wrap').forEach(wrapper => {
    const table = wrapper.querySelector('table');
    if (!table) return;
    const hint = document.createElement('p');
    hint.className = 'table-scroll-hint';
    hint.textContent = 'Swipe or scroll sideways to see all columns.';
    hint.hidden = true;
    wrapper.before(hint);
    const update = () => {
        const overflowing = wrapper.scrollWidth > wrapper.clientWidth + 2;
        hint.hidden = !overflowing;
        if (overflowing) {
            wrapper.setAttribute('tabindex', '0');
            wrapper.setAttribute('role', 'region');
            wrapper.setAttribute('aria-label', table.getAttribute('aria-label') || 'Scrollable table');
        } else {
            wrapper.removeAttribute('tabindex');
            wrapper.removeAttribute('role');
            wrapper.removeAttribute('aria-label');
        }
    };
    update();
    if ('ResizeObserver' in window) {
        const observer = new ResizeObserver(update);
        observer.observe(wrapper);
        observer.observe(table);
    } else {
        window.addEventListener('resize', update);
    }
});
