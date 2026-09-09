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

### Bouton flottant persistant (ajout ultérieur)

Le navigateur (Chrome/Edge) n'émet l'évènement `beforeinstallprompt` qu'après ses **propres heuristiques d'engagement** (plusieurs visites, temps passé sur le site...) — rien ne garantit qu'il se déclenche dès la toute première connexion. Et une fois que l'utilisateur a cliqué "Plus tard", le modal automatique ne réapparaît plus jamais (`transgest_pwa_prompt_dismissed`).

Un petit **bouton flottant** ("Installer l'app", coin bas-droit) a donc été ajouté pour donner un accès manuel permanent à l'installation :

- Il apparaît dès que le navigateur confirme que l'installation est possible (réception de `beforeinstallprompt` pour Chrome/Edge, ou détection iOS Safari mobile pour les instructions manuelles) — **indépendamment** du fait que le modal ait déjà été refusé ou non.
- Un clic dessus rouvre exactement le même modal (`showModal()`), sans reconstruire d'UI.
- Il **disparaît automatiquement** dès que l'application est effectivement installée (évènement `appinstalled`, ou détection de mode standalone au chargement).

Ce bouton ne contourne pas la limite ci-dessus : si le navigateur n'a pas encore émis `beforeinstallprompt` (heuristique pas encore satisfaite), ni le modal ni le bouton n'apparaissent — c'est une décision du navigateur, hors de portée du code de ce projet.

## Comment tester

1. Vider dans les outils développeur du navigateur (onglet Application/Storage) les clés `localStorage` suivantes, propres à ce domaine :
   - `transgest_pwa_prompt_dismissed`
   - `transgest_pwa_installed`
2. Aller sur la page de connexion : le modal ne doit **pas** apparaître.
3. Se connecter : le modal doit apparaître environ 1,2 seconde après l'arrivée sur `admin/Homes/home` — y compris dans une fenêtre de navigateur desktop de taille normale (pas seulement en réduisant la fenêtre pour simuler du mobile).
4. Cliquer "Plus tard" : le modal automatique ne doit plus réapparaître tant que `transgest_pwa_prompt_dismissed` n'est pas effacé — mais le bouton flottant "Installer l'app" doit, lui, rester visible en bas à droite.
5. Cliquer sur le bouton flottant : le modal doit se rouvrir.
6. Cliquer "Installer" (Chrome/Edge desktop ou Android) : un raccourci/une icône d'application doit être proposé(e) par le navigateur, et le bouton flottant doit disparaître une fois l'installation confirmée.

## Limites connues / choix assumés

- Le modal (et le bouton flottant) ne se déclenchent que si le navigateur émet l'évènement `beforeinstallprompt` (Chrome, Edge, la plupart des navigateurs Chromium) ou s'il s'agit de Safari iOS (instructions manuelles). Firefox desktop, par exemple, ne propose pas d'installation PWA native — ni l'un ni l'autre ne s'y déclenchera jamais, ce qui est un comportement de navigateur, pas un bug de ce code.
- `beforeinstallprompt` est soumis aux heuristiques d'engagement propres à Chrome/Edge (ce n'est pas systématiquement dès la première visite/connexion) : ni le modal ni le bouton flottant ne peuvent apparaître avant que le navigateur ait lui-même décidé que le site est "installable".
- Aucun changement sur le contenu du modal lui-même (icône, textes, boutons) : uniquement sa condition de déclenchement, plus l'ajout du bouton flottant comme point d'entrée manuel permanent.
- Le choix (installé / "plus tard") reste mémorisé par navigateur via `localStorage`, comme avant — pas de mémorisation côté serveur par utilisateur.

## Fichiers modifiés

- `index.php`
- `public/mon_js/pwa-install.js`
- `GESTION_PWA_INSTALL.md` (ce fichier, nouveau)

## Rollback

Retirer la ligne `window.PWA_USER_LOGGED_IN = ...;` dans `index.php`, et dans `pwa-install.js` : remettre `isMobile()` sur la branche `beforeinstallprompt`, retirer `estConnecte()` des deux conditions de déclenchement, et retirer les appels à `showFloatingButton()`/`hideFloatingButton()` (ou revenir simplement au commit précédent pour ces deux fichiers).
