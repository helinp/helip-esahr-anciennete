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
     * @param Attribution[] $attribution
     * @return array
     */
    public static function groupeParFonction(array $attributions): array
    {
        return self::groupeParPropriete($attributions, 'getFonction');
    }

    /**
     * Retourne un array d'attributions groupées par catégorie
     * 
     * @param Attribution[] $attribution
     * @return array
     */
    public static function groupeParCategorieEtPo(array $attributions)
    {
        return self::groupeParPropriete($attributions, 'getCategorie');
    }

    private static function groupeParPropriete(array $items, string $propriete): array
    {
        $groupes = [];

        foreach ($items as $item) {

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
}
