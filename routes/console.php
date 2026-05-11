<?php

use App\Console\Commands\AwardBirthdayPointsCommand;
use App\Console\Commands\ExpireRewardsCommand;
use App\Console\Commands\PublishScheduledPromotionsCommand;
use Illuminate\Support\Facades\Schedule;

Schedule::command(ExpireRewardsCommand::class)->daily();
Schedule::command(PublishScheduledPromotionsCommand::class)->everyMinute();
// Schedule::command(AwardBirthdayPointsCommand::class)->dailyAt('00:05');
