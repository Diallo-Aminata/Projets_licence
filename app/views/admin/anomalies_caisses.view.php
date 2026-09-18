<?php $this->view('admin/partials/headers') ?>

<body>
<div class="wrapper">
    <?php $this->view('admin/partials/navbar') ?>
    <?php $this->view('admin/partials/sidebar') ?>

    <main class="page-content">

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1"><i class="bx bx-error-circle text-danger me-1"></i>Anomalies de caisse</h4>
                <p class="text-muted small mb-0">Caisses jamais fermées ou fermées-non-versées d'un jour précédent, toutes gares confondues.</p>
            </div>
            <span class="badge bg-danger rounded-pill px-3 py-2"><?= count($anomalies) ?> anomalie(s)</span>
        </div>

        <?php $this->view("admin/set_flash") ?>

        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-0">
                <?php if (empty($anomalies)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="bx bx-check-shield" style="font-size:3rem;"></i>
                        <p class="mt-2 mb-0">Aucune anomalie : toutes les caisses des jours précédents ont été fermées et versées.</p>
                    </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-hover-effect table-custom-header mobile-card-table" style="width:100%">
                        <thead class="table-light text-uppercase small text-center">
                            <tr>
                                <th>Gare</th>
                                <th>Opérateur</th>
                                <th>Rôle</th>
                                <th>Date</th>
                                <th>Ancienneté</th>
                                <th class="text-end">Billets</th>
                                <th class="text-end">Colis</th>
                                <th>Statut</th>
                                <th>Détail</th>
                            </tr>
                        </thead>
                        <tbody class="text-center">
                        <?php foreach ($anomalies as $a): ?>
                            <tr>
                                <td data-label="Gare"><?= htmlspecialchars($a->localite . ' (' . $a->numeroGare . ')') ?></td>
                                <td data-label="Opérateur" class="fw-semibold"><?= htmlspecialchars($a->nom_operateur) ?></td>
                                <td data-label="Rôle"><span class="badge bg-light text-dark border"><?= htmlspecialchars($a->droit) ?></span></td>
                                <td data-label="Date"><?= date('d/m/Y', strtotime($a->date_service)) ?></td>
                                <td data-label="Ancienneté">
                                    <span class="badge <?= $a->jours_ecoules > 3 ? 'bg-danger' : 'bg-warning text-dark' ?>">
                                        <?= (int)$a->jours_ecoules ?> jour<?= $a->jours_ecoules > 1 ? 's' : '' ?>
                                    </span>
                                </td>
                                <td data-label="Billets" class="text-end"><?= number_format((float)$a->total_billets, 0, ',', ' ') ?></td>
                                <td data-label="Colis" class="text-end"><?= number_format((float)$a->total_colis, 0, ',', ' ') ?></td>
                                <td data-label="Statut">
                                    <?php if ($a->statut === 'ouverte'): ?>
                                        <span class="badge bg-success">Jamais fermée</span>
                                    <?php elseif (($a->statut_versement ?? null) === 'en_attente'): ?>
                                        <span class="badge bg-info-subtle text-info-emphasis">Versement en attente</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Fermée, non versée</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Détail">
                                    <a href="<?= BASE_URL ?>/admin/Caisse/caisses_escale?<?= http_build_query(['id_agence' => $a->id_agence, 'date' => $a->date_service]) ?>"
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bx bx-search-alt me-1"></i>Voir la gare ce jour-là
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <p class="text-muted small mt-3">
            <i class="bx bx-info-circle me-1"></i>Vue en lecture seule : seul le titulaire d'une caisse peut la fermer ou la verser
            (depuis son propre écran "Ma Caisse" &gt; Caisses anciennes).
        </p>

    </main>
</div>

<?php $this->view('admin/partials/foot') ?>
</body>
</html>
