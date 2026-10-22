<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Tous les voyages - TransGest</title>
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

        /* ========== PAGE HEADER ========== */
        .page-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 64px 0 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .page-header::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: radial-gradient(rgba(255, 255, 255, 0.07) 1.4px, transparent 1.4px);
            background-size: 22px 22px;
            opacity: 0.6;
            pointer-events: none;
        }
        .page-header .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(2px);
            background: rgba(255, 255, 255, 0.06);
            pointer-events: none;
            animation: floatBlob 12s ease-in-out infinite;
        }
        .page-header .blob-1 {
            width: 260px;
            height: 260px;
            top: -110px;
            left: -80px;
        }
        .page-header .blob-2 {
            width: 200px;
            height: 200px;
            bottom: -100px;
            right: -40px;
            animation-direction: reverse;
        }
        @keyframes floatBlob {
            0%, 100% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(14px, -14px) scale(1.04); }
        }
        .page-header h1 {
            font-size: 2.4rem;
            font-weight: 700;
            letter-spacing: -0.3px;
            margin-bottom: 14px;
            position: relative;
            z-index: 2;
        }
        .page-header p {
            font-size: 1rem;
            opacity: 0.75;
            max-width: 600px;
            margin: 0 auto;
            position: relative;
            z-index: 2;
        }
        .header-stats {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
            gap: 28px;
            margin-top: 30px;
            position: relative;
            z-index: 2;
        }
        .header-stats .stat-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.82rem;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.8);
        }
        .header-stats .stat-item i {
            color: var(--secondary);
            font-size: 0.85rem;
        }
        .header-stats .stat-divider {
            width: 1px;
            height: 14px;
            background: rgba(255, 255, 255, 0.2);
        }
        @media (max-width: 768px) {
            .header-stats .stat-divider { display: none; }
        }

        /* ========== FILTRES ========== */
        .filters-section {
            margin-top: -30px;
            position: relative;
            z-index: 10;
        }
        .filters-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: 25px 30px;
            box-shadow: var(--shadow-md);
        }
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            align-items: end;
        }
        .filter-group label {
            display: block;
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .filter-select, .filter-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: var(--radius);
            font-size: 0.9rem;
            background: #f8fafc;
            transition: all 0.25s ease;
        }
        .filter-select:hover, .filter-input:hover {
            border-color: #cbd5e1;
        }
        .filter-select:focus, .filter-input:focus {
            outline: none;
            border-color: var(--secondary);
            background: white;
            box-shadow: 0 0 0 4px rgba(230, 126, 34, 0.12);
            transform: translateY(-1px);
        }
        .btn-filter {
            background: var(--secondary);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: var(--radius);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
            position: relative;
            overflow: hidden;
        }
        .btn-filter:hover {
            background: var(--secondary-dark);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(230, 126, 34, 0.3);
        }
        .btn-filter:active {
            transform: translateY(0);
        }
        .ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            transform: scale(0);
            animation: rippleEffect 0.6s ease-out;
            pointer-events: none;
        }
        @keyframes rippleEffect {
            to { transform: scale(3); opacity: 0; }
        }

        /* ========== GRILLE DES VOYAGES ========== */
        .trips-section {
            padding: 60px 0;
        }
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }
        .section-header h2 {
            font-size: 1.6rem;
            font-weight: 700;
        }
        .result-count {
            color: var(--gray);
            font-size: 0.9rem;
        }
        .trips-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
        }
        .trip-card {
            background: white;
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: transform 0.35s cubic-bezier(.22,1,.36,1), box-shadow 0.35s ease;
            position: relative;
            border: 1px solid transparent;
            display: flex;
            flex-direction: column;
        }
        .trip-card:hover {
            transform: translateY(-8px) scale(1.01);
            box-shadow: var(--shadow-lg);
            border-color: rgba(230, 126, 34, 0.25);
        }
        .trip-badge {
            position: absolute;
            top: 12px;
            right: 16px;
            background: var(--secondary);
            color: white;
            padding: 5px 12px;
            border-radius: 50px;
            font-size: 0.7rem;
            font-weight: 600;
            z-index: 2;
            display: flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 4px 12px rgba(230, 126, 34, 0.35);
        }
        .trip-badge .dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: white;
            animation: pulseDot 1.5s ease-in-out infinite;
        }
        @keyframes pulseDot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(1.4); }
        }
        .trip-banner {
            background: linear-gradient(120deg, var(--primary) 0%, var(--primary-light) 100%);
            padding: 34px 24px 22px;
            position: relative;
            overflow: hidden;
        }
        .trip-banner::before {
            content: '';
            position: absolute;
            top: -30%;
            right: -10%;
            width: 140px;
            height: 140px;
            background: rgba(255, 255, 255, 0.06);
            border-radius: 50%;
        }
        .route-path {
            display: flex;
            align-items: center;
            gap: 0;
            position: relative;
            margin-bottom: 12px;
        }
        .route-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: white;
            flex-shrink: 0;
            box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.2);
        }
        .route-dot.end { background: var(--secondary); }
        .route-line {
            flex: 1;
            height: 2px;
            background-image: linear-gradient(90deg, rgba(255,255,255,0.7) 50%, transparent 50%);
            background-size: 10px 2px;
            background-repeat: repeat-x;
            position: relative;
            animation: dashMove 1s linear infinite;
        }
        @keyframes dashMove {
            from { background-position: 0 0; }
            to { background-position: -20px 0; }
        }
        .route-bus-icon {
            color: white;
            font-size: 0.95rem;
            margin: 0 10px;
            transition: transform 0.35s ease;
        }
        .trip-card:hover .route-bus-icon {
            transform: translateX(4px);
        }
        .trip-card:hover .route-line {
            animation-duration: 0.4s;
        }
        .banner-cities {
            display: flex;
            justify-content: space-between;
            color: white;
            font-size: 0.72rem;
            opacity: 0.85;
            position: relative;
            z-index: 1;
        }
        .trip-image {
            height: 180px;
            background-size: cover;
            background-position: center;
            position: relative;
        }
        .trip-company {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px 20px 0 20px;
        }
        .company-avatar {
            width: 40px;
            height: 40px;
            background: var(--gray-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: var(--primary);
            transition: transform 0.3s ease, background 0.3s ease;
        }
        .trip-card:hover .company-avatar {
            transform: rotate(-8deg) scale(1.08);
            background: #dfeaf3;
        }
        .company-name {
            font-weight: 700;
            font-size: 1rem;
        }
        .trip-details {
            padding: 16px 20px 20px;
            display: flex;
            flex-direction: column;
            flex: 1;
        }
        .trip-route {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }
        .route-point {
            flex: 1;
        }
        .route-city {
            font-weight: 700;
            font-size: 1.1rem;
        }
        .route-time {
            font-size: 0.75rem;
            color: var(--gray);
        }
        .route-arrow {
            color: var(--secondary);
            font-size: 1.2rem;
        }
        .trip-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 16px;
            padding-top: 12px;
            border-top: 1px solid #eef2f6;
        }
        .info-item {
            text-align: center;
        }
        .info-label {
            font-size: 0.7rem;
            color: var(--gray);
        }
        .info-value {
            font-weight: 700;
            font-size: 0.9rem;
        }
        .trip-price {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
            padding-top: 12px;
            border-top: 1px solid #eef2f6;
        }
        .price {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--secondary);
        }
        .btn-book {
            background: var(--primary);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }
        .btn-book:hover {
            background: var(--primary-dark);
            transform: scale(1.04);
            box-shadow: 0 8px 20px rgba(15, 59, 94, 0.35);
        }
        .btn-book i {
            transition: transform 0.3s ease;
        }
        .btn-book:hover i {
            transform: translateX(4px);
        }
        .trip-card:hover .price {
            animation: priceBounce 0.4s ease;
        }
        @keyframes priceBounce {
            0% { transform: scale(1); }
            40% { transform: scale(1.08); }
            100% { transform: scale(1); }
        }

        /* ========== PAGINATION ========== */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 50px;
        }
        .page-link {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            border-radius: var(--radius);
            text-decoration: none;
            color: var(--dark);
            font-weight: 500;
            transition: all 0.3s;
        }
        .page-link:hover, .page-link.active {
            background: var(--secondary);
            color: white;
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
            .filters-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .footer-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 768px) {
            .nav { display: none; }
            .menu-toggle { display: block; }
            .filters-grid {
                grid-template-columns: 1fr;
            }
            .trips-grid {
                grid-template-columns: 1fr;
            }
            .section-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .footer-grid {
                grid-template-columns: 1fr;
                text-align: center;
            }
            .page-header h1 { font-size: 1.8rem; }
        }
    </style>
