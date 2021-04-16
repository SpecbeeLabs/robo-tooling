<?php

namespace SbRoboTooling\Robo\Plugin\Commands;

use Robo\Tasks;
use SbRoboTooling\Robo\Traits\UtilityTrait;

class DrupalInstallCommand extends Tasks
{
    use UtilityTrait;

    /**
     * The local database URL.
     *
     * @var string
     */
    private const DB_URL = 'mysql://drupal:drupal@database/drupal';

    /**
     * Setup a fresh Drupal site from existing config if present.
     *
     * @command drupal:install
     */
    public function drupalInstall($opts = ['db-url' => '', 'no-interaction|n' => false])
    {
        $this->say('drupal:install');
        $task = $this->drush()
        ->args('site-install')
        ->arg($this->getConfigValue('project.config.profile'))
        ->option('site-name', $this->getConfigValue('project.human_name'), '=')
        ->option('site-mail', $this->getConfigValue('project.config.account.mail'), '=')
        ->option('account-name', $this->getConfigValue('project.config.account.name'), '=')
        ->option('account-mail', $this->getConfigValue('project.config.account.mail'), '=');

        if ($opts['no-interaction']) {
            $task->arg('--no-interaction');
        }

        if (!empty($opts['db-url'])) {
            $task->option('db-url', $opts['db-url'], '=');
        } else {
            $task->option('db-url', static::DB_URL, '=');
        }

        // Check if config directory exists.
        if (file_exists($this->getDocroot() . '/config/sync/core.extension.yml')) {
            $task->option('existing-config');
        }

        return $task;
    }
}
