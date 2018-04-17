# Using deployment

We use Capifony as a base, but we extended it with our own specific
configuration for usage with the framework.

All configuration for the specific stage (staging, production) is done in the
`deployments/stages/`-folder, wherein each stage has its own file.

General configuration is done in the Capfile.

## Configuration

### Capfile

When you create a project you will need to fill in 3 variables:

* `:client`, the `xxx` should be replaced with the name of the client.
* `:project`, the `xxx` should be replaced with the name of the project.
* `:repository`, the `xxx` should be replaced with the url of the git-repo.

Remark: when choosing a name for the project, please don't use generic names
as: site, app, ... as it makes no sense when there are multiple projects for
that client.

### deployments/stages/staging.rb

In the is file you can configure which branch should be deployed on the
staging-server. By default this is the staging-branch

* `:branch`, if needed you can replace `staging` with your branch.

Remark: when you change this, make sure you notify other people, as when they
deploy and are not working on your branch your changes will be lost. Also make
sure that you change it back before creating a Pull Request.

### deployments/stages/production.rb

In the is file you can configure production specific items. In most cases the
server-layout will be different from the staging-server, so there is somewhat
more to configure.

The default production.rb is inspired on the Hostbots-server-layout.

* `set :host`, replace `xxx` with the hostname/ip of the server.
* `set :user`, replace `xxx` with the ssh-username.
* `set :domain`, replace `xxx` with the final domain of the application,
    without http(s)://.
* `set :deploy_to`, you can alter this if you need to deploy the application in
    a specific directory.
* `set :document_root`, this should reflect the document_root.

## Putting the site in maintenance

Sometimes it can be usefull to set the site into maintenance mode.

Disable the site:

    cap <stage> deploy:web:disable

You can specify a reason for the downtime by setting a REASON:

    cap <stage> deploy:web:disable REASON="the reason"

You can specify when the site will be back online by setting a DEADLINE:

    cap <stage> deploy:web:disable DEADLINE="at 20 june 2015 14:00"

Enable the site:

    cap <stage> deploy:web:disable

## Deploying the site for the first time

First of all you need to create the database. If you are deploying to the
SumoCoders-staging server you can use the following command:

    cap staging sumo:db:create

Deploy for the first time:

    cap <stage> deploy

A lot of questions will be asked, this will generate the `parameters.yml`-file.
Answer the questions with sane things.

Depending on the environment you should give other answers:

### Staging

* debug_email:  null
* database.driver: pdo_mysql
* database.host: 127.0.0.1
* database.port: null
* database.name: the name of the database you created
* database.user: the user for the database you created
* database.password: the password for the database you created
* mailer.transport: mail
* locales: enter an array with the languages that should be available
* locale: enter the default locale
* secret: this should be a random string
* sentry.dsn: null

### Production

* debug_email:  bugs@sumocoders.be
* database.driver: pdo_mysql
* database.host: the host for the database
* database.port: null, unless the database is running on a non-default port
* database.name: the name of the database you created
* database.user: the user for the database you created
* database.password: the password for the database you created
* mailer.transport: mail
* locales: enter an array with the languages that should be available
* locale: enter the default locale
* secret: this should be a random string
* sentry.dsn: the sentry api key you have created for this project, see [Sentry](https://sentry.io)

When this is done you have two options: creating an empty database, or putting
your local database online.

The second one is the one which requires the least amount of work:

    cap database:copy:to_remote

The first options requires some extra steps. First of all you should create the
schema. As we use migrations this is best done by migrating the "empty"
database to the latest migration:

    cap <stage> symfony:doctrine:migrations:migrate

When this is done you will need to create a user:

    cap <stage> framework:setup:create_user
