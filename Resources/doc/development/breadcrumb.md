# Using the breadcrumb

The breadcrumb is a nice way to indicate where a user is in the application. 
But it shouldn't be a hassle to use it from a code-point. Therefore it is 
automated, but it can be manipulated.

There are several ways to manipulate the breadcrumb:

* overrule it completely
* start from the breadcrumb from another route

## Overrule it completely

In your controller you should use the code below:

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
        $this->generateUrl('some_route')
    );
$breadCrumbBuilder->addItem($item);

// add item with only a label
$breadCrumbBuilder->addSimpleItem('Second');

// add an item with a label and url, this is the same as building it ourself, 
// but with less code
$breadCrumbBuilder->addSimpleItem(
    'Third',
    $this->generateUrl('some_route')
);
```

## Start from the breadcrumb from another route

In your controller you should use the code below:

```php
$this->get('framework.breadcrumb_builder')
    ->extractItemsBasedOnUri(
        $this->generateUrl('some_route'),
        $request->getLocale()
    )
    ->addSimpleItem(
        'some.translation',
        $this->generateUrl('the_curent_route')
    );
```
