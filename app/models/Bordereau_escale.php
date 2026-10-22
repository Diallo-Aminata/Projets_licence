<?php

// Saisie dans le systeme d'un bordereau d'escale papier (escale isolee sans electricite ni
// reseau, cf. ajout_bordereau_escale.sql). Pas de faux billets individuels : le papier ne
// capture que des comptages agreges (nombre de billets par destination), jamais de
// passager nomme ni de numero de place. Alimente directement caisse_utilisateur (deja
// fermee) + depense, exactement comme une vraie caisse, et conserve l'ecriture exacte du
// papier dans bordereau_escale(_lignes) pour la tracabilite.
class Bordereau_escale extends Model
{
    // Retourne l'id du bordereau cree en cas de succes, false sinon.
    public function saveBordereau()
    {
        if (!csrf_verify()) {
            $this->set_flash("Session expirée, veuillez réessayer.", "danger");
            return false;
        }

        $idCompagnie = $_SESSION['id_compagnie'] ?? null;
        $idUser      = (int)($_SESSION['id_utilisateur'] ?? 0);
        if (!$idCompagnie || !$idUser) {
            $this->set_flash("Session invalide.", "danger");
            return false;
        }

        // Gare de rattachement : chef_d_escale/Utilisateur limites a leur propre gare, Admin
        // choisit dans le formulaire (meme logique que Caisse::resolveGareEscale()).
        if (($_SESSION['droit'] ?? null) === 'Admin') {
            $idAgence = (int)($_POST['id_agence'] ?? 0);
        } else {
            $idAgence = (int)($_SESSION['id_agence'] ?? 0);
        }
        $agence = $this->fetchOne(
            "SELECT idAgence FROM agence WHERE idAgence = :id AND id_compagnie = :ic LIMIT 1",
            [':id' => $idAgence, ':ic' => $idCompagnie]
        );
        if (!$agence) {
            $this->set_flash("Veuillez choisir la gare de rattachement.", "danger");
            return false;
        }

        $dateEscale = trim($_POST['date_escale'] ?? '');
        $dateEscale = $dateEscale !== '' ? date('Y-m-d', strtotime($dateEscale)) : '';
        $aujourdhui = date('Y-m-d');
        if (!$dateEscale || $dateEscale === '1970-01-01' || $dateEscale > $aujourdhui) {
            $this->set_flash("Date invalide : elle ne peut pas être vide ni dans le futur.", "danger");
            return false;
        }

        $numeroBus       = trim($_POST['numero_bus'] ?? '') ?: null;
        $escaleLibelle   = trim($_POST['escale_libelle'] ?? '') ?: null;
        $nomAgentEscale  = trim($_POST['nom_agent_escale'] ?? '') ?: null;
        $numeroBordereau = trim($_POST['numero_bordereau'] ?? '') ?: null;

        // Lignes "billets" (destination / nombre / prix unitaire) : tableaux paralleles,
        // meme convention que bagage_description[]/bagage_poids[]/bagage_prix[] ailleurs.
        $destinations  = $_POST['destination'] ?? [];
        $nombres       = $_POST['nombre_billets'] ?? [];
        $prixUnitaires = $_POST['prix_unitaire'] ?? [];

        $lignesBillets = [];
        $totalBillets  = 0.0;
        $nbBillets     = 0;
        foreach ($destinations as $i => $dest) {
            $dest   = trim($dest);
            $nombre = (int)($nombres[$i] ?? 0);
            $prix   = (float)($prixUnitaires[$i] ?? 0);
            if ($dest === '' || $nombre <= 0 || $prix <= 0) {
                continue;
            }
            $montant = $nombre * $prix;
            $lignesBillets[] = ['destination' => $dest, 'nombre' => $nombre, 'prix_unitaire' => $prix, 'montant' => $montant];
            $totalBillets += $montant;
            $nbBillets    += $nombre;
        }

        // Lignes "bagages" (description / prix)
        $descriptions = $_POST['bagage_description'] ?? [];
        $prixBagages  = $_POST['bagage_prix'] ?? [];

        $lignesBagages = [];
        $totalBagages  = 0.0;
        foreach ($prixBagages as $i => $prix) {
            if ($prix === '' || $prix === null) {
                continue;
            }
            $prix = (float)$prix;
            if ($prix <= 0) {
                continue;
            }
            $lignesBagages[] = ['description' => trim($descriptions[$i] ?? '') ?: null, 'montant' => $prix];
            $totalBagages += $prix;
        }

        if (empty($lignesBillets) && empty($lignesBagages)) {
            $this->set_flash("Veuillez saisir au moins une ligne de billets ou de bagages.", "danger");
            return false;
        }

        $montantDepenses   = (float)($_POST['montant_depenses'] ?? 0);
        $montantCommission = (float)($_POST['montant_commission'] ?? 0);
        $totalVerseSaisi   = (float)($_POST['total_verse'] ?? 0);
        if ($montantDepenses < 0 || $montantCommission < 0 || $totalVerseSaisi < 0) {
            $this->set_flash("Les montants ne peuvent pas être négatifs.", "danger");
            return false;
        }

        $totalGeneral = $totalBillets + $totalBagages;

        $pdo = $this->connect();
        try {
            $pdo->beginTransaction();

            // Caisse deja fermee : pas de passage par ouvrirCaisse() (cablee sur CURDATE()),
            // la saisie etant par nature retroactive. Formule d'ecart identique a
            // Caisse_utilisateur::fermerCaisse() (montant_initial + total_billets + total_colis),
            // avec montant_initial=0 et total_colis=0 ici.
            $reference = 'CU' . date('Ymd', strtotime($dateEscale)) . '-' . $idUser . '-' . random_int(100, 999);
            $ecart     = $totalVerseSaisi - $totalGeneral;

            $stmt = $pdo->prepare("
                INSERT INTO caisse_utilisateur
                    (id_utilisateur, id_agence, id_compagnie, date_service, heure_ouverture, heure_fermeture,
                     montant_initial, total_billets, total_colis, nb_billets, nb_colis,
                     montant_depense, montant_compte, ecart, statut, reference)
                VALUES
                    (:id_user, :id_agence, :id_compagnie, :date_service, :heure_ouverture, :heure_fermeture,
                     0, :total_billets, 0, :nb_billets, 0,
                     :montant_depense, :montant_compte, :ecart, 'fermee', :reference)
            ");
            $stmt->execute([
                ':id_user'         => $idUser,
                ':id_agence'       => $idAgence,
                ':id_compagnie'    => $idCompagnie,
                ':date_service'    => $dateEscale,
                ':heure_ouverture' => $dateEscale . ' 00:00:00',
                ':heure_fermeture' => $dateEscale . ' 23:59:00',
                ':total_billets'   => $totalGeneral,
                ':nb_billets'      => $nbBillets,
                ':montant_depense' => $montantDepenses + $montantCommission,
                ':montant_compte'  => $totalVerseSaisi,
                ':ecart'           => $ecart,
                ':reference'       => $reference,
            ]);
            $idCaisseUser = (int)$pdo->lastInsertId();

            // Journal : ouverture, une ligne par destination, une ligne par bagage, fermeture
            // (meme format que Caisse_utilisateur, pour que l'onglet Journal de cette caisse
            // soit aussi lisible qu'une caisse reelle).
            $this->insererJournalLocal($pdo, $idCaisseUser, $idUser, 'ouverture', $reference, 0, 'Ouverture de caisse (saisie bordereau papier)');
            foreach ($lignesBillets as $l) {
                $this->insererJournalLocal(
                    $pdo,
                    $idCaisseUser,
                    $idUser,
                    'billet',
                    $numeroBordereau,
                    $l['montant'],
                    "Vente groupée {$l['nombre']} billet(s) vers {$l['destination']} — bordereau papier"
                );
            }
            foreach ($lignesBagages as $l) {
                $this->insererJournalLocal(
                    $pdo,
                    $idCaisseUser,
                    $idUser,
                    'bagage',
                    $numeroBordereau,
                    $l['montant'],
                    "Bagages — bordereau papier" . (!empty($l['description']) ? " ({$l['description']})" : '')
                );
            }
            $this->insererJournalLocal(
                $pdo,
                $idCaisseUser,
                $idUser,
                'fermeture',
                $reference,
                $totalVerseSaisi,
                "Fermeture – attendu: {$totalGeneral} FCFA, compté: {$totalVerseSaisi} FCFA, écart: {$ecart} FCFA"
            );

            // Dépenses (catégorie 'Autre') et commission (catégorie dédiée) : déjà
            // physiquement réglées et signées sur le papier, donc statut 'valide' direct
            // (pas de circuit de validation a posteriori à rejouer, contrairement à une
            // dépense saisie en direct par un chef d'escale).
            if ($montantDepenses > 0) {
                $pdo->prepare("
                    INSERT INTO depense (id_compagnie, id_agence, id_caisse_user, categorie, libelle, montant, date_depense, id_utilisateur, statut)
                    VALUES (:ic, :ia, :icu, 'Autre', :libelle, :montant, :date, :iu, 'valide')
                ")->execute([
                    ':ic'      => $idCompagnie,
                    ':ia'      => $idAgence,
                    ':icu'     => $idCaisseUser,
                    ':libelle' => "Dépenses — bordereau escale du " . date('d/m/Y', strtotime($dateEscale)),
                    ':montant' => (int) round($montantDepenses),
                    ':date'    => $dateEscale,
                    ':iu'      => $idUser,
                ]);
            }
            if ($montantCommission > 0) {
                $pdo->prepare("
                    INSERT INTO depense (id_compagnie, id_agence, id_caisse_user, categorie, libelle, montant, date_depense, id_utilisateur, statut)
                    VALUES (:ic, :ia, :icu, 'Commission agent d''escale', :libelle, :montant, :date, :iu, 'valide')
                ")->execute([
                    ':ic'      => $idCompagnie,
                    ':ia'      => $idAgence,
                    ':icu'     => $idCaisseUser,
                    ':libelle' => "Commission agent d'escale" . (!empty($nomAgentEscale) ? " ($nomAgentEscale)" : '') .
                        " — bordereau du " . date('d/m/Y', strtotime($dateEscale)),
                    ':montant' => (int) round($montantCommission),
                    ':date'    => $dateEscale,
                    ':iu'      => $idUser,
                ]);
            }

            // Bordereau (audit) + ses lignes, tel quel depuis le papier.
            $stmtB = $pdo->prepare("
                INSERT INTO bordereau_escale
                    (numero_bordereau, date_escale, numero_bus, escale_libelle, nom_agent_escale,
                     id_agence, id_compagnie, id_caisse_user, total_billets, total_bagages,
                     montant_depenses, montant_commission, total_verse_saisi, saisi_par)
                VALUES
                    (:num, :date, :bus, :escale, :agent,
                     :ia, :ic, :icu, :tb, :tbag,
                     :dep, :com, :tv, :su)
            ");
            $stmtB->execute([
                ':num'    => $numeroBordereau,
                ':date'   => $dateEscale,
                ':bus'    => $numeroBus,
                ':escale' => $escaleLibelle,
                ':agent'  => $nomAgentEscale,
                ':ia'     => $idAgence,
                ':ic'     => $idCompagnie,
                ':icu'    => $idCaisseUser,
                ':tb'     => $totalBillets,
                ':tbag'   => $totalBagages,
                ':dep'    => $montantDepenses,
                ':com'    => $montantCommission,
                ':tv'     => $totalVerseSaisi,
                ':su'     => $idUser,
            ]);
            $idBordereau = (int)$pdo->lastInsertId();

            $stmtL = $pdo->prepare("
                INSERT INTO bordereau_escale_lignes (id_bordereau, type_ligne, destination, nombre, prix_unitaire, description, montant)
                VALUES (:ib, :type, :dest, :nb, :pu, :desc, :montant)
            ");
            foreach ($lignesBillets as $l) {
                $stmtL->execute([
                    ':ib' => $idBordereau, ':type' => 'billet', ':dest' => $l['destination'],
                    ':nb' => $l['nombre'], ':pu' => $l['prix_unitaire'], ':desc' => null, ':montant' => $l['montant'],
                ]);
            }
            foreach ($lignesBagages as $l) {
                $stmtL->execute([
                    ':ib' => $idBordereau, ':type' => 'bagage', ':dest' => null,
                    ':nb' => null, ':pu' => null, ':desc' => $l['description'], ':montant' => $l['montant'],
                ]);
            }

            $pdo->commit();

            // Avertissement non bloquant si le total saisi ne colle pas a l'arithmetique du
            // papier (billets+bagages-depenses-commission = total verse) : aide a reperer une
            // erreur de transcription, distinct de l'ecart systeme (deja enregistre ci-dessus
            // dans caisse_utilisateur.ecart, qui peut legitimement etre non nul si la caisse
            // physique avait un vrai ecart).
            $calcule = $totalGeneral - $montantDepenses - $montantCommission;
            if (abs($calcule - $totalVerseSaisi) > 1) {
                $this->set_flash(
                    "Bordereau enregistré. Attention : billets + bagages − dépenses − commission = " .
                    number_format($calcule, 0, ',', ' ') . " FCFA, mais le total versé saisi est de " .
                    number_format($totalVerseSaisi, 0, ',', ' ') . " FCFA. Vérifiez la saisie.",
                    "warning"
                );
            } else {
                $this->set_flash("Bordereau enregistré et caisse créée avec succès.", "success");
            }

            return $idBordereau;
        } catch (Throwable $e) {
            $pdo->rollBack();
            $this->set_flash("Erreur SQL : " . $e->getMessage(), "danger");
            return false;
        }
    }

    private function insererJournalLocal(\PDO $pdo, int $idCaisseUser, int $idUser, string $type, ?string $reference, float $montant, string $libelle): void
    {
        $pdo->prepare("
            INSERT INTO journal_caisse (id_caisse_user, id_utilisateur, type_operation, reference_op, montant, libelle)
            VALUES (:cu, :user, :type, :ref, :montant, :libelle)
        ")->execute([
            ':cu'      => $idCaisseUser,
            ':user'    => $idUser,
            ':type'    => $type,
            ':ref'     => $reference,
            ':montant' => $montant,
            ':libelle' => $libelle,
        ]);
    }

