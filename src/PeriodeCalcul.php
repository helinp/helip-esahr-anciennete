<?php

namespace Helip\EsahrAnciennete;

/** Une période sans changement d'attributions, bornes incluses. @internal */
final class PeriodeCalcul
{
    /** @param list<Attribution> $attributions */
    public function __construct(
        public readonly string $anneeScolaire,
        public readonly string $debut,
        public readonly string $fin,
        public readonly int $jours,
        public readonly array $attributions
    ) {
    }
}
