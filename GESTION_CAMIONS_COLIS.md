# Gestion des camions de colis (fret)

## Résumé

Ajout d'un second type de véhicule, le **camion**, dédié au transport de colis (fret), en plus des **cars** existants (bus passagers). Un camion se gère sur le même écran que les cars, un chauffeur peut être affecté à l'un ou l'autre, un colis peut être envoyé sur un camion en plus d'un car, un camion apparaît dans l'écran "Flotte", et un camion peut désormais être loué au même titre qu'un car dans l'écran "Location des cars".

## Contexte / motivation

Avant cette fonctionnalité, l'application ne connaissait qu'un seul type de véhicule (`car`), utilisé à la fois pour le transport de passagers (billetterie, programmation de trajets) et pour l'envoi de colis — un colis ne pouvait être envoyé que sur un car déjà programmé sur un trajet passager du jour (`programmation_voyage`).

Un camion de colis n'a pas vocation à transporter des passagers ni à suivre une programmation de trajet horaire : il doit simplement pouvoir être choisi pour un envoi de colis dès qu'il est en service.

**Choix architectural : une table `camion` séparée de `car`**, plutôt qu'un simple type ajouté sur `car`. La table `car` est fortement couplée à la logique billetterie/programmation (`nbr_place`, `nbr_place_reserve`, `programmer_car`, `programmation_voyage`, `liaison_car_trajet`, `location_car`...) ; séparer les deux tables évite tout risque de régression sur cette logique existante. Toute la fonctionnalité a été construite de façon **additive** : aucune méthode existante liée aux cars n'a été modifiée dans sa logique interne, seules de nouvelles méthodes miroir ont été ajoutées.

## Modèle de données

Migration : voir [`ajout_camions.sql`](ajout_camions.sql) à la racine du repo — à exécuter manuellement (comme les autres scripts `.sql` de ce projet), d'abord en dev puis en prod. **Vérifier la structure réelle de la base avant exécution** (`DESCRIBE chauffeur;`, `DESCRIBE envoi;`, `DESCRIBE ligne_envoi;`) : le dump SQL versionné dans ce repo est connu pour être partiellement périmé par rapport à la base réelle.

### Nouvelle table `camion`

| Colonne | Type | Description |
|---|---|---|
| `id_camion` | INT, PK auto-increment | |
| `numero_camion` | INT | Numéro du camion |
| `matriculle` | VARCHAR(100) | Matricule (orthographe à deux L, cohérente avec `car.matriculle`) |
| `actif` | VARCHAR(10) | `'on'` (actif, sélectionnable) ou `'off'` (inactif) — défaut `'on'` |
| `id_compagnie` | INT | Compagnie propriétaire |

