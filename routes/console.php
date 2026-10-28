<?php

use Illuminate\Support\Facades\Schedule;

// Hostinger cron (hPanel → Advanced → Cron Jobs), every minute (INSTALL-HOSTINGER.md, "Cron"):
//   /usr/bin/php /home/USER/domains/allinsontransport.co.uk/public_html/allinson-app/artisan schedule:run
// Times are Europe/London (APP_TIMEZONE).
Schedule::command('app:compliance-reminders')->dailyAt('07:00')->withoutOverlapping();
Schedule::command('app:prune-held-uploads')->hourly();
