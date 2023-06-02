<?php

namespace App\Interfaces\Deployments;

interface DeploymentInterface
{
    public static function load(): static;

    public function serversByTags(array $tags): static;

    public function serversByName(array $names): static;

    public function getCommandsForServer(array $server): array;

    public static function exists(): bool;

    public static function encryptedFileExists(): bool;

    public static function loadEncryptedFile(string $password): static;

    public static function validatePassword(string $password): bool;
}
