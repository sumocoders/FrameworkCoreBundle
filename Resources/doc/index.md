# Getting Started With FrameworkCoreBundle

## Installation

Add FrameworkCoreBundle as a requirement in your composer.json:

```
{
    "require": {
        "sumocoders/framework-core-bundle": "dev-master"
    }
}
```

**Warning**
> Replace `dev-master` with a sane thing

Run `composer update`:

Enable the bundle in the kernel.

```php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new SumoCoders\FrameworkCoreBundle\SumoCodersFrameworkCoreBundle(),
    );
}
```

## Error handling
Enable the FrameworkErrorBundle in the kernel, just add it in production mode, as this bundle
is intended to handle errors so our visitors don't freak out.

```php
<?php
// app/AppKernel.php

public function registerBundles()
{
    // ...
    if (in_array($this->getEnvironment(), array('prod'))) {
        $bundles[] = new SumoCoders\FrameworkErrorBundle\SumoCodersFrameworkErrorBundle();
    }
}
```

