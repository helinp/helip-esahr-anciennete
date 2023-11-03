<?php

namespace Tests\Helpers;

use DateTime;
use Helip\EsahrAnciennete\AncienneteServiceCalculateur;
use Helip\EsahrAnciennete\Attribution;
use Helip\EsahrAnciennete\Evenement;
use Helip\AnneeScolaire\AnneeScolaire;
use PHPUnit\Framework\TestCase;

/**
 * Tests réalisés à partir des exemples du Vade-Mecum
 * LES ANCIENNETES STATUTAIRES
 * Décret du 6 juin 1994 Version ESAHR de septembre 2021
 * Réalisé par le CECP
 */
class AncienneteServiceTest extends TestCase
{
    /**
     * Exemple 1 à page 14
     * 
     * @return void
     */
    public function testSimpleCf20PeriodesEnseignant()
    {
        $attributionA = new Attribution(
            fraction: 24,
            periodes: 12,
            situation: 'T',
            fonction: 'Violoncelle',
            categorie: Attribution::CAT_PERSONNEL_EDUCATIF,
            anneeScolaire: new AnneeScolaire('2020-2021'),
            estSubventionne: true,
            estPO: false,
            estTitreRequis: true
        );

        $attributionB = new Attribution(
            fraction: 24,
            periodes: 6,
            situation: 'T',
            fonction: 'Musique de chambre instrumentale',
            categorie: Attribution::CAT_PERSONNEL_EDUCATIF,
            anneeScolaire: new AnneeScolaire('2020-2021'),
            estSubventionne: true,
            estPO: false,
            estTitreRequis: true
        );

        $attributionC = new Attribution(
            fraction: 24,
            periodes: 2,
            situation: 'T',
            fonction: 'Ensemble instrumental',
            categorie: Attribution::CAT_PERSONNEL_EDUCATIF,
            anneeScolaire: new AnneeScolaire('2020-2021'),
            estSubventionne: true,
            estPO: false,
            estTitreRequis: true
        );

        $evenenement = new Evenement(
            dateDebut: new DateTime('2020-09-01'),
            dateFin: new DateTime('2020-12-01'),
            attributions: [$attributionA, $attributionB, $attributionC]
        );

        $ancienneteService = new AncienneteServiceCalculateur();

        $anciennete = $ancienneteService->calculer([$evenenement]);

        // Ancienneté de la catégorie
        $this->assertEquals(92, $anciennete->get(Attribution::CAT_PERSONNEL_EDUCATIF, 'CF'), 'Ancienneté de la catégorie');
        $this->assertEquals(92, $anciennete->get(Attribution::CAT_PERSONNEL_EDUCATIF, 'TOTAL'), 'Ancienneté de la catégorie');
    }

    /**
     * 
     * @return void
     */
    public function testSimpleCf10PeriodesEnseignant()
    {
        $attributionA = new Attribution(
            fraction: 24,
            periodes: 2,
            situation: 'T',
            fonction: 'Violoncelle',
            categorie: Attribution::CAT_PERSONNEL_EDUCATIF,
            anneeScolaire: new AnneeScolaire('2020-2021'),
            estSubventionne: true,
            estPO: false,
            estTitreRequis: true
        );

        $attributionB = new Attribution(
            fraction: 24,
            periodes: 6,
            situation: 'T',
            fonction: 'Musique de chambre instrumentale',
            categorie: Attribution::CAT_PERSONNEL_EDUCATIF,
            anneeScolaire: new AnneeScolaire('2020-2021'),
            estSubventionne: true,
            estPO: false,
            estTitreRequis: true
        );

        $attributionC = new Attribution(
            fraction: 24,
            periodes: 2,
            situation: 'T',
            fonction: 'Ensemble instrumental',
            categorie: Attribution::CAT_PERSONNEL_EDUCATIF,
            anneeScolaire: new AnneeScolaire('2020-2021'),
            estSubventionne: true,
            estPO: false,
            estTitreRequis: true
        );

        $evenenement = new Evenement(
            dateDebut: new DateTime('2020-09-01'),
            dateFin: new DateTime('2020-12-01'),
            attributions: [$attributionA, $attributionB, $attributionC]
        );

        $ancienneteService = new AncienneteServiceCalculateur();

        $anciennete = $ancienneteService->calculer([$evenenement]);

        // Ancienneté de la catégorie
        $this->assertEquals(46, $anciennete->get(Attribution::CAT_PERSONNEL_EDUCATIF), 'Ancienneté de la catégorie');
    }

