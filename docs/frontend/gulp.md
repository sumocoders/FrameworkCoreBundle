# Using gulp

We are using gulp to handle a shitload of frontend related tasks, below you
can find all stuff that is done. This is more a guide for you to understand
what is happening.


### Coffee

We will look into all bundles in the `src`-folder for all files in the
`Resources/assets/coffee`-folder, these files will be copied to the
`assets/coffee`-folder in the root directory. All files will be prefixed with
the lowercased bundlename.

After that the files will be compiled into `web/assets/js`.

You can reference them in your templates

    <script type="text/javascript" src="{{ asset('/assets/js/frameworkcorebundle.framework.data.js') }}"></script>
    <script type="text/javascript" src="{{ asset('/assets/js/frameworkcorebundle.framework.locale.js') }}"></script>
    <script type="text/javascript" src="{{ asset('/assets/js/frameworkcorebundle.framework.form.js') }}"></script>
    <script type="text/javascript" src="{{ asset('/assets/js/frameworkcorebundle.framework.js') }}"></script>
    <script type="text/javascript" src="{{ asset('/assets/js/frameworkcorebundle.framework.form.search.js') }}"></script>
    <script type="text/javascript" src="{{ asset('/assets/js/frameworkcorebundle.app.js') }}"></script>


### Fonts

We will look into all bundles in the `src`-folder for all the ttf and otf-files
in the `Resources/assets/fonts`-folder, these files will be copied to
`assets/fonts`-folder in the root directory. When this is done all the other
formats (eot, svg, ttf, woff) are created in the `web/assets/fonts`-folder.

You can link to the font-files with the compass-shortcurt font-url('filename')
in your scss-files.


### Icons

We will look into all bundle in the `src` folder for svg files in the
`Resources/assets/icon-font` folder. These svg icons will be embedded in a
CSS file which will be asynchronously loaded into the web page. If the browser
doesn't support SVG, another CSS file with PNG fallbacks will be loaded.
The class names are automatically generated based on the file name of the icon.
For example, the icon with the name `arrow.svg` will generate a CSS class
with the name `icon-arrow`. To keep the class names consistent, always use
dashes in the file names, no underscores.

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


### JS

We will look into all bundles in the `src`-folder for all files in the
`Resources/assets/js`-folder, these files will be copied to the
`web/assets/js`-folder in the root directory. The folder structure you
(optionally) created will be preserved.

You can use the `asset`-method in twig templates like below:

    <img src="{{ asset('assets/js/sumo_plugins.js') }}" />


### SASS/SCSS

We will look into all bundles in the `src`-folder for all files in the
`Resources/assets/sass`-folder, these files will be copied to the
`assets/sass`-folder in the root directory. The folder structure you
(optionally) created will be preserved.

After that the files will be compiled into `web/assets/css`. When running
`gulp build` the files will be compiled as minified as possible, so without
any comments.

When the sass/scss-files are compiled we use the
[gulp-autoprefixer-plugin](https://www.npmjs.com/package/gulp-autoprefixer)


## Usage

### While developing

    gulp serve

We have implemented live-reload, so your changes will be reloaded in the
browser. This will only happen in the dev-environment.


### Before launching your website

    gulp build

You don't have to bother if you are using deployment as we will handle it for
you.

