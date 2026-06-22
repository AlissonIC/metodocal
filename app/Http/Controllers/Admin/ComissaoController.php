<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClienteLicenciado;
use App\Models\Comissao;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ComissaoController extends Controller
{
    public function index()
    {
        return view('content.admin.comissoes.index');
    }

    public function datatable(): JsonResponse
    {
        $query = Comissao::query()->with(['licenciado:id,name', 'cliente:id,nome']);

        return DataTables::eloquent($query)
            ->addColumn('licenciado_nome', fn (Comissao $c) => $c->licenciado?->name ?? '—')
            ->addColumn('cliente_nome', fn (Comissao $c) => $c->cliente?->nome ?? '—')
            ->addColumn('valor_formatado', fn (Comissao $c) => 'R$ ' . number_format((float) $c->valor, 2, ',', '.'))
            ->addColumn('data_formatada', fn (Comissao $c) => $c->data_referencia->format('d/m/Y'))
            ->addColumn('status_badge', function (Comissao $c) {
                $map = ['pendente' => 'warning', 'paga' => 'success', 'cancelada' => 'secondary'];
                return '<span class="badge bg-label-' . ($map[$c->status] ?? 'secondary') . '">' . ucfirst($c->status) . '</span>';
            })
            ->rawColumns(['status_badge'])
            ->toJson();
    }

    public function create()
    {
        return view('content.admin.comissoes.form', [
            'comissao' => new Comissao(),
            'licenciados' => User::role('licenciado')->orderBy('name')->get(['id', 'name']),
            'clientes' => ClienteLicenciado::orderBy('nome')->get(['id', 'nome', 'licensed_by_user_id']),
        ]);
    }

    public function edit(Comissao $comissao)
    {
        return view('content.admin.comissoes.form', [
            'comissao' => $comissao,
            'licenciados' => User::role('licenciado')->orderBy('name')->get(['id', 'name']),
            'clientes' => ClienteLicenciado::orderBy('nome')->get(['id', 'nome', 'licensed_by_user_id']),
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
            'descricao' => ['required', 'string', 'max:160'],
            'valor' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'data_referencia' => ['required', 'date'],
            'status' => ['required', 'in:pendente,paga,cancelada'],
        ]);
    }
}
