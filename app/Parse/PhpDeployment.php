<?php

namespace App\Parse;

use App\Interfaces\Deployments\DeploymentInterface;
use App\Models\Deployment;
use Illuminate\Support\Collection;

class PhpDeployment implements DeploymentInterface
{
    public string $projectName = '';

    public string|null $projectRepo = null;

    public Collection|null $servers = null;

    public bool $isTriggered = false;

    public Collection|null $commands = null;

    public static function load(): static
    {
        // Get the deploy.php file
        $deploy = require getcwd().'/deploy.php';

        // The deploy.php file is a PHP file that returns an array
        // of the deployment configuration. We can use this to create a new instance
        // of this class.

        $class = new self();

        $class->projectName = $deploy['name'];
        $class->projectRepo = $deploy['repo'] ?? null;
        $class->servers = collect($deploy['servers']);
        $class->isTriggered = $deploy['triggers'] ?? false;
        $class->commands = collect($deploy['commands']);

        return $class;
    }

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

        $serverCommands = [
            'echo "Connction to '.$server['name'].' successful"',
            'mkdir -p '.$server['path'],
            'cd '.$server['path'],
        ];

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
        return file_exists(getcwd().'/deploy.php');
    }

    public static function encryptedFileExists(): bool
    {
        return file_exists(getcwd().'/deploy.php.enc');
    }

    public static function loadEncryptedFile(string $password): static
    {
        $data = openssl_decrypt(file_get_contents(getcwd().'/deploy.php.enc'), 'aes-256-cbc', $password, 0, substr(hash('sha256', 'deploy'), 0, 16));

        $deploy = require getcwd().'/deploy.php.enc';

        // The deploy.php file is a PHP file that returns an array
        // of the deployment configuration. We can use this to create a new instance
        // of this class.

        $class = new self();

        $class->projectName = $deploy['name'];
        $class->projectRepo = $deploy['repo'] ?? null;
        $class->servers = collect($deploy['servers']);
        $class->isTriggered = $deploy['triggers'] ?? false;
        $class->commands = collect($deploy['commands']);

        return $class;
    }

    public static function validatePassword(string $password): bool
    {
        $data = openssl_decrypt(file_get_contents(getcwd().'/deploy.php.enc'), 'aes-256-cbc', $password, 0, substr(hash('sha256', 'deploy'), 0, 16));

        return ! ($data == false);
    }

    public function validate()
    {
        $errors = collect();

        if (empty($this->projectName)) {
            $errors->push('Project name is required.');
        }

        if (empty($this->projectRepo)) {
            $errors->push('Project repo is required.');
        }

        if ($this->servers->count() == 0) {
            $errors->push('At least one server is required.');
        }

        if (! $this->isTriggered) {
            if ($this->commands->count() == 0) {
                $errors->push('At least one command is required.');
            }
        }

        return $errors;
    }
}
