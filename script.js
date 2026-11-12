/* ==========================================================================
   To-do List Web App - script.js
   Basic DOM event handling:
   - mobile drawer and bottom sheet (open / close)
   - confirmation dialog for Delete and Clear Completed
   - clears a field's error styling as soon as the user starts fixing it
   ========================================================================== */

(function () {
    'use strict';

    var body = document.body;
    var mobileQuery = window.matchMedia('(max-width: 640px)');

    var panel = document.getElementById('task-panel');
    var nameInput = document.getElementById('task_name');
    var menuButton = document.querySelector('[data-open-drawer]');
    var dialog = document.getElementById('confirm-dialog');

    function isMobile() {
        return mobileQuery.matches;
    }

    /* ---------- Drawer (mobile sidebar) ---------- */

    function openDrawer() {
        body.classList.add('drawer-open');
        if (menuButton) {
            menuButton.setAttribute('aria-expanded', 'true');
        }
    }

    function closeDrawer() {
        body.classList.remove('drawer-open');
        if (menuButton) {
            menuButton.setAttribute('aria-expanded', 'false');
            if (isMobile()) {
                menuButton.focus();
            }
        }
    }

    /* ---------- Bottom sheet (mobile task form) ---------- */

    function openSheet() {
        body.classList.add('sheet-open');
        if (nameInput) {
            nameInput.focus({ preventScroll: true });
        }
    }

    function closeSheet() {
        // While editing, closing the sheet means cancelling the edit
        if (panel && panel.dataset.mode === 'edit') {
            window.location.href = panel.dataset.cancelUrl;
            return;
        }
        body.classList.remove('sheet-open');
    }

    /* ---------- Click handling (event delegation) ---------- */

    document.addEventListener('click', function (event) {
        var target = event.target;
        if (!(target instanceof Element)) {
            return;
        }

        if (target.closest('[data-open-drawer]')) {
            openDrawer();
            return;
        }

        if (target.closest('[data-close-drawer]')) {
            closeDrawer();
            return;
        }

        if (target.closest('[data-close-sheet]')) {
            closeSheet();
            return;
        }

        if (target.closest('[data-backdrop]')) {
            if (body.classList.contains('drawer-open')) {
                closeDrawer();
            } else if (body.classList.contains('sheet-open')) {
                closeSheet();
            }
            return;
        }

        var newTaskButton = target.closest('[data-new-task]');
        if (newTaskButton) {
            // Leave edit mode first, then come back with a blank form
            if (panel && panel.dataset.mode === 'edit') {
                window.location.href = newTaskButton.dataset.newUrl;
                return;
            }

            body.classList.remove('drawer-open');
            if (menuButton) {
                menuButton.setAttribute('aria-expanded', 'false');
            }

            if (isMobile()) {
                openSheet();
            } else if (nameInput) {
                nameInput.focus();
                nameInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape' || (dialog && dialog.open)) {
            return;
        }
        if (body.classList.contains('drawer-open')) {
            closeDrawer();
        } else if (isMobile() && body.classList.contains('sheet-open')) {
            closeSheet();
        }
    });

    // Close the drawer if the screen grows past the mobile breakpoint
    mobileQuery.addEventListener('change', function () {
        body.classList.remove('drawer-open');
        if (menuButton) {
            menuButton.setAttribute('aria-expanded', 'false');
        }
    });

    /* ---------- Confirmation dialog (Delete / Clear Completed) ---------- */

    var pendingForm = null;

    if (dialog) {
        var dialogTitle = document.getElementById('confirm-title');
        var dialogText = document.getElementById('confirm-text');

        document.addEventListener('submit', function (event) {
            var form = event.target;
            if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-confirm')) {
                return;
            }

            event.preventDefault();

            var title = form.dataset.confirmTitle || 'Are you sure?';
            var text = form.dataset.confirmText || '';

            // Older browsers without <dialog> support fall back to window.confirm
            if (typeof dialog.showModal !== 'function') {
                if (window.confirm(title + '\n' + text)) {
                    form.submit();
                }
                return;
            }

            pendingForm = form;
            dialogTitle.textContent = title;
            dialogText.textContent = text;
            dialog.showModal();
        });

        dialog.addEventListener('click', function (event) {
            var target = event.target;

            if (target === dialog || (target instanceof Element && target.closest('[data-dialog-cancel]'))) {
                pendingForm = null;
                dialog.close();
                return;
            }

            if (target instanceof Element && target.closest('[data-dialog-confirm]')) {
                var form = pendingForm;
                pendingForm = null;
                dialog.close();
                if (form) {
                    form.submit();
                }
            }
        });

        dialog.addEventListener('close', function () {
            pendingForm = null;
        });
    }

    /* ---------- Clear a field's error once the user edits it ---------- */

    document.addEventListener('input', function (event) {
        var field = event.target;
        if (!(field instanceof Element)) {
            return;
        }

        var wrapper = field.closest('.field--error');
        if (!wrapper) {
            return;
        }

        wrapper.classList.remove('field--error');
        field.removeAttribute('aria-invalid');
        field.removeAttribute('aria-describedby');

        var message = wrapper.querySelector('.field-error');
        if (message) {
            message.remove();
        }
    });

    /* ---------- Initial focus ---------- */

    if (body.dataset.focusForm === 'true') {
        var firstInvalid = document.querySelector('[aria-invalid="true"]');
        var focusTarget = firstInvalid || nameInput;

        if (focusTarget && (!isMobile() || body.classList.contains('sheet-open'))) {
            focusTarget.focus({ preventScroll: true });
        }
    }
})();