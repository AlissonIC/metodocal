<?php

namespace Database\Seeders;

use App\Models\Comprador;
use App\Models\User;
use Illuminate\Database\Seeder;

class CompradorSeeder extends Seeder
{
    public function run(): void
    {
        $faker = \Faker\Factory::create('pt_BR');

        // 1) Comprador demo (comprador@metodocal.com.br) — cria o registro na
        //    tabela `compradores` linkado ao User criado no UserSeeder.
        $compDemoUser = User::where('email', 'comprador@metodocal.com.br')->first();
        if ($compDemoUser) {
            Comprador::updateOrCreate(
                ['user_id' => $compDemoUser->id],
                [
                    'nome' => $compDemoUser->name,
                    'tipo_documento' => 'cpf',
                    'documento' => $compDemoUser->cpf_cnpj ?: $faker->cpf(false),
                    'email' => $compDemoUser->email,
                    'telefone' => $compDemoUser->phone,
                    'observacoes' => 'Comprador demo — vinculado a alguns processos pelo ProcessoSeeder.',
                    'ativo' => true,
                ]
            );
        }

        // 2) Compradores fake (6) já com User vinculado (criados no UserSeeder com role 'comprador')
        $compradoresUsers = User::role('comprador')
            ->where('email', '!=', 'comprador@metodocal.com.br')
            ->get();

        foreach ($compradoresUsers as $u) {
            Comprador::updateOrCreate(
                ['user_id' => $u->id],
                [
                    'nome' => $u->name,
                    'tipo_documento' => 'cpf',
                    'documento' => $u->cpf_cnpj ?: $faker->cpf(false),
                    'email' => $u->email,
                    'telefone' => $u->phone,
                    'observacoes' => rand(0, 100) < 50 ? $faker->sentence(rand(6, 12)) : null,
                    'ativo' => true,
                ]
            );
        }

        // 3) Compradores fake sem login (só cadastro interno) — 12 registros
        for ($i = 0; $i < 12; $i++) {
            $tipo = $faker->randomElement(['cpf', 'cpf', 'cpf', 'cnpj']);
            $documento = $tipo === 'cpf' ? $faker->cpf(false) : $faker->cnpj(false);

            Comprador::updateOrCreate(
                ['documento' => $documento],
                [
                    'nome' => $tipo === 'cpf' ? $faker->name() : $faker->company(),
                    'tipo_documento' => $tipo,
                    'email' => $faker->safeEmail(),
                    'telefone' => $faker->cellphoneNumber(),
                    'observacoes' => rand(0, 100) < 50 ? $faker->sentence(rand(6, 12)) : null,
                    'ativo' => true,
                ]
            );
        }
    }
}
