<?php

namespace SbRoboTooling\Robo\Plugin\Commands;

use SbRoboTooling\Robo\Traits\UtilityTrait;

class ThemeBuildCommand extends DrupalCommands
{
    use UtilityTrait;

    /**
     * Build frontend theme.
     *
     * @command theme:build
     */
    public function themeBuild()
    {
        $this->buildFrontendReqs();
    }
}
