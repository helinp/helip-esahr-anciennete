# Calculateur d'Ancienneté pour l'ESAHR

Ce projet fournit une bibliothèque pour le calcul de l'ancienneté des membres du personnel de l'ESAHR. Il permet de calculer l'ancienneté de service et l'ancienneté par fonction, avec un tableau détaillant le calcul.

![En développement](https://img.shields.io/badge/statut-en%20développement-orange?color=FFA500)

## Prérequis

- PHP 8.1 ou ultérieur
- Composer

## Installation

Dès que le paquet sera disponible sur Packagist, utilisez [Composer](https://getcomposer.org/) pour l'installer :

```bash
composer require helip/esahr-anciennete
```

Pour travailler directement sur le dépôt :

```bash
composer install
```

## Utilisation

### 1 - Création des `Attribution`

Une `Attribution` représente un travail ou une tâche spécifique attribuée à une personne pendant un `Evenement`. Chaque `Attribution` contient des détails tels que le nombre de périodes, la situation, la fonction, la catégorie et plus encore.

```php
use Helip\AnneeScolaire\AnneeScolaire;
use Helip\EsahrAnciennete\AncienneteServiceCalculateur;
use Helip\EsahrAnciennete\Attribution;
use Helip\EsahrAnciennete\Evenement;
use Helip\EsahrAnciennete\TableauCalculHtml;

$categorie = Attribution::CAT_PERSONNEL_EDUCATIF;
$attribution = new Attribution(
    fraction: 24,
    periodes: 12,
    situation: 'T',
    fonction: 'Violoncelle',
    categorie: $categorie,
    anneeScolaire: new AnneeScolaire('2020-2021'),
    estSubventionne: true,
    estPO: false,
    estTitreRequis: true
);
```

### 2 - Création d'un ou plusieurs `Evenement`

Un `Evenement` représente une période donnée pendant laquelle une personne a travaillé. Il contient une date de début, une date de fin et une liste d'attributions liées à cette période.

```php
$evenement = new Evenement(
    dateDebut: new DateTime('2020-09-01'),
    dateFin: new DateTime('2020-12-01'),
    attributions: [$attribution]
);
```

### 3 - Calcul de l'ancienneté

Après avoir défini les événements et les attributions, créez une instance du `AncienneteServiceCalculateur` et utilisez la méthode `calculer` :

```php
$ancienneteService = new AncienneteServiceCalculateur();
$anciennete = $ancienneteService->calculer([$evenement]);

// Ancienneté de service du mdp éducatif : 92 jours
$anciennete->get($categorie);

// Ancienneté dans la fonction de violoncelle : 92 jours
$anciennete->getParFonction($categorie, 'Violoncelle');

// Ancienneté PO du mdp éducatif
$anciennete->get($categorie, 'PO');

// Ancienneté CF du mdp auxiliaire
$anciennete->get(Attribution::CAT_PERSONNEL_AUXILIAIRE, 'CF');

// Fonctions et bilans annuels, avant troncature
$anciennete->getFonctions($categorie);
$anciennete->getParAnnee($categorie);
$anciennete->getParAnnee($categorie, 'Violoncelle');
```

### 4 - Tableau du calcul

Le détail du calcul peut être récupéré sous forme de tableau PHP ou affiché en HTML :

```php
// Tableau de l'ancienneté de service
$lignes = $anciennete->getTableauCalcul($categorie);

// Tableau de l'ancienneté dans une fonction
$lignesFonction = $anciennete->getTableauCalcul($categorie, 'Violoncelle');

// Affichage HTML
$tableau = new TableauCalculHtml();
echo $tableau->rendre($anciennete, $categorie);
echo $tableau->rendre($anciennete, $categorie, 'Violoncelle');
```

L'exemple complet de la page 14, avec ses trois fonctions, est exécutable :

```bash
php examples/tableau.php > /tmp/anciennete.html
```

Il produit 92 jours de service et, par fonction, 92 jours de violoncelle, 46 jours de musique de chambre et 46 jours d'ensemble instrumental. **Les anciennetés de fonction ne s'additionnent pas pour obtenir celle de service.**

## Résultats

`get()` et `getParFonction()` acceptent un type optionnel :

| Type | Signification |
| --- | --- |
| `CF` | Ancienneté des services subventionnés ou assimilés |
| `PO` | Ancienneté autonome des services sur fonds propres, après coefficients |
| `PO_RAW` | Jours PO bruts, sans coefficient de charge ni réduction de 0,3, plafonnés à 300 par année |
| `PO_SUM` | Part PO effectivement additionnable au CF, après règles de cumul et plafond annuel |
| `TOTAL` | CF + PO cumulable, avec la politique de troncature choisie |

Sans arguments, `getTableauCalcul()` renvoie les lignes de service de toutes les catégories. Avec une fonction, il renvoie uniquement les lignes de cette fonction. Une fonction inconnue dans une catégorie explicitement demandée provoque une exception.

Chaque ligne contient l'année, les dates incluses, les jours calendrier, les charges CF/PO, les coefficients de charge (0, 0,5 ou 1), l'ancienneté PO brute avant la période, les jours de part et d'autre du seuil des 1 200 jours, les valeurs avant/après plafond et la part PO cumulable. `contribution_totale` est la somme non tronquée de `cf_retenu` et `po_cumulable`. Les exclusions indiquent la fonction et leur motif.

Les valeurs des lignes et des bilans annuels conservent leurs décimales. Les getters finaux tronquent après cumul des années. Le tableau HTML affiche au plus six décimales ; son pied de tableau affiche les résultats finaux et la politique de troncature. Il ne faut donc pas additionner des valeurs déjà affichées/tronquées pour refaire le calcul.

## Troncature des décimales

L'exemple de la page 19 tronque séparément les financements CF et PO cumulable, donnant **248 jours**, tandis qu'une troncature globale des mêmes valeurs donne **249 jours**. Le comportement par défaut reproduit l'exemple :

```php
$moteur = new AncienneteServiceCalculateur(); // TRONCATURE_PAR_FINANCEMENT

$moteurGlobal = new AncienneteServiceCalculateur(
    AncienneteServiceCalculateur::TRONCATURE_GLOBALE
);
```

La troncature séparée porte sur le cumul de carrière de chaque financement, jamais sur chaque période. Le mode est consultable avec `getModeTroncature()`.

## Attributions et événements

Les événements peuvent être désordonnés, se chevaucher et couvrir plusieurs années scolaires au sein d'un même appel. Chaque attribution déclare son année scolaire ESAHR. Pour les établissements à 37 semaines, le rattachement des événements utilise, depuis la rentrée 2022, la date de rentrée fournie par `helip/annee-scolaire`, jusqu’à la veille de la rentrée suivante (été inclus). Avant 2022, le début de rattachement reste le 1er septembre. Un événement traversant deux années doit être scindé en deux, avec les attributions correspondantes. La fin des cours (`getDateFin()`) n’est pas une borne de rejet des services estivaux ; leur admissibilité reste à vérifier en amont. Les calendriers particuliers à 29 ou 33 semaines ne sont pas gérés. Référence : [décret du 31 mars 2022](https://www.gallilex.cfwb.be/document/pdf/50303_000.pdf), applicable aussi à l’ESAHR.

L'appelant fournit les dates de désignation et les interruptions réelles. Le moteur n'invente pas de désignation pendant les vacances et ne décide pas qu'un congé est admissible. Il ne filtre pas automatiquement une carrière à une date d'évaluation : fournir uniquement les services à prendre en compte.

Pour des services PO, utiliser `estPO: true` et `estSubventionne: false`. Le PO n'est pas exclu au seul motif qu'il n'est pas subventionné par la CF. Les anciennes entrées `estPO: true, estSubventionne: true` restent acceptées.

Les paramètres facultatifs suivants complètent `Attribution` :

| Paramètre | Défaut | Effet |
| --- | --- | --- |
| `estFonctionPrincipale` | `true` | `false` exclut les services accessoires |
| `estTitreSuffisant` | `true` | En CF, titre requis ou suffisant ; sans aucun titre, mettre aussi `estTitreRequis: false` |
| `estFonctionReglementaire` | `true` | `false` exclut une fonction hors cadre réglementaire |
| `estEncadrementPedagogique` | `false` | Génère du service, mais pas d'ancienneté de fonction |
| `estAssimileSubventionne` | `false` | Permet de valoriser une période CF non subventionnée mais légalement assimilée, après qualification par l'appelant |

En PO, le titre requis reste obligatoire, indépendamment de `estTitreSuffisant`. La situation administrative `D`, `S`, `T`, `I` ou `ST` ne remplace aucun de ces indicateurs.

### Ancienneté PO initiale

`ancienneteActuellePOEducatif` et `ancienneteActuellePOAuxiliaire` représentent les **jours PO bruts antérieurs à tout l'historique transmis**, par catégorie. Fournir cette valeur sur un événement ; elle peut être répétée à l'identique sur les autres. Zéro signifie non renseigné. Plusieurs valeurs non nulles différentes pour une même catégorie sont refusées pour éviter un double comptage.

Le moteur fait ensuite progresser le compteur automatiquement, avec au plus 300 jours bruts par année et catégorie. Les fonctions partagent le compteur de carrière de leur catégorie : changer de fonction ne redémarre pas les 1 200 jours.

## Organisation du calcul

1. `NormalisateurPeriodes` découpe les événements aux changements d'attributions. Les charges simultanées sont regroupées, sans compter deux fois les jours. Une même instance d'attribution répétée sur des événements qui se chevauchent n'est ajoutée qu'une fois ; des instances distinctes représentent des attributions distinctes. Les périodes contiguës d'une même année dont les attributions ont les mêmes valeurs (`Attribution::getSignature()`) sont ensuite regroupées : une désignation reconduite par plusieurs événements successifs donne une seule ligne dans le tableau, sans changer les résultats.
2. `AncienneteServiceCalculateur` applique l'admissibilité, puis le même moteur au service et à chaque fonction. Les catégories restent séparées.
3. Les plafonds annuels sont consommés dans l'ordre chronologique. Le plafond CF est de 150 jours si toutes les prestations CF de l'année sont inférieures au mi-temps, sinon 300. Le compteur brut PO est limité à 300 jours avant application des coefficients. Le total annuel est limité à 300 en réduisant uniquement la part PO additionnable.
4. `BilanCalcul` et `LigneCalcul` conservent les valeurs et les étapes. `AncienneteService` expose un résultat immuable, et `TableauCalculHtml` assure seulement sa présentation.

## Limitations

Cette bibliothèque calcule des jours d'ancienneté selon la référence de septembre 2021. Elle ne constitue pas un moteur de classement ou de nomination : identité des PO/réseaux, fenêtres de cinq ans, nombre minimal d'années, candidatures et conditions administratives doivent être traités en amont. Le régime transitoire des services antérieurs à septembre 2004 n'est pas automatisé. Les évolutions réglementaires ultérieures ne sont pas intégrées automatiquement.

La refonte conserve `calculer()`, les constructeurs historiques d'attribution/événement et les getters `get()`. `AncienneteService` devient un résultat immuable : ses anciennes méthodes de mutation (`add`, `setJoursCalendrier`, `setAncienneteBrutePo`, `calculerAnciennetes`, `resetCalculVariables`) sont supprimées. Utiliser désormais les événements et le calculateur. Le minimum PHP passe à 8.1.

## Tests

Les tests unitaires se basent sur le Vade-Mecum, "LES ANCIENNETES STATUTAIRES - Décret du 6 juin 1994 Version ESAHR de septembre 2021" réalisé par le CECP. Pour lancer les tests et les vérifications :

```bash
composer test
composer analyse
composer check-php
composer check-psr12
```

En environnement restreint empêchant PHPStan d'ouvrir un port local, utiliser `vendor/bin/phpstan analyse --level 7 --no-progress --debug src`.

Les tests couvrent les exemples du vadémécum, les bornes du seuil PO, le cumul CF/PO, les années et catégories distinctes, les chevauchements, le regroupement des attributions qui se suivent, les fonctions, les exclusions, la réconciliation des lignes avec les bilans, les deux politiques de troncature et le rendu HTML.

## TODO

- Prise en compte des pouvoirs organisateurs et des réseaux pour les classements.
- Vérification des conditions de priorité et de nomination.
- Prise en compte du régime transitoire des services antérieurs à septembre 2004.

## Contribution

Les contributions sont les bienvenues ! Assurez-vous de lancer les tests avant de soumettre un pull request.

## Licence

LGPL-3.0-only, conformément à `composer.json` et `license.md`.
