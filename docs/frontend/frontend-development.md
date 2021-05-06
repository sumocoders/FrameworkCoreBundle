# Frontend development

The base scss file is placed in the sass folder of the FrameworkCoreBundle. The
layout is based on Bootstrap, so don't create new components when there's
already a Bootstrap component available. Also try to customize as much of the
components as possible through the bootstrap-variables file. This makes the code
easier to maintain.

If it's not possible to customize a Bootstrap component through the bootstrap
variables, you can create a new file in the components folder where you override
the necessary properties. This has already been done for many components to
create our own layout. If you want to customize the layout you can edit the
existing code in the sass folder, or you can create new components or layout
styles by adding and importing new files.

All scss files from all bundles placed in the src directory will be put
together, which means you can import styles from other bundles from within
FrameworkCoreBundle. Bower components are also added to the import path, so you
can easily import sass files from plugins when available. Try to use Bower as
much as possible for frontend plugins to make upgrading and maintaining easier.
