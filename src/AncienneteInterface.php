<?php

namespace Helip\EsahrAnciennete;

/**
 * Class AncienneteService
 * @package Helip\Anciennete
 */
interface AncienneteInterface
{
    public function add(int|float $jours, string $categorie, float $charge, bool $estPO): void;

    public function get(string $categorie, string $type): int|float;
}
