(() => {
    'use strict';
    const dialog = document.querySelector('#confirm-dialog');
    let pendingForm = null, callback = null;
    window.arenaConfirm = (title, message, action, onConfirm) => {
        document.querySelector('#confirm-title').textContent = title;
        document.querySelector('#confirm-description').textContent = message;
        document.querySelector('#confirm-action').textContent = action;
        callback = onConfirm; dialog.returnValue = ''; dialog.showModal();
    };
    dialog.addEventListener('close', () => {
        if (dialog.returnValue === 'confirm') {
            if (pendingForm) { pendingForm.dataset.confirmed = 'true'; pendingForm.requestSubmit(); }
            else if (callback) callback();
        }
        pendingForm = null; callback = null;
    });
    document.addEventListener('submit', e => {
        const form = e.target;
        if (form.dataset.confirm && !form.dataset.confirmed) {
            e.preventDefault(); pendingForm = form;
            window.arenaConfirm(form.dataset.confirmTitle, form.dataset.confirm, form.dataset.confirmAction, null);
            return;
        }
        if (form.method.toLowerCase() !== 'get' && !form.closest('dialog')) {
            // Preserve submit button name/value (draft/ready) while preventing double submits.
            if (form.dataset.submitting) { e.preventDefault(); return; }
            form.dataset.submitting = 'true';
            const button = e.submitter;
            if (button) { button.setAttribute('aria-busy', 'true'); button.style.opacity = '.65'; }
        }
    });
    document.querySelectorAll('.password-toggle').forEach(button => button.addEventListener('click', () => {
        const input = document.getElementById(button.getAttribute('aria-controls'));
        const show = input.type === 'password'; input.type = show ? 'text' : 'password';
        button.textContent = show ? 'Sembunyikan' : 'Lihat'; button.setAttribute('aria-pressed', String(show));
    }));
    document.addEventListener('click', e => document.querySelectorAll('.card-menu[open]').forEach(menu => { if (!menu.contains(e.target)) menu.open = false; }));
    document.addEventListener('keydown', e => { if (e.key === 'Escape') document.querySelectorAll('.card-menu[open]').forEach(menu => menu.open = false); });
    const errors = document.querySelector('.error-summary'); if (errors) errors.focus();
    window.addEventListener('pageshow', () => document.querySelectorAll('form[data-submitting]').forEach(f => { delete f.dataset.submitting; f.querySelectorAll('[aria-busy]').forEach(b => { b.removeAttribute('aria-busy'); b.style.opacity = ''; }); }));
})();
