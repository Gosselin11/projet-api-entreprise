<?php
require 'vendor/autoload.php';
require 'src/SireneApi.php';
require 'src/Mailer.php';

// Chargement de la config sécurisée
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Initialisation des outils
$api = new SireneApi($_ENV['INSEE_API_KEY'], $_ENV['DEPARTEMENT']);
$dateCible = date('Y-m-d', strtotime('-1 month'));

// Récupération des données
$etablissements = [];
$curseur = '*';
do {
    $data = $api->fetchEntreprises($dateCible, $curseur);
    if (isset($data['etablissements'])) {
        $etablissements = array_merge($etablissements, $data['etablissements']);
    }
    $curseur = $data['header']['curseurSuivant'] ?? null;
} while ($curseur && $curseur !== '*');

// Affichage et préparation du mail
if (!empty($etablissements)) {
    $nbTotal = count($etablissements);
    
    // Bouton manuel
   echo "<div style='margin-bottom:20px; display: flex; gap: 10px; font-family: Arial;'>
        <a href='?action=send' style='padding: 10px 20px; background-color: #28a745; color: white; text-decoration: none; border-radius: 5px;'>
            Envoyer par mail
        </a>
        
        <a href='generer_pdf.php' target='_blank' style='padding: 10px 20px; background-color: #dc3545; color: white; text-decoration: none; border-radius: 5px;'>
            Télécharger PDF (Totalité)
        </a>
      </div>";
    
    // Tableau Navigateur
    echo "<h2>Rapport du $dateCible ($nbTotal résultats)</h2>";
    echo "<table border='1' style='border-collapse:collapse; width:100%; text-align:left;'>
            <tr style='padding: 8px; text-align: left; background:#f2f2f2;'>
                <th >Nom</th><th>Commune</th><th>CP</th><th>SIRET</th><th>Mis en ligne</th><th>Fiche</th>
            </tr>";
    
    $lignesMail = "";
    foreach($etablissements as $index => $e) {
        // Extraction des données
        $nom = $e['uniteLegale']['denominationUniteLegale'] ?? 'Non diffusable';
        $siret = $e['siret'];
        $ville = $e['adresseEtablissement']['libelleCommuneEtablissement'] ?? 'N/C';
        $cp = $e['adresseEtablissement']['codePostalEtablissement'] ?? 'N/C';
        $dateTraitement = isset($e['dateDernierTraitementEtablissement']) ? substr($e['dateDernierTraitementEtablissement'], 0, 10) : 'N/C';
        $lienFigaro = "https://entreprises.lefigaro.fr/recherche?q=$siret";

        // Affichage Navigateur
        echo "<tr>
                <td style='border: 1px solid #dddddd; padding: 8px; text-align: left;'>$nom</td>
                <td style='border: 1px solid #dddddd; padding: 8px; text-align: left;'>$ville</td>
                <td style='border: 1px solid #dddddd; padding: 8px; text-align: left;'>$cp</td>
                <td style='border: 1px solid #dddddd; padding: 8px; text-align: left;'>$siret</td>
                <td style='border: 1px solid #dddddd; padding: 8px; text-align: left;'>$dateTraitement</td>
                <td style='border: 1px solid #dddddd; padding: 8px; text-align: left;'><a href='$lienFigaro' target='_blank' style='color:blue; text-decoration:none;'>Figaro</a></td>
              </tr>";
        
        // Préparation du contenu pour le mail
        if ($index < 20) {
            $lignesMail .= "<tr>
                                <td style='border: 1px solid #dddddd; padding: 8px;'><strong>$nom</strong></td>
                                <td style='border: 1px solid #dddddd; padding: 8px;'>$ville</td>
                                <td style='border: 1px solid #dddddd; padding: 8px;'>$siret</td>
                                <td style='border: 1px solid #dddddd; padding: 8px;'><a href='$lienFigaro'>Figaro</a></td>
                            </tr>";
        }
    }
    echo "</table>";

    // Envoi du mail
    if (isset($_GET['action']) && $_GET['action'] === 'send' || php_sapi_name() === 'cli') {
        
        // Construction du tableau dans le mail
        $corpsMail = "
        <html>
        <body style='font-family: Arial, sans-serif;'>
            <h3>Bonjour, voici les entreprises du $dateCible ($nbTotal au total).</h3>
            <table style='border-collapse: collapse; width: 100%; border: 1px solid #dddddd;'>
                <tr style='background-color: #eeeeee;'>
                    <th style='border: 1px solid #dddddd; padding: 8px; text-align: left;'>Entreprise</th>
                    <th style='border: 1px solid #dddddd; padding: 8px; text-align: left;'>Ville</th>
                    <th style='border: 1px solid #dddddd; padding: 8px; text-align: left;'>SIRET</th>
                    <th style='border: 1px solid #dddddd; padding: 8px; text-align: left;'>Fiche</th>
                </tr>
                $lignesMail
            </table>";
        
        if ($nbTotal > 0) {
            $corpsMail .= "<p>
                    <a href='http://localhost/projet-api-entreprise/index.php' 
                       style='display: inline-block; padding: 10px 20px; background-color: #007bff; color: #ffffff; text-decoration: none; border-radius: 5px; font-weight: bold;'>
                       Voir détail</a>
                </p>";
        }
        $corpsMail .= "</body></html>";

        $mailer = new Mailer([
            'user' => $_ENV['SMTP_USER'],
            'pass' => $_ENV['SMTP_PASS'],
            'dest' => $_ENV['DESTINATAIRE_MAIL']
        ]);
        
        if ($mailer->sendReport($dateCible, $nbTotal, $corpsMail)) {
            echo "<p style='color:green;'>Mail envoyé avec succès !</p>";
        } else {
            echo "<p style='color:red;'>Erreur lors de l'envoi.</p>";
        }
    }
} else {
    echo "<h3>Aucune entreprise trouvée.</h3>";
}