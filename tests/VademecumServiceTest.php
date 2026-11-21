<?php

namespace Tests;

use DateTime;
use Helip\AnneeScolaire\AnneeScolaire;
use Helip\EsahrAnciennete\AncienneteServiceCalculateur;
use Helip\EsahrAnciennete\Attribution;
use Helip\EsahrAnciennete\Evenement;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Contrats de référence : vadémécum CECP, version ESAHR septembre 2021.
 * Les attentes viennent du document, indépendamment du résultat actuel de src/.
 * Ces contrats sont également exécutés sur le nouveau moteur de calcul.
 *
 * Couverture complémentaire aux exemples des pages 14, 16 et 19 déjà présents
 * dans AncienneteServiceTest. Les calculs multiannuels et les événements
 * simultanés sont pris en charge par la normalisation du nouveau moteur.
 *
 * Non représentable actuellement dans l'API, donc non couvert ici :
 * - p8, p23-35 : identité du PO/réseau, fenêtres de cinq ans, nombre d'années,
 *   classements, conditions de nomination/sélection ;
 * - p16 note 24 : régime transitoire avant 2004 ;
 * - p9, p12, p36-44 : nature des congés et exceptions au subventionnement.
 * Les indicateurs d'admissibilité, l'encadrement pédagogique et l'ancienneté de
 * fonction sont couverts dans AncienneteDetailTest ; les congés sont qualifiés
 * en amont par l'appelant.
 *
 * Ambiguïté du document à arbitrer : p19 donne 248 jours en tronquant CF et PO
 * séparément ; la troncature du seul total (p15-16) donnerait 249 jours.
 * Le test historique testPage19Total conserve la valeur explicite de l'exemple.
 */
final class VademecumServiceTest extends TestCase
{
    private static function attribution(
        int $periodes,
        bool $po = false,
        bool $subventionne = true,
        bool $titreRequis = true,
        int $fraction = 24,
        string $categorie = Attribution::CAT_PERSONNEL_EDUCATIF,
        string $annee = '2020-2021',
        string $fonction = 'Formation musicale'
    ): Attribution {
        return new Attribution(
            fraction: $fraction,
            periodes: $periodes,
            situation: 'T',
            fonction: $fonction,
            categorie: $categorie,
            anneeScolaire: new AnneeScolaire($annee),
            estSubventionne: $subventionne,
            estPO: $po,
            estTitreRequis: $titreRequis
        );
    }

    private static function evenement(string $debut, string $fin, array $attributions, int $poInitial = 0): Evenement
    {
        return new Evenement(new DateTime($debut), new DateTime($fin), $attributions, $poInitial);
    }

    public static function servicesSimples(): iterable
    {
        // Pages 12-15 : bornes incluses, week-ends, seuil du mi-temps, plafond et troncature.
        yield 'p12 septembre 14 sur 24' => ['2020-09-01', '2020-09-30', 14, 30];
        yield 'p12 septembre 6 sur 24' => ['2020-09-01', '2020-09-30', 6, 15];
        yield 'p12 mi-temps exact' => ['2020-09-01', '2020-09-30', 12, 30];
        yield 'p12 juste sous le mi-temps' => ['2020-09-01', '2020-09-30', 11, 15];
        yield 'p12 un seul jour inclus' => ['2020-09-01', '2020-09-01', 12, 1];
        yield 'p12 week-end inclus' => ['2020-09-04', '2020-09-07', 12, 4];
        yield 'p12 vacances hiver incluses' => ['2020-12-21', '2021-01-03', 12, 14];
        yield 'p13 plafond annuel mi-temps' => ['2020-09-01', '2021-06-30', 12, 300];
        yield 'p13 plafond annuel sous mi-temps' => ['2020-09-01', '2021-06-30', 6, 150];
        yield 'p15 mars 31 jours troncature' => ['2021-03-01', '2021-03-31', 6, 15];
        yield 'aucune prestation' => ['2020-09-01', '2021-06-30', 0, 0];
    }

    #[DataProvider('servicesSimples')]
    public function testServicesSimples(string $debut, string $fin, int $periodes, int $attendu): void
    {
        $resultat = (new AncienneteServiceCalculateur())->calculer([
            self::evenement($debut, $fin, [self::attribution($periodes)]),
        ]);
        self::assertEquals($attendu, $resultat->get(Attribution::CAT_PERSONNEL_EDUCATIF));
    }

    public static function cumulsFonctions(): iterable
    {
        yield 'p13 violon 4 et musique chambre 5' => [4, 24, 5, 24, 15];
        yield 'p13 violon 6 et musique chambre 6' => [6, 24, 6, 24, 30];
        yield 'p13 denominateurs 26 et 24' => [6, 26, 6, 24, 15];
        yield 'p13 denominateurs differents mi-temps exact' => [6, 24, 9, 36, 30];
    }

