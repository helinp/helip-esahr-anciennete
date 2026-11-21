<?php

namespace Helip\EsahrAnciennete;

use InvalidArgumentException;

/** Résultat d'une catégorie, toutes fonctions confondues ou pour une seule fonction. */
final class BilanCalcul
{
    /** @param array<string, array<string, float>> $annees
     *  @param list<LigneCalcul> $lignes
     */
    public function __construct(
        private readonly array $annees = [],
        private readonly array $lignes = [],
        private readonly string $troncature = AncienneteServiceCalculateur::TRONCATURE_PAR_FINANCEMENT
    ) {
    }

    public function get(string $type = 'TOTAL'): int
    {
        if (!in_array($type, ['CF', 'PO', 'PO_RAW', 'PO_SUM', 'TOTAL'], true)) {
            throw new InvalidArgumentException('Type d’ancienneté inconnu : ' . $type);
        }
        $cf = $po = 0.0;
        foreach ($this->annees as $annee) {
            if ($type === 'TOTAL') {
                $cf += $annee['CF'];
                $po += $annee['PO_SUM'];
            } else {
                $cf += $annee[$type];
            }
        }
        if ($type === 'TOTAL' && $this->troncature === AncienneteServiceCalculateur::TRONCATURE_PAR_FINANCEMENT) {
            return self::tronquer($cf) + self::tronquer($po);
        }
        return self::tronquer($cf + $po);
    }

    private static function tronquer(float $jours): int
    {
        // Les coefficients 0,3 et les sommes de charges produisent des erreurs binaires.
        return (int) floor(round($jours, 9));
    }

    /**
     * Valeurs annuelles avant troncature finale.
     * @return array<string, array<string, float>>
     */
    public function getParAnnee(): array
    {
        return $this->annees;
    }

    /** @return list<array<string, mixed>> */
    public function getTableauCalcul(): array
    {
        return array_map(static fn (LigneCalcul $ligne): array => $ligne->toArray(), $this->lignes);
    }
}