    /**
     * 
     * @return void
     */
    public function testSimpleCf2Evenements10PeriodesEnseignantAvecTroncature()
    {
        /*
        - Du 1er septembre au 30 novembre 2020 (soit 91 jours) : 4 périodes ;
- Du 14 décembre 2020 au 18 décembre 2020 (soit 5 jours) : 11 périodes ;
- Du 19 avril au 30 juin 2021 (soit 73 jours) : 6 périodes.
*/

        $attributionA = new Attribution(
            fraction: 24,
            periodes: 4,
            situation: 'T',
            fonction: 'Ensemble instrumental',
            categorie: Attribution::CAT_PERSONNEL_EDUCATIF,
            anneeScolaire: new AnneeScolaire('2020-2021'),
            estSubventionne: true,
            estPO: false,
            estTitreRequis: true
        );

        $evenenementA = new Evenement(
            dateDebut: new DateTime('2020-09-01'),
            dateFin: new DateTime('2020-11-30'),
            attributions: [$attributionA]
        );

        $attributionB = new Attribution(
            fraction: 24,
            periodes: 11,
            situation: 'T',
            fonction: 'Ensemble instrumental',
            categorie: Attribution::CAT_PERSONNEL_EDUCATIF,
            anneeScolaire: new AnneeScolaire('2020-2021'),
            estSubventionne: true,
            estPO: false,
            estTitreRequis: true
        );

        $evenenementB = new Evenement(
            dateDebut: new DateTime('2020-12-14'),
            dateFin: new DateTime('2020-12-18'),
            attributions: [$attributionB]
        );

        $attributionC = new Attribution(
            fraction: 24,
            periodes: 6,
            situation: 'T',
            fonction: 'Ensemble instrumental',
            categorie: Attribution::CAT_PERSONNEL_EDUCATIF,
            anneeScolaire: new AnneeScolaire('2020-2021'),
            estSubventionne: true,
            estPO: false,
            estTitreRequis: true
        );

        $evenenementC = new Evenement(
            dateDebut: new DateTime('2021-04-19'),
            dateFin: new DateTime('2021-06-30'),
            attributions: [$attributionC]
        );

        $ancienneteService = new AncienneteServiceCalculateur();

        $anciennete = $ancienneteService->calculer([$evenenementA, $evenenementB, $evenenementC]);

        // Ancienneté de la catégorie
        /* Son ancienneté au 30 juin 2021 sera de 84 jours (45,5 + 2,5 + 36,5 = 84,5) */

        $this->assertEquals(84, $anciennete->get(Attribution::CAT_PERSONNEL_EDUCATIF), 'Ancienneté de la catégorie');
    }

    /**
     * 
     * @return void
     */
    public function testPoEtCf1Evenement()
    {
        /*
        Par exemple, un professeur de formation instrumentale, spécialité guitare et guitare d’accompagnement, 
        preste 6 périodes subventionnées et 12 périodes à charge des fonds communaux durant une année scolaire complète. 
        Son ancienneté statutaire se calcule comme suit :
        o Pour les services subventionnés : 300 jours /2 = 150 jours (6p < 1⁄2 temps)
        o Pour les services financés par la commune : 300 jours x 0,3 = 90 jours (12p = 1⁄2
        temps)
        o Total pour l’année scolaire : 240 jours */

        $attributionA = new Attribution(
            fraction: 24,
            periodes: 6,
            situation: 'T',
            fonction: 'Guitare',
            categorie: Attribution::CAT_PERSONNEL_EDUCATIF,
            anneeScolaire: new AnneeScolaire('2021-2022'),
            estSubventionne: true,
            estPO: false,
            estTitreRequis: true
        );

        $attributionB = new Attribution(
            fraction: 24,
            periodes: 12,
            situation: 'T',
            fonction: 'Guitare',
            categorie: Attribution::CAT_PERSONNEL_EDUCATIF,
            anneeScolaire: new AnneeScolaire('2021-2022'),
            estSubventionne: true,
            estPO: true,
            estTitreRequis: true
        );

        $evenenement = new Evenement(
            dateDebut: new DateTime('2021-09-01'),
            dateFin: new DateTime('2022-06-30'),
            attributions: [$attributionA, $attributionB]
        );

        $ancienneteService = new AncienneteServiceCalculateur();

        $anciennete = $ancienneteService->calculer([$evenenement]);

        $this->assertEquals(150, $anciennete->get(Attribution::CAT_PERSONNEL_EDUCATIF, 'CF'), 'Ancienneté de la catégorie CF');
        $this->assertEquals(90, $anciennete->get(Attribution::CAT_PERSONNEL_EDUCATIF, 'PO'), 'Ancienneté de la catégorie PO');
    }

