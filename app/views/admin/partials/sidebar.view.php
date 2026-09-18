  <?php $user = new Configuration($_SESSION['id_utilisateur']) ?>
  <?php
    // Alerte proactive : bus deja plein mais dont des passagers ne sont pas encore
    // embarques. Calcule ici (et non dans chaque controleur) pour rester visible sur
    // toutes les pages, sans attendre que quelqu'un pense a ouvrir Embarquement.
    $carsCompletsSidebar = [];
    if ($user->userHasPermission('Billets_embarquement')) {
      $isAdminSidebar = in_array($_SESSION['droit'] ?? null, ['Admin', 'PDG'], true);
      $carsCompletsSidebar = (new Liste_du_jour())->getCarsComplets(
        $isAdminSidebar ? null : ($_SESSION['ville'] ?? null),
        $isAdminSidebar ? null : ($_SESSION['numero_gare'] ?? null),
        $_SESSION['id_compagnie'] ?? null
      );
    }

    // Meme principe pour les demandes de report en attente : le chef d'escale doit voir
    // instantanement qu'une demande de sa gare attend son examen (etape 1), et l'Admin
    // qu'une demande lui a ete transmise (etape 2) — sans avoir a penser a aller consulter
    // "Demandes de report" au hasard.
    $demandesReportSidebar = [];
    if ($user->userHasPermission('Billets_annulation')) {
      $droitSidebar = $_SESSION['droit'] ?? null;
      $modelReport = new Liste_du_jour();
      if (in_array($droitSidebar, ['Admin', 'super_admin', 'PDG'], true)) {
        $demandesReportSidebar = $modelReport->getDemandesReportEnAttente($_SESSION['id_compagnie'] ?? null);
      } elseif ($droitSidebar === 'chef_d_escale') {
        $demandesReportSidebar = $modelReport->getDemandesReportEnAttenteChef(
          $_SESSION['id_compagnie'] ?? null,
          $_SESSION['ville'] ?? null,
          $_SESSION['numero_gare'] ?? null
        );
      }
    }
  ?>
  <aside class="sidebar-wrapper" data-simplebar="true">
    <div class="sidebar-header">
      <div>
        <a href="<?= BASE_URL ?>/admin/Homes/home" style="text-decoration:none; display:flex; align-items:center; justify-content:center;">
          <img src="<?= ASSET_URL ?>/images/logos/transgest_logo.png" alt="TransGest" style="height:60px; width:auto; object-fit:contain;">
        </a>
      </div>
      <div class="toggle-icon ms-auto"><i class="bi bi-chevron-double-left"></i>
      </div>
    </div>
    <!--navigation-->
    <ul class="metismenu" id="menu">

      <li>
        <a href="<?= BASE_URL ?>/admin/Homes/home" class="has-">

          <div class="">Accueil</div>
        </a>

      </li>

      <?php if ($_SESSION['droit'] !== 'super_admin'): ?>
      <?php if ($user->userHasPermission('Billets_creation')) { ?>
        <li class="menu-label">Gestion des reservation</li>
        <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="bx bx-category"></i>
            </div>
            <div class="menu-title">G-réservation</div>
          </a>
        <?php }
        ?>
        <ul>
          <?php if ($user->userHasPermission('Billets_creation') && !$user->estLectureSeule()) { ?>
            <li> <a href="<?= BASE_URL ?>/admin/Add_billets"><i class="bi bi-arrow-right-short"></i>Achat de ticket</a>
            </li>
          <?php }
          ?>

          <?php if ($user->userHasPermission('Billets_apercue')) { ?>
            <li> <a href="<?= BASE_URL ?>/admin/Liste_du_jours"><i class="bi bi-arrow-right-short"></i>Liste des ticket</a>
            </li>
            <li> <a href="<?= BASE_URL ?>/admin/Liste_du_jours/historique"><i class="bi bi-arrow-right-short"></i>Historique des billets</a>
            </li>
          <?php }
          ?>
          <?php if ($user->userHasPermission('Billets_embarquement')) { ?>
            <li> <a href="<?= BASE_URL ?>/admin/Liste_du_jours/embarquement"><i class="bi bi-arrow-right-short"></i>Embarquement
                <?php if (!empty($carsCompletsSidebar)): ?>
                    <span class="badge bg-danger rounded-pill float-end" title="Bus complet(s), embarquement requis"><?= count($carsCompletsSidebar) ?></span>
                <?php endif; ?>
              </a>
            </li>
          <?php }
          ?>
          <?php if ($user->userHasPermission('Billets_validation')) { ?>
            <li> <a href="<?= BASE_URL ?>/admin/Liste_ententes"><i class="bi bi-arrow-right-short"></i>Ticket en entente</a>
            </li>
          <?php }
          ?>
          <?php if (in_array($_SESSION['droit'] ?? null, ['Admin', 'super_admin', 'PDG'], true)): ?>
            <li> <a href="<?= BASE_URL ?>/admin/Liste_du_jours/demandesAnnulation"><i class="bi bi-arrow-right-short"></i>Demandes d'annulation</a>
            </li>
          <?php endif; ?>
          <?php if (in_array($_SESSION['droit'] ?? null, ['Admin', 'super_admin', 'PDG', 'chef_d_escale'], true) && $user->userHasPermission('Billets_annulation')): ?>
            <li> <a href="<?= BASE_URL ?>/admin/Liste_du_jours/demandesReport"><i class="bi bi-arrow-right-short"></i>Demandes de report
                <?php if (!empty($demandesReportSidebar)): ?>
                    <span class="badge bg-warning text-dark rounded-pill float-end" title="Demande(s) de report en attente"><?= count($demandesReportSidebar) ?></span>
                <?php endif; ?>
              </a>
            </li>
          <?php endif; ?>
        </ul>
        </li>
        <?php if ($user->userHasPermission('Billets_creation')) { ?>
          <li>
            <a href="javascript:;" class="has-arrow">
              <div class="parent-icon"><i class="bx bx-bar-chart-alt-2"></i>
              </div>
              <div class="menu-title">Rapport billets</div>
            </a>
          <?php }
          ?>
          <ul>

            <?php if ($user->userHasPermission('Billets_apercue')) { ?>
              <li> <a href="<?= BASE_URL ?>/admin/Rapport_billets/rapport_billets"><i class="bi bi-arrow-right-short"></i>Rapport mensuel</a>
              </li>
            <?php }
            ?>
            <?php if ($user->userHasPermission('Billets_validation')) { ?>
              <li> <a href="<?= BASE_URL ?>/admin/Rapport_billets/rapport_annuel"><i class="bi bi-arrow-right-short"></i>Rapport annuel</a>
              </li>
            <?php }
            ?>
          </ul>
          </li>

          <?php if ($user->userHasPermission('colis_creation')) { ?>
            <li class="menu-label">Gestion des colis</li>
            <li>
              <a href="javascript:;" class="has-arrow">
                <div class="font-22"> <i class="fadeIn animated bx bx-layer-plus"></i>
                </div>
                <div class="menu-title">G-colis</div>
              </a>
            <?php } ?>
            <ul>
              <?php if ($user->userHasPermission('colis_creation')) { ?>
                <li> <a href="<?= BASE_URL ?>/admin/Colis_prise_en_charges"><i class="bi bi-arrow-right-short"></i>Liste des colis</a>
                </li>
              <?php } ?>
              <?php if ($user->userHasPermission('colis_envoi')) { ?>
                <li> <a href="<?= BASE_URL ?>/admin/Envoi_colis/envoi_colis"><i class="bi bi-arrow-right-short"></i>Envoi des colis</a>
                </li>
              <?php } ?>
              <?php if ($user->userHasPermission('colis_mouvement')) { ?>
                <li> <a href="<?= BASE_URL ?>/admin/Mouvement_colis"><i class="bi bi-arrow-right-short"></i>Mouvement des colis</a>
                </li>
              <?php } ?>
              <?php if ($user->userHasPermission('colis_livraison')) { ?>
                <li> <a href="<?= BASE_URL ?>/admin/Livraison_colis"><i class="bi bi-arrow-right-short"></i>Livraison des colis</a>
                </li>
              <?php } ?>
            </ul>
            </li>
            <?php if ($user->userHasPermission('colis_reclamation')) { ?>
              <li>
                <a href="<?= BASE_URL ?>/admin/Reclamations">
                  <div class="font-22"> <i class="fadeIn animated bx bx-error"></i>
                  </div>
                  <div class="menu-title">Reclamation</div>
                </a>
              </li>
            <?php } ?>
            <?php if ($user->userHasPermission('colis_historique')) { ?>
              <li>
                <a href="<?= BASE_URL ?>/admin/Historiques/historique_colis_enregistrer">
                  <div class="font-22"><i class="bx bx-history"></i>
                  </div>
                  <div class="menu-title">Historique des colis</div>
                </a>
              </li>
            <?php } ?>

            <?php if ($user->userHasPermission('Caisse_apercue')) {
            ?>
              <li class="menu-label">Gestion de caisse</li>
              <li>
                <a href="javascript:;" class="has-arrow">
                  <div class="parent-icon"><i class="bx bx-wallet"></i>
                  </div>
                  <div class="menu-title">Caisse</div>
                </a>
              <?php } ?>
              <ul>
                <?php if ($user->userHasPermission('Caisse_apercue')) {
                ?>
                  <!-- Module de caisse individuelle (remplace l'ancienne "Caisse Générale") -->
                  <?php if (in_array($_SESSION['droit'] ?? null, ['Utilisateur', 'Admin', 'chef_d_escale'], true)): ?>
                    <li> <a href="<?= BASE_URL ?>/admin/Caisse/ma_caisse"><i class="bi bi-arrow-right-short"></i>Ma Caisse</a></li>
                  <?php endif; ?>

                  <?php if (in_array($_SESSION['droit'] ?? null, ['chef_d_escale', 'Admin', 'PDG'], true)): ?>
                    <li> <a href="<?= BASE_URL ?>/admin/Caisse/caisses_escale"><i class="bi bi-arrow-right-short"></i>Supervision Escale</a></li>
                  <?php endif; ?>

                  <?php if (in_array($_SESSION['droit'] ?? null, ['Admin', 'PDG'], true)): ?>
                    <li> <a href="<?= BASE_URL ?>/admin/Caisse/rapport_proprietaire"><i class="bi bi-arrow-right-short"></i>Rapport Compagnie</a></li>
                    <li> <a href="<?= BASE_URL ?>/admin/Caisse/anomalies_caisses"><i class="bi bi-arrow-right-short"></i>Anomalies de caisse</a></li>
                  <?php endif; ?>
                <?php } ?>
                <?php if ($user->userHasPermission('Caisse_billant') && ($_SESSION['droit'] ?? null) !== 'Utilisateur') {
                ?>
                  <li> <a href="<?= BASE_URL ?>/admin/Caisse/bilant_caisse_billets"><i class="bi bi-arrow-right-short"></i>Bilan de caisse</a>
                  </li>
                <?php } ?>
              </ul>
              </li>

              <?php if (in_array($_SESSION['droit'] ?? null, ['Admin', 'chef_d_escale', 'PDG'], true)): ?>
                <li class="menu-label">Finances</li>
                <li>
                  <a href="javascript:;" class="has-arrow">
                    <div class="parent-icon"><i class="bx bx-money"></i>
                    </div>
                    <div class="menu-title">Dépenses</div>
                  </a>
                  <ul>
                    <li> <a href="<?= BASE_URL ?>/admin/Depenses"><i class="bi bi-arrow-right-short"></i>Gérer les dépenses</a>
                    </li>
                    <?php if (in_array($_SESSION['droit'] ?? null, ['Admin', 'PDG'], true)): ?>
                      <li> <a href="<?= BASE_URL ?>/admin/Depenses/benefice"><i class="bi bi-arrow-right-short"></i>Bénéfice de la compagnie</a>
                      </li>
                    <?php endif; ?>
                  </ul>
                </li>
                <li>
                  <a href="javascript:;" class="has-arrow">
                    <div class="parent-icon"><i class="bx bx-car"></i>
                    </div>
                    <div class="menu-title">Location des cars</div>
                  </a>
                  <ul>
                    <li> <a href="<?= BASE_URL ?>/admin/Locations_cars"><i class="bi bi-arrow-right-short"></i>Gérer les locations</a>
                    </li>
                  </ul>
                </li>
              <?php endif; ?>

              <?php
                $peutVoirUtilisateurs = $user->userHasPermission('utilisateur_apercu');
                $peutVoirChauffeurs = $user->userHasPermission('Configuration_gestion_car/chauffeur');
              ?>
              <?php if ($peutVoirUtilisateurs || $peutVoirChauffeurs): ?>
                <li class="menu-label">Personnel</li>
                <li>
                  <a href="<?= BASE_URL ?>/admin/Employes">
                    <div class="parent-icon"><i class="bx bx-id-card"></i>
                    </div>
                    <div class="menu-title">Employés</div>
                  </a>
                </li>
              <?php endif; ?>

              <?php if ($user->userHasPermission('Salaire_apercu')): ?>
                <li>
                  <a href="<?= BASE_URL ?>/admin/Salaires">
                    <div class="parent-icon"><i class="bx bx-money"></i>
                    </div>
                    <div class="menu-title">Salaires</div>
                  </a>
                </li>
              <?php endif; ?>

              <?php if (in_array($_SESSION['droit'] ?? null, ['Admin', 'chef_d_escale', 'PDG'], true)): ?>
                <li class="menu-label">Banque</li>
                <li>
                  <a href="javascript:;" class="has-arrow">
                    <div class="parent-icon"><i class="bx bx-buildings"></i>
                    </div>
                    <div class="menu-title">Dépôt en banque</div>
                  </a>
                  <ul>
                    <?php if (in_array($_SESSION['droit'] ?? null, ['Admin', 'PDG'], true)): ?>
                      <li> <a href="<?= BASE_URL ?>/admin/Banques"><i class="bi bi-arrow-right-short"></i>Comptes banque</a>
                      </li>
                      <li> <a href="<?= BASE_URL ?>/admin/Depots_banque/enAttente"><i class="bi bi-arrow-right-short"></i>Demandes en attente</a>
                      </li>
                    <?php endif; ?>
                    <?php if (($_SESSION['droit'] ?? null) !== 'PDG'): ?>
                      <li> <a href="<?= BASE_URL ?>/admin/Depots_banque"><i class="bi bi-arrow-right-short"></i>Faire un dépôt</a>
                      </li>
                    <?php endif; ?>
                    <li> <a href="<?= BASE_URL ?>/admin/Depots_banque/historique"><i class="bi bi-arrow-right-short"></i>Historique des dépôts</a>
                    </li>
                  </ul>
                </li>
              <?php endif; ?>

              <?php
                $peutVoirGProgramme = $user->userHasPermission('Programme_Creation')
                    || $user->userHasPermission('Programme_programmer_car')
                    || $user->userHasPermission('Programme_programmation_voyage')
                    || $user->userHasPermission('Programme_hors_programme')
                    || in_array($_SESSION['droit'] ?? null, ['Admin', 'super_admin', 'PDG'], true);
              ?>
              <?php if ($peutVoirGProgramme): ?>
                <li class="menu-label">Gestion des programmations</li>
                <li>
                  <a href="javascript:;" class="has-arrow">
                    <div class="parent-icon"><i class="bx bx-calendar"></i>
                    </div>
                    <div class="menu-title">G-programme</div>
                  </a>
                <ul>
                  <?php if ($user->userHasPermission('Programme_Creation')) { ?>
                    <li> <a href="<?= BASE_URL ?>/admin/Programmer_voyages"><i class="bi bi-arrow-right-short"></i>Programme du voyage</a>
                    </li>
                  <?php } ?>
                  <?php if ($user->userHasPermission('Programme_programmer_car')) { ?>
                    <li> <a href="<?= BASE_URL ?>/admin/Programmation_cars"><i class="bi bi-arrow-right-short"></i>Affectation des cars</a>
                    </li>
                  <?php } ?>
                  <?php if ($user->userHasPermission('Programme_programmation_voyage')) { ?>
                    <li> <a href="<?= BASE_URL ?>/admin/Programmation_voyages/liste_programmer_voyage"><i class="bi bi-arrow-right-short"></i>Programmation du voyage</a>
                    </li>
                  <?php } ?>
                  <?php if ($user->userHasPermission('Programme_programmation_voyage') && in_array($_SESSION['droit'] ?? null, ['Admin', 'chef_d_escale', 'super_admin', 'PDG'], true)): ?>
                    <li> <a href="<?= BASE_URL ?>/admin/Transferts_gares/historique"><i class="bi bi-arrow-right-short"></i>Transferts entre gares</a>
                    </li>
                  <?php endif; ?>
                  <?php if ($user->userHasPermission('Programme_hors_programme')) { ?>
                    <li> <a href="#"><i class="bi bi-arrow-right-short"></i>Hors programmer</a>
                    </li>
                  <?php } ?>
                  <?php if (in_array($_SESSION['droit'] ?? null, ['Admin', 'super_admin', 'PDG'], true)): ?>
                    <li> <a href="<?= BASE_URL ?>/admin/Flotte"><i class="bi bi-arrow-right-short"></i>État de la flotte</a>
                    </li>
                  <?php endif; ?>
                </ul>
                </li>
              <?php endif; ?>
                <li class="menu-label"></li>
                <!-- <li>
              <a href="#">
                <div class="parent-icon"><i class="fadeIn animated bx bx-shape-polygon"></i>
                </div>
                <div class="menu-title">Reclamation</div>
              </a>
            </li>
             <li>
              <a href="#">
                <div class="parent-icon"><i class="fadeIn animated bx bx-shape-polygon"></i>
                </div>
                <div class="menu-title">Historique</div>
              </a>
            </li>
             <li>
              <a href="#">
                <div class="parent-icon"><i class="fadeIn animated bx bx-shape-polygon"></i>
                </div>
                <div class="menu-title">Caisse</div>
              </a>
            </li> -->

                <?php endif; // Fin du if !== 'super_admin' ?>

                <?php if (isset($_SESSION['droit']) && in_array($_SESSION['droit'], ['Admin', 'super_admin', 'PDG'], true)): ?>
                <?php if ($user->userHasPermission('Configuration_apercu')) { ?>
                  <li class="menu-label">Paramètre</li>
                  <li>
                    <?php if ($_SESSION['droit'] === 'super_admin'): ?>
                      <a href="<?= BASE_URL ?>/admin/Compagnies">
                        <div class="parent-icon">
                          <i class="fadeIn animated bx bx-shape-polygon"></i>
                        </div>
                        <div class="menu-title">Configuration</div>
                      </a>
                    <?php else: ?>
                      <a href="<?= BASE_URL ?>/admin/Liste_gares">
                        <div class="parent-icon">
                          <i class="fadeIn animated bx bx-shape-polygon"></i>
                        </div>
                        <div class="menu-title">Configuration</div>
                      </a>
                    <?php endif; ?>
                  </li>
                  <?php if ($_SESSION['droit'] === 'super_admin'): ?>
                  <?php $partenaireModel = new Partenaire(); $nbEnAttente = $partenaireModel->countEnAttenteReponse(); ?>
                  <li>
                      <a href="<?= BASE_URL ?>/admin/Partenariats">
                        <div class="parent-icon">
                          <i class="fadeIn animated bx bx-handshake"></i>
                        </div>
                        <div class="menu-title">
                          Demandes de partenariat
                          <?php if ($nbEnAttente > 0): ?>
                            <span class="badge bg-danger rounded-pill ms-1"><?= $nbEnAttente ?></span>
                          <?php endif; ?>
                        </div>
                      </a>
                  </li>
                  <?php endif; ?>
   <?php } ?>
                <?php endif; ?>

                <!-- Accessible a TOUT compte connecte, quel que soit le role ou les permissions
                     (documentation, pas un ecran metier) : volontairement hors de tout if de
                     role/permission ci-dessus, et placee tout en bas du menu. -->
                <li class="menu-label">Aide</li>
                <li>
                  <a href="<?= BASE_URL ?>/admin/Documentations">
                    <div class="parent-icon"><i class="bx bx-book-open"></i>
                    </div>
                    <div class="menu-title">Documentation</div>
                  </a>
                </li>

    </ul>
    <!--end navigation-->
  </aside>