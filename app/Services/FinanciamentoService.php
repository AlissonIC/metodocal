<?php

namespace App\Services;

use App\Models\ParcelaFinanciamento;
use App\Models\Processo;
use Illuminate\Support\Facades\DB;

/**
 * Gera e mantém o carnê de parcelas de um processo.
 *
 * A regra que governa tudo aqui: histórico de pagamento é sagrado. Editar o
 * financiamento depois só mexe nas parcelas ainda PENDENTES — as pagas e as
 * canceladas ficam exatamente como estão, inclusive valor e vencimento.
 */
class FinanciamentoService
{
    /**
     * Sincroniza as parcelas com os dados de financiamento do processo.
     *
     * @return array{criadas:int, atualizadas:int, removidas:int, preservadas:int}
     */
    public function sincronizar(Processo $processo): array
    {
        $resumo = ['criadas' => 0, 'atualizadas' => 0, 'removidas' => 0, 'preservadas' => 0];

        // Sem os três dados essenciais não há carnê a gerar. Não apaga o que existe:
        // se o admin limpou um campo por engano, o histórico continua lá.
        if (! $processo->data_primeira_parcela || ! $processo->qtd_parcelas || $processo->valor_parcela === null) {
            return $resumo;
        }

        $qtd = (int) $processo->qtd_parcelas;
        $valor = (float) $processo->valor_parcela;
        $primeira = $processo->data_primeira_parcela->copy();

        DB::transaction(function () use ($processo, $qtd, $valor, $primeira, &$resumo) {
            $existentes = ParcelaFinanciamento::where('processo_id', $processo->id)
                ->get()
                ->keyBy('numero');

            for ($n = 1; $n <= $qtd; $n++) {
                // addMonthsNoOverflow: dia 31 em mês de 30 cai no dia 30, não vira o dia 1 do mês seguinte.
                $vencimento = $primeira->copy()->addMonthsNoOverflow($n - 1);
                $parcela = $existentes->get($n);

                if (! $parcela) {
                    ParcelaFinanciamento::create([
                        'processo_id' => $processo->id,
                        'numero' => $n,
                        'vencimento' => $vencimento->toDateString(),
                        'valor' => $valor,
                        'status' => 'pendente',
                    ]);
                    $resumo['criadas']++;
                    continue;
                }

                if ($parcela->status !== 'pendente') {
                    $resumo['preservadas']++;
                    continue;
                }

                $mudou = $parcela->vencimento->toDateString() !== $vencimento->toDateString()
                    || (float) $parcela->valor !== $valor;

                if ($mudou) {
                    $parcela->update([
                        'vencimento' => $vencimento->toDateString(),
                        'valor' => $valor,
                    ]);
                    $resumo['atualizadas']++;
                }
            }

            // Reduziu o número de parcelas: descarta só as sobras ainda pendentes.
            $sobras = ParcelaFinanciamento::where('processo_id', $processo->id)
                ->where('numero', '>', $qtd)
                ->get();

            foreach ($sobras as $sobra) {
                if ($sobra->status === 'pendente') {
                    $sobra->delete();
                    $resumo['removidas']++;
                } else {
                    $resumo['preservadas']++;
                }
            }
        });

        return $resumo;
    }

    /** Frase curta para o log de atividades do processo. Null quando nada mudou. */
    public function descreverResumo(array $r): ?string
    {
        $partes = [];
        if ($r['criadas']) $partes[] = $r['criadas'] . ' criada(s)';
        if ($r['atualizadas']) $partes[] = $r['atualizadas'] . ' atualizada(s)';
        if ($r['removidas']) $partes[] = $r['removidas'] . ' removida(s)';
        if ($r['preservadas']) $partes[] = $r['preservadas'] . ' preservada(s) por já estarem pagas/canceladas';

        return $partes ? 'Parcelas do financiamento: ' . implode(', ', $partes) . '.' : null;
    }
}
