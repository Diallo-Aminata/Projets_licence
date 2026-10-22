<?php
class Bagages extends Controller
{
    public function __construct()
    {
        $this->requirePermission('Billets_bagages');
    }

    // Ajout de bagage(s) a un billet DEJA vendu (depuis "Liste du jour", pour un passager qui
    // revient avec des bagages). Cf. app/models/Bagage.php::ajouterApresCoup().
    public function ajouter($idBillets = null)
    {
        $idBillets = (int)$idBillets;
        $billetModel = new Liste_du_jour();
        $bagageModel = new Bagage();

        $billet = $billetModel->getBilletById($idBillets);
        if (!$billet) {
            $bagageModel->set_flash("Billet introuvable.", "danger");
            header("Location: " . BASE_URL . "/admin/Liste_du_jours/index");
            exit;
        }

        if (isset($_POST['save'])) {
            if (!csrf_verify()) {
                $bagageModel->set_flash("Session expirée, veuillez réessayer.", "danger");
            } else {
                $descriptions = $_POST['bagage_description'] ?? [];
                $poidsListe   = $_POST['bagage_poids'] ?? [];
                $prixListe    = $_POST['bagage_prix'] ?? [];

                $lignes = [];
                foreach ($prixListe as $i => $prix) {
                    $lignes[] = [
                        'description' => $descriptions[$i] ?? null,
                        'poids'       => $poidsListe[$i] ?? null,
                        'prix'        => $prix
                    ];
                }

                if ($bagageModel->ajouterApresCoup($idBillets, $lignes)) {
                    header("Location: " . BASE_URL . "/admin/Bagages/ajouter/$idBillets");
                    exit;
                }
            }
        }

        $this->view('admin/ajouter_bagages', [
            'billet'       => $billet,
            'bagages'      => $bagageModel->getBagagesPourBillet($idBillets),
            'totalBagages' => $bagageModel->getTotalBagages($idBillets)
        ]);
    }
}
