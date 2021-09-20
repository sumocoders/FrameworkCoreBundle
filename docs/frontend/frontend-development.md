# Frontend development

## Intro

The styling of the FrameworkCoreBundle is base in a separated npm package, namely [FrameworkStylepackage](https://github.com/sumocoders/frameworkStylePackage).

The base scss file is `style.scss` and is placed in `/assets/styles` in the FrameworkCoreBundle.
There is also `style-dark.scss` for the dark theme. The last file that is by default in this folder is `mail.scss`.
All 3 of these files import the appropriate file from the [FrameworkStylepackage](https://github.com/sumocoders/frameworkStylePackage).

The layout is based on [Bootstrap5](https://getbootstrap.com/docs/5.1/getting-started/introduction/), so don't create new components when there's
already a Bootstrap component available. Also try to customize as much of the
components as possible through the variables file. This makes the code easier to maintain.

## Overwrite variables
Make a file `_variables.scss` in the folder styles. In this file you can set all bootstrap variables you want to override.
Import this file in `style.scss` and in `style-dark.scss`.

### Differences in variables for dark mode
Make an extra file `_variables-dark.scss` in the folder styles and import this file in `style-dark.scss`.

## Custom components or extensions
Create a folder `components` in the styles folder. Add your new component sass file in the new folder.
To make it easy on yourself, make a `_components.scss` file in de styles folder. In this file you can import all your new components.
Example: `@import 'components/component-name';`

Now you can just easily import the `_conponents.scss` in your `style.scss` and `style-dark.scss`.
So know in the future, you only need to add new component sass files in 1 file (`_components.scss`) instead of in 2 files.

### Differences in custom component for dark mode
Make a extra folder, for example `components-dark`, and add your `_your-component.scss` file with the same name in this folder.

You can again make a collector file for all dark components and import that file in `style-dark.scss`, 
but in this scenario it's overkill.
You can just import your custom dark mode component file in `style-dark.scss` after the import of `_components.scss`.

Example: `@import 'components-dark/component-name';`


## Overview folder structure
```$xslt
- assets
    - styles
        - components
            - _component-name.scss
        - _variables.scss
        - mail.scss
        - style.scss
        - style-dark.scss
```

You can always fall back on the folder structure or existing components in [FrameworkStylepackage](https://github.com/sumocoders/FrameworkStylePackage/tree/master/src/sass).
