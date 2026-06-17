<?php

namespace App\Http\Controllers\Licenciado;

use App\Http\Controllers\Controller;
use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MaterialController extends Controller
{
    public function index()
    {
        return view('content.licenciado.materiais', [
            'porCategoria' => Material::where('ativo', true)
                ->orderBy('categoria')
                ->orderBy('titulo')
                ->get()
                ->groupBy(fn ($m) => $m->categoria ?? 'Outros'),
        ]);
    }

    public function download(Material $material): StreamedResponse
    {
        abort_unless($material->ativo, 404);

        if (! Storage::disk('public')->exists($material->arquivo)) {
            abort(404, 'Arquivo não encontrado no disco. (Cadastro demo — o arquivo real precisa ser enviado pelo admin.)');
        }

        $filename = basename($material->arquivo);
        return Storage::disk('public')->download($material->arquivo, $filename);
    }
}
