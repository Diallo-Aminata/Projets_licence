
<?php
class Permission extends Model
{
    // Catalogue des permissions correspondant aux écrans réels de l'admin (sidebar,
    // écrans Configuration...). La table permision n'a pas de seed SQL versionné : sur
    // une base neuve ou vidée, elle est vide et personne (pas même un super_admin
    // nouvellement créé) ne peut se voir assigner de permission tant qu'un admin n'en a
    // pas ajouté manuellement via l'écran "Ajouter permission". On la seed donc ici.
    private const NOMS_PERMISSIONS_PAR_DEFAUT = [
        'utilisateur_apercu',
        'utilisateur_creation',
        'utilisateur_modifier',
        'utilisateur_active/desactive',
        'Configuration_apercu',
        'Configuration_gestion_gare',
        'Configuration_gestion_escale',
        'Configuration_gestion_trajets',
        'Configuration_gestion_horaire',
        'Configuration_gestion_car/chauffeur',
        'Configuration_place/limite',
        'Caisse_creation',
        'Caisse_apercue',
        'Caisse_billant',
        'Caisse_modifier',
        'Billets_creation',
        'Billets_apercue',
        'Billets_validation',
        'Billets_historique',
        'Billets_notification',
        'Billets_annulation',
        'Billets_impression',
        'Billets_rapport',
        'Billets_reporte',
        'Billets_embarquement',
        'Billets_bagages',
        'colis_creation',
        'colis_envoi',
        'colis_mouvement',
        'colis_livraison',
        'colis_reclamation',
        'colis_historique',
        'colis_apercue',
        'Depenses_gestion',
        'Location_gestion',
        'Programme_Creation',
        'Programme_programmer_car',
        'Programme_programmation_voyage',
        'Programme_hors_programme',
        // Salaire/bulletin de paie : donne acces a SON PROPRE salaire + celui de son
        // equipe (scope par gare, cf. Employe::getEmployesVisibles()). Volontairement
        // absente de NOMS_PERMISSIONS_CHEF_ESCALE/BILLET/COLIS : un chef d'escale ou un
        // simple Utilisateur ne la recoit que si l'Admin la lui attribue explicitement.
        'Salaire_apercu',
    ];

    // Chef d'escale : toutes les permissions par défaut SAUF la programmation fixe
    // ("Programme du voyage") et l'affectation des cars sur un trajet — ces deux écrans
    // restent réservés à l'Admin/super_admin, un chef d'escale ne fait qu'utiliser les
    // trajets déjà programmés (programmation journalière, billets, colis, caisse...).
    private const NOMS_PERMISSIONS_CHEF_ESCALE = [
        'utilisateur_apercu',
        'Configuration_apercu',
        'Configuration_gestion_gare',
        'Configuration_gestion_escale',
        'Configuration_gestion_trajets',
        'Configuration_gestion_horaire',
        'Configuration_gestion_car/chauffeur',
        'Configuration_place/limite',
        'Caisse_creation',
        'Caisse_apercue',
        'Caisse_billant',
        'Caisse_modifier',
        'Billets_creation',
        'Billets_apercue',
        'Billets_validation',
        'Billets_historique',
        'Billets_notification',
        'Billets_annulation',
        'Billets_impression',
        'Billets_rapport',
        'Billets_reporte',
        'Billets_embarquement',
        'Billets_bagages',
        'colis_creation',
        'colis_envoi',
        'colis_mouvement',
        'colis_livraison',
        'colis_reclamation',
        'colis_historique',
        'colis_apercue',
        'Depenses_gestion',
        'Location_gestion',
        'Programme_programmation_voyage',
        'Programme_hors_programme',
    ];

    // Utilisateur simple, service "Billetterie" : uniquement les écrans billets + la
    // caisse (indispensable pour encaisser une vente de billet). Pas de Caisse_billant :
    // le "Bilan de caisse" est une vue consolidée toutes gares/toute la compagnie, un
    // simple Utilisateur ne doit voir que sa propre caisse ("Ma Caisse").
    private const NOMS_PERMISSIONS_BILLET = [
        'Billets_creation',
        'Billets_apercue',
        'Billets_validation',
        'Billets_historique',
        'Billets_notification',
        'Billets_impression',
        'Billets_rapport',
        'Billets_reporte',
        'Billets_embarquement',
        'Billets_bagages',
        'Caisse_creation',
        'Caisse_apercue',
        'Caisse_modifier',
    ];

    // Utilisateur simple, service "Colis / Courrier" : uniquement les écrans colis.
    private const NOMS_PERMISSIONS_COLIS = [
        'colis_creation',
        'colis_envoi',
        'colis_mouvement',
        'colis_livraison',
        'colis_reclamation',
        'colis_historique',
        'colis_apercue',
    ];

