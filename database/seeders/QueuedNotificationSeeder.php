<?php

namespace Database\Seeders;

use App\Models\Fatura;
use App\Models\QueuedNotification;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class QueuedNotificationSeeder extends Seeder
{
    public function run(): void
    {
        $faker = \Faker\Factory::create('pt_BR');

        $templates = [
            ['channel' => 'email', 'subject' => 'Bem-vindo ao MetodoCal',          'body' => 'Olá {nome}, sua conta foi criada com sucesso!'],
            ['channel' => 'email', 'subject' => 'Confirme seu e-mail',             'body' => 'Olá {nome}, clique no link para confirmar seu e-mail.'],
            ['channel' => 'email', 'subject' => 'Sua fatura está disponível',      'body' => 'Olá {nome}, sua fatura no valor de R$ {valor} está disponível.'],
            ['channel' => 'email', 'subject' => 'Pagamento confirmado',            'body' => 'Olá {nome}, recebemos seu pagamento de R$ {valor}.'],
            ['channel' => 'email', 'subject' => 'Lembrete: fatura próxima do vencimento', 'body' => 'Olá {nome}, sua fatura vence em 3 dias.'],
            ['channel' => 'email', 'subject' => 'Fatura em atraso',                'body' => 'Olá {nome}, sua fatura está em atraso.'],
            ['channel' => 'email', 'subject' => 'Estorno processado',              'body' => 'Olá {nome}, seu estorno foi processado.'],
            ['channel' => 'whatsapp', 'subject' => null,                           'body' => 'Olá {nome}, lembrete de sessão de mentoria amanhã.'],
            ['channel' => 'sms', 'subject' => null,                                'body' => 'MetodoCal: código de verificação {codigo}'],
        ];

        $users = User::limit(20)->inRandomOrder()->get();

        foreach ($users as $user) {
            $qtd = rand(2, 8);
            for ($i = 0; $i < $qtd; $i++) {
                $tpl = $faker->randomElement($templates);
                $createdAt = Carbon::now()->subDays(rand(0, 90))->subHours(rand(0, 23));

                $r = rand(1, 100);
                if ($r <= 70) {
                    $status = 'enviada';
                    $sentAt = $createdAt->copy()->addMinutes(rand(1, 30));
                    $attempts = 1;
                    $nextAttempt = null;
                    $lastError = null;
                } elseif ($r <= 85) {
                    $status = 'pendente';
                    $sentAt = null;
                    $attempts = 0;
                    $nextAttempt = Carbon::now()->addMinutes(rand(1, 60));
                    $lastError = null;
                } elseif ($r <= 95) {
                    $status = 'falhou';
                    $sentAt = null;
                    $attempts = rand(2, 5);
                    $nextAttempt = null;
                    $lastError = $faker->randomElement([
                        'SMTP connection timeout',
                        'Invalid recipient address',
                        'WhatsApp template not approved',
                        'Quota exceeded',
                        'Connection refused',
                    ]);
                } else {
                    $status = 'cancelada';
                    $sentAt = null;
                    $attempts = 0;
                    $nextAttempt = null;
                    $lastError = null;
                }

                $body = str_replace(
                    ['{nome}', '{valor}', '{codigo}'],
                    [$user->name, number_format(rand(100, 5000) / 10, 2, ',', '.'), (string) rand(100000, 999999)],
                    $tpl['body']
                );

                $to = match ($tpl['channel']) {
                    'whatsapp', 'sms' => $user->phone ?? '+5511999999999',
                    default => $user->email,
                };

                QueuedNotification::create([
                    'channel' => $tpl['channel'],
                    'to' => $to,
                    'subject' => $tpl['subject'],
                    'body' => $body,
                    'data' => ['template' => $tpl['subject'] ?? 'sms_code', 'user_id' => $user->id],
                    'status' => $status,
                    'attempts' => $attempts,
                    'next_attempt_at' => $nextAttempt,
                    'last_error' => $lastError,
                    'sent_at' => $sentAt,
                    'user_id' => $user->id,
                    'related_type' => null,
                    'related_id' => null,
                    'created_at' => $createdAt,
                    'updated_at' => $sentAt ?? $createdAt,
                ]);
            }
        }
    }
}
