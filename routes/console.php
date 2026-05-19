<?php

use App\Console\Commands\SendBirthdayGreetingsCommand;
use App\Console\Commands\ExpireRewardsCommand;
use App\Console\Commands\PublishScheduledPromotionsCommand;
use Illuminate\Support\Facades\Schedule;

Schedule::command(ExpireRewardsCommand::class)->daily();
Schedule::command(PublishScheduledPromotionsCommand::class)->everyMinute();
Schedule::command(SendBirthdayGreetingsCommand::class)->dailyAt('06:00');
