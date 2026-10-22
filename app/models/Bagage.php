<?php

// Bagages payants associes a un billet (cf. ajout_bagages.sql). Pas de formule de calcul
// automatique pour l'instant : l'agent pese le bagage et saisit le prix a la main.
class Bagage extends Model
{
    // Insertion brute, sans gestion de transaction : utilisee a la fois depuis une
    // transaction deja ouverte par l'appelant (vente d'un billet, cf. Add_billet::saveBillets())
    // et depuis ajouterApresCoup() ci-dessous (qui gere, elle, sa propre transaction).
    public function saveBagageAvecPdo(\PDO $pdo, int $idBillets, ?string $description, $poids, $prix): int
    {
        $stmt = $pdo->prepare(
            "INSERT INTO bagages (idBillets, description, poids, prix, id_compagnie, id_utilisateur)
             VALUES (:ib, :desc, :poids, :prix, :ic, :iu)"
        );
        $stmt->execute([
            ':ib'    => $idBillets,
            ':desc'  => ($description !== null && $description !== '') ? $description : null,
            ':poids' => ($poids !== null && $poids !== '') ? $poids : null,
            ':prix'  => $prix,
            ':ic'    => $_SESSION['id_compagnie'] ?? null,
            ':iu'    => $_SESSION['id_utilisateur'] ?? null,
        ]);

        return (int)$pdo->lastInsertId();
    }

    // Ajout d'un ou plusieurs bagages APRES la vente du billet (depuis "Liste du jour") :
    // gere sa propre transaction (insertion de chaque ligne + UNE seule ligne de caisse pour
    // le total, meme principe qu'a la vente). Credite la caisse de l'agent CONNECTE, qui
    // n'est pas forcement celui qui a vendu le billet initial. Mirroir de
    // Transfert_gare::transfererPassagers() (modele autonome pour une operation ponctuelle).
    // $lignes : tableau de ['description' => ?string, 'poids' => mixed, 'prix' => mixed].
    public function ajouterApresCoup(int $idBillets, array $lignes): bool
    {
        $idCompagnie = $_SESSION['id_compagnie'] ?? null;
        $billet = $this->fetchOne(
            "SELECT idBillets, numeroBillets FROM billets WHERE idBillets = :id AND id_compagnie = :ic LIMIT 1",
            [':id' => $idBillets, ':ic' => $idCompagnie]
        );
        if (!$billet) {
            $this->set_flash("Billet introuvable.", "danger");
            return false;
        }

        $lignesValides = [];
        foreach ($lignes as $ligne) {
            $prix = $ligne['prix'] ?? '';
            if ($prix === '' || $prix === null) {
                continue;
            }
            $prix = (float)$prix;
            if ($prix < 0) {
                $this->set_flash("Le prix d'un bagage ne peut pas être négatif.", "danger");
                return false;
            }
            $poids = $ligne['poids'] ?? null;
            if ($poids !== null && $poids !== '' && (float)$poids < 0) {
                $this->set_flash("Le poids d'un bagage ne peut pas être négatif.", "danger");
                return false;
            }
            $lignesValides[] = ['description' => $ligne['description'] ?? null, 'poids' => $poids, 'prix' => $prix];
        }

        if (empty($lignesValides)) {
            $this->set_flash("Veuillez saisir au moins un prix de bagage.", "danger");
            return false;
        }

        $pdo = $this->connect();
        $pdo->beginTransaction();
        try {
            $total = 0.0;
            foreach ($lignesValides as $ligne) {
                $this->saveBagageAvecPdo($pdo, $idBillets, $ligne['description'], $ligne['poids'], $ligne['prix']);
                $total += $ligne['prix'];
            }

            $caisseModel = new Caisse_utilisateur();
            $creditOk = $caisseModel->crediterBagage(
                $pdo,
                (int)$_SESSION['id_utilisateur'],
                $total,
                $billet['numeroBillets'],
                $idBillets
            );

            if ($creditOk === false) {
                $pdo->rollBack();
                $this->set_flash("Opération bloquée : Aucune caisse ouverte pour votre compte. Veuillez ouvrir votre caisse d'abord.", "danger");
                return false;
            }

            $pdo->commit();
            $this->set_flash("Bagage(s) ajouté(s) et caisse alimentée.", "success");
            return true;
        } catch (Throwable $e) {
            $pdo->rollBack();
            $this->set_flash("Erreur SQL : " . $e->getMessage(), "danger");
            return false;
        }
    }

    public function getBagagesPourBillet($idBillets)
    {
        return $this->fetchAll(
            "SELECT id_bagage, description, poids, prix, date_enregistrement
             FROM bagages WHERE idBillets = :ib AND id_compagnie = :ic
             ORDER BY id_bagage",
            [':ib' => $idBillets, ':ic' => $_SESSION['id_compagnie'] ?? null]
        );
    }

    public function getTotalBagages($idBillets): float
    {
        $row = $this->fetchOne(
            "SELECT COALESCE(SUM(prix), 0) AS total FROM bagages
             WHERE idBillets = :ib AND id_compagnie = :ic",
            [':ib' => $idBillets, ':ic' => $_SESSION['id_compagnie'] ?? null]
        );
        return $row ? (float)$row['total'] : 0.0;
    }
}
