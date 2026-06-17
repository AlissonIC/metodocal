<?php

namespace App\Services;

use App\Mail\QueuedEmail;
use App\Models\QueuedNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Fila de notificações controlada pelo banco.
 *
 * Estratégia de retry: até 3 tentativas com backoff (1ª tentativa imediata,
 * 2ª após 1min, 3ª após 5min). Depois disso → status "falhou" definitivo.
 */
class NotificationQueueService
{
    /** Número máximo de tentativas antes de marcar como "falhou" definitivo */
    public const MAX_ATTEMPTS = 3;

    /**
     * Delay em segundos antes da próxima tentativa, dado o número de tentativas
     * já feitas. Ex.: depois da 1ª falha → 60s, depois da 2ª → 300s (5min).
     */
    public const BACKOFF_SECONDS = [
        1 => 60,
        2 => 300,
    ];

    public function queueEmail(
        string $to,
        string $subject,
        string $body,
        ?User $user = null,
        ?Model $related = null,
        ?array $data = null,
    ): QueuedNotification {
        return QueuedNotification::create([
            'channel' => 'email',
            'to' => $to,
            'subject' => $subject,
            'body' => $body,
            'data' => $data,
            'status' => 'pendente',
            'user_id' => $user?->id,
            'related_type' => $related?->getMorphClass(),
            'related_id' => $related?->getKey(),
        ]);
    }

    public function queueEmailFromView(
        string $to,
        string $subject,
        string $view,
        array $viewData = [],
        ?User $user = null,
        ?Model $related = null,
    ): QueuedNotification {
        $body = view($view, $viewData)->render();
        return $this->queueEmail($to, $subject, $body, $user, $related, $viewData);
    }

    public function queueWhatsapp(
        string $to,
        string $body,
        ?User $user = null,
        ?Model $related = null,
    ): QueuedNotification {
        return QueuedNotification::create([
            'channel' => 'whatsapp',
            'to' => $to,
            'body' => $body,
            'status' => 'pendente',
            'user_id' => $user?->id,
            'related_type' => $related?->getMorphClass(),
            'related_id' => $related?->getKey(),
        ]);
    }

    /**
     * Processa N notificações pendentes em sequência. Pega apenas aquelas que
     * já podem ser tentadas agora (next_attempt_at <= now() ou nulo).
     */
    public function processPending(int $limit = 20): array
    {
        $enviadas = 0;
        $falhadas = 0;

        QueuedNotification::where('status', 'pendente')
            ->where(function ($q) {
                $q->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', now());
            })
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->each(function (QueuedNotification $n) use (&$enviadas, &$falhadas) {
                if ($this->processOne($n)) {
                    $enviadas++;
                } else {
                    $falhadas++;
                }
            });

        return [$enviadas, $falhadas];
    }

    /**
     * Processa uma notificação específica. Em caso de falha, aplica backoff
     * e reagenda enquanto attempts < MAX_ATTEMPTS; depois disso marca falhou.
     */
    public function processOne(QueuedNotification $n): bool
    {
        $n->update([
            'status' => 'enviando',
            'attempts' => $n->attempts + 1,
            'next_attempt_at' => null,
        ]);

        try {
            match ($n->channel) {
                'email' => $this->sendEmail($n),
                'whatsapp' => $this->sendWhatsapp($n),
                default => throw new \RuntimeException("Canal '{$n->channel}' não suportado."),
            };

            $n->update([
                'status' => 'enviada',
                'sent_at' => now(),
                'last_error' => null,
            ]);
            return true;
        } catch (\Throwable $e) {
            Log::error('NotificationQueue: falha ao enviar', [
                'id' => $n->id,
                'channel' => $n->channel,
                'to' => $n->to,
                'attempt' => $n->attempts,
                'error' => $e->getMessage(),
            ]);

            $this->scheduleRetryOrFail($n, $e->getMessage());
            return false;
        }
    }

    /**
     * Quando o reenvio é forçado pelo admin, zera attempts para dar 3 novas chances.
     */
    public function forceRetry(QueuedNotification $n): bool
    {
        $n->update([
            'attempts' => 0,
            'status' => 'pendente',
            'next_attempt_at' => null,
            'last_error' => null,
        ]);
        return $this->processOne($n->fresh());
    }

    private function scheduleRetryOrFail(QueuedNotification $n, string $error): void
    {
        if ($n->attempts < self::MAX_ATTEMPTS) {
            $delay = self::BACKOFF_SECONDS[$n->attempts] ?? end(self::BACKOFF_SECONDS);
            $n->update([
                'status' => 'pendente',
                'last_error' => $error,
                'next_attempt_at' => now()->addSeconds($delay),
            ]);
            return;
        }

        $n->update([
            'status' => 'falhou',
            'last_error' => $error,
            'next_attempt_at' => null,
        ]);
    }

    private function sendEmail(QueuedNotification $n): void
    {
        Mail::to($n->to)->send(new QueuedEmail(
            subjectLine: $n->subject ?? '(sem assunto)',
            htmlBody: $n->body,
        ));
    }

    private function sendWhatsapp(QueuedNotification $n): void
    {
        Log::info('NotificationQueue: WhatsApp (stub)', [
            'id' => $n->id,
            'to' => $n->to,
            'body' => mb_substr($n->body, 0, 100),
        ]);
    }
}
