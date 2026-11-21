<?php

namespace Helip\EsahrAnciennete;

use DateTimeImmutable;
use InvalidArgumentException;

/** Découpe les désignations aux changements d'attributions, sans modifier les entrées. */
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
                // Le calendrier ESAHR est fourni par l'appelant, pas déduit du calendrier
                // de l'enseignement obligatoire de la dépendance AnneeScolaire.
                if ($debut < "$premiere-09-01" || $fin > ($premiere + 1) . '-08-31') {
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
                if ($attributions !== []) {
                    $resultat[] = new PeriodeCalcul(
                        $annee,
                        $debut,
                        $fin->format('Y-m-d'),
                        (int) (new DateTimeImmutable($debut))->diff($fin)->days + 1,
                        array_values($attributions)
                    );
                }
            }
        }
        return $resultat;
    }
}
