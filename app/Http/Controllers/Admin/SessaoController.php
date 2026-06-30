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

    public function calendario()
    {
        return view('content.admin.sessoes.calendario');
    }

    public function events(Request $request): JsonResponse
    {
        $sessoes = Sessao::query()
            ->with('user:id,name')
            ->when($request->start, fn ($q) => $q->where('scheduled_at', '>=', $request->start))
            ->when($request->end, fn ($q) => $q->where('scheduled_at', '<=', $request->end))
            ->get();

        // Cores alinhadas ao tema dourado: agendada (dourado), concluida (verde), cancelada (cinza)
        $colors = [
            'agendada' => '#B8860B',
            'concluida' => '#16a34a',
            'cancelada' => '#82868b',
        ];

        return response()->json($sessoes->map(function (Sessao $s) use ($colors) {
            return [
                'id' => $s->id,
                'title' => $s->titulo . ($s->user ? ' · ' . $s->user->name : ''),
                'start' => $s->scheduled_at->toIso8601String(),
                'end' => $s->scheduled_at->copy()->addMinutes($s->duracao_minutos)->toIso8601String(),
                'backgroundColor' => $colors[$s->status] ?? $colors['agendada'],
                'borderColor' => $colors[$s->status] ?? $colors['agendada'],
                'url' => route('admin.sessoes.edit', $s),
                'extendedProps' => [
                    'status' => $s->status,
                    'user' => $s->user?->name,
                    'descricao' => $s->descricao,
                    'link_reuniao' => $s->link_reuniao,
                ],
            ];
        }));
    }

    public function datatable(\Illuminate\Http\Request $request): JsonResponse
    {
        $query = Sessao::query()
            ->with('user:id,name,email')
            ->latest('scheduled_at');

        if ($s = $request->query('status'))  $query->where('status', $s);
        if ($de = $request->query('de'))     $query->whereDate('scheduled_at', '>=', $de);
        if ($ate = $request->query('ate'))   $query->whereDate('scheduled_at', '<=', $ate);

        return DataTables::eloquent($query)
            ->addColumn('user_name', fn (Sessao $s) => $s->user?->name ?? '—')
            ->addColumn('scheduled_formatado', fn (Sessao $s) => $s->scheduled_at->format('d/m/Y H:i'))
            ->addColumn('status_badge', function (Sessao $s) {
                $map = ['agendada' => 'primary', 'concluida' => 'success', 'cancelada' => 'secondary'];
                return '<span class="badge bg-label-' . ($map[$s->status] ?? 'secondary') . '">' . ucfirst($s->status) . '</span>';
            })
            ->addColumn('actions', fn (Sessao $s) => $s->id)
            ->addColumn('details', fn (Sessao $s) => [
                'user_name'       => $s->user?->name,
                'titulo'          => $s->titulo,
                'scheduled_at'    => $s->scheduled_at->format('d/m/Y H:i'),
                'duracao_minutos' => $s->duracao_minutos,
                'status'          => ucfirst($s->status),
                'link_reuniao'    => $s->link_reuniao,
                'descricao'       => $s->descricao,
                'notas'           => $s->notas,
            ])
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
