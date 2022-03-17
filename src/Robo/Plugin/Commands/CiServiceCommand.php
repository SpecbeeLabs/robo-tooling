<?php

namespace Specbee\DevSuite\Robo\Plugin\Commands;

use Robo\Tasks;
use Specbee\DevSuite\Robo\Traits\IO;
use Specbee\DevSuite\Robo\Traits\UtilityTrait;

/**
 * Defines command to initialize CI/CD service.
 */
class CircleCiServiceCommand extends Tasks
{
    use UtilityTrait;
    use IO;

    /**
     * Setup CircleCI.
     *
     * @command service:init:ci:circle
     */
    public function initServiceCiCircle()
    {
        $docroot = $this->getDocroot();
        $this->say('Setting up CI/CD using CircleCI.');

        if (file_exists($docroot . '/.circleci/config.yml')) {
            $this->info('CircleCI config file exists.', true);
            return;
        }
        $source = $docroot . "/vendor/specbee/robo-tooling/scripts/.circleci";

        $this->taskFilesystemStack()
        ->taskCopyDir([$source, $docroot])
        ->run();
    }
}
