-- Saisie dans le systeme des bordereaux d'escale papier (escales isolees sans electricite
-- ni reseau). Deux tables d'audit qui conservent l'ecriture exacte du papier, ligne par
-- ligne, sans creer de faux billets individuels (le papier ne capture que des comptages
-- agreges, pas de passager nomme ni de numero de place). Pas de contrainte FOREIGN KEY,
-- conforme a la convention deja en place partout ailleurs dans ce projet.
--
-- A executer une seule fois.

-- depense.categorie est un ENUM : 'Commission agent d'escale' doit y etre ajoutee avant
-- tout enregistrement avec cette categorie (sinon MySQL tronque la valeur et insere une
-- chaine vide, meme bug que journal_caisse.type_operation corrige dans ajout_bagages.sql).
ALTER TABLE depense
  MODIFY COLUMN categorie ENUM('Carburant','Entretien/Reparation','Peage','Fournitures','Communication','Salaire','Loyer','Assurance','Remboursement annulation','Commission agent d''escale','Autre') NOT NULL;

CREATE TABLE IF NOT EXISTS bordereau_escale (
  id_bordereau INT AUTO_INCREMENT PRIMARY KEY,
  numero_bordereau VARCHAR(40) NULL,
  date_escale DATE NOT NULL,
  numero_bus VARCHAR(50) NULL,
  escale_libelle VARCHAR(150) NULL,
  nom_agent_escale VARCHAR(150) NULL,
  id_agence INT NOT NULL,
  id_compagnie INT NOT NULL,
  id_caisse_user INT NULL,
  total_billets DECIMAL(12,2) NOT NULL DEFAULT 0,
  total_bagages DECIMAL(12,2) NOT NULL DEFAULT 0,
  montant_depenses DECIMAL(12,2) NOT NULL DEFAULT 0,
  montant_commission DECIMAL(12,2) NOT NULL DEFAULT 0,
  total_verse_saisi DECIMAL(12,2) NOT NULL DEFAULT 0,
  saisi_par INT NOT NULL,
  date_saisie DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_bordereau_date (date_escale),
  INDEX idx_bordereau_agence (id_agence)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS bordereau_escale_lignes (
  id_ligne INT AUTO_INCREMENT PRIMARY KEY,
  id_bordereau INT NOT NULL,
  type_ligne ENUM('billet','bagage') NOT NULL,
  destination VARCHAR(150) NULL,
  nombre INT NULL,
  prix_unitaire DECIMAL(10,2) NULL,
  description VARCHAR(255) NULL,
  montant DECIMAL(10,2) NOT NULL,
  INDEX idx_lignes_bordereau (id_bordereau)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
