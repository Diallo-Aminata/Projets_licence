<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Nos Compagnies - TransGest</title>
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

        /* ========== TYPOGRAPHY ========== */
        h1, h2, h3, h4, h5, h6 {
            font-weight: 700;
            margin-bottom: 1rem;
        }

        /* ========== LAYOUT ========== */
        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 24px;
        }

        section {
            padding: 60px 0;
        }

        /* ========== BUTTONS ========== */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 28px;
            border-radius: var(--radius);
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s;
            cursor: pointer;
            border: none;
            text-decoration: none;
        }
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: var(--secondary);
            color: white;
        }
        .btn-secondary:hover {
            background: var(--secondary-dark);
            transform: translateY(-2px);
        }
        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
        }
        .btn-outline:hover {
            background: var(--primary);
            color: white;
        }
        .btn-outline-light {
            background: transparent;
            border: 2px solid white;
            color: white;
        }
        .btn-outline-light:hover {
            background: white;
            color: var(--primary);
        }
        .btn-block {
            width: 100%;
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
        .mobile-nav.active {
            right: 0;
        }
        .mobile-nav a {
            display: block;
            padding: 15px 0;
            text-decoration: none;
            color: var(--dark);
            font-weight: 500;
            border-bottom: 1px solid #eee;
        }
        .mobile-nav a:hover, .mobile-nav a.active {
            color: var(--secondary);
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
        .overlay.active {
            display: block;
        }

        /* ========== PAGE HEADER (BANDEAU) ========== */
        .page-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 60px 0;
            text-align: center;
        }
        .page-header h1 {
            font-size: 2.5rem;
            margin-bottom: 16px;
        }
        .page-header p {
            font-size: 1rem;
            opacity: 0.85;
            max-width: 600px;
            margin: 0 auto;
        }

        /* ========== FILTER SECTION ========== */
        .filter-section {
            margin-top: -30px;
            position: relative;
            z-index: 10;
        }
        .filter-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: 20px 30px;
            box-shadow: var(--shadow-md);
        }
        .filter-group {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
        }
        .search-box {
            display: flex;
            gap: 10px;
            align-items: center;
            background: var(--gray-light);
            padding: 5px 15px;
            border-radius: 50px;
            width: 100%;
            max-width: 360px;
        }
        .search-box input {
            border: none;
            background: none;
            padding: 10px 0;
            width: 200px;
            outline: none;
        }
        .search-box i {
            color: var(--gray);
        }

        /* ========== COMPANY CARDS ========== */
        .company-card {
            background: white;
            border-radius: var(--radius-xl);
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: var(--shadow);
            position: relative;
        }
        .company-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg);
        }
        .company-cover {
            height: 140px;
            background: linear-gradient(135deg, #2563eb, #8b5cf6);
            position: relative;
        }
        .company-logo {
            width: 100px;
            height: 100px;
            background: white;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: -50px auto 0;
            position: relative;
            z-index: 2;
            box-shadow: var(--shadow-md);
        }
        .company-logo i {
            font-size: 3rem;
            color: var(--primary);
        }
        .company-logo img {
            width: 60px;
            height: 60px;
            object-fit: contain;
        }
        .company-content {
            padding: 20px;
            text-align: center;
        }
        .company-name {
            font-size: 1.3rem;
            font-weight: 800;
            margin-bottom: 8px;
        }
        .company-desc {
            font-size: 0.85rem;
            color: var(--gray);
            margin-bottom: 16px;
            line-height: 1.5;
        }
        .company-stats {
            display: flex;
            justify-content: space-around;
            padding: 15px 0;
            border-top: 1px solid #eee;
            border-bottom: 1px solid #eee;
            margin-bottom: 16px;
        }
        .stat {
            text-align: center;
        }
        .stat-value {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--primary);
        }
        .stat-label {
            font-size: 0.7rem;
            color: var(--gray);
        }
        .company-footer {
            display: flex;
            gap: 12px;
        }
        .company-footer .btn {
            flex: 1;
            padding: 10px;
            font-size: 0.8rem;
        }

        /* ========== CTA SECTION ========== */
        .cta-section {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            text-align: center;
            padding: 60px;
            border-radius: var(--radius-xl);
            margin: 40px 0;
        }
        .cta-section h2 {
            color: white;
            font-size: 2rem;
            margin-bottom: 16px;
        }
        .cta-section p {
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 30px;
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
        .footer a:hover {
            color: var(--secondary);
        }
        .footer-bottom {
            text-align: center;
            padding-top: 24px;
            border-top: 1px solid #334155;
            font-size: 0.75rem;
        }

        /* ========== ANIMATION ========== */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 992px) {
            .footer-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 768px) {
            .nav {
                display: none;
            }
            .menu-toggle {
                display: block;
            }
            .filter-group {
                flex-direction: column;
                align-items: stretch;
            }
            .search-box {
                max-width: none;
            }
            .company-stats {
                flex-wrap: wrap;
                gap: 10px;
            }
            .footer-grid {
                grid-template-columns: 1fr;
                text-align: center;
            }
            .cta-section {
                padding: 40px 20px;
            }
            .cta-section h2 {
                font-size: 1.5rem;
            }
        }
        @media (max-width: 480px) {
            .company-footer {
                flex-direction: column;
            }
        }

    </style>
