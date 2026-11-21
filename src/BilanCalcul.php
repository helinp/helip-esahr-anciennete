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
        $jours = 0;
        foreach ($this->getParAnnee() as $annee) {
            $jours += $annee[$type];
        }
        return $jours;
    }

    private static function tronquer(float $jours): int
    {
        // Les coefficients 0,3 et les sommes de charges produisent des erreurs binaires.
        return (int) floor(round($jours, 9));
    }

    /**
     * Résultats tronqués pour chaque année, avant cumul de carrière.
     * @return array<string, array<string, int>>
     */
    public function getParAnnee(): array
    {
        $resultat = [];
        foreach ($this->annees as $annee => $valeurs) {
            $annuel = array_map(self::tronquer(...), $valeurs);
            $annuel['TOTAL'] = $this->troncature === AncienneteServiceCalculateur::TRONCATURE_PAR_FINANCEMENT
                ? $annuel['CF'] + $annuel['PO_SUM']
                : self::tronquer($valeurs['CF'] + $valeurs['PO_SUM']);
            $resultat[$annee] = $annuel;
        }
        return $resultat;
    }

    /** @return list<array<string, mixed>> */
    public function getTableauCalcul(): array
    {
        return array_map(static fn (LigneCalcul $ligne): array => $ligne->toArray(), $this->lignes);
    }
}
