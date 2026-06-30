<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreServicoRequest;
use App\Http\Requests\Admin\UpdateServicoRequest;
use App\Models\Servico;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class ServicoController extends Controller
{
    public function index()
    {
        return view('content.admin.servicos.index');
    }

    public function datatable(Request $request): JsonResponse
    {
        $query = Servico::query()
            ->withCount('processos')
            ->latest('created_at');

        if ($request->filled('ativo')) {
            $query->where('ativo', (bool) $request->query('ativo'));
        }

        return DataTables::eloquent($query)
            ->addColumn('valor_formatado', fn (Servico $s) => $s->valor_padrao !== null
                ? 'R$ ' . number_format((float) $s->valor_padrao, 2, ',', '.')
                : '—')
            ->addColumn('status_badge', fn (Servico $s) => $s->ativo
                ? '<span class="badge bg-label-success">Ativo</span>'
                : '<span class="badge bg-label-secondary">Inativo</span>')
            ->addColumn('actions', fn (Servico $s) => $s->id)
            ->rawColumns(['status_badge'])
            ->toJson();
    }

    public function create()
    {
        return view('content.admin.servicos.form', [
            'servico' => new Servico(['ativo' => true]),
        ]);
    }

    public function edit(Servico $servico)
    {
        return view('content.admin.servicos.form', [
            'servico' => $servico,
        ]);
    }

    public function store(StoreServicoRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = $this->uniqueSlug($data['nome']);

        Servico::create($data);

        return redirect()
            ->route('admin.servicos')
            ->with('status', 'Serviço criado com sucesso.');
    }

    public function update(UpdateServicoRequest $request, Servico $servico): RedirectResponse
    {
        $data = $request->validated();

        if ($servico->nome !== $data['nome']) {
            $data['slug'] = $this->uniqueSlug($data['nome'], $servico->id);
        }

        $servico->update($data);

        return redirect()
            ->route('admin.servicos')
            ->with('status', 'Serviço atualizado com sucesso.');
    }

    public function destroy(Servico $servico): JsonResponse
    {
        if ($servico->processos()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Não é possível excluir um serviço com processos vinculados. Desative-o.',
            ], 422);
        }

        $servico->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Serviço excluído.',
        ]);
    }

    private function uniqueSlug(string $nome, ?int $ignoreId = null): string
    {
        $base = Str::slug($nome);
        $slug = $base;
        $i = 1;
        while (Servico::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base . '-' . (++$i);
        }
        return $slug;
    }
}
