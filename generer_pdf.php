<?php
// Gestion des ressources
ini_set('memory_limit', '512M');
set_time_limit(300);

require 'vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Récupération de la date cible
$dateCible = $_GET['date_debut'] ?? date('Y-m-d', strtotime('-1 month'));

// Définition du chemin du cache (doit être identique à index.php)
$cacheFolder = __DIR__ . DIRECTORY_SEPARATOR . 'cache';
$cacheFile = $cacheFolder . DIRECTORY_SEPARATOR . 'sirene_' . $_ENV['DEPARTEMENT'] . '_' . $dateCible . '.json';

$etablissements = [];

// Récupération des données depuis le cache
if (file_exists($cacheFile)) {
    $content = file_get_contents($cacheFile);
    $cacheData = json_decode($content, true);
    $etablissements = $cacheData['data'] ?? [];
} else {
    // Si l'utilisateur essaie de générer un PDF sans avoir vu la page index.php d'abord
    die("ERREUR : Les données pour la date $dateCible ne sont pas encore en cache. Veuillez d'abord afficher le rapport sur votre navigateur.");
}

$nbTotal = count($etablissements);

// Construction HTML 
$html = "<html><head>
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 9px; }
    header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #87a9ce; padding-bottom: 10px; }
    table { width:100%; border-collapse:collapse; }
    th { background-color: #eeeeee; color: black; padding: 5px; text-transform: uppercase; border: 1px solid #dddddd; }
    td { border: 1px solid #dddddd; padding: 4px; }
    .page-number:after { content: counter(page); }
    .footer { position: fixed; bottom: -30px; left: 0; right: 0; height: 30px; text-align: center; font-size: 8px; }
</style>
</head>
<body>
    <div class='footer'>Page <span class='page-number'></span> | Rapport SIRENE - Aude (11)</div>
    
    <header>
        <h1>Rapport intégral des créations d'entreprises</h1>
        <p>Département de l'Aude (11) - Période : <strong>$dateCible</strong></p>
        <p>Nombre total d'établissements : <strong>$nbTotal</strong></p>
    </header>

    <table>
        <thead>
            <tr>
                <th>Entreprise</th>
                <th>Ville</th>
                <th>CP</th>
                <th>SIRET</th>
                <th>Date Traitement</th>
                <th>Fiche</th>
                <th>Annuaire</th>
            </tr>
        </thead>
        <tbody>";

foreach ($etablissements as $e) {
    $nom = $e['uniteLegale']['denominationUniteLegale'] ?? 'Non diffusable';
    $ville = $e['adresseEtablissement']['libelleCommuneEtablissement'] ?? 'N/C';
    $cp = $e['adresseEtablissement']['codePostalEtablissement'] ?? 'N/C';
    $siret = $e['siret'];
    $date = isset($e['dateDernierTraitementEtablissement']) ? substr($e['dateDernierTraitementEtablissement'], 0, 10) : 'N/C';
    
    $lienFigaro = "https://entreprises.lefigaro.fr/recherche?q=$siret";
    $lienAnnuaire = "https://annuaire-entreprises.data.gouv.fr/entreprise/$siret";

    $html .= "<tr>
                <td><strong>$nom</strong></td>
                <td>$ville</td>
                <td>$cp</td>
                <td>$siret</td>
                <td>$date</td>
                <td><a href='$lienFigaro' style='color:blue; text-decoration:none;'>Figaro</a></td>
                <td><a href='$lienAnnuaire' style='color:blue; text-decoration:none;'>Annuaires</a></td>
              </tr>";
}

$html .= "</tbody></table></body></html>";

// Génération du PDF
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true); 

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');

$dompdf->render();

// Nom du fichier personnalisé avec la date cible
$filename = "Rapport_SIRENE_Aude_" . $dateCible . ".pdf";
$dompdf->stream($filename, ["Attachment" => true]);
