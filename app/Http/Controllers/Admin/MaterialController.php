<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Material;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class MaterialController extends Controller
{
    public function index()
    {
        return view('content.admin.materiais.index');
    }

    public function datatable(): JsonResponse
    {
        return DataTables::eloquent(Material::query())
            ->addColumn('tamanho_formatado', fn (Material $m) => $m->tamanho_bytes
                ? round($m->tamanho_bytes / 1024, 1) . ' KB'
                : '—')
            ->addColumn('status_badge', fn (Material $m) => $m->ativo
                ? '<span class="badge bg-label-success">Ativo</span>'
                : '<span class="badge bg-label-secondary">Inativo</span>')
            ->rawColumns(['status_badge'])
            ->toJson();
    }

    public function create()
    {
        return view('content.admin.materiais.form', [
            'material' => new Material(),
        ]);
    }

    public function edit(Material $material)
    {
        return view('content.admin.materiais.form', [
            'material' => $material,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['arquivo'] = $this->storeFile($request);
        $data['tamanho_bytes'] = $request->file('arquivo')->getSize();

        Material::create($data);

        return redirect()
            ->route('admin.materiais')
            ->with('status', 'Material enviado.');
    }

    public function update(Request $request, Material $material): RedirectResponse
    {
        $data = $this->validateData($request, ignoreFile: true);

        if ($request->hasFile('arquivo')) {
            if ($material->arquivo) {
                Storage::disk('public')->delete($material->arquivo);
            }
            $data['arquivo'] = $this->storeFile($request);
            $data['tamanho_bytes'] = $request->file('arquivo')->getSize();
        }

        $material->update($data);

        return redirect()
            ->route('admin.materiais')
            ->with('status', 'Material atualizado.');
    }

    public function destroy(Material $material): JsonResponse
    {
        if ($material->arquivo) {
            Storage::disk('public')->delete($material->arquivo);
        }
        $material->delete();
        return response()->json(['status' => 'success', 'message' => 'Material removido.']);
    }

    private function validateData(Request $request, bool $ignoreFile = false): array
    {
        $rules = [
            'titulo' => ['required', 'string', 'max:160'],
            'descricao' => ['nullable', 'string', 'max:2000'],
            'categoria' => ['nullable', 'string', 'max:80'],
            'ativo' => ['nullable', 'boolean'],
        ];
        if (! $ignoreFile) {
            $rules['arquivo'] = ['required', 'file', 'max:20480'];
        } else {
            $rules['arquivo'] = ['nullable', 'file', 'max:20480'];
        }

        $data = $request->validate($rules);
        $data['ativo'] = (bool) ($data['ativo'] ?? false);
        unset($data['arquivo']);
        return $data;
    }

    private function storeFile(Request $request): string
    {
        return $request->file('arquivo')->store('materiais', 'public');
    }
}
