# Using deployment

## Local alias

You should configure an `alias`:

```
dep='symfony php vendor/bin/dep'
```

With this you can run `dep` instead of `symfony php vendor/bin/dep`.

## Configuration

You will need to fill in 4 variables:

* `:client`, the `xxx` should be replaced with the name of the client.
* `:project`, the `xxx` should be replaced with the name of the project.
* `:repository`, the `xxx` should be replaced with the url of the git-repo.
* `:production_url`, the `xxx` should be replaced with the production url.
* `:production_user`, the `xxx` should be replaced with the production user.

Remark: when choosing a name for the project, please don't use generic names
as: site, app, ... as it makes no sense when there are multiple projects for
that client.

## Deploy commands

List all possible deploy commands

    dep list

## Deploy

    dep deploy stage=staging

## Deploy images and files

In the deploy.php script we can set the shared directory.
This is how the local and remote server will be able to download or upload the correct directory.

    Example: add('shared_dirs', ['public/files']);

Please keep in mind that when executing the get or put files command that the files are being replaced.

Get all files from remote server to local

    dep sumo:files:get <stage>

Put all files from local to remote server

    dep sumo:files:put <stage>
