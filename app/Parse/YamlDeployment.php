<?php

namespace App\Parse;

use App\Interfaces\Deployments\DeploymentInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Symfony\Component\Yaml\Yaml;

class YamlDeployment implements DeploymentInterface
{
    public string $projectName = '';

    public string|null $projectRepo = null;

    public Collection|null $servers = null;

    public Collection|null $commands = null;

    public static function load(): static
    {
        $yaml = Yaml::parseFile(getcwd().'/deploy.yaml');

        $class = new self();

        $class->projectName = $yaml['name'];
        $class->projectRepo = $yaml['repo'] ?? null;
        $class->servers = collect($yaml['servers']);
        $class->commands = collect($yaml['commands']);

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
        return File::exists(getcwd().'/deploy.yaml');
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
        return File::exists(getcwd().'/deploy.yaml.enc');
    }

    public static function loadEncryptedFile(string $password): static
    {
        $data = openssl_decrypt(file_get_contents(getcwd().'/deploy.yaml.env'), 'aes-256-cbc', $password, 0, substr(hash('sha256', 'deploy'), 0, 16));

        $yaml = Yaml::parse($data);

        $class = new self();

        $class->projectName = $yaml['name'];
        $class->projectRepo = $yaml['repo'] ?? null;
        $class->servers = collect($yaml['servers']);
        $class->commands = collect($yaml['commands']);

        return $class;
    }

    public static function validatePassword(string $password): bool
    {
        $data = openssl_decrypt(file_get_contents(getcwd().'/deploy.yaml.env'), 'aes-256-cbc', $password, 0, substr(hash('sha256', 'deploy'), 0, 16));

        return ! ($data == false);
    }
}
