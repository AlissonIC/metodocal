<?php

namespace App\Console\Commands;

use App\Services\CobrancaRecorrenteService;
use App\Services\DespesaService;
use Illuminate\Console\Command;

/**
 * Materializa o que é recorrente no mês corrente.
 *
 * Sem isto a geração só acontecia quando alguém abria a tela de despesas — ou
 * seja, um mês em que ninguém entrasse no sistema ficava com buraco nos
 * relatórios. Rodando todo dia, o mês vira sozinho e é idempotente: se já
 * existe a competência, nada é criado em duplicidade.
 */
class GerarRecorrencias extends Command
{
    protected $signature = 'metodocal:gerar-recorrencias';

    protected $description = 'Gera as faturas recorrentes e as despesas fixas do mês corrente';

    public function handle(CobrancaRecorrenteService $cobrancas, DespesaService $despesas): int
    {
        $faturas = $cobrancas->garantirCobrancasAtuais();
        $this->info("Faturas recorrentes geradas: {$faturas}");

        $ocorrencias = $despesas->garantirOcorrenciasAtuais();
        $this->info("Ocorrências de despesa geradas: {$ocorrencias}");

        return self::SUCCESS;
    }
}
