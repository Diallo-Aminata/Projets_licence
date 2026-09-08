<?php
class Location_car extends Model
{
    const STATUTS = ['en_attente', 'valide', 'rejete'];

    // Cars disponibles pour une gare de depart et une periode donnees.
    // - Si la location est pour AUJOURD'HUI et que $limiterAGare est vrai (chef d'escale) :
    //   uniquement les cars normalement affectes a un trajet au depart de cette gare
    //   (liaison_car_trajet -> programmer.idDepart), le car doit etre physiquement present.
    // - Sinon (Admin, ou date future meme pour un chef d'escale) : tous les cars de la
    //   compagnie, sans cette restriction.
    // Dans tous les cas, un car deja reserve (en_attente ou valide) sur une periode qui
    // chevauche celle demandee est exclu.
    public function carsDisponibles($id_agence_depart, $date_depart, $date_retour, $limiterAGare)
    {
        $id_compagnie = $_SESSION['id_compagnie'];

        $sql = "SELECT DISTINCT car.id_car, car.numero_car, car.matriculle
                FROM car
                WHERE car.id_compagnie = :id_compagnie";
        $params = [':id_compagnie' => $id_compagnie];

        if ($limiterAGare) {
            $sql .= " AND car.id_car IN (
                SELECT lct.id_car FROM liaison_car_trajet lct
                INNER JOIN programmer p ON p.idProgrammer = lct.id_trajets
                WHERE p.idDepart = :id_agence_depart
            )";
            $params[':id_agence_depart'] = $id_agence_depart;
        }

