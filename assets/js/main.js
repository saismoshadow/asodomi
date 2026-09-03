/* ASODOMI – JavaScript mínimo */
(function () {
    'use strict';

    // Menú móvil
    var toggle = document.getElementById('navToggle');
    if (toggle) {
        toggle.addEventListener('click', function () {
            var abierto = document.body.classList.toggle('nav-open');
            toggle.setAttribute('aria-expanded', abierto ? 'true' : 'false');
        });
        // Cerrar el menú al navegar (móvil)
        document.querySelectorAll('.main-nav a').forEach(function (a) {
            a.addEventListener('click', function () {
                document.body.classList.remove('nav-open');
                toggle.setAttribute('aria-expanded', 'false');
            });
        });
    }

    // Confirmación visual al enviar formularios
    document.querySelectorAll('form.form').forEach(function (form) {
        form.addEventListener('submit', function () {
            var btn = form.querySelector('button[type="submit"]');
            if (btn) {
                btn.disabled = true;
                btn.textContent = btn.getAttribute('data-sending') || '…';
            }
        });
    });

    // Formulario de inscripción: botón habilitado solo si la casilla de
    // privacidad está marcada (visible pero no clicable antes de consentir).
    document.querySelectorAll('form.iscrizione-form').forEach(function (form) {
        var chk = form.querySelector('input[name="consenso"]');
        var btn = form.querySelector('button[type="submit"]');
        if (chk && btn) {
            // En modo modifica la casilla parte marcada: se inicializa igualmente
            function actualizar() {
                btn.disabled = !chk.checked;
                btn.setAttribute('aria-disabled', btn.disabled ? 'true' : 'false');
            }
            chk.addEventListener('change', actualizar);
            actualizar();
        }
    });

    // ── PWA: service worker + pulsante "Installa app" ─────────────
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('/sw.js').catch(function () { /* ok, non bloccante */ });
        });
    }

    var installBtn = document.getElementById('installBtn');
    var deferredPrompt = null;
    window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault();
        deferredPrompt = e;
        if (installBtn) installBtn.hidden = false;
    });
    if (installBtn) {
        installBtn.addEventListener('click', function () {
            if (!deferredPrompt) return;
            deferredPrompt.prompt();
            deferredPrompt.userChoice.finally(function () {
                deferredPrompt = null;
                installBtn.hidden = true;
            });
        });
    }
    // Nasconde il pulsante se l'app è già installata
    window.addEventListener('appinstalled', function () {
        if (installBtn) installBtn.hidden = true;
    });
})();
