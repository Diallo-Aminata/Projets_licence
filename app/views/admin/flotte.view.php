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
        <main class="page-content">
            <!--breadcrumb-->
            <div class="page-breadcrumb d-flex flex-column flex-sm-row align-items-start align-items-sm-center mb-3">
                <div class="breadcrumb-title pe-3">
                    <i class="bx bx-map-pin me-1"></i> État de la flotte
                </div>
                <div class="ps-0 ps-sm-3">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 p-0">
                            <li class="breadcrumb-item">
                                <a href="javascript:;"><i class="bx bx-home-alt"></i></a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Où se trouvent les cars</li>
                        </ol>
                    </nav>
                </div>
                <div class="ms-sm-auto mt-2 mt-sm-0">
                    <a href="javascript:history.back()" class="btn btn-sm btn-outline-primary rounded-pill shadow-sm">
                        <i class="bx bx-left-arrow-alt"></i> Retour
                    </a>
                </div>
            </div>
            <!--end breadcrumb-->

            <?php $this->view("admin/set_flash") ?>

            <div class="card shadow-lg border-0 rounded-3">
                <div class="card-header bg-primary text-white fw-bold">
                    <i class="bx bx-bus me-1"></i> État actuel de tous les cars
                </div>
                <div class="card-body">
                    <?php if (empty($cars)): ?>
                        <p class="text-muted mb-0">Aucun car trouvé pour votre compagnie.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered align-middle shadow-sm rounded">
                                <thead class="table-light text-center">
                                    <tr>
                                        <th>Numéro Car</th>
                                        <th>Matricule</th>
                                        <th>Places</th>
                                        <th>État</th>
                                        <th>Détails</th>
                                    </tr>
                                </thead>
                                <tbody class="text-center">
                                    <?php foreach ($cars as $car): ?>
                                        <?php
                                            $enTransit = !empty($car->status_car) && strpos($car->status_car, 'En_transit_') === 0;

                                            if (empty($car->status_car)) {
                                                $badge = 'secondary';
                                                $etat = 'Position inconnue';
                                            } elseif (!$enTransit) {
                                                $badge = 'success';
                                                $etat = 'Disponible à ' . $car->status_car;
                                            } elseif (empty($car->id_programmation)) {
                                                $badge = 'danger';
                                                $etat = 'Anomalie — voyage actif introuvable';
                                            } elseif (empty($car->decolle_le)) {
                                                $badge = 'warning';
                                                $etat = 'Embarquement en cours';
                                            } else {
                                                $badge = 'info';
                                                $etat = 'En route';
                                            }
                                        ?>
                                        <tr>
                                            <td class="fw-bold"><?= htmlspecialchars($car->numero_car) ?></td>
                                            <td><?= htmlspecialchars($car->matriculle) ?></td>
                                            <td><?= htmlspecialchars($car->nbr_place) ?></td>
                                            <td>
                                                <span class="badge bg-<?= $badge ?>"><?= htmlspecialchars($etat) ?></span>
                                            </td>
                                            <td class="text-start">
                                                <?php if ($enTransit && !empty($car->id_programmation)): ?>
                                                    <div>
                                                        <?= htmlspecialchars($car->origine) ?>
                                                        <i class="bx bx-right-arrow-alt mx-1"></i>
                                                        <?= htmlspecialchars($car->destination) ?>
                                                        <?php if (!empty($car->numeroGareDestination)): ?>
                                                            <span class="text-muted">(gare <?= htmlspecialchars($car->numeroGareDestination) ?>)</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php if (!empty($car->decolle_le)): ?>
                                                        <?php
                                                            $minutes = max(0, floor((time() - strtotime($car->decolle_le)) / 60));
                                                            $heures = intdiv($minutes, 60);
                                                            $reste = $minutes % 60;
                                                        ?>
                                                        <small class="text-muted">Parti depuis <?= $heures > 0 ? $heures . 'h ' : '' ?><?= $reste ?>min</small>
                                                    <?php else: ?>
                                                        <small class="text-muted">Programmé le <?= htmlspecialchars(date('d/m/Y', strtotime($car->date_enregistre))) ?></small>
                                                    <?php endif; ?>
                                                <?php elseif ($enTransit): ?>
                                                    <span class="text-muted">
                                                        Marqué « <?= htmlspecialchars($car->status_car) ?> » mais aucune programmation active correspondante.
                                                        Voir <a href="<?= BASE_URL ?>/admin/Programmation_voyages">Cars bloqués</a> pour corriger.
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">—</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Un camion n'a ni status_car ni programmation_voyage (pas de trajet, pas de
                 position) : seulement un statut actif/inactif -- affiché dans un tableau
                 séparé plutôt que fusionné avec celui des cars, dont la logique d'état
                 (en transit/disponible à X/anomalie) n'a pas d'équivalent ici. -->
            <div class="card shadow-lg border-0 rounded-3 mt-4">
                <div class="card-header bg-primary text-white fw-bold">
                    <i class="bx bx-truck me-1"></i> État actuel de tous les camions
                </div>
                <div class="card-body">
                    <?php if (empty($camions)): ?>
                        <p class="text-muted mb-0">Aucun camion trouvé pour votre compagnie.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered align-middle shadow-sm rounded">
                                <thead class="table-light text-center">
                                    <tr>
                                        <th>Numéro Camion</th>
                                        <th>Matricule</th>
                                        <th>Statut</th>
                                    </tr>
                                </thead>
                                <tbody class="text-center">
                                    <?php foreach ($camions as $camion): ?>
                                        <tr>
                                            <td class="fw-bold"><?= htmlspecialchars($camion->numero_camion) ?></td>
                                            <td><?= htmlspecialchars($camion->matriculle) ?></td>
                                            <td>
                                                <?php if (!empty($camion->destination_location)): ?>
                                                    <span class="badge bg-info">En location</span>
                                                    <br><small class="text-muted">Vers <?= htmlspecialchars($camion->destination_location) ?><br>Jusqu'au <?= date('d/m/Y', strtotime($camion->retour_prevu)) ?></small>
                                                <?php elseif (($camion->actif ?? 'on') === 'on'): ?>
                                                    <span class="badge bg-success">Disponible</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Inactif</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

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
</body>

</html>
