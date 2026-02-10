<?php
require 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$dateCible = $_GET['date_debut'] ?? '';
$cacheFolder = __DIR__ . DIRECTORY_SEPARATOR . 'cache';
$cacheFile = $cacheFolder . DIRECTORY_SEPARATOR . 'sirene_' . $_ENV['DEPARTEMENT'] . '_' . $dateCible . '.json';

if (!$dateCible || !file_exists($cacheFile)) {
    die("Fichier cache introuvable pour cette date.");
}

$cacheData = json_decode(file_get_contents($cacheFile), true);
$modifie = false;

foreach ($cacheData['data'] as &$e) {
    $nomActuel = $e['uniteLegale']['denominationUniteLegale'] ?? 'Non diffusable';

    // On enrichit si le nom est masqué OU si le libellé est absent
    if ($nomActuel === 'Non diffusable' || !isset($e['uniteLegale']['libelleActivitePrincipale'])) {
        $siret = $e['siret'];
        $url = "https://recherche-entreprises.api.gouv.fr/search?q=$siret";
        $res = @file_get_contents($url);
        
        if ($res) {
            $dataExt = json_decode($res, true);
            if (!empty($dataExt['results'][0])) {
                $info = $dataExt['results'][0];

                // On remplace le nom
                $e['uniteLegale']['denominationUniteLegale'] = "*" . ($info['nom_complet'] ?? 'Nom inconnu');

                // On ajoute le libellé de l'activité (Dénomination NAF)
                $e['uniteLegale']['libelleActivitePrincipale'] = $info['libelle_activite_principale'] ?? 'N/C';

                $e['uniteLegale']['section_activite_principale'] = $info['section_activite_principale'] ?? 'N/C';
                
                $modifie = true;
            }
        }
        usleep(100000); // 0.1 seconde
    }
}

if ($modifie) {
    file_put_contents($cacheFile, json_encode($cacheData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

header("Location: index.php?date_debut=$dateCible&enriched=1");
exit;
