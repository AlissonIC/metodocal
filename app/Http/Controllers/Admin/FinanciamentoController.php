<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ExportaPlanilha;
use App\Http\Controllers\Controller;
use App\Models\Banco;
use App\Models\HistoricoProcesso;
use App\Models\ParcelaFinanciamento;
use App\Models\Processo;
use App\Services\FinanciamentoService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Yajra\DataTables\Facades\DataTables;

/**
 * Controle interno das parcelas de financiamento dos processos.
 *
 * O carnê é gerado pelo FinanciamentoService quando o processo é salvo; aqui o
 * admin acompanha o que venceu, o que foi pago e o que ficou para trás.
 */
class FinanciamentoController extends Controller
{
    use ExportaPlanilha;

    public function __construct(private FinanciamentoService $service) {}

    public function index(Request $request)
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $hoje = now()->startOfDay();
        $pendentes = ParcelaFinanciamento::where('status', 'pendente');

        return view('content.admin.financiamentos.index', [
            'kpi_a_receber' => (float) (clone $pendentes)->sum('valor'),
            'kpi_atrasado' => (float) (clone $pendentes)->where('vencimento', '<', $hoje)->sum('valor'),
            'kpi_recebido' => (float) ParcelaFinanciamento::where('status', 'paga')->sum('valor'),
            'kpi_vence_mes' => (float) (clone $pendentes)
                ->whereBetween('vencimento', [now()->startOfMonth(), now()->endOfMonth()])
                ->sum('valor'),
            'qtd_atrasadas' => (clone $pendentes)->where('vencimento', '<', $hoje)->count(),
            'bancos' => Banco::orderBy('nome')->get(['id', 'nome']),
            'processoFiltrado' => $request->query('processo_id'),
        ]);
    }

    /** Filtros da listagem — compartilhados entre o DataTable e a exportação. */
    private function queryFiltrada(Request $request): Builder
    {
        $query = ParcelaFinanciamento::query()
            ->with([
                'processo:id,nome_completo,user_id,banco_id,status,qtd_parcelas',
                'processo.user:id,name',
                'processo.banco:id,nome',
                'statusAlteradoPor:id,name',
            ]);

        if ($status = $request->query('status')) {
            if ($status === 'atrasada') {
                $query->where('status', 'pendente')->where('vencimento', '<', now()->startOfDay());
            } elseif ($status === 'pendente') {
                // "Pendente" na tela significa em aberto e ainda no prazo — o que já
                // venceu aparece sob "Atrasada", senão o mesmo registro cairia nos dois.
                $query->where('status', 'pendente')->where('vencimento', '>=', now()->startOfDay());
            } else {
                $query->where('status', $status);
            }
        }

        if ($processoId = $request->query('processo_id')) {
            $query->where('processo_id', $processoId);
        }

        if ($bancoId = $request->query('banco_id')) {
            $query->whereHas('processo', fn ($p) => $p->where('banco_id', $bancoId));
        }

        if ($de = $request->query('vencimento_de')) {
            $query->whereDate('vencimento', '>=', $de);
        }
        if ($ate = $request->query('vencimento_ate')) {
            $query->whereDate('vencimento', '<=', $ate);
        }

        if ($b = trim((string) $request->query('busca', ''))) {
            $query->whereHas('processo', function ($p) use ($b) {
                $p->where('nome_completo', 'like', "%$b%")
                    ->orWhere('id', $b)
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%$b%"));
            });
        }

        return $query;
    }

    public function datatable(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $query = $this->queryFiltrada($request)->orderBy('vencimento')->orderBy('numero');

        return DataTables::eloquent($query)
            ->addColumn('processo_label', fn (ParcelaFinanciamento $p) => $p->processo
                ? '#' . $p->processo->id . ' · ' . Str::limit($p->processo->nome_completo, 34)
                : '—')
            ->addColumn('cliente_nome', fn (ParcelaFinanciamento $p) => $p->processo?->user?->name)
            ->addColumn('banco_nome', fn (ParcelaFinanciamento $p) => $p->processo?->banco?->nome)
            ->addColumn('processo_url', fn (ParcelaFinanciamento $p) => $p->processo
                ? route('processos.show', $p->processo_id)
                : null)
            ->addColumn('parcela_label', fn (ParcelaFinanciamento $p) => $p->numero . '/' . ($p->processo?->qtd_parcelas ?: '?'))
            ->addColumn('vencimento_fmt', fn (ParcelaFinanciamento $p) => $p->vencimento->format('d/m/Y'))
            ->addColumn('valor_fmt', fn (ParcelaFinanciamento $p) => 'R$ ' . number_format((float) $p->valor, 2, ',', '.'))
            ->addColumn('status_badge', fn (ParcelaFinanciamento $p) =>
                '<span class="badge bg-label-' . $p->statusColor() . '">' . e($p->statusLabel()) . '</span>')
            ->addColumn('status_atual', fn (ParcelaFinanciamento $p) => $p->isAtrasada() ? 'atrasada' : $p->status)
            ->addColumn('dias_atraso', fn (ParcelaFinanciamento $p) => $p->diasAtraso())
            ->addColumn('pago_em_fmt', fn (ParcelaFinanciamento $p) => $p->pago_em?->format('d/m/Y'))
            ->addColumn('alterado_por_nome', fn (ParcelaFinanciamento $p) => $p->statusAlteradoPor?->name)
            ->addColumn('alterado_por_url', fn (ParcelaFinanciamento $p) => $p->status_alterado_por_user_id
                ? route('admin.users.edit', $p->status_alterado_por_user_id)
                : null)
            ->addColumn('alterado_em_fmt', fn (ParcelaFinanciamento $p) => $p->status_alterado_em?->format('d/m/Y H:i'))
            ->rawColumns(['status_badge'])
            ->toJson();
    }

    public function export(Request $request): StreamedResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        return $this->exportarCsv(
            'financiamentos-parcelas',
            ['Processo', 'Cliente', 'Banco', 'Parcela', 'Vencimento', 'Valor (R$)', 'Status', 'Dias em atraso', 'Pago em', 'Método', 'Status alterado por', 'Alterado em'],
            $this->queryFiltrada($request)->orderBy('vencimento'),
            fn (ParcelaFinanciamento $p) => [
                $p->processo ? '#' . $p->processo->id . ' · ' . $p->processo->nome_completo : null,
                $p->processo?->user?->name,
                $p->processo?->banco?->nome,
                $p->numero . '/' . ($p->processo?->qtd_parcelas ?: '?'),
                $p->vencimento,
                $this->dinheiroCsv($p->valor),
                $p->statusLabel(),
                $p->diasAtraso() ?: null,
                $p->pago_em?->format('d/m/Y H:i'),
                $p->metodo,
                $p->statusAlteradoPor?->name,
                $p->status_alterado_em?->format('d/m/Y H:i'),
            ],
        );
    }

    public function marcarPaga(Request $request, ParcelaFinanciamento $parcela): JsonResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $data = $request->validate([
            'metodo' => ['nullable', 'string', 'max:40'],
            'pago_em' => ['nullable', 'date'],
        ]);

        $parcela->update($this->carimbarAutor([
            'status' => 'paga',
            'pago_em' => $data['pago_em'] ?? now(),
            'metodo' => $data['metodo'] ?? 'manual',
        ]));

        $this->log($parcela, 'Parcela ' . $parcela->numero . ' marcada como paga (R$ '
            . number_format((float) $parcela->valor, 2, ',', '.') . ').');

        return response()->json(['message' => 'Parcela ' . $parcela->numero . ' marcada como paga.']);
    }

    public function marcarPendente(Request $request, ParcelaFinanciamento $parcela): JsonResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $parcela->update($this->carimbarAutor(['status' => 'pendente', 'pago_em' => null, 'metodo' => null]));

        $this->log($parcela, 'Parcela ' . $parcela->numero . ' voltou para pendente.');

        return response()->json(['message' => 'Parcela ' . $parcela->numero . ' voltou para pendente.']);
    }

    public function cancelar(Request $request, ParcelaFinanciamento $parcela): JsonResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $parcela->update($this->carimbarAutor(['status' => 'cancelada', 'pago_em' => null, 'metodo' => null]));

        $this->log($parcela, 'Parcela ' . $parcela->numero . ' cancelada.');

        return response()->json(['message' => 'Parcela ' . $parcela->numero . ' cancelada.']);
    }

    /** Regera o carnê de um processo sob demanda, sem passar pelo formulário. */
    public function regerar(Request $request, Processo $processo): JsonResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $resumo = $this->service->sincronizar($processo);
        $desc = $this->service->descreverResumo($resumo);

        if ($desc) {
            HistoricoProcesso::create([
                'processo_id' => $processo->id,
                'user_id' => $request->user()->id,
                'acao' => HistoricoProcesso::ACAO_UPDATED,
                'entidade' => 'parcela',
                'observacao' => $desc,
            ]);
        }

        return response()->json([
            'message' => $desc ?: 'Nada a gerar — confira banco, valor da parcela, quantidade e data da primeira parcela.',
            'resumo' => $resumo,
        ]);
    }

    /** Anexa autor e momento da mudança de status ao conjunto de campos a gravar. */
    private function carimbarAutor(array $campos): array
    {
        return $campos + [
            'status_alterado_por_user_id' => auth()->id(),
            'status_alterado_em' => now(),
        ];
    }

    private function log(ParcelaFinanciamento $parcela, string $descricao): void
    {
        HistoricoProcesso::create([
            'processo_id' => $parcela->processo_id,
            'user_id' => auth()->id(),
            'acao' => HistoricoProcesso::ACAO_UPDATED,
            'entidade' => 'parcela',
            'entidade_id' => $parcela->id,
            'observacao' => $descricao,
        ]);
    }
}
