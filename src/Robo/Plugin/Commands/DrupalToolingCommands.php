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
        $this->drupalInstall($opts);

        // Don't try to import configurations for new installation.
        if (file_exists($this->getDocroot() . '/config/sync/core.extension.yml')) {
            $this->importConfig();
            $this->cacheRebuild();
        }
    }

    /**
     * Update & refresh Drupal database udpates and import pending config.
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
     * @return void
     */
    public function sync()
    {
        $this->io()->title('Syncing database and files from ' . $this->getConfigValue('remote.sync'));
        $this->syncDb();
        $this->syncFiles();
        $this->cacheRebuild();
    }
}
