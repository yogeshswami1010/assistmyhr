(function () {
    'use strict';

    var installPrompt = null;
    var standalone = window.matchMedia('(display-mode: standalone)');

    function message(text) {
        document.querySelectorAll('[data-ats-install-status]').forEach(function (status) {
            status.textContent = text;
        });
    }

    function installed() {
        return standalone.matches || navigator.standalone === true;
    }

    function updateButtons() {
        document.querySelectorAll('[data-ats-install]').forEach(function (button) {
            button.hidden = installed();
        });
        if (installed()) message('You are using the ATS app.');
    }

    function instructions() {
        if (/iPad|iPhone|iPod/.test(navigator.userAgent)) {
            return 'Open this page in Safari, tap Share, then Add to Home Screen.';
        }
        if (/Android/.test(navigator.userAgent)) {
            return 'In Chrome, open the menu (⋮), choose Add to Home screen, then Install.';
        }
        if (/Edg\//.test(navigator.userAgent)) {
            return 'In Edge, open the menu (…), choose Apps, then Install this site as an app.';
        }
        return 'In Chrome, use the install icon in the address bar, or open the menu (⋮) → Cast, save and share → Install page as app.';
    }

    window.addEventListener('beforeinstallprompt', function (event) {
        event.preventDefault();
        installPrompt = event;
        updateButtons();
    });

    window.addEventListener('appinstalled', function () {
        installPrompt = null;
        document.querySelectorAll('[data-ats-install]').forEach(function (button) { button.hidden = true; });
        message('ATS app installed. Open its icon from your desktop or home screen.');
    });

    document.addEventListener('click', async function (event) {
        var button = event.target.closest('[data-ats-install]');
        if (!button || button.disabled) return;
        if (!installPrompt) { message(instructions()); return; }

        var prompt = installPrompt;
        installPrompt = null;
        button.disabled = true;
        try {
            await prompt.prompt();
            var choice = await prompt.userChoice;
            message(choice.outcome === 'accepted'
                ? 'Finish the browser installation, then open your ATS from its app icon.'
                : 'You can install the ATS app later from this button or your browser menu.');
        } catch (error) {
            message(instructions());
        } finally {
            button.disabled = false;
        }
    });

    if (standalone.addEventListener) standalone.addEventListener('change', updateButtons);
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', updateButtons);
    else updateButtons();
})();
