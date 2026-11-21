<?php

namespace Helip\EsahrAnciennete;

use InvalidArgumentException;

/** Résultat immuable : les getters n'effectuent aucune correction de calcul. */
final class AncienneteService implements AncienneteInterface
{
    /** @param array<string, BilanCalcul> $services
     *  @param array<string, array<string, BilanCalcul>> $fonctions
     */
    public function __construct(
        private readonly array $services = [],
        private readonly array $fonctions = [],
        private readonly string $troncature = AncienneteServiceCalculateur::TRONCATURE_PAR_FINANCEMENT
    ) {
    }

    public function getModeTroncature(): string
    {
        return $this->troncature;
    }

    public function get(string $categorie, string $type = 'TOTAL'): int
    {
        $this->validerCategorie($categorie);
        return ($this->services[$categorie] ?? new BilanCalcul())->get($type);
    }

    public function getParFonction(string $categorie, string $fonction, string $type = 'TOTAL'): int
    {
        $this->validerCategorie($categorie);
        if (!isset($this->fonctions[$categorie][$fonction])) {
            throw new InvalidArgumentException('Fonction inconnue dans cette catégorie : ' . $fonction);
        }
        return $this->fonctions[$categorie][$fonction]->get($type);
    }

    /** @return list<string> */
    public function getFonctions(string $categorie): array
    {
        $this->validerCategorie($categorie);
        return array_map('strval', array_keys($this->fonctions[$categorie] ?? []));
    }

    /** @return array<string, array<string, float>> */
    public function getParAnnee(string $categorie, ?string $fonction = null): array
    {
        $this->validerCategorie($categorie);
        return $this->bilan($categorie, $fonction)->getParAnnee();
    }

    /** Sans filtre de fonction : tableau du service. Avec une fonction : son propre calcul.
     *  @return list<array<string, mixed>>
     */
    public function getTableauCalcul(?string $categorie = null, ?string $fonction = null): array
    {
        $categories = $categorie === null
            ? [Attribution::CAT_PERSONNEL_EDUCATIF, Attribution::CAT_PERSONNEL_AUXILIAIRE]
            : [$categorie];
        $lignes = [];
        foreach ($categories as $cat) {
            $this->validerCategorie($cat);
            if ($fonction !== null && !isset($this->fonctions[$cat][$fonction])) {
                if ($categorie !== null) {
                    throw new InvalidArgumentException('Fonction inconnue dans cette catégorie : ' . $fonction);
                }
                continue;
            }
            array_push($lignes, ...$this->bilan($cat, $fonction)->getTableauCalcul());
        }
        return $lignes;
    }

    private function bilan(string $categorie, ?string $fonction): BilanCalcul
    {
        if ($fonction !== null) {
            if (!isset($this->fonctions[$categorie][$fonction])) {
                throw new InvalidArgumentException('Fonction inconnue dans cette catégorie : ' . $fonction);
            }
            return $this->fonctions[$categorie][$fonction];
        }
        return $this->services[$categorie] ?? new BilanCalcul();
    }

    private function validerCategorie(string $categorie): void
    {
        if (!in_array($categorie, [Attribution::CAT_PERSONNEL_EDUCATIF, Attribution::CAT_PERSONNEL_AUXILIAIRE], true)) {
            throw new InvalidArgumentException('Catégorie inconnue : ' . $categorie);
        }
    }
}
