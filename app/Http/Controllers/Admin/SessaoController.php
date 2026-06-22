<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sessao;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class SessaoController extends Controller
{
    public function index()
    {
        return view('content.admin.sessoes.index');
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
            ->addColumn('actions', fn (Sessao $s) => $s->id)
            ->rawColumns(['status_badge'])
            ->toJson();
    }

    public function create()
    {
        return view('content.admin.sessoes.form', [
            'sessao' => new Sessao(),
            'mentorados' => User::role('mentorado')->orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    public function edit(Sessao $sessao)
    {
        return view('content.admin.sessoes.form', [
            'sessao' => $sessao,
            'mentorados' => User::role('mentorado')->orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Sessao::create($this->validateData($request));

        return redirect()
            ->route('admin.sessoes')
            ->with('status', 'Sessão agendada com sucesso.');
    }

    public function update(Request $request, Sessao $sessao): RedirectResponse
    {
        $sessao->update($this->validateData($request));

        return redirect()
            ->route('admin.sessoes')
            ->with('status', 'Sessão atualizada com sucesso.');
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
