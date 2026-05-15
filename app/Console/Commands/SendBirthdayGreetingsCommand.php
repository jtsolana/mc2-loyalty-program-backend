<?php

namespace App\Console\Commands;

use App\Jobs\SendBirthdayGreetingsJob;
use Illuminate\Console\Command;

class SendBirthdayGreetingsCommand extends Command
{
    protected $signature = 'customers:birthday-greetings';

    protected $description = 'Dispatch a job that sends birthday greetings to qualifying customers.';

    public function handle(): int
    {
        SendBirthdayGreetingsJob::dispatch()->onQueue('loyverse');

        $this->info('Birthday greetings job dispatched.');

        return self::SUCCESS;
    }
}
