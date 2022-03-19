<?php

namespace Specbee\DevSuite\Robo\Plugin\Commands;

use Robo\Exception\TaskException;
use Robo\Result;
use Robo\Tasks;
use Specbee\DevSuite\Robo\Traits\IO;
use Specbee\DevSuite\Robo\Traits\UtilityTrait;

/**
 * Defines commands in the test:* namespace.
 */
class SecurityUpdateCommands extends Tasks
{
    use UtilityTrait;
    use IO;

    /**
     * Check local Drupal installation for security updates.
     *
     * @command security:check:drupal
     */
    public function securityCheckDrupal()
    {
        if (!$this->getConfigValue('security.check.drupal')) {
            $this->info("Security check for Drupal is disabled in your Robo configurations.");
            return;
        }
        $result = $this->drush()
        ->drush("pm:security")
        ->run();

        if ($result->getExitCode()) {
            $this->info('To disable security checks, set security.check.drupal to false in robo.yml.');
            return 1;
        } else {
            $this->say("There are no outstanding security updates for Drupal projects.");
            return 0;
        }
    }

    /**
     * Check composer.lock for security updates.
     *
     * @command security:check:composer
     */
    public function securityCheckComposer()
    {
        if (!$this->getConfigValue('security.check.composer')) {
            $this->info("Security check for Composer packages is disabled in your Robo configurations.");
            return;
        }

        $result = $this->taskExecStack()
        ->stopOnFail()
        ->dir($this->getDocroot())
        ->exec("vendor/bin/security-checker security:check composer.lock")
        ->run();

        if ($result->getExitCode()) {
            $this->info('To disable security checks, set security.check.composer to false in robo.yml.');
            return 1;
        } else {
            $this->say("There are no outstanding security updates for your composer packages.");
            return 0;
        }
    }
}
