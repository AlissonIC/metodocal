<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comissao;
use App\Models\DespesaOcorrencia;
use App\Models\Fatura;
use App\Models\PaymentEvent;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\FaturaService;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;
use Yajra\DataTables\Facades\DataTables;

class FinanceiroController extends Controller
{
    use \App\Http\Controllers\Concerns\ExportaPlanilha;

    public function __construct(private FaturaService $faturaService) {}

    public function index()
    {
        $hoje = now()->startOfDay();

        return view('content.admin.financeiro.index', [
            'kpi_recebido_mes' => (float) Fatura::where('status', 'paga')
                ->whereBetween('pago_em', [now()->startOfMonth(), now()->endOfMonth()])
                ->sum('valor'),
            'kpi_pendente' => (float) Fatura::where('status', 'pendente')->sum('valor'),
            'kpi_atrasado' => (float) Fatura::where('status', 'pendente')->where('vencimento', '<', $hoje)->sum('valor'),
            'kpi_estornado_mes' => (float) Fatura::where('status', 'estornada')
                ->whereBetween('estornada_em', [now()->startOfMonth(), now()->endOfMonth()])
                ->sum('valor'),
            'kpi_mrr' => (float) Subscription::where('status', 'ativa')
                ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
                ->where('plans.recorrencia', 'mensal')
                ->sum('plans.preco'),
            'qtd_assinaturas_ativas' => Subscription::where('status', 'ativa')->count(),
            'plans' => \App\Models\Plan::orderBy('nome')->get(['id', 'nome', 'tipo']),
        ]);
    }

    /** Filtros da listagem — compartilhados entre o DataTable e a exportação. */
    private function queryFiltrada(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        $query = Fatura::query()
            ->with(['user:id,name,email', 'plan:id,nome,tipo,preco,recorrencia'])
            ->latest('created_at');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($metodo = $request->query('metodo')) {
            $query->where('metodo', $metodo);
        }
        if ($planId = $request->query('plan_id')) {
            $query->where('plan_id', $planId);
        }
        if ($de = $request->query('vencimento_de')) {
            $query->whereDate('vencimento', '>=', $de);
        }
        if ($ate = $request->query('vencimento_ate')) {
            $query->whereDate('vencimento', '<=', $ate);
        }

        // Busca livre — só na exportação; no DataTable quem faz isso é o próprio yajra.
        if ($b = trim((string) $request->query('busca', ''))) {
            $query->where(function ($q) use ($b) {
                $q->where('gateway_payment_id', 'like', "%$b%")
                    ->orWhere('descricao', 'like', "%$b%")
                    ->orWhere('payer_name', 'like', "%$b%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%$b%")->orWhere('email', 'like', "%$b%"))
                    ->orWhereHas('plan', fn ($p) => $p->where('nome', 'like', "%$b%"));
            });
        }

