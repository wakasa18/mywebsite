
        const pass = document.getElementById('password');
        const btn  = document.getElementById('togglePass');
        const icon = document.getElementById('eyeIcon');
        const eyeOpen   = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
        const eyeClosed = '<path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19M1 1l22 22"/>';
        btn.addEventListener('click', () => {
            const show = pass.type === 'password';
            pass.type  = show ? 'text' : 'password';
            icon.innerHTML = show ? eyeClosed : eyeOpen;
            btn.setAttribute('aria-pressed', String(show));
            btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            btn.title = show ? 'Hide password' : 'Show password';
        });
    