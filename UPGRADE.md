# Upgrade guide

## JS

Sinds versie 5 zijn ale data attrbiuten van Bootstrap ge"namespaced". Dit wil dus zeggen dat ipv data-toggle, het nu data-*bs*-toggle is.

Je kan dit oplossen door een search & replace te doen door heel je project.

## PHP

### Breadcrumbs
De breadcrumbs in onze core bundle zijn niet BC. Als ze nog op de oude manier werken (met de listener in de controller) dan moet je dit manueel refactoren.

Als het enkel annotation -> attributen is, kan je volgende Rector sets gebruiken:

```php
DoctrineSetList::ANNOTATIONS_TO_ATTRIBUTES,
SymfonySetList::ANNOTATIONS_TO_ATTRIBUTES,
```

### Pagination
Sinds v5.1.0 van de core bundle is paginatie niet meer met Pagerfanta, maar met onze eigen versie. Hiervoor zijn er een aantal changes (manueel) nodig:
* Vervang in de repository alle oude `Pagerfanta` objecten door onze eigen `Paginator` objecten. Beide hebben een QueryBuilder als parameter, zelfde werking.
* Vervang in je template de oude Pagerfanta Twig helper door onze eigen Twig helper.
```twig
    {{ pagination(your_paginated_items) }}
```
* Pas je Controllers aan naar de nieuwe werking:
```php
// getItems returns a Paginator object with a QueryBuilder inside.
$paginatedItems = $itemRepository->getItems();

$paginatedItems->paginate($request->query->getInt('page', 1));
```

### VO -> ENUM
Vroeger gebruikten we veel ValueObjects, ook om simpele string values op te slaan. Sinds PHP8 hebben we hiervoor native enums.

Je kan je VOs dus omzetten naar enums (indien ze enkel een vaste lijst string values kunnen hebben). Zaken zoals Money, Coordinates of Range zijn nog steeds VOs. Zaken zoals Gender, Province, etc.. zijn dus enums.

```php
enum Gender: string
{
    case MALE = 'male';
    case FEMALE = 'female';
}
```
### Annotation -> attributes
Gebruik Rector, met volgende config:
```php
<?php

use Rector\DeadCode\Rector\ClassMethod\RemoveUselessParamTagRector;
use Rector\Doctrine\Set\DoctrineSetList;
use Rector\Php54\Rector\Array_\LongArrayToShortArrayRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Symfony\Set\SymfonySetList;
use Rector\Symfony\Set\SensiolabsSetList;
use Rector\Config\RectorConfig;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictTypedPropertyRector;
use Rector\TypeDeclaration\Rector\Param\ParamTypeFromStrictTypedPropertyRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromAssignsRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictConstructorRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictGetterMethodReturnTypeRector;

return function (RectorConfig $rectorConfig): void {
    $rectorConfig->sets([
        DoctrineSetList::ANNOTATIONS_TO_ATTRIBUTES,
        SymfonySetList::ANNOTATIONS_TO_ATTRIBUTES,
        SymfonySetList::SYMFONY_53,
        SymfonySetList::SYMFONY_54,
        SymfonySetList::SYMFONY_60,
        SymfonySetList::SYMFONY_61,
        SymfonySetList::SYMFONY_62,
        SymfonySetList::SYMFONY_CODE_QUALITY,
        SymfonySetList::SYMFONY_CONSTRUCTOR_INJECTION,
    ]);

    $rectorConfig->rules([
        ClassPropertyAssignToConstructorPromotionRector::class,
        ParamTypeFromStrictTypedPropertyRector::class,
        TypedPropertyFromStrictConstructorRector::class,
        TypedPropertyFromStrictGetterMethodReturnTypeRector::class,
        LongArrayToShortArrayRector::class,
        TypedPropertyFromAssignsRector::class,
        ReturnTypeFromStrictTypedPropertyRector::class,
        ParamTypeFromStrictTypedPropertyRector::class,
        RemoveUselessParamTagRector::class,
    ]);
};
```

### Security
config/packages/security.yaml
```yaml
    # oud
    encoders:
        App\Entity\User\User:
            algorithm: auto

    # nieuw
    password_hashers:
        Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface: 'auto'
```

### Config

config/packages/sentry.yaml
```yaml
when@prod:
  sentry:
    dsn: '%env(SENTRY_DSN)%'
    options:
      integrations:
        - 'Sentry\Integration\IgnoreErrorsIntegration'
  
services:
  Sentry\Integration\IgnoreErrorsIntegration:
    arguments:
      $options:
        ignore_exceptions:
          - 'Symfony\Component\HttpKernel\Exception\NotFoundHttpException'
          - 'Symfony\Component\Security\Core\Exception\AccessDeniedException'

```