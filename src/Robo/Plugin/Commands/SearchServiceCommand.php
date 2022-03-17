<?php

namespace Specbee\DevSuite\Robo\Plugin\Commands;

use Robo\Result;
use Robo\Tasks;
use Specbee\DevSuite\Robo\Traits\IO;
use Specbee\DevSuite\Robo\Traits\UtilityTrait;
use Symfony\Component\Yaml\Yaml;

/**
 * Defines command to initialize cache service.
 */
class SearchServiceCommand extends Tasks
{
    use UtilityTrait;
    use IO;

    /**
     * Setup redis.
     *
     * @command service:init:search
     */
    public function initServiceSearch()
    {
        $docroot = $this->getDocroot();
        $this->say('Setting up Elasticsearch.');

        $landoFileConfig = Yaml::parse(file_get_contents($docroot . '/.lando.yml', 128));
        $this->say('Checking if there is cache service is setup.');
        if (!array_key_exists('search', $landoFileConfig['services'])) {
            $this->say("Updating Landofile");
            $landoFileConfig['services']['search'] = [
            'type' => 'elasticsearch:7',
            'portforward' => true,
            'mem' => '1025m',
            'environment' => [
                    'cluster.name=' . $this->getConfigValue('project.machine_name')
                ]
            ];

            file_put_contents($docroot . '/.lando.yml', Yaml::dump($landoFileConfig, 5, 2));
            $this->success('Lando configurations are updated with search service.');
            $this->info('Do a `lando rebuild` for the change to take effect.');
        } else {
            $this->info('Search service is already added to Lando configuration.', true);
        }
    }
}
