<?php

// Enregistrer une visite programmée
include "MonProjet.php";


$data = [
    "id_camp" => rawurldecode($_REQUEST["id_camp"]),
    "identite" => rawurldecode($_REQUEST["identite"]),
    "data" => rawurldecode($_REQUEST["data"]),
];

return file_put_contents(__DIR__ . "/data/" . MonProjet::fichierVisite, json_encode($data) . PHP_EOL, FILE_APPEND);
