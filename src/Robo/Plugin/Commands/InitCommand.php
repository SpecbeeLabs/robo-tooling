<?php

namespace SbRoboTooling\Robo\Plugin\Commands;

use Robo\Robo;
use Robo\Tasks;
use SbRoboTooling\Robo\Traits\UtilityTrait;

/**
 * Robo commands to initialize a project.
 */
class InitCommand extends Tasks
{
    use UtilityTrait;

    /**
     * Initialize git and make an empty initial commit.
     */
    public function initGit()
    {
        $this->say('setup:git');
        chdir($this->getDocroot());
        $config = Robo::config();
        $this->taskGitStack()
        ->stopOnFail()
        ->exec("git init")
        ->commit('Initial commit.', '--allow-empty')
        ->add('-A')
        ->commit($config->get('project.prefix') . '-000: Created project from Specbee starterkit.')
        ->interactive(false)
        ->printOutput(false)
        ->printMetadata(false)
        ->run();
    }
}
