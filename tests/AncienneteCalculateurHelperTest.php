<?php

namespace Tests\Helpers;

use Helip\EsahrAnciennete\AncienneteCalculateurHelper as EsahrAncienneteAncienneteCalculateurHelper;
use PHPUnit\Framework\TestCase;

class AncienneteCalculateurHelperTest extends TestCase
{
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
}
