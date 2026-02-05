<?php
class SireneApi {
    private $apiKey;
    private $dept;

    public function __construct($apiKey, $dept) {
        $this->apiKey = $apiKey;
        $this->dept = $dept;
    }

    public function fetchEntreprises($date, $curseur = '*') {
        $filtre = "codePostalEtablissement:{$this->dept}*";
        $queryParams = [
            'q' => $filtre . " AND (dateCreationEtablissement:$date OR dateDernierTraitementEtablissement:$date*)",
            'nombre' => 1000,
            'curseur' => $curseur,
            'tri' => 'libelleCommuneEtablissement',
        ];

        $url = "https://api.insee.fr/api-sirene/3.11/siret?" . http_build_query($queryParams);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "X-INSEE-Api-Key-Integration: " . $this->apiKey,
            "Accept: application/json"
        ]);
        $response = curl_exec($ch);
        return json_decode($response, true);
    }

}