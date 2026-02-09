<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);
 // set_time_limit(0);

require 'vendor/autoload.php';
require 'src/SireneApi.php';
require 'src/Mailer.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();


$dateCible = $_GET['date_debut'] ?? date('Y-m-d', strtotime('-1 month'));
$emailSaisi = $_GET['destinataire'] ?? '';

// Détection du lancement par fichier .bat
if (php_sapi_name() === 'cli') {
    $_GET['action'] = 'send';
    $emailSaisi = $_ENV['SMTP_USER']; 
}

$api = new SireneApi($_ENV['INSEE_API_KEY'], $_ENV['DEPARTEMENT']);

$etablissements = [];
$curseur = '*';
$nbTotalInsee = 0; 


// Configuration du cache

$cacheFolder = __DIR__ . DIRECTORY_SEPARATOR . 'cache';

// Si le dossier n'existe pas, on le crée
if (!is_dir($cacheFolder)) {
    mkdir($cacheFolder, 0777, true);
}

$cacheFile = $cacheFolder . DIRECTORY_SEPARATOR . 'sirene_' . $_ENV['DEPARTEMENT'] . '_' . $dateCible . '.json';
$cacheExpiration = 86400; // 24 heures

$etablissements = [];
$nbTotalInsee = 0;
$statusMessage = ""; // Initialisation


// On vérifie si un cache valide existe
if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheExpiration)) {
    $content = file_get_contents($cacheFile);
    if ($content) {
        $cacheData = json_decode($content, true);
        $etablissements = $cacheData['data'] ?? [];
        $nbTotalInsee = $cacheData['total'] ?? 0;
        $statusMessage = "Données chargées depuis le cache local.";
    }
} 

else {
    // Si pas de cache : On appelle l'API
    $curseur = '*';
    try {
        do {
            $data = $api->fetchEntreprises($dateCible, $curseur);
            
            if ($curseur === '*') {
                $nbTotalInsee = $data['header']['total'] ?? 0;
            }

            if (isset($data['etablissements'])) {
                foreach ($data['etablissements'] as $e) {
                    $etablissements[] = $e;
                }
            }
            $curseur = $data['header']['curseurSuivant'] ?? null;
        } while ($curseur && $curseur !== '*');

        // On sauvegarde dans le cache si on a récupéré des données
        if (!empty($etablissements)) {
            file_put_contents($cacheFile, json_encode([
                'total' => $nbTotalInsee,
                'data' => $etablissements
            ]));
        }
        $isFromCache = false;

    } catch (Exception $e) {

        // Si l'API plante et qu'un vieux cache existe, on l'utilise quand même en secours
        if (file_exists($cacheFile)) {
            $cacheData = json_decode(file_get_contents($cacheFile), true);
            $etablissements = $cacheData['data'];
            $nbTotalInsee = $cacheData['total'];
            $isFromCache = "Secours (L'API ne répond pas)";
        }
    }
}


// Formulaire
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Rapport INSEE</title>
</head>
<body style="font-family: Arial, sans-serif; margin: 20px;">

    <h2>Recherche d'entreprises (Aude)</h2>
    
        <form method="GET" style="margin-bottom: 20px; padding: 15px; background: #f4f4f4; border-radius: 5px; display: flex; gap: 15px; align-items: center;">
        <div>
            <label>Date : </label>
            <input type="date" name="date_debut" value="<?php echo $dateCible; ?>">
        </div>
        
        <button type="submit">Actualiser la liste</button>
    </form>

    <?php if ($statusMessage) echo "<p style='color: gray; font-style: italic;'>$statusMessage</p>"; ?>

