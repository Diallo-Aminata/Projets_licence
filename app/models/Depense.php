<?php
class Depense extends Model
{
    const CATEGORIES = [
        'Carburant',
        'Entretien/Reparation',
        'Peage',
        'Fournitures',
        'Communication',
        'Salaire',
        'Loyer',
        'Assurance',
        'Remboursement annulation',
        'Commission agent d\'escale',
        'Autre'
    ];

    // Enregistre une dépense locale (rattachée à la caisse ouverte d'une gare)
    // ou globale (à l'échelle de la compagnie, sans gare/caisse).
    public function saveDepense()
    {
        $id_compagnie = $_SESSION['id_compagnie'];
        $droit        = $_SESSION['droit'] ?? null;

        extract($_POST);
        $portee = $portee ?? 'locale';

        if (!in_array($categorie, self::CATEGORIES, true)) {
            $this->set_flash("Catégorie de dépense invalide.", "danger");
            return false;
        }

        if (!is_numeric($montant) || (float)$montant <= 0) {
            $this->set_flash("Le montant de la dépense doit être un nombre positif.", "danger");
            return false;
        }

        // Seul l'Admin peut enregistrer une dépense globale (charges de la compagnie).
        if ($portee === 'globale') {
            if ($droit !== 'Admin') {
                $this->set_flash("Seul l'Admin peut enregistrer une dépense globale.", "danger");
                return false;
            }

            $insertion = $this->insertion_update_simples(
                "INSERT INTO depense (id_compagnie, categorie, libelle, montant, date_depense, id_utilisateur, statut)
                 VALUES (:id_compagnie, :categorie, :libelle, :montant, :date_depense, :id_utilisateur, 'valide')",
                [
                    ':id_compagnie'   => $id_compagnie,
                    ':categorie'      => $categorie,
                    ':libelle'        => $libelle ?? null,
                    ':montant'        => $montant,
                    ':date_depense'   => $date_depense,
                    ':id_utilisateur' => $_SESSION['id_utilisateur']
                ]
            );

            if ($insertion) {
                $this->set_flash("Dépense globale enregistrée avec succès.", "success");
                return true;
            }
            $this->set_flash("Erreur lors de l'enregistrement de la dépense.", "danger");
            return false;
        }

        // Dépense locale : rattachée à une caisse ouverte.
        // Un chef d'escale ne peut viser que sa propre gare, même si le formulaire est trafiqué.
        if ($droit === 'chef_d_escale') {
            $id_agence = $_SESSION['id_agence'];
        } elseif (empty($id_agence)) {
            $this->set_flash("Veuillez choisir la gare concernée par cette dépense.", "danger");
            return false;
        }

        // Un Admin ne peut débiter que les gares de sa propre compagnie (IDOR sinon :
        // id_agence posté pouvait sinon viser la caisse d'une AUTRE compagnie).
        $agenceValide = $this->FetchSelectWhere(
            "idAgence",
            "agence",
            "idAgence = :id_agence AND id_compagnie = :id_compagnie",
            [":id_agence" => $id_agence, ":id_compagnie" => $id_compagnie]
        );
        if (!$agenceValide) {
            $this->set_flash("Cette gare n'appartient pas à votre compagnie.", "danger");
            return false;
        }

        $id_caisse      = null;
        $id_caisse_user = null;

        if ($droit === 'chef_d_escale') {
            // Le chef d'escale débite toujours SA PROPRE caisse individuelle du jour,
            // jamais celle d'un collègue.
            $caisseUser = $this->fetchOne(
                "SELECT id_caisse_user FROM caisse_utilisateur
                 WHERE id_utilisateur = :id_utilisateur AND date_service = CURDATE() AND statut = 'ouverte'
                 LIMIT 1",
                [':id_utilisateur' => $_SESSION['id_utilisateur']]
            );
            if (!$caisseUser) {
                $this->set_flash("Vous devez avoir votre propre caisse ouverte pour enregistrer une dépense.", "danger");
                return false;
            }
            $id_caisse_user = (int)$caisseUser['id_caisse_user'];
        } else {
            // Admin ciblant une gare précise : caisse de gare (ancien système) si ouverte,
            // sinon caisse individuelle ouverte pour cette gare aujourd'hui.
            $caisse = $this->FetchSelectWhere(
                "id_caisse",
                "caisse",
                "id_agence = :id_agence AND status_caisse = 1",
                [":id_agence" => $id_agence]
            );

            if ($caisse) {
                $id_caisse = $caisse->id_caisse;
            } else {
                $caisseUser = $this->fetchOne(
                    "SELECT id_caisse_user FROM caisse_utilisateur
                     WHERE id_agence = :id_agence AND date_service = CURDATE() AND statut = 'ouverte'
                     LIMIT 1",
                    [':id_agence' => $id_agence]
                );
                if ($caisseUser) {
                    $id_caisse_user = (int)$caisseUser['id_caisse_user'];
                }
            }

            if (!$id_caisse && !$id_caisse_user) {
                $this->set_flash("Aucune caisse ouverte pour cette gare : impossible d'enregistrer la dépense.", "danger");
                return false;
            }
        }

        $statut = ($droit === 'Admin') ? 'valide' : 'en_attente';

        $insertion = $this->insertion_update_simples(
            "INSERT INTO depense (id_compagnie, id_agence, id_caisse, id_caisse_user, categorie, libelle, montant, date_depense, id_utilisateur, statut)
             VALUES (:id_compagnie, :id_agence, :id_caisse, :id_caisse_user, :categorie, :libelle, :montant, :date_depense, :id_utilisateur, :statut)",
            [
                ':id_compagnie'   => $id_compagnie,
                ':id_agence'      => $id_agence,
                ':id_caisse'      => $id_caisse,
                ':id_caisse_user' => $id_caisse_user,
                ':categorie'      => $categorie,
                ':libelle'        => $libelle ?? null,
                ':montant'        => $montant,
                ':date_depense'   => $date_depense,
                ':id_utilisateur' => $_SESSION['id_utilisateur'],
                ':statut'         => $statut
            ]
        );

        if (!$insertion) {
            $this->set_flash("Erreur lors de l'enregistrement de la dépense.", "danger");
            return false;
        }

        if ($statut === 'valide') {
            $this->deduireCaisse($id_caisse, $id_caisse_user, $montant);
            $this->set_flash("Dépense enregistrée avec succès et déduite de la caisse.", "success");
        } else {
            $this->set_flash("Dépense enregistrée avec succès. Elle est en attente de validation par l'administrateur.", "success");
        }
        return true;
    }

