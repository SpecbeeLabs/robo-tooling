<?php

namespace SbRoboTooling\Robo\Plugin\Commands;

use Robo\Exception\TaskException;
use Robo\Result;
use Robo\Tasks;
use SbRoboTooling\Robo\Traits\UtilityTrait;
use Symfony\Component\Yaml\Yaml;

/**
 * Defines commands in the test:* namespace.
 */
class CacheServiceCommand extends Tasks
{
    use UtilityTrait;

    /**
     * Setup redis.
     *
     * @command init:service:cache
     */
    public function initServiceCache()
    {
        $this->say('init:recipe-redis');
        $this->io()->section('Adding the Drupal Redis module via composer.');
        $this->taskComposerRequire()
        ->dependency('drupal/redis')
        ->ansi()
        ->noInteraction()
        ->run();

        $this->say('Enabling Redis module..');
        $this->drush()
        ->args('pm-enable')
        ->args('redis')
        ->arg('--no-interaction')
        ->run();

        $task = $this->taskWriteToFile($this->getDocroot() . '/' . $this->getConfigValue('drupal.webroot') . '/sites/default/settings.php')
        ->append()
        ->line('# Redis Configuration.')
        ->line('$conf[\'redis_client_host\'] = \'cache\';')
        ->line('require DRUPAL_ROOT . "/../vendor/specbee/robo-tooling/settings/redis.settings.php";')
        ->run();
        if (!$task->wasSuccessful()) {
            throw new TaskException($task, "Could not udpate settings.php");
        }

        $confirm = $this->io()->confirm('Do you want to update Landofile to add the redis service?', true);
        if (!$confirm) {
            return Result::cancelled();
        }
        $landoFileConfig = Yaml::parse(file_get_contents($this->getDocroot() . '/.lando.yml', 128));
        $this->say('Checking if there is cache service is setup.');
        if (!array_key_exists('cache', $landoFileConfig['services'])) {
            $landoFileConfig['services']['cache'] = [
            'type' => 'redis:4.0',
            'portforward' => true,
            'persist' => true,
            ];
            $landoFileConfig['tooling']['redis-cli'] = [
            'service' => 'cache',
            ];

            file_put_contents($this->getDocroot() . '/.lando.yml', Yaml::dump($landoFileConfig, 5, 2));
            $this->io()->note('Lando configurations are updated with cache service.');
            $this->io()->note('Do a `lando rebuild` for the change to take effect.');
        } else {
            $this->io()->note('Cache service is already added to Lando configuration. Skipping..');
        }
    }
}