<?php
if (!empty($etablissements)) {
    $nbTotal = $nbTotalInsee; 
    ?>

    <div style="margin-bottom:20px; display: flex; gap: 15px; align-items: center; background: #ebf3f0; padding: 15px; border-radius: 8px;">
        
        <div style="display: flex; align-items: center; gap: 10px;">
            <label style="font-weight: bold;">Envoyer à :</label>
            <input type="email" id="email_destination" 
                   value="<?php echo htmlspecialchars($emailSaisi); ?>" 
                   placeholder="exemple@mail.com" 
                   style="padding: 10px; border: 1px solid #ccc; border-radius: 5px; width: 250px;">
            
            <a href="#" 
               onclick="let mail = document.getElementById('email_destination').value; 
            if(mail == '') { alert('Veuillez saisir une adresse mail.'); return false; }
this.href='?action=send&date_debut=<?php echo $dateCible; ?>&destinataire=' + encodeURIComponent(document.getElementById('email_destination').value);"
               style="padding: 10px 20px; background-color: #28a745; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;">
               Envoyer Mail
            </a>
        </div>

        <span style="color: #ccc;">|</span>

        <a href="generer_pdf.php?date_debut=<?php echo $dateCible; ?>" target="_blank" 
           style="padding: 10px 20px; background-color: #dc3545; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;">
           Télécharger PDF
        </a>
        <a href="annuaireApi.php?date_debut=<?php echo $dateCible; ?>" 
       style="margin-left: auto; padding: 10px 20px; background-color: #17a2b8; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;"
       onclick="return confirm('Enrichir les noms peut prendre quelques secondes. Continuer ?');">
       Enrichir les données
    </a>

    </div>

    <?php

    echo "<h2>Rapport du $dateCible ($nbTotal résultats officiels)</h2>";
    echo "<table border='1' style='border-collapse:collapse; width:100%; text-align:left;'>
            <tr style='background:#f2f2f2;'>
                <th>Nom</th><th>Commune</th><th>CP</th><th>SIRET</th><th>Mis en ligne</th><th>Fiche</th><th>Annuaire</th>
            </tr>";
    
    $lignesMail = "";
    foreach($etablissements as $index => $e) {
        $nom = $e['uniteLegale']['denominationUniteLegale'] ?? 'Non diffusable';
        $siret = $e['siret'];
        $ville = $e['adresseEtablissement']['libelleCommuneEtablissement'] ?? 'N/C';
        $cp = $e['adresseEtablissement']['codePostalEtablissement'] ?? 'N/C';
        $dateTraitement = isset($e['dateDernierTraitementEtablissement']) ? substr($e['dateDernierTraitementEtablissement'], 0, 10) : 'N/C';
        
        $lienFigaro = "https://entreprises.lefigaro.fr/recherche?q=$siret";
        $lienAnnuaire = "https://annuaire-entreprises.data.gouv.fr/entreprise/$nom-$siret" . urlencode($siret);
        $lienDetail = "http://localhost/projet-api-entreprise/index.php?date_debut=" . $dateCible;
        echo "<tr>
                <td style='padding: 8px;'>$nom</td>
                <td style='padding: 8px;'>$ville</td>
                <td style='padding: 8px;'>$cp</td>
                <td style='padding: 8px;'>$siret</td>
                <td style='padding: 8px;'>$dateTraitement</td>
                <td style='padding: 8px;'><a href='$lienFigaro' target='_blank'>Figaro</a></td>
                <td style='padding: 8px;'><a href='$lienAnnuaire' target='_blank'>Annuaire</a></td>
              </tr>";
        
        if ($index < 20) {
            $lignesMail .= "<tr>
                <td style='border: 1px solid #dddddd; padding: 8px;'><strong>$nom</strong></td>
                <td style='border: 1px solid #dddddd; padding: 8px;'>$ville</td>
                <td style='border: 1px solid #dddddd; padding: 8px;'>$siret</td>
                <td style='border: 1px solid #dddddd; padding: 8px;'><a href='$lienFigaro'>Figaro</a></td>
                <td style='border: 1px solid #dddddd; padding: 8px;'><a href='$lienAnnuaire'>Annuaire</a></td>
            </tr>";
        }
    }
    echo "</table>";

    // Mail
    if (isset($_GET['action']) && $_GET['action'] === 'send') {
        $corpsMail = "<html><body style='font-family: Arial;'>
            <h3>Bonjour, voici les entreprises du $dateCible ($nbTotal au total).</h3>
            <table style='border-collapse: collapse; width: 100%; border: 1px solid #dddddd;'>
                <tr style='background-color: #eeeeee;'>
                    <th>Entreprise</th><th>Ville</th><th>SIRET</th><th>Fiche</th><th>Annuaire</th>
                </tr>
                $lignesMail
            </table>

            <p style='margin-top: 20px;'>
        <a href='$lienDetail' 
           style='display: inline-block; padding: 12px 25px; background-color: #007bff; color: #ffffff; text-decoration: none; border-radius: 5px; font-weight: bold;'>
           Voir le détail complet au $dateCible
        </a>
    </p>;

            
            </body></html>";


        $mailer = new Mailer([
            'user' => $_ENV['SMTP_USER'],
            'pass' => $_ENV['SMTP_PASS'],
            'dest' => $emailSaisi
        ]);
        
        
    }
} 
if (isset($_GET['sent']) && $_GET['sent'] == 1) {
    echo "<p style='color:green; font-weight:bold;'>Mail envoyé avec succès à : $emailSaisi</p>";
}


?>
</body>
</html>

