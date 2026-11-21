<?php

namespace Tests;

use DateTime;
use Helip\AnneeScolaire\AnneeScolaire;
use Helip\EsahrAnciennete\AncienneteService;
use Helip\EsahrAnciennete\AncienneteCalculateurHelper;
use Helip\EsahrAnciennete\AncienneteServiceCalculateur;
use Helip\EsahrAnciennete\Attribution;
use Helip\EsahrAnciennete\Evenement;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AncienneteDetailTest extends TestCase
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

    private function evenement(string $debut, string $fin, array $attributions, int $initial = 0): Evenement
    {
        return new Evenement(new DateTime($debut), new DateTime($fin), $attributions, $initial);
    }

    private function exemplePage14(): AncienneteService
    {
        return (new AncienneteServiceCalculateur())->calculer([
            $this->evenement('2020-09-01', '2020-12-01', [
                $this->attribution(),
                $this->attribution(['fonction' => 'Musique de chambre', 'periodes' => 6]),
                $this->attribution(['fonction' => 'Ensemble', 'periodes' => 2]),
            ]),
        ]);
    }

    public function testPage14AnciennetesDeFonctionDistinctesDuService(): void
    {
        $resultat = $this->exemplePage14();
        self::assertSame(92, $resultat->get(self::CAT));
        self::assertSame(92, $resultat->getParFonction(self::CAT, 'Violon'));
        self::assertSame(46, $resultat->getParFonction(self::CAT, 'Musique de chambre'));
        self::assertSame(46, $resultat->getParFonction(self::CAT, 'Ensemble'));
        self::assertSame(['Ensemble', 'Musique de chambre', 'Violon'], $resultat->getFonctions(self::CAT));
    }

    public function testTableauServiceEtFonctionExpliquentDesChargesDifferentes(): void
    {
        $resultat = $this->exemplePage14();
        $service = $resultat->getTableauCalcul(self::CAT)[0];
        $fonction = $resultat->getTableauCalcul(self::CAT, 'Ensemble')[0];
        self::assertSame('service', $service['portee']);
        self::assertEqualsWithDelta(20 / 24, $service['charge_cf'], 1e-12);
        self::assertSame(1.0, $service['coefficient_charge_cf']);
        self::assertSame(92.0, $service['cf_retenu']);
        self::assertSame('fonction', $fonction['portee']);
        self::assertSame('Ensemble', $fonction['fonction']);
        self::assertSame(0.5, $fonction['coefficient_charge_cf']);
        self::assertSame(46.0, $fonction['cf_retenu']);
    }

    public function testSeuilPoPartageEntreFonctionsEtVisibleDansLeTableau(): void
    {
        $resultat = (new AncienneteServiceCalculateur())->calculer([
            $this->evenement('2020-09-01', '2020-09-10', [$this->attribution(['estPO' => true])], 1195),
            $this->evenement('2020-09-11', '2020-09-11', [
                $this->attribution(['estPO' => true, 'fonction' => 'Piano']),
            ]),
        ]);
        $lignes = $resultat->getTableauCalcul(self::CAT);
        self::assertSame(1195.0, $lignes[0]['anciennete_po_avant']);
        self::assertSame(5.0, $lignes[0]['jours_po_coefficient_03']);
        self::assertSame(5.0, $lignes[0]['jours_po_coefficient_1']);
        self::assertSame(6.5, $lignes[0]['po_retenu']);
        self::assertSame(1205.0, $lignes[1]['anciennete_po_avant']);
        self::assertSame(1, $resultat->getParFonction(self::CAT, 'Piano', 'PO'));
        self::assertSame(7, $resultat->get(self::CAT, 'PO'));
    }

    public function testSeuilPoNeTraversePasLesCategories(): void
    {
        $resultat = (new AncienneteServiceCalculateur())->calculer([
            $this->evenement('2020-09-01', '2020-09-10', [
                $this->attribution(['estPO' => true]),
                $this->attribution(['estPO' => true, 'categorie' => Attribution::CAT_PERSONNEL_AUXILIAIRE]),
            ], 1200),
        ]);
        self::assertSame(10, $resultat->get(self::CAT, 'PO'));
        self::assertSame(3, $resultat->get(Attribution::CAT_PERSONNEL_AUXILIAIRE, 'PO'));
    }

    public static function exclusions(): iterable
    {
        yield 'fonction accessoire' => [['estFonctionPrincipale' => false], 'fonction accessoire'];
        yield 'sans titre' => [['estTitreRequis' => false, 'estTitreSuffisant' => false], 'absence de titre'];
        yield 'non reglementaire' => [['estFonctionReglementaire' => false], 'non réglementaire'];
        yield 'PO titre suffisant' => [['estPO' => true, 'estTitreRequis' => false], 'titre requis obligatoire'];
        yield 'non subventionne' => [['estSubventionne' => false], 'non subventionné'];
    }

    #[DataProvider('exclusions')]
    public function testExclusionsSontExpliqueesSansGenererDeJours(array $options, string $raison): void
    {
        $resultat = (new AncienneteServiceCalculateur())->calculer([
            $this->evenement('2020-09-01', '2020-09-30', [$this->attribution($options)]),
        ]);
        self::assertSame(0, $resultat->get(self::CAT));
        self::assertSame(0, $resultat->getParFonction(self::CAT, 'Violon'));
        self::assertStringContainsString($raison, $resultat->getTableauCalcul(self::CAT)[0]['exclusions'][0]);
    }

    public function testEncadrementPedagogiqueGenereUniquementDuService(): void
    {
        $resultat = (new AncienneteServiceCalculateur())->calculer([
            $this->evenement('2020-09-01', '2020-09-30', [
                $this->attribution(['estEncadrementPedagogique' => true]),
            ]),
        ]);
        self::assertSame(30, $resultat->get(self::CAT));
        self::assertSame(0, $resultat->getParFonction(self::CAT, 'Violon'));
        self::assertStringContainsString('encadrement pédagogique', $resultat->getTableauCalcul(self::CAT, 'Violon')[0]['exclusions'][0]);
    }

    public function testCongeAssimileNonSubventionneResteValorise(): void
    {
        $resultat = (new AncienneteServiceCalculateur())->calculer([
            $this->evenement('2020-09-01', '2020-09-30', [
                $this->attribution(['estSubventionne' => false, 'estAssimileSubventionne' => true]),
            ]),
        ]);
        self::assertSame(30, $resultat->get(self::CAT));
        self::assertSame([], $resultat->getTableauCalcul(self::CAT)[0]['exclusions']);
    }

    public function testChevauchementPartielAdditionneLesChargesSurLaSeuleIntersection(): void
    {
        $resultat = (new AncienneteServiceCalculateur())->calculer([
            $this->evenement('2020-09-01', '2020-09-20', [$this->attribution(['periodes' => 6])]),
            $this->evenement('2020-09-11', '2020-09-30', [
                $this->attribution(['periodes' => 6, 'fonction' => 'Piano']),
            ]),
        ]);
        self::assertSame(20, $resultat->get(self::CAT)); // 10/2 + 10 + 10/2.
        self::assertSame([5.0, 10.0, 5.0], array_column($resultat->getTableauCalcul(self::CAT), 'cf_retenu'));
        self::assertSame(10, $resultat->getParFonction(self::CAT, 'Violon'));
        self::assertSame(10, $resultat->getParFonction(self::CAT, 'Piano'));
    }

    public function testDecoupageEtOrdreNeChangentPasLeCalculPo(): void
    {
        $attribution = $this->attribution(['estPO' => true]);
        $moteur = new AncienneteServiceCalculateur();
        $entier = $moteur->calculer([$this->evenement('2020-09-01', '2021-06-30', [$attribution], 1100)]);
        $decoupe = $moteur->calculer([
            $this->evenement('2021-02-01', '2021-06-30', [$attribution], 1100),
            $this->evenement('2020-09-01', '2021-01-31', [$attribution], 1100),
        ]);
        foreach (['PO', 'PO_SUM', 'PO_RAW', 'TOTAL'] as $type) {
            self::assertSame($entier->get(self::CAT, $type), $decoupe->get(self::CAT, $type));
        }
        self::assertSame(230, $decoupe->get(self::CAT, 'PO'));
        self::assertSame(300.0, array_sum(array_column($decoupe->getTableauCalcul(self::CAT), 'po_brut_retenu')));
    }

    public function testFonctionMultiannuelleEtTableauSeReconcilientAvecLesBilans(): void
    {
        $resultat = (new AncienneteServiceCalculateur())->calculer([
            $this->evenement('2019-09-01', '2020-06-30', [
                $this->attribution(['anneeScolaire' => new AnneeScolaire('2019-2020')]),
            ]),
            $this->evenement('2020-09-01', '2021-06-30', [$this->attribution()]),
        ]);
        self::assertSame(600, $resultat->getParFonction(self::CAT, 'Violon'));
        self::assertSame(600.0, array_sum(array_column($resultat->getTableauCalcul(self::CAT), 'cf_retenu')));
        self::assertSame(300.0, $resultat->getParAnnee(self::CAT)['2020-2021']['TOTAL']);
        self::assertSame(300.0, $resultat->getParAnnee(self::CAT, 'Violon')['2019-2020']['TOTAL']);
        // Un appelant peut modifier sa copie du tableau sans modifier le résultat.
        $copie = $resultat->getTableauCalcul(self::CAT);
        $copie[0]['cf_retenu'] = -100;
        self::assertSame(600, $resultat->get(self::CAT));
    }

    public static function modesTroncature(): iterable
    {
        yield 'exemple explicite page19' => [AncienneteServiceCalculateur::TRONCATURE_PAR_FINANCEMENT, 248];
        yield 'regle generale pages15-16' => [AncienneteServiceCalculateur::TRONCATURE_GLOBALE, 249];
    }

    #[DataProvider('modesTroncature')]
    public function testPage19ModeDeTroncatureExplicite(string $mode, int $attendu): void
    {
        $po = $this->attribution(['estPO' => true, 'estSubventionne' => false, 'periodes' => 2]);
        $resultat = (new AncienneteServiceCalculateur($mode))->calculer([
            $this->evenement('2020-09-01', '2021-01-31', [$po, $this->attribution(['periodes' => 9])]),
            $this->evenement('2021-02-01', '2021-06-30', [$po, $this->attribution()]),
        ]);
        self::assertSame($attendu, $resultat->get(self::CAT));
        self::assertSame($attendu, $resultat->getParFonction(self::CAT, 'Violon'));
        self::assertEqualsWithDelta(249.45, array_sum(array_column($resultat->getTableauCalcul(self::CAT), 'contribution_totale')), 1e-9);
        self::assertEqualsWithDelta(22.95, $resultat->getParAnnee(self::CAT)['2020-2021']['PO_SUM'], 1e-9);
    }

    public function testPlafondDuCumulApparaitDansLesLignes(): void
    {
        $resultat = (new AncienneteServiceCalculateur())->calculer([
            $this->evenement('2020-09-01', '2021-06-30', [
                $this->attribution(['periodes' => 6]), $this->attribution(['estPO' => true]),
            ], 1200),
        ]);
        $ligne = $resultat->getTableauCalcul(self::CAT)[0];
        self::assertSame(150.0, $ligne['cf_retenu']);
        self::assertSame(300.0, $ligne['po_retenu']);
        self::assertSame(150.0, $ligne['po_cumulable']);
        self::assertSame(300.0, $ligne['contribution_totale']);
    }

    public function testResultatVideEtReutilisationDuMoteur(): void
    {
        $moteur = new AncienneteServiceCalculateur();
        $moteur->calculer([$this->evenement('2020-09-01', '2020-09-30', [$this->attribution()])]);
        $vide = $moteur->calculer([]);
        self::assertSame(0, $vide->get(self::CAT));
        self::assertSame([], $vide->getTableauCalcul());
        self::assertSame([], $vide->getFonctions(self::CAT));
    }

    public function testHistoriqueInitialIncoherentEstRefuse(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new AncienneteServiceCalculateur())->calculer([
            $this->evenement('2020-09-01', '2020-09-10', [$this->attribution()], 1100),
            $this->evenement('2020-09-11', '2020-09-20', [$this->attribution()], 1110),
        ]);
    }

    public function testAnneeScolaireIncoherenteEstRefusee(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new AncienneteServiceCalculateur())->calculer([
            $this->evenement('2019-09-01', '2020-06-30', [$this->attribution()]),
        ]);
    }

    public function testDatesEntreesEtResultatSontProtegesDesMutations(): void
    {
        $date = new DateTime('2020-09-01 12:00:00');
        $evenement = new Evenement($date, new DateTime('2020-09-30'), [$this->attribution()]);
        $date->modify('+1 year');
        $evenement->getDateDebut()->modify('+1 year');
        $resultat = (new AncienneteServiceCalculateur())->calculer([$evenement]);
        self::assertSame(30, $resultat->get(self::CAT));
        $evenement->getAttributions()[0]->setPeriodes(1);
        self::assertSame(30, $resultat->get(self::CAT));
    }

    public function testFonctionInconnueEstSignalee(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->exemplePage14()->getParFonction(self::CAT, 'Fonction absente');
    }

    public static function changementsChargePo(): iterable
    {
        yield 'baisse apres janvier' => [12, 6, 156, 156.5];
        yield 'hausse apres janvier' => [6, 12, 188, 188.5];
    }

    #[DataProvider('changementsChargePo')]
    public function testPoChargeVariableEtPlafondChronologique(int $avant, int $apres, int $attendu, float $brut): void
    {
        $resultat = (new AncienneteServiceCalculateur())->calculer([
            $this->evenement('2020-09-01', '2021-01-31', [
                $this->attribution(['estPO' => true, 'periodes' => $avant]),
            ], 1100),
            $this->evenement('2021-02-01', '2021-06-30', [
                $this->attribution(['estPO' => true, 'periodes' => $apres]),
            ]),
        ]);
        // 153 jours bruts sur la première période, puis 147 retenus sur 150 :
        // (100*0,3 + 53)*coefficient1 + 147*coefficient2.
        self::assertSame($attendu, $resultat->get(self::CAT, 'PO'));
        self::assertSame($attendu, $resultat->getParFonction(self::CAT, 'Violon', 'PO'));
        self::assertEqualsWithDelta($brut, array_sum(array_column($resultat->getTableauCalcul(self::CAT), 'po_retenu')), 1e-9);
        self::assertSame([100.0, 0.0], array_column($resultat->getTableauCalcul(self::CAT), 'jours_po_coefficient_03'));
        self::assertSame([153.0, 147.0], array_column($resultat->getTableauCalcul(self::CAT), 'po_brut_retenu'));
    }

    public function testRegroupementHistoriqueNeModifiePasLesAttributions(): void
    {
        $a = $this->attribution(['estPO' => true, 'periodes' => 6]);
        $b = $this->attribution(['estPO' => true, 'periodes' => 6]);
        $groupes = AncienneteCalculateurHelper::groupeParCategorieEtPo([$a, $b]);
        self::assertCount(1, $groupes);
        $groupe = array_values($groupes)[0];
        self::assertSame(12, $groupe->getPeriodes());
        self::assertSame(0.5, $groupe->getChargeDecimal());
        self::assertSame(6, $a->getPeriodes());
        self::assertSame(0.25, $a->getChargeDecimal());
    }
}
