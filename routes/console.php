<?php

use Illuminate\Foundation\Console\ClosureCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    /** @var ClosureCommand $this */
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('metodocal:suspend-overdue')->dailyAt('02:00');
Schedule::command('notifications:process --limit=50')->everyMinute()->withoutOverlapping();

// Vira o mês sozinho: cria a fatura recorrente e a despesa fixa da competência
// corrente. Idempotente — rodar mais de uma vez no dia não duplica nada.
Schedule::command('metodocal:gerar-recorrencias')->dailyAt('03:00');
