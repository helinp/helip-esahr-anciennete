<?php

namespace Helip\EsahrAnciennete;

/** Valeurs non tronquées d'une étape, directement exportables vers un tableau. */
final class LigneCalcul
{
    /** @param list<string> $exclusions */
    public function __construct(
        public readonly string $anneeScolaire,
        public readonly string $categorie,
        public readonly ?string $fonction,
        public readonly string $debut,
        public readonly string $fin,
        public readonly int $joursCalendrier,
        public readonly float $chargeCf,
        public readonly float $chargePo,
        public readonly float $coefficientCf,
        public readonly float $coefficientPo,
        public readonly float $anciennetePoAvant,
        public readonly float $joursPoReduits,
        public readonly float $joursPoNonReduits,
        public readonly float $cfAvantPlafond,
        public readonly float $cfRetenu,
        public readonly float $poAvantPlafond,
        public readonly float $poRetenu,
        public readonly float $poBrutRetenu,
        public readonly float $poCumulable,
        public readonly array $exclusions
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'portee' => $this->fonction === null ? 'service' : 'fonction',
            'annee_scolaire' => $this->anneeScolaire,
            'categorie' => $this->categorie,
            'fonction' => $this->fonction,
            'debut' => $this->debut,
            'fin' => $this->fin,
            'jours_calendrier' => $this->joursCalendrier,
            'charge_cf' => $this->chargeCf,
            'charge_po' => $this->chargePo,
            'coefficient_charge_cf' => $this->coefficientCf,
            'coefficient_charge_po' => $this->coefficientPo,
            'anciennete_po_avant' => $this->anciennetePoAvant,
            'jours_po_coefficient_03' => $this->joursPoReduits,
            'jours_po_coefficient_1' => $this->joursPoNonReduits,
            'cf_avant_plafond' => $this->cfAvantPlafond,
            'cf_retenu' => $this->cfRetenu,
            'po_avant_plafond' => $this->poAvantPlafond,
            'po_retenu' => $this->poRetenu,
            'po_brut_retenu' => $this->poBrutRetenu,
            'po_cumulable' => $this->poCumulable,
            'contribution_totale' => $this->cfRetenu + $this->poCumulable,
            'exclusions' => $this->exclusions,
        ];
    }
}
