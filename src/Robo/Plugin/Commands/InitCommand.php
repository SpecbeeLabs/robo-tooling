<?php

namespace SbRoboTooling\Robo\Plugin\Commands;

use Robo\Exception\TaskException;
use Robo\Result;
use Robo\Robo;
use Robo\Tasks;
use SbRoboTooling\Robo\Traits\UtilityTrait;

/**
 * Robo commands to initialize a project.
 */
class InitCommand extends Tasks
{
    use UtilityTrait;

    public function __construct()
    {
        if (!file_exists("./robo.yml")) {
            throw new TaskException(
                $this,
                "No Robo configuration file detected.
  Copy the example.robo.yml file to your project root and rename it to robo.yml."
            );
        }
    }

    /**
     * Initialize Git and make an empty initial commit.
     */
    public function initGit()
    {
        if (!file_exists($this->getDocroot() . "/.git")) {
            $this->io()->title("Initializing empty Git repository in " . $this->getDocroot());
            $this->say('setup:git');
            chdir($this->getDocroot());
            $config = Robo::config();
            $result = $this->taskGitStack()
            ->stopOnFail()
            ->exec("git init")
            ->commit('Initial commit.', '--allow-empty')
            ->add('-A')
            ->commit($config->get('project.prefix') . '-000: Created project from Specbee starterkit.')
            ->interactive(false)
            ->printOutput(false)
            ->printMetadata(false)
            ->run();

            // Throw exception is the command fails.
            if (!$result->wasSuccessful()) {
                throw new TaskException($result, "Could not initialize Git repository.");
            }
        } else {
            $this->say("Git is already initialized at " . $this->getDocroot() . ". Skipping...");
        }
    }

    /**
     * Initialize and configure project tools.
     *
     * @command init:repo
     */
    public function initRepo()
    {
        $this->io()->title("Initializing and configuring project tool at " . $this->getDocroot());
        $this->copyDrushAliases();
        $this->confDrushAlias();
        $this->confLando();
        $this->confGrumphp();
    }

    /**
     * Copy default drush aliases file.
     */
    public function copyDrushAliases()
    {
        $this->say('copy:default-drush-alias');
        $config = Robo::config();
        if (!$config->get('project.machine_name')) {
            throw new TaskException(
                $this,
                "Require Robo configuration 'project.machine_name' not found in the configuration file."
            );
        }

        $drushPath = $this->getDocroot() . '/drush/sites';
        $aliasPath = $drushPath . '/' . $config->get('project.machine_name') . '.site.yml';

        // Skip if alias file is already generated.
        if (file_exists($aliasPath)) {
            $this->io()->newLine();
            $this->yell('Drush alias file exists. Skipping');
            $this->io()->newLine();
            return;
        }

        // Check if default.sites.yml exisits (the file will exist if project is created using specbee/drupal-start)
        // Attempt to create an aliase file if does not exists.
        if (!file_exists($drushPath . "/default.site.yml")) {
            $confirm = $this->io()->confirm('Default Drush aliases file does not exist. Do you want to create one?', true);
            if (!$confirm) {
                return Result::cancelled();
            }

            $this->taskFilesystemStack()
            ->mkdir($drushPath)
            ->touch($drushPath . '/default.sites.yml')
            ->copy($this->getDocroot() . "/vendor/specbee/robo-tooling/assets/default.sites.yml", $drushPath . '/default.sites.yml', true)
            ->run();
        }

        $task = $this->taskFilesystemStack()
        ->rename($drushPath . "/default.sites.yml", $aliasPath, false)
        ->run();

        if (!$task->wasSuccessful()) {
            throw new TaskException($task, "Could not copy Drush aliases.");
        }
    }

    /**
   * Setup the Drupal aliases.
   */
    public function confDrushAlias()
    {
        $this->say('setup:drupal-alias');
        $config = Robo::config();
        $drushFile = $this->getDocroot() . '/drush/sites/' . $config->get('project.machine_name') . '.site.yml';
        if (
            empty($config->get('remote.dev.host')) ||
            empty($config->get('remote.dev.user')) ||
            empty($config->get('remote.dev.root')) ||
            empty($config->get('remote.dev.uri')) ||
            empty($config->get('remote.stage.host')) ||
            empty($config->get('remote.stage.user')) ||
            empty($config->get('remote.stage.root')) ||
            empty($config->get('remote.stage.uri'))
        ) {
            $this->io()->newLine();
            $this->io()->warning('Drush aliases were not properly configured. Please add the information about remote server and run the command again.');
            return;
        }
        $task = $this->taskReplaceInFile($drushFile)
        ->from(['${REMOTE_DEV_HOST}', '${REMOTE_DEV_USER}', '${REMOTE_DEV_ROOT}', '${REMOTE_DEV_URI}', '${REMOTE_STAGE_HOST}', '${REMOTE_STAGE_USER}', '${REMOTE_STAGE_ROOT}', '${REMOTE_STAGE_URI}'])
        ->to([$config->get('remote.dev.host'), $config->get('remote.dev.user'), $config->get('remote.dev.root'), $config->get('remote.dev.uri'), $config->get('remote.stage.host'), $config->get('remote.stage.user'), $config->get('remote.stage.root'), $config->get('remote.stage.uri')])
        ->run();

        if (!$task->wasSuccessful()) {
            throw new TaskException($task, "Could not configure Drush aliases.");
        }
    }

    /**
    * Setup lando.yml for local environment.
    */
    public function confLando()
    {
        $this->say('setup:lando');
        $config = Robo::config();
        $landoFile = $this->getDocroot() . '/.lando.yml';
        if (!file_exists($landoFile)) {
            $confirm = $this->io()->confirm('Lando file does not exist. Do you want to initialize lando for local development?', true);
            if (!$confirm) {
                return Result::cancelled();
            }

            $this->taskFilesystemStack()
            ->touch($landoFile)
            ->copy($this->getDocroot() . "/vendor/specbee/robo-tooling/assets/.lando.yml", $landoFile, true)
            ->run();
        }
        $task = $this->taskReplaceInFile($landoFile)
        ->from('${PROJECT_NAME}')
        ->to($config->get('project.machine_name'))
        ->run();

        if (!$task->wasSuccessful()) {
            throw new TaskException($task, "Could not setup Lando.");
        } else {
            $this->io()->newLine();
            $this->io()->success("Lando was successfully initialized. Run `lando start` to spin up the docker containers");
        }
    }

    /**
    * Setup Grumphp file.
    */
    public function confGrumphp()
    {
        $this->say('setup:grumphp');
        $config = Robo::config();
        $grumphpFile = $this->getDocroot() . '/grumphp.yml';
        if (!file_exists($grumphpFile)) {
            $confirm = $this->io()->confirm('Grumphp configuration not found. Do you want to initialize Grumphp?', true);
            if (!$confirm) {
                return Result::cancelled();
            }

            $this->io()->note("Ensure Grumphp is installed. If not run `composer require vijaycs85/drupal-quality-checker` to install it.");

            $this->taskFilesystemStack()
            ->touch($grumphpFile)
            ->copy($this->getDocroot() . "/vendor/specbee/robo-tooling/assets/grumphp.yml", $grumphpFile, true)
            ->run();
        }
        $task = $this->taskReplaceInFile($grumphpFile)
        ->from('${PROJECT_PREFIX}')
        ->to($config->get('project.prefix'))
        ->run();

        if (!$task->wasSuccessful()) {
            throw new TaskException($task, "Could not setup Lando.");
        } else {
            $this->io()->newLine();
            $this->io()->success("Grumphp is successfully configured to watch your commits.");
        }
    }
}
