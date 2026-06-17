<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sessao;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class SessaoController extends Controller
{
    public function index()
    {
        return view('content.admin.sessoes.index', [
            'mentorados' => User::role('mentorado')->orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    public function datatable(): JsonResponse
    {
        $query = Sessao::query()->with('user:id,name,email');

        return DataTables::eloquent($query)
            ->addColumn('user_name', fn (Sessao $s) => $s->user?->name ?? '—')
            ->addColumn('scheduled_formatado', fn (Sessao $s) => $s->scheduled_at->format('d/m/Y H:i'))
            ->addColumn('status_badge', function (Sessao $s) {
                $map = ['agendada' => 'primary', 'concluida' => 'success', 'cancelada' => 'secondary'];
                return '<span class="badge bg-label-' . ($map[$s->status] ?? 'secondary') . '">' . ucfirst($s->status) . '</span>';
            })
            ->rawColumns(['status_badge'])
            ->toJson();
    }

    public function show(Sessao $sessao): JsonResponse
    {
        return response()->json([
            'id' => $sessao->id,
            'user_id' => $sessao->user_id,
            'titulo' => $sessao->titulo,
            'descricao' => $sessao->descricao,
            'scheduled_at' => $sessao->scheduled_at->format('Y-m-d\TH:i'),
            'duracao_minutos' => $sessao->duracao_minutos,
            'link_reuniao' => $sessao->link_reuniao,
            'status' => $sessao->status,
            'notas' => $sessao->notas,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $sessao = Sessao::create($this->validateData($request));
        return response()->json(['status' => 'success', 'message' => 'Sessão agendada.', 'data' => $sessao], 201);
    }

    public function update(Request $request, Sessao $sessao): JsonResponse
    {
        $sessao->update($this->validateData($request));
        return response()->json(['status' => 'success', 'message' => 'Sessão atualizada.']);
    }

    public function destroy(Sessao $sessao): JsonResponse
    {
        $sessao->delete();
        return response()->json(['status' => 'success', 'message' => 'Sessão removida.']);
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'titulo' => ['required', 'string', 'max:120'],
            'descricao' => ['nullable', 'string', 'max:2000'],
            'scheduled_at' => ['required', 'date'],
            'duracao_minutos' => ['required', 'integer', 'min:15', 'max:480'],
            'link_reuniao' => ['nullable', 'url', 'max:300'],
            'status' => ['required', 'in:agendada,concluida,cancelada'],
            'notas' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
