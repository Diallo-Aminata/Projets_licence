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
            <div class="page-breadcrumb d-flex flex-wrap align-items-center mb-3">
                <div class="breadcrumb-title pe-3">G-reservation</div>
                <div class="ps-3 d-none d-sm-block">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 p-0">
                            <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a>
                            </li>
                            <li class="breadcrumb-item active text-primary" aria-current="page">Historique des colis</li>
                        </ol>
                    </nav>
                </div>
                <div class="ms-sm-auto mt-2 mt-sm-0">
                    <div class="btn-group">
                        <a href="<?= BASE_URL ?>/Envoi_colis/liste_colis_envoyer" class="btn btn-primary split-bg-primary text-white"> Voir la liste</a> &nbsp;
                        <a href="javascript:history.back()" class="btn btn-primary "><i
                                class="fadeIn animated bx bx-left-arrow-alt"></i></a>
                    </div>
                </div>
            </div>
            <!--end breadcrumb-->
            <?php $this->view("admin/set_flash") ?>
            <div class="row">
                <div class="card border-top border-primary border-1">
                    <div class="bg-light border-bottom rounded-top px-3 py-2 d-flex align-items-center mb-0 mt-1" style="gap:8px;">
                        <i class="bx bx-filter-alt text-primary" style="font-size:1.3rem;"></i>
                        <h6 class="mb-0 fw-bold text-primary" style="letter-spacing:1px;">Filtrage</h6>
                    </div>

                    <div class="card-body p-4 border-1">
                        <div class="row">
                            <div class="col-md-4">
                                <label for="destination" class="form-label">Jour du voyage</label>
                                <input type="date" class="form-control" id="jourVoyage" name="jourVoyage"
                                    required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Heure de départ</label>
                                <select class="single-select" id="selectheure" name="selectheure">
                                    <option value="">Toutes les heures</option>
                                    <?php foreach ($liste_horaire as $liste_horaires): ?>
                                        <option value="<?= $liste_horaires->heuredepart ?>"><?= $liste_horaires->heuredepart ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Destination</label>
                                <select class="form-select" id="id_destination" name="id_destination">
                                    <option value="">Toutes les destinations</option>
                                    <?php if (!empty($destinations) && is_array($destinations)): ?>
                                        <?php foreach ($destinations as $destination): ?>
                                            <?php if (is_array($destination) && isset($destination['idDestination'])): ?>
                                                <option value="<?= htmlspecialchars(trim($destination['idDestination'])) ?>">
                                                    <?= htmlspecialchars(trim($destination['idDestination'])) ?>
                                                </option>
                                            <?php endif ?>
                                        <?php endforeach ?>
                                    <?php endif ?>
                                </select>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="card">
                    <div class="card-body">

                        <div class="tab-content py-3 table-responsive">
                            <table id="example" class="table table-striped table-bordered mobile-card-table" style="width:100%">
                                <thead>
                                    <tr class="text-center">
                                        <th>Client</th>
                                        <th>Destionation</th>
                                        <th>Nbr de passage</th>
                                        <th>Jour de voyage</th>
                                        <th>heure de depart</th>
                                        <th>Date expiration</th>

                                    </tr>
                                </thead>
                                <tbody id="tableClient">

                                </tbody>
                            </table>


                        </div>
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


    <?php $this->view('admin/partials/foot') ?>
    <script>
        $(document).ready(function() {
            $('#selectheure, #id_destination, #jourVoyage').change(function() {
                const selectheure = $('#selectheure').val();
                const id_destination = $('#id_destination').val();
                const jourVoyage = $('#jourVoyage').val();


                if (selectheure && id_destination && jourVoyage) {
                    $.ajax({
                        url: '<?= BASE_URL ?>/admin/AjaxFiltreHistorique', // Contrôleur AJAX MVC
                        type: 'POST',
                        data: {
                            selectheure: selectheure,
                            id_destination: id_destination,
                            jourVoyage: jourVoyage
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.error) {
                                Swal.fire('Erreur', response.error, 'error');
                            } else {
                                $('#tableClient').html(response.tbody);
                            }
                        },
                        error: function(xhr, status, error) {
                            Swal.fire('Erreur AJAX', xhr.responseText, 'error');
                        }
                    });
                }
            });
        });

 
    </script>
</body>

</html>