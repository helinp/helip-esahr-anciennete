<?php

namespace Helip\EsahrAnciennete;

/**
 * Class AncienneteService
 * @package Helip\Anciennete
 */
class AncienneteService implements AncienneteInterface
{
    private int $joursCalendrier = 0;

    private array $ancienneteBrutePo = [
        Attribution::CAT_PERSONNEL_EDUCATIF => 0,
        Attribution::CAT_PERSONNEL_AUXILIAIRE => 0
    ];

    /**
     * @var float[]
     */
    private const ANCIENNETES = [
        'PO_RAW' => 0.0, // Ancienneté PO sans coefficient (pour calcul 1200 jours)
        'PO_SUM' => 0.0, // Ancienneté PO additionable avec avec CF
        'PO' => 0.0,
        'CF' => 0.0,
        'TOTAL' => 0.0 // Ancienne PO_SUM + CF
    ];

    /**
     * @var float[][]
     */
    private array $ancienneteTotal = [
        Attribution::CAT_PERSONNEL_EDUCATIF => self::ANCIENNETES,
        Attribution::CAT_PERSONNEL_AUXILIAIRE => self::ANCIENNETES
    ];

    /**
     * @var float[][]
     */
    private array $ancienneteTmp = [
        Attribution::CAT_PERSONNEL_EDUCATIF => self::ANCIENNETES,
        Attribution::CAT_PERSONNEL_AUXILIAIRE => self::ANCIENNETES
    ];

    /**
     * @var float[][]
     */
    private array $chargeDecimalTotal = [
        Attribution::CAT_PERSONNEL_EDUCATIF => self::ANCIENNETES,
        Attribution::CAT_PERSONNEL_AUXILIAIRE => self::ANCIENNETES
    ];

    /**
     * Set le nombre de jours calendrier
     * pour le calcul de l'ancienneté en cours
     * limite à 300 jours
     * 
     * @param int $joursCalendrier
     * @return void
     */
    public function setJoursCalendrier(int $joursCalendrier): void
    {
        $this->joursCalendrier = AncienneteCalculateurHelper::appliquerLimitesAnciennete($joursCalendrier, 1.0);
    }

    /**
     * Set le nombre de jours PO bruts pour calcul des 1200 jours
     * 
     * @param int $ancienneteBrutePo
     * @param string $categorie doit être une des constantes de la classe Attribution
     * @return void
     */
    public function setAncienneteBrutePo(int $ancienneteBrutePo, string $categorie): void
    {
        if (!array_key_exists($categorie, $this->ancienneteBrutePo)) {
            throw new \InvalidArgumentException('La catégorie ' . $categorie . ' n\'existe pas.');
        }

        $this->ancienneteBrutePo[$categorie] = $ancienneteBrutePo;
    }

    /**
     * @param string $categorie
     * @param string $type (PO, CF ou TOTAL)
     * @return int|float
     */
    public function get(string $categorie, string $type = 'TOTAL'): int|float
    {
        if (!array_key_exists($categorie, $this->ancienneteTotal)) {
            throw new \InvalidArgumentException('La catégorie ' . $categorie . ' n\'existe pas.');
        }

        if (!array_key_exists($type, $this->ancienneteTotal[$categorie])) {
            throw new \InvalidArgumentException('Le type ' . $type . ' n\'existe pas.');
        }

        // gestion cas particulier PO: Année complète + une partie en .3 
        $joursCorriges = 0;
        if (
            $this->ancienneteTotal[$categorie]['PO_RAW'] + $this->ancienneteBrutePo[$categorie] >= 1200
            &&
            ($this->ancienneteTotal[$categorie]['PO_RAW'] > 300 && $this->chargeDecimalTotal[$categorie]['PO_RAW'] > .5
                ||
                $this->ancienneteTotal[$categorie]['PO_RAW'] > 150 && $this->chargeDecimalTotal[$categorie]['PO_RAW'] <= .5)
        ) {
            if ($type === 'PO') {
                return $this->applyCorrectionCalculPo($categorie);
            }
        }

        // somme pour total
        if ($type === 'TOTAL') {
            $jours = $this->ancienneteTotal[$categorie]['PO_SUM'] + $this->ancienneteTotal[$categorie]['CF'];
        } else {
            $jours = $this->ancienneteTotal[$categorie][$type];
        }

        // troncature se fait après le calcul du total
        $jours = AncienneteCalculateurHelper::tronqueDecimales($jours);

        $chargeDecimal = $this->chargeDecimalTotal[$categorie][$type];
        $jours = AncienneteCalculateurHelper::appliquerLimitesAnciennete($jours, $chargeDecimal);

        return ($jours);
    }

