<?php

namespace SbRoboTooling\Robo\Plugin\Commands;

use Robo\Contract\VerbosityThresholdInterface;
use Robo\Exception\TaskException;
use Robo\Result;
use Robo\Tasks;
use SbRoboTooling\Robo\Traits\UtilityTrait;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Defines commands in the validate:* namespace.
 */
class ValidateCommands extends Tasks
{
    use UtilityTrait;

    /**
     * Array containing the file extensions PHPCS should check.
     *
     * @var array
     */
    protected $phpcsCheckExtensions;

    /**
     * Array containing paths PHPCS should ignore.
     *
     * @var array
     */
    protected $phpcsIgnorePaths;

    /**
     * A space separated list of custom code paths.
     *
     * @var string
     */
    protected $customCodePaths;

    /**
     * RoboFile constructor.
     */
    public function __construct()
    {
        $this->stopOnFail();
        $this->phpcsCheckExtensions = $this->getConfigValue('phpcs.check_extensions');
        $this->phpcsIgnorePaths = $this->getConfigValue('phpcs.ignore_paths');
        $this->customCodePaths = implode(' ', $this->getCustomCodePaths());
    }

    /**
     * Get custom code paths for static analysis tools to use.
     *
     * @return array
     *   An array containing application custom code paths.
     *
     * @throws \Robo\Exception\TaskException
     */
    protected function getCustomCodePaths(): array
    {
        if (!$customCodePaths = $this->getConfigValue('phpcs.code_paths')) {
            throw new TaskException($this, 'Expected Robo configuration not present: phpcs.code_paths');
        }
        return $customCodePaths;
    }

    /**
     * Get coding standard(s) to use in PHPCS checks.
     *
     * @return array
     *   An array containing application custom code paths.
     *
     * @throws \Robo\Exception\TaskException
     */
    protected function getCodingStandards(): array
    {
        if (!$phpcsStandards = $this->getConfigValue('phpcs.standards')) {
            throw new TaskException($this, 'Expected Robo configuration not present: phpcs.standards');
        }
        return $phpcsStandards;
    }

    /**
     * Validate Composer.
     *
     * @command validate:composer
     *
     * @aliases vc
    */
    public function validateComposer(): Result
    {
        $this->say("Validating composer.json and composer.lock...");
        $task = $this->taskExecStack()
        ->dir($this->getDocroot())
        ->exec('composer validate --no-check-all --ansi')
        ->run();
        if (!$task->wasSuccessful()) {
            $this->io()->error('The composer.lock is invalid. Mostly a `composer update --lock` will resolve the issue. Otherwise, run `composer update` to fix the problem.');
            throw new TaskException($task, "The composer.lock file is invalid!");
        }

        $task = $this->taskExecStack()
        ->dir($this->getDocroot())
        ->exec('composer normalize --dry-run')
        ->run();

        if (!$task->wasSuccessful()) {
            $this->io()->error($task->getMessage());
            throw new TaskException($task, "The composer.json file is not normalized!");
        }

        return $task;
    }

    /**
     * Validate custom code coding standards using PHPCS.
     *
     * @command validate:phpcs
     *
     * @aliases phpcs
    */
    public function validatePhpCs(): Result
    {
        $tasks = [];
        $this->say("Validating Drupal coding standards...");
        foreach ($this->getCustomCodePaths() as $path) {
            if (!file_exists($path)) {
                $this->io()->newLine();
                $this->io()->warning('Path ' . $path . ' not found. PHPCS will likely fail. Skipping...');
                $this->io()->newLine();
                return $this->taskExecStack()
                ->setVerbosityThreshold(VerbosityThresholdInterface::VERBOSITY_DEBUG)
                ->exec('echo Skipping...')
                ->run();
            }
        }
        $tasks[] = $this->taskExecStack()
        ->exec('vendor/bin/phpcs --config-set installed_paths vendor/drupal/coder/coder_sniffer')
        ->run();
        $standards = implode(',', $this->getCodingStandards());
        $extensions = implode(',', $this->phpcsCheckExtensions);
        $ignorePaths = implode(',', $this->phpcsIgnorePaths);

        return $this->taskExecStack()
        ->stopOnFail()
        ->exec("vendor/bin/phpcs --standard=$standards --extensions=$extensions \
                --ignore=$ignorePaths $this->customCodePaths")
        ->run();
    }

    /**
     * Lint the theme files.
     *
     * @command validate:theme
     *
     * @aliases vt
     */
    public function validateTheme(): Result
    {
        if (empty($this->getConfigValue('drupal.theme.lint'))) {
            $this->io()->newLine();
            $this->io()->note('No theme lint command found. Skipping...');
            $this->io()->newLine();
            return $this->taskExecStack()
            ->setVerbosityThreshold(VerbosityThresholdInterface::VERBOSITY_DEBUG)
            ->exec('echo Skipping...')
            ->run();
        }

        return $this->taskExecStack()
            ->exec($this->getConfigValue('drupal.theme.build'))
            ->exec($this->getConfigValue('drupal.theme.lint'))
            ->dir($this->getConfigValue('drupal.theme.path'))
            ->run();
    }

    /**
     * Run all validate command.
     *
     * @command validate
     */
    public function validate(): void
    {
        $this->io()->title('Validating fileset...');
        $this->validateComposer();
        $this->validatePhpCs();
        $this->validateTheme();
    }
}
