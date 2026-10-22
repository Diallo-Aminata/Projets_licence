<?php $this->view('admin/partials/headers') ?>

<body>
    <!--start wrapper-->
    <div class="wrapper">
        <?php $this->view('admin/partials/navbar') ?>
        <?php $this->view('admin/partials/sidebar') ?>

        <!--start content-->
        <main class="page-content">
            <!--breadcrumb-->
            <div class="page-breadcrumb d-flex flex-wrap align-items-center mb-3">
                <div class="breadcrumb-title pe-3">Bagages</div>
                <div class="ps-3">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 p-0">
                            <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Ajouter des bagages</li>
                        </ol>
                    </nav>
                </div>
                <div class="ms-auto">
                    <div class="btn-group">
                        <a href="<?= BASE_URL ?>/admin/Liste_du_jours" class="btn btn-primary split-bg-primary"><i
                                class="fadeIn animated bx bx-left-arrow-alt"></i></a>
                    </div>
                </div>
            </div>
            <!--end breadcrumb-->

            <div class="row">
                <div class="col-xl-8 mx-auto">
                    <?php $this->view("admin/set_flash") ?>

                    <div class="card mb-3">
                        <div class="card-header px-4 py-3">
                            <h5 class="mb-0 text-primary">Billet n°<?= htmlspecialchars($billet->numeroBillets) ?></h5>
                        </div>
                        <div class="card-body">
                            <p class="mb-1"><strong>Client :</strong> <?= htmlspecialchars($billet->Client) ?></p>
                            <p class="mb-1"><strong>Trajet :</strong> <?= htmlspecialchars($billet->departId) ?> &rarr; <?= htmlspecialchars($billet->destinationId) ?></p>
                            <p class="mb-1"><strong>Voyage :</strong> <?= htmlspecialchars($billet->jourVoyage) ?> à <?= htmlspecialchars($billet->Heur_departs) ?></p>
                        </div>
                    </div>

                    <?php if (!empty($bagages)): ?>
                    <div class="card mb-3">
                        <div class="card-header px-4 py-3">
                            <h5 class="mb-0 text-primary">Bagages déjà enregistrés</h5>
                        </div>
                        <div class="card-body">
                            <table class="table">
                                <thead>
                                    <tr><th>Description</th><th>Poids (kg)</th><th>Prix (FCFA)</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bagages as $b): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($b['description'] ?? '-') ?></td>
                                        <td><?= $b['poids'] !== null ? htmlspecialchars($b['poids']) : '-' ?></td>
                                        <td><?= number_format((float)$b['prix'], 0, ',', ' ') ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr><th colspan="2">Total bagages</th><th><?= number_format((float)$totalBagages, 0, ',', ' ') ?> FCFA</th></tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="card">
                        <div class="card-header px-4 py-3">
                            <h5 class="mb-0 text-primary">Ajouter un ou plusieurs bagages</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <?= csrf_field() ?>
                                <div id="bagagesRows">
                                    <div class="row align-items-end mb-2 bagage-row">
                                        <div class="col-md-5">
                                            <label class="form-label">Description</label>
                                            <input type="text" class="form-control" placeholder="Ex: 1 valise" name="bagage_description[]" autocomplete="off">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Poids (kg)</label>
                                            <input type="number" step="0.1" min="0" class="form-control" placeholder="Poids" name="bagage_poids[]" autocomplete="off">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Prix (FCFA) <span class="text-danger">*</span></label>
                                            <input type="number" min="0" class="form-control" placeholder="Prix" name="bagage_prix[]" autocomplete="off">
                                        </div>
                                        <div class="col-md-1">
                                            <button type="button" class="btn btn-outline-danger remove-bagage-btn d-none w-100" title="Retirer cette ligne">
                                                <i class="bx bx-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" id="addBagageRow" class="btn btn-sm btn-outline-primary mb-3">
                                    <i class="bx bx-plus"></i> Ajouter une ligne
                                </button>
                                <div>
                                    <button type="submit" name="save" class="btn btn-primary px-4">Enregistrer</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <!--end row-->
        </main>
        <!--end page main-->

        <div class="overlay nav-toggle-icon"></div>
        <a href="javaScript:;" class="back-to-top"><i class='bx bxs-up-arrow-alt'></i></a>
    </div>
    <!--end wrapper-->

    <?php $this->view('admin/partials/foot') ?>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const rowsContainer = document.getElementById("bagagesRows");
            const addBtn = document.getElementById("addBagageRow");

            function toggleRemoveButtons() {
                const rows = rowsContainer.querySelectorAll(".bagage-row");
                rows.forEach(function(row) {
                    row.querySelector(".remove-bagage-btn").classList.toggle("d-none", rows.length <= 1);
                });
            }

            addBtn.addEventListener("click", function() {
                const firstRow = rowsContainer.querySelector(".bagage-row");
                const newRow = firstRow.cloneNode(true);
                newRow.querySelectorAll("input").forEach(function(input) { input.value = ""; });
                rowsContainer.appendChild(newRow);
                toggleRemoveButtons();
            });

            rowsContainer.addEventListener("click", function(e) {
                const btn = e.target.closest(".remove-bagage-btn");
                if (btn) {
                    btn.closest(".bagage-row").remove();
                    toggleRemoveButtons();
                }
            });
        });
    </script>
</body>

</html>