    public function getAll()
    {
        return $this->FetchSelectAllWhere("id_permision, nom_permission", "permision", "1", []);
    }

    // Synchronise le catalogue par défaut vers la table permision : insère les noms qui n'y
    // sont pas encore (base neuve, vidage total, ou catalogue enrichi après coup dans le
    // code sans que la table ait été mise à jour), sans toucher aux permissions déjà
    // présentes ni à celles ajoutées manuellement via l'écran "Ajouter permission".
    public function seedPermissionsParDefautSiVide(): void
    {
        $existants = array_map(fn($p) => $p->nom_permission, $this->getAll());

        $sql = "INSERT INTO permision (nom_permission) VALUES (:nom_permission)";
        foreach (self::NOMS_PERMISSIONS_PAR_DEFAUT as $nom) {
            if (!in_array($nom, $existants, true)) {
                $this->insertion_update_simples($sql, [':nom_permission' => $nom]);
            }
        }
    }

    // Assigne à un utilisateur nouvellement créé le jeu de permissions correspondant à
    // son droit (et, pour un simple Utilisateur, à son service billet/colis), pour qu'il
    // ait directement accès à ce qu'il peut faire sans passer par l'écran d'assignation.
    // Ne fait rien pour les droits non concernés (ex: Admin, géré autrement).
    public function assignPermissionsParDefautPourRole($idUtilisateur, $droit, $profile = null): void
    {
        // PDG : superviseur lecture seule d'une compagnie. Reçoit le catalogue complet pour
        // que tous les écrans lui soient accessibles ; l'interdiction d'écrire est appliquée
        // ailleurs par un verrou central (App::isEcritureBloqueePourPDG()), pas ici.
        if ($droit === 'super_admin' || $droit === 'Admin' || $droit === 'PDG') {
            $noms = self::NOMS_PERMISSIONS_PAR_DEFAUT;
        } elseif ($droit === 'chef_d_escale') {
            $noms = self::NOMS_PERMISSIONS_CHEF_ESCALE;
        } elseif ($droit === 'Utilisateur' && $profile === 'billet') {
            $noms = self::NOMS_PERMISSIONS_BILLET;
        } elseif ($droit === 'Utilisateur' && $profile === 'colis') {
            $noms = self::NOMS_PERMISSIONS_COLIS;
        } elseif ($droit === 'secretaire') {
            // Un secrétaire n'aura aucun droit par défaut à sa création (0 permission).
            // C'est l'Admin qui choisit manuellement les permissions à attribuer.
            return;
        } else {
            return;
        }

        $this->seedPermissionsParDefautSiVide();

        $parNom = [];
        foreach ($this->getAll() as $permission) {
            $parNom[$permission->nom_permission] = $permission->id_permision;
        }

        foreach ($noms as $nom) {
            if (isset($parNom[$nom])) {
                $this->assignPermissionToUser($idUtilisateur, $parNom[$nom]);
            }
        }
    }

    // Récupère les IDs des permissions d'un utilisateur
    public function getUserPermissions($userId)
    {
        $sql = "SELECT permission_id FROM user_permission WHERE user_id = ?";
        $result = $this->select_data_table_join_where($sql, [$userId]);
        // Retourne un tableau d'IDs
        return array_map(function ($row) {
            return $row->permission_id;
        }, $result);
    }
    // Récupère tous les modules
    public function removeAllUserPermissions($userId)
    {
        $sql = "DELETE FROM user_permission WHERE user_id = ?";
        $this->insertion_update_simples($sql, [$userId]);
    }
    // Assigne une permission à un utilisateur, uniquement si elle n'existe pas
    public function assignPermissionToUser($userId, $permissionId)
    {
        // Vérifier si la permission existe déjà pour cet utilisateur
        $exists = $this->FetchSelectWhere2(
            "*",
            "user_permission",
            "user_id = :user_id AND permission_id = :permission_id",
            [
                ':user_id' => $userId,
                ':permission_id' => $permissionId
            ]
        );

        if (empty($exists)) {
            // Si elle n'existe pas, on l'insère
            $sql = "INSERT INTO user_permission (user_id, permission_id) VALUES (?, ?)";
            $this->insertion_update_simples($sql, [$userId, $permissionId]);
        }
    }
    public function updatePermission($id, $name)
    {
        return $this->insertion_update_simples(
            "UPDATE permissions SET name = :name WHERE id = :id",
            [':name' => $name, ':id' => $id]
        );
    }
    public function countUsersWithPermission($permissionId)
    {
        $sql = "SELECT COUNT(*) as total FROM user_permission WHERE permission_id = ?";
        $result = $this->select_data_table_join_where($sql, [$permissionId]);
        return $result[0]->total ?? 0;
    }
    // Autres méthodes pour gérer les permissions...
}
