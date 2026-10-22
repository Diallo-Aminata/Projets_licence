<?php $this->view('admin/partials/header') ?>
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
            <div class="page-breadcrumb d-flex flex-wrap align-items-center mb-3">
                <div class="breadcrumb-title pe-3">Caisse</div>
                <div class="ps-3">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 p-0">
                            <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a></li>
                            <li class="breadcrumb-item active" aria-current="page">Bordereaux d'escale</li>
                        </ol>
                    </nav>
                </div>
                <div class="ms-auto">
                    <div class="d-flex gap-2">
                        <a href="<?= BASE_URL ?>/admin/Bordereau_escales/telecharger_modele"
                            class="btn btn-outline-primary d-flex align-items-center gap-2 shadow-sm">
                            <i class="bx bx-download fs-5"></i> Télécharger le modèle
                        </a>
                        <a href="<?= BASE_URL ?>/admin/Bordereau_escales/ajouter"
                            class="btn btn-success d-flex align-items-center gap-2 shadow-sm">
                            <i class="bx bx-plus-circle fs-5"></i> Nouveau bordereau
                        </a>
                    </div>
                </div>
            </div>
            <!--end breadcrumb-->

            <div class="row">
                <div class="col-12">
                    <?php $this->view("admin/set_flash") ?>

                    <div class="card config-card">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h5 class="mb-0 fw-bold"><i class="bx bx-file me-2"></i>Bordereaux d'escale saisis</h5>
                            <form method="get" class="d-flex gap-2 flex-wrap">
                                <?php if ($estAdmin): ?>
                                    <select name="id_agence" class="form-select form-select-sm" onchange="this.form.submit()">
                                        <option value="">Toutes les gares</option>
                                        <?php foreach ($listeAgences as $a): ?>
                                            <option value="<?= htmlspecialchars($a->idAgence) ?>" <?= (int)$a->idAgence === $idAgenceSelectionnee ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($a->localite . ' (' . $a->numeroGare . ')') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>
                                <input type="date" name="date" class="form-control form-control-sm" value="<?= htmlspecialchars($date ?? '') ?>" max="<?= date('Y-m-d') ?>">
                                <button type="submit" class="btn btn-primary btn-sm">Filtrer</button>
                            </form>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered table-hover-effect table-custom-header text-center mobile-card-table" style="width:100%">
                                    <thead class="table-light text-center">
                                        <tr>
                                            <th>Date</th>
                                            <th>Gare</th>
                                            <th>Escale</th>
                                            <th>N° Bordereau</th>
                                            <th>Bus</th>
                                            <th>Billets + bagages</th>
                                            <th>Écart</th>
                                            <th>Saisi par</th>
                                            <th>Caisse</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($bordereaux)): ?>
                                            <tr><td colspan="10" class="text-center text-muted py-4">Aucun bordereau saisi pour ces critères.</td></tr>
                                        <?php endif; ?>
                                        <?php foreach ($bordereaux as $b): ?>
                                            <tr>
                                                <td data-label="Date"><?= date('d/m/Y', strtotime($b['date_escale'])) ?></td>
                                                <td data-label="Gare"><?= htmlspecialchars(($b['localite'] ?? '-') . ' (' . ($b['numeroGare'] ?? '-') . ')') ?></td>
                                                <td data-label="Escale"><?= htmlspecialchars($b['escale_libelle'] ?? '-') ?></td>
                                                <td data-label="N° Bordereau"><?= htmlspecialchars($b['numero_bordereau'] ?? '-') ?></td>
                                                <td data-label="Bus"><?= htmlspecialchars($b['numero_bus'] ?? '-') ?></td>
                                                <td data-label="Billets + bagages"><?= number_format((float)$b['total_billets'] + (float)$b['total_bagages'], 0, ',', ' ') ?></td>
                                                <td data-label="Écart" class="<?= (float)($b['ecart'] ?? 0) < 0 ? 'text-danger' : 'text-success' ?>">
                                                    <?= isset($b['ecart']) ? number_format((float)$b['ecart'], 0, ',', ' ') : '-' ?>
                                                </td>
                                                <td data-label="Saisi par"><?= htmlspecialchars($b['nom_saisi_par'] ?? '-') ?></td>
                                                <td data-label="Caisse">
                                                    <?php if (($b['statut_caisse'] ?? null) === 'versee'): ?>
                                                        <span class="badge bg-success">Versée</span>
                                                    <?php elseif (($b['statut_caisse'] ?? null) === 'fermee'): ?>
                                                        <span class="badge bg-warning text-dark">À verser</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">—</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td data-label="Action">
                                                    <a href="<?= BASE_URL ?>/admin/Bordereau_escales/detail/<?= $b['id_bordereau'] ?>" class="btn btn-sm btn-outline-primary">Détail</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
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
</body>

</html>
