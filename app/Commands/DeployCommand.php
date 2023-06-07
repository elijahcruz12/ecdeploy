<?php

namespace App\Commands;

use App\Parse\JsonDeployment;
use App\Parse\PhpDeployment;
use App\Parse\YamlDeployment;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
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
        $tags = $this->option('tags');
        $all = $this->option('all');

        // We'll assume json file is in the root of the project
        if (JsonDeployment::exists()) {
            $deploy = JsonDeployment::load();
        } elseif (YamlDeployment::exists()) {
            $deploy = YamlDeployment::load();
        } elseif (JsonDeployment::encryptedFileExists()) {
            $password = $this->secret('Enter the password to decrypt the file.');

            if (JsonDeployment::validatePassword($password) === false) {
                $this->error('Incorrect password.');

                return Command::FAILURE;
            }

            $deploy = JsonDeployment::loadEncryptedFile($password);
        } elseif (YamlDeployment::encryptedFileExists()) {
            $password = $this->secret('Enter the password to decrypt the file.');

            if (YamlDeployment::validatePassword($password) === false) {
                $this->error('Incorrect password.');

                return Command::FAILURE;
            }

            $deploy = YamlDeployment::loadEncryptedFile($password);

        } elseif (PhpDeployment::exists()) {
            $deploy = PhpDeployment::load();
        } elseif (PhpDeployment::encryptedFileExists()) {
            $password = $this->secret('Enter the password to decrypt the file.');

            if (PhpDeployment::validatePassword($password) === false) {
                $this->error('Incorrect password.');

                return Command::FAILURE;
            }

            $deploy = PhpDeployment::loadEncryptedFile($password);
        } else {
            $this->error('No deploy file found. Please run `init` to create one.');

            return Command::FAILURE;
        }

        if ($tags) {
            // Get all the tags as an array, split by comma
            $deploy->serversByTags(explode(',', $tags));
        }

        if (! $tags && ! $all) {
            $serverChoice = $this->choice('Which servers would you like to deploy to?',
                $deploy->servers->pluck('name')->toArray(),
                0,
                null,
                true);

            $deploy->serversByName($serverChoice);
        }

        if (! $deploy->isTriggered) {
            $deploy->servers->each(function ($server) use ($deploy) {

                // Get the private key from ~/.ssh/config
                // Because we run the command as the user, we need to get the private key from the config

                $this->info('Deploying to '.$server['name']);

                $process = Ssh::create($server['user'], $server['host'], $server['port'] ?? 22)
                    ->usePrivateKey(getenv('HOME').'/.ssh/id_rsa')
                    ->disablePasswordAuthentication()
                    ->onOutput(function ($type, $line) {
                        $this->output->write($line);
                    })
                    ->execute($deploy->getCommandsForServer($server));

                if ($process->isSuccessful()) {
                    $this->info('Deployed to '.$server['name']);
                } else {
                    $this->error('Failed to deploy to '.$server['name']);
                }

            });
        } else {

            $deploy->servers->each(function ($server) {
                $url = $server['trigger']['url'];
                $method = $server['trigger']['method'];
                $headers = $server['trigger']['headers'] ?? null;

                $this->info('Deploying to '.$server['name']);

                if ($url == '' || $url == null) {
                    $this->error('Failed to deploy to '.$server['name']);
                    $this->error('No trigger url found.');

                    return;
                }

                if ($method == '' || $method == null) {
                    $this->error('Failed to deploy to '.$server['name']);
                    $this->error('No trigger method found.');

                    return;
                }

                try {
                    $process = Http::withHeaders($headers)
                        ->$method($url);
                } catch (\Exception $e) {
                    $this->error('Failed to deploy to '.$server['name']);
                    if ($this->getOutput()->getVerbosity() == 64) {
                        $this->error($e->getMessage());
                    }

                    return;
                }

                if ($process->successful()) {
                    $this->info('Sent deploy trigger for '.$server['name']);
                } else {
                    $this->error('Failed to deploy to '.$server['name']);
                    if ($this->getOutput()->getVerbosity() == 64) {
                        $this->error($process->body());
                    }
                }
            });
        }

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
