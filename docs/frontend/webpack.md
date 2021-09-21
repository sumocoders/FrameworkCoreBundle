# Using Webpack Encore

Webpack Encore is a simpler way to integrate Webpack into your application. 
It wraps Webpack, giving you a clean & powerful API for bundling JavaScript modules, 
pre-processing CSS & JS and compiling and minifying assets.


### Js

There is an entry for `app.js`. Each entry in `webpack.config.js` will result in one JavaScript file and compiled in `public/build`

You can reference an entry in your templates by name

    {{ encore_entry_script_tags('app') }}


### SASS/SCSS

There is an entry for `style.scss` and `style-dark.scss`. 
Each entry in `webpack.config.js` will result in one CSS file and compiled in `public/build`

You can reference an entry in your templates by name

    {{ encore_entry_link_tags('style') }}
    {{ encore_entry_link_tags('style-dark') }}


### Images

We will look into all bundles in the `src`-folder for all files in the
`Resources/assets/images`-folder, these files will be copied to the
`web/assets/images`-folder in the root directory. The folder structure you
(optionally) created will be preserved.

*Important*: make sure you don't have duplicate filenames as the files will be
overwritten.

You can link to the font-files with the compass-shortcurt image-url('filename')`
in your scss-files.

When running `gulp build` the images will be optimized for web.

You can use the `asset`-method in twig templates like below:

    <img src="{{ asset('assets/images/arrow_show_menu.png') }}" />


## Usage

### While developing

    npm run watch

We have implemented live-reload, so your changes will be reloaded in the
browser. This will only happen in the dev-environment.


### Before launching your website

    npm run build

You don't have to bother if you are using deployment as we will handle it for
you.

