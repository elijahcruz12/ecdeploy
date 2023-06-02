<?php

namespace App\Models;

use Illuminate\Support\Collection;

class Deployment
{
    public string $name;

    public string|null $repo = null;

    public Collection $servers;

    public Collection $commands;

    public function __construct($name, $repo, $servers, $commands)
    {
        $this->name = $name;
        $this->repo = $repo;
        $this->servers = $servers;
        $this->commands = $commands;
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
}
