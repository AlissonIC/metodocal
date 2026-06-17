<?php

namespace App\Services;

use App\Models\Fatura;
use Illuminate\Support\Facades\Log;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Client\PaymentRefund\PaymentRefundClient;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Exceptions\MPApiException;
use MercadoPago\MercadoPagoConfig;

/**
 * Wrapper para o SDK do Mercado Pago.
 *
 * Em ambiente sem credenciais configuradas (MP_ACCESS_TOKEN vazio), o service
 * retorna links nulos e o sistema entra em "modo manual": a fatura é criada como
 * pendente e o admin marca como paga em /painel/financeiro.
 */
class MercadoPagoService
{
    private bool $configured = false;

    public function __construct()
    {
        $token = config('services.mercadopago.access_token');
        if (! empty($token)) {
            MercadoPagoConfig::setAccessToken($token);
            $this->configured = true;
        }
    }

    public function isConfigured(): bool
    {
        return $this->configured;
    }

    /**
     * Cria uma preference para checkout e devolve [init_point, preference_id]
     * ou null se o gateway não estiver configurado.
     *
     * @return array{init_point: string, preference_id: string}|null
     */
    public function createPreference(Fatura $fatura): ?array
    {
        if (! $this->configured) {
            return null;
        }

        try {
            $client = new PreferenceClient();
            $preference = $client->create([
                'items' => [[
                    'id' => (string) $fatura->plan_id,
                    'title' => $fatura->plan->nome,
                    'description' => $fatura->plan->descricao ?? '',
                    'quantity' => 1,
                    'currency_id' => 'BRL',
                    'unit_price' => (float) $fatura->valor,
                ]],
                'payer' => [
                    'email' => $fatura->user->email,
                    'name' => $fatura->user->name,
                ],
                'back_urls' => [
                    'success' => config('services.mercadopago.success_url'),
                    'failure' => config('services.mercadopago.failure_url'),
                    'pending' => config('services.mercadopago.pending_url'),
                ],
                'auto_return' => 'approved',
                'notification_url' => config('services.mercadopago.notification_url'),
                'external_reference' => 'fatura_' . $fatura->id,
                'statement_descriptor' => 'METODOCAL',
            ]);

            return [
                'init_point' => $preference->init_point,
                'preference_id' => $preference->id,
            ];
        } catch (MPApiException $e) {
            Log::error('MP createPreference failed', [
                'fatura_id' => $fatura->id,
                'error' => $e->getMessage(),
                'response' => $e->getApiResponse()?->getContent(),
            ]);
            return null;
        }
    }

    /**
     * Consulta um pagamento no Mercado Pago.
     * Retorna array com dados não-sensíveis do pagador (sem dados de cartão).
     */
    public function fetchPayment(string $paymentId): ?array
    {
        if (! $this->configured) {
            return null;
        }

        try {
            $client = new PaymentClient();
            $p = $client->get($paymentId);

            $payerName = trim(
                ($p->payer->first_name ?? '') . ' ' . ($p->payer->last_name ?? '')
            ) ?: null;
            $payerEmail = $p->payer->email ?? null;
            $payerDoc = isset($p->payer->identification)
                ? trim(($p->payer->identification->type ?? '') . ' ' . ($p->payer->identification->number ?? ''))
                : null;

            $address = $p->additional_info->payer->address ?? null;
            $addressArray = $address ? [
                'street_name' => $address->street_name ?? null,
                'street_number' => $address->street_number ?? null,
                'zip_code' => $address->zip_code ?? null,
            ] : null;

            return [
                'id' => (string) $p->id,
                'status' => $p->status,
                'status_detail' => $p->status_detail ?? null,
                'external_reference' => $p->external_reference ?? null,
                'method' => $p->payment_type_id ?? null,
                'amount' => (float) ($p->transaction_amount ?? 0),
                'payer' => [
                    'name' => $payerName,
                    'email' => $payerEmail,
                    'document' => $payerDoc,
                ],
                'address' => $addressArray,
                'full' => json_decode(json_encode($p), true),
            ];
        } catch (MPApiException $e) {
            Log::error('MP fetchPayment failed', ['payment_id' => $paymentId, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Solicita estorno (refund) total ou parcial do pagamento.
     * Retorna ['id' => ..., 'status' => ...] ou null em caso de falha/sem config.
     */
    public function refundPayment(string $paymentId, ?float $amount = null): ?array
    {
        if (! $this->configured) {
            return null;
        }

        try {
            $client = new PaymentRefundClient();
            $refund = $amount === null
                ? $client->refundTotal($paymentId)
                : $client->refundPartial($paymentId, $amount);

            return [
                'id' => (string) ($refund->id ?? ''),
                'status' => $refund->status ?? 'unknown',
                'amount' => (float) ($refund->amount ?? 0),
            ];
        } catch (MPApiException $e) {
            Log::error('MP refundPayment failed', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
                'response' => $e->getApiResponse()?->getContent(),
            ]);
            return null;
        }
    }

    public static function mapMethod(?string $mpMethod): ?string
    {
        return match ($mpMethod) {
            'pix', 'bank_transfer' => 'pix',
            'ticket', 'atm' => 'boleto',
            'credit_card', 'debit_card' => 'cartao',
            default => null,
        };
    }
}
