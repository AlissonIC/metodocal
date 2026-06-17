<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClienteLicenciado;
use App\Models\Comissao;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ComissaoController extends Controller
{
    public function index()
    {
        return view('content.admin.comissoes.index', [
            'licenciados' => User::role('licenciado')->orderBy('name')->get(['id', 'name']),
            'clientes' => ClienteLicenciado::orderBy('nome')->get(['id', 'nome', 'licensed_by_user_id']),
        ]);
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

    public function show(Comissao $comissao): JsonResponse
    {
        return response()->json([
            'id' => $comissao->id,
            'licensed_by_user_id' => $comissao->licensed_by_user_id,
            'cliente_id' => $comissao->cliente_id,
            'descricao' => $comissao->descricao,
            'valor' => $comissao->valor,
            'data_referencia' => $comissao->data_referencia->format('Y-m-d'),
            'status' => $comissao->status,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $c = Comissao::create($this->validateData($request));
        return response()->json(['status' => 'success', 'message' => 'Comissão lançada.', 'data' => $c], 201);
    }

    public function update(Request $request, Comissao $comissao): JsonResponse
    {
        $data = $this->validateData($request);
        if ($data['status'] === 'paga' && $comissao->status !== 'paga') {
            $data['pago_em'] = now();
        } elseif ($data['status'] !== 'paga') {
            $data['pago_em'] = null;
        }
        $comissao->update($data);
        return response()->json(['status' => 'success', 'message' => 'Comissão atualizada.']);
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
