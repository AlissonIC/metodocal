<?php

namespace Database\Seeders;

use App\Models\Fatura;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@metodocal.local'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('admin@2026'),
                'status' => 'ativo',
                'email_verified_at' => now(),
            ]
        );
        $admin->syncRoles(['admin']);

        $mentorado = User::firstOrCreate(
            ['email' => 'mentorado@metodocal.local'],
            [
                'name' => 'Mentorado Demo',
                'password' => Hash::make('mentorado@2026'),
                'status' => 'ativo',
                'email_verified_at' => now(),
            ]
        );
        $mentorado->syncRoles(['mentorado']);

        $licenciado = User::firstOrCreate(
            ['email' => 'licenciado@metodocal.local'],
            [
                'name' => 'Licenciado Demo',
                'password' => Hash::make('licenciado@2026'),
                'status' => 'ativo',
                'email_verified_at' => now(),
            ]
        );
        $licenciado->syncRoles(['licenciado']);

        // Atribuir um plano demo a cada cliente
        $planoMentorado = Plan::where('tipo', 'mentorado')->orderBy('preco')->first();
        $planoLicenciado = Plan::where('tipo', 'licenciado')->orderBy('preco')->first();

        if ($planoMentorado && ! $mentorado->currentSubscription) {
            $sub = Subscription::create([
                'user_id' => $mentorado->id,
                'plan_id' => $planoMentorado->id,
                'status' => 'ativa',
                'started_at' => now(),
                'ends_at' => now()->addMonth(),
            ]);
            $mentorado->forceFill(['current_subscription_id' => $sub->id])->save();
            $this->seedFaturas($mentorado, $sub);
        }

        if ($planoLicenciado && ! $licenciado->currentSubscription) {
            $sub = Subscription::create([
                'user_id' => $licenciado->id,
                'plan_id' => $planoLicenciado->id,
                'status' => 'ativa',
                'started_at' => now(),
                'ends_at' => now()->addMonth(),
            ]);
            $licenciado->forceFill(['current_subscription_id' => $sub->id])->save();
            $this->seedFaturas($licenciado, $sub);
        }
    }

    private function seedFaturas(User $user, Subscription $sub): void
    {
        // Fatura paga do mês anterior
        Fatura::create([
            'subscription_id' => $sub->id,
            'user_id' => $user->id,
            'plan_id' => $sub->plan_id,
            'valor' => $sub->plan->preco,
            'vencimento' => now()->subMonth()->addDays(5)->toDateString(),
            'status' => 'paga',
            'pago_em' => now()->subMonth()->addDays(5),
            'metodo' => 'pix',
            'gateway_payment_id' => 'demo_' . uniqid(),
        ]);

        // Fatura pendente do mês atual
        Fatura::create([
            'subscription_id' => $sub->id,
            'user_id' => $user->id,
            'plan_id' => $sub->plan_id,
            'valor' => $sub->plan->preco,
            'vencimento' => now()->addDays(5)->toDateString(),
            'status' => 'pendente',
        ]);
    }
}
