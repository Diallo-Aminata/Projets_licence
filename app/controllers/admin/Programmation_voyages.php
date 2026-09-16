<?php

class Programmation_voyages extends Controller
{

    public function __construct()
    {
        $this->requirePermission('Programme_programmation_voyage');
    }

    // Affiche la page avec la table, les horaires, etc.
    public function index()
    {
        $programmation_voyage = new Programmation_voyage();

        // Récupération des horaires
        $listehoraire = $programmation_voyage->getHoraires();

        // Récupération des programmations des cars
        $programmations = $programmation_voyage->getProgrammationCars();

        // Groupement des programmations par numéro de car

        $cars_destinations = [];
        foreach ($programmations as $ligne) {
            $cars_destinations[$ligne->numero_car][] = $ligne;
        }
        //         $cars_destinations = [];

        // foreach ($programmations as $ligne) {
        //     $id_car = $ligne->id_car;          // 👈 ID réel
        //     $numero_car = $ligne->numero_car;  // 👁️ affiché

        //     if (!isset($cars_destinations[$id_car])) {
        //         $cars_destinations[$id_car] = [
        //             'numero_car' => $numero_car,
        //             'destinations' => []
        //         ];
        //     }

        //     $cars_destinations[$id_car]['destinations'][] = $ligne;
        // }

        // Méthode pour traiter le formulaire de programmation
        // if (isset($_POST['programmer']) && !empty($_POST['select_car'])) {
        //     $model = new Programmation_voyage();
        //     $localite_user = $_SESSION['ville'] ?? 'non-defini';
        //     $date_enregistre = date('Ymd');

        //     foreach ($_POST['select_car'] as $index => $val) {
        //         $id_care = $_POST['id_care'][$index] ?? null;
        //         $id_horaire = $_POST['id_horaire'][$index] ?? null;
        //         $id_destination = $_POST['id_destination'][$index] ?? null;

        //         if ($id_care && $id_horaire && $id_destination) {
        //             $insert_result = $model->insertProgrammation($id_care, $id_horaire, $id_destination, $localite_user, $date_enregistre);
        //             if ($insert_result) {
        //                 $update_result = $model->updateCareStatus($id_care, $id_destination);
        //                 if (!$update_result) {
        //                     $model->set_flash("Erreur lors de la mise à jour du statut du car $id_care.", "danger");

        //                 }
        //             } else {
        //                 $model->set_flash("Erreur lors de l'insertion de la programmation pour le car $id_care.", "danger") ;

        //             }
        //         }
        //     }
        //    $model->set_flash("Programmation générée avec succès !", "success");


        // } 


        if (isset($_POST['programmer']) && !empty($_POST['select_car'])) {
            $model = new Programmation_voyage();
            $localite_user = $_SESSION['ville'] ?? 'non-defini';
            $date_enregistre = date('Y-m-d');

            $errors = [];  // tableau pour collecter les erreurs
            $programmations_reussies = 0;

            foreach ($_POST['select_car'] as $val) {
                $index = $val;
                $id_care = $_POST['id_care'][$index] ?? null;
                $id_horaire = $_POST['id_horaire'][$index] ?? null;
                $id_destination = $_POST['id_destination'][$index] ?? null;
                // Départ choisi dans le formulaire (Admin uniquement) ; pour un chef d'escale,
                // le modèle retombe sur sa propre gare de session.
                $id_depart = $_POST['id_depart'][$index] ?? null;
                // Gare précise (idAgence) correspondant à ce départ : nécessaire pour ne pas
                // mélanger deux gares d'une même ville sur le même créneau (revalidée côté
                // modèle, jamais faite confiance telle quelle).
                $id_agence_depart = $_POST['id_depart_agence'][$index] ?? null;
                // Gare précise (idAgence) de la destination, pour le même motif : sans elle,
                // deux gares de la même ville de destination seraient indiscernables.
                $id_agence_destination = $_POST['id_destination_agence'][$index] ?? null;

                // Une ligne cochée mais jamais renseignée (aucune destination choisie) n'est
                // pas une erreur de saisie : elle correspond à un car resté sélectionné sans
                // intention réelle (case "tout sélectionner", reste d'un "Reproduire hier"
                // partiel...). On l'ignore silencieusement plutôt que de bloquer les lignes
                // réellement remplies avec un message d'erreur trompeur.
                if (empty($id_destination)) {
                    continue;
                }

                if (!$id_care || !$id_horaire || !$id_agence_destination) {
                    $errors[] = "Veuillez remplir tous les champs pour la ligne choisie.";
                    continue; // passe à la ligne suivante sans insérer
                }

                if ($_SESSION['droit'] === 'Admin' && empty($id_depart)) {
                    $errors[] = "Veuillez choisir une destination pour renseigner le départ de la ligne choisie.";
                    continue;
                }

                $insert_result = $model->insertProgrammation($id_care, $id_horaire, $id_destination, $localite_user, $date_enregistre, $id_depart, $id_agence_depart, $id_agence_destination);
                if ($insert_result) {
                    $update_result = $model->updateCareStatus($id_care, $id_destination);
                    if (!$update_result) {
                        $errors[] = "Erreur lors de la mise à jour du statut du car $id_care.";
                    } else {
                        $programmations_reussies++;
                    }
                } else {
                    $errors[] = "Erreur lors de l'insertion de la programmation pour le car $id_care.";
                }
            }

            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $model->set_flash($error, "danger");
                }
            } elseif ($programmations_reussies > 0) {
                $model->set_flash("Programmation générée avec succès !", "info");
                header("Location: " . BASE_URL . "/admin/Programmation_voyages/index");
                exit;
            } else {
                $model->set_flash("Veuillez cocher au moins un car et choisir sa destination.", "danger");
            }
        }

        // Gestion de la validation d'arrivée
        if (isset($_POST['valider_arrivee']) && !empty($_POST['id_car_arrivee'])) {
            $id_car = $_POST['id_car_arrivee'];

            if ($programmation_voyage->validerArrivee($id_car)) {
                $programmation_voyage->set_flash("L'arrivée du car a été validée avec succès !", "success");
            } else {
                $programmation_voyage->set_flash("Erreur lors de la validation de l'arrivée.", "danger");
            }
            header("Location: " . BASE_URL . "/admin/Programmation_voyages/index");
            exit;
        }

        // Déblocage d'un car "fantôme" (En_transit_ sans decolle_le, cf. getCarsBloques()).
        if (isset($_POST['debloquer_arrive']) && !empty($_POST['id_programmation_bloque'])) {
            $programmation_voyage->debloquerCarArrive($_POST['id_programmation_bloque'], $_SESSION['id_compagnie']);
            header("Location: " . BASE_URL . "/admin/Programmation_voyages/index");
            exit;
        }
        if (isset($_POST['debloquer_jamais_parti']) && !empty($_POST['id_programmation_bloque'])) {
            $programmation_voyage->debloquerCarJamaisParti($_POST['id_programmation_bloque'], $_SESSION['id_compagnie']);
            header("Location: " . BASE_URL . "/admin/Programmation_voyages/index");
            exit;
        }

        // Récupération des cars en transit
        $cars_en_transit = $programmation_voyage->getCarsInTransit();

        // Cars bloqués (anomalie) : visible seulement pour Admin/super_admin.
        $cars_bloques = $programmation_voyage->getCarsBloques();

        // Pour chaque car en approche : numéro de gare de destination à afficher.
        foreach ($cars_en_transit as $car) {
            $destination = substr($car->status_car, 11);
            $prog = $programmation_voyage->getProgrammationActivePourCar($car->id_car, $destination);
            $car->numeroGareDestination = $prog->numeroGareDestination ?? null;
        }

        // Dernière programmation existante (pour pré-remplissage à la demande, cf. bouton "Reproduire").
        // Admin : toute la compagnie (le départ varie ligne par ligne). Chef d'escale : sa propre gare.
        // On reprend la DERNIÈRE date disponible (pas forcément hier) pour couvrir les jours sans
        // activité ou le tout début d'utilisation du système.
        $aujourdhui = date('Y-m-d');
        $localite_filtre = $_SESSION['droit'] === 'chef_d_escale' ? ($_SESSION['ville'] ?? null) : null;
        $derniere_date = $programmation_voyage->getDerniereDateProgrammation(
            $_SESSION['id_compagnie'],
            $aujourdhui,
            $localite_filtre
        );
        $programmation_veille = $derniere_date
            ? $programmation_voyage->getProgrammationParDate($_SESSION['id_compagnie'], $derniere_date, $localite_filtre)
            : [];

        // Tous les trajets de la compagnie : le select Destination de chaque car ne doit pas
        // se limiter aux seuls trajets deja affectes a CE car via "Affectation des cars".
        $tousLesTrajets = $programmation_voyage->getTousLesTrajets();

        // Envoi à la vue
        $this->view('admin/programmation_voyage', [
            'listehoraire' => $listehoraire,
            'cars_destinations' => $cars_destinations,
            'cars_en_transit' => $cars_en_transit,
            'cars_bloques' => $cars_bloques,
            'programmation_veille' => $programmation_veille,
            'derniere_date' => $derniere_date,
            'tousLesTrajets' => $tousLesTrajets
        ]);
    }

    public function liste_programmer_voyage()
    {
        // Récupérer les données des filières
        $programmation_voyage = new Programmation_voyage();
        $id_compagnie = $_SESSION['id_compagnie'];

        $listeProgrammer = $programmation_voyage->FetchSelectWheres(
            'pv.*,
     c.numero_car,
     c.nbr_place,
     c.nbr_place_reserve,
     (c.nbr_place - c.nbr_place_reserve) AS place_disponible,
     COALESCE(
       (SELECT aDest.numeroGare FROM agence aDest WHERE aDest.idAgence = pv.id_agence_destination),
       (SELECT aDest.numeroGare
          FROM programmer p
          INNER JOIN agence aDest ON p.idDestination = aDest.idAgence
          WHERE p.idDepart = pv.id_agence AND p.heureDepart = pv.id_horaire
            AND p.id_compagnie = pv.id_compagnie AND aDest.localite = pv.id_trajet
          LIMIT 1)
     ) AS numeroGareDestination',
            'programmation_voyage pv
     INNER JOIN car c ON pv.id_car_programmer = c.id_car',
            // Un voyage annulé (ex. après un transfert de passagers vers une autre gare) ne doit
            // plus apparaître dans la liste des départs à gérer aujourd'hui.
            "pv.id_compagnie = :id_compagnie AND pv.statut = 'active'",
            ['id_compagnie' => $id_compagnie]
        );


        $this->view('admin/programmer_voyage_journalier', [

            'listeProgrammer' => $listeProgrammer

        ]);
    }

    public function edit($id_programmation)
    {
        $programmation_voyage = new Programmation_voyage();

        // index() restreint déjà cette section à Admin/chef_d_escale : edit() (modification
        // d'une programmation de voyage) doit avoir le même contrôle.
        if (!in_array($_SESSION['droit'] ?? null, ['Admin', 'chef_d_escale', 'secretaire'], true)) {
            $programmation_voyage->set_flash("Accès refusé ou session invalide", "danger");
            header("Location: " . BASE_URL . "/admin/Programmation_voyages/index");
            exit;
        }

        $id_compagnie = $_SESSION['id_compagnie'];

        // Récupérer la programmation (filtrée par compagnie de session : empêche de modifier
        // la programmation d'une autre compagnie en changeant l'ID dans l'URL)
        $programmation = $programmation_voyage->FetchSelectWheres(
            'pv.*, c.numero_car, c.nbr_place, c.nbr_place_reserve',
            'programmation_voyage pv
         INNER JOIN car c ON pv.id_car_programmer = c.id_car',
            'pv.id_programmation = :id_programmation AND pv.id_compagnie = :id_compagnie',
            ['id_programmation' => $id_programmation, 'id_compagnie' => $id_compagnie]
        );

        if (empty($programmation)) {
            $programmation_voyage->set_flash("Programmation introuvable !", "danger");
            header("Location: " . BASE_URL . "/admin/Programmation_voyages/liste_programmer_voyage");
            exit;
        }

        // Récupérer les horaires disponibles
        $listehoraire = $programmation_voyage->FetchSelectWheres(
            '*',
            'horaire',
            "id_compagnie = :id_compagnie",
            [":id_compagnie" => $id_compagnie],
            '1=1'
        );

        // Récupérer les destinations réellement assignées à ce car, au départ de la
        // localité d'origine de cette programmation (pour proposer un changement cohérent).
        $destinations = $programmation_voyage->getDestinationsForCar(
            $programmation[0]->id_car_programmer,
            $programmation[0]->localite_user,
            $id_compagnie
        );

        $cars_destinations = [];
        $cars_destinations[$programmation[0]->numero_car] = $destinations;

        // Traitement de la soumission du formulaire de modification
        if (isset($_POST['modifier'])) {
            $id_horaire = $_POST['id_horaire'][0] ?? null;
            $id_destination = $_POST['id_destination'][0] ?? null;
            $id_care = $_POST['id_care'][0] ?? null;
            $action = $_POST['action_reservations'] ?? null;
            $id_car_remplacement = $_POST['id_car_remplacement'] ?? null;

            if (!$id_horaire || !$id_destination || !$id_care) {
                $programmation_voyage->set_flash("Veuillez remplir tous les champs.", "danger");
                header("Location: " . BASE_URL . "/admin/Programmation_voyages/liste_programmer_voyage");
                exit;
            }

            $resultat = $programmation_voyage->updateProgrammation(
                $id_programmation,
                $id_horaire,
                $id_destination,
                $action,
                $id_car_remplacement ?: null
            );

            // Des billets existent déjà sur l'ancien créneau : on redemande le formulaire avec
            // le choix à faire (suivre / autre car), sans rien enregistrer pour l'instant.
            if (is_array($resultat) && !empty($resultat['needs_choice'])) {
                $carsRemplacement = $programmation_voyage->getCarsDisponiblesPourRemplacement(
                    $id_compagnie,
                    $programmation[0]->id_car_programmer
                );

                $this->view('admin/programmer_voyage_modifier', [
                    'programmation' => $programmation[0],
                    'cars_destinations' => $cars_destinations,
                    'listehoraire' => $listehoraire,
                    'besoin_choix' => $resultat,
                    'cars_remplacement' => $carsRemplacement,
                    'horaire_soumis' => $id_horaire,
                    'destination_soumise' => $id_destination
                ]);
                return;
            }

            if (is_array($resultat) && !empty($resultat['error'])) {
                $messages = [
                    'introuvable' => "Programmation introuvable.",
                    'horaire_invalide' => "Cette heure n'existe pas pour ce trajet (départ/destination).",
                    'car_remplacement_requis' => "Veuillez choisir un car de remplacement pour l'ancien créneau.",
                    'car_remplacement_invalide' => "Ce car de remplacement n'est pas disponible."
                ];
                $programmation_voyage->set_flash($messages[$resultat['error']] ?? "Erreur lors de la modification.", "danger");
                header("Location: " . BASE_URL . "/admin/Programmation_voyages/edit/" . $id_programmation);
                exit;
            }

            if ($resultat) {
                $programmation_voyage->updateCareStatus($id_care, $id_destination);
                $programmation_voyage->set_flash("La programmation a été modifiée avec succès !", "success");
            } else {
                $programmation_voyage->set_flash("Erreur lors de la modification de la programmation.", "danger");
            }

            header("Location: " . BASE_URL . "/admin/Programmation_voyages/liste_programmer_voyage");
            exit;
        }

        $this->view('admin/programmer_voyage_modifier', [
            'programmation' => $programmation[0],
            'cars_destinations' => $cars_destinations,
            'listehoraire' => $listehoraire
        ]);
    }

    // Bouton "Désactiver" de liste_programmer_voyage() : annule un voyage du jour pas
    // encore décollé et sans place vendue (cf. Programmation_voyage::desactiverProgrammation()).
    public function desactiver($id_programmation)
    {
        $programmation_voyage = new Programmation_voyage();

        // Même restriction que edit() : accès en écriture réservé à ces rôles, même si
        // la permission Programme_programmation_voyage venait à être assignée à un autre.
        if (!in_array($_SESSION['droit'] ?? null, ['Admin', 'chef_d_escale', 'secretaire'], true)) {
            $programmation_voyage->set_flash("Accès refusé ou session invalide", "danger");
            header("Location: " . BASE_URL . "/admin/Programmation_voyages/liste_programmer_voyage");
            exit;
        }

        $programmation_voyage->desactiverProgrammation($id_programmation);
        header("Location: " . BASE_URL . "/admin/Programmation_voyages/liste_programmer_voyage");
        exit;
    }
}
