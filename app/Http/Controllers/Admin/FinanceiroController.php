<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Fatura;
use App\Models\PaymentEvent;
use App\Models\Subscription;
use App\Services\FaturaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;
use Yajra\DataTables\Facades\DataTables;

class FinanceiroController extends Controller
{
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
        ]);
    }

    public function datatable(): JsonResponse
    {
        $query = Fatura::query()->with(['user:id,name,email', 'plan:id,nome,tipo']);

        return DataTables::eloquent($query)
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
            ->addColumn('metodo_label', fn (Fatura $f) => $f->metodo ? ucfirst($f->metodo) : '—')
            ->rawColumns(['status_badge'])
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
}
