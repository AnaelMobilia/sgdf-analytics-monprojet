<?php
// Transformer un fichier de formation d'Intranet en un fichier plat

/**
 * Chargement des classes de PhpSpreadsheet
 */
spl_autoload_register(function ($class) {
    // Mettre en minuscule les deux premiers répertoires + ajouter src + passer en chemin linux
    [$vendor, $project, $namespace] = explode("\\", $class, 3);
    // Cas particulier pour SimpleCache qui ne respecte pas le formattage normal
    if (strpos($project, "SimpleCache") !== false) {
        $project = "simple-cache";
        $subProject = "";
    } else {
        $subProject = $project . "/";
    }

    $path = strtolower($vendor) . "/" . strtolower($project) . "/src/" . $subProject . str_replace("\\", "/", $namespace);
    $file = __DIR__ . '/libs/' . $path . '.php';

    // Si le fichier existe...
    if (file_exists($file)) {
        require $file;
    }
});

/**
 * Chargement du fichier de données
 */
$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($_FILES["upload"]["tmp_name"]);
// Feuille 1
$worksheet = $spreadsheet->getActiveSheet();// Colonnes à identifier
$tabCols = ["Individu.CodeAdherent" => "", "FormationsType.Libelle" => "", "Formations.DateFin" => "",];

// Nombre de colonnes
$highestColumn = $worksheet->getHighestColumn(); // e.g 'F'
$highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn); // e.g. 5

/**
 * Trouver les clefs de colonnes
 */
// Itérer sur les colonnes pour trouver la position de celles que l'on cherche
$tabColsKeys = array_keys($tabCols);
for ($col = 1; $col <= $highestColumnIndex; ++$col) {
    $value = $worksheet->getCell([$col, 1])->getValue();
    if (in_array($value, $tabColsKeys)) {
        $tabCols[$value] = $col;
    }
}
unset($tabColsKeys);

// Vérifier qu'on a bien toutes les clefs requises
$ok = true;
foreach ($tabCols as $key => $value) {
    if ($value = "") {
        echo "Clef " . $key . " manquante dans le fichier !";
        $ok = false;
    }
}
if (!$ok) {
    die;
}
unset($ok);

/**
 * Générer le tableau
 */
$tabStagiaires = [];
// Nombre de lignes
$highestRow = $worksheet->getHighestRow(); // e.g. 10
// Ne pas prendre les entêtes
for ($row = 2; $row <= $highestRow; ++$row) {
    $stagiaire = $worksheet->getCell([$tabCols["Individu.CodeAdherent"], $row])->getValue();
    $date = $worksheet->getCell([$tabCols["Formations.DateFin"], $row])->getValue();
    if (!isset($tabStagiaires[$stagiaire])) {
        // Créer la ligne
        $tabStagiaires[$stagiaire] = $date;
    } else {
        // La formation peut apparaître plusieurs fois, ne garder que la plus vieille
        $dateActuelle = DateTime::createFromFormat("d/m/Y", $tabStagiaires[$stagiaire]);
        $dateLigne = DateTime::createFromFormat("d/m/Y", $date);
        if ($dateLigne < $dateActuelle) {
            $tabStagiaires[$stagiaire] = $date;
        }
    }
}

// Récupérer le nom de la formation
$formation = preg_replace("#[^\w\s]#", "", strtolower($worksheet->getCell([$tabCols["FormationsType.Libelle"], 2])));

file_put_contents(__DIR__ . "/data/" . $formation, json_encode($tabStagiaires));

echo "Données mises à jour !";