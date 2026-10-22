<?php

/**
 * Génère un billet en commandes ESC/POS brutes pour imprimante thermique 80mm,
 * et l'envoie soit en réseau (TCP/IP, port 9100), soit en USB local, selon
 * PRINTER_MODE (.env). La génération du contenu est indépendante du mode d'envoi.
 */
class ThermalPrinter
{
    private const LARGEUR_COLONNES = 32; // ~32 caractères par ligne en police normale sur 80mm

    // --- Génération du contenu (indépendante du mode d'envoi) ---

    public static function buildBilletContent(array $billet): string
    {
        $ESC = "\x1B";
        $GS  = "\x1D";
        $sep = str_repeat('-', self::LARGEUR_COLONNES) . "\n";

        $r = $ESC . "@"; // init imprimante

        // En-tête centré : logo (comme sur le PDF cable/USB) puis nom/slogan de la compagnie.
        $r .= $ESC . "a" . "\x01";
        $r .= self::imageRaster($billet['logo'] ?? null);
        $r .= $ESC . "!" . "\x30";
        $r .= self::clean($billet['compagnie'] ?? '') . "\n";
        $r .= $ESC . "!" . "\x00";
        if (!empty($billet['slogan'])) {
            $r .= self::clean($billet['slogan']) . "\n";
        }
        $r .= $sep;
        $r .= $ESC . "!" . "\x10";
        $r .= "BILLET DE VOYAGE\n";
        $r .= "N " . self::clean($billet['numero'] ?? '-') . "\n";
        $r .= $ESC . "!" . "\x00";
        $r .= $sep;

        // Détails : libellé à gauche, valeur alignée à droite (comme le tableau du PDF
        // cable/USB), une ligne par champ — les mêmes six champs, dans le même ordre.
        $r .= $ESC . "a" . "\x00";
        $r .= self::champ("Client", $billet['client'] ?? '-');
        $r .= self::champ("Date", $billet['date'] ?? '-');
        $r .= self::champ("Depart", $billet['depart'] ?? '-');
        $r .= self::champ("Heure", $billet['heure'] ?? '-');
        $r .= self::champ("Destination", $billet['destination'] ?? '-');
        $r .= self::champ("Place(s)", $billet['places'] ?? '-');
        $r .= $sep;

        // Bagages payants (optionnel, cf. app/models/Bagage.php) : une ligne par bagage,
        // comme sur le PDF cable/USB.
        if (!empty($billet['bagages'])) {
            foreach ($billet['bagages'] as $bagage) {
                $libelle = !empty($bagage['description']) ? "Bagage (" . $bagage['description'] . ")" : "Bagage";
                $r .= self::champ($libelle, ($bagage['prix'] ?? '-') . " FCFA");
            }
            $r .= $sep;
        }

        // Montant en gras/agrandi
        $r .= $ESC . "!" . "\x10";
        $r .= "MONTANT PAYE   " . self::clean($billet['montant'] ?? '-') . " FCFA\n";
        $r .= $ESC . "!" . "\x00";
        if (!empty($billet['totalBagages'])) {
            $r .= self::champ("Bagages", $billet['totalBagages'] . " FCFA");
        }
        $r .= $sep;

        // Mentions légales
        $r .= "Merci d'etre a la gare 45 minutes\n";
        $r .= "avant l'heure de depart.\n";
        $r .= "Ce billet est valable 1 semaine\n";
        $r .= "apres sa date d'emission.\n";
        $r .= $sep;

        // Pied de page centré
        $r .= $ESC . "a" . "\x01";
        $r .= "Emis par " . self::clean($billet['emisPar'] ?? '-') . "\n";
        $r .= "Merci d'avoir choisi " . self::clean($billet['compagnie'] ?? '') . "\n";
        $r .= "\n\n\n";
        $r .= $GS . "V" . "\x00"; // coupe papier

        return $r;
    }

