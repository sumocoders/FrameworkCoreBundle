# Using deployment

## Install deployer

To use the deployer we need to have a local installation of the deployer.
Run the following commands in the terminal:

```
curl -LO https://deployer.org/deployer.phar
mv deployer.phar /usr/local/bin/dep
chmod +x /usr/local/bin/dep
```

The deploy settings can be found in the deploy.php file.

## Configuration

You will need to fill in 4 variables:

* `:client`, the `xxx` should be replaced with the name of the client.
* `:project`, the `xxx` should be replaced with the name of the project.
* `:repository`, the `xxx` should be replaced with the url of the git-repo.
* `:production_url`, the `xxx` should be replaced with the production url.

Remark: when choosing a name for the project, please don't use generic names
as: site, app, ... as it makes no sense when there are multiple projects for
that client.

### Define staging server

Define the staging server in the deploy file and adjust the variables if needed.

```
host({{ host }})
    ->user({{ username }})
    ->stage('staging')
    ->set('deploy_path', '~/apps/{{client}}/{{project}}')
    ->set('branch', 'staging')
    ->set('bin/php', 'php7.2')
    ->set('cachetool', '/var/run/php_71_fpm_sites.sock')
    ->set('document_root', '~/php72/{{client}}/{{project}}');
```

### Define production server

We can use the same approach to deploy to production.
Change the stage to production and adjust settings where needed.

```
host({{ host }})
    ->user({{ username }})
    ->stage('production')
    ->set('deploy_path', '~/apps/{{client}}/{{project}}')
    ->set('branch', 'staging')
    ->set('bin/php', 'php7.2')
    ->set('cachetool', '/var/run/php_71_fpm_sites.sock')
    ->set('document_root', '~/php72/{{client}}/{{project}}');
```

## Deploy commands

List all possible deploy commands

    dep list

## Deploying for the first time

First of all you need to create the database. If you are deploying to the
SumoCoders-staging server you can use the following command:

    dep sumo:db:create staging

You have two options: creating an empty database, or putting
your local database online by using following command.

    dep sumo:db:put <stage>

Deploy:

    dep deploy staging

When using an empty database it is important to migrate the "empty" database to the latest migration
to keep the database up to date and avoid errors.

    dep database:migrate <stage>

When deploying to staging for the first time the database credentials will need to be set in the .env.local file
located in the shared folder.

    DATABASE_URL=mysql://db_user:db_password@127.0.0.1:3306/db_name

Official documentation to connect to the database can be found [here](https://symfony.com/doc/current/doctrine.html)

## Deploy

    dep deploy <stage>
