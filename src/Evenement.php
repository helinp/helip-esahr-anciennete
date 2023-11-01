<?php

namespace Helip\EsahrAnciennete;

use DateTime;
use InvalidArgumentException;

/**
 * Class Evenement
 * Représente un événement (document 12)
 * @package Helip\Anciennete
 */
class Evenement
{
    private DateTime $dateDebut;
    private DateTime $dateFin;
    private int $ancienneteActuellePOEducatif;
    private int $ancienneteActuellePOAuxiliaire;

    /**
     * @var Attribution[]
     */
    private array $attributions = [];

    /**
     * @param DateTime $dateDebut Représente la date de début de l'événement
     * @param DateTime $dateFin Représente la date de fin de l'événement
     * @param int $ancienneteActuellePO Ancienneté PO actuelle, essentiel pour les 1200 jours PO
     * @param Attribution[] $attributions
     */
    public function __construct(
        DateTime $dateDebut,
        DateTime $dateFin,
        array $attributions,
        int $ancienneteActuellePOEducatif = 0,
        int $ancienneteActuellePOAuxiliaire = 0
    ) {
        if ($dateDebut > $dateFin && $dateFin !== null) {
            throw new InvalidArgumentException('La date de début doit être inférieure à la date de fin.');
        }

        // check if attributions are valid
        foreach ($attributions as $attribution) {
            if (!$attribution instanceof Attribution) {
                throw new InvalidArgumentException('Les attributions doivent être des instances de la classe Attribution.');
            }
        }

        $this->dateDebut = $dateDebut;
        $this->dateFin = $dateFin;
        $this->attributions = $attributions;
        $this->ancienneteActuellePOEducatif = $ancienneteActuellePOEducatif;
        $this->ancienneteActuellePOAuxiliaire = $ancienneteActuellePOAuxiliaire;
    }

    /**
     * @return DateTime
     */
    public function getDateDebut(): DateTime
    {
        return $this->dateDebut;
    }

    /**
     * @return DateTime
     */
    public function getDateFin(): DateTime
    {
        return $this->dateFin;
    }

    /**
     * @return Attribution[]
     */
    public function getAttributions(): array
    {
        return $this->attributions;
    }

    /**
     * @return int
     */
    public function getAncienneteActuellePOEducatif(): int
    {
        return $this->ancienneteActuellePOEducatif;
    }

    /**
     * @return int
     */
    public function getAncienneteActuellePOAuxiliaire(): int
    {
        return $this->ancienneteActuellePOAuxiliaire;
    }
}
