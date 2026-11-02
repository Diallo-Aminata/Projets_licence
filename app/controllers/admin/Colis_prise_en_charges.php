<?php

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;                 // ← énumération 5.x
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\RoundBlockSizeMode;   // ← au lieu du namespace\RoundBlockSizeModeMargin


class Colis_prise_en_charges extends  Controller
{
  public function __construct()
  {
    // historique_colis() était gaté dans la sidebar par 'Billets_historique' (nom hérité
    // d'une confusion billets/colis) alors qu'il affiche des données colis : corrigé pour
    // utiliser 'colis_historique', cohérent avec le reste du module colis.
    $segments = isset($_GET['url']) ? explode('/', rtrim($_GET['url'], '/')) : [];

    if (in_array('historique_colis', $segments, true)) {
      $this->requirePermission('colis_historique');
      return;
    }

    if (in_array('ajouter_colis', $segments, true)) {
      $this->requirePermission('colis_creation');
      return;
    }

    // index() (liste + mise à jour) : colis_apercue (lecture seule prévue pour ce cas)
    // suffit, colis_creation reste accepté pour ne pas retirer l'accès à qui gère déjà
    // la création de colis.
    $this->requireLogin();
    $configuration = new Configuration($_SESSION['id_utilisateur']);
    if (!$configuration->userHasPermission('colis_apercue') && !$configuration->userHasPermission('colis_creation')) {
      $configuration->set_flash("Accès refusé : vous n'avez pas la permission nécessaire.", "danger");
      header('Location: ' . BASE_URL . '/admin/Homes/home');
      exit();
    }
  }

  public  function  index()
  {
    $colis_prise_en_charge =  new Colis_prise_en_charge();

    if (isset($_POST['update_colis'])) {
      $colis_prise_en_charge->updateColis();
      header('Location: ' . BASE_URL . '/admin/Colis_prise_en_charges');
      exit;
    }

    $liste_colis = $colis_prise_en_charge->FetchSelectcolis();

    $id_compagnie = $_SESSION['id_compagnie'];
    $listes_agences = $colis_prise_en_charge->FetchSelectWheres(
      '*',
      'agence',
      'id_compagnie = :id_compagnie',
      ['id_compagnie' => $id_compagnie]
    );

    $this->view('admin/liste_colis_prise', ['liste_colis' => $liste_colis, 'listes_agences' => $listes_agences]);
  }
  public function ajouter_colis()
  {

    $colis_prise_en_charge =  new Colis_prise_en_charge();
    $code_colis = $this->genererCodeUnique($colis_prise_en_charge); // générer un code unique

    // Le reçu est la preuve de dépôt remise au client : comme pour le billet
    // (cf. Add_billet::saveBillets(), paramètre billetImprime), on transmet l'id
    // du colis tout juste créé à la vue pour déclencher son impression automatique.
    // Pas de redirection ici (contrairement au billet) : set_swal() écrit directement
    // dans la réponse en cours, une redirection casserait ce mécanisme ("headers already sent").
    $colisImprime = null;
    if (isset($_POST['envoi'])) {
      $idColisCree = $colis_prise_en_charge->saveColis();
      $colisImprime = $idColisCree ?: null;
    }
    // liste
    $id_compagnie = $_SESSION['id_compagnie'];

    $listes =  $colis_prise_en_charge->FetchSelectWheres(
      '*',
      'agence',
      'id_compagnie = :id_compagnie',
      ['id_compagnie' => $id_compagnie]
    );
    $this->view('admin/ajouter_colis', ['listes' => $listes, 'code_colis' => $code_colis, 'colisImprime' => $colisImprime]);
  }

  private function genererCodeColis($longueur = 6)
  {
    $caracteres = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = '';
    for ($i = 0; $i < $longueur; $i++) {
      $code .= $caracteres[random_int(0, strlen($caracteres) - 1)];
    }
    return $code;
  }

  private function genererCodeUnique($model)
  {
    $colis_prise_en_charge =  new Colis_prise_en_charge();
    do {
      $code = $this->genererCodeColis();
      $existe = $colis_prise_en_charge->existe('colis', 'code_colis', $code); // suppose que tu as cette méthode dans ton modèle
    } while ($existe);
    return $code;
  }

  public function historique_colis()
  {
    $id_compagnie = $_SESSION['id_compagnie'];
    $idDepart = $_SESSION['ville'];
    $colis_prise_en_charge =  new Colis_prise_en_charge();
    $model = new Liste_du_jour();
    $liste_colis = $colis_prise_en_charge->FetchSelectcolis();
    $liste_horaire = $colis_prise_en_charge->FetchSelectWhereS(
      "*",
      "horaire",
      "id_compagnie = :id_compagnie",
      [":id_compagnie" => $id_compagnie]
    );
    $destinations = $model->getDestinations($idDepart, $id_compagnie);

    $this->view('admin/historique_colis', [
      'liste_colis' => $liste_colis,
      'liste_horaire' => $liste_horaire,
      'destinations' => $destinations
    ]);
  }
  public function imprimer_recu(int $id_colis): void
  {
    /* ---------- 1. Récupération DB ---------- */
    $colisModel = new Livraisons_colis();

    $colis = $colisModel->getById($id_colis);
    $compagnie = $colisModel->infoCompagnie($_SESSION['id_compagnie'] ?? 0);

    if (!$colis || !$compagnie) {
      $colisModel->set_flash('Colis ou compagnie introuvable.', 'danger');
      header('Location: ' . BASE_URL . 'admin/livraison_colis');
      exit;
    }

    $logoPath = null;
    if (!empty($compagnie['logo'])) {
      $logoPath = '/public/images/logos/' . $compagnie['logo'];
    }

    /* ---------- 2. Génération du QR Code en base64 ---------- */
    $qrData = "Nom du colis : {$colis['nom_colis']}\n" .
      "Nature       : {$colis['nature']}\n" .
      "Code         : {$colis['code_colis']}\n" .
      "Depart       : {$colis['provient_de']}\n" .
      "Destination  : {$colis['localite']}\n" .
      "Enregistre par : {$colis['agent_nom']}\n" .
      "Valeur       : " . number_format($colis['valeur'], 0, ',', ' ') . " FCFA\n" .
      "Frais        : " . number_format($colis['fraix_transaction'], 0, ',', ' ') . " FCFA";

    $qrResult = Builder::create()
      ->writer(new PngWriter())
      ->data($qrData)
      ->size(200)
      ->margin(6)
      ->build();

    $qrBase64 = base64_encode($qrResult->getString());
    $qrPath   = "data:image/png;base64," . $qrBase64;

    /* ---------- 3. Construction HTML ---------- */
    ob_start();
    include ROOT . '/app/views/admin/pdf/recu_colis.php';
    $html = ob_get_clean();

    /* ---------- 4. Génération PDF (imprimante thermique 80mm) ---------- */
    $this->streamThermalPdf($html, "recu_colis_{$colis['id_colis']}.pdf");
  }


