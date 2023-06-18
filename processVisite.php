<?php

// Enregistrer une visite programmée
include "MonProjet.php";
include "Helpers.php";


$data = [
    "id_camp" => rawurldecode($_REQUEST["id_camp"]),
    "identite" => rawurldecode($_REQUEST["identite"]),
    "date" => Helpers::dateFormatDmy(rawurldecode($_REQUEST["date"])),
    "infos" => rawurldecode($_REQUEST["infos"]),
];

return file_put_contents(__DIR__ . "/data/" . MonProjet::fichierVisite, json_encode($data) . PHP_EOL, FILE_APPEND);
