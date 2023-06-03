<?php

namespace App\Deployment;

use Symfony\Component\Yaml\Yaml;

class TriggerDeploymentGenerator
{
    public string $projectName;

    public string|null $projectRepo = null;

    public array $servers = [];

    public array $commands = [];

    public function __construct(string $projectName, string|null $projectRepo = null)
    {
        $this->projectName = $projectName;
        $this->projectRepo = $projectRepo;
    }

    /**
     * --------------------------------------------------
     * SERVERS
     * --------------------------------------------------
     */

    /**
     * Make the servers array the default.
     *
     * @return $this
     */
    public function defaultServers(): static
    {
        $this->servers = [
            [
                'name' => 'Server 1',
                'trigger' => [
                    'url' => '',
                    'method' => 'GET',
                    'headers' => [],
                ],
                'tags' => [
                    'prod',
                ],
            ],
        ];

        return $this;
    }

    /**
     * --------------------------------------------------
     * COMMANDS
     * --------------------------------------------------
     */

    /**
     * Make the commands array the default.
     *
     * @return $this
     */
    public function defaultCommands(): static
    {
        $this->commands = [
        ];

        return $this;
    }

    public function laravelCommands()
    {
        $this->commands = [
        ];

        return $this;
    }

    /**
     * --------------------------------------------------
     * GENERATE
     * --------------------------------------------------
     */

    /**
     * Return the json of the generated deployment.
     */
    public function toJson(): bool|string
    {

        return json_encode([
            'version' => 1,
            'name' => $this->projectName,
            'repo' => $this->projectRepo,
            'triggers' => true,
            'servers' => $this->servers,
            'commands' => $this->commands,
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Returns the Yaml of the generated deployment.
     */
    public function toYaml(): string
    {
        $array = [
            'version' => 1,
            'name' => $this->projectName,
            'repo' => $this->projectRepo,
            'triggers' => true,
            'servers' => $this->servers,
            'commands' => $this->commands,
        ];

        $yaml = Yaml::dump($array);

        return preg_replace('/^(  +)/m', '$1$1', $yaml);
    }

    /**
     * Return the array of the generated deployment.
     */
    public function toArray(): array
    {
        return [
            'name' => $this->projectName,
            'repo' => $this->projectRepo,
            'triggers' => true,
            'servers' => $this->servers,
            'commands' => $this->commands,
        ];
    }

    public function toFileArray(array $array = null, string $indentation = ''): string
    {

        $output = '<?php'.PHP_EOL.PHP_EOL;
        $output .= 'return ';

        if ($array === null) {
            $output .= var_export($this->toArray(), true);
        } else {
            $output .= var_export($array, true);
        }

        $output = str_replace('array (', '[', $output);
        $output = str_replace(')', ']', $output);

        // Find all the numeric keys and remove them
        $output = preg_replace('/[0-9]+ => /', '', $output);

        $output .= ';'.PHP_EOL;

        return $output;

    }
}
