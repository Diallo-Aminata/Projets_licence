<?php $this->view('admin/partials/headers') ?>

<body>
    <!--start wrapper-->
    <div class="wrapper">
        <!--start top header-->
        <?php $this->view('admin/partials/navbar') ?>
        <!--end top header-->

        <!--start sidebar -->
        <?php $this->view('admin/partials/sidebar') ?>
        <!--end sidebar -->

        <!--start content-->
        <main class="page-content ">
            <!--breadcrumb-->
            <div class="page-breadcrumb d-flex flex-column flex-sm-row align-items-start align-items-sm-center mb-3">
                <div class="breadcrumb-title pe-3 text-primary"><i class="bx bx-package me-1"></i> G-colis</div>
                <div class="ps-3 flex-grow-1">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 p-0">
                            <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a></li>
                            <li class="breadcrumb-item active " aria-current="page">Ajouter des colis à envoyer</li>
                        </ol>
                    </nav>
                </div>
                <div class="ms-auto mt-2 mt-sm-0 d-flex gap-2">
                    <a href="<?= BASE_URL ?>/admin/Envoi_colis/liste_colis_envoyer" class="btn btn-sm btn-success rounded-pill shadow-sm">
                        <i class="bx bx-list-ul me-1"></i> Voir la liste
                    </a>
                    <a href="javascript:history.back()" class="btn btn-sm btn-outline-primary rounded-pill shadow-sm">
                        <i class="bx bx-left-arrow-alt"></i>Retour
                    </a>
                </div>
            </div>
            <!--end breadcrumb-->
            <?php $this->view("admin/set_flash") ?>
            <div class="card shadow-sm rounded-3">
                <form action="" method="post">
                    <div class="card-body border-top border-4 border-primary">

                        <div class="row">
                        <!-- Sélection du car -->
                        <div class="mb-4 col-12 col-md-4">
                            <label for="id_car_selectionner" class="form-label fw-bold">Sélectionner un car</label>
                            <select id="id_car_selectionner" name="id_car_selectionner" class="form-select shadow-sm">
                                <option value="">-- Choisir un car --</option>
                                <?php foreach ($liste_cars as $car): ?>
                                    <option value="<?= $car['id_car_programmer'] ?>"
                                        <?= (!empty($car_selectionne) && $car['id_car_programmer'] == $car_selectionne['id_car_programmer']) ? 'selected' : '' ?>>
                                        Car N°<?= $car['id_car_programmer'] ?> —
                                        Départ: <?= $car['id_horaire'] ?> —
                                        Destination: <?= $car['id_trajet'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (!empty($car_selectionne)): ?>
                                <div class="form-text text-success">
                                    <i class="bx bx-check-circle"></i> Car N°<?= htmlspecialchars($car_selectionne['id_car_programmer']) ?> présélectionné (départ <?= htmlspecialchars($car_selectionne['id_horaire']) ?> vers <?= htmlspecialchars($car_selectionne['id_trajet']) ?>).
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Sélection du camion (véhicule de fret, pas de trajet programmé requis) -->
                        <div class="mb-4 col-12 col-md-4">
                            <label for="id_camion_selectionner" class="form-label fw-bold">— ou sélectionner un camion —</label>
                            <select id="id_camion_selectionner" name="id_camion_selectionner" class="form-select shadow-sm">
                                <option value="">-- Choisir un camion --</option>
                                <?php foreach ($liste_camions as $camion): ?>
                                    <option value="<?= $camion['id_camion'] ?>"
                                        <?= (!empty($camion_selectionne) && $camion['id_camion'] == $camion_selectionne['id_camion']) ? 'selected' : '' ?>>
                                        Camion N°<?= $camion['numero_camion'] ?> — Matricule : <?= htmlspecialchars($camion['matriculle']) ?>
                                        <?php if (!empty($camion['destination_location'])): ?>
                                             — État : En location vers <?= htmlspecialchars($camion['destination_location']) ?> (Départ : <?= date('d/m/Y', strtotime($camion['date_depart'])) ?> - Arrivée : <?= date('d/m/Y', strtotime($camion['retour_prevu'])) ?>)
                                        <?php else: ?>
                                             — État : Disponible
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (!empty($camion_selectionne)): ?>
                                <div class="form-text text-success">
                                    <i class="bx bx-check-circle"></i> Camion N°<?= htmlspecialchars($camion_selectionne['numero_camion']) ?> présélectionné.
                                </div>
                            <?php endif; ?>
                        </div>
                        </div>

                        <!-- Table des colis -->
                        <table class="table table-hover table-striped table-bordered align-middle shadow-sm">
                            <thead class="table-primary text-center">
                                <tr>
                                    <th><?php if (($_SESSION['droit'] ?? null) !== 'PDG'): ?><input type="checkbox" id="selectAll"><?php endif; ?></th>
                                    <th>Nom colis</th>
                                    <th>Nature</th>
                                    <th>Valeur</th>
                                    <th>Fraix de transaction</th>
                                    <th>Destination</th>
                                </tr>
                            </thead>
                            <tbody class="text-center">
                                <?php foreach ($liste_colis as $colis) : ?>
                                    <?php if ($colis['status'] === 'enregistre') : ?>
                                        <tr>
                                            <td><?php if (($_SESSION['droit'] ?? null) !== 'PDG'): ?><input type="checkbox" name="selected_colis[]" class="checkbox-car" value="<?= htmlspecialchars($colis['id_colis']) ?>"><?php endif; ?></td>
                                            <td><?= htmlspecialchars($colis['nom_colis']) ?></td>
                                            <td><?= htmlspecialchars($colis['nature']) ?></td>
                                            <td><?= htmlspecialchars($colis['valeur']) ?></td>
                                            <td><?= htmlspecialchars($colis['fraix_transaction']) ?></td>
                                            <td><?= htmlspecialchars($colis['destination']) ?></td>

                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <!-- Bouton enregistrer -->
                        <?php if (($_SESSION['droit'] ?? null) !== 'PDG'): ?>
                        <div class="mt-4 text-end">
                            <button class="btn btn-success rounded-pill shadow-sm px-4" type="submit" name="submit">
                                <i class="bx bx-save me-1"></i> Enregistrer
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!--end row-->
        </main>
        <!--end page main-->

        <!--start overlay-->
        <div class="overlay nav-toggle-icon"></div>
        <!--end overlay-->

        <!--Start Back To Top Button-->
        <a href="javaScript:;" class="back-to-top"><i class='bx bxs-up-arrow-alt'></i></a>
        <!--End Back To Top Button-->

    </div>
    <!--end wrapper-->


    <?php $this->view('admin/partials/foot') ?>
    <!-- ✅ Script JS pour gérer la sélection de tous les checkboxes -->
    <script>
        const selectAllCheckbox = document.getElementById('selectAll');
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                const isChecked = this.checked;
                document.querySelectorAll('.checkbox-car').forEach(function(checkbox) {
                    checkbox.checked = isChecked;
                });
            });
        }
    </script>
    <script>
        // Un colis part soit sur un car, soit sur un camion : choisir l'un désélectionne l'autre.
        document.addEventListener("DOMContentLoaded", function () {
            const carSel = document.getElementById('id_car_selectionner');
            const camionSel = document.getElementById('id_camion_selectionner');
            carSel.addEventListener('change', function () { if (this.value) camionSel.value = ''; });
            camionSel.addEventListener('change', function () { if (this.value) carSel.value = ''; });
        });
    </script>


</body>

</html>