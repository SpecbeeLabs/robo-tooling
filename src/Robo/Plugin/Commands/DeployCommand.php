<?php

namespace SbRoboTooling\Robo\Plugin\Commands;

use Robo\Exception\TaskException;
use Robo\Tasks;
use SbRoboTooling\Robo\Traits\UtilityTrait;

/**
 * Defines command deploy code.
 */
class DeployCommand extends Tasks
{
    use UtilityTrait;

    /**
     * RoboFile constructor.
     */
    public function __construct()
    {
        // Treat this command like bash -e and exit as soon as there's a failure.
        $this->stopOnFail();
    }

    /**
     * Run deployment commands.
     *
     * @command deploy
     */
    public function deploy($opts = ['production' => false])
    {
        // Install composer dependencies with --no-dev flag.
        $this->taskComposerInstall()
        ->noDev()
        ->noInteraction()
        ->ansi()
        ->run();

        // Check site status to debug in case of failed deployment.
        $this->drush()
        ->args('core:status')
        ->run();

        // Set the system UUID for importing configurations.
        $this->cacheRebuild();
        $this->drush()
        ->arg('config:set')
        ->arg('system.site')
        ->arg('uuid')
        ->arg($this->getExportedSiteUuid())
        ->arg('--no-interaction')
        ->run();

        // Put the site in maintenance mode in case of a Production deployment.
        if ($opts['production']) {
            $this->io()->note('Putting the site in manintenance mode');
            $this->drush()
            ->args('state:set')
            ->args('system.maintenance_mode')
            ->args('1')
            ->run();
            $this->io()->newLine();
            $this->io()->warning("Site is now offline");
        }

        // Run the drush deploy command which runs the below commands.
        /**
         *  drush updatedb --no-cache-clear
         * drush cache:rebuild
         * drush config:import
         * drush cache:rebuild
         * drush deploy:hook
         */
        $task = $this->drush()
        ->args('deploy')
        ->option('-v')
        ->option('-y')
        ->run();

        if (!$task->wasSuccessful()) {
            return new TaskException($task, "Deployment failed. Check the logs for more information");
        }

        # Run cron.
        $this->drush()
        ->args('core-cron')
        ->run();

        # Clear the cache.
        $this->cacheRebuild();

        #Disable maintenance mode for production deployments.
        if ($opts['production']) {
            $this->io()->note('Disabling maintenance mode.');
            $this->drush()
            ->args('state:set')
            ->args('system.maintenance_mode')
            ->args('0')
            ->run();
        }
        $this->io()->newLine();
        $this->io()->success("🚀 Deployment completed. Site is now online. 🚀");
    }
}
