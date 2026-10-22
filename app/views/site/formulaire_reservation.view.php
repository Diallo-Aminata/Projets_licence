<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Réservation - TransGest</title>
    <link href="<?= ASSET_URL ?>/assets_site/css/inter.css" rel="stylesheet">
    <link href="<?= ASSET_URL ?>/assets_site/css/all.min.css" rel="stylesheet">
    <link href="<?= ASSET_URL ?>/assets_site/css/aos.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f5f7fb;
            color: #1a1f2e;
            overflow-x: hidden;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ========== VARIABLES ========== */
        :root {
            --primary: #0f3b5e;
            --primary-dark: #0a2a44;
            --primary-light: #1a5276;
            --secondary: #e67e22;
            --secondary-dark: #c0392b;
            --accent: #3498db;
            --success: #27ae60;
            --warning: #f39c12;
            --gray-light: #ecf0f1;
            --gray: #7f8c8d;
            --dark: #2c3e50;
            --white: #ffffff;
            --shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 5px 20px rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 15px 35px rgba(0, 0, 0, 0.1);
            --radius: 8px;
            --radius-lg: 16px;
            --radius-xl: 24px;
        }

        /* ========== HEADER ========== */
        .header {
            background: white;
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .header-inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 0;
        }
        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 24px;
        }
        .logo {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary);
            text-decoration: none;
        }
        .logo span {
            color: var(--secondary);
        }
        .nav {
            display: flex;
            gap: 32px;
            align-items: center;
        }
        .nav a {
            text-decoration: none;
            color: var(--dark);
            font-weight: 500;
            transition: color 0.3s;
        }
        .nav a:hover, .nav a.active {
            color: var(--secondary);
        }
        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
            padding: 8px 20px;
            border-radius: var(--radius);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-outline:hover {
            background: var(--primary);
            color: white;
        }
        .menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.8rem;
            color: var(--primary);
            cursor: pointer;
        }
        .mobile-nav {
            position: fixed;
            top: 0;
            right: -100%;
            width: 80%;
            max-width: 350px;
            height: 100vh;
            background: white;
            box-shadow: -5px 0 30px rgba(0, 0, 0, 0.1);
            z-index: 2000;
            padding: 80px 30px 30px;
            transition: right 0.3s ease;
        }
        .mobile-nav.active { right: 0; }
        .mobile-nav a {
            display: block;
            padding: 15px 0;
            text-decoration: none;
            color: var(--dark);
            font-weight: 500;
            border-bottom: 1px solid #eee;
        }
        .close-menu {
            position: absolute;
            top: 20px;
            right: 20px;
            background: none;
            border: none;
            font-size: 1.8rem;
            cursor: pointer;
        }
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1500;
            display: none;
        }
        .overlay.active { display: block; }

        /* ========== PAGE HEADER avec IMAGE DE FOND (nouvelle) ========== */
        .page-header {
            position: relative;
            color: white;
            padding: 100px 0;
            text-align: center;
            overflow: hidden;
            min-height: 350px;
            display: flex;
            align-items: center;
        }
        .page-header-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: 0;
        }
        .page-header-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(15, 59, 94, 0.6) 0%, rgba(10, 42, 68, 0.5) 100%);
            z-index: 1;
        }
        .page-header .container {
            position: relative;
            z-index: 2;
        }
        .page-header h1 {
            font-size: 3rem;
            margin-bottom: 16px;
            font-weight: 800;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        .page-header p {
            font-size: 1.1rem;
            opacity: 0.95;
            max-width: 600px;
            margin: 0 auto;
            text-shadow: 0 1px 5px rgba(0,0,0,0.3);
        }

        /* ========== FORMULAIRE ========== */
        .booking-section {
            padding: 60px 0;
        }
        .booking-card {
            background: white;
            border-radius: var(--radius-xl);
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            display: grid;
            grid-template-columns: 1fr 1fr;
        }
        .booking-image {
            position: relative;
            overflow: hidden;
            min-height: 100%;
        }
        .booking-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        .booking-image:hover img {
            transform: scale(1.05);
        }
        .booking-image-overlay {
            position: absolute;
            bottom: 30px;
            left: 0;
            right: 0;
            text-align: center;
            color: white;
            z-index: 2;
        }
        .booking-image-overlay p {
            font-size: 1rem;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(5px);
            display: inline-block;
            padding: 10px 24px;
            border-radius: 50px;
            font-weight: 600;
        }
        .booking-form {
            padding: 40px;
        }
        .booking-form h2 {
            font-size: 1.8rem;
            margin-bottom: 8px;
            color: var(--primary);
        }
        .booking-form .subtitle {
            color: var(--gray);
            margin-bottom: 30px;
            font-size: 0.9rem;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            font-size: 0.85rem;
            margin-bottom: 8px;
            color: var(--dark);
        }
        .form-group label i {
            color: var(--secondary);
            margin-right: 6px;
        }
        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: var(--radius);
            font-size: 0.9rem;
            transition: all 0.3s;
            background: #f8fafc;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--secondary);
            background: white;
            box-shadow: 0 0 0 3px rgba(230, 126, 34, 0.1);
        }
        .form-control[readonly] {
            background: #f1f5f9;
            cursor: not-allowed;
        }
        .form-check {
            margin-bottom: 10px;
        }
        .form-check-input {
            margin-right: 10px;
            cursor: pointer;
        }
        .form-check-label {
            cursor: pointer;
            font-size: 0.9rem;
        }
        .row-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .price-box {
            background: linear-gradient(135deg, #fef3e8, #fff5eb);
            border-radius: var(--radius-lg);
            padding: 15px 20px;
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .price-label {
            font-weight: 600;
            color: var(--dark);
        }
        .price-value {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--secondary);
        }
        .price-value small {
            font-size: 0.8rem;
            font-weight: normal;
        }
        .btn-reserver {
            background: linear-gradient(135deg, var(--secondary), var(--secondary-dark));
            color: white;
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: var(--radius);
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .btn-reserver:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(230, 126, 34, 0.3);
        }
        .info-badge {
            background: #e8f4fd;
            border-radius: var(--radius);
            padding: 12px 16px;
            margin-top: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.8rem;
            color: var(--primary);
        }
        .info-badge i {
            font-size: 1.2rem;
        }

        /* ========== FOOTER ========== */
        .footer {
            background: #1a1f2e;
            color: #94a3b8;
            padding: 48px 0 24px;
            margin-top: auto;
        }
        .footer-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 48px;
            margin-bottom: 48px;
        }
        .footer h4 {
            color: white;
            font-size: 1rem;
            margin-bottom: 20px;
        }
        .footer a {
            color: #94a3b8;
            text-decoration: none;
            display: block;
            margin-bottom: 12px;
            font-size: 0.85rem;
        }
        .footer a:hover { color: var(--secondary); }
        .footer-bottom {
            text-align: center;
            padding-top: 24px;
            border-top: 1px solid #334155;
            font-size: 0.75rem;
        }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 992px) {
            .booking-card {
                grid-template-columns: 1fr;
            }
            .booking-image {
                min-height: 300px;
            }
            .booking-image img {
                height: 300px;
            }
            .footer-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 768px) {
            .nav { display: none; }
            .menu-toggle { display: block; }
            .row-grid { grid-template-columns: 1fr; gap: 15px; }
            .booking-form { padding: 25px; }
            .booking-form h2 { font-size: 1.4rem; }
            .footer-grid { grid-template-columns: 1fr; text-align: center; }
            .page-header h1 { font-size: 2rem; }
            .page-header { padding: 60px 0; min-height: 280px; }
            .page-header p { font-size: 0.9rem; }
        }
        @media (max-width: 480px) {
            .booking-form { padding: 20px; }
            .price-value { font-size: 1.4rem; }
        }
    </style>
