# Initial bundle

## Naming convention

All official bundles use the same naming convention. The convention is fairly 
easy: "FrameworkXXXBundle", where XXX is replaced with a descriptive name of 
the basic functionality.


## Minimal bundle

A minimum bundle contains at least the following files

* .gitignore
* .travis.yml
* composer.json
* phpunit.xml.dist
* README.md
* SumoCodersFrameworkXXXBundle.php
* Resources/doc/index.md
* Resources/meta/LICENSE

Below I will explain what each file should do/contains.


### .gitignore

This file contains some files/folders that should be ignored by git.

    /vendor/
    composer.lock
    coverage.xml
    
The `vendor`-folder is ignored as the Bundle won't exists on its own, and the 
dependencies will be installed in your project. But the dependencies are 
required for testing.

`composer.lock` isn't submit as it will be ignored when the bundle is installed
in a project.

`coverag.xml` is a file that will be generated when running the tests in our 
CI-tool. Normally it won't be generated when running the tests manually.
    

### .travis.yml

This file will configure Travis CI to build and test our bundle/project.

    language: php
    
    matrix:
      include:
        - php: 5.4
        - php: 5.5
        - php: 5.6
        - php: 7
        - php: nightly
        - php: hhvm
        - php: hhvm-nightly
      allow_failures:
        - php: 7
        - php: nightly
        - php: hhvm
        - php: hhvm-nightly
    
    before_script:
      - travis_retry composer self-update
      - travis_retry composer install --no-interaction --prefer-source --dev
    
    script:
      - vendor/bin/phpunit --verbose --coverage-text  --coverage-clover=coverage.clover
    
    after_success:
      - wget https://scrutinizer-ci.com/ocular.phar
      - php ocular.phar code-coverage:upload --format=php-clover coverage.clover

There are several sections in this yml-file. 

The `matrix` defines which versions of PHP we will test against and which are 
allowed to fail. For now the minimum is PHP 5.4 until 5.6 that should pass the
tests.

The `before_script` defines which commands should be run before the actual 
scripts are run. We make sure composer is updated and the vendors are installed.

The `script` defines which scripts should be ran, here we run the tests. As we 
install phpunit as a dependency we use that executable. As you see we output 
a coverage-report as it will be handled by Scrutinizer.

The `after_success` section will send the coverage-report to Scrutinizer once 
the scripts are finished.


### composer.json