    #[DataProvider('cumulsFonctions')]
    public function testCumulDesChargesAvantApplicationDuMiTemps(int $a, int $fa, int $b, int $fb, int $attendu): void
    {
        $resultat = (new AncienneteServiceCalculateur())->calculer([
            self::evenement('2021-06-01', '2021-06-30', [
                self::attribution($a, fraction: $fa, fonction: 'Violon'),
                self::attribution($b, fraction: $fb, fonction: 'Musique de chambre'),
            ]),
        ]);
        self::assertEquals($attendu, $resultat->get(Attribution::CAT_PERSONNEL_EDUCATIF));
    }

    public static function cumulsCfPo(): iterable
    {
        // Pages 19-21 : valeurs explicites du document, avant et après 1200 jours.
        yield 'p19 CF quart PO mi-temps' => [6, 12, 0, 240];
        yield 'p19 CF mi-temps PO quart' => [12, 6, 0, 300];
        yield 'p20 CF 6 PO 4 total inferieur mi-temps' => [6, 4, 0, 195];
        yield 'p21 deux quarts avant seuil' => [6, 6, 0, 195];
        yield 'p21 deux quarts apres seuil' => [6, 6, 1200, 300];
        yield 'p21 total sous mi-temps apres seuil' => [6, 4, 1200, 150];
        yield 'p21 CF quart PO mi-temps apres seuil' => [6, 12, 1200, 300];
        yield 'p21 CF mi-temps PO quart apres seuil' => [12, 6, 1200, 300];
        yield 'p21 deux mi-temps avant seuil' => [12, 12, 0, 300];
        yield 'p21 deux mi-temps apres seuil' => [12, 12, 1200, 300];
    }

    #[DataProvider('cumulsCfPo')]
    public function testCumulsCfPo(int $cf, int $po, int $initial, int $attendu): void
    {
        // Le drapeau subventionne=true pour PO conserve ici la convention des anciens tests.
        // Sa signification contradictoire est testée séparément ci-dessous.
        $resultat = (new AncienneteServiceCalculateur())->calculer([
            self::evenement('2020-09-01', '2021-06-30', [
                self::attribution($cf), self::attribution($po, po: true),
            ], $initial),
        ]);
        self::assertEquals($attendu, $resultat->get(Attribution::CAT_PERSONNEL_EDUCATIF));
    }

    public function testPage18PoAvecTitreSuffisantNeGenereAucuneAnciennete(): void
    {
        $resultat = (new AncienneteServiceCalculateur())->calculer([
            self::evenement('2020-09-01', '2021-06-30', [
                self::attribution(12, po: true, titreRequis: false),
            ]),
        ]);
        self::assertEquals(0, $resultat->get(Attribution::CAT_PERSONNEL_EDUCATIF, 'PO'));
    }

    public function testPages14Et23CfAvecTitreSuffisantGenereAnciennete(): void
    {
        // Ici false désigne un titre suffisant : l'API ne distingue pas l'absence de titre.
        $resultat = (new AncienneteServiceCalculateur())->calculer([
            self::evenement('2020-09-01', '2021-06-30', [self::attribution(12, titreRequis: false)]),
        ]);
        self::assertEquals(300, $resultat->get(Attribution::CAT_PERSONNEL_EDUCATIF, 'CF'));
    }

    public function testPages16Et17ServicesFinancesParPoNonSubventionnesSontValorises(): void
    {
        $resultat = (new AncienneteServiceCalculateur())->calculer([
            self::evenement('2020-09-01', '2021-06-30', [
                self::attribution(12, po: true, subventionne: false),
            ]),
        ]);
        self::assertEquals(90, $resultat->get(Attribution::CAT_PERSONNEL_EDUCATIF, 'PO'));
    }

    public function testPages9Et12InterruptionNonSubventionnee(): void
    {
        // Exemple p9 : maladie non rémunérée du 20 au 30 novembre, soit 11 jours exclus.
        $resultat = (new AncienneteServiceCalculateur())->calculer([
            self::evenement('2020-09-01', '2020-11-19', [self::attribution(12)]),
            self::evenement('2020-11-20', '2020-11-30', [self::attribution(12, subventionne: false)]),
            self::evenement('2020-12-01', '2021-06-30', [self::attribution(12)]),
        ]);
        self::assertEquals(292, $resultat->get(Attribution::CAT_PERSONNEL_EDUCATIF, 'CF'));
    }

