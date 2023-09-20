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
        $this->prepareArtifact();
        $this->sanitizeArtifact();
        $this->addGitRemote();
        $this->mergeUpstreamChanges();
        $this->push();
    }

    /**
     * Prepare the Artifact.
     */
    protected function prepareArtifact()
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
        $this->say("Committing artifact to <comment>{$this->branchName}</comment>...");

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
        ->commit("Commit:" . $commit . ": " . $message)
        ->push('upstream', $this->branchName)
        ->run();


        if (!$task->wasSuccessful()) {
            throw new TaskException($task, "Failed to commit deployment artifact!");
        }
    }
}
