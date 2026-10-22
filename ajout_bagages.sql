-- Ajoute la gestion des bagages payants associes a un billet : un passager peut avoir
-- plusieurs bagages, saisis soit au moment de la vente du billet, soit apres coup (depuis
-- "Liste du jour"). Pas de formule de calcul automatique pour l'instant : l'agent pese le
-- bagage et saisit le prix a la main. Mirroir du pattern deja utilise par transfert_billets
-- (table liee a billets par idBillets, 1-N, pas de contrainte FOREIGN KEY : coherence
-- deleguee au code applicatif, comme partout ailleurs dans ce projet).
--
-- A executer une seule fois.

-- journal_caisse.type_operation est un ENUM : 'bagage' doit y etre ajoute avant tout
-- appel a Caisse_utilisateur::crediterBagage() (sinon MySQL tronque la valeur et insere
-- une chaine vide, provoquant une erreur SQL a la vente).
ALTER TABLE journal_caisse
  MODIFY COLUMN type_operation ENUM('ouverture','billet','colis','fermeture','versement','annulation','bagage') NOT NULL;

CREATE TABLE IF NOT EXISTS bagages (
  id_bagage INT AUTO_INCREMENT PRIMARY KEY,
  idBillets INT NOT NULL,
  description VARCHAR(255) NULL,
  poids DECIMAL(6,2) NULL,
  prix DECIMAL(10,2) NOT NULL,
  id_compagnie INT NOT NULL,
  id_utilisateur INT NOT NULL,
  date_enregistrement DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_bagages_idBillets (idBillets)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Nouvelle permission, convention "Billets_<action>" deja en place (Billets_creation,
-- Billets_impression, ...).
INSERT INTO permision (nom_permission)
SELECT 'Billets_bagages'
WHERE NOT EXISTS (SELECT 1 FROM permision WHERE nom_permission = 'Billets_bagages');

-- Rattrapage : tout compte qui a deja Billets_creation recoit automatiquement
-- Billets_bagages (meme principe que nettoyage_extension_permissions_2.sql pour
-- Billets_annulation/impression/rapport/reporte), pour que les comptes existants n'aient
-- pas besoin d'une reattribution manuelle.
INSERT INTO user_permission (user_id, permission_id)
SELECT DISTINCT up.user_id, bagages.id_permision
FROM user_permission up
JOIN permision parent ON parent.id_permision = up.permission_id
JOIN permision bagages ON bagages.nom_permission = 'Billets_bagages'
WHERE parent.nom_permission = 'Billets_creation'
  AND NOT EXISTS (
    SELECT 1 FROM user_permission up2
    WHERE up2.user_id = up.user_id AND up2.permission_id = bagages.id_permision
  );
