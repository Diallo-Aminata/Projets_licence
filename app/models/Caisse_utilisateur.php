<?php
/**
 * Modèle dédié à la caisse individuelle des opérateurs (billettères et agents colis).
 * Gère le cycle complet : ouverture → alimentation → fermeture → versement → journal.
 */
class Caisse_utilisateur extends Model
{
    // ─────────────────────────────────────────────────────────────────────────
    // OUVERTURE
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Retourne la caisse active (statut='ouverte') de l'utilisateur connecté pour aujourd'hui.
     * Null si aucune caisse ouverte.
     */
    public function getCaisseOuverte(int $idUser): ?object
    {
        $pdo = $this->connect();
        $stmt = $pdo->prepare("
            SELECT cu.*, a.localite, a.numeroGare
            FROM caisse_utilisateur cu
            INNER JOIN agence a ON cu.id_agence = a.idAgence
            WHERE cu.id_utilisateur = :id_user
              AND cu.date_service = CURDATE()
              AND cu.statut = 'ouverte'
            LIMIT 1
        ");
        $stmt->execute([':id_user' => $idUser]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: null;
    }

    /**
     * Caisse fermée aujourd'hui mais pas encore versée (statut='fermee'). Sans cette étape
     * intermédiaire, l'écran "Ma Caisse" ne montrait plus AUCUNE trace de la caisse dès sa
     * fermeture (seule getCaisseOuverte() était utilisée) : le bouton "Verser" disparaissait
     * avec elle, obligeant à ouvrir une nouvelle caisse juste pour pouvoir verser l'ancienne.
     */
    public function getCaisseFermeeNonVersee(int $idUser): ?object
    {
        $pdo = $this->connect();
        $stmt = $pdo->prepare("
            SELECT cu.*, a.localite, a.numeroGare, v.statut AS statut_versement
            FROM caisse_utilisateur cu
            INNER JOIN agence a ON cu.id_agence = a.idAgence
            LEFT JOIN versements_caisse v ON v.id_caisse_user = cu.id_caisse_user AND v.statut != 'rejete'
            WHERE cu.id_utilisateur = :id_user
              AND cu.date_service = CURDATE()
              AND cu.statut = 'fermee'
            LIMIT 1
        ");
        $stmt->execute([':id_user' => $idUser]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: null;
    }

    /**
     * Caisses "oubliées" d'un utilisateur : encore ouvertes ou fermées-non-versées, mais
     * datant d'un jour AVANT aujourd'hui (celle du jour même est déjà couverte par
     * getCaisseOuverte()/getCaisseFermeeNonVersee() ci-dessus). Sans ça, une caisse jamais
     * fermée la veille restait invisible de "Ma Caisse" dès le lendemain (les deux méthodes
     * ci-dessus sont scopées à CURDATE()) : l'opérateur ne pouvait plus ni la fermer ni la
     * verser lui-même, et personne d'autre n'a de moyen de le faire à sa place.
     */
    public function getCaissesAnciennesEnAttente(int $idUser): array
    {
        $pdo = $this->connect();
        $stmt = $pdo->prepare("
            SELECT cu.*, a.localite, a.numeroGare, v.statut AS statut_versement
            FROM caisse_utilisateur cu
            INNER JOIN agence a ON cu.id_agence = a.idAgence
            LEFT JOIN versements_caisse v ON v.id_caisse_user = cu.id_caisse_user AND v.statut != 'rejete'
            WHERE cu.id_utilisateur = :id_user
              AND cu.date_service < CURDATE()
              AND cu.statut IN ('ouverte', 'fermee')
            ORDER BY cu.date_service ASC
        ");
        $stmt->execute([':id_user' => $idUser]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Meme principe que getCaissesAnciennesEnAttente() mais pour TOUTE la compagnie, toutes
     * gares confondues, tous operateurs confondus (billettieres, agents colis, ET chefs
     * d'escale eux-memes s'ils vendent aussi) : vue d'anomalie pour l'Admin/PDG, qui ne
     * devrait pas avoir a naviguer gare par gare et date par date (cf. caisses_escale()) pour
     * repérer une caisse oubliée quelque part dans la compagnie.
     */
    public function getCaissesAnciennesCompagnie(int $idCompagnie): array
    {
        $pdo = $this->connect();
        $stmt = $pdo->prepare("
            SELECT cu.*, a.localite, a.numeroGare, u.utilisateurs AS nom_operateur, u.droit,
                   v.statut AS statut_versement,
                   DATEDIFF(CURDATE(), cu.date_service) AS jours_ecoules
            FROM caisse_utilisateur cu
            INNER JOIN agence a ON cu.id_agence = a.idAgence
            INNER JOIN utilisateur u ON cu.id_utilisateur = u.idUser
            LEFT JOIN versements_caisse v ON v.id_caisse_user = cu.id_caisse_user AND v.statut != 'rejete'
            WHERE cu.id_compagnie = :id_compagnie
              AND cu.date_service < CURDATE()
              AND cu.statut IN ('ouverte', 'fermee')
            ORDER BY cu.date_service ASC, a.localite ASC
        ");
        $stmt->execute([':id_compagnie' => $idCompagnie]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Ouvre une nouvelle caisse pour l'utilisateur connecté.
     * Refuse si une caisse est déjà ouverte aujourd'hui.
     */
    public function ouvrirCaisse(): bool
    {
        $idUser       = (int)($_SESSION['id_utilisateur'] ?? 0);
        $idCompagnie  = (int)($_SESSION['id_compagnie'] ?? 0);

        // Admin (plusieurs gares) : gare choisie dans le formulaire, revalidée contre sa
        // propre compagnie. chef_d_escale/Utilisateur (une seule gare) : toujours leur gare
        // de session, jamais une valeur postée par le client.
        if (($_SESSION['droit'] ?? null) === 'Admin') {
            $agence = $this->fetchOne(
                "SELECT idAgence FROM agence WHERE idAgence = :id AND id_compagnie = :ic LIMIT 1",
                [':id' => (int)($_POST['id_agence'] ?? 0), ':ic' => $idCompagnie]
            );
            $idAgence = $agence ? (int)$agence['idAgence'] : 0;
            if (!$idAgence) {
                $this->set_flash("Veuillez choisir la gare concernée par cette caisse.", "danger");
                return false;
            }
        } else {
            $idAgence = (int)($_SESSION['id_agence'] ?? 0);
        }

        if (!$idUser || !$idAgence || !$idCompagnie) {
            $this->set_flash("Informations de session manquantes.", "danger");
            return false;
        }

        $montantInitial = (float)($_POST['montant_initial'] ?? 0);
        if ($montantInitial < 0) {
            $this->set_flash("Le montant initial ne peut pas être négatif.", "danger");
            return false;
        }

        $reference = 'CU' . date('Ymd') . '-' . $idUser . '-' . random_int(100, 999);
        $pdo       = $this->connect();

        try {
            $pdo->beginTransaction();

            // Verrouille l'utilisateur le temps de la transaction : sans ça, deux clics rapides
            // (ou deux onglets) sur "Ouvrir ma caisse" peuvent tous les deux lire "aucune caisse
            // ouverte" avant qu'aucun n'écrive, et créer deux caisses ouvertes en parallèle pour
            // le même utilisateur le même jour (les crédits de vente iraient alors au hasard sur
            // l'une ou l'autre).
            $pdo->prepare("SELECT idUser FROM utilisateur WHERE idUser = :id FOR UPDATE")
                ->execute([':id' => $idUser]);

            $existante = $this->getCaisseOuverte($idUser);
            if ($existante) {
                $pdo->rollBack();
                $this->set_flash("Vous avez déjà une caisse ouverte aujourd'hui (Réf : {$existante->reference}).", "warning");
                return false;
            }

            $stmt = $pdo->prepare("
                INSERT INTO caisse_utilisateur
                    (id_utilisateur, id_agence, id_compagnie, date_service, heure_ouverture, montant_initial, statut, reference)
                VALUES
                    (:id_user, :id_agence, :id_compagnie, CURDATE(), NOW(), :montant_initial, 'ouverte', :reference)
            ");
            $stmt->execute([
                ':id_user'        => $idUser,
                ':id_agence'      => $idAgence,
                ':id_compagnie'   => $idCompagnie,
                ':montant_initial' => $montantInitial,
                ':reference'      => $reference,
            ]);
            $idCaisseUser = (int)$pdo->lastInsertId();

            // Journal : ouverture
            $this->insererJournal($pdo, $idCaisseUser, $idUser, 'ouverture', $reference, $montantInitial, 'Ouverture de caisse');

            $pdo->commit();
            $this->set_flash("Caisse ouverte avec succès. Réf : $reference", "success");
            return true;
        } catch (Throwable $e) {
            $pdo->rollBack();
            $this->set_flash("Erreur lors de l'ouverture de caisse : " . $e->getMessage(), "danger");
            return false;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ALIMENTATION AUTOMATIQUE
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Crédite la caisse de l'utilisateur lors d'une vente de billet.
     * Appelé depuis Add_billet::saveBillets() dans la même transaction PDO.
     *
     * @param \PDO   $pdo          Connexion PDO de la transaction en cours
     * @param int    $idUser       Identifiant du billettère
     * @param float  $montant      Montant du billet
     * @param string $reference    Numéro du billet
     * @param int    $idBillet     ID du billet inséré
     * @return int|false           id_caisse_user ou false si pas de caisse ouverte
     */
    public function crediterBillet(\PDO $pdo, int $idUser, float $montant, string $reference, int $idBillet)
    {
        $caisse = $this->_getCaisseOuverteByPdo($pdo, $idUser);
        if (!$caisse) {
            return false;
        }

        $stmt = $pdo->prepare("
            UPDATE caisse_utilisateur
            SET total_billets = total_billets + :montant,
                nb_billets    = nb_billets + 1
            WHERE id_caisse_user = :id
        ");
        $stmt->execute([':montant' => $montant, ':id' => $caisse->id_caisse_user]);

        // Rattacher le billet à la caisse
        $pdo->prepare("UPDATE billets SET id_caisse_user = :cu WHERE idBillets = :ib")
            ->execute([':cu' => $caisse->id_caisse_user, ':ib' => $idBillet]);

        $this->insererJournal($pdo, $caisse->id_caisse_user, $idUser, 'billet', $reference, $montant, "Vente billet $reference");

        return $caisse->id_caisse_user;
    }

    /**
     * Crédite la caisse de l'utilisateur lors d'un enregistrement de colis.
     * Appelé depuis Colis_prise_en_charge::saveColis() dans la même transaction PDO.
     */
    public function crediterColis(\PDO $pdo, int $idUser, float $montant, string $codeColis, int $idColis)
    {
        $caisse = $this->_getCaisseOuverteByPdo($pdo, $idUser);
        if (!$caisse) {
            return false;
        }

        $stmt = $pdo->prepare("
            UPDATE caisse_utilisateur
            SET total_colis = total_colis + :montant,
                nb_colis    = nb_colis + 1
            WHERE id_caisse_user = :id
        ");
        $stmt->execute([':montant' => $montant, ':id' => $caisse->id_caisse_user]);

        // Rattacher le colis à la caisse
        $pdo->prepare("UPDATE colis SET id_caisse_user = :cu WHERE id_colis = :ic")
            ->execute([':cu' => $caisse->id_caisse_user, ':ic' => $idColis]);

        $this->insererJournal($pdo, $caisse->id_caisse_user, $idUser, 'colis', $codeColis, $montant, "Colis $codeColis");

        return $caisse->id_caisse_user;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FERMETURE
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Ferme la caisse de l'utilisateur.
     * Calcule l'écart entre montant attendu et montant compté.
     */
    public function fermerCaisse(): bool
    {
        if (!csrf_verify()) {
            $this->set_flash("Session expirée, réessayez.", "danger");
            return false;
        }

        $idUser        = (int)($_SESSION['id_utilisateur'] ?? 0);
        $idCaisseUser  = (int)($_POST['id_caisse_user'] ?? 0);
        $montantCompte = (float)($_POST['montant_compte'] ?? -1);

        if ($montantCompte < 0) {
            $this->set_flash("Veuillez saisir le montant compté (0 ou plus).", "danger");
            return false;
        }

        $pdo = $this->connect();

        // Récupérer la caisse et vérifier qu'elle appartient bien à cet utilisateur
        $stmt = $pdo->prepare("
            SELECT * FROM caisse_utilisateur
            WHERE id_caisse_user = :id AND id_utilisateur = :idUser AND statut = 'ouverte'
            LIMIT 1
        ");
        $stmt->execute([':id' => $idCaisseUser, ':idUser' => $idUser]);
        $caisse = $stmt->fetch(PDO::FETCH_OBJ);

        if (!$caisse) {
            $this->set_flash("Caisse introuvable ou déjà fermée.", "danger");
            return false;
        }

        $montantAttendu = (float)$caisse->montant_initial + (float)$caisse->total_billets + (float)$caisse->total_colis;
        $ecart          = $montantCompte - $montantAttendu;

        try {
            $pdo->beginTransaction();

            // Compare-and-swap : sans le "AND statut = 'ouverte'" ici, deux clics sur
            // "Fermer la caisse" pourraient tous les deux passer le check ci-dessus (fait
            // avant la transaction) et créer deux entrées de journal "fermeture" pour la
            // même caisse.
            $stmtFermer = $pdo->prepare("
                UPDATE caisse_utilisateur
                SET statut           = 'fermee',
                    heure_fermeture  = NOW(),
                    montant_compte   = :montant_compte,
                    ecart            = :ecart
                WHERE id_caisse_user = :id AND statut = 'ouverte'
            ");
            $stmtFermer->execute([
                ':montant_compte' => $montantCompte,
                ':ecart'          => $ecart,
                ':id'             => $idCaisseUser,
            ]);

            if ($stmtFermer->rowCount() === 0) {
                $pdo->rollBack();
                $this->set_flash("Cette caisse a déjà été fermée entre-temps.", "warning");
                return false;
            }

            $this->insererJournal($pdo, $idCaisseUser, $idUser, 'fermeture', $caisse->reference, $montantCompte,
                "Fermeture – attendu: {$montantAttendu} FCFA, compté: {$montantCompte} FCFA, écart: {$ecart} FCFA");

            $pdo->commit();

            $msg = $ecart == 0 ? "Caisse fermée – Aucun écart." :
                  ($ecart > 0 ? "Caisse fermée – Excédent : " . number_format($ecart, 0, ',', ' ') . " FCFA." :
                                "Caisse fermée – Déficit : " . number_format(abs($ecart), 0, ',', ' ') . " FCFA.");
            $type = $ecart == 0 ? 'success' : ($ecart > 0 ? 'info' : 'warning');
            $this->set_flash($msg, $type);
            return true;
        } catch (Throwable $e) {
            $pdo->rollBack();
            $this->set_flash("Erreur lors de la fermeture : " . $e->getMessage(), "danger");
            return false;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // VERSEMENT
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Crée un versement (opérateur → chef d'escale).
     * La caisse doit être fermée avant de verser.
     */
    public function creerVersement(): bool
    {
        if (!csrf_verify()) {
            $this->set_flash("Session expirée, réessayez.", "danger");
            return false;
        }

        $idUser       = (int)($_SESSION['id_utilisateur'] ?? 0);
        $idCaisseUser = (int)($_POST['id_caisse_user'] ?? 0);
        $idChef       = (int)($_POST['id_chef_escale'] ?? 0);
        $montant      = (float)($_POST['montant'] ?? 0);
        $commentaire  = trim($_POST['commentaire'] ?? '');

        if ($montant <= 0) {
            $this->set_flash("Le montant du versement doit être supérieur à 0.", "danger");
            return false;
        }
        if (!$idChef) {
            $this->set_flash("Veuillez sélectionner un chef d'escale.", "danger");
            return false;
        }

        $pdo = $this->connect();

        // Vérifier que la caisse est fermée et appartient à cet utilisateur
        $stmt = $pdo->prepare("
            SELECT * FROM caisse_utilisateur
            WHERE id_caisse_user = :id AND id_utilisateur = :idUser AND statut = 'fermee'
            LIMIT 1
        ");
        $stmt->execute([':id' => $idCaisseUser, ':idUser' => $idUser]);
        $caisse = $stmt->fetch(PDO::FETCH_OBJ);

        if (!$caisse) {
            $this->set_flash("Veuillez d'abord fermer votre caisse avant de verser.", "warning");
            return false;
        }

        // Le formulaire ne propose que les chefs d'escale de la même gare/compagnie
        // (getChefsDEscale()), mais rien ne revérifiait côté serveur que l'ID posté l'est
        // toujours : un id_chef_escale trafiqué pouvait viser un chef d'une AUTRE compagnie
        // (le versement restait alors orphelin, invisible de tous, mais l'argent de la caisse
        // était quand même marqué "versé" sans destinataire valide).
        $chefValide = $pdo->prepare("
            SELECT idUser FROM utilisateur
            WHERE idUser = :chef AND droit = 'chef_d_escale' AND id_agence = :agence AND id_compagnie = :compagnie
            LIMIT 1
        ");
        $chefValide->execute([':chef' => $idChef, ':agence' => $caisse->id_agence, ':compagnie' => $caisse->id_compagnie]);
        if (!$chefValide->fetch()) {
            $this->set_flash("Ce chef d'escale ne correspond pas à la gare de cette caisse.", "danger");
            return false;
        }

        try {
            $pdo->beginTransaction();

            // Verrouille la caisse le temps de la transaction, puis revérifie qu'aucun
            // versement n'existe déjà : sans ça, deux soumissions rapides du même formulaire
            // (double-clic) pouvaient toutes les deux passer ce contrôle avant qu'aucune
            // n'écrive, et créer deux versements pour la même caisse.
            $pdo->prepare("SELECT id_caisse_user FROM caisse_utilisateur WHERE id_caisse_user = :id FOR UPDATE")
                ->execute([':id' => $idCaisseUser]);

            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM versements_caisse WHERE id_caisse_user = :id AND statut != 'rejete'");
            $stmtCheck->execute([':id' => $idCaisseUser]);
            if ($stmtCheck->fetchColumn() > 0) {
                $pdo->rollBack();
                $this->set_flash("Un versement existe déjà pour cette caisse.", "warning");
                return false;
            }

            $pdo->prepare("
                INSERT INTO versements_caisse
                    (id_caisse_user, id_emetteur, id_chef_escale, id_agence, id_compagnie, montant, statut, commentaire)
                VALUES
                    (:cu, :em, :chef, :agence, :comp, :montant, 'en_attente', :comment)
            ")->execute([
                ':cu'      => $idCaisseUser,
                ':em'      => $idUser,
                ':chef'    => $idChef,
                ':agence'  => $caisse->id_agence,
                ':comp'    => $caisse->id_compagnie,
                ':montant' => $montant,
                ':comment' => $commentaire ?: null,
            ]);
            $idVersement = $pdo->lastInsertId();

            // La caisse reste 'fermee' tant que le versement n'est pas VALIDE par le chef
            // d'escale (cf. validerVersement()) : elle ne doit passer 'versee' qu'a ce moment-la,
            // pas des la simple soumission de la demande, sinon l'ecran "Ma Caisse" affiche
            // "Versee" avant meme que quiconque n'ait valide quoi que ce soit -- et si la demande
            // est rejetee, rien ne repasse la caisse a 'fermee', bloquant tout nouveau versement
            // pour cette caisse (creerVersement() exige statut = 'fermee' plus haut). Le garde-fou
            // anti-doublon ci-dessus (COUNT ... WHERE statut != 'rejete') suffit deja a empecher
            // une deuxieme demande tant que celle-ci est en_attente.
            $this->insererJournal($pdo, $idCaisseUser, $idUser, 'versement', "VRS-$idVersement", $montant,
                "Versement de " . number_format($montant, 0, ',', ' ') . " FCFA au chef d'escale");

            $pdo->commit();
            $this->set_flash("Versement de " . number_format($montant, 0, ',', ' ') . " FCFA soumis avec succès. En attente de validation.", "success");
            return true;
        } catch (Throwable $e) {
            $pdo->rollBack();
            $this->set_flash("Erreur lors du versement : " . $e->getMessage(), "danger");
            return false;
        }
    }

    /**
     * Chef d'escale valide ou rejette un versement.
     */
    public function validerVersement(int $idVersement, string $action): bool
    {
        if (!in_array($action, ['valide', 'rejete'], true)) return false;

        $pdo         = $this->connect();
        $idCompagnie = (int)($_SESSION['id_compagnie'] ?? 0);

        // Admin (pas de gare fixe, supervise toute la compagnie) : gare postée, revalidée
        // contre sa propre compagnie — jamais de filtre sur id_chef_escale, l'Admin n'est pas
        // "le" chef d'escale visé par le versement. chef_d_escale : uniquement les versements
        // qui lui sont explicitement adressés, sur sa propre gare de session.
        if (($_SESSION['droit'] ?? null) === 'Admin') {
            $idAgence = (int)($_POST['id_agence'] ?? 0);
            $agenceValide = $this->fetchOne(
                "SELECT idAgence FROM agence WHERE idAgence = :id AND id_compagnie = :ic LIMIT 1",
                [':id' => $idAgence, ':ic' => $idCompagnie]
            );
            if (!$agenceValide) {
                $this->set_flash("Gare invalide.", "danger");
                return false;
            }
            $where  = "id_versement = :id AND id_agence = :agence AND statut = 'en_attente'";
            $params = [':id' => $idVersement, ':agence' => $idAgence];
        } else {
            $idChef   = (int)($_SESSION['id_utilisateur'] ?? 0);
            $idAgence = (int)($_SESSION['id_agence'] ?? 0);
            $where    = "id_versement = :id AND id_chef_escale = :chef AND id_agence = :agence AND statut = 'en_attente'";
            $params   = [':id' => $idVersement, ':chef' => $idChef, ':agence' => $idAgence];
        }

        $stmt = $pdo->prepare("SELECT * FROM versements_caisse WHERE $where LIMIT 1");
        $stmt->execute($params);
        $versement = $stmt->fetch(PDO::FETCH_OBJ);

        if (!$versement) {
            $this->set_flash("Versement introuvable ou déjà traité.", "danger");
            return false;
        }

        try {
            $pdo->beginTransaction();

            // Compare-and-swap : sans le "AND statut = 'en_attente'" ici, valider et rejeter
            // lancés au même moment (deux onglets, double-clic) pourraient tous les deux
            // passer le SELECT ci-dessus avant qu'aucun n'écrive.
            $stmtValider = $pdo->prepare("
                UPDATE versements_caisse
                SET statut = :statut, date_validation = NOW()
                WHERE id_versement = :id AND statut = 'en_attente'
            ");
            $stmtValider->execute([':statut' => $action, ':id' => $idVersement]);

            if ($stmtValider->rowCount() === 0) {
                $pdo->rollBack();
                $this->set_flash("Ce versement a déjà été traité entre-temps.", "warning");
                return false;
            }

            // La caisse ne passe 'versee' qu'ICI, a la validation reelle par le chef d'escale
            // (cf. creerVersement(), qui ne la touche plus a la simple soumission). En cas de
            // rejet, la caisse est deja restee 'fermee' depuis sa fermeture initiale : rien a
            // faire, l'operateur peut directement soumettre un nouveau versement corrige.
            if ($action === 'valide') {
                $pdo->prepare("UPDATE caisse_utilisateur SET statut = 'versee' WHERE id_caisse_user = :id")
                    ->execute([':id' => $versement->id_caisse_user]);
            }

            $pdo->commit();

            $msg = $action === 'valide' ? "Versement validé avec succès." : "Versement rejeté.";
            $type = $action === 'valide' ? 'success' : 'warning';
            $this->set_flash($msg, $type);
            return true;
        } catch (Throwable $e) {
            $pdo->rollBack();
            $this->set_flash("Erreur : " . $e->getMessage(), "danger");
            return false;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CLÔTURE D'ESCALE (Chef d'escale)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Clôture la journée de l'escale et génère un rapport récapitulatif.
     */
    public function cloturerEscale(): bool
    {
        if (!csrf_verify()) {
            $this->set_flash("Session expirée, réessayez.", "danger");
            return false;
        }

        $idChef      = (int)($_SESSION['id_utilisateur'] ?? 0);
        $idCompagnie = (int)($_SESSION['id_compagnie'] ?? 0);
        $dateCloture = $_POST['date_cloture'] ?? date('Y-m-d');

        // Admin (pas de gare fixe) : gare postée, revalidée contre sa compagnie.
        // chef_d_escale : toujours sa propre gare de session.
        if (($_SESSION['droit'] ?? null) === 'Admin') {
            $idAgence = (int)($_POST['id_agence'] ?? 0);
            $agenceValide = $this->fetchOne(
                "SELECT idAgence FROM agence WHERE idAgence = :id AND id_compagnie = :ic LIMIT 1",
                [':id' => $idAgence, ':ic' => $idCompagnie]
            );
            if (!$agenceValide) {
                $this->set_flash("Veuillez choisir la gare à clôturer.", "danger");
                return false;
            }
        } else {
            $idAgence = (int)($_SESSION['id_agence'] ?? 0);
        }

        $pdo = $this->connect();

        // Calculer les totaux pour la date
        $stmt = $pdo->prepare("
            SELECT
                COALESCE(SUM(total_billets), 0)  AS total_billets,
                COALESCE(SUM(total_colis), 0)    AS total_colis,
                COALESCE(SUM(CASE WHEN ecart IS NOT NULL THEN ecart ELSE 0 END), 0) AS total_ecarts
            FROM caisse_utilisateur
            WHERE id_agence = :agence AND date_service = :date
              AND statut IN ('fermee', 'versee')
        ");
        $stmt->execute([':agence' => $idAgence, ':date' => $dateCloture]);
        $totaux = $stmt->fetch(PDO::FETCH_OBJ);

        // Versements validés pour cette date
        $stmt2 = $pdo->prepare("
            SELECT COALESCE(SUM(v.montant), 0) AS total_versements
            FROM versements_caisse v
            INNER JOIN caisse_utilisateur cu ON v.id_caisse_user = cu.id_caisse_user
            WHERE v.id_agence = :agence
              AND cu.date_service = :date
              AND v.statut = 'valide'
        ");
        $stmt2->execute([':agence' => $idAgence, ':date' => $dateCloture]);
        $totalVersements = (float)$stmt2->fetchColumn();

        // Détail par opérateur pour le rapport JSON
        $stmt3 = $pdo->prepare("
            SELECT cu.*, u.utilisateurs AS nom_operateur, u.droit
            FROM caisse_utilisateur cu
            INNER JOIN utilisateur u ON cu.id_utilisateur = u.idUser
            WHERE cu.id_agence = :agence AND cu.date_service = :date
              AND cu.statut IN ('fermee', 'versee')
        ");
        $stmt3->execute([':agence' => $idAgence, ':date' => $dateCloture]);
        $detail = $stmt3->fetchAll(PDO::FETCH_ASSOC);

        $rapport = json_encode([
            'date'      => $dateCloture,
            'operateurs' => $detail,
            'totaux'    => [
                'billets'     => (float)$totaux->total_billets,
                'colis'       => (float)$totaux->total_colis,
                'versements'  => $totalVersements,
                'ecarts'      => (float)$totaux->total_ecarts,
            ],
        ], JSON_UNESCAPED_UNICODE);

        try {
            $pdo->beginTransaction();

            $pdo->prepare("
                INSERT INTO clotures_escale
                    (id_chef_escale, id_agence, id_compagnie, date_cloture, total_billets, total_colis, total_versements, total_ecarts, statut, rapport_json)
                VALUES
                    (:chef, :agence, :comp, :date, :tbillets, :tcolis, :tversements, :tecarts, 'validee', :rapport)
                ON DUPLICATE KEY UPDATE
                    total_billets    = VALUES(total_billets),
                    total_colis      = VALUES(total_colis),
                    total_versements = VALUES(total_versements),
                    total_ecarts     = VALUES(total_ecarts),
                    statut           = 'validee',
                    rapport_json     = VALUES(rapport_json)
            ")->execute([
                ':chef'        => $idChef,
                ':agence'      => $idAgence,
                ':comp'        => $idCompagnie,
                ':date'        => $dateCloture,
                ':tbillets'    => (float)$totaux->total_billets,
                ':tcolis'      => (float)$totaux->total_colis,
                ':tversements' => $totalVersements,
                ':tecarts'     => (float)$totaux->total_ecarts,
                ':rapport'     => $rapport,
            ]);

            $pdo->commit();
            $this->set_flash("Clôture de l'escale effectuée avec succès pour le $dateCloture.", "success");
            return true;
        } catch (Throwable $e) {
            $pdo->rollBack();
            $this->set_flash("Erreur lors de la clôture : " . $e->getMessage(), "danger");
            return false;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DONNÉES POUR LES VUES
    // ─────────────────────────────────────────────────────────────────────────

    /** Journal des opérations d'une caisse */
    public function getJournal(int $idCaisseUser): array
    {
        $pdo  = $this->connect();
        $stmt = $pdo->prepare("
            SELECT j.*, u.utilisateurs AS nom_user
            FROM journal_caisse j
            INNER JOIN utilisateur u ON j.id_utilisateur = u.idUser
            WHERE j.id_caisse_user = :id
            ORDER BY j.date_heure DESC
        ");
        $stmt->execute([':id' => $idCaisseUser]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /** Toutes les caisses d'une agence pour une date donnée (vue chef d'escale) */
    public function getCaissesEscale(int $idAgence, string $date = null): array
    {
        $date = $date ?? date('Y-m-d');
        $pdo  = $this->connect();
        $stmt = $pdo->prepare("
            SELECT cu.*, u.utilisateurs AS nom_operateur, u.droit,
                   COALESCE(v.montant, 0) AS montant_verse, v.statut AS statut_versement
            FROM caisse_utilisateur cu
            INNER JOIN utilisateur u ON cu.id_utilisateur = u.idUser
            LEFT JOIN versements_caisse v ON v.id_caisse_user = cu.id_caisse_user AND v.statut != 'rejete'
            WHERE cu.id_agence = :agence AND cu.date_service = :date
            ORDER BY cu.heure_ouverture
        ");
        $stmt->execute([':agence' => $idAgence, ':date' => $date]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Versements en attente pour une gare. $idChef = null pour un Admin en supervision
     * (voit tous les versements de la gare, pas seulement ceux adressés à lui-même — un
     * Admin n'est jamais "le" chef d'escale visé par un versement).
     */
    public function getVersementsEnAttente(?int $idChef, int $idAgence): array
    {
        $sql = "
            SELECT v.*, u.utilisateurs AS nom_emetteur,
                   cu.total_billets, cu.total_colis, cu.montant_compte, cu.ecart,
                   cu.date_service, cu.reference AS ref_caisse
            FROM versements_caisse v
            INNER JOIN utilisateur u ON v.id_emetteur = u.idUser
            INNER JOIN caisse_utilisateur cu ON v.id_caisse_user = cu.id_caisse_user
            WHERE v.id_agence = :agence AND v.statut = 'en_attente'";
        $params = [':agence' => $idAgence];
        if ($idChef !== null) {
            $sql .= " AND v.id_chef_escale = :chef";
            $params[':chef'] = $idChef;
        }
        $sql .= " ORDER BY v.date_versement DESC";

        $stmt = $this->connect()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /** Tous les versements (historique) pour une gare. $idChef = null : voir getVersementsEnAttente(). */
    public function getHistoriqueVersements(?int $idChef, int $idAgence): array
    {
        $sql = "
            SELECT v.*, u.utilisateurs AS nom_emetteur, cu.date_service, cu.reference AS ref_caisse
            FROM versements_caisse v
            INNER JOIN utilisateur u ON v.id_emetteur = u.idUser
            INNER JOIN caisse_utilisateur cu ON v.id_caisse_user = cu.id_caisse_user
            WHERE v.id_agence = :agence";
        $params = [':agence' => $idAgence];
        if ($idChef !== null) {
            $sql .= " AND v.id_chef_escale = :chef";
            $params[':chef'] = $idChef;
        }
        $sql .= " ORDER BY v.date_versement DESC LIMIT 100";

        $stmt = $this->connect()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /** Données consolidées pour le propriétaire (toutes escales d'une compagnie) */
    public function getRapportProprietaire(int $idCompagnie, string $date = null): array
    {
        $date = $date ?? date('Y-m-d');
        $pdo  = $this->connect();
        $stmt = $pdo->prepare("
            SELECT
                a.localite, a.numeroGare, a.idAgence,
                COALESCE(SUM(cu.total_billets), 0)  AS total_billets,
                COALESCE(SUM(cu.total_colis), 0)    AS total_colis,
                COALESCE(SUM(cu.total_billets) + SUM(cu.total_colis), 0) AS grand_total,
                COALESCE(SUM(CASE WHEN cu.ecart IS NOT NULL THEN cu.ecart ELSE 0 END), 0) AS total_ecarts,
                COUNT(cu.id_caisse_user) AS nb_caisses,
                COALESCE(SUM(CASE WHEN cu.statut = 'ouverte' THEN 1 ELSE 0 END), 0) AS caisses_ouvertes,
                COALESCE(SUM(CASE WHEN cu.statut IN ('fermee','versee') THEN 1 ELSE 0 END), 0) AS caisses_fermees
            FROM agence a
            LEFT JOIN caisse_utilisateur cu ON cu.id_agence = a.idAgence AND cu.date_service = :date
            WHERE a.id_compagnie = :comp
            GROUP BY a.idAgence, a.localite, a.numeroGare
            ORDER BY a.localite, a.numeroGare
        ");
        $stmt->execute([':comp' => $idCompagnie, ':date' => $date]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /** Chefs d'escale disponibles pour une agence (pour le formulaire de versement) */
    public function getChefsDEscale(int $idAgence, int $idCompagnie): array
    {
        $pdo  = $this->connect();
        $stmt = $pdo->prepare("
            SELECT idUser, utilisateurs
            FROM utilisateur
            WHERE droit = 'chef_d_escale'
              AND id_agence = :agence
              AND id_compagnie = :comp
              AND status = 1
        ");
        $stmt->execute([':agence' => $idAgence, ':comp' => $idCompagnie]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /** Historique des caisses d'un utilisateur */
    public function getHistoriqueCaisses(int $idUser, int $limit = 30): array
    {
        $pdo  = $this->connect();
        $stmt = $pdo->prepare("
            SELECT cu.*, a.localite, a.numeroGare,
                   (cu.montant_initial + cu.total_billets + cu.total_colis) AS montant_attendu
            FROM caisse_utilisateur cu
            INNER JOIN agence a ON cu.id_agence = a.idAgence
            WHERE cu.id_utilisateur = :id
            ORDER BY cu.date_service DESC, cu.heure_ouverture DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':id', $idUser, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPERS PRIVÉS
    // ─────────────────────────────────────────────────────────────────────────

    /** Récupère la caisse ouverte via une connexion PDO existante (pour les transactions) */
    private function _getCaisseOuverteByPdo(\PDO $pdo, int $idUser): ?object
    {
        $stmt = $pdo->prepare("
            SELECT * FROM caisse_utilisateur
            WHERE id_utilisateur = :id AND date_service = CURDATE() AND statut = 'ouverte'
            LIMIT 1
        ");
        $stmt->execute([':id' => $idUser]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: null;
    }

    /** Insère une ligne dans le journal de caisse */
    private function insererJournal(\PDO $pdo, int $idCaisseUser, int $idUser, string $type, ?string $reference, float $montant, string $libelle): void
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
}
