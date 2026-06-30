<?php

namespace Database\Seeders;

use App\Models\Fatura;
use App\Models\PaymentEvent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class PaymentEventSeeder extends Seeder
{
    public function run(): void
    {
        $faker = \Faker\Factory::create('pt_BR');

        // Pega faturas que têm gateway_payment_id (ou seja, passaram por MP)
        Fatura::whereNotNull('gateway_payment_id')
            ->orWhereNotNull('gateway_preference_id')
            ->chunk(50, function ($faturas) use ($faker) {
                foreach ($faturas as $fatura) {
                    // 1 a 3 eventos por fatura (criação, atualização, confirmação)
                    $qtd = rand(1, 3);
                    for ($i = 0; $i < $qtd; $i++) {
                        $tipoEvento = match ($i) {
                            0 => 'payment.created',
                            1 => $fatura->status === 'paga' ? 'payment.updated' : 'payment.pending',
                            default => $fatura->status === 'estornada' ? 'payment.refunded' : 'payment.notification',
                        };

                        PaymentEvent::create([
                            'provider' => 'mercadopago',
                            'provider_event_id' => 'evt_' . Str::random(24),
                            'fatura_id' => $fatura->id,
                            'subscription_id' => $fatura->subscription_id,
                            'payload' => [
                                'action' => $tipoEvento,
                                'api_version' => 'v1',
                                'data' => [
                                    'id' => $fatura->gateway_payment_id ?? Str::random(20),
                                ],
                                'date_created' => $fatura->created_at->copy()->addDays($i)->toIso8601String(),
                                'id' => rand(10000000, 99999999),
                                'live_mode' => false,
                                'type' => 'payment',
                                'user_id' => '123456789',
                                'payment' => [
                                    'status' => match ($fatura->status) {
                                        'paga' => 'approved',
                                        'pendente' => 'pending',
                                        'cancelada' => 'cancelled',
                                        'atrasada' => 'pending',
                                        'estornada' => 'refunded',
                                        default => 'pending',
                                    },
                                    'status_detail' => 'accredited',
                                    'transaction_amount' => (float) $fatura->valor,
                                    'payment_method_id' => $fatura->metodo ?? 'pix',
                                    'payer' => [
                                        'email' => $fatura->payer_email,
                                        'identification' => [
                                            'type' => strlen($fatura->payer_document ?? '') > 14 ? 'CNPJ' : 'CPF',
                                            'number' => $fatura->payer_document,
                                        ],
                                    ],
                                ],
                            ],
                            'processed_at' => $fatura->pago_em ?? $fatura->created_at->copy()->addMinutes(rand(1, 60)),
                            'created_at' => $fatura->created_at->copy()->addDays($i),
                            'updated_at' => $fatura->created_at->copy()->addDays($i),
                        ]);
                    }
                }
            });
    }
}
