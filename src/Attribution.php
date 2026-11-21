<?php

namespace Helip\EsahrAnciennete;

use Helip\AnneeScolaire\AnneeScolaire;
use InvalidArgumentException;

class Attribution
{
    public const CAT_PERSONNEL_EDUCATIF = 'personnel directeur et enseignant';
    public const CAT_PERSONNEL_AUXILIAIRE = 'personnel auxiliaire d’éducation';

    private const SITUATIONS_VALIDES = ['D', 'S', 'T', 'I', 'ST'];
    private const CATEGORIES_VALIDES = [self::CAT_PERSONNEL_EDUCATIF, self::CAT_PERSONNEL_AUXILIAIRE];

    private int $fraction;
    private int $periodes;
    private float $chargeDecimal;
    private string $situation;
    private string $fonction;
    private string $categorie;
    private AnneeScolaire $anneeScolaire;
    private bool $estSubventionne;
    private bool $estTitreRequis;
    private bool $estPO;
    private bool $estFonctionPrincipale;
    private bool $estTitreSuffisant;
    private bool $estFonctionReglementaire;
    private bool $estEncadrementPedagogique;
    private bool $estAssimileSubventionne;

    /**
     * @param int $fraction La fraction de l'attribution
     * @param int $periodes Le nombre de périodes de l'attribution
     * @param string $situation La situation de l'attribution (D, S, T, I, ST)
     * @param string $fonction La fonction de l'attribution
     * @param string $categorie La catégorie de l'attribution (enseignement, direction, surveillant-educateur, autre)
     * @param AnneeScolaire $anneeScolaire L'année scolaire de l'attribution
     * @param bool $estSubventionne Si l'attribution est subventionnée par la CF (si congé, voir Vade-Mecum)
     * @param bool $estPO Si l'attribution est prise en charge par le PO
     * @param bool $estTitreRequis Si l'attribution est préstée en tant que titre requis
     */
    public function __construct(
        int $fraction,
        int $periodes,
        string $situation,
        string $fonction,
        string $categorie,
        AnneeScolaire $anneeScolaire,
        bool $estSubventionne,
        bool $estPO,
        bool $estTitreRequis,
        bool $estFonctionPrincipale = true,
        bool $estTitreSuffisant = true,
        bool $estFonctionReglementaire = true,
        bool $estEncadrementPedagogique = false,
        bool $estAssimileSubventionne = false
    ) {
        // Contrôles
        if ($fraction <= 0 || $periodes < 0 || $periodes > $fraction) {
            throw new InvalidArgumentException(
                'La fraction doit être positive et les périodes comprises entre zéro et cette fraction.'
            );
        }

        if (!in_array($situation, self::SITUATIONS_VALIDES)) {
            throw new InvalidArgumentException(
                'La situation doit être une des valeurs suivantes: ' . implode(', ', self::SITUATIONS_VALIDES)
            );
        }

        if (!in_array($categorie, self::CATEGORIES_VALIDES)) {
            throw new InvalidArgumentException(
                'La catégorie doit être une des valeurs suivantes: ' . implode(', ', self::CATEGORIES_VALIDES)
            );
        }

        $this->fraction = $fraction;
        $this->periodes = $periodes;
        $this->situation = $situation;
        $this->fonction = $fonction;
        $this->categorie = $categorie;
        $this->anneeScolaire = $anneeScolaire;
        $this->estSubventionne = $estSubventionne;
        $this->estPO = $estPO;
        $this->estTitreRequis = $estTitreRequis;
        $this->estFonctionPrincipale = $estFonctionPrincipale;
        $this->estTitreSuffisant = $estTitreSuffisant;
        $this->estFonctionReglementaire = $estFonctionReglementaire;
        $this->estEncadrementPedagogique = $estEncadrementPedagogique;
        $this->estAssimileSubventionne = $estAssimileSubventionne;

        $this->chargeDecimal = $this->calculerChargeDecimal();
    }

    /**
     * La fraction de l'attribution
     * Ex: 24, 36
     *
     * @return int
     */
    public function getFraction(): int
    {
        return $this->fraction;
    }