    // Déduit le montant d'une dépense validée de la caisse concernée (gare ou individuelle).
    private function deduireCaisse($id_caisse, $id_caisse_user, $montant)
    {
        if ($id_caisse) {
            $this->insertion_update_simples(
                "UPDATE caisse SET montant_depense = montant_depense + :montant WHERE id_caisse = :id_caisse",
                [':montant' => $montant, ':id_caisse' => $id_caisse]
            );
        } elseif ($id_caisse_user) {
            $this->insertion_update_simples(
                "UPDATE caisse_utilisateur SET montant_depense = montant_depense + :montant WHERE id_caisse_user = :id_caisse_user",
                [':montant' => $montant, ':id_caisse_user' => $id_caisse_user]
            );
        }
    }

    // Liste des dépenses visibles selon le rôle : le chef d'escale ne voit que
    // les dépenses locales de sa propre gare, l'Admin voit tout (locales + globales).
    public function getDepenses()
    {
        $id_compagnie = $_SESSION['id_compagnie'];
        $droit        = $_SESSION['droit'] ?? null;

        $condition = 'd.id_compagnie = :id_compagnie';
        $params    = ['id_compagnie' => $id_compagnie];

        if ($droit === 'chef_d_escale') {
            $condition .= ' AND d.id_agence = :id_agence';
            $params['id_agence'] = $_SESSION['id_agence'];
        }

        return $this->FetchSelectWheres(
            'd.*, a.localite, a.numeroGare, u.utilisateurs AS agent',
            'depense d
                LEFT JOIN agence a ON d.id_agence = a.idAgence
                LEFT JOIN utilisateur u ON d.id_utilisateur = u.idUser',
            $condition . ' ORDER BY d.date_depense DESC, d.id_depense DESC',
            $params
        );
    }

