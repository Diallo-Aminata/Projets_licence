<footer class="footer-section fix footer-3 section-padding pb-0">
    <div class="container">
        <!-- Footer Haut : Logo + Contact -->
        <div class="footer-top style-new d-flex flex-column flex-md-row justify-content-between align-items-start mb-5">
            <div class="logo-items mb-3">
                <a href="index.html">
                    <img src="assets_site/img/logo/white-logo.svg" alt="Logo">
                </a>
            </div>
            <div class="contact-info d-flex flex-column flex-md-row gap-4">
                <div class="contact-items d-flex align-items-center">
                    <div class="icon me-2"><i class="fa-solid fa-phone"></i></div>
                    <div class="content"><a href="tel:+22374167088">+223 74 16 70 88</a></div>
                </div>
                <div class="contact-items d-flex align-items-center">
                    <div class="icon me-2"><i class="fa-regular fa-envelope"></i></div>
                    <div class="content"><a href="mailto:transgest@gmail.com">transgest@gmail.com</a></div>
                </div>
                <div class="contact-items d-flex align-items-center">
                    <div class="icon me-2"><i class="fa-regular fa-location-dot"></i></div>
                    <div class="content"><span>Pelegana, Segou, Mali</span></div>
                </div>
            </div>
        </div>

        <!-- Widgets du Footer -->
        <div class="footer-widget-wrapper-new style-2 row gy-4">
            <!-- Newsletter -->
            <div class="col-xl-4 col-lg-6 col-md-12">
                <div class="single-widget-items">
                    <h3 class="text-white">Abonnez-vous à la newsletter</h3>
                    <p>Recevez nos dernières offres et mises à jour</p>
                    <div class="footer-input style-3 d-flex gap-2">
                        <input type="email" id="email2" placeholder="Votre adresse e-mail" class="form-control">
                        <button class="newsletter-btn theme-btn" type="submit">
                            S'abonner <i class="fa-sharp fa-regular fa-arrow-right"></i>
                        </button>
                    </div>
                    <div class="social-icon style-3 mt-3 d-flex gap-2">
                        <a href="#"><i class="fab fa-facebook-f text-white"></i></a>
                        <a href="#"><i class="fab fa-twitter text-white"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in text-white"></i></a>
                        <a href="#"><i class="fab fa-instagram text-white"></i></a>
                    </div>
                </div>
            </div>

            <!-- Compagnies -->
            <div class="col-xl-3 col-lg-6 col-md-6">
                <div class="single-widget-items">
                    <h4 class="style-3 text-white"> Nos Partenaire Compagnies</h4>
                    <ul class="list-items style-3">
                        <li><a href="tour-details.html">Wanderlust Adventures</a></li>
                        <li><a href="tour-details.html">Globe Trotters Travel</a></li>
                    
                    </ul>
                </div>
            </div>

            <!-- Liens rapides -->
            <div class="col-xl-2 col-lg-6 col-md-6">
                <div class="single-widget-items">
                    <h4 class="style-3 text-white">Liens rapides</h4>
                    <ul class="list-items style-3">
                        <li><a href="<?= BASE_URL ?>/site/Accueil">Accueil</a></li>
                        <li><a href="#" onclick="alerteReservation(event)">Reservation</a></li>
                        <li><a href="<?= BASE_URL ?>/site/Suivis_colis">Suivis de colis</a></li>
                        <li><a href="<?= BASE_URL ?>/site/Reservation_formulaire">Contact</a></li>
                    </ul>
                </div>
            </div>

            <!-- Galerie -->
          
        </div>

        <!-- Footer Bas -->
        <div class="footer-bottom style-2 mt-4 pt-3 border-top d-flex flex-column flex-md-row justify-content-between align-items-center">
            <p>Copyright © 2026 Computer Service Barry. All rights reserved.</p>
            <ul class="bottom-list d-flex gap-3">
                <li>Conditions d'utilisation</li>
                <li>Politique de confidentialité et environnementale</li>
            </ul>
        </div>
    </div>
</footer>
