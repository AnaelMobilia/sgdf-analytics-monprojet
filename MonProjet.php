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

class MonProjet
{
    /**
     * Constantes issues de mon projet
     */
    // Types de camps
    const typeCamps = ["Farfadets", "Louveteaux-jeannettes", "Scouts-guides", "Pionniers-Caravelles", "Compagnons T1 et T3", "Compagnons T2", "Audace", "Camp accompagné", "Camp autre"];
    // Couleurs associées
    const couleurTypeCamps = ["#65bc99", "#ff8300", "#0077b3", "#d03f15", "#007254", "#007254", "#6e74aa", "#003a5d", "#003a5d"];
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
    private bool $filtrerCampsCompas;
    private bool $filtrerCampsFinis;

    // URL de base de l'API
    const base_url = "https://monprojet.sgdf.fr/api/";
    // Nombre de camps retournés par l'API
    const nbCamps = 50;

    /**
     * Constructeur avec les paramètres d'affichage
     * @param array $tabParams
     */
    public function __construct(array $tabParams)
    {
        $this->filtrerCampsCompas = $tabParams["filtrerCompas"];
        $this->filtrerCampsFinis = $tabParams["filtrerFinis"];
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
            $this->identite = $response->userData->fullName;
            $this->codeStructure = $response->userData->selectedFunctionality->codeStructure;
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

        $ch = curl_init(self::base_url . "camps/multi-criteres?dateDebut=2022-09-01T00:00:00.000Z&dateFin=2023-09-15T00:00:00.000Z&statutsCamp=1&chercherDossiersParticipants=0&codeStructure=" . $this->codeStructure . "&chercherStructuresDependates=1&idsTamRefExercices=18&selectedPage=" . $selectedPage);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $this->token,
                'Content-Type: application/json'
            ),
        ));
        $response = curl_exec($ch);
        $response = json_decode($response);
        curl_close($ch);

        // Récupération de la liste des camps
        if (isset($response->camps)) {
            foreach ($response->camps as $unCamp) {
                // Application des paramètres de filtrage
                $prendreLeCamp = true;
                if ($this->filtrerCampsFinis && $unCamp->dateFin < date(DateTimeInterface::ISO8601, time())) {
                    $prendreLeCamp = false;
                }
                if ($this->filtrerCampsCompas && in_array($unCamp->typeCamp->libelle, [self::typeCamps["4"], self::typeCamps["5"]])) {
                    $prendreLeCamp = false;
                }
                // Si on veut toujours le camp...
                if ($prendreLeCamp) {
                    $returnValue[$unCamp->id] = $unCamp;
                }
            }
            // Si on a plus de camps
            if ($response->campsTotalCount > ($selectedPage * self::nbCamps)) {
                // Appel récursif
                $returnValue = array_merge($returnValue, $this->getListeCamps(++$selectedPage));
            }
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
                $returnValue .= Helpers::formatPrenomNom($unSoutien->adherent->prenom, $unSoutien->adherent->nom) . "<br/>";
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
    private function getInfosContactChef(object $unChef): string
    {
        $returnValue = Helpers::formatPrenomNom($unChef->adherent->prenom, $unChef->adherent->nom) . "<br />";
        $returnValue .= $unChef->adherent->telephonePortable . "<br />";
        $returnValue .= $unChef->adherent->email . "<br />";
        return $returnValue;
    }
}