<?php $this->view('admin/partials/header') ?>

<body>
    <!--start wrapper-->
    <div class="wrapper">
        <?php $this->view('admin/partials/navbar') ?>
        <?php $this->view('admin/partials/sidebar') ?>

        <!--start content-->
        <main class="page-content">
            <!--breadcrumb-->
            <div class="page-breadcrumb d-flex flex-wrap align-items-center mb-3">
                <div class="breadcrumb-title pe-3">Caisse</div>
                <div class="ps-3">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 p-0">
                            <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a></li>
                            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/Bordereau_escales/index">Bordereau d'escale</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Nouvelle saisie</li>
                        </ol>
                    </nav>
                </div>
                <div class="ms-auto">
                    <div class="btn-group">
                        <a href="<?= BASE_URL ?>/admin/Bordereau_escales/index" class="btn btn-primary split-bg-primary"><i class="fadeIn animated bx bx-left-arrow-alt"></i></a>
                    </div>
                </div>
            </div>
            <!--end breadcrumb-->

            <div class="row">
                <div class="col-xl-10 mx-auto">
                    <?php $this->view("admin/set_flash") ?>

                    <div class="card">
                        <div class="card-header px-4 py-3">
                            <h5 class="mb-0 text-primary">Saisie d'un bordereau d'escale papier</h5>
                            <small class="text-muted">Reprenez les champs du bordereau imprimé dans le même ordre.</small>
                        </div>
                        <div class="card-body">
                            <form method="POST" class="needs-validation" novalidate>
                                <?= csrf_field() ?>

                                <div class="row g-3 mb-4">
                                    <div class="col-md-3">
                                        <label class="form-label">Date <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" name="date_escale" id="dateEscale" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Bus N°</label>
                                        <input type="text" class="form-control" name="numero_bus" placeholder="Ex: 208">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">N° Bordereau</label>
                                        <input type="text" class="form-control" name="numero_bordereau" placeholder="Numéro écrit sur le papier">
                                    </div>
                                    <?php if ($estAdmin): ?>
                                    <div class="col-md-3">
                                        <label class="form-label">Gare de rattachement <span class="text-danger">*</span></label>
                                        <select class="form-select" name="id_agence" required>
                                            <option value="">Choisissez la gare</option>
                                            <?php foreach ($listeAgences as $ag): ?>
                                                <option value="<?= $ag->idAgence ?>"><?= htmlspecialchars($ag->localite) ?> - <?= htmlspecialchars($ag->numeroGare) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="form-text">La caisse réelle de cette gare sera créditée.</div>
                                    </div>
                                    <?php endif; ?>
                                    <div class="col-md-6">
                                        <label class="form-label">Escale</label>
                                        <input type="text" class="form-control" name="escale_libelle" placeholder="Ex: Diéma, Nioro, Diboly…">
                                        <div class="form-text">Nom de l'arrêt tel qu'écrit sur le papier (peut différer de la gare de rattachement si ce n'est pas une gare enregistrée).</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Chef / agent d'escale</label>
                                        <input type="text" class="form-control" name="nom_agent_escale" placeholder="Nom tel qu'écrit sur le papier">
                                    </div>
                                </div>

                                <h6 class="text-primary">Billets vendus</h6>
                                <div id="billetsRows">
                                    <div class="row g-2 align-items-end mb-2 billet-row">
                                        <div class="col-md-5">
                                            <label class="form-label">Destination</label>
                                            <input type="text" class="form-control" name="destination[]" autocomplete="off">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Nb billets</label>
                                            <input type="number" min="0" class="form-control champ-nombre" name="nombre_billets[]" autocomplete="off">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Prix unit. FCFA</label>
                                            <input type="number" min="0" class="form-control champ-prix" name="prix_unitaire[]" autocomplete="off">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Montant FCFA</label>
                                            <input type="text" class="form-control montant-ligne" readonly tabindex="-1">
                                        </div>
                                        <div class="col-md-1">
                                            <button type="button" class="btn btn-outline-danger remove-row-btn d-none w-100" title="Retirer cette ligne"><i class="bx bx-trash"></i></button>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" id="addBilletRow" class="btn btn-sm btn-outline-primary mb-2">
                                    <i class="bx bx-plus"></i> Ajouter une destination
                                </button>
                                <div class="text-end fw-bold mb-4">Sous-total billets : <span id="sousTotalBillets">0</span> FCFA</div>

                                <h6 class="text-primary">Bagages payants</h6>
                                <div id="bagagesRows">
                                    <div class="row g-2 align-items-end mb-2 bagage-row">
                                        <div class="col-md-7">
                                            <label class="form-label">Description</label>
                                            <input type="text" class="form-control" name="bagage_description[]" autocomplete="off">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Prix FCFA</label>
                                            <input type="number" min="0" class="form-control champ-prix" name="bagage_prix[]" autocomplete="off">
                                        </div>
                                        <div class="col-md-2">
                                            <button type="button" class="btn btn-outline-danger remove-bagage-btn d-none w-100" title="Retirer cette ligne"><i class="bx bx-trash"></i></button>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" id="addBagageRow" class="btn btn-sm btn-outline-primary mb-2">
                                    <i class="bx bx-plus"></i> Ajouter un bagage
                                </button>
                                <div class="text-end fw-bold mb-4">Sous-total bagages : <span id="sousTotalBagages">0</span> FCFA</div>

                                <h6 class="text-primary">Récapitulatif</h6>
                                <div class="row g-3 mb-2">
                                    <div class="col-md-4">
                                        <label class="form-label">Dépenses (FCFA)</label>
                                        <input type="number" min="0" class="form-control champ-recap" name="montant_depenses" value="0">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Commission agent d'escale (FCFA)</label>
                                        <input type="number" min="0" class="form-control champ-recap" name="montant_commission" value="0">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Total versé <span class="text-danger">*</span></label>
                                        <input type="number" min="0" class="form-control champ-recap" name="total_verse" id="totalVerse" required>
                                        <div class="form-text">Le montant physiquement remis, tel qu'écrit sur le bordereau.</div>
                                    </div>
                                </div>

                                <div class="alert alert-info" id="recapCalcul">
                                    Billets + bagages − dépenses − commission = <strong><span id="totalCalcule">0</span> FCFA</strong>
                                </div>

                                <button type="submit" name="save" class="btn btn-primary px-4">Enregistrer le bordereau</button>
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
        (function initDateMax() {
            document.getElementById('dateEscale').max = new Date().toISOString().slice(0, 10);
        })();

        function formatMontant(n) {
            return isNaN(n) ? '0' : n.toLocaleString('fr-FR');
        }

        function recalculer() {
            let totalBillets = 0;
            document.querySelectorAll('#billetsRows .billet-row').forEach(function(row) {
                const nb = parseFloat(row.querySelector('.champ-nombre').value) || 0;
                const prix = parseFloat(row.querySelector('.champ-prix').value) || 0;
                const montant = nb * prix;
                row.querySelector('.montant-ligne').value = montant ? formatMontant(montant) : '';
                totalBillets += montant;
            });

            let totalBagages = 0;
            document.querySelectorAll('#bagagesRows .bagage-row').forEach(function(row) {
                totalBagages += parseFloat(row.querySelector('.champ-prix').value) || 0;
            });

            const depenses = parseFloat(document.querySelector('[name="montant_depenses"]').value) || 0;
            const commission = parseFloat(document.querySelector('[name="montant_commission"]').value) || 0;

            document.getElementById('sousTotalBillets').textContent = formatMontant(totalBillets);
            document.getElementById('sousTotalBagages').textContent = formatMontant(totalBagages);
            document.getElementById('totalCalcule').textContent = formatMontant(totalBillets + totalBagages - depenses - commission);
        }

        document.addEventListener('DOMContentLoaded', function() {
            const billetsRows = document.getElementById('billetsRows');
            const bagagesRows = document.getElementById('bagagesRows');

            function toggleRemove(container, rowClass, btnClass) {
                const rows = container.querySelectorAll('.' + rowClass);
                rows.forEach(function(row) {
                    row.querySelector('.' + btnClass).classList.toggle('d-none', rows.length <= 1);
                });
            }

            document.getElementById('addBilletRow').addEventListener('click', function() {
                const firstRow = billetsRows.querySelector('.billet-row');
                const newRow = firstRow.cloneNode(true);
                newRow.querySelectorAll('input').forEach(function(input) { input.value = ''; });
                billetsRows.appendChild(newRow);
                toggleRemove(billetsRows, 'billet-row', 'remove-row-btn');
            });

            document.getElementById('addBagageRow').addEventListener('click', function() {
                const firstRow = bagagesRows.querySelector('.bagage-row');
                const newRow = firstRow.cloneNode(true);
                newRow.querySelectorAll('input').forEach(function(input) { input.value = ''; });
                bagagesRows.appendChild(newRow);
                toggleRemove(bagagesRows, 'bagage-row', 'remove-bagage-btn');
            });

            billetsRows.addEventListener('click', function(e) {
                const btn = e.target.closest('.remove-row-btn');
                if (btn) { btn.closest('.billet-row').remove(); toggleRemove(billetsRows, 'billet-row', 'remove-row-btn'); recalculer(); }
            });
            bagagesRows.addEventListener('click', function(e) {
                const btn = e.target.closest('.remove-bagage-btn');
                if (btn) { btn.closest('.bagage-row').remove(); toggleRemove(bagagesRows, 'bagage-row', 'remove-bagage-btn'); recalculer(); }
            });

            document.querySelector('.card-body').addEventListener('input', function(e) {
                if (e.target.matches('.champ-nombre, .champ-prix, .champ-recap')) {
                    recalculer();
                }
            });

            recalculer();
        });
    </script>
</body>

</html>
