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
            <div class="page-breadcrumb d-flex flex-wrap align-items-center mb-4 p-2 rounded  ">
                <div class="breadcrumb-title pe-3 text-primary ">
                    <i class="bx bx-package me-1"></i> G-colis
                </div>
                <div class="ps-3 flex-grow-1">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 p-0">
                            <li class="breadcrumb-item">
                                <a href="javascript:;" class="text-decoration-none text-muted">
                                    <i class="bx bx-home-alt"></i>
                                </a>
                            </li>
                            <li class="breadcrumb-item active f text-dark" aria-current="page">
                                Enregistrement des colis
                            </li>
                        </ol>
                    </nav>
                </div>
                <div class="ms-auto d-flex gap-2">
                    <a href="<?= BASE_URL ?>/admin/Colis_prise_en_charges"
                        class="btn btn-sm btn-outline-primary rounded-pill shadow-sm">
                        <i class="bx bx-list-ul me-1"></i> Liste des colis
                    </a>
                    <a href="javascript:history.back()"
                        class="btn btn-sm btn-primary rounded-pill shadow-sm">
                        <i class="bx bx-left-arrow-alt"></i> Retour
                    </a>
                </div>
            </div>
            <!--end breadcrumb-->
            <div class="row">

                <div class="col-xxl-12">
                    <?php $this->view("admin/set_flash") ?>
                    <div class="col-xl-12 mx-auto">
                        <div id="stepper1" class="bs-stepper">
                            <div class="card border-top border-primary border-3">
                                <div class="card-header">
                                    <div class="d-lg-flex flex-lg-row align-items-lg-center justify-content-lg-between"
                                        role="tablist">

                                        <!-- Étape 1 : Expéditeur -->
                                        <div class="step" data-target="#test-l-1">
                                            <div class="step-trigger" role="tab" id="stepper1trigger1" aria-controls="test-l-1">
                                                <div class="bs-stepper-circle bg-primary text-white">
                                                    <i class="bx bx-user"></i>
                                                </div>
                                                <div>
                                                    <h5 class="mb-0 steper-title">Expéditeur</h5>
                                                    <p class="mb-0 steper-sub-title small text-muted">Infos expéditeur</p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="bs-stepper-line"></div>

                                        <!-- Étape 2 : Destinataire -->
                                        <div class="step" data-target="#test-l-2">
                                            <div class="step-trigger" role="tab" id="stepper1trigger2" aria-controls="test-l-2">
                                                <div class="bs-stepper-circle bg-primary text-white">
                                                    <i class="bx bx-user-check"></i>
                                                </div>
                                                <div>
                                                    <h5 class="mb-0 steper-title">Destinataire</h5>
                                                    <p class="mb-0 steper-sub-title small text-muted">Infos destinataire</p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="bs-stepper-line"></div>

                                        <!-- Étape 3 : Colis -->
                                        <div class="step" data-target="#test-l-4">
                                            <div class="step-trigger" role="tab" id="stepper1trigger4" aria-controls="test-l-4">
                                                <div class="bs-stepper-circle bg-primary text-white">
                                                    <i class="bx bx-package"></i>
                                                </div>
                                                <div>
                                                    <h5 class="mb-0 steper-title">Colis</h5>
                                                    <p class="mb-0 steper-sub-title small text-muted">Détails du colis</p>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>

                                <div class="card-body">

                                    <div class="bs-stepper-content">
                                        <form method="post" id="formStepper" onsubmit="return validerFormulaire();">
                                            <!-- Étape 1 -->
                                            <div id="test-l-1" role="tabpanel" class="bs-stepper-pane" aria-labelledby="stepper1trigger1">
                                                <div class="row g-3">
                                                    <div class="col-12 col-lg-6">
                                                        <div class="form-group">
                                                            <label for="expediteur" class="form-label">Expéditeur</label>
                                                            <input type="text" class="form-control" id="expediteur" name="expediteur" placeholder="Expéditeur" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-12 col-lg-6">
                                                        <div class="form-group">
                                                            <label for="numero_exp" class="form-label">
                                                                <i class="bx bxl-whatsapp text-success"></i> Numéro expéditeur (WhatsApp)
                                                            </label>
                                                            <input type="text" class="form-control" id="numero_exp" name="numero_exp"
                                                                placeholder="Numéro expéditeur" required oninput="verifierNumero(this, 'erreur_exp')">
                                                            <div id="erreur_exp" class="text-danger small mt-1"></div>
                                                        </div>
                                                    </div>
                                                    <div class="col-12">
                                                        <div class="d-flex align-items-center gap-3">
                                                            <button type="button" class="btn btn-primary px-4" onclick="validateStep1()">Next<i class='bx bx-right-arrow-alt ms-2'></i></button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Étape 2 -->
                                            <div id="test-l-2" role="tabpanel" class="bs-stepper-pane" aria-labelledby="stepper1trigger2">
                                                <div class="row g-3">
                                                    <div class="col-12 col-lg-6">
                                                        <div class="form-group">
                                                            <label for="destinataire" class="form-label">Destinataire</label>
                                                            <input type="text" class="form-control" id="destinataire" name="destinataire" placeholder="Destinataire" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-12 col-lg-6">
                                                        <div class="form-group">
                                                            <label for="numero_dest" class="form-label">
                                                                <i class="bx bxl-whatsapp text-success"></i> Numéro destinataire (WhatsApp)
                                                            </label>
                                                            <input type="text" class="form-control" id="numero_dest" name="numero_dest"
                                                                placeholder="Numéro destinataire" required oninput="verifierNumero(this, 'erreur_dest')">
                                                            <div id="erreur_dest" class="text-danger small mt-1"></div>
                                                        </div>
                                                    </div>
                                                    <div class="col-12">
                                                        <div class="d-flex align-items-center gap-3">
                                                            <button type="button" class="btn btn-outline-secondary px-4" onclick="stepper1.previous()"><i class='bx bx-left-arrow-alt me-2'></i>Previous</button>
                                                            <button type="button" class="btn btn-primary px-4" onclick="validateStep2()">Next<i class='bx bx-right-arrow-alt ms-2'></i></button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Étape 3 -->
                                            <div id="test-l-4" role="tabpanel" class="bs-stepper-pane" aria-labelledby="stepper1trigger4">
                                                <div class="row g-3">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="form-group mt-3">
                                                                <label class="form-label" for="nom_colis">Nom colis</label>
                                                                <input type="text" id="nom_colis" class="form-control" placeholder="Nom colis" name="nom_colis" required />
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="form-group mt-3">
                                                                <label class="form-label" for="nature">Nature</label>
                                                                <input type="text" id="nature" class="form-control" placeholder="Nature" name="nature" required />
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="form-group mt-3">
                                                                <label class="form-label" for="destination">Destination</label>
                                                                <select class="form-control" name="destination" id="destination" required>
                                                                    <option value="" disabled selected>Choisissez la destination</option>
                                                                    <?php foreach ($listes as $liste): ?>
                                                                        <?php if ($liste->idAgence != $_SESSION['id_agence']): ?>
                                                                            <option value="<?= htmlspecialchars($liste->idAgence); ?>">
                                                                                <?= htmlspecialchars($liste->localite . ' ( ' . $liste->numeroGare . ' )'); ?>
                                                                            </option>
                                                                    <?php endif;
                                                                    endforeach; ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="form-group mt-3">
                                                                <label class="form-label" for="valeur">Valeur</label>
                                                                <input type="number" class="form-control" id="valeur" name="valeur" min="0" required />
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-6 mt-3">
                                                            <label class="form-label" for="fraix_transaction">Frais transaction</label>
                                                            <input class="form-control" type="number" id="fraix_transaction" name="fraix_transaction" readonly min="0" required />
                                                            <div class="form-check mt-1">
                                                                <input class="form-check-input" type="checkbox" id="modifierFraisCheck">
                                                                <label class="form-check-label" for="modifierFraisCheck">Modifier manuellement les frais</label>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6 mt-3">
                                                            <label class="form-label" for="code_colis">Code colis</label>
                                                            <input class="form-control" type="text" id="code_colis" name="code_colis"
                                                                value="<?= $code_colis ?>" readonly />
                                                        </div>

                                                    </div>
                                                    <div class="col-12">
                                                        <div class="d-flex align-items-center gap-3">
                                                            <button type="button" class="btn btn-outline-secondary px-4" onclick="stepper1.previous()"><i class='bx bx-left-arrow-alt me-2'></i>Previous</button>

                                                            <?php if (($_SESSION['droit'] ?? null) !== 'PDG'): ?>
                                                            <button type="submit" class="btn btn-success rounded-pill shadow-sm" name="envoi">
                                                                <i class="bx bx-check me-1"></i> Enregistrer
                                                            </button>
                                                            <?php endif; ?>

                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>

                                    </div>

                                </div>
                            </div>
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
    <!-- JS -->
    <style>
        .input-error {
            border: 1px solid red;
        }

        .error-message {
            color: red;
            margin-top: 5px;
        }
    </style>
    <!-- Scripts de validation -->
    <script src="<?= ASSET_URL ?>/assets/js/scrip_validations.js?v=<?= @filemtime(ROOT . '/public/assets/js/scrip_validations.js') ?: time() ?>"></script>
    <script>

    </script>

</body>

</html>