Contrairement à `car`, pas de `nbr_place` (un camion n'a pas de notion de places passagers), pas de `programmer_car` (pas de programmation de trajet).

### Table `chauffeur` — colonnes ajoutées

| Colonne | Type | Description |
|---|---|---|
| `id_car` | INT, nullable (était `NOT NULL`) | Rempli uniquement si `type_vehicule = 'car'` |
| `id_camion` | INT, nullable | Rempli uniquement si `type_vehicule = 'camion'` |
| `type_vehicule` | ENUM('car','camion') | Indique lequel des deux champs ci-dessus fait foi |

**Règle imposée par le code applicatif** (pas de contrainte SQL, ce schéma n'utilise nulle part de `CHECK`/`FOREIGN KEY`) : exactement un des deux champs `id_car`/`id_camion` est renseigné, jamais les deux, jamais aucun.

### Table `envoi` — colonne ajoutée

| Colonne | Type | Description |
|---|---|---|
| `id_camion` | INT, nullable | Miroir de `id_car` (déjà nullable) |

### Table `ligne_envoi` — colonne ajoutée

| Colonne | Type | Description |
|---|---|---|
| `numero_camion` | INT, nullable | Miroir de `numero_car` (rendu nullable par la migration) |

**Convention historique à connaître** : `numero_car` et `numero_camion` stockent en réalité un `id_car`/`id_camion`, pas un "numéro" au sens propre — nom trompeur déjà présent avant cette fonctionnalité, conservé par cohérence.

## Écran "Configuration > Cars & Camions & Chauffeurs"

Trois onglets sur un même écran de configuration :

| Onglet | Contrôleur | Modèle | Vue |
|---|---|---|---|
| Cars | `Cars_chauffeurs` | `Cars_chauffeur` | `cars_chauffeur.view.php` |
| **Camions** | `Camions` (nouveau) | `Camion` (nouveau) | `camions.view.php` (nouveau) |
| Chauffeurs | `Chauffeurs_cars` | `Chauffeurs_car` | `chauffeur_cars.view.php` |

### Ajout/modification/suppression d'un camion

Identique au fonctionnement des cars (formulaire "add to row" permettant d'ajouter plusieurs camions en une soumission), **sans le champ "Nombre de place"**. Un select "Actif / Inactif" remplace l'équivalent `programmer_car` des cars (qui n'a pas de sens pour un camion).

### Affectation d'un chauffeur à un car ou un camion

Dans le formulaire d'enregistrement des chauffeurs (modals d'ajout et d'édition), une checkbox **"Chauffeur de camion (au lieu d'un car)"** bascule entre un select "Car" et un select "Camion". Le champ requis change dynamiquement (JS), et le contrôleur/modèle valident côté serveur qu'exactement un véhicule est sélectionné.

**Point technique important** : la liste des chauffeurs (`Chauffeurs_cars::index()`) utilisait un `INNER JOIN car` — avec `id_car` désormais nullable, ce join aurait silencieusement exclu tout chauffeur affecté à un camion de la liste. Corrigé en un double `LEFT JOIN` (vers `car` et vers `camion`, chacun conditionné par `type_vehicule`).

**Effet de bord du module Salaire** (ajouté après cette fonctionnalité, voir [`GESTION_SALAIRES.md`](GESTION_SALAIRES.md)) : `Chauffeurs_car::saveChauffeur()` crée désormais aussi, automatiquement, une fiche de paie (`employe`) pour tout nouveau chauffeur, qu'il conduise un car ou un camion — sans salaire de base renseigné (0 par défaut, à compléter par l'Admin depuis l'écran "Salaires"). Ça a nécessité de faire passer l'insertion du chauffeur de `insertion_update_simples()` à `insertion_update_simples_insert_id()` pour récupérer son id et créer la fiche liée.

## Écran "Envoi des colis"

URL : `/admin/Envoi_colis/envoi_colis`.

Deux modes de sélection de véhicule, mutuellement exclusifs (JS) :

- **Car** : comportement inchangé — seuls les cars déjà programmés sur un trajet passager du jour (`programmation_voyage`, statut `'active'`) sont proposables.
- **Camion** : tout camion actif (`actif = 'on'`) de la compagnie est proposable, sans notion de programmation du jour.

### Cycle de vie d'un lot d'envoi

Identique pour les deux types, sur le modèle déjà existant pour les cars :

1. Sélection de colis + d'un véhicule (car ou camion) → `traiterEnvoi1()` (car, inchangé) ou `traiterEnvoiCamion()` (camion, nouveau) : verrouille le véhicule et les colis (transaction + `FOR UPDATE`), crée ou réutilise la ligne `ligne_envoi` du jour pour ce véhicule, insère une ligne `envoi` par colis, passe `colis.status` à `'en_cours'`.
2. **Liste des colis envoyés** (`/admin/Envoi_colis/liste_colis_envoyer`) : chaque lot affiche un badge "Car n°X" ou "Camion n°X" selon le type (déduit en SQL : `numero_car` renseigné → car, sinon camion).
3. **Détails / changement de véhicule** (`/admin/Envoi_colis/details_colis_envoyer`) : liste les colis du lot, permet de réaffecter un colis à un autre véhicule. **Le changement reste intra-type** (un colis envoyé par camion ne peut être réaffecté qu'à un autre camion, jamais basculé vers un car, et inversement).
4. **Annulation** (`/admin/Envoi_colis/annuler_envoi`) : remet les colis du lot en statut `'enregistre'`, supprime les lignes `envoi`/`ligne_envoi` correspondantes.

Ces trois dernières actions acceptent un paramètre `type` (`car` par défaut, pour rester compatibles avec d'éventuels liens déjà en cache générés avant cette fonctionnalité).

## Écran "Flotte" (`/admin/Flotte`)

Réservé à Admin/PDG/super_admin (restriction déjà en place, inchangée).

**Constat** : `Programmation_voyage::getEtatFlotte()` construit l'état de chaque car ("en transit", "disponible à X", "anomalie") à partir de `car.status_car` + `programmation_voyage` — un camion n'a ni l'un ni l'autre (seulement `actif` on/off, pas de position ni de trajet). Impossible de fusionner les deux dans un même tableau avec la même logique d'état.

**Solution retenue** : un second tableau, plus simple, sous celui des cars — colonnes Numéro Camion | Matricule | Statut (badge Actif/Inactif). Nouvelle méthode `Camion::getTousPourFlotte()` (tous les camions de la compagnie, actifs **et** inactifs, contrairement à `Envoie_colis::getCamionsActifs()` qui ne veut que les actifs pour l'envoi de colis).

## Écran "Location des cars" (`/admin/Locations_cars`) → camions

Migration : voir [`ajout_location_camion.sql`](ajout_location_camion.sql) — à exécuter manuellement, après `ajout_camions.sql`. **Vérifier la structure réelle avant exécution** (`DESCRIBE location_car;`) : sur cette base, deux migrations `location_car` antérieures (`ajout_location_car_caisse_utilisateur.sql`, ajout des colonnes `id_caisse`/`id_caisse_user`) pouvaient ne pas encore avoir été exécutées — à vérifier également.

### Table `location_car` — colonne ajoutée

| Colonne | Type | Description |
|---|---|---|
| `id_car` | INT, nullable (était `NOT NULL`) | Rempli uniquement si la location porte sur un car |
| `id_camion` | INT, nullable (nouveau) | Rempli uniquement si la location porte sur un camion |

Même convention que partout ailleurs dans cette fonctionnalité : pas de colonne "type" séparée, le type se déduit de savoir laquelle des deux colonnes est renseignée (calculé en SQL via `CASE WHEN id_car IS NOT NULL THEN 'car' ELSE 'camion' END`, même technique que `Envoi_colis::liste_colis_envoyer()`).

### Formulaire de location

Checkbox **"Louer un camion (au lieu d'un car)"** basculant entre le select "Car" existant et un nouveau select "Camion" (même pattern JS `toggleVehiculeFields` que `chauffeur_cars.view.php`, adapté ici car chaque select est peuplé dynamiquement par AJAX plutôt que rendu statiquement).

- **Disponibilité d'un camion** (`Location_car::camionsDisponibles()`) : miroir de `carsDisponibles()`, mais **sans** le sous-filtre "limiterAGare" (pas de `liaison_car_trajet`/`programmer` équivalent pour un camion — même choix déjà fait pour l'envoi de colis : un camion est disponible compagnie entière, pas scopé par gare). Un camion déjà loué sur une période chevauchante est exclu, même logique SQL que pour un car. Seuls les camions `actif = 'on'` sont proposés (contrairement à la Flotte, qui montre tout).
- **Verrouillage anti-double-réservation** : le verrou `FOR UPDATE` + revérification anti-chevauchement à l'écriture (dans `saveLocation()`) s'applique de façon identique, juste sur `camion`/`id_camion` selon le cas (`est_camion` est un flag de formulaire, même convention que `Chauffeurs_car::saveChauffeur()`).
- **IDOR** : `camionAppartientCompagnie()` (miroir de `carAppartientCompagnie()`) vérifie que le camion choisi appartient bien à la compagnie de l'utilisateur.
- La logique de caisse (`crediterCaisse()`, choix caisse de gare vs caisse individuelle du chef d'escale) est **inchangée** : elle ne dépend pas du type de véhicule.

### Historique, facture, validation/rejet

- Tableau historique : colonne "Véhicule" (au lieu de "Car"), avec badge Car/Camion (même style que `liste_colis_envoyer.view.php`).
- `getById()`/`getLocations()` : `LEFT JOIN` vers `car` **et** vers `camion` (au lieu du seul `LEFT JOIN car`), avec les champs calculés `type_vehicule`, `numero_vehicule`, `matricule_vehicule`.
- Facture PDF (`pdf/facture_location.php`) : titre et ligne "Car"/"Camion" dynamiques selon `type_vehicule`.
- `validerLocation()`, `rejeterLocation()`, `crediterCaisse()`, `infoCompagnie()` : **aucun changement** — ils opèrent sur `location_car` par `id_location` uniquement, indépendamment du type de véhicule loué.

## Permissions

**Aucune nouvelle permission créée.** Les permissions existantes couvrent déjà la fonctionnalité :

| Action | Permission |
|---|---|
| Gestion des camions et des chauffeurs (3 onglets) | `Configuration_gestion_car/chauffeur` |
| Envoi de colis (car ou camion) | `colis_envoi` |
| Consultation de la Flotte (cars et camions) | Restriction par rôle (Admin/PDG/super_admin), pas de permission dédiée |
| Location d'un car ou d'un camion | `Location_gestion` |

Ces permissions sont accordées par défaut à `super_admin`/`Admin`/`PDG`, et assignables individuellement à d'autres rôles (`chef_d_escale`, `secretaire`, `Utilisateur`) via l'écran d'assignation de permissions existant.

## Limites connues / choix assumés

- Pas de contrainte `FOREIGN KEY` sur les nouvelles colonnes (cohérence déléguée au code applicatif, comme partout ailleurs dans ce schéma).
- Pas de bascule car ↔ camion lors d'un changement de véhicule pour un colis déjà envoyé (uniquement intra-type).
- Pas d'équivalent de `programmer_car`/`status_car` pour un camion : la seule notion de disponibilité est le champ `actif` (on/off), activé/désactivé manuellement.
- Le champ `id_car_selectionner`/`id_camion_selectionner` du formulaire d'envoi de colis sont deux champs distincts (pas un select unique unifié) : plus simple à raccorder à deux logiques de disponibilité différentes (programmation du jour vs simple statut actif).

## Fichiers créés

- `ajout_camions.sql`
- `ajout_location_camion.sql`
- `app/models/Camion.php`
- `app/controllers/admin/Camions.php`
- `app/views/admin/camions.view.php`
- `GESTION_CAMIONS_COLIS.md` (ce fichier)

## Fichiers modifiés

**Chauffeurs :**
- `app/models/Chauffeurs_car.php`
- `app/controllers/admin/Chauffeurs_cars.php`
- `app/views/admin/chauffeur_cars.view.php`
- `app/views/admin/cars_chauffeur.view.php` (nouvel onglet)

**Envoi de colis :**
- `app/models/Envoie_colis.php`
- `app/controllers/admin/Envoi_colis.php`
- `app/views/admin/ajouter_colis_envoi.view.php`
- `app/views/admin/liste_colis_envoyer.view.php`
- `app/views/admin/details_colis_envoyer.view.php`

**Flotte :**
- `app/models/Camion.php` (`getTousPourFlotte()`)
- `app/controllers/admin/Flotte.php`
- `app/views/admin/flotte.view.php`

**Location des cars :**
- `app/models/Location_car.php` (`camionsDisponibles()`, `camionAppartientCompagnie()`, `saveLocation()`, `getById()`, `getLocations()`)
- `app/controllers/admin/Locations_cars.php` (`ajaxCamionsDisponibles()`)
- `app/views/admin/locations_cars.view.php`
- `app/views/admin/pdf/facture_location.php`

**Libellé "Cars & Chauffeurs" → "Cars & Camions & Chauffeurs" (texte uniquement, 14 vues) :**
`add_liste_horaire.view.php`, `add_liste_escale.view.php`, `configuration.view.php`, `asssignier_permission.view.php`, `documentation.view.php`, `compagnies.view.php`, `place_limite.view.php`, `add_utilisateur.view.php`, `add_gare.view.php`, `add_permission.view.php`, `add_liste_trajet.view.php`, `liste_gare.view.php`, ainsi que `cars_chauffeur.view.php` et `chauffeur_cars.view.php` (déjà listés ci-dessus).

## Procédure de déploiement

1. Vérifier la structure réelle des tables concernées (`DESCRIBE chauffeur; DESCRIBE envoi; DESCRIBE ligne_envoi; DESCRIBE location_car;`).
2. Exécuter `ajout_camions.sql` sur la base (dev, puis prod après validation).
3. Exécuter `ajout_location_camion.sql` **après** `ajout_camions.sql` (colonne `id_camion` sur `location_car`). Si les migrations antérieures `ajout_location_car_caisse_utilisateur.sql`/`ajout_location_car_valide_par.sql` n'ont pas encore été exécutées sur cet environnement, les exécuter aussi (indépendantes de cette fonctionnalité, mais nécessaires au bon fonctionnement de l'écran Location).
4. Déployer le code (`git pull`).
5. Tester : ajout d'un camion, affectation d'un chauffeur de camion (vérifier qu'il apparaît bien dans la liste des chauffeurs), envoi de colis par camion, liste/détails/changement/annulation ; écran Flotte (tableau camions visible) ; écran Location des cars (cocher "Louer un camion", vérifier la liste AJAX, créer une location de camion, valider/rejeter, facture PDF) ; puis re-tester les flux car (envoi, location) pour confirmer l'absence de régression.

### Rollback

```sql
ALTER TABLE location_car DROP COLUMN id_camion;
-- location_car.id_car peut rester nullable sans casser l'existant (idem chauffeur.id_car
-- ci-dessous) ; à ne remettre NOT NULL que si aucune ligne id_car IS NULL ne subsiste.

DROP TABLE IF EXISTS camion;
ALTER TABLE chauffeur DROP COLUMN id_camion, DROP COLUMN type_vehicule;
ALTER TABLE envoi DROP COLUMN id_camion;
ALTER TABLE ligne_envoi DROP COLUMN numero_camion;
-- chauffeur.id_car peut rester nullable sans casser l'existant (les chauffeurs
-- "car" ont tous cette colonne renseignée) ; à ne remettre NOT NULL que si
-- aucune ligne id_car IS NULL ne subsiste.
```
Retirer aussi les fichiers créés (`Camion.php`, `Camions.php`, `camions.view.php`) et revenir au commit précédent pour le reste du code si un rollback complet est nécessaire.
