# Asset mapper

We use Symfony asset mapper for our css and js packages. See Symfony documentation for more information: https://symfony.com/doc/current/frontend/asset_mapper.html

## Add package

To add a package, you can run the following command:

```bash
symfony console importmap:require <package-name>
```

For example to add flatpickr:

```bash
symfony console importmap:require flatpickr
``` 

This will then automatically be added to the import map and the necessary files will be downloaded.

### Use CSS package

External CSS packages should be added in the assets/app.js, example for flatpickr:

```javascript
import 'flatpickr/dist/flatpickr.css'
```

### Use JS package

External JS packages should be added in your JS file, example for flatpickr:

```javascript
import flatpickr from 'flatpickr'
```

## Fresh install

When you don't have the packages downloaded yet, you can run the following command:

```bash
symfony console importmap:install
```

## Our CSS

Our main CSS is currently located in the framework-core-bundle as this still needs to be compile with scss for Bootstrap.
This CSS is included in the `assets/styles/style.scss` file.

To compile the CSS with SASS, you can run the following command:

```bash
symfony console sass:build
```

## Deploy

For production, we compile the assets with the following command:

```bash
symfony console asset-map:compile
```

This is normally already done for you when deploying.
