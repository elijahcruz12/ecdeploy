<?php

namespace App\Commands;

use App\Parse\JsonDeployment;
use App\Parse\YamlDeployment;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\File;
use LaravelZero\Framework\Commands\Command;

class EncryptCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'encrypt
        {--password= : The password to encrypt the file with.}
        {--remove : Remove the unencrypted file.}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Encypts the deployment file.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if(JsonDeployment::exists()){
            $file = getcwd().'/deploy.json';
            $encFile = getcwd().'/deploy.json.enc';
        }
        elseif(YamlDeployment::exists()){
            $file = getcwd().'/deploy.yaml';
            $encFile = getcwd().'/deploy.yaml.enc';
        }
        else{
            $this->error('No deployment file found.');
            return Command::FAILURE;
        }

        $password = $this->option('password') ?? $this->secret('Enter a password to encrypt the file with.');

        if(!$this->option('password')){
            $confirm = $this->secret('Confirm the password.');

            if($password !== $confirm){
                $this->error('Passwords do not match.');
                return Command::FAILURE;
            }
        }

        $encrypted = openssl_encrypt(File::get($file), 'aes-256-cbc', $password, 0, substr(hash('sha256', 'deploy'), 0, 16));

        File::put($encFile, $encrypted);

        if($this->option('remove')){
            File::delete($file);
        }

        $this->info('File encrypted.');

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
