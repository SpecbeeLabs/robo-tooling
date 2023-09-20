<?php

namespace Specbee\DevSuite\Robo\Plugin\Commands;

use Robo\Contract\VerbosityThresholdInterface;
use Robo\Exception\TaskException;
use Robo\Tasks;
use Specbee\DevSuite\Robo\Traits\IO;
use Specbee\DevSuite\Robo\Traits\UtilityTrait;
use Symfony\Component\Finder\Finder;

/**
 * Defines command deploy code.
 */
class DeployCommand extends Tasks
{
    use UtilityTrait;
    use IO;

    protected $branchName;

    /**
     * Builds the artifact and pushes to git.remotes.
     *
     * @command artifact:deploy
     */
    public function deploy($branch)
    {

        if (empty($this->getConfigValue('project.git.remote'))) {
            $this->info("The built artifact will not be pushed since the git remote configuration is not added.");
        }

        $this->branchName = $branch . "-build";
        $this->buildDependencies();
        $this->prepareDir();
        $this->addGitRemote();
        $this->checkoutLocalDeployBranch();
        $this->mergeUpstreamChanges();
        $this->sanitizeArtifact();
        $this->push();
    }

    /**
     * Prepare the Artifact.
     */
    protected function buildDependencies()
    {
        $this->say("Preparing the artifact...");
        $composerBuildTask = $this->taskComposerInstall()
        ->noDev()
        ->optimizeAutoloader()
        ->ansi()
        ->run();

        if (!$composerBuildTask->wasSuccessful()) {
            $this->toggleMaintenanceMode(0);
            return new TaskException($composerBuildTask, "Deployment failed. Check the logs for more information");
        }

        // Build frontend.
        $this->buildTheme();
    }

    /**
     * Deletes the existing deploy directory and initializes git repo.
     */
    protected function prepareDir()
    {
        $this->say("Preparing artifact directory...");
        $git_user = $this->getConfigValue("project.git.user.name");
        $git_email = $this->getConfigValue("project.git.user.email");
        $this->taskExecStack()
        ->setVerbosityThreshold(VerbosityThresholdInterface::VERBOSITY_VERBOSE)
        ->stopOnFail()
        ->dir($this->getDocroot())
        ->exec("git config --local --add user.name '$git_user'")
        ->exec("git config --local --add user.email '$git_email'")
        ->exec("git config --local core.fileMode true")
        ->run();
    }

    /**
     * Sanitize the artifact.
     */
    protected function sanitizeArtifact()
    {
        $docroot = $this->getDocroot();
        $this->say("Sanitizing the artifact...");
        $this->taskExecStack()
        ->exec("find '{$docroot}/vendor' -type d -name '.git' -exec rm -fr \\{\\} \\+")
        ->exec("find '{$docroot}/docroot' -type d -name '.git' -exec rm -fr \\{\\} \\+")
        ->stopOnFail()
        ->setVerbosityThreshold(VerbosityThresholdInterface::VERBOSITY_VERBOSE)
        ->run();

        $taskFilesystemStack = $this->taskFilesystemStack()
        ->setVerbosityThreshold(VerbosityThresholdInterface::VERBOSITY_VERBOSE);

        $finder = new Finder();
        $files = $finder
        ->in($docroot)
        ->files()
        ->name('CHANGELOG.txt');

        foreach ($files->getIterator() as $item) {
            $taskFilesystemStack->remove($item->getRealPath());
        }

        $finder = new Finder();
        $files = $finder
        ->in($docroot . '/' . $this->getConfigValue('drupal.webroot') . '/core')
        ->files()
        ->name('*.txt');

        foreach ($files->getIterator() as $item) {
            $taskFilesystemStack->remove($item->getRealPath());
        }

        $this->say("Removing .txt files...");
        $taskFilesystemStack->run();
    }

    /**
     * Adds a single remote to the /deploy repository.
     */
    protected function addGitRemote()
    {
        $git_remote = $this->getConfigValue('project.git.remote');
        if (empty($git_remote)) {
            $this->info("git.remotes is empty. Please define the git.remote in robo.yml.");
        }
      // Generate an md5 sum of the remote URL to use as remote name.
        $this->say("Adding git remote:" . $this->getConfigValue('project.git.remote'));
        $this->taskExecStack()
        ->stopOnFail()
        ->setVerbosityThreshold(VerbosityThresholdInterface::VERBOSITY_VERBOSE)
        ->dir($this->getDocroot())
        ->exec("git remote add upstream $git_remote")
        ->run();
        $this->say("Added git remote:" . $this->getConfigValue('project.git.remote'));
    }

