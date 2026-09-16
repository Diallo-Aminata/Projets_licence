<?php


class Programmation_voyage extends Model
{

    public function getHoraires()
    {
        $id_compagnie = $_SESSION['id_compagnie'];
        return $this->FetchSelectWhereS(
            "*",
            "horaire",
            "id_compagnie = :id_compagnie",
            [":id_compagnie" => $id_compagnie]
        );
    }

    // public function getProgrammationCars()
    // {
    //     $select = "liaison_car_trajet.*, trajet.*, car.*";
    //     $fromAndWhere = "liaison_car_trajet 
    //     INNER JOIN trajet ON liaison_car_trajet.id_trajets = trajet.idTrajet
    //     INNER JOIN car ON liaison_car_trajet.id_car = car.id_car";

    //     $where = "";
    //     $params = [];

    //     if (isset($_SESSION['droit'])) {
    //         if ($_SESSION['droit'] === 'Admin') {
    //             $where = " WHERE car.status_car IS NULL";
    //         } elseif ($_SESSION['droit'] === 'chef_d_escale' && isset($_SESSION['ville'])) {
    //             $where = " WHERE car.status_car = :ville";
    //             $params[':ville'] = $_SESSION['ville'];
    //         }
    //     }


    //     // Ajout du ORDER BY à la fin
    //     $fromAndWhere .= " $where ORDER BY car.numero_car, trajet.depart";

    //     return $this->SelectAllDatas($select, $fromAndWhere, $params);
    // }

    public function getProgrammationCars()
    {
        // $select = "liaison_car_trajet.*, programmer.*, car.*";
        // $fromAndWhere = "liaison_car_trajet 
        // INNER JOIN programmer ON liaison_car_trajet.id_trajets = programmer.idProgrammer
        // INNER JOIN car ON liaison_car_trajet.id_car = car.id_car";
        $select = "liaison_car_trajet.*,
           programmer.*,
           car.*,
           a1.localite AS departLocalite,
           a2.localite AS destinationLocalite,
           a1.numeroGare AS numeroGareDepart,
           a2.numeroGare AS numeroGareDestination";

$fromAndWhere = "liaison_car_trajet
    INNER JOIN programmer ON liaison_car_trajet.id_trajets = programmer.idProgrammer
    INNER JOIN car ON liaison_car_trajet.id_car = car.id_car
    LEFT JOIN agence a1 ON programmer.idDepart = a1.idAgence
    LEFT JOIN agence a2 ON programmer.idDestination = a2.idAgence";


        $where = "";
        $params = [];

        if (isset($_SESSION['droit'], $_SESSION['id_compagnie'])) {
            if ($_SESSION['droit'] === 'Admin') {
                // Admin : ne voit que les cars JAMAIS ENCORE programmés (status_car NULL, cars
                // tout juste ajoutés à la flotte — voir Cars_chauffeur::insertCar(), qui ne
                // renseigne pas status_car à la création). C'est l'Admin qui fait la toute
                // première affectation (ville de départ) d'un car ; une fois cette première
                // programmation faite, status_car devient une vraie ville et c'est ensuite au
                // chef d'escale de cette ville de reprogrammer le car (cf. branche ci-dessous).
                $where = " WHERE car.status_car IS NULL AND car.id_compagnie = :compagnie";
                $params[':compagnie'] = $_SESSION['id_compagnie'];
            } elseif ($_SESSION['droit'] === 'chef_d_escale' && isset($_SESSION['ville'])) {
                // Chef d'escale : status_car = ville, id_compagnie = leur compagnie,
                // ET seuls les trajets dont le départ correspond à la ville actuelle du car
                // sont proposés (le car ne peut pas "partir" d'une ville où il n'est pas).
                $where = " WHERE car.status_car = :ville AND car.id_compagnie = :compagnie AND a1.localite = car.status_car";
                $params[':ville'] = $_SESSION['ville'];
                $params[':compagnie'] = $_SESSION['id_compagnie'];
            }
        }

        $fromAndWhere .= " $where ORDER BY car.numero_car, programmer.idDepart";

        return $this->SelectAllDatas($select, $fromAndWhere, $params);
    }

    // Tous les trajets de la compagnie (toutes gares confondues), independamment de leur
    // affectation a un car particulier (liaison_car_trajet) : utilise pour le select
    // Destination de "Nouvelle programmation de voyage", qui ne doit pas se limiter aux
    // seuls trajets deja affectes a CE car precis via "Affectation des cars".
    public function getTousLesTrajets()
    {
        $select = "programmer.idDepart, programmer.idDestination, programmer.heureDepart,
                   a1.localite AS departLocalite, a1.numeroGare AS numeroGareDepart,
                   a2.localite AS destinationLocalite, a2.numeroGare AS numeroGareDestination";

        $fromAndWhere = "programmer
            LEFT JOIN agence a1 ON programmer.idDepart = a1.idAgence
            LEFT JOIN agence a2 ON programmer.idDestination = a2.idAgence
            WHERE programmer.id_compagnie = :compagnie
            ORDER BY a1.localite, a2.localite, programmer.heureDepart";

        return $this->SelectAllDatas($select, $fromAndWhere, [':compagnie' => $_SESSION['id_compagnie'] ?? null]);
    }

    // Insertion programmation avec ta méthode d'insertion
    // public function insertProgrammation($id_care, $id_horaire, $id_destination, $localite_user, $date_enregistre)
    // {
    //     $localite_user = $_SESSION['ville'];
    //     $id_compagnie = $_SESSION["id_compagnie"];
    //     $jourVoyage = $_POST['jourVoyage'];
    //     // Empêcher que la destination soit la même que la localité de l'utilisateur
    //     if ($id_destination == $localite_user) {
    //         return false; // ou tu peux retourner un message ou une exception
    //     }

    //     $insert = "INSERT INTO programmation_voyage (
    //                 id_car_programmer, id_horaire, id_trajet, localite_user, date_enregistre, id_compagnie
    //            ) VALUES (
    //                 :id_car_programmer, :id_horaire, :id_trajet, :localite_user, :date_enregistre, :id_compagnie
    //            )";

    //     $params = [
    //         ':id_car_programmer' => $id_care,
    //         ':id_horaire' => $id_horaire,
    //         ':id_trajet' => $id_destination,
    //         ':localite_user' => $localite_user,
    //         ':date_enregistre' => $jourVoyage,
    //         ':id_compagnie' => $id_compagnie
    //     ];

