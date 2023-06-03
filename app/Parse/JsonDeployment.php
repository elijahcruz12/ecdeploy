<?php

namespace App\Parse;

use App\Interfaces\Deployments\DeploymentInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class JsonDeployment implements DeploymentInterface
{
    public string $projectName = '';

    public string|null $projectRepo = null;

    public Collection|null $servers = null;

    public Collection|null $commands = null;

    public function __construct()
    {

    }

    public static function load(): static
    {
        $deploy = json_decode(File::get(getcwd().'/deploy.json'), true);

        $class = new self();

        $class->projectName = $deploy['name'];
        $class->projectRepo = $deploy['repo'] ?? null;
        $class->servers = collect($deploy['servers']);
        $class->commands = collect($deploy['commands']);

        return $class;
    }

    /**
     * @return $this
     */
    public function serversByTags(array $tags): static
    {

        $this->servers->filter(function ($server) use ($tags) {
            return count(array_intersect($server['tags'], $tags)) > 0;
        });

        return $this;
    }

    public function serversByName(array $names): static
    {
        $this->servers->filter(function ($server) use ($names) {
            return in_array($server['name'], $names);
        });

        return $this;
    }

    public function getCommandsForServer(array $server): array
    {
        $stages = $server['commands'];

        $serverCommands = [];

        foreach ($this->commands as $stage => $commands) {
            if (in_array($stage, $stages)) {
                foreach ($commands as $command) {
                    $serverCommands[] = $command;
                }
            }
        }

        return $serverCommands;
    }

    public static function exists(): bool
    {
        return File::exists(getcwd().'/deploy.json');
    }

    public function validate()
    {
        $errors = collect();

        if ($this->projectName === '') {
            $errors->push('Project name is required');
        }

        if ($this->projectRepo === null) {
            $errors->push('Project repo is not set');
        }

        if ($this->servers->count() === 0) {
            $errors->push('No servers are defined');
        }

        if ($this->commands->count() === 0) {
            $errors->push('No commands are defined');
        }

        return $errors;
    }

    public static function encryptedFileExists(): bool
    {
        return File::exists(getcwd().'/deploy.json.enc');
    }

    public static function loadEncryptedFile(string $password): static
    {
        $data = openssl_decrypt(file_get_contents(getcwd().'/deploy.json.env'), 'aes-256-cbc', $password, 0, substr(hash('sha256', 'deploy'), 0, 16));

        $deploy = json_decode($data, true);

        $class = new self();

        $class->projectName = $deploy['name'];
        $class->projectRepo = $deploy['repo'] ?? null;
        $class->servers = collect($deploy['servers']);
        $class->commands = collect($deploy['commands']);

        return $class;
    }

    public static function validatePassword(string $password): bool
    {
        $data = openssl_decrypt(file_get_contents(getcwd().'/deploy.json.env'), 'aes-256-cbc', $password, 0, substr(hash('sha256', 'deploy'), 0, 16));

        return ! ($data == false);
    }
}
