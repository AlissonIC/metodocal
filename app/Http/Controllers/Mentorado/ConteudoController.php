<?php

namespace App\Http\Controllers\Mentorado;

use App\Http\Controllers\Controller;
use App\Models\Conteudo;
use App\Models\ProgressoConteudo;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ConteudoController extends Controller
{
    public function index()
    {
        $userId = Auth::id();
        $conteudos = Conteudo::where('ativo', true)
            ->orderBy('categoria')
            ->orderBy('ordem')
            ->get();

        $concluidos = ProgressoConteudo::where('user_id', $userId)
            ->pluck('concluido_em', 'conteudo_id');

        $porCategoria = $conteudos->groupBy('categoria');

        return view('content.mentorado.conteudos', [
            'porCategoria' => $porCategoria,
            'concluidos' => $concluidos,
            'totalConteudos' => $conteudos->count(),
            'totalConcluidos' => $concluidos->count(),
        ]);
    }

    public function toggleComplete(Conteudo $conteudo): JsonResponse
    {
        $userId = Auth::id();
        $progresso = ProgressoConteudo::where('user_id', $userId)
            ->where('conteudo_id', $conteudo->id)
            ->first();

        if ($progresso) {
            $progresso->delete();
            return response()->json(['status' => 'success', 'message' => 'Conteúdo marcado como não concluído.', 'concluido' => false]);
        }

        ProgressoConteudo::create([
            'user_id' => $userId,
            'conteudo_id' => $conteudo->id,
            'concluido_em' => now(),
        ]);

        return response()->json(['status' => 'success', 'message' => 'Conteúdo concluído!', 'concluido' => true]);
    }
}
