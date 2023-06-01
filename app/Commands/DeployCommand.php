<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use LaravelZero\Framework\Commands\Command;
use Spatie\Ssh\Ssh;

class DeployCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'deploy
        {--tags= : Tags to deploy}
        {--all : Deploy all servers}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Deploys the application to the server.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $tags = $this->option('tags', false);
        $all = $this->option('all');

        // Check to see if the deploy.json exists
        if (! File::exists(getcwd().'/deploy.json')) {
            $this->error('deploy.json does not exist.');

            return Command::FAILURE;
        }

        // Get the contents of the deploy.json
        $deploy = json_decode(File::get(getcwd().'/deploy.json'), true);

        // Check to see if the servers key exists
        if (! array_key_exists('servers', $deploy)) {
            $this->error('servers key does not exist in deploy.json.');

            return Command::FAILURE;
        }

        // Check to see if the servers key is an array
        if (! is_array($deploy['servers'])) {
            $this->error('servers key is not an array in deploy.json.');

            return Command::FAILURE;
        }

        // Check to see if the servers array is empty
        if (empty($deploy['servers'])) {
            $this->error('servers array is empty in deploy.json.');

            return Command::FAILURE;
        }

        // Check to see if the commands key exists
        if (! array_key_exists('commands', $deploy)) {
            $this->error('commands key does not exist in deploy.json.');

            return Command::FAILURE;
        }

        // Check to see if the commands key is an array
        if (! is_array($deploy['commands'])) {
            $this->error('commands key is not an array in deploy.json.');

            return Command::FAILURE;
        }

        // Get the servers
        $serversArr = $deploy['servers'];
        // Get the commands
        $commandsArr = $deploy['commands'];

        $servers = new Collection($serversArr);
        $commands = new Collection($commandsArr);

        if ($tags) {
            // Get all servers that have the tags
            $servers = $servers->filter(function ($server) use ($tags) {
                return in_array($tags, $server['tags']);
            });
        }

        if (! $tags && ! $all) {
            $serverChoice = $this->choice('Which servers would you like to deploy to?',
                $servers->pluck('name')->toArray(),
                0,
                null,
                true);

            $servers = $servers->filter(function ($server) use ($serverChoice) {
                return in_array($server['name'], $serverChoice);
            });
        }

        $servers->each(function ($server) use ($commands) {
            // Get the commands for the server
            $serverCommandTags = $server['commands'];

            $serverCommand = [];

            // Get the commands for the server by its category
            foreach ($commands as $stage => $command) {
                if (in_array($stage, $serverCommandTags)) {
                    foreach ($command as $item) {
                        $serverCommands[] = $item;
                    }
                }
            }

            if (count($serverCommands) == 0) {
                $this->error('No commands found for '.$server['name']);

                return Command::FAILURE;
            } else {
                // Prepend the commands with the cd command
                array_unshift($serverCommands, 'cd '.$server['path']);
                array_unshift($serverCommands, 'mkdir -p '.$server['path']);
            }

            // Get the private key from ~/.ssh/config
            // Because we run the command as the user, we need to get the private key from the config

            $this->info('Deploying to '.$server['name']);

            $process = Ssh::create($server['user'], $server['host'], $server['port'] ?? 22)
                ->usePrivateKey(getenv('HOME').'/.ssh/id_rsa')
                ->disablePasswordAuthentication()
                ->onOutput(function ($type, $line) {
                    $this->line($line);
                })
                ->execute($serverCommands);

            if ($process->isSuccessful()) {
                $this->info('Deployed to '.$server['name']);
            } else {
                $this->error('Failed to deploy to '.$server['name']);

                return Command::FAILURE;
            }

        });

        $this->info('Deployed to all selected servers.');

        return Command::SUCCESS;
    }

    /**
     * Define the command's schedule.
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
