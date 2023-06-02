<?php
/*
 * Copyright 2023-2023 Anael MOBILIA
 *
 * This file is part of analytics-monprojet.
 *
 * analytics-monprojet is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * analytics-monprojet is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with analytics-monprojet. If not, see <http://www.gnu.org/licenses/>
 */

include __DIR__ . "/Helpers.php";
include __DIR__ . "/MonProjet.php";

// Paramètres par défaut des filtres
$filtrerTypeCamps = ["FARFADET", "8-11", "11-14", "14-17"];
$filtrerCampsFinis = true;

// Récupérer les valeurs passées en paramètres
if (isset($_REQUEST["campsFinis"])) {
    $filtrerCampsFinis = (bool)$_REQUEST["campsFinis"];
}
if (isset($_REQUEST["typeCamps"])) {
    $filtrerTypeCamps = $_REQUEST["typeCamps"];
}

// Gestion de la session (stockage du token d'authentification)
session_start();
$objMP = new MonProjet($filtrerTypeCamps, $filtrerCampsFinis);
// Authentification si demandée
if (isset($_POST["connexion"])) {
    // Tentative de connexion
    $objMP->authentifier($_POST["login"], $_POST["password"]);
}

$listeDesCamps = [];
if ($objMP->getIdentite() !== "") {
    // Charger la liste des camps si on est conneccté
    $listeDesCamps = $objMP->getListeCamps();
}
?>
<!doctype html>
<html lang="en" class="h-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <title>Analytics - Mon Projet</title>
    <link rel="icon" type="image/png" href="https://monprojet.sgdf.fr/favicon.ico" sizes="16x16">

    <!-- Bootstrap core CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <style>
        /* Sticky navbar */
        /* Show it is fixed to the top */
        body {
            min-height: 75rem;
            padding-top: 4.5rem;
        }

        /* pastilles de couleurs */
        .pastilles {
            width: 10px;
            height: 10px;
            border-radius: 20px;
            display: inline-block;
            vertical-align: middle;
        }

        /* pointeur de souris */
        .pointer {
            cursor: pointer;
        }
    </style>
    <!-- Datatables -->
    <link href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" rel="stylesheet">
</head>
<body class="d-flex flex-column h-100">
<header>
    <!-- navbar -->
    <nav class="navbar navbar-light bg-light fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <img src="https://monprojet.sgdf.fr/favicon.ico" width="30" alt="Analytics - Mon Projet">
                Analytics - Mon Projet
            </a>
            <div class="dropdown">
                <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" data-bs-auto-close="outside">
                    Filtres d'affichage
                </button>
                <form class="dropdown-menu p-4">
                    <?php foreach (MonProjet::typeCamps as $key => $value): ?>
                        <div class="form-check dropdown-item">
                            <input type="checkbox" class="form-check-input" id="typeCamps" name="typeCamps[]" value="<?= $value[MonProjet::typeCampsCodeApi] ?>" <?= (in_array($value[MonProjet::typeCampsCodeApi], $filtrerTypeCamps) ? "checked=\"checked\"" : "") ?>>
                            <label class="form-check-label" for="typeCamps"><?= $key ?></label>
                        </div>
                    <?php endforeach; ?>
                    <hr class="dropdown-divider">
                    <div class="form-check dropdown-item">
                        <!-- Pour récupérer la valeur de la checkbox si décochée -->
                        <input type="hidden" name="campsFinis" value="0">
                        <input type="checkbox" class="form-check-input" id="campsFinis" name="campsFinis" value="1" <?= ($filtrerCampsFinis ? "checked=\"checked\"" : "") ?>>
                        <label class="form-check-label" for="campsFinis">Exclure les camps terminés</label>
                    </div>
                    <button type="submit" class="btn btn-primary">Valider</button>
                </form>
            </div>
            <span class="navbar-text"><?= $objMP->getIdentite() ?></span>
        </div>
    </nav>
