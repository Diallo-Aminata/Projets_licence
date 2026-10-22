<?php $this->view('admin/partials/headers') ?>

<body>
    <!--start wrapper-->
    <div class="wrapper">
        <!--start top header-->
        <?php $this->view('admin/partials/navbar')
        ?>
        <!--end top header-->

        <!--start sidebar -->
        <?php $this->view('admin/partials/sidebar')?>
        <!--end sidebar -->

        <!--start content-->
        <main class="page-content">
            <!--breadcrumb-->
            <div class="page-breadcrumb d-flex flex-wrap align-items-center mb-3">
                <div class="breadcrumb-title pe-3">G-reservation</div>
                <div class="ps-3">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 p-0">
                            <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Enregistrement de ticket</li>
                        </ol>
                    </nav>
                </div>
                <div class="ms-auto">
                    <div class="btn-group">

                        <a href="javascript:history.back()" class="btn btn-primary split-bg-primary"><i
                                class="fadeIn animated bx bx-left-arrow-alt"></i></a>

                    </div>
                </div>
            </div>
            <!--end breadcrumb-->
            <div class="row">
                <div class="col-xl-12 mx-auto">
                    <?php $this->view("admin/set_flash") ?>
                    <?php $this->view("admin/helpers") ?>
                    <div class="card">
                        <div class="card-header px-4 py-3">
                            <h5 class="mb-0 text-primary">Reservation des billets</h5>
                        </div>

                        <div class="card-body">
                            <div class="form-validation">
                                <form class="needs-validation" method="POST">
                                    <?= csrf_field() ?>
                                    <div class="row">
                                        <div class="col-xl-12">
                                            <?php if (($_SESSION['droit'] ?? null) === 'Admin'): ?>
                                            <div class="mb-3 row">
                                                <label class="col-lg-4 col-form-label" for="departSelect">Départ (gare)
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <div class="col-lg-8">
                                                    <select class="form-select" name="idDepart" id="departSelect" required>
                                                        <option value="">Choisissez la gare de départ</option>
                                                        <?php foreach (($agences ?? []) as $ag): ?>
                                                            <option value="<?= $ag['idAgence'] ?>"
                                                                <?= (isset($idDepartSelectionne) && $idDepartSelectionne == $ag['idAgence']) ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($ag['localite']) ?> - <?= htmlspecialchars($ag['numeroGare']) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <div class="invalid-feedback">
                                                        Veuillez choisir la gare de départ. C'est la caisse de cette gare qui sera alimentée.
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                            <div class="mb-3 row">
                                                <label class="col-lg-4 col-form-label"
                                                    for="validationCustom05">Destination
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <div class="col-lg-8">
                                                    <select class="form-select" name="destination" id="destinationSelect">
                                                        <option value="">Choisissez une destination</option>
                                                        <?php foreach ($destinations as $i => $dest): ?>
                                                            <option value="<?= $i ?>" data-id="<?= $dest['nom'] ?>">
                                                                <?= htmlspecialchars($dest['nom'] ) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>

                                                    <div class="invalid-feedback">
                                                        Veuillez choisir la destination du client.
                                                    </div>
                                                </div>
                                            </div>
                                            <input type="hidden" name="destinationId" id="hiddenDestinationId">
                                            <div class="mb-3 row">
                                                <label class="col-lg-4 col-form-label"
                                                    for="validationCustom06">Nom et
                                                    Prénom
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <div class="col-lg-8">
                                                    <input type="text" class="form-control" id="" name="Client"
                                                        value="" required
                                                        placeholder="Nom & Prénom">
                                                    <div class="invalid-feedback">
                                                        Veuillez entrez le nom et le prenom du client.
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mb-3 row">
                                                <label class="col-lg-4 col-form-label" for="jourVoyage">Date de voyage
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <div class="col-lg-8">
                                                    <input type="date"
                                                        class="form-control"
                                                        id="jourVoyage"
                                                        name="jourVoyage"
                                                        required>
                                                    <div class="invalid-feedback">Veuillez saisir la date de départ.</div>
                                                </div>
                                            </div>

                                            <div class="mb-3 row">
                                                <label class="col-lg-4 col-form-label" for="programmeSelect">Heure de départ
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <div class="col-lg-8" style="margin-top:20px;">
                                                    <select name="programme" class="form-select" id="programmeSelect">
                                                        <option value="">Sélectionnez une heure</option>
                                                    </select>
                                                    <div class="form-check mt-2">
                                                        <input class="form-check-input" type="checkbox" id="heureNonListee">
                                                        <label class="form-check-label" for="heureNonListee">Heure non listée (la saisir manuellement)</label>
                                                    </div>
                                                    <input type="time" name="programme" class="form-control mt-2 d-none" id="heureLibreInput" disabled>
                                                </div>
                                            </div>

                                            <!-- Visible uniquement pour un voyage d'aujourd'hui : soit un car est deja
                                                 programme sur ce creneau (bandeau informatif), soit aucun ne l'est
                                                 encore et l'agent en choisit un parmi ceux disponibles a sa gare
                                                 (la programmation est alors creee a la volee lors de l'enregistrement). -->
                                            <div class="mb-3 row d-none" id="etatCreneauRow">
                                                <label class="col-lg-4 col-form-label">Car pour ce voyage</label>
                                                <div class="col-lg-8">
                                                    <div id="bandeauCarProgramme" class="alert d-none" role="alert"></div>
                                                    <div id="choixCarBox" class="d-none">
                                                        <select class="form-select" id="carChoisiSelect" name="idCarChoisi">
                                                            <option value="">Choisissez un car disponible</option>
                                                        </select>
                                                        <div class="invalid-feedback">Veuillez choisir un car pour ce voyage.</div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mb-3 row">
                                                <label class="col-lg-4 col-form-label" for="escalesList">Escales</label>
                                                <div class="col-lg-8" id="escalesList" style="margin-top:8px"></div>
                                                <input type="hidden" id="escaleSelected" name="escaleNom" value="">
                                            </div>

                                            <div class="mb-3 row" id="nombrePassagersRow">
                                                <label class="col-lg-4 col-form-label" for="nombrePassagers">
                                                    Nombre de place <span class="text-danger">*</span>
                                                </label>
                                                <div class="col-lg-8">
                                                    <input type="number" class="form-control"
                                                        id="nombrePassagers" name="nombrePassages"
                                                        placeholder="Nombre de passagers"
                                                        oninput="calculerPrixTotal()">
                                                    <div class="invalid-feedback">
                                                        Veuillez entrer le nombre de passagers.
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mb-3 row">
                                                <label class="col-lg-4 col-form-label" for="prixTotal">
                                                    Prix <span class="text-danger">*</span>
                                                </label>

                                                <div class="col-lg-8" id="prixAutoBox">
                                                    <input type="hidden" class="form-control" id="prix" name="prix_unitaire" readonly>
                                                    <input type="text" class="form-control" id="prixTotal" name="montant_payer" readonly>
                                                </div>

                                                <div class="col-lg-8 d-none" id="prixManuelBox" style="margin-top:10px;">
                                                    <input type="number" class="form-control" id="prixManuel" name="montant_payers" placeholder="Saisir prix l'escale">
                                                </div>
                                            </div>

                                            <!-- Bagages payants : optionnel. Pas de formule automatique, l'agent pese le
                                                 bagage et saisit le prix a la main (cf. app/models/Bagage.php). Un passager
                                                 peut avoir plusieurs bagages : "add to row" comme pour "Ajout de camions". -->
                                            <div class="mb-3 row">
                                                <label class="col-lg-4 col-form-label">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="afficherBagages">
                                                        <label class="form-check-label" for="afficherBagages">Bagages payants</label>
                                                    </div>
                                                </label>
                                                <div class="col-lg-8 d-none" id="bagagesBox">
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
                                                                <label class="form-label">Prix (FCFA)</label>
                                                                <input type="number" min="0" class="form-control" placeholder="Prix" name="bagage_prix[]" autocomplete="off">
                                                            </div>
                                                            <div class="col-md-1">
                                                                <button type="button" class="btn btn-outline-danger remove-bagage-btn d-none w-100" title="Retirer cette ligne">
                                                                    <i class="bx bx-trash"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <button type="button" id="addBagageRow" class="btn btn-sm btn-outline-primary">
                                                        <i class="bx bx-plus"></i> Ajouter un bagage
                                                    </button>
                                                </div>
                                            </div>


                                            <div class="mb-3 row">
                                                <label class="col-lg-4 col-form-label" for="validationCustom06">
                                                    Numero de billets
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <div class="col-lg-8">
                                                    <input type="text" class="form-control"
                                                        id="validationCustom06" name="numeroBillets"
                                                        value="<?= genererNumeroBillet() ?>" readonly>
                                                </div>
                                            </div>

                                            <div class="mb-3 row">
                                                <div class="col-lg-8 ms-auto">
                                                    <button type="submit" name="save" class="btn btn-primary px-4">Enregistre</button>

                                                    <a href="<?= BASE_URL ?>/admin/Liste_du_jours"><button type="button"
                                                            class="btn btn-primary px-4 split-bg-primary ">
                                                            Liste clients
                                                        </button></a>

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
    <script src="<?= ASSET_URL ?>/mon_js/thermal-print.js"></script>
    <script>
        // Le ticket est la preuve de paiement remise au client : imprimé automatiquement
        // dès l'arrivée sur cette page après enregistrement d'un billet (cf.
        // Add_billet::saveBillets(), paramètre ?billetImprime=<idBillets> dans l'URL de
        // redirection). Nettoie ensuite l'URL pour qu'un rechargement de page ne réimprime pas.
        (function () {
            const params = new URLSearchParams(window.location.search);
            const idBillet = params.get('billetImprime');
            if (idBillet) {
                imprimerBilletThermique(idBillet, { auto: true });
                params.delete('billetImprime');
                const nouvelleUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
                window.history.replaceState({}, '', nouvelleUrl);
            }
        })();
    </script>

    <script>
        const dateInput = document.getElementById('jourVoyage');

        (function initDateLimits() {
            const today = new Date();
            const maxDate = new Date(today);
            maxDate.setDate(today.getDate() + 6);
            const toISO = d => d.toISOString().slice(0, 10);
            dateInput.min = toISO(today);
            dateInput.max = toISO(maxDate);
            dateInput.value = dateInput.min;
        })();

        // Prix resolu cote serveur pour une heure non listee (cf. rafraichirEtatCreneau) :
        // aucun data-prix disponible sur un <option> catalogue pour une heure saisie
        // librement, il faut donc le recuperer par AJAX. null = aucun tarif sur (destination
        // sans heure programmee du tout, ou plusieurs tarifs differents selon l'heure).
        let prixUnitaireHeureLibre = null;

        function calculerPrixTotal() {
            const nbPassagers = parseInt(document.getElementById('nombrePassagers').value) || 0;
            let prixUnitaire;
            if (heureNonListee.checked) {
                if (prixUnitaireHeureLibre === null) {
                    document.getElementById('prix').value = '';
                    document.getElementById('prixTotal').value = 'Tarif introuvable pour cette heure — choisissez une heure déjà listée';
                    return;
                }
                prixUnitaire = prixUnitaireHeureLibre;
            } else {
                const selectedOption = programmeSelect.options[programmeSelect.selectedIndex];
                prixUnitaire = parseFloat(selectedOption.dataset.prix || 0);
            }
            const total = prixUnitaire * nbPassagers;
            document.getElementById('prix').value = prixUnitaire;
            document.getElementById('prixTotal').value = isNaN(total) ? '' : `${total.toLocaleString()} FCFA`;
        }

        function gererAffichagePrixSelonEscale() {
            const escalesCochees = document.querySelectorAll('input[name="escales[]"]:checked');
            if (escalesCochees.length > 0) {
                prixAutoBox.classList.add('d-none');
                prixManuelBox.classList.remove('d-none');
            } else {
                prixAutoBox.classList.remove('d-none');
                prixManuelBox.classList.add('d-none');
            }
        }

        // Pour un Admin, les destinations de TOUTES les gares sont préchargées d'un coup
        // (destinationsParGare) : changer "Départ (gare)" ne fait qu'échanger quel jeu de
        // données JS est utilisé, sans recharger la page (avant : location.href complet).
        <?php if (($_SESSION['droit'] ?? null) === 'Admin'): ?>
        const destinationsParGare = <?= json_encode($destinationsParGare ?? []) ?>;
        <?php endif; ?>
        let destinations = <?= json_encode($destinations) ?>;
        const destinationSelect = document.getElementById('destinationSelect');
        const programmeSelect = document.getElementById('programmeSelect');
        const heureNonListee = document.getElementById('heureNonListee');
        const heureLibreInput = document.getElementById('heureLibreInput');
        const escalesList = document.getElementById('escalesList');
        const prixManuelInput = document.getElementById('prixManuel');
        const prixManuelBox = document.getElementById('prixManuelBox');

        // Les escales sont des boutons radio : nativement, impossible de revenir à "aucune
        // escale" une fois qu'on en a coché une. On permet de la décocher en cliquant une
        // seconde fois sur celle déjà sélectionnée (délégué sur escalesList, qui reste le
        // même élément même quand son contenu est reconstruit à chaque changement d'heure).
        escalesList.addEventListener('mousedown', function(event) {
            const radio = event.target.closest('input[type="radio"][name="escale"]');
            if (radio) radio.dataset.tgEtaitCoche = radio.checked ? '1' : '0';
        });
        escalesList.addEventListener('click', function(event) {
            const radio = event.target.closest('input[type="radio"][name="escale"]');
            if (radio && radio.dataset.tgEtaitCoche === '1') {
                radio.checked = false;
                radio.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });

        function rebuildDestinationOptions() {
            destinationSelect.innerHTML = '<option value="">Choisissez une destination</option>' +
                destinations.map((dest, i) => {
                    const nomEchappe = String(dest.nom).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
                    return `<option value="${i}" data-id="${nomEchappe}">${nomEchappe}</option>`;
                }).join('');
            document.getElementById('hiddenDestinationId').value = '';
            programmeSelect.innerHTML = '<option value="">Sélectionnez une heure</option>';
            escalesList.innerHTML = '';
            resetEtatCreneau();
        }

        <?php if (($_SESSION['droit'] ?? null) === 'Admin'): ?>
        document.getElementById('departSelect').addEventListener('change', function() {
            destinations = destinationsParGare[this.value] || [];
            rebuildDestinationOptions();
        });
        <?php endif; ?>

        escalesList.addEventListener('change', () => {
            const checkedEscale = escalesList.querySelector('input[type="radio"]:checked');
            const escaleInput = document.getElementById('escaleSelected');
            if (checkedEscale) {
                escaleInput.value = checkedEscale.value;
                prixManuelBox.classList.remove('d-none');
                prixManuelInput.value = checkedEscale.dataset.prix || 0;
            } else {
                escaleInput.value = '';
                prixManuelBox.classList.add('d-none');
                prixManuelInput.value = '';
            }
        });

        const dateEqToday = sel => sel === (new Date().toISOString().slice(0, 10));
        const hourIsPast = (hstr) => {
            const [h, m] = hstr.split(':').map(Number);
            const now = new Date();
            return (h < now.getHours()) || (h === now.getHours() && m <= now.getMinutes());
        };

        function updateProgrammeOptions() {
            const destIdx = destinationSelect.value;
            const selectedDate = dateInput.value;
            const isToday = dateEqToday(selectedDate);
            const programmes = (destinations[destIdx] ?? {}).programmes ?? [];
            programmeSelect.innerHTML =
                '<option value="">Sélectionnez une heure</option>' +
                programmes
                .map(p => {
                    // Une heure deja passee aujourd'hui reste selectionnable (le car peut
                    // partir en retard, ou l'agent vend apres coup) : juste signalee, jamais
                    // masquee - sans ca, plus aucune vente n'etait possible des que la
                    // derniere heure programmee de la journee etait depassee.
                    const passee = isToday && hourIsPast(p.heureDepart);
                    const label = p.heureDepart + (passee ? ' (déjà passée)' : '');
                    return `<option value="${p.heureDepart}" data-prix="${p.prix}" data-id-programmer="${p.idProgrammer}" data-escales='${JSON.stringify(p.escales)}'>${label}</option>`;
                }).join('');
            if (programmeSelect.options.length === 1) {
                programmeSelect.innerHTML += '<option disabled>(Aucune heure programmée pour cette destination)</option>';
            }
            escalesList.innerHTML = '';
            resetEtatCreneau();
        }

        // Visible uniquement pour un voyage d'aujourd'hui (cf. rafraichirEtatCreneau) : soit un
        // bandeau informatif (car déjà programmé), soit un select de car à choisir (programmation
        // créée à la volée lors de l'enregistrement du billet). Masqué/réinitialisé sinon.
        function resetEtatCreneau() {
            const row = document.getElementById('etatCreneauRow');
            const bandeau = document.getElementById('bandeauCarProgramme');
            const choixBox = document.getElementById('choixCarBox');
            const select = document.getElementById('carChoisiSelect');
            row.classList.add('d-none');
            bandeau.classList.add('d-none');
            bandeau.textContent = '';
            choixBox.classList.add('d-none');
            select.innerHTML = '<option value="">Choisissez un car disponible</option>';
            select.required = false;
        }

        function rafraichirEtatCreneau() {
            resetEtatCreneau();

            const isToday = dateEqToday(dateInput.value);
            // Heure non cataloguée (case "Heure non listée" cochée) ou heure choisie dans le
            // select des heures programmées : dans les deux cas, on verifie le creneau par
            // heure + destination, jamais par idProgrammer (qui n'existe pas pour une heure
            // libre, saisie a la main quand aucune heure programmee ne convient).
            const heureDepart = heureNonListee.checked ? heureLibreInput.value : programmeSelect.value;
            const destinationLocalite = document.getElementById('hiddenDestinationId').value;

            // Seule la vente d'AUJOURD'HUI dépend d'un car programmé : demain et les jours
            // suivants passent par un mécanisme de quota (places minimales), sans car assigné.
            if (!isToday || !heureDepart || !destinationLocalite) return;

            const data = { heureDepart: heureDepart, destinationLocalite: destinationLocalite };
            <?php if (($_SESSION['droit'] ?? null) === 'Admin'): ?>
            data.idDepart = document.getElementById('departSelect').value;
            <?php endif; ?>

            $.ajax({
                url: '<?= BASE_URL ?>/admin/Add_billets/ajaxEtatCreneau',
                type: 'POST',
                data: data,
                dataType: 'json',
                success: function(response) {
                    const row = document.getElementById('etatCreneauRow');
                    const bandeau = document.getElementById('bandeauCarProgramme');
                    const choixBox = document.getElementById('choixCarBox');
                    const select = document.getElementById('carChoisiSelect');

                    // Heure non listée : pas de data-prix côté catalogue, le prix (ou son
                    // absence, si tarifs différents selon l'heure pour cette destination)
                    // vient donc de cette réponse AJAX plutôt que d'un <option>.
                    if (heureNonListee.checked) {
                        prixUnitaireHeureLibre = (response.prix_unitaire !== null && response.prix_unitaire !== undefined)
                            ? parseFloat(response.prix_unitaire) : null;
                        calculerPrixTotal();
                    }

                    row.classList.remove('d-none');

                    if (response.erreur) {
                        bandeau.classList.remove('d-none');
                        bandeau.className = 'alert alert-danger';
                        bandeau.textContent = response.erreur;
                        return;
                    }

                    if (response.existe) {
                        bandeau.classList.remove('d-none');
                        bandeau.className = 'alert alert-info';
                        bandeau.textContent = `Car n°${response.numero_car} (${response.matriculle}) déjà programmé — ${response.places_restantes} place(s) restante(s).`;
                    } else if (response.cars && response.cars.length > 0) {
                        choixBox.classList.remove('d-none');
                        select.required = true;
                        select.innerHTML = '<option value="">Choisissez un car disponible</option>' +
                            response.cars.map(c =>
                                `<option value="${c.id_car}">Car n°${c.numero_car} (${c.matriculle}) — ${c.places_restantes} places</option>`
                            ).join('');
                    } else {
                        bandeau.classList.remove('d-none');
                        bandeau.className = 'alert alert-warning';
                        bandeau.textContent = "Aucun car disponible à cette gare pour l'instant.";
                    }
                },
                error: function() {
                    const row = document.getElementById('etatCreneauRow');
                    const bandeau = document.getElementById('bandeauCarProgramme');
                    row.classList.remove('d-none');
                    bandeau.classList.remove('d-none');
                    bandeau.className = 'alert alert-danger';
                    bandeau.textContent = "Erreur lors de la vérification du créneau.";
                }
            });
        }

        // dateInput/destinationSelect invalident déjà l'état (resetEtatCreneau appelé depuis
        // updateProgrammeOptions()/rebuildDestinationOptions()) : il suffit de réagir au choix
        // effectif d'une heure, qui survient forcément après que ces resets aient eu lieu.
        programmeSelect.addEventListener('change', rafraichirEtatCreneau);

        // "Heure non listée" : permet de saisir une heure qui n'existe dans aucun programme
        // (ex: toutes les heures programmées de la journée sont déjà passées). Un seul des
        // deux champs porte name="programme" a la fois (l'autre est disabled, donc exclu du
        // POST) : jamais les deux en meme temps.
        heureNonListee.addEventListener('change', function() {
            const actif = this.checked;
            programmeSelect.classList.toggle('d-none', actif);
            programmeSelect.disabled = actif;
            heureLibreInput.classList.toggle('d-none', !actif);
            heureLibreInput.disabled = !actif;
            escalesList.innerHTML = '';
            prixUnitaireHeureLibre = null;
            calculerPrixTotal();
            rafraichirEtatCreneau();
        });
        heureLibreInput.addEventListener('change', rafraichirEtatCreneau);

        destinationSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const destinationId = selectedOption.dataset.id;
            document.getElementById('hiddenDestinationId').value = destinationId;
            updateProgrammeOptions();
        });

        function gererAffichagePrixSelonEscale() {
            const checkedEscales = escalesList.querySelectorAll('input[type="checkbox"]:checked');
            if (checkedEscales.length > 0) {
                const escale = checkedEscales[0];
                document.getElementById('escaleSelected').value = escale.value;
                prixManuelInput.value = escale.dataset.prix || '';
                prixManuelBox.classList.remove('d-none');
            } else {
                document.getElementById('escaleSelected').value = '';
                prixManuelInput.value = '';
                prixManuelBox.classList.add('d-none');
            }
        }

        escalesList.addEventListener('change', gererAffichagePrixSelonEscale);

        programmeSelect.addEventListener('change', function() {
            const escales = JSON.parse(this.options[this.selectedIndex].dataset.escales || '[]');
            escalesList.innerHTML =
                escales.length ?
                escales.map(e => `
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio"
                    name="escale" id="escale_${e.id}"
                    value="${e.escale_nom}"
                    data-prix="${e.prix_escale || 0}">
                <label class="form-check-label" for="escale_${e.id}">
                    ${e.escale_nom} - ${e.prix_escale || 0} FCFA
                </label>
            </div>
        `).join('') :
                '<em>Pas d\'escale pour ce trajet.</em>';

            document.querySelectorAll('input[name="escales[]"]').forEach(chk => {
                chk.addEventListener('change', gererAffichagePrixSelonEscale);
            });

            calculerPrixTotal();
            gererAffichagePrixSelonEscale();
        });

        destinationSelect.addEventListener('change', updateProgrammeOptions);
        dateInput.addEventListener('change', updateProgrammeOptions);
        updateProgrammeOptions();

        // Bagages payants (optionnel) : "add to row", mêmes JS que "Ajout de camions"
        // (camions.view.php) — une ligne par bagage (description/poids/prix).
        document.addEventListener('DOMContentLoaded', function() {
            const afficherBagages = document.getElementById('afficherBagages');
            const bagagesBox = document.getElementById('bagagesBox');
            const bagagesRows = document.getElementById('bagagesRows');
            const addBagageBtn = document.getElementById('addBagageRow');

            afficherBagages.addEventListener('change', function() {
                bagagesBox.classList.toggle('d-none', !this.checked);
            });

            function toggleRemoveBagageButtons() {
                const rows = bagagesRows.querySelectorAll('.bagage-row');
                rows.forEach(function(row) {
                    row.querySelector('.remove-bagage-btn').classList.toggle('d-none', rows.length <= 1);
                });
            }

            addBagageBtn.addEventListener('click', function() {
                const firstRow = bagagesRows.querySelector('.bagage-row');
                const newRow = firstRow.cloneNode(true);
                newRow.querySelectorAll('input').forEach(function(input) { input.value = ''; });
                bagagesRows.appendChild(newRow);
                toggleRemoveBagageButtons();
            });

            bagagesRows.addEventListener('click', function(e) {
                const btn = e.target.closest('.remove-bagage-btn');
                if (btn) {
                    btn.closest('.bagage-row').remove();
                    toggleRemoveBagageButtons();
                }
            });
        });

        document.addEventListener('DOMContentLoaded', () => {
            const escalesList = document.getElementById('escalesList');
            const prixInput = document.getElementById('prix');
            const prixTotalInput = document.getElementById('prixTotal');
            const passagersInput = document.getElementById('nombrePassagers');
            const programmeSelect = document.getElementById('programmeSelect');
            let nbPassagers = parseInt(passagersInput.value) || 1;

            passagersInput.addEventListener('input', () => {
                nbPassagers = parseInt(passagersInput.value) || 1;
                calculerPrix();
            });

            function afficherEscales() {
                const selectedOption = programmeSelect.options[programmeSelect.selectedIndex];
                if (!selectedOption) { escalesList.innerHTML = ''; return; }
                const escales = JSON.parse(selectedOption.dataset.escales || '[]');
                escalesList.innerHTML = escales.length ?
                    escales.map(e => `
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio"
                        name="escale" id="escale_${e.id}"
                        value="${e.escale_nom}"
                        data-prix="${e.prix_escale || 0}">
                    <label class="form-check-label" for="escale_${e.id}">
                        ${e.escale_nom} - ${e.prix_escale || 0} FCFA
                    </label>
                </div>
            `).join('') :
                    '<em>Pas d\'escale pour ce trajet.</em>';
            }

            function calculerPrix() {
                const selectedOption = programmeSelect.options[programmeSelect.selectedIndex];
                if (!selectedOption) return;
                const prixProgramme = parseFloat(selectedOption.dataset.prix || 0);
                const escaleSelectionnee = escalesList.querySelector('input[type="radio"]:checked');
                const prixUnitaire = escaleSelectionnee ?
                    parseFloat(escaleSelectionnee.dataset.prix || 0) : prixProgramme;
                const total = prixUnitaire * nbPassagers;
                prixInput.value = prixUnitaire || '';
                prixTotalInput.value = total > 0 ? `${total.toLocaleString('fr-FR')} FCFA` : '';
            }

            escalesList.addEventListener('change', calculerPrix);
            programmeSelect.addEventListener('change', () => { afficherEscales(); calculerPrix(); });
            afficherEscales();
            calculerPrix();
        });
    </script>

</body>

</html>