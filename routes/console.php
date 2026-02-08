<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

// Run scheduled clonings every minute
Schedule::command('clonings:run-scheduled')->everyMinute();
