<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <style>
    @page { margin: 14mm 12mm; }

    body {
      font-family: DejaVu Sans, sans-serif;
      font-size: 11px;
      margin: 0;
      padding: 0;
      color: #222;
    }

    header { border-bottom: 2px solid #333; padding-bottom: 8px; margin-bottom: 14px; }
    header table { width: 100%; }
    header img { height: 46px; }
    header .nom { font-size: 17px; font-weight: bold; text-transform: uppercase; color: #1c2b63; }
    header .slogan { font-size: 10px; font-style: italic; color: #555; }
    header .titre { text-align: right; font-size: 15px; font-weight: bold; text-transform: uppercase; color: #1c2b63; }
    header .num { text-align: right; font-size: 10px; color: #555; margin-top: 4px; }
    header .ligne-remplir { display: inline-block; min-width: 70px; border-bottom: 1px solid #333; }

    table.champs { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    table.champs td { padding: 5px 4px; font-size: 11px; vertical-align: bottom; }
    table.champs .label { font-weight: bold; color: #444; white-space: nowrap; padding-right: 6px; }
    table.champs .valeur { border-bottom: 1px solid #333; }
    table.champs .hint { font-size: 8.5px; font-style: italic; color: #888; }

    h3 {
      margin: 16px 0 6px;
      font-size: 11.5px;
      text-transform: uppercase;
      letter-spacing: .03em;
      color: #1c2b63;
      background: #e8ecf9;
      padding: 4px 6px;
      border-left: 3px solid #1c2b63;
    }

    table.lignes { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
    table.lignes th {
      text-align: left; font-size: 9px; text-transform: uppercase; color: #555;
      border-bottom: 1.5px solid #333; padding: 4px;
    }
    table.lignes td { border-bottom: 1px solid #ccc; padding: 10px 4px; font-size: 10px; }
    table.lignes tr.soustotal td { border-bottom: none; border-top: 1.5px solid #333; font-weight: bold; padding-top: 6px; }
    table.lignes tr.soustotal td:first-child { text-align: right; }

    table.recap { width: 60%; margin-left: auto; border-collapse: collapse; margin-top: 16px; }
    table.recap td { padding: 6px 4px; font-size: 11px; }
    table.recap td.label { color: #444; }
    table.recap td.valeur { border-bottom: 1px solid #333; width: 140px; text-align: right; }
    table.recap tr.total td { border-top: 2px solid #333; font-weight: bold; font-size: 13px; padding-top: 10px; }

    .copies { margin-top: 18px; font-size: 10px; color: #555; }
    .case { display: inline-block; width: 10px; height: 10px; border: 1.2px solid #333; margin-right: 4px; vertical-align: middle; }

    table.signatures { width: 100%; margin-top: 26px; border-collapse: collapse; }
    table.signatures td { width: 50%; font-size: 10px; vertical-align: top; padding-right: 20px; }
    table.signatures .role { font-weight: bold; text-transform: uppercase; color: #444; font-size: 9px; margin-bottom: 26px; display: block; }
    table.signatures .trait { display: block; border-bottom: 1px solid #333; margin-bottom: 4px; height: 1px; }

    footer {
      margin-top: 18px; padding-top: 8px; border-top: 1px dashed #999;
      font-size: 8.5px; color: #666; line-height: 1.5;
    }
  </style>
</head>
<body>

  <header>
    <table>
      <tr>
        <td style="width:60%;">
          <?php if (!empty($logoPath) && file_exists($logoPath)): ?>
            <img src="file://<?= realpath($logoPath) ?>" alt="Logo"><br>
          <?php endif; ?>
          <span class="nom"><?= htmlspecialchars($compagnie['nom'] ?? 'Nom Compagnie') ?></span><br>
          <?php if (!empty($compagnie['slogant'])): ?>
            <span class="slogan"><?= htmlspecialchars($compagnie['slogant']) ?></span>
          <?php endif; ?>
        </td>
        <td style="width:40%;">
          <div class="titre">Bordereau d'escale</div>
          <div class="num">N° Bordereau <span class="ligne-remplir">&nbsp;</span></div>
        </td>
      </tr>
    </table>
  </header>

  <table class="champs">
    <tr>
      <td class="label">Date</td><td class="valeur" style="width:20%;">&nbsp;</td>
      <td class="label">Bus N°</td><td class="valeur" style="width:20%;">&nbsp;</td>
    </tr>
    <tr>
      <td class="label">Escale</td><td class="valeur" colspan="3">&nbsp;</td>
    </tr>
    <tr><td></td><td class="hint" colspan="3">ex. Diéma, Nioro, Diboly…</td></tr>
    <tr>
      <td class="label">Chef / agent d'escale</td><td class="valeur" colspan="3">&nbsp;</td>
    </tr>
  </table>

  <h3>Billets vendus</h3>
  <table class="lignes">
    <thead>
      <tr><th style="width:40%;">Destination</th><th style="width:18%;">Nb billets</th><th style="width:20%;">Prix unit. FCFA</th><th style="width:22%;">Montant FCFA</th></tr>
    </thead>
    <tbody>
      <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
      <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
      <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
      <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
      <tr class="soustotal"><td colspan="3">Sous-total billets</td><td>&nbsp;</td></tr>
    </tbody>
  </table>

  <h3>Bagages payants</h3>
  <table class="lignes">
    <thead><tr><th style="width:70%;">Description</th><th style="width:30%;">Prix FCFA</th></tr></thead>
    <tbody>
      <tr><td>&nbsp;</td><td>&nbsp;</td></tr>
      <tr><td>&nbsp;</td><td>&nbsp;</td></tr>
      <tr class="soustotal"><td>Sous-total bagages</td><td>&nbsp;</td></tr>
    </tbody>
  </table>

  <h3>Récapitulatif</h3>
  <table class="recap">
    <tr><td class="label">Total billets + bagages</td><td class="valeur">&nbsp;</td></tr>
    <tr><td class="label">Dépenses (motif au dos si besoin)</td><td class="valeur">&nbsp;</td></tr>
    <tr><td class="label">Commission agent d'escale</td><td class="valeur">&nbsp;</td></tr>
    <tr class="total"><td class="label">Total versé</td><td class="valeur">&nbsp;</td></tr>
  </table>

  <div class="copies">
    <span class="case"></span> Copie compagnie (reste à l'escale) &nbsp;&nbsp;&nbsp;
    <span class="case"></span> Copie chauffeur / convoyeur
  </div>

  <table class="signatures">
    <tr>
      <td>
        <span class="role">Agent d'escale</span>
        <span class="trait">&nbsp;</span>
        Nom et signature
      </td>
      <td>
        <span class="role">Chauffeur ou convoyeur</span>
        <span class="trait">&nbsp;</span>
        Nom et signature
      </td>
    </tr>
  </table>

</body>
</html>
