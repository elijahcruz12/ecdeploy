<?php

namespace App\Commands;

use App\Deployment\DeployGenerator;
use App\Parse\JsonDeployment;
use App\Parse\YamlDeployment;
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
    protected $signature = 'init
    {--laravel : Created a laravel based deploy.json}
    {--gitignore : Add deploy.json to .gitignore}
    {--format=json : What format you want the deploy file to be in. Options: json, yaml, php}';

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

        if (JsonDeployment::exists() || YamlDeployment::exists()) {
            $this->info('Deployment File already exists.');
            $makeNewOne = $this->confirm('Would you like to recreate file?');
            if ($makeNewOne) {
                File::delete('deploy.json');
                File::delete('deploy.yaml');
                $this->info('Removed Deployment File');
            } else {
                $this->info('Exiting');

                return Command::SUCCESS;
            }
        }

        // Get current folder name
        $folderName = basename(getcwd());

        // Check if the project is a git repo, and get origin if it is.
        if (is_dir(getcwd().'/.git')) {
            $origin = shell_exec('git config --get remote.origin.url');
            $origin = trim($origin);
        } else {
            $origin = null;
        }

        $projectName = $this->ask('Project Name', $folderName);

        $projectRepo = $this->ask('Project Git Repo URL', $origin);

        $generator = new DeployGenerator($projectName, $projectRepo);

        $generator->defaultServers();

        if ($this->option('laravel')) {
            $generator->laravelCommands();
        } else {
            $generator->defaultCommands();
        }

        if ($this->option('format') == 'yaml') {
            $yaml = $generator->toYaml();

            File::put('deploy.yaml', $yaml);

            $this->info('deploy.yaml created successfully.');
        } elseif ($this->option('format') == 'json') {
            $json = $generator->toJson();

            // Remove \/ and replace with /
            $json = str_replace('\/', '/', $json);

            File::put('deploy.json', $json);

            $this->info('deploy.json created successfully.');
        } elseif ($this->option('format') == 'php') {
            $php = $generator->toFileArray();

            $output = "<?php\n\nreturn ".$php.";\n";

            File::put('deploy.php', $php);

            $this->info('deploy.php created successfully.');
        } else {
            $this->error('Invalid format. Please use json or yaml');

            return Command::FAILURE;
        }

        if ($this->option('gitignore')) {
            $this->info('Adding deploy.json to .gitignore');
            File::append('.gitignore', 'deploy.json');
            $this->info('Adding deploy.yaml to .gitignore');
            File::append('.gitignore', 'deploy.yaml');
        }

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
