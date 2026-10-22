<?php
class Add_billets extends Controller
{
    public function __construct()
    {
        $this->requirePermission('Billets_creation');
    }

    public function index()
    {
        $model = new Add_billet();
        date_default_timezone_set('Africa/Bamako');
        $data['destinations'] = $model->getDestinationsWithHeuresAndEscales();

        if (($_SESSION['droit'] ?? null) === 'Admin') {
            $data['agences'] = $model->getAgencesByCompagnie();
            $data['idDepartSelectionne'] = $_POST['idDepart'] ?? $_GET['idDepart'] ?? null;

            // Destinations de TOUTES les gares de la compagnie, préchargées d'un coup :
            // changer la gare de départ ne fait plus qu'échanger quel jeu de données JS est
            // utilisé (instantané), au lieu de recharger toute la page (location.href avant).
            $data['destinationsParGare'] = [];
            foreach ($data['agences'] as $agence) {
                $data['destinationsParGare'][$agence['idAgence']] = $model->getDestinationsWithHeuresAndEscales($agence['idAgence']);
            }
        }

        if (isset($_POST['save'])) {
            $result = $model->saveBillets();

            if ($result === true) {
                $_SESSION['flash'] = "Réservation enregistrée !";
                $this->view('admin/add_billets');
                exit;
            } else {
                $data['erreurs'] = $result; // tableau d'erreurs
            }
        }


        // var_dump($data['destinations']);exit;
    
        $this->view('admin/add_billets', $data);
    }

    // Etat du creneau d'aujourd'hui (car deja programme + places restantes, ou liste des
    // cars disponibles a la gare pour en choisir un a la volee) : cf. Add_billet::getEtatCreneauPourAjax().
    // Lecture seule : pas de csrf_verify(), meme choix que Locations_cars::ajaxCarsDisponibles().
    public function ajaxEtatCreneau()
    {
        header('Content-Type: application/json; charset=utf-8');

        $idProgrammer = (int)($_POST['idProgrammer'] ?? 0);
        if (!$idProgrammer) {
            echo json_encode(['erreur' => "Trajet manquant."]);
            exit;
        }

        $model = new Add_billet();
        echo json_encode($model->getEtatCreneauPourAjax($idProgrammer));
        exit;
    }
}
