<?php

class Locations_cars extends Controller
{
    public function __construct()
    {
        $this->requireLogin();

        // Reserve a Admin/chef_d_escale/PDG (comme Depenses) : Location_gestion seul ne
        // suffit pas, sinon un Utilisateur simple a qui cette permission serait assignee par
        // erreur y aurait quand meme acces.
        if (!in_array($_SESSION['droit'] ?? null, ['Admin', 'chef_d_escale', 'PDG'], true)) {
            (new Configuration())->set_flash("Accès refusé.", "danger");
            header("Location: " . BASE_URL . "/admin/Homes/home");
            exit;
        }

        $this->requirePermission('Location_gestion');
    }

    public function index()
    {
        $model = new Location_car();
        $id_compagnie = $_SESSION['id_compagnie'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_location'])) {
            $model->saveLocation();
            header("Location: " . BASE_URL . "/admin/Locations_cars");
            exit;
        }

        // Liste des gares, utilisee par l'Admin pour choisir la gare de depart
        $listeAgences = [];
        if ($_SESSION['droit'] === 'Admin') {
            $listeAgences = $model->FetchSelectWheres(
                '*',
                'agence',
                'id_compagnie = :id_compagnie',
                ['id_compagnie' => $id_compagnie]
            );
        }

        $this->view('admin/locations_cars', [
            'listeLocations' => $model->getLocations(),
            'listeAgences'   => $listeAgences,
        ]);
    }

    // Renvoie en JSON les cars disponibles pour la gare de depart / periode demandees,
    // pour peupler dynamiquement le menu deroulant du formulaire.
    public function ajaxCarsDisponibles()
    {
        header('Content-Type: application/json; charset=utf-8');

        $droit = $_SESSION['droit'] ?? null;
        $id_agence_depart = $droit === 'chef_d_escale' ? $_SESSION['id_agence'] : (int)($_POST['id_agence_depart'] ?? 0);
        $date_depart = $_POST['date_depart'] ?? '';
        $date_retour_prevu = $_POST['date_retour_prevu'] ?? '';

        if (!$id_agence_depart || !$date_depart || !$date_retour_prevu) {
            echo json_encode(['error' => 'Gare de départ et dates requises.']);
            exit;
        }
        if ($date_retour_prevu < $date_depart) {
            echo json_encode(['error' => 'La date de retour prévu ne peut pas être avant la date de départ.']);
            exit;
        }

        $model = new Location_car();
        $limiterAGare = ($droit === 'chef_d_escale') && $date_depart === date('Y-m-d');
        $cars = $model->carsDisponibles($id_agence_depart, $date_depart, $date_retour_prevu, $limiterAGare);

        echo json_encode([
            'cars' => array_map(fn($c) => [
                'id_car'     => (int)$c->id_car,
                'numero_car' => $c->numero_car,
                'matriculle' => $c->matriculle,
            ], $cars)
        ]);
        exit;
    }

    // Miroir de ajaxCarsDisponibles() pour un camion (pas de limiterAGare, cf.
    // Location_car::camionsDisponibles()).
    public function ajaxCamionsDisponibles()
    {
        header('Content-Type: application/json; charset=utf-8');

        $droit = $_SESSION['droit'] ?? null;
        $id_agence_depart = $droit === 'chef_d_escale' ? $_SESSION['id_agence'] : (int)($_POST['id_agence_depart'] ?? 0);
        $date_depart = $_POST['date_depart'] ?? '';
        $date_retour_prevu = $_POST['date_retour_prevu'] ?? '';

        if (!$id_agence_depart || !$date_depart || !$date_retour_prevu) {
            echo json_encode(['error' => 'Gare de départ et dates requises.']);
            exit;
        }
        if ($date_retour_prevu < $date_depart) {
            echo json_encode(['error' => 'La date de retour prévu ne peut pas être avant la date de départ.']);
            exit;
        }

        $model = new Location_car();
        $camions = $model->camionsDisponibles($id_agence_depart, $date_depart, $date_retour_prevu);

        echo json_encode([
            'camions' => array_map(fn($c) => [
                'id_camion'     => (int)$c->id_camion,
                'numero_camion' => $c->numero_camion,
                'matriculle'    => $c->matriculle,
            ], $camions)
        ]);
        exit;
    }

    public function valider($id = null)
    {
        if ($id === null) {
            header("Location: " . BASE_URL . "/admin/Locations_cars");
            exit;
        }

        $model = new Location_car();
        if (($_SESSION['droit'] ?? null) !== 'Admin') {
            $model->set_flash("Accès réservé à l'Admin de la compagnie.", "danger");
            header("Location: " . BASE_URL . "/admin/Homes/home");
            exit;
        }

        $model->validerLocation($id);
        header("Location: " . BASE_URL . "/admin/Locations_cars");
        exit;
    }

    public function rejeter($id = null)
    {
        if ($id === null) {
            header("Location: " . BASE_URL . "/admin/Locations_cars");
            exit;
        }

        $model = new Location_car();
        if (($_SESSION['droit'] ?? null) !== 'Admin') {
            $model->set_flash("Accès réservé à l'Admin de la compagnie.", "danger");
            header("Location: " . BASE_URL . "/admin/Homes/home");
            exit;
        }

        $model->rejeterLocation($id);
        header("Location: " . BASE_URL . "/admin/Locations_cars");
        exit;
    }

    // Facture A4 imprimable d'une location (peu importe son statut).
    public function imprimerFacture($id = null)
    {
        if ($id === null) {
            header("Location: " . BASE_URL . "/admin/Locations_cars");
            exit;
        }

        $model = new Location_car();
        $location = $model->getById($id);
        $compagnie = $model->infoCompagnie($_SESSION['id_compagnie'] ?? 0);

        if (!$location || !$compagnie) {
            $model->set_flash("Location ou compagnie introuvable.", "danger");
            header("Location: " . BASE_URL . "/admin/Locations_cars");
            exit;
        }

        $logoPath = null;
        if (!empty($compagnie['logo'])) {
            $logoPath = ROOT . '/public/images/logos/' . $compagnie['logo'];
        }

        ob_start();
        include ROOT . '/app/views/admin/pdf/facture_location.php';
        $html = ob_get_clean();

        $opt = new \Dompdf\Options();
        $opt->setChroot(ROOT);
        $opt->setIsRemoteEnabled(true);

        $dompdf = new \Dompdf\Dompdf($opt);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $dompdf->stream("facture_location_{$location->id_location}.pdf", ['Attachment' => false]);
        exit;
    }
}
