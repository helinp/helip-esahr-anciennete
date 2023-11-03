<?php

namespace Helip\EsahrAnciennete;

use DateTime;

class AncienneteCalculateurHelper
{
    /**
     * Retourne le nombre de jours calendrier entre deux dates
     * 
     * @param DateTime $dateDebut
     * @param DateTime $dateFin
     * @return int
     */
    public static function getNombreJoursCalendrier(DateTime $dateDebut, DateTime $dateFin): int
    {
        $interval = $dateDebut->diff($dateFin);
        return $interval->days + 1;
    }

    /**
     * Trie un tableau d'événements par date de début
     * 
     * @param Evenement[] $evenements
     * @return Evenement[]
     */
    public static function trierEvenementsParDateDebut(array $evenements): array
    {
        usort($evenements, function ($a, $b) {
            return $a->getDateDebut() <=> $b->getDateDebut();
        });

        return $evenements;
    }

    /**
     * Tronque les décimales d'un nombre
     * 
     * @param float $nombre
     * @return int
     */
    public static function tronqueDecimales(float $nombre): int
    {
        return intval($nombre);
    }

    /**
     * Retourne un array d'attributions groupées par fonction
     * 
     * @param Attribution[] $attributions
     * @return Attribution[]
     */
    public static function groupeParFonction(array $attributions): array
    {
        return self::groupeParPropriete($attributions, 'getFonction');
    }

    /**
     * Retourne un array d'attributions groupées par catégorie
     * 
     * @param Attribution[] $attributions
     * @return Attribution[]
     */
    public static function groupeParCategorieEtPo(array $attributions): array
    {
        return self::groupeParPropriete($attributions, 'getCategorie');
    }

    /**
     * Retourne un array d'attributions groupées par propriété
     * 
     * @param mixed[] $attributions
     * @param Attribution[] $attributions
     * 
     * @return mixed[]
     */
    private static function groupeParPropriete(array $attributions, string $propriete): array
    {
        $groupes = [];

        foreach ($attributions as $item) {

            $key = $item->$propriete() . (string) $item->getEstPo();
            if (!array_key_exists($key, $groupes)) {
                $groupes[$key] = $item;
            } else {
                $itemExistante = $groupes[$key];
                $itemExistante->setPeriodes($itemExistante->getPeriodes() + $item->getPeriodes());
                $itemExistante->setChargeDecimal($itemExistante->getChargeDecimal() + $item->getChargeDecimal());

                $groupes[$item->$propriete()] = $itemExistante;
            }
        }
        return $groupes;
    }

    /**
     * Applique les limites d'ancienneté
     * Tronque les décimales et limite à 300 jours par année scolaire
     * 
     * @param int $anciennete
     * @param float $chargeDecimal
     * @return int
     */
    public static function appliquerLimitesAnciennete(int $anciennete, float $chargeDecimal): int
    {
        $max = ($chargeDecimal >= .5) ? 300 : 150;

        // Pas plus de 300 jours par année scolaire
        $anciennete = min($anciennete, $max);

        return $anciennete;
    }

    /**
     * Trie un tableau d'attributions par PO
     * CF en premier, puis PO, pour calcul CF > .5
     * 
     * @param Attribution[] $attributions
     * @return Attribution[]
     */
    public static function trierParPo(array $attributions): array
    {
        usort($attributions, function ($a, $b) {
            return $a->getEstPo() <=> $b->getEstPo();
        });

        return $attributions;
    }

    /**
     * Calcule les jours d'ancienneté avec le coefficient
     * .3 (30%) pour les 1200 premiers jours PO
     *
     * @param float $jours le nombre de jours à calculer
     * @param float $anciennetePo l'anncienneté PO brute
     * @return float le nombre de jours avec le coefficient appliqué
     */
    public static function calculerJoursPo(float $jours, float $anciennetePo): float
    {
        // négatif 
        if($anciennetePo < 0 || $jours < 0) {
            throw new \InvalidArgumentException('L\'ancienneté PO ne peut pas être négative.');
        }

        if($jours == 0) {
            return 0;
        }

        $seuilJours = 1200;
        $coefficientReducteur = .3;

        if ($anciennetePo >= $seuilJours) {
            return $jours;
        }
        
        $nbJoursAvant1200 = min($seuilJours - $anciennetePo, $jours);
        $nbJoursApres1200 = max($jours - $nbJoursAvant1200, 0);

        // En dessous de 1200 jours, on applique la règle des 30%
        return ($nbJoursAvant1200 * $coefficientReducteur) + $nbJoursApres1200;
    }

    /**
     * Calcule les jours PO pour une année complète
     * avec une partie en .3
     * 
     * @caution: ATTENTION: * ne fonctionne pas si charge passe au dessus
     * et au dessous de .5 durant l'année
     * 
     * @param float $ancienneteTotalPoRaw l'ancienneté calculée sans coéfficient (jours calendiers * 0.5|1.0)
     * @param float $ancienneteBrutePo l'ancienneté de carrière PO brute (pour calcul 1200 jours)
     * @param float $ancienneteTotalPo l'ancienneté à corriger calculée avec coéficient (jours calendriers * 0.5|1.0) 
     * @param float $chargeDecimalTotal la dernière charge calculée *
     * @return float
     */
    public static function correctionCalculPo(float $ancienneteTotalPoRaw, float $ancienneteBrutePo, float $ancienneteTotalPo, float $chargeDecimalTotal): float
    {
        $correction = max($ancienneteTotalPoRaw - 300, 0);
        $difference1200Jours =  max(0, $ancienneteTotalPoRaw + $ancienneteBrutePo - 1200);

        if ($correction > 0) {

            $coefficientMiTemps = ($chargeDecimalTotal < .5 ? .5 : 1);
            $coefficient1200Jours = ($difference1200Jours >= 0 ? 1 : .3);

            $ancienneteTotalPo = $ancienneteTotalPo - ($correction  * $coefficient1200Jours * $coefficientMiTemps);
        }

        return $ancienneteTotalPo;
    }
}
