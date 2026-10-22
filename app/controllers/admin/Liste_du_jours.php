<?php

use Dompdf\Dompdf;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;                 // ← énumération 5.x
use Endroid\QrCode\RoundBlockSizeMode;

class Liste_du_jours extends  Controller
{
  public function __construct()
  {
    $this->requirePermission('Billets_apercue');
  }


  public function index()
  {
    date_default_timezone_set('Africa/Bamako');
    $id_compagnie = $_SESSION['id_compagnie'];
    // Admin n'a pas de gare fixe en session : il voit les billets de toute la compagnie.
    // Pour les autres rôles, numeroGare précise la gare exacte : une ville peut avoir
    // plusieurs gares (ex. "Segou" Gare I et Gare II), le nom de ville seul ne suffit pas.
    $isAdmin = in_array($_SESSION['droit'] ?? null, ['Admin', 'PDG', 'secretaire'], true);
    $idDepart = $isAdmin ? null : ($_SESSION['ville'] ?? null);
    $numeroGare = $isAdmin ? null : ($_SESSION['numero_gare'] ?? null);
    $model = new Liste_du_jour();

    $resultats = $model->listeBillets($idDepart, $numeroGare);
    $liste_horaires = $model->FetchSelectWheres(
      '*',
      'horaire',
      'id_compagnie = :id_compagnie',
      ['id_compagnie' => $id_compagnie]
    );
    $destinations = $model->getDestinations($idDepart, $id_compagnie, $numeroGare);

    $this->view('admin/liste_du_jours', [
      'liste_du_jour' => $resultats,
      'liste_horaires' => $liste_horaires,
      'destinations' => $destinations
    ]);
  }

  // Historique des billets : consulter et imprimer/reimprimer les billets d'une date
  // passee precise (contrairement a index()/Liste_de_demains, qui ne montrent que les
  // billets du jour/du lendemain). Le recu (recu()/donneesTicketThermique()) fonctionne
  // deja pour n'importe quel billet peu importe sa date, seule cette liste manquait.
  public function historique()
  {
    date_default_timezone_set('Africa/Bamako');
    $id_compagnie = $_SESSION['id_compagnie'];
    $isAdmin = in_array($_SESSION['droit'] ?? null, ['Admin', 'PDG', 'secretaire'], true);
    $idDepart = $isAdmin ? null : ($_SESSION['ville'] ?? null);
    $numeroGare = $isAdmin ? null : ($_SESSION['numero_gare'] ?? null);
    $model = new Liste_du_jour();

    $date = $_GET['date'] ?? date('Y-m-d', strtotime('-1 day'));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || $date > date('Y-m-d')) {
      $date = date('Y-m-d', strtotime('-1 day'));
    }

    $where = 'billets.id_compagnie = :id_compagnie AND billets.jourVoyage = :jour';
    $params = ['id_compagnie' => $id_compagnie, 'jour' => $date];

    if ($idDepart !== null) {
      $where .= ' AND billets.departId = :depart';
      $params['depart'] = $idDepart;
    }
    if ($numeroGare !== null) {
      $where .= ' AND billets.num_gare = :numeroGare';
      $params['numeroGare'] = $numeroGare;
    }
    if (!empty($_GET['destination'])) {
      $where .= ' AND billets.destinationId = :destination';
      $params['destination'] = $_GET['destination'];
    }
    if (!empty($_GET['heure'])) {
      $where .= ' AND billets.Heur_departs = :heure';
      $params['heure'] = $_GET['heure'];
    }

    $resultats = $model->FetchSelectWheres(
      '*',
      'billets inner join client on billets.id_client = client.idClient',
      $where . ' ORDER BY billets.Heur_departs',
      $params
    );

    $liste_horaires = $model->FetchSelectWheres(
      '*',
      'horaire',
      'id_compagnie = :id_compagnie',
      ['id_compagnie' => $id_compagnie]
    );
    $destinations = $model->getDestinations($idDepart, $id_compagnie, $numeroGare);