</head>
<body>

<!-- HEADER -->
<?php $this->view('site/partials/nav') ?>


<!-- PAGE HEADER avec NOUVELLE IMAGE DE FOND (route et montagnes) -->
<section class="page-header">
    <img src="<?= ASSET_URL ?>/assets_site/img/reservation-bg.jpg" alt="Route de montagne" class="page-header-bg">
    <div class="page-header-overlay"></div>
    <div class="container">
        <h1 data-aos="fade-up">Réservez votre voyage</h1>
        <p data-aos="fade-up" data-aos-delay="100">Remplissez le formulaire ci-dessous pour réserver vos billets en toute simplicité</p>
    </div>
</section>

<?php
$t = $trajet ?? [];
$this->view("admin/helpers");
?>
<!-- FORMULAIRE DE RÉSERVATION -->
<section class="booking-section">
    <div class="container">
        <div class="booking-card" data-aos="fade-up">
            <!-- Partie Image - Bus avec des passagers (NOUVELLE IMAGE) -->
            <div class="booking-image">
                <img src="<?= ASSET_URL ?>/assets_site/img/reservation-bus.jpg" alt="Passagers dans un bus">
                <div class="booking-image-overlay">
                    <p><i class="fas fa-users"></i> Voyagez avec vos proches</p>
                </div>
            </div>

            <!-- Formulaire -->
            <div class="booking-form">
                <h2>Planifiez votre voyage</h2>
                <p class="subtitle">Voyagez confortablement avec TransGest</p>

                <form method="post" action="" id="bookingForm">
                    <?= csrf_field() ?>
                    <div class="row-grid">
                        <!-- Départ -->
                        <div class="form-group">
                            <label><i class="fas fa-map-marker-alt"></i> Départ</label>
                            <input type="text" name="depart" value="<?= htmlspecialchars(($t['depart'] ?? '') . ' ( ' . ($t['numeroGare1'] ?? '') . ' )') ?>" class="form-control" readonly>
                            <input type="hidden" name="departId" value="<?= htmlspecialchars($t['depart'] ?? '') ?>">
                            <input type="hidden" name="numeroGare" value="<?= htmlspecialchars($t['numeroGare1'] ?? '') ?>">
                        </div>

                        <!-- Destination -->
                        <div class="form-group">
                            <label><i class="fas fa-flag-checkered"></i> Destination</label>
                            <input type="text" name="destination" value="<?= htmlspecialchars(($t['destination'] ?? '') . ' ( ' . ($t['numeroGare1'] ?? '') . ' )') ?>" class="form-control" readonly>
                            <input type="hidden" name="destinationId" value="<?= htmlspecialchars($t['destination'] ?? '') ?>">
                            <input type="hidden" name="id_compagnie" value="<?= htmlspecialchars($t['id_compagnie'] ?? '') ?>">
                        </div>
                    </div>

                    <!-- Escales -->
                    <div class="form-group">
                        <label><i class="fas fa-code-branch"></i> Escale optionnelle</label>
                        <?php
                        if (!empty($t['escales_avec_frais'])) {
                            $escales = explode(', ', $t['escales_avec_frais']);
                            foreach ($escales as $index => $escale) {
                                if (preg_match('/^(.*?)\s*\((\d+)\s*FCFA\)/', $escale, $matches)) {
                                    $ville = trim($matches[1]);
                                    $prix = intval($matches[2]);
                                } else {
                                    continue;
                                }
                        ?>
                                <div class="form-check">
                                    <input class="form-check-input escale-radio" type="radio" name="escale_finale" id="escale_<?= $index ?>" value="<?= htmlspecialchars($ville) ?>" data-prix="<?= $prix ?>">
                                    <label class="form-check-label" for="escale_<?= $index ?>"><?= htmlspecialchars($ville) ?> <span class="text-muted">(+<?= number_format($prix, 0, ',', ' ') ?> FCFA)</span></label>
                                </div>
                        <?php
                            }
                        } else {
                            echo '<p class="text-muted fst-italic" style="font-size: 0.9rem;">Pas d\'escale disponible</p>';
                        }
                        ?>
                    </div>

                    <div class="row-grid">
                        <!-- Date -->
                        <div class="form-group">
                            <label><i class="fas fa-calendar-alt"></i> Date du voyage</label>
                             <?php
                             $today = date('Y-m-d');
                             $maxDate = date('Y-m-d', strtotime('+6 days'));
                             ?>
                             <input type="date" name="jourVoyage" class="form-control" min="<?= $today ?>" max="<?= $maxDate ?>" value="<?= $today ?>">
                        </div>

                        <!-- Heure -->
                        <div class="form-group">
                            <label><i class="fas fa-clock"></i> Heure de départ</label>
                            <input type="text" name="Heur_departs" value="<?= htmlspecialchars($t['heure_depart'] ?? '') ?>" class="form-control" readonly>
                        </div>
                    </div>

                    <div class="row-grid">
                        <!-- Nom complet -->
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> Nom complet</label>
                            <input type="text" name="Client" class="form-control" placeholder="Ex: Diallo Aminata" required>
                        </div>

                        <!-- Nombre passagers -->
                        <div class="form-group">
                            <label><i class="fas fa-users"></i> Nombre de passagers</label>
                            <input type="number" name="nombrePassages" id="nombrePassages" class="form-control" min="1" value="1" required>
                        </div>
                    </div>

                    <div class="row-grid">
                        <!-- Téléphone -->
                        <div class="form-group">
                            <label><i class="fas fa-phone-alt"></i> Téléphone</label>
                            <input type="tel" name="numeroClient" id="numeroClient" class="form-control" placeholder="77 78 88 99" required>
                        </div>

                        <!-- Email -->
                        <div class="form-group">
                            <label><i class="fas fa-envelope"></i> Email</label>
                            <input type="email" name="emailClient" class="form-control" placeholder="exemple@mail.com">
                        </div>
                    </div>

                    <div class="row-grid">
                        <!-- Numéro paiement -->
                        <div class="form-group">
                            <label><i class="fas fa-credit-card"></i> Numéro de paiement</label>
                            <input type="text" name="numeroPaiement" id="numeroPaiement" class="form-control" placeholder="66 77 88 99" required>
                        </div>

                        <!-- Code marchand -->
                        <div class="form-group">
                            <label><i class="fas fa-qrcode"></i> Code marchand</label>
                            <input type="text" name="code_marchand" class="form-control" value="<?= htmlspecialchars($t['codeDepart'] ?? '') ?>" readonly>
                        </div>
                    </div>

                    <div class="row-grid">
                        <!-- Numéro billet -->
                        <div class="form-group">
                            <label><i class="fas fa-ticket-alt"></i> Numéro de billet</label>
                            <input type="text" name="numeroBillets" class="form-control" value="<?= genererNumeroBillet() ?>" readonly>
                        </div>

                        <!-- Prix -->
                        <div class="form-group">
                            <label><i class="fas fa-money-bill-wave"></i> Prix unitaire</label>
                            <input type="text" name="prix_unitaire" id="prix_unitaire" value="<?= number_format(intval($t['prix'] ?? 0), 0, ',', ' ') ?> FCFA" class="form-control" readonly>
                            <input type="hidden" name="montant_payer" id="montant_payer" value="<?= intval($t['prix'] ?? 0) ?>">
                        </div>
                    </div>

                    <!-- Prix total dynamique -->
                    <div class="price-box">
                        <span class="price-label"><i class="fas fa-calculator"></i> Total à payer</span>
                        <span class="price-value"><span id="totalPrice">5 000</span> <small>FCFA</small></span>
                    </div>

                    <!-- Informations supplémentaires -->
                    <div class="info-badge">
                        <i class="fas fa-shield-alt"></i>
                        <span>Votre réservation est sécurisée. Un email de confirmation vous sera envoyé après validation du paiement.</span>
                    </div>

                    <button type="submit" name="reserver" class="btn-reserver">
                        <i class="fas fa-check-circle"></i> Réserver maintenant
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- FOOTER -->
<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div>
                <h4>TransGest</h4>
                <p style="font-size: 0.85rem;">La plateforme N°1 de réservation de billets de bus et suivi de colis au Mali.</p>
            </div>
            <div>
                <h4>Liens rapides</h4>
                <a href="#">Accueil</a>
                <a href="#">Compagnies</a>
                <a href="#">Destinations</a>
                <a href="#">Contact</a>
            </div>
            <div>
                <h4>Support</h4>
                <a href="#">FAQ</a>
                <a href="#">Conditions générales</a>
                <a href="#">Politique de confidentialité</a>
            </div>
            <div>
                <h4>Contact</h4>
                <a href="tel:+22374167088"><i class="fas fa-phone"></i> +223 74 16 70 88</a>
                <a href="mailto:transgest@gmail.com"><i class="fas fa-envelope"></i> transgest@gmail.com</a>
                <a href="#"><i class="fas fa-map-marker-alt"></i> Pelegana, Segou, Mali</a>
            </div>
        </div>
        <div class="footer-bottom">
            <p>Copyright &copy; 2026 Computer Service Barry. All rights reserved.</p>
        </div>
    </div>