    public static function buildColisContent(array $colis): string
    {
        $ESC = "\x1B";
        $GS  = "\x1D";
        $sep = str_repeat('-', self::LARGEUR_COLONNES) . "\n";

        $r = $ESC . "@"; // init imprimante

        // En-tête centré : logo (comme sur le PDF cable/USB) puis nom/slogan de la compagnie.
        $r .= $ESC . "a" . "\x01";
        $r .= self::imageRaster($colis['logo'] ?? null);
        $r .= $ESC . "!" . "\x30";
        $r .= self::clean($colis['compagnie'] ?? '') . "\n";
        $r .= $ESC . "!" . "\x00";
        if (!empty($colis['slogan'])) {
            $r .= self::clean($colis['slogan']) . "\n";
        }
        $r .= $sep;

        // Reprend exactement les mêmes blocs, dans le même ordre, que le reçu PDF
        // cable/USB (cf. app/views/admin/pdf/recu_colis.php) : Colis, Expéditeur,
        // Destinataire, Trajet, puis le QR code et le code rappelé en dessous.
        $r .= $ESC . "a" . "\x00";
        $r .= $ESC . "!" . "\x10" . "COLIS\n" . $ESC . "!" . "\x00";
        $r .= self::champ("Nom", $colis['nom'] ?? '-');
        $r .= self::champ("Nature", $colis['nature'] ?? '-');
        $r .= $sep;

        $r .= $ESC . "!" . "\x10" . "EXPEDITEUR\n" . $ESC . "!" . "\x00";
        $r .= self::champ("Nom", $colis['expediteur'] ?? '-');
        $r .= self::champ("Tel", $colis['numeroExp'] ?? '-');
        $r .= $sep;

        $r .= $ESC . "!" . "\x10" . "DESTINATAIRE\n" . $ESC . "!" . "\x00";
        $r .= self::champ("Nom", $colis['destinataire'] ?? '-');
        $r .= self::champ("Tel", $colis['numeroDest'] ?? '-');
        $r .= $sep;

        $r .= $ESC . "!" . "\x10" . "TRAJET\n" . $ESC . "!" . "\x00";
        $r .= self::champ("Depart", $colis['depart'] ?? '-');
        $r .= self::champ("Destination", $colis['destination'] ?? '-');
        $r .= $sep;

        // QR code (vérification/suivi) : encode les mêmes informations que celui du PDF
        // (pas seulement le code), centré, avec le code du colis rappelé en dessous.
        $r .= $ESC . "a" . "\x01";
        $qrData = "Nom du colis : " . self::clean($colis['nom'] ?? '-') . "\n"
            . "Nature       : " . self::clean($colis['nature'] ?? '-') . "\n"
            . "Code         : " . self::clean($colis['code'] ?? '-') . "\n"
            . "Depart       : " . self::clean($colis['depart'] ?? '-') . "\n"
            . "Destination  : " . self::clean($colis['destination'] ?? '-') . "\n"
            . "Enregistre par : " . self::clean($colis['agent'] ?? '-') . "\n"
            . "Valeur       : " . self::clean($colis['valeur'] ?? '-') . " FCFA\n"
            . "Frais        : " . self::clean($colis['frais'] ?? '-') . " FCFA";
        $r .= self::qrCode($qrData);
        $r .= "Code : " . self::clean($colis['code'] ?? '-') . "\n";
        $r .= $sep;

        $r .= "Enregistre par " . self::clean($colis['agent'] ?? '-') . "\n";
        $r .= "Merci d'avoir choisi " . self::clean($colis['compagnie'] ?? '') . "\n";
        $r .= "\n\n\n";
        $r .= $GS . "V" . "\x00"; // coupe papier

        return $r;
    }

