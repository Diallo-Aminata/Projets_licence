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
                <div class="breadcrumb-title pe-3">
                    <i class="bx bx-calendar-event me-1"></i> G-programmer
                </div>
                <div class="ps-0 ps-sm-3">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 p-0">
                            <li class="breadcrumb-item">
                                <a href="javascript:;"><i class="bx bx-home-alt"></i></a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Programmation de voyage</li>
                        </ol>
                    </nav>
                </div>
                <div class="ms-sm-auto mt-2 mt-sm-0">
                    <div class="btn-group">
                        <?php if (($_SESSION['droit'] ?? null) !== 'PDG'): ?>
                        <a href="<?= BASE_URL ?>/admin/Programmation_voyages"
                            class="btn btn-sm btn-info text-white rounded-pill shadow-sm">
                            <i class="bx bx-plus me-1"></i> Ajouter
                        </a>
                        <?php endif; ?>
                        <a href="javascript:history.back()"
                            class="btn btn-sm btn-outline-primary rounded-pill shadow-sm ms-2">
                            <i class="bx bx-left-arrow-alt"></i>
                        </a>
                    </div>
                </div>
            </div>

            <?php $this->view("admin/set_flash") ?>

            <div class="card shadow-lg border-0 rounded-3">
                <div class="card-header bg-primary text-white fw-bold">
                    <i class="bx bx-bus me-1"></i> Liste des programmations du jour
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="example"
                            class="table table-hover align-middle mb-0 table-striped table-bordered rounded-3">
                            <thead class="table-primary text-center">
                                <tr>
                                    <th>Numéro de car</th>
                                    <?php if ($_SESSION['droit'] === 'Admin'): ?>
                                        <th>Gare</th>
                                    <?php endif; ?>
                                    <th>Horaire</th>
                                    <th>Destination</th>
                                    <th>Place disponible</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody class="text-center">
                                <?php
                                date_default_timezone_set('Africa/Bamako');
                                $dateActuelle = new DateTime();

                                foreach ($listeProgrammer as $listeProgrammers):
                                    if ($_SESSION['droit'] !== 'Admin' && $listeProgrammers->localite_user !== $_SESSION['ville']) {
                                        continue;
                                    }
                                    $dateEnregistrement = new DateTime($listeProgrammers->date_enregistre);
                                    $interval = $dateActuelle->getTimestamp() - $dateEnregistrement->getTimestamp();

                                    if ($dateEnregistrement->format('Y-m-d') !== $dateActuelle->format('Y-m-d') || $interval > 86400) {
                                        continue;
                                    }
                                ?>
                                    <tr>
                                        <td><?= htmlspecialchars($listeProgrammers->numero_car) ?></td>
                                        <?php if ($_SESSION['droit'] === 'Admin'): ?>
                                            <td><?= htmlspecialchars($listeProgrammers->localite_user) ?></td>
                                        <?php endif; ?>

                                        <td><?= htmlspecialchars($listeProgrammers->id_horaire) ?></td>
                                        <td>
                                            <?= htmlspecialchars($listeProgrammers->id_trajet) ?>
                                            <?php if (!empty($listeProgrammers->numeroGareDestination)): ?>
                                                <span class="text-muted">(Gare <?= htmlspecialchars($listeProgrammers->numeroGareDestination) ?>)</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="fw-bold text-success">
                                            <?= htmlspecialchars($listeProgrammers->place_disponible) ?>
                                        </td>

                                        <td>
                                            <div class="dropdown">
                                                <a href="#" class="text-dark fs-5" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="bx bx-dots-vertical-rounded"></i>
                                                </a>
                                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                    <?php if (($_SESSION['droit'] ?? null) !== 'PDG'): ?>
                                                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/Programmation_voyages/edit/<?= $listeProgrammers->id_programmation ?>"><i class="bx bx-edit me-2"></i>Modifier</a></li>
                                                    <?php if ((int)$listeProgrammers->place_disponible > 0): ?>
                                                        <li><a class="dropdown-item transfer-btn" href="#"
                                                                data-id-programmation="<?= $listeProgrammers->id_programmation ?>"
                                                                data-bs-toggle="modal" data-bs-target="#modalTransfert">
                                                                <i class="bx bx-transfer me-2"></i>Transférer les passagers</a></li>
                                                    <?php endif; ?>
                                                    <li><a class="dropdown-item text-danger desactiver-programmation-btn" href="#"
                                                            data-url="<?= BASE_URL ?>/admin/Programmation_voyages/desactiver/<?= $listeProgrammers->id_programmation ?>"
                                                            data-numero-car="<?= htmlspecialchars($listeProgrammers->numero_car) ?>">
                                                            <i class="bx bx-x-circle me-2"></i>Désactiver</a></li>
                                                    <?php endif; ?>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
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

    <!-- Modal transfert de passagers -->
    <div class="modal fade" id="modalTransfert" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title text-white">Transférer les passagers</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: invert(1) grayscale(100%) brightness(200%);"></button>
                </div>
                <div class="modal-body">
                    <div id="transfertLoading" class="text-center text-muted py-3">Recherche des gares compatibles...</div>
                    <div id="transfertAucune" class="alert alert-warning" style="display:none;">
                        Aucune gare compatible n'est disponible pour l'instant (même destination, même heure, même jour, même localité).
                    </div>
                    <div id="transfertContenu" style="display:none;">
                        <p class="text-muted">Le transfert n'a lieu que si la gare choisie peut accueillir <strong>la totalité</strong> des passagers de votre gare. Votre voyage sera alors annulé et la recette transférée vers la caisse de la gare choisie.</p>
                        <div id="transfertListeGares"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>
    <!-- fin modal transfert -->

    <?php $this->view('admin/partials/foot') ?>

    <script>
        const csrfTokenTransfert = <?= json_encode(csrf_token()) ?>;

        $(document).ready(function() {
            $('.transfer-btn').click(function(e) {
                e.preventDefault();
                const idProgrammation = $(this).data('id-programmation');

                $('#transfertLoading').show();
                $('#transfertContenu').hide();
                $('#transfertAucune').hide();
                $('#transfertListeGares').empty();

                $.ajax({
                    url: '<?= BASE_URL ?>/admin/Transferts_gares/candidats/' + idProgrammation,
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        $('#transfertLoading').hide();

                        if (response.error || !response.gares || response.gares.length === 0) {
                            $('#transfertAucune').show();
                            return;
                        }

                        let html = '';
                        response.gares.forEach(function(g) {
                            const a = g.apercu;
                            const possible = a && a.possible;
                            const montant = a ? Number(a.montant_total).toLocaleString('fr-FR') : '0';

                            html += '<div class="card mb-2">'
                                + '<div class="card-body d-flex justify-content-between align-items-center">'
                                + '<div>'
                                + '<strong>' + g.numeroGare + '</strong><br>'
                                + '<small>Places libres : ' + g.places_libres + '</small><br>'
                                + (a ? '<small>' + a.nombre_passagers + ' passager(s) à transférer — ' + montant + ' FCFA</small>' : '')
                                + (a && !possible ? '<br><small class="text-danger">Transfert impossible (places insuffisantes ou aucun passager à transférer)</small>' : '')
                                + '</div>'
                                + '<form method="post" action="<?= BASE_URL ?>/admin/Transferts_gares/executer">'
                                + '<input type="hidden" name="csrf_token" value="' + csrfTokenTransfert + '">'
                                + '<input type="hidden" name="id_programmation_source" value="' + idProgrammation + '">'
                                + '<input type="hidden" name="id_programmation_destination" value="' + g.id_programmation + '">'
                                + '<button type="submit" class="btn btn-sm btn-success"' + (possible ? '' : ' disabled') + '>Confirmer</button>'
                                + '</form>'
                                + '</div></div>';
                        });

                        $('#transfertListeGares').html(html);
                        $('#transfertContenu').show();
                    },
                    error: function() {
                        $('#transfertLoading').hide();
                        $('#transfertAucune').text('Erreur lors de la recherche des gares compatibles.').show();
                    }
                });
            });

            $('.desactiver-programmation-btn').click(function(e) {
                e.preventDefault();
                const url = $(this).data('url');
                const numeroCar = $(this).data('numero-car');

                Swal.fire({
                    title: 'Désactiver ce voyage ?',
                    text: `Le voyage du car n°${numeroCar} sera annulé et le car redeviendra disponible. Impossible si des places ont déjà été vendues dessus.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Oui, désactiver',
                    cancelButtonText: 'Annuler'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = url;
                    }
                });
            });
        });
    </script>

</body>

</html>