    /**
     * 
     * @return void
     */
    public function testPoEtCf1Evenement300Jours()
    {
        /*
Par exemple, un professeur de formation instrumentale, spécialité guitare et guitare d’accompagnement 
preste 12 périodes subventionnées et 6 périodes à charge des fonds communaux durant une année scolaire complète. 
Son ancienneté statutaire se calcule comme suit :
o Pour les services subventionnés : 300 jours (12p = 1⁄2 temps)
o Pour les services financés par la commune : (300 jours/2) x 0,3 = 45 jours (6p < 1⁄2
temps)
o Totalpourl’annéescolaire:300jourspuisqu’uneannéescolairenepeutdépasser
300 jours et que les services subventionnés atteignent le mi-temps
        */

        $attributionA = new Attribution(
            fraction: 24,
            periodes: 12,
            situation: 'T',
            fonction: 'Guitare',
            categorie: Attribution::CAT_PERSONNEL_EDUCATIF,
            anneeScolaire: new AnneeScolaire('2021-2022'),
            estSubventionne: true,
            estPO: false,
            estTitreRequis: true
        );

        $attributionB = new Attribution(
            fraction: 24,
            periodes: 6,
            situation: 'T',
            fonction: 'Guitare',
            categorie: Attribution::CAT_PERSONNEL_EDUCATIF,
            anneeScolaire: new AnneeScolaire('2021-2022'),
            estSubventionne: true,
            estPO: true,
            estTitreRequis: true
        );

        $evenenement = new Evenement(
            dateDebut: new DateTime('2021-09-01'),
            dateFin: new DateTime('2022-06-30'),
            attributions: [$attributionA, $attributionB]
        );

        $ancienneteService = new AncienneteServiceCalculateur();

        $anciennete = $ancienneteService->calculer([$evenenement]);

        $this->assertEquals(300, $anciennete->get(Attribution::CAT_PERSONNEL_EDUCATIF, 'CF'), 'Ancienneté CF');
        $this->assertEquals(45, $anciennete->get(Attribution::CAT_PERSONNEL_EDUCATIF, 'PO'), 'Ancienneté PO');
        $this->assertEquals(300, $anciennete->get(Attribution::CAT_PERSONNEL_EDUCATIF), 'Ancienneté TOTALE');
    }

    private function testPage19()
    {
        /*
Par exemple, un professeur de danse jazz preste 2 périodes à charge des fonds communaux durant une année scolaire complète, 
mais aussi 9 périodes subventionnées du 1er septembre 2020 au 31 janvier 2021, puis, en raison d’un congé d’un autre membre du personnel, 
12 périodes subventionnées du 1er février 2021 au 30 juin 2021 inclus.
Son ancienneté statutaire se calcule comme suit :

o Pour les services subventionnés :
9 périodes du 01/09/20 au 31/01/21 : 76,5 jours (9p < 1⁄2 temps) ;
12 périodes du 01/02/21 au 30/06/21 : 150 jours (12p = 1⁄2 temps)
➔ 76,5 jours + 150 jours = 226 jours avec la troncature (voir ci-dessous)

o Pour les services financés par la commune :
2 périodes du 01/09/20 au 31/01/21 : 76,5 jours x 0,3 = 22 jours (2p < 1⁄2 temps).

Les services prestés du 01/02/21 au 30/06/21 ne pourront donner lieu à un calcul
d’ancienneté puisque le membre du personnel a déjà un mi-temps subventionné. o Total pour l’année scolaire :
226 + 22 = 248 jours
        */

        $attributionCfA = new Attribution(
            fraction: 24,
            periodes: 9,
            situation: 'T',
            fonction: 'Danse jazz',
            categorie: Attribution::CAT_PERSONNEL_EDUCATIF,
            anneeScolaire: new AnneeScolaire('2020-2021'),
            estSubventionne: true,
            estPO: false,
            estTitreRequis: true
        );
        $attributionPoA = new Attribution(
            fraction: 24,
            periodes: 2,
            situation: 'T',
            fonction: 'Danse jazz',
            categorie: Attribution::CAT_PERSONNEL_EDUCATIF,
            anneeScolaire: new AnneeScolaire('2020-2021'),
            estSubventionne: true,
            estPO: true,
            estTitreRequis: true
        );

        $evenenementA = new Evenement(
            dateDebut: new DateTime('2020-09-01'),
            dateFin: new DateTime('2021-01-31'),
            attributions: [$attributionCfA, $attributionPoA]
        );

        $attributionCfB = new Attribution(
            fraction: 24,
            periodes: 12,
            situation: 'T',
            fonction: 'Danse jazz',
            categorie: Attribution::CAT_PERSONNEL_EDUCATIF,
            anneeScolaire: new AnneeScolaire('2020-2021'),
            estSubventionne: true,
            estPO: false,
            estTitreRequis: true
        );

        $attributionPoB = new Attribution(
            fraction: 24,
            periodes: 2,
            situation: 'T',
            fonction: 'Danse jazz',
            categorie: Attribution::CAT_PERSONNEL_EDUCATIF,
            anneeScolaire: new AnneeScolaire('2020-2021'),
            estSubventionne: true,
            estPO: true,
            estTitreRequis: true
        );

        $evenenementB = new Evenement(
            dateDebut: new DateTime('2021-02-01'),
            dateFin: new DateTime('2021-06-30'),
            attributions: [$attributionCfB, $attributionPoB]
        );

        $ancienneteService = new AncienneteServiceCalculateur();

        return $ancienneteService->calculer([$evenenementA, $evenenementB]);
    }

