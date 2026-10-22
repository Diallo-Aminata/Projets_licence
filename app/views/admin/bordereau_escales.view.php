<?php $this->view('admin/partials/headers') ?>

<body>
<div class="wrapper">
    <?php $this->view('admin/partials/navbar') ?>
    <?php $this->view('admin/partials/sidebar') ?>

    <main class="page-content">

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1"><i class="bx bx-file text-primary me-1"></i>Bordereaux d'escale</h4>
                <p class="text-muted small mb-0">Saisie des bordereaux papier des escales sans réseau.</p>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <form method="get" class="d-flex gap-2 flex-wrap">
                    <?php if ($estAdmin): ?>
                        <select name="id_agence" class="form-select form-select-sm rounded-pill px-3" onchange="this.form.submit()">
                            <option value="">Toutes les gares</option>
                            <?php foreach ($listeAgences as $a): ?>
                                <option value="<?= htmlspecialchars($a->idAgence) ?>" <?= (int)$a->idAgence === $idAgenceSelectionnee ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($a->localite . ' (' . $a->numeroGare . ')') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                    <input type="date" name="date" class="form-control form-control-sm rounded-pill px-3" value="<?= htmlspecialchars($date ?? '') ?>" max="<?= date('Y-m-d') ?>">
                    <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3">Filtrer</button>
                </form>
                <a href="<?= BASE_URL ?>/admin/Bordereau_escales/ajouter" class="btn btn-success btn-sm d-flex align-items-center gap-2 shadow-sm">
                    <i class="bx bx-plus-circle"></i> Nouveau bordereau
                </a>
                <a href="<?= BASE_URL ?>/admin/Bordereau_escales/telecharger_modele" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-2 shadow-sm">
                    <i class="bx bx-download"></i> Télécharger le modèle vierge
                </a>
            </div>
        </div>

        <?php $this->view("admin/set_flash") ?>

        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Gare</th>
                                <th>Escale</th>
                                <th>N° Bordereau</th>
                                <th>Bus</th>
                                <th class="text-end">Billets + bagages</th>
                                <th class="text-end">Écart</th>
                                <th>Saisi par</th>
                                <th>Caisse</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($bordereaux)): ?>
                                <tr><td colspan="10" class="text-center text-muted py-4">Aucun bordereau saisi pour ces critères.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($bordereaux as $b): ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($b['date_escale'])) ?></td>
                                    <td><?= htmlspecialchars(($b['localite'] ?? '-') . ' (' . ($b['numeroGare'] ?? '-') . ')') ?></td>
                                    <td><?= htmlspecialchars($b['escale_libelle'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($b['numero_bordereau'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($b['numero_bus'] ?? '-') ?></td>
                                    <td class="text-end"><?= number_format((float)$b['total_billets'] + (float)$b['total_bagages'], 0, ',', ' ') ?></td>
                                    <td class="text-end <?= (float)($b['ecart'] ?? 0) < 0 ? 'text-danger' : 'text-success' ?>">
                                        <?= isset($b['ecart']) ? number_format((float)$b['ecart'], 0, ',', ' ') : '-' ?>
                                    </td>
                                    <td><?= htmlspecialchars($b['nom_saisi_par'] ?? '-') ?></td>
                                    <td>
                                        <?php if (($b['statut_caisse'] ?? null) === 'versee'): ?>
                                            <span class="badge bg-success">Versée</span>
                                        <?php elseif (($b['statut_caisse'] ?? null) === 'fermee'): ?>
                                            <span class="badge bg-warning text-dark">À verser</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><a href="<?= BASE_URL ?>/admin/Bordereau_escales/detail/<?= $b['id_bordereau'] ?>" class="btn btn-sm btn-outline-primary">Détail</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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
