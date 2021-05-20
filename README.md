# Specbee Robo Tooling

The composer package intends to provide an automation layer using Robo commands to setup, build, test & deploy Drupal applications.

## Installation requisites
 - Lando
 - Docker
 - PHP >= 7.4
 - Composer v2

## Creating a new project
The package comes pre-installed with Specbee's Drupal starterkit https://github.com/SpecbeeLabs/drupal-starterkit

```
composer create-project specbee/drupal-starterkit:9.x-dev projname --no-interaction
```

## Adding to existing project
To add the package to an existing project
```
composer require specbee/robo-tooling:1.x-dev
```

## Configuration
* Copy the `example.robo.yml` and rename it `robo.yml` to the root directory if not added already
* Update the `robo.yml` to change the configurations based on your requirements.

Once, done run `vendor/bin/robo init-repo` which will:

- Setup Drush aliases
- Configure the Landofile
- Configure Grumphp for checking commits

## Usage
Run `lando start` to spin up the containers used to run the application.

Once the lando containers are running, run the lando command

```
lando robo setup -n
```

This will install a fresh Drupal site using the installation profile _drupal.profile_ mentioned in the `robo.yml`. After which if existing configurations are present those will be imported and theme will be build if present.

## Tooling
The package provides the following tooling commands to automate development tasks.

All the commands can be accessed under lando namespace. `lando robo <command>`

| Task                                            | Command                                         |
|-------------------------------------------------|-----------------------------------------------|
| Setup the site from scratch | ```robo setup``` |
| Running database updates and importing configurations| ```robo drupal:update```|
| Sync database and files from remote environment defines under _remote_ in `robo.yml` | ```robo sync:all```|
| Sync database from remote environment defines under _remote_ in `robo.yml` | ```robo sync:db```|
| Sync files from remote environment defines under _remote_ in `robo.yml` | ```robo sync:files```|
| Validate files - Check composer validation, Run PHPCS against modules and themes, Check SASS Lint in the theme | ```robo validate```|
| Initialize and setup Redis caching | ```robo init:service:cache```|
| Running Behat test | ```robo test:behat```|
| Running PHPUnit test | ```robo test:phpunit```|
| Run deployment commands | ```robo test:phpunit```|


