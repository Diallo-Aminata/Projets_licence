<?php

class Flotte extends Controller
{

    public function __construct()
    {
        $this->requireLogin();
        if (!in_array($_SESSION['droit'] ?? null, ['Admin', 'super_admin', 'PDG'], true)) {
            (new Configuration($_SESSION['id_utilisateur']))->set_flash("Accès refusé.", "danger");
            header('Location: ' . BASE_URL . '/admin/Homes/home');
            exit();
        }
    }

    public function index()
    {
        $programmation_voyage = new Programmation_voyage();
        $camion = new Camion();

        $this->view('admin/flotte', [
            'cars' => $programmation_voyage->getEtatFlotte(),
            'camions' => $camion->getTousPourFlotte(),
        ]);
    }
}
