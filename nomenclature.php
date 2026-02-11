<?php

function getNatureEntreprise($codeAPE) {
    if (!$codeAPE || $codeAPE == 'N/C') return 'Inconnue';
    
    // On récupère la première lettre ou les deux premiers chiffres
    $prefixe = substr($codeAPE, 0, 2);
    $lettre = strtoupper(substr($codeAPE, 0, 1));

    // Dictionnaire basé sur les sections officielles 
    $sections = [
        'A' => 'Agriculture, sylviculture et pêche',
        'B' => 'Industries extractives',
        'C' => 'Industrie manufacturière',
        'D' => 'Production et distribution d électricité, de gaz, de vapeur et d air conditionné',
        'E' => 'Production et distribution d eau ; assainissement, gestion des déchets et dépollution',
        'F' => 'Construction',
        'G' => 'Commerce ; réparation d automobiles et de motocycles',
        'H' => 'Transports et entreposage',
        'I' => 'Hébergement et restauration',
        'J' => 'Information et communication',
        'K' => 'Activités financières et d assurance',
        'L' => 'Activités immobilières',
        'M' => 'Activités spécialisées, scientifiques et techniques',
        'N' => 'Activités de services administratifs et de soutien',
        'O' => 'Administration publique',
        'P' => 'Enseignement',
        'Q' => 'Santé humaine et Action sociale',
        'R' => 'Arts, spectacles et activités récréatives',
        'S' => 'Autres activités de services',
        'T' => 'Activités des ménages en tant qu employeurs ; activités indifférenciées des ménages en tant que producteurs de biens et services pour usage propre',
        'U' => ' Activités extra-territoriales'
    ];

    
    $val = intval($prefixe);
    if ($val >= 1 && $val <= 3) return $sections['A'];
    if ($val >= 5 && $val <= 9) return $sections['B'];
    if ($val >= 10 && $val <= 33) return $sections['C'];
    if ($val >= 35 && $val <= 35) return $sections['D'];
    if ($val >= 36 && $val <= 39) return $sections['E'];
    if ($val >= 41 && $val <= 43) return $sections['F'];
    if ($val >= 45 && $val <= 47) return $sections['G'];
    if ($val >= 49 && $val <= 53) return $sections['H'];
    if ($val >= 55 && $val <= 56) return $sections['I'];
    if ($val >= 58 && $val <= 63) return $sections['J'];
    if ($val >= 64 && $val <= 66) return $sections['K'];
    if ($val >= 68 && $val <= 68) return $sections['L'];
    if ($val >= 69 && $val <= 75) return $sections['M'];
    if ($val >= 77 && $val <= 82) return $sections['N'];
    if ($val >= 84 && $val <= 84) return $sections['O'];
    if ($val >= 85 && $val <= 85) return $sections['P'];
    if ($val >= 86 && $val <= 88) return $sections['Q'];
    if ($val >= 90 && $val <= 93) return $sections['R'];
    if ($val >= 94 && $val <= 96) return $sections['S'];
    if ($val >= 97 && $val <= 98) return $sections['T'];
    if ($val >= 99 && $val <= 99) return $sections['U'];


    return $sections[$lettre] ?? 'Inconue';
}
