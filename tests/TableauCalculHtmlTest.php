<?php

namespace Tests;

use DateTime;
use DOMDocument;
use DOMXPath;
use Helip\AnneeScolaire\AnneeScolaire;
use Helip\EsahrAnciennete\AncienneteServiceCalculateur;
use Helip\EsahrAnciennete\Attribution;
use Helip\EsahrAnciennete\Evenement;
use Helip\EsahrAnciennete\TableauCalculHtml;
use PHPUnit\Framework\TestCase;

final class TableauCalculHtmlTest extends TestCase
{
    public function testHtmlAfficheLesEtapesEtLeResultatSansExecuterLesLibelles(): void
    {
        $fonction = '<script>alert("fonction")</script>';
        $attribution = new Attribution(
            24, 6, 'T', $fonction, Attribution::CAT_PERSONNEL_EDUCATIF,
            new AnneeScolaire('2020-2021'), true, false, true
        );
        $resultat = (new AncienneteServiceCalculateur())->calculer([
            new Evenement(new DateTime('2021-03-01'), new DateTime('2021-03-31'), [$attribution]),
        ]);
        $html = (new TableauCalculHtml())->rendre($resultat, Attribution::CAT_PERSONNEL_EDUCATIF, $fonction);
        $document = new DOMDocument();
        self::assertTrue($document->loadHTML('<meta charset="UTF-8">' . $html));
        $xpath = new DOMXPath($document);
        self::assertSame(0, $xpath->query('//script')->length);
        self::assertStringContainsString($fonction, $xpath->query('//caption')->item(0)->textContent);
        self::assertSame(16, $xpath->query('//thead/tr/th')->length);
        self::assertSame('0,5', $xpath->query('//tbody/tr[1]/td[@data-colonne="coefficient_charge_cf"]')->item(0)->textContent);
        self::assertSame('15,5', $xpath->query('//tbody/tr[1]/td[@data-colonne="cf_retenu"]')->item(0)->textContent);
        self::assertSame('15', $xpath->query('//tfoot/tr[1]/td[last()]')->item(0)->textContent);
        self::assertStringContainsString('séparée par financement', $xpath->query('//tfoot')->item(0)->textContent);
    }

    public function testRenduVideEtModeGlobal(): void
    {
        $resultat = (new AncienneteServiceCalculateur(AncienneteServiceCalculateur::TRONCATURE_GLOBALE))->calculer([]);
        $html = (new TableauCalculHtml())->rendre($resultat, Attribution::CAT_PERSONNEL_EDUCATIF);
        self::assertStringContainsString('<tbody></tbody>', $html);
        self::assertStringContainsString('troncature finale globale', $html);
        self::assertStringContainsString('<td>0</td>', $html);
    }
}