    public function testPage11CategoriesNeSeCumulentPasPourLeMiTemps(): void
    {
        $resultat = (new AncienneteServiceCalculateur())->calculer([
            self::evenement('2021-06-01', '2021-06-30', [
                self::attribution(6),
                self::attribution(6, categorie: Attribution::CAT_PERSONNEL_AUXILIAIRE),
            ]),
        ]);
        self::assertEquals(15, $resultat->get(Attribution::CAT_PERSONNEL_EDUCATIF));
        self::assertEquals(15, $resultat->get(Attribution::CAT_PERSONNEL_AUXILIAIRE));
    }

    public function testPages12Et13BaisseDeChargeNePlafonnePasTouteAnneeA150(): void
    {
        // 153 jours à mi-temps + 150 jours sous mi-temps / 2 = 228,5, tronqués à 228.
        $resultat = (new AncienneteServiceCalculateur())->calculer([
            self::evenement('2020-09-01', '2021-01-31', [self::attribution(12)]),
            self::evenement('2021-02-01', '2021-06-30', [self::attribution(6)]),
        ]);
        self::assertEquals(228, $resultat->get(Attribution::CAT_PERSONNEL_EDUCATIF, 'CF'));
    }

    public function testPages13Et17PlafondAppliqueParAnneeEtNonSurTouteCarriere(): void
    {
        $evenements = [];
        for ($annee = 2016; $annee <= 2020; ++$annee) {
            $suivante = $annee + 1;
            $evenements[] = self::evenement("$annee-09-01", "$suivante-06-30", [
                self::attribution(12, po: true, annee: "$annee-$suivante"),
            ]);
        }
        $resultat = (new AncienneteServiceCalculateur())->calculer($evenements);
        // Exemple explicite p17 : 1500 jours prestés = 1200 * 0,3 + 300 = 660.
        self::assertEquals(660, $resultat->get(Attribution::CAT_PERSONNEL_EDUCATIF, 'PO'));
    }

    public function testPage13DeuxAnneesCfPeuventDepasser300Jours(): void
    {
        $resultat = (new AncienneteServiceCalculateur())->calculer([
            self::evenement('2019-09-01', '2020-06-30', [self::attribution(12, annee: '2019-2020')]),
            self::evenement('2020-09-01', '2021-06-30', [self::attribution(12)]),
        ]);
        self::assertEquals(600, $resultat->get(Attribution::CAT_PERSONNEL_EDUCATIF, 'CF'));
    }

    public function testPage14UnJourNePeutPasCompterDeuxFoisDansDesEvenementsSimultanes(): void
    {
        // Deux fonctions simultanées, présentées en événements séparés.
        // Si ce format doit être interdit, le contrat d'entrée devra le préciser et le valider.
        $resultat = (new AncienneteServiceCalculateur())->calculer([
            self::evenement('2021-06-01', '2021-06-30', [self::attribution(12, fonction: 'Violon')]),
            self::evenement('2021-06-01', '2021-06-30', [self::attribution(12, fonction: 'Musique de chambre')]),
        ]);
        self::assertEquals(30, $resultat->get(Attribution::CAT_PERSONNEL_EDUCATIF));
    }

    public static function servicesPo(): iterable
    {
        yield 'p20 PO sous mi-temps avant seuil' => [6, 0, 45];
        yield 'p21 PO mi-temps avant seuil' => [12, 0, 90];
        yield 'p20 PO sous mi-temps apres seuil' => [6, 1200, 150];
        yield 'p21 PO mi-temps apres seuil' => [12, 1200, 300];
    }

    #[DataProvider('servicesPo')]
    public function testPlafondsAnnuelsPo(int $periodes, int $initial, int $attendu): void
    {
        $resultat = (new AncienneteServiceCalculateur())->calculer([
            self::evenement('2020-09-01', '2021-06-30', [self::attribution($periodes, po: true)], $initial),
        ]);
        self::assertEquals($attendu, $resultat->get(Attribution::CAT_PERSONNEL_EDUCATIF, 'PO'));
    }

    public function testPage20TroncaturePoApresCumulDePlusieursAnnees(): void
    {
        $resultat = (new AncienneteServiceCalculateur())->calculer([
            self::evenement('2019-09-01', '2019-09-03', [self::attribution(12, po: true, annee: '2019-2020')]),
            self::evenement('2020-09-01', '2020-09-03', [self::attribution(12, po: true)]),
        ]);
        // 3 * 0,3 + 3 * 0,3 = 1,8 : une seule troncature, y compris pour TOTAL.
        self::assertEquals(1, $resultat->get(Attribution::CAT_PERSONNEL_EDUCATIF, 'PO'));
        self::assertEquals(1, $resultat->get(Attribution::CAT_PERSONNEL_EDUCATIF));
    }
}
