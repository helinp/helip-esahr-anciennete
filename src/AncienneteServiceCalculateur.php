<?php

namespace Helip\EsahrAnciennete;

use InvalidArgumentException;

/** Moteur commun à l'ancienneté de service et à l'ancienneté de fonction. */
final class AncienneteServiceCalculateur implements AncienneteCalculateurInterface
{
    public const TRONCATURE_PAR_FINANCEMENT = 'par_financement';
    public const TRONCATURE_GLOBALE = 'globale';

    public function __construct(private readonly string $troncature = self::TRONCATURE_PAR_FINANCEMENT)
    {
        if (!in_array($troncature, [self::TRONCATURE_PAR_FINANCEMENT, self::TRONCATURE_GLOBALE], true)) {
            throw new InvalidArgumentException('Mode de troncature inconnu.');
        }
    }

    /** @param Evenement[] $evenements */
    public function calculer(array $evenements): AncienneteService
    {
        $periodes = (new NormalisateurPeriodes())->normaliser($evenements);
        $services = $fonctions = [];
        foreach ([Attribution::CAT_PERSONNEL_EDUCATIF, Attribution::CAT_PERSONNEL_AUXILIAIRE] as $categorie) {
            $initial = $this->ancienneteInitiale($evenements, $categorie);
            $carriere = [];
            $services[$categorie] = $this->calculerPortee($periodes, $categorie, null, $initial, $carriere);
            $noms = [];
            foreach ($periodes as $periode) {
                foreach ($periode->attributions as $attribution) {
                    if ($attribution->getCategorie() === $categorie) {
                        $noms[$attribution->getFonction()] = true;
                    }
                }
            }
            ksort($noms);
            foreach (array_keys($noms) as $nom) {
                $fonction = (string) $nom;
                // Le seuil de 1200 jours appartient à la carrière de la catégorie,
                // pas à chacune des fonctions séparément.
                $historique = $carriere;
                $fonctions[$categorie][$fonction] = $this->calculerPortee(
                    $periodes,
                    $categorie,
                    $fonction,
                    $initial,
                    $historique
                );
            }
        }
        return new AncienneteService($services, $fonctions, $this->troncature);
    }

    /** L'ancienneté initiale précède tout l'historique transmis (0 = non renseigné).
     *  @param Evenement[] $evenements
     */
    private function ancienneteInitiale(array $evenements, string $categorie): int
    {
        $valeurs = [];
        foreach ($evenements as $evenement) {
            $valeur = $categorie === Attribution::CAT_PERSONNEL_EDUCATIF
                ? $evenement->getAncienneteActuellePOEducatif()
                : $evenement->getAncienneteActuellePOAuxiliaire();
            if ($valeur !== 0) {
                $valeurs[$valeur] = true;
            }
        }
        if (count($valeurs) > 1) {
            throw new InvalidArgumentException(
                'Fournir une seule ancienneté PO initiale par catégorie, avant tout l’historique.'
            );
        }
        return array_key_first($valeurs) ?? 0;
    }

