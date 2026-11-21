<?php

namespace Helip\EsahrAnciennete;

use DateTimeImmutable;
use Helip\AnneeScolaire\AnneeScolaire;
use InvalidArgumentException;

/**
 * Découpe les désignations aux changements d'attributions, sans modifier les entrées.
 * Les périodes contiguës d'une même année dont les attributions sont identiques
 * (même signature) sont regroupées.
 */
final class NormalisateurPeriodes
{
    /** @param Evenement[] $evenements
     *  @return list<PeriodeCalcul>
     */
    public function normaliser(array $evenements): array
    {
        /** @var array<string, list<array{debut: string, fin: string, attribution: Attribution}>> $annees */
        $annees = [];
        foreach ($evenements as $evenement) {
            if (!$evenement instanceof Evenement) {
                throw new InvalidArgumentException('Les événements doivent être des instances de Evenement.');
            }
            $debut = $evenement->getDateDebut()->format('Y-m-d');
            $fin = $evenement->getDateFin()->format('Y-m-d');
            foreach ($evenement->getAttributions() as $attribution) {
                $annee = $attribution->getAnneeScolaire()->getAnneeScolaire();
                $premiere = (int) substr($annee, 0, 4);
                // Conserver le rattachement historique au 1er septembre. Depuis
                // 2022, l'ESAHR à 37 semaines suit la rentrée calculée par la lib.
                // Inclure l'été jusqu'à la veille de la rentrée suivante.
                $rentree = $premiere < 2022
                    ? "$premiere-09-01"
                    : $attribution->getAnneeScolaire()->getDateDebut()->format('Y-m-d');
                $suivante = $premiere + 1;
                $rentreeSuivante = $suivante < 2022
                    ? "$suivante-09-01"
                    : (new AnneeScolaire("$suivante-" . ($suivante + 1)))->getDateDebut()->format('Y-m-d');
                if ($debut < $rentree || $fin >= $rentreeSuivante) {
                    throw new InvalidArgumentException(
                        'Les dates doivent appartenir à l’année ESAHR de chaque attribution.'
                    );
                }
                $annees[$annee][] = ['debut' => $debut, 'fin' => $fin, 'attribution' => $attribution];
            }
        }
        ksort($annees);
        $resultat = [];
        foreach ($annees as $annee => $designations) {
            $bornes = [];
            foreach ($designations as $designation) {
                $bornes[$designation['debut']] = true;
                $bornes[(new DateTimeImmutable($designation['fin']))->modify('+1 day')->format('Y-m-d')] = true;
            }
            $dates = array_keys($bornes);
            sort($dates);
            for ($i = 0; $i < count($dates) - 1; ++$i) {
                $debut = $dates[$i];
                $fin = (new DateTimeImmutable($dates[$i + 1]))->modify('-1 day');
                $attributions = [];
                foreach ($designations as $designation) {
                    if ($designation['debut'] <= $debut && $designation['fin'] >= $debut) {
                        // Une même attribution répétée dans des événements qui se recouvrent
                        // ne doit pas être ajoutée deux fois.
                        $attributions[spl_object_id($designation['attribution'])] = $designation['attribution'];
                    }
                }
                if ($attributions === []) {
                    continue;
                }
                $attributions = array_values($attributions);
                $precedente = $resultat === [] ? null : $resultat[count($resultat) - 1];
                if (
                    $precedente !== null
                    && $precedente->anneeScolaire === (string) $annee
                    && self::lendemain($precedente->fin) === $debut
                    && self::signatures($precedente->attributions) === self::signatures($attributions)
                ) {
                    // Attributions identiques qui se suivent (ex. : désignation reconduite
                    // par un nouvel événement) : une seule période, mêmes résultats.
                    $resultat[count($resultat) - 1] = new PeriodeCalcul(
                        $precedente->anneeScolaire,
                        $precedente->debut,
                        $fin->format('Y-m-d'),
                        self::jours($precedente->debut, $fin),
                        $precedente->attributions
                    );
                    continue;
                }
                $resultat[] = new PeriodeCalcul(
                    (string) $annee,
                    $debut,
                    $fin->format('Y-m-d'),
                    self::jours($debut, $fin),
                    $attributions
                );
            }
        }
        return $resultat;
    }

    private static function lendemain(string $date): string
    {
        return (new DateTimeImmutable($date))->modify('+1 day')->format('Y-m-d');
    }

    private static function jours(string $debut, DateTimeImmutable $fin): int
    {
        return (int) (new DateTimeImmutable($debut))->diff($fin)->days + 1;
    }

    /** @param list<Attribution> $attributions
     *  @return list<string>
     */
    private static function signatures(array $attributions): array
    {
        $signatures = array_map(static fn (Attribution $a): string => $a->getSignature(), $attributions);
        sort($signatures);
        return $signatures;
    }
}
