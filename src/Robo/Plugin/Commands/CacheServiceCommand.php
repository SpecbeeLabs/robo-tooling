<?php

namespace SbRoboTooling\Robo\Plugin\Commands;

use Robo\Contract\VerbosityThresholdInterface;
use Robo\Exception\TaskException;
use Robo\Result;
use Robo\Tasks;
use SbRoboTooling\Robo\Traits\UtilityTrait;
use Symfony\Component\Yaml\Yaml;

/**
 * Defines command to initialize cache service.
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

        $task = $this->taskExec('composer show -- | grep "redis"')
        ->setVerbosityThreshold(VerbosityThresholdInterface::VERBOSITY_DEBUG)
        ->run();
        if (!$task->wasSuccessful()) {
            $this->io()->section('Adding the Drupal Redis module via composer.');
            $this->taskComposerRequire()
            ->dependency('drupal/redis')
            ->ansi()
            ->noInteraction()
            ->run();
        } else {
            $this->io()->note("Redis is already added to composer.json. Skipping..");
        }

        $this->say('Enabling Redis module..');
        $this->drush()
        ->args('pm-enable')
        ->args('redis')
        ->arg('--no-interaction')
        ->run();

        $this->say('Checking if Redis is already configured');
        $task = $this->taskExec('drush rq | egrep "Redis|OK"')
        ->setVerbosityThreshold(VerbosityThresholdInterface::VERBOSITY_DEBUG)
        ->run();
        if ($task->wasSuccessful()) {
            $this->io()->note('Redis is already installed and configured. Skipping...');
        } else {
            $task = $this->taskWriteToFile($this->getDocroot() . '/' . $this->getConfigValue('drupal.webroot') . '/sites/default/settings.php')
            ->append()
            ->line('# Redis Configuration.')
            ->line('$conf[\'redis_client_host\'] = \'cache\';')
            ->line('require DRUPAL_ROOT . "/../vendor/specbee/robo-tooling/settings/redis.settings.php";')
            ->run();
            if (!$task->wasSuccessful()) {
                throw new TaskException($task, "Could not udpate settings.php");
            }
        }

        $landoFileConfig = Yaml::parse(file_get_contents($this->getDocroot() . '/.lando.yml', 128));
        $this->say('Checking if there is cache service is setup.');
        if (!array_key_exists('cache', $landoFileConfig['services'])) {
            $confirm = $this->io()->confirm('Do you want to update Lando to add the `cache` service for Redis?', true);
            if (!$confirm) {
                return Result::cancelled();
            }
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
            $this->io()->warning('Do a `lando rebuild` for the change to take effect.');
        } else {
            $this->io()->note('Cache service is already added to Lando configuration. Skipping..');
        }
    }
}
