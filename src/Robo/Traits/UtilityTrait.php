<?php

namespace SbRoboTooling\Robo\Traits;

use DrupalFinder\DrupalFinder;
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
    public function installDependencies()
    {
        chdir($this->getDocroot());
        return $this->taskComposerInstall()->ansi()->noInteraction();
    }

    /**
     * Return drush with default arguments.
     */
    protected function drush()
    {
      // Drush needs an absolute path to the docroot.
        $docroot = $this->getDocroot() . '/docroot';
        return $this->taskExec('vendor/bin/drush')
        ->option('root', $docroot, '=');
    }
}
