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

class Helpers
{

    /**
     * Formate une date en dd/mm/YYYY
     * @param string $date
     * @return string
     */
    public static function dateFormatDmy(string $date): string
    {
        return substr($date, 8, 2) . "/" . substr($date, 5, 2) . "/" . substr($date, 0, 4);
    }

    /**
     * Formate une date en y-m-d
     * @param string $date
     * @return string
     */
    public static function dateFormatIso8601(string $date): string
    {
        return substr($date, 0, 10);
    }

    public static function dateCountDays(string $dateStart, string $dateEnd): int
    {
        return round((strtotime($dateEnd) - strtotime($dateStart)) / 60 / 60 / 24);
    }

    /**
     * Valeur pour data-order pour Datatables
     * @param string $category Tranche d'âge
     * @return string
     */
    public static function getCategorieForDatatables(string $category): string
    {
        return array_search($category, MonProjet::typeCamps);
    }

    /**
     * Valeur pour data-order pour Datatables
     * @param string $statut Statut TAM
     * @return string
     */
    public static function getStatutTamForDatatables(string $statut): string
    {
        return array_search($statut, MonProjet::typeTamShort);
    }

    /**
     * Libellé long pour le statut TAM
     * @param string $statut Statut TAM
     * @return string
     */
    public static function getStatutTamLong(string $statut): string
    {
        return MonProjet::typeTamLong[array_search($statut, MonProjet::typeTamShort)];
    }

    /**
     * Lien vers un camp
     * @param string $ref ID du camp
     * @param bool $withHtml <a href="aaa">xx</a> ou aaa
     * @return string
     */
    public static function getLienVersCamp(string $ref, bool $withHtml = true): string
    {
        if ($withHtml) {
            return "<a href=\"https://monprojet.sgdf.fr/camp/" . $ref . "\" target=\"_blank\">" . $ref . "</a>";
        } else {
            return "https://monprojet.sgdf.fr/camp/" . $ref;
        }
    }

    /**
     * Couleur associée à une tranche d'âge
     * @param string $typeCamps
     * @return string
     */
    public static function getColor(string $typeCamps): string
    {
        return MonProjet::typeCamps[$typeCamps][MonProjet::typeCampsCouleur];
    }

    /**
     * Formatter un Prénom Nom-Composé
     * @param string $nom
     * @param string $prenom
     * @return string
     */
    public static function formatPrenomNom(string $prenom, string $nom): string
    {
        // prenom nom-composé
        $returnValue = mb_strtolower($prenom . " " . $nom);
        // Prenom Nom-composé
        $returnValue = ucwords($returnValue);
        // Prenom Nom-Compose
        $returnValue = preg_replace_callback("#-([a-z])#", function ($matches) {
            return mb_strtoupper($matches[0]);
        }, $returnValue);

        return $returnValue;
    }
}