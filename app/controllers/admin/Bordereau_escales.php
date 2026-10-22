<?php
class Bordereau_escales extends Controller
{
    public function __construct()
    {
        $this->requirePermission('Caisse_modifier');
    }

    // Meme logique que Caisse::resolveGareEscale() : Admin choisit la gare dans le
    // formulaire/la requete, les autres roles sont limites a leur propre gare de session.
    private function resolveGareEscale(Bordereau_escale $model): array
    {
        $estAdmin = ($_SESSION['droit'] ?? null) === 'Admin';

        if (!$estAdmin) {
            return [(int)($_SESSION['id_agence'] ?? 0), false, []];
        }

        $idCompagnie  = (int)($_SESSION['id_compagnie'] ?? 0);
        $listeAgences = $model->FetchSelectWheres(
            'idAgence, localite, numeroGare',
            'agence',
            'id_compagnie = :id_compagnie',
            [':id_compagnie' => $idCompagnie]
        );

        $idAgencePoste = (int)($_GET['id_agence'] ?? $_POST['id_agence'] ?? 0);
        if ($idAgencePoste) {
            $agenceValide = $model->fetchOne(
                "SELECT idAgence FROM agence WHERE idAgence = :id AND id_compagnie = :ic LIMIT 1",
                [':id' => $idAgencePoste, ':ic' => $idCompagnie]
            );
            $idAgencePoste = $agenceValide ? $idAgencePoste : 0;
        }

        return [$idAgencePoste, true, $listeAgences];
    }

    public function index()
    {
        $model = new Bordereau_escale();
        [$idAgence, $estAdmin, $listeAgences] = $this->resolveGareEscale($model);
        $date = $_GET['date'] ?? null;

        $bordereaux = $model->getBordereaux([
            'id_agence' => $idAgence ?: null,
            'date'      => $date,
        ]);

        $this->view('admin/bordereau_escales', [
            'bordereaux'           => $bordereaux,
            'date'                 => $date,
            'estAdmin'             => $estAdmin,
            'listeAgences'         => $listeAgences,
            'idAgenceSelectionnee' => $idAgence,
        ]);
    }

    public function ajouter()
    {
        $model = new Bordereau_escale();
        [$idAgence, $estAdmin, $listeAgences] = $this->resolveGareEscale($model);

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
            $idBordereau = $model->saveBordereau();
            if ($idBordereau !== false) {
                header("Location: " . BASE_URL . "/admin/Bordereau_escales/detail/$idBordereau");
                exit;
            }
        }

        $this->view('admin/ajouter_bordereau_escale', [
            'estAdmin'     => $estAdmin,
            'listeAgences' => $listeAgences,
        ]);
    }

    public function detail($id = null)
    {
        $model     = new Bordereau_escale();
        $bordereau = $model->getBordereauDetail((int)$id);

        if (!$bordereau) {
            $model->set_flash("Bordereau introuvable.", "danger");
            header("Location: " . BASE_URL . "/admin/Bordereau_escales/index");
            exit;
        }

        $this->view('admin/detail_bordereau_escale', ['bordereau' => $bordereau]);
    }

    // Bordereau VIERGE (lignes vides) a imprimer et distribuer aux escales sans reseau,
    // pour saisie ulterieure via ajouter(). Meme pattern Dompdf que
    // Salaires::telecharger_bulletin()/Colis_prise_en_charges::imprimer_liste_colis().
    public function telecharger_modele()
    {
        $compagnieModel = new Livraisons_colis();
        $compagnie = $compagnieModel->infoCompagnie($_SESSION['id_compagnie'] ?? 0);

        $logoPath = null;
        if (!empty($compagnie['logo'])) {
            $logoPath = ROOT . '/public/images/logos/' . $compagnie['logo'];
        }

        ob_start();
        include ROOT . '/app/views/admin/pdf/bordereau_escale_modele.php';
        $html = ob_get_clean();

        $opt = new \Dompdf\Options();
        $opt->setChroot(ROOT);
        $opt->setIsRemoteEnabled(true);

        $dompdf = new \Dompdf\Dompdf($opt);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $dompdf->stream('bordereau_escale_modele.pdf', ['Attachment' => true]);
        exit;
    }
}
