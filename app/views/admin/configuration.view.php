<?php $this->view('admin/partials/header') ?>
 <?php $user = new Configuration($_SESSION['id_utilisateur']) ?>
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
      <div class="page-breadcrumb d-flex flex-wrap align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Configuration</div>
        <div class="ps-3">
          <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
              <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a>
              </li>
              <li class="breadcrumb-item active" aria-current="page">Utilisateur</li>
            </ol>
          </nav>
        </div>
        <div class="ms-auto">
          <div class="d-flex gap-2">
            <?php if (!$user->estLectureSeule()): ?>
            <a href="<?= BASE_URL ?>/admin/Configurations/add_utilisateurs" class="btn btn-success d-flex align-items-center gap-2 shadow-sm">
                            <i class="bx bx-plus-circle fs-5"></i> Ajouter
                        </a>
                        <?php endif; ?>
                        <a href="javascript:history.back()"
                            class="btn btn-outline-primary d-flex align-items-center gap-2 shadow-sm">
                            <i class="bx bx-left-arrow-alt fs-5"></i> Retour
                        </a>

          </div>
        </div>
      </div>
      <!--end breadcrumb-->

      <div class="row">
                                <div class="col-12 col-xxl-3">
          <div class="card config-card">
            <div class="card-header">
              <div class="card-title">
                <i class="bx bx-cog fs-4 me-2"></i> Paramètres Généraux
              </div>
            </div>
            <div class="card-body p-3">
              <ul class="nav nav-tabs flex-column vertical-tabs-custom" role="tablist">
                <?php if ($_SESSION['droit'] === 'super_admin'): ?>
                  <li class="nav-item">
                    <a class="nav-link  text-break" role="tab"
                      aria-current="page" href="<?= BASE_URL ?>/admin/Compagnies"
                      aria-selected="true">
                      <i class="bx bx-buildings me-2 align-middle d-inline-block"></i>Compagnie
                    </a>
                  </li>
                <?php endif; ?>
                <?php if ($user->userHasPermission('utilisateur_apercu')) { ?>
                  <li class="nav-item">
                    <a class="nav-link active text-break" role="tab"
                      aria-current="page" href="<?= BASE_URL ?>/admin/Configurations"
                      aria-selected="true">
                      <i class="bx bx-user me-2 align-middle d-inline-block"></i>Utilisateur
                    </a>
                  </li>
                <?php } ?>
                <?php if ($user->userHasPermission('Configuration_gestion_gare')) { ?>
                  <li class="nav-item">
                    <a class="nav-link  text-break" role="tab"
                      aria-current="page" href="<?= BASE_URL ?>/admin/Liste_gares"
                      aria-selected="true">
                      <i class="bx bx-home me-2 align-middle d-inline-block"></i>Gares
                    </a>
                  </li>
                <?php } ?>
                
                <?php if ($user->userHasPermission('Configuration_gestion_escale')) { ?>
                  <li class="nav-item">
                    <a class="nav-link  text-break mb-0" role="tab"
                      aria-current="page" href="<?= BASE_URL ?>/admin/Add_liste_escales"
                      aria-selected="true">
                      <i class="bx bx-map-pin me-2 align-middle d-inline-block"></i>Escale
                    </a>
                  </li>
                <?php } ?>
                <?php /* Trajets masqué : ne doit pas apparaître dans Configuration
                <?php if ($user->userHasPermission('Configuration_gestion_trajets')) { ?>
                  <li class="nav-item mt-2">
                    <a class="nav-link  text-break mb-0" role="tab"
                      aria-current="page" href="<?= BASE_URL ?>/admin/Add_liste_trajets"
                      aria-selected="true">
                      <i class="bx bx-map-alt me-2 align-middle d-inline-block"></i>Trajets
                    </a>
                  </li>
                <?php } ?>
                */ ?>
                <?php if ($user->userHasPermission('Configuration_gestion_horaire')) { ?>
                  <li class="nav-item mt-2">
                    <a class="nav-link  text-break mb-0" role="tab"
                      aria-current="page" href="<?= BASE_URL ?>/admin/Add_liste_horaire"
                      aria-selected="true">
                      <i class="bx bx-time me-2 align-middle d-inline-block"></i>Horaire
                    </a>
                  </li>
                <?php } ?>
                <?php if ($user->userHasPermission('Configuration_gestion_car/chauffeur')) { ?>
                  <li class="nav-item mt-2">
                    <a class="nav-link  text-break" role="tab"
                      aria-current="page" href="<?= BASE_URL ?>/admin/Cars_chauffeurs"
                      aria-selected="true">
                      <i class="bx bx-car me-2 align-middle d-inline-block"></i>Cars & Camions & Chauffeurs
                    </a>
                  </li>
                <?php } ?>
                
                <?php if ($_SESSION['droit'] === 'super_admin'): ?>
                <li class="nav-item mt-2">
                  <a class="nav-link  text-break mb-0" role="tab"
                    aria-current="page" href="<?= BASE_URL ?>/admin/Add_liste_horaire/add_permission"
                    aria-selected="true">
                    <i class="bx bx-shield-quarter me-2 align-middle d-inline-block"></i>Permission
                  </a>
                </li>
                <?php endif; ?>
                
                <?php if ($user->userHasPermission('Configuration_place/limite')) { ?>
                  <li class="nav-item mt-2">
                    <a class="nav-link  text-break" role="tab"
                      aria-current="page" href="<?= BASE_URL ?>/admin/Compagnies/place_limite"
                      aria-selected="true">
                      <i class="bx bx-chair me-2 align-middle d-inline-block"></i>Place limite
                    </a>
                  </li>
                <?php } ?>
              </ul>
            </div>
          </div>
        </div>
        <div class="col-12 col-xxl-9">
          <?php $this->view("admin/set_flash") ?>
          <div class="card config-card">
            <div class="card-header">
              <h5 class="mb-0 fw-bold"><i class="bx bx-group me-2"></i>Liste des utilisateurs</h5>
            </div>
            <div class="card-body p-4">
              <div class="table-responsive">
                <table id="example" class="table table-striped table-bordered table-hover-effect table-custom-header text-center mobile-card-table" style="width:100%">
                  <thead class="table-light text-center">
                    <tr>
                      <th class="fw-semibold">Photo</th>
                      <th class="fw-semibold">Utilisateur</th>
                      <th class="fw-semibold">Email</th>
                      <th class="fw-semibold">Téléphone</th>
                      <th class="fw-semibold">Gare</th>
                      <th class="fw-semibold">Droit</th>
                      <th class="fw-semibold">Service</th>
                      <th class="fw-semibold">Action</th>
                    </tr>
                  </thead>
                  <tbody class="text-center">
                    <?php foreach ($liste as $listes): ?>
                      <tr class="align-middle text-center">
                        <td data-label="Photo">
                            <?php if (!empty($listes->photo)): ?>
                                <img src="<?= ASSET_URL ?>/uploads/profiles/<?= htmlspecialchars($listes->photo) ?>" alt="Photo" class="rounded-circle" width="40" height="40" style="object-fit: cover;">
                            <?php else: ?>
                                <div class="rounded-circle bg-secondary text-white d-inline-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    <i class="bx bx-user fs-5"></i>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td data-label="Utilisateur"><?= htmlspecialchars($listes->utilisateurs) ?></td>
                        <td data-label="Email"><?= htmlspecialchars($listes->emailUser) ?></td>
                        <td data-label="Téléphone"><?= htmlspecialchars($listes->telephone ?? '') ?></td>
                        <td data-label="Gare"><?= htmlspecialchars($listes->numeroGare ?? '') ?></td>
                        <td data-label="Droit"><?= htmlspecialchars($listes->droit) ?></td>
                        <td data-label="Service">
                          <?php if ($listes->droit === 'Utilisateur' && !empty($listes->profile)): ?>
                            <?= $listes->profile === 'billet' ? 'Billetterie' : ($listes->profile === 'colis' ? 'Colis / Courrier' : htmlspecialchars($listes->profile)) ?>
                          <?php else: ?>
                            <span class="text-muted">—</span>
                          <?php endif; ?>
                        </td>
                        <!-- <td>
                          <div class="dropdown">
                            <a href="#" class="text-dark fs-5" data-bs-toggle="dropdown" aria-expanded="false">
                              &#8943;
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                              <li>
                                <a class="dropdown-item" href="#"><i class="bi bi-pencil-square me-2"></i>Modifier</a>
                              </li>
                              <li>
                                <a class="dropdown-item text-danger" href="#"><i class="bi bi-x-circle me-2"></i>Désactiver</a>
                              </li>
                            </ul>
                          </div>
                        </td> -->
                        <td class="text-center" data-label="Action">
                          <?php if (!$user->estLectureSeule()): ?>
                          <a href="<?= BASE_URL ?>/admin/Permissions/assigner/<?= htmlspecialchars($listes->idUser) ?>"
                            title="Permission">
                            <i class="bx bx-lock-open text-primary fs-4 cursor-pointer"></i>
                          </a>


                          <!-- Modifier : gestion d'equipe par l'Admin/super_admin, comme les icones
                               Permission et Activer/Desactiver ci-dessus. L'edition de ses propres
                               infos par l'utilisateur connecte se fait via "Mon Profil"
                               (admin/Profils/index), pas ici. -->
                          <i class="bx bx-edit text-primary me-2 fs-4 cursor-pointer add-button"
                            title="Modifier"
                            data-bs-toggle="modal"
                            data-bs-target="#modalModification"
                            data-id="<?= $listes->idUser ?>"
                            data-utilisateurs="<?= $listes->utilisateurs ?>"
                            data-email="<?= $listes->emailUser ?>"
                            data-telephone="<?= htmlspecialchars($listes->telephone ?? '') ?>"
                            data-motpasse="<?= $listes->motPasse ?>"
                            data-droit="<?= $listes->droit ?>"
                            data-profile="<?= htmlspecialchars($listes->profile ?? '') ?>"
                            data-id-agence="<?= htmlspecialchars($listes->id_agence ?? '') ?>"
                            data-gares='<?= htmlspecialchars(json_encode($garesParCompagnie[(int)($listes->id_compagnie ?? 0)] ?? []), ENT_QUOTES) ?>'>
                          </i>

                          <!-- Activation/Désactivation -->
                          <?php if ($listes->status == 1): ?>

                            <!-- Activer -->
                            <i class="bx bx-user-check text-success fs-4 cursor-pointer"

                              data-bs-toggle="modal"
                              data-bs-target="#animationModal<?= $listes->idUser ?>">
                            </i>
                          <?php else: ?>
                            <!-- Désactiver -->
                            <i class="bx bx-user-x text-danger fs-4 cursor-pointer"
                              data-bs-toggle="modal"
                              data-bs-target="#animationModal<?= $listes->idUser ?>">
                            </i>
                          <?php endif; ?>
                          <?php endif; ?>

                          <?php if (($_SESSION['droit'] ?? null) === 'super_admin'): ?>
                            <!-- Supprimer (réservé au super_admin) -->
                            <i class="bx bx-trash text-danger fs-4 cursor-pointer ms-2"
                              title="Supprimer définitivement"
                              data-bs-toggle="modal"
                              data-bs-target="#deleteModal<?= $listes->idUser ?>">
                            </i>
                          <?php endif; ?>
                        </td>
                      </tr>
                    <?php endforeach ?>
                  </tbody>
                </table>
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

  <?php /* Modals par utilisateur (suppression, activation/désactivation) : rendues ici, hors
           du tableau, sinon un modal Bootstrap (position: fixed) imbriqué dans .table-responsive
           (overflow-x: auto) se retrouve piégé/mal positionné dans la card au lieu de s'afficher
           par-dessus la page. */ ?>
  <?php foreach ($liste as $listes): ?>
    <?php if (($_SESSION['droit'] ?? null) === 'super_admin'): ?>
    <!-- Modal Suppression définitive -->
    <div class="modal fade" id="deleteModal<?= $listes->idUser ?>" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <form action="<?= BASE_URL ?>/admin/Configurations" method="post">
            <div class="modal-header bg-danger">
              <h5 class="modal-title text-white">Supprimer définitivement ce compte</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: invert(1) grayscale(100%) brightness(200%);"></button>
            </div>
            <div class="modal-body">
              <p>
                Cette action est <strong class="text-danger">irréversible</strong>. Le compte
                <strong><?= htmlspecialchars($listes->utilisateurs) ?></strong> sera supprimé
                ainsi que ses données propres (permissions, historique de connexion).
                Ses billets/colis/dépenses déjà enregistrés sont conservés mais détachés de son compte.
              </p>
              <p class="mb-1">Pour confirmer, saisissez l'email exact de ce compte :</p>
              <p class="fw-bold mb-2"><?= htmlspecialchars($listes->emailUser) ?></p>
              <input type="text"
                class="form-control confirm-delete-input"
                name="confirmation"
                data-expected="<?= htmlspecialchars($listes->emailUser) ?>"
                autocomplete="off"
                placeholder="Saisir l'email pour confirmer">
              <input type="hidden" name="idUser" value="<?= $listes->idUser ?>">
              <?= csrf_field() ?>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
              <button type="submit" name="deleteUtilisateur" class="btn btn-danger delete-submit-btn" disabled>
                Supprimer définitivement
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <?php endif; ?>
    <!-- Modal Activation/Désactivation -->
    <div class="modal fade animate__animated animate__slideInDown"
      id="animationModal<?= $listes->idUser ?>"
      tabindex="-1"
      role="dialog"
      aria-labelledby="exampleModalCenterTitle"
      aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
          <form action="<?= BASE_URL ?>/admin/Configurations" method="post">
            <div class="modal-header">
              <h5 class="modal-title fw-bold text-primary" id="exampleModalCenterTitle">
                Confirmation de <?= $listes->status == 1 ? 'désactivation' : 'réactivation' ?>
              </h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
              </button>
            </div>
            <div class="modal-body text-center">
              <i class="nav-icon fa fa-exclamation-triangle text-danger" style="font-size: 60px;"></i>
              <p class="mt-3">
                Voulez-vous vraiment
                <strong class="text-danger">
                  <?= $listes->status == 1 ? 'désactiver' : 'activer' ?>
                </strong>
                le compte <br><strong><?= $listes->utilisateurs ?></strong> ?
              </p>

              <input type="hidden" name="idUser" value="<?= $listes->idUser ?>">
              <input type="hidden" name="newStatut" value="<?= $listes->status == 1 ? 0 : 1 ?>">
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
              <button type="submit" name="valider" class="btn btn-primary">
                Oui <?= $listes->status == 1 ? 'Désactiver' : 'Activer' ?>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  <?php endforeach ?>

  <!-- Modal Modification Utilisateur -->
  <div class="modal fade" id="modalModification" tabindex="-1" aria-labelledby="modalModificationLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header bg-primary">
          <h5 class="modal-title text-white" id="modalModificationLabel">Modifier l'utilisateur</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: invert(1) grayscale(100%) brightness(200%);"></button>
        </div>
        <form action="<?= BASE_URL ?>/admin/Configurations" method="post">
          <div class="modal-body text-start">
            <input type="hidden" name="editUtilisateur" value="1">
            <input type="hidden" name="idUser" id="edit_idUser">
            
            <div class="mb-3">
              <label for="edit_utilisateurs" class="form-label fw-semibold">Nom & prénom</label>
              <input type="text" class="form-control" id="edit_utilisateurs" name="utilisateurs" required>
            </div>
            
            <div class="mb-3">
              <label for="edit_emailUser" class="form-label fw-semibold">Email</label>
              <input type="email" class="form-control" id="edit_emailUser" name="emailUser" required>
            </div>

            <div class="mb-3">
              <label for="edit_telephone" class="form-label fw-semibold">Téléphone</label>
              <input type="tel" class="form-control" id="edit_telephone" name="telephone" pattern="[0-9+\s.\-]{6,20}">
            </div>

            <div class="mb-3">
              <label for="edit_droit" class="form-label fw-semibold">Droit</label>
              <select class="form-select" id="edit_droit" name="droit" required>
                  <option value="Utilisateur">Utilisateur</option>
                  <option value="secretaire">Secrétaire Général</option>
                  <?php if(isset($_SESSION['droit']) && $_SESSION['droit'] !== 'chef_d_escale'): ?>
                      <option value="chef_d_escale">Chef d'escale</option>
                  <?php endif; ?>
                  <?php if(isset($_SESSION['droit']) && $_SESSION['droit'] === 'super_admin'): ?>
                      <option value="Admin">Admin</option>
                      <option value="PDG">PDG (superviseur, lecture seule)</option>
                  <?php endif; ?>
              </select>
            </div>

            <div class="mb-3" id="edit_gareField" style="display: none;">
              <label for="edit_id_agence" class="form-label fw-semibold">Gare</label>
              <select class="form-select" id="edit_id_agence" name="id_agence">
                  <option value="" disabled selected>Choisissez une gare</option>
              </select>
              <div class="form-text">Le changement de gare ne prend effet qu'à la prochaine connexion de la personne.</div>
            </div>

            <div class="mb-3" id="edit_serviceField" style="display: none;">
              <label for="edit_profile" class="form-label fw-semibold">Service</label>
              <select class="form-select" id="edit_profile" name="profile">
                  <option value="" disabled selected>Choisissez un service</option>
                  <option value="billet">Billetterie</option>
                  <option value="colis">Colis / Courrier</option>
              </select>
            </div>

            <div class="mb-3">
              <label for="edit_motPasse" class="form-label fw-semibold">Nouveau mot de passe (laisser vide pour ne pas modifier)</label>
              <input type="password" class="form-control" id="edit_motPasse" name="motPasse">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
            <button type="submit" class="btn btn-primary">Enregistrer</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <?php $this->view('admin/partials/foot') ?>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      var editButtons = document.querySelectorAll('.bx-edit');
      var editDroit = document.getElementById('edit_droit');
      var editServiceField = document.getElementById('edit_serviceField');
      var editProfile = document.getElementById('edit_profile');
      var editGareField = document.getElementById('edit_gareField');
      var editIdAgence = document.getElementById('edit_id_agence');

      function toggleServiceField() {
        if (editDroit.value === 'Utilisateur') {
          editServiceField.style.display = 'block';
        } else {
          editServiceField.style.display = 'none';
          editProfile.value = '';
        }
      }

      // Meme regle que le formulaire d'ajout (add_utilisateur.view.php) : gare obligatoire
      // seulement pour Utilisateur/chef_d_escale, jamais pour Admin/PDG/secretaire.
      function toggleGareField() {
        if (editDroit.value === 'Utilisateur' || editDroit.value === 'chef_d_escale') {
          editGareField.style.display = 'block';
          editIdAgence.setAttribute('required', 'required');
        } else {
          editGareField.style.display = 'none';
          editIdAgence.removeAttribute('required');
          editIdAgence.value = '';
        }
      }

      editButtons.forEach(function(button) {
        button.addEventListener('click', function() {
          document.getElementById('edit_idUser').value = this.dataset.id;
          document.getElementById('edit_utilisateurs').value = this.dataset.utilisateurs;
          document.getElementById('edit_emailUser').value = this.dataset.email;
          document.getElementById('edit_telephone').value = this.dataset.telephone || '';
          editDroit.value = this.dataset.droit;
          document.getElementById('edit_motPasse').value = '';
          toggleServiceField();
          if (this.dataset.profile) {
            editProfile.value = this.dataset.profile;
          }

          // Reconstruit les options de gare a partir des gares de LA COMPAGNIE de ce
          // compte precis (data-gares, deja filtre cote serveur) : un select unique
          // statique melangerait les gares de compagnies differentes pour un super_admin.
          var gares = [];
          try { gares = JSON.parse(this.dataset.gares || '[]'); } catch (e) { gares = []; }
          var options = '<option value="" disabled selected>Choisissez une gare</option>';
          gares.forEach(function(g) {
            options += '<option value="' + g.idAgence + '">' + g.localite + ' ( ' + g.numeroGare + ' )</option>';
          });
          editIdAgence.innerHTML = options;
          if (this.dataset.idAgence) {
            editIdAgence.value = this.dataset.idAgence;
          }
          toggleGareField();
        });
      });

      editDroit.addEventListener('change', function() {
        toggleServiceField();
        toggleGareField();
      });

      // Suppression : le bouton ne s'active que si l'email saisi correspond exactement
      document.querySelectorAll('.confirm-delete-input').forEach(function(input) {
        input.addEventListener('input', function() {
          var form = input.closest('form');
          var btn = form.querySelector('.delete-submit-btn');
          btn.disabled = input.value !== input.dataset.expected;
        });
      });
    });
  </script>
</body>
</html>