    /** @param list<PeriodeCalcul> $periodes
     *  @param array<string, float> $carriere
     */
    private function calculerPortee(
        array $periodes,
        string $categorie,
        ?string $fonction,
        int $initial,
        array &$carriere
    ): BilanCalcul {
        /** @var array<string, list<array{periode: PeriodeCalcul, charges: ChargesPeriode}>> $annees */
        $annees = [];
        foreach ($periodes as $periode) {
            $annees[$periode->anneeScolaire][] = [
                'periode' => $periode,
                'charges' => $this->charges($periode, $categorie, $fonction),
            ];
        }
        $bilans = $lignes = [];
        $poCarriere = (float) $initial;
        foreach ($annees as $annee => $etapes) {
            $cfPlafond = 150.0;
            foreach ($etapes as $etape) {
                if ($etape['charges']->coefficientCf() === 1.0) {
                    $cfPlafond = 300.0;
                }
            }
            $cfAnnuel = $poAnnuel = $poBrutAnnuel = $cumulAnnuel = 0.0;
            /** @var list<array{periode: PeriodeCalcul, charges: ChargesPeriode, avant: float, reduits: float, pleins: float, cf: float, cfRetenu: float, po: float, brut: float, cumul: float}> $calculs */
            $calculs = [];
            foreach ($etapes as $etape) {
                $periode = $etape['periode'];
                $charges = $etape['charges'];
                $cle = $annee . '/' . $periode->debut;
                if ($fonction === null) {
                    $carriere[$cle] = $poCarriere;
                }
                $avant = $carriere[$cle];
                if (!$charges->presente) {
                    continue;
                }
                $cf = $periode->jours * $charges->coefficientCf();
                $cfRetenu = min($cf, max(0.0, $cfPlafond - $cfAnnuel));
                $brut = $charges->po > 0 ? min((float) $periode->jours, 300.0 - $poBrutAnnuel) : 0.0;
                $reduits = min($brut, max(0.0, 1200.0 - $avant));
                $pleins = $brut - $reduits;
                $po = ($reduits * 0.3 + $pleins) * $charges->coefficientPo();
                // CF >= mi-temps : PO non additionnable. Après 1200 jours,
                // le cumul sous le mi-temps reste réduit de moitié (p21).
                $cumul = 0.0;
                if ($charges->cf + 1e-12 < 0.5) {
                    $cumul = $po;
                    if ($charges->cf > 0 && $charges->cf + $charges->po + 1e-12 < 0.5) {
                        $cumul -= $pleins * $charges->coefficientPo();
                    } elseif ($charges->cf > 0 && $charges->coefficientPo() === 1.0) {
                        // Un jour ne peut générer plus d'un jour au total.
                        $cumul = min($reduits * 0.3, $reduits * 0.5) + $pleins * 0.5;
                    }
                }
                $calculs[] = [
                    'periode' => $periode, 'charges' => $charges, 'avant' => $avant,
                    'reduits' => $reduits, 'pleins' => $pleins,
                    'cf' => $cf, 'cfRetenu' => $cfRetenu, 'po' => $po, 'brut' => $brut, 'cumul' => $cumul,
                ];
                $cfAnnuel += $cfRetenu;
                $poAnnuel += $po;
                $poBrutAnnuel += $brut;
                $cumulAnnuel += $cumul;
                if ($fonction === null) {
                    $poCarriere += $brut;
                }
            }
            // Le plafond du total ne retire jamais l'ancienneté CF. On limite
            // seulement la part PO additionnable, indépendamment du PO autonome.
            $budgetCumul = min($cumulAnnuel, max(0.0, 300.0 - $cfAnnuel));
            $bilans[$annee] = [
                'CF' => $cfAnnuel, 'PO' => $poAnnuel, 'PO_RAW' => $poBrutAnnuel,
                'PO_SUM' => $budgetCumul, 'TOTAL' => $cfAnnuel + $budgetCumul,
            ];
            foreach ($calculs as $calcul) {
                $periode = $calcul['periode'];
                $charges = $calcul['charges'];
                $cumul = min($calcul['cumul'], $budgetCumul);
                $budgetCumul -= $cumul;
                $lignes[] = new LigneCalcul(
                    $annee,
                    $categorie,
                    $fonction,
                    $periode->debut,
                    $periode->fin,
                    $periode->jours,
                    $charges->cf,
                    $charges->po,
                    $charges->coefficientCf(),
                    $charges->coefficientPo(),
                    $calcul['avant'],
                    $calcul['reduits'],
                    $calcul['pleins'],
                    $calcul['cf'],
                    $calcul['cfRetenu'],
                    // PO avant plafond : projection sur tous les jours calendrier.
                    AncienneteCalculateurHelper::calculerJoursPo(
                        $charges->po > 0 ? $periode->jours : 0,
                        $calcul['avant']
                    ) * $charges->coefficientPo(),
                    $calcul['po'],
                    $calcul['brut'],
                    $cumul,
                    $charges->exclusions
                );
            }
        }
        return new BilanCalcul($bilans, $lignes, $this->troncature);
    }

    private function charges(PeriodeCalcul $periode, string $categorie, ?string $fonction): ChargesPeriode
    {
        $charges = new ChargesPeriode();
        foreach ($periode->attributions as $attribution) {
            if (
                $attribution->getCategorie() !== $categorie
                || ($fonction !== null && $attribution->getFonction() !== $fonction)
            ) {
                continue;
            }
            $charges->presente = true;
            $raison = $attribution->getMotifExclusion($fonction !== null);
            if ($raison !== null) {
                $charges->exclusions[] = $attribution->getFonction() . ' : ' . $raison;
            } elseif ($attribution->getEstPO()) {
                $charges->po += $attribution->getChargeDecimal();
            } else {
                $charges->cf += $attribution->getChargeDecimal();
            }
        }
        return $charges;
    }
}
