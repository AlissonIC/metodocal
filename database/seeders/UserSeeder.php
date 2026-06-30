<?php

namespace Database\Seeders;

use App\Models\Fatura;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $faker = \Faker\Factory::create('pt_BR');

        // ===== ADMIN (fixo, demo) =====
        $admin = User::firstOrCreate(
            ['email' => 'admin@metodocal.local'],
            [
                'name' => 'Administrador Demo',
                'password' => Hash::make('admin@2026'),
                'phone' => $faker->cellphoneNumber(),
                'cpf_cnpj' => $faker->cpf(),
                'status' => 'ativo',
                'email_verified_at' => now()->subMonths(rand(2, 12)),
                'last_login_at' => now()->subHours(rand(1, 48)),
            ]
        );
        $admin->syncRoles(['admin']);

        // ===== MENTORADO DEMO (fixo) =====
        $mentDemo = User::firstOrCreate(
            ['email' => 'mentorado@metodocal.local'],
            [
                'name' => 'Mentorado Demo',
                'password' => Hash::make('mentorado@2026'),
                'phone' => $faker->cellphoneNumber(),
                'cpf_cnpj' => $faker->cpf(),
                'status' => 'ativo',
                'email_verified_at' => now()->subMonths(rand(1, 6)),
                'last_login_at' => now()->subHours(rand(1, 72)),
            ]
        );
        $mentDemo->syncRoles(['mentorado']);
        $this->giveSubscription($mentDemo, 'mentorado', 'ativa');

        // ===== LICENCIADO DEMO (fixo) =====
        $licDemo = User::firstOrCreate(
            ['email' => 'licenciado@metodocal.local'],
            [
                'name' => 'Licenciado Demo',
                'password' => Hash::make('licenciado@2026'),
                'phone' => $faker->cellphoneNumber(),
                'cpf_cnpj' => $faker->cpf(),
                'status' => 'ativo',
                'email_verified_at' => now()->subMonths(rand(1, 6)),
                'last_login_at' => now()->subHours(rand(1, 72)),
            ]
        );
        $licDemo->syncRoles(['licenciado']);
        $this->giveSubscription($licDemo, 'licenciado', 'ativa');

        // ===== ADMINS EXTRAS =====
        for ($i = 0; $i < 2; $i++) {
            $u = $this->createBaseUser($faker, $faker->safeEmail(), 'ativo');
            $u->syncRoles(['admin']);
        }

        // ===== MENTORADOS (25) — mix de statuses e subscriptions =====
        for ($i = 0; $i < 25; $i++) {
            $email = 'mentorado.' . Str::slug($faker->unique()->firstName()) . $i . '@example.com';
            $status = $faker->randomElement(['ativo', 'ativo', 'ativo', 'ativo', 'inativo', 'bloqueado']);
            $u = $this->createBaseUser($faker, $email, $status);
            $u->syncRoles(['mentorado']);

            // 75% têm subscription ativa, 15% suspensa/cancelada, 10% sem
            $chance = rand(1, 100);
            if ($chance <= 75) {
                $this->giveSubscription($u, 'mentorado', 'ativa');
            } elseif ($chance <= 90) {
                $this->giveSubscription($u, 'mentorado', $faker->randomElement(['suspensa', 'cancelada']));
            }
        }

        // ===== LICENCIADOS (12) =====
        for ($i = 0; $i < 12; $i++) {
            $email = 'licenciado.' . Str::slug($faker->unique()->lastName()) . $i . '@example.com';
            $status = $faker->randomElement(['ativo', 'ativo', 'ativo', 'inativo']);
            $u = $this->createBaseUser($faker, $email, $status);
            $u->syncRoles(['licenciado']);

            $chance = rand(1, 100);
            if ($chance <= 80) {
                $this->giveSubscription($u, 'licenciado', 'ativa');
            } elseif ($chance <= 92) {
                $this->giveSubscription($u, 'licenciado', $faker->randomElement(['suspensa', 'cancelada']));
            }
        }
    }

    private function createBaseUser(\Faker\Generator $faker, string $email, string $status): User
    {
        $createdAt = Carbon::now()->subDays(rand(7, 720));

        return User::create([
            'name' => $faker->name(),
            'email' => $email,
            'password' => Hash::make('senha123'),
            'phone' => $faker->cellphoneNumber(),
            'cpf_cnpj' => rand(0, 100) < 70 ? $faker->cpf() : $faker->cnpj(),
            'status' => $status,
            'email_verified_at' => rand(0, 100) < 85 ? $createdAt->copy()->addDays(rand(0, 3)) : null,
            'last_login_at' => $status === 'ativo' ? Carbon::now()->subHours(rand(1, 24 * 30)) : null,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    private function giveSubscription(User $user, string $tipo, string $status): void
    {
        $plan = Plan::where('tipo', $tipo)
            ->where('ativo', true)
            ->inRandomOrder()
            ->first();
        if (! $plan) {
            return;
        }

        $startedAt = Carbon::now()->subDays(rand(15, 365));

        $endsAt = match ($plan->recorrencia) {
            'anual' => $startedAt->copy()->addYear(),
            'vitalicio' => null,
            default => $startedAt->copy()->addMonth(),
        };

        $canceledAt = $status === 'cancelada' ? $startedAt->copy()->addDays(rand(7, 60)) : null;

        $sub = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => $status,
            'started_at' => $startedAt,
            'ends_at' => $endsAt,
            'canceled_at' => $canceledAt,
            'gateway_subscription_id' => 'mp_sub_' . Str::random(16),
        ]);

        if ($status === 'ativa') {
            $user->forceFill(['current_subscription_id' => $sub->id])->save();
        }

        $this->seedFaturas($user, $sub);
    }

    private function seedFaturas(User $user, Subscription $sub): void
    {
        $faker = \Faker\Factory::create('pt_BR');
        $start = $sub->started_at->copy();
        $now = Carbon::now();
        $valor = $sub->plan->preco;

        // Quantos ciclos já passaram?
        $ciclos = match ($sub->plan->recorrencia) {
            'anual' => max(1, $start->diffInYears($now) + 1),
            default => max(1, $start->diffInMonths($now) + 1),
        };
        $ciclos = min($ciclos, 12); // cap

        for ($i = 0; $i < $ciclos; $i++) {
            $venc = match ($sub->plan->recorrencia) {
                'anual' => $start->copy()->addYears($i)->addDays(5),
                default => $start->copy()->addMonths($i)->addDays(5),
            };

            $jaPassou = $venc->lt($now);

            // Status realista: passados → maioria paga / alguns atrasados/cancelados; futuro → pendente
            if (! $jaPassou) {
                $status = 'pendente';
                $pagoEm = null;
                $estornadaEm = null;
            } else {
                $r = rand(1, 100);
                if ($r <= 80) {
                    $status = 'paga';
                    $pagoEm = $venc->copy()->subDays(rand(0, 5));
                    $estornadaEm = null;
                } elseif ($r <= 90) {
                    $status = 'atrasada';
                    $pagoEm = null;
                    $estornadaEm = null;
                } elseif ($r <= 95) {
                    $status = 'cancelada';
                    $pagoEm = null;
                    $estornadaEm = null;
                } else {
                    $status = 'estornada';
                    $pagoEm = $venc->copy()->subDays(2);
                    $estornadaEm = $pagoEm->copy()->addDays(rand(1, 30));
                }
            }

            $metodo = $faker->randomElement(['pix', 'boleto', 'cartao', 'cartao', 'manual']);

            Fatura::create([
                'subscription_id' => $sub->id,
                'user_id' => $user->id,
                'plan_id' => $sub->plan_id,
                'valor' => $valor,
                'vencimento' => $venc->toDateString(),
                'status' => $status,
                'pago_em' => $pagoEm,
                'estornada_em' => $estornadaEm,
                'metodo' => $status === 'pendente' ? null : $metodo,
                'gateway_payment_id' => $status === 'pendente' ? null : 'mp_pay_' . Str::random(20),
                'gateway_preference_id' => 'mp_pref_' . Str::random(20),
                'gateway_refund_id' => $status === 'estornada' ? 'mp_ref_' . Str::random(20) : null,
                'link_pagamento' => $status === 'pendente' ? 'https://mpago.la/' . Str::random(8) : null,
                'qr_code' => $metodo === 'pix' && $status === 'pendente' ? '00020126580014BR.GOV.BCB.PIX...' . Str::random(40) : null,
                'payer_name' => $user->name,
                'payer_email' => $user->email,
                'payer_document' => $user->cpf_cnpj,
                'payer_address' => [
                    'zip_code' => $faker->postcode(),
                    'street_name' => $faker->streetName(),
                    'street_number' => (string) rand(1, 9999),
                    'neighborhood' => $faker->citySuffix(),
                    'city' => $faker->city(),
                    'federal_unit' => $faker->stateAbbr(),
                ],
                'payer_info' => [
                    'phone' => $user->phone,
                    'ip' => $faker->ipv4(),
                ],
                'created_at' => $venc->copy()->subDays(rand(5, 10)),
                'updated_at' => $pagoEm ?? $estornadaEm ?? $venc,
            ]);
        }
    }
}
