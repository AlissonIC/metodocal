<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Despesa;
use App\Models\DespesaOcorrencia;
use App\Services\DespesaService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class DespesaController extends Controller
{
    public function __construct(private DespesaService $service) {}

    public function index(Request $request)
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        // Garante que a ocorrência do mês corrente exista para toda despesa fixa ativa
        $this->service->garantirOcorrenciasAtuais();

        return view('content.admin.despesas.index', [
            'categorias' => Despesa::query()->whereNotNull('categoria')->distinct()->orderBy('categoria')->pluck('categoria'),
        ]);
    }

    public function datatable(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $query = DespesaOcorrencia::query()->with('despesa:id,nome,tipo,categoria,encerrada_em');

        if ($mes = $request->query('competencia')) {
            $ref = Carbon::createFromFormat('Y-m', $mes)->startOfMonth();
            $query->whereBetween('competencia', [$ref->copy()->startOfMonth(), $ref->copy()->endOfMonth()]);
        }

        if ($status = $request->query('status')) {
            if ($status === 'atrasada') {
                $query->where('status', 'pendente')->where('vencimento', '<', now()->startOfDay());
            } else {
                $query->where('status', $status);
            }
        }

        if ($tipo = $request->query('tipo')) {
            $query->whereHas('despesa', fn ($q) => $q->where('tipo', $tipo));
        }

        if ($cat = $request->query('categoria')) {
            $query->whereHas('despesa', fn ($q) => $q->where('categoria', $cat));
        }

        return DataTables::eloquent($query->orderByDesc('vencimento')->orderByDesc('id'))
            ->addColumn('despesa_nome', function (DespesaOcorrencia $o) {
                $badge = $o->despesa->tipo === Despesa::TIPO_FIXA
                    ? '<span class="badge bg-label-info ms-1" title="Despesa fixa">Fixa</span>'
                    : '<span class="badge bg-label-secondary ms-1" title="Despesa única">Única</span>';
                $encerrada = $o->despesa->encerrada_em
                    ? '<i class="icon-base ti tabler-lock text-muted ms-1" title="Despesa encerrada"></i>'
                    : '';
                return e($o->despesa->nome) . $badge . $encerrada;
            })
            ->addColumn('categoria', fn (DespesaOcorrencia $o) => $o->despesa->categoria ?: '<span class="text-muted">—</span>')
            ->addColumn('competencia_fmt', fn (DespesaOcorrencia $o) => $o->competencia->translatedFormat('M/Y'))
            ->addColumn('vencimento_fmt', fn (DespesaOcorrencia $o) => $o->vencimento->format('d/m/Y'))
            ->addColumn('valor_fmt', fn (DespesaOcorrencia $o) => 'R$ ' . number_format((float) $o->valor, 2, ',', '.'))
            ->addColumn('status_badge', fn (DespesaOcorrencia $o) =>
                '<span class="badge bg-label-' . $o->statusColor() . '">' . e($o->statusLabel()) . '</span>')
            ->addColumn('despesa_id', fn (DespesaOcorrencia $o) => $o->despesa_id)
            ->addColumn('is_fixa', fn (DespesaOcorrencia $o) => $o->despesa->tipo === Despesa::TIPO_FIXA)
            ->addColumn('encerrada', fn (DespesaOcorrencia $o) => (bool) $o->despesa->encerrada_em)
            ->rawColumns(['despesa_nome', 'categoria', 'status_badge'])
            ->toJson();
    }

    public function storeDespesa(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $data = $this->validateDespesa($request);

        $despesa = DB::transaction(function () use ($data, $request) {
            $d = Despesa::create(array_merge($data, [
                'created_by_user_id' => $request->user()->id,
            ]));
            $this->service->criarOcorrenciasIniciais($d);
            return $d;
        });

        return response()->json(['message' => 'Despesa cadastrada.', 'id' => $despesa->id]);
    }

    public function showDespesa(Request $request, Despesa $despesa): JsonResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        return response()->json([
            'id' => $despesa->id,
            'nome' => $despesa->nome,
            'categoria' => $despesa->categoria,
            'descricao' => $despesa->descricao,
            'valor' => number_format((float) $despesa->valor, 2, ',', '.'),
            'tipo' => $despesa->tipo,
            'dia_vencimento' => $despesa->dia_vencimento,
            'data_inicio' => $despesa->data_inicio?->toDateString(),
            'encerrada_em' => $despesa->encerrada_em?->format('d/m/Y H:i'),
        ]);
    }

    public function updateDespesa(Request $request, Despesa $despesa): JsonResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $data = $this->validateDespesa($request);

        // Não permite mudar tipo — se precisa mudar, exclui e recria (evita drift entre template e ocorrências)
        unset($data['tipo']);

        $despesa->update($data);

        // Se ficou fixa e ainda ativa, garante ocorrências até o mês corrente
        if ($despesa->isFixa() && ! $despesa->isEncerrada()) {
            $this->service->gerarOcorrenciasFaltantes($despesa);
        }

        // Atualiza valor nas ocorrências futuras pendentes (não altera as pagas nem as vencidas)
        DespesaOcorrencia::where('despesa_id', $despesa->id)
            ->where('status', 'pendente')
            ->where('vencimento', '>=', now()->startOfDay())
            ->update(['valor' => $despesa->valor]);

        return response()->json(['message' => 'Despesa atualizada.']);
    }

    public function destroyDespesa(Request $request, Despesa $despesa): JsonResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $temPaga = $despesa->ocorrencias()->where('status', 'paga')->exists();
        if ($temPaga) {
            return response()->json([
                'message' => 'Esta despesa possui ocorrências pagas. Encerre-a em vez de excluir.',
            ], 422);
        }

        $despesa->delete(); // cascade nas ocorrências

        return response()->json(['message' => 'Despesa excluída.']);
    }

    public function encerrarDespesa(Request $request, Despesa $despesa): JsonResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        if (! $despesa->isFixa()) {
            return response()->json(['message' => 'Apenas despesas fixas podem ser encerradas.'], 422);
        }

        if ($despesa->isEncerrada()) {
            return response()->json(['message' => 'Esta despesa já está encerrada.'], 422);
        }

        DB::transaction(function () use ($despesa) {
            $despesa->update(['encerrada_em' => now()]);

            // Cancela ocorrências futuras pendentes (não mexe em pagas)
            DespesaOcorrencia::where('despesa_id', $despesa->id)
                ->where('status', 'pendente')
                ->where('vencimento', '>', now()->startOfDay())
                ->update(['status' => 'cancelada']);
        });

        return response()->json(['message' => 'Despesa encerrada. Não gerará novos meses.']);
    }

    public function reabrirDespesa(Request $request, Despesa $despesa): JsonResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        if (! $despesa->isEncerrada()) {
            return response()->json(['message' => 'Esta despesa não está encerrada.'], 422);
        }

        $despesa->update(['encerrada_em' => null]);
        $this->service->gerarOcorrenciasFaltantes($despesa);

        return response()->json(['message' => 'Despesa reaberta.']);
    }

    public function marcarPaga(Request $request, DespesaOcorrencia $ocorrencia): JsonResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $ocorrencia->update([
            'status' => 'paga',
            'pago_em' => now(),
            'metodo' => $request->input('metodo', 'manual'),
        ]);

        return response()->json(['message' => 'Marcada como paga.']);
    }

    public function marcarPendente(Request $request, DespesaOcorrencia $ocorrencia): JsonResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $ocorrencia->update([
            'status' => 'pendente',
            'pago_em' => null,
            'metodo' => null,
        ]);

        return response()->json(['message' => 'Marcada como pendente.']);
    }

    public function destroyOcorrencia(Request $request, DespesaOcorrencia $ocorrencia): JsonResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        if ($ocorrencia->status === 'paga') {
            return response()->json(['message' => 'Não é possível remover uma ocorrência paga. Marque como pendente primeiro.'], 422);
        }

        $ocorrencia->delete();
        return response()->json(['message' => 'Ocorrência removida.']);
    }

    private function validateDespesa(Request $request): array
    {
        $data = $request->validate([
            'nome' => ['required', 'string', 'max:160'],
            'categoria' => ['nullable', 'string', 'max:60'],
            'descricao' => ['nullable', 'string', 'max:2000'],
            'valor' => ['required', 'numeric', 'min:0.01', 'max:9999999.99'],
            'tipo' => ['required', 'in:fixa,unica'],
            'dia_vencimento' => ['nullable', 'integer', 'between:1,31'],
            'data_inicio' => ['required', 'date'],
        ]);

        // Fixa exige dia_vencimento — se não veio, deriva do dia da data_inicio
        if ($data['tipo'] === Despesa::TIPO_FIXA && empty($data['dia_vencimento'])) {
            $data['dia_vencimento'] = (int) Carbon::parse($data['data_inicio'])->day;
        }
        // Única não usa dia_vencimento
        if ($data['tipo'] === Despesa::TIPO_UNICA) {
            $data['dia_vencimento'] = null;
        }

        return $data;
    }
}