    /**
     * Checks out a new, local branch for artifact.
     */
    protected function checkoutLocalDeployBranch()
    {
        $this->taskExecStack()
        ->dir($this->getDocroot())
        // Create new branch locally.We intentionally use stopOnFail(FALSE) in
        // case the branch already exists. `git checkout -B` does not seem to work
        // as advertised.
        // @todo perform this in a way that avoid errors completely.
        ->stopOnFail(false)
        ->setVerbosityThreshold(VerbosityThresholdInterface::VERBOSITY_VERBOSE)
        ->exec("git checkout -b {$this->branchName}")
        ->run();
    }

    /**
     * Merges upstream changes into deploy branch.
     */
    protected function mergeUpstreamChanges()
    {
        $this->say("Merging upstream changes into local artifact...");
        $task = $this->taskGitStack()
        ->stopOnFail(false)
        ->dir($this->getDocroot())
        ->exec("fetch upstream {$this->branchName}")
        ->exec("merge upstream/{$this->branchName}")
        ->setVerbosityThreshold(VerbosityThresholdInterface::VERBOSITY_VERBOSE)
        ->run();
    }

    /**
     * Push the artifact to build repository.
     */
    protected function push()
    {
        $this->say("Pushing artifact to <comment>{$this->branchName}</comment>...");

        // Force add all dependencies.
        chdir($this->getDocroot());
        $webroot = $this->getDocroot() . '/' . $this->getConfigValue('drupal.webroot');
        $dirs = [
            'vendor',
            $webroot . '/core',
            $webroot . '/modules/contrib',
            $webroot . '/themes/contrib',
            $webroot . '/profiles/contrib',
            $webroot . '/libraries',
            $this->getConfigValue('drupal.theme.path')
        ];
        $this->taskGitStack()
        ->exec("add -f " . implode(" ", $dirs))
        ->run();

        $this->taskGitStack()->exec('prune')->run();

        // Commit the new changes.
        $commit = trim(shell_exec("git rev-parse HEAD"));
        $message = trim(shell_exec("git log -1 --pretty=%B"));
        $task = $this->taskGitStack()
        ->commit("Commit:" . $commit . ": " . $message, '--quiet')
        ->push('upstream', $this->branchName)
        ->run();


        if (!$task->wasSuccessful()) {
            throw new TaskException($task, "Failed to commit deployment artifact!");
        }
    }

    /**
     * Fetch remote changes from git repository.
     *
     * @command fetch:remote:branch
     */
    public function fetchRemoteBranch($branch, $remote = 'origin')
    {
        $this->say('Remove any untracked file just for safety and fetch the remote changes.');
        $this->taskGitStack()
        ->stopOnFail()
        ->checkout('.')
        ->exec('clean -f -d')
        ->checkout($branch)
        ->exec("fetch $remote {$this->branchName}")
        ->exec("merge $remote/{$this->branchName}")
        ->run();
    }

    /**
     * Run site updates after code deployment.
     *
     * @command artifact:update:drupal
     */
    public function siteUpdate()
    {
        $this->say("Deploying updates to " . $this->getConfigValue('project.human_name') . "...");
        $this->say("Putting the site on maintenance mode");
        $this->toggleMaintenanceMode(1);
        // Check site status to debug in case of failed deployment.
        $this->drush()
        ->args('core:status')
        ->option('ansi')
        ->run();

        // Run the drush deploy command which runs the below commands.
        /**
         *  drush updatedb --no-cache-clear
         * drush cache:rebuild
         * drush config:import
         * drush cache:rebuild
         * drush deploy:hook
         */
        $task = $this->drush()
        ->args('deploy')
        ->option('-v')
        ->option('-y')
        ->option('ansi')
        ->run();

        if (!$task->wasSuccessful()) {
            $this->toggleMaintenanceMode(0);
            return new TaskException($task, "Deployment failed. Check the logs for more information");
        }

        // Import the latest configuration again. This includes the latest
        // configuration_split configuration. Importing this twice ensures that
        // the latter command enables and disables modules based upon the most up
        // to date configuration. Additional information and discussion can be
        // found here:
        // https://github.com/drush-ops/drush/issues/2449#issuecomment-708655673
        $task = $this->drush()
        ->arg('config:import')
        ->option('ansi')
        ->option('no-interaction')
        ->run();

        if (!$task->wasSuccessful) {
            return new TaskException($task, "Deployment failed. Check the logs for more information");
        }

        # Run cron.
        $this->drush()
        ->args('core-cron')
        ->option('ansi')
        ->run();

        # Clear the cache.
        $this->cacheRebuild();

        $this->say("Removing the site from maintenance mode");
        $this->toggleMaintenanceMode(1);
    }
}
