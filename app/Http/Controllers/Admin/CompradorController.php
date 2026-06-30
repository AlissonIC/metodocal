<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCompradorRequest;
use App\Http\Requests\Admin\UpdateCompradorRequest;
use App\Models\Comprador;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class CompradorController extends Controller
{
    public function index()
    {
        return view('content.admin.compradores.index');
    }

    public function datatable(Request $request): JsonResponse
    {
        $query = Comprador::query()->withCount('processos');

        if ($request->filled('ativo')) {
            $query->where('ativo', (bool) $request->query('ativo'));
        }

        return DataTables::eloquent($query)
            ->addColumn('documento_formatado', fn (Comprador $c) => strtoupper($c->tipo_documento) . ': ' . $c->documentoFormatado())
            ->addColumn('status_badge', fn (Comprador $c) => $c->ativo
                ? '<span class="badge bg-label-success">Ativo</span>'
                : '<span class="badge bg-label-secondary">Inativo</span>')
            ->addColumn('actions', fn (Comprador $c) => $c->id)
            ->rawColumns(['status_badge'])
            ->toJson();
    }

    public function create()
    {
        return view('content.admin.compradores.form', [
            'comprador' => new Comprador(['tipo_documento' => 'cpf', 'ativo' => true]),
        ]);
    }

    public function edit(Comprador $comprador)
    {
        return view('content.admin.compradores.form', [
            'comprador' => $comprador,
        ]);
    }

    public function store(StoreCompradorRequest $request): RedirectResponse
    {
        Comprador::create($request->validated());

        return redirect()
            ->route('admin.compradores')
            ->with('status', 'Comprador cadastrado.');
    }

    public function update(UpdateCompradorRequest $request, Comprador $comprador): RedirectResponse
    {
        $comprador->update($request->validated());

        return redirect()
            ->route('admin.compradores')
            ->with('status', 'Comprador atualizado.');
    }

    public function destroy(Comprador $comprador): JsonResponse
    {
        if ($comprador->processos()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Há processos vinculados a este comprador. Desative-o ou desvincule antes.',
            ], 422);
        }

        $comprador->delete();

        return response()->json(['status' => 'success', 'message' => 'Comprador excluído.']);
    }
}