    // Bénéfice de la compagnie sur une période donnée : revenus (billets + colis)
    // moins remboursements moins dépenses (locales + globales). Se base sur les caisses
    // fermées ET ouvertes (le statut d'une caisse ne concerne que son activité courante,
    // pas l'historique qui doit rester compté dans le bénéfice).
    // $jourDate : date precise pour periode='jour' (defaut aujourd'hui). Permet au tableau
    // de bord de consulter le benefice d'un jour passe, comme Rapport Compagnie/Supervision
    // Escale le font deja pour les caisses.
    public function getBenefice($periode = 'jour', $gareVille = null, $jourDate = null)
    {
        $id_compagnie = $_SESSION['id_compagnie'];
        $pdo = $this->connect();

        $filtreDate  = '';
        $paramsDate  = [];
        if ($periode === 'jour') {
            $filtreDate = ' AND date_col = :jourDate';
            $paramsDate[':jourDate'] = $jourDate ?? date('Y-m-d');
        } elseif ($periode === 'mois') {
            $filtreDate = ' AND MONTH(date_col) = MONTH(CURDATE()) AND YEAR(date_col) = YEAR(CURDATE())';
        }

        $stmtBillets = $pdo->prepare(
            "SELECT SUM(CAST(REPLACE(REPLACE(c.montant_payer, ' ', ''), 'FCFA', '') AS DECIMAL(12,2))) AS total
             FROM billets b
             INNER JOIN client c ON b.id_client = c.idClient
             WHERE b.id_compagnie = :id_compagnie AND b.validation_billets = 'valider'
               AND (b.status_billets IS NULL OR b.status_billets != 'annule')"
            . ($gareVille ? ' AND b.departId = :gareVille' : '')
            . str_replace('date_col', 'b.date_reservation', $filtreDate)
        );
        $paramsBillets = [':id_compagnie' => $id_compagnie] + $paramsDate;
        if ($gareVille) $paramsBillets[':gareVille'] = $gareVille;
        $stmtBillets->execute($paramsBillets);
        $totalBillets = (float)($stmtBillets->fetchColumn() ?: 0);

        $stmtColis = $pdo->prepare(
            "SELECT SUM(colis.fraix_transaction) AS total
             FROM colis
             INNER JOIN agence ON colis.id_agence = agence.idAgence
             WHERE colis.id_compagnie = :id_compagnie"
            . ($gareVille ? ' AND agence.localite = :gareVille' : '')
            . str_replace('date_col', 'colis.date_enregistrement', $filtreDate)
        );
        $paramsColis = [':id_compagnie' => $id_compagnie] + $paramsDate;
        if ($gareVille) $paramsColis[':gareVille'] = $gareVille;
        $stmtColis->execute($paramsColis);
        $totalColis = (float)($stmtColis->fetchColumn() ?: 0);

        $stmtRembourse = $pdo->prepare(
            "SELECT SUM(c.montant_rembourse) AS total
             FROM caisse c
             INNER JOIN agence a ON c.id_agence = a.idAgence
             WHERE a.id_compagnie = :id_compagnie"
            . ($gareVille ? ' AND a.localite = :gareVille' : '')
        );
        $paramsRembourse = [':id_compagnie' => $id_compagnie];
        if ($gareVille) $paramsRembourse[':gareVille'] = $gareVille;
        $stmtRembourse->execute($paramsRembourse);
        $totalRembourse = (float)($stmtRembourse->fetchColumn() ?: 0);

        $stmtDepenseLocale = $pdo->prepare(
            "SELECT SUM(depense.montant) AS total
             FROM depense
             INNER JOIN agence a ON depense.id_agence = a.idAgence
             WHERE depense.id_compagnie = :id_compagnie AND depense.statut = 'valide'"
            . ($gareVille ? ' AND a.localite = :gareVille' : '')
            . str_replace('date_col', 'depense.date_depense', $filtreDate)
        );
        $paramsDepenseLocale = [':id_compagnie' => $id_compagnie] + $paramsDate;
        if ($gareVille) $paramsDepenseLocale[':gareVille'] = $gareVille;
        $stmtDepenseLocale->execute($paramsDepenseLocale);
        $totalDepenseLocale = (float)($stmtDepenseLocale->fetchColumn() ?: 0);

        // Les dépenses "globales" (id_agence IS NULL) ne sont pas rattachées à une gare précise :
        // elles ne doivent pas être imputées à une seule gare quand on filtre par gare.
        $totalDepenseGlobale = 0.0;
        if (!$gareVille) {
            $stmtDepenseGlobale = $pdo->prepare(
                "SELECT SUM(montant) AS total FROM depense
                 WHERE id_compagnie = :id_compagnie AND id_agence IS NULL AND statut = 'valide'"
                . str_replace('date_col', 'date_depense', $filtreDate)
            );
            $stmtDepenseGlobale->execute([':id_compagnie' => $id_compagnie] + $paramsDate);
            $totalDepenseGlobale = (float)($stmtDepenseGlobale->fetchColumn() ?: 0);
        }

        $stmtLocation = $pdo->prepare(
            "SELECT SUM(l.frais_location) AS total
             FROM location_car l
             INNER JOIN agence a ON l.id_agence_depart = a.idAgence
             WHERE l.id_compagnie = :id_compagnie AND l.statut = 'valide'"
            . ($gareVille ? ' AND a.localite = :gareVille' : '')
            . str_replace('date_col', 'l.date_depart', $filtreDate)
        );
        $paramsLocation = [':id_compagnie' => $id_compagnie] + $paramsDate;
        if ($gareVille) $paramsLocation[':gareVille'] = $gareVille;
        $stmtLocation->execute($paramsLocation);
        $totalLocation = (float)($stmtLocation->fetchColumn() ?: 0);

        $benefice = $totalBillets + $totalColis + $totalLocation - $totalRembourse - $totalDepenseLocale - $totalDepenseGlobale;

        return [
            'revenus_billets'   => $totalBillets,
            'revenus_colis'     => $totalColis,
            'revenus_location'  => $totalLocation,
            'remboursements'    => $totalRembourse,
            'depenses_locales'  => $totalDepenseLocale,
            'depenses_globales' => $totalDepenseGlobale,
            'benefice'          => $benefice
        ];
    }

