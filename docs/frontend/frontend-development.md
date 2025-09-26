# Frontend development

## Quick start
We use Symfony AssetMapper to manage our assets. See the [documentation](https://symfony.com/doc/current/frontend/asset_mapper.html) for more information.
- Install assets with `symfony console importmap:install`
- Build assets with `symfony console asset-map:compile`

## Sass
All Sass sources are included in `framework-core-bundle` bundle under `assets/scss/`.

While developing, you can run `symfony console sass:build --watch` to automatically compile on change.

### Overriding Bootstrap variables
Use `assets/scss/_bootstrap-variables.scss` to override Bootstrap variables. Import this file in `assets/scss/style.scss` before the Bootstrap imports so your overrides take effect.

Use Bootstrap variables as much as possible to customize styling — this makes the code easier to maintain.

#### Variables
- `$top-color` sets the top navbar background color
- `$menu-bg` sets the sidebar background color
- `$primary` sets the primary color used in buttons, links, etc.
- `$secondary` sets the secondary color used in buttons, links, etc.

### Dark mode
We use Bootstrap’s dark mode implementation. See the
[documentation](https://getbootstrap.com/docs/5.3/customize/color-modes/).
Most importantly, there are no separate dark mode files or stylesheets. Use `@include color-mode(dark) { ... }` to add dark mode styles. This mixin cannot be nested; put all dark mode styles in a single block at the end of the relevant SCSS file.

### Custom components or extensions (Sass)
Place custom SCSS components in `assets/scss/components/`. Import your components after the Bootstrap imports. Try to base your custom components on Bootstrap components as much as possible.
You can find frequently used components in the documentation file [components.html](https://github.com/sumocoders/FrameworkCoreBundle/blob/master/docs/frontend/components.html).

### Folder overview

```text
- assets
    - scss
        - components
            - _component-name.scss
        - _variables.scss
        - style.scss
        - mail.scss
```

## JavaScript

Most JavaScript in this bundle is provided as Stimulus controllers under `assets-public/controllers/` and utility modules under `assets-public/js/`. In your application, import the controllers in `assets/bootstrap.js`. Your `assets/app.js` typically acts as the main JavaScript entry.

### Custom components or extensions (JavaScript)
Create a `components/` folder under your app’s JavaScript directory. Add your new component module (ES6 class/module) there and import it in `app.js`.

If you want to extend or replace an existing module, update the import in `bootstrap.js` to point to your new class. The new class can be completely different or extend a class from the existing assets.

## Separate layout for the application’s frontend

For a separate public/frontend layout, use a Bootstrap 5 setup.

Create a file `style-frontend.scss` in `assets/scss/`. You can import Bootstrap as explained in the Bootstrap [documentation](https://getbootstrap.com/docs/5.3/customize/sass/#importing).

Don’t forget to add a new entry in `config/packages/symfonycasts_sass.yaml` for `style-frontend.scss` and include that entry in your frontend templates.

#### Import Bootstrap yourself

- Create a `frontend/` folder under `assets/scss/`.
- Create a `components/` folder under `frontend/` for your custom/extended components.
- Create `bootstrap-variables.scss` in the `frontend/` folder for your Bootstrap variable overrides.
- Create `bootstrap-imports.scss` in the `frontend/` folder for the Bootstrap imports.
- Import your variables file before the Bootstrap imports.
- Import your components after the Bootstrap imports.
- You can copy starter files from `vendor/sumocoders/framework-core-bundle/assets/scss/` into your new `frontend/` folder. Update variables as needed or copy them from the original Bootstrap `_variables.scss` (found in `vendor/twbs/bootstrap/scss/`). If you copy these, remove the `!default` modifiers so your overrides take effect.

### Separate JavaScript

This works the same way as Sass. Create a new entry and a new collector file (for example: `app-frontend.js`) in a separate frontend JavaScript folder in your app. Load the correct entry in your frontend templates.

## Upgrading from separate dark mode stylesheet

- Remove the style-dark.scss stylesheet
- Remove the import from style.scss
- Remove the style-dark entry from config/packages/symfonycasts_sass.yaml

## Extra

- You can group all backend Sass files into a folder named `backend` so frontend and backend are fully separated.
- Create a common `components/` folder for components shared between frontend and backend.