</header>
<!-- Begin page content -->
<main role="main" class="flex-shrink-0">
    <div class="container-lg">
        <!-- Mire de connexion -->
        <div class="modal" id="modalConnexion" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Se connecter</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="post">
                        <div class="modal-body">
                            <div class="mb-3 row">
                                <label for="login" class="col-sm-4 col-form-label">Numéro d'adhérent</label>
                                <div class="col-sm-8">
                                    <input type="text" class="form-control" id="login" name="login">
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label for="password" class="col-sm-4 col-form-label">Mot de passe</label>
                                <div class="col-sm-8">
                                    <input type="password" class="form-control" id="password" name="password">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" name="connexion" class="btn btn-primary">Se connecter</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="row">
            <ul class="nav nav-tabs" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="liste-camps-tab" data-bs-toggle="tab" data-bs-target="#liste-camps-tab-pane" type="button" role="tab" aria-controls="liste-camps-tab-pane" aria-selected="true">Liste des camps</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="calendrier-tab" data-bs-toggle="tab" data-bs-target="#calendrier-tab-pane" type="button" role="tab" aria-controls="calendrier-tab-pane" aria-selected="false" onclick="calendar.render();">Calendrier</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="pole-peda-tab" data-bs-toggle="tab" data-bs-target="#pole-peda-tab-pane" type="button" role="tab" aria-controls="pole-peda-tab-pane" aria-selected="false">Pôle péda</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="stages-pratiques-tab" data-bs-toggle="tab" data-bs-target="#stages-pratiques-tab-pane" type="button" role="tab" aria-controls="stages-pratiques-tab-pane" aria-selected="false">Stages pratiques</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="stages-pratiques-tab" data-bs-toggle="tab" data-bs-target="#tam-tab-pane" type="button" role="tab" aria-controls="tam-tab-pane" aria-selected="false">Télédéclaration</button>
                </li>
            </ul>
            <div class="tab-content" id="myTabContent">
                <div class="tab-pane fade show active" id="liste-camps-tab-pane" role="tabpanel" aria-labelledby="liste-camps-tab" tabindex="0">
                    <!-- Liste des camps -->
                    <h1>Liste des camps</h1>
                    <table class="table table-striped" id="listeCamps">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Structure(s) parent</th>
                            <th>Structure(s)</th>
                            <th>Type de camp</th>
                            <th>Début</th>
                            <th>Fin</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($listeDesCamps as $unCamp) : ?>
                            <tr>
                                <td><?= Helpers::getLienVersCamp($unCamp->id) ?></td>
                                <td>
                                    <?php foreach ($unCamp->campStructures as $uneStructure): ?>
                                        <?= ($uneStructure->structure->libelle) ? substr($uneStructure->structure->code, 0, -2) . "00<br/>" : "" ?>
                                    <?php endforeach; ?>
                                </td>
                                <td>
                                    <?php foreach ($unCamp->campStructures as $uneStructure): ?>
                                        <?= $uneStructure->structure->code ?> - <?= ($uneStructure->structure->libelle) ?? "Structure hors périmètre" ?><br/>
                                    <?php endforeach; ?>
                                </td>
                                <td data-order="<?= Helpers::getCategorieForDatatables($unCamp->typeCamp->libelle) ?>"><span class="pastilles" style="background-color:<?= Helpers::getColor($unCamp->typeCamp->libelle) ?>"></span>&nbsp;<?= $unCamp->typeCamp->libelle ?></td>
                                <td data-order="<?= $unCamp->dateDebut ?>"><?= Helpers::dateFormatDmy($unCamp->dateDebut) ?></td>
                                <td data-order="<?= $unCamp->dateFin ?>"><?= Helpers::dateFormatDmy($unCamp->dateFin) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="tab-pane fade" id="calendrier-tab-pane" role="tabpanel" aria-labelledby="calendrier-tab" tabindex="0">
                    <!-- Calendrier -->
                    <div id="calendar"></div>
                </div>
                <div class="tab-pane fade" id="pole-peda-tab-pane" role="tabpanel" aria-labelledby="pole-peda-tab" tabindex="0">
                    <!-- Infos demandées par le pôle péda -->
                    <h1>Infos pour le pôle péda</h1>
                    <table class="table table-striped" id="polePeda">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Structure(s)</th>
                            <th>Dates</th>
                            <th>Durée</th>
                            <th>Jeunes</th>
                            <th title="Animateurs + Directeurs">Chefs</th>
                            <th>Directeur</th>
                            <th>AP en soutien</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($listeDesCamps as $unCamp) : ?>
                            <tr>
                                <td><?= Helpers::getLienVersCamp($unCamp->id) ?></td>
                                <td>
                                    <?php foreach ($unCamp->campStructures as $uneStructure): ?>
                                        <span class="pastilles" style="background-color:<?= Helpers::getColor($unCamp->typeCamp->libelle) ?>" title="<?= $unCamp->typeCamp->libelle ?>"></span>&nbsp;<?= ($uneStructure->structure->libelle) ?? "Structure hors périmètre" ?><br/>
                                    <?php endforeach; ?>
                                </td>
                                <td data-order="<?= $unCamp->dateDebut ?>"><?= Helpers::dateFormatDmy($unCamp->dateDebut) ?> au <?= Helpers::dateFormatDmy($unCamp->dateFin) ?></td>
                                <td><?= Helpers::dateCountDays($unCamp->dateDebut, $unCamp->dateFin) ?></td>
                                <td><?= sizeof($unCamp->campAdherentParticipants) ?></td>
                                <td data-order="<?= $objMP->getStaffRoleNb($unCamp->campAdherentStaffs, MonProjet::typeStaffChef) + $objMP->getStaffRoleNb($unCamp->campAdherentStaffs, MonProjet::typeStaffDirecteur) ?>" title="Animateurs + Directeurs"><?= $objMP->getStaffRoleNb($unCamp->campAdherentStaffs, MonProjet::typeStaffChef) ?> + <?= $objMP->getStaffRoleNb($unCamp->campAdherentStaffs, MonProjet::typeStaffDirecteur) ?></td>
                                <td><?= $objMP->getDirecteurInfos($unCamp->campAdherentStaffs) ?></td>
                                <td><?= $objMP->getAps($unCamp->campAdherentSoutiens) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="tab-pane fade" id="stages-pratiques-tab-pane" role="tabpanel" aria-labelledby="stages-pratiques-tab" tabindex="0">
                    <!-- Suivi des stages pratiques / RFT -->
                    <h1>Stages pratiques BAFA / BAFD</h1>
                    <table class="table table-striped" id="stagesPratiques">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Structure(s)</th>
                            <th>Dates</th>
                            <th>Directeur</th>
                            <th>AP en soutien</th>
                            <th>SP BAFA</th>
                            <th>SP BAFD</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($listeDesCamps as $unCamp) : ?>
                            <tr>
                                <td><?= Helpers::getLienVersCamp($unCamp->id) ?></td>
                                <td>
                                    <?php foreach ($unCamp->campStructures as $uneStructure): ?>
                                        <span class="pastilles" style="background-color:<?= Helpers::getColor($unCamp->typeCamp->libelle) ?>" title="<?= $unCamp->typeCamp->libelle ?>"></span>&nbsp;<?= ($uneStructure->structure->libelle) ?? "Structure hors périmètre" ?><br/>
                                    <?php endforeach; ?>
                                </td>
                                <td data-order="<?= $unCamp->dateDebut ?>"><?= Helpers::dateFormatDmy($unCamp->dateDebut) ?> au <?= Helpers::dateFormatDmy($unCamp->dateFin) ?></td>
                                <td><?= $objMP->getDirecteurInfos($unCamp->campAdherentStaffs) ?></td>
                                <td><?= $objMP->getAps($unCamp->campAdherentSoutiens) ?></td>
                                <td><?= $objMP->getStagePratique($unCamp->campAdherentStaffs, "Bafa") ?></td>
                                <td><?= $objMP->getStagePratique($unCamp->campAdherentStaffs, "Bafd") ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="tab-pane fade" id="tam-tab-pane" role="tabpanel" aria-labelledby="tam-tab" tabindex="0">
                    <!-- Télédéclaration TAM -->
                    <h1>Télédéclaration TAM</h1>
                    <table class="table table-striped" id="tam">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Structure(s)</th>
                            <th>Dates</th>
                            <th>Directeur</th>
                            <th>Statut TAM</th>
                            <th>Délai maximal</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($listeDesCamps as $unCamp) : ?>
                            <tr>
                                <td><?= Helpers::getLienVersCamp($unCamp->id) ?></td>
                                <td>
                                    <?php foreach ($unCamp->campStructures as $uneStructure): ?>
                                        <span class="pastilles" style="background-color:<?= Helpers::getColor($unCamp->typeCamp->libelle) ?>" title="<?= $unCamp->typeCamp->libelle ?>"></span>&nbsp;<?= ($uneStructure->structure->libelle) ?? "Structure hors périmètre" ?><br/>
                                    <?php endforeach; ?>
                                </td>
                                <td data-order="<?= $unCamp->dateDebut ?>"><?= Helpers::dateFormatDmy($unCamp->dateDebut) ?> au <?= Helpers::dateFormatDmy($unCamp->dateFin) ?></td>
                                <td><?= $objMP->getDirecteurInfos($unCamp->campAdherentStaffs) ?></td>
                                <td data-order="<?= Helpers::getStatutTamForDatatables($unCamp->statutDeclarationTam) ?>"><?= Helpers::getStatutTamLong($unCamp->statutDeclarationTam) ?></td>
                                <td data-order="<?= date("Y-m-d", strtotime(Helpers::dateFormatIso8601($unCamp->dateDebut) . " -1 month")) ?>"><?= date("d/m/Y", strtotime(Helpers::dateFormatIso8601($unCamp->dateDebut) . " -1 month")) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- jQuery for Datatables -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.4/dist/jquery.min.js"></script>
