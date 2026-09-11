<?php

namespace App\Services;

use App\Models\CobrancaRecorrente;
use App\Models\Fatura;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Materializa cobranças recorrentes em faturas, um mês por vez.
 *
 * A regra central: enquanto `encerrada_em` for null, todo mês entre a data de
 * início e o mês corrente precisa existir como fatura. É isso que mantém a
 * cobrança viva nos dados e relatórios sem ninguém relançar nada a cada mês.
 *
 * Encerrar não apaga histórico: para de gerar dali para frente e cancela apenas
 * o que ainda estava pendente com vencimento futuro.
 */
class CobrancaRecorrenteService
{
    /**
     * Cria as faturas que faltam, da competência inicial até o mês corrente
     * (ou até o mês do encerramento, o que vier primeiro).
     */
    public function gerarFaturasFaltantes(CobrancaRecorrente $cobranca, ?Carbon $ate = null): int
    {
        $inicio = $cobranca->data_inicio->copy()->startOfMonth();
        $fim = ($ate ?? now())->copy()->startOfMonth();

        if ($cobranca->encerrada_em) {
            $fimEncerramento = $cobranca->encerrada_em->copy()->startOfMonth();
            if ($fimEncerramento->lt($fim)) {
                $fim = $fimEncerramento;
            }
        }

        if ($inicio->gt($fim)) {
            return 0;
        }

        $cobranca->loadMissing('processo.user:id,name,email,cpf_cnpj');

        $criadas = 0;
        $cursor = $inicio->copy();

        while ($cursor->lte($fim)) {
            if ($this->criarFaturaDaCompetencia($cobranca, $cursor->copy())) {
                $criadas++;
            }
            $cursor->addMonthNoOverflow();
        }

        return $criadas;
    }

    /** Roda para todas as recorrências ativas — usada pelo agendador diário. */
    public function garantirCobrancasAtuais(): int
    {
        $total = 0;

        CobrancaRecorrente::whereNull('encerrada_em')
            ->with('processo.user:id,name,email,cpf_cnpj')
            ->chunk(100, function ($chunk) use (&$total) {
                foreach ($chunk as $cobranca) {
                    $total += $this->gerarFaturasFaltantes($cobranca);
                }
            });

        return $total;
    }

    /**
     * Encerra a recorrência a partir de agora. As faturas já vencidas ou pagas
     * ficam intactas; só as pendentes de vencimento futuro são canceladas.
     */
    public function encerrar(CobrancaRecorrente $cobranca): int
    {
        return DB::transaction(function () use ($cobranca) {
            $cobranca->update(['encerrada_em' => now()]);

            return Fatura::where('cobranca_recorrente_id', $cobranca->id)
                ->where('status', 'pendente')
                ->where('vencimento', '>', now()->startOfDay())
                ->update(['status' => 'cancelada']);
        });
    }

    /** Reativa e já repõe os meses que ficaram para trás. */
    public function reabrir(CobrancaRecorrente $cobranca): int
    {
        $cobranca->update(['encerrada_em' => null]);

        return $this->gerarFaturasFaltantes($cobranca->refresh());
    }

    /** Idempotente: se a competência já tem fatura, não cria outra. */
    private function criarFaturaDaCompetencia(CobrancaRecorrente $cobranca, Carbon $competencia): bool
    {
        $existe = Fatura::where('cobranca_recorrente_id', $cobranca->id)
            ->whereDate('competencia', $competencia->toDateString())
            ->exists();

        if ($existe) {
            return false;
        }

        $cliente = $cobranca->processo?->user;

        Fatura::create([
            'cobranca_recorrente_id' => $cobranca->id,
            'processo_id' => $cobranca->processo_id,
            'user_id' => $cobranca->user_id,
            'competencia' => $competencia->toDateString(),
            'descricao' => $cobranca->descricao . ' (' . $competencia->format('m/Y') . ')',
            'valor' => $cobranca->valor,
            'vencimento' => $this->vencimentoDoMes($cobranca, $competencia)->toDateString(),
            'status' => 'pendente',
            'payer_name' => $cliente?->name,
            'payer_email' => $cliente?->email,
            'payer_document' => $cliente?->cpf_cnpj,
        ]);

        return true;
    }

    /** Dia 31 em mês de 30 vira o último dia do mês, não o dia 1 do seguinte. */
    private function vencimentoDoMes(CobrancaRecorrente $cobranca, Carbon $competencia): Carbon
    {
        $dia = (int) ($cobranca->dia_vencimento ?: $cobranca->data_inicio->day);
        $ultimoDia = (int) $competencia->copy()->endOfMonth()->day;

        return $competencia->copy()->day(min($dia, $ultimoDia));
    }
}
