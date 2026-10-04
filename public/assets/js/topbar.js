(() => {
    const wrap = document.getElementById('accountWrap');
    const button = document.getElementById('accountButton');
    const panel = document.getElementById('accountPanel');
    if (!wrap || !button || !panel) return;
    const close = (restoreFocus = false) => {
        panel.hidden = true;
        button.setAttribute('aria-expanded', 'false');
        if (restoreFocus) button.focus();
    };
    const open = () => {
        document.dispatchEvent(new Event('pharxmaco:close-notifications'));
        panel.hidden = false;
        button.setAttribute('aria-expanded', 'true');
    };
    button.addEventListener('click', () => panel.hidden ? open() : close());
    button.addEventListener('keydown', event => {
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            open();
            panel.querySelector('a').focus();
        }
    });
    wrap.addEventListener('keydown', event => {
        if (event.key === 'Escape' && !panel.hidden) {
            event.preventDefault();
            event.stopPropagation();
            close(true);
        }
    });
    // Capture handles buttons such as notifications that stop click bubbling.
    document.addEventListener('click', event => { if (!wrap.contains(event.target)) close(); }, true);
    document.addEventListener('focusin', event => { if (!wrap.contains(event.target)) close(); });
})();