</footer>

<script src="<?= ASSET_URL ?>/assets_site/js/aos.js"></script>
<script>
    AOS.init({ duration: 600, once: true, offset: 50 });

    // Mobile Menu
    const menuToggle = document.getElementById('menuToggle');
    const mobileNav = document.getElementById('mobileNav');
    const closeMenu = document.getElementById('closeMenu');
    const overlay = document.getElementById('overlay');
    
    function openMenu() { 
        mobileNav.classList.add('active'); 
        overlay.classList.add('active'); 
        document.body.style.overflow = 'hidden'; 
    }
    
    function closeMenuFunc() { 
        mobileNav.classList.remove('active'); 
        overlay.classList.remove('active'); 
        document.body.style.overflow = ''; 
    }
    
    menuToggle.addEventListener('click', openMenu);
    closeMenu.addEventListener('click', closeMenuFunc);
    overlay.addEventListener('click', closeMenuFunc);
    
    window.addEventListener('resize', () => { 
        if (window.innerWidth > 768) closeMenuFunc(); 
    });

    // Format Mali Phone
    function formatMaliPhone(inputId) {
        document.getElementById(inputId).addEventListener('input', function() {
            let val = this.value.replace(/\D/g, '');
            val = val.slice(0, 8);
            let formatted = val;
            if (val.length > 2) formatted = val.slice(0, 2) + ' ' + val.slice(2);
            if (val.length > 4) formatted = formatted.slice(0, 5) + ' ' + formatted.slice(5);
            if (val.length > 6) formatted = formatted.slice(0, 8) + ' ' + formatted.slice(8);
            this.value = formatted;
        });
    }

    formatMaliPhone('numeroClient');
    formatMaliPhone('numeroPaiement');

    // Calcul dynamique du prix total
    let prixBase = <?= intval($t['prix'] ?? 0) ?>;
    let prixUnitaireActuel = prixBase;
    
    const nombrePassagesInput = document.getElementById('nombrePassages');
    const totalPriceSpan = document.getElementById('totalPrice');
    const montantPayerInput = document.getElementById('montant_payer');
    const escaleRadios = document.querySelectorAll('.escale-radio');

    function updateTotalPrice() {
        let nbPassagers = parseInt(nombrePassagesInput.value) || 1;
        let total = prixUnitaireActuel * nbPassagers;
        
        totalPriceSpan.textContent = total.toLocaleString('fr-FR');
        if(montantPayerInput) montantPayerInput.value = total;
    }

    escaleRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            const prixEscale = parseInt(this.dataset.prix) || 0;
            prixUnitaireActuel = prixEscale;
            updateTotalPrice();
        });
    });

    nombrePassagesInput.addEventListener('input', updateTotalPrice);
    updateTotalPrice();
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php if (isset($_SESSION['toast'])): ?>
    <script>
        Swal.fire({
            position: "top-end",
            icon: "info",
            title: "<?= addslashes($_SESSION['toast']['title']); ?>",
            text: "<?= addslashes($_SESSION['toast']['text']); ?>",
            showConfirmButton: true,
            confirmButtonText: "OK",
            background: "<?= $_SESSION['toast']['bg'] ?: '#fff'; ?>",
            color: "#000"
        }).then(() => {
            <?php if (!empty($_SESSION['toast']['url'])): ?>
                window.location.href = "<?= $_SESSION['toast']['url']; ?>";
            <?php endif; ?>
        });
    </script>
    <?php unset($_SESSION['toast']); ?>
<?php endif; ?>
</body>
</html>