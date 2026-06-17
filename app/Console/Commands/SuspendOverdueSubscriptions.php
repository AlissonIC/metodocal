<?php

namespace App\Console\Commands;

use App\Models\Fatura;
use App\Models\Subscription;
use Illuminate\Console\Command;

class SuspendOverdueSubscriptions extends Command
{
    protected $signature = 'metodocal:suspend-overdue';

    protected $description = 'Marca faturas vencidas e pendentes como atrasadas; suspende assinaturas atrasadas há mais de 7 dias.';

    public function handle(): int
    {
        $hoje = now()->startOfDay();

        $marcadasAtraso = Fatura::where('status', 'pendente')
            ->where('vencimento', '<', $hoje)
            ->update(['status' => 'atrasada']);
        $this->info("Faturas marcadas como atrasadas: {$marcadasAtraso}");

        // Suspende subscriptions com fatura atrasada há mais de 7 dias e sem nenhuma
        // fatura paga posterior.
        $limite = now()->subDays(7)->startOfDay();
        $count = 0;
        Subscription::where('status', 'ativa')->get()->each(function (Subscription $s) use (&$count, $limite) {
            $temFaturaAtrasada = Fatura::where('subscription_id', $s->id)
                ->where('status', 'atrasada')
                ->where('vencimento', '<', $limite)
                ->exists();
            $temFaturaPagaRecente = Fatura::where('subscription_id', $s->id)
                ->where('status', 'paga')
                ->where('pago_em', '>=', $limite)
                ->exists();

            if ($temFaturaAtrasada && ! $temFaturaPagaRecente) {
                $s->update(['status' => 'suspensa']);
                $count++;
            }
        });
        $this->info("Assinaturas suspensas por inadimplência (>7 dias): {$count}");

        return self::SUCCESS;
    }
}