<!-- Tooltip for Fullcalendar (à fournir avant bootstrap) -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.7/dist/umd/popper.min.js"></script>
<!-- Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
<!-- Datatables -->
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<!-- Fullcalendar -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.6/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.6/locales/fr.js"></script>
<script>
    // Lancer Datatables
    $(document).ready(function () {
        $('#listeCamps').DataTable({
            // En français
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/fr-FR.json',
            },
            // Pas de pagination
            paging: false,
            // Tri sur la structure parent et la tranche d'âge
            order: [[1, 'asc'], [3, 'asc']],
        });
        $('#polePeda').DataTable({
            // En français
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/fr-FR.json',
            },
            // Pas de pagination
            paging: false,
            // Tri sur la date
            order: [[2, 'asc']],
        });
        $('#stagesPratiques').DataTable({
            // En français
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/fr-FR.json',
            },
            // Pas de pagination
            paging: false,
            // Tri sur les SP
            order: [[6, 'desc'], [5, 'desc']],
        });
        $('#tam').DataTable({
            // En français
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/fr-FR.json',
            },
            // Pas de pagination
            paging: false,
            // Tri sur le statut TAM, date de déclaration puis code structure
            order: [[4, 'desc'], [5, 'asc'], [0, 'desc']],
        });
    });

    // Lancer Fullcalendar
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        // Mettre sur début juillet par défaut
        initialDate: '<?= date("Y")?>-07-01',
        // En français
        locale: 'fr',
        // Commencer les semaines le lundi
        firstDay: 1,
        events: [
            <?php foreach ($listeDesCamps as $unCamp) : ?>
            {
                title: '<?php foreach ($unCamp->campStructures as $uneStructure): ?><?= (str_replace("'", "\'", $uneStructure->structure->libelle)) ?? "Structure hors périmètre" ?>\n<?php endforeach; ?>',
                url: '<?= Helpers::getLienVersCamp($unCamp->id, false) ?>',
                start: '<?= Helpers::dateFormatIso8601($unCamp->dateDebut) ?>',
                // Date de fin : exclue de la plage (donc ajouter 1 jour)
                end: '<?= date("Y-m-d", strtotime(Helpers::dateFormatIso8601($unCamp->dateFin) . " +1 day")) ?>',
                // Evenement sur toute la journée
                allDay: true,
                color: '<?= Helpers::getColor($unCamp->typeCamp->libelle) ?>',
                description: '<?= Helpers::dateFormatDmy($unCamp->dateDebut) ?> au <?= Helpers::dateFormatDmy($unCamp->dateFin) ?>'
            },
            <?php endforeach; ?>
        ],
        // Ouvrir le lien dans un nouvel onglet
        eventClick: function (info) {
            info.jsEvent.preventDefault(); // don't let the browser navigate

            if (info.event.url) {
                window.open(info.event.url);
            }
        },
        // Infobulle avec les dates du camp (utilisation des tooltips bootstrap)
        eventDidMount: function (info) {
            new bootstrap.Tooltip(info.el, {
                title: info.event.extendedProps.description,
                placement: "top",
                trigger: "hover",
                container: "body"
            });
        },
        // Désactivation de la scrollbar => prendre toute la hauteur de la page
        contentHeight: "auto",
    });
    calendar.render();

    <?php if ($objMP->getIdentite() === ""): ?>
    // Mire de connexion
    const myModal = new bootstrap.Modal(document.getElementById('modalConnexion'), '');
    myModal.show();
    <?php endif; ?>

    // Copier dans le presse papier un élément
    function copyToClipboard(elem) {
        const maVal = elem.title;
        navigator.clipboard.writeText(maVal);

        // Retour utilisateur via tooltip
        const tooltip = new bootstrap.Tooltip(elem, {
            title: 'Copié !',
        });
        tooltip.show();
        setTimeout(() => {  tooltip.hide(); }, 1000);
    }
</script>
</body>
</html>