</head>
<body>

<!-- HEADER -->
<?php $this->view('site/partials/nav') ?>

<?php
// Villes réelles de départ/destination extraites des programmes de la compagnie
$villesDepart = [];
$villesDestination = [];
if (!empty($programmes)) {
    foreach ($programmes as $p) {
        $d = trim($p->departLocalite ?? '');
        $a = trim($p->destinationLocalite ?? '');
        if ($d !== '') $villesDepart[$d] = true;
        if ($a !== '') $villesDestination[$a] = true;
    }
    $villesDepart = array_keys($villesDepart);
    $villesDestination = array_keys($villesDestination);
    sort($villesDepart);
    sort($villesDestination);
}
$nbAgences = count(array_unique(array_merge($villesDepart, $villesDestination)));
?>

<!-- PAGE HEADER -->
<section class="page-header">
    <span class="blob blob-1"></span>
    <span class="blob blob-2"></span>

    <div class="container">
        <h1 data-aos="fade-up">
            <?php if (!empty($compagnie)): ?>
                <i class="fas fa-bus" style="font-size:1.8rem;margin-right:10px;"></i>
                <?= htmlspecialchars($compagnie->nom_compagnie ?? 'La Compagnie') ?>
            <?php else: ?>
                Tous nos voyages
            <?php endif; ?>
        </h1>
        <p data-aos="fade-up" data-aos-delay="100">
            <?php if (!empty($compagnie) && !empty($compagnie->slogant)): ?>
                <?= htmlspecialchars($compagnie->slogant) ?>
            <?php else: ?>
                Découvrez tous les trajets disponibles vers vos destinations préférées
            <?php endif; ?>
        </p>

        <div class="header-stats" data-aos="fade-up" data-aos-delay="150">
            <?php if ($nbAgences > 0): ?>
            <span class="stat-item"><i class="fas fa-building"></i> <?= $nbAgences ?> agence<?= $nbAgences > 1 ? 's' : '' ?> desservie<?= $nbAgences > 1 ? 's' : '' ?></span>
            <span class="stat-divider"></span>
            <?php endif; ?>
            <span class="stat-item"><i class="fas fa-clock"></i> Ponctualité garantie</span>
            <span class="stat-divider"></span>
            <span class="stat-item"><i class="fas fa-lock"></i> Paiement sécurisé</span>
        </div>
    </div>