This file contains the dependencies and required attributes for publication on 
[packagist.org](http://www.packagist.org).

    {
      "name": "sumocoders/framework-xxx-bundle",
      "type": "symfony-bundle",
      "description": "...,
      "keywords": ["SumoCoders"],
      "license": "MIT",
      "authors": [
        {
          "name": "John Doe",
          "email": "John@example.net"
        }
      ],
      "minimum-stability": "dev",
      "prefer-stable": true,
      "require": {
        "php": ">=5.4"
      },
      "require-dev": {
        "phpunit/phpunit": "4.*"
      },
      "autoload": {
        "psr-0": { "SumoCoders\\FrameworkXXXBundle": "" }
      },
      "target-dir": "SumoCoders/FrameworkXXXBundle"
    }
    
### phpunit.xml.dist

This file will be used by PHPUnit to configure the tests.

    <?xml version="1.0" encoding="UTF-8"?>
    <phpunit backupGlobals="false"
             backupStaticAttributes="false"
             bootstrap="vendor/autoload.php"
             cacheTokens="true"
             colors="true"
             convertErrorsToExceptions="true"
             convertNoticesToExceptions="true"
             convertWarningsToExceptions="true"
             processIsolation="false"
             stopOnFailure="false"
             syntaxCheck="false"
    >
        <php>
            <server name="KERNEL_DIR" value="Tests/Controller/App" />
        </php>
        <testsuites>
            <testsuite name="Bundle test suite">
                <directory suffix="Test.php">./Tests</directory>
            </testsuite>
        </testsuites>
    
        <filter>
            <whitelist>
                <directory>./</directory>
                <exclude>
                    <directory>./Resources</directory>
                    <directory>./Tests</directory>
                    <directory>./vendor</directory>
                </exclude>
            </whitelist>
        </filter>
    
        <logging>
            <log type="coverage-clover" target="./coverage.clover"/>
        </logging>
    </phpunit>


### README.md

This file 

    # SumoCoders FrameworkXXXBundle
    
    ... replace with badges ...
    
    ... describe the basic functionality ...
    
    ## Documentation
    
    All documentation is located in the `Resources/doc/index.md`
    
    ## License
    
    This bundle is under the MIT license. See the complete license in the bundle:
    
        Resources/meta/LICENSE
    
    ## About
    
    FrameworkXXXBundle is a bundle created by [SumoCoders](https://github.com/sumocoders)
    and is intended to be used with the Framework
    
    ## Issues?
    
    Feel free to add an Issue on Github, or even better create a Pull Request.


### SumoCodersFrameworkXXXBundle.php

This file is the minimum to exists as a bundle.

    <?php
    
    namespace SumoCoders\FrameworkXXXBundle;
    
    use Symfony\Component\HttpKernel\Bundle\Bundle;
    
    class SumoCodersFrameworkXXXBundle extends Bundle
    {
    }
    

### Resources/doc/index.md

In this file we describe on how the bundle can be used. At least provide two 
sections: Installation and Usage.

    # Getting Started With FrameworkXXXBundle
    
    ## Installation
    
    Add FrameworkXXXBundle as a requirement in your composer.json:
    
    ```
    {
        "require": {
            "sumocoders/framework-xxx-bundle": "dev-master"
        }
    }
    ```
    
    **Warning**
    
    > Replace `dev-master` with a sane thing
    
    Run `composer update`
    
    Enable the bundle in the kernel.
    
    ```php
    <?php
    // app/AppKernel.php
    
    public function registerBundles()
    {
        // ...
        $bundles = [
            // ...
            new SumoCoders\FrameworkXXXBundle\SumoCodersFrameworkXXXBundle(),
        ];
    }
    ```
    
    ## Usage
    
    ... describe on how the bundle can be used ...
    
    ```

### Resources/meta/LICENSE

This file contains our LICENCE, all our bundles are released under the MIT.

    Copyright (c) SumoCoders
    
    Permission is hereby granted, free of charge, to any person obtaining a copy
    of this software and associated documentation files (the "Software"), to deal
    in the Software without restriction, including without limitation the rights
    to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
    copies of the Software, and to permit persons to whom the Software is furnished
    to do so, subject to the following conditions:
    
    The above copyright notice and this permission notice shall be included in all
    copies or substantial portions of the Software.
    
    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
    IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
    FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
    AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
    LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
    OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
    THE SOFTWARE.


# Create a (public) repo

Once the initial folder structure is created you can push the initial state 
into your versioning system. At SumoCoders we use gitlab or GitHub for our 
public bundles.

For a public repo on GitHub you can follow the steps below:

1. Go to [github.com](https://github.com).
2. Log in with your Github-account.
3. Click "New repository".
4. Give it the same name as your bundle, eg.: FrameworkXXXBundle.
5. Fill in the Description.
6. Click "Create repository".
7. Open the settings.
8. Open the tab "Options".
9. Disable "Wikis".
10. Enable "Issues".

Now you can push the initial state by running the commands below:

1. Open up Terminal.app
2. `cd` to the correct directory
3. `git init`
4. `git add .`
5. `git commit -m "Initial commit"`
6. `git remote add origin https://github.com/xxx/xxx.git`
7. `git push -u origin/master`

If you are publishing a bundle as a member of SumoCoders

* Make sure you add it under the SumoCoders Organization
* Give the "PHP Team" access under Collaborators.
       
# External tools

We use some external tools, each with its own task.

## Publish the bundle as a package

Packagist is used to expose our bundles into the wild. With packagist we can 
install our bundles through `composer`.

1. Go to [packagist.org](https://packagist.org).
2. Log in with your Packagist-account.
3. Click "Submit Package"
4. Enter the repo-url

Because we want the project to be updated each time we push something new, we 
will configure a service.

1. Go to the correct repo on [GitHub](https://github.com).
2. Click "Settings".
3. Select "Webhooks & Services".
4. Click "Add Service".
5. Search for "Packagist".

## Enable Travis CI

1. Go to [travis-ci.org](https://travis-ci.org).
2. Log in with your GitHub-account.
3. Click the `+`-icon on the left to add a repository.
4. If you can't find your repo, click "Sync" at the top.
5. Enable the correct repo.

Because we want the project to be build and tested each time we push something 
new, we will configure a service.

1. Go to the correct repo on [GitHub](https://github.com).
2. Click "Settings".
3. Select "Webhooks & Services".
4. Click "Add Service".
5. Search for "Travis CI".


As it is useful to see if everything works we wil add a badge into our 
`README.md`-file. 

1. Go to [travis-ci.org](https://travis-ci.org).
2. Log in with your GitHub-account.
3. Click the correct repo under "My Repositories".
4. Click the badge at the top.
5. Make sure "master" is selected under Branch.
6. Select "Markdown" in the type-dropdown.
7. Copy the code.
8. Insert it as the first badge into the `README.md`-file.


## Enable Scrutinizer

1. Go to [scrutinizer-ci.com](https://scrutinizer-ci.com).
2. Log in with your GitHub-account.
3. Click "Add Repository".
4. Select "PHP" under "Default Config"

As it is useful to see what the code quality is, we will add a badge into our 
`README.md`-file. 

1. Go to [scrutinizer-ci.com](https://scrutinizer-ci.com).
2. Log in with your GitHub-account.
3. Click the correct repo under "Repositories".
4. Click the tiny "i"-icon next to the Scrutinizer-badge.
5. Copy the code next to "Markdown".
6. Insert it as the second badge into the `README.md`-file.


## Enable Code Coverage on Scrutinizer

1. Go to [scrutinizer-ci.com](https://scrutinizer-ci.com).
2. Log in with your GitHub-account.
3. Click the correct repo under "Repositories".
4. Click the "settings"-icon.
5. Select "Configuration".
6. Enter the code below in the "Repository Config".
7. Save the config.

    checks:
        php:
            uppercase_constants: true
            phpunit_assertions: true
            remove_extra_empty_lines: true
            fix_line_ending: true
            use_self_instead_of_fqcn: true
            simplify_boolean_return: true
            properties_in_camelcaps: true
            parameters_in_camelcaps: true
            parameter_doc_comments: true
            param_doc_comment_if_not_inferrable: true
            newline_at_end_of_file: true
            line_length:
                max_length: '120'
            function_in_camel_caps: true
            classes_in_camel_caps: true
            avoid_perl_style_comments: true
    coding_style:
        php: { }
    tools:
        external_code_coverage: true
        php_code_sniffer:
            config:
                standard: "PSR2"

As it is useful to see what the code coverage is, we will add a badge into our 
`README.md`-file. 

1. Go to [scrutinizer-ci.com](https://scrutinizer-ci.com).
2. Log in with your GitHub-account.
3. Click the correct repo under "Repositories".
4. Click the tiny "i"-icon next to the Coverage-badge.
5. Copy the code next to "Markdown".
6. Insert it as the third badge into the `README.md`-file.


## Enable Sensio Labs Insights

1. Go to [insight.sensiolabs.com](https://insight.sensiolabs.com).
2. Log in.
3. Click "+ Add Project"
4. Select "Symfony2 Bundle"

Because we want the analyzer to be ran each time we push something new, we will
configure a hook.

1. Go to [insight.sensiolabs.com](https://insight.sensiolabs.com).
2. Log in.
3. Click "Account".
4. Scroll down to "Getting the Project UUID".
5. Select the correct project under "Project Name".
6. Scroll down to "For GitHub Repositories".
7. Copy the WebHook URL.
8. Go to the correct repo on [GitHub](https://github.com).
9. Click "Settings"
10. Select "Webhooks & Services"
11. Click "Add webhook"
12. Paste the URL into "Payload URL".
13. Click "Add Webhook"

As it is useful to see what the code quality is, we will add a badge into our 
`README.md`-file. 

1. Go to [insight.sensiolabs.com](https://insight.sensiolabs.com).
2. Log in.
3. Click the correct repo under "Projects".
4. Click the badge on the right.
5. Scroll to "Mini widget".
5. Copy the code next to "Markdown".
6. Insert it as the fourth badge into the `README.md`-file.
