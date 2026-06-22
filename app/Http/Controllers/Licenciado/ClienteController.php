<?php

namespace App\Http\Controllers\Licenciado;

use App\Http\Controllers\Controller;
use App\Models\ClienteLicenciado;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
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

    public function create()
    {
        return view('content.licenciado.crm-form', [
            'cliente' => new ClienteLicenciado(),
        ]);
    }

    public function edit(ClienteLicenciado $cliente)
    {
        $this->ensureOwnership($cliente);

        return view('content.licenciado.crm-form', [
            'cliente' => $cliente,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['licensed_by_user_id'] = Auth::id();
        ClienteLicenciado::create($data);

        return redirect()
            ->route('licenciado.crm')
            ->with('status', 'Cliente cadastrado.');
    }

    public function update(Request $request, ClienteLicenciado $cliente): RedirectResponse
    {
        $this->ensureOwnership($cliente);
        $cliente->update($this->validateData($request));

        return redirect()
            ->route('licenciado.crm')
            ->with('status', 'Cliente atualizado.');
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
