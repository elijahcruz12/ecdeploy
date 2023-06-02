<?php

namespace App\Commands;

use App\Parse\JsonDeployment;
use App\Parse\YamlDeployment;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\File;
use LaravelZero\Framework\Commands\Command;

class DecryptCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'decrypt
        {--password= : The password to decrypt the file with.}
        {--remove : Remove the encrypted file.}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (JsonDeployment::encryptedFileExists()) {
            $file = getcwd().'/deploy.json';
            $encFile = getcwd().'/deploy.json.enc';
        } elseif (YamlDeployment::encryptedFileExists()) {
            $file = getcwd().'/deploy.yaml';
            $encFile = getcwd().'/deploy.yaml.enc';
        } else {
            $this->error('No deployment file found.');

            return Command::FAILURE;
        }

        $password = $this->option('password') ?? $this->secret('Enter a password to encrypt the file with.');

        $data = openssl_decrypt(file_get_contents($encFile), 'aes-256-cbc', $password, 0, substr(hash('sha256', 'deploy'), 0, 16));

        if ($data === false) {
            $this->error('Incorrect password.');

            return Command::FAILURE;
        }

        File::put($file, $data);

        if ($this->option('remove')) {
            File::delete($encFile);
        }

        $this->info('File decrypted.');

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
