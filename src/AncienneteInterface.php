<?php

namespace Helip\EsahrAnciennete;

interface AncienneteInterface
{
    public function get(string $categorie, string $type = 'TOTAL'): int;

    public function getParFonction(string $categorie, string $fonction, string $type = 'TOTAL'): int;

    /** @return list<string> */
    public function getFonctions(string $categorie): array;

    /** @return array<string, array<string, int>> */
    public function getParAnnee(string $categorie, ?string $fonction = null): array;

    public function getModeTroncature(): string;

    /** @return list<array<string, mixed>> */
    public function getTableauCalcul(?string $categorie = null, ?string $fonction = null): array;
}
