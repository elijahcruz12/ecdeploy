<?php

namespace App\Json;

class DeployGenerator
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
                'host' => '',
                'user' => '',
                'port' => 22,
                'tags' => [
                    'production'
                ],
                'path' => '~/' . $this->projectName,
                'commands' => ['before', 'during', 'after']
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
     * @return $this
     */
    public function defaultCommands(): static
    {
        $this->commands = [
            'before' => [],
            'during' => [],
            'after' => [],
            'extra' => []
        ];

        return $this;
    }

    public function laravelCommands()
    {
        $this->commands = [
            'before' => [
                'php artisan down',
                'php artisan optimize:clear',
                'git pull origin master',
                'composer install --no-interaction --prefer-dist --no-dev --optimize-autoloader',
            ],
            'during' => [
                'php artisan migrate --force',
                'php artisan config:cache',
                'php artisan route:cache',
                'php artisan view:cache',
            ],
            'after' => [
                'php artisan up',
            ],
            'queue' => [
                'php artisan queue:restart',
            ],
            'node' => [
                'npm install',
                'npm run prod',
            ],

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
     *
     * @return bool|string
     */
    public function toJson(): bool|string
    {

        return json_encode([
            'name' => $this->projectName,
            'repo' => $this->projectRepo,
            'servers' => $this->servers,
            'commands' => $this->commands
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Return the array of the generated deployment.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'name' => $this->projectName,
            'repo' => $this->projectRepo,
            'servers' => $this->servers,
            'commands' => $this->commands
        ];
    }
}