  // Export PDF A4 de la liste des colis (tous, ou filtres par destination/statut si
  // renseignes) : meme principe que Liste_du_jours::imprimerListe() (liste d'embarquement),
  // avec les memes informations que le recu colis (cf. recu_colis.php) pour chaque ligne.
  public function imprimerListeColis(): void
  {
    $colisModel = new Colis_prise_en_charge();
    $liste_colis = $colisModel->FetchSelectColis();

    $idDestination = trim($_GET['destination'] ?? '');
    $destinationNom = trim($_GET['destination_nom'] ?? '');
    $statut = trim($_GET['statut'] ?? '');
    $statutNom = trim($_GET['statut_nom'] ?? '');

    if ($idDestination !== '') {
      $liste_colis = array_values(array_filter($liste_colis, function ($colis) use ($idDestination) {
        return (string)($colis['id_agence'] ?? '') === $idDestination;
      }));
    }

    if ($statut !== '') {
      $liste_colis = array_values(array_filter($liste_colis, function ($colis) use ($statut) {
        return ($colis['status'] ?? '') === $statut;
      }));
    }

    $compagnieModel = new Livraisons_colis();
    $compagnie = $compagnieModel->infoCompagnie($_SESSION['id_compagnie'] ?? 0);

    $logoPath = null;
    if (!empty($compagnie['logo'])) {
      $logoPath = ROOT . '/public/images/logos/' . $compagnie['logo'];
    }

    ob_start();
    include ROOT . '/app/views/admin/pdf/liste_colis.php';
    $html = ob_get_clean();

    $opt = new \Dompdf\Options();
    $opt->setChroot(ROOT);
    $opt->setIsRemoteEnabled(true);

    $dompdf = new \Dompdf\Dompdf($opt);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape'); // nombreuses colonnes (infos du recu pour chaque colis)
    $dompdf->render();

    $dompdf->stream('liste_colis_' . date('Ymd_His') . '.pdf', ['Attachment' => false]);
    exit;
  }

  // Renvoie les données du reçu colis en JSON pour impression thermique ESC/POS
  // (pont local, cf. donneesTicketThermique() dans Liste_du_jours pour les billets).
  public function donneesRecuThermique(int $id_colis): void
  {
    $colisModel = new Livraisons_colis();

    $colis = $colisModel->getById($id_colis);
    $compagnie = $colisModel->infoCompagnie($_SESSION['id_compagnie'] ?? 0);

    header('Content-Type: application/json; charset=utf-8');

    if (!$colis || !$compagnie) {
      echo json_encode(['error' => 'Colis ou compagnie introuvable.']);
      exit;
    }

    // Logo envoye en base64, meme principe que pour le billet (cf. Liste_du_jours::
    // donneesTicketThermique()) : le pont n'a acces a aucun fichier du site.
    $logoBase64 = null;
    if (!empty($compagnie['logo'])) {
      $logoPath = ROOT . '/public/images/logos/' . $compagnie['logo'];
      if (file_exists($logoPath)) {
        $logoBase64 = base64_encode(file_get_contents($logoPath));
      }
    }

    $json = json_encode([
      'type' => 'colis',
      'compagnie' => $compagnie['nom'] ?? 'Nom Compagnie',
      'slogan' => $compagnie['slogant'] ?? '',
      'logo' => $logoBase64,
      'nom' => $colis['nom_colis'] ?? '-',
      'nature' => $colis['nature'] ?? '-',
      'code' => $colis['code_colis'] ?? '-',
      'expediteur' => $colis['expediteur'] ?? '-',
      'numeroExp' => $colis['numero_exp'] ?? '-',
      'destinataire' => $colis['destinataire'] ?? '-',
      'numeroDest' => $colis['numero_dest'] ?? '-',
      'depart' => $colis['provient_de'] ?? '-',
      'destination' => $colis['localite'] ?? '-',
      'valeur' => number_format((float)($colis['valeur'] ?? 0), 0, ',', ' '),
      'frais' => number_format((float)($colis['fraix_transaction'] ?? 0), 0, ',', ' '),
      'agent' => $colis['agent_nom'] ?? '-',
    ]);

    echo $json !== false ? $json : json_encode(['error' => 'Erreur d\'encodage des données : ' . json_last_error_msg()]);
    exit;
  }
}
