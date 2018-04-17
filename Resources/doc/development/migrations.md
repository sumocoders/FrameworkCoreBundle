# Changing the database

While developing you sometimes need to change the database. But to be able to 
change the database on the server while deploying instead of doing these 
changes manually we will use migrations.

Migrations in the framework are handled by the [DoctrineMigrationsBundle](http://symfony.com/doc/current/bundles/DoctrineMigrationsBundle/index.html).

## Usage

Basically it is simple. The only thing you have to do when you need to change 
the database structure is to change the annotation/configuration/... and run:
 
    app/console doctrine:migrations:diff
    
This will generate a migration-class which you can commit along your changes.
Of course you will need to run the migration to update your own database:

    app/console doctrine:migration:migrate