        return $query;
    }

    public function export(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return $this->exportarCsv(
            'faturas-a-receber',
            ['ID', 'Usuário', 'E-mail', 'Plano', 'Valor (R$)', 'Status', 'Vencimento', 'Pago em', 'Método', 'Estornada em', 'ID no gateway', 'Criada em'],
            $this->queryFiltrada($request),
            fn (Fatura $f) => [
                $f->id,
                $f->user?->name,
                $f->user?->email,
                $f->plan?->nome,
                $this->dinheiroCsv($f->valor),
                $f->isAtrasada() ? 'Atrasada' : ucfirst($f->status),
                $f->vencimento,
                $f->pago_em?->format('d/m/Y H:i'),
                $f->metodo ? ucfirst($f->metodo) : null,
                $f->estornada_em?->format('d/m/Y H:i'),
                $f->gateway_payment_id,
                $f->created_at?->format('d/m/Y H:i'),
            ],
        );
    }

    public function datatable(Request $request): JsonResponse
    {
        return DataTables::eloquent($this->queryFiltrada($request))
            ->addColumn('user_name', fn (Fatura $f) => $f->user?->name ?? '—')
            ->addColumn('plan_nome', fn (Fatura $f) => $f->plan?->nome ?? '—')
            ->addColumn('valor_formatado', fn (Fatura $f) => 'R$ ' . number_format((float) $f->valor, 2, ',', '.'))
            ->addColumn('vencimento_formatado', fn (Fatura $f) => $f->vencimento->format('d/m/Y'))
            ->addColumn('status_badge', function (Fatura $f) {
                if ($f->isAtrasada()) {
                    return '<span class="badge bg-label-danger">Atrasada</span>';
                }
                $map = ['pendente' => 'warning', 'paga' => 'success', 'cancelada' => 'secondary', 'estornada' => 'info'];
                return '<span class="badge bg-label-' . ($map[$f->status] ?? 'secondary') . '">' . ucfirst($f->status) . '</span>';
            })
            ->addColumn('metodo_label', fn (Fatura $f) => $f->metodo ? ucfirst($f->metodo) : '<span class="text-muted">—</span>')
            ->addColumn('actions', fn (Fatura $f) => $f->id)
            ->addColumn('details', function (Fatura $f) {
                return [
                    'user_name'          => $f->user?->name,
                    'user_email'         => $f->user?->email,
                    'plan_nome'          => $f->plan?->nome,
                    'plan_tipo'          => $f->plan?->tipo,
                    'plan_recorrencia'   => $f->plan?->recorrencia,
                    'valor'              => (float) $f->valor,
                    'vencimento'         => $f->vencimento?->format('d/m/Y'),
                    'status'             => $f->status,
                    'is_atrasada'        => $f->isAtrasada(),
                    'pago_em'            => $f->pago_em?->format('d/m/Y H:i'),
                    'estornada_em'       => $f->estornada_em?->format('d/m/Y H:i'),
                    'metodo'             => $f->metodo,
                    'gateway_payment_id' => $f->gateway_payment_id,
                    'gateway_preference_id' => $f->gateway_preference_id,
                    'gateway_refund_id'  => $f->gateway_refund_id,
                    'payer_name'         => $f->payer_name,
                    'payer_email'        => $f->payer_email,
                    'payer_document'     => $f->payer_document,
                    'created_at'         => $f->created_at?->format('d/m/Y H:i'),
                ];
            })
            ->rawColumns(['status_badge', 'metodo_label'])
            ->toJson();
    }

    /**
     * Página de detalhes da fatura: dados + pagador + timeline de eventos MP + auditoria manual.
     */
    public function show(Fatura $fatura): View
    {
        [$eventos, $auditoria] = $this->loadTimelines($fatura);

        return view('content.admin.financeiro.show', [
            'fatura' => $fatura,
            'eventos' => $eventos,
            'auditoria' => $auditoria,
        ]);
    }

    /**
     * Devolve o HTML de cada partial renderizado, para atualização parcial via AJAX.
     */
    public function refresh(Fatura $fatura): JsonResponse
    {
        [$eventos, $auditoria] = $this->loadTimelines($fatura);

        $partial = fn (string $name, array $data) => view("content.admin.financeiro._partials.$name", $data)->render();

        return response()->json([
            'status' => $fatura->status,
            'header' => $partial('header', ['fatura' => $fatura]),
            'resumo' => $partial('resumo', ['fatura' => $fatura]),
            'pagador' => $partial('pagador', ['fatura' => $fatura]),
            'eventos' => $partial('eventos', ['eventos' => $eventos]),
            'auditoria' => $partial('auditoria', ['auditoria' => $auditoria]),
            'acoes' => $partial('acoes', ['fatura' => $fatura]),
        ]);
    }

    /** @return array{0: \Illuminate\Pagination\LengthAwarePaginator, 1: \Illuminate\Pagination\LengthAwarePaginator} */
    private function loadTimelines(Fatura $fatura): array
    {
        $fatura->load([
            'user:id,name,email',
            'plan:id,nome,tipo,preco,recorrencia',
            'subscription:id,user_id,plan_id,status,started_at,ends_at',
        ]);

        $eventos = PaymentEvent::where('fatura_id', $fatura->id)
            ->orderByDesc('created_at')
            ->paginate(5, ['*'], 'pageEv')
            ->withQueryString();

        $auditoria = Activity::where('subject_type', Fatura::class)
            ->where('subject_id', $fatura->id)
            ->orderByDesc('created_at')
            ->with('causer')
            ->paginate(5, ['*'], 'pageAud')
            ->withQueryString();

        return [$eventos, $auditoria];
    }

    public function marcarPaga(Fatura $fatura): JsonResponse
    {
        $result = $this->faturaService->mudarStatusManual($fatura, 'paga');
        return response()->json([
            'status' => $result['ok'] ? 'success' : 'error',
            'message' => $result['message'],
        ], $result['ok'] ? 200 : 422);
    }

    public function cancelar(Fatura $fatura): JsonResponse
    {
        $result = $this->faturaService->mudarStatusManual($fatura, 'cancelada');
        return response()->json([
            'status' => $result['ok'] ? 'success' : 'error',
            'message' => $result['message'],
        ], $result['ok'] ? 200 : 422);
    }

    /**
     * Estornar — chama o gateway (ou marca manualmente se sem credenciais).
     */
    public function estornar(Fatura $fatura): JsonResponse
    {
        $result = $this->faturaService->estornar($fatura);
        return response()->json([
            'status' => $result['ok'] ? 'success' : 'error',
            'message' => $result['message'],
            'refund_id' => $result['refund_id'] ?? null,
        ], $result['ok'] ? 200 : 422);
    }

    /**
     * Troca de status manual — admin escolhe um novo valor da enum.
     */
    public function mudarStatus(Request $request, Fatura $fatura): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:pendente,paga,atrasada,cancelada,estornada'],
            'motivo' => ['nullable', 'string', 'max:500'],
        ]);

        $result = $this->faturaService->mudarStatusManual($fatura, $data['status'], $data['motivo'] ?? null);
        return response()->json([
            'status' => $result['ok'] ? 'success' : 'error',
            'message' => $result['message'],
        ], $result['ok'] ? 200 : 422);
    }

    public function paymentEvents(): JsonResponse
    {
        return DataTables::eloquent(PaymentEvent::query())
            ->addColumn('processed_label', fn (PaymentEvent $e) => $e->processed_at?->format('d/m/Y H:i') ?? '—')
            ->addColumn('created_label', fn (PaymentEvent $e) => $e->created_at->format('d/m/Y H:i'))
            ->toJson();
    }

    /**
     * Tela de relatórios consolidados (faturas + comissões).
     * Carrega só a view; os dados vêm via AJAX em relatoriosData().
     */
    public function relatorios()
    {
        return view('content.admin.financeiro.relatorios', [
            'plans' => Plan::orderBy('nome')->get(['id', 'nome', 'tipo']),
        ]);
    }

    /**
     * Endpoint AJAX que retorna KPIs + séries dos gráficos para o período filtrado.
     */
    public function relatoriosData(Request $request): JsonResponse
    {
        $hoje = now()->startOfDay();

        // Período: default = mês corrente
        $de = $request->query('de') ? Carbon::parse($request->query('de'))->startOfDay() : now()->startOfMonth();
        $ate = $request->query('ate') ? Carbon::parse($request->query('ate'))->endOfDay() : now()->endOfMonth();

        // Despesa fixa é projetada para frente; se o período pedido passa do
        // horizonte já gerado, completa as competências antes de somar — senão
        // o relatório mostraria zero num mês que tem compromisso contratado.
        if ($ate->isFuture()) {
            app(\App\Services\DespesaService::class)->garantirOcorrenciasAtuais($ate->copy());
        }

        $statusFatura = $request->query('status_fatura');
        $statusComissao = $request->query('status_comissao');
        $tipoComissao = $request->query('tipo_comissao'); // a_receber | a_pagar
        $planId = $request->query('plan_id');

        // --------- Queries base ---------
        $faturasQuery = Fatura::query()
            ->whereBetween('created_at', [$de, $ate])
            ->when($statusFatura, fn ($q) => $q->where('status', $statusFatura))
            ->when($planId, fn ($q) => $q->where('plan_id', $planId));

        $comissoesQuery = Comissao::query()
            ->whereBetween('data_referencia', [$de->toDateString(), $ate->toDateString()])
            ->when($statusComissao, fn ($q) => $q->where('status', $statusComissao))
            ->when($tipoComissao, fn ($q) => $q->where('tipo', $tipoComissao));

        // --------- KPIs ---------
        $faturasRecebidas = (clone $faturasQuery)
            ->where('status', 'paga')
            ->whereBetween('pago_em', [$de, $ate])
            ->sum('valor');

        $faturasPendentes = (clone $faturasQuery)->where('status', 'pendente')->sum('valor');
        $faturasAtrasadas = (clone $faturasQuery)
            ->where('status', 'pendente')
            ->where('vencimento', '<', $hoje)
            ->sum('valor');

        $comissoesAReceber = (clone $comissoesQuery)->where('tipo', 'a_receber')->sum('valor');
        $comissoesAPagar = (clone $comissoesQuery)->where('tipo', 'a_pagar')->sum('valor');
        $comissoesPagas = (clone $comissoesQuery)->where('status', 'paga')->sum('valor');

        $saldoEstimado = (float) $faturasRecebidas + (float) $comissoesAReceber - (float) $comissoesAPagar;

        // --------- Série temporal: receita (faturas pagas) por dia ---------
        // Agrupa por dia para o gráfico de linha. Se o período for grande (>90d),
        // troca para agrupamento mensal para não inundar o gráfico.
        $usarMes = $de->diffInDays($ate) > 90;
        $serieReceita = $this->serieReceita($de, $ate, $usarMes);
        $serieComissoes = $this->serieComissoes($de, $ate, $usarMes);

        // --------- Distribuição: status das faturas no período ---------
        $statusFaturas = (clone $faturasQuery)
            ->selectRaw('status, COUNT(*) as qtd, SUM(valor) as total')
            ->groupBy('status')
            ->get()
            ->map(fn ($r) => [
                'status' => $r->status,
                'qtd' => (int) $r->qtd,
                'total' => (float) $r->total,
            ])
            ->values();

        // --------- Top usuários por valor de fatura paga no período ---------
        $topUsuarios = Fatura::query()
            ->where('status', 'paga')
            ->whereBetween('pago_em', [$de, $ate])
            ->with('user:id,name')
            ->selectRaw('user_id, COUNT(*) as qtd, SUM(valor) as total')
            ->groupBy('user_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn ($r) => [
                'user' => $r->user?->name ?? '—',
                'qtd' => (int) $r->qtd,
                'total' => (float) $r->total,
            ])
            ->values();

        return response()->json([
            'periodo' => [
                'de' => $de->toDateString(),
                'ate' => $ate->toDateString(),
                'granularidade' => $usarMes ? 'mes' : 'dia',
            ],
            'kpis' => [
                'faturas_recebidas' => (float) $faturasRecebidas,
                'faturas_pendentes' => (float) $faturasPendentes,
                'faturas_atrasadas' => (float) $faturasAtrasadas,
                'comissoes_a_receber' => (float) $comissoesAReceber,
                'comissoes_a_pagar' => (float) $comissoesAPagar,
                'comissoes_pagas' => (float) $comissoesPagas,
                'saldo_estimado' => (float) $saldoEstimado,
            ],
            'serie_receita' => $serieReceita,
            'serie_comissoes' => $serieComissoes,
            'status_faturas' => $statusFaturas,
            'top_usuarios' => $topUsuarios,
        ]);
    }

    /**
     * Fluxo de caixa consolidado do período: unifica faturas + comissões + despesas
     * em uma lista cronológica com sinal (+ entrada, - saída) e totais realizados.
     *
     * Realizado = já pago (pago_em preenchido). Previsto = pendente com vencimento no período.
     */
    public function fluxoCaixaData(Request $request): JsonResponse
    {
        $de = $request->query('de') ? Carbon::parse($request->query('de'))->startOfDay() : now()->startOfMonth();
        $ate = $request->query('ate') ? Carbon::parse($request->query('ate'))->endOfDay() : now()->endOfMonth();

        // Mesma garantia do relatório: projeta a despesa fixa até o fim do período pedido.
        if ($ate->isFuture()) {
            app(\App\Services\DespesaService::class)->garantirOcorrenciasAtuais($ate->copy());
        }

        // Filtra pela DATA DE REFERÊNCIA (pago_em para realizados; vencimento para previstos).
        // Para incluir o item no relatório, sua data relevante precisa cair no período.

        $linhas = collect();

        // ---- FATURAS ----
        Fatura::query()
            ->with(['user:id,name', 'processo:id,nome_completo'])
            ->where(function ($q) use ($de, $ate) {
                $q->whereBetween('pago_em', [$de, $ate])
                  ->orWhere(fn ($q2) => $q2->whereBetween('vencimento', [$de->toDateString(), $ate->toDateString()])->whereIn('status', ['pendente']));
            })
            ->orderByDesc('vencimento')
            ->chunk(500, function ($chunk) use (&$linhas) {
                foreach ($chunk as $f) {
                    $realizada = $f->status === 'paga' && $f->pago_em;
                    $data = $realizada ? $f->pago_em : $f->vencimento;
                    $descricao = 'Fatura: ' . ($f->descricao ?: 'cobrança')
                        . ($f->user?->name ? ' — ' . $f->user->name : '')
                        . ($f->processo_id ? ' (proc. #' . $f->processo_id . ')' : '');

                    $linhas->push([
                        'data' => Carbon::parse($data)->toDateString(),
                        'tipo' => 'fatura',
                        'descricao' => $descricao,
                        'valor' => (float) $f->valor,
                        'sinal' => '+', // fatura é sempre entrada
                        'status' => $f->isAtrasada() ? 'atrasada' : $f->status,
                        'realizada' => $realizada,
                        'url' => route('admin.financeiro.show', $f),
                    ]);
                }
            });

        // ---- COMISSÕES ----
        Comissao::query()
            ->with('licenciado:id,name')
            ->whereBetween('data_referencia', [$de->toDateString(), $ate->toDateString()])
            ->where('status', '!=', 'cancelada')
            ->orderByDesc('data_referencia')
            ->chunk(500, function ($chunk) use (&$linhas) {
                foreach ($chunk as $c) {
                    $realizada = $c->status === 'paga' && $c->pago_em;
                    $sinal = $c->tipo === 'a_receber' ? '+' : '-';
                    $descricao = 'Comissão (' . $c->tipoLabel() . '): ' . $c->descricao
                        . ($c->licenciado?->name ? ' — ' . $c->licenciado->name : '');

                    $linhas->push([
                        'data' => $c->data_referencia->toDateString(),
                        'tipo' => 'comissao',
                        'descricao' => $descricao,
                        'valor' => (float) $c->valor,
                        'sinal' => $sinal,
                        'status' => $c->status,
                        'realizada' => $realizada,
                        'url' => route('admin.comissoes.edit', $c),
                    ]);
                }
            });

        // ---- DESPESAS (ocorrências) ----
        DespesaOcorrencia::query()
            ->with('despesa:id,nome,categoria')
            ->where('status', '!=', 'cancelada')
            ->where(function ($q) use ($de, $ate) {
                $q->whereBetween('pago_em', [$de, $ate])
                  ->orWhereBetween('vencimento', [$de->toDateString(), $ate->toDateString()]);
            })
            ->orderByDesc('vencimento')
            ->chunk(500, function ($chunk) use (&$linhas) {
                foreach ($chunk as $o) {
                    $realizada = $o->status === 'paga' && $o->pago_em;
                    $data = $realizada ? $o->pago_em : $o->vencimento;
                    $descricao = 'Despesa: ' . ($o->despesa?->nome ?? '—')
                        . ($o->despesa?->categoria ? ' [' . $o->despesa->categoria . ']' : '')
                        . ' (comp. ' . $o->competencia->format('m/Y') . ')';

                    $linhas->push([
                        'data' => Carbon::parse($data)->toDateString(),
                        'tipo' => 'despesa',
                        'descricao' => $descricao,
                        'valor' => (float) $o->valor,
                        'sinal' => '-', // despesa é sempre saída
                        'status' => $o->isAtrasada() ? 'atrasada' : $o->status,
                        'realizada' => $realizada,
                        'url' => route('admin.despesas'),
                    ]);
                }
            });

        // Ordena mais recente primeiro
        $linhas = $linhas->sortByDesc('data')->values();

        $totais = [
            'entradas_realizadas' => $linhas->where('realizada', true)->where('sinal', '+')->sum('valor'),
            'saidas_realizadas' => $linhas->where('realizada', true)->where('sinal', '-')->sum('valor'),
            'entradas_previstas' => $linhas->where('realizada', false)->where('sinal', '+')->sum('valor'),
            'saidas_previstas' => $linhas->where('realizada', false)->where('sinal', '-')->sum('valor'),
            'qtd' => $linhas->count(),
        ];
        $totais['saldo_realizado'] = $totais['entradas_realizadas'] - $totais['saidas_realizadas'];
        $totais['saldo_projetado'] = $totais['saldo_realizado']
            + ($totais['entradas_previstas'] - $totais['saidas_previstas']);

        return response()->json([
            'periodo' => ['de' => $de->toDateString(), 'ate' => $ate->toDateString()],
            'linhas' => $linhas,
            'totais' => $totais,
        ]);
    }

    /**
     * Série de receita (faturas pagas) por dia ou mês.
     */
    private function serieReceita(Carbon $de, Carbon $ate, bool $porMes): array
    {
        $format = $porMes ? '%Y-%m' : '%Y-%m-%d';
        $rows = Fatura::query()
            ->where('status', 'paga')
            ->whereBetween('pago_em', [$de, $ate])
            ->selectRaw("DATE_FORMAT(pago_em, '{$format}') as bucket, SUM(valor) as total")
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->pluck('total', 'bucket');

        return $this->preencherSerie($de, $ate, $porMes, $rows);
    }

    /**
     * Série de comissões (a_receber e a_pagar) por dia ou mês.
     */
    private function serieComissoes(Carbon $de, Carbon $ate, bool $porMes): array
    {
        $format = $porMes ? '%Y-%m' : '%Y-%m-%d';

        $aReceber = Comissao::query()
            ->where('tipo', 'a_receber')
            ->whereBetween('data_referencia', [$de->toDateString(), $ate->toDateString()])
            ->selectRaw("DATE_FORMAT(data_referencia, '{$format}') as bucket, SUM(valor) as total")
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->pluck('total', 'bucket');

        $aPagar = Comissao::query()
            ->where('tipo', 'a_pagar')
            ->whereBetween('data_referencia', [$de->toDateString(), $ate->toDateString()])
            ->selectRaw("DATE_FORMAT(data_referencia, '{$format}') as bucket, SUM(valor) as total")
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->pluck('total', 'bucket');

        return [
            'a_receber' => $this->preencherSerie($de, $ate, $porMes, $aReceber),
            'a_pagar' => $this->preencherSerie($de, $ate, $porMes, $aPagar),
        ];
    }

    /**
     * Preenche buckets ausentes com 0 para o gráfico ter uma série contínua.
     */
    private function preencherSerie(Carbon $de, Carbon $ate, bool $porMes, $rows): array
    {
        $labels = [];
        $valores = [];

        $cursor = $de->copy();
        $stop = $ate->copy();
        while ($cursor <= $stop) {
            $key = $porMes ? $cursor->format('Y-m') : $cursor->format('Y-m-d');
            $label = $porMes ? $cursor->translatedFormat('M/Y') : $cursor->format('d/m');
            $labels[] = $label;
            $valores[] = (float) ($rows[$key] ?? 0);

            if ($porMes) {
                $cursor->addMonth();
            } else {
                $cursor->addDay();
            }
        }

        return ['labels' => $labels, 'valores' => $valores];
    }
}
