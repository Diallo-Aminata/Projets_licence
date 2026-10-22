 <?php
    class Add_billet extends Model
    {

        // public function getDestinationsWithHeuresAndEscales()
        // {
        //     $idDepart    = $_SESSION['ville'];
        //     $idCompagnie = $_SESSION['id_compagnie'];

        //     $sql = "SELECT DISTINCT p.idProgrammer,
        //                 p.idDestination AS destination_nom,
        //                 p.heureDepart,
        //                 p.prix
        //         FROM programmer p
        //         WHERE p.idDepart = :idDepart
        //           AND p.id_compagnie = :idCompagnie
        //         ORDER BY p.idDestination, p.heureDepart";

        //     $rows = $this->fetchAll($sql, [
        //         ':idDepart' => $idDepart,
        //         ':idCompagnie' => $idCompagnie
        //     ]);

        //     $result = [];

        //     foreach ($rows as $row) {
        //         $destNom = $row['destination_nom'];
        //         $progId  = $row['idProgrammer'];

        //         if (!isset($result[$destNom])) {
        //             $result[$destNom] = [
        //                 'nom' => $destNom,
        //                 'programmes' => []
        //             ];
        //         }

        //         $escales = $this->fetchAll(
        //             "SELECT e.id_escale, prix_escale, e.escales AS escale_nom
        //          FROM ligneTrajet lt
        //          JOIN escale e ON e.id_escale = lt.id_escales
        //          WHERE lt.id_trajets = :progId",
        //             [':progId' => $progId]
        //         );

        //         $result[$destNom]['programmes'][] = [
        //             'idProgrammer' => $progId,
        //             'heureDepart' => $row["heureDepart"],
        //             'prix'        => $row['prix'],
        //             'escales'     => $escales
        //         ];
        //     }

        //     return array_values($result);
        // }


        // Liste des gares (agences) de la compagnie, utilisée pour le select "Départ"
        // affiché uniquement à l'Admin (les autres rôles ont une gare fixe en session).
        public function getAgencesByCompagnie()
        {
            $idCompagnie = $_SESSION['id_compagnie'] ?? null;
            if (!$idCompagnie) return [];

            return $this->fetchAll(
                "SELECT idAgence, localite, numeroGare FROM agence WHERE id_compagnie = :ic ORDER BY localite, numeroGare",
                [':ic' => $idCompagnie]
            );
        }

        // Détermine la gare de départ effective pour la vente en cours.
        // - Admin : gare choisie dans le formulaire (idDepart posté/GET), revalidée contre
        //   sa propre compagnie pour empêcher toute sélection d'une gare d'une autre compagnie.
        // - Autres rôles (chef_d_escale, Utilisateur) : toujours leur gare de session, jamais
        //   influencée par une valeur postée par le client (empêche de créditer la caisse d'une
        //   autre gare que la sienne).
        private function resolveDepart(): array
        {
            if (($_SESSION['droit'] ?? null) === 'Admin') {
                $idAgencePoste = $_POST['idDepart'] ?? $_GET['idDepart'] ?? null;
                if (!$idAgencePoste) {
                    return [null, null, null];
                }
                $agence = $this->fetchOne(
                    "SELECT idAgence, localite, numeroGare FROM agence WHERE idAgence = :id AND id_compagnie = :ic LIMIT 1",
                    [':id' => $idAgencePoste, ':ic' => $_SESSION['id_compagnie'] ?? null]
                );
                return $agence
                    ? [$agence['idAgence'], $agence['localite'], $agence['numeroGare']]
                    : [null, null, null];
            }

            return [$_SESSION['id_agence'] ?? null, $_SESSION['ville'] ?? null, $_SESSION['numero_gare'] ?? null];
        }

        // $idAgenceDepartOverride permet de calculer les destinations d'une gare précise sans
        // dépendre de $_GET/$_POST (utilisé par le contrôleur pour précharger la liste de
        // TOUTES les gares d'un coup, cf. Add_billets::index()). Sans override, se comporte
        // comme avant : déduit la gare depuis la session/requête via resolveDepart().
        public function getDestinationsWithHeuresAndEscales($idAgenceDepartOverride = null)
        {
            if ($idAgenceDepartOverride !== null) {
                $idAgenceDepart = $idAgenceDepartOverride;
            } else {
                [$idAgenceDepart, , ] = $this->resolveDepart();
            }
            $idCompagnie = $_SESSION['id_compagnie'] ?? null;

            if (!$idAgenceDepart || !$idCompagnie) return [];

            // Filtre sur l'ID de la gare (et non son nom de ville) : plusieurs gares d'une
            // même compagnie peuvent partager la même localité (ex. "Segou" Gare I et Gare II),
            // un filtre par nom mélangerait alors les trajets des deux gares.
            $sql = "SELECT
                p.idProgrammer,
                p.heureDepart,
                p.prix,
                a1.localite AS departLocalite,
                 a1.numeroGare AS departnumeroGare,
                a2.localite AS destinationLocalite
            FROM programmer p
            LEFT JOIN agence a1 ON p.idDepart = a1.idAgence
            LEFT JOIN agence a2 ON p.idDestination = a2.idAgence
            WHERE p.idDepart = :idAgenceDepart
              AND p.id_compagnie = :idCompagnie
           ";

            $rows = $this->fetchAll($sql, [
                ':idAgenceDepart' => $idAgenceDepart,
                ':idCompagnie' => $idCompagnie
            ]);

            $result = [];

            foreach ($rows as $row) {
                $destNom = $row['destinationLocalite'] ?? $row['idDestination'];
                $progId  = $row['idProgrammer'];
                $departnumeroGare = $row['departnumeroGare'] ?? null;

                if (!isset($result[$destNom])) {
                    $result[$destNom] = [
                        'nom' => $destNom,
                        'departLocalite' => $row['departLocalite'],
                        'departnumeroGare' => $departnumeroGare,
                        'programmes' => []
                    ];
                }

                $escales = $this->fetchAll(
                    "SELECT e.id_escale, prix_escale, e.escales AS escale_nom
                 FROM ligneTrajet lt
                 JOIN escale e ON e.id_escale = lt.id_escales
                 WHERE lt.id_trajets = :progId AND lt.type_trajet = 'programmer'",
                    [':progId' => $progId]
                );

                $result[$destNom]['programmes'][] = [
                    'idProgrammer' => $progId,
                    'heureDepart'  => $row["heureDepart"],
                    'prix'         => $row['prix'],
                    'depart'       => $row['departLocalite'],
                    'destination'  => $row['destinationLocalite'],
                    'escales'      => $escales
                ];
            }

            // Retourne un tableau indexé pour le foreach
            return array_values($result);
        }


        // Recalcule le prix de référence côté serveur (ne jamais faire confiance au prix posté par le client)
        private function getPrixReference($heureDepart, $destinationLocalite, $escaleNom)
        {
            [$idAgenceDepart, , ] = $this->resolveDepart();
            $idCompagnie = $_SESSION['id_compagnie'] ?? null;

            $row = $this->fetchOne(
                "SELECT p.idProgrammer, p.prix
                 FROM programmer p
                 LEFT JOIN agence a1 ON p.idDepart = a1.idAgence
                 LEFT JOIN agence a2 ON p.idDestination = a2.idAgence
                 WHERE p.idDepart = :idAgenceDepart
                   AND a2.localite = :destLocalite
                   AND p.heureDepart = :heure
                   AND p.id_compagnie = :idCompagnie
                 LIMIT 1",
                [
                    ':idAgenceDepart' => $idAgenceDepart,
                    ':destLocalite'   => $destinationLocalite,
                    ':heure'          => $heureDepart,
                    ':idCompagnie'    => $idCompagnie
                ]
            );

            if (!$row) {
                return null;
            }

            $prixUnitaire = (float)$row['prix'];

            if (!empty($escaleNom)) {
                $escaleRow = $this->fetchOne(
                    "SELECT prix_escale FROM ligneTrajet lt
                     JOIN escale e ON e.id_escale = lt.id_escales
                     WHERE lt.id_trajets = :progId AND lt.type_trajet = 'programmer' AND e.escales = :escaleNom
                     LIMIT 1",
                    [':progId' => $row['idProgrammer'], ':escaleNom' => $escaleNom]
                );
                if ($escaleRow) {
                    $prixUnitaire = (float)$escaleRow['prix_escale'];
                }
            }

            return $prixUnitaire;
        }

        // Génère un numeroBillets et vérifie son unicité en base avant de le retenir : le
        // numéro affiché dans le formulaire (genererNumeroBillet() côté vue) n'est qu'un
        // aperçu — on ne lui fait jamais confiance côté serveur, car il est modifiable
        // côté client et deux ventes dans la même seconde peuvent produire la même valeur
        // (timestamp + rand(100,999), 1 chance sur ~900 de collision).
        private function genererNumeroBilletUnique(): string
        {
            for ($i = 0; $i < 5; $i++) {
                $numero = 'SMT' . date('Hismd') . random_int(100, 999);
                $existe = $this->fetchOne(
                    "SELECT 1 FROM billets WHERE numeroBillets = :num LIMIT 1",
                    [':num' => $numero]
                );
                if (!$existe) {
                    return $numero;
                }
            }
            // Filet de sécurité si les 5 tentatives collisionnent toutes (extrêmement improbable) :
            // uniqid garantit l'unicité par construction.
            return 'SMT' . uniqid();
        }

        // Retrouve l'idAgence precis de la destination finale (jamais l'escale) a partir de
        // son nom de localite : insertProgrammation() exige un id, jamais un nom de ville.
        // Jamais fait confiance a un id poste par le client : toujours redecouvert via le
        // trajet programme (programmer), comme getPrixReference() le fait deja pour le prix.
        private function resolveIdAgenceDestination($idAgenceDepart, $heureDepart, $destinationLocalite, $idCompagnie)
        {
            $row = $this->fetchOne(
                "SELECT p.idDestination
                 FROM programmer p
                 LEFT JOIN agence a2 ON p.idDestination = a2.idAgence
                 WHERE p.idDepart = :idAgenceDepart
                   AND a2.localite = :destLocalite
                   AND p.heureDepart = :heure
                   AND p.id_compagnie = :idCompagnie
                 LIMIT 1",
                [
                    ':idAgenceDepart' => $idAgenceDepart,
                    ':destLocalite'   => $destinationLocalite,
                    ':heure'          => $heureDepart,
                    ':idCompagnie'    => $idCompagnie
                ]
            );

            return $row ? (int)$row['idDestination'] : null;
        }

        // Etat d'un creneau d'aujourd'hui pour la vente sans programmation prealable : soit
        // un car est deja programme (bandeau informatif, places restantes), soit aucun ne
        // l'est encore (liste des cars physiquement presents a la gare pour en choisir un).
        // Utilise par Add_billets::ajaxEtatCreneau() (appel AJAX depuis le formulaire de vente).
        public function getEtatCreneauPourAjax($idProgrammer)
        {
            [$idAgenceDepart, $departLocalite, ] = $this->resolveDepart();
            $idCompagnie = $_SESSION['id_compagnie'] ?? null;
            if (!$idAgenceDepart || !$idCompagnie) {
                return ['erreur' => "Gare de départ introuvable."];
            }

            $programme = $this->fetchOne(
                "SELECT p.idProgrammer, p.heureDepart, a2.localite AS destinationLocalite
                 FROM programmer p
                 LEFT JOIN agence a2 ON p.idDestination = a2.idAgence
                 WHERE p.idProgrammer = :id AND p.idDepart = :idDepart AND p.id_compagnie = :ic
                 LIMIT 1",
                [':id' => $idProgrammer, ':idDepart' => $idAgenceDepart, ':ic' => $idCompagnie]
            );
            if (!$programme) {
                return ['erreur' => "Trajet introuvable pour cette gare."];
            }

            $aujourdhui = date('Y-m-d');

            $rowProg = $this->fetchOne(
                "SELECT c.numero_car, c.matriculle, c.nbr_place, c.nbr_place_reserve
                 FROM programmation_voyage pv
                 INNER JOIN car c ON c.id_car = pv.id_car_programmer
                 WHERE pv.id_horaire = :h AND pv.date_enregistre = :d AND pv.id_trajet = :t
                   AND pv.localite_user = :l AND pv.id_agence = :a AND pv.id_compagnie = :c
                 LIMIT 1",
                [
                    ':h' => $programme['heureDepart'],
                    ':d' => $aujourdhui,
                    ':t' => $programme['destinationLocalite'],
                    ':l' => $departLocalite,
                    ':a' => $idAgenceDepart,
                    ':c' => $idCompagnie
                ]
            );

            if ($rowProg) {
                return [
                    'existe'           => true,
                    'numero_car'       => $rowProg['numero_car'],
                    'matriculle'       => $rowProg['matriculle'],
                    'places_restantes' => max(0, (int)$rowProg['nbr_place'] - (int)$rowProg['nbr_place_reserve'])
                ];
            }

            $programmationModel = new Programmation_voyage();
            $cars = $programmationModel->getCarsDisponiblesGare($departLocalite, $idCompagnie);

            return [
                'existe' => false,
                'cars'   => array_map(fn($c) => [
                    'id_car'           => (int)$c['id_car'],
                    'numero_car'       => $c['numero_car'],
                    'matriculle'       => $c['matriculle'],
                    'places_restantes' => max(0, (int)$c['nbr_place'] - (int)$c['nbr_place_reserve'])
                ], $cars)
            ];
        }

        // public function saveBillets(): bool
        // {
        //     extract($_POST);
        //     $pdo = $this->connect();

        //     $jourVoyage = date('Y-m-d', strtotime($jourVoyage));
        //     $aujourdhui = date('Y-m-d');
        //     $demain     = date('Y-m-d', strtotime('+1 day'));

        //     if (!in_array($jourVoyage, [$aujourdhui, $demain])) {
        //         $this->set_flash("Date invalide : choisissez aujourd’hui ou demain.", "danger");
        //         return false;
        //     }

        //     if (empty($Client) || empty($destinationId) || empty($programme) || empty($nombrePassages)) {
        //         $this->set_flash("Tous les champs obligatoires doivent être remplis.", "danger");
        //         return false;
        //     }

        //     $car             = null;
        //     $idCarProgrammer = null;
        //     $numPlace        = '-'; // par défaut
        //     $escaleNom = $_POST['escale'] ?? '';    // récupère le hidden
        //     $destinationId = $_POST['destinationId'] ?? '';

        //     $destFinale = !empty($escaleNom) ? $escaleNom : $destinationId;

        //     $prixUtilise     = $_POST['montant_payer'] ?? '';

        //     if ($jourVoyage == $aujourdhui) {
        //         $rowProg = $this->fetchOne(
        //             "SELECT id_car_programmer FROM programmation_voyage
        //      WHERE id_horaire = :h AND date_enregistre = :d AND id_trajet = :t
        //      AND localite_user = :l AND id_compagnie = :c LIMIT 1",
        //             [
        //                 ':h' => $programme,
        //                 ':d' => $jourVoyage,
        //                 ':t' => $destinationId,
        //                 ':l' => $_SESSION['ville'],
        //                 ':c' => $_SESSION['id_compagnie']
        //             ]
        //         );

        //         if (!$rowProg) {
        //             $this->set_flash("Aucun car programmé pour cette heure et ce trajet.", "danger");
        //             return false;
        //         }

        //         $idCarProgrammer = $rowProg['id_car_programmer'];

        //         $car = $this->fetchOne(
        //             "SELECT nbr_place, nbr_place_reserve FROM car WHERE numero_car = :num LIMIT 1",
        //             [':num' => $idCarProgrammer]
        //         );

        //         if (!$car) {
        //             $this->set_flash("Car introuvable.", "danger");
        //             return false;
        //         }

        //         $placesDispo = $car['nbr_place'] - $car['nbr_place_reserve'];
        //         if ($nombrePassages > $placesDispo) {
        //             $this->set_flash("Places insuffisantes : $placesDispo restantes.", "danger");
        //             return false;
        //         }

        //         $start    = (int)$car['nbr_place_reserve'] + 1;
        //         $end      = $start + (int)$nombrePassages - 1;
        //         $numPlace = ($nombrePassages == 1) ? "$start" : "$start-$end";
        //     }

        //     try {
        //         $pdo->beginTransaction();

        //         // Insertion client
        //         $stmt = $pdo->prepare("INSERT INTO client (Client, montant_payer, date_enregistrement, id_compagnie)
        //     VALUES (:c, :m, :d, :ic )");
        //         $stmt->execute([
        //             ':c'  => $Client,
        //             ':m'  => $prixUtilise,
        //             ':d'  => date('Ymd'),
        //             ':ic' => $_SESSION['id_compagnie']

        //         ]);
        //         $idClient = $pdo->lastInsertId();

        //         if ($jourVoyage == $demain) {
        //             // Récupérer la place minimale dynamique
        //             $stmt = $pdo->prepare("SELECT place_minumale FROM place_minumale LIMIT 1");
        //             $stmt->execute();
        //             $rowPlace = $stmt->fetch();
        //             $placeTotale = $rowPlace ? (int)$rowPlace['place_minumale'] : 0;

        //             if ($placeTotale <= 0) {
        //                 $pdo->rollBack();
        //                 $this->set_flash("Erreur : nombre de places minimales non défini.", "danger");
        //                 return false;
        //             }

        //             // Vérifier les places disponibles pour demain
        //             $stmt = $pdo->prepare(
        //                 "SELECT idSuivis, place_totals, place_reserve FROM suivis
        //          WHERE depart = :dep AND destination = :dest AND heur_depart = :h
        //          AND date_reservation = :jr AND id_compagnie = :id_compagnie LIMIT 1"
        //             );
        //             $stmt->execute([
        //                 ':dep'  => $_SESSION['ville'],
        //                 ':dest' => $destFinale,
        //                 ':h'    => $programme,
        //                 ':jr'   => $jourVoyage,
        //                 ':id_compagnie' => $_SESSION['id_compagnie']
        //             ]);
        //             $suivi = $stmt->fetch();

        //             if ($suivi) {
        //                 $placesDispo = $suivi['place_totals'] - $suivi['place_reserve'];
        //                 if ($nombrePassages > $placesDispo) {
        //                     $pdo->rollBack();
        //                     $this->set_flash("Places insuffisantes pour demain : $placesDispo restantes.", "danger");
        //                     return false;
        //                 }
        //                 $stmt = $pdo->prepare("UPDATE suivis SET place_reserve = place_reserve + :n WHERE idSuivis = :id");
        //                 $stmt->execute([
        //                     ':n'  => (int)$nombrePassages,
        //                     ':id' => $suivi['idSuivis']
        //                 ]);
        //             } else {
        //                 if ($nombrePassages > $placeTotale) {
        //                     $pdo->rollBack();
        //                     $this->set_flash("Places insuffisantes pour demain : $placeTotale restantes.", "danger");
        //                     return false;
        //                 }
        //                 $stmt = $pdo->prepare(
        //                     "INSERT INTO suivis (place_reserve, place_totals, depart, destination, heur_depart, date_reservation, id_compagnie)
        //              VALUES (:n, :total, :dep, :dest, :h, :jr, :id_compagnie)"
        //                 );
        //                 $stmt->execute([
        //                     ':n'     => (int)$nombrePassages,
        //                     ':total' => $placeTotale,
        //                     ':dep'   => $_SESSION['ville'],
        //                     ':dest'  => $destFinale,
        //                     ':h'     => $programme,
        //                     ':jr'    => $jourVoyage,
        //                     ':id_compagnie' => $_SESSION['id_compagnie']
        //                 ]);
        //                 $suivi['idSuivis'] = $pdo->lastInsertId();
        //             }

        //             // Calcul des numéros de places
        //             $stmt = $pdo->prepare("SELECT numeroPlace FROM billets
        //         WHERE jourVoyage = :j AND Heur_departs = :h AND departId = :dep AND destinationId = :dest AND id_compagnie = :id_compagnie");
        //             $stmt->execute([
        //                 ':j'   => $jourVoyage,
        //                 ':h'   => $programme,
        //                 ':dep' => $_SESSION['id_agence'],
        //                 ':dest' => $destFinale,
        //                 ':id_compagnie' => $_SESSION['id_compagnie']
        //             ]);

        //             $placesPrises = [];
        //             while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        //                 foreach (explode('-', $row['numeroPlace']) as $p) {
        //                     $placesPrises[] = (int)$p;
        //                 }
        //             }

        //             $start = 1;
        //             $numPlacesAttribues = [];
        //             while (count($numPlacesAttribues) < $nombrePassages) {
        //                 if (!in_array($start, $placesPrises)) {
        //                     $numPlacesAttribues[] = $start;
        //                 }
        //                 $start++;
        //             }

        //             $numPlace = implode('-', $numPlacesAttribues);
        //         }

        //         // Insertion du billet
        //         $stmt = $pdo->prepare("INSERT INTO billets (id_client,idUser, numeroBillets, jourVoyage, Heur_departs,
        //                       nombrePassages, destinationId, departId, date_expiration, numeroPlace,
        //                       date_reservation, status_reservation, validation_billets ,id_compagnie)
        //     VALUES (:cl, :idUser, :num, :j, :h, :n, :dest, :dep, :exp, :place, :res, :stat, :validation_billets, :id_compagnie)");
        //         $stmt->execute([
        //             ':cl'    => $idClient,
        //             ':idUser'  => $_SESSION['id_utilisateur'],
        //             ':num'   => $numeroBillets,
        //             ':j'     => $jourVoyage,
        //             ':h'     => $programme,
        //             ':n'     => $nombrePassages,
        //             ':dest'  => $destFinale,
        //             ':dep'   => $_SESSION['ville'],
        //             ':exp'   => date('Y-m-d', strtotime($jourVoyage . ' +1 week')),
        //             ':place' => $numPlace,
        //             ':res'   => date('Ymd'),
        //             ':stat'  => 'presentiel',
        //             ':validation_billets' => 'valider',
        //             ':id_compagnie' => $_SESSION['id_compagnie']
        //         ]);

        //         // Màj car pour aujourd'hui
        //         if ($jourVoyage == $aujourdhui) {
        //             $stmt = $pdo->prepare("UPDATE car SET nbr_place_reserve = nbr_place_reserve + :n WHERE numero_car = :num");
        //             $stmt->execute([
        //                 ':n'   => (int)$nombrePassages,
        //                 ':num' => $idCarProgrammer
        //             ]);

        //             if ($stmt->rowCount() === 0) {
        //                 $pdo->rollBack();
        //                 $this->set_flash("Échec mise à jour du car.", "danger");
        //                 return false;
        //             }
        //         }

        //         $pdo->commit();
        //         $this->set_flash("Réservation enregistrée avec succès.", "info");
        //         header("Location: " . $_SERVER['REQUEST_URI']);
        //         exit;
        //     } catch (Throwable $e) {
        //         $pdo->rollBack();
        //         $this->set_flash("Erreur SQL : " . $e->getMessage(), "danger");
        //         return false;
        //     }
        // }
        public function saveBillets(): bool
        {
            if (!csrf_verify()) {
                $this->set_flash("Session expirée, veuillez réessayer.", "danger");
                return false;
            }

            extract($_POST);
            $pdo = $this->connect();

            [$idAgenceDepart, $departLocalite, $numeroGareDepart] = $this->resolveDepart();
            if (!$departLocalite) {
                $this->set_flash("Veuillez choisir la gare de départ.", "danger");
                return false;
            }

            $jourVoyage = date('Y-m-d', strtotime($jourVoyage));
            $aujourdhui = date('Y-m-d');
            $maxJour    = date('Y-m-d', strtotime('+6 days'));

            // Vérification date : autorise aujourd'hui jusqu'à J+6 (une semaine)
            if ($jourVoyage < $aujourdhui || $jourVoyage > $maxJour) {
                $this->set_flash("Date invalide : choisissez une date entre aujourd'hui et dans 6 jours.", "danger");
                return false;
            }

            // Champs obligatoires
            if (empty($Client) || empty($destinationId) || empty($programme) || empty($nombrePassages)) {
                $this->set_flash("Tous les champs obligatoires doivent être remplis.", "danger");
                return false;
            }

            $nombrePassages = (int)$nombrePassages;
            if ($nombrePassages <= 0) {
                $this->set_flash("Le nombre de places doit être supérieur à zéro.", "danger");
                return false;
            }

            $car             = null;
            $idCarProgrammer = null;
            $numPlace        = '-';
            $escaleNom       = $_POST['escale'] ?? '';
            $destinationId   = $_POST['destinationId'] ?? '';
            $destFinale      = !empty($escaleNom) ? $escaleNom : $destinationId;

            // Le prix ne vient jamais du client : il est recalculé côté serveur à partir du trajet programmé.
            $prixUnitaireServeur = $this->getPrixReference($programme, $destinationId, $escaleNom);
            if ($prixUnitaireServeur === null) {
                $this->set_flash("Trajet ou tarif introuvable pour cette destination/heure.", "danger");
                return false;
            }
            $prixUtilise = $prixUnitaireServeur * $nombrePassages;

            $idCompagnie = $_SESSION['id_compagnie'] ?? null;

            // === Vente d'aujourd'hui sans programmation prealable ===
            // Resolue (ou creee a la volee) AVANT d'ouvrir la transaction de vente ci-dessous :
            // garde le code plus simple a suivre (aucune programmation fantome possible si la
            // vente echoue ensuite, puisqu'elle est deja actee independamment a ce stade).
            if ($jourVoyage == $aujourdhui) {
                $rowProg = $this->fetchOne(
                    "SELECT id_car_programmer FROM programmation_voyage
                     WHERE id_horaire = :h AND date_enregistre = :d AND id_trajet = :t
                     AND localite_user = :l AND id_agence = :a AND id_compagnie = :c LIMIT 1",
                    [
                        ':h' => $programme,
                        ':d' => $jourVoyage,
                        ':t' => $destinationId,
                        ':l' => $departLocalite,
                        ':a' => $idAgenceDepart,
                        ':c' => $idCompagnie
                    ]
                );

                if ($rowProg) {
                    $idCarProgrammer = $rowProg['id_car_programmer'];
                } else {
                    // Aucun car programme : l'agent en a choisi un parmi ceux disponibles a sa
                    // gare (cf. Add_billets::ajaxEtatCreneau() cote vue).
                    $idCarChoisi = (int)($_POST['idCarChoisi'] ?? 0);
                    if (!$idCarChoisi) {
                        $this->set_flash("Aucun car programmé pour cette heure et ce trajet. Veuillez choisir un car disponible.", "danger");
                        return false;
                    }

                    // Defense en profondeur : le car doit etre physiquement a CETTE gare, meme
                    // pour un Admin (insertProgrammation() est plus permissif pour lui — il peut
                    // reaffecter un car present ailleurs dans sa compagnie — ce qui ne convient
                    // pas ici : on ne doit jamais "teleporter" un car absent de la gare qui
                    // encaisse la vente).
                    $carValide = $this->fetchOne(
                        "SELECT id_car FROM car WHERE id_car = :id AND id_compagnie = :ic AND status_car = :loc LIMIT 1",
                        [':id' => $idCarChoisi, ':ic' => $idCompagnie, ':loc' => $departLocalite]
                    );
                    if (!$carValide) {
                        $this->set_flash("Ce car n'est plus disponible à cette gare. Veuillez réessayer.", "danger");
                        return false;
                    }

                    $idAgenceDestination = $this->resolveIdAgenceDestination($idAgenceDepart, $programme, $destinationId, $idCompagnie);
                    if (!$idAgenceDestination) {
                        $this->set_flash("Trajet introuvable pour cette destination/heure.", "danger");
                        return false;
                    }

                    $programmationModel = new Programmation_voyage();
                    // Admin : gare choisie dans le formulaire ; autres roles : null (le modele
                    // retombe alors sur $_SESSION['ville'], leur propre gare fixe).
                    $idDepartPourAdmin = (($_SESSION['droit'] ?? null) === 'Admin') ? $departLocalite : null;
                    $programmationCreee = $programmationModel->insertProgrammation(
                        $idCarChoisi,
                        $programme,
                        $destinationId,
                        $departLocalite,
                        $jourVoyage,
                        $idDepartPourAdmin,
                        $idAgenceDepart,
                        $idAgenceDestination
                    );
                    if (!$programmationCreee) {
                        $this->set_flash("Impossible de programmer ce car sur ce créneau (déjà en route, déjà programmé ailleurs, ou créneau invalide). Veuillez réessayer ou choisir un autre car.", "danger");
                        return false;
                    }

                    // Anti-course : si un autre agent de la meme gare a programme un AUTRE car
                    // sur ce meme creneau quelques millisecondes avant nous (insertProgrammation()
                    // ne bloque que les doublons sur le MEME car, pas deux cars differents sur le
                    // meme trajet), un seul doit survivre : le premier cree. Le perdant desactive
                    // sa propre programmation (sans risque : par construction, aucune place n'a
                    // encore ete vendue dessus) et la vente continue avec le car gagnant.
                    $gagnant = $this->fetchOne(
                        "SELECT id_programmation, id_car_programmer FROM programmation_voyage
                         WHERE id_horaire = :h AND date_enregistre = :d AND id_trajet = :t
                         AND localite_user = :l AND id_agence = :a AND id_compagnie = :c AND statut = 'active'
                         ORDER BY id_programmation ASC LIMIT 1",
                        [
                            ':h' => $programme,
                            ':d' => $jourVoyage,
                            ':t' => $destinationId,
                            ':l' => $departLocalite,
                            ':a' => $idAgenceDepart,
                            ':c' => $idCompagnie
                        ]
                    );

                    if ($gagnant && (int)$gagnant['id_car_programmer'] !== $idCarChoisi) {
                        $notre = $this->fetchOne(
                            "SELECT id_programmation FROM programmation_voyage
                             WHERE id_horaire = :h AND date_enregistre = :d AND id_trajet = :t
                             AND localite_user = :l AND id_agence = :a AND id_compagnie = :c
                             AND id_car_programmer = :car AND statut = 'active' LIMIT 1",
                            [
                                ':h' => $programme,
                                ':d' => $jourVoyage,
                                ':t' => $destinationId,
                                ':l' => $departLocalite,
                                ':a' => $idAgenceDepart,
                                ':c' => $idCompagnie,
                                ':car' => $idCarChoisi
                            ]
                        );
                        if ($notre) {
                            $programmationModel->desactiverProgrammation($notre['id_programmation']);
                        }
                        $idCarProgrammer = $gagnant['id_car_programmer'];
                    } else {
                        $idCarProgrammer = $idCarChoisi;
                    }
                }
            }

            try {
                $pdo->beginTransaction();

                // Insertion client
                $stmt = $pdo->prepare("INSERT INTO client (Client, montant_payer, date_enregistrement, id_compagnie)
            VALUES (:c, :m, :d, :ic)");
                $stmt->execute([
                    ':c'  => $Client,
                    ':m'  => $prixUtilise,
                    ':d'  => date('Ymd'),
                    ':ic' => $_SESSION['id_compagnie']
                ]);
                $idClient = $pdo->lastInsertId();

                // Gestion des places
                if ($jourVoyage == $aujourdhui) {
                    // Aujourd'hui → réservation sur le car resolu/créé juste avant l'ouverture de
                    // cette transaction (programmation existante, ou créée à la volée).
                    // SELECT ... FOR UPDATE sur la connexion transactionnelle : verrouille la ligne
                    // jusqu'au commit/rollback pour empêcher deux ventes simultanées de survendre les places.
                    $stmt = $pdo->prepare(
                        "SELECT nbr_place, nbr_place_reserve, id_car FROM car WHERE id_car = :num LIMIT 1 FOR UPDATE"
                    );
                    $stmt->execute([':num' => $idCarProgrammer]);
                    $car = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$car) {
                        $pdo->rollBack();
                        $this->set_flash("Car introuvable.", "danger");
                        return false;
                    }

                    $placesDispo = $car['nbr_place'] - $car['nbr_place_reserve'];
                    if ($nombrePassages > $placesDispo) {
                        $pdo->rollBack();
                        $this->set_flash("Places insuffisantes : $placesDispo restantes.", "danger");
                        return false;
                    }

                    $start    = (int)$car['nbr_place_reserve'] + 1;
                    $end      = $start + (int)$nombrePassages - 1;
                    $numPlace = ($nombrePassages == 1) ? "$start" : "$start-$end";
                } else {
                    // J+1 à J+6 → gestion via suivi et place minimale
                    $stmt = $pdo->prepare("SELECT place_minumale FROM place_minumale WHERE id_compagnie = :ic LIMIT 1");
                    $stmt->execute([':ic' => $_SESSION['id_compagnie']]);
                    $rowPlace = $stmt->fetch();
                    $placeTotale = $rowPlace ? (int)$rowPlace['place_minumale'] : 0;

                    if ($placeTotale <= 0) {
                        $pdo->rollBack();
                        $this->set_flash("Erreur : nombre de places minimales non défini.", "danger");
                        return false;
                    }

                    // FOR UPDATE : verrouille la ligne de suivi jusqu'au commit/rollback pour éviter
                    // que deux ventes simultanées ne dépassent le quota de places de demain.
                    $stmt = $pdo->prepare(
                        "SELECT idSuivis, place_totals, place_reserve FROM suivis
                 WHERE depart = :dep AND destination = :dest AND heur_depart = :h
                 AND date_reservation = :jr AND id_compagnie = :id_compagnie LIMIT 1 FOR UPDATE"
                    );
                    $stmt->execute([
                        ':dep'          => $departLocalite,
                        ':dest'         => $destFinale,
                        ':h'            => $programme,
                        ':jr'           => $jourVoyage,
                        ':id_compagnie' => $_SESSION['id_compagnie']
                    ]);
                    $suivi = $stmt->fetch();

                    if ($suivi) {
                        $placesDispo = $suivi['place_totals'] - $suivi['place_reserve'];
                        if ($nombrePassages > $placesDispo) {
                            $pdo->rollBack();
                            $this->set_flash("Places insuffisantes pour ce créneau : $placesDispo restantes.", "danger");
                            return false;
                        }
                        $stmt = $pdo->prepare("UPDATE suivis SET place_reserve = place_reserve + :n WHERE idSuivis = :id");
                        $stmt->execute([
                            ':n'  => (int)$nombrePassages,
                            ':id' => $suivi['idSuivis']
                        ]);
                    } else {
                        if ($nombrePassages > $placeTotale) {
                            $pdo->rollBack();
                            $this->set_flash("Places insuffisantes pour ce créneau : $placeTotale restantes.", "danger");
                            return false;
                        }
                        $stmt = $pdo->prepare(
                            "INSERT INTO suivis (place_reserve, place_totals, depart, destination, heur_depart, date_reservation, id_compagnie)
                     VALUES (:n, :total, :dep, :dest, :h, :jr, :id_compagnie)"
                        );
                        $stmt->execute([
                            ':n'     => (int)$nombrePassages,
                            ':total' => $placeTotale,
                            ':dep'   => $departLocalite,
                            ':dest'  => $destFinale,
                            ':h'     => $programme,
                            ':jr'    => $jourVoyage,
                            ':id_compagnie' => $_SESSION['id_compagnie']
                        ]);
                        $suivi['idSuivis'] = $pdo->lastInsertId();
                    }

                    // Numéros de places pour demain
                    $stmt = $pdo->prepare("SELECT numeroPlace FROM billets
                WHERE jourVoyage = :j AND Heur_departs = :h AND departId = :dep AND destinationId = :dest AND id_compagnie = :id_compagnie");
                    $stmt->execute([
                        ':j'            => $jourVoyage,
                        ':h'            => $programme,
                        ':dep'          => $departLocalite,
                        ':dest'         => $destFinale,
                        ':id_compagnie' => $_SESSION['id_compagnie']
                    ]);

                    $placesPrises = [];
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        foreach (explode('-', $row['numeroPlace']) as $p) {
                            $placesPrises[] = (int)$p;
                        }
                    }

                    $start = 1;
                    $numPlacesAttribues = [];
                    while (count($numPlacesAttribues) < $nombrePassages) {
                        if (!in_array($start, $placesPrises)) {
                            $numPlacesAttribues[] = $start;
                        }
                        $start++;
                    }

                    $numPlace = implode('-', $numPlacesAttribues);
                }

                // Le numéro de billet ne vient jamais du client : régénéré et vérifié côté
                // serveur (cf. genererNumeroBilletUnique) pour éviter tout doublon.
                $numeroBillets = $this->genererNumeroBilletUnique();

                // Insertion du billet
                $stmt = $pdo->prepare("INSERT INTO billets (id_client,idUser, numeroBillets, jourVoyage, Heur_departs,
                              nombrePassages, destinationId, departId, date_expiration, numeroPlace,
                              date_reservation, status_reservation, validation_billets ,id_compagnie,num_gare)
            VALUES (:cl, :idUser, :num, :j, :h, :n, :dest, :dep, :exp, :place, :res, :stat, :validation_billets, :id_compagnie,:num_gare)");
                $stmt->execute([
                    ':cl'    => $idClient,
                    ':idUser' => $_SESSION['id_utilisateur'],
                    ':num'   => $numeroBillets,
                    ':j'     => $jourVoyage,
                    ':h'     => $programme,
                    ':n'     => $nombrePassages,
                    ':dest'  => $destFinale,
                    ':dep'   => $departLocalite,
                    ':exp'   => date('Y-m-d', strtotime($jourVoyage . ' +1 week')),
                    ':place' => $numPlace,
                    ':res'   => date('Ymd'),
                    ':stat'  => 'presentiel',
                    ':validation_billets' => 'valider',
                    ':id_compagnie' => $_SESSION['id_compagnie'],
                    ':num_gare' => $numeroGareDepart
                ]);

                $idBilletCree = (int) $pdo->lastInsertId();

                // Màj car pour aujourd'hui
                if ($jourVoyage == $aujourdhui) {
                    $stmt = $pdo->prepare("UPDATE car SET nbr_place_reserve = nbr_place_reserve + :n WHERE id_car = :num");
                    $stmt->execute([
                        ':n'   => (int)$nombrePassages,
                        ':num' => $idCarProgrammer
                    ]);

                    if ($stmt->rowCount() === 0) {
                        $pdo->rollBack();
                        $this->set_flash("Échec mise à jour du car.", "danger");
                        return false;
                    }
                }

                // === Alimentation de la caisse individuelle de l'utilisateur ===
                $caisseModel = new Caisse_utilisateur();
                $idBilletCreeTemp = $idBilletCree ?? (int)$pdo->lastInsertId();
                $creditOk = $caisseModel->crediterBillet(
                    $pdo,
                    (int)$_SESSION['id_utilisateur'],
                    $prixUtilise,
                    $numeroBillets,
                    $idBilletCree
                );

                if ($creditOk === false) {
                    $pdo->rollBack();
                    $this->set_flash("Opération bloquée : Aucune caisse ouverte pour votre compte. Veuillez ouvrir votre caisse d'abord.", "danger");
                    return false;
                }

                // === Bagages optionnels ajoutés au moment de la vente (cf. app/models/Bagage.php) ===
                // Pas de formule automatique : le prix est saisi à la main par l'agent après
                // pesée réelle. Une seule ligne de caisse pour le total (pas une par bagage).
                $bagagePrixListe = $_POST['bagage_prix'] ?? [];
                if (is_array($bagagePrixListe) && !empty($bagagePrixListe)) {
                    $bagageDescriptions = $_POST['bagage_description'] ?? [];
                    $bagagePoidsListe   = $_POST['bagage_poids'] ?? [];
                    $bagageModel        = new Bagage();
                    $totalBagages       = 0.0;

                    foreach ($bagagePrixListe as $i => $prixBagage) {
                        if ($prixBagage === '' || $prixBagage === null) {
                            continue;
                        }
                        $prixBagage = (float)$prixBagage;
                        if ($prixBagage < 0) {
                            continue;
                        }
                        $bagageModel->saveBagageAvecPdo(
                            $pdo,
                            $idBilletCree,
                            $bagageDescriptions[$i] ?? null,
                            $bagagePoidsListe[$i] ?? null,
                            $prixBagage
                        );
                        $totalBagages += $prixBagage;
                    }

                    if ($totalBagages > 0) {
                        $creditBagageOk = $caisseModel->crediterBagage(
                            $pdo,
                            (int)$_SESSION['id_utilisateur'],
                            $totalBagages,
                            $numeroBillets,
                            $idBilletCree
                        );
                        if ($creditBagageOk === false) {
                            $pdo->rollBack();
                            $this->set_flash("Opération bloquée : Aucune caisse ouverte pour votre compte. Veuillez ouvrir votre caisse d'abord.", "danger");
                            return false;
                        }
                    }
                }

                // Maintenir la caisse d'agence pour compatibilité (bilan global)
                $stmtAgence = $pdo->prepare("
                    SELECT c.id_caisse FROM caisse c
                    INNER JOIN agence a ON c.id_agence = a.idAgence
                    WHERE c.id_compagnie = :ic AND a.localite = :ville
                      AND a.numeroGare = :ng AND c.status_caisse = 1 LIMIT 1
                ");
                $stmtAgence->execute([':ic' => $_SESSION['id_compagnie'], ':ville' => $departLocalite, ':ng' => $numeroGareDepart]);
                $caisseAgence = $stmtAgence->fetch(PDO::FETCH_ASSOC);
                if ($caisseAgence) {
                    $pdo->prepare("UPDATE caisse SET montant_billets = montant_billets + :m WHERE id_caisse = :id")
                        ->execute([':m' => $prixUtilise, ':id' => $caisseAgence['id_caisse']]);
                }

                $pdo->commit();
                $this->set_flash("Réservation enregistrée avec succès et caisse alimentée.", "info");
                $redirectUrl = strtok($_SERVER['REQUEST_URI'], '?');
                $params = [];
                if (($_SESSION['droit'] ?? null) === 'Admin' && $idAgenceDepart) {
                    $params['idDepart'] = $idAgenceDepart;
                }
                // Le ticket est la preuve de paiement remise au client : imprimé automatiquement
                // (cf. thermal-print.js) dès que la page de résultat se charge, sans clic supplémentaire.
                $params['billetImprime'] = $idBilletCree;
                $redirectUrl .= '?' . http_build_query($params);
                header("Location: " . $redirectUrl);
                exit;
            } catch (Throwable $e) {
                $pdo->rollBack();
                $this->set_flash("Erreur SQL : " . $e->getMessage(), "danger");
                return false;
            }
        }
    }
