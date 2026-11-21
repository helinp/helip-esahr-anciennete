<?php

namespace Tests\Helpers;

use Helip\EsahrAnciennete\AncienneteCalculateurHelper as EsahrAncienneteAncienneteCalculateurHelper;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class AncienneteCalculateurHelperTest extends TestCase
{
    public static function seuilPoVademecum(): iterable
    {
        // Vadémécum p17 : réduction sur les 1200 premiers jours,
        // puis un jour vaut un jour dès le 1201e.
        yield '1200 premiers jours' => [1200, 0, 360.0];
        yield '1201 premiers jours' => [1201, 0, 361.0];
        yield 'dernier jour reduit' => [1, 1199, 0.3];
        yield 'franchissement exact' => [2, 1199, 1.3];
        yield 'premier jour non reduit' => [1, 1200, 1.0];
    }

    #[DataProvider('seuilPoVademecum')]
    public function testSeuilPoVademecum(float $jours, float $initial, float $attendu): void
    {
        self::assertEqualsWithDelta(
            $attendu,
            EsahrAncienneteAncienneteCalculateurHelper::calculerJoursPo($jours, $initial),
            0.000001
        );
    }

    /**
     * 
     * @return void
     */
    public function testCalculerJoursPo() 
    {
        // 300 jours PO ancienneté 0
        $jours = EsahrAncienneteAncienneteCalculateurHelper::calculerJoursPo(300, 0);
        $this->assertEquals(90, $jours);

        // 150 jours PO ancienneté 500
        $jours = EsahrAncienneteAncienneteCalculateurHelper::calculerJoursPo(150, 500);
        $this->assertEquals(45, $jours);

        // 0 jours PO ancienneté 0
        $jours = EsahrAncienneteAncienneteCalculateurHelper::calculerJoursPo(0, 0);
        $this->assertEquals(0, $jours);

        // 150 jours ancienneté 1200
        $jours = EsahrAncienneteAncienneteCalculateurHelper::calculerJoursPo(150, 1200);
        $this->assertEquals(150, $jours);

        // 300 jours ancienneté 1100
        // (100 x .3) + (200 x 1) = 230
        $jours = EsahrAncienneteAncienneteCalculateurHelper::calculerJoursPo(300, 1100);
        $this->assertEquals(230, $jours);

        // 300 jours ancienneté 1300
        $jours = EsahrAncienneteAncienneteCalculateurHelper::calculerJoursPo(300, 1300);
        $this->assertEquals(300, $jours);
    }

    public function testCorrectionCalculPo() {

        $dataTests = [
            [303, 0, 90.9, .5, 90],
            [303, 0, 45.45, .25, 45],
            [
            303, // ancienneteTotalPoRaw
            1100,  // ancienneteBrutePo
            233, // ancienneteTotalPo
            .54, // chargeDecimalTotal
            230 // resultat attendu
            ],
            [
            303, // ancienneteTotalPoRaw
            1100,  // ancienneteBrutePo
            116.5, // ancienneteTotalPo
            .25, // chargeDecimalTotal
            115 // resultat attendu
            ],
        ];

        foreach ($dataTests as $key => $dataTest) {
            $jours = EsahrAncienneteAncienneteCalculateurHelper::correctionCalculPo(
                ancienneteTotalPoRaw: $dataTest[0],
                ancienneteBrutePo: $dataTest[1],
                ancienneteTotalPo: $dataTest[2],
                chargeDecimalTotal: $dataTest[3]
            );

            $this->assertEquals($dataTest[4], $jours, 'dataTest key n°'  .$key);
        }
    }
}
