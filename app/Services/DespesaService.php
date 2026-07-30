<?php

namespace App\Services;

use App\Models\Despesa;
use App\Models\DespesaOcorrencia;
use Carbon\Carbon;

/**
 * Gera ocorrências mensais para despesas fixas.
 *
 * "Fixa" produz uma linha em despesa_ocorrencias por mês entre data_inicio e hoje
 * (ou até encerrada_em, o que vier primeiro). Chamada quando: a despesa é criada,
 * quando o admin abre a página de despesas, ou quando um cron periódico dispara.
 */
class DespesaService
{
    /**
     * Cria as ocorrências iniciais de uma despesa recém-cadastrada.
     * Para "unica" cria 1; para "fixa" cria todas até o mês corrente.
     */
    public function criarOcorrenciasIniciais(Despesa $d): int
    {
        if ($d->tipo === Despesa::TIPO_UNICA) {
            return $this->criarOcorrencia($d, $d->data_inicio->copy()->startOfMonth(), $d->data_inicio) ? 1 : 0;
        }

        return $this->gerarOcorrenciasFaltantes($d);
    }

    /**
     * Gera ocorrências mensais que ainda não existem, do mês da data_inicio
     * até o mês corrente (ou até $ate). Respeita encerrada_em.
     */
    public function gerarOcorrenciasFaltantes(Despesa $d, ?Carbon $ate = null): int
    {
        if (! $d->isFixa()) return 0;

        $inicio = $d->data_inicio->copy()->startOfMonth();
        $fim = ($ate ?? now())->copy()->startOfMonth();

        // Se a despesa foi encerrada, não gera além do mês do encerramento
        if ($d->encerrada_em) {
            $fimEncerramento = $d->encerrada_em->copy()->startOfMonth();
            if ($fimEncerramento->lt($fim)) $fim = $fimEncerramento;
        }

        if ($inicio->gt($fim)) return 0;

        $cursor = $inicio->copy();
        $criadas = 0;
        while ($cursor->lte($fim)) {
            if ($this->criarOcorrencia($d, $cursor->copy(), $this->vencimentoDoMes($d, $cursor))) {
                $criadas++;
            }
            $cursor->addMonthNoOverflow();
        }
        return $criadas;
    }

    /**
     * Roda para todas as despesas fixas ativas — garante que a ocorrência do
     * mês corrente exista. Chamada pelo controller ao abrir a listagem.
     */
    public function garantirOcorrenciasAtuais(): int
    {
        $total = 0;
        Despesa::where('tipo', Despesa::TIPO_FIXA)
            ->whereNull('encerrada_em')
            ->chunk(100, function ($chunk) use (&$total) {
                foreach ($chunk as $d) {
                    $total += $this->gerarOcorrenciasFaltantes($d);
                }
            });
        return $total;
    }

    private function vencimentoDoMes(Despesa $d, Carbon $competencia): Carbon
    {
        $dia = (int) ($d->dia_vencimento ?? $d->data_inicio->day);
        $ultimoDiaMes = (int) $competencia->copy()->endOfMonth()->day;
        $dia = min($dia, $ultimoDiaMes);
        return $competencia->copy()->day($dia);
    }

    /**
     * Idempotente: se já existe ocorrência dessa competência, retorna false.
     */
    private function criarOcorrencia(Despesa $d, Carbon $competencia, Carbon $vencimento): bool
    {
        $existe = DespesaOcorrencia::where('despesa_id', $d->id)
            ->whereDate('competencia', $competencia->toDateString())
            ->exists();
        if ($existe) return false;

        DespesaOcorrencia::create([
            'despesa_id' => $d->id,
            'competencia' => $competencia->toDateString(),
            'vencimento' => $vencimento->toDateString(),
            'valor' => $d->valor,
            'status' => 'pendente',
        ]);
        return true;
    }
}
