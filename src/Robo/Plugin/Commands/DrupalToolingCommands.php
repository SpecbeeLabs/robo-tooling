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
        $this->installComposerDependencies();
        $this->info('Building theme..');
        $this->buildTheme();
        $this->drupalInstall($opts);

        // Don't try to import configurations for new installation.
        if (file_exists($this->getDocroot() . '/' . $this->getConfigValue('drupal.config.path') . '/core.extension.yml')) {
            echo "Importing config.";
            $this->importConfig();
            $this->cacheRebuild();
        }
    }

    /**
     * Update & refresh Drupal database udpates and import pending config.
     *
     * @command drupal:udpate
     *
     * @aliases du
     */
    public function drupalUpdate(): void
    {
        $this->title('Updating & refreshing Drupal database');
        $this->installComposerDependencies();
        $this->updateDatabase();
        $this->importConfig();
    }

    /**
     * Sync database and files from remote envrionment.
     *
     * @command sync
     */
    public function sync($opts = ['skip-import|s' => false, 'db' => false, 'files' => false]): void
    {
        $remote = $this->getConfigValue('sync.remote');
        if ($opts['db']) {
            $this->title('Syncing database ' . $remote);
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
