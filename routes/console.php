<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

Schedule::command('backup:run --only-db')->dailyAt('02:00')->onOneServer();
Schedule::command('backup:clean')->weeklyOn(0, '03:00')->onOneServer();
Schedule::command('backup:monitor')->dailyAt('04:00')->onOneServer();
