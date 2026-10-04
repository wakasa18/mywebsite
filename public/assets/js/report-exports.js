/* One identifier per deliberate export; browser retries keep the same URL. */
(() => {
    'use strict';
    document.querySelectorAll('[data-report-export]').forEach(link => {
        let busy = false;
        const activate = event => {
            if (event.type === 'auxclick' && event.button !== 1) return;
            if (event.defaultPrevented) return;
            if (busy) {
                event.preventDefault();
                return;
            }
            const url = new URL(link.href, window.location.href);
            if (window.crypto && window.crypto.getRandomValues) {
                const bytes = window.crypto.getRandomValues(new Uint8Array(16));
                url.searchParams.set('export_request', Array.from(bytes, byte => byte.toString(16).padStart(2, '0')).join(''));
            } else {
                url.searchParams.delete('export_request');
            }
            link.href = url.toString();
            busy = true;
            link.setAttribute('aria-disabled', 'true');
            window.setTimeout(() => {
                busy = false;
                link.removeAttribute('aria-disabled');
            }, 2000);
        };
        link.addEventListener('click', activate);
        link.addEventListener('auxclick', activate);
    });
})();
