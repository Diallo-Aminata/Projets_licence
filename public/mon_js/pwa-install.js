(function () {
    var BASE = window.PWA_BASE_URL || '';
    var STORAGE_DISMISSED = 'transgest_pwa_prompt_dismissed';
    var STORAGE_INSTALLED = 'transgest_pwa_installed';
    var deferredPrompt = null;

    // L'application installable est réservée à l'espace admin (agents/staff),
    // pas au site public consulté par les clients.
    function isAdminSection() {
        return /\/admin(\/|$)/i.test(window.location.pathname);
    }

    function addMeta(attr, name, content) {
        if (document.querySelector('meta[' + attr + '="' + name + '"]')) return;
        var m = document.createElement('meta');
        m.setAttribute(attr, name);
        m.setAttribute('content', content);
        document.head.appendChild(m);
    }

    if (isAdminSection()) {
        // --- Manifest + meta tags "app mobile" (injectés une seule fois, sans toucher chaque vue) ---
        if (!document.querySelector('link[rel="manifest"]')) {
            var manifestLink = document.createElement('link');
            manifestLink.rel = 'manifest';
            manifestLink.href = BASE + '/manifest.json';
            document.head.appendChild(manifestLink);
        }
        if (!document.querySelector('link[rel="apple-touch-icon"]')) {
            var appleIcon = document.createElement('link');
            appleIcon.rel = 'apple-touch-icon';
            appleIcon.href = BASE + '/assets_site/img/icons/apple-touch-icon.png';
            document.head.appendChild(appleIcon);
        }
        addMeta('name', 'theme-color', '#0f3b5e');
        addMeta('name', 'mobile-web-app-capable', 'yes');
        addMeta('name', 'apple-mobile-web-app-capable', 'yes');
        addMeta('name', 'apple-mobile-web-app-status-bar-style', 'black-translucent');
        addMeta('name', 'apple-mobile-web-app-title', 'TransGest Admin');
    }

    // --- Service worker (nécessaire pour l'installabilité + un peu de cache statique) ---
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register(BASE + '/sw.js', { scope: BASE + '/' }).catch(function () {});
        });
    }

    function isStandalone() {
        return (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches)
            || window.navigator.standalone === true;
    }

    function isIos() {
        return /iPhone|iPad|iPod/i.test(navigator.userAgent) && !window.MSStream;
    }

    function isMobile() {
        return /Android|iPhone|iPad|iPod/i.test(navigator.userAgent)
            || (window.matchMedia && window.matchMedia('(max-width: 900px)').matches);
    }

    if (isStandalone()) {
        localStorage.setItem(STORAGE_INSTALLED, '1');
    }

    function alreadyHandled() {
        return isStandalone()
            || localStorage.getItem(STORAGE_INSTALLED) === '1'
            || localStorage.getItem(STORAGE_DISMISSED) === '1';
    }

    function showModal(mode) {
        if (document.getElementById('pwaInstallOverlay')) return;

        var overlay = document.createElement('div');
        overlay.id = 'pwaInstallOverlay';

        var iosBlock = '<p>Ajoutez TransGest Admin sur votre écran d’accueil pour l’ouvrir comme une vraie application.</p>'
            + '<ul class="pwa-steps">'
            + '<li><b>1</b> Appuyez sur <strong>Partager</strong> en bas de l’écran</li>'
            + '<li><b>2</b> Choisissez <strong>« Sur l’écran d’accueil »</strong></li>'
            + '<li><b>3</b> Confirmez avec <strong>Ajouter</strong></li>'
            + '</ul>';
        var androidBlock = '<p>Installez TransGest Admin sur votre téléphone pour un accès rapide en plein écran, comme une vraie application.</p>';

        overlay.innerHTML =
            '<style>' +
            '#pwaInstallOverlay{position:fixed;inset:0;background:rgba(15,23,32,.55);z-index:99999;display:flex;align-items:flex-end;justify-content:center;animation:pwaFadeIn .25s ease;}' +
            '@keyframes pwaFadeIn{from{opacity:0}to{opacity:1}}' +
            '@keyframes pwaSlideUp{from{transform:translateY(40px);opacity:0}to{transform:translateY(0);opacity:1}}' +
            '#pwaInstallCard{background:#fff;width:100%;max-width:440px;border-radius:20px 20px 0 0;padding:24px 22px 28px;box-shadow:0 -8px 30px rgba(0,0,0,.25);font-family:"Inter",system-ui,sans-serif;animation:pwaSlideUp .3s ease;box-sizing:border-box;}' +
            '@media (min-width:600px){#pwaInstallOverlay{align-items:center}#pwaInstallCard{border-radius:20px}}' +
            '#pwaInstallCard .pwa-icon{width:64px;height:64px;border-radius:16px;margin-bottom:14px;box-shadow:0 4px 14px rgba(15,59,94,.35);display:block;}' +
            '#pwaInstallCard h3{margin:0 0 6px;font-size:1.15rem;color:#0f3b5e;}' +
            '#pwaInstallCard p{margin:0 0 18px;color:#5b6472;font-size:.92rem;line-height:1.5;}' +
            '#pwaInstallCard .pwa-steps{margin:0 0 18px;padding:0;list-style:none;font-size:.88rem;color:#3a4250;}' +
            '#pwaInstallCard .pwa-steps li{display:flex;align-items:center;gap:10px;margin-bottom:8px;}' +
            '#pwaInstallCard .pwa-steps b{background:#eef3f8;color:#0f3b5e;border-radius:8px;width:26px;height:26px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:.8rem;}' +
            '#pwaInstallCard .pwa-actions{display:flex;gap:10px;}' +
            '#pwaInstallCard button{flex:1;border:none;border-radius:12px;padding:13px;font-size:.95rem;font-weight:600;cursor:pointer;}' +
            '#pwaInstallBtn{background:#0f3b5e;color:#fff;}' +
            '#pwaDismissBtn{background:#f1f3f5;color:#5b6472;}' +
            '</style>' +
            '<div id="pwaInstallCard">' +
            '<img class="pwa-icon" src="' + BASE + '/assets_site/img/icons/icon-192.png" alt="TransGest Admin">' +
            '<h3>Installer TransGest Admin</h3>' +
            (mode === 'ios' ? iosBlock : androidBlock) +
            '<div class="pwa-actions">' +
            '<button id="pwaDismissBtn" type="button">Plus tard</button>' +
            (mode === 'ios' ? '' : '<button id="pwaInstallBtn" type="button">Installer</button>') +
            '</div>' +
            '</div>';

        document.body.appendChild(overlay);

        document.getElementById('pwaDismissBtn').addEventListener('click', function () {
            localStorage.setItem(STORAGE_DISMISSED, '1');
            overlay.remove();
        });

        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) overlay.remove();
        });

        var installBtn = document.getElementById('pwaInstallBtn');
        if (installBtn) {
            installBtn.addEventListener('click', function () {
                if (!deferredPrompt) {
                    overlay.remove();
                    return;
                }
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then(function (choice) {
                    localStorage.setItem(choice.outcome === 'accepted' ? STORAGE_INSTALLED : STORAGE_DISMISSED, '1');
                    deferredPrompt = null;
                    overlay.remove();
                });
            });
        }
    }

    // Le modal ne doit jamais apparaître avant connexion (ex: sur la page de login
    // elle-même) : PWA_USER_LOGGED_IN n'est injecté à `true` par index.php que
    // lorsqu'une session utilisateur est ouverte.
    function estConnecte() {
        return window.PWA_USER_LOGGED_IN === true;
    }

    window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault();
        deferredPrompt = e;
        // Pas de isMobile() ici volontairement : deferredPrompt.prompt() fonctionne
        // aussi sur Chrome/Edge desktop (crée un raccourci comme sur mobile).
        if (isAdminSection() && estConnecte() && !alreadyHandled()) {
            setTimeout(function () { showModal('android'); }, 1200);
        }
    });

    window.addEventListener('appinstalled', function () {
        localStorage.setItem(STORAGE_INSTALLED, '1');
        localStorage.removeItem(STORAGE_DISMISSED);
    });

    // iOS Safari ne déclenche jamais beforeinstallprompt : instructions manuelles.
    // isMobile()/isIos() restent ici : pas d'équivalent iOS desktop.
    if (isAdminSection() && estConnecte() && isIos() && !alreadyHandled() && isMobile()) {
        setTimeout(function () { showModal('ios'); }, 1200);
    }
})();
