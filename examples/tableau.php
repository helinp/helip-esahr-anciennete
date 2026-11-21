<?php

// php examples/tableau.php > /tmp/anciennete.html
require dirname(__DIR__) . '/vendor/autoload.php';

use Helip\AnneeScolaire\AnneeScolaire;
use Helip\EsahrAnciennete\AncienneteServiceCalculateur;
use Helip\EsahrAnciennete\Attribution;
use Helip\EsahrAnciennete\Evenement;
use Helip\EsahrAnciennete\TableauCalculHtml;

$attributions = [];
foreach (['Violoncelle' => 12, 'Musique de chambre instrumentale' => 6, 'Ensemble instrumental' => 2] as $fonction => $periodes) {
    $attributions[] = new Attribution(
        fraction: 24,
        periodes: $periodes,
        situation: 'T',
        fonction: $fonction,
        categorie: Attribution::CAT_PERSONNEL_EDUCATIF,
        anneeScolaire: new AnneeScolaire('2020-2021'),
        estSubventionne: true,
        estPO: false,
        estTitreRequis: true
    );
}
$resultat = (new AncienneteServiceCalculateur())->calculer([
    new Evenement(new DateTime('2020-09-01'), new DateTime('2020-12-01'), $attributions),
]);
$rendu = new TableauCalculHtml();
$categorie = Attribution::CAT_PERSONNEL_EDUCATIF;

echo '<!doctype html><html lang="fr"><meta charset="UTF-8"><title>Exemple de calcul ESAHR</title>';
echo '<style>body{font:16px system-ui;margin:2rem}table{border-collapse:collapse;margin:2rem 0}';
echo 'th,td{border:1px solid #bbb;padding:.5rem;text-align:right}caption{font-weight:bold;text-align:left}';
echo 'thead,tfoot{background:#f5f5f5}tfoot td{max-width:90rem}</style>';
echo '<h1>Ancienneté de service et de fonction</h1><p>Exemple de la page 14 du vadémécum ESAHR 2021.</p>';
echo $rendu->rendre($resultat, $categorie);
foreach ($resultat->getFonctions($categorie) as $fonction) {
    echo $rendu->rendre($resultat, $categorie, $fonction);
}
echo '</html>';
