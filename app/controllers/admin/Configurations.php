<?php
class Configurations extends Controller
{
    public function __construct()
    {
        $this->requirePermission('utilisateur_apercu');
    }

    // public function index()
    // {
    //     $configuration = new Configuration();

    //     // Initialisation sécurisée
    //     $listes = [];
    //     // Vérifie si l'utilisateur est connecté
    //     if (isset($_SESSION['droit'])) {
    //         $role = $_SESSION['droit'];
    //         // SuperAdmin → affiche tous les utilisateurs
    //         if ($role === 'super_admin') {
    //             $listes = $configuration->SelectAllData(
    //                 '*',
    //                 'utilisateur 
    //              LEFT JOIN agence ON agence.idAgence = utilisateur.id_agence 
    //              LEFT JOIN compagnie ON compagnie.id_compagnie = agence.id_compagnie'
    //             );

    //             // Admin → affiche uniquement les utilisateurs de sa compagnie
    //         } elseif ($role === 'Admin' && isset($_SESSION['id_compagnie'])) {
    //             $id_compagnie = $_SESSION['id_compagnie'];

    //             $listes = $configuration->FetchSelectWheres(
    //                 '*',
    //                 'utilisateur 
    //              INNER JOIN agence ON agence.idAgence = utilisateur.id_agence 
    //              INNER JOIN compagnie ON compagnie.id_compagnie = agence.id_compagnie',
    //                 'agence.id_compagnie = :id_compagnie',
    //                 ['id_compagnie' => $id_compagnie]
    //             );
    //         } else {
    //             $configuration->set_flash("Accès restreint ou informations incomplètes.", "danger");
    //         }
    //     } else {
    //         $configuration->set_flash("Session expirée ou utilisateur non connecté.", "warning");
    //         $this->redirect("admin/Login/index");
    //         return;
    //     }


    //      if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['idUser'], $_POST['newStatut'])) {
    //         $id = (int)$_POST['idUser'];
    //         $status = (int)$_POST['newStatut'];

    //         $result = $configuration->insertion_update_simple("UPDATE utilisateur SET status = :status WHERE idUser = :id", [
    //             ":status" => $status,
    //             ":id" => $id
    //         ]);


    //     }

    //     // Appel de la vue
    //     $this->view('admin/configuration', ['liste' => $listes]);
    // }

    public function index()
    {
        $configuration = new Configuration();

        // Seuls super_admin et Admin gèrent les comptes utilisateurs. Sans ce garde-fou,
        // un simple compte "Utilisateur" connecté pouvait poster idUser=<son propre id>&droit=super_admin
        // sur ce même endpoint et s'auto-promouvoir (le code précédent ne faisait qu'un set_flash
        // sans exit avant de continuer vers les blocs POST plus bas).
        $role = $_SESSION['droit'] ?? null;
        if (!in_array($role, ['super_admin', 'Admin'], true)) {
            $configuration->set_flash("Accès restreint.", "danger");
            $this->redirect("admin/Homes/home");
            return;
        }

        $id_compagnie = $_SESSION['id_compagnie'] ?? null;

        // Un Admin ne peut agir que sur les utilisateurs de sa propre compagnie ; jamais
        // se/les promouvoir Admin, PDG ou super_admin (seul un super_admin peut attribuer ces droits).
        $droitsAutorises = $this->droitsAutorisesPour($role);

        // Suppression d'un compte : réservée au super_admin, confirmation façon GitHub
        // (il faut saisir l'email exact du compte ciblé, pas juste cliquer "Oui").
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deleteUtilisateur'])) {
            if ($role !== 'super_admin' || !csrf_verify()) {
                $configuration->set_flash("Action non autorisée.", "danger");
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit;
            }

            $idUserCible = (int)($_POST['idUser'] ?? 0);
            $cible = $configuration->getUserById($idUserCible);

            if (!$cible) {
                $configuration->set_flash("Utilisateur introuvable.", "danger");
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit;
            }

            if ($idUserCible === (int)($_SESSION['id_utilisateur'] ?? 0)) {
                $configuration->set_flash("Vous ne pouvez pas supprimer votre propre compte.", "danger");
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit;
            }

            $confirmation = trim($_POST['confirmation'] ?? '');
            if ($confirmation === '' || $confirmation !== $cible['emailUser']) {
                $configuration->set_flash("Confirmation incorrecte : l'email saisi ne correspond pas au compte.", "danger");
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit;
            }

            if ($configuration->deleteUtilisateur($idUserCible)) {
                $configuration->set_flash("Utilisateur supprimé avec succès.", "success");
            } else {
                $configuration->set_flash("Erreur lors de la suppression de l'utilisateur.", "danger");
            }
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        }

