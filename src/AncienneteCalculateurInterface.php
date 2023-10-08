<?php

namespace Helip\EsahrAnciennete;

interface AncienneteCalculateurInterface
{
    public function calculer(array $evenements, ?AncienneteInterface $anciennete = null): AncienneteInterface;
}
