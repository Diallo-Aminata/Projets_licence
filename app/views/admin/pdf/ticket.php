<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <style>
    @page { margin: 2mm 3mm; }

    * { box-sizing: border-box; }

    body {
      font-family: DejaVu Sans, sans-serif;
      font-size: 13px;
      margin: 0;
      padding: 0;
      color: #000;
    }

    .ticket { width: 100%; }

    .center { text-align: center; }

    header { text-align: center; margin-bottom: 4px; }

    header img {
      height: 34px;
      display: block;
      margin: 0 auto 3px;
    }

    h1 {
      margin: 1px 0;
      font-size: 17px;
      font-weight: bold;
      text-transform: uppercase;
      letter-spacing: 0.3px;
    }

    .slogan {
      display: block;
      font-size: 11px;
      font-style: italic;
      color: #000;
    }

    hr {
      border: none;
      border-top: 1px dashed #000;
      margin: 5px 0;
    }

    .doc-title {
      text-align: center;
      font-size: 14px;
      font-weight: bold;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin: 2px 0;
    }

    .doc-number {
      text-align: center;
      font-size: 15px;
      font-weight: bold;
      margin: 2px 0 4px;
    }

    table.infos {
      width: 100%;
      border-collapse: collapse;
      margin: 3px 0;
    }

    table.infos td {
      padding: 2px 0;
      vertical-align: top;
      font-size: 13px;
    }

    table.infos td.label {
      color: #000;
      white-space: nowrap;
      padding-right: 6px;
    }
    

    table.infos td.value {
      text-align: right;
      font-weight: bold;
    }

    .total-box {
      margin: 4px 0;
      padding: 2px 0;
    }

    .total-box table { width: 100%; border-collapse: collapse; }

    .total-box .label {
      font-size: 13px;
      font-weight: bold;
      text-transform: uppercase;
    }

    .total-box .value {
      font-size: 17px;
      font-weight: bold;
      text-align: right;
    }

    .conditions {
      margin: 6px 0;
      font-size: 10.5px;
      color: #000;
      line-height: 1.5;
      text-align: center;
    }

    footer {
      margin-top: 6px;
      padding-top: 4px;
      border-top: 1px dashed #000;
      font-size: 10px;
      color: #000;
      text-align: center;
      line-height: 1.5;
    }

    footer .thanks {
      font-size: 11px;
      color: #000;
      font-weight: bold;
      margin-top: 2px;
    }
  </style>
</head>
<body>

<?php date_default_timezone_set('Africa/Bamako'); ?>

<div class="ticket">

  <header>
    <?php if (!empty($logoPath) && file_exists($logoPath)): ?>
      <img src="file://<?= realpath($logoPath) ?>" alt="Logo">
    <?php endif; ?>
    <h1><?= htmlspecialchars($compagnie['nom'] ?? 'Nom Compagnie') ?></h1>
    <?php if (!empty($compagnie['slogant'])): ?>
      <span class="slogan"><?= htmlspecialchars($compagnie['slogant']) ?></span>
    <?php endif; ?>
  </header>

  <hr>

  <div class="doc-title">Billet de voyage</div>
  <div class="doc-number">N° <?= htmlspecialchars($billet->numeroBillets ?? '-') ?></div>

  <table class="infos">
    <tr>
      <td class="label">Client</td>
      <td class="value"><?= htmlspecialchars($billet->Client ?? '-') ?></td>
    </tr>
    <tr>
      <td class="label">Date</td>
      <td class="value"><?= !empty($billet->jourVoyage) ? date('d/m/Y', strtotime($billet->jourVoyage)) : '-' ?></td>
    </tr>
    <tr>
      <td class="label">Départ</td>
      <td class="value"><?= htmlspecialchars($_SESSION['ville'] ?? '-') ?></td>
    </tr>
    <tr>
      <td class="label">Heure</td>
      <td class="value">
        <?php
          $heureTs = !empty($billet->Heur_departs) ? strtotime($billet->Heur_departs) : false;
          echo $heureTs !== false ? date('H\hi', $heureTs) : htmlspecialchars($billet->Heur_departs ?? '-');
        ?>
      </td>
    </tr>
    <tr>
      <td class="label">Destination</td>
      <td class="value"><?= htmlspecialchars($billet->destinationId ?? '-') ?></td>
    </tr>
    <tr>
      <td class="label">Place(s)</td>
      <td class="value"><?= htmlspecialchars($billet->numeroPlace ?? '-') ?></td>
    </tr>
  </table>

  <hr>

  <?php if (!empty($bagages)): ?>
  <table class="infos">
    <?php foreach ($bagages as $b): ?>
    <tr>
      <td class="label">Bagage<?= !empty($b['description']) ? ' (' . htmlspecialchars($b['description']) . ')' : '' ?></td>
      <td class="value"><?= number_format((float)$b['prix'], 0, ',', ' ') ?> FCFA</td>
    </tr>
    <?php endforeach; ?>
  </table>
  <hr>
  <?php endif; ?>

  <div class="total-box">
    <table>
      <tr>
        <td class="label">Montant payé</td>
        <td class="value">
          <?php
            $montantNet = preg_replace('/[^\d.]/', '', $billet->montant_payer ?? '');
            echo !empty($montantNet)
                ? number_format((float)$montantNet, 0, ',', ' ') . ' FCFA'
                : '-';
          ?>
        </td>
      </tr>
      <?php if (!empty($bagages)): ?>
      <tr>
        <td class="label">Bagages</td>
        <td class="value"><?= number_format((float)array_sum(array_column($bagages, 'prix')), 0, ',', ' ') ?> FCFA</td>
      </tr>
      <?php endif; ?>
    </table>
  </div>

  <hr>

  <div class="conditions">
    Merci d’être à la gare <strong>45 minutes avant</strong> l’heure de départ.<br>
    Ce billet est <strong>valable pendant 1 semaine</strong> après sa date d’émission.
  </div>

  <footer>
    Émis par <?= htmlspecialchars($billet->utilisateurs ?? '-') ?>
    <div class="thanks">Merci d’avoir choisi <?= htmlspecialchars($compagnie['nom'] ?? 'notre compagnie') ?></div>
  </footer>

</div>

</body>
</html>
