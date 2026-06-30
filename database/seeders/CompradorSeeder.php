<?php

namespace Database\Seeders;

use App\Models\Comprador;
use Illuminate\Database\Seeder;

class CompradorSeeder extends Seeder
{
    public function run(): void
    {
        $faker = \Faker\Factory::create('pt_BR');

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
