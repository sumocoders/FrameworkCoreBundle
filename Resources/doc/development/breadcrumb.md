# Using the breadcrumb

The breadcrumb is a nice way to indicate where a user is in the application. 
But it shouldn't be a hassle to use it from a code-point. Therefore it is 
automated, but it can be manipulated.

There are several ways to manipulate the breadcrumb:

* start from the breadcrumb from another route
* overrule it completely

## Overrule it completely

In your controller you can implement the BreadCrumbBuilder interface and use the code below:

```php
// ...
/** @var /SumoCoders\FrameworkCoreBundle\BreadCrumb\BreadCrumbBuilder $breadCrumbBuilder */
$breadCrumbBuilder = $this->get('framework.breadcrumb_builder');

// disable the default behaviour
$breadCrumbBuilder->dontExtractFromTheRequest();

// add a full item ourself
$factory = $this->get('knp_menu.factory');
$item = (new MenuItem('foo.bar', $factory))
    ->setlabel('First!')
    ->setUri(
        $router->generate('some_route')
    );
$breadCrumbBuilder->addItem($item);
```

## Start from the breadcrumb from another route

In your controller you can implement the BreadCrumbBuilder interface and use the code below:

```php

/** @var /SumoCoders\FrameworkCoreBundle\BreadCrumb\BreadCrumbBuilder $breadCrumbBuilder */

$breadCrumbBuilder
    ->extractItemsBasedOnUri(
        $router->generate('some_route'),
        $request->getLocale()
    )
    ->addSimpleItem(
        'some.translation',
        $router->generate('the_curent_route')
    );
```

## Extra breadcrumb usage information

To use breadcrumbs without a locale we can set an empty string instead.

```php
->extractItemsBasedOnUri(
        $router->generate('some_route'),
        ''
    );
```

Add an item with only a label

```php
$breadCrumbBuilder->addSimpleItem('some.translation');
```

Add an item with a label and url, this is the same as building it yourself but with less code.

```php
$breadCrumbBuilder->addSimpleItem(
    'some.translation',
    $router->generate('some_route')
);
```