    /**
     * 
     * @return void
     */
    public function testPage19Cf()
    {
        $anciennete = $this->testPage19();
        $this->assertEquals(226, $anciennete->get(Attribution::CAT_PERSONNEL_EDUCATIF, 'CF'), 'Ancienneté CF');
    }

    /**
     * 
     * @return void
     */
    public function testPage19Po()
    {
        $anciennete = $this->testPage19();

        $this->assertEquals(45, $anciennete->get(Attribution::CAT_PERSONNEL_EDUCATIF, 'PO'), 'Ancienneté PO');
    }

    /**
     * 
     * @return void
     */
    public function testPage19Total()
    {
        $anciennete = $this->testPage19();
        $this->assertEquals(248, $anciennete->get(Attribution::CAT_PERSONNEL_EDUCATIF), 'Ancienneté totale');
    }

    /**
     * 
     * @return void
     */
    public function testPoPlus1200Jours()
    {
        /* Exemple d'un professeur de mandoline qui preste 13 périodes PO et a une ancienneté de 1100 jours PO 
        
        Son ancienneté statutaire se calcule comme suit :
        o Une année scolaire complète = 300 jours PO
        o Pour les derniers jours à .3 (1200-1100): 100 jours x 0,3 = 30 jours 
        o Pour les autres jours: 200 jours = 200 jours
        o Total pour l’année scolaire : 230 jours
        */

        $attributionA = new Attribution(
            fraction: 24,
            periodes: 13,
            situation: 'T',
            fonction: 'Mandoline',
            categorie: Attribution::CAT_PERSONNEL_EDUCATIF,
            anneeScolaire: new AnneeScolaire('2021-2022'),
            estSubventionne: true,
            estPO: true,
            estTitreRequis: true
        );

        $evenenement = new Evenement(
            dateDebut: new DateTime('2021-09-01'),
            dateFin: new DateTime('2022-06-30'),
            attributions: [$attributionA],
            ancienneteActuellePOEducatif: 1100,
            ancienneteActuellePOAuxiliaire: 0
        );

        $ancienneteService = new AncienneteServiceCalculateur();
        $anciennete = $ancienneteService->calculer([$evenenement]);

        $this->assertEquals(230, $anciennete->get(Attribution::CAT_PERSONNEL_EDUCATIF, 'PO'), 'Ancienneté PO');
    }

    // test anciennete 1200 se fait sur plusieurs événements
    public function testPoPlus1200JoursPlusieursEvents()
    {
        /* Exemple d'un professeur de mandoline qui preste 13 périodes PO et a une ancienneté de 1100 jours PO */

        $attributionA = new Attribution(
            fraction: 24,
            periodes: 13,
            situation: 'T',
            fonction: 'Mandoline',
            categorie: Attribution::CAT_PERSONNEL_EDUCATIF,
            anneeScolaire: new AnneeScolaire('2021-2022'),
            estSubventionne: true,
            estPO: true,
            estTitreRequis: true
        );

        $evenenementA = new Evenement(
            dateDebut: new DateTime('2021-09-01'),
            dateFin: new DateTime('2021-12-19'),
            attributions: [$attributionA],
            ancienneteActuellePOEducatif: 1100
        );

        /* 
        110 jours bruts deviennent
        100 * 0.3 = 30 jours (1200 - 1100)
        + 10 jours = 40 jours
        */
        $ancienneteService = new AncienneteServiceCalculateur();
        $anciennete = $ancienneteService->calculer([$evenenementA]);
        $this->assertEquals(40, $anciennete->get(Attribution::CAT_PERSONNEL_EDUCATIF, 'PO'), 'Ancienneté PO Evenement A');
        $this->assertEquals(110, $anciennete->get(Attribution::CAT_PERSONNEL_EDUCATIF, 'PO_RAW'), 'Ancienneté jours bruts PO');

        $evenenementB = new Evenement(
            dateDebut: new DateTime('2021-12-20'),
            dateFin: new DateTime('2022-06-30'),
            attributions: [$attributionA],
            ancienneteActuellePOEducatif: (1100 + $anciennete->get(Attribution::CAT_PERSONNEL_EDUCATIF, 'PO_RAW'))
        );

        /* 
        193 jours calendriers
        Ancienneté = 1100 + 110 jours
        */
        $anciennete = $ancienneteService->calculer([$evenenementB]);
        $this->assertEquals(193, $anciennete->get(Attribution::CAT_PERSONNEL_EDUCATIF, 'PO'), 'Ancienneté PO Ev. B');
    }

