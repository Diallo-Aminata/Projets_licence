<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>TransGest - Réservation & Suivi de colis</title>
    <link href="<?= ASSET_URL ?>/assets_site/css/inter.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= ASSET_URL ?>/assets_site/css/all.min.css">
    <link href="<?= ASSET_URL ?>/assets_site/css/aos.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f4f6fa;
            color: #1a1f2e;
            line-height: 1.5;
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
            --radius: 8px;
            --radius-lg: 12px;
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
        }
        .btn-outline {
            background: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
        }
        .btn-outline:hover {
            background: var(--primary);
            color: white;
        }
        .btn-block {
            width: 100%;
        }

        /* ========== HEADER RESPONSIVE ========== */
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
        
        /* Desktop Navigation */
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
        .nav a:hover {
            color: var(--secondary);
        }
        
        /* Mobile Menu Button */
        .menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.8rem;
            color: var(--primary);
            cursor: pointer;
            padding: 8px;
            transition: all 0.3s;
        }
        .menu-toggle:hover {
            color: var(--secondary);
        }
        
        /* Mobile Navigation */
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
            overflow-y: auto;
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
            font-size: 1.1rem;
            border-bottom: 1px solid #eee;
            transition: color 0.3s;
        }
        .mobile-nav a:hover {
            color: var(--secondary);
        }
        .mobile-nav .btn-outline {
            display: inline-block;
            margin-top: 20px;
            text-align: center;
        }
        .close-menu {
            position: absolute;
            top: 20px;
            right: 20px;
            background: none;
            border: none;
            font-size: 1.8rem;
            cursor: pointer;
            color: var(--gray);
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

        /* ========== HERO avec IMAGE EN ARRIÈRE-PLAN ========== */
        .hero {
            position: relative;
            color: white;
            min-height: 600px;
            display: flex;
            align-items: center;
            overflow: hidden;
        }
        .hero-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: 0;
            opacity: 0;
            transition: opacity 1.2s ease-in-out;
        }
        .hero-bg.active {
            opacity: 1;
        }
        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(15, 59, 94, 0.7) 0%, rgba(10, 42, 68, 0.6) 100%);
            z-index: 1;
        }
        .hero .container {
            position: relative;
            z-index: 2;
        }
        .hero-inner {
            display: flex;
            justify-content: center;
            min-height: 550px;
        }
        .hero-inner > div {
            max-width: 720px;
            text-align: center;
        }
        .hero-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(5px);
            padding: 6px 16px;
            border-radius: 50px;
            font-size: 0.75rem;
            margin-bottom: 24px;
        }
        .hero h1 {
            font-size: 2.8rem;
            margin-bottom: 20px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        .hero h1 span {
            color: var(--secondary);
        }
        .hero p {
            font-size: 1.05rem;
            opacity: 0.95;
            margin: 0 auto 32px;
            line-height: 1.6;
            text-shadow: 0 1px 5px rgba(0,0,0,0.2);
            max-width: 560px;
        }
        .hero-stats {
            display: flex;
            justify-content: center;
            gap: 40px;
            margin-top: 40px;
        }
        .hero-stat h3 {
            font-size: 1.8rem;
            margin-bottom: 4px;
        }
        .hero-stat p {
            font-size: 0.75rem;
            opacity: 0.8;
            margin: 0;
        }

        /* ========== SEARCH CARD ========== */
        .search-section {
            margin-top: -40px;
            position: relative;
            z-index: 10;
        }
        .search-card {
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            padding: 32px;
        }
        .search-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 20px;
            align-items: end;
        }
        .form-group label {
            display: block;
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .form-control, .form-select {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #ddd;
            border-radius: var(--radius);
            font-size: 0.9rem;
            transition: all 0.3s;
        }
        .form-select {
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='%237f8c8d'><path d='M5.5 7.5l4.5 5 4.5-5z'/></svg>");
            background-repeat: no-repeat;
            background-position: right 14px center;
            background-size: 14px;
            padding-right: 40px;
            cursor: pointer;
        }
        .form-control:focus, .form-select:focus {
            outline: none;
            border-color: var(--secondary);
        }

        /* ========== SECTION HEADER ========== */
        .section-header {
            text-align: center;
            margin-bottom: 48px;
        }
        .section-header h2 {
            font-size: 2rem;
            margin-bottom: 12px;
        }
        .section-header p {
            color: var(--gray);
            max-width: 600px;
            margin: 0 auto;
        }

        /* ========== COMPANY GRID ========== */
        .company-grid {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 24px;
        }
        .company-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: 24px;
            text-align: center;
            transition: all 0.3s;
            box-shadow: var(--shadow);
            flex: 1 1 250px;
            max-width: 270px;
        }
        .company-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }
        .company-icon {
            width: 70px;
            height: 70px;
            background: var(--gray-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            font-size: 1.8rem;
            color: var(--primary);
        }
        .company-card h4 {
            font-size: 1.1rem;
            margin-bottom: 8px;
        }
        .company-card .trajets {
            font-size: 0.8rem;
            color: var(--gray);
            margin-bottom: 16px;
        }

        /* ========== TRACKING SECTION ========== */
        .tracking-section {
            background: var(--gray-light);
            border-radius: var(--radius-lg);
            padding: 48px;
        }
        .tracking-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 48px;
            align-items: center;
        }
        .tracking-info h3 {
            font-size: 1.8rem;
            margin-bottom: 16px;
        }
        .tracking-features {
            display: flex;
            gap: 24px;
            margin-top: 24px;
            flex-wrap: wrap;
        }
        .tracking-features span {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
            color: var(--gray);
        }
        .tracking-box {
            background: white;
            border-radius: var(--radius-lg);
            padding: 32px;
        }
        .tracking-form {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .input-group {
            display: flex;
            gap: 12px;
        }
        .input-group input {
            flex: 1;
            padding: 14px 20px;
            border: 1px solid #ddd;
            border-radius: var(--radius);
            font-size: 0.9rem;
        }

        /* ========== DESTINATION GRID ========== */
        .dest-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }
        .dest-card {
            background: white;
            border-radius: var(--radius-lg);
            border-left: 4px solid var(--primary);
            box-shadow: var(--shadow);
            padding: 20px 22px;
            transition: all 0.3s;
        }
        .dest-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
            border-left-color: var(--secondary);
        }
        .dest-card-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }
        .dest-route-icon {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: var(--gray-light);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }
        .dest-info h4 {
            font-size: 1.05rem;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--dark);
        }
        .dest-info h4 i {
            color: var(--gray);
            font-size: 0.8rem;
        }
        .dest-info p {
            font-size: 0.85rem;
            color: var(--gray);
            display: flex;
            align-items: center;
            gap: 6px;
            margin: 0;
        }
        .dest-heures-label {
            margin-bottom: 8px !important;
        }
        .dest-heures {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }
        .heure-badge {
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--primary);
            background: var(--gray-light);
            padding: 4px 10px;
            border-radius: 20px;
        }
        .dest-price {
            font-weight: 700;
            font-size: 0.8rem;
            color: white;
            background: var(--secondary);
            padding: 5px 14px;
            border-radius: 20px;
            white-space: nowrap;
        }

        /* ========== TABS COMPAGNIES (Destinations populaires) ========== */
        .dest-tabs-wrapper {
            display: flex;
            gap: 24px;
            align-items: flex-start;
        }
        .dest-tab-list {
            list-style: none;
            margin: 0;
            padding: 0;
            flex: 0 0 220px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .dest-tab-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            text-align: left;
            background: white;
            border: 1px solid #e2e6ea;
            border-radius: var(--radius);
            padding: 12px 16px;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--dark);
            cursor: pointer;
            transition: all 0.2s;
        }
        .dest-tab-btn:hover {
            border-color: var(--primary-light);
        }
        .dest-tab-btn.active {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }
        .dest-tab-btn img {
            width: 24px;
            height: 24px;
            object-fit: contain;
            border-radius: 4px;
            background: white;
        }
        .dest-tab-content {
            flex: 1;
            min-width: 0;
        }
        .dest-tab-panel {
            display: none;
        }
        .dest-tab-panel.active {
            display: block;
        }
        .dest-tab-empty {
            text-align: center;
            padding: 40px;
            color: var(--gray);
        }
        .dest-panel-empty {
            text-align: center;
            padding: 48px 20px;
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            color: var(--gray);
        }
        .dest-panel-empty i {
            font-size: 2rem;
            color: var(--gray-light);
            margin-bottom: 12px;
            display: block;
        }
        @media (max-width: 768px) {
            .dest-tabs-wrapper {
                flex-direction: column;
            }
            .dest-tab-list {
                flex-direction: row;
                flex-wrap: wrap;
                flex: 1 1 auto;
                width: 100%;
            }
        }

        /* ========== STATS BAR ========== */
        .stats-bar {
            background: var(--primary);
            color: white;
            padding: 48px 0;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            text-align: center;
            gap: 32px;
        }
        .stats-grid h3 {
            font-size: 2rem;
            margin-bottom: 8px;
        }
        .stats-grid p {
            font-size: 0.85rem;
            opacity: 0.7;
        }

        /* ========== FOOTER ========== */
        .footer {
            background: #1a1f2e;
            color: #94a3b8;
            padding: 48px 0 24px;
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

        /* ========== RESPONSIVE ========== */
        @media (max-width: 992px) {
            .search-grid {
                grid-template-columns: repeat(3, 1fr);
            }
            .dest-grid, .stats-grid, .footer-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .tracking-grid {
                grid-template-columns: 1fr;
            }
            .tracking-section {
                padding: 30px;
            }
        }
        
        @media (max-width: 768px) {
            .search-grid {
                grid-template-columns: 1fr;
            }
            .dest-grid, .stats-grid {
                grid-template-columns: 1fr;
            }
            .company-card {
                flex: 1 1 100%;
                max-width: 320px;
            }
            .hero h1 {
                font-size: 2rem;
            }
            .input-group {
                flex-direction: column;
            }
            .hero {
                min-height: 500px;
            }
            .hero-stats {
                gap: 20px;
                flex-wrap: wrap;
            }
            .hero-stat h3 {
                font-size: 1.4rem;
            }
            .tracking-features {
                flex-direction: column;
                gap: 10px;
            }
            
            /* Afficher le bouton menu sur mobile */
            .nav {
                display: none;
            }
            .menu-toggle {
                display: block;
            }
        }
        
        @media (max-width: 480px) {
            .hero h1 {
                font-size: 1.6rem;
            }
            .hero p {
                font-size: 0.85rem;
            }
            .search-card {
                padding: 20px;
            }
            .stats-grid h3 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>

<!-- HEADER -->
<?php $this->view('site/partials/nav') ?>

<!-- Mobile Navigation Menu -->
<div class="mobile-nav" id="mobileNav">
    <button class="close-menu" id="closeMenu">
        <i class="fas fa-times"></i>
    </button>
    <a href="<?= BASE_URL ?>/site/Accueil">Accueil</a>
    <a href="<?= BASE_URL ?>/compagnies">Compagnies</a>
    <a href="#destinations">Destinations</a>
    <a href="<?= BASE_URL ?>/site/Contact">Contact</a>
    <a href="<?= BASE_URL ?>/login" class="btn btn-outline btn-block" style="margin-top: 20px; text-decoration: none;">Espace pro</a>
</div>
<div class="overlay" id="overlay"></div>

<!-- HERO avec IMAGE EN ARRIÈRE-PLAN -->
<section class="hero">
    <?php if (!empty($heroSlides)): ?>
        <?php foreach ($heroSlides as $i => $slide): ?>
            <img src="<?= htmlspecialchars($slide) ?>" alt="TransGest" class="hero-bg<?= $i === 0 ? ' active' : '' ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    <div class="hero-overlay"></div>
    <div class="container">
        <div class="hero-inner">
            <div data-aos="fade-up">
                <div class="hero-badge">✓ Transport agréé</div>
                <h1>Trans<span>Gest</span><br>Réservation & suivi de colis</h1>
                <p>La plateforme qui simplifie vos déplacements et l'envoi de vos colis au Mali. Comparez les compagnies, réservez en ligne et suivez vos colis en temps réel.</p>
                <div class="hero-stats">
                    <div class="hero-stat">
                        <h3><?= $heroStats['destinations'] ?></h3>
                        <p>Destinations</p>
                    </div>
                    <div class="hero-stat">
                        <h3><?= $heroStats['compagnies'] ?></h3>
                        <p>Compagnies</p>
                    </div>
                    <div class="hero-stat">
                        <h3><?= $heroStats['clients'] ?></h3>
                        <p>Clients</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- RECHERCHE -->
<section class="search-section">
    <div class="container">
        <div class="search-card" data-aos="fade-up">
            <form action="<?= BASE_URL ?>/site/Recherche" method="GET" class="search-grid">
                <div class="form-group">
                    <label>Départ</label>
                    <select name="depart" class="form-select" required>
                        <option value="">Choisissez la ville</option>
                        <?php if (!empty($villes)): foreach ($villes as $ville): ?>
                            <option value="<?= htmlspecialchars($ville) ?>"><?= htmlspecialchars($ville) ?></option>
                        <?php endforeach; endif; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-flag-checkered"></i> Destination</label>
                    <select name="destination" class="form-select" required>
                        <option value="">Choisissez la destination</option>
                        <?php if (!empty($villes)): foreach ($villes as $ville): ?>
                            <option value="<?= htmlspecialchars($ville) ?>"><?= htmlspecialchars($ville) ?></option>
                        <?php endforeach; endif; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-building"></i> Compagnie</label>
                    <select name="compagnie" class="form-select">
                        <option value="">Toutes les compagnies</option>
                        <?php if (!empty($listecompagnie)): foreach ($listecompagnie as $c): ?>
                            <option value="<?= $c->id_compagnie ?>"><?= htmlspecialchars($c->nom_compagnie) ?></option>
                        <?php endforeach; endif; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-calendar"></i> Date</label>
                    <input type="date" name="date" class="form-control" value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>">
                </div>
                <div>
                    <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-search"></i> Rechercher</button>
                </div>
            </form>
        </div>
    </div>
</section>

<!-- SUIVI COLIS -->
<section>
    <div class="container">
        <div class="tracking-section" data-aos="fade-up">
            <div class="tracking-grid">
                <div class="tracking-info">
                    <div class="hero-badge" style="background: #e8f4fd; color: var(--accent); display: inline-block;">Suivi 24/7</div>
                    <h3>Suivez vos colis en temps réel</h3>
                    <p>Entrez votre numéro de suivi et connaissez à tout moment l'emplacement exact de votre colis.</p>
                    <div class="tracking-features">
                        <span><i class="fas fa-check-circle" style="color: var(--success);"></i> Livraison garantie</span>
                        <span><i class="fas fa-shield-alt" style="color: var(--primary);"></i> Colis assurés</span>
                        <span><i class="fas fa-clock" style="color: var(--warning);"></i> Mise à jour en direct</span>
                    </div>
                </div>
                <div class="tracking-box">
                    <form action="<?= BASE_URL ?>/site/Suivis_colis" method="GET" class="tracking-form">
                        <div class="form-group">
                            <label><i class="fas fa-building"></i> Compagnie</label>
                            <select name="id_compagnie" class="form-select" required>
                                <option value="">Choisissez la compagnie</option>
                                <?php if (!empty($listecompagnie)): foreach ($listecompagnie as $c): ?>
                                    <option value="<?= $c->id_compagnie ?>"><?= htmlspecialchars($c->nom_compagnie) ?></option>
                                <?php endforeach; endif; ?>
                            </select>
                        </div>
                        <div class="input-group">
                            <input type="text" name="code_colis" placeholder="Ex: BL-2024-001234" required>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Suivre</button>
                        </div>
                    </form>
                    <p style="font-size: 0.7rem; color: var(--gray); margin-top: 16px;">Exemple: BL-2024-001234, BL-2024-567890</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- COMPAGNIES -->
<section>
    <div class="container">
        <div class="section-header" data-aos="fade-up">
            <h2>Nos compagnies partenaires</h2>
            <p>Des transporteurs fiables et agréés pour vos déplacements</p>
        </div>
        <div class="company-grid">
            <?php $i = 0; if(!empty($listecompagnie)): foreach($listecompagnie as $c): ?>
            <div class="company-card" data-aos="fade-up" data-aos-delay="<?= ($i + 1) * 100 ?>">
                <div class="company-icon" style="overflow: hidden;">
                    <?php if(!empty($c->logo)): ?>
                        <img src="<?= ASSET_URL ?>/images/logos/<?= htmlspecialchars($c->logo) ?>" alt="<?= htmlspecialchars($c->nom_compagnie) ?>" style="width: 100%; height: 100%; object-fit: contain;">
                    <?php else: ?>
                        <i class="fas fa-bus"></i>
                    <?php endif; ?>
                </div>
                <h4><?= htmlspecialchars($c->nom_compagnie) ?></h4>
                <div class="trajets"><?= htmlspecialchars($c->slogant ?? 'Voyagez en sécurité') ?></div>
                <a href="<?= BASE_URL ?>/site/Programmer/show/<?= base64_encode($c->id_compagnie) ?>" class="btn btn-outline btn-block" style="padding: 8px; text-decoration: none;">Voir les trajets</a>
            </div>
            <?php $i++; endforeach; else: ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                <p style="color: var(--gray);">Aucune compagnie disponible pour le moment.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- DESTINATIONS -->
<section style="background: var(--gray-light);">
    <div class="container">
        <div class="section-header" data-aos="fade-up">
            <h2>Destinations populaires</h2>
            <p>Tous les trajets programmés, par compagnie</p>
        </div>

        <?php if (!empty($programmesParCompagnie)): ?>
            <div class="dest-tabs-wrapper" data-aos="fade-up">
                <ul class="dest-tab-list" role="tablist">
                    <?php $first = true; foreach ($programmesParCompagnie as $id_compagnie => $programmes): ?>
                        <?php
                            $compagnie = null;
                            foreach ($listecompagnie as $c) {
                                if ($c->id_compagnie == $id_compagnie) { $compagnie = $c; break; }
                            }
                        ?>
                        <li>
                            <button type="button" class="dest-tab-btn<?= $first ? ' active' : '' ?>" data-tab-target="dest-tab-<?= $id_compagnie ?>">
                                <?php if (!empty($compagnie->logo)): ?>
                                    <img src="<?= ASSET_URL ?>/images/logos/<?= htmlspecialchars($compagnie->logo) ?>" alt="">
                                <?php endif; ?>
                                <?= htmlspecialchars($compagnie->nom_compagnie ?? 'Compagnie') ?>
                            </button>
                        </li>
                        <?php $first = false; ?>
                    <?php endforeach; ?>
                </ul>

                <div class="dest-tab-content">
                    <?php $first = true; foreach ($programmesParCompagnie as $id_compagnie => $programmes): ?>
                        <div class="dest-tab-panel<?= $first ? ' active' : '' ?>" id="dest-tab-<?= $id_compagnie ?>">
                            <?php if (!empty($programmes)): ?>
                                <div class="dest-grid">
                                    <?php foreach ($programmes as $i => $p): ?>
                                        <div class="dest-card" data-aos="fade-up" data-aos-delay="<?= ($i % 4 + 1) * 100 ?>">
                                            <div class="dest-card-top">
                                                <span class="dest-route-icon"><i class="fas fa-bus"></i></span>
                                                <span class="dest-price"><?= number_format((float)$p->prix, 0, ',', ' ') ?> FCFA</span>
                                            </div>
                                            <div class="dest-info">
                                                <h4><?= htmlspecialchars($p->departLocalite) ?> <i class="fas fa-long-arrow-alt-right"></i> <?= htmlspecialchars($p->destinationLocalite) ?></h4>
                                                <p class="dest-heures-label"><i class="far fa-clock"></i> Départs</p>
                                                <div class="dest-heures">
                                                    <?php foreach ($p->heures as $h): ?>
                                                        <span class="heure-badge"><?= htmlspecialchars(substr($h, 0, 5)) ?></span>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="dest-panel-empty">
                                    <i class="fas fa-route"></i>
                                    Aucun trajet disponible pour le moment.
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php $first = false; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="dest-tab-empty">Aucun trajet programmé pour le moment.</div>
        <?php endif; ?>
    </div>
</section>

<!-- STATS -->
<section class="stats-bar">
    <div class="container">
        <div class="stats-grid">
            <div data-aos="zoom-in">
                <h3><?= $heroStats['destinations'] ?></h3>
                <p>Destinations</p>
            </div>
            <div data-aos="zoom-in" data-aos-delay="100">
                <h3><?= $heroStats['compagnies'] ?></h3>
                <p>Compagnies</p>
            </div>
            <div data-aos="zoom-in" data-aos-delay="200">
                <h3><?= $heroStats['trajets'] ?></h3>
                <p>Trajets quotidiens</p>
            </div>
            <div data-aos="zoom-in" data-aos-delay="300">
                <h3>24/7</h3>
                <p>Support client</p>
            </div>
        </div>
    </div>
</section>

<!-- COMMENT ÇA MARCHE -->
<section>
    <div class="container">
        <div class="section-header" data-aos="fade-up">
            <h2>Comment ça marche ?</h2>
            <p>Réservez votre billet en 3 étapes simples</p>
        </div>
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 32px;">
            <div style="text-align: center;" data-aos="fade-up" data-aos-delay="100">
                <div style="width: 70px; height: 70px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: bold; margin: 0 auto 20px;">1</div>
                <h4>Recherchez</h4>
                <p style="color: var(--gray); font-size: 0.85rem;">Trouvez votre trajet et votre compagnie</p>
            </div>
            <div style="text-align: center;" data-aos="fade-up" data-aos-delay="200">
                <div style="width: 70px; height: 70px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: bold; margin: 0 auto 20px;">2</div>
                <h4>Réservez & Payez</h4>
                <p style="color: var(--gray); font-size: 0.85rem;">Choisissez vos places et payez en ligne</p>
            </div>
            <div style="text-align: center;" data-aos="fade-up" data-aos-delay="300">
                <div style="width: 70px; height: 70px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: bold; margin: 0 auto 20px;">3</div>
                <h4>Voyagez</h4>
                <p style="color: var(--gray); font-size: 0.85rem;">Présentez votre billet et embarquez</p>
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
    
    // Mobile Menu Toggle
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
    
    // Close menu on window resize if open
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            closeMenuFunc();
        }
    });

    // Onglets "Destinations populaires" par compagnie
    document.querySelectorAll('.dest-tab-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.dest-tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.dest-tab-panel').forEach(p => p.classList.remove('active'));
            btn.classList.add('active');
            document.getElementById(btn.getAttribute('data-tab-target')).classList.add('active');
        });
    });

    // Slider du hero : fondu enchaîné entre les images de public/assets_site/img/hero-slides/.
    // Ne fait rien s'il n'y a qu'une seule image (ou aucune).
    const heroSlides = document.querySelectorAll('.hero-bg');
    if (heroSlides.length > 1) {
        let heroIndex = 0;
        setInterval(function() {
            heroSlides[heroIndex].classList.remove('active');
            heroIndex = (heroIndex + 1) % heroSlides.length;
            heroSlides[heroIndex].classList.add('active');
        }, 5000);
    }
</script>
</body>
</html>