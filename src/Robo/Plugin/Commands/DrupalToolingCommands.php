<?php

namespace SbRoboTooling\Robo\Plugin\Commands;

use SbRoboTooling\Robo\Traits\UtilityTrait;

class DrupalToolingCommands extends DrupalCommands
{
    use UtilityTrait;

    /**
     * Setting up a new Drupal site.
     *
     * @command setup
     */
    public function setup($opts = ['db-url' => '', 'no-interaction|n' => false])
    {
        $this->io()->title('Setting up a new Drupal site - ' . $this->getConfigValue('project.human_name'));
        $this->installComposerDependencies();
        $this->buildFrontendReqs();
        $this->drupalInstall($opts);

        // Don't try to import configurations for new installation.
        if (file_exists($this->getDocroot() . '/' . $this->getConfigValue('drupal.config.path') . '/core.extension.yml')) {
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
    public function drupalUpdate()
    {
        $this->io()->title('Updating & refreshing Drupal database');
        $this->installComposerDependencies();
        $this->updateDatabase();
        $this->importConfig();
    }

    /**
     * Sync database and files from remote envrionment.
     *
     * @command sync
     */
    public function sync($opts = ['skip-import|s' => false, 'db' => false, 'files' => false])
    {
        if ($opts['db']) {
            $this->io()->title('Syncing database ' . $this->getConfigValue('sync.remote'));
            $this->syncDb($opts);
        } elseif ($opts['files']) {
            $this->io()->title('Syncing public files from ' . $this->getConfigValue('sync.remote'));
            $this->syncFiles();
        } else {
            $this->io()->title('Syncing database and files from ' . $this->getConfigValue('sync.remote'));
            $this->syncDb($opts);
            $this->syncFiles();
        }

        $this->cacheRebuild();
    }
}
