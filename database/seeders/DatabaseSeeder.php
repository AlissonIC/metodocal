<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // 1) Fundação: roles e permissions
            RoleSeeder::class,
            PermissionSeeder::class,

            // 2) Catálogos (independem de users)
            PlanSeeder::class,
            ConteudoSeeder::class,
            MaterialSeeder::class,
            EmpresaGuinchoSeeder::class,
            ServicoSeeder::class,
            BancoSeeder::class,
            CompradorSeeder::class,

            // 3) Users + Subscriptions + Faturas
            UserSeeder::class,

            // 4) Domínios que dependem de users + subscriptions
            SessaoSeeder::class,
            ProgressoConteudoSeeder::class,
            ClienteLicenciadoSeeder::class,
            ProcessoSeeder::class,

            // 5) Eventos auxiliares (dependem de faturas + users)
            PaymentEventSeeder::class,
            QueuedNotificationSeeder::class,
        ]);
    }
}