        // Gestion du POST : changement de statut
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['idUser'], $_POST['newStatut'])) {
            $this->requirePermission('utilisateur_active/desactive');
            $id = (int)$_POST['idUser'];
            $status = (int)$_POST['newStatut'];

            if ($role === 'Admin' && !$this->utilisateurAppartientCompagnie($configuration, $id, $id_compagnie)) {
                $configuration->set_flash("Action non autorisée.", "danger");
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit;
            }

            $result = $configuration->insertion_update_simple(
                "UPDATE utilisateur SET status = :status WHERE idUser = :id",
                [
                    ":status" => $status,
                    ":id" => $id
                ]
            );

            // Si la mise à jour est réussie, on recharge la page
            if ($result !== false) {
                $configuration->set_flash("Statut mis à jour avec succès.", "success");
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit;
            } else {
                $configuration->set_flash("Erreur lors de la mise à jour du statut.", "danger");
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editUtilisateur'])) {
            $this->requirePermission('utilisateur_modifier');
            $idUser = (int)$_POST['idUser'];
            $utilisateurs = $_POST['utilisateurs'];
            $emailUser = $_POST['emailUser'];
            $telephone = trim($_POST['telephone'] ?? '');
            $droit = $_POST['droit'];

            if (($role === 'Admin' && !$this->utilisateurAppartientCompagnie($configuration, $idUser, $id_compagnie))
                || !in_array($droit, $droitsAutorises, true)
                || ($telephone !== '' && !preg_match('/^[0-9+\s.\-]{6,20}$/', $telephone))
            ) {
                $configuration->set_flash("Action non autorisée.", "danger");
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit;
            }

            $profile = ($droit === 'Utilisateur') ? ($_POST['profile'] ?? null) : null;

            // Meme regle qu'a la creation (Configuration::saveUtilisateur()) : un chef d'escale
            // ou un simple Utilisateur doit etre rattache a une gare (ses operations -- billets,
            // colis, caisse -- sont scopees dessus) ; un Admin/PDG/secretaire, rattache a toute
            // la compagnie, n'en a pas. Le changement ne prend effet qu'a la prochaine connexion
            // de la personne : sa session actuelle garde l'ancienne gare jusqu'a reconnexion.
            $id_agence = !empty($_POST['id_agence']) ? (int)$_POST['id_agence'] : null;
            if (!in_array($droit, ['Admin', 'PDG', 'secretaire'], true)) {
                if (!$id_agence) {
                    $configuration->set_flash("La gare est obligatoire pour ce type de compte.", "danger");
                    header("Location: " . $_SERVER['REQUEST_URI']);
                    exit;
                }
                // IDOR : la gare postee doit appartenir a la meme compagnie que le compte cible
                // (celle de l'Admin connecte, ou celle du compte cible pour un super_admin).
                $id_compagnie_cible = $role === 'super_admin'
                    ? (int)($configuration->getUserById($idUser)['id_compagnie'] ?? 0)
                    : $id_compagnie;
                $gareValide = $configuration->fetchOne(
                    "SELECT idAgence FROM agence WHERE idAgence = :id AND id_compagnie = :ic",
                    [':id' => $id_agence, ':ic' => $id_compagnie_cible]
                );
                if (!$gareValide) {
                    $configuration->set_flash("Gare invalide pour la compagnie de ce compte.", "danger");
                    header("Location: " . $_SERVER['REQUEST_URI']);
                    exit;
                }
            } else {
                $id_agence = null;
            }

            $updateFields = "utilisateurs = :utilisateurs, emailUser = :emailUser, telephone = :telephone, droit = :droit, profile = :profile, id_agence = :id_agence";
            $params = [
                ":utilisateurs" => $utilisateurs,
                ":emailUser" => $emailUser,
                ":telephone" => $telephone !== '' ? $telephone : null,
                ":droit" => $droit,
                ":profile" => $profile,
                ":id_agence" => $id_agence,
                ":id" => $idUser
            ];

            if (!empty($_POST['motPasse'])) {
                $updateFields .= ", motPasse = :motPasse";
                $params[":motPasse"] = password_hash($_POST['motPasse'], PASSWORD_DEFAULT);
            }

            $result = $configuration->insertion_update_simple(
                "UPDATE utilisateur SET $updateFields WHERE idUser = :id",
                $params
            );

            if ($result !== false) {
                $configuration->set_flash("Utilisateur modifié avec succès.", "success");
            } else {
                $configuration->set_flash("Erreur lors de la modification de l'utilisateur.", "danger");
            }
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        }

        $userColumns = 'utilisateur.idUser, utilisateur.utilisateurs, utilisateur.emailUser, utilisateur.telephone, utilisateur.motPasse,
            utilisateur.droit, utilisateur.profile, utilisateur.status, utilisateur.photo, utilisateur.id_compagnie,
            utilisateur.id_agence, agence.numeroGare';

        // Les comptes super_admin n'apparaissent pas dans cette liste (ce rôle n'est ni
        // géré ni visible ici pour les autres), SAUF l'utilisateur connecté lui-même :
        // sans cette exception, un super_admin ne pouvait jamais se voir ni modifier ses
        // propres infos depuis cette page.
        $idUserConnecte = $_SESSION['id_utilisateur'];
        if ($role === 'super_admin') {
            $listes = $configuration->FetchSelectWheres(
                $userColumns,
                'utilisateur
            LEFT JOIN agence ON agence.idAgence = utilisateur.id_agence
            LEFT JOIN compagnie ON compagnie.id_compagnie = agence.id_compagnie',
                "(utilisateur.droit != 'super_admin' OR utilisateur.idUser = :self_id)",
                ['self_id' => $idUserConnecte]
            );
        } else {
            // LEFT JOIN (pas INNER) : un Admin ou un utilisateur sans agence assignee
            // (id_agence NULL -- cas normal pour un Admin qui gere toute la compagnie,
            // pas une gare precise) disparaissait entierement de la liste, lui y compris,
            // des qu'un INNER JOIN agence echouait a trouver une correspondance. Le filtre
            // par compagnie utilise utilisateur.id_compagnie directement (colonne existant
            // deja sur la table, cf. utilisateurAppartientCompagnie() plus bas) plutot que
            // de dependre de cette jointure.
            // Un Admin ne gere que ses subalternes depuis cette page (pas lui-meme : l'edition
            // de ses propres infos se fait via "Mon Profil") : sa propre ligne est exclue,
            // contrairement au super_admin ci-dessus qui doit se voir.
            $listes = $configuration->FetchSelectWheres(
                $userColumns,
                'utilisateur
            LEFT JOIN agence ON agence.idAgence = utilisateur.id_agence',
                "utilisateur.id_compagnie = :id_compagnie AND utilisateur.droit != 'super_admin' AND utilisateur.idUser != :self_id",
                ['id_compagnie' => $id_compagnie, 'self_id' => $idUserConnecte]
            );
        }

        // Gares disponibles pour le select "Gare" du modal de modification, regroupees par
        // compagnie : un Admin ne gere qu'une seule compagnie (la sienne), mais un super_admin
        // edite des comptes de plusieurs compagnies depuis cette meme liste -- il faut donc les
        // bonnes gares pour CHAQUE ligne, jamais un select unique qui melangerait tout.
        if ($role === 'super_admin') {
            $toutesGares = $configuration->SelectAllData('idAgence, localite, numeroGare, id_compagnie', 'agence');
        } else {
            $toutesGares = $configuration->FetchSelectWheres(
                'idAgence, localite, numeroGare, id_compagnie',
                'agence',
                'id_compagnie = :id_compagnie',
                ['id_compagnie' => $id_compagnie]
            );
        }
        $garesParCompagnie = [];
        foreach ($toutesGares as $gare) {
            $garesParCompagnie[(int)$gare->id_compagnie][] = $gare;
        }

        // Affichage de la vue
        $this->view('admin/configuration', ['liste' => $listes, 'garesParCompagnie' => $garesParCompagnie]);
    }

    // Vérifie que l'utilisateur ciblé appartient bien à la compagnie de l'Admin connecté
    // (les comptes super_admin/Admin utilisent utilisateur.id_compagnie directement,
    // cf. Configuration::saveUtilisateur()).
    private function utilisateurAppartientCompagnie(Configuration $configuration, $idUser, $id_compagnie)
    {
        $cible = $configuration->getUserById($idUser);
        return $cible && (int)($cible['id_compagnie'] ?? 0) === (int)$id_compagnie;
    }

    // Liste des droits qu'un rôle a le droit d'attribuer à un compte (création ou modification).
    // Un Admin ne peut jamais s'attribuer, ni attribuer à un autre compte, Admin/PDG/super_admin :
    // seul un super_admin distribue ces rôles (PDG supervise une compagnie, il ne doit pas
    // pouvoir être créé par celui qu'il supervise).
    private function droitsAutorisesPour($role)
    {
        return $role === 'super_admin'
            ? ['super_admin', 'Admin', 'PDG', 'Utilisateur', 'chef_d_escale', 'secretaire']
            : ['Utilisateur', 'chef_d_escale', 'secretaire'];
    }




    public function add_utilisateurs()
    {
        $this->requirePermission('utilisateur_creation');
        $configuration = new Configuration();

        // Récupérer les données des filières
        $listeCompagnie = $configuration->SelectAllData("*", "compagnie");

        if (isset($_POST["saveutilisateur"])) {
            // Sans ce contrôle, un Admin pouvait poster droit=super_admin (ou droit=PDG,
            // le rôle superviseur qui doit rester exclusif au super_admin) sur ce même
            // endpoint : saveUtilisateur() ne validait pas le droit demandé contre le rôle
            // du créateur (seul le <select> du formulaire le limitait, contournable via un POST direct).
            $droitsAutorises = $this->droitsAutorisesPour($_SESSION['droit'] ?? null);
            if (!in_array($_POST['droit'] ?? null, $droitsAutorises, true)) {
                $configuration->set_flash("Action non autorisée.", "danger");
            } else {
                $configuration->saveUtilisateur();
            }
        }

        $listeGares = [];

        if ($_SESSION['droit'] === 'Admin' && isset($_SESSION['id_compagnie'])) {
            $id_compagnie = $_SESSION['id_compagnie'];

            // Récupérer uniquement les gares liées à cette compagnie
            $listeGares = $configuration->FetchSelectWheres(
                '*',
                'agence',
                'id_compagnie = :id_compagnie',
                ['id_compagnie' => $id_compagnie]
            );
        } else {
            // SuperAdmin ou autres → voir toutes les gares
            $listeGares = $configuration->SelectAllData("*", "agence");
        }

        // Ensuite, envoie à la vue
        $this->view('admin/add_utilisateur', [
            'listeGares' => $listeGares,
            'listeCompagnie' => $listeCompagnie
        ]);
    }
}
