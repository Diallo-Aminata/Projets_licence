<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>À propos & Contact - TransGest</title>
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

        /* ========== SECTION À PROPOS ========== */
        .about-section {
            padding: 60px 0;
        }
        .about-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
            align-items: center;
        }
        .about-content h2 {
            font-size: 2rem;
            margin-bottom: 20px;
            color: var(--primary);
        }
        .about-content p {
            color: var(--gray);
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .about-stats {
            display: flex;
            gap: 30px;
            margin-top: 30px;
        }
        .stat {
            text-align: center;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: 800;
            color: var(--secondary);
        }
        .stat-label {
            font-size: 0.8rem;
            color: var(--gray);
        }
        .about-image img {
            width: 100%;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
        }

        /* ========== NOS VALEURS ========== */
        .values-section {
            background: #f8fafc;
            padding: 60px 0;
        }
        .section-title {
            text-align: center;
            font-size: 2rem;
            margin-bottom: 50px;
            color: var(--primary);
        }
        .values-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
        }
        .value-card {
            background: white;
            padding: 30px;
            border-radius: var(--radius-lg);
            text-align: center;
            box-shadow: var(--shadow);
            transition: all 0.3s;
        }
        .value-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }
        .value-icon {
            width: 70px;
            height: 70px;
            background: rgba(230, 126, 34, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 1.8rem;
            color: var(--secondary);
        }
        .value-card h3 {
            margin-bottom: 15px;
        }
        .value-card p {
            color: var(--gray);
            font-size: 0.9rem;
            line-height: 1.5;
        }

        /* ========== CONTACT SECTION ========== */
        .contact-section {
            padding: 60px 0;
        }
        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
        }
        .contact-info {
            background: white;
            padding: 30px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
        }
        .contact-info h3 {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: var(--primary);
        }
        .contact-details {
            margin: 30px 0;
        }
        .contact-item {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        .contact-icon {
            width: 45px;
            height: 45px;
            background: rgba(230, 126, 34, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--secondary);
            font-size: 1.2rem;
        }
        .contact-text h4 {
            font-size: 0.9rem;
            margin-bottom: 4px;
        }
        .contact-text p {
            color: var(--gray);
            font-size: 0.85rem;
        }
        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        .social-link {
            width: 40px;
            height: 40px;
            background: var(--gray-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            transition: all 0.3s;
        }
        .social-link:hover {
            background: var(--secondary);
            color: white;
        }
        .contact-form {
            background: white;
            padding: 30px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
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
        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: var(--radius);
            font-size: 0.9rem;
            transition: all 0.3s;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--secondary);
        }
        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }
        .btn-submit {
            background: var(--secondary);
            color: white;
            border: none;
            padding: 14px 30px;
            border-radius: var(--radius);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
        }
        .btn-submit:hover {
            background: var(--secondary-dark);
            transform: translateY(-2px);
        }

        /* ========== MAP SECTION ========== */
        .map-section {
            padding: 0 0 60px;
        }
        .map-container {
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-lg);
        }
        .map-container iframe {
            width: 100%;
            height: 400px;
            border: 0;
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
            .about-grid, .contact-grid, .values-grid {
                grid-template-columns: 1fr;
            }
            .footer-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 768px) {
            .nav { display: none; }
            .menu-toggle { display: block; }
            .footer-grid {
                grid-template-columns: 1fr;
                text-align: center;
            }
            .page-header h1 { font-size: 1.8rem; }
            .about-stats {
                justify-content: center;
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
        <h1 data-aos="fade-up">À propos de TransGest</h1>
        <p data-aos="fade-up" data-aos-delay="100">Découvrez qui nous sommes et comment nous révolutionnons le transport au Mali</p>
    </div>
</section>

<!-- SECTION À PROPOS -->
<section class="about-section">
    <div class="container">
        <div class="about-grid">
            <div class="about-content" data-aos="fade-right">
                <h2>Votre partenaire de voyage au Mali</h2>
                <p>TransGest est la première plateforme digitale de réservation de billets de bus et de suivi de colis au Mali. Créée en 2020, notre mission est de simplifier les déplacements des Maliens en offrant une solution fiable, rapide et sécurisée.</p>
                <p>Nous collaborons avec les meilleures compagnies de transport pour vous garantir confort, ponctualité et sécurité. Grâce à notre technologie, réservez vos trajets en quelques clics et suivez vos colis en temps réel.</p>
                <div class="about-stats">
                    <div class="stat">
                        <div class="stat-number"><?= $stats['destinations'] ?></div>
                        <div class="stat-label">Destinations</div>
                    </div>
                    <div class="stat">
                        <div class="stat-number"><?= $stats['compagnies'] ?></div>
                        <div class="stat-label">Compagnies</div>
                    </div>
                    <div class="stat">
                        <div class="stat-number"><?= $stats['clients'] ?></div>
                        <div class="stat-label">Clients</div>
                    </div>
                </div>
            </div>
            <div class="about-image" data-aos="fade-left">
                <img src="<?= ASSET_URL ?>/assets_site/img/about_unsplash.jpg" alt="Équipe TransGest">
            </div>
        </div>
    </div>
</section>

<!-- NOS VALEURS -->
<section class="values-section">
    <div class="container">
        <h2 class="section-title" data-aos="fade-up">Nos valeurs</h2>
        <div class="values-grid">
            <div class="value-card" data-aos="fade-up" data-aos-delay="100">
                <div class="value-icon"><i class="fas fa-handshake"></i></div>
                <h3>Confiabilité</h3>
                <p>Des partenaires de confiance et un service client disponible 24h/24 pour vous accompagner.</p>
            </div>
            <div class="value-card" data-aos="fade-up" data-aos-delay="200">
                <div class="value-icon"><i class="fas fa-bolt"></i></div>
                <h3>Rapidité</h3>
                <p>Réservation instantanée, suivi en temps réel et paiement sécurisé en quelques secondes.</p>
            </div>
            <div class="value-card" data-aos="fade-up" data-aos-delay="300">
                <div class="value-icon"><i class="fas fa-smile"></i></div>
                <h3>Satisfaction</h3>
                <p>Des milliers de voyageurs satisfaits grâce à notre qualité de service et notre écoute.</p>
            </div>
        </div>
    </div>
</section>

<!-- CONTACT SECTION -->
<section class="contact-section">
    <div class="container">
        <div class="contact-grid">
            <div class="contact-info" data-aos="fade-right">
                <h3>Contactez-nous</h3>
                <p>Notre équipe est à votre disposition pour toute question ou assistance.</p>
                <div class="contact-details">
                    <div class="contact-item">
                        <div class="contact-icon"><i class="fas fa-map-marker-alt"></i></div>
                        <div class="contact-text">
                            <h4>Adresse</h4>
                            <p>Pelegana, Segou, Mali</p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <div class="contact-icon"><i class="fas fa-phone-alt"></i></div>
                        <div class="contact-text">
                            <h4>Téléphone</h4>
                            <p>+223 74 16 70 88</p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <div class="contact-icon"><i class="fas fa-envelope"></i></div>
                        <div class="contact-text">
                            <h4>Email</h4>
                            <p>transgest@gmail.com</p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <div class="contact-icon"><i class="fas fa-clock"></i></div>
                        <div class="contact-text">
                            <h4>Horaires</h4>
                            <p>24/7</p>
                        </div>
                    </div>
                </div>
                <div class="social-links">
                    <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
            <div class="contact-form" data-aos="fade-left">
                <h3>Envoyez-nous un message</h3>
                <form>
                    <div class="form-group">
                        <label>Nom complet</label>
                        <input type="text" class="form-control" placeholder="Votre nom">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" class="form-control" placeholder="votre@email.com">
                    </div>
                    <div class="form-group">
                        <label>Téléphone</label>
                        <input type="tel" class="form-control" placeholder="77 78 88 99">
                    </div>
                    <div class="form-group">
                        <label>Message</label>
                        <textarea class="form-control" placeholder="Votre message..."></textarea>
                    </div>
                    <button type="submit" class="btn-submit">Envoyer le message <i class="fas fa-paper-plane"></i></button>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- MAP SECTION -->
<section class="map-section">
    <div class="container">
        <div class="map-container" data-aos="fade-up">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3868.382152195884!2d-8.002433!3d12.639465!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0xe5c6f8b8b8b8b8b%3A0x8b8b8b8b8b8b8b8b!2sBamako%2C%20Mali!5e0!3m2!1sfr!2sfr!4v1700000000000!5m2!1sfr!2sfr" allowfullscreen="" loading="lazy"></iframe>
        </div>
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
    AOS.init({ duration: 600, once: true, offset: 50 });
</script>
</body>
</html>