-- Permet de louer un camion en plus d'un car dans l'ecran "Location des cars".
--
-- Meme convention que envoi/ligne_envoi (cf. ajout_camions.sql) : colonne miroir
-- nullable, pas de colonne "type" separee -- le type se deduit de savoir laquelle
-- des deux colonnes (id_car / id_camion) est renseignee.
--
-- Pas de contrainte FOREIGN KEY (ce projet n'en utilise nulle part) : coherence
-- deleguee au code applicatif, comme partout ailleurs dans ce schema.
--
-- A verifier avant execution : DESCRIBE location_car;
-- A executer une seule fois, manuellement, en dev puis en prod, APRES ajout_camions.sql.

ALTER TABLE location_car
    MODIFY id_car INT(11) NULL,
    ADD COLUMN id_camion INT(11) NULL AFTER id_car;
