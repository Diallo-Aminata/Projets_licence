 <?php
    class Programmer_voyage extends Model
    {

        // Le formulaire permet de cocher plusieurs heures de départ à la fois (au lieu
        // d'en choisir une seule) : le même itinéraire/escales/tarifs saisis une fois sont
        // réutilisés pour programmer un voyage par heure cochée, RDV calculé automatiquement
        // (heure de départ - 45 min) pour chacune. Une heure déjà programmée pour ce même
        // trajet est ignorée (pas de doublon) plutôt que de faire échouer tout l'envoi.
        public function saveProgrammer()
        {
            $errors = [];
            extract($_POST);
            $id_compagnie = $_SESSION["id_compagnie"];

            // Le chef d'escale ne peut créer un programme qu'au départ de sa propre gare
            // (protection côté serveur en plus du filtrage du menu déroulant).
            if (($_SESSION['droit'] ?? null) === 'chef_d_escale' && (string)$idDepart !== (string)($_SESSION['id_agence'] ?? '')) {
                $errors[] = "Vous ne pouvez créer un programme qu'au départ de votre propre gare.";
            }

            // Vérification de cohérence départ/destination
            if ($idDepart == $idDestination) {
                $errors[] = "Impossible d'enregistrer ce trajet : départ et destination identiques.";
            }

            // Vérification qu'on n'enregistre pas un voyage interne (départ et destination dans la même localité)
            $departAgence = $this->FetchSelectWhere("localite", "agence", "idAgence = :id", [":id" => $idDepart]);
            $destinationAgence = $this->FetchSelectWhere("localite", "agence", "idAgence = :id", [":id" => $idDestination]);
            if ($departAgence && $destinationAgence && $departAgence->localite === $destinationAgence->localite) {
                $errors[] = "Impossible d'enregistrer ce trajet : le départ et la destination sont dans la même localité (voyage interne).";
            }

            $heureDeparts = $_POST['heureDepart'] ?? [];
            if (!is_array($heureDeparts)) {
                $heureDeparts = [$heureDeparts];
            }
            $heureDeparts = array_values(array_unique(array_filter(
                $heureDeparts,
                fn($h) => trim((string) $h) !== ''
            )));
            if (empty($heureDeparts)) {
                $errors[] = "Veuillez cocher au moins une heure de départ.";
            }

            if (!empty($errors)) {
                $errorsHtml = implode("<br>", array_map('htmlspecialchars', $errors));
                $this->set_swal("Erreurs détectées", $errorsHtml, "warning", "#ffc107");
                return $errors;
            }

            $idEscales = !empty($_POST['idEscale']) && is_array($_POST['idEscale']) ? $_POST['idEscale'] : [];
            $prixEscales = $_POST['prix_escale'] ?? [];

            $nbAjoutes = 0;
            $dejaExistants = [];

            foreach ($heureDeparts as $heureDepart) {
                // Un même trajet (départ/destination/heure) déjà programmé n'est pas dupliqué.
                $existe = $this->FetchSelectWhere(
                    "idProgrammer",
                    "programmer",
                    "idDepart = :idDepart AND idDestination = :idDestination AND heureDepart = :heureDepart AND id_compagnie = :id_compagnie",
                    [
                        ":idDepart" => $idDepart,
                        ":idDestination" => $idDestination,
                        ":heureDepart" => $heureDepart,
                        ":id_compagnie" => $id_compagnie
                    ]
                );
                if ($existe) {
                    $dejaExistants[] = $heureDepart;
                    continue;
                }

                $rdv = $this->calculerRdv($heureDepart);

                $id_trajet = $this->insertion_update_simple(
                    "INSERT INTO programmer (idDepart, idDestination, heureDepart, rdv, prix, id_compagnie)
                     VALUES(:idDepart, :idDestination, :heureDepart, :rdv, :prix, :id_compagnie)",
                    [
                        ":idDepart" => $idDepart,
                        ":idDestination" => $idDestination,
                        ":heureDepart" => $heureDepart,
                        ":rdv" => $rdv,
                        ":prix" => $prix,
                        ":id_compagnie" => $id_compagnie
                    ]
                );

                if (!$id_trajet) {
                    $errors[] = "Échec de l'enregistrement du trajet pour $heureDepart.";
                    continue;
                }

                foreach ($idEscales as $escale) {
                    $prixEscale = isset($prixEscales[$escale]) ? (float) $prixEscales[$escale] : 0;
                    $this->insertion_update_simple(
                        "INSERT INTO ligneTrajet (id_escales, id_trajets, type_trajet, prix_escale)
                         VALUES(:id_escales, :id_trajets, 'programmer', :prix_escale)",
                        [
                            ":id_escales" => $escale,
                            ":id_trajets" => $id_trajet,
                            ":prix_escale" => $prixEscale
                        ]
                    );
                }

                // Crée automatiquement le trajet retour (destination -> depart) s'il n'existe pas déjà,
                // pour qu'une ligne soit toujours disponible dans les deux sens, pour cette heure.
                $this->ensureReverseProgrammer(
                    $idDepart,
                    $idDestination,
                    $heureDepart,
                    $rdv,
                    $prix,
                    $id_compagnie,
                    $idEscales,
                    $prixEscales
                );

                $nbAjoutes++;
            }

            if ($nbAjoutes > 0) {
                $message = $nbAjoutes > 1
                    ? "$nbAjoutes voyages programmés avec succès (aller-retour créé automatiquement pour chacun)."
                    : "Le programme a été ajouté avec succès (aller-retour créé automatiquement).";
                if (!empty($dejaExistants)) {
                    $message .= " Déjà programmé, donc ignoré pour : " . implode(', ', $dejaExistants) . ".";
                }
                $this->set_swal(
                    "🕒 Programme enregistré !",
                    $message,
                    "success",
                    "#0d6efd",
                    BASE_URL . "/admin/Programmer_voyages/add_programmer"
                );
            } elseif (!empty($dejaExistants)) {
                $this->set_swal(
                    "Déjà programmé",
                    "Ce trajet est déjà programmé pour : " . implode(', ', $dejaExistants) . ".",
                    "warning",
                    "#ffc107"
                );
            } elseif (!empty($errors)) {
                $errorsHtml = implode("<br>", array_map('htmlspecialchars', $errors));
                $this->set_swal("Erreurs détectées", $errorsHtml, "warning", "#ffc107");
            }

            return $errors;
        }

        // RDV = heure de départ moins 45 minutes (même règle que l'ancien calcul en JS,
        // maintenant appliquée par heure côté serveur puisqu'on peut en cocher plusieurs).
        private function calculerRdv(string $heureDepart): string
        {
            $parts = array_map('intval', explode(':', $heureDepart));
            $heures = $parts[0] ?? 0;
            $minutes = $parts[1] ?? 0;

            $minutes -= 45;
            if ($minutes < 0) {
                $minutes += 60;
                $heures -= 1;
                if ($heures < 0) {
                    $heures += 24;
                }
            }

            return sprintf('%02d:%02d', $heures, $minutes);
        }

        // Garantit qu'un trajet et son sens inverse existent toujours ensemble,
        // pour qu'un car ne se retrouve jamais bloqué sans destination valide au retour.
        //
        // Le contrôle d'existence porte aussi sur heureDepart, pas seulement sur le couple
        // départ/destination : sinon, avec plusieurs horaires programmés dans le même envoi
        // (voir saveProgrammer), un seul trajet retour aurait été créé au lieu d'un par
        // horaire, cassant la correspondance "un départ par horaire" côté retour.
        private function ensureReverseProgrammer($idDepart, $idDestination, $heureDepart, $rdv, $prix, $id_compagnie, $idEscales = [], $prixEscales = [])
        {
            $existingReverse = $this->FetchSelectWhere(
                "idProgrammer",
                "programmer",
                "idDepart = :idDepart AND idDestination = :idDestination AND heureDepart = :heureDepart AND id_compagnie = :id_compagnie",
                [
                    ":idDepart" => $idDestination,
                    ":idDestination" => $idDepart,
                    ":heureDepart" => $heureDepart,
                    ":id_compagnie" => $id_compagnie
                ]
            );

            if ($existingReverse) {
                return $existingReverse->idProgrammer;
            }

            $id_reverse = $this->insertion_update_simple(
                "INSERT INTO programmer (idDepart, idDestination, heureDepart, rdv, prix, id_compagnie)
                 VALUES(:idDepart, :idDestination, :heureDepart, :rdv, :prix, :id_compagnie)",
                [
                    ":idDepart" => $idDestination,
                    ":idDestination" => $idDepart,
                    ":heureDepart" => $heureDepart,
                    ":rdv" => $rdv,
                    ":prix" => $prix,
                    ":id_compagnie" => $id_compagnie
                ]
            );

            if ($id_reverse) {
                foreach ($idEscales as $escale) {
                    $prixEscale = isset($prixEscales[$escale]) ? (float) $prixEscales[$escale] : 0;
                    $this->insertion_update_simple(
                        "INSERT INTO ligneTrajet (id_escales, id_trajets, type_trajet, prix_escale)
                         VALUES(:id_escales, :id_trajets, 'programmer', :prix_escale)",
                        [
                            ":id_escales" => $escale,
                            ":id_trajets" => $id_reverse,
                            ":prix_escale" => $prixEscale
                        ]
                    );
                }
            }

            return $id_reverse;
        }

        public function FetchSelectCustom($query, $params = [])
        {
            $stmt = $this->connect()->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        }


        public function editPrix($data)
        {
            $req = "UPDATE programmer 
            SET prix = :prix 
            WHERE idProgrammer = :idProgrammer";

            $params = [
                ":prix" => $data['prix'],
                ":idProgrammer" => $data['idProgrammer'],
            ];

            $modification = $this->insertion_update_simples($req, $params);
            if ($modification == true) {
                $this->set_flash("Modification faite avec succès", "primary");
            }
        }

        // Empêche de changer l'heure d'un trajet vers une heure déjà utilisée par un AUTRE
        // trajet identique (même départ/destination) : sans ce contrôle, on pouvait créer un
        // doublon simplement en éditant l'heure, en contournant la vérification déjà en
        // place à la création (voir saveProgrammer()). Retourne 'doublon' dans ce cas.
        //
        // Retourne aussi 'billets_existants' si des billets (aujourd'hui/futur) ou une
        // programmation active du jour utilisent déjà l'ANCIENNE heure de ce trajet :
        // heureDepart/id_horaire/Heur_departs sont chacun une simple copie de texte, sans
        // lien permanent entre programmer/programmation_voyage/billets (cf. saveProgrammer()) --
        // changer l'heure ici les désynchroniserait silencieusement (billets déjà vendus
        // et car déjà programmé restant sur l'heure d'avant, invisibles depuis l'écran
        // d'embarquement). Pour un changement jour-par-jour, Programmation_voyage::
        // updateProgrammation() gère déjà ça correctement (choix "les billets suivent" /
        // "un autre car reprend l'ancien créneau") -- c'est la voie à utiliser à la place.
        public function editHoraire($idProgrammer, $heureDepart, $rdv)
        {
            $trajet = $this->FetchSelectWhere(
                "idDepart, idDestination, id_compagnie, heureDepart",
                "programmer",
                "idProgrammer = :id",
                [":id" => $idProgrammer]
            );
            if (!$trajet) {
                return false;
            }

            $doublon = $this->FetchSelectWhere(
                "idProgrammer",
                "programmer",
                "idDepart = :idDepart AND idDestination = :idDestination AND heureDepart = :heureDepart
                 AND id_compagnie = :id_compagnie AND idProgrammer != :idProgrammer",
                [
                    ":idDepart" => $trajet->idDepart,
                    ":idDestination" => $trajet->idDestination,
                    ":heureDepart" => $heureDepart,
                    ":id_compagnie" => $trajet->id_compagnie,
                    ":idProgrammer" => $idProgrammer,
                ]
            );
            if ($doublon) {
                return 'doublon';
            }

            if ($heureDepart !== $trajet->heureDepart) {
                // Meme technique que saveProgrammer() ci-dessus : billets/programmation_voyage
                // stockent une localite (texte), pas un id_agence.
                $departAgence = $this->FetchSelectWhere("localite", "agence", "idAgence = :id", [":id" => $trajet->idDepart]);
                $destinationAgence = $this->FetchSelectWhere("localite", "agence", "idAgence = :id", [":id" => $trajet->idDestination]);
                $localiteDepart = $departAgence->localite ?? null;
                $localiteDestination = $destinationAgence->localite ?? null;

                $billetExistant = $this->fetchOne(
                    "SELECT idBillets FROM billets
                     WHERE departId = :depart AND destinationId = :dest AND Heur_departs = :heure
                       AND id_compagnie = :id_compagnie AND jourVoyage >= CURDATE() LIMIT 1",
                    [
                        ":depart" => $localiteDepart, ":dest" => $localiteDestination,
                        ":heure" => $trajet->heureDepart, ":id_compagnie" => $trajet->id_compagnie,
                    ]
                );
                $programmationActive = $this->fetchOne(
                    "SELECT id_programmation FROM programmation_voyage
                     WHERE localite_user = :depart AND id_trajet = :dest AND id_horaire = :heure
                       AND id_compagnie = :id_compagnie AND statut = 'active' AND date_enregistre >= CURDATE() LIMIT 1",
                    [
                        ":depart" => $localiteDepart, ":dest" => $localiteDestination,
                        ":heure" => $trajet->heureDepart, ":id_compagnie" => $trajet->id_compagnie,
                    ]
                );
                if ($billetExistant || $programmationActive) {
                    return 'billets_existants';
                }
            }

            $req = "UPDATE programmer
            SET heureDepart = :heureDepart, rdv = :rdv
            WHERE idProgrammer = :idProgrammer";

            $params = [
                ":heureDepart" => $heureDepart,
                ":rdv" => $rdv,
                ":idProgrammer" => $idProgrammer,
            ];

            return $this->insertion_update_simples($req, $params);
        }

        public function editPrixEscales($idProgrammer, $prixEscales)
        {
            foreach ($prixEscales as $idEscale => $prix) {
                $this->insertion_update_simples(
                    "UPDATE ligneTrajet SET prix_escale = :prix WHERE id_trajets = :idProgrammer AND id_escales = :idEscale AND type_trajet = 'programmer'",
                    [
                        ":prix" => (float) $prix,
                        ":idProgrammer" => $idProgrammer,
                        ":idEscale" => $idEscale
                    ]
                );
            }
        }

        public function deleteProgrammer($id)
        {
            // Supprimer d'abord les dépendances : escales du trajet, puis affectations aux cars
            $this->insertion_update_simples("DELETE FROM ligneTrajet WHERE id_trajets = :id AND type_trajet = 'programmer'", [":id" => $id]);
            $this->insertion_update_simples("DELETE FROM liaison_car_trajet WHERE id_trajets = :id", [":id" => $id]);

            // Supprimer ensuite le programme lui-même
            $stmt = $this->insertion_update_simples("DELETE FROM programmer WHERE idProgrammer = :id", [":id" => $id]);
            return $stmt ? true : false;
        }
    }
