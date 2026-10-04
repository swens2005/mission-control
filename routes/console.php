<?php

use Illuminate\Support\Facades\Schedule;

// Needs the cPanel cron job: docs/production-setup.md, step 4.
Schedule::command('sandbox:prune')->hourly();
