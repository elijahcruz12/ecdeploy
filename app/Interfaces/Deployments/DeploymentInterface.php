<?php

namespace App\Interfaces\Deployments;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

interface DeploymentInterface
{
    public static function load(): static;
    public function serversByTags(array $tags): static;

    public function serversByName(array $names): static;

    public function getCommandsForServer(array $server): array;

    public static function exists(): bool;
}
