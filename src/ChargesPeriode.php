<?php

namespace Helip\EsahrAnciennete;

/** @internal */
final class ChargesPeriode
{
    public float $cf = 0.0;
    public float $po = 0.0;
    public bool $presente = false;
    /** @var list<string> */
    public array $exclusions = [];

    public function coefficientCf(): float
    {
        return self::coefficient($this->cf);
    }

    public function coefficientPo(): float
    {
        return self::coefficient($this->po);
    }

    private static function coefficient(float $charge): float
    {
        return $charge <= 0 ? 0.0 : ($charge + 1e-12 >= 0.5 ? 1.0 : 0.5);
    }
}