</section>

<!-- FILTRES -->
<section class="filters-section">
    <div class="container">
        <div class="filters-card" data-aos="fade-up">
            <div class="filters-grid">
                <div class="filter-group">
                    <label><i class="fas fa-map-marker-alt"></i> Départ</label>
                    <select class="filter-select" id="filterDepart">
                        <option value="">Toutes les villes</option>
                        <?php foreach ($villesDepart as $ville): ?>
                        <option value="<?= htmlspecialchars($ville) ?>"><?= htmlspecialchars($ville) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-flag-checkered"></i> Destination</label>
                    <select class="filter-select" id="filterDestination">
                        <option value="">Toutes les destinations</option>
                        <?php foreach ($villesDestination as $ville): ?>
                        <option value="<?= htmlspecialchars($ville) ?>"><?= htmlspecialchars($ville) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <button class="btn-filter" id="btnRechercher" type="button"><i class="fas fa-search"></i> Rechercher</button>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- LISTE DES VOYAGES -->
<section class="trips-section">
    <div class="container">
        <div class="section-header" data-aos="fade-up">
            <h2>Programmes disponibles</h2>
            <span class="result-count">
                <?php if (!empty($programmes)): ?>
                    <span class="count-number" data-count="<?= count($programmes) ?>">0</span> voyage(s) trouvé(s)
                <?php else: ?>
                    Aucun voyage disponible
                <?php endif; ?>
            </span>
        </div>

        <div id="noResultsFilter" style="display:none;text-align:center;padding:60px 20px;">
            <i class="fas fa-search" style="font-size:3rem;color:#ddd;margin-bottom:16px;"></i>
            <h3 style="color:var(--gray);margin-bottom:8px;">Aucun voyage ne correspond à ces critères</h3>
            <p style="color:#aaa;">Essayez une autre ville de départ ou de destination.</p>
        </div>

        <?php if (!empty($programmes)): ?>
        <?php
        // Regrouper les programmes par ville de départ
        $groupedByDepart = [];
        foreach ($programmes as $programme) {
            $depart = htmlspecialchars(($programme->departLocalite ?? $programme->idDepart) . ' (' . ($programme->numeroGare1 ?? '') . ')');
            $groupedByDepart[$depart][] = $programme;
        }
        ?>

        <?php foreach ($groupedByDepart as $depart => $programmesDepart): ?>
        <div class="depart-group" style="margin-bottom:40px;">
            <div style="background:linear-gradient(135deg,var(--primary),var(--primary-light));color:white;padding:12px 20px;border-radius:var(--radius);margin-bottom:20px;display:flex;align-items:center;gap:10px;font-weight:700;font-size:1rem;" data-aos="fade-right">
                <i class="fas fa-map-marker-alt"></i>
                Départ depuis : <?= $depart ?>
            </div>

            <div class="trips-grid">
                <?php foreach ($programmesDepart as $index => $programme): ?>
                <div class="trip-card" data-aos="fade-up" data-aos-delay="<?= ($index % 3) * 100 ?>"
                     data-depart="<?= htmlspecialchars(trim($programme->departLocalite ?? '')) ?>"
                     data-destination="<?= htmlspecialchars(trim($programme->destinationLocalite ?? '')) ?>">
                    <div class="trip-badge">
                        <span class="dot"></span> <i class="fas fa-clock"></i> <?= htmlspecialchars($programme->heureDepart ?? '--') ?>
                    </div>
                    <div class="trip-banner">
                        <div class="route-path">
                            <span class="route-dot start"></span>
                            <span class="route-line"></span>
                            <i class="fas fa-bus route-bus-icon"></i>
                            <span class="route-line"></span>
                            <span class="route-dot end"></span>
                        </div>
                        <div class="banner-cities">
                            <span><?= htmlspecialchars($programme->departLocalite ?? 'Départ') ?></span>
                            <span><?= htmlspecialchars($programme->destinationLocalite ?? $programme->idDestination ?? 'Destination') ?></span>
                        </div>
                    </div>
                    <div class="trip-company" style="padding:20px 20px 0;">
                        <div class="company-avatar"><i class="fas fa-bus"></i></div>
                        <div>
                            <div class="company-name"><?= htmlspecialchars($compagnie->nom_compagnie ?? 'Compagnie') ?></div>
                            <?php if (!empty($programme->rdv)): ?>
                            <div style="font-size:0.75rem;color:var(--gray);">RDV : <?= htmlspecialchars($programme->rdv) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="trip-details">
                        <div class="trip-route">
                            <div class="route-point">
                                <div class="route-city"><?= htmlspecialchars($programme->departLocalite ?? 'Départ') ?></div>
                                <div class="route-time"><?= htmlspecialchars($programme->heureDepart ?? '--') ?></div>
                            </div>
                            <div class="route-arrow"><i class="fas fa-arrow-right"></i></div>
                            <div class="route-point">
                                <div class="route-city"><?= htmlspecialchars($programme->destinationLocalite ?? $programme->idDestination ?? 'Destination') ?></div>
                                <div class="route-time"><?= !empty($programme->numeroGare2) ? 'Gare : ' . htmlspecialchars($programme->numeroGare2) : '' ?></div>
                            </div>
                        </div>

                        <?php if (!empty($programme->escales_avec_frais)): ?>
                        <div style="padding:10px 0;border-top:1px solid #eef2f6;margin-top:10px;">
                            <div style="font-size:0.75rem;color:var(--gray);margin-bottom:6px;"><i class="fas fa-code-branch"></i> Escales disponibles</div>
                            <?php
                            $escales = explode(', ', $programme->escales_avec_frais);
                            foreach ($escales as $escale): ?>
                                <span style="display:inline-block;background:#e8f4fd;color:var(--primary);padding:3px 10px;border-radius:50px;font-size:0.72rem;font-weight:600;margin:2px;"><?= htmlspecialchars($escale) ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <div class="trip-price">
                            <span class="price"><?= number_format($programme->prix ?? 0, 0, ',', ' ') ?> FCFA</span>
                            <a href="<?= BASE_URL ?>/site/Reservation_formulaire?id=<?= $programme->idProgrammer ?>" class="btn-book">
                                Réserver <i class="fas fa-arrow-right" style="margin-left:5px;"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <?php else: ?>
        <div style="text-align:center;padding:80px 20px;">
            <i class="fas fa-bus" style="font-size:4rem;color:#ddd;margin-bottom:20px;"></i>
            <h3 style="color:var(--gray);margin-bottom:10px;">Aucun programme disponible</h3>
            <p style="color:#aaa;">Cette compagnie n'a pas encore de trajets programmés.</p>
            <a href="<?= BASE_URL ?>/site/compagnies" style="display:inline-block;margin-top:20px;padding:12px 24px;background:var(--secondary);color:white;border-radius:var(--radius);text-decoration:none;font-weight:600;">Voir d'autres compagnies</a>
        </div>
        <?php endif; ?>
    </div>
