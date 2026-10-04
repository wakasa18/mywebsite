(() => {
    'use strict';
    const page = document.querySelector('.receipt-page');
    if (!page) return;
    const widthInputs = page.querySelectorAll('[name="receipt-paper-width"]');
    const applyWidth = value => {
        page.dataset.paperWidth = value === '58' ? '58' : '80';
        widthInputs.forEach(input => { input.checked = input.value === page.dataset.paperWidth; });
        const label = page.querySelector('[data-paper-label]');
        if (label) label.textContent = page.dataset.paperWidth + ' mm';
    };
    try { applyWidth(localStorage.getItem('pharxmaco-receipt-paper-width')); } catch (_) { applyWidth('80'); }
    widthInputs.forEach(input => input.addEventListener('change', () => {
        applyWidth(input.value);
        try { localStorage.setItem('pharxmaco-receipt-paper-width', page.dataset.paperWidth); } catch (_) { /* Printing still works without storage. */ }
    }));
    page.querySelectorAll('[data-print-receipt]').forEach(button => button.addEventListener('click', () => window.print()));
    if (page.dataset.autoPrint === 'true') {
        const printWhenReady = () => {
            Promise.resolve(document.fonts?.ready).then(() => window.setTimeout(() => window.print(), 150));
        };
        if (document.readyState === 'complete') printWhenReady();
        else window.addEventListener('load', printWhenReady, { once: true });
    }
})();
