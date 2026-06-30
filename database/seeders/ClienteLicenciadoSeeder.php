<?php

namespace Database\Seeders;

use App\Models\ClienteLicenciado;
use App\Models\Comissao;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ClienteLicenciadoSeeder extends Seeder
{
    public function run(): void
    {
        $faker = \Faker\Factory::create('pt_BR');

        // Pra cada licenciado, criar 5 a 20 clientes com comissões
        $licenciados = User::role('licenciado')->get();

        foreach ($licenciados as $licenciado) {
            $qtdClientes = rand(5, 20);

            for ($i = 0; $i < $qtdClientes; $i++) {
                $isPessoaJuridica = rand(0, 100) < 40;

                $cliente = ClienteLicenciado::create([
                    'licensed_by_user_id' => $licenciado->id,
                    'nome' => $isPessoaJuridica
                        ? $faker->company()
                        : $faker->name(),
                    'email' => $faker->safeEmail(),
                    'telefone' => $faker->cellphoneNumber(),
                    'cpf_cnpj' => $isPessoaJuridica ? $faker->cnpj() : $faker->cpf(),
                    'endereco' => $faker->streetAddress() . ', ' . $faker->city() . '/' . $faker->stateAbbr(),
                    'status' => $faker->randomElement(['lead', 'lead', 'ativo', 'ativo', 'ativo', 'ativo', 'perdido']),
                    'notas' => rand(0, 100) < 60 ? $faker->paragraph(rand(1, 3)) : null,
                    'created_at' => Carbon::now()->subDays(rand(7, 365)),
                ]);

                // Comissões só pra clientes ativos
                if ($cliente->status === 'ativo') {
                    $qtdComissoes = rand(1, 6);
                    for ($j = 0; $j < $qtdComissoes; $j++) {
                        $dataRef = Carbon::now()->subMonths($j)->startOfMonth();
                        $jaPassou = $dataRef->copy()->addDays(15)->lt(Carbon::now());

                        $r = rand(1, 100);
                        if (! $jaPassou || $r <= 30) {
                            $status = 'pendente';
                            $pagoEm = null;
                        } elseif ($r <= 90) {
                            $status = 'paga';
                            $pagoEm = $dataRef->copy()->addDays(rand(10, 25));
                        } else {
                            $status = 'cancelada';
                            $pagoEm = null;
                        }

                        Comissao::create([
                            'licensed_by_user_id' => $licenciado->id,
                            'cliente_id' => $cliente->id,
                            'descricao' => 'Comissão ' . $dataRef->translatedFormat('F Y') . ' — ' . $cliente->nome,
                            'valor' => $faker->randomFloat(2, 80, 1500),
                            'tipo' => $faker->randomElement(['a_receber', 'a_receber', 'a_receber', 'a_pagar']),
                            'data_referencia' => $dataRef->toDateString(),
                            'status' => $status,
                            'pago_em' => $pagoEm,
                        ]);
                    }
                }
            }
        }

        // Garante que o licenciado@metodocal.local tenha pelo menos alguns
        $licDemo = User::where('email', 'licenciado@metodocal.local')->first();
        if ($licDemo && ClienteLicenciado::where('licensed_by_user_id', $licDemo->id)->doesntExist()) {
            for ($i = 0; $i < 8; $i++) {
                $cliente = ClienteLicenciado::create([
                    'licensed_by_user_id' => $licDemo->id,
                    'nome' => $faker->name(),
                    'email' => $faker->safeEmail(),
                    'telefone' => $faker->cellphoneNumber(),
                    'cpf_cnpj' => $faker->cpf(),
                    'endereco' => $faker->streetAddress(),
                    'status' => 'ativo',
                    'notas' => $faker->paragraph(),
                ]);

                Comissao::create([
                    'licensed_by_user_id' => $licDemo->id,
                    'cliente_id' => $cliente->id,
                    'descricao' => 'Comissão demo — ' . $cliente->nome,
                    'valor' => $faker->randomFloat(2, 150, 800),
                    'tipo' => 'a_receber',
                    'data_referencia' => Carbon::now()->subMonth()->startOfMonth(),
                    'status' => 'paga',
                    'pago_em' => Carbon::now()->subDays(rand(5, 20)),
                ]);
            }
        }
    }
}