    // Commande ESC/POS standard (GS ( k) pour imprimer un QR code, supportée par la
    // quasi-totalité des imprimantes thermiques modernes (Epson TM et clones compatibles).
    private static function qrCode(string $data): string
    {
        $prefix = "\x1D" . "(k";
        $len = strlen($data) + 3;

        $r = '';
        $r .= $prefix . chr(4) . chr(0) . chr(0x31) . chr(0x41) . chr(50) . chr(0); // modèle 2
        $r .= $prefix . chr(3) . chr(0) . chr(0x31) . chr(0x43) . chr(6);            // taille module
        $r .= $prefix . chr(3) . chr(0) . chr(0x31) . chr(0x45) . chr(49);           // correction erreur (M)
        $r .= $prefix . chr($len % 256) . chr(intdiv($len, 256)) . chr(0x31) . chr(0x50) . chr(0x30) . $data; // stocke
        $r .= $prefix . chr(3) . chr(0) . chr(0x31) . chr(0x51) . chr(0x30) . "\n"; // imprime

        return $r;
    }

    // La plupart des imprimantes thermiques génériques (jeu de caractères par défaut)
    // n'affichent pas correctement les accents UTF-8 : on les retire par sécurité.
    private static function clean(string $texte): string
    {
        $sansAccents = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texte);
        return $sansAccents !== false ? $sansAccents : $texte;
    }

    // Une ligne "libelle + valeur alignee a droite" sur la largeur du papier, pour
    // reproduire la mise en page en colonnes du ticket PDF (cable/USB) : le libelle
    // reste a gauche, la valeur colle au bord droit au lieu d'etre juste accolee au
    // libelle.
    private static function champ(string $label, string $valeur): string
    {
        $valeur = self::clean($valeur);
        $largeurRestante = max(1, self::LARGEUR_COLONNES - strlen($label));
        return $label . sprintf("%{$largeurRestante}s", $valeur) . "\n";
    }

    // Imprime une image (logo de la compagnie) en mode matriciel (commande ESC/POS
    // GS v 0), pour que le ticket WiFi affiche le meme logo que le PDF cable/USB (qui,
    // lui, embarque directement le fichier image dans le PDF). Le pont ne stocke aucune
    // image lui-meme : c'est le site qui l'envoie en base64 dans les donnees du ticket
    // (cf. Liste_du_jours::donneesTicketThermique()), puisque ce pont tourne sur le poste
    // du comptoir et n'a pas acces aux fichiers du site.
    private static function imageRaster(?string $base64): string
    {
        if (empty($base64) || !function_exists('imagecreatefromstring')) {
            return '';
        }

        $binaire = base64_decode($base64, true);
        if ($binaire === false) {
            return '';
        }

        $source = @imagecreatefromstring($binaire);
        if ($source === false) {
            return '';
        }

        // 384 points ~ 48mm a 203dpi : tient dans la zone imprimable des papiers 58mm et
        // 80mm les plus courants, sans avoir besoin de connaitre le modele exact.
        $largeurCible = 384;
        $largeurSource = imagesx($source);
        $hauteurSource = imagesy($source);
        if ($largeurSource <= 0 || $hauteurSource <= 0) {
            imagedestroy($source);
            return '';
        }
        $hauteurCible = (int) round($hauteurSource * ($largeurCible / $largeurSource));

        $redim = imagecreatetruecolor($largeurCible, $hauteurCible);
        imagefill($redim, 0, 0, imagecolorallocate($redim, 255, 255, 255));
        imagecopyresampled($redim, $source, 0, 0, 0, 0, $largeurCible, $hauteurCible, $largeurSource, $hauteurSource);
        imagedestroy($source);

        // Conversion en bitmap 1 bit/pixel (noir/blanc), format attendu par GS v 0 :
        // chaque octet code 8 pixels horizontaux, bit a 1 = point noir imprime.
        $largeurOctets = (int) ceil($largeurCible / 8);
        $bitmap = '';
        for ($y = 0; $y < $hauteurCible; $y++) {
            for ($xOctet = 0; $xOctet < $largeurOctets; $xOctet++) {
                $octet = 0;
                for ($bit = 0; $bit < 8; $bit++) {
                    $x = $xOctet * 8 + $bit;
                    $noir = 0;
                    if ($x < $largeurCible) {
                        $couleurs = imagecolorsforindex($redim, imagecolorat($redim, $x, $y));
                        $gris = ($couleurs['red'] + $couleurs['green'] + $couleurs['blue']) / 3;
                        $noir = $gris < 128 ? 1 : 0;
                    }
                    $octet = ($octet << 1) | $noir;
                }
                $bitmap .= chr($octet);
            }
        }
        imagedestroy($redim);

        $xL = $largeurOctets % 256;
        $xH = intdiv($largeurOctets, 256);
        $yL = $hauteurCible % 256;
        $yH = intdiv($hauteurCible, 256);

        return "\x1D" . "v0" . chr(0) . chr($xL) . chr($xH) . chr($yL) . chr($yH) . $bitmap . "\n";
    }

    // --- Envoi réseau : imprimante avec IP fixe, écoute TCP/IP (port 9100 en général) ---

    public static function sendToNetworkPrinter(string $ip, int $port, string $data): array
    {
        $socket = @fsockopen($ip, $port, $errno, $errstr, 5);
        if (!$socket) {
            return ['success' => false, 'message' => "Imprimante réseau injoignable ($ip:$port) : $errstr ($errno)"];
        }

        fwrite($socket, $data);
        fclose($socket);
        return ['success' => true, 'message' => 'Ticket envoyé à l\'imprimante réseau.'];
    }

    // --- Envoi USB : imprimante branchée en local sur le poste qui imprime ---
    //
    // Windows : l'imprimante doit être installée dans le Panneau de configuration (pilote
    // "Generic / Text Only" ou pilote constructeur), sous le nom exact donné dans
    // PRINTER_USB_NAME ; le spouleur Windows relaie ensuite les octets bruts au périphérique.
    // Linux : écriture directe sur le périphérique bloc de l'imprimante (ex: /dev/usb/lp0).

    public static function sendToUsbPrinter(string $data, ?string $devicePath = null): array
    {
        $devicePath = $devicePath ?: (defined('PRINTER_USB_NAME') ? PRINTER_USB_NAME : 'POS-80');

        if (stripos(PHP_OS, 'WIN') === 0) {
            $tmpFile = tempnam(sys_get_temp_dir(), 'ticket_') . '.prn';
            file_put_contents($tmpFile, $data);

            $commande = 'COPY /B ' . escapeshellarg($tmpFile) . ' ' . escapeshellarg('\\\\.\\' . $devicePath);
            exec($commande, $sortie, $code);
            @unlink($tmpFile);

            if ($code !== 0) {
                return ['success' => false, 'message' => "Imprimante USB \"$devicePath\" non détectée ou inaccessible."];
            }
            return ['success' => true, 'message' => 'Ticket envoyé à l\'imprimante USB.'];
        }

        // Linux : $devicePath est ici un chemin de périphérique (ex: /dev/usb/lp0)
        if (@file_put_contents($devicePath, $data) === false) {
            return ['success' => false, 'message' => "Imprimante USB \"$devicePath\" non détectée ou inaccessible."];
        }
        return ['success' => true, 'message' => 'Ticket envoyé à l\'imprimante USB.'];
    }

    // --- Point d'entrée unique : lit PRINTER_MODE (.env) et route vers le bon envoi ---

    public static function printBillet(array $billet): array
    {
        return self::envoyer(self::buildBilletContent($billet));
    }

    public static function printColis(array $colis): array
    {
        return self::envoyer(self::buildColisContent($colis));
    }

    private static function envoyer(string $data): array
    {
        $mode = strtolower(defined('PRINTER_MODE') ? PRINTER_MODE : 'network');

        if ($mode === 'usb') {
            return self::sendToUsbPrinter($data);
        }

        $ip = defined('PRINTER_IP') ? PRINTER_IP : '192.168.1.100';
        $port = (int) (defined('PRINTER_PORT') ? PRINTER_PORT : 9100);
        return self::sendToNetworkPrinter($ip, $port, $data);
    }
}