</head>
<body>

<!-- HEADER -->
<?php $this->view('site/partials/nav') ?>

<!-- PAGE HEADER -->
<section class="page-header">
    <div class="container">
        <h1 data-aos="fade-up">Nos compagnies partenaires</h1>
        <p data-aos="fade-up" data-aos-delay="100">Des transporteurs fiables et agréés pour vos déplacements au Mali</p>
    </div>
</section>

<!-- FILTER SECTION -->
<section class="filter-section">
    <div class="container">
        <div class="filter-card" data-aos="fade-up">
            <div class="filter-group">
                <span style="font-weight: 600; color: var(--dark);"><?= count($listecompagnie ?? []) ?> compagnie(s) partenaire(s)</span>
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="rechercheCompagnie" placeholder="Rechercher une compagnie...">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- COMPAGNIES GRID -->
<section>
    <div class="container">
        <div class="row" id="grilleCompagnies" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 30px;">
            <?php $i = 0; if(!empty($listecompagnie)): foreach($listecompagnie as $c): ?>
            <div class="company-card" data-nom="<?= htmlspecialchars(strtolower($c->nom_compagnie)) ?>" data-aos="fade-up" data-aos-delay="<?= ($i + 1) * 100 ?>">
                <div class="company-cover" style="background: linear-gradient(135deg, #0f3b5e, #1a5276);"></div>
                <div class="company-logo">
                    <?php if(!empty($c->logo)): ?>
                        <img src="<?= ASSET_URL ?>/images/logos/<?= htmlspecialchars($c->logo) ?>" alt="<?= htmlspecialchars($c->nom_compagnie) ?>" style="width: 100%; height: 100%; object-fit: contain; border-radius: 20px;">
                    <?php else: ?>
                        <i class="fas fa-bus"></i>
                    <?php endif; ?>
                </div>
                <div class="company-content">
                    <h3 class="company-name"><?= htmlspecialchars($c->nom_compagnie) ?></h3>
                    <p class="company-desc"><?= htmlspecialchars($c->slogant ?? 'Transport sécurisé et fiable au Mali.') ?></p>
                    <div class="company-stats">
                        <div class="stat"><div class="stat-value"><?= $statsParCompagnie[$c->id_compagnie]['trajets'] ?? 0 ?></div><div class="stat-label">Trajets</div></div>
                        <div class="stat"><div class="stat-value"><?= $statsParCompagnie[$c->id_compagnie]['destinations'] ?? 0 ?></div><div class="stat-label">Destinations</div></div>
                    </div>
                    <div class="company-footer">
                        <a href="<?= BASE_URL ?>/site/Programmer/show/<?= base64_encode($c->id_compagnie) ?>" class="btn btn-primary" style="flex: 1; text-decoration: none; padding: 10px; font-size: 0.8rem; text-align: center;">Voir les trajets</a>
                    </div>
                </div>
            </div>
            <?php $i++; endforeach; else: ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                <p style="color: var(--gray);">Aucune compagnie disponible pour le moment.</p>
            </div>
            <?php endif; ?>
        </div>
        <p id="aucunResultat" style="display:none; text-align:center; padding: 40px; color: var(--gray);">Aucune compagnie ne correspond à votre recherche.</p>
    </div>
</section>

<!-- CTA SECTION -->
<section>
    <div class="container">
        <div class="cta-section" data-aos="fade-up">
            <h2>Vous êtes une compagnie de transport ?</h2>
            <p>Rejoignez TransGest et développez votre clientèle</p>
            <a href="<?= BASE_URL ?>/site/EspacePartenaire/login" class="btn btn-outline-light btn-lg" style="text-decoration:none;">Devenir partenaire <i class="fas fa-arrow-right"></i></a>
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

    // Recherche en direct parmi les compagnies affichées
    const rechercheInput = document.getElementById('rechercheCompagnie');
    const cartesCompagnies = document.querySelectorAll('#grilleCompagnies .company-card');
    const aucunResultat = document.getElementById('aucunResultat');
    if (rechercheInput) {
        rechercheInput.addEventListener('input', function() {
            const terme = this.value.trim().toLowerCase();
            let visibles = 0;
            cartesCompagnies.forEach(function(carte) {
                const correspond = carte.getAttribute('data-nom').includes(terme);
                carte.style.display = correspond ? '' : 'none';
                if (correspond) visibles++;
            });
            if (aucunResultat) {
                aucunResultat.style.display = visibles === 0 ? 'block' : 'none';
            }
        });
    }

</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php if (isset($_SESSION['toast'])): ?>
    <script>
        Swal.fire({
            icon: "<?= addslashes($_SESSION['toast']['icon'] ?? 'info') ?>",
            title: "<?= addslashes($_SESSION['toast']['title']); ?>",
            text: "<?= addslashes($_SESSION['toast']['text']); ?>",
            confirmButtonText: "OK"
        });
    </script>
    <?php unset($_SESSION['toast']); ?>
<?php endif; ?>
</body>
</html>