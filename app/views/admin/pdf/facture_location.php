<?php
date_default_timezone_set('Africa/Bamako');

$statutLabels = [
    'en_attente' => 'En attente de validation',
    'valide'     => 'Validée',
    'rejete'     => 'Rejetée',
];
$statutLabel = $statutLabels[$location->statut] ?? $location->statut;

// Une location creee par un chef d'escale n'est effective qu'apres validation par
// l'Admin : la signature doit alors porter la mention "P.O." (pour ordre), le chef
// d'escale signant par delegation de l'Admin qui a valide. Une location creee
// directement par l'Admin (toujours "validee" des sa creation) n'a pas besoin de
// cette mention : il signe en son nom propre.
$creeParChefEscale = ($location->agent_droit ?? null) === 'chef_d_escale';
$mentionSignature = ($creeParChefEscale && $location->statut === 'valide')
    ? 'Signature (P.O. ' . ($location->valide_par_nom ?? 'Admin') . ')'
    : 'Signature';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <style>
    @page { margin: 15mm 10mm; }

    body {
      font-family: DejaVu Sans, sans-serif;
      font-size: 12px;
      margin: 0;
      padding: 0;
      color: #222;
    }

    header {
      border-bottom: 2px solid #333;
      padding-bottom: 8px;
      margin-bottom: 14px;
    }

    header table { width: 100%; }
    header img { height: 45px; }

    h2 {
      margin: 4px 0;
      font-size: 16px;
      text-align: center;
      text-transform: uppercase;
      color: #2c3e50;
    }

    h3 {
      text-align: center;
      font-size: 13px;
      margin: 4px 0 16px;
      color: #333;
      font-weight: normal;
    }

    .section { margin-bottom: 14px; }

    .section h4 {
      font-size: 12px;
      text-transform: uppercase;
      margin: 0 0 4px;
      padding-bottom: 2px;
      border-bottom: 1px solid #999;
      color: #2c3e50;
    }

    table.infos { width: 100%; border-collapse: collapse; }

    table.infos td {
      border: 1px solid #ccc;
      padding: 5px 8px;
      font-size: 12px;
    }

    table.infos td.label {
      width: 35%;
      font-weight: bold;
      background: #f5f5f5;
    }

    .montant {
      text-align: center;
      margin-top: 18px;
      padding: 12px;
      border: 2px solid #2c3e50;
      border-radius: 6px;
    }

    .montant .label { font-size: 12px; color: #555; }
    .montant .valeur { font-size: 22px; font-weight: bold; color: #2c3e50; }

    .signature-wrap {
      margin-top: 40px;
      text-align: right;
    }

    .signature {
      display: inline-block;
      width: 240px;
      text-align: center;
    }

    .signature .ligne {
      margin-top: 50px;
      border-top: 1px solid #333;
      padding-top: 4px;
      font-size: 11px;
      color: #444;
    }

    footer {
      margin-top: 20px;
      font-size: 9px;
      text-align: center;
      color: #777;
    }
  </style>
</head>
<body>

  <header>
    <table>
      <tr>
        <td width="60">
          <?php if (!empty($logoPath) && file_exists($logoPath)): ?>
            <img src="file://<?= realpath($logoPath) ?>" alt="Logo">
          <?php endif; ?>
        </td>
        <td>
          <h2><?= htmlspecialchars($compagnie['nom'] ?? 'Nom Compagnie') ?></h2>
          <?php if (!empty($compagnie['slogant'])): ?>
            <div style="text-align:center;font-size:11px;font-style:italic;"><?= htmlspecialchars($compagnie['slogant']) ?></div>
          <?php endif; ?>
        </td>
      </tr>
    </table>
  </header>

  <h3>Facture de location de <?= ($location->type_vehicule ?? 'car') === 'camion' ? 'camion' : 'car' ?> — N° <?= str_pad($location->id_location, 6, '0', STR_PAD_LEFT) ?></h3>

  <div class="section">
    <h4>Détails de la location</h4>
    <table class="infos">
      <tr>
        <td class="label">Gare de départ</td>
        <td><?= htmlspecialchars(($location->localite ?? '-') . ' (' . ($location->numeroGare ?? '-') . ')') ?></td>
        <td class="label">Destination</td>
        <td><?= htmlspecialchars($location->destination) ?></td>
      </tr>
      <tr>
        <td class="label"><?= ($location->type_vehicule ?? 'car') === 'camion' ? 'Camion' : 'Car' ?></td>
        <td><?= htmlspecialchars('N°' . ($location->numero_vehicule ?? '-') . ' - ' . ($location->matricule_vehicule ?? '-')) ?></td>
        <td class="label">Statut</td>
        <td><?= htmlspecialchars($statutLabel) ?></td>
      </tr>
      <tr>
        <td class="label">Date de départ</td>
        <td><?= date('d/m/Y', strtotime($location->date_depart)) ?></td>
        <td class="label">Date de retour prévu</td>
        <td><?= date('d/m/Y', strtotime($location->date_retour_prevu)) ?></td>
      </tr>
      <tr>
        <td class="label">Enregistrée par</td>
        <td colspan="3"><?= htmlspecialchars($location->agent ?? '-') ?></td>
      </tr>
    </table>
  </div>

  <div class="section">
    <h4>Client</h4>
    <table class="infos">
      <tr>
        <td class="label">Nom et prénom</td>
        <td><?= htmlspecialchars($location->prenom_client . ' ' . $location->nom_client) ?></td>
        <td class="label">Téléphone</td>
        <td><?= htmlspecialchars($location->telephone_client) ?></td>
      </tr>
    </table>
  </div>

  <div class="montant">
    <div class="label">Frais de location</div>
    <div class="valeur"><?= number_format($location->frais_location, 0, ',', ' ') ?> FCFA</div>
  </div>

  <div class="signature-wrap">
    <div class="signature">
      <div class="ligne"><?= htmlspecialchars($mentionSignature) ?></div>
    </div>
  </div>

  <footer>
    Facture générée le <?= date('d/m/Y à H:i') ?>.
  </footer>

</body>
</html>