    $this->view('admin/historique_billets', [
      'liste_historique' => $resultats,
      'liste_horaires' => $liste_horaires,
      'destinations' => $destinations,
      'date' => $date,
    ]);
  }

  // public function recu($idBillets)
  // {
  //   $colisModel = new Livraisons_colis();
  //   $billets = new Liste_du_jour();

  //   $Billets = $billets->listeBillets();
  //   $compagnie = $colisModel->infoCompagnie($_SESSION['id_compagnie'] ?? 0);

  //   if (!$Billets || !$compagnie) {
  //     $colisModel->set_flash('bi ou compagnie introuvable.', 'danger');
  //     header('Location: ' . BASE_URL . '/livraison_colis');
  //     exit;
  //   }

  //   // Préparation du chemin vers le logo pour Dompdf
  //   $logoPath = null;
  //   if (!empty($compagnie['logo'])) {
  //     $logoPath = '/public/images/logos/' . $compagnie['logo']; // relatif à ROOT (chroot)
  //   }

  //   /* ---------- 3. Construction HTML ---------- */
  //   ob_start();
  //   include ROOT . '/app/views/pdf/ticket.php'; // la vue utilise $colis, $compagnie, $qrPath, $logoPath
  //   $html = ob_get_clean();

  //   /* ---------- 4. Génération PDF avec Dompdf ---------- */
  //   $opt = new \Dompdf\Options();
  //   $opt->setChroot(ROOT);
  //   $opt->setIsRemoteEnabled(true);

  //   $dompdf = new \Dompdf\Dompdf($opt);
  //   $dompdf->loadHtml($html);
  //   $dompdf->setPaper('A6', 'portrait'); // Taille réduite du reçu
  //   $dompdf->render();



  //   // Affichage dans le navigateur
  //   $dompdf->stream("ticket{$Billets['id_colis']}.pdf", ['Attachment' => false]);
  //   exit;
  // }

  public function recu($idBillets)
  {
    $this->requirePermission('Billets_impression');
    $colisModel = new Livraisons_colis();
    $billets = new Liste_du_jour();

    $billet = $billets->getBilletById($idBillets); // ✅ récupère un billet spécifique
    $compagnie = $colisModel->infoCompagnie($_SESSION['id_compagnie'] ?? 0);
    $bagages = (new Bagage())->getBagagesPourBillet($idBillets);

    if (!$billet || !$compagnie) {
      $colisModel->set_flash('Billet ou compagnie introuvable.', 'danger');
      header('Location: ' . BASE_URL . '/admin/livraison_colis');
      exit;
    }

    // Chemin vers le logo
    $logoPath = null;
    if (!empty($compagnie['logo'])) {
      $logoPath = ROOT . '/public/images/logos/' . $compagnie['logo'];
    }

    // Construction HTML
    ob_start();
    include ROOT . '/app/views/admin/pdf/ticket.php';
    $html = ob_get_clean();

    // Génération PDF (imprimante thermique 80mm)
    $this->streamThermalPdf($html, "ticket_{$idBillets}.pdf");
  }

  // Renvoie les données du billet en JSON pour impression thermique ESC/POS.
  //
  // Le site est hébergé sur un serveur distant (LWS) : il ne peut pas atteindre
  // l'imprimante du comptoir (IP locale type 192.168.1.x, ou USB branchée sur le poste).
  // Le navigateur récupère donc les données ici, puis les transmet en JS au pont
  // d'impression local (local-print-bridge/), qui tourne sur le poste du comptoir
  // et qui seul peut réellement parler à l'imprimante (cf. ThermalPrinter::printBillet()).
  public function donneesTicketThermique($idBillets)
  {
    $this->requirePermission('Billets_impression');
    // Tampon dédié : si un warning/notice PHP s'imprime avant le JSON (APP_ENV=local
    // affiche les erreurs à l'écran), il casserait le parsing JSON côté navigateur sans
    // qu'on comprenne pourquoi. On l'intercepte ici et on ne garde que le JSON final.
    ob_start();

    $colisModel = new Livraisons_colis();
    $billets = new Liste_du_jour();

    $billet = $billets->getBilletById($idBillets);
    $compagnie = $colisModel->infoCompagnie($_SESSION['id_compagnie'] ?? 0);
    $bagageModel = new Bagage();
    $bagages = $billet ? $bagageModel->getBagagesPourBillet($idBillets) : [];

    ob_clean();
    header('Content-Type: application/json; charset=utf-8');

    if (!$billet || !$compagnie) {
      echo json_encode(['error' => 'Billet ou compagnie introuvable.']);
      exit;
    }

    $heureTs = !empty($billet->Heur_departs) ? strtotime($billet->Heur_departs) : false;
    $montantNet = preg_replace('/[^\d.]/', '', $billet->montant_payer ?? '');

    // Logo envoye en base64 : le pont d'impression tourne sur le poste du comptoir et n'a
    // aucun acces a la base de donnees ni aux fichiers du site (cf. bridge.php), donc c'est
    // au site d'envoyer l'image elle-meme pour que le ticket WiFi reprenne exactement la
    // meme presentation que le ticket imprime en cable/USB (qui, lui, lit le fichier
    // directement puisqu'il tourne cote serveur).
    $logoBase64 = null;
    if (!empty($compagnie['logo'])) {
      $logoPath = ROOT . '/public/images/logos/' . $compagnie['logo'];
      if (file_exists($logoPath)) {
        $logoBase64 = base64_encode(file_get_contents($logoPath));
      }
    }

    $json = json_encode([
      'compagnie' => $compagnie['nom'] ?? 'Nom Compagnie',
      'slogan' => $compagnie['slogant'] ?? '',
      'logo' => $logoBase64,
      'numero' => $billet->numeroBillets ?? '-',
      'client' => $billet->Client ?? '-',
      'date' => !empty($billet->jourVoyage) ? date('d/m/Y', strtotime($billet->jourVoyage)) : '-',
      'depart' => $_SESSION['ville'] ?? '-',
      'heure' => $heureTs !== false ? date('H\hi', $heureTs) : (string)($billet->Heur_departs ?? '-'),
      'destination' => $billet->destinationId ?? '-',
      'places' => $billet->numeroPlace ?? '-',
      'montant' => !empty($montantNet) ? number_format((float)$montantNet, 0, ',', ' ') : '-',
      'emisPar' => $billet->utilisateurs ?? '-',
      // Bagages payants (optionnel, cf. app/models/Bagage.php) : rendus par le pont
      // d'impression local (local-print-bridge/ThermalPrinter.php::buildBilletContent()).
      'bagages' => array_map(fn($b) => [
        'description' => $b['description'] ?? '-',
        'prix' => number_format((float)$b['prix'], 0, ',', ' '),
      ], $bagages),
      'totalBagages' => array_sum(array_column($bagages, 'prix')) > 0
        ? number_format((float)array_sum(array_column($bagages, 'prix')), 0, ',', ' ')
        : null,
    ]);

    // json_encode() renvoie false (donc une réponse vide, invalide en JSON) en cas de
    // caractères mal encodés dans les données — on l'expose ici plutôt que de laisser
    // le navigateur échouer sur une réponse vide sans explication.
    echo $json !== false ? $json : json_encode(['error' => 'Erreur d\'encodage des données : ' . json_last_error_msg()]);
    exit;
  }

  public function imprimerListe()
  {
    $this->requirePermission('Billets_impression');
    date_default_timezone_set('Africa/Bamako');
    $id_compagnie = $_SESSION['id_compagnie'];
    // Admin n'a pas de gare fixe en session : il voit les billets de toute la compagnie.
    $isAdmin      = in_array($_SESSION['droit'] ?? null, ['Admin', 'PDG', 'secretaire'], true);
    $idDepart     = $isAdmin ? null : ($_SESSION['ville'] ?? null);
    $numeroGare   = $isAdmin ? null : ($_SESSION['numero_gare'] ?? null);
    $destination  = trim($_GET['destination'] ?? '');
    $heure        = trim($_GET['heure'] ?? '');
    // Accepte une date passee (depuis l'onglet Historique) : sans ce parametre, le
    // comportement reste identique a avant (aujourd'hui), utilise par Liste du jour.
    $aujourdhui   = trim($_GET['date'] ?? '');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $aujourdhui) || $aujourdhui > date('Y-m-d')) {
      $aujourdhui = date('Y-m-d');
    }

    $model = new Liste_du_jour();
    $colisModel = new Livraisons_colis();

    $where = 'billets.id_compagnie = :id_compagnie AND billets.jourVoyage = :jour';
    $params = [
      'id_compagnie' => $id_compagnie,
      'jour'         => $aujourdhui
    ];

    if ($idDepart !== null) {
      $where .= ' AND billets.departId = :depart';
      $params['depart'] = $idDepart;
    }

    if ($numeroGare !== null) {
      $where .= ' AND billets.num_gare = :numeroGare';
      $params['numeroGare'] = $numeroGare;
    }

    if ($destination !== '') {
      $where .= ' AND billets.destinationId = :destination';
      $params['destination'] = $destination;
    }
    if ($heure !== '') {
      $where .= ' AND billets.Heur_departs = :heure';
      $params['heure'] = $heure;
    }

    $billets = $model->FetchSelectWheres(
      '*',
      'billets INNER JOIN client ON billets.id_client = client.idClient',
      $where,
      $params
    );

    $compagnie = $colisModel->infoCompagnie($id_compagnie);

    $logoPath = null;
    if (!empty($compagnie['logo'])) {
      $logoPath = ROOT . '/public/images/logos/' . $compagnie['logo'];
    }

    ob_start();
    include ROOT . '/app/views/admin/pdf/liste_embarquement.php';
    $html = ob_get_clean();

    $opt = new \Dompdf\Options();
    $opt->setChroot(ROOT);
    $opt->setIsRemoteEnabled(true);

    $dompdf = new \Dompdf\Dompdf($opt);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $dompdf->stream('liste_embarquement_' . date('Ymd_His') . '.pdf', ['Attachment' => false]);
    exit;
  }

  public function getHeuresDisponibles()
  {
    $destinationId = $_POST['destination_id'];
    // Admin n'a pas de gare fixe en session : il voit les heures de toute la compagnie.
    $isAdmin = in_array($_SESSION['droit'] ?? null, ['Admin', 'PDG', 'secretaire'], true);
    $villeDepart = $isAdmin ? null : ($_SESSION['ville'] ?? null);
    $numeroGare  = $isAdmin ? null : ($_SESSION['numero_gare'] ?? null);
    $billets = new Liste_du_jour();

    $heures = $billets->getHeures($destinationId, $villeDepart, $numeroGare);

    echo json_encode($heures);
  }

  public function reporter()
  {
    $this->requirePermission('Billets_reporte');
    $billets = new Liste_du_jour();

    if (isset($_POST['edit'])) {
      if (!csrf_verify()) {
        $billets->set_flash("Session expirée, veuillez réessayer.", "danger");
        header("Location: " . BASE_URL . "/admin/Liste_du_jours/index");
        exit;
      }

      date_default_timezone_set('Africa/Bamako');
      $idBillets = $_POST['idClient'];
      $billetActuel = $billets->getBilletById($idBillets);

      if (!$billetActuel) {
        $billets->set_flash("Billet introuvable.", "danger");
        header("Location: " . BASE_URL . "/admin/Liste_du_jours/index");
        exit;
      }

      // Le client peut avoir raté son départ initial : on autorise quand même le report
      // (l'agent est prévenu côté interface), tant que le billet n'est pas expiré et que
      // la nouvelle date n'est pas elle-même dans le passé.

      // Sans heure de départ valide, l'UPDATE plante (colonne TIME "" invalide en SQL strict).
      if (empty($_POST['nouvelle_date']) || empty($_POST['heure_depart'])) {
        $billets->set_flash("Veuillez choisir une nouvelle date et une heure de départ valides.", "danger");
        header("Location: " . BASE_URL . "/admin/Liste_du_jours/index");
        exit;
      }

      // Le billet a une date de validité : au-delà, il ne peut plus être reporté.
      $expiration = strtotime($billetActuel->date_expiration . ' 23:59:59');
      if ($expiration !== false && $expiration < time()) {
        $billets->set_flash("Ce billet a expiré, impossible de le reporter.", "danger");
        header("Location: " . BASE_URL . "/admin/Liste_du_jours/index");
        exit;
      }

      // Un report ne peut viser qu'aujourd'hui ou demain, exactement comme une vente initiale
      // (Add_billet::saveBillets) : au-delà, reporte_voyage() ne sait pas réserver de place
      // (ni sur un car programmé, ni dans "suivis"), ce qui rendrait le billet invisible pour
      // le contrôle de capacité au jour venu.
      $nouveauJour = date('Y-m-d', strtotime($_POST['nouvelle_date']));
      $aujourdhui  = date('Y-m-d');
      $maxJour     = date('Y-m-d', strtotime('+6 days'));
      if ($nouveauJour < $aujourdhui || $nouveauJour > $maxJour) {
        $billets->set_flash("Le report n'est possible que vers une date entre aujourd'hui et dans 6 jours.", "danger");
        header("Location: " . BASE_URL . "/admin/Liste_du_jours/index");
        exit;
      }

      // La nouvelle date/heure de départ ne peut pas être dans le passé.
      $nouveauDepart = strtotime($_POST['nouvelle_date'] . ' ' . $_POST['heure_depart']);
      if ($nouveauDepart === false || $nouveauDepart <= time()) {
        $billets->set_flash("La nouvelle date/heure de départ doit être dans le futur.", "danger");
        header("Location: " . BASE_URL . "/admin/Liste_du_jours/index");
        exit;
      }

      $data = [
        "jourVoyage" => $_POST['nouvelle_date'],
        "Heur_departs" => $_POST['heure_depart'],
        "idBillets" => $idBillets,
      ];

      $billets->reporte_voyage($data);
      header("Location: " . BASE_URL . "/admin/Liste_du_jours/index");
      exit;
    }
  }

  // Un simple Utilisateur ne peut pas annuler un billet. Un chef d'escale ne peut que
  // DEMANDER l'annulation (aucune place/caisse touchée avant confirmation). Seul un Admin
  // (ou super_admin) peut annuler directement — c'est déjà l'équivalent d'une confirmation.
  public function annuler()
  {
    $this->requirePermission('Billets_annulation');
    if (isset($_POST['annuler_billet'])) {
      $billets = new Liste_du_jour();
      $idBillets = $_POST['idBillets'] ?? null;
      $motif = trim($_POST['motif_annulation'] ?? '');
      $droit = $_SESSION['droit'] ?? null;

      if ($idBillets && in_array($droit, ['Admin', 'super_admin'], true)) {
        $billets->confirmerAnnulationBillet($idBillets, $motif);
      } elseif ($idBillets && $droit === 'chef_d_escale') {
        $billets->demanderAnnulationBillet($idBillets, $motif);
      } else {
        $billets->set_flash("Vous n'avez pas le droit d'annuler un billet.", "danger");
      }
    }

    header("Location: " . BASE_URL . "/admin/Liste_du_jours/index");
    exit;
  }

  // Admin/super_admin : demandes d'annulation en attente, pour toute la compagnie.
  public function demandesAnnulation()
  {
    $this->requirePermission('Billets_annulation');
    $billets = new Liste_du_jour();

    if (!in_array($_SESSION['droit'] ?? null, ['Admin', 'super_admin', 'PDG'], true)) {
      $billets->set_flash("Accès refusé.", "danger");
      header("Location: " . BASE_URL . "/admin/Homes/home");
      exit;
    }

    $this->view('admin/demandes_annulation_billet', [
      'listeDemandes' => $billets->getDemandesAnnulationEnAttente($_SESSION['id_compagnie'])
    ]);
  }

  public function confirmerAnnulation($idBillets)
  {
    $this->requirePermission('Billets_annulation');
    $billets = new Liste_du_jour();
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_SESSION['droit'] ?? null, ['Admin', 'super_admin'], true)) {
      $billets->confirmerAnnulationBillet($idBillets);
    }
    header("Location: " . BASE_URL . "/admin/Liste_du_jours/demandesAnnulation");
    exit;
  }

  public function rejeterAnnulation($idBillets)
  {
    $this->requirePermission('Billets_annulation');
    $billets = new Liste_du_jour();
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_SESSION['droit'] ?? null, ['Admin', 'super_admin'], true)) {
      $billets->rejeterAnnulationBillet($idBillets);
    }
    header("Location: " . BASE_URL . "/admin/Liste_du_jours/demandesAnnulation");
    exit;
  }

  // ─────────────────────────────────────────────────────────────────────────
  // EMBARQUEMENT
  // ─────────────────────────────────────────────────────────────────────────

  public function embarquement()
  {
    $this->requirePermission('Billets_embarquement');
    date_default_timezone_set('Africa/Bamako');
    $id_compagnie = $_SESSION['id_compagnie'];
    $isAdmin = in_array($_SESSION['droit'] ?? null, ['Admin', 'PDG', 'secretaire'], true);
    $idDepart = $isAdmin ? null : ($_SESSION['ville'] ?? null);
    $numeroGare = $isAdmin ? null : ($_SESSION['numero_gare'] ?? null);
    $model = new Liste_du_jour();

    $jour = $_GET['date'] ?? date('Y-m-d');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $jour)) {
      $jour = date('Y-m-d');
    }
    $destination = trim($_GET['destination'] ?? '');
    $heure = trim($_GET['heure'] ?? '');

    $liste = $model->getBilletsPourEmbarquement($idDepart, $numeroGare, $jour, $destination, $heure);
    $carsComplets = $model->getCarsComplets($idDepart, $numeroGare, $id_compagnie, $jour);
    $carsDuJour = $model->getCarsDuJourPourEmbarquement($idDepart, $numeroGare, $id_compagnie, $jour);

    $liste_horaires = $model->FetchSelectWheres('*', 'horaire', 'id_compagnie = :id_compagnie', ['id_compagnie' => $id_compagnie]);
    $destinations = $model->getDestinations($idDepart, $id_compagnie, $numeroGare);

    $this->view('admin/embarquement', [
      'liste' => $liste,
      'liste_horaires' => $liste_horaires,
      'destinations' => $destinations,
      'date' => $jour,
      'carsComplets' => $carsComplets,
      'carsDuJour' => $carsDuJour,
    ]);
  }

  // Marque le depart reel d'un car (AJAX depuis l'ecran Embarquement) : voir
  // Programmation_voyage::decollerCar() pour les regles (bloque tant que des passagers
  // ne sont pas traites, desactive ensuite Embarquer/Annuler pour ce trajet).
  public function decollerCar()
  {
    $this->requirePermission('Billets_embarquement');
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $ok = (new Programmation_voyage())->decollerCar($_POST['idProgrammation'] ?? null, $_SESSION['id_compagnie']);
      if ($this->estAjax()) {
        $message = $_SESSION['notification']['message'] ?? ($ok ? 'Bus décollé.' : 'Erreur.');
        unset($_SESSION['notification']);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => (bool)$ok, 'message' => $message]);
        exit;
      }
    }
    header("Location: " . BASE_URL . "/admin/Liste_du_jours/embarquement");
    exit;
  }

  // Repond en JSON si la requete vient du fetch() de embarquement.view.php (AJAX), sinon
  // conserve l'ancien comportement (redirection + flash) pour rester utilisable sans JS.
  private function estAjax()
  {
    return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
  }

  public function marquerEmbarque()
  {
    $this->requirePermission('Billets_embarquement');
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $ok = (new Liste_du_jour())->marquerEmbarque($_POST['idBillets'] ?? null);
      if ($this->estAjax()) {
        $message = $_SESSION['notification']['message'] ?? ($ok ? 'Client embarqué.' : 'Erreur.');
        unset($_SESSION['notification']);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => (bool)$ok, 'message' => $message]);
        exit;
      }
    }
    header("Location: " . BASE_URL . "/admin/Liste_du_jours/embarquement");
    exit;
  }

  public function annulerEmbarquement()
  {
    $this->requirePermission('Billets_embarquement');
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $ok = (new Liste_du_jour())->annulerEmbarquementBillet($_POST['idBillets'] ?? null);
      if ($this->estAjax()) {
        $message = $_SESSION['notification']['message'] ?? ($ok ? 'Embarquement annulé.' : 'Erreur.');
        unset($_SESSION['notification']);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => (bool)$ok, 'message' => $message]);
        exit;
      }
    }
    header("Location: " . BASE_URL . "/admin/Liste_du_jours/embarquement");
    exit;
  }

  // Embarquement en masse : case a cocher + bouton "Embarquer la selection" (AJAX uniquement).
  public function marquerEmbarqueLot()
  {
    $this->requirePermission('Billets_embarquement');
    header('Content-Type: application/json; charset=utf-8');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify()) {
      echo json_encode(['ok' => false, 'message' => 'Session expirée, veuillez réessayer.']);
      exit;
    }

    $ids = $_POST['idsBillets'] ?? [];
    if (!is_array($ids) || empty($ids)) {
      echo json_encode(['ok' => false, 'message' => 'Aucun billet sélectionné.']);
      exit;
    }

    $resultat = (new Liste_du_jour())->marquerEmbarqueLot($ids);
    unset($_SESSION['notification']);
    echo json_encode(array_merge(['ok' => true], $resultat));
    exit;
  }

  // Client non embarqué : l'agent propose une nouvelle date/heure, soumise à validation Admin.
  public function demanderReport()
  {
    $this->requirePermission('Billets_embarquement');
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      (new Liste_du_jour())->demanderReportBillet(
        $_POST['idBillets'] ?? null,
        $_POST['nouvelle_date'] ?? '',
        $_POST['nouvelle_heure'] ?? ''
      );
    }
    header("Location: " . BASE_URL . "/admin/Liste_du_jours/embarquement");
    exit;
  }

  // Ecran unique, contenu différent selon le rôle : le chef d'escale voit les demandes de
  // sa propre gare en attente de SON examen (étape 1) ; l'Admin/super_admin voit celles
  // déjà transmises par un chef d'escale, en attente de validation finale (étape 2).
  public function demandesReport()
  {
    $this->requirePermission('Billets_annulation');
    $billets = new Liste_du_jour();
    $droit = $_SESSION['droit'] ?? null;

    if (in_array($droit, ['Admin', 'super_admin', 'PDG'], true)) {
      $this->view('admin/demandes_report_billet', [
        'listeDemandes' => $billets->getDemandesReportEnAttente($_SESSION['id_compagnie']),
        'estAdmin' => true,
      ]);
      return;
    }

    if ($droit === 'chef_d_escale') {
      $this->view('admin/demandes_report_billet', [
        'listeDemandes' => $billets->getDemandesReportEnAttenteChef(
          $_SESSION['id_compagnie'],
          $_SESSION['ville'] ?? null,
          $_SESSION['numero_gare'] ?? null
        ),
        'estAdmin' => false,
      ]);
      return;
    }

    $billets->set_flash("Accès refusé.", "danger");
    header("Location: " . BASE_URL . "/admin/Homes/home");
    exit;
  }

  // Etape 1 -> 2 (chef d'escale uniquement) : transmet une demande de sa gare à l'Admin.
  public function transmettreReport($idBillets)
  {
    $this->requirePermission('Billets_annulation');
    $billets = new Liste_du_jour();
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_SESSION['droit'] ?? null) === 'chef_d_escale') {
      $billets->transmettreReportBillet($idBillets);
    }
    header("Location: " . BASE_URL . "/admin/Liste_du_jours/demandesReport");
    exit;
  }

  // Validation finale (Admin/super_admin uniquement) : applique réellement le report.
  public function confirmerReport($idBillets)
  {
    $this->requirePermission('Billets_annulation');
    $billets = new Liste_du_jour();
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_SESSION['droit'] ?? null, ['Admin', 'super_admin'], true)) {
      $billets->confirmerReportBillet($idBillets);
    }
    header("Location: " . BASE_URL . "/admin/Liste_du_jours/demandesReport");
    exit;
  }

  // Rejet, possible par le chef d'escale (sa gare, étape 1) ou l'Admin (étapes 1 et 2) —
  // le modèle vérifie lui-même la portée exacte autorisée pour chaque rôle.
  public function rejeterReport($idBillets)
  {
    $this->requirePermission('Billets_annulation');
    $billets = new Liste_du_jour();
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_SESSION['droit'] ?? null, ['Admin', 'super_admin', 'chef_d_escale'], true)) {
      $billets->rejeterReportBillet($idBillets);
    }
    header("Location: " . BASE_URL . "/admin/Liste_du_jours/demandesReport");
    exit;
  }
}
