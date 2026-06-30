<?php

namespace Database\Seeders;

use App\Models\Banco;
use Illuminate\Database\Seeder;

class BancoSeeder extends Seeder
{
    public function run(): void
    {
        $bancos = [
            ['nome' => 'AYMORÉ',         'cnpj' => '07707650000110', 'taxa' => 80],
            ['nome' => 'BRADESCO',       'cnpj' => '60746948000112', 'taxa' => 80],
            ['nome' => 'ITAUCARD',       'cnpj' => '17192451000170', 'taxa' => 80],
            ['nome' => 'ITAÚ UNIBANCO',  'cnpj' => '60701190000104', 'taxa' => 80],
            ['nome' => 'SANTANDER',      'cnpj' => '90400888000142', 'taxa' => 80],
            ['nome' => 'BV',             'cnpj' => '01149953000189', 'taxa' => 80],
            ['nome' => 'VW',             'cnpj' => '59109165000149', 'taxa' => 70],
            ['nome' => 'OMNI',           'cnpj' => '92228410000102', 'taxa' => 50],
            ['nome' => 'PAN',            'cnpj' => '59285411000113', 'taxa' => 70],
            // CNPJ informado com 13 dígitos — preservado como veio; o admin pode corrigir na UI.
            ['nome' => 'DAYCOVAL',       'cnpj' => '6223288900157',  'taxa' => 50],
            ['nome' => 'PORTO SEGURO',   'cnpj' => '02149205000169', 'taxa' => 70],
            ['nome' => 'RCI',            'cnpj' => '62307848000115', 'taxa' => 70],
            ['nome' => 'MONEY PLUS',     'cnpj' => '34337707000100', 'taxa' => 80],
            ['nome' => 'BANCO SAFRA',    'cnpj' => '58160789000128', 'taxa' => 50],
            ['nome' => 'BANCO DIGIMAIS', 'cnpj' => '92874270000140', 'taxa' => 65],
        ];

        foreach ($bancos as $b) {
            Banco::updateOrCreate(
                ['cnpj' => $b['cnpj']],
                array_merge($b, ['ativo' => true]),
            );
        }
    }
}
