# Using Webpack Encore

Webpack Encore is a simpler way to integrate Webpack into your application.
It wraps Webpack, giving you a clean & powerful API for bundling JavaScript modules,
pre-processing CSS & JS and compiling and minifying assets.

### Js

There is an entry for `app.js`. Each entry in `webpack.config.js` will result in one JavaScript file and compiled in
`public/build`

You can reference an entry in your templates by name

    {{ encore_entry_script_tags('app') }}

### SASS

There is an entry for `style.scss` and `style-dark.scss`.
Each entry in `webpack.config.js` will result in one CSS file and compiled in `public/build`

You can reference an entry in your templates by name

    {{ encore_entry_link_tags('style') }}
    {{ encore_entry_link_tags('style-dark') }}

### Images

Image can be placed in `assets/images`. The files wil be moved when compiling to `public/build/images/`.
A random hash will be added to the file name to prevent browser caching.

You can use the `asset`-method in twig templates like below:

    {{ asset('build/images/logo-application.svg') }}

`absolute_url`-method can be wrapper around for an absolute path:

    {{ absolute_url(asset('build/images/logo-application.svg')) }}

## Usage

### While developing

    npm run watch

We have implemented live-reload, so your changes will be reloaded in the
browser. This will only happen in the dev-environment.

### Before launching your website

    npm run build

You don't have to bother if you are using deployment as we will handle it for
you.

