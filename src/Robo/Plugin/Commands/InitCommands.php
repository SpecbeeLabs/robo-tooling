<?php

namespace Specbee\DevSuite\Robo\Plugin\Commands;

use Robo\Contract\VerbosityThresholdInterface;
use Robo\Exception\TaskException;
use Robo\Result;
use Robo\ResultData;
use Robo\Tasks;
use Specbee\DevSuite\Robo\Traits\IO;
use Specbee\DevSuite\Robo\Traits\UtilityTrait;

/**
 * Robo commands to initialize a project.
 */
class InitCommands extends Tasks
{
    use UtilityTrait;
    use IO;

    private const CONFIG_PATH = '/vendor/specbee/robo-tooling/assets/conf';

    public function __construct()
    {
        if (!file_exists($this->getDocroot() . "/robo.yml")) {
            throw new TaskException(
                $this,
                "No Robo configuration file detected. Copy the example.robo.yml
                file to your project root and rename it to robo.yml."
            );
        }
    }

    /**
     * Initialize Git and make an empty initial commit.
     *
     * @command init:git
     */
    public function initGit(): Result
    {
        if (!file_exists($this->getDocroot() . "/.git")) {
            $this->title("Initializing empty Git repository in " . $this->getDocroot());
            $result = $this->taskGitStack()
            ->stopOnFail()
            ->dir($this->getDocroot())
            ->exec('git init')
            ->commit('Initial commit.', '--allow-empty')
            ->add('-A')
            ->commit($this->getConfigValue('project.prefix') . '-000: Created project from Specbee starterkit.')
            ->interactive(false)
            ->printOutput(false)
            ->printMetadata(false)
            ->run();

            // Switch to develop branch once master is setup.
            $this->title("Switching to develop branch.");
            $result = $this->taskGitStack()
            ->stopOnFail()
            ->exec('git branch develop')
            ->checkout('develop')
            ->run();

            // Throw exception is the command fails.
            if (!$result->wasSuccessful()) {
                throw new TaskException($result, "Could not initialize Git repository.");
            }

            return $result;
        } else {
            $this->info("Git is already initialized at " . $this->getDocroot(), true);
            return $this->taskExecStack()
                ->setVerbosityThreshold(VerbosityThresholdInterface::VERBOSITY_DEBUG)
                ->exec('echo Skipping...')
                ->run();
        }
    }

    /**
     * Initialize and configure project tools.
     *
     * @command init:project
     */
    public function initProject($opts = ['yes|y' => false]): void
    {
        $this->title("Initializing and configuring project tool at " . $this->getDocroot());
        $this->copyDrushAliases($opts);
        $this->confLando($opts);
        $this->confDrupalQualityChecker($opts);
        $this->commitSetup();
    }

    /**
     * Copy default drush aliases file.
     */
    public function copyDrushAliases($opts = ['yes|y' => false])
    {
        $this->say('Copy default drush aliases file');
        if (!$this->getConfigValue('project.machine_name')) {
            throw new TaskException(
                $this,
                "Require Robo configuration 'project.machine_name' not found in the configuration file."
            );
        }

        $drushPath = $this->getDocroot() . '/drush/sites';
        $aliasPath = $drushPath . '/' . $this->getConfigValue('project.machine_name') . '.site.yml';

        // Skip if alias file is already generated.
        if (file_exists($aliasPath)) {
            $this->info('Drush alias file exists.', true);
            return;
        }

        // Check if default.sites.yml exisits.
        // Attempt to create an aliase file if does not exists.
        if (!file_exists($drushPath . "/default.site.yml")) {
            if (!$opts['yes']) {
                $confirm = $this->confirm('Default Drush aliases file does not
                exist. Do you want to create one?', true);
                if (!$confirm) {
                    return Result::cancelled();
                }
            }

            $source = $this->getDocroot() . self::CONFIG_PATH . "/default.sites.yml";
            $dest = $drushPath . '/default.sites.yml';

            $this->taskFilesystemStack()
            ->mkdir($drushPath)
            ->touch($dest)
            ->copy($source, $dest, true)
            ->run();
        }

        $task = $this->taskFilesystemStack()
        ->rename($dest, $aliasPath, false)
        ->run();

        if (!$task->wasSuccessful()) {
            throw new TaskException($task, "Could not copy Drush aliases.");
        } else {
            $this->success("Drush aliases were copied to " . $drushPath);
        }

        return $task;
    }

    /**
     * Setup lando.yml for local environment.
     */
    public function confLando($opts = ['yes|y' => false]): ResultData
    {
        $this->say('Setup lando.yml for local environment.');
        $landoFile = $this->getDocroot() . '/.lando.yml';
        if (!file_exists($landoFile)) {
            if (!$opts['yes']) {
                $confirm = $this->confirm('Lando file does not exist. Do you
                want to initialize lando for local development?', true);
                if (!$confirm) {
                    return Result::cancelled();
                }
            }

            $source = $this->getDocroot() . self::CONFIG_PATH . "/.lando.yml";

            $this->taskFilesystemStack()
            ->touch($landoFile)
            ->copy($source, $landoFile, true)
            ->run();
        }

        $task = $this->taskReplaceInFile($landoFile)
        ->from('${PHP_VERSION}')
        ->to('\'' . $this->getConfigValue('project.php') . '\'')
        ->taskReplaceInFile($landoFile)
        ->from('${PROJECT_NAME}')
        ->to($this->getConfigValue('project.machine_name'))
        ->taskReplaceInFile($landoFile)
        ->from('${WEBROOT}')
        ->to($this->getConfigValue('drupal.webroot'))
        ->run();

        if (!$task->wasSuccessful()) {
            throw new TaskException($task, "Could not setup Lando.");
        } else {
            $this->success("Lando was successfully initialized. Run `lando start` to spin up the docker containers");
        }

        return $task;
    }

    /**
     * Setup Quality checker.
     */
    public function confDrupalQualityChecker($opts = ['yes|y' => false]): ResultData
    {
        $this->say('Setup Drupal quality checker.');
        $grumphpFile = $this->getDocroot() . '/grumphp.yml';
        if (!file_exists($grumphpFile)) {
            if (!$opts['yes']) {
                $confirm = $this->confirm('GrumPHP configuration not found. Do you want to initialize GrumPHP?', true);
                if (!$confirm) {
                    return Result::cancelled();
                }
            }

            $this->taskComposerRequire()
            ->dependency('specbee/drupal-quality-checker')
            ->dev()
            ->noInteraction()
            ->option('no-progress')
            ->run();

            $source = $this->getDocroot() . self::CONFIG_PATH . "/grumphp.yml";

            $this->taskFilesystemStack()
            ->touch($grumphpFile)
            ->copy($source, $grumphpFile, true)
            ->run();
        }
        $task = $this->taskReplaceInFile($grumphpFile)
        ->from('${PROJECT_PREFIX}')
        ->to($this->getConfigValue('project.prefix'))
        ->run();

        if (!$task->wasSuccessful()) {
            throw new TaskException($task, "Could not setup GrumPHP.");
        } else {
            $this->success("GrumPHP is successfully configured to watch your commits.");
        }

        return $task;
    }

    /**
     * Commit the changes of init:project command.
     */
    public function commitSetup(): ResultData
    {
        $this->say('Committing the changes...');
        $this->say('Normalizing composer.json file...');
        $this->taskExec('composer normalize')
        ->run();
        $task = $this->taskGitStack()
        ->stopOnFail()
        ->dir($this->getDocroot())
        ->add('-A')
        ->commit($this->getConfigValue('project.prefix') . '-000: Initialized new project from Specbee starterkit.')
        ->interactive(false)
        ->printOutput(false)
        ->printMetadata(false)
        ->run();

        if (!$task->wasSuccessful()) {
            throw new TaskException($task, $task->getMessage());
        }

        return $task;
    }

    /**
     * Initialize Behat & PHPUnit tests.
     *
     * @command init:project:tests
     */
    public function initTests()
    {
        $this->title("Settings up Behat!");
        $this->say('Installing the Behat packages...');
        $this->taskComposerRequire()
            ->dependency('behat/behat', '^3.8')
            ->dependency('behat/mink-goutte-driver', '^1.2')
            ->dependency('behat/mink-selenium2-driver', '^1.4')
            ->dependency('bex/behat-screenshot', '^2.1')
            ->dependency('dmore/behat-chrome-extension', '^1.3')
            ->dependency('dmore/chrome-mink-driver', '^2.7')
            ->dependency('drupal/drupal-extension', '^4.1')
            ->dev(true)
            ->ansi()
            ->noInteraction()
            ->run();

        $behatDir = $this->getConfigValue('tests.behat.dir');
        $behatConfig = $this->getConfigValue('tests.behat.config');
        $this->say('Initializing Behat at ' . $behatDir);
        $this->taskBehat()
            ->dir($this->getDocroot() . '/' . $behatDir)
            ->option('init')
            ->run();

        $this->say('Configuring Behat at ' . $behatDir);
        $this->taskFilesystemStack()
            ->touch($behatConfig)
            ->copy($this->getDocroot() . self::CONFIG_PATH . "/behat.yml", $behatConfig, true)
            ->run();

        $this->title("Settings up PHPUnit!");
        $this->say('Installing the PHPUnit packages...');
        $this->taskComposerRequire()
            ->dependency('symfony/phpunit-bridge', '^4.1')
            ->dev(true)
            ->ansi()
            ->noInteraction()
            ->run();
        $phpUnitDir = $this->getConfigValue('tests.phpunit.dir');
        $phpUnitConfig = $this->getConfigValue('tests.phpunit.config');
        $this->say('Configuring PHPUnit at ' . $phpUnitDir);
        $this->taskFilesystemStack()
            ->touch($phpUnitConfig)
            ->copy($this->getDocroot() . self::CONFIG_PATH . "/phpunit.xml", $phpUnitConfig, true)
            ->run();
    }
}
