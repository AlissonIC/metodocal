<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ExportaPlanilha;
use App\Http\Controllers\Controller;
use App\Models\ClienteLicenciado;
use App\Models\Comissao;
use App\Models\Processo;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Yajra\DataTables\Facades\DataTables;

class ComissaoController extends Controller
{
    use ExportaPlanilha;

    public function index()
    {
        return view('content.admin.comissoes.index');
    }

    /** Filtros da listagem — compartilhados entre o DataTable e a exportação. */
    private function queryFiltrada(Request $request): Builder
    {
        $query = Comissao::query()->with(['licenciado:id,name', 'cliente:id,nome', 'processo:id,nome_completo']);

        if ($s = $request->query('status')) {
            $query->where('status', $s);
        }
        if ($t = $request->query('tipo')) {
            $query->where('tipo', $t);
        }
        if ($de = $request->query('data_de')) {
            $query->whereDate('data_referencia', '>=', $de);
        }
        if ($ate = $request->query('data_ate')) {
            $query->whereDate('data_referencia', '<=', $ate);
        }

        // Busca livre — só na exportação; no DataTable quem faz isso é o próprio yajra.
        if ($b = trim((string) $request->query('busca', ''))) {
            $query->where(function ($q) use ($b) {
                $q->where('descricao', 'like', "%$b%")
                    ->orWhereHas('licenciado', fn ($u) => $u->where('name', 'like', "%$b%"))
                    ->orWhereHas('cliente', fn ($c) => $c->where('nome', 'like', "%$b%"))
                    ->orWhereHas('processo', fn ($p) => $p->where('nome_completo', 'like', "%$b%"));
            });
        }

        return $query;
    }

    public function export(Request $request): StreamedResponse
    {
        $query = $this->queryFiltrada($request)->orderByDesc('data_referencia');

        $tipo = $request->query('tipo');
        $nome = match ($tipo) {
            'a_receber' => 'comissoes-a-receber',
            'a_pagar' => 'comissoes-a-pagar',
            default => 'comissoes',
        };

        return $this->exportarCsv(
            $nome,
            ['ID', 'Usuário', 'Tipo', 'Descrição', 'Cliente', 'Processo', 'Data de referência', 'Valor (R$)', 'Status', 'Pago em'],
            $query,
            fn (Comissao $c) => [
                $c->id,
                $c->licenciado?->name,
                $c->tipoLabel(),
                $c->descricao,
                $c->cliente?->nome,
                $c->processo ? '#' . $c->processo->id . ' · ' . $c->processo->nome_completo : null,
                $c->data_referencia,
                $this->dinheiroCsv($c->valor),
                ucfirst($c->status),
                $c->pago_em?->format('d/m/Y H:i'),
            ],
        );
    }

    public function datatable(Request $request): JsonResponse
    {
        return DataTables::eloquent($this->queryFiltrada($request))
            ->addColumn('licenciado_nome', fn (Comissao $c) => $c->licenciado?->name ?? '—')
            ->addColumn('cliente_nome', fn (Comissao $c) => $c->cliente?->nome ?? '—')
            ->addColumn('processo_label', fn (Comissao $c) => $c->processo
                ? '#' . $c->processo->id . ' · ' . \Illuminate\Support\Str::limit($c->processo->nome_completo, 30)
                : '<span class="text-muted">—</span>')
            ->addColumn('valor_formatado', fn (Comissao $c) => 'R$ ' . number_format((float) $c->valor, 2, ',', '.'))
            ->addColumn('data_formatada', fn (Comissao $c) => $c->data_referencia->format('d/m/Y'))
            ->addColumn('tipo_badge', fn (Comissao $c) =>
                '<span class="badge bg-label-' . $c->tipoColor() . '">' . $c->tipoLabel() . '</span>')
            ->addColumn('status_badge', function (Comissao $c) {
                $map = ['pendente' => 'warning', 'paga' => 'success', 'cancelada' => 'secondary'];
                return '<span class="badge bg-label-' . ($map[$c->status] ?? 'secondary') . '">' . ucfirst($c->status) . '</span>';
            })
            ->rawColumns(['status_badge', 'tipo_badge', 'processo_label'])
            ->toJson();
    }

    public function create()
    {
        return view('content.admin.comissoes.form', [
            'comissao' => new Comissao(['tipo' => 'a_receber', 'status' => 'pendente']),
            'usuarios' => User::with('roles:id,name')->orderBy('name')->get(['id', 'name', 'email']),
            'clientes' => ClienteLicenciado::orderBy('nome')->get(['id', 'nome', 'licensed_by_user_id']),
            'processos' => Processo::orderByDesc('id')->limit(500)->get(['id', 'nome_completo']),
        ]);
    }

    public function edit(Comissao $comissao)
    {
        return view('content.admin.comissoes.form', [
            'comissao' => $comissao,
            'usuarios' => User::with('roles:id,name')->orderBy('name')->get(['id', 'name', 'email']),
            'clientes' => ClienteLicenciado::orderBy('nome')->get(['id', 'nome', 'licensed_by_user_id']),
            'processos' => Processo::orderByDesc('id')->limit(500)->get(['id', 'nome_completo']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Comissao::create($this->validateData($request));

        return redirect()
            ->route('admin.comissoes')
            ->with('status', 'Comissão lançada.');
    }

    public function update(Request $request, Comissao $comissao): RedirectResponse
    {
        $data = $this->validateData($request);
        if ($data['status'] === 'paga' && $comissao->status !== 'paga') {
            $data['pago_em'] = now();
        } elseif ($data['status'] !== 'paga') {
            $data['pago_em'] = null;
        }
        $comissao->update($data);

        return redirect()
            ->route('admin.comissoes')
            ->with('status', 'Comissão atualizada.');
    }

    public function destroy(Comissao $comissao): JsonResponse
    {
        $comissao->delete();
        return response()->json(['status' => 'success', 'message' => 'Comissão removida.']);
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'licensed_by_user_id' => ['required', 'exists:users,id'],
            'cliente_id' => ['nullable', 'exists:clientes_licenciado,id'],
            'processo_id' => ['nullable', 'exists:processos,id'],
            'descricao' => ['required', 'string', 'max:160'],
            'valor' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'tipo' => ['required', 'in:a_receber,a_pagar'],
            'data_referencia' => ['required', 'date'],
            'status' => ['required', 'in:pendente,paga,cancelada'],
        ]);
    }
}
