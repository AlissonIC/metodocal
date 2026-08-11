<?php

namespace App\Http\Controllers\Concerns;

use Closure;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Exportação de listagens para planilha.
 *
 * As telas usam DataTables em modo server-side, então o botão de export nativo só
 * enxergaria a página atual. Aqui a mesma query dos filtros é percorrida inteira,
 * em lotes, e transmitida como CSV — sem paginação e sem carregar tudo em memória.
 *
 * Formato: separador ";" e BOM UTF-8, que é o que o Excel em pt-BR abre com dois
 * cliques sem passar pelo assistente de importação.
 */
trait ExportaPlanilha
{
    protected function exportarCsv(
        string $nomeBase,
        array $cabecalho,
        Builder $query,
        Closure $linha,
        int $tamanhoLote = 500,
    ): StreamedResponse {
        $arquivo = $nomeBase . '-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($cabecalho, $query, $linha, $tamanhoLote) {
            $saida = fopen('php://output', 'w');
            fwrite($saida, "\xEF\xBB\xBF");

            fputcsv($saida, $cabecalho, ';');

            $query->chunkById($tamanhoLote, function ($registros) use ($saida, $linha) {
                foreach ($registros as $registro) {
                    fputcsv($saida, array_map([$this, 'celulaCsv'], $linha($registro)), ';');
                }
                flush();
            });

            fclose($saida);
        }, $arquivo, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /** Dinheiro no padrão pt-BR, pronto para o Excel somar depois de reconhecer a coluna. */
    protected function dinheiroCsv(mixed $valor): string
    {
        return $valor === null ? '' : number_format((float) $valor, 2, ',', '.');
    }

    private function celulaCsv(mixed $valor): string
    {
        if ($valor === null || $valor === false) {
            return '';
        }
        if ($valor === true) {
            return 'Sim';
        }
        if ($valor instanceof DateTimeInterface) {
            return $valor->format('d/m/Y');
        }

        return (string) $valor;
    }
}
