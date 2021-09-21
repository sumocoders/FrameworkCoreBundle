# Frontend development

## Sass

The styling of the FrameworkCoreBundle is base in a separated npm package, namely [FrameworkStylepackage](https://github.com/sumocoders/frameworkStylePackage).

The base scss file is `style.scss` and is placed in `/assets/styles` in the FrameworkCoreBundle.
There is also `style-dark.scss` for the dark theme. The last default file in this folder is `mail.scss`.
These 3 files import the appropriate file from the [FrameworkStylepackage](https://github.com/sumocoders/frameworkStylePackage).

The layout is based on [Bootstrap5](https://getbootstrap.com/docs/5.1/getting-started/introduction/), so don't create new components when there's
already a Bootstrap component available. Also try to customize as much of the
components as possible through the variables file. This makes the code easier to maintain.

### Overwrite variables
Make a file `_variables.scss` in the folder styles. In this file you can set all bootstrap variables you want to override.
Import this file in `style.scss` and in `style-dark.scss`.

#### Differences in variables for dark mode
Make an extra file `_variables-dark.scss` in the folder styles and import this file in `style-dark.scss`.

### Custom components or extensions
Create a folder `components` in the styles folder. Add your new component sass file in the new folder.
To make it easy on yourself, make a `_components.scss` file in de styles folder. In this file you can import all your new components.
Example: `@import 'components/component-name';`

Now you can just easily import the `_conponents.scss` in your `style.scss` and `style-dark.scss`.
In the future you can just add the new component into `_components.scss` instead of adding it in both `style.scss` and `style-dark.scss`.

#### Differences in custom component for dark mode
Make a extra folder, for example `components-dark`, and add your `_your-component.scss` file with the same name in this folder.

You can again make a collector file for all dark components and import that file in `style-dark.scss`, 
but in this scenario it's overkill.
You can just import your custom dark mode component file in `style-dark.scss` after the import of `_components.scss`.

Example: `@import 'components-dark/component-name';`


### Overview folder structure
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

## JS

You can find `app.js` in the folder `assets/`. This is the main collector for the Javascript.
The file `imports.js` is imported in `app.js`.
These imports are the default components from our [FrameworkStylepackage](https://github.com/sumocoders/frameworkStylePackage).

We use ES6 classes for each Javascript component. More info [here](https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Classes)

### Custom components or extensions
Create a folder `components` in the js folder. Add your new component js file in the new folder.
Build your ES6 class in a new file that has the same name as your component. Do not forgot to import you new component file in `app.js`.

If you want to extend or change an existing ES6 class from the FrameworkStylepackage you can update the path to the import in `imports.js`
to a new class you made yourself. The new class can be totally different of based on the class in de FrameworkStylepackage.


## Separate layout for frontend of application

For a basic frontend layout of the application you can use a basic Bootstrap5 setup. 
We do not need FrameworkStylepackage here.

Create a file `style-frontend.scss` in de folder styles.
You can import Bootstrap in here in the way they do it in there [documentation](https://getbootstrap.com/docs/5.1/getting-started/webpack/#importing-styles)
or you can import the Bootstrap components yourself and copy the bootstrap variables.
Both work fine.

Do not forget to add a new entry in `webpack.config.js` for `style-fronted.scss` and load you entry in the head.
More info about how wepback works in this project can be found in our [documentation](https://github.com/sumocoders/FrameworkCoreBundle/blob/master/docs/frontend/webpack.md)

#### import Bootstrap yourself

- Create a `frontend` folder under folder styles
- Create a `components` folder under folder frontend for your custom/extended components
- Create a `_bootstrap-imports.scss` file under frontend folder.
    - Copy/Paste the content of `node_modules/bootstrap/scss/bootstrap.scss` in it. 
    - Update the paths from `@import "...` to `@import "~bootstrap/scss/...`.
    - Comment the first 2 imports, namely functions and variables
- Create a `_bootstrap_variables.scss` file under frontend folder.
    - Copy/Paste the content of `node_modules/bootstrap/scss/variables.scss` in it
    - Remove the ` !default` from every line in the file.
- Add imports to `style-frontend.scss`, should look like this:
```$xslt
@charset 'UTF-8';

// bootstrap
@import '~bootstrap/scss/functions';
@import '~bootstrap/scss/variables';
@import 'frontend/bootstrap-variables';
@import 'frontend/bootstrap-imports';

// components
@import 'frontend/components/component-name';
```

### Separate JS

Works the same way as the Sass. Create a new entry, a new collector js file, example: `app-frontend.js`, in a separated js frontend folder.
Load the correct entry in you frontend templates.

## Extra

- You can also group all the backend sass files and put them in a folder named backend. So the 2 are totally separated.
- Make a common `components` folder for components that are used in frontend and backend.
