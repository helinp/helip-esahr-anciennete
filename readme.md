# Calculateur d'Ancienneté pour l'ESAHR

Ce projet fournit une bibliothèque pour le calcul de l'ancienneté des membres du personnel de l'ESAHR.

![En développement](https://img.shields.io/badge/statut-en%20développement-orange?color=FFA500)

## Prérequis

- PHP 7.4 ou ultérieur

## Installation

Dès que le paquet sera disponible sur Packagist, utilisez [Composer](https://getcomposer.org/) pour l'installer :

```bash
composer require helip/esahr-anciennete
```

## Utilisation

### 1 - Création des `Attribution`

Une `Attribution` représente un travail ou une tâche spécifique attribuée à une personne pendant un `Evenement`. Chaque `Attribution` contient des détails tels que le nombre de périodes, la situation, la fonction, la catégorie et plus encore.

```php
$attribution = new Attribution(
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
```

### 2 - Création d'un ou plusieurs `Evenement`

Un `Evenement` représente une période donnée pendant laquelle une personne a travaillé. Il contient une date de début, une date de fin et une liste d'attributions liées à cette période.

```php
$evenenement = new Evenement(
    dateDebut: new DateTime('2020-09-01'),
    dateFin: new DateTime('2020-12-01'),
    attributions: [$attributionA, $attributionB, $attributionC]
);
```

### 3 - Calcul de l'ancienneté
Après avoir défini les événements et les attributions, créez une instance du `AncienneteServiceCalculateur` et utilisez la méthode `calculer` :

```php
$ancienneteService = new AncienneteServiceCalculateur();
$anciennete = $ancienneteService->calculer([$evenenement]);

// Ancienneté PO du mdp éducatif
$anciennete->get(Attribution::CAT_PERSONNEL_EDUCATIF, 'PO')

// Ancienneté CF du mdp auxiliaire
$anciennete->get(Attribution::Attribution::CAT_PERSONNEL_AUXILIAIRE, 'CF')
```

## Tests

Les tests unitaires se basent sur le Vade-Mecum, "LES ANCIENNETES STATUTAIRES - Décret du 6 juin 1994 Version ESAHR de septembre 2021" réalisé par le CECP. Pour lancer les tests :

```bash
composer test
```

## Limitations

Le calcul de l'ancienneté pour les catégories PO et CF présente des inexactitudes dans des cas spécifiques où il y a un changement de temps de travail au cours d'une année scolaire complète. Plus précisément :

    - Pour la catégorie PO, les erreurs surviennent lorsqu'un membre du personnel passe d'un temps de travail inférieur à un mi-temps à un mi-temps ou plus sur une année scolaire complète.
    - Pour la catégorie CF, les erreurs peuvent se produire si le temps de travail est inférieur à un mi-temps et qu'il y a également des heures comptabilisées en PO.

Ces problèmes découlent de notre méthode de calcul actuelle, qui traite l'ancienneté séparément pour chaque période avant de les combiner. Idéalement, nous devrions agréger toutes les périodes de travail en premier lieu et ensuite procéder au calcul de l'ancienneté.

## TODO
- Révision de l'architecture de calcul pour une agrégation préalable des périodes.
- Ajout de tests spécifiques aux limitations.
- Le calcul de l'ancienneté de fonction.

## Contribution

Les contributions sont les bienvenues ! Assurez-vous de lancer les tests avant de soumettre un pull request.

## Licence

GPL-v3