<?php

namespace App\Commands;

use App\Parse\JsonDeployment;
use App\Parse\YamlDeployment;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class ValidateCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'validate';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Validated the contents of the deploy file.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if(JsonDeployment::exists()){
            $deploy = JsonDeployment::load();
        }
        elseif(YamlDeployment::exists()){
            $deploy = YamlDeployment::load();
        }
        else{
            $this->error('No deploy file found. Please run `init` to create one.');
            return Command::FAILURE;
        }

        $this->info('Validating deploy file...');

        $errors = $deploy->validate();

        if($errors->count() > 0){
            $this->error('The following errors were found:');
            $errors->each(function($error){
                $this->error($error);
            });
            return Command::FAILURE;
        }

        $this->info('No errors found.');
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