</section>


    </div>
</section>

<!-- FOOTER -->
<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div><h4>TransGest</h4><p style="font-size: 0.85rem;">La plateforme N°1 de réservation de billets de bus et suivi de colis au Mali.</p></div>
            <div><h4>Liens rapides</h4>
                <a href="<?= BASE_URL ?>/site/Accueil">Accueil</a>
                <a href="<?= BASE_URL ?>/site/compagnies">Compagnies</a>
                <a href="<?= BASE_URL ?>/site/Contact">Contact</a>
            </div>
            <div><h4>Support</h4><a href="#">FAQ</a><a href="#">Conditions générales</a><a href="#">Politique de confidentialité</a></div>
            <div><h4>Contact</h4><a href="tel:+22374167088"><i class="fas fa-phone"></i> +223 74 16 70 88</a><a href="mailto:transgest@gmail.com"><i class="fas fa-envelope"></i> transgest@gmail.com</a><a href="#"><i class="fas fa-map-marker-alt"></i> Pelegana, Segou, Mali</a></div>
        </div>
        <div class="footer-bottom"><p>Copyright &copy; 2026 Computer Service Barry. All rights reserved.</p></div>
    </div>
</footer>

<script src="<?= ASSET_URL ?>/assets_site/js/aos.js"></script>
<script>
    AOS.init({ duration: 650, once: true, offset: 60, easing: 'ease-out-cubic' });

    // Compteur animé du nombre de voyages
    function animateCount(el, target) {
        const duration = 500;
        const start = performance.now();
        const from = parseInt(el.textContent, 10) || 0;
        function tick(now) {
            const progress = Math.min((now - start) / duration, 1);
            el.textContent = Math.floor(from + (target - from) * progress);
            if (progress < 1) requestAnimationFrame(tick);
            else el.textContent = target;
        }
        requestAnimationFrame(tick);
    }
    document.querySelectorAll('.count-number').forEach(function (el) {
        animateCount(el, parseInt(el.dataset.count, 10) || 0);
    });

    // Filtre départ / destination sur les vraies données des programmes
    (function () {
        const departSelect = document.getElementById('filterDepart');
        const destSelect = document.getElementById('filterDestination');
        const searchBtn = document.getElementById('btnRechercher');
        const noResults = document.getElementById('noResultsFilter');
        const countEl = document.querySelector('.count-number');
        if (!departSelect || !destSelect) return;

        function normalize(str) {
            return (str || '').trim().toLowerCase();
        }

        function applyFilters() {
            const dep = normalize(departSelect.value);
            const dest = normalize(destSelect.value);
            let visibleCount = 0;

            document.querySelectorAll('.trip-card').forEach(function (card) {
                const matchDep = !dep || normalize(card.dataset.depart) === dep;
                const matchDest = !dest || normalize(card.dataset.destination) === dest;
                const visible = matchDep && matchDest;
                card.style.display = visible ? '' : 'none';
                if (visible) visibleCount++;
            });

            document.querySelectorAll('.depart-group').forEach(function (group) {
                const anyVisible = Array.from(group.querySelectorAll('.trip-card')).some(function (c) {
                    return c.style.display !== 'none';
                });
                group.style.display = anyVisible ? '' : 'none';
            });

            if (countEl) animateCount(countEl, visibleCount);
            if (noResults) noResults.style.display = visibleCount === 0 ? 'block' : 'none';
        }

        departSelect.addEventListener('change', applyFilters);
        destSelect.addEventListener('change', applyFilters);
        if (searchBtn) searchBtn.addEventListener('click', applyFilters);
    })();

    // Effet ripple sur les boutons
    document.querySelectorAll('.btn-filter, .btn-book').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            const rect = btn.getBoundingClientRect();
            const ripple = document.createElement('span');
            ripple.className = 'ripple';
            ripple.style.left = (e.clientX - rect.left) + 'px';
            ripple.style.top = (e.clientY - rect.top) + 'px';
            btn.appendChild(ripple);
            setTimeout(function () { ripple.remove(); }, 600);
        });
    });
</script>
</body>
</html>