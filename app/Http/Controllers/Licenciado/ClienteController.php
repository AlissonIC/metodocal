<?php

namespace App\Http\Controllers\Licenciado;

use App\Http\Controllers\Controller;
use App\Http\Requests\BaseFormRequest;
use App\Models\ClienteLicenciado;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class ClienteController extends Controller
{
    public function index()
    {
        return view('content.licenciado.crm');
    }

    public function datatable(): JsonResponse
    {
        $query = $this->scope();

        return DataTables::eloquent($query)
            ->addColumn('status_badge', function (ClienteLicenciado $c) {
                $map = ['lead' => 'warning', 'ativo' => 'success', 'perdido' => 'secondary'];
                return '<span class="badge bg-label-' . ($map[$c->status] ?? 'secondary') . '">' . ucfirst($c->status) . '</span>';
            })
            ->addColumn('actions', fn (ClienteLicenciado $c) => $c->id)
            ->rawColumns(['status_badge'])
            ->toJson();
    }

    public function show(ClienteLicenciado $cliente): JsonResponse
    {
        $this->ensureOwnership($cliente);
        return response()->json($cliente);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateData($request);
        $data['licensed_by_user_id'] = Auth::id();
        $cliente = ClienteLicenciado::create($data);
        return response()->json(['status' => 'success', 'message' => 'Cliente cadastrado.', 'data' => $cliente], 201);
    }

    public function update(Request $request, ClienteLicenciado $cliente): JsonResponse
    {
        $this->ensureOwnership($cliente);
        $cliente->update($this->validateData($request));
        return response()->json(['status' => 'success', 'message' => 'Cliente atualizado.']);
    }

    public function destroy(ClienteLicenciado $cliente): JsonResponse
    {
        $this->ensureOwnership($cliente);
        $cliente->delete();
        return response()->json(['status' => 'success', 'message' => 'Cliente removido.']);
    }

    private function scope()
    {
        return ClienteLicenciado::query()->where('licensed_by_user_id', Auth::id());
    }

    private function ensureOwnership(ClienteLicenciado $cliente): void
    {
        abort_unless($cliente->licensed_by_user_id === Auth::id(), 403);
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'nome' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:180'],
            'telefone' => ['nullable', 'string', 'max:30'],
            'cpf_cnpj' => ['nullable', 'string', 'max:20'],
            'endereco' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', 'in:lead,ativo,perdido'],
            'notas' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