    // test anciennete 1200 se fait sur plusieurs événements
    public function testPoPlus1200JoursSommeEvents()
    {
        /* Exemple d'un professeur de mandoline qui preste 13 périodes PO et a une ancienneté de 1100 jours PO */

        // 110 jours calendriers
        $attributionA = new Attribution(
            fraction: 24,
            periodes: 13,
            situation: 'T',
            fonction: 'Mandoline',
            categorie: Attribution::CAT_PERSONNEL_EDUCATIF,
            anneeScolaire: new AnneeScolaire('2021-2022'),
            estSubventionne: true,
            estPO: true,
            estTitreRequis: true
        );

        $evenenementA = new Evenement(
            dateDebut: new DateTime('2021-09-01'),
            dateFin: new DateTime('2021-12-19'),
            attributions: [$attributionA],
            ancienneteActuellePOEducatif: 1100
        );

        /* 
        110 jours bruts deviennent
        100 * 0.3 = 30 jours (1200 - 1100)
        + 10 jours = 40 jours
        */
        $ancienneteService = new AncienneteServiceCalculateur();

        // 193 jours calendriers
        $evenenementB = new Evenement(
            dateDebut: new DateTime('2021-12-20'),
            dateFin: new DateTime('2022-06-30'),
            attributions: [$attributionA],
            ancienneteActuellePOEducatif: 1100
        );

        /* 
        193 jours calendriers
        Ancienneté = 1100 + 110 jours
        */
        $anciennete = $ancienneteService->calculer([$evenenementA, $evenenementB]);
        $this->assertEquals(300, $anciennete->get(Attribution::CAT_PERSONNEL_EDUCATIF, 'PO_RAW'), 'Ancienneté PO Raw');
        $this->assertEquals(230, $anciennete->get(Attribution::CAT_PERSONNEL_EDUCATIF, 'PO'), 'Ancienneté PO Evenement A + B');
    }
    // test anciennete 1200 se fait sur plusieurs événements
    public function testPoPlus1200JoursQuartTempsSommeEvents()
    {
        /* Exemple d'un professeur de mandoline qui preste 13 périodes PO et a une ancienneté de 1100 jours PO */

        // 110 jours calendriers
        $attributionA = new Attribution(
            fraction: 24,
            periodes: 6,
            situation: 'T',
            fonction: 'Mandoline',
            categorie: Attribution::CAT_PERSONNEL_EDUCATIF,
            anneeScolaire: new AnneeScolaire('2021-2022'),
            estSubventionne: true,
            estPO: true,
            estTitreRequis: true
        );

        $evenenementA = new Evenement(
            dateDebut: new DateTime('2021-09-01'),
            dateFin: new DateTime('2021-12-19'),
            attributions: [$attributionA],
            ancienneteActuellePOEducatif: 1100
        );

        /* 
        110 jours calendrier deviennent 35 jours
        -- 100 (1200 - 1100) * 0.3 * 0.5 => 15 jours 
        -- 10 * 0.5 jours => 20 jours
        */
        $ancienneteService = new AncienneteServiceCalculateur();

        // 193 jours calendriers
        $evenenementB = new Evenement(
            dateDebut: new DateTime('2021-12-20'),
            dateFin: new DateTime('2022-06-30'),
            attributions: [$attributionA],
            ancienneteActuellePOEducatif: 1100
        );

        /* 
        193 jours calendriers deviennent 96.5 jours
        -- 193 * .5 => 96.5 jours
        TOTAL: 230 x .5 = 115 jours
        */
        $anciennete = $ancienneteService->calculer([$evenenementA, $evenenementB]);
        $this->assertEquals(115, $anciennete->get(Attribution::CAT_PERSONNEL_EDUCATIF, 'PO'), 'Ancienneté PO Evenement A + B');
    }
}