    /**
     * Ajoute une attribution à l'ancienneté
     * 
     * @param string $categorie doit être une des constantes de la classe Attribution
     * @param float $chargeDecimal
     * @param bool $estPO
     */
    public function add(
        string $categorie,
        float $chargeDecimal,
        bool $estPO
    ): void {

        $this->validateParameters($categorie, $chargeDecimal);

        // Note: La charge décimale est additionnée pour avoir la charge totale des différentes attributions
        if ($estPO) {
            $this->ancienneteTmp[$categorie]['PO'] = $this->joursCalendrier;
            $this->ancienneteTmp[$categorie]['PO_RAW'] = $this->joursCalendrier;
            $this->chargeDecimalTotal[$categorie]['PO'] += $chargeDecimal;
            $this->chargeDecimalTotal[$categorie]['PO_RAW'] += $chargeDecimal;
        } else {
            $this->ancienneteTmp[$categorie]['CF'] = $this->joursCalendrier;
            $this->chargeDecimalTotal[$categorie]['CF'] += $chargeDecimal;
        }

        $this->chargeDecimalTotal[$categorie]['TOTAL'] += $chargeDecimal;
    }

    private function resetChargeDecimal(): void
    {
        $this->chargeDecimalTotal = [
            Attribution::CAT_PERSONNEL_EDUCATIF => self::ANCIENNETES,
            Attribution::CAT_PERSONNEL_AUXILIAIRE => self::ANCIENNETES
        ];
    }

    private function resetancienneteTmp(): void
    {
        $this->ancienneteTmp = [
            Attribution::CAT_PERSONNEL_EDUCATIF => self::ANCIENNETES,
            Attribution::CAT_PERSONNEL_AUXILIAIRE => self::ANCIENNETES
        ];
    }

    /**
     * Valide les paramètres d'entrée
     * 
     * @param string $categorie
     * @param float $chargeDecimal
     * @return void
     * 
     * @throws \InvalidArgumentException
     */
    private function validateParameters(string $categorie, float $chargeDecimal): void
    {
        if (!array_key_exists($categorie, $this->ancienneteTotal)) {
            throw new \InvalidArgumentException('La catégorie ' . $categorie . ' n\'existe pas.');
        }
        if ($chargeDecimal < 0) {
            throw new \InvalidArgumentException('La charge décimale doit être positive.');
        }
        if ($chargeDecimal > 1) {
            throw new \InvalidArgumentException('La charge décimale doit être inférieure ou égale à 1.');
        }
    }

    /**
     * Calcule les anciennetés pour les attributions d'un événement
     * Doit être appelé pour chaque événement
     * 
     * @return void
     */
    public function calculerAnciennetes(): void
    {
        foreach ($this->ancienneteTmp as $categorie => $_) {

            $this->calculAnciennetePo($categorie);
            $this->calculAncienneteCf($categorie);

            // On ajoute les anciennetés calculées à l'ancienneté totale
            $this->ancienneteTotal[$categorie]['CF'] += $this->ancienneteTmp[$categorie]['CF'];
            $this->ancienneteTotal[$categorie]['PO'] += $this->ancienneteTmp[$categorie]['PO'];
            $this->ancienneteTotal[$categorie]['PO_RAW'] += $this->ancienneteTmp[$categorie]['PO_RAW'];
        }
    }

    private function calculAncienneteCf(string $categorie)
    {
        // Pour moins d'un mi-temps CF:
        if ($this->chargeDecimalTotal[$categorie]['CF'] < .5) {

            // - diviser par 2 le nombre de jours
            $this->ancienneteTmp[$categorie]['CF'] /= 2;

            // - l'ancienneté PO est additionnable à l'ancienneté CF
            // @todo: vérifier si la troncature est nécessaire
            $this->ancienneteTotal[$categorie]['PO_SUM'] += AncienneteCalculateurHelper::tronqueDecimales(
                $this->ancienneteTmp[$categorie]['PO']
            );
        }
    }

    private function calculAnciennetePo(string $categorie)
    {
        // Ancienneté PO Règle des 1200 jours
        $this->ancienneteTmp[$categorie]['PO'] =
            AncienneteCalculateurHelper::calculerJoursPo(
                $this->ancienneteTmp[$categorie]['PO'],
                $this->ancienneteBrutePo[$categorie]
                    + $this->ancienneteTotal[$categorie]['PO_RAW']
            );

        // Ancienneté PO - Si charge < 0.5, diviser par 2
        if ($this->chargeDecimalTotal[$categorie]['PO'] < .5) {
            $this->ancienneteTmp[$categorie]['PO'] /= 2;
        }
    }

    /**
     * Remet à zéro les variables de calcul
     * pour ne pas interférer avec le calcul de l'ancienneté suivante
     * 
     * @return void
     */
    public function resetCalculVariables(): void
    {
        $this->resetChargeDecimal();
        $this->resetancienneteTmp();
        $this->setJoursCalendrier(0);
    }

    /**
     * Applique la correction du calcul de l'ancienneté PO
     * 
     * @param string $categorie
     * @return void
     */
    private function applyCorrectionCalculPo(string $categorie): float
    {
        $corr = AncienneteCalculateurHelper::correctionCalculPo(
            $this->ancienneteTotal[$categorie]['PO_RAW'],
            $this->ancienneteBrutePo[$categorie],
            $this->ancienneteTotal[$categorie]['PO'],
            $this->chargeDecimalTotal[$categorie]['PO_RAW']
        );

        return $corr;
    }
}
