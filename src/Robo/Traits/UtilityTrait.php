<?php

namespace SbRoboTooling\Robo\Traits;

use DrupalFinder\DrupalFinder;
use Robo\Exception\TaskException;
use Robo\Robo;
use Symfony\Component\Yaml\Yaml;

trait UtilityTrait
{

    /**
     * Undocumented function
     *
     * @param string $key
     *   The config to fetch.
     *
     * @return string
     *   The config value.
     */
    public function getConfigValue($key)
    {
        $config = Robo::config();
        return $config->get($key);
    }

    /**
     * Get the absolute path to the docroot.
     *
     * @return string
     */
    public function getDocroot()
    {
        $drupalFinder = new DrupalFinder();
        $drupalFinder->locateRoot(getcwd());
        $docroot = $drupalFinder->getComposerRoot();
        return $docroot;
    }

    /**
     * Returns the site UUID stored in exported configuration.
     */
    public function getExportedSiteUuid()
    {
        $site_config_file = $this->getDocroot() . '/config/sync/system.site.yml';
        if (file_exists($site_config_file)) {
            $site_config = Yaml::parseFile($site_config_file);
            $site_uuid = $site_config['uuid'];

            return $site_uuid;
        }

        return null;
    }

    /**
     * Installs composer dependencies.
     */
    public function installComposerDependencies()
    {
        chdir($this->getDocroot());
        return $this->taskComposerInstall()->ansi()->noInteraction()->run();
    }
    
    /**
     * Build the frontend assets.
     */
    public function buildTheme()
    {
        if (file_exists($this->getConfigValue('drupal.theme.path'))) {
            if (!empty($this->getConfigValue('drupal.theme.build'))) {
                $this->taskExec($this->getConfigValue('drupal.theme.build'))
                ->dir($this->getConfigValue('drupal.theme.path'))
                ->run();
            }
            if (!empty($this->getConfigValue('drupal.theme.compile'))) {
                $this->taskExec($this->getConfigValue('drupal.theme.compile'))
                ->dir($this->getConfigValue('drupal.theme.path'))
                ->run();
            }
        } else {
            $this->io()->newLine();
            $this->io()->note('No theme found at ' . $this->getConfigValue('drupal.theme.path'));
        }
    }

    /**
     * Return drush with default arguments.
     */
    public function drush()
    {
        // Drush needs an absolute path to the docroot.
        $docroot = $this->getDocroot() . '/docroot';
        $drushBin = $this->getDocroot() . '/vendor/bin/drush';
        return $this->taskExec($drushBin)
        ->option('root', $docroot, '=');
    }

    /**
     * Private methid to rebuild Drupal cache.
     */
    public function cacheRebuild()
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
}
