(() => {
    const form = document.getElementById('uiSettingsForm');
    if (!form) return;
    const key = 'pharxmaco-ui-settings';
    const defaults = {theme:'system', accent:'blue', textSize:'normal', density:'comfortable', largeControls:false, reduceMotion:false, highContrast:false, stickyHeaders:false};
    const allowed = {theme:['system','light','dark'], accent:['blue','green','purple','orange'], textSize:['normal','large','extra-large'], density:['compact','comfortable','spacious']};
    const switches = ['largeControls','reduceMotion','highContrast','stickyHeaders'];
    const normalize = value => Object.fromEntries(Object.entries(defaults).map(([name, fallback]) => [name,
        allowed[name] ? (allowed[name].includes(value?.[name]) ? value[name] : fallback) : value?.[name] === true
    ]));
    const read = () => {
        try { return normalize(JSON.parse(localStorage.getItem(key))); }
        catch (_) { return {...defaults}; }
    };
    const collect = () => {
        const value = Object.fromEntries(new FormData(form));
        switches.forEach(name => value[name] = form.elements[name].checked);
        return normalize(value);
    };
    const fill = settings => {
        Object.keys(allowed).forEach(name => form.querySelector(`input[name="${name}"][value="${settings[name]}"]`).checked = true);
        switches.forEach(name => form.elements[name].checked = settings[name]);
    };
    let saved = read();
    const preview = document.getElementById('interfacePreview');
    const status = document.getElementById('settingsStatus');
    const feedback = document.getElementById('settingsFeedback');
    const error = document.getElementById('settingsError');
    const saveButton = document.getElementById('saveUiSettings');
    const discardButton = document.getElementById('discardUiSettings');
    const scheme = matchMedia('(prefers-color-scheme: dark)');
    const dirtyKeys = () => Object.keys(defaults).filter(name => collect()[name] !== saved[name]);
    const message = text => { feedback.textContent = text; feedback.hidden = !text; };
    const labels = {theme:{system:'Device',light:'Light',dark:'Dark'},accent:{blue:'Blue',green:'Green',purple:'Purple',orange:'Orange'}};
    const update = () => {
        const value = collect();
        const dirty = dirtyKeys().length;
        status.textContent = dirty ? `${dirty} unsaved ${dirty === 1 ? 'change' : 'changes'}` : 'No unsaved changes';
        status.classList.toggle('unsaved', dirty > 0);
        saveButton.disabled = discardButton.disabled = dirty === 0;
        const dark = value.theme === 'dark' || (value.theme === 'system' && scheme.matches);
        preview.dataset.theme = dark ? 'dark' : 'light';
        preview.dataset.accent = value.accent;
        preview.dataset.textSize = value.textSize;
        preview.dataset.density = value.density;
        switches.forEach(name => preview.classList.toggle(name, value[name]));
        document.getElementById('settingsPreviewSummary').textContent = `${labels.theme[value.theme]}${value.theme === 'system' ? (dark ? ' · Dark' : ' · Light') : ''} · ${labels.accent[value.accent]}`;
        const features = document.getElementById('settingsPreviewFeatures');
        features.replaceChildren(...switches.filter(name => value[name]).map(name => {
            const item = document.createElement('li');
            item.textContent = document.getElementById(`${name}Label`).textContent;
            return item;
        }));
        features.hidden = !features.childElementCount;
    };
    const clearNotices = () => { message(''); error.hidden = true; };
    fill(saved);
    update();
    if (matchMedia('(max-width: 980px)').matches) document.getElementById('settingsPreviewDetails').open = false;
    form.addEventListener('change', () => { clearNotices(); update(); });
    form.addEventListener('submit', event => {
        event.preventDefault();
        if (!dirtyKeys().length) return;
        const value = collect();
        clearNotices();
        try { localStorage.setItem(key, JSON.stringify(value)); }
        catch (_) {
            error.textContent = 'Could not save. Allow browser storage and try again. Your selected choices are still here.';
            error.hidden = false;
            return;
        }
        saved = value;
        // The shared layout applies saved preferences and updates the topbar control.
        window.dispatchEvent(new Event('pharxmaco:ui-settings-saved'));
        update(); message('Preferences saved in this browser.');
    });
    discardButton.addEventListener('click', () => { fill(saved); clearNotices(); update(); message('Unsaved changes discarded.'); });
    document.getElementById('resetUiSettings').addEventListener('click', () => {
        fill(defaults); clearNotices(); update();
        message(dirtyKeys().length ? 'Defaults are ready to preview. Save preferences to apply them.' : 'You are already using the defaults.');
    });
    // Preserve fields being edited if the topbar or another tab changes saved preferences.
    const sync = value => {
        const next = normalize(value), draft = collect(), edited = dirtyKeys();
        if (!Object.keys(defaults).some(name => next[name] !== saved[name])) return;
        saved = next;
        fill({...next, ...Object.fromEntries(edited.map(name => [name,draft[name]]))});
        clearNotices(); update();
        message(edited.length ? 'Saved preferences changed elsewhere. Your unsaved choices are kept.' : 'Showing the latest saved preferences.');
    };
    window.addEventListener('pharxmaco:ui-settings-applied', event => { if (event.detail) sync(event.detail); });
    window.addEventListener('storage', event => { if (event.key === key || event.key === null) sync(read()); });
    scheme.addEventListener('change', update);
    window.addEventListener('beforeunload', event => {
        if (!dirtyKeys().length) return;
        event.preventDefault(); event.returnValue = '';
    });
})();
