<?php

namespace Specbee\DevSuite\Robo\Plugin\Commands;

use Specbee\DevSuite\Robo\Traits\IO;
use Specbee\DevSuite\Robo\Traits\UtilityTrait;

/**
 * Provides tooling commands for drupal:* namespace.
 */
class DrupalToolingCommands extends DrupalCommands
{
    use UtilityTrait;
    use IO;

    /**
     * RoboFile constructor.
     */
    public function __construct()
    {
        $this->stopOnFail();
    }

    /**
     * Setting up a new Drupal site.
     *
     * @command setup
     */
    public function setup($opts = ['db-url' => '', 'no-interaction|n' => false]): void
    {
        $this->title('Setting up a new Drupal site - ' . $this->getConfigValue('project.human_name'));
        $this->taskWorkDir($this->getDocroot());
        $this->buildTheme();
        $this->drupalInstall($opts);

        // Don't try to import configurations for new installation.
        $ext_file = $this->getDocroot() . '/' . $this->getConfigValue('drupal.config.path') . '/core.extension.yml';
        if (file_exists($ext_file)) {
            $this->importConfig();
            $this->cacheRebuild();
        }
    }

    /**
     * Update & refresh Drupal database updates and import pending config.
     *
     * @command drupal:update
     *
     * @aliases du
     */
    public function drupalUpdate(): void
    {
        $this->title('Updating & refreshing Drupal database');
        $this->buildTheme();
        $this->updateDatabase();
        $this->importConfig();
        $this->cacheRebuild();
    }

    /**
     * Sync database and files from remote environments.
     *
     * @command sync
     */
    public function sync($opts = ['skip-import|s' => false, 'db' => false, 'files' => false]): void
    {
        $remote = $this->getConfigValue('sync.remote');
        if ($opts['db']) {
            $this->title('Syncing database frpm' . $remote);
            $this->syncDb($opts);
        } elseif ($opts['files']) {
            $this->title('Syncing public files from ' . $remote);
            $this->syncFiles();
        } else {
            $this->title('Syncing database from ' . $remote . ' and running database updated.');
            $this->syncDb($opts);
        }

        $this->cacheRebuild();
    }
}
