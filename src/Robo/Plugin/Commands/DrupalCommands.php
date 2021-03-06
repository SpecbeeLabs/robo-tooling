<?php

namespace Specbee\DevSuite\Robo\Plugin\Commands;

use Robo\Exception\TaskException;
use Robo\Result;
use Robo\Tasks;
use Specbee\DevSuite\Robo\Traits\IO;
use Specbee\DevSuite\Robo\Traits\UtilityTrait;

/**
 * Defines the commands in the drupal:* namespace.
 */
class DrupalCommands extends Tasks
{
    use UtilityTrait;
    use IO;

    /**
     * The local database URL.
     *
     * @var string
     */
    protected const DB_URL = 'mysql://drupal:drupal@database/drupal';

    /**
     * Setup a fresh Drupal site from existing config if present.
     *
     * @command drupal:site:install
     *
     * @aliases dsi
     *
     * @return Robo\Result
     */
    public function drupalInstall($opts = ['db-url' => '', 'no-interaction|n' => false]): Result
    {
        $this->say('drupal:install');
        $task = $this->drush()
            ->args('site-install')
            ->args('-v')
            ->arg($this->getConfigValue('drupal.profile'))
            ->option('site-name', $this->getConfigValue('project.human_name'), '=')
            ->option('site-mail', $this->getConfigValue('drupal.account.mail'), '=')
            ->option('account-name', $this->getConfigValue('drupal.account.name'), '=')
            ->option('account-mail', $this->getConfigValue('drupal.account.mail'), '=')
            ->option('ansi');

        if ($opts['no-interaction']) {
            $task->arg('--no-interaction');
        }

        if (!empty($opts['db-url'])) {
            $task->option('db-url', $opts['db-url'], '=');
        } else {
            $task->option('db-url', static::DB_URL, '=');
        }

        $result = $task->run();
        if (!$result->wasSuccessful()) {
            throw new TaskException($task, "Could not install Drupal.");
        }

        return $result;
    }

    /**
     * Import pending configurations.
     *
     * @command drupal:import:config
     *
     * @aliases dci, dic
     *
     * @return Robo\Result
     */
    public function importConfig(): Result
    {
        $this->say('drupal:import:config');
        $this->cacheRebuild();
        $this->drush()
        ->arg('config:set')
        ->arg('system.site')
        ->arg('uuid')
        ->arg($this->getExportedSiteUuid())
        ->option('no-interaction')
        ->option('ansi')
        ->run();

        $task = $this->drush()
        ->arg('config:import')
        ->option('ansi')
        ->option('no-interaction')
        ->run();

        // Import the latest configuration again. This includes the latest
        // configuration_split configuration. Importing this twice ensures that
        // the latter command enables and disables modules based upon the most up
        // to date configuration. Additional information and discussion can be
        // found here:
        // https://github.com/drush-ops/drush/issues/2449#issuecomment-708655673
        $task = $task = $this->drush()
        ->arg('config:import')
        ->option('ansi')
        ->option('no-interaction')
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
     *
     * @aliases dupdb
     *
     * @return Robo\Result
     */
    public function updateDatabase(): Result
    {
        $this->say('drupal:update:db');
        $this->cacheRebuild();
        $task = $this->drush()
        ->arg('updatedb')
        ->option('ansi')
        ->option('no-interaction')
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
     *
     * @aliases dsdb
     *
     * @return Robo\Result
     */
    public function syncDb($opts = ['skip-import|s' => false]): Result
    {
        $machineName = $this->getConfigValue('project.machine_name');
        $remote = $this->getConfigValue('sync.remote');
        $this->say('drupal:sync:db');
        $remote_alias = '@' . $machineName . '.' . $remote;
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
            ->option('ansi')
        );
        if ($this->getConfigValue('sync.sanitize') === true) {
            $collection->addTask(
                $this->drush()
                ->args('sql-sanitize')
                ->option('no-interaction')
                ->option('ansi')
            );
        }
        $result = $collection->run();

        if (!$result->wasSuccessful()) {
            throw new TaskException($result, "Failed to sync database from the remote server");
        }

        if (!$opts['skip-import']) {
            $this->importConfig();
        }

        return $result;
    }

    /**
     * Sync files from remote server.
     *
     * @command drupal:sync:files
     *
     * @aliases dsf
     *
     * @return Robo\Result
     */
    public function syncFiles(): Result
    {
        $machineName = $this->getConfigValue('project.machine_name');
        $remote = $this->getConfigValue('sync.remote');
        $this->say('drupal:sync:files');
        $remote_alias = '@' . $machineName . '.' . $remote;
        $local_alias = '@self';
        $task = $this->drush()
        ->args('core-rsync')
        ->arg($remote_alias . ':%files')
        ->arg($local_alias . ':%files')
        ->option('ansi')
        ->option('no-interaction')
        ->run();

        if (!$task->wasSuccessful()) {
            throw new TaskException($task, "Failed to sync files from the remote server!");
        }

        return $task;
    }
}