        $sql .= " AND car.id_car NOT IN (
            SELECT lc.id_car FROM location_car lc
            WHERE lc.statut IN ('en_attente', 'valide')
              AND lc.date_depart <= :date_retour AND lc.date_retour_prevu >= :date_depart
        )
        ORDER BY car.numero_car";
        $params[':date_depart'] = $date_depart;
        $params[':date_retour'] = $date_retour;

        return $this->FetchSelectCustom($sql, $params);
    }

    // Miroir de carsDisponibles() pour un camion : pas de sous-filtre "limiterAGare"
    // (pas de liaison_car_trajet/programmer equivalente pour un camion -- meme choix
    // deja fait pour l'envoi de colis, Envoie_colis::getCamionsActifs() : un camion
    // est disponible compagnie entiere, pas scope par gare). Un camion deja loue sur
    // une periode qui chevauche celle demandee est exclu, meme logique que pour un car.
    public function camionsDisponibles($id_agence_depart, $date_depart, $date_retour)
    {
        $id_compagnie = $_SESSION['id_compagnie'];

        $sql = "SELECT DISTINCT camion.id_camion, camion.numero_camion, camion.matriculle
                FROM camion
                WHERE camion.id_compagnie = :id_compagnie AND camion.actif = 'on'
                AND camion.id_camion NOT IN (
                    SELECT lc.id_camion FROM location_car lc
                    WHERE lc.id_camion IS NOT NULL
                      AND lc.statut IN ('en_attente', 'valide')
                      AND lc.date_depart <= :date_retour AND lc.date_retour_prevu >= :date_depart
                )
                ORDER BY camion.numero_camion";

        return $this->FetchSelectCustom($sql, [
            ':id_compagnie' => $id_compagnie,
            ':date_depart' => $date_depart,
            ':date_retour' => $date_retour,
        ]);
    }

    public function FetchSelectCustom($query, $params = [])
    {
        $stmt = $this->connect()->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // Un Admin ne peut louer que les cars de sa propre compagnie (IDOR sinon)
    private function carAppartientCompagnie($id_car)
    {
        if (($_SESSION['droit'] ?? null) === 'super_admin') {
            return true;
        }
        $car = $this->FetchSelectWhere(
            "id_car",
            "car",
            "id_car = :id_car AND id_compagnie = :id_compagnie",
            [":id_car" => $id_car, ":id_compagnie" => $_SESSION['id_compagnie'] ?? null]
        );
        return (bool) $car;
    }

    // Miroir de carAppartientCompagnie() pour un camion.
    private function camionAppartientCompagnie($id_camion)
    {
        if (($_SESSION['droit'] ?? null) === 'super_admin') {
            return true;
        }
        $camion = $this->FetchSelectWhere(
            "id_camion",
            "camion",
            "id_camion = :id_camion AND id_compagnie = :id_compagnie",
            [":id_camion" => $id_camion, ":id_compagnie" => $_SESSION['id_compagnie'] ?? null]
        );
        return (bool) $camion;
    }

    public function saveLocation()
    {
        $id_compagnie = $_SESSION['id_compagnie'];
        $droit        = $_SESSION['droit'] ?? null;

        extract($_POST);

        // Le chef d'escale ne peut louer qu'au depart de sa propre gare, meme si le
        // formulaire est trafique (IDOR sinon, comme pour les depenses locales).
        if ($droit === 'chef_d_escale') {
            $id_agence_depart = $_SESSION['id_agence'];
        } elseif (empty($id_agence_depart)) {
            $this->set_flash("Veuillez choisir la gare de départ.", "danger");
            return false;
        }

        if (empty($destination) || trim($destination) === '') {
            $this->set_flash("Veuillez indiquer la destination.", "danger");
            return false;
        }

        // Un vehicule loue est soit un car, soit un camion (jamais les deux), meme
        // convention que Chauffeurs_car::saveChauffeur().
        $estCamion = isset($_POST['est_camion']) && $_POST['est_camion'] === '1';
        $id_camion = $estCamion ? ($_POST['id_camion'] ?? null) : null;
        $id_car    = $estCamion ? null : ($_POST['id_car'] ?? null);

        if ($estCamion ? empty($id_camion) : empty($id_car)) {
            $this->set_flash("Veuillez choisir un véhicule.", "danger");
            return false;
        }
        if (empty($nom_client) || empty($prenom_client) || empty($telephone_client)) {
            $this->set_flash("Les coordonnées du client (nom, prénom, téléphone) sont obligatoires.", "danger");
            return false;
        }
        if (empty($date_depart) || empty($date_retour_prevu)) {
            $this->set_flash("Veuillez indiquer les dates de départ et de retour prévu.", "danger");
            return false;
        }
        if ($date_retour_prevu < $date_depart) {
            $this->set_flash("La date de retour prévu ne peut pas être avant la date de départ.", "danger");
            return false;
        }
        if (!is_numeric($frais_location) || (float)$frais_location <= 0) {
            $this->set_flash("Le frais de location doit être un nombre positif.", "danger");
            return false;
        }

        if ($estCamion) {
            if (!$this->camionAppartientCompagnie($id_camion)) {
                $this->set_flash("Ce camion n'appartient pas à votre compagnie.", "danger");
                return false;
            }
        } else {
            if (!$this->carAppartientCompagnie($id_car)) {
                $this->set_flash("Ce car n'appartient pas à votre compagnie.", "danger");
                return false;
            }
        }

        // Re-verification serveur de la disponibilite du vehicule choisi : ne jamais faire
        // confiance a la liste proposee cote client (formulaire trafique, race condition
        // entre deux locations soumises en meme temps...).
        $aujourdhui = date('Y-m-d');
        if ($estCamion) {
            // Pas de limiterAGare pour un camion (cf. camionsDisponibles()).
            $disponibles = $this->camionsDisponibles($id_agence_depart, $date_depart, $date_retour_prevu);
            $idsDisponibles = array_map(fn($c) => (int)$c->id_camion, $disponibles);
            if (!in_array((int)$id_camion, $idsDisponibles, true)) {
                $this->set_flash("Ce camion n'est plus disponible sur la période demandée. Veuillez en choisir un autre.", "danger");
                return false;
            }
        } else {
            $limiterAGare = ($droit === 'chef_d_escale') && $date_depart === $aujourdhui;
            $disponibles = $this->carsDisponibles($id_agence_depart, $date_depart, $date_retour_prevu, $limiterAGare);
            $idsDisponibles = array_map(fn($c) => (int)$c->id_car, $disponibles);
            if (!in_array((int)$id_car, $idsDisponibles, true)) {
                $this->set_flash("Ce car n'est plus disponible sur la période demandée. Veuillez en choisir un autre.", "danger");
                return false;
            }
        }

        // Le check ci-dessus n'est pas suffisant seul : deux locations pour LE MEME vehicule sur
        // des periodes qui se chevauchent, soumises a quelques millisecondes d'intervalle,
        // peuvent toutes les deux le lire comme disponible avant qu'aucune n'ecrive. On
        // reverrouille ce vehicule precis (FOR UPDATE) et on rejoue le test de chevauchement dans
        // la transaction : la 2e requete, bloquee le temps de la 1ere, le retrouvera alors
        // pris et sera rejetee proprement au lieu de doubler la reservation.
        $pdo = $this->connect();
        $pdo->beginTransaction();
        try {
            if ($estCamion) {
                $pdo->prepare("SELECT id_camion FROM camion WHERE id_camion = :id_camion FOR UPDATE")
                    ->execute([':id_camion' => $id_camion]);

                $stmtConflit = $pdo->prepare(
                    "SELECT COUNT(*) FROM location_car
                     WHERE id_camion = :id_camion AND statut IN ('en_attente', 'valide')
                       AND date_depart <= :date_retour AND date_retour_prevu >= :date_depart"
                );
                $stmtConflit->execute([
                    ':id_camion' => $id_camion,
                    ':date_depart' => $date_depart,
                    ':date_retour' => $date_retour_prevu,
                ]);
            } else {
                $pdo->prepare("SELECT id_car FROM car WHERE id_car = :id_car FOR UPDATE")
                    ->execute([':id_car' => $id_car]);

                $stmtConflit = $pdo->prepare(
                    "SELECT COUNT(*) FROM location_car
                     WHERE id_car = :id_car AND statut IN ('en_attente', 'valide')
                       AND date_depart <= :date_retour AND date_retour_prevu >= :date_depart"
                );
                $stmtConflit->execute([
                    ':id_car' => $id_car,
                    ':date_depart' => $date_depart,
                    ':date_retour' => $date_retour_prevu,
                ]);
            }
            if ((int)$stmtConflit->fetchColumn() > 0) {
                $pdo->rollBack();
                $this->set_flash("Ce véhicule vient d'être réservé sur cette période par quelqu'un d'autre. Veuillez en choisir un autre.", "danger");
                return false;
            }
        } catch (Throwable $e) {
            $pdo->rollBack();
            $this->set_flash("Erreur lors de la vérification de disponibilité du véhicule.", "danger");
            return false;
        }

        $statut = in_array($droit, ['Admin', 'super_admin'], true) ? 'valide' : 'en_attente';

        // Meme logique a deux niveaux que Depense::saveDepense() : un chef d'escale credite
        // toujours SA PROPRE caisse individuelle du jour (jamais celle d'un collegue) ; un
        // Admin utilise la caisse de gare (ancien systeme) si elle est ouverte, sinon une
        // caisse individuelle ouverte pour cette gare aujourd'hui. La reference resolue ici
        // est stockee sur la location des sa creation (id_caisse / id_caisse_user), pour que
        // la validation ulterieure credite exactement la bonne caisse sans avoir a la
        // re-detecter (et donc sans risquer de dire "aucune caisse ouverte" a tort si elle a
        // ete ouverte apres coup, ou fermee entre-temps).
        $id_caisse = null;
        $id_caisse_user = null;

        if ($droit === 'chef_d_escale') {
            $caisseUser = $this->fetchOne(
                "SELECT id_caisse_user FROM caisse_utilisateur
                 WHERE id_utilisateur = :id_utilisateur AND date_service = CURDATE() AND statut = 'ouverte'
                 LIMIT 1",
                [':id_utilisateur' => $_SESSION['id_utilisateur']]
            );
            if (!$caisseUser) {
                $pdo->rollBack();
                $this->set_flash("Vous devez avoir votre propre caisse ouverte pour enregistrer une location.", "danger");
                return false;
            }
            $id_caisse_user = (int)$caisseUser['id_caisse_user'];
        } else {
            $caisse = $this->FetchSelectWhere(
                "id_caisse",
                "caisse",
                "id_agence = :id_agence AND status_caisse = 1",
                [":id_agence" => $id_agence_depart]
            );

            if ($caisse) {
                $id_caisse = $caisse->id_caisse;
            } else {
                $caisseUser = $this->fetchOne(
                    "SELECT id_caisse_user FROM caisse_utilisateur
                     WHERE id_agence = :id_agence AND date_service = CURDATE() AND statut = 'ouverte'
                     LIMIT 1",
                    [':id_agence' => $id_agence_depart]
                );
                if ($caisseUser) {
                    $id_caisse_user = (int)$caisseUser['id_caisse_user'];
                }
            }

            if (!$id_caisse && !$id_caisse_user) {
                $pdo->rollBack();
                $this->set_flash("Aucune caisse ouverte pour cette gare : impossible d'enregistrer la location.", "danger");
                return false;
            }
        }

        $stmtInsert = $pdo->prepare(
            "INSERT INTO location_car
                (id_compagnie, id_agence_depart, destination, id_car, id_camion, id_caisse, id_caisse_user, nom_client, prenom_client,
                 telephone_client, date_depart, date_retour_prevu, frais_location, statut, id_utilisateur)
             VALUES
                (:id_compagnie, :id_agence_depart, :destination, :id_car, :id_camion, :id_caisse, :id_caisse_user, :nom_client, :prenom_client,
                 :telephone_client, :date_depart, :date_retour_prevu, :frais_location, :statut, :id_utilisateur)"
        );
        $insertion = $stmtInsert->execute([
            ':id_compagnie'      => $id_compagnie,
            ':id_agence_depart'  => $id_agence_depart,
            ':destination'       => trim($destination),
            ':id_car'            => $id_car,
            ':id_camion'         => $id_camion,
            ':id_caisse'         => $id_caisse,
            ':id_caisse_user'    => $id_caisse_user,
            ':nom_client'        => trim($nom_client),
            ':prenom_client'     => trim($prenom_client),
            ':telephone_client'  => trim($telephone_client),
            ':date_depart'       => $date_depart,
            ':date_retour_prevu' => $date_retour_prevu,
            ':frais_location'    => $frais_location,
            ':statut'            => $statut,
            ':id_utilisateur'    => $_SESSION['id_utilisateur']
        ]);

        if (!$insertion) {
            $pdo->rollBack();
            $this->set_flash("Erreur lors de l'enregistrement de la location.", "danger");
            return false;
        }

        $pdo->commit();

        if ($statut === 'valide') {
            $this->crediterCaisse($id_caisse, $id_caisse_user, (float)$frais_location);
            $this->set_flash("Location enregistrée avec succès et créditée à la caisse.", "success");
        } else {
            $this->set_flash("Location enregistrée avec succès. Elle est en attente de validation par l'administrateur.", "success");
        }
        return true;
    }

    // Credite le montant a la caisse (de gare ou individuelle) determinee et stockee des
    // la creation de la location.
    private function crediterCaisse($id_caisse, $id_caisse_user, $montant)
    {
        if ($id_caisse) {
            $this->insertion_update_simples(
                "UPDATE caisse SET montant_location = montant_location + :montant WHERE id_caisse = :id_caisse",
                [':montant' => $montant, ':id_caisse' => $id_caisse]
            );
        } elseif ($id_caisse_user) {
            $this->insertion_update_simples(
                "UPDATE caisse_utilisateur SET montant_location = montant_location + :montant WHERE id_caisse_user = :id_caisse_user",
                [':montant' => $montant, ':id_caisse_user' => $id_caisse_user]
            );
        }
    }

    public function infoCompagnie(int $id): ?array
    {
        $sql = "SELECT id_compagnie, nom_compagnie AS nom, slogant, logo, libele
                FROM compagnie WHERE id_compagnie = :id";
        return $this->query($sql, [':id' => $id], true);
    }

    // Une seule location, pour la facture A4. Filtre par compagnie (IDOR) et, pour un
    // chef d'escale, par sa propre gare de depart (comme getLocations()).
    public function getById($id)
    {
        $id_compagnie = $_SESSION['id_compagnie'];
        $droit        = $_SESSION['droit'] ?? null;

        $condition = 'l.id_location = :id AND l.id_compagnie = :id_compagnie';
        $params    = [':id' => (int)$id, ':id_compagnie' => $id_compagnie];

        if ($droit === 'chef_d_escale') {
            $condition .= ' AND l.id_agence_depart = :id_agence';
            $params[':id_agence'] = $_SESSION['id_agence'];
        }

        // Meme discriminant calcule que Envoi_colis::liste_colis_envoyer() : id_car
        // renseigne -> car, sinon camion (jamais les deux, cf. saveLocation()).
        $rows = $this->FetchSelectWheres(
            "l.*, a.localite, a.numeroGare,
             CASE WHEN l.id_car IS NOT NULL THEN 'car' ELSE 'camion' END AS type_vehicule,
             COALESCE(c.numero_car, cam.numero_camion) AS numero_vehicule,
             COALESCE(c.matriculle, cam.matriculle) AS matricule_vehicule,
             c.numero_car, c.matriculle, u.utilisateurs AS agent, u.droit AS agent_droit,
             v.utilisateurs AS valide_par_nom",
            'location_car l
                LEFT JOIN agence a ON l.id_agence_depart = a.idAgence
                LEFT JOIN car c ON l.id_car = c.id_car
                LEFT JOIN camion cam ON l.id_camion = cam.id_camion
                LEFT JOIN utilisateur u ON l.id_utilisateur = u.idUser
                LEFT JOIN utilisateur v ON l.id_valide_par = v.idUser',
            $condition,
            $params
        );

        return $rows[0] ?? null;
    }

    // Liste des locations visibles selon le role : le chef d'escale ne voit que celles de
    // sa propre gare de depart, l'Admin voit tout.
    public function getLocations()
    {
        $id_compagnie = $_SESSION['id_compagnie'];
        $droit        = $_SESSION['droit'] ?? null;

        $condition = 'l.id_compagnie = :id_compagnie';
        $params    = ['id_compagnie' => $id_compagnie];

        if ($droit === 'chef_d_escale') {
            $condition .= ' AND l.id_agence_depart = :id_agence';
            $params['id_agence'] = $_SESSION['id_agence'];
        }

        return $this->FetchSelectWheres(
            "l.*, a.localite, a.numeroGare,
             CASE WHEN l.id_car IS NOT NULL THEN 'car' ELSE 'camion' END AS type_vehicule,
             COALESCE(c.numero_car, cam.numero_camion) AS numero_vehicule,
             COALESCE(c.matriculle, cam.matriculle) AS matricule_vehicule,
             c.numero_car, c.matriculle, u.utilisateurs AS agent",
            'location_car l
                LEFT JOIN agence a ON l.id_agence_depart = a.idAgence
                LEFT JOIN car c ON l.id_car = c.id_car
                LEFT JOIN camion cam ON l.id_camion = cam.id_camion
                LEFT JOIN utilisateur u ON l.id_utilisateur = u.idUser',
            $condition . ' ORDER BY l.date_depart DESC, l.id_location DESC',
            $params
        );
    }

    public function validerLocation($id)
    {
        $id = (int)$id;
        $id_compagnie = $_SESSION['id_compagnie'];

        $location = $this->FetchSelectWhere(
            "*",
            "location_car",
            "id_location = :id AND id_compagnie = :id_compagnie",
            [":id" => $id, ":id_compagnie" => $id_compagnie]
        );

        if (!$location) {
            $this->set_flash("Location introuvable.", "danger");
            return false;
        }
        if ($location->statut !== 'en_attente') {
            $this->set_flash("Cette location a déjà été traitée (validée ou rejetée).", "warning");
            return false;
        }

        // Compare-and-swap : sans "AND statut = 'en_attente'" ici, deux validations (ou une
        // validation + un rejet) concurrentes pourraient toutes les deux passer le test
        // ci-dessus avant qu'aucune n'ecrive, et crediterCaisse() serait appelee deux fois.
        $update = $this->insertion_update_simples(
            "UPDATE location_car SET statut = 'valide', id_valide_par = :id_valide_par
             WHERE id_location = :id AND statut = 'en_attente'",
            [":id" => $id, ":id_valide_par" => $_SESSION['id_utilisateur']]
        );
        if ($update->rowCount() === 0) {
            $this->set_flash("Cette location a déjà été traitée entre-temps par quelqu'un d'autre.", "warning");
            return false;
        }

        // La caisse (de gare ou individuelle) a deja ete determinee et stockee sur la
        // location au moment de sa creation par le chef d'escale (cf. saveLocation) : on
        // credite cette meme reference, sans la re-detecter a la validation.
        $this->crediterCaisse($location->id_caisse, $location->id_caisse_user, (float)$location->frais_location);

        $this->set_flash("Location validée avec succès et créditée à la caisse.", "success");
        return true;
    }

    public function rejeterLocation($id)
    {
        $id = (int)$id;
        $id_compagnie = $_SESSION['id_compagnie'];

        $location = $this->FetchSelectWhere(
            "*",
            "location_car",
            "id_location = :id AND id_compagnie = :id_compagnie",
            [":id" => $id, ":id_compagnie" => $id_compagnie]
        );

        if (!$location) {
            $this->set_flash("Location introuvable.", "danger");
            return false;
        }
        if ($location->statut !== 'en_attente') {
            $this->set_flash("Cette location a déjà été traitée.", "warning");
            return false;
        }

        // Meme garde que validerLocation() : empeche un rejet d'ecraser une location deja
        // validee (et donc deja creditee a la caisse) par une validation concurrente.
        $update = $this->insertion_update_simples(
            "UPDATE location_car SET statut = 'rejete' WHERE id_location = :id AND statut = 'en_attente'",
            [":id" => $id]
        );

        if ($update->rowCount() > 0) {
            $this->set_flash("Location rejetée avec succès.", "success");
            return true;
        }

        $this->set_flash("Cette location a déjà été traitée entre-temps par quelqu'un d'autre.", "warning");
        return false;
    }
}
