<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conteudo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ConteudoController extends Controller
{
    public function index()
    {
        return view('content.admin.conteudos.index');
    }

    public function datatable(): JsonResponse
    {
        return DataTables::eloquent(Conteudo::query()->withCount('progressos'))
            ->addColumn('tipo_label', fn (Conteudo $c) => ucfirst($c->tipo))
            ->addColumn('status_badge', fn (Conteudo $c) => $c->ativo
                ? '<span class="badge bg-label-success">Ativo</span>'
                : '<span class="badge bg-label-secondary">Inativo</span>')
            ->rawColumns(['status_badge'])
            ->toJson();
    }

    public function create()
    {
        return view('content.admin.conteudos.form', [
            'conteudo' => new Conteudo(),
        ]);
    }

    public function edit(Conteudo $conteudo)
    {
        return view('content.admin.conteudos.form', [
            'conteudo' => $conteudo,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Conteudo::create($this->validateData($request));

        return redirect()
            ->route('admin.conteudos')
            ->with('status', 'Conteúdo criado com sucesso.');
    }

    public function update(Request $request, Conteudo $conteudo): RedirectResponse
    {
        $conteudo->update($this->validateData($request));

        return redirect()
            ->route('admin.conteudos')
            ->with('status', 'Conteúdo atualizado com sucesso.');
    }

    public function destroy(Conteudo $conteudo): JsonResponse
    {
        $conteudo->delete();
        return response()->json(['status' => 'success', 'message' => 'Conteúdo removido.']);
    }

    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'titulo' => ['required', 'string', 'max:160'],
            'descricao' => ['nullable', 'string', 'max:2000'],
            'tipo' => ['required', 'in:video,pdf,texto,link'],
            'url' => ['required', 'string', 'max:500'],
            'categoria' => ['nullable', 'string', 'max:80'],
            'ordem' => ['nullable', 'integer', 'min:0'],
            'ativo' => ['nullable', 'boolean'],
        ]);
        $data['ativo'] = (bool) ($data['ativo'] ?? false);
        $data['ordem'] = (int) ($data['ordem'] ?? 0);
        return $data;
    }
}
