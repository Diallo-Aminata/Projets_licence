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
        $id_compagnie = $_SESSION['id_compagnie'] ?? null;
        $sql = "SELECT c.*, 
                (SELECT lc.destination 
                 FROM location_car lc 
                 WHERE lc.id_camion = c.id_camion 
                   AND lc.statut IN ('en_attente', 'valide') 
                   AND CURDATE() BETWEEN lc.date_depart AND lc.date_retour_prevu 
                 LIMIT 1) as destination_location,
                (SELECT lc.date_retour_prevu 
                 FROM location_car lc 
                 WHERE lc.id_camion = c.id_camion 
                   AND lc.statut IN ('en_attente', 'valide') 
                   AND CURDATE() BETWEEN lc.date_depart AND lc.date_retour_prevu 
                 LIMIT 1) as retour_prevu,
                (SELECT COUNT(*) 
                 FROM envoi e
                 INNER JOIN colis col ON e.id_coli = col.id_colis
                 WHERE e.id_camion = c.id_camion AND col.status = 'en_cours'
                ) as nb_colis_en_cours,
                (SELECT e.date_enregistre
                 FROM envoi e
                 INNER JOIN colis col ON e.id_coli = col.id_colis
                 WHERE e.id_camion = c.id_camion AND col.status = 'en_cours'
                 ORDER BY e.date_enregistre DESC LIMIT 1
                ) as date_envoi_colis
                FROM camion c
                WHERE c.id_compagnie = :id_compagnie
                ORDER BY c.numero_camion";
        
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([':id_compagnie' => $id_compagnie]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
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
