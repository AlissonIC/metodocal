<?php

namespace App\Http\Controllers;

use App\Models\Fatura;
use App\Models\PaymentEvent;
use App\Services\FaturaService;
use App\Services\MercadoPagoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        private MercadoPagoService $mp,
        private FaturaService $faturaService,
    ) {}

    /**
     * Endpoint público chamado pelo Mercado Pago.
     *
     * Idempotência: cada evento (provider_event_id) é gravado em payment_events
     * com UNIQUE — se o mesmo evento chega duas vezes, retornamos 200 e ignoramos.
     */
    public function mercadopago(Request $request): JsonResponse
    {
        $payload = $request->all();
        $type = $payload['type'] ?? $payload['topic'] ?? null;
        $providerEventId = (string) ($payload['id'] ?? $payload['data']['id'] ?? '');

        if (! $providerEventId) {
            return response()->json(['status' => 'ignored', 'reason' => 'missing event id'], 200);
        }

        $alreadyProcessed = PaymentEvent::where('provider', 'mercadopago')
            ->where('provider_event_id', $providerEventId)
            ->whereNotNull('processed_at')
            ->exists();
        if ($alreadyProcessed) {
            return response()->json(['status' => 'ok', 'reason' => 'duplicate ignored'], 200);
        }

        DB::beginTransaction();
        try {
            $event = PaymentEvent::firstOrCreate(
                ['provider' => 'mercadopago', 'provider_event_id' => $providerEventId],
                ['payload' => $payload]
            );

            if (in_array($type, ['payment', 'payment.created', 'payment.updated'], true)) {
                $this->handlePayment((string) ($payload['data']['id'] ?? $providerEventId), $event);
            } else {
                Log::info('MP webhook: tipo não tratado', ['type' => $type, 'event_id' => $providerEventId]);
            }

            $event->update(['processed_at' => now()]);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('MP webhook error', ['error' => $e->getMessage(), 'event_id' => $providerEventId]);
            return response()->json(['status' => 'error'], 500);
        }

        return response()->json(['status' => 'ok'], 200);
    }

    private function handlePayment(string $mpPaymentId, PaymentEvent $event): void
    {
        $payment = $this->mp->fetchPayment($mpPaymentId);
        if (! $payment) {
            return;
        }

        $fatura = $this->resolveFatura($payment);
        if (! $fatura) {
            Log::warning('MP webhook: fatura não encontrada', ['payment_id' => $payment['id']]);
            return;
        }

        $event->update([
            'subscription_id' => $fatura->subscription_id,
            'fatura_id' => $fatura->id,
        ]);

        // Guarda dados não-sensíveis do pagador na fatura
        $fatura->fill([
            'payer_name' => $payment['payer']['name'] ?? $fatura->payer_name,
            'payer_email' => $payment['payer']['email'] ?? $fatura->payer_email,
            'payer_document' => $payment['payer']['document'] ?? $fatura->payer_document,
            'payer_address' => $payment['address'] ?? $fatura->payer_address,
            'payer_info' => $payment['full'] ?? $fatura->payer_info,
        ])->save();

        if ($payment['status'] === 'approved') {
            $this->faturaService->marcarComoPaga(
                $fatura,
                gatewayPaymentId: $payment['id'],
                metodo: MercadoPagoService::mapMethod($payment['method']) ?? 'manual'
            );
        } elseif (in_array($payment['status'], ['rejected', 'cancelled'], true)) {
            $this->faturaService->marcarComoCancelada($fatura);
        } elseif ($payment['status'] === 'refunded') {
            $this->faturaService->marcarComoEstornada($fatura);
        }
    }

    private function resolveFatura(array $payment): ?Fatura
    {
        if (! empty($payment['external_reference']) && str_starts_with($payment['external_reference'], 'fatura_')) {
            $id = (int) substr($payment['external_reference'], 7);
            if ($id) {
                return Fatura::find($id);
            }
        }

        if (! empty($payment['id'])) {
            return Fatura::where('gateway_payment_id', $payment['id'])->first();
        }

        return null;
    }
}
