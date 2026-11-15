/* ==========================================================================
   To-do List Web App - script.js
   What this file does:
   1. Opens and closes the sidebar drawer (mobile)
   2. Opens and closes the task form as a bottom sheet (mobile)
   3. Shows a confirmation dialog before Delete / Clear Completed
   4. Removes a field's error message as soon as the user starts typing
   5. Focuses the form when it has errors or is in edit mode
   ========================================================================== */

(function () {
    'use strict';

    /* ---------- Elements we use throughout the file ---------- */

    var body        = document.body;
    var panel       = document.getElementById('task-panel');
    var nameInput   = document.getElementById('task_name');
    var menuButton  = document.querySelector('[data-open-drawer]');
    var dialog      = document.getElementById('confirm-dialog');
    var mobileQuery = window.matchMedia('(max-width: 640px)');

    // The form waiting for the user to confirm (Delete / Clear Completed)
    var pendingForm = null;

    // True when the screen is phone-sized
    function isMobile() {
        return mobileQuery.matches;
    }

    /* ---------- 1. Sidebar drawer (mobile) ---------- */

    function setDrawer(open) {
        body.classList.toggle('drawer-open', open);
        if (menuButton) {
            menuButton.setAttribute('aria-expanded', open ? 'true' : 'false');
        }
    }

    /* ---------- 2. Task form bottom sheet (mobile) ---------- */

    function openSheet() {
        body.classList.add('sheet-open');
        if (nameInput) {
            nameInput.focus({ preventScroll: true });
        }
    }

    function closeSheet() {
        // In edit mode, closing the sheet means cancelling the edit
        if (panel && panel.dataset.mode === 'edit') {
            window.location.href = panel.dataset.cancelUrl;
        } else {
            body.classList.remove('sheet-open');
        }
    }

    /* ---------- Click handling (one listener for all buttons) ---------- */

    document.addEventListener('click', function (event) {
        var target = event.target;
        if (!(target instanceof Element)) {
            return;
        }

        // Hamburger button -> open drawer
        if (target.closest('[data-open-drawer]')) {
            setDrawer(true);
            return;
        }

        // X button inside the drawer -> close drawer
        if (target.closest('[data-close-drawer]')) {
            setDrawer(false);
            return;
        }

        // X button on the form -> close sheet
        if (target.closest('[data-close-sheet]')) {
            closeSheet();
            return;
        }

        // Dark area behind the drawer or sheet -> close whichever is open
        if (target.closest('[data-backdrop]')) {
            if (body.classList.contains('drawer-open')) {
                setDrawer(false);
            } else {
                closeSheet();
            }
            return;
        }

        // "New Task" buttons
        var newTaskButton = target.closest('[data-new-task]');
        if (newTaskButton) {
            // If we are editing, reload with a blank form
            if (panel && panel.dataset.mode === 'edit') {
                window.location.href = newTaskButton.dataset.newUrl;
                return;
            }

            setDrawer(false);

            if (isMobile()) {
                openSheet();
            } else if (nameInput) {
                nameInput.focus();
                nameInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    });

    // Escape key closes the drawer or sheet (the dialog handles its own Escape)
    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape' || (dialog && dialog.open)) {
            return;
        }
        if (body.classList.contains('drawer-open')) {
            setDrawer(false);
        } else if (isMobile() && body.classList.contains('sheet-open')) {
            closeSheet();
        }
    });

    // Close the drawer if the screen is resized past the mobile size
    mobileQuery.addEventListener('change', function () {
        setDrawer(false);
    });

    /* ---------- 3. Confirmation dialog (Delete / Clear Completed) ---------- */

    if (dialog) {
        var dialogTitle = document.getElementById('confirm-title');
        var dialogText  = document.getElementById('confirm-text');

        // Intercept any form marked with data-confirm
        document.addEventListener('submit', function (event) {
            var form = event.target;
            if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-confirm')) {
                return;
            }

            // Stop the form until the user confirms
            event.preventDefault();

            var title = form.dataset.confirmTitle || 'Are you sure?';
            var text  = form.dataset.confirmText || '';

            // Old browsers without <dialog>: use the basic confirm box
            if (typeof dialog.showModal !== 'function') {
                if (window.confirm(title + '\n' + text)) {
                    form.submit();
                }
                return;
            }

            // Show the dialog with this form's message
            pendingForm = form;
            dialogTitle.textContent = title;
            dialogText.textContent  = text;
            dialog.showModal();
        });

        // Buttons inside the dialog
        dialog.addEventListener('click', function (event) {
            var target = event.target;
            if (!(target instanceof Element)) {
                return;
            }

            // Confirm -> submit the form that was waiting
            if (target.closest('[data-dialog-confirm]')) {
                var form = pendingForm;
                dialog.close();
                if (form) {
                    form.submit();
                }
                return;
            }

            // Cancel button or click on the dark backdrop -> just close
            if (target === dialog || target.closest('[data-dialog-cancel]')) {
                dialog.close();
            }
        });

        // Whenever the dialog closes, forget the waiting form
        dialog.addEventListener('close', function () {
            pendingForm = null;
        });
    }

    /* ---------- 4. Clear a field's error when the user types ---------- */

    document.addEventListener('input', function (event) {
        var field = event.target;
        if (!(field instanceof Element)) {
            return;
        }

        var wrapper = field.closest('.field--error');
        if (!wrapper) {
            return;
        }

        // Remove the red styling, accessibility flags and the message
        wrapper.classList.remove('field--error');
        field.removeAttribute('aria-invalid');
        field.removeAttribute('aria-describedby');

        var message = wrapper.querySelector('.field-error');
        if (message) {
            message.remove();
        }
    });

    /* ---------- 5. Initial focus ---------- */

    // PHP sets data-focus-form="true" when the form has errors or is in edit mode
    if (body.dataset.focusForm === 'true') {
        var firstInvalid = document.querySelector('[aria-invalid="true"]');
        var focusTarget  = firstInvalid || nameInput;

        // On mobile, only focus if the sheet is actually open
        if (focusTarget && (!isMobile() || body.classList.contains('sheet-open'))) {
            focusTarget.focus({ preventScroll: true });
        }
    }
})();