    // Valide une dépense en attente et la déduit de la caisse associée
    public function validerDepense($id)
    {
        $id = (int)$id;
        $id_compagnie = $_SESSION['id_compagnie'];

        // Récupérer la dépense
        $depense = $this->FetchSelectWhere(
            "*",
            "depense",
            "id_depense = :id AND id_compagnie = :id_compagnie",
            [":id" => $id, ":id_compagnie" => $id_compagnie]
        );

        if (!$depense) {
            $this->set_flash("Dépense introuvable.", "danger");
            return false;
        }

        if ($depense->statut !== 'en_attente') {
            $this->set_flash("Cette dépense a déjà été traitée (validée ou rejetée).", "warning");
            return false;
        }

        // Reservation atomique du statut ("compare-and-swap") : sans le "AND statut =
        // 'en_attente'" ici, deux validations (ou une validation + un rejet) lancees en
        // meme temps pourraient toutes les deux passer le test ci-dessus avant qu'aucune
        // n'ecrive, et deduireCaisse() serait alors appelee deux fois pour la meme depense.
        $update = $this->insertion_update_simples(
            "UPDATE depense SET statut = 'valide' WHERE id_depense = :id AND statut = 'en_attente'",
            [":id" => $id]
        );

        if ($update->rowCount() === 0) {
            $this->set_flash("Cette dépense a déjà été traitée entre-temps par quelqu'un d'autre.", "warning");
            return false;
        }

        // Déduire de la caisse si c'est une dépense locale rattachée à une caisse
        // (de gare ou individuelle).
        if (!empty($depense->id_caisse) || !empty($depense->id_caisse_user)) {
            $this->deduireCaisse($depense->id_caisse, $depense->id_caisse_user, $depense->montant);
        }

        $this->set_flash("Dépense validée avec succès et déduite de la caisse.", "success");
        return true;
    }

    // Rejette une dépense en attente
    public function rejeterDepense($id)
    {
        $id = (int)$id;
        $id_compagnie = $_SESSION['id_compagnie'];

        // Récupérer la dépense
        $depense = $this->FetchSelectWhere(
            "*",
            "depense",
            "id_depense = :id AND id_compagnie = :id_compagnie",
            [":id" => $id, ":id_compagnie" => $id_compagnie]
        );

        if (!$depense) {
            $this->set_flash("Dépense introuvable.", "danger");
            return false;
        }

        if ($depense->statut !== 'en_attente') {
            $this->set_flash("Cette dépense a déjà été traitée.", "warning");
            return false;
        }

        // Meme garde que validerDepense() : empeche un rejet d'ecraser une depense deja
        // validee (et donc deja deduite de la caisse) par une validation concurrente.
        $update = $this->insertion_update_simples(
            "UPDATE depense SET statut = 'rejete' WHERE id_depense = :id AND statut = 'en_attente'",
            [":id" => $id]
        );

        if ($update->rowCount() > 0) {
            $this->set_flash("Dépense rejetée avec succès.", "success");
            return true;
        }

        $this->set_flash("Cette dépense a déjà été traitée entre-temps par quelqu'un d'autre.", "warning");
        return false;
    }
}
