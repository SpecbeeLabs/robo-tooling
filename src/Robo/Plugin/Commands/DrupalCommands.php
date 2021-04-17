<?php

namespace SbRoboTooling\Robo\Plugin\Commands;

use Robo\Exception\TaskException;
use Robo\Tasks;
use SbRoboTooling\Robo\Traits\UtilityTrait;

class DrupalCommands extends Tasks
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
     * @command drupal:site:install
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

        $result = $task->run();
        if (!$result->wasSuccessful()) {
            throw new TaskException($task, "Could not install Drupal.");
        }

        return $result;
    }

    /**
     * Private methid to rebuild Drupal cache.
     */
    private function cacheRebuild()
    {
        $this->say('Rebuilding cache...');
        $task = $this->drush()
        ->arg('cache-rebuild')
        ->arg('--no-interaction')
        ->arg('--ansi')
        ->run();

        if (!$task->wasSuccessful()) {
            throw new TaskException($task, "Something went wrong!!!");
        }

        return $task;
    }

    /**
    * Import pending configurations.
    *
    * @command drupal:import:config
    */
    public function importConfig()
    {
        $this->say('import:config');
        $this->cacheRebuild();
        $this->drush()
        ->arg('config:set')
        ->arg('system.site')
        ->arg('uuid')
        ->arg($this->getExportedSiteUuid())
        ->arg('--no-interaction')
        ->run();

        $task = $this->drush()
        ->arg('config:import')
        ->arg('--no-interaction')
        ->run();

        if (!$task->wasSuccessful()) {
            throw new TaskException($task, "Failed to import configuration updates!");
        }

        return $task;
    }

    /**
    * Update database.
    *
    * @command drupal:update:db
    */
    public function updateDatabase()
    {
        $this->say('drupal:update:db');
        $this->cacheRebuild();
        $task = $this->drush()
        ->arg('updatedb')
        ->arg('--no-interaction')
        ->arg('--ansi')
        ->run();
        if (!$task->wasSuccessful()) {
            throw new TaskException($task, "Failed to execute database updates!");
        }

        return $task;
    }

    /**
    * Sync database from remote server.
    *
    * @command drupal:sync:db
    */
    public function syncDb()
    {
        $this->say('sync:db');
        $remote_alias = '@' . $this->getConfigValue('project.machine_name') . '.' . $this->getConfigValue('sync.remote');
        $local_alias = '@self';
        $collection = $this->collectionBuilder();
        $collection->addTask(
            $this->drush()
            ->args('sql-sync')
            ->args('--no-interaction')
            ->arg($remote_alias)
            ->arg($local_alias)
            ->option('--target-dump', sys_get_temp_dir() . '/tmp.target.sql.gz')
            ->option('structure-tables-key', 'lightweight')
            ->option('create-db')
        );
        if ($this->getConfigValue('sync.sanitize') === true) {
            $collection->addTask(
                $this->drush()
                ->args('--no-interaction')
                ->args('sql-sanitize')
            );
        }
        $result = $collection->run();

        if (!$result->wasSuccessful()) {
            throw new TaskException($result, "Failed to sync database from the remote server");
        }

        return $result;
    }

    /**
    * Sync files from remote server.
    *
    * @command drupal:sync:files
    */
    public function syncFiles()
    {
        $this->say('sync:files');
        $remote_alias = '@' . $this->getConfigValue('project.machine_name') . '.' . $this->getConfigValue('sync.remote');
        $local_alias = '@self';
        $task = $this->drush()
        ->args('core-rsync')
        ->args('--no-interaction')
        ->arg($remote_alias . ':%files')
        ->arg($local_alias . ':%files')
        ->run();

        if (!$task->wasSuccessful()) {
            throw new TaskException($task, "Failed to sync files from the remote server!");
        }

        return $task;
    }
}
