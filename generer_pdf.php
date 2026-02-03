<?php
// Gestion des ressource pour supprimer les limites
ini_set('memory_limit', '512M');
set_time_limit(300);

require 'vendor/autoload.php';
require 'src/SireneApi.php';
use Dompdf\Dompdf;
use Dompdf\Options;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$api = new SireneApi($_ENV['INSEE_API_KEY'], $_ENV['DEPARTEMENT']);
$dateCible = date('Y-m-d', strtotime('-1 month'));

// Récuperation de toutes les données
$etablissements = [];
$curseur = '*';
do {
    $data = $api->fetchEntreprises($dateCible, $curseur);
    if (isset($data['etablissements'])) {
        $etablissements = array_merge($etablissements, $data['etablissements']);
    }
    // On récupère le curseur pour la page suivante
    $curseur = $data['header']['curseurSuivant'] ?? null;
} while ($curseur && $curseur !== '*');

$nbTotal = count($etablissements);

// Construction html pour le pdf
$html = "
<html>
<head>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; }
        header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #87a9ce; padding-bottom: 10px; }
        table { border='1' style='border-collapse:collapse; width:100%; text-align:left;' }
        th { background-color: #eeeeee; color: black; padding: 5px; text-transform: uppercase; }
        td { border-bottom: 1px solid #dddddd; padding: 4px; }
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
                <th style='border: 1px solid #dddddd; padding: 8px; text-align: left;'>Entreprise</th>
                <th style='border: 1px solid #dddddd; padding: 8px; text-align: left;'>Ville</th>
                <th style='border: 1px solid #dddddd; padding: 8px; text-align: left;'>Code Postal</th>
                <th style='border: 1px solid #dddddd; padding: 8px; text-align: left;'>SIRET</th>
                <th style='border: 1px solid #dddddd; padding: 8px; text-align: left;'>Date Traitement</th>
                <th style='border: 1px solid #dddddd; padding: 8px; text-align: left;'>Fiche</th>
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
    
    $html .= "<tr>
                <td><strong>$nom</strong></td>
                <td>$ville</td>
                <td>$cp</td>
                <td><code>$siret</code></td>
                <td>$date</td>
                <td style='border: 1px solid #dddddd; padding: 8px; text-align: left;'><a href='$lienFigaro' target='_blank' style='color:blue; text-decoration:none;'>Figaro</a></td>
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

// Forcer le téléchargement avec un nom de fichier clair
$filename = "Rapport_Integral_Aude_" . date('Y-m-d') . ".pdf";
$dompdf->stream($filename, ["Attachment" => true]);