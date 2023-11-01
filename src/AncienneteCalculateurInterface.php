<?php

namespace Helip\EsahrAnciennete;

interface AncienneteCalculateurInterface
{
    /**
     * Calcule l'ancienneté d'un enseignant
     * 
     * @param Evenement[] $evenements
     * @return AncienneteInterface
     */
    public function calculer(array $evenements): AncienneteInterface;
}
