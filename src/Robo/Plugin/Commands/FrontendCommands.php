<?php

namespace Specbee\DevSuite\Robo\Plugin\Commands;

use Robo\Exception\TaskException;
use Robo\Result;
use Robo\Tasks;
use Specbee\DevSuite\Robo\Traits\IO;
use Specbee\DevSuite\Robo\Traits\UtilityTrait;

/**
 * Defines the commands in the drupal:* namespace.
 */
class FrontendCommands extends Tasks
{
    use UtilityTrait;
    use IO;

    /**
     * Build the frontend assets.
     *
     * @command theme:build
     *
     * @return Robo\Result
     */
    public function themeBuild()
    {
        $this->say('theme:build');

        // Return early if theme is not found.
        if (!file_exists($this->getConfigValue('drupal.theme.path'))) {
            $this->warning('No theme found at ' . $this->getConfigValue('drupal.theme.path'));
            return;
        }


        if (!empty($this->getConfigValue('drupal.theme.build'))) {
            $task = $this->taskExec($this->getConfigValue('drupal.theme.build'))
            ->dir($this->getConfigValue('drupal.theme.path'))
            ->run();
        }

        if (!$task->wasSuccessful()) {
            throw new TaskException($task, "Failed to build frontend assets!");
        }

        return $task;
    }

    /**
     * Build the frontend assets.
     *
     * @command theme:compile
     *
     * @return Robo\Result
     */
    public function themeCompile()
    {
        $this->say('theme:compile');

        // Return early if theme is not found.
        if (!file_exists($this->getConfigValue('drupal.theme.path'))) {
            $this->warning('No theme found at ' . $this->getConfigValue('drupal.theme.path'));
            return;
        }


        if (!empty($this->getConfigValue('drupal.theme.compile'))) {
            $task = $this->taskExec($this->getConfigValue('drupal.theme.compile'))
            ->dir($this->getConfigValue('drupal.theme.path'))
            ->run();
        }

        if (!$task->wasSuccessful()) {
            throw new TaskException($task, "Failed to compile frontend assets!");
        }

        return $task;
    }
}
