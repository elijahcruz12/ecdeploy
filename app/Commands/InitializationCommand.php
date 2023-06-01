<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\File;
use LaravelZero\Framework\Commands\Command;

class InitializationCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'init';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Initializes ECDeploy in the current project.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $checkForJsonFile = File::exists('deploy.json');

        if($checkForJsonFile){
            $this->info('Deployment File already exists.');
            $makeNewOne = $this->confirm('Would you like to recreate file?');
            if($makeNewOne){
                File::delete('deploy.json');
                $this->info('Removed deploy.json');
            }
            else{
                $this->info('Exiting');
                return Command::SUCCESS;
            }
        }

        // Get current folder name
        $folderName = basename(getcwd());

        // Check if the project is a git repo, and get origin if it is.
        if(is_dir(getcwd() . '/.git')){
            $origin = shell_exec('git config --get remote.origin.url');
            $origin = trim($origin);
        }
        else{
            $origin = null;
        }


        $projectName = $this->ask('Project Name', $folderName);

        $projectRepo = $this->ask('Project Git Repo URL', $origin);

        $array = [
            'name' => $projectName,
            'repo' => $projectRepo,
            'servers' => [
                [
                    'name' => 'Server 1',
                    'host' => '',
                    'user' => '',
                    'port' => 22,
                    'tags' => [
                        'production'
                    ],
                    'path' => '~/' . $projectName,
                    'commands' => ['before', 'during', 'after']
                ],
                [
                    'name' => 'Server 2',
                    'host' => '',
                    'user' => '',
                    'port' => 22,
                    'tags' => [
                        'production'
                    ],
                    'path' => '~/' . $projectName,
                    'commands' => ['before', 'during', 'after']
                ]
            ],
            'commands' => [
                'before' => [],
                'during' => [],
                'after' => [],
                'extra' => []
            ]

        ];

        $json = json_encode($array, JSON_PRETTY_PRINT);

        // Remove \/ and replace with /
        $json = str_replace('\/', '/', $json);

        File::put('deploy.json', $json);

        $this->info('deploy.json created successfully.');

        return Command::SUCCESS;
    }

    /**
     * Define the command's schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
