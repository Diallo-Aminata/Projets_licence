 <?php
  class Liste_du_jour extends Model
  {

    // Heure de départ "en cours" pour cette gare : la prochaine heure programmée non
    // encore dépassée, ou la dernière de la journée si toutes sont déjà passées.
    // Remplace une ancienne grille d'horaires codée en dur dans la vue (05:00, 06:00,
    // 08:00...) qui ne correspondait qu'aux horaires d'une seule compagnie et rendait
    // invisibles les billets de toute compagnie ayant configuré d'autres heures.
    public function getHeureDepartCourante($villeDepart, $idCompagnie)
    {
      $heures = $this->fetchAll(
        "SELECT DISTINCT p.heureDepart
         FROM programmer p
         LEFT JOIN agence a1 ON p.idDepart = a1.idAgence
         WHERE a1.localite = :depart AND p.id_compagnie = :id_compagnie
         ORDER BY p.heureDepart",
        [':depart' => $villeDepart, ':id_compagnie' => $idCompagnie]
      );

      if (empty($heures)) {
        return null;
      }

      $current = date('H:i:s');
      foreach ($heures as $row) {
        if ($row['heureDepart'] >= $current) {
          return $row['heureDepart'];
        }
      }

      return end($heures)['heureDepart'];
    }

    // $idDepart à null : Admin (pas de gare fixe en session) voit les destinations de
    // toute la compagnie, plutôt qu'une liste vide. $numeroGare : précise la gare exacte
    // quand une ville a plusieurs gares (ex. "Segou" Gare I et Gare II) ; sans ça, un chef
    // d'escale verrait aussi les destinations de l'autre gare partageant le même nom de ville.
    public function getDestinations($idDepart, $idCompagnie, $numeroGare = null)
    {
      // programmer.idDepart/idDestination sont des idAgence : on les relie à la table agence
      // pour comparer sur la localité (billets.destinationId est stocké comme un nom de localité).
      $sql = "SELECT DISTINCT a2.localite AS idDestination
            FROM programmer p
            INNER JOIN agence a1 ON p.idDepart = a1.idAgence
            INNER JOIN agence a2 ON p.idDestination = a2.idAgence
            WHERE p.id_compagnie = :idCompagnie";
      $params = [':idCompagnie' => $idCompagnie];

      if ($idDepart !== null) {
        $sql .= " AND a1.localite = :idDepart";
        $params[':idDepart'] = $idDepart;
      }

      if ($numeroGare !== null) {
        $sql .= " AND a1.numeroGare = :numeroGare";
        $params[':numeroGare'] = $numeroGare;
      }

      $sql .= " ORDER BY a2.localite";
      return $this->fetchAll($sql, $params);
    }

    // $villeDepart à null : Admin voit les billets de toute la compagnie (toutes gares),
    // les autres rôles restent filtrés sur leur propre gare de session. $numeroGare : précise
    // la gare exacte quand une ville a plusieurs gares (le nom de ville seul ne suffit pas
    // à distinguer "Segou" Gare I de "Segou" Gare II).
    public function listeBillets($villeDepart = null, $numeroGare = null)
    {
      $id_compagnie = $_SESSION['id_compagnie'];
      $where = 'billets.id_compagnie = :id_compagnie AND billets.validation_billets = :validation';
      $params = [
        'id_compagnie' => $id_compagnie,
        'validation'   => 'valider'
      ];

      if ($villeDepart !== null) {
        $where .= ' AND billets.departId = :depart';
        $params['depart'] = $villeDepart;
      }

      if ($numeroGare !== null) {
        $where .= ' AND billets.num_gare = :numeroGare';
        $params['numeroGare'] = $numeroGare;
      }

      $where .= ' ORDER BY billets.idBillets DESC LIMIT 10';

      return $this->FetchSelectWheres(
        '*',
        'billets inner join client on billets.id_client = client.idClient',
        $where,
        $params
      );
    }

    public function getBilletById($idBillets)
    {
      // Filtré par compagnie de session : empêche un utilisateur de consulter/modifier
      // un billet d'une autre compagnie en changeant simplement l'ID dans l'URL/le formulaire.
      //
      // billets, client ET utilisateur ont chacune une colonne id_compagnie : un "SELECT *"
      // les fait entrer en collision et PDO ne garde que celle de la DERNIERE table jointe
      // (utilisateur.id_compagnie, souvent NULL) — tous les appelants qui filtrent ensuite
      // une UPDATE/DELETE sur $billet->id_compagnie echouaient alors silencieusement (0 ligne
      // affectee, aucune erreur). On ne selectionne donc que billets.* + les quelques colonnes
      // non ambigues realmenet utilisees ailleurs (nom client, montant paye, nom du vendeur).
      $sql = "SELECT billets.*, client.Client, client.montant_payer, utilisateur.utilisateurs
        FROM billets inner join client on billets.id_client = client.idClient
        inner join utilisateur on utilisateur.idUser = billets.idUser
        WHERE idBillets = :id AND billets.id_compagnie = :id_compagnie";
      $stmt = $this->connect()->prepare($sql);
      $stmt->execute([
        ':id' => $idBillets,
        ':id_compagnie' => $_SESSION['id_compagnie'] ?? null
      ]);
      return $stmt->fetch(PDO::FETCH_OBJ);
    }
    // public function getBilletById($idBillets)
    // {
    //   $sql = "SELECT 
    //             b.idBillets,
    //             b.numeroBillets,
    //             b.jourvoyage,
    //             b.heur_departs,
    //             b.numeroPlace,
    //             b.montant,
    //             c.nom AS nom_client,
    //             t.depart AS ville_depart,
    //             t.destination AS ville_destination,
    //             co.nom AS nom_compagnie,
    //             co.slogant,
    //             co.logo
    //         FROM billets b
    //         JOIN client c ON b.id_client = c.id

    //         WHERE b.idBillets = :id";

    //   $stmt = $this->connect()->prepare($sql);
    //   $stmt->execute([':id' => $idBillets]);
    //   return $stmt->fetch(PDO::FETCH_OBJ);
    // }


    // $villeDepart à null/vide : Admin voit les heures de toute la compagnie pour cette destination.
    // $numeroGare : précise la gare exacte quand une ville a plusieurs gares.
    public  function getHeures($destinationId, $villeDepart = null, $numeroGare = null)
    {
      // programmer.idDepart/idDestination sont des idAgence : on les relie à la table agence
      // pour comparer sur la localité, comme dans getDestinations().
      $sql = "SELECT DISTINCT p.heureDepart
              FROM programmer p
              INNER JOIN agence a1 ON p.idDepart = a1.idAgence
              INNER JOIN agence a2 ON p.idDestination = a2.idAgence
              WHERE a2.localite = :destinationId
                AND p.id_compagnie = :id_compagnie";
      $params = [
        ':destinationId' => $destinationId,
        ':id_compagnie'  => $_SESSION['id_compagnie']
      ];

      if (!empty($villeDepart)) {
        $sql .= " AND a1.localite = :villeDepart";
        $params[':villeDepart'] = $villeDepart;
      }

      if (!empty($numeroGare)) {
        $sql .= " AND a1.numeroGare = :numeroGare";
        $params[':numeroGare'] = $numeroGare;
      }

      $sql .= " ORDER BY p.heureDepart";

      $stmt = $this->connect()->prepare($sql);
      $stmt->execute($params);
      $results = $stmt->fetchAll(PDO::FETCH_COLUMN); // Un tableau avec toutes les heures

      return $results;
    }
    public function updateBillet($data)
    {
      $pdo = $this->connect();
      $id_compagnie = $_SESSION['id_compagnie'] ?? null;

      // id_client vient toujours du billet vérifié (appelant), jamais directement du POST,
      // pour empêcher d'écraser le nom d'un client arbitraire via un id_client falsifié.
      $stmtClient = $pdo->prepare("UPDATE client SET Client = :client WHERE idClient = :id_client");
      $stmtClient->execute([
        ':client'    => $data['Client'],
        ':id_client' => $data['id_client'],
      ]);

      $stmtBillet = $pdo->prepare("UPDATE billets SET date_expiration = :date_expiration WHERE idBillets = :idBillets AND id_compagnie = :id_compagnie");
      $stmtBillet->execute([
        ':date_expiration' => $data['date_expiration'],
        ':idBillets'       => $data['idBillets'],
        ':id_compagnie'    => $id_compagnie,
      ]);

      if ($stmtBillet->rowCount() > 0) {
        $this->set_flash("Billet modifié avec succès", "success");
      } else {
        $this->set_flash("Aucun billet correspondant n'a été modifié.", "warning");
      }
    }

    // Reporte un billet à une nouvelle date/heure en maintenant les compteurs de places
    // cohérents : libère la place sur l'ANCIEN créneau (car ou suivis selon le jour) puis
    // la réserve sur le NOUVEAU créneau, avec les mêmes contrôles de capacité que la vente
    // initiale (Add_billet::saveBillets). Avant cette correction, seule la date/heure du
    // billet était modifiée : la place restait bloquée à tort sur l'ancien créneau et
    // n'était jamais décomptée sur le nouveau (risque de survente).
    public function reporte_voyage($data)
    {
      $pdo = $this->connect();
      $id_compagnie = $_SESSION['id_compagnie'] ?? null;

      try {
        $pdo->beginTransaction();

        // Verrouille le billet le temps de la transaction pour éviter qu'une annulation ou
        // une vente concurrente sur le même créneau ne modifie les compteurs en parallèle.
        $stmt = $pdo->prepare(
          "SELECT * FROM billets WHERE idBillets = :id AND id_compagnie = :id_compagnie LIMIT 1 FOR UPDATE"
        );
        $stmt->execute([':id' => $data['idBillets'], ':id_compagnie' => $id_compagnie]);
        $billet = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$billet) {
          $pdo->rollBack();
          $this->set_flash("Aucune modification effectuée : billet introuvable.", "warning");
          return false;
        }

        date_default_timezone_set('Africa/Bamako');
        $aujourdhui  = date('Y-m-d');
        $maxJour     = date('Y-m-d', strtotime('+6 days'));
        $ancienJour  = date('Y-m-d', strtotime($billet['jourVoyage']));
        $nouveauJour = date('Y-m-d', strtotime($data['jourVoyage']));

        // Idempotence anti double-soumission : si le billet est DEJA sur exactement ce
        // creneau (double-clic sur "Modifier", ou deux appels concurrents avec la meme
        // cible - confirmerReportBillet() et reporter() passent tous les deux par ici),
        // la 2e execution, une fois debloquee par le verrou FOR UPDATE ci-dessus, verrait
        // ce creneau comme "l'ancien" et le libererait une seconde fois a tort (la place
        // n'a jamais ete occupee deux fois). On s'arrete ici, sans toucher aux compteurs.
        if ($ancienJour === $nouveauJour && $billet['Heur_departs'] === $data['Heur_departs']) {
          $pdo->commit();
          $this->set_flash("Le billet est déjà programmé sur cette date et cette heure.", "info");
          return true;
        }
        $nombrePassages = (int)$billet['nombrePassages'];

        $mainDest = $this->resolveDestinationPrincipale(
          $billet['departId'], $billet['Heur_departs'], $billet['destinationId'], $id_compagnie
        );

        // Gare précise du billet (idAgence) : indispensable pour ne pas résoudre le car
        // d'une autre gare partageant la même localité. Voir ajout_id_agence_programmation_voyage.sql.
        $agenceBillet = $this->fetchOne(
          "SELECT idAgence FROM agence WHERE localite = :l AND numeroGare = :ng AND id_compagnie = :c LIMIT 1",
          [':l' => $billet['departId'], ':ng' => $billet['num_gare'], ':c' => $id_compagnie]
        );
        $idAgenceBillet = $agenceBillet['idAgence'] ?? null;

        // 1) Libère la place sur l'ANCIEN créneau, s'il était suivi (aujourd'hui ou dans la semaine).
        if ($ancienJour === $aujourdhui) {
          $rowProg = $this->fetchOne(
            "SELECT id_car_programmer FROM programmation_voyage
             WHERE id_horaire = :h AND date_enregistre = :d AND id_trajet = :t
             AND localite_user = :l AND id_agence = :a AND id_compagnie = :c LIMIT 1",
            [':h' => $billet['Heur_departs'], ':d' => $ancienJour, ':t' => $mainDest, ':l' => $billet['departId'], ':a' => $idAgenceBillet, ':c' => $id_compagnie]
          );
          if ($rowProg) {
            $stmt = $pdo->prepare("SELECT nbr_place_reserve FROM car WHERE id_car = :id FOR UPDATE");
            $stmt->execute([':id' => $rowProg['id_car_programmer']]);
            $car = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($car) {
              $nouveauReserve = max(0, (int)$car['nbr_place_reserve'] - $nombrePassages);
              $pdo->prepare("UPDATE car SET nbr_place_reserve = :n WHERE id_car = :id")
                ->execute([':n' => $nouveauReserve, ':id' => $rowProg['id_car_programmer']]);
            }
          }
        } elseif ($ancienJour > $aujourdhui) {
          // J+1 à J+6 : la place est comptabilisée dans la table suivis
          $stmt = $pdo->prepare(
            "SELECT idSuivis, place_reserve FROM suivis
             WHERE depart = :dep AND destination = :dest AND heur_depart = :h
             AND date_reservation = :jr AND id_compagnie = :id_compagnie LIMIT 1 FOR UPDATE"
          );
          $stmt->execute([':dep' => $billet['departId'], ':dest' => $mainDest, ':h' => $billet['Heur_departs'], ':jr' => $ancienJour, ':id_compagnie' => $id_compagnie]);
          $suivi = $stmt->fetch(PDO::FETCH_ASSOC);
          if ($suivi) {
            $nouveauReserve = max(0, (int)$suivi['place_reserve'] - $nombrePassages);
            $pdo->prepare("UPDATE suivis SET place_reserve = :n WHERE idSuivis = :id")
              ->execute([':n' => $nouveauReserve, ':id' => $suivi['idSuivis']]);
          }
        }

        // 2) Réserve la place sur le NOUVEAU créneau, s'il est suivi (aujourd'hui ou dans la semaine).
        //    Recalcule aussi le numéro de place : celui de l'ancien créneau n'a plus de sens ici.
        $numPlace = $billet['numeroPlace'];

        if ($nouveauJour === $aujourdhui) {
          $rowProg = $this->fetchOne(
            "SELECT id_car_programmer FROM programmation_voyage
             WHERE id_horaire = :h AND date_enregistre = :d AND id_trajet = :t
             AND localite_user = :l AND id_agence = :a AND id_compagnie = :c LIMIT 1",
            [':h' => $data['Heur_departs'], ':d' => $nouveauJour, ':t' => $mainDest, ':l' => $billet['departId'], ':a' => $idAgenceBillet, ':c' => $id_compagnie]
          );
          if (!$rowProg) {
            $pdo->rollBack();
            $this->set_flash("Aucun car programmé pour cette heure et ce trajet à la nouvelle date.", "danger");
            return false;
          }
          $stmt = $pdo->prepare("SELECT nbr_place, nbr_place_reserve FROM car WHERE id_car = :id FOR UPDATE");
          $stmt->execute([':id' => $rowProg['id_car_programmer']]);
          $car = $stmt->fetch(PDO::FETCH_ASSOC);
          if (!$car) {
            $pdo->rollBack();
            $this->set_flash("Car introuvable pour la nouvelle date.", "danger");
            return false;
          }
          $placesDispo = $car['nbr_place'] - $car['nbr_place_reserve'];
          if ($nombrePassages > $placesDispo) {
            $pdo->rollBack();
            $this->set_flash("Places insuffisantes sur le nouveau créneau : $placesDispo restantes.", "danger");
            return false;
          }
          $pdo->prepare("UPDATE car SET nbr_place_reserve = nbr_place_reserve + :n WHERE id_car = :id")
            ->execute([':n' => $nombrePassages, ':id' => $rowProg['id_car_programmer']]);

          $start = (int)$car['nbr_place_reserve'] + 1;
          $end   = $start + $nombrePassages - 1;
          $numPlace = ($nombrePassages == 1) ? "$start" : "$start-$end";
        } elseif ($nouveauJour > $aujourdhui) {
          // J+1 à J+6 : gestion via la table suivis
          $stmt = $pdo->prepare("SELECT place_minumale FROM place_minumale WHERE id_compagnie = :ic LIMIT 1");
          $stmt->execute([':ic' => $id_compagnie]);
          $rowPlace = $stmt->fetch();
          $placeTotale = $rowPlace ? (int)$rowPlace['place_minumale'] : 0;

          $stmt = $pdo->prepare(
            "SELECT idSuivis, place_totals, place_reserve FROM suivis
             WHERE depart = :dep AND destination = :dest AND heur_depart = :h
             AND date_reservation = :jr AND id_compagnie = :id_compagnie LIMIT 1 FOR UPDATE"
          );
          $stmt->execute([':dep' => $billet['departId'], ':dest' => $mainDest, ':h' => $data['Heur_departs'], ':jr' => $nouveauJour, ':id_compagnie' => $id_compagnie]);
          $suivi = $stmt->fetch(PDO::FETCH_ASSOC);

          if ($suivi) {
            $placesDispo = $suivi['place_totals'] - $suivi['place_reserve'];
            if ($nombrePassages > $placesDispo) {
              $pdo->rollBack();
              $this->set_flash("Places insuffisantes sur le nouveau créneau : $placesDispo restantes.", "danger");
              return false;
            }
            $pdo->prepare("UPDATE suivis SET place_reserve = place_reserve + :n WHERE idSuivis = :id")
              ->execute([':n' => $nombrePassages, ':id' => $suivi['idSuivis']]);
          } else {
            if ($placeTotale <= 0) {
              $pdo->rollBack();
              $this->set_flash("Erreur : nombre de places minimales non défini.", "danger");
              return false;
            }
            if ($nombrePassages > $placeTotale) {
              $pdo->rollBack();
              $this->set_flash("Places insuffisantes sur le nouveau créneau : $placeTotale restantes.", "danger");
              return false;
            }
            $pdo->prepare(
              "INSERT INTO suivis (place_reserve, place_totals, depart, destination, heur_depart, date_reservation, id_compagnie)
               VALUES (:n, :total, :dep, :dest, :h, :jr, :id_compagnie)"
            )->execute([
              ':n' => $nombrePassages, ':total' => $placeTotale, ':dep' => $billet['departId'], ':dest' => $mainDest,
              ':h' => $data['Heur_departs'], ':jr' => $nouveauJour, ':id_compagnie' => $id_compagnie
            ]);
          }

          $stmt = $pdo->prepare("SELECT numeroPlace FROM billets
            WHERE jourVoyage = :j AND Heur_departs = :h AND departId = :dep AND destinationId = :dest
            AND id_compagnie = :id_compagnie AND idBillets != :idBillets");
          $stmt->execute([
            ':j' => $nouveauJour, ':h' => $data['Heur_departs'], ':dep' => $billet['departId'],
            ':dest' => $mainDest, ':id_compagnie' => $id_compagnie, ':idBillets' => $data['idBillets']
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

        // 3) Met à jour le billet lui-même.
        $stmtUpd = $pdo->prepare(
          "UPDATE billets
           SET jourVoyage = :jourVoyage, Heur_departs = :Heur_departs, numeroPlace = :numeroPlace, date_repporte = CURDATE()
           WHERE idBillets = :idBillets AND id_compagnie = :id_compagnie"
        );
        $stmtUpd->execute([
          ':jourVoyage'   => $data['jourVoyage'],
          ':Heur_departs' => $data['Heur_departs'],
          ':numeroPlace'  => $numPlace,
          ':idBillets'    => $data['idBillets'],
          ':id_compagnie' => $id_compagnie,
        ]);

        if ($stmtUpd->rowCount() === 0) {
          $pdo->rollBack();
          $this->set_flash("Aucune modification effectuée : billet introuvable.", "warning");
          return false;
        }

        $pdo->commit();
        $this->set_flash("Voyage reporté avec succès, places mises à jour.", "primary");
        return true;
      } catch (Throwable $e) {
        $pdo->rollBack();
        $this->set_flash("Erreur lors du report : " . $e->getMessage(), "danger");
        return false;
      }
    }

    // Retrouve la destination FINALE du trajet à partir du nom stocké sur le billet, qui peut
    // être soit la destination finale, soit le nom d'une escale intermédiaire (prix différent,
    // mais même car/même créneau de programmation).
    private function resolveDestinationPrincipale($depart, $heure, $destinationId, $id_compagnie)
    {
      $direct = $this->fetchOne(
        "SELECT a2.localite FROM programmer p
         LEFT JOIN agence a1 ON p.idDepart = a1.idAgence
         LEFT JOIN agence a2 ON p.idDestination = a2.idAgence
         WHERE a1.localite = :dep AND a2.localite = :dest AND p.heureDepart = :heure AND p.id_compagnie = :id_compagnie
         LIMIT 1",
        [':dep' => $depart, ':dest' => $destinationId, ':heure' => $heure, ':id_compagnie' => $id_compagnie]
      );
      if ($direct) {
        return $destinationId;
      }

      $viaEscale = $this->fetchOne(
        "SELECT a2.localite AS mainDest FROM ligneTrajet lt
         JOIN escale e ON e.id_escale = lt.id_escales
         JOIN programmer p ON p.idProgrammer = lt.id_trajets AND lt.type_trajet = 'programmer'
         LEFT JOIN agence a1 ON p.idDepart = a1.idAgence
         LEFT JOIN agence a2 ON p.idDestination = a2.idAgence
         WHERE e.escales = :dest AND a1.localite = :dep AND p.heureDepart = :heure AND p.id_compagnie = :id_compagnie
         LIMIT 1",
        [':dest' => $destinationId, ':dep' => $depart, ':heure' => $heure, ':id_compagnie' => $id_compagnie]
      );

      return $viaEscale['mainDest'] ?? $destinationId;
    }

    // Étape 1 (chef d'escale uniquement — voir Liste_du_jours::annuler()) : soumet une
    // demande d'annulation sans toucher à la place ni à la caisse. Un simple Utilisateur ne
    // peut même pas atteindre cette méthode (bloqué côté contrôleur). Seul un Admin peut
    // ensuite confirmer (confirmerAnnulationBillet) ou rejeter (rejeterAnnulationBillet).
    public function demanderAnnulationBillet($idBillets, $motif = '')
    {
      if (!csrf_verify()) {
        $this->set_flash("Session expirée, veuillez réessayer.", "danger");
        return false;
      }

      $billet = $this->getBilletById($idBillets); // déjà filtré par compagnie de session
      if (!$billet) {
        $this->set_flash("Billet introuvable.", "danger");
        return false;
      }

      if (!in_array($billet->status_billets ?? null, [null, ''], true)) {
        $this->set_flash("Ce billet est déjà annulé ou une demande (annulation/report) est déjà en cours.", "warning");
        return false;
      }

      date_default_timezone_set('Africa/Bamako');
      $jourVoyage = date('Y-m-d', strtotime($billet->jourVoyage));
      if ($jourVoyage < date('Y-m-d')) {
        $this->set_flash("Impossible de demander l'annulation d'un billet dont le voyage est déjà passé.", "danger");
        return false;
      }

      $ok = $this->insertion_update_simples(
        "UPDATE billets SET status_billets = 'annulation_demandee', motif_annulation = :motif,
            demande_annulation_par = :par, demande_annulation_le = NOW()
         WHERE idBillets = :id AND id_compagnie = :id_compagnie",
        [
          ':motif' => $motif !== '' ? $motif : null,
          ':par' => $_SESSION['id_utilisateur'] ?? null,
          ':id' => $idBillets,
          ':id_compagnie' => $billet->id_compagnie
        ]
      );

      if ($ok) {
        $this->set_flash("Demande d'annulation envoyée : un Admin doit la confirmer avant que la place et l'argent ne soient libérés.", "success");
        return true;
      }
      $this->set_flash("Erreur lors de l'envoi de la demande d'annulation.", "danger");
      return false;
    }

    // Demandes en attente de confirmation, pour l'écran Admin dédié.
    public function getDemandesAnnulationEnAttente($id_compagnie)
    {
      return $this->fetchAll(
        "SELECT b.idBillets, b.numeroBillets, b.jourVoyage, b.Heur_departs, b.departId, b.destinationId,
                b.motif_annulation, b.demande_annulation_le, c.Client, c.montant_payer,
                u.utilisateurs AS demandeur
         FROM billets b
         INNER JOIN client c ON b.id_client = c.idClient
         LEFT JOIN utilisateur u ON u.idUser = b.demande_annulation_par
         WHERE b.id_compagnie = :id_compagnie AND b.status_billets = 'annulation_demandee'
         ORDER BY b.demande_annulation_le ASC",
        [':id_compagnie' => $id_compagnie]
      );
    }

    // Étape 2a (Admin uniquement) : rejette une demande d'annulation en attente. Le billet
    // redevient actif normalement, sans laisser de trace de la demande sur le billet lui-même.
    public function rejeterAnnulationBillet($idBillets)
    {
      if (!csrf_verify()) {
        $this->set_flash("Session expirée, veuillez réessayer.", "danger");
        return false;
      }

      $billet = $this->getBilletById($idBillets);
      if (!$billet || ($billet->status_billets ?? null) !== 'annulation_demandee') {
        $this->set_flash("Aucune demande d'annulation en attente pour ce billet.", "warning");
        return false;
      }

      $ok = $this->insertion_update_simples(
        "UPDATE billets SET status_billets = NULL, motif_annulation = NULL,
            demande_annulation_par = NULL, demande_annulation_le = NULL
         WHERE idBillets = :id AND id_compagnie = :id_compagnie",
        [':id' => $idBillets, ':id_compagnie' => $billet->id_compagnie]
      );

      if ($ok) {
        $this->set_flash("Demande d'annulation rejetée : le billet reste actif.", "info");
        return true;
      }
      $this->set_flash("Erreur lors du rejet de la demande.", "danger");
      return false;
    }

    // Étape 2b (Admin uniquement — voir Liste_du_jours::annuler()) : confirme l'annulation
    // d'un billet, qu'elle vienne d'une demande d'un chef d'escale (statut
    // 'annulation_demandee', motif déjà renseigné) ou d'une annulation directe par l'Admin
    // lui-même (motif fourni ici). Restaure la place vendue (car ou suivis selon le jour),
    // marque le billet comme annulé et enregistre le remboursement comme une dépense
    // formelle ("Remboursement annulation") sur la caisse actuellement ouverte de la gare,
    // pour garder une trace auditable de la sortie d'argent (si aucune caisse n'est ouverte,
    // l'annulation a quand même lieu mais la caisse devra être ajustée manuellement).
    public function confirmerAnnulationBillet($idBillets, $motifSiDirect = '')
    {
      if (!csrf_verify()) {
        $this->set_flash("Session expirée, veuillez réessayer.", "danger");
        return false;
      }

      $billet = $this->getBilletById($idBillets); // déjà filtré par compagnie de session
      if (!$billet) {
        $this->set_flash("Billet introuvable.", "danger");
        return false;
      }

      if (($billet->status_billets ?? null) === 'annule') {
        $this->set_flash("Ce billet est déjà annulé.", "warning");
        return false;
      }

      date_default_timezone_set('Africa/Bamako');
      $aujourdhui = date('Y-m-d');
      $jourVoyage = date('Y-m-d', strtotime($billet->jourVoyage));

      if ($jourVoyage < $aujourdhui) {
        $this->set_flash("Impossible d'annuler un billet dont le voyage est déjà passé.", "danger");
        return false;
      }

      $motif = ($billet->status_billets ?? null) === 'annulation_demandee'
        ? $billet->motif_annulation
        : ($motifSiDirect !== '' ? $motifSiDirect : null);

      $pdo = $this->connect();

      try {
        $pdo->beginTransaction();

        // Verrou anti double-traitement : si deux personnes confirment (ou une confirme et
        // une autre rejette) la meme annulation au meme moment, seule la premiere doit passer.
        // Comparaison "compare-and-swap" sur le statut lu plus haut (<=> gere le cas NULL) :
        // si une autre requete a deja modifie status_billets entre-temps, rowCount() = 0 et on
        // s'arrete avant de toucher aux places/a la caisse (double remboursement sinon).
        $stmtClaim = $pdo->prepare(
          "UPDATE billets SET status_billets = 'annulation_en_cours'
           WHERE idBillets = :id AND id_compagnie = :id_compagnie AND status_billets <=> :ancien"
        );
        $stmtClaim->execute([
          ':id' => $idBillets,
          ':id_compagnie' => $billet->id_compagnie,
          ':ancien' => $billet->status_billets ?? null,
        ]);
        if ($stmtClaim->rowCount() === 0) {
          $pdo->rollBack();
          $this->set_flash("Ce billet a déjà été traité entre-temps par quelqu'un d'autre.", "warning");
          return false;
        }

        $mainDest = $this->resolveDestinationPrincipale(
          $billet->departId,
          $billet->Heur_departs,
          $billet->destinationId,
          $billet->id_compagnie
        );

        // Gare précise du billet (idAgence) : indispensable pour ne pas résoudre le car
        // d'une autre gare partageant la même localité. Voir ajout_id_agence_programmation_voyage.sql.
        $agenceBillet = $this->fetchOne(
          "SELECT idAgence FROM agence WHERE localite = :l AND numeroGare = :ng AND id_compagnie = :c LIMIT 1",
          [':l' => $billet->departId, ':ng' => $billet->num_gare, ':c' => $billet->id_compagnie]
        );
        $idAgenceBillet = $agenceBillet['idAgence'] ?? null;

        if ($jourVoyage == $aujourdhui) {
          // Aujourd'hui : la place vendue est comptabilisée sur car.nbr_place_reserve
          $rowProg = $this->fetchOne(
            "SELECT id_car_programmer FROM programmation_voyage
             WHERE id_horaire = :h AND date_enregistre = :d AND id_trajet = :t
             AND localite_user = :l AND id_agence = :a AND id_compagnie = :c LIMIT 1",
            [
              ':h' => $billet->Heur_departs,
              ':d' => $jourVoyage,
              ':t' => $mainDest,
              ':l' => $billet->departId,
              ':a' => $idAgenceBillet,
              ':c' => $billet->id_compagnie
            ]
          );

          if ($rowProg) {
            $stmt = $pdo->prepare("SELECT nbr_place_reserve FROM car WHERE id_car = :id FOR UPDATE");
            $stmt->execute([':id' => $rowProg['id_car_programmer']]);
            $car = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($car) {
              $nouveauReserve = max(0, (int)$car['nbr_place_reserve'] - (int)$billet->nombrePassages);
              $upd = $pdo->prepare("UPDATE car SET nbr_place_reserve = :n WHERE id_car = :id");
              $upd->execute([':n' => $nouveauReserve, ':id' => $rowProg['id_car_programmer']]);
            }
          }
        } else {
          // Demain : la place vendue est comptabilisée dans la table suivis
          $stmt = $pdo->prepare(
            "SELECT idSuivis, place_reserve FROM suivis
             WHERE depart = :dep AND destination = :dest AND heur_depart = :h
             AND date_reservation = :jr AND id_compagnie = :id_compagnie LIMIT 1 FOR UPDATE"
          );
          $stmt->execute([
            ':dep' => $billet->departId,
            ':dest' => $mainDest,
            ':h' => $billet->Heur_departs,
            ':jr' => $jourVoyage,
            ':id_compagnie' => $billet->id_compagnie
          ]);
          $suivi = $stmt->fetch(PDO::FETCH_ASSOC);
          if ($suivi) {
            $nouveauReserve = max(0, (int)$suivi['place_reserve'] - (int)$billet->nombrePassages);
            $upd = $pdo->prepare("UPDATE suivis SET place_reserve = :n WHERE idSuivis = :id");
            $upd->execute([':n' => $nouveauReserve, ':id' => $suivi['idSuivis']]);
          }
        }

        // Marquer le billet comme annulé
        $updBillet = $pdo->prepare(
          "UPDATE billets SET status_billets = 'annule', date_annulation = NOW(), motif_annulation = :motif, annule_par = :annule_par
           WHERE idBillets = :id AND id_compagnie = :id_compagnie"
        );
        $updBillet->execute([
          ':motif' => $motif,
          ':annule_par' => $_SESSION['id_utilisateur'] ?? null,
          ':id' => $idBillets,
          ':id_compagnie' => $billet->id_compagnie
        ]);

        // Caisse actuellement ouverte pour cette gare, si elle existe
        $stmtCaisse = $pdo->prepare("
          SELECT c.id_caisse
          FROM caisse c
          INNER JOIN agence a ON c.id_agence = a.idAgence
          WHERE c.id_compagnie = :id_compagnie
            AND a.localite = :ville
            AND a.numeroGare = :numeroGare
            AND c.status_caisse = 1
          LIMIT 1
        ");
        $stmtCaisse->execute([
          ':id_compagnie' => $billet->id_compagnie,
          ':ville' => $billet->departId,
          ':numeroGare' => $billet->num_gare
        ]);
        $caisse = $stmtCaisse->fetch(PDO::FETCH_ASSOC);

        $montant = (int) preg_replace('/[^\d]/', '', (string) $billet->montant_payer);

        if ($caisse && $montant > 0) {
          // Le remboursement est enregistré comme une dépense formelle (pas une simple
          // déduction silencieuse) : ça garde une trace auditable de l'argent qui sort
          // de la caisse, visible dans le suivi des dépenses de la gare.
          $pdo->prepare(
            "INSERT INTO depense (id_compagnie, id_agence, id_caisse, categorie, libelle, montant, date_depense, id_utilisateur)
             VALUES (:id_compagnie, :id_agence, :id_caisse, 'Remboursement annulation', :libelle, :montant, :date_depense, :id_utilisateur)"
          )->execute([
            ':id_compagnie' => $billet->id_compagnie,
            ':id_agence' => $idAgenceBillet,
            ':id_caisse' => $caisse['id_caisse'],
            ':libelle' => 'Remboursement billet annulé n°' . $billet->numeroBillets,
            ':montant' => $montant,
            ':date_depense' => $aujourdhui,
            ':id_utilisateur' => $_SESSION['id_utilisateur'] ?? null
          ]);

          $pdo->prepare("UPDATE caisse SET montant_depense = montant_depense + :montant WHERE id_caisse = :id_caisse")
            ->execute([':montant' => $montant, ':id_caisse' => $caisse['id_caisse']]);
        }

        $pdo->commit();

        if ($caisse && $montant > 0) {
          $this->set_flash("Billet annulé avec succès. Remboursement de " . number_format($montant, 0, ',', ' ') . " FCFA enregistré comme dépense.", "success");
        } else {
          $this->set_flash("Billet annulé avec succès. Aucune caisse ouverte pour cette gare : le remboursement devra être enregistré manuellement.", "warning");
        }
        return true;
      } catch (Throwable $e) {
        $pdo->rollBack();
        $this->set_flash("Erreur lors de l'annulation : " . $e->getMessage(), "danger");
        return false;
      }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // EMBARQUEMENT (remplace la case a cocher papier de la liste d'embarquement)
    // ─────────────────────────────────────────────────────────────────────────

    // Billets d'un trajet/date donnes, pour l'ecran Embarquement. Les billets annules ou en
    // attente de traitement (annulation/report) sont exclus : rien a embarquer pour eux.
    //
    // Un billet deja embarque disparait de la liste une fois son heure de depart passee
    // (le bus est parti, plus rien a faire dessus) : comportement historique de la liste
    // papier, repris ici. Un billet NON embarque, lui, reste toujours visible meme apres
    // l'heure de depart — on laisse une marge de retard (cf. getBilletsEnRetard(), qui
    // alerte via la cloche apres 30 minutes) plutot que de faire disparaitre silencieusement
    // un client qui n'a pas ete pris en charge.
    public function getBilletsPourEmbarquement($idDepart, $numeroGare, $jourVoyage, $destination = '', $heure = '')
    {
      // Comparaison faite avec l'heure PHP (Africa/Bamako), jamais avec NOW()/CURDATE() cote
      // MySQL : le serveur de base de donnees (hebergement distant LWS) peut tourner sur un
      // fuseau/horloge different, ce qui ferait disparaitre des billets pas encore partis
      // (ex. depart 20h00 deja considere "passe" alors qu'il n'est que 19h55 a Bamako).
      date_default_timezone_set('Africa/Bamako');
      $maintenant = date('Y-m-d H:i:s');

      $where = 'b.id_compagnie = :id_compagnie AND b.jourVoyage = :jour
                 AND (b.status_billets IS NULL OR b.status_billets = \'\')
                 AND (b.statut_embarquement IS NULL OR b.statut_embarquement != \'embarque\'
                      OR TIMESTAMP(b.jourVoyage, b.Heur_departs) >= :maintenant)';
      $params = [':id_compagnie' => $_SESSION['id_compagnie'] ?? null, ':jour' => $jourVoyage, ':maintenant' => $maintenant];

      if ($idDepart !== null) {
        $where .= ' AND b.departId = :depart';
        $params[':depart'] = $idDepart;
      }
      if ($numeroGare !== null) {
        $where .= ' AND b.num_gare = :numeroGare';
        $params[':numeroGare'] = $numeroGare;
      }
      if ($destination !== '') {
        $where .= ' AND b.destinationId = :destination';
        $params[':destination'] = $destination;
      }
      if ($heure !== '') {
        $where .= ' AND b.Heur_departs = :heure';
        $params[':heure'] = $heure;
      }

      return $this->fetchAll(
        "SELECT b.idBillets, b.numeroBillets, b.destinationId, b.Heur_departs, b.numeroPlace,
                b.statut_embarquement, b.embarque_le, c.Client,
                ue.utilisateurs AS embarque_par_nom, pv.decolle_le AS bus_decolle_le
         FROM billets b
         INNER JOIN client c ON b.id_client = c.idClient
         LEFT JOIN utilisateur ue ON ue.idUser = b.embarque_par
         LEFT JOIN programmation_voyage pv ON pv.date_enregistre = b.jourVoyage
                AND pv.id_horaire = b.Heur_departs AND pv.id_trajet = b.destinationId
                AND pv.localite_user = b.departId AND pv.id_compagnie = b.id_compagnie
                AND pv.statut = 'active'
         WHERE $where
         ORDER BY b.Heur_departs, c.Client",
        $params
      );
    }

    // Billets du jour non embarques dont l'heure de depart est passee depuis plus de 30
    // minutes (marge de retard). Alimente la cloche de notification (chef d'escale/Admin) :
    // sans ca, un client absent au depart passerait inapercu une fois que personne n'a
    // pense a verifier manuellement la liste d'embarquement.
    public function getBilletsEnRetard($idDepart, $numeroGare, $id_compagnie)
    {
      // Heure PHP (Africa/Bamako), pas NOW()/CURDATE() cote MySQL : voir le commentaire de
      // getBilletsPourEmbarquement() sur le decalage possible avec l'horloge du serveur DB.
      date_default_timezone_set('Africa/Bamako');
      $aujourdhui = date('Y-m-d');
      $seuilRetard = date('Y-m-d H:i:s', strtotime('-30 minutes'));

      $where = "b.id_compagnie = :id_compagnie AND b.jourVoyage = :jour
                 AND (b.status_billets IS NULL OR b.status_billets = '')
                 AND (b.statut_embarquement IS NULL OR b.statut_embarquement != 'embarque')
                 AND TIMESTAMP(b.jourVoyage, b.Heur_departs) < :seuil";
      $params = [':id_compagnie' => $id_compagnie, ':jour' => $aujourdhui, ':seuil' => $seuilRetard];

      if ($idDepart !== null) {
        $where .= ' AND b.departId = :depart';
        $params[':depart'] = $idDepart;
      }
      if ($numeroGare !== null) {
        $where .= ' AND b.num_gare = :numeroGare';
        $params[':numeroGare'] = $numeroGare;
      }

      return $this->fetchAll(
        "SELECT b.idBillets, b.destinationId, b.Heur_departs, c.Client
         FROM billets b
         INNER JOIN client c ON b.id_client = c.idClient
         WHERE $where
         ORDER BY b.Heur_departs, c.Client",
        $params
      );
    }

    // Le bus associe a ce billet (meme jour/heure/destination/depart) a-t-il deja
    // "decolle" (cf. Programmation_voyage::decollerCar()) ? Le bus programme peut avoir
    // change de creneau (report/modification) : on prend la programmation active la plus
    // recente correspondant exactement au trajet du billet.
    private function busDejaDecolle($billet): bool
    {
      $prog = $this->fetchOne(
        "SELECT decolle_le FROM programmation_voyage
         WHERE date_enregistre = :jour AND id_horaire = :heure AND id_trajet = :dest
           AND localite_user = :depart AND id_compagnie = :id_compagnie AND statut = 'active'
         ORDER BY id_programmation DESC LIMIT 1",
        [
          ':jour' => $billet->jourVoyage, ':heure' => $billet->Heur_departs,
          ':dest' => $billet->destinationId, ':depart' => $billet->departId,
          ':id_compagnie' => $billet->id_compagnie,
        ]
      );
      return !empty($prog['decolle_le']);
    }

    // Logique commune d'embarquement, sans gestion de flash (reutilisee par marquerEmbarque()
    // en solo, marquerEmbarqueLot() en masse, et les endpoints AJAX du controleur).
    private function embarquerBilletInterne($idBillets): array
    {
      $billet = $this->getBilletById($idBillets);
      if (!$billet) {
        return ['ok' => false, 'deja' => false, 'message' => 'Billet introuvable.'];
      }
      if (($billet->statut_embarquement ?? null) === 'embarque') {
        return ['ok' => true, 'deja' => true, 'message' => 'Déjà embarqué.'];
      }
      // Meme garde que le filtre de getBilletsPourEmbarquement() (status_billets IS NULL/vide) :
      // un billet annule ou en cours de report (quelle que soit l'etape — demande, transmis,
      // en_validation — ou d'annulation) n'a pas a etre embarque, meme si la requete arrive
      // directement (rejeu, ancien onglet ouvert) sans passer par la liste affichee a l'ecran.
      if (!in_array($billet->status_billets ?? null, [null, ''], true)) {
        return ['ok' => false, 'deja' => false, 'message' => "Annulé ou en attente de traitement (report/annulation) : impossible de l'embarquer."];
      }
      if ($this->busDejaDecolle($billet)) {
        return ['ok' => false, 'deja' => false, 'message' => "Le bus a déjà décollé : impossible d'embarquer."];
      }

      $ok = $this->insertion_update_simples(
        "UPDATE billets SET statut_embarquement = 'embarque', embarque_le = NOW(), embarque_par = :par
         WHERE idBillets = :id AND id_compagnie = :id_compagnie",
        [':par' => $_SESSION['id_utilisateur'] ?? null, ':id' => $idBillets, ':id_compagnie' => $billet->id_compagnie]
      );

      return $ok
        ? ['ok' => true, 'deja' => false, 'message' => 'Client embarqué.', 'client' => $billet->Client ?? '']
        : ['ok' => false, 'deja' => false, 'message' => "Erreur lors de l'enregistrement de l'embarquement."];
    }

    public function marquerEmbarque($idBillets)
    {
      if (!csrf_verify()) {
        $this->set_flash("Session expirée, veuillez réessayer.", "danger");
        return false;
      }

      $res = $this->embarquerBilletInterne($idBillets);
      $this->set_flash($res['message'], $res['deja'] ? 'warning' : ($res['ok'] ? 'success' : 'danger'));
      return $res['ok'] && !$res['deja'];
    }

    // Embarquement en masse (case a cocher + "Embarquer la selection") : boucle sur la meme
    // logique que marquerEmbarque(), sans flash par billet — un seul resume est renvoye.
    public function marquerEmbarqueLot(array $idsBillets): array
    {
      $succes = 0;
      $deja = 0;
      $echecs = 0;
      $details = [];
      foreach ($idsBillets as $idBillets) {
        $res = $this->embarquerBilletInterne($idBillets);
        if ($res['deja']) {
          $deja++;
        } elseif ($res['ok']) {
          $succes++;
        } else {
          $echecs++;
        }
        $details[] = ['id' => $idBillets, 'ok' => $res['ok'], 'deja' => $res['deja']];
      }
      return ['succes' => $succes, 'deja' => $deja, 'echecs' => $echecs, 'details' => $details];
    }

    // Annule un embarquement marqué par erreur (clic accidentel) : reste possible tant que
    // le bus n'a pas reellement decolle (decollerCar()) — une fois le bus parti, revenir en
    // arriere sur l'embarquement d'un passager qui est physiquement dans le bus n'a plus de sens.
    public function annulerEmbarquementBillet($idBillets)
    {
      if (!csrf_verify()) {
        $this->set_flash("Session expirée, veuillez réessayer.", "danger");
        return false;
      }

      $billet = $this->getBilletById($idBillets);
      if (!$billet || ($billet->statut_embarquement ?? null) !== 'embarque') {
        $this->set_flash("Ce client n'est pas marqué comme embarqué.", "warning");
        return false;
      }
      if ($this->busDejaDecolle($billet)) {
        $this->set_flash("Le bus a déjà décollé : impossible d'annuler cet embarquement.", "warning");
        return false;
      }

      $ok = $this->insertion_update_simples(
        "UPDATE billets SET statut_embarquement = NULL, embarque_le = NULL, embarque_par = NULL
         WHERE idBillets = :id AND id_compagnie = :id_compagnie",
        [':id' => $idBillets, ':id_compagnie' => $billet->id_compagnie]
      );

      if ($ok) {
        $this->set_flash("Embarquement annulé.", "info");
        return true;
      }
      $this->set_flash("Erreur lors de l'annulation de l'embarquement.", "danger");
      return false;
    }

    // Cars "complets" (places reservees >= capacite) ayant encore au moins un passager non
    // embarque ce jour-la : permet d'alerter proactivement (badge menu + banniere embarquement)
    // au lieu d'attendre que quelqu'un pense a verifier manuellement. L'alerte disparait d'elle
    // meme une fois tout le monde embarque (voir la sous-requete EXISTS sur billets), ou une
    // fois l'heure de depart passee (meme instant que la disparition des lignes "embarque" de
    // la liste principale) : passe ce cap, l'alerte n'a plus lieu d'etre, embarquement fait ou pas.
    public function getCarsComplets($idDepart, $numeroGare, $id_compagnie, $jour = null)
    {
      $jour = $jour ?? date('Y-m-d');

      date_default_timezone_set('Africa/Bamako');
      $maintenant = date('Y-m-d H:i:s');

      $idAgence = null;
      if ($idDepart !== null && $numeroGare !== null) {
        $agence = $this->fetchOne(
          "SELECT idAgence FROM agence WHERE localite = :l AND numeroGare = :ng AND id_compagnie = :c LIMIT 1",
          [':l' => $idDepart, ':ng' => $numeroGare, ':c' => $id_compagnie]
        );
        $idAgence = $agence['idAgence'] ?? null;
      }

      $where = "pv.date_enregistre = :jour AND pv.id_compagnie = :id_compagnie AND pv.statut = 'active'
                 AND c.nbr_place > 0 AND c.nbr_place_reserve >= c.nbr_place
                 AND TIMESTAMP(pv.date_enregistre, pv.id_horaire) >= :maintenant";
      $params = [':jour' => $jour, ':id_compagnie' => $id_compagnie, ':maintenant' => $maintenant];

      if ($idAgence !== null) {
        $where .= ' AND pv.id_agence = :agence';
        $params[':agence'] = $idAgence;
      } elseif ($idDepart !== null) {
        $where .= ' AND pv.localite_user = :depart';
        $params[':depart'] = $idDepart;
      }

      return $this->fetchAll(
        "SELECT pv.id_trajet AS destination, pv.id_horaire AS heure, pv.localite_user AS depart,
                c.numero_car, c.matriculle, c.nbr_place, c.nbr_place_reserve
         FROM programmation_voyage pv
         INNER JOIN car c ON c.id_car = pv.id_car_programmer
         WHERE $where
           AND EXISTS (
             SELECT 1 FROM billets b
             WHERE b.jourVoyage = pv.date_enregistre
               AND b.Heur_departs = pv.id_horaire
               AND b.destinationId = pv.id_trajet
               AND b.departId = pv.localite_user
               AND b.id_compagnie = pv.id_compagnie
               AND (b.status_billets IS NULL OR b.status_billets = '')
               AND (b.statut_embarquement IS NULL OR b.statut_embarquement != 'embarque')
           )
         ORDER BY pv.id_horaire",
        $params
      );
    }

    // Cars programmes aujourd'hui pour cette gare, quel que soit leur taux de remplissage :
    // affiche sur l'ecran Embarquement avec un bouton "Faire decoller" par car/trajet (une
    // fois tous les passagers traites — cf. Programmation_voyage::decollerCar()), ou un badge
    // "Decolle" si deja fait. nb_restants sert a desactiver le bouton cote vue tant qu'il
    // reste du monde a traiter (embarquer, reporter ou annuler).
    public function getCarsDuJourPourEmbarquement($idDepart, $numeroGare, $id_compagnie, $jour = null)
    {
      $jour = $jour ?? date('Y-m-d');

      $idAgence = null;
      if ($idDepart !== null && $numeroGare !== null) {
        $agence = $this->fetchOne(
          "SELECT idAgence FROM agence WHERE localite = :l AND numeroGare = :ng AND id_compagnie = :c LIMIT 1",
          [':l' => $idDepart, ':ng' => $numeroGare, ':c' => $id_compagnie]
        );
        $idAgence = $agence['idAgence'] ?? null;
      }

      $where = "pv.date_enregistre = :jour AND pv.id_compagnie = :id_compagnie AND pv.statut = 'active'";
      $params = [':jour' => $jour, ':id_compagnie' => $id_compagnie];

      if ($idAgence !== null) {
        $where .= ' AND pv.id_agence = :agence';
        $params[':agence'] = $idAgence;
      } elseif ($idDepart !== null) {
        $where .= ' AND pv.localite_user = :depart';
        $params[':depart'] = $idDepart;
      }

      return $this->fetchAll(
        "SELECT pv.id_programmation, pv.id_trajet AS destination, pv.id_horaire AS heure,
                pv.localite_user AS depart, pv.decolle_le, pv.decolle_par,
                ud.utilisateurs AS decolle_par_nom,
                c.numero_car, c.matriculle, c.nbr_place, c.nbr_place_reserve,
                (SELECT COUNT(*) FROM billets b
                  WHERE b.jourVoyage = pv.date_enregistre AND b.Heur_departs = pv.id_horaire
                    AND b.destinationId = pv.id_trajet AND b.departId = pv.localite_user
                    AND b.id_compagnie = pv.id_compagnie
                    AND (b.statut_embarquement IS NULL OR b.statut_embarquement != 'embarque')
                    -- status_billets = '' (pas seulement != 'annule') : meme condition que
                    -- getBilletsPourEmbarquement() ci-dessus -- un billet avec un report/une
                    -- annulation deja demande(e) a disparu de la liste d'embarquement (en
                    -- attente de validation Admin ailleurs) et ne doit donc plus compter comme
                    -- restant ici non plus, sinon ce compteur (et le blocage du bouton Faire
                    -- decoller dans Programmation_voyage::decollerCar(), meme correction) reste
                    -- bloque sur un passager que l'agent ne peut plus traiter depuis cet ecran.
                    AND (b.status_billets IS NULL OR b.status_billets = '')
                ) AS nb_restants
         FROM programmation_voyage pv
         INNER JOIN car c ON c.id_car = pv.id_car_programmer
         LEFT JOIN utilisateur ud ON ud.idUser = pv.decolle_par
         WHERE $where
         ORDER BY pv.id_horaire",
        $params
      );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // REPORT POUR NON-EMBARQUEMENT (soumis a validation Admin, meme principe que
    // demanderAnnulationBillet() / confirmerAnnulationBillet() ci-dessus)
    // ─────────────────────────────────────────────────────────────────────────

    // Etape 1 : soumet une demande de report (aucune place touchee avant validation). Un
    // client deja embarque n'a pas de raison d'etre reporte.
    //
    // Flux a deux etapes, intelligent selon qui demande : un simple Utilisateur passe
    // d'abord par le chef d'escale de sa gare (statut 'report_demande') ; un chef d'escale
    // (ou l'Admin) a deja l'autorite de transmission, sa propre demande part directement
    // vers l'Admin (statut 'report_transmis'), sans étape intermediaire inutile.
    public function demanderReportBillet($idBillets, $nouvelleDate, $nouvelleHeure)
    {
      if (!csrf_verify()) {
        $this->set_flash("Session expirée, veuillez réessayer.", "danger");
        return false;
      }

      $billet = $this->getBilletById($idBillets);
      if (!$billet) {
        $this->set_flash("Billet introuvable.", "danger");
        return false;
      }
      if (($billet->statut_embarquement ?? null) === 'embarque') {
        $this->set_flash("Ce client est déjà embarqué : aucune raison de le reporter.", "warning");
        return false;
      }
      if (!in_array($billet->status_billets ?? null, [null, ''], true)) {
        $this->set_flash("Ce billet est déjà annulé ou une demande est déjà en cours.", "warning");
        return false;
      }
      if (empty($nouvelleDate) || empty($nouvelleHeure)) {
        $this->set_flash("Veuillez choisir une nouvelle date et une heure de départ valides.", "danger");
        return false;
      }

      date_default_timezone_set('Africa/Bamako');
      $nouveauJour = date('Y-m-d', strtotime($nouvelleDate));
      $aujourdhui  = date('Y-m-d');
      $demain      = date('Y-m-d', strtotime('+1 day'));
      if (!in_array($nouveauJour, [$aujourdhui, $demain], true)) {
        $this->set_flash("Le report n'est possible que vers aujourd'hui ou demain.", "danger");
        return false;
      }

      $droit = $_SESSION['droit'] ?? null;
      $idUtilisateur = $_SESSION['id_utilisateur'] ?? null;
      $sauteEtapeChef = in_array($droit, ['chef_d_escale', 'Admin', 'super_admin', 'PDG'], true);
      $statut = $sauteEtapeChef ? 'report_transmis' : 'report_demande';

      $sql = "UPDATE billets SET status_billets = :statut, nouvelle_date_demandee = :nd,
                  nouvelle_heure_demandee = :nh, demande_report_par = :par, demande_report_le = NOW()";
      $params = [
        ':statut' => $statut,
        ':nd' => $nouveauJour,
        ':nh' => $nouvelleHeure,
        ':par' => $idUtilisateur,
        ':id' => $idBillets,
        ':id_compagnie' => $billet->id_compagnie,
      ];
      if ($sauteEtapeChef) {
        $sql .= ", report_transmis_par = :tp, report_transmis_le = NOW()";
        $params[':tp'] = $idUtilisateur;
      }
      $sql .= " WHERE idBillets = :id AND id_compagnie = :id_compagnie";

      $ok = $this->insertion_update_simples($sql, $params);

      if ($ok) {
        $message = $sauteEtapeChef
          ? "Demande de report envoyée : un Admin doit la valider."
          : "Demande de report envoyée à votre chef d'escale.";
        $this->set_flash($message, "success");
        return true;
      }
      $this->set_flash("Erreur lors de l'envoi de la demande de report.", "danger");
      return false;
    }

    // Etape 1 (chef d'escale) : demandes de sa propre gare en attente de son examen.
    public function getDemandesReportEnAttenteChef($id_compagnie, $ville, $numeroGare)
    {
      return $this->fetchAll(
        "SELECT b.idBillets, b.numeroBillets, b.jourVoyage, b.Heur_departs, b.departId, b.destinationId,
                b.nouvelle_date_demandee, b.nouvelle_heure_demandee, b.demande_report_le,
                c.Client, u.utilisateurs AS demandeur
         FROM billets b
         INNER JOIN client c ON b.id_client = c.idClient
         LEFT JOIN utilisateur u ON u.idUser = b.demande_report_par
         WHERE b.id_compagnie = :id_compagnie AND b.status_billets = 'report_demande'
           AND b.departId = :ville AND b.num_gare = :numeroGare
         ORDER BY b.demande_report_le ASC",
        [':id_compagnie' => $id_compagnie, ':ville' => $ville, ':numeroGare' => $numeroGare]
      );
    }

    // Etape 2 (Admin) : demandes deja transmises par un chef d'escale (ou soumises
    // directement par un chef d'escale/Admin), en attente de validation finale.
    public function getDemandesReportEnAttente($id_compagnie)
    {
      return $this->fetchAll(
        "SELECT b.idBillets, b.numeroBillets, b.jourVoyage, b.Heur_departs, b.departId, b.destinationId,
                b.nouvelle_date_demandee, b.nouvelle_heure_demandee, b.demande_report_le,
                c.Client, u.utilisateurs AS demandeur, ut.utilisateurs AS transmis_par_nom
         FROM billets b
         INNER JOIN client c ON b.id_client = c.idClient
         LEFT JOIN utilisateur u ON u.idUser = b.demande_report_par
         LEFT JOIN utilisateur ut ON ut.idUser = b.report_transmis_par
         WHERE b.id_compagnie = :id_compagnie AND b.status_billets = 'report_transmis'
         ORDER BY b.report_transmis_le ASC",
        [':id_compagnie' => $id_compagnie]
      );
    }

    // Etape 1 -> 2 (chef d'escale uniquement) : transmet une demande de sa gare à l'Admin.
    public function transmettreReportBillet($idBillets)
    {
      if (!csrf_verify()) {
        $this->set_flash("Session expirée, veuillez réessayer.", "danger");
        return false;
      }

      $billet = $this->getBilletById($idBillets);
      if (!$billet || ($billet->status_billets ?? null) !== 'report_demande') {
        $this->set_flash("Aucune demande de report en attente pour ce billet.", "warning");
        return false;
      }

      // IDOR : un chef d'escale ne peut transmettre qu'une demande de sa propre gare.
      if (($_SESSION['droit'] ?? null) === 'chef_d_escale') {
        if ($billet->departId !== ($_SESSION['ville'] ?? null) || $billet->num_gare !== ($_SESSION['numero_gare'] ?? null)) {
          $this->set_flash("Cette demande ne concerne pas votre gare.", "danger");
          return false;
        }
      }

      // Compare-and-swap sur le statut deja lu ci-dessus : si quelqu'un d'autre (l'Admin en
      // rejet direct, un double-clic...) a deja fait bouger ce billet entre-temps, rowCount()
      // vaut 0 et on l'affiche comme "deja traite" plutot que de l'ecraser silencieusement.
      $stmt = $this->insertion_update_simples(
        "UPDATE billets SET status_billets = 'report_transmis', report_transmis_par = :par, report_transmis_le = NOW()
         WHERE idBillets = :id AND id_compagnie = :id_compagnie AND status_billets <=> :ancien",
        [
          ':par' => $_SESSION['id_utilisateur'] ?? null,
          ':id' => $idBillets,
          ':id_compagnie' => $billet->id_compagnie,
          ':ancien' => $billet->status_billets,
        ]
      );

      if ($stmt->rowCount() > 0) {
        $this->set_flash("Demande transmise à l'Admin pour validation.", "success");
        return true;
      }
      $this->set_flash("Cette demande a déjà été traitée entre-temps par quelqu'un d'autre.", "warning");
      return false;
    }

    // Rejet, possible aux deux etapes : par le chef d'escale (sa gare, etape 1 uniquement)
    // ou par l'Admin (n'importe quelle gare, aux deux etapes). Le billet redevient actif
    // sur son depart initial.
    public function rejeterReportBillet($idBillets)
    {
      if (!csrf_verify()) {
        $this->set_flash("Session expirée, veuillez réessayer.", "danger");
        return false;
      }

      $billet = $this->getBilletById($idBillets);
      if (!$billet || !in_array($billet->status_billets ?? null, ['report_demande', 'report_transmis'], true)) {
        $this->set_flash("Aucune demande de report en attente pour ce billet.", "warning");
        return false;
      }

      if (($_SESSION['droit'] ?? null) === 'chef_d_escale') {
        $estSaGare = $billet->departId === ($_SESSION['ville'] ?? null) && $billet->num_gare === ($_SESSION['numero_gare'] ?? null);
        if ($billet->status_billets !== 'report_demande' || !$estSaGare) {
          $this->set_flash("Vous ne pouvez pas rejeter cette demande.", "danger");
          return false;
        }
      }

      $stmt = $this->insertion_update_simples(
        "UPDATE billets SET status_billets = NULL, nouvelle_date_demandee = NULL,
            nouvelle_heure_demandee = NULL, demande_report_par = NULL, demande_report_le = NULL,
            report_transmis_par = NULL, report_transmis_le = NULL
         WHERE idBillets = :id AND id_compagnie = :id_compagnie AND status_billets <=> :ancien",
        [':id' => $idBillets, ':id_compagnie' => $billet->id_compagnie, ':ancien' => $billet->status_billets]
      );

      if ($stmt->rowCount() > 0) {
        $this->set_flash("Demande de report rejetée : le billet reste sur son départ initial.", "info");
        return true;
      }
      $this->set_flash("Cette demande a déjà été traitée entre-temps par quelqu'un d'autre.", "warning");
      return false;
    }

    // Etape finale (Admin) : valide la demande deja transmise, applique reellement le
    // report (reutilise reporte_voyage(), qui gere places/car/suivis), puis nettoie les
    // traces de la demande.
    public function confirmerReportBillet($idBillets)
    {
      if (!csrf_verify()) {
        $this->set_flash("Session expirée, veuillez réessayer.", "danger");
        return false;
      }

      $billet = $this->getBilletById($idBillets);
      if (!$billet || ($billet->status_billets ?? null) !== 'report_transmis') {
        $this->set_flash("Aucune demande de report transmise en attente pour ce billet.", "warning");
        return false;
      }

      // Reserve la demande de facon atomique avant de toucher aux places : sans ca, deux
      // confirmations (ou une confirmation + un rejet) lancees en meme temps passeraient
      // toutes les deux le test ci-dessus et reporte_voyage() serait appele deux fois pour
      // le meme billet (double liberation/reservation de place). rowCount() = 0 signifie
      // qu'une autre requete a deja pris la main entre-temps.
      $stmtClaim = $this->insertion_update_simples(
        "UPDATE billets SET status_billets = 'report_en_validation'
         WHERE idBillets = :id AND id_compagnie = :id_compagnie AND status_billets <=> 'report_transmis'",
        [':id' => $idBillets, ':id_compagnie' => $billet->id_compagnie]
      );
      if ($stmtClaim->rowCount() === 0) {
        $this->set_flash("Cette demande a déjà été traitée entre-temps par quelqu'un d'autre.", "warning");
        return false;
      }

      $ok = $this->reporte_voyage([
        'idBillets' => $idBillets,
        'jourVoyage' => $billet->nouvelle_date_demandee,
        'Heur_departs' => $billet->nouvelle_heure_demandee,
      ]);

      if (!$ok) {
        // reporte_voyage() a deja pose son propre message d'erreur (places insuffisantes,
        // car introuvable...) : on remet la demande en attente pour que l'Admin puisse
        // reessayer, plutot que de la laisser bloquee sur le marqueur transitoire.
        $this->insertion_update_simples(
          "UPDATE billets SET status_billets = 'report_transmis' WHERE idBillets = :id AND id_compagnie = :id_compagnie",
          [':id' => $idBillets, ':id_compagnie' => $billet->id_compagnie]
        );
        return false;
      }

      $this->insertion_update_simples(
        "UPDATE billets SET status_billets = NULL, nouvelle_date_demandee = NULL,
            nouvelle_heure_demandee = NULL, demande_report_par = NULL, demande_report_le = NULL,
            report_transmis_par = NULL, report_transmis_le = NULL,
            statut_embarquement = NULL, embarque_le = NULL, embarque_par = NULL
         WHERE idBillets = :id AND id_compagnie = :id_compagnie",
        [':id' => $idBillets, ':id_compagnie' => $billet->id_compagnie]
      );

      $this->set_flash("Report validé : le billet est désormais programmé à la nouvelle date.", "success");
      return true;
    }
  }
