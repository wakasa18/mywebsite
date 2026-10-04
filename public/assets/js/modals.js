/* Shared focus, background isolation and scroll restoration for workspace dialogs. */
(() => {
    let active = null;
    const controls = overlay => Array.from(overlay.querySelectorAll('a[href],button:not([disabled]),input:not([disabled]):not([type="hidden"]),select:not([disabled]),textarea:not([disabled]),[tabindex="0"]'))
        .filter(element => element.getClientRects().length && !element.closest('[inert]'));
    function close(overlay) {
        if (!active || active.overlay !== overlay) return;
        const state = active;
        active = null;
        overlay.classList.remove(state.className);
        overlay.setAttribute('aria-hidden', 'true');
        state.trigger?.setAttribute('aria-expanded', 'false');
        state.inert.forEach(([element, previous]) => { element.inert = previous; });
        document.body.style.overflow = state.overflow;
        if (state.returnFocus?.isConnected && state.returnFocus.getClientRects().length && !state.returnFocus.closest('[inert]')) {
            state.returnFocus.focus({preventScroll: true});
        }
    }
    function open(overlay, options = {}) {
        if (!overlay || active?.overlay === overlay) return;
        if (active) close(active.overlay);
        const state = {
            overlay, className: options.className || 'show',
            trigger: options.trigger || document.activeElement,
            returnFocus: options.returnFocus || options.trigger || document.activeElement,
            overflow: document.body.style.overflow, inert: [],
        };
        active = state;
        overlay.classList.add(state.className);
        overlay.setAttribute('aria-hidden', 'false');
        overlay.querySelector('.low-stock-list, .forecast-modal-body, .held-modal-body')?.scrollTo(0, 0);
        state.trigger?.setAttribute('aria-expanded', 'true');
        document.body.style.overflow = 'hidden';
        const dialog = overlay.querySelector('[role="dialog"]');
        dialog.setAttribute('tabindex', '-1');
        (options.initialFocus || controls(overlay)[0] || dialog).focus({preventScroll: true});
        // Keep the dialog's ancestors usable while isolating their siblings.
        // This also works for page dialogs nested inside main content.
        for (let node = overlay; node.parentElement && node !== document.body; node = node.parentElement) {
            Array.from(node.parentElement.children).forEach(sibling => {
                if (sibling === node || ['SCRIPT', 'STYLE', 'LINK'].includes(sibling.tagName)) return;
                state.inert.push([sibling, sibling.inert]);
                sibling.inert = true;
            });
        }
    }
    document.addEventListener('keydown', event => {
        if (!active) return;
        if (event.key === 'Escape' && !event.isComposing) {
            event.preventDefault();
            event.stopImmediatePropagation();
            close(active.overlay);
        } else if (event.key === 'Tab') {
            const items = controls(active.overlay);
            const first = items[0] || active.overlay.querySelector('[role="dialog"]');
            const last = items[items.length - 1] || first;
            if (!active.overlay.contains(document.activeElement) || (event.shiftKey && document.activeElement === first) || (!event.shiftKey && document.activeElement === last)) {
                event.preventDefault();
                (event.shiftKey ? last : first).focus();
            }
        }
    }, true);
    document.addEventListener('focusin', event => {
        if (active && !active.overlay.contains(event.target)) {
            (controls(active.overlay)[0] || active.overlay.querySelector('[role="dialog"]')).focus();
        }
    }, true);
    window.PharxmacoModal = {open, close, isOpen: () => active !== null};
})();
