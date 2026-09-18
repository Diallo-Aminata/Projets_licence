<?php $this->view('admin/partials/headers') ?>
<?php $user = new Configuration($_SESSION['id_utilisateur']) ?>

<body>
<div class="wrapper">
    <?php $this->view('admin/partials/navbar') ?>
    <?php $this->view('admin/partials/sidebar') ?>

    <main class="page-content">

        <!-- En-tête -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1"><i class="bx bx-wallet text-primary me-1"></i>Ma Caisse</h4>
                <p class="text-muted small mb-0"><?= htmlspecialchars($_SESSION['ville'] ?? '') ?> – <?= htmlspecialchars($_SESSION['utilisateurs'] ?? '') ?></p>
            </div>
            <div class="d-flex gap-2 flex-wrap mt-2 mt-sm-0">
                <?php if ($caisse): ?>
                    <a href="<?= BASE_URL ?>/admin/Caisse/fermer_caisse_user" class="btn btn-warning btn-sm rounded-pill px-3">
                        <i class="bx bx-lock-alt me-1"></i>Fermer ma caisse
                    </a>
                <?php elseif ($caisseFermee && ($caisseFermee->statut_versement ?? null) === 'en_attente'): ?>
                    <span class="badge bg-info-subtle text-info-emphasis rounded-pill px-3 py-2">
                        <i class="bx bx-time me-1"></i>Versement en attente de validation
                    </span>
                <?php elseif ($caisseFermee): ?>
                    <a href="<?= BASE_URL ?>/admin/Caisse/verser" class="btn btn-primary btn-sm rounded-pill px-3">
                        <i class="bx bx-transfer me-1"></i>Verser
                    </a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/admin/Caisse/ouvrir_caisse_user" class="btn btn-success btn-sm rounded-pill px-3">
                        <i class="bx bx-lock-open-alt me-1"></i>Ouvrir ma caisse
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php $this->view("admin/set_flash") ?>

        <?php if ($caisseFermee && ($caisseFermee->statut_versement ?? null) === 'en_attente'): ?>
        <!-- Caisse fermée, versement deja soumis, en attente de validation du chef d'escale -->
        <div class="card border-0 shadow-sm rounded-3 mb-4 text-center py-5">
            <div class="text-info"><i class="bx bx-time-five" style="font-size:4rem;"></i></div>
            <h5 class="mt-2 fw-bold">Versement en attente de validation</h5>
            <p class="text-muted mb-1">Réf : <strong><?= htmlspecialchars($caisseFermee->reference) ?></strong></p>
            <p class="text-muted">Montant compté : <strong class="text-dark"><?= number_format((float)$caisseFermee->montant_compte, 0, ',', ' ') ?> FCFA</strong></p>
            <p class="text-muted small mb-0">Le chef d'escale doit encore valider ce versement avant qu'il ne soit définitif.</p>
        </div>
        <?php elseif ($caisseFermee): ?>
        <!-- Caisse fermée, en attente de versement -->
        <div class="card border-0 shadow-sm rounded-3 mb-4 text-center py-5">
            <div class="text-warning"><i class="bx bx-lock-alt" style="font-size:4rem;"></i></div>
            <h5 class="mt-2 fw-bold">Caisse fermée — en attente de versement</h5>
            <p class="text-muted mb-1">Réf : <strong><?= htmlspecialchars($caisseFermee->reference) ?></strong></p>
            <p class="text-muted">Montant compté : <strong class="text-dark"><?= number_format((float)$caisseFermee->montant_compte, 0, ',', ' ') ?> FCFA</strong></p>
            <a href="<?= BASE_URL ?>/admin/Caisse/verser" class="btn btn-primary rounded-pill px-4 mx-auto" style="width:fit-content;">
                <i class="bx bx-transfer me-1"></i>Verser au chef d'escale
            </a>
        </div>
        <?php elseif ($caisse): ?>
        <!-- KPI Caisse ouverte -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-3 text-center py-3">
                    <div class="fs-1 fw-bold text-success"><?= number_format((float)$caisse->total_billets, 0, ',', ' ') ?></div>
                    <div class="text-muted small">Billets (FCFA)</div>
                    <div class="text-secondary small"><?= $caisse->nb_billets ?> vente(s)</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-3 text-center py-3">
                    <div class="fs-1 fw-bold text-info"><?= number_format((float)$caisse->total_colis, 0, ',', ' ') ?></div>
                    <div class="text-muted small">Colis (FCFA)</div>
                    <div class="text-secondary small"><?= $caisse->nb_colis ?> colis</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-3 text-center py-3">
                    <?php $total = (float)$caisse->montant_initial + (float)$caisse->total_billets + (float)$caisse->total_colis; ?>
                    <div class="fs-1 fw-bold text-primary"><?= number_format($total, 0, ',', ' ') ?></div>
                    <div class="text-muted small">Solde actuel (FCFA)</div>
                    <div class="text-secondary small">Initial : <?= number_format((float)$caisse->montant_initial, 0, ',', ' ') ?></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-3 text-center py-3">
                    <div class="fs-3 fw-bold <?= ($caisse->statut === 'ouverte') ? 'text-success' : 'text-danger' ?>">
                        <?= ($caisse->statut === 'ouverte') ? '● Ouverte' : '● Fermée' ?>
                    </div>
                    <div class="text-muted small">Réf : <?= htmlspecialchars($caisse->reference) ?></div>
                    <div class="text-secondary small">Ouvert : <?= date('H:i', strtotime($caisse->heure_ouverture)) ?></div>
                </div>
            </div>
        </div>

        <!-- Journal des opérations -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="bx bx-receipt me-2"></i>Journal des opérations du jour</h6>
                <span class="badge bg-primary"><?= count($journal) ?> opération(s)</span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($journal)): ?>
                    <div class="text-center text-muted py-4">Aucune opération enregistrée.</div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-hover-effect table-custom-header mobile-card-table" style="width:100%">
                        <thead class="table-light text-uppercase small text-center">
                            <tr>
                                <th>Heure</th>
                                <th>Type</th>
                                <th>Référence</th>
                                <th>Libellé</th>
                                <th class="text-end">Montant</th>
                            </tr>
                        </thead>
                        <tbody class="text-center">
                        <?php foreach ($journal as $j): ?>
                            <?php
                                $icons = [
                                    'ouverture'   => 'bx-lock-open-alt text-success',
                                    'billet'      => 'bx-purchase-tag text-primary',
                                    'colis'       => 'bx-package text-info',
                                    'fermeture'   => 'bx-lock-alt text-warning',
                                    'versement'   => 'bx-transfer text-primary',
                                    'annulation'  => 'bx-x-circle text-danger',
                                ];
                                $icon = $icons[$j->type_operation] ?? 'bx-dots-horizontal-rounded text-muted';
                            ?>
                            <tr>
                                <td data-label="Heure" class="small text-muted"><?= date('H:i:s', strtotime($j->date_heure)) ?></td>
                                <td data-label="Type"><span class="badge bg-light text-dark border"><i class="bx <?= $icon ?> me-1"></i><?= ucfirst($j->type_operation) ?></span></td>
                                <td data-label="Référence" class="small fw-semibold"><?= htmlspecialchars($j->reference_op ?? '—') ?></td>
                                <td data-label="Libellé" class="small"><?= htmlspecialchars($j->libelle ?? '') ?></td>
                                <td data-label="Montant" class="text-end fw-bold <?= $j->montant > 0 ? 'text-success' : 'text-muted' ?>">
                                    <?= $j->montant > 0 ? '+' : '' ?><?= number_format((float)$j->montant, 0, ',', ' ') ?> FCFA
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php else: ?>
        <!-- Pas de caisse ouverte -->
        <div class="card border-0 shadow-sm rounded-3 mb-4 text-center py-5">
            <div class="text-muted"><i class="bx bx-wallet" style="font-size:4rem;"></i></div>
            <h5 class="mt-2 fw-bold">Aucune caisse ouverte aujourd'hui</h5>
            <p class="text-muted">Ouvrez votre caisse pour commencer à enregistrer des ventes.</p>
            <a href="<?= BASE_URL ?>/admin/Caisse/ouvrir_caisse_user" class="btn btn-success rounded-pill px-4 mx-auto" style="width:fit-content;">
                <i class="bx bx-lock-open-alt me-1"></i>Ouvrir ma caisse
            </a>
        </div>
        <?php endif; ?>

        <!-- Caisses anciennes (oubliées) : jamais fermées ou fermées-non-versées d'un jour précédent -->
        <?php if (!empty($caissesAnciennes)): ?>
        <div class="card border-0 shadow-sm rounded-3 mb-4 border-start border-4 border-danger">
            <div class="card-header bg-danger-subtle">
                <h6 class="fw-bold mb-0 text-danger"><i class="bx bx-error-circle me-2"></i>Caisses anciennes à régulariser</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-hover-effect table-custom-header mobile-card-table" style="width:100%">
                        <thead class="table-light text-uppercase small text-center">
                            <tr>
                                <th>Date</th>
                                <th>Réf</th>
                                <th class="text-end">Billets</th>
                                <th class="text-end">Colis</th>
                                <th>Statut</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody class="text-center">
                        <?php foreach ($caissesAnciennes as $a): ?>
                            <tr>
                                <td data-label="Date"><?= date('d/m/Y', strtotime($a->date_service)) ?></td>
                                <td data-label="Réf" class="fw-semibold"><?= htmlspecialchars($a->reference) ?></td>
                                <td data-label="Billets" class="text-end"><?= number_format((float)$a->total_billets, 0, ',', ' ') ?></td>
                                <td data-label="Colis" class="text-end"><?= number_format((float)$a->total_colis, 0, ',', ' ') ?></td>
                                <td data-label="Statut">
                                    <?php if ($a->statut === 'ouverte'): ?>
                                        <span class="badge bg-success">Ouverte</span>
                                    <?php elseif (($a->statut_versement ?? null) === 'en_attente'): ?>
                                        <span class="badge bg-info-subtle text-info-emphasis">Versement en attente</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Fermée</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Action">
                                    <?php if ($a->statut === 'ouverte'): ?>
                                        <a href="<?= BASE_URL ?>/admin/Caisse/fermer_caisse_user/<?= $a->id_caisse_user ?>"
                                           class="btn btn-sm btn-warning">
                                            <i class="bx bx-lock-alt me-1"></i>Fermer
                                        </a>
                                    <?php elseif (($a->statut_versement ?? null) !== 'en_attente'): ?>
                                        <a href="<?= BASE_URL ?>/admin/Caisse/verser/<?= $a->id_caisse_user ?>"
                                           class="btn btn-sm btn-primary">
                                            <i class="bx bx-transfer me-1"></i>Verser
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Historique des caisses -->
        <?php if (!empty($historique)): ?>
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header">
                <h6 class="fw-bold mb-0"><i class="bx bx-history me-2"></i>Historique de mes caisses</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-hover-effect table-custom-header mobile-card-table" style="width:100%">
                        <thead class="table-light text-uppercase small text-center">
                            <tr>
                                <th>Date</th>
                                <th>Réf</th>
                                <th class="text-end">Billets</th>
                                <th class="text-end">Colis</th>
                                <th class="text-end">Attendu</th>
                                <th class="text-end">Compté</th>
                                <th class="text-end">Écart</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody class="text-center">
                        <?php foreach ($historique as $h): ?>
                            <?php
                                $attendu = (float)$h->montant_initial + (float)$h->total_billets + (float)$h->total_colis;
                                $ecart   = isset($h->ecart) ? (float)$h->ecart : null;
                                $statuts = ['ouverte'=>['bg-success','Ouverte'],'fermee'=>['bg-secondary','Fermée'],'versee'=>['bg-primary','Versée']];
                                [$bg, $libStatut] = $statuts[$h->statut] ?? ['bg-light','—'];
                            ?>
                            <tr>
                                <td data-label="Date"><?= date('d/m/Y', strtotime($h->date_service)) ?></td>
                                <td data-label="Réf" class="fw-semibold"><?= htmlspecialchars($h->reference) ?></td>
                                <td data-label="Billets" class="text-end"><?= number_format((float)$h->total_billets, 0, ',', ' ') ?></td>
                                <td data-label="Colis" class="text-end"><?= number_format((float)$h->total_colis, 0, ',', ' ') ?></td>
                                <td data-label="Attendu" class="text-end"><?= number_format($attendu, 0, ',', ' ') ?></td>
                                <td data-label="Compté" class="text-end"><?= isset($h->montant_compte) ? number_format((float)$h->montant_compte, 0, ',', ' ') : '—' ?></td>
                                <td data-label="Écart" class="text-end <?= ($ecart === null) ? '' : ($ecart >= 0 ? 'text-success' : 'text-danger') ?>">
                                    <?= ($ecart !== null) ? (($ecart >= 0 ? '+' : '') . number_format($ecart, 0, ',', ' ')) : '—' ?>
                                </td>
                                <td data-label="Statut"><span class="badge <?= $bg ?>"><?= $libStatut ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </main>
</div>

<?php $this->view('admin/partials/foot') ?>
</body>
</html>
