<?php

namespace Helip\EsahrAnciennete;

/**
 * Class AncienneteService
 * @package Helip\Anciennete
 */
class AncienneteService implements AncienneteInterface
{

    private const ANCIENNETES = [
        'PO' => 0,
        'PO_SUM' => 0, // Ancienneté à additionner avec CF
        'CF' => 0,
        'TOTAL' => '0'
    ];

    private array $anciennete = [
        Attribution::CAT_PERSONNEL_EDUCATIF => self::ANCIENNETES,
        Attribution::CAT_PERSONNEL_AUXILIAIRE => self::ANCIENNETES
    ];

    private array $chargeDecimalTotal = [
        Attribution::CAT_PERSONNEL_EDUCATIF => self::ANCIENNETES,
        Attribution::CAT_PERSONNEL_AUXILIAIRE => self::ANCIENNETES
    ];

    /**
     * @param string $categorie
     * @param string $type (PO, CF ou TOTAL)
     * @return int
     */
    public function get(string $categorie, string $type = 'TOTAL'): float
    {
        if (!array_key_exists($categorie, $this->anciennete)) {
            throw new \InvalidArgumentException('La catégorie ' . $categorie . ' n\'existe pas.');
        }

        if (!array_key_exists($type, $this->anciennete[$categorie])) {
            throw new \InvalidArgumentException('Le type ' . $type . ' n\'existe pas.');
        }

        // somme pour total
        if ($type === 'TOTAL') {
            $jours = $this->anciennete[$categorie]['PO_SUM'] + $this->anciennete[$categorie]['CF'];
        } else {
            $jours = $this->anciennete[$categorie][$type];
        }

        $jours = AncienneteCalculateurHelper::tronqueDecimales($jours);

        $chargeDecimal = $this->chargeDecimalTotal[$categorie][$type];
        $jours = AncienneteCalculateurHelper::appliquerLimitesAnciennete($jours, $chargeDecimal);

        return ($jours);
    }

    /**
     * @param int|float $jours
     * @param string $categorie
     * @param bool $estPO
     */
    public function add(
        int|float $jours,
        string $categorie,
        float $chargeDecimal,
        bool $estPO,
        int $anciennetePo = 0
    ): void {

        $this->validateParameters($categorie, $chargeDecimal);

        // Si la charge décimale est inférieure à 0.5, on divise les jours par 2
        if (.5 > $chargeDecimal) {
            $jours /= 2.0;
        }

        // Todo calcul s'applique jusque 1200 jours -> soustraction des jours déjà calculés
        if ($estPO) {
            $remainingDaysTo1200 = 1200 - ($anciennetePo + $this->anciennete[$categorie]['PO']);
            $jours = AncienneteCalculateurHelper::appliquerLimitesAnciennete($jours, $chargeDecimal);

            if ($jours <= $remainingDaysTo1200) {
                $jours *= .3;
            } else {
                $reducedDays = $remainingDaysTo1200 * .3;
                $nonReducedDays = $jours - $remainingDaysTo1200;
                $jours = $reducedDays + $nonReducedDays;
            }

            $jours_po_tronques = AncienneteCalculateurHelper::tronqueDecimales($jours);
        }

        $this->anciennete[$categorie][$estPO ? 'PO' : 'CF'] += $jours;
        $this->chargeDecimalTotal[$categorie][$estPO ? 'PO' : 'CF'] += $chargeDecimal;

        // Ajout au total
        // Ajoute ancienneté PO uniquement si la charge décimale CF est inférieure à 0.5
        if ($this->chargeDecimalTotal[$categorie]['CF'] >= .5 && $estPO) {
            return;
        }

        if ($estPO) {
            $this->anciennete[$categorie]['PO_SUM'] += $jours_po_tronques;
        } else {
            $this->anciennete[$categorie]['TOTAL'] += $jours;
            $this->chargeDecimalTotal[$categorie]['TOTAL'] += $chargeDecimal;
        }
    }

    public function resetChargeDecimal(): void
    {
        $this->chargeDecimalTotal = [
            Attribution::CAT_PERSONNEL_EDUCATIF => self::ANCIENNETES,
            Attribution::CAT_PERSONNEL_AUXILIAIRE => self::ANCIENNETES
        ];
    }

    private function validateParameters(string $categorie, float $chargeDecimal): void
    {
        if (!array_key_exists($categorie, $this->anciennete)) {
            throw new \InvalidArgumentException('La catégorie ' . $categorie . ' n\'existe pas.');
        }
        if ($chargeDecimal < 0) {
            throw new \InvalidArgumentException('La charge décimale doit être positive.');
        }
        if ($chargeDecimal > 1) {
            throw new \InvalidArgumentException('La charge décimale doit être inférieure ou égale à 1.');
        }
    }
}
