<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QueuedNotification;
use App\Services\NotificationQueueService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class NotificacaoController extends Controller
{
    public function __construct(private NotificationQueueService $service) {}

    public function index(): View
    {
        return view('content.admin.notificacoes.index', [
            'kpi_total' => QueuedNotification::count(),
            'kpi_pendentes' => QueuedNotification::where('status', 'pendente')->count(),
            'kpi_enviadas' => QueuedNotification::where('status', 'enviada')->count(),
            'kpi_falhadas' => QueuedNotification::where('status', 'falhou')->count(),
        ]);
    }

    public function datatable(\Illuminate\Http\Request $request): JsonResponse
    {
        $query = QueuedNotification::query()
            ->with('user:id,name')
            ->latest('created_at');

        if ($s = $request->query('status'))  $query->where('status', $s);
        if ($c = $request->query('channel')) $query->where('channel', $c);
        if ($de = $request->query('de'))     $query->whereDate('created_at', '>=', $de);
        if ($ate = $request->query('ate'))   $query->whereDate('created_at', '<=', $ate);

        $channelIcons = [
            'email' => ['icon' => 'mail', 'color' => 'primary'],
            'whatsapp' => ['icon' => 'brand-whatsapp', 'color' => 'success'],
            'sms' => ['icon' => 'message', 'color' => 'info'],
            'push' => ['icon' => 'bell', 'color' => 'warning'],
        ];

        return DataTables::eloquent($query)
            ->addColumn('channel_cell', function (QueuedNotification $n) use ($channelIcons) {
                $c = $channelIcons[$n->channel] ?? ['icon' => 'message-circle', 'color' => 'secondary'];
                return '<span class="d-inline-flex align-items-center gap-2">'
                    . '<span class="avatar avatar-xs"><span class="avatar-initial rounded-circle bg-label-' . $c['color'] . '"><i class="ti tabler-' . $c['icon'] . ' icon-16px"></i></span></span>'
                    . '<span class="text-capitalize">' . $n->channel . '</span>'
                    . '</span>';
            })
            ->addColumn('destinatario_cell', function (QueuedNotification $n) {
                $main = '<div class="fw-medium text-truncate" style="max-width:220px;" title="' . e($n->to) . '">' . e($n->to) . '</div>';
                if ($n->user) {
                    $main .= '<small class="text-muted">' . e($n->user->name) . '</small>';
                }
                return $main;
            })
            ->addColumn('subject_cell', function (QueuedNotification $n) {
                $subj = $n->subject ?? '—';
                return '<div class="text-truncate" style="max-width:260px;" title="' . e($subj) . '">' . e($subj) . '</div>';
            })
            ->addColumn('data_cell', function (QueuedNotification $n) {
                $abs = $n->created_at->format('d/m/Y H:i');
                $rel = $n->created_at->diffForHumans(['short' => true]);
                $cell = '<div class="text-nowrap" title="' . $abs . '">' . $rel . '</div>';
                if ($n->sent_at) {
                    $cell .= '<small class="text-success">enviada ' . $n->sent_at->diffForHumans(['short' => true]) . '</small>';
                }
                return $cell;
            })
            ->addColumn('status_badge', function (QueuedNotification $n) {
                $map = [
                    'pendente' => ['warning', 'clock'],
                    'enviando' => ['info', 'loader'],
                    'enviada' => ['success', 'check'],
                    'falhou' => ['danger', 'alert-circle'],
                    'cancelada' => ['secondary', 'ban'],
                ];
                [$color, $icon] = $map[$n->status] ?? ['secondary', 'circle'];
                $max = NotificationQueueService::MAX_ATTEMPTS;
                $badge = '<span class="badge bg-label-' . $color . ' d-inline-flex align-items-center gap-1"><i class="ti tabler-' . $icon . ' icon-14px"></i>' . ucfirst($n->status) . '</span>';

                if ($n->status === 'pendente' && $n->next_attempt_at) {
                    $badge .= '<div><small class="text-muted">retry ' . $n->next_attempt_at->diffForHumans(['short' => true]) . '</small></div>';
                } elseif ($n->status === 'falhou') {
                    $badge .= '<div><small class="text-muted">' . $n->attempts . '/' . $max . ' tentativas</small></div>';
                }
                return $badge;
            })
            ->addColumn('actions', fn (QueuedNotification $n) => $n->id)
            ->addColumn('details', fn (QueuedNotification $n) => [
                'channel'         => $n->channel,
                'to'              => $n->to,
                'user_name'       => $n->user?->name,
                'subject'         => $n->subject,
                'body'            => $n->body,
                'status'          => $n->status,
                'attempts'        => $n->attempts,
                'last_error'      => $n->last_error,
                'next_attempt_at' => $n->next_attempt_at?->format('d/m/Y H:i'),
                'sent_at'         => $n->sent_at?->format('d/m/Y H:i'),
                'created_at'      => $n->created_at->format('d/m/Y H:i'),
            ])
            ->rawColumns(['channel_cell', 'destinatario_cell', 'subject_cell', 'data_cell', 'status_badge'])
            ->toJson();
    }

    public function show(QueuedNotification $notificacao): View
    {
        $notificacao->load('user');
        return view('content.admin.notificacoes.show', ['n' => $notificacao]);
    }

    public function preview(QueuedNotification $notificacao)
    {
        // Renderiza o corpo cru para ser carregado num iframe seguro (srcdoc).
        // Não passa pelo layout do painel — é o HTML do email exato.
        return response($notificacao->body, 200)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('X-Frame-Options', 'SAMEORIGIN');
    }

    public function resend(QueuedNotification $notificacao): JsonResponse
    {
        if ($notificacao->status === 'enviando') {
            return response()->json(['status' => 'error', 'message' => 'A notificação já está sendo enviada.'], 422);
        }
        // forceRetry zera attempts → reenvio manual dá 3 novas chances
        $ok = $this->service->forceRetry($notificacao);

        return response()->json([
            'status' => $ok ? 'success' : 'error',
            'message' => $ok ? 'Notificação reenviada.' : 'Falha no reenvio — veja o log e o histórico.',
        ], $ok ? 200 : 422);
    }

    public function cancel(QueuedNotification $notificacao): JsonResponse
    {
        if (! in_array($notificacao->status, ['pendente', 'falhou'], true)) {
            return response()->json(['status' => 'error', 'message' => 'Só é possível cancelar notificações pendentes ou que falharam.'], 422);
        }
        $notificacao->update(['status' => 'cancelada']);
        return response()->json(['status' => 'success', 'message' => 'Notificação cancelada.']);
    }
}
