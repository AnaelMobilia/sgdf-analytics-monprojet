<?php
/*
 * Copyright 2023-2024 Anael MOBILIA
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

class MonProjet
{
    /**
     * Constantes issues de mon projet
     */
    const typeCampsCouleur = "couleur";
    const typeCampsCodeApi = "code";
    const typeCampsOrdreAge = "triAge";
    // Types de camps
    const typeCamps = [
        "Farfadets" => [
            self::typeCampsCouleur => "#65bc99",
            self::typeCampsCodeApi => "FARFADET",
            self::typeCampsOrdreAge => "1",
        ],
        "Louveteaux-jeannettes" => [
            self::typeCampsCouleur => "#ff8300",
            self::typeCampsCodeApi => "8-11",
            self::typeCampsOrdreAge => "2",
        ],
        "Scouts-guides" => [
            self::typeCampsCouleur => "#0077b3",
            self::typeCampsCodeApi => "11-14",
            self::typeCampsOrdreAge => "3",
        ],
        "Pionniers-Caravelles" => [
            self::typeCampsCouleur => "#d03f15",
            self::typeCampsCodeApi => "14-17",
            self::typeCampsOrdreAge => "4",
        ],
        "Compagnons T1 et T3" => [
            self::typeCampsCouleur => "#007254",
            self::typeCampsCodeApi => "COMPAGNONS-T1",
            self::typeCampsOrdreAge => "5",
        ],
        "Compagnons T2" => [
            self::typeCampsCouleur => "#007254",
            self::typeCampsCodeApi => "COMPAGNONS-T2",
            self::typeCampsOrdreAge => "6",
        ],
        "Audace" => [
            self::typeCampsCouleur => "#6e74aa",
            self::typeCampsCodeApi => "AUDACE",
            self::typeCampsOrdreAge => "7",
        ],
        "Camp accompagné" => [
            self::typeCampsCouleur => "#003a5d",
            self::typeCampsCodeApi => "CAMP-ACCOMPAGNE",
            self::typeCampsOrdreAge => "8",
        ],
        "Camp autre" => [
            self::typeCampsCouleur => "#003a5d",
            self::typeCampsCodeApi => "AUTRE",
            self::typeCampsOrdreAge => "9",
        ],
    ];

    // Type de soutien : AP
    const typeSoutienAp = "AP";
    // Type de rôles : Chef
    const typeStaffChef = "C";
    // Type de rôles : Directeur
    const typeStaffDirecteur = "D";
    // Statut TAM
    const typeTamLong = ["Déclaration Partielle", "Déclaration Complete", "Non Conforme", "Insuffisant", "Validé par la DDCS", "Modifiée depuis la déclaration J&S", "Non transmis"];
    // Tri TAM
    const typeTamShort = ["PD", "FD", "UD", "ID", "VD", "MD", "ND"];

    // Token de session
    private string $token;
    // Identité de la personne
    private string $identite;
    // Structure de la personne
    private string $codeStructure;

    // Paramètres de filtrage
    private array $filtrerTypeCamps;
    private bool $filtrerCampsFinis;

    // URL de base de l'API
    const base_url = "https://monprojet.sgdf.fr/api/";
    // Nombre de camps retournés par l'API
    const nbCamps = 50;

    // Variables de cache
    private array $cacheElearning = [];
    private array $cacheVisite = [];
    const fichierVisite = "visites";

    /**
     * Constructeur avec les paramètres d'affichage
     * @param array $typeCamps Type de camps à afficher (clefs de self::typeCamps)
     * @param bool $campsFinis Afficher les camps terminés ?
     */
    public function __construct(array $typeCamps, bool $campsFinis)
    {
        $this->filtrerTypeCamps = $typeCamps;
        $this->filtrerCampsFinis = $campsFinis;

        // Charger les valeurs enregistrées dans la session
        $this->token = $_SESSION["token"] ?? "";
        $this->identite = $_SESSION["identite"] ?? "";
        $this->codeStructure = $_SESSION["codeStructure"] ?? "";
    }

    /**
     * Authentifier une personne
     * @param string $user
     * @param string $password
     * @return string Identité de la personne ?
     */
    public function authentifier(string $user, string $password): string
    {
        $ch = curl_init(self::base_url . "login");
        curl_setopt_array($ch, array(
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
            CURLOPT_POSTFIELDS => json_encode(["numero" => $user, "password" => $password]),
        ));
        $response = curl_exec($ch);
        $response = json_decode($response);
        curl_close($ch);

        // Récupération du token de session
        if (isset($response->token)) {
            $this->token = $response->token;
            $this->identite = Helpers::formatPrenomNom($response->userData->fullName);
            $this->codeStructure = $response->userData->selectedFunctionality->codeStructure;

            // Enregistrer les valeurs en session
            $_SESSION["token"] = $this->token;
            $_SESSION["identite"] = $this->identite;
            $_SESSION["codeStructure"] = $this->codeStructure;
        }

        return $this->identite;
    }

    /**
     * Liste des camps
     * @param int $selectedPage
     * @return array
     */
    public function getListeCamps(int $selectedPage = 1): array
    {
        $returnValue = [];

        // Calcul des bornes de l'exercice en cours
        $anneeDebut = date("Y");
        if (date("m") <= "8") {
            $anneeDebut = date("Y") - 1;
        }

        // Date de début du séjour
        $dateDeb = $anneeDebut . "-09-01";
        if ($this->filtrerCampsFinis) {
            // Ne pas prendre les camps terminés
            $dateDeb = date("Y-m-d");
        }
        $dateDeb .= "T00:00:00.000Z";

        // Date de fin
        $dateFin = ($anneeDebut + 1) . "-09-15T00:00:00.000Z";

        // Type de camps
        $typeCamps = implode(",", $this->filtrerTypeCamps);

        // Construction de l'URL
        $url = self::base_url . "camps/multi-criteres?dateDebut=" . $dateDeb . "&dateFin=" . $dateFin . "&statutsCamp=1&chercherDossiersParticipants=0&codeStructure=" . $this->codeStructure . "&chercherStructuresDependates=1&codesTypesCamp=" . $typeCamps . "&idsTamRefExercices=19&selectedPage=" . $selectedPage;
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $this->token,
                'Content-Type: application/json'
            ),
        ));
        $response = curl_exec($ch);
        $response = json_decode($response);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_status == "200") {
            // Récupération de la liste des camps
            if (isset($response->camps)) {
                foreach ($response->camps as $unCamp) {
                    $returnValue[$unCamp->id] = $unCamp;
                }
                // Si on a plus de camps
                if ($response->campsTotalCount > ($selectedPage * self::nbCamps)) {
                    // Appel récursif
                    $returnValue = array_merge($returnValue, $this->getListeCamps(++$selectedPage));
                }
            }
        } else {
            // Erreur lors de l'appel à l'API, invalidation de la session
            session_destroy();
            $this->identite = "";
        }

        return $returnValue;
    }

    /**
     * Retourne le nom de l'AP en soutien du camp
     * @param array $listeSoutien liste des soutients
     * @return string
     */
    public function getAps(array $listeSoutien): string
    {
        $returnValue = "";
        foreach ($listeSoutien as $unSoutien) {
            if ($unSoutien->typeSoutien == self::typeSoutienAp) {
                $returnValue .= Helpers::formatPrenomNom($unSoutien->adherent->prenom . " " . $unSoutien->adherent->nom) . "<br>";
            }
        }
        return $returnValue;
    }

    /**
     * Retourne les infos du directeur (Nom Prénom / tél / mail)
     * @param array $listeStaff ensemble de la maîtrise
     * @return string
     */
    public function getDirecteurInfos(array $listeStaff): string
    {
        $returnValue = "";
        foreach ($listeStaff as $unChef) {
            if ($unChef->roleStaff == self::typeStaffDirecteur) {
                $returnValue .= $this->getInfosContactChef($unChef);
            }
        }
        return $returnValue;
    }

    /**
     * Nombre de chefs ayant le rôle xxx
     * @param array $listeStaff ensemble de la maîtrise
     * @param string $role xxx
     * @return int
     */
    public function getStaffRoleNb(array $listeStaff, string $role): int
    {
        $returnValue = 0;

        foreach ($listeStaff as $unChef) {
            if ($unChef->roleStaff == $role) {
                $returnValue++;
            }
        }

        return $returnValue;
    }

    /**
     * Liste des personnes en stage pratique
     * @param array $listeStaff ensemble de la maîtrise
     * @param string $type "Bafa" ou "Bafd"
     * @return string
     */
    public function getStagePratique(array $listeStaff, string $type): string
    {
        $returnValue = "";
        foreach ($listeStaff as $unChef) {
            if ($unChef->{"validationStagePratique" . $type}) {
                $returnValue .= $this->getInfosContactChef($unChef);
            }
        }
        return $returnValue;
    }

    /**
     * Retourner les infos de contact d'un chef
     * @param object $unChef
     * @return string
     */
    public function getInfosContactChef(object $unChef): string
    {
        $returnValue = Helpers::formatPrenomNom($unChef->adherent->prenom . " " . $unChef->adherent->nom) . "<br>";

        $contact = "";
        if (!empty($unChef->adherent->email)) {
            $contact .= $this->generateSpanInfosContactChef($unChef->adherent->email, "email");
        }
        if (!empty($unChef->adherent->telephonePortable)) {
            // Gestion des téléphones multiples : "06xxxxxxxx / 07xxxxxxxx"
            foreach (explode("/", $unChef->adherent->telephonePortable) as $unNumero) {
                if ($contact !== "") {
                    $contact .= " - ";
                }
                $contact .= $this->generateSpanInfosContactChef($unNumero, "tel");
            }
        }
        $returnValue .= $contact . "<br>";

        return $returnValue;
    }

    /**
     * Retourner les e-learning suivis par une personne
     * @param object $unChef
     * @return array
     */
    public function getELearning(object $unChef): array
    {
        $returnValue = [];

        if (empty($this->cacheElearning)) {
            // Liste des fichiers
            $tabFiles = scandir(__DIR__ . "/data/");
            foreach ($tabFiles as $unFichier) {
                if ($unFichier == "." || $unFichier == ".." || $unFichier == ".htaccess" || $unFichier == MonProjet::fichierVisite) {
                    continue;
                }
                $this->cacheElearning[$unFichier] = json_decode(file_get_contents(__DIR__ . "/data/" . $unFichier), true);
            }
        }

        foreach ($this->cacheElearning as $unFichier => $tabStagiaires) {
            // Le chef est-il présent dans cette formation ?
            if (in_array($unChef->adherent->numero, array_keys($tabStagiaires))) {
                $returnValue[] = $unFichier . " - " . $tabStagiaires[$unChef->adherent->numero];
            }
        }

        return $returnValue;
    }

    /**
     * Génère le code HTML pour les infos de contact d'un chef
     * @param string $value valeur
     * @param string $type type d'information ["email", "tel"]
     * @return string
     */
    private function generateSpanInfosContactChef(string $value, string $type): string
    {
        $value = trim($value);

        switch ($type) {
            case "email":
                $emoji = "📧";
                break;
            case "tel":
                $emoji = "📞";
                break;
            default:
                $emoji = "❔";
                break;
        }
        return "<span class=\"pointer\" title=\"" . $value . "\" onclick=\"copyToClipboard(this);\" data-toggle=\"tooltip\" data-placement=\"bottom\" data-trigger=\"manual\">" . $emoji . "</span>";
    }

    /**
     * Récupérer l'identité de la personne
     * @return string
     */
    public function getIdentite(): string
    {
        return $this->identite;
    }

    /**
     * Lien vers une adresse
     * @param object $campLieuPrincipal
     * @return string
     */
    public function getAdresse(object $campLieuPrincipal): string
    {
        return "<a href=\"https://maps.google.com/?q=" . str_replace("\"", "'", $campLieuPrincipal->adresseLigne1) . " " . str_replace("\"", "'", $campLieuPrincipal->tamRefCommune->codePostale) . " " . str_replace("\"", "'", $campLieuPrincipal->tamRefCommune->libelle) . "\" target=\"_blank\">" . $campLieuPrincipal->tamRefCommune->codePostale . " " . $campLieuPrincipal->tamRefCommune->libelle . "</a>";
    }

    /**
     * Liste des visites pour le camp
     * @param int $idCamp
     * @return string
     */
    public function getVisite(int $idCamp): string
    {
        if (empty($this->cacheVisite)) {
            // Une ligne = une visite
            foreach (file(__DIR__ . "/data/" . MonProjet::fichierVisite) as $uneLigne) {
                $uneVisite = json_decode($uneLigne, true);
                $data = $uneVisite["identite"] . " : " . $uneVisite["date"];
                if (!empty($uneVisite["infos"])) {
                    $data .= " (" . $uneVisite["infos"] . ")";
                }
                if (isset($this->cacheVisite[$uneVisite["id_camp"]])) {
                    // Une visite est déjà prévue
                    $this->cacheVisite[$uneVisite["id_camp"]] .= "<br>" . $data;
                } else {
                    // Pas encore de visite de prévue
                    $this->cacheVisite[$uneVisite["id_camp"]] = $data;
                }
            }
        }

        return $this->cacheVisite[$idCamp] ?? "";
    }
}