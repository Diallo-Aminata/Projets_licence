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

            <div class="page-breadcrumb d-flex flex-column flex-sm-row align-items-start align-items-sm-center mb-3">
                <div class="breadcrumb-title pe-3">
                    <i class="bx bx-calendar-event me-1"></i> G-programmer
                </div>
                <div class="ps-0 ps-sm-3">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 p-0">
                            <li class="breadcrumb-item">
                                <a href="javascript:;"><i class="bx bx-home-alt"></i></a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Nouvelle programmation de voyage</li>
                        </ol>
                    </nav>
                </div>
                <div class="ms-sm-auto mt-2 mt-sm-0">
                    <a href="javascript:history.back()" class="btn btn-sm btn-outline-primary rounded-pill shadow-sm">
                        <i class="bx bx-left-arrow-alt"></i> Retour
                    </a>
                </div>
            </div>
            <!--end breadcrumb-->

            <?php $this->view("admin/set_flash") ?>

            <div class="card shadow-lg border-0 rounded-3">
                <div class="card-header bg-primary text-white fw-bold">
                    <i class="bx bx-bus me-1"></i> Programmation des voyages
                </div>
                <div class="card-body">
                    <form action="" method="post">

                        <!-- Date du voyage -->
                        <div class="row mb-4 align-items-center">
                            <label for="jourVoyage" class="col-sm-2 col-form-label fw-semibold">Jour du voyage</label>
                            <div class="col-sm-4">
                                <input type="date" class="form-control shadow-sm" id="jourVoyage" name="jourVoyage" required>
                            </div>
                            <?php if (!empty($programmation_veille) && ($_SESSION['droit'] ?? null) !== 'PDG'): ?>
                                <div class="col-sm-6">
                                    <button type="button" id="btnReproduireHier" class="btn btn-outline-primary shadow-sm">
                                        <i class="bx bx-repeat me-1"></i> Reproduire la programmation du <?= date('d/m/Y', strtotime($derniere_date)) ?>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Tableau -->
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered align-middle shadow-sm rounded">
                                <thead class="table-primary text-center">
                                    <tr>
                                        <th><?php if (($_SESSION['droit'] ?? null) !== 'PDG'): ?><input type="checkbox" id="selectAll"><?php endif; ?></th>
                                        <th>Numéro Car</th>
                                        <th>Horaire</th>
                                        <?php if ($_SESSION['droit'] === 'Admin'): ?>
                                            <th>Départ</th>
                                        <?php endif; ?>
                                        <th>Destination</th>
                                    </tr>
                                </thead>
                                <tbody class="text-center">
                                    <?php if (!empty($cars_destinations)) : ?>
                                        <?php $indexRow = 0; ?>
                                        <?php foreach ($cars_destinations as $numero_car => $destinations ) : ?>
                                            <tr data-id-car="<?= htmlspecialchars($destinations[0]->id_car) ?>">
                                                <td><?php if (($_SESSION['droit'] ?? null) !== 'PDG'): ?><input type="checkbox" name="select_car[]" value="<?= $indexRow ?>" class="form-check-input checkbox-car"><?php endif; ?></td>
                                                <td>
                                                    <input type="text" name="numero_car[]" class="form-control text-center shadow-sm" value="<?= htmlspecialchars($numero_car) ?>" readonly>
                                                    <input type="hidden" name="id_care[]" value="<?= htmlspecialchars($destinations[0]->id_car) ?>">
                                                </td>
                                                <td>
                                                    <!-- Rempli en JS depuis le trajet choisi (jamais une saisie libre) : évite de
                                                         programmer un car à une heure qui n'existe pas pour ce trajet. -->
                                                    <select class="form-select shadow-sm" name="id_horaire[]">
                                                        <!-- Pas de "disabled" ici : une option disabled-selected est exclue de la
                                                             soumission du formulaire (spec HTML), ce qui décale les index de ce
                                                             tableau par rapport à id_care[]/select_car[] pour toute ligne non
                                                             touchée. On garde juste "selected" pour l'affichage du placeholder. -->
                                                        <option value="" selected>Choisir d'abord une destination</option>
                                                    </select>
                                                </td>
                                                <?php if ($_SESSION['droit'] === 'Admin'): ?>
                                                    <td>
                                                        <input type="text" name="id_depart[]" class="form-control text-center shadow-sm champ-depart" readonly placeholder="—">
                                                        <input type="hidden" name="id_depart_agence[]" class="champ-depart-agence">
                                                    </td>
                                                <?php endif; ?>
                                                <td>
                                                    <select class="form-select shadow-sm" name="id_destination[]">
                                                        <!-- Pas de "disabled" ici, pour la même raison que id_horaire[] ci-dessus. -->
                                                        <option selected value="">Choisir une destination</option>
                                                        <?php foreach ($tousLesTrajets as $d): ?>
                                                            <?php
                                                            // Tous les trajets de la compagnie sont proposes ici (pas seulement ceux
                                                            // deja affectes a ce car via "Affectation des cars") : voir
                                                            // Programmation_voyage::getTousLesTrajets().
                                                            // Cas 1 : Admin => il voit tout
                                                            if ($_SESSION['droit'] === 'Admin') {
                                                                $afficher = true;
                                                            }
                                                            // Cas 2 : chef_d_escale => il voit uniquement les départs qui correspondent à sa ville
                                                            elseif ($_SESSION['droit'] === 'chef_d_escale' && $d->departLocalite === $_SESSION['ville']) {
                                                                $afficher = true;
                                                            }
                                                            // Cas 3 : autres => rien
                                                            else {
                                                                $afficher = false;
                                                            }
                                                            ?>

                                                            <?php if ($afficher): ?>
                                                                <option value="<?= htmlspecialchars($d->destinationLocalite) ?>"
                                                                    data-depart="<?= htmlspecialchars($d->departLocalite) ?>"
                                                                    data-depart-agence="<?= htmlspecialchars($d->idDepart) ?>"
                                                                    data-destination-agence="<?= htmlspecialchars($d->idDestination) ?>"
                                                                    data-heure="<?= htmlspecialchars($d->heureDepart) ?>">
                                                                    <?= htmlspecialchars($d->departLocalite . ' (Gare ' . $d->numeroGareDepart . ') -> ' . $d->destinationLocalite . ' (Gare ' . $d->numeroGareDestination . ')') ?>
                                                                    (<?= htmlspecialchars(date('H:i', strtotime($d->heureDepart))) ?>)
                                                                </option>
                                                            <?php endif; ?>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <input type="hidden" name="id_destination_agence[]" class="champ-destination-agence">
                                                </td>
                                            </tr>
                                            <?php $indexRow++; ?>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <tr>
                                            <td colspan="4" class="text-muted">🚫 Aucun car disponible</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Boutons -->
                        <div class="mt-4 d-flex gap-2">
                            <?php if (($_SESSION['droit'] ?? null) !== 'PDG'): ?>
                            <button class="btn btn-success shadow-sm" type="submit" name="programmer">
                                <i class="bx bx-save me-1"></i> Enregistrer
                            </button>
                            <?php endif; ?>
                            <a href="<?= BASE_URL ?>/admin/Programmation_voyages/liste_programmer_voyage"
                                class="btn btn-info text-white shadow-sm">
                                <i class="bx bx-list-ul me-1"></i> Voir la liste
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Véhicules en approche -->
            <?php if (!empty($cars_en_transit)): ?>
            <div class="card shadow-lg border-0 rounded-3 mt-4">
                <div class="card-header bg-success text-white fw-bold">
                    <i class="bx bx-check-shield me-1"></i> Véhicules en approche (Validation d'arrivée)
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered align-middle shadow-sm rounded">
                            <thead class="table-success text-center">
                                <tr>
                                    <th>Numéro Car</th>
                                    <th>Places Totales</th>
                                    <th>Destination prévue</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody class="text-center">
                                <?php foreach ($cars_en_transit as $car): ?>
                                    <tr>
                                        <td class="fw-bold"><?= htmlspecialchars($car->numero_car) ?></td>
                                        <td><?= htmlspecialchars($car->nbr_place) ?></td>
                                        <td>
                                            <?php
                                                // Extract the destination string from En_transit_X
                                                $dest = substr($car->status_car, 11);
                                                echo htmlspecialchars($dest);
                                                if (!empty($car->numeroGareDestination)) {
                                                    echo ' <span class="text-muted">(Gare ' . htmlspecialchars($car->numeroGareDestination) . ')</span>';
                                                }
                                            ?>
                                        </td>
                                        <td>
                                            <?php if (($_SESSION['droit'] ?? null) !== 'PDG'): ?>
                                            <form action="" method="post" class="d-inline">
                                                <input type="hidden" name="id_car_arrivee" value="<?= $car->id_car ?>">
                                                <button type="submit" name="valider_arrivee" class="btn btn-sm btn-success shadow-sm rounded-pill px-3">
                                                    <i class="bx bx-check-double me-1"></i> Valider l'arrivée
                                                </button>
                                            </form>
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

            <!-- Cars bloqués : "En transit" en base mais sans decollage jamais enregistré
                 (voyage antérieur à l'ajout de decolle_le, ou anomalie) — invisibles à la
                 fois de "disponibles" et "en approche" tant que personne ne les débloque. -->
            <?php if (!empty($cars_bloques)): ?>
            <div class="card shadow-lg border-0 rounded-3 mt-4">
                <div class="card-header bg-warning text-dark fw-bold">
                    <i class="bx bx-error me-1"></i> Cars bloqués (anomalie à résoudre)
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">
                        Ces cars sont marqués « en transit » en base, mais leur décollage n'a jamais été
                        enregistré (voyage créé avant l'ajout du suivi de décollage, ou anomalie).
                        Ils n'apparaissent ni comme disponibles, ni comme « en approche ». Indiquez
                        leur état réel pour les débloquer.
                    </p>
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered align-middle shadow-sm rounded">
                            <thead class="table-warning text-center">
                                <tr>
                                    <th>Numéro Car</th>
                                    <th>Départ prévu</th>
                                    <th>Destination prévue</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody class="text-center">
                                <?php foreach ($cars_bloques as $car): ?>
                                    <tr>
                                        <td class="fw-bold"><?= htmlspecialchars($car['numero_car']) ?></td>
                                        <td><?= htmlspecialchars($car['origine']) ?></td>
                                        <td><?= htmlspecialchars($car['destination']) ?></td>
                                        <td><?= htmlspecialchars(date('d/m/Y', strtotime($car['date_enregistre']))) ?></td>
                                        <td>
                                            <form action="" method="post" class="d-inline" id="form-arrivee-<?= $car['id_programmation'] ?>">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id_programmation_bloque" value="<?= $car['id_programmation'] ?>">
                                                <input type="hidden" name="debloquer_arrive" value="1">
                                                <button type="button" class="btn btn-sm btn-success shadow-sm rounded-pill px-2 mb-1"
                                                    onclick="confirmerArriveeCar(<?= $car['id_programmation'] ?>, '<?= addslashes($car['destination']) ?>')">
                                                    <i class="bx bx-check-double me-1"></i> Arrivé à destination
                                                </button>
                                            </form>
                                            <form action="" method="post" class="d-inline"
                                                onsubmit="return confirm('Confirmer : ce car n\'a jamais quitté <?= htmlspecialchars(addslashes($car['origine'])) ?> ?');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id_programmation_bloque" value="<?= $car['id_programmation'] ?>">
                                                <button type="submit" name="debloquer_jamais_parti" class="btn btn-sm btn-outline-secondary shadow-sm rounded-pill px-2 mb-1">
                                                    <i class="bx bx-undo me-1"></i> Jamais parti
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

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
        function confirmerArriveeCar(idProgrammation, destination) {
            Swal.fire({
                title: 'Confirmer l\'arrivée',
                text: `Ce car est bien arrivé à ${destination} ?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                confirmButtonText: 'Oui, arrivé',
                cancelButtonText: 'Annuler'
            }).then(r => {
                if (r.isConfirmed) document.getElementById('form-arrivee-' + idProgrammation).submit();
            });
        }
    </script>
    <!-- ✅ Script JS pour gérer la sélection de tous les checkboxes -->
    <script>
        const dateInput = document.getElementById('jourVoyage');

        (function initDateLimits() {
            const today = new Date();
            const tomorrow = new Date(today);
            tomorrow.setDate(today.getDate() + 1);

            const toISO = d => d.toISOString().slice(0, 10); // YYYY-MM-DD
            dateInput.min = toISO(today);
            dateInput.max = toISO(tomorrow);

            // Fixer la valeur par défaut à aujourd'hui
            dateInput.value = dateInput.min;
        })();


        // La destination n'est obligatoire que pour les lignes cochées : sinon le navigateur
        // bloque la soumission en demandant de remplir des cars qu'on ne veut pas programmer.
        function syncDestinationRequired(tr) {
            const checkbox = tr.querySelector('.checkbox-car');
            const selectDestination = tr.querySelector('select[name="id_destination[]"]');
            if (checkbox && selectDestination) {
                selectDestination.required = checkbox.checked;
            }
        }

        document.querySelectorAll('tbody tr[data-id-car]').forEach(function(tr) {
            const checkbox = tr.querySelector('.checkbox-car');
            if (checkbox) {
                checkbox.addEventListener('change', function() {
                    syncDestinationRequired(tr);
                });
            }
        });

        document.getElementById('selectAll').addEventListener('change', function() {
            const isChecked = this.checked;
            document.querySelectorAll('.checkbox-car').forEach(function(checkbox) {
                checkbox.checked = isChecked;
                syncDestinationRequired(checkbox.closest('tr'));
            });
        });

        // Remplit automatiquement, selon la destination (trajet) choisie :
        // - le champ "Départ" (Admin uniquement : localité de départ réelle du trajet)
        // - le select "Horaire" : jamais une saisie libre, toujours l'heure réelle de ce
        //   trajet précis (évite de programmer un car à une heure qui n'existe pas pour lui).
        document.querySelectorAll('select[name="id_destination[]"]').forEach(function(select) {
            select.addEventListener('change', function() {
                const tr = this.closest('tr');
                const selectedOption = this.options[this.selectedIndex];

                const champDepart = tr.querySelector('.champ-depart');
                if (champDepart) {
                    champDepart.value = selectedOption?.getAttribute('data-depart') || '';
                }

                const champDepartAgence = tr.querySelector('.champ-depart-agence');
                if (champDepartAgence) {
                    champDepartAgence.value = selectedOption?.getAttribute('data-depart-agence') || '';
                }

                const champDestinationAgence = tr.querySelector('.champ-destination-agence');
                if (champDestinationAgence) {
                    champDestinationAgence.value = selectedOption?.getAttribute('data-destination-agence') || '';
                }

                const selectHoraire = tr.querySelector('select[name="id_horaire[]"]');
                if (selectHoraire) {
                    const heure = selectedOption?.getAttribute('data-heure') || '';
                    selectHoraire.innerHTML = heure
                        ? `<option value="${heure}" selected>${heure.slice(0, 5)}</option>`
                        : '<option value="" selected>Choisir d\'abord une destination</option>';
                }
            });
        });

        // Reproduire la programmation de la veille : pré-remplit chaque ligne dont le car
        // était déjà programmé hier. Les cars indisponibles aujourd'hui (pas dans le tableau)
        // ou dont l'horaire/destination n'existe plus sont simplement ignorés — l'agent les
        // programme alors manuellement comme d'habitude.
        <?php if (!empty($programmation_veille)): ?>
            const programmationVeille = <?= json_encode($programmation_veille, JSON_HEX_TAG | JSON_HEX_APOS) ?>;

            document.getElementById('btnReproduireHier').addEventListener('click', function() {
                let appliques = 0;
                let ignores = 0;

                document.querySelectorAll('tbody tr[data-id-car]').forEach(function(tr) {
                    const idCar = tr.getAttribute('data-id-car');
                    const prev = programmationVeille[idCar];
                    if (!prev) return;

                    const selectHoraire = tr.querySelector('select[name="id_horaire[]"]');
                    const selectDestination = tr.querySelector('select[name="id_destination[]"]');
                    const checkbox = tr.querySelector('.checkbox-car');

                    const destinationExiste = selectDestination && [...selectDestination.options].some(o => o.value === prev.id_trajet);
                    if (!destinationExiste) {
                        ignores++;
                        return;
                    }

                    selectDestination.value = prev.id_trajet;
                    // Déclenche le remplissage automatique de l'horaire (et du champ "Départ" pour Admin)
                    // depuis le trajet choisi ci-dessus.
                    selectDestination.dispatchEvent(new Event('change'));

                    // L'horaire d'hier doit correspondre exactement à celui déduit du trajet
                    // aujourd'hui (sinon ce trajet n'existe plus à cette heure : on l'ignore).
                    if (!selectHoraire || selectHoraire.value !== prev.id_horaire) {
                        ignores++;
                        return;
                    }

                    if (checkbox) checkbox.checked = true;
                    syncDestinationRequired(tr);
                    appliques++;
                });

                let message = appliques + ' car(s) pré-rempli(s) depuis la programmation du <?= date('d/m/Y', strtotime($derniere_date)) ?>.';
                if (ignores > 0) {
                    message += ' ' + ignores + ' car(s) non repris (horaire/destination indisponible aujourd\'hui) — à programmer manuellement.';
                }
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Reproduction terminée', message, 'info');
                } else {
                    alert(message);
                }
            });
        <?php endif; ?>

    </script>
</body>

</html>