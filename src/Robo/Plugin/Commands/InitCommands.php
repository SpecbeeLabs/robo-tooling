<?php

namespace SbRoboTooling\Robo\Plugin\Commands;

use Robo\Contract\VerbosityThresholdInterface;
use Robo\Exception\TaskException;
use Robo\Result;
use Robo\Tasks;
use SbRoboTooling\Robo\Traits\UtilityTrait;

/**
 * Robo commands to initialize a project.
 */
class InitCommands extends Tasks
{
    use UtilityTrait;

    public function __construct()
    {
        $this->stopOnFail();
        if (!file_exists($this->getDocroot() . "/robo.yml")) {
            throw new TaskException(
                $this,
                "No Robo configuration file detected.
  Copy the example.robo.yml file to your project root and rename it to robo.yml."
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
            $this->io()->title("Initializing empty Git repository in " . $this->getDocroot());
            $this->say('setup:git');
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
            $this->io()->newLine();
            $this->io()->section("Switching to develop branch.");
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
            $this->say("Git is already initialized at " . $this->getDocroot() . ". Skipping...");
        }
    }

    /**
     * Initialize and configure project tools.
     *
     * @command init:project
     */
    public function initProject(): void
    {
        $this->io()->title("Initializing and configuring project tool at " . $this->getDocroot());
        $this->copyDrushAliases();
        $this->confDrushAlias();
        $this->confLando();
        $this->confGrumphp();
        $this->commitSetup();
    }

    /**
     * Copy default drush aliases file.
     */
    public function copyDrushAliases()
    {
        $this->say('copy:default-drush-alias');
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
            $this->io()->newLine();
            $this->yell('Drush alias file exists. Skipping...');
            $this->io()->newLine();
            return;
        }

        // Check if default.sites.yml exisits.
        // Attempt to create an aliase file if does not exists.
        if (!file_exists($drushPath . "/default.site.yml")) {
            $confirm = $this->io()->confirm('Default Drush aliases file does not exist. Do you want to create one?', true);
            if (!$confirm) {
                return Result::cancelled();
            }

            $this->taskFilesystemStack()
            ->mkdir($drushPath)
            ->touch($drushPath . '/default.sites.yml')
            ->copy($this->getDocroot() . "/vendor/specbee/robo-tooling/config/default.sites.yml", $drushPath . '/default.sites.yml', true)
            ->run();
        }

        $task = $this->taskFilesystemStack()
        ->rename($drushPath . "/default.sites.yml", $aliasPath, false)
        ->run();

        if (!$task->wasSuccessful()) {
            throw new TaskException($task, "Could not copy Drush aliases.");
        }

        return $task;
    }

    /**
     * Setup the Drupal aliases.
     */
    public function confDrushAlias(): Result
    {
        $this->say('setup:drupal-alias');
        $drushFile = $this->getDocroot() . '/drush/sites/' . $this->getConfigValue('project.machine_name') . '.site.yml';
        if (
            empty($this->getConfigValue('remote.dev.host')) ||
            empty($this->getConfigValue('remote.dev.user')) ||
            empty($this->getConfigValue('remote.dev.root')) ||
            empty($this->getConfigValue('remote.dev.uri')) ||
            empty($this->getConfigValue('remote.stage.host')) ||
            empty($this->getConfigValue('remote.stage.user')) ||
            empty($this->getConfigValue('remote.stage.root')) ||
            empty($this->getConfigValue('remote.stage.uri'))
        ) {
            $this->io()->newLine();
            $this->io()->warning('Drush aliases were not properly configured. Please add the information about remote server and run the command again.');
        }
        $task = $this->taskReplaceInFile($drushFile)
        ->from('${REMOTE_DEV_HOST}')
        ->to($this->getConfigValue('remotes.dev.host'))
        ->taskReplaceInFile($drushFile)
        ->from('${REMOTE_DEV_USER}')
        ->to($this->getConfigValue('remotes.dev.user'))
        ->taskReplaceInFile($drushFile)
        ->from('${REMOTE_DEV_ROOT}')
        ->to($this->getConfigValue('remotes.dev.root'))
        ->taskReplaceInFile($drushFile)
        ->from('${REMOTE_DEV_URI}')
        ->to($this->getConfigValue('remotes.dev.uri'))
        ->taskReplaceInFile($drushFile)
        ->from('${REMOTE_STAGE_HOST}')
        ->to($this->getConfigValue('remotes.stage.host'))
        ->taskReplaceInFile($drushFile)
        ->from('${REMOTE_STAGE_USER}')
        ->to($this->getConfigValue('remotes.stage.user'))
        ->taskReplaceInFile($drushFile)
        ->from('${REMOTE_STAGE_ROOT}')
        ->to($this->getConfigValue('remotes.stage.root'))
        ->taskReplaceInFile($drushFile)
        ->from('${REMOTE_STAGE_URI}')
        ->to($this->getConfigValue('remotes.stage.uri'))
        ->run();

        if (!$task->wasSuccessful()) {
            throw new TaskException($task, "Could not configure Drush aliases.");
        }

        return $task;
    }

    /**
     * Setup lando.yml for local environment.
     */
    public function confLando(): Result
    {
        $this->say('setup:lando');
        $landoFile = $this->getDocroot() . '/.lando.yml';
        if (!file_exists($landoFile)) {
            $confirm = $this->io()->confirm('Lando file does not exist. Do you want to initialize lando for local development?', true);
            if (!$confirm) {
                return Result::cancelled();
            }

            $this->taskFilesystemStack()
            ->touch($landoFile)
            ->copy($this->getDocroot() . "/vendor/specbee/robo-tooling/config/.lando.yml", $landoFile, true)
            ->run();
        }

        $task = $this->taskReplaceInFile($landoFile)
        ->from('${PROJECT_NAME}')
        ->to($this->getConfigValue('project.machine_name'))
        ->taskReplaceInFile($landoFile)
        ->from('${WEBROOT}')
        ->to($this->getConfigValue('drupal.webroot'))
        ->run();

        if (!$task->wasSuccessful()) {
            throw new TaskException($task, "Could not setup Lando.");
        } else {
            $this->io()->newLine();
            $this->io()->success("Lando was successfully initialized. Run `lando start` to spin up the docker containers");
        }

        return $task;
    }

    /**
     * Setup Grumphp file.
     */
    public function confGrumphp(): Result
    {
        $this->say('setup:grumphp');
        $grumphpFile = $this->getDocroot() . '/grumphp.yml';
        if (!file_exists($grumphpFile)) {
            $confirm = $this->io()->confirm('Grumphp configuration not found. Do you want to initialize Grumphp?', true);
            if (!$confirm) {
                return Result::cancelled();
            }

            $this->taskComposerRequire()
            ->dependency('vijaycs85/drupal-quality-checker')
            ->dev()
            ->noInteraction()
            ->run();

            $this->taskFilesystemStack()
            ->touch($grumphpFile)
            ->copy($this->getDocroot() . "/vendor/specbee/robo-tooling/config/grumphp.yml", $grumphpFile, true)
            ->run();
        }
        $task = $this->taskReplaceInFile($grumphpFile)
        ->from('${PROJECT_PREFIX}')
        ->to($this->getConfigValue('project.prefix'))
        ->run();

        if (!$task->wasSuccessful()) {
            throw new TaskException($task, "Could not setup Lando.");
        } else {
            $this->io()->newLine();
            $this->io()->success("Grumphp is successfully configured to watch your commits.");
        }

        return $task;
    }

    /**
     * Commit the changes of init:project command.
     */
    public function commitChanges(): Result
    {
        $this->say('Committing the changes...');
        return $this->taskGitStack()
        ->stopOnFail()
        ->dir($this->getDocroot())
        ->add('-A')
        ->commit($this->getConfigValue('project.prefix') . '-000: Initialized new project from Specbee starterkit.')
        ->interactive(false)
        ->printOutput(false)
        ->printMetadata(false)
        ->run();
    }
}
