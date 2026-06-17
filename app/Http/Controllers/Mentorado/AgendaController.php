<?php

namespace App\Http\Controllers\Mentorado;

use App\Http\Controllers\Controller;
use App\Models\Sessao;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AgendaController extends Controller
{
    public function index()
    {
        return view('content.mentorado.agenda', [
            'proximas' => Sessao::where('user_id', Auth::id())
                ->where('status', 'agendada')
                ->where('scheduled_at', '>=', now())
                ->orderBy('scheduled_at')
                ->limit(5)
                ->get(),
        ]);
    }

    public function events(Request $request): JsonResponse
    {
        $sessoes = Sessao::where('user_id', Auth::id())
            ->when($request->start, fn ($q) => $q->where('scheduled_at', '>=', $request->start))
            ->when($request->end, fn ($q) => $q->where('scheduled_at', '<=', $request->end))
            ->get();

        return response()->json($sessoes->map(function (Sessao $s) {
            $colors = ['agendada' => '#7367f0', 'concluida' => '#28c76f', 'cancelada' => '#82868b'];
            return [
                'id' => $s->id,
                'title' => $s->titulo,
                'start' => $s->scheduled_at->toIso8601String(),
                'end' => $s->scheduled_at->copy()->addMinutes($s->duracao_minutos)->toIso8601String(),
                'backgroundColor' => $colors[$s->status] ?? '#7367f0',
                'borderColor' => $colors[$s->status] ?? '#7367f0',
                'extendedProps' => [
                    'status' => $s->status,
                    'descricao' => $s->descricao,
                    'link_reuniao' => $s->link_reuniao,
                ],
            ];
        }));
    }

    public function complete(Sessao $sessao): JsonResponse
    {
        abort_unless($sessao->user_id === Auth::id(), 403);
        $sessao->update(['status' => 'concluida']);
        return response()->json(['status' => 'success', 'message' => 'Sessão marcada como concluída.']);
    }

    public function cancel(Sessao $sessao): JsonResponse
    {
        abort_unless($sessao->user_id === Auth::id(), 403);
        if ($sessao->status !== 'agendada') {
            return response()->json(['status' => 'error', 'message' => 'Apenas sessões agendadas podem ser canceladas.'], 422);
        }
        $sessao->update(['status' => 'cancelada']);
        return response()->json(['status' => 'success', 'message' => 'Sessão cancelada.']);
    }
}
