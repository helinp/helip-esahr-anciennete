<?php

namespace Helip\EsahrAnciennete;

/** Rendu facultatif : aucune règle de calcul dans la présentation. */
final class TableauCalculHtml
{
    public function rendre(AncienneteInterface $resultat, string $categorie, ?string $fonction = null): string
    {
        $lignes = $resultat->getTableauCalcul($categorie, $fonction);
        $titre = $fonction === null ? 'Ancienneté de service' : 'Ancienneté de fonction : ' . $fonction;
        $html = '<table class="anciennete-calcul"><caption>' . self::texte($titre . ' — ' . $categorie);
        $html .= '</caption><thead><tr>';
        foreach (
            [
            'Année / période', 'Jours calendrier', 'Charge CF', 'Charge PO', 'Coefficient charge CF',
            'Coefficient charge PO', 'PO bruts avant la période', 'Jours PO à 0,3', 'Jours PO à 1',
            'Jours PO bruts retenus',
            'CF avant plafond', 'CF retenu', 'PO avant plafond', 'PO retenu', 'PO cumulable', 'Contribution totale',
            ] as $titreColonne
        ) {
            $html .= '<th scope="col">' . self::texte($titreColonne) . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($lignes as $ligne) {
            $html .= '<tr><th scope="row">' . self::texte(
                (string) $ligne['annee_scolaire'] . ' : ' . $ligne['debut'] . ' au ' . $ligne['fin']
            ) . '</th>';
            foreach (
                [
                'jours_calendrier', 'charge_cf', 'charge_po', 'coefficient_charge_cf', 'coefficient_charge_po',
                'anciennete_po_avant', 'jours_po_coefficient_03', 'jours_po_coefficient_1', 'po_brut_retenu',
                'cf_avant_plafond', 'cf_retenu',
                'po_avant_plafond', 'po_retenu', 'po_cumulable', 'contribution_totale',
                ] as $cle
            ) {
                $html .= '<td data-colonne="' . $cle . '">' . self::nombre((float) $ligne[$cle]) . '</td>';
            }
            $html .= '</tr>';
            if ($ligne['exclusions'] !== []) {
                $html .= '<tr><td colspan="16">Exclusions : '
                    . self::texte(implode(' ; ', $ligne['exclusions'])) . '</td></tr>';
            }
        }
        $get = static fn (string $type): int => $fonction === null
            ? $resultat->get($categorie, $type)
            : $resultat->getParFonction($categorie, $fonction, $type);
        $html .= '</tbody><tfoot><tr><th scope="row" colspan="11">Résultat après troncature</th>';
        $html .= '<td>' . $get('CF') . '</td><td></td><td>' . $get('PO') . '</td><td>'
            . $get('PO_SUM') . '</td><td>' . $get('TOTAL') . '</td></tr>';
        $mode = $resultat->getModeTroncature() === AncienneteServiceCalculateur::TRONCATURE_GLOBALE
            ? 'globale' : 'séparée par financement CF et PO cumulable';
        $html .= '<tr><td colspan="16">Charges exprimées en fractions de temps plein (0,5 = mi-temps). '
            . 'Les étapes conservent les décimales ; troncature finale ' . $mode . ' pour chaque année scolaire, avant cumul des années. '
            . 'Les valeurs affichées sont limitées à six décimales. '
            . 'Les plafonds sont appliqués par année, dans l’ordre chronologique ; '
            . 'les 1 200 premiers jours PO bruts de la catégorie sont valorisés à 0,3. '
            . 'Le PO autonome ne s’additionne pas nécessairement au CF : seule la part PO cumulable est retenue.'
            . '</td></tr></tfoot></table>';
        return $html;
    }

    private static function texte(string $texte): string
    {
        return htmlspecialchars($texte, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private static function nombre(float $nombre): string
    {
        return rtrim(rtrim(number_format($nombre, 6, ',', ''), '0'), ',');
    }
}
