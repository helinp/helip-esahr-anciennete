<?php

namespace Tests;

use DateTime;
use Helip\AnneeScolaire\AnneeScolaire;
use Helip\EsahrAnciennete\AncienneteServiceCalculateur;
use Helip\EsahrAnciennete\Attribution;
use Helip\EsahrAnciennete\Evenement;
use Helip\EsahrAnciennete\NormalisateurPeriodes;
use PHPUnit\Framework\TestCase;

final class RegroupementPeriodesTest extends TestCase
{
    private const CAT = Attribution::CAT_PERSONNEL_EDUCATIF;

    private function attribution(array $options = []): Attribution
    {
        return new Attribution(...array_replace([
            'fraction' => 24, 'periodes' => 12, 'situation' => 'T', 'fonction' => 'Violon',
            'categorie' => self::CAT, 'anneeScolaire' => new AnneeScolaire('2020-2021'),
            'estSubventionne' => true, 'estPO' => false, 'estTitreRequis' => true,
        ], $options));
    }

    private function evenement(string $debut, string $fin, array $attributions): Evenement
    {
        return new Evenement(new DateTime($debut), new DateTime($fin), $attributions);
    }

    public function testAttributionsIdentiquesQuiSeSuiventSontRegroupees(): void
    {
        $evenements = [
            $this->evenement('2020-09-01', '2020-09-30', [$this->attribution(), $this->attribution(['fonction' => 'Piano', 'periodes' => 2])]),
            // Mêmes valeurs, autres instances et autre ordre.
            $this->evenement('2020-10-01', '2020-12-31', [$this->attribution(['fonction' => 'Piano', 'periodes' => 2]), $this->attribution()]),
        ];
        $periodes = (new NormalisateurPeriodes())->normaliser($evenements);
        self::assertCount(1, $periodes);
        self::assertSame('2020-09-01', $periodes[0]->debut);
        self::assertSame('2020-12-31', $periodes[0]->fin);
        self::assertSame(122, $periodes[0]->jours);
        self::assertCount(2, $periodes[0]->attributions);

        $resultat = (new AncienneteServiceCalculateur())->calculer($evenements);
        self::assertCount(1, $resultat->getTableauCalcul(self::CAT));
        self::assertSame(122, $resultat->get(self::CAT));
        self::assertSame(61, $resultat->getParFonction(self::CAT, 'Piano'));
    }

    public function testChangementDeChargeOuInterruptionNeRegroupePas(): void
    {
        $periodes = (new NormalisateurPeriodes())->normaliser([
            $this->evenement('2020-09-01', '2020-09-30', [$this->attribution()]),
            $this->evenement('2020-10-01', '2020-10-31', [$this->attribution(['periodes' => 10])]),
            $this->evenement('2020-11-02', '2020-11-30', [$this->attribution(['periodes' => 10])]),
        ]);
        self::assertSame(
            [['2020-09-01', '2020-09-30'], ['2020-10-01', '2020-10-31'], ['2020-11-02', '2020-11-30']],
            array_map(static fn ($p) => [$p->debut, $p->fin], $periodes)
        );
    }

    public function testPasDeRegroupementEntreDeuxAnnees(): void
    {
        $periodes = (new NormalisateurPeriodes())->normaliser([
            $this->evenement('2021-08-01', '2021-08-31', [$this->attribution()]),
            $this->evenement('2021-09-01', '2021-09-30', [$this->attribution(['anneeScolaire' => new AnneeScolaire('2021-2022')])]),
        ]);
        self::assertCount(2, $periodes);
    }

    public function testRegroupementSansEffetSurLesResultats(): void
    {
        // Mêmes services décrits en un ou plusieurs événements consécutifs,
        // avec du PO franchissant le seuil des 1 200 jours et le plafond annuel.
        $po = fn () => $this->attribution(['estPO' => true, 'estSubventionne' => false, 'periodes' => 6]);
        $cf = fn () => $this->attribution(['periodes' => 4]);
        $morceaux = [];
        foreach ([['2020-09-01', '2020-10-15'], ['2020-10-16', '2021-01-31'], ['2021-02-01', '2021-08-31']] as [$d, $f]) {
            $morceaux[] = new Evenement(new DateTime($d), new DateTime($f), [$po(), $cf()], 1100);
        }
        $entier = [new Evenement(new DateTime('2020-09-01'), new DateTime('2021-08-31'), [$po(), $cf()], 1100)];
        $moteur = new AncienneteServiceCalculateur();
        $a = $moteur->calculer($morceaux);
        $b = $moteur->calculer($entier);
        foreach (['CF', 'PO', 'PO_RAW', 'PO_SUM', 'TOTAL'] as $type) {
            self::assertSame($b->get(self::CAT, $type), $a->get(self::CAT, $type), $type);
        }
        self::assertEquals($b->getTableauCalcul(self::CAT), $a->getTableauCalcul(self::CAT));
    }
}
