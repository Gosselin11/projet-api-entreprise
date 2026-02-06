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

    if ($nomActuel === 'Non diffusable') {
        $siret = $e['siret'];
        // Utilisation de l'API de recherche d'entreprises (gratuite et rapide)
        $url = "https://recherche-entreprises.api.gouv.fr/search?q=$siret";
        
        $res = @file_get_contents($url);
        if ($res) {
            $dataExt = json_decode($res, true);
            if (!empty($dataExt['results'][0]['nom_complet'])) {
                // On remplace le nom et on ajoute une petite marque pour savoir que c'est enrichi
                $e['uniteLegale']['denominationUniteLegale'] = "*" . $dataExt['results'][0]['nom_complet'];
                $modifie = true;
            }
        }
        // Petit délai pour ne pas brusquer l'API si tu as beaucoup de lignes
        usleep(100000); // 0.1 seconde
    }
}

if ($modifie) {
    file_put_contents($cacheFile, json_encode($cacheData));
}

// Retour à l'index avec un message de succès
header("Location: index.php?date_debut=$dateCible&enriched=1");
exit;