    //     return $this->insertion_update_simples($insert, $params);
    // }

    public function insertProgrammation($id_care, $id_horaire, $id_destination, $localite_user, $date_enregistre, $id_depart = null, $id_agence_depart = null, $id_agence_destination = null)
    {
        // Admin (plusieurs gares) : départ choisi dans le formulaire.
        // chef_d_escale (une seule gare) : toujours sa propre localité de session.
        $localite_user = $id_depart ?: $_SESSION['ville'];
        $id_compagnie = $_SESSION["id_compagnie"];

        if ($id_destination == $localite_user) {
            return false;
        }

        // Gare précise de départ (idAgence) : indispensable pour ne pas mélanger deux gares
        // d'une même ville sur le même créneau (ex. "Segou" Gare I et Gare II toutes deux
        // programmées vers Bamako à la même heure). Voir ajout_id_agence_programmation_voyage.sql.
        if ($id_agence_depart) {
            // Admin : revalider que la gare postée appartient bien à sa compagnie et
            // correspond à la localité choisie, jamais faire confiance à l'ID posté tel quel.
            $agenceDepart = $this->fetchOne(
                "SELECT idAgence, localite FROM agence WHERE idAgence = :id AND id_compagnie = :ic LIMIT 1",
                [':id' => $id_agence_depart, ':ic' => $id_compagnie]
            );
            if (!$agenceDepart || $agenceDepart['localite'] !== $localite_user) {
                return false;
            }
            $id_agence = $agenceDepart['idAgence'];
        } else {
            $id_agence = $_SESSION['id_agence'] ?? null;
        }

        if (!$id_agence) {
            return false;
        }

        // Gare précise de destination (idAgence) : obligatoire, pour le même motif que le
        // départ — sans elle, deux gares de la même ville de destination (ex. "Bamako" Gare I
        // et Gare II) seraient indiscernables. Revalidée côté modèle, jamais faite confiance
        // telle quelle. Voir ajout_id_agence_destination_programmation_voyage.sql.
        if (!$id_agence_destination) {
            return false;
        }
        $agenceDestination = $this->fetchOne(
            "SELECT idAgence, localite FROM agence WHERE idAgence = :id AND id_compagnie = :ic LIMIT 1",
            [':id' => $id_agence_destination, ':ic' => $id_compagnie]
        );
        if (!$agenceDestination || $agenceDestination['localite'] !== $id_destination) {
            return false;
        }

        // L'heure choisie doit correspondre à un trajet réellement programmé (table "programmer")
        // pour CETTE gare précise et CETTE gare de destination précise : sinon on pourrait
        // enregistrer un voyage à une heure qui n'existe pas pour ce trajet, ou mélanger deux
        // gares de la même ville.
        $trajetValide = $this->fetchOne(
            "SELECT p.idProgrammer
             FROM programmer p
             WHERE p.idDepart = :id_agence AND p.idDestination = :id_agence_destination AND p.heureDepart = :heure AND p.id_compagnie = :id_compagnie
             LIMIT 1",
            [':id_agence' => $id_agence, ':id_agence_destination' => $id_agence_destination, ':heure' => $id_horaire, ':id_compagnie' => $id_compagnie]
        );

        if (!$trajetValide) {
            return false;
        }

        // Le formulaire ne propose déjà que les cars disponibles (getProgrammationCars()),
        // mais rien ne revérifiait côté serveur qu'un id_care soumis directement l'est
        // toujours : un car déjà "En_transit_*" (parti sur un trajet en cours) pouvait être
        // reprogrammé une seconde fois, écrasant le compteur de places du trajet en cours.
        // Un car peut légitimement faire plusieurs tournées par jour, mais seulement après
        // que son arrivée ait été validée (validerArrivee() le rend de nouveau disponible).
        //
        // Verrouillé (FOR UPDATE) le temps de la transaction : sans ça, deux programmations
        // du MEME car vers deux trajets différents, soumises à quelques millisecondes
        // d'intervalle, pourraient toutes les deux lire "disponible" avant qu'aucune n'écrive,
        // affectant un seul car physique à deux trajets simultanés.
        $pdo = $this->connect();
        $pdo->beginTransaction();
        try {
            $stmtCar = $pdo->prepare(
                "SELECT status_car FROM car WHERE id_car = :id_car AND id_compagnie = :id_compagnie FOR UPDATE"
            );
            $stmtCar->execute([':id_car' => $id_care, ':id_compagnie' => $id_compagnie]);
            $car = $stmtCar->fetch(PDO::FETCH_ASSOC);

            if (!$car) {
                $pdo->rollBack();
                return false;
            }

            $statusCar = $car['status_car'];
            if ($statusCar !== null && strpos($statusCar, 'En_transit_') === 0) {
                $pdo->rollBack();
                return false;
            }

            // chef_d_escale (pas d'id_depart fourni) : le car doit être physiquement dans sa
            // gare. Admin (id_depart fourni) : peut réaffecter un car présent ailleurs dans sa
            // compagnie (choix déjà assumé dans getProgrammationCars() pour ce rôle).
            if ($id_depart === null && $statusCar !== $localite_user) {
                $pdo->rollBack();
                return false;
            }

            // Revérifie qu'aucune programmation n'existe déjà pour ce car sur CE créneau
            // exact (même horaire, même jour) : le check de statut ci-dessus couvre "car en
            // route", mais pas deux programmations pour le même créneau avant tout départ.
            $stmtDoublon = $pdo->prepare(
                "SELECT COUNT(*) FROM programmation_voyage
                 WHERE id_car_programmer = :id_car AND id_horaire = :h AND date_enregistre = :d AND id_compagnie = :ic"
            );
            $stmtDoublon->execute([
                ':id_car' => $id_care, ':h' => $id_horaire, ':d' => $date_enregistre, ':ic' => $id_compagnie,
            ]);
            if ((int)$stmtDoublon->fetchColumn() > 0) {
                $pdo->rollBack();
                return false;
            }

            // 1. Insertion dans programmation_voyage
            $stmtInsert = $pdo->prepare(
                "INSERT INTO programmation_voyage (
                    id_car_programmer, id_horaire, id_trajet, localite_user, id_agence, id_agence_destination, date_enregistre, id_compagnie
               ) VALUES (
                    :id_car_programmer, :id_horaire, :id_trajet, :localite_user, :id_agence, :id_agence_destination, :date_enregistre, :id_compagnie
               )"
            );
            $result = $stmtInsert->execute([
                ':id_car_programmer' => $id_care,
                ':id_horaire' => $id_horaire,
                ':id_trajet' => $id_destination,
                ':localite_user' => $localite_user,
                ':id_agence' => $id_agence,
                ':id_agence_destination' => $id_agence_destination,
                ':date_enregistre' => $date_enregistre,
                ':id_compagnie' => $id_compagnie
            ]);

            // 2. Recalculer (jamais remettre à 0 aveuglément) le nombre de places déjà réservées
            //    sur ce créneau exact : une reprogrammation ne doit jamais faire "disparaître" des
            //    billets déjà vendus (aujourd'hui ou demain), sinon les places redeviennent
            //    disponibles alors que des tickets existent déjà dessus (risque de survente).
            $placesDejaVendues = $this->countPlacesVendues($id_horaire, $id_destination, $localite_user, $date_enregistre, $id_compagnie, $id_agence);

            $pdo->prepare("UPDATE car SET nbr_place_reserve = :n WHERE id_car = :id_car")
                ->execute([':n' => $placesDejaVendues, ':id_car' => $id_care]);

            $pdo->commit();
            return $result;
        } catch (Throwable $e) {
            $pdo->rollBack();
            return false;
        }
    }

    // Toutes les valeurs de destinationId qui correspondent à un créneau donné : la destination
    // finale du trajet, plus le nom de chaque escale (les billets vendus vers une escale sont
    // enregistrés avec le nom de l'escale comme destinationId, pas la destination finale).
    // Public : réutilisé par Transfert_gare pour retrouver les billets d'un créneau
    // (destination finale + escales) lors d'un transfert de passagers entre gares.
    public function getDestinationsPourCreneau($id_horaire, $id_destination, $localite_user, $id_compagnie, $id_agence = null)
    {
        $destinations = [$id_destination];

        // Gare précise si disponible (id_agence) : évite de mélanger deux gares d'une même
        // ville. Repli sur la ville seule pour les lignes historiques pas encore rattachées
        // à une gare précise (voir ajout_id_agence_programmation_voyage.sql).
        if ($id_agence) {
            $prog = $this->fetchOne(
                "SELECT p.idProgrammer
                 FROM programmer p
                 LEFT JOIN agence a2 ON p.idDestination = a2.idAgence
                 WHERE p.idDepart = :id_agence AND a2.localite = :dest AND p.heureDepart = :heure AND p.id_compagnie = :id_compagnie
                 LIMIT 1",
                [':id_agence' => $id_agence, ':dest' => $id_destination, ':heure' => $id_horaire, ':id_compagnie' => $id_compagnie]
            );
        } else {
            $prog = $this->fetchOne(
                "SELECT p.idProgrammer
                 FROM programmer p
                 LEFT JOIN agence a1 ON p.idDepart = a1.idAgence
                 LEFT JOIN agence a2 ON p.idDestination = a2.idAgence
                 WHERE a1.localite = :dep AND a2.localite = :dest AND p.heureDepart = :heure AND p.id_compagnie = :id_compagnie
                 LIMIT 1",
                [':dep' => $localite_user, ':dest' => $id_destination, ':heure' => $id_horaire, ':id_compagnie' => $id_compagnie]
            );
        }

        if ($prog) {
            $escales = $this->fetchAll(
                "SELECT e.escales FROM ligneTrajet lt
                 JOIN escale e ON e.id_escale = lt.id_escales
                 WHERE lt.id_trajets = :progId AND lt.type_trajet = 'programmer'",
                [':progId' => $prog['idProgrammer']]
            );
            foreach ($escales as $e) {
                $destinations[] = $e['escales'];
            }
        }

        return $destinations;
    }

    // Compte les places déjà vendues (billets) pour un créneau exact (départ/destination/heure/date/compagnie).
    // Inclut les billets vendus vers une escale du trajet, car ceux-ci sont enregistrés avec le nom
    // de l'escale comme destinationId plutôt que la destination finale.
    public function countPlacesVendues($id_horaire, $id_destination, $localite_user, $jourVoyage, $id_compagnie, $id_agence = null)
    {
        $destinations = $this->getDestinationsPourCreneau($id_horaire, $id_destination, $localite_user, $id_compagnie, $id_agence);

        $placeholders = implode(',', array_fill(0, count($destinations), '?'));
        $sql = "SELECT COALESCE(SUM(nombrePassages), 0) AS total
                FROM billets
                WHERE jourVoyage = ? AND Heur_departs = ? AND departId = ? AND id_compagnie = ?
                  AND destinationId IN ($placeholders)";

        $params = array_merge([$jourVoyage, $id_horaire, $localite_user, $id_compagnie], $destinations);

        // Gare précise si disponible : sans ceci, deux gares d'une même ville sur le même
        // créneau compteraient les billets l'une de l'autre (num_gare distingue les gares
        // qui partagent une localité, cf. ajout_id_agence_programmation_voyage.sql).
        if ($id_agence) {
            $agence = $this->fetchOne("SELECT numeroGare FROM agence WHERE idAgence = :id", [':id' => $id_agence]);
            if ($agence && $agence['numeroGare'] !== null) {
                $sql .= " AND num_gare = ?";
                $params[] = $agence['numeroGare'];
            }
        }

        $stmt = $this->connect()->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }



    public function updateCareStatus($id_car, $id_destination)
    {
        $update = "UPDATE car SET status_car = :id_trajet WHERE id_car = :id_car";
        $params = [
            ':id_trajet' => 'En_transit_' . $id_destination,
            ':id_car' => $id_car
        ];
        return $this->insertion_update_simples($update, $params);
    }

    // Dernière date (strictement avant $avant_date) où une programmation a été enregistrée.
    // Permet de reproduire "la dernière programmation" même s'il n'y en a pas eu hier
    // (jour sans activité, tout début d'utilisation du système, etc.).
    public function getDerniereDateProgrammation($id_compagnie, $avant_date, $localite_user = null)
    {
        $where = "id_compagnie = :id_compagnie AND date_enregistre < :avant_date";
        $params = [
            ':id_compagnie' => $id_compagnie,
            ':avant_date' => $avant_date
        ];

        if ($localite_user) {
            $where .= " AND localite_user = :localite_user";
            $params[':localite_user'] = $localite_user;
        }

        $sql = "SELECT MAX(date_enregistre) AS derniere_date FROM programmation_voyage WHERE $where";

        $stmt = $this->connect()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchColumn() ?: null;
    }

    // Dernière programmation enregistrée pour une date donnée (typiquement la veille),
    // utilisée pour pré-remplir le formulaire et éviter de tout ressaisir chaque jour.
    // Indexé par id_car_programmer pour un accès direct depuis la vue.
    public function getProgrammationParDate($id_compagnie, $date, $localite_user = null)
    {
        $where = "pv.id_compagnie = :id_compagnie AND pv.date_enregistre = :date";
        $params = [
            ':id_compagnie' => $id_compagnie,
            ':date' => $date
        ];

        if ($localite_user) {
            $where .= " AND pv.localite_user = :localite_user";
            $params[':localite_user'] = $localite_user;
        }

        $sql = "SELECT pv.id_car_programmer, pv.id_horaire, pv.id_trajet, pv.localite_user
                FROM programmation_voyage pv
                WHERE $where";

        $stmt = $this->connect()->prepare($sql);
        $stmt->execute($params);

        $result = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[$row['id_car_programmer']] = [
                'id_horaire'    => $row['id_horaire'],
                'id_trajet'     => $row['id_trajet'],
                'localite_user' => $row['localite_user']
            ];
        }
        return $result;
    }

    public function getCarsInTransit()
    {
        $select = "car.*";
        $fromAndWhere = "car";
        // "En transit" affiche ici seulement si le bus a REELLEMENT decolle (decolle_le rempli
        // sur sa derniere programmation active vers cette destination — cf. decollerCar()),
        // et non des la simple programmation du voyage : sinon "Valider" l'arrivee devenait
        // possible bien avant que l'embarquement n'ait meme commence.
        $decolleReel = "(
            SELECT pv.decolle_le FROM programmation_voyage pv
            WHERE pv.id_car_programmer = car.id_car
              AND pv.id_trajet = SUBSTRING(car.status_car, 12)
              AND pv.statut = 'active'
            ORDER BY pv.id_programmation DESC LIMIT 1
        ) IS NOT NULL";
        $where = " WHERE status_car LIKE 'En_transit_%' AND $decolleReel";
        $params = [];

        if (isset($_SESSION['droit'], $_SESSION['id_compagnie'])) {
            if ($_SESSION['droit'] === 'Admin') {
                $where .= " AND id_compagnie = :compagnie";
                $params[':compagnie'] = $_SESSION['id_compagnie'];
            } elseif ($_SESSION['droit'] === 'chef_d_escale' && isset($_SESSION['ville'])) {
                $where = " WHERE status_car = :ville AND id_compagnie = :compagnie AND $decolleReel";
                $params[':ville'] = 'En_transit_' . $_SESSION['ville'];
                $params[':compagnie'] = $_SESSION['id_compagnie'];
            }
        }

        $fromAndWhere .= $where . " ORDER BY numero_car ASC";
        return $this->SelectAllDatas($select, $fromAndWhere, $params);
    }

    // Cars programmes aujourd'hui vers la gare du chef d'escale mais pas encore reellement
    // decolles (decolle_le IS NULL) : complement de getCarsInTransit() pour que la page
    // d'accueil montre, en un coup d'oeil, tout ce qui est en approche vers sa gare — deja
    // en route (getCarsInTransit) ou juste programme (ici).
    public function getCarsProgrammesVersMaGare()
    {
        if (($_SESSION['droit'] ?? null) !== 'chef_d_escale' || empty($_SESSION['ville'])) {
            return [];
        }

        $sql = "SELECT car.id_car, car.numero_car, car.nbr_place, pv.id_horaire, pv.localite_user
                FROM programmation_voyage pv
                JOIN car ON car.id_car = pv.id_car_programmer
                WHERE pv.id_trajet = :ville
                  AND pv.id_compagnie = :compagnie
                  AND pv.statut = 'active'
                  AND pv.decolle_le IS NULL
                  AND pv.date_enregistre = :today
                ORDER BY pv.id_horaire ASC";

        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([
            ':ville'     => $_SESSION['ville'],
            ':compagnie' => $_SESSION['id_compagnie'],
            ':today'     => date('Y-m-d'),
        ]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // Marque le depart reel d'un voyage programme : appele depuis l'ecran Embarquement une
    // fois tous les passagers traites (embarques ou annules — cf. Liste_du_jour::busDejaDecolle(),
    // qui s'appuie sur ce champ pour bloquer ensuite tout embarquement/annulation sur ce trajet).
    //
    // Ne touche pas a car.status_car ('En_transit_...', deja positionne des la programmation du
    // voyage pour empecher qu'un meme car soit programme deux fois) : ce champ reste le suivi de
    // "reservation" du car. decolle_le est le suivi, precis a CE trajet, du depart physique reel —
    // c'est lui qui conditionne desormais getCarsInTransit()/validerArrivee() (l'arrivee ne peut
    // etre validee qu'apres un vrai decollage) et le blocage des transferts entre gares
    // (Transfert_gare.php, qui autorise a nouveau un transfert tant que le bus n'a pas decolle).
    public function decollerCar($id_programmation, $id_compagnie)
    {
      if (!csrf_verify()) {
        $this->set_flash("Session expirée, veuillez réessayer.", "danger");
        return false;
      }

      $prog = $this->fetchOne(
        "SELECT * FROM programmation_voyage WHERE id_programmation = :id AND id_compagnie = :id_compagnie",
        [':id' => $id_programmation, ':id_compagnie' => $id_compagnie]
      );
      if (!$prog) {
        $this->set_flash("Programmation introuvable.", "danger");
        return false;
      }
      if (!empty($prog['decolle_le'])) {
        $this->set_flash("Ce bus a déjà décollé.", "warning");
        return false;
      }

      // IDOR/portee : un chef d'escale ne peut decoller qu'un car partant de sa propre gare.
      if (($_SESSION['droit'] ?? null) === 'chef_d_escale' && $prog['localite_user'] !== ($_SESSION['ville'] ?? null)) {
        $this->set_flash("Ce car ne part pas de votre gare.", "danger");
        return false;
      }

      // status_billets = '' (pas seulement != 'annule') : un billet avec un report ou une
      // annulation deja DEMANDE(e) ('report_demande'/'report_transmis'/'annulation_demandee'/
      // 'annulation_en_cours') est hors des mains de l'agent -- il a disparu de la liste
      // d'embarquement (cf. Liste_du_jour::getBilletsPourEmbarquement(), meme condition) en
      // attendant sa validation par l'Admin ailleurs, donc il ne doit plus non plus bloquer le
      // decollage : sinon le bus reste bloque indefiniment sur un passager que l'agent ne
      // peut plus traiter depuis cet ecran (source d'un bug confirme le 2026-09-16 : un
      // report deja transmis empechait "Faire decoller" sans que rien ne soit actionnable).
      $restants = $this->fetchOne(
        "SELECT COUNT(*) AS n FROM billets
         WHERE jourVoyage = :jour AND Heur_departs = :heure AND destinationId = :dest
           AND departId = :depart AND id_compagnie = :id_compagnie
           AND (statut_embarquement IS NULL OR statut_embarquement != 'embarque')
           AND (status_billets IS NULL OR status_billets = '')",
        [
          ':jour' => $prog['date_enregistre'], ':heure' => $prog['id_horaire'],
          ':dest' => $prog['id_trajet'], ':depart' => $prog['localite_user'],
          ':id_compagnie' => $id_compagnie,
        ]
      );
      $nbRestants = (int)($restants['n'] ?? 0);
      if ($nbRestants > 0) {
        $this->set_flash("$nbRestants passager(s) non traité(s) (ni embarqué, ni annulé) : impossible de faire décoller le bus.", "warning");
        return false;
      }

      date_default_timezone_set('Africa/Bamako');
      $stmt = $this->insertion_update_simples(
        "UPDATE programmation_voyage SET decolle_le = :maintenant, decolle_par = :par
         WHERE id_programmation = :id AND id_compagnie = :id_compagnie AND decolle_le IS NULL",
        [
          ':maintenant' => date('Y-m-d H:i:s'),
          ':par' => $_SESSION['id_utilisateur'] ?? null,
          ':id' => $id_programmation,
          ':id_compagnie' => $id_compagnie,
        ]
      );

      if ($stmt->rowCount() > 0) {
        $this->set_flash("Bus décollé avec ses passagers.", "success");
        return true;
      }
      $this->set_flash("Ce bus a déjà été traité entre-temps par quelqu'un d'autre.", "warning");
      return false;
    }

    public function validerArrivee($id_car)
    {
        $car = $this->FetchSelectWheres("status_car", "car", "id_car = :id_car", [":id_car" => $id_car]);
        if (!empty($car)) {
            $status = $car[0]->status_car;
            if (strpos($status, 'En_transit_') === 0) {
                $ville = substr($status, 11);
                // Garde-fou (defense en profondeur) : meme si getCarsInTransit() ne liste plus
                // ce car normalement, on revalide ici que le bus a reellement decolle avant de
                // le "faire arriver" — sans ca, un POST direct pourrait valider l'arrivee d'un
                // bus qui n'a jamais quitte sa gare.
                $prog = $this->getProgrammationActivePourCar($id_car, $ville);
                if (empty($prog->decolle_le)) {
                    $this->set_flash("Ce bus n'a pas encore décollé : impossible de valider son arrivée.", "warning");
                    return false;
                }
                $update = "UPDATE car SET status_car = :ville WHERE id_car = :id_car";
                return $this->insertion_update_simples($update, [':ville' => $ville, ':id_car' => $id_car]);
            }
        }
        return false;
    }

    // Dernière programmation ACTIVE enregistrée pour ce car vers cette destination : sert à
    // retrouver le numéro de gare de destination à afficher dans la liste des cars en transit,
    // et à vérifier si le bus a réellement décollé (decolle_le) avant de valider son arrivée.
    public function getProgrammationActivePourCar($id_car, $destination)
    {
        $sql = "SELECT pv.id_programmation, pv.date_enregistre, pv.id_horaire, pv.decolle_le, pv.localite_user,
                       a.numeroGare AS numeroGareDestination
                FROM programmation_voyage pv
                LEFT JOIN agence a ON a.idAgence = pv.id_agence_destination
                WHERE pv.id_car_programmer = :id_car AND pv.id_trajet = :destination AND pv.statut = 'active'
                ORDER BY pv.date_enregistre DESC, pv.id_programmation DESC
                LIMIT 1";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([':id_car' => $id_car, ':destination' => $destination]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: null;
    }

    // Etat courant de TOUS les cars de la compagnie (disponible a une gare, en transit avec
    // ou sans decollage reel enregistre, ou anomalie sans programmation active correspondante)
    // — vue d'ensemble pour l'ecran "Etat de la flotte". Contrairement a getCarsInTransit()/
    // getCarsBloques(), qui ne montrent chacun qu'un sous-ensemble filtre, celle-ci renvoie
    // une ligne par car (LEFT JOIN) pour que meme les cars sans transit en cours apparaissent.
    // Reserve a Admin/super_admin/PDG (PDG en lecture seule) : vue de supervision globale.
    public function getEtatFlotte()
    {
        if (!in_array($_SESSION['droit'] ?? null, ['Admin', 'super_admin', 'PDG'], true)) {
            return [];
        }

        $select = "c.id_car, c.numero_car, c.matriculle, c.nbr_place, c.status_car,
                   pv.id_programmation, pv.decolle_le, pv.date_enregistre, pv.id_horaire,
                   pv.localite_user AS origine, pv.id_trajet AS destination,
                   aOrig.numeroGare AS numeroGareDepart,
                   aDest.numeroGare AS numeroGareDestination";
        $fromAndWhere = "car c
            LEFT JOIN programmation_voyage pv
                ON pv.id_car_programmer = c.id_car
               AND pv.statut = 'active'
               AND c.status_car = CONCAT('En_transit_', pv.id_trajet)
            LEFT JOIN agence aOrig ON aOrig.idAgence = pv.id_agence
            LEFT JOIN agence aDest ON aDest.idAgence = pv.id_agence_destination
            WHERE c.id_compagnie = :id_compagnie
            ORDER BY c.numero_car";

        return $this->SelectAllDatas($select, $fromAndWhere, [':id_compagnie' => $_SESSION['id_compagnie'] ?? null]);
    }

    // Cars "fantomes" : status_car indique un transit ('En_transit_XXX') mais aucun
    // decollage n'a jamais ete enregistre sur leur programmation active correspondante
    // (typiquement des voyages crees avant l'ajout de decolle_le, ou une anomalie
    // operationnelle). Ni disponibles (getProgrammationCars() les exclut), ni visibles
    // comme "en approche" (getCarsInTransit() exige decolle_le) : invisibles partout tant
    // que personne ne les debloque manuellement. Reserve a Admin/super_admin — c'est une
    // correction d'etat incoherent, pas un flux operationnel courant.
    public function getCarsBloques()
    {
        if (!in_array($_SESSION['droit'] ?? null, ['Admin', 'super_admin'], true)) {
            return [];
        }

        return $this->fetchAll(
            "SELECT c.id_car, c.numero_car, c.status_car,
                    pv.id_programmation, pv.date_enregistre, pv.id_horaire,
                    pv.id_trajet AS destination, pv.localite_user AS origine
             FROM car c
             JOIN programmation_voyage pv
               ON pv.id_car_programmer = c.id_car
              AND pv.id_trajet = SUBSTRING(c.status_car, 12)
              AND pv.statut = 'active'
             WHERE c.status_car LIKE 'En\\_transit\\_%'
               AND pv.decolle_le IS NULL
               AND c.id_compagnie = :id_compagnie
             ORDER BY c.numero_car",
            [':id_compagnie' => $_SESSION['id_compagnie'] ?? null]
        );
    }

    // Debloque un car fantome dont le voyage est en realite deja arrive a destination
    // (juste jamais enregistre comme tel) : enregistre coup sur coup le decollage (a
    // l'instant de la correction, faute de connaitre l'heure reelle) et l'arrivee, comme
    // l'aurait fait le flux normal decollerCar() + validerArrivee().
    public function debloquerCarArrive($id_programmation, $id_compagnie)
    {
        if (!csrf_verify()) {
            $this->set_flash("Session expirée, veuillez réessayer.", "danger");
            return false;
        }
        if (!in_array($_SESSION['droit'] ?? null, ['Admin', 'super_admin'], true)) {
            $this->set_flash("Accès refusé.", "danger");
            return false;
        }

        $prog = $this->fetchOne(
            "SELECT * FROM programmation_voyage WHERE id_programmation = :id AND id_compagnie = :ic AND statut = 'active'",
            [':id' => $id_programmation, ':ic' => $id_compagnie]
        );
        if (!$prog) {
            $this->set_flash("Programmation introuvable.", "danger");
            return false;
        }

        $car = $this->fetchOne(
            "SELECT id_car, status_car FROM car WHERE id_car = :id AND id_compagnie = :ic",
            [':id' => $prog['id_car_programmer'], ':ic' => $id_compagnie]
        );
        if (!$car || $car['status_car'] !== 'En_transit_' . $prog['id_trajet']) {
            $this->set_flash("Ce car n'est plus dans l'état bloqué attendu.", "warning");
            return false;
        }

        date_default_timezone_set('Africa/Bamako');
        $pdo = $this->connect();
        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                "UPDATE programmation_voyage SET decolle_le = :maintenant, decolle_par = :par
                 WHERE id_programmation = :id AND id_compagnie = :ic AND decolle_le IS NULL"
            )->execute([
                ':maintenant' => date('Y-m-d H:i:s'),
                ':par' => $_SESSION['id_utilisateur'] ?? null,
                ':id' => $id_programmation,
                ':ic' => $id_compagnie,
            ]);

            $pdo->prepare("UPDATE car SET status_car = :dest WHERE id_car = :id_car")
                ->execute([':dest' => $prog['id_trajet'], ':id_car' => $prog['id_car_programmer']]);

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            $this->set_flash("Erreur lors du déblocage.", "danger");
            return false;
        }

        $this->set_flash("Car débloqué : marqué arrivé à destination.", "success");
        return true;
    }

    // Debloque un car fantome dont le voyage n'a en realite jamais eu lieu (le car n'a
    // jamais quitte sa gare). Le voyage est annule (meme semantique que le statut pose par
    // Transfert_gare pour un voyage sans depart reel) et le car redevient disponible a son
    // origine, compteur de places reserve remis a zero (mirroir de Transfert_gare::
    // transfererPassagers(), etape 8, pour un voyage qui ne part finalement pas).
    public function debloquerCarJamaisParti($id_programmation, $id_compagnie)
    {
        if (!csrf_verify()) {
            $this->set_flash("Session expirée, veuillez réessayer.", "danger");
            return false;
        }
        if (!in_array($_SESSION['droit'] ?? null, ['Admin', 'super_admin'], true)) {
            $this->set_flash("Accès refusé.", "danger");
            return false;
        }

        $prog = $this->fetchOne(
            "SELECT * FROM programmation_voyage WHERE id_programmation = :id AND id_compagnie = :ic AND statut = 'active'",
            [':id' => $id_programmation, ':ic' => $id_compagnie]
        );
        if (!$prog) {
            $this->set_flash("Programmation introuvable.", "danger");
            return false;
        }
        if (!empty($prog['decolle_le'])) {
            $this->set_flash("Ce bus a déjà décollé : impossible de le remettre à sa gare de départ.", "warning");
            return false;
        }

        $car = $this->fetchOne(
            "SELECT id_car, status_car FROM car WHERE id_car = :id AND id_compagnie = :ic",
            [':id' => $prog['id_car_programmer'], ':ic' => $id_compagnie]
        );
        if (!$car || $car['status_car'] !== 'En_transit_' . $prog['id_trajet']) {
            $this->set_flash("Ce car n'est plus dans l'état bloqué attendu.", "warning");
            return false;
        }

        $pdo = $this->connect();
        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                "UPDATE programmation_voyage SET statut = 'annulee' WHERE id_programmation = :id AND id_compagnie = :ic"
            )->execute([':id' => $id_programmation, ':ic' => $id_compagnie]);

            $pdo->prepare("UPDATE car SET status_car = :origine, nbr_place_reserve = 0 WHERE id_car = :id_car")
                ->execute([':origine' => $prog['localite_user'], ':id_car' => $prog['id_car_programmer']]);

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            $this->set_flash("Erreur lors du déblocage.", "danger");
            return false;
        }

        $this->set_flash("Car débloqué : remis disponible à sa gare de départ.", "success");
        return true;
    }

    // Desactive (annule) une programmation du jour qui n'a pas encore decolle -- bouton
    // "Desactiver" de liste_programmer_voyage(), present dans l'UI depuis l'origine mais
    // jamais cable a aucune logique jusqu'ici. Meme mecanique que debloquerCarJamaisParti()
    // (annule + libere le car), mais ici c'est une annulation VOLONTAIRE d'un voyage sain,
    // pas la reparation d'une anomalie -- donc refusee des qu'une place a deja ete vendue,
    // pour ne jamais faire disparaitre un voyage sous des passagers deja enregistres.
    public function desactiverProgrammation($id_programmation)
    {
        $id_compagnie = $_SESSION['id_compagnie'];

        $prog = $this->fetchOne(
            "SELECT id_programmation, id_car_programmer, localite_user, statut, decolle_le
             FROM programmation_voyage WHERE id_programmation = :id AND id_compagnie = :ic",
            [':id' => $id_programmation, ':ic' => $id_compagnie]
        );

        if (!$prog) {
            $this->set_flash("Programmation introuvable.", "danger");
            return false;
        }
        if ($prog['statut'] !== 'active') {
            $this->set_flash("Cette programmation est déjà annulée.", "warning");
            return false;
        }
        if (!empty($prog['decolle_le'])) {
            $this->set_flash("Ce car a déjà décollé : impossible de désactiver ce voyage.", "warning");
            return false;
        }

        $pdo = $this->connect();
        $pdo->beginTransaction();
        try {
            // Reverification sous verrou juste avant d'ecrire : une vente de billet
            // concurrente entre le check ci-dessus et cette transaction ne doit jamais
            // pouvoir etre ecrasee par une desactivation qui la croirait encore a zero.
            $stmtCar = $pdo->prepare("SELECT nbr_place_reserve FROM car WHERE id_car = :id_car FOR UPDATE");
            $stmtCar->execute([':id_car' => $prog['id_car_programmer']]);
            $car = $stmtCar->fetch(PDO::FETCH_ASSOC);

            if (!$car || (int)$car['nbr_place_reserve'] > 0) {
                $pdo->rollBack();
                $this->set_flash("Impossible de désactiver : des places ont déjà été vendues sur ce voyage.", "danger");
                return false;
            }

            $stmtMaj = $pdo->prepare(
                "UPDATE programmation_voyage SET statut = 'annulee'
                 WHERE id_programmation = :id AND id_compagnie = :ic AND statut = 'active'"
            );
            $stmtMaj->execute([':id' => $id_programmation, ':ic' => $id_compagnie]);

            if ($stmtMaj->rowCount() === 0) {
                $pdo->rollBack();
                $this->set_flash("Cette programmation vient d'être modifiée par quelqu'un d'autre. Veuillez réessayer.", "danger");
                return false;
            }

            $pdo->prepare("UPDATE car SET status_car = :origine, nbr_place_reserve = 0 WHERE id_car = :id_car")
                ->execute([':origine' => $prog['localite_user'], ':id_car' => $prog['id_car_programmer']]);

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            $this->set_flash("Erreur lors de la désactivation.", "danger");
            return false;
        }

        $this->set_flash("Voyage désactivé : le car est de nouveau disponible.", "success");
        return true;
    }

    public function getProgrammationById($id)
    {
        return $this->FetchSelectWhereS(
            "*",
            "programmation_voyage",
            "id_programmation = :id_programmation",
            [":id_programmation" => $id]
        );
    }

    // Destinations valides pour un car donné, au départ d'une localité donnée
    // (les trajets réellement assignés à ce car via "Affectation des cars").
    public function getDestinationsForCar($id_car, $localite_depart, $id_compagnie)
    {
        $select = "programmer.idProgrammer, programmer.heureDepart, a1.localite AS departLocalite, a2.localite AS destinationLocalite";
        $from = "liaison_car_trajet
            INNER JOIN programmer ON liaison_car_trajet.id_trajets = programmer.idProgrammer
            INNER JOIN agence a1 ON programmer.idDepart = a1.idAgence
            INNER JOIN agence a2 ON programmer.idDestination = a2.idAgence";
        $where = "liaison_car_trajet.id_car = :id_car
            AND a1.localite = :localite_depart
            AND liaison_car_trajet.id_compagnie = :id_compagnie";

        return $this->FetchSelectWheres($select, $from, $where, [
            ':id_car' => $id_car,
            ':localite_depart' => $localite_depart,
            ':id_compagnie' => $id_compagnie
        ]);
    }

    // Cars de la compagnie utilisables comme remplacement sur un créneau libéré : pas en
    // transit (un car déjà parti ne peut pas être affecté à un second trajet en même temps).
    public function getCarsDisponiblesPourRemplacement($id_compagnie, $id_car_a_exclure)
    {
        return $this->fetchAll(
            "SELECT id_car, numero_car FROM car
             WHERE id_compagnie = :id_compagnie
               AND id_car != :id_car_a_exclure
               AND (status_car IS NULL OR status_car NOT LIKE 'En\\_transit\\_%')
             ORDER BY numero_car",
            [':id_compagnie' => $id_compagnie, ':id_car_a_exclure' => $id_car_a_exclure]
        );
    }

    // Modifie l'heure/destination d'une programmation existante.
    //
    // S'il existe déjà des billets vendus sur l'ancien créneau (ancienne heure/destination),
    // la modification ne peut pas se faire silencieusement : ces clients ont acheté un billet
    // pour un départ précis, et changer le créneau du car sans rien faire les abandonnerait.
    // Deux issues possibles, au choix de l'utilisateur (résolu par $action) :
    //   - 'suivre'     : les billets déjà vendus suivent le car vers la nouvelle heure (uniquement
    //                    si la destination ne change pas : on ne réachemine jamais silencieusement
    //                    des clients vers une autre ville).
    //   - 'nouveau_car': l'ancien créneau (heure/destination d'origine) est repris par un autre
    //                    car choisi par l'utilisateur, pendant que celui-ci part sur le nouveau créneau.
    // Sans $action, si des réservations existent, on retourne ce qu'il faut pour proposer le choix
    // à l'utilisateur au lieu d'enregistrer quoi que ce soit.
    public function updateProgrammation($id_programmation, $id_horaire, $id_destination, $action = null, $id_car_remplacement = null)
    {
        $prog = $this->fetchOne(
            "SELECT id_car_programmer, id_horaire, id_trajet, localite_user, id_agence, id_agence_destination, date_enregistre, id_compagnie
             FROM programmation_voyage WHERE id_programmation = :id",
            [':id' => $id_programmation]
        );
        if (!$prog) {
            return ['error' => 'introuvable'];
        }

        $id_compagnie   = $prog['id_compagnie'];
        $localite_user  = $prog['localite_user'];
        $id_agence      = $prog['id_agence'];

        // La nouvelle heure doit correspondre à un trajet réellement programmé (même règle que pour
        // une nouvelle programmation) : jamais une heure qui n'existe pas pour ce départ/cette destination.
        // Gare précise si disponible (id_agence) : repli sur la ville seule pour les lignes
        // historiques pas encore rattachées à une gare précise.
        if ($id_agence) {
            $trajetValide = $this->fetchOne(
                "SELECT p.idProgrammer, p.idDestination
                 FROM programmer p
                 LEFT JOIN agence a2 ON p.idDestination = a2.idAgence
                 WHERE p.idDepart = :id_agence AND a2.localite = :dest AND p.heureDepart = :heure AND p.id_compagnie = :id_compagnie
                 LIMIT 1",
                [':id_agence' => $id_agence, ':dest' => $id_destination, ':heure' => $id_horaire, ':id_compagnie' => $id_compagnie]
            );
        } else {
            $trajetValide = $this->fetchOne(
                "SELECT p.idProgrammer, p.idDestination
                 FROM programmer p
                 LEFT JOIN agence a1 ON p.idDepart = a1.idAgence
                 LEFT JOIN agence a2 ON p.idDestination = a2.idAgence
                 WHERE a1.localite = :dep AND a2.localite = :dest AND p.heureDepart = :heure AND p.id_compagnie = :id_compagnie
                 LIMIT 1",
                [':dep' => $localite_user, ':dest' => $id_destination, ':heure' => $id_horaire, ':id_compagnie' => $id_compagnie]
            );
        }
        if (!$trajetValide) {
            return ['error' => 'horaire_invalide'];
        }
        // Gare précise de la NOUVELLE destination (résolue via le trajet fixe qui vient d'être
        // validé ci-dessus), pour garder id_agence_destination cohérent après une modification.
        $nouvelIdAgenceDestination = $trajetValide['idDestination'];

        $memeCreneau = ($id_horaire === $prog['id_horaire'] && $id_destination === $prog['id_trajet']);

        if (!$memeCreneau) {
            $dejaReserve = $this->countPlacesVendues(
                $prog['id_horaire'],
                $prog['id_trajet'],
                $localite_user,
                $prog['date_enregistre'],
                $id_compagnie,
                $id_agence
            );

            if ($dejaReserve > 0) {
                $destinationChange = $id_destination !== $prog['id_trajet'];

                if ($action === null || ($destinationChange && $action !== 'nouveau_car')) {
                    return ['needs_choice' => true, 'count' => $dejaReserve, 'destination_change' => $destinationChange];
                }

                if ($action === 'nouveau_car') {
                    if (!$id_car_remplacement) {
                        return ['error' => 'car_remplacement_requis'];
                    }
                    // Réutilise la logique d'insertion existante (vérifie que le car est libre,
                    // recalcule les places déjà vendues) pour faire reprendre l'ancien créneau
                    // par le car de remplacement choisi.
                    $ok = $this->insertProgrammation(
                        $id_car_remplacement,
                        $prog['id_horaire'],
                        $prog['id_trajet'],
                        null,
                        $prog['date_enregistre'],
                        $localite_user,
                        $id_agence,
                        $prog['id_agence_destination']
                    );
                    if (!$ok) {
                        return ['error' => 'car_remplacement_invalide'];
                    }
                } elseif ($action === 'suivre') {
                    // Les billets déjà vendus (destination finale + escales de l'ancien trajet)
                    // suivent le car vers la nouvelle heure.
                    $destinationsAncien = $this->getDestinationsPourCreneau(
                        $prog['id_horaire'],
                        $prog['id_trajet'],
                        $localite_user,
                        $id_compagnie,
                        $id_agence
                    );
                    $placeholders = implode(',', array_fill(0, count($destinationsAncien), '?'));
                    $stmt = $this->connect()->prepare(
                        "UPDATE billets SET Heur_departs = ?
                         WHERE departId = ? AND Heur_departs = ? AND jourVoyage = ? AND id_compagnie = ?
                           AND destinationId IN ($placeholders)"
                    );
                    $stmt->execute(array_merge(
                        [$id_horaire, $localite_user, $prog['id_horaire'], $prog['date_enregistre'], $id_compagnie],
                        $destinationsAncien
                    ));
                }
            }
        }

        $update = "UPDATE programmation_voyage
            SET id_horaire = :id_horaire, id_trajet = :id_trajet, id_agence_destination = :id_agence_destination
            WHERE id_programmation = :id_programmation";

        $result = $this->insertion_update_simples($update, [
            ':id_horaire' => $id_horaire,
            ':id_trajet' => $id_destination,
            ':id_agence_destination' => $nouvelIdAgenceDestination,
            ':id_programmation' => $id_programmation
        ]);

        // Le car d'origine sert maintenant le NOUVEAU créneau : son compteur de places réservées
        // doit refléter ce nouveau créneau, pas l'ancien (sinon il resterait avec un décompte qui
        // ne correspond plus à rien, faussant la capacité disponible affichée).
        $placesActuelles = $this->countPlacesVendues($id_horaire, $id_destination, $localite_user, $prog['date_enregistre'], $id_compagnie, $id_agence);
        $this->insertion_update_simples(
            "UPDATE car SET nbr_place_reserve = :n WHERE id_car = :id_car",
            [':n' => $placesActuelles, ':id_car' => $prog['id_car_programmer']]
        );

        return $result;
    }
}
