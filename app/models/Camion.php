<?php
class Camion extends Model
{

    // Meme principe que Cars_chauffeur::saveCare() ("add to row", plusieurs
    // lignes numero_camion[]/matriculle_camion[] alignees par index) mais sans
    // nbr_place : un camion n'a pas de notion de places passagers.
    public function saveCamion()
    {
        $id_compagnie = $_SESSION["id_compagnie"];
        $numeros = $_POST['numero_camion'] ?? [];
        $matriculles = $_POST['matriculle_camion'] ?? [];
        if (!is_array($numeros)) {
            $numeros = [$numeros];
            $matriculles = [$matriculles];
        }

        $nbAjoutes = 0;
        $erreurs = [];

        foreach ($numeros as $i => $numero_camion) {
            $numero_camion = trim($numero_camion);
            $matriculle = trim($matriculles[$i] ?? '');

            if ($numero_camion === '' && $matriculle === '') {
                continue;
            }

            if (empty($numero_camion)) {
                $erreurs[] = "Ligne " . ($i + 1) . " : le numéro du camion est obligatoire.";
                continue;
            }
            if (empty($matriculle)) {
                $erreurs[] = "Ligne " . ($i + 1) . " : le matricule est obligatoire.";
                continue;
            }
            if ($this->existe_deja('numero_camion', $numero_camion, 'camion')) {
                $erreurs[] = "Le camion « $numero_camion » existe déjà.";
                continue;
            }

            $insertion = $this->insertion_update_simples(
                "INSERT INTO camion (numero_camion, matriculle, actif, id_compagnie)
    VALUES (:numero_camion, :matriculle, :actif, :id_compagnie)",
                [
                    ":numero_camion" => $numero_camion,
                    ":matriculle" => $matriculle,
                    ":actif" => "on",
                    ":id_compagnie" => $id_compagnie
                ]
            );

            if ($insertion) {
                $nbAjoutes++;
            } else {
                $erreurs[] = "Le camion « $numero_camion » n'a pas pu être ajouté.";
            }
        }

        if ($nbAjoutes > 0) {
            $this->set_flash($nbAjoutes > 1 ? "$nbAjoutes camions ajoutés avec succès." : "Camion ajouté avec succès.", 'info');
        }
        foreach ($erreurs as $erreur) {
            $this->set_flash($erreur, "danger");
        }
        if ($nbAjoutes === 0 && count($erreurs) === 0) {
            $this->set_flash("Aucun camion à ajouter.", "danger");
        }
    }

    public function updateCamion($id, $data) {
        // Un Admin ne peut modifier que les camions de sa propre compagnie (IDOR sinon)
        $sql = "UPDATE camion SET numero_camion = :numero, matriculle = :matriculle, actif = :actif WHERE id_camion = :id";
        if (($_SESSION['droit'] ?? null) !== 'super_admin') {
            $sql .= " AND id_compagnie = :id_compagnie";
        }
        $stmt = $this->connect()->prepare($sql);
        $stmt->bindParam(':numero', $data['numero_camion']);
        $stmt->bindParam(':matriculle', $data['matriculle']);
        $stmt->bindParam(':actif', $data['actif']);
        $stmt->bindParam(':id', $id);
        if (($_SESSION['droit'] ?? null) !== 'super_admin') {
            $stmt->bindValue(':id_compagnie', $_SESSION['id_compagnie'] ?? null);
        }
        return $stmt->execute();
    }

    // Pour l'ecran "Flotte" : tous les camions de la compagnie, actifs ET inactifs
    // (contrairement a Envoie_colis::getCamionsActifs(), qui ne veut que les actifs
    // pour l'envoi de colis -- ici on veut voir l'etat de toute la flotte). Meme
    // portee que Programmation_voyage::getEtatFlotte() : filtre par id_compagnie de
    // session sans exception super_admin (deja le comportement actuel pour les cars).
    public function getTousPourFlotte()
    {
        // FetchSelectWheres() (et non FetchSelectWhere1()) : renvoie des OBJETS, pour
        // rester coherent avec $cars dans la meme vue flotte.view.php (Programmation_voyage::
        // getEtatFlotte() utilise SelectAllDatas(), objets egalement -- une confusion
        // tableau/objet entre les deux jeux de donnees affiches cote a cote serait facile
        // a introduire par erreur, cf. les bugs deja rencontres sur ce point dans ce projet).
        return $this->FetchSelectWheres(
            "*",
            "camion",
            "id_compagnie = :id_compagnie",
            [":id_compagnie" => $_SESSION['id_compagnie'] ?? null]
        );
    }

    public function deleteCamion($id) {
        // Un Admin ne peut supprimer que les camions de sa propre compagnie (IDOR sinon)
        $sql = "DELETE FROM camion WHERE id_camion = :id";
        if (($_SESSION['droit'] ?? null) !== 'super_admin') {
            $sql .= " AND id_compagnie = :id_compagnie";
        }
        $stmt = $this->connect()->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        if (($_SESSION['droit'] ?? null) !== 'super_admin') {
            $stmt->bindValue(':id_compagnie', $_SESSION['id_compagnie'] ?? null);
        }
        return $stmt->execute();
    }

}
