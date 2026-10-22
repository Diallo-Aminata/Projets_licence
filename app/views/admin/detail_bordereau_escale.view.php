<?php $this->view('admin/partials/headers') ?>

<body>
<div class="wrapper">
    <?php $this->view('admin/partials/navbar') ?>
    <?php $this->view('admin/partials/sidebar') ?>

    <main class="page-content">
        <div class="page-breadcrumb d-flex flex-wrap align-items-center mb-3">
            <div class="breadcrumb-title pe-3">Bordereau d'escale</div>
            <div class="ps-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/Bordereau_escales/index">Bordereaux</a></li>
                        <li class="breadcrumb-item active">N° <?= htmlspecialchars($bordereau['numero_bordereau'] ?? $bordereau['id_bordereau']) ?></li>
                    </ol>
                </nav>
            </div>
            <div class="ms-auto">
                <a href="<?= BASE_URL ?>/admin/Bordereau_escales/index" class="btn btn-primary split-bg-primary"><i class="fadeIn animated bx bx-left-arrow-alt"></i></a>
            </div>
        </div>

        <?php $this->view("admin/set_flash") ?>

        <div class="row">
            <div class="col-xl-8 mx-auto">

                <div class="card mb-3">
                    <div class="card-header px-4 py-3"><h5 class="mb-0 text-primary">Informations</h5></div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-4"><strong>Date :</strong> <?= date('d/m/Y', strtotime($bordereau['date_escale'])) ?></div>
                            <div class="col-md-4"><strong>Bus N° :</strong> <?= htmlspecialchars($bordereau['numero_bus'] ?? '-') ?></div>
                            <div class="col-md-4"><strong>N° Bordereau :</strong> <?= htmlspecialchars($bordereau['numero_bordereau'] ?? '-') ?></div>
                            <div class="col-md-4"><strong>Gare de rattachement :</strong> <?= htmlspecialchars(($bordereau['localite'] ?? '-') . ' (' . ($bordereau['numeroGare'] ?? '-') . ')') ?></div>
                            <div class="col-md-4"><strong>Escale :</strong> <?= htmlspecialchars($bordereau['escale_libelle'] ?? '-') ?></div>
                            <div class="col-md-4"><strong>Chef / agent d'escale :</strong> <?= htmlspecialchars($bordereau['nom_agent_escale'] ?? '-') ?></div>
                            <div class="col-md-4"><strong>Saisi par :</strong> <?= htmlspecialchars($bordereau['nom_saisi_par'] ?? '-') ?></div>
                            <div class="col-md-4"><strong>Saisi le :</strong> <?= date('d/m/Y H:i', strtotime($bordereau['date_saisie'])) ?></div>
                            <div class="col-md-4">
                                <strong>Caisse :</strong>
                                <?php if (($bordereau['statut_caisse'] ?? null) === 'versee'): ?>
                                    <span class="badge bg-success">Versée</span>
                                <?php elseif (($bordereau['statut_caisse'] ?? null) === 'fermee'): ?>
                                    <span class="badge bg-warning text-dark">À verser</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">—</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if (($bordereau['statut_caisse'] ?? null) === 'fermee' && (int)($bordereau['id_utilisateur_caisse'] ?? 0) === (int)($_SESSION['id_utilisateur'] ?? 0)): ?>
                            <div class="alert alert-info mt-3 mb-0">
                                Cette caisse est encore à verser.
                                <a href="<?= BASE_URL ?>/admin/Caisse/ma_caisse" class="alert-link">Aller sur « Ma Caisse »</a> pour procéder au versement.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header px-4 py-3"><h5 class="mb-0 text-primary">Billets vendus</h5></div>
                    <div class="card-body p-0">
                        <table class="table mb-0">
                            <thead><tr><th>Destination</th><th>Nb billets</th><th>Prix unit.</th><th class="text-end">Montant</th></tr></thead>
                            <tbody>
                                <?php $aBillets = false; foreach ($bordereau['lignes'] as $l): if ($l['type_ligne'] !== 'billet') continue; $aBillets = true; ?>
                                    <tr>
                                        <td><?= htmlspecialchars($l['destination']) ?></td>
                                        <td><?= (int)$l['nombre'] ?></td>
                                        <td><?= number_format((float)$l['prix_unitaire'], 0, ',', ' ') ?></td>
                                        <td class="text-end"><?= number_format((float)$l['montant'], 0, ',', ' ') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$aBillets): ?><tr><td colspan="4" class="text-center text-muted">Aucune ligne.</td></tr><?php endif; ?>
                            </tbody>
                            <tfoot><tr><th colspan="3">Sous-total</th><th class="text-end"><?= number_format((float)$bordereau['total_billets'], 0, ',', ' ') ?></th></tr></tfoot>
                        </table>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header px-4 py-3"><h5 class="mb-0 text-primary">Bagages payants</h5></div>
                    <div class="card-body p-0">
                        <table class="table mb-0">
                            <thead><tr><th>Description</th><th class="text-end">Montant</th></tr></thead>
                            <tbody>
                                <?php $aBagages = false; foreach ($bordereau['lignes'] as $l): if ($l['type_ligne'] !== 'bagage') continue; $aBagages = true; ?>
                                    <tr>
                                        <td><?= htmlspecialchars($l['description'] ?? '-') ?></td>
                                        <td class="text-end"><?= number_format((float)$l['montant'], 0, ',', ' ') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$aBagages): ?><tr><td colspan="2" class="text-center text-muted">Aucune ligne.</td></tr><?php endif; ?>
                            </tbody>
                            <tfoot><tr><th>Sous-total</th><th class="text-end"><?= number_format((float)$bordereau['total_bagages'], 0, ',', ' ') ?></th></tr></tfoot>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header px-4 py-3"><h5 class="mb-0 text-primary">Récapitulatif</h5></div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-6"><strong>Dépenses :</strong> <?= number_format((float)$bordereau['montant_depenses'], 0, ',', ' ') ?> FCFA</div>
                            <div class="col-md-6"><strong>Commission agent d'escale :</strong> <?= number_format((float)$bordereau['montant_commission'], 0, ',', ' ') ?> FCFA</div>
                            <div class="col-md-6"><strong>Total versé (saisi) :</strong> <?= number_format((float)$bordereau['total_verse_saisi'], 0, ',', ' ') ?> FCFA</div>
                            <div class="col-md-6">
                                <strong>Écart système :</strong>
                                <span class="<?= (float)($bordereau['ecart'] ?? 0) < 0 ? 'text-danger' : 'text-success' ?>">
                                    <?= isset($bordereau['ecart']) ? number_format((float)$bordereau['ecart'], 0, ',', ' ') . ' FCFA' : '-' ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <div class="overlay nav-toggle-icon"></div>
    <a href="javaScript:;" class="back-to-top"><i class='bx bxs-up-arrow-alt'></i></a>
</div>

<?php $this->view('admin/partials/foot') ?>
</body>

</html>
