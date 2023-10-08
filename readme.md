# Calculateur d'Ancienneté pour l'ESAHR

Ce projet fournit une bibliothèque pour le calcul de l'ancienneté des membres du personnel de l'ESAHR.

## Prérequis

- PHP 7.4 ou ultérieur

## Installation

Dès que le paquet sera disponible sur Packagist, utilisez [Composer](https://getcomposer.org/) pour l'installer :

```bash
composer require helip/esahr-anciennete
```

## Utilisation

### Création d'un `Evenement`

Un `Evenement` représente une période donnée pendant laquelle une personne a travaillé. Il contient une date de début, une date de fin et une liste d'attributions liées à cette période.

```php
$evenenement = new Evenement(
    dateDebut: new DateTime('2020-09-01'),
    dateFin: new DateTime('2020-12-01'),
    attributions: [$attributionA, $attributionB, $attributionC]
);
```

### Création d'une `Attribution`

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

Après avoir défini les événements et les attributions, créez une instance du `AncienneteServiceCalculateur` et utilisez la méthode `calculer` :

```php
$ancienneteService = new AncienneteServiceCalculateur();
$anciennete = $ancienneteService->calculer([$evenenement]);
```

## Tests

Les tests unitaires se basent sur le Vade-Mecum, "LES ANCIENNETES STATUTAIRES - Décret du 6 juin 1994 Version ESAHR de septembre 2021" réalisé par le CECP. Pour lancer les tests :

```bash
composer test
```

## Limitations

- Le calcul des heures PO n'est pas encore validé.
- Le projet est toujours en cours de développement. Des changements et des améliorations sont à prévoir.

## Contribution

Les contributions sont les bienvenues ! Assurez-vous de lancer les tests avant de soumettre un pull request.

## Licence

GPL-v3