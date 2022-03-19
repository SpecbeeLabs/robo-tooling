<?php

namespace Specbee\DevSuite\Robo\Plugin\Commands;

use Robo\Contract\VerbosityThresholdInterface;
use Robo\Exception\TaskException;
use Robo\Tasks;
use Specbee\DevSuite\Robo\Traits\IO;
use Specbee\DevSuite\Robo\Traits\UtilityTrait;

/**
 * Defines command to initialize CI/CD service.
 */
class CiServiceCommand extends Tasks
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
        $source = $docroot . "/vendor/specbee/robo-tooling/ci/";
        $dest = $docroot . "/.circleci/";

        $task = $this->taskFilesystemStack()
        ->mkdir($dest)
        ->mkdir($docroot . '/ci/.circleci')
        ->copy($source . 'circleci/config.yml', $dest . 'config.yml')
        ->copy($source . 'deploy.sh', $docroot . '/ci/.circleci/deploy.sh')
        ->stopOnFail()
        ->setVerbosityThreshold(VerbosityThresholdInterface::VERBOSITY_VERBOSE)
        ->run();

        if (!$task->wasSuccessful()) {
            throw new TaskException($task, "Could not initialize Travis CI configuration.");
        }

        $this->success("A pre-configured CircleCI was copied to your repository root.");
    }
}
