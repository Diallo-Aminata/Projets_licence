<?php $this->view('admin/partials/headers') ?>
<body>
    <div class="wrapper">
        <?php $this->view('admin/partials/navbar') ?>
        <?php $this->view('admin/partials/sidebar') ?>

        <main class="page-content">

            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                <h4 class="fw-bold mb-0">🚌 Location des cars</h4>
            </div>

            <?php $this->view("admin/set_flash") ?>

            <?php if (($_SESSION['droit'] ?? null) !== 'PDG'): ?>
            <div class="card shadow-sm border-0 rounded-3 mb-4">
                <div class="card-header bg-primary text-white fw-bold">
                    <i class="bx bx-plus-circle me-1"></i> Nouvelle location
                </div>
                <div class="card-body">
                    <form action="" method="post" class="row g-3" id="formLocation">

                        <?php if (($_SESSION['droit'] ?? null) === 'Admin'): ?>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Gare de départ</label>
                                <select class="form-select" id="id_agence_depart" name="id_agence_depart" required>
                                    <option value="" disabled selected>Choisir la gare</option>
                                    <?php foreach ($listeAgences as $agence): ?>
                                        <option value="<?= htmlspecialchars($agence->idAgence) ?>">
                                            <?= htmlspecialchars($agence->localite . ' ( ' . $agence->numeroGare . ' )') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php else: ?>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Gare de départ</label>
                                <input type="text" class="form-control" disabled
                                    value="<?= htmlspecialchars(($_SESSION['ville'] ?? '-') . ' ( ' . ($_SESSION['numero_gare'] ?? '-') . ' )') ?>">
                                <input type="hidden" name="id_agence_depart" value="<?= (int)($_SESSION['id_agence'] ?? 0) ?>">
                            </div>
                        <?php endif; ?>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Destination</label>
                            <input type="text" class="form-control" name="destination" id="destination"
                                placeholder="Ville ou lieu (même hors itinéraires habituels)" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Frais de location</label>
                            <div class="input-group">
                                <input type="number" min="1" class="form-control" name="frais_location" required>
                                <span class="input-group-text">FCFA</span>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Date de départ</label>
                            <input type="date" class="form-control" id="date_depart" name="date_depart"
                                value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Date de retour prévu</label>
                            <input type="date" class="form-control" id="date_retour_prevu" name="date_retour_prevu"
                                value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="estCamionCheck" name="est_camion" value="1">
                                <label class="form-check-label" for="estCamionCheck">Louer un camion (au lieu d'un car)</label>
                            </div>
                        </div>

                        <div class="col-md-4" id="carField">
                            <label class="form-label fw-semibold">Car</label>
                            <select class="form-select" id="id_car" name="id_car" required disabled>
                                <option value="" selected>Choisissez d'abord la gare et les dates</option>
                            </select>
                        </div>

                        <div class="col-md-4 d-none" id="camionField">
                            <label class="form-label fw-semibold">Camion</label>
                            <select class="form-select" id="id_camion" name="id_camion" disabled>
                                <option value="" selected>Choisissez d'abord la gare et les dates</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Nom du client</label>
                            <input type="text" class="form-control" name="nom_client" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Prénom du client</label>
                            <input type="text" class="form-control" name="prenom_client" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Téléphone du client</label>
                            <input type="text" class="form-control" name="telephone_client" required>
                        </div>

                        <div class="col-12">
                            <button type="submit" name="save_location" class="btn btn-success">
                                <i class="bx bx-save me-1"></i> Enregistrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header fw-bold">
                    <i class="bx bx-list-ul me-1"></i> Historique des locations
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Gare départ</th>
                                <th>Destination</th>
                                <th>Véhicule</th>
                                <th>Client</th>
                                <th>Période</th>
                                <th>Frais</th>
                                <th>Enregistré par</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($listeLocations)): ?>
                                <tr><td colspan="9" class="text-center text-muted py-4">Aucune location enregistrée.</td></tr>
                            <?php else: ?>
                                <?php foreach ($listeLocations as $l): ?>
                                    <tr>
                                        <td><?= htmlspecialchars(($l->localite ?? '-') . ' (' . ($l->numeroGare ?? '-') . ')') ?></td>
                                        <td><?= htmlspecialchars($l->destination) ?></td>
                                        <td>
                                            <?php if (($l->type_vehicule ?? 'car') === 'camion'): ?>
                                                <span class="badge bg-info text-dark">Camion</span>
                                            <?php else: ?>
                                                <span class="badge bg-primary">Car</span>
                                            <?php endif; ?>
                                            <?= htmlspecialchars(($l->numero_vehicule ?? '-') . ' - ' . ($l->matricule_vehicule ?? '-')) ?>
                                        </td>
                                        <td><?= htmlspecialchars($l->prenom_client . ' ' . $l->nom_client) ?><br><small class="text-muted"><?= htmlspecialchars($l->telephone_client) ?></small></td>
                                        <td><?= date('d/m/Y', strtotime($l->date_depart)) ?> → <?= date('d/m/Y', strtotime($l->date_retour_prevu)) ?></td>
                                        <td class="fw-bold text-success"><?= number_format($l->frais_location, 0, ',', ' ') ?> F</td>
                                        <td><?= htmlspecialchars($l->agent ?? '-') ?></td>
                                        <td>
                                            <?php if ($l->statut === 'valide'): ?>
                                                <span class="badge bg-success">Validée</span>
                                            <?php elseif ($l->statut === 'en_attente'): ?>
                                                <span class="badge bg-warning text-dark">En attente</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Rejetée</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="<?= BASE_URL ?>/admin/Locations_cars/imprimerFacture/<?= $l->id_location ?>"
                                               class="btn btn-sm btn-outline-secondary py-0 px-2" target="_blank">
                                                <i class="bx bx-printer"></i> Facture
                                            </a>
                                            <?php if (($_SESSION['droit'] ?? null) === 'Admin' && $l->statut === 'en_attente'): ?>
                                                <a href="javascript:void(0);"
                                                   class="btn btn-sm btn-success py-0 px-2 btn-valider-location"
                                                   data-url="<?= BASE_URL ?>/admin/Locations_cars/valider/<?= $l->id_location ?>"
                                                   data-destination="<?= htmlspecialchars($l->destination) ?>"
                                                   data-frais="<?= number_format($l->frais_location, 0, ',', ' ') ?>">
                                                    <i class="bx bx-check"></i> Valider
                                                </a>
                                                <a href="javascript:void(0);"
                                                   class="btn btn-sm btn-danger py-0 px-2 btn-rejeter-location"
                                                   data-url="<?= BASE_URL ?>/admin/Locations_cars/rejeter/<?= $l->id_location ?>"
                                                   data-destination="<?= htmlspecialchars($l->destination) ?>"
                                                   data-frais="<?= number_format($l->frais_location, 0, ',', ' ') ?>">
                                                    <i class="bx bx-x"></i> Rejeter
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <?php $this->view('admin/partials/foot') ?>

    <script>
        const idAgenceDepartInput = document.getElementById('id_agence_depart'); // Admin uniquement
        const dateDepartInput = document.getElementById('date_depart');
        const dateRetourInput = document.getElementById('date_retour_prevu');
        const idCarSelect = document.getElementById('id_car');
        const idCamionSelect = document.getElementById('id_camion');
        const carField = document.getElementById('carField');
        const camionField = document.getElementById('camionField');
        const estCamionCheck = document.getElementById('estCamionCheck');
        const idAgenceDepartFixe = <?= json_encode((int)($_SESSION['id_agence'] ?? 0)) ?>;
        const isAdmin = <?= json_encode(($_SESSION['droit'] ?? null) === 'Admin') ?>;

        // Bascule Car / Camion : meme principe que toggleVehiculeFields() dans
        // chauffeur_cars.view.php, mais chaque select est repeuple par AJAX (pas de
        // liste statique ici), donc on rafraichit aussitot le select qui devient visible.
        function toggleVehiculeFields() {
            const estCamion = estCamionCheck.checked;
            carField.classList.toggle('d-none', estCamion);
            camionField.classList.toggle('d-none', !estCamion);
            idCarSelect.required = !estCamion;
            idCamionSelect.required = estCamion;
            if (estCamion) {
                idCarSelect.value = '';
                rafraichirCamionsDisponibles();
            } else {
                idCamionSelect.value = '';
                rafraichirCarsDisponibles();
            }
        }

        function rafraichirCarsDisponibles() {
            const id_agence_depart = isAdmin ? (idAgenceDepartInput ? idAgenceDepartInput.value : '') : idAgenceDepartFixe;
            const date_depart = dateDepartInput.value;
            const date_retour_prevu = dateRetourInput.value;

            idCarSelect.innerHTML = '<option value="">Chargement...</option>';
            idCarSelect.disabled = true;

            if (!id_agence_depart || !date_depart || !date_retour_prevu) {
                idCarSelect.innerHTML = '<option value="">Choisissez d\'abord la gare et les dates</option>';
                return;
            }

            $.ajax({
                url: '<?= BASE_URL ?>/admin/Locations_cars/ajaxCarsDisponibles',
                type: 'POST',
                data: { id_agence_depart, date_depart, date_retour_prevu },
                dataType: 'json',
                success: function(response) {
                    if (response.error) {
                        idCarSelect.innerHTML = '<option value="">' + response.error + '</option>';
                        return;
                    }
                    if (!response.cars || response.cars.length === 0) {
                        idCarSelect.innerHTML = '<option value="">Aucun car disponible sur cette période</option>';
                        return;
                    }
                    let options = '<option value="" disabled selected>Choisir un car</option>';
                    response.cars.forEach(function(c) {
                        options += `<option value="${c.id_car}">Car n°${c.numero_car} - ${c.matriculle}</option>`;
                    });
                    idCarSelect.innerHTML = options;
                    idCarSelect.disabled = false;
                },
                error: function() {
                    idCarSelect.innerHTML = '<option value="">Erreur lors du chargement des cars</option>';
                }
            });
        }

        // Miroir de rafraichirCarsDisponibles() pour un camion.
        function rafraichirCamionsDisponibles() {
            const id_agence_depart = isAdmin ? (idAgenceDepartInput ? idAgenceDepartInput.value : '') : idAgenceDepartFixe;
            const date_depart = dateDepartInput.value;
            const date_retour_prevu = dateRetourInput.value;

            idCamionSelect.innerHTML = '<option value="">Chargement...</option>';
            idCamionSelect.disabled = true;

            if (!id_agence_depart || !date_depart || !date_retour_prevu) {
                idCamionSelect.innerHTML = '<option value="">Choisissez d\'abord la gare et les dates</option>';
                return;
            }

            $.ajax({
                url: '<?= BASE_URL ?>/admin/Locations_cars/ajaxCamionsDisponibles',
                type: 'POST',
                data: { id_agence_depart, date_depart, date_retour_prevu },
                dataType: 'json',
                success: function(response) {
                    if (response.error) {
                        idCamionSelect.innerHTML = '<option value="">' + response.error + '</option>';
                        return;
                    }
                    if (!response.camions || response.camions.length === 0) {
                        idCamionSelect.innerHTML = '<option value="">Aucun camion disponible sur cette période</option>';
                        return;
                    }
                    let options = '<option value="" disabled selected>Choisir un camion</option>';
                    response.camions.forEach(function(c) {
                        options += `<option value="${c.id_camion}">Camion n°${c.numero_camion} - ${c.matriculle}</option>`;
                    });
                    idCamionSelect.innerHTML = options;
                    idCamionSelect.disabled = false;
                },
                error: function() {
                    idCamionSelect.innerHTML = '<option value="">Erreur lors du chargement des camions</option>';
                }
            });
        }

        function rafraichirVehiculesDisponibles() {
            if (estCamionCheck.checked) {
                rafraichirCamionsDisponibles();
            } else {
                rafraichirCarsDisponibles();
            }
        }

        estCamionCheck.addEventListener('change', toggleVehiculeFields);
        idAgenceDepartInput?.addEventListener('change', rafraichirVehiculesDisponibles);
        dateDepartInput.addEventListener('change', function() {
            if (dateRetourInput.value < dateDepartInput.value) {
                dateRetourInput.value = dateDepartInput.value;
            }
            dateRetourInput.min = dateDepartInput.value;
            rafraichirVehiculesDisponibles();
        });
        dateRetourInput.addEventListener('change', rafraichirVehiculesDisponibles);
        rafraichirCarsDisponibles();

        document.querySelectorAll('.btn-valider-location').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const url = this.getAttribute('data-url');
                const destination = this.getAttribute('data-destination');
                const frais = this.getAttribute('data-frais');

                Swal.fire({
                    title: 'Valider la location ?',
                    text: `Voulez-vous valider la location vers "${destination}" de ${frais} FCFA ? Elle sera créditée à la caisse.`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#198754',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Oui, valider',
                    cancelButtonText: 'Annuler'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = url;
                    }
                });
            });
        });

        document.querySelectorAll('.btn-rejeter-location').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const url = this.getAttribute('data-url');
                const destination = this.getAttribute('data-destination');
                const frais = this.getAttribute('data-frais');

                Swal.fire({
                    title: 'Rejeter la location ?',
                    text: `Voulez-vous rejeter la location vers "${destination}" de ${frais} FCFA ?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Oui, rejeter',
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
