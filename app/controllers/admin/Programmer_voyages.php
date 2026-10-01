<?php
class Programmer_voyages extends  Controller
{
  public function __construct()
  {
    $this->requirePermission('Programme_Creation');
  }

  public  function  index()
  {
    $programmer_voyage = new Programmer_voyage();

    if (isset($_SESSION['droit'])) {
      $role = $_SESSION['droit'];
      if (in_array($role, ['Admin', 'PDG', 'secretaire'], true) || $role === 'chef_d_escale' && isset($_SESSION['id_compagnie'])) {
        // Admin : voit seulement ce qui est lié à sa compagnie
        $id_compagnie = $_SESSION['id_compagnie'];

       $listeProgrammer = $programmer_voyage->FetchSelectCustom("
    SELECT 
        p.idProgrammer,
        p.idDepart,
        a1.localite AS departLocalite,       -- Jointure pour départ
        a1.numeroGare AS numeroGare1,
        p.idDestination,
        a2.localite AS destinationLocalite, -- Jointure pour destination
        a2.numeroGare AS numeroGare2,
        p.heureDepart,
        p.rdv,
        p.prix,
        GROUP_CONCAT(CONCAT(e.escales, ' (', lt.prix_escale, ' FCFA)') SEPARATOR ', ') AS escales
    FROM programmer p
    LEFT JOIN ligneTrajet lt ON p.idProgrammer = lt.id_trajets AND lt.type_trajet = 'programmer'
    LEFT JOIN escale e ON lt.id_escales = e.id_escale
    LEFT JOIN agence a1 ON p.idDepart = a1.idAgence
    LEFT JOIN agence a2 ON p.idDestination = a2.idAgence
    WHERE p.id_compagnie = :id_compagnie
    GROUP BY p.idProgrammer
    ORDER BY a1.localite, a2.localite, p.heureDepart
", ['id_compagnie' => $id_compagnie]);

        // Détail des escales (id + nom + prix) de chaque programme, utilisé pour le formulaire de modification
        if ($listeProgrammer) {
          foreach ($listeProgrammer as $programme) {
            $programme->escalesDetails = $programmer_voyage->FetchSelectCustom(
              "SELECT e.id_escale, e.escales, lt.prix_escale
               FROM ligneTrajet lt
               INNER JOIN escale e ON lt.id_escales = e.id_escale
               WHERE lt.id_trajets = :id AND lt.type_trajet = 'programmer'",
              [':id' => $programme->idProgrammer]
            );
          }

          // Un même trajet (départ/destination/heure) ne devrait jamais apparaître deux
          // fois : ça ne peut plus arriver depuis la création/modification (contrôles
          // ajoutés), mais on le signale si ça existe encore dans des données plus
          // anciennes, plutôt que de le laisser passer inaperçu dans la liste.
          $occurrences = [];
          foreach ($listeProgrammer as $programme) {
            $cle = $programme->idDepart . '|' . $programme->idDestination . '|' . $programme->heureDepart;
            $occurrences[$cle] = ($occurrences[$cle] ?? 0) + 1;
          }
          foreach ($listeProgrammer as $programme) {
            $cle = $programme->idDepart . '|' . $programme->idDestination . '|' . $programme->heureDepart;
            $programme->estDoublon = $occurrences[$cle] > 1;
          }
        }
      } else {
        $programmer_voyage->set_flash("Accès refusé ou session invalide", "danger");
        $programmer_voyage->redirect("/admin/Login/index");
        return;
      }
    } else {
      $programmer_voyage->set_flash("Vous devez vous connecter", "warning");
      $programmer_voyage->redirect("/admin/Login/index");
      return;
    }

    // Horaires de la compagnie, pour permettre de changer l'heure de départ à la modification
    // (l'heure de départ de Segou n'est pas la même que celle de Bamako par exemple).
    $listehoraire = $programmer_voyage->FetchSelectWheres(
      "*",
      "horaire",
      "id_compagnie = :id_compagnie",
      [":id_compagnie" => $_SESSION['id_compagnie']]
    );

    $this->view('admin/programmer_voyage', [
      'listeProgrammer' => $listeProgrammer,
      'listehoraire' => $listehoraire
    ]);
  }



  // enregistrement du programmer de voyage
  public function add_programmer()
  {
    // instanciation 
    $add_liste_trajet = new Add_liste_trajet();
    $programmer_voyage = new Programmer_voyage();

    if (isset($_POST['enregistre'])) {
      $programmer_voyage->saveProgrammer();
    }
    $id_compagnie = $_SESSION['id_compagnie'];
    $liste_agence = [];
    $listeEscale = [];
    $listehoraire = [];
    $listehoraire = $programmer_voyage->FetchSelectWhereS(
      "*",
      "horaire",
      "id_compagnie = :id_compagnie",
      [":id_compagnie" => $id_compagnie]
    );
    if (isset($_SESSION['droit'])) {
      $role = $_SESSION['droit'];

      if ($role === 'super_admin') {
        // SuperAdmin : voit tout

        $liste_agence = $add_liste_trajet->SelectAllData('*', "agence");
        $liste_agence_depart = $liste_agence;
        $listeEscale = $add_liste_trajet->SelectAllData("*", "escale");
      } elseif (in_array($role, ['Admin', 'PDG', 'secretaire'], true) || $role === 'chef_d_escale' && isset($_SESSION['id_compagnie'])) {
        // Admin : voit seulement ce qui est lié à sa compagnie
        $id_compagnie = $_SESSION['id_compagnie'];

        // Agences liées à cette compagnie (sert de liste pour la destination)
        $liste_agence = $add_liste_trajet->FetchSelectWheres(
          '*',
          'agence',
          'id_compagnie = :id_compagnie',
          ['id_compagnie' => $id_compagnie]
        );

        // Le chef d'escale ne peut créer un programme qu'au départ de sa propre gare ;
        // l'Admin, lui, peut choisir n'importe quelle gare de sa compagnie comme départ.
        if ($role === 'chef_d_escale') {
          $liste_agence_depart = $add_liste_trajet->FetchSelectWheres(
            '*',
            'agence',
            'idAgence = :id_agence',
            ['id_agence' => $_SESSION['id_agence']]
          );
        } else {
          $liste_agence_depart = $liste_agence;
        }

        // Escales liées à cette compagnie
        $listeEscale = $add_liste_trajet->FetchSelectWheres(
          '*',
          'escale',
          'id_compagnie = :id_compagnie',
          ['id_compagnie' => $id_compagnie]
        );
      } else {
        $add_liste_trajet->set_flash("Accès refusé ou session invalide", "danger");
        $add_liste_trajet->redirect("admin/Login/index");
        return;
      }
    } else {
      $add_liste_trajet->set_flash("Vous devez vous connecter", "warning");
      $add_liste_trajet->redirect("/admin/Login/index");
      return;
    }

    // Tous les trajets deja programmes (toutes gares confondues) : affiches en reference
    // a cote du choix de la Destination, pour voir d'un coup d'oeil ce qui existe deja
    // avant d'en creer un nouveau (evite les doublons, aide a fixer un prix coherent).
    $tousLesTrajets = $programmer_voyage->FetchSelectCustom(
      "SELECT a1.localite AS departLocalite, a1.numeroGare AS departNumeroGare,
              a2.localite AS destinationLocalite, a2.numeroGare AS destinationNumeroGare,
              p.heureDepart, p.prix
       FROM programmer p
       INNER JOIN agence a1 ON p.idDepart = a1.idAgence
       INNER JOIN agence a2 ON p.idDestination = a2.idAgence
       WHERE p.id_compagnie = :id_compagnie
       ORDER BY a1.localite, a2.localite, p.heureDepart",
      ['id_compagnie' => $id_compagnie]
    );

    // Envoi des données à la vue
    $this->view('admin/add_programmer', [

      'listeEscale' => $listeEscale,
      'listehoraire' => $listehoraire,
      'liste_agence' => $liste_agence,
      'liste_agence_depart' => $liste_agence_depart,
      'tousLesTrajets' => $tousLesTrajets
    ]);
  }

  public function edit()
  {
    $programmer_voyage = new Programmer_voyage();

    // index() et add_programmer() restreignent déjà cette section à Admin/chef_d_escale :
    // edit() (modification du prix d'un trajet programmé) doit avoir le même contrôle.
    if (!in_array($_SESSION['droit'] ?? null, ['Admin', 'chef_d_escale', 'secretaire'], true)) {
      $programmer_voyage->set_flash("Accès refusé ou session invalide", "danger");
      header("Location: " . BASE_URL . "/admin/Programmer_voyages/index");
      exit;
    }

    if (isset($_POST['edit'])) {
      $data = [
        "prix" => $_POST['prix'],
        "idProgrammer" => $_POST['idProgrammer']
      ];

      $programmer_voyage->editPrix($data);

      if (!empty($_POST['heureDepart']) && !empty($_POST['rdv'])) {
        $resultatHoraire = $programmer_voyage->editHoraire($_POST['idProgrammer'], $_POST['heureDepart'], $_POST['rdv']);
        if ($resultatHoraire === 'doublon') {
          $programmer_voyage->set_flash(
            "Impossible de changer l'heure : ce trajet existe déjà à cette heure-là. Modifiez plutôt ce trajet existant, ou choisissez une autre heure.",
            "danger"
          );
          header("Location: " . BASE_URL . "/admin/Programmer_voyages/index");
          exit;
        }
        if ($resultatHoraire === 'billets_existants') {
          $programmer_voyage->set_flash(
            "Impossible de changer l'heure : des billets ou une programmation active utilisent déjà l'heure actuelle de ce trajet. " .
            "Pour un changement ponctuel (un seul jour), utilisez plutôt \"Modifier la programmation\" depuis l'écran de programmation du voyage.",
            "danger"
          );
          header("Location: " . BASE_URL . "/admin/Programmer_voyages/index");
          exit;
        }
      }

      if (!empty($_POST['prix_escale']) && is_array($_POST['prix_escale'])) {
        $programmer_voyage->editPrixEscales($_POST['idProgrammer'], $_POST['prix_escale']);
      }

      header("Location: " . BASE_URL . "/admin/Programmer_voyages/index");
      exit;
    }
  }

  public function delete($id)
  {
    $programmer_voyage = new Programmer_voyage();

    if (!in_array($_SESSION['droit'] ?? null, ['Admin', 'chef_d_escale', 'secretaire'], true)) {
      $programmer_voyage->set_flash("Accès refusé ou session invalide", "danger");
      header("Location: " . BASE_URL . "/admin/Programmer_voyages/index");
      exit;
    }

    if (!empty($id)) {
      if ($programmer_voyage->deleteProgrammer($id)) {
        $programmer_voyage->set_flash("Le programme a été supprimé avec succès.", "success");
      } else {
        $programmer_voyage->set_flash("Erreur lors de la suppression du programme.", "danger");
      }
    }

    header("Location: " . BASE_URL . "/admin/Programmer_voyages/index");
    exit;
  }
}