    /**
     * Le nombre de périodes de l'attribution
     * Ex: 1, 2, 3
     *
     * @return int
     */
    public function getPeriodes(): int
    {
        return $this->periodes;
    }

    public function setPeriodes(int $periodes): void
    {
        if ($periodes < 0) {
            throw new InvalidArgumentException('Le nombre de périodes ne peut pas être négatif.');
        }
        $this->periodes = $periodes;
        $this->chargeDecimal = $this->calculerChargeDecimal();
    }

    /**
     * La situation de l'attribution
     * Valeurs: self::SITUATIONS_VALIDES
     *
     * @return string
     */
    public function getSituation(): string
    {
        return $this->situation;
    }

    /**
     * La fonction de l'attribution
     *
     * @return string
     */
    public function getFonction(): string
    {
        return $this->fonction;
    }

    /**
     * La catégorie de l'attribution
     * Valeurs: self::CATEGORIES_VALIDES
     *
     * @return string
     */
    public function getCategorie(): string
    {
        return $this->categorie;
    }

    /**
     * L'année scolaire de l'attribution
     *
     * @return AnneeScolaire
     */
    public function getAnneeScolaire(): AnneeScolaire
    {
        return $this->anneeScolaire;
    }

    /**
     * Si l'attribution est subventionnée par la CF
     *
     * @return bool
     */
    public function getEstSubventionne(): bool
    {
        return $this->estSubventionne;
    }

    /**
     * Si l'attribution est prise en charge par le PO
     *
     * @return bool
     */
    public function getEstPO(): bool
    {
        return $this->estPO;
    }

    /**
     * Si l'attribution est prestée en tant que titre requis
     *
     * @return bool
     */
    public function getEstTitreRequis(): bool
    {
        return $this->estTitreRequis;
    }

    public function setChargeDecimal(float $chargeDecimal): void
    {
        if (!is_finite($chargeDecimal) || $chargeDecimal < 0) {
            throw new InvalidArgumentException('La charge doit être un nombre positif fini.');
        }
        $this->chargeDecimal = $chargeDecimal;
    }

    /** null signifie admissible. La qualification des congés reste à charge de l'appelant. */
    public function getMotifExclusion(bool $pourFonction = false): ?string
    {
        if ($this->periodes === 0) {
            return 'aucune prestation';
        }
        if (!$this->estFonctionPrincipale) {
            return 'fonction accessoire';
        }
        if (!$this->estFonctionReglementaire) {
            return 'fonction non réglementaire';
        }
        if (!$this->estTitreRequis && ($this->estPO || !$this->estTitreSuffisant)) {
            return $this->estPO ? 'titre requis obligatoire pour les services PO' : 'absence de titre de capacité';
        }
        if (!$this->estPO && !$this->estSubventionne && !$this->estAssimileSubventionne) {
            return 'service non subventionné et non assimilé';
        }
        if ($pourFonction && $this->estEncadrementPedagogique) {
            return 'encadrement pédagogique : ancienneté de service uniquement';
        }
        return null;
    }

    /**
     * Empreinte des valeurs qui influencent le calcul (et l'affichage du détail).
     * Deux attributions de même signature sont interchangeables : des périodes
     * consécutives portant les mêmes signatures peuvent être regroupées.
     */
    public function getSignature(): string
    {
        return implode('|', [
            $this->categorie,
            $this->fonction,
            $this->anneeScolaire->getAnneeScolaire(),
            $this->situation,
            $this->fraction,
            $this->periodes,
            sprintf('%.12F', $this->chargeDecimal),
            (int) $this->estSubventionne,
            (int) $this->estPO,
            (int) $this->estTitreRequis,
            (int) $this->estFonctionPrincipale,
            (int) $this->estTitreSuffisant,
            (int) $this->estFonctionReglementaire,
            (int) $this->estEncadrementPedagogique,
            (int) $this->estAssimileSubventionne,
        ]);
    }

    /**
     * La charge de l'attribution en décimal
     *
     * @return float
     */
    public function getChargeDecimal(): float
    {
        return $this->chargeDecimal;
    }


    /**
     * La charge de l'attribution en décimal
     *
     * @return float
     */
    private function calculerChargeDecimal(): float
    {
        return $this->periodes / $this->fraction;
    }
}
