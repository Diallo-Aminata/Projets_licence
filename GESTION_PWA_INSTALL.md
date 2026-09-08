# Modal d'installation de l'application (PWA) après connexion

## Résumé

L'application admin est installable comme une PWA (Progressive Web App) depuis longtemps déjà (manifest, service worker, modal d'installation entièrement fonctionnel). Ce qui a changé ici : **le moment où le modal apparaît**. Avant, il pouvait apparaître dès qu'une page `/admin/...` était affichée — y compris la page de connexion elle-même, avant qu'un utilisateur ne soit identifié — et uniquement sur mobile. Il apparaît désormais **seulement après une connexion réussie**, et aussi bien sur **mobile que sur ordinateur (desktop)**.

## Contexte / motivation

Rien n'a été reconstruit : toute l'infrastructure PWA existait déjà et fonctionnait :

- `public/manifest.json` — nom, icônes, couleurs de l'application.
- `public/sw.js` — service worker minimal (cache des fichiers statiques, condition d'installabilité).
- `public/mon_js/pwa-install.js` — modal d'installation complet (icône, titre, bouton "Installer" ou instructions manuelles iOS, bouton "Plus tard", mémorisation du choix dans `localStorage`).

Ce script est injecté automatiquement sur chaque page admin par `index.php` (juste avant `</body>`, sans toucher chaque vue individuellement).

Deux problèmes identifiés dans le déclenchement existant :

1. **Condition basée uniquement sur l'URL** (`/admin/...`) : la page de connexion elle-même est sous `/admin/`, donc le modal pouvait apparaître avant même qu'un utilisateur ne soit connecté — peu pertinent (rien à "réutiliser" pour quelqu'un qui n'est pas encore dans l'application), et perturbant sur l'écran de connexion.
2. **Restriction `isMobile()` sur la branche Android/Chrome** : `deferredPrompt.prompt()` (l'API native d'installation) fonctionne très bien aussi sur Chrome/Edge **desktop** et crée un vrai raccourci, mais le code bloquait cette branche aux seuls écrans mobiles.

## Ce qui a changé

### `index.php`

Le callback `ob_start()` déjà existant (celui qui injecte `PWA_BASE_URL`) injecte désormais aussi un second global JS :

```php
window.PWA_USER_LOGGED_IN = <?= json_encode(isset($_SESSION['id_utilisateur'])) ?>;
```

`$_SESSION` est déjà disponible à ce point (`session_start()` est appelé plus haut dans le même fichier). Cette valeur vaut `true` uniquement lorsqu'une session utilisateur est ouverte — donc jamais sur la page de connexion elle-même.

### `public/mon_js/pwa-install.js`

Nouvelle fonction `estConnecte()` qui lit `window.PWA_USER_LOGGED_IN`. Elle est ajoutée en garde sur les deux points de déclenchement automatique du modal :

- **Branche Android/Chrome/desktop** (a un `deferredPrompt` disponible, via l'évènement `beforeinstallprompt`) : la condition `isMobile()` a été **retirée** — c'était le seul frein empêchant le modal d'apparaître sur ordinateur. `isAdminSection()` et `estConnecte()` restent en garde.
- **Branche iOS** (Safari ne déclenche jamais `beforeinstallprompt`, instructions manuelles) : `isMobile()`/`isIos()` sont **conservés** — il n'existe pas d'équivalent "iOS desktop" à gérer.

Résultat concret : le modal ne peut plus apparaître sur la page de connexion (session pas encore ouverte), et apparaît bien juste après la redirection vers `admin/Homes/home`, aussi bien sur mobile que sur une fenêtre desktop large.

## Comment tester

1. Vider dans les outils développeur du navigateur (onglet Application/Storage) les clés `localStorage` suivantes, propres à ce domaine :
   - `transgest_pwa_prompt_dismissed`
   - `transgest_pwa_installed`
2. Aller sur la page de connexion : le modal ne doit **pas** apparaître.
3. Se connecter : le modal doit apparaître environ 1,2 seconde après l'arrivée sur `admin/Homes/home` — y compris dans une fenêtre de navigateur desktop de taille normale (pas seulement en réduisant la fenêtre pour simuler du mobile).
4. Cliquer "Plus tard" : le modal ne doit plus réapparaître tant que `transgest_pwa_prompt_dismissed` n'est pas effacé.
5. Cliquer "Installer" (Chrome/Edge desktop ou Android) : un raccourci/une icône d'application doit être proposé(e) par le navigateur.

## Limites connues / choix assumés

- Le modal ne se déclenche que si le navigateur émet l'évènement `beforeinstallprompt` (Chrome, Edge, la plupart des navigateurs Chromium) ou s'il s'agit de Safari iOS (instructions manuelles). Firefox desktop, par exemple, ne propose pas d'installation PWA native — le modal ne s'y déclenchera simplement jamais, ce qui est un comportement de navigateur, pas un bug de ce code.
- Aucun changement sur le contenu du modal lui-même (icône, textes, boutons) : uniquement sa condition de déclenchement.
- Le choix (installé / "plus tard") reste mémorisé par navigateur via `localStorage`, comme avant — pas de mémorisation côté serveur par utilisateur.

## Fichiers modifiés

- `index.php`
- `public/mon_js/pwa-install.js`
- `GESTION_PWA_INSTALL.md` (ce fichier, nouveau)

## Rollback

Retirer la ligne `window.PWA_USER_LOGGED_IN = ...;` dans `index.php`, et dans `pwa-install.js` : remettre `isMobile()` sur la branche `beforeinstallprompt` et retirer `estConnecte()` des deux conditions de déclenchement (ou revenir simplement au commit précédent pour ces deux fichiers).
