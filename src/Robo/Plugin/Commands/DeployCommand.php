<?php

namespace Specbee\DevSuite\Robo\Plugin\Commands;

use Robo\Exception\TaskException;
use Robo\Tasks;
use Specbee\DevSuite\Robo\Traits\IO;
use Specbee\DevSuite\Robo\Traits\UtilityTrait;

/**
 * Defines command deploy code.
 */
class DeployCommand extends Tasks
{
    use UtilityTrait;
    use IO;

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

        // Build frontend assets
        $this->buildTheme();

        // Check site status to debug in case of failed deployment.
        $this->drush()
        ->args('core:status')
        ->option('ansi')
        ->run();

        // Set the system UUID for importing configurations.
        $this->cacheRebuild();
        $this->drush()
        ->arg('config:set')
        ->arg('system.site')
        ->arg('uuid')
        ->arg($this->getExportedSiteUuid())
        ->option('ansi')
        ->option('no-interaction')
        ->run();

        // Put the site in maintenance mode in case of a Production deployment.
        if ($opts['production']) {
            $this->say('Putting the site in manintenance mode');
            $this->drush()
            ->args('state:set')
            ->args('system.maintenance_mode')
            ->args('1')
            ->option('ansi')
            ->run();
            $this->warning("Site is now offline");
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
        ->option('ansi')
        ->run();

        if (!$task->wasSuccessful()) {
            return new TaskException($task, "Deployment failed. Check the logs for more information");
        }

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

        # Run cron.
        $this->drush()
        ->args('core-cron')
        ->option('ansi')
        ->run();

        # Clear the cache.
        $this->cacheRebuild();

        #Disable maintenance mode for production deployments.
        if ($opts['production']) {
            $this->say('Disabling maintenance mode.');
            $this->drush()
            ->args('state:set')
            ->args('system.maintenance_mode')
            ->args('0')
            ->option('ansi')
            ->run();
        }

        $this->success("🚀 Deployment completed. Site is now online. 🚀");
    }
}
