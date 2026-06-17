<?php

namespace App\Console\Commands;

use App\Services\NotificationQueueService;
use Illuminate\Console\Command;

class ProcessNotifications extends Command
{
    protected $signature = 'notifications:process {--limit=20 : Quantas processar por execução}';

    protected $description = 'Processa a fila de notificações (e-mail, WhatsApp, etc) em sequência.';

    public function handle(NotificationQueueService $service): int
    {
        $limit = (int) $this->option('limit');
        [$enviadas, $falhadas] = $service->processPending($limit);
        $this->info("Notificações processadas — enviadas: {$enviadas} | falhadas: {$falhadas}");
        return self::SUCCESS;
    }
}
