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
class TestCommands extends Tasks
{
    use UtilityTrait;
    use IO;

    /**
     * Run behat tests.
     *
     * @command test:behat
     *
     * @aliases behat
     *
     * @return Robo\Result
     */
    public function testBehat($opts = ['config' => '',]): Result
    {
        $this->say("Running behat...");
        if (empty($this->getConfigValue('tests.behat.config'))) {
            throw new TaskException($this, 'Expected Robo configuration not present: tests.behat.config');
        }

        if (!file_exists($this->getDocroot() . '/' . $this->getConfigValue('tests.behat.config'))) {
            throw new TaskException($this, 'Behat configuration file is not found at ' . $this->getConfigValue('tests.behat.config'));
        }

        $behatDir = $this->getDocroot() . '/' . $this->getConfigValue('tests.behat.dir');
        $behatPath = $this->getDocroot() . '/' . $this->getConfigValue('tests.behat.path');
        $behatConfig = $this->getDocroot() . '/' . $this->getConfigValue('tests.behat.config');
        $task =  $this->taskBehat()
        ->stopOnFail()
        ->dir($behatDir)
        ->arg($behatPath)
        ->format('pretty')
        ->colors()
        ->option('strict')
        ->verbose('v')
        ->noInteraction();
        if (!empty($opts['config'])) {
            $task->option('config', $behatDir . '/' . $opts['config']);
        } else {
            $task->option('config', $behatConfig);
        }
        if (!empty($this->getConfigValue('tests.behat.tags'))) {
            $task->option('tags', $this->getConfigValue('tests.behat.tags'));
        }
        return $task->run();
    }

    /**
     * Run PHPUnit tests.
     *
     * @command test:phpunit
     *
     * @aliases phpunit
     *
     * @return Robo\Result
     */
    public function testPhpUnit()
    {
        $this->say("Running PHPUnit tests...");
        return $this->taskExec('simple-phpunit')
        ->option('config', $this->getConfigValue('tests.phpunit.config'))
        ->arg($this->getConfigValue('tests.phpunit.dir'))
        ->run();
    }

    /**
     * Run test, Behat and PHPUnit.
     *
     * @command test
     */
    public function test(): void
    {
        $this->title("Running all tests...");
        $this->testBehat();
        $this->testPhpUnit();
    }
}