    public function getBordereaux(array $filtres = []): array
    {
        $where  = ['b.id_compagnie = :ic'];
        $params = [':ic' => $_SESSION['id_compagnie'] ?? null];

        if (!empty($filtres['id_agence'])) {
            $where[] = 'b.id_agence = :ia';
            $params[':ia'] = $filtres['id_agence'];
        }
        if (!empty($filtres['date'])) {
            $where[] = 'b.date_escale = :date';
            $params[':date'] = $filtres['date'];
        }

        $sql = "SELECT b.*, a.localite, a.numeroGare, cu.statut AS statut_caisse, cu.ecart,
                       u.utilisateurs AS nom_saisi_par
                FROM bordereau_escale b
                LEFT JOIN agence a ON b.id_agence = a.idAgence
                LEFT JOIN caisse_utilisateur cu ON b.id_caisse_user = cu.id_caisse_user
                LEFT JOIN utilisateur u ON b.saisi_par = u.idUser
                WHERE " . implode(' AND ', $where) . "
                ORDER BY b.date_escale DESC, b.id_bordereau DESC
                LIMIT 200";

        return $this->fetchAll($sql, $params);
    }

    public function getBordereauDetail($id)
    {
        $idCompagnie = $_SESSION['id_compagnie'] ?? null;
        $bordereau = $this->fetchOne(
            "SELECT b.*, a.localite, a.numeroGare, cu.statut AS statut_caisse, cu.ecart,
                    cu.id_utilisateur AS id_utilisateur_caisse, u.utilisateurs AS nom_saisi_par
             FROM bordereau_escale b
             LEFT JOIN agence a ON b.id_agence = a.idAgence
             LEFT JOIN caisse_utilisateur cu ON b.id_caisse_user = cu.id_caisse_user
             LEFT JOIN utilisateur u ON b.saisi_par = u.idUser
             WHERE b.id_bordereau = :id AND b.id_compagnie = :ic LIMIT 1",
            [':id' => $id, ':ic' => $idCompagnie]
        );
        if (!$bordereau) {
            return null;
        }

        $bordereau['lignes'] = $this->fetchAll(
            "SELECT * FROM bordereau_escale_lignes WHERE id_bordereau = :id ORDER BY type_ligne, id_ligne",
            [':id' => $id]
        );

        return $bordereau;
    }
}
