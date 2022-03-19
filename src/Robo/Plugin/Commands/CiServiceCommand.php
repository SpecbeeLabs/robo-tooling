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
    public function serviceInitCiCircle()
    {
        $docroot = $this->getDocroot();
        $this->say('Setting up CI/CD using CircleCI.');

        if (file_exists($docroot . '/.circleci/config.yml')) {
            $this->info('Existing CircleCI configuration found.', true);
            return;
        }

        $source = $docroot . "/vendor/specbee/robo-tooling/ci/";
        $dest = $docroot . "/.circleci";

        $task = $this->taskFilesystemStack()
        ->mkdir($dest)
        ->mkdir($docroot . '/scripts/.circleci')
        ->copy($source . 'circleci/config.yml', $dest . '/config.yml')
        ->copy($source . 'deploy.sh', $docroot . '/scripts/.circleci/deploy.sh')
        ->stopOnFail()
        ->setVerbosityThreshold(VerbosityThresholdInterface::VERBOSITY_VERBOSE)
        ->run();

        if (!$task->wasSuccessful()) {
            throw new TaskException($task, "Could not initialize CircleCI configuration.");
        }

        $this->success("A pre-configured CircleCI was copied to your repository.");
    }

    /**
     * Setup Bitbucket Pipelines.
     *
     * @command service:init:ci:bitbucket-pipelines
     */
    public function serviceInitCiBitbucketPipelines()
    {
        $docroot = $this->getDocroot();
        $this->say('Setting up CI/CD using Bitbucket Pipelines.');

        if (file_exists($docroot . '/.bitbucket-pipelines.yml')) {
            $this->info('Existing Bitbucket Pipeline configuration found.', true);
            return;
        }

        $pipelineSource = $docroot . "/vendor/specbee/robo-tooling/ci/bitbucket/bitbucket-pipelines.yml";
        $behatConfig = $docroot . "/vendor/specbee/robo-tooling/config/ci.behat.yml";
        $dest = $docroot . "/scripts/.bitbucket";

        $task = $this->taskFilesystemStack()
        ->copy($pipelineSource, $docroot)
        ->copy($behatConfig, $docroot . "/config/ci.behat.yml")
        ->mkdir($dest)
        ->copy($docroot . "/vendor/specbee/robo-tooling/ci/bitbucket/run", $docroot . "/scripts/run")
        ->copy($docroot . "/vendor/specbee/robo-tooling/ci/bitbucket/deploy", $docroot . "/scripts/deploy")
        ->run();

        if (!$task->wasSuccessful()) {
            throw new TaskException($task, "Could not initialize Bitbucket Pipeline configuration.");
        }

        $this->success("A pre-configured bitbucket-pipelines.yml was copied to your repository root.");
    }
}
