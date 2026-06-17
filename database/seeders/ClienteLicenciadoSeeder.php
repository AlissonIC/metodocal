<?php

namespace Database\Seeders;

use App\Models\ClienteLicenciado;
use App\Models\Comissao;
use App\Models\User;
use Illuminate\Database\Seeder;

class ClienteLicenciadoSeeder extends Seeder
{
    public function run(): void
    {
        $licenciado = User::where('email', 'licenciado@metodocal.local')->first();
        if (! $licenciado) {
            return;
        }

        $clientes = [
            ['nome' => 'Empresa Alfa LTDA', 'email' => 'contato@alfa.com.br', 'telefone' => '(11) 99999-1111', 'status' => 'ativo'],
            ['nome' => 'Beta Comércio', 'email' => 'beta@beta.com.br', 'telefone' => '(11) 99999-2222', 'status' => 'ativo'],
            ['nome' => 'Gama Serviços', 'email' => 'gama@gama.com.br', 'telefone' => '(11) 99999-3333', 'status' => 'lead'],
        ];

        foreach ($clientes as $c) {
            $cliente = ClienteLicenciado::firstOrCreate(
                ['licensed_by_user_id' => $licenciado->id, 'nome' => $c['nome']],
                array_merge($c, ['licensed_by_user_id' => $licenciado->id])
            );

            if ($cliente->status === 'ativo' && $cliente->comissoes()->doesntExist()) {
                Comissao::create([
                    'licensed_by_user_id' => $licenciado->id,
                    'cliente_id' => $cliente->id,
                    'descricao' => 'Comissão mensal — ' . $cliente->nome,
                    'valor' => 250.00,
                    'data_referencia' => now()->subMonth()->startOfMonth(),
                    'status' => 'paga',
                    'pago_em' => now()->subDays(5),
                ]);
                Comissao::create([
                    'licensed_by_user_id' => $licenciado->id,
                    'cliente_id' => $cliente->id,
                    'descricao' => 'Comissão mensal — ' . $cliente->nome,
                    'valor' => 250.00,
                    'data_referencia' => now()->startOfMonth(),
                    'status' => 'pendente',
                ]);
            }
        }
    }
}
