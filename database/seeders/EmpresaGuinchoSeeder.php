<?php

namespace Database\Seeders;

use App\Models\EmpresaGuincho;
use Illuminate\Database\Seeder;

class EmpresaGuinchoSeeder extends Seeder
{
    public function run(): void
    {
        $faker = \Faker\Factory::create('pt_BR');

        $estados = [
            'SP' => ['São Paulo', 'Campinas', 'Santos', 'São José dos Campos', 'Ribeirão Preto', 'Sorocaba', 'Guarulhos', 'Osasco'],
            'RJ' => ['Rio de Janeiro', 'Niterói', 'Petrópolis', 'Nova Iguaçu', 'Duque de Caxias', 'Volta Redonda'],
            'MG' => ['Belo Horizonte', 'Uberlândia', 'Contagem', 'Juiz de Fora', 'Betim', 'Montes Claros'],
            'PR' => ['Curitiba', 'Londrina', 'Maringá', 'Ponta Grossa', 'Cascavel'],
            'RS' => ['Porto Alegre', 'Caxias do Sul', 'Pelotas', 'Canoas', 'Santa Maria'],
            'SC' => ['Florianópolis', 'Joinville', 'Blumenau', 'Itajaí'],
            'BA' => ['Salvador', 'Feira de Santana', 'Vitória da Conquista', 'Camaçari'],
            'PE' => ['Recife', 'Olinda', 'Jaboatão dos Guararapes', 'Caruaru'],
            'CE' => ['Fortaleza', 'Caucaia', 'Juazeiro do Norte'],
            'GO' => ['Goiânia', 'Aparecida de Goiânia', 'Anápolis'],
            'DF' => ['Brasília', 'Taguatinga', 'Ceilândia'],
            'ES' => ['Vitória', 'Vila Velha', 'Serra'],
        ];

        $nomesBase = [
            'Guincho Rápido', 'Guincho Express', 'Resgate 24h', 'Auto Socorro', 'Reboque Veloz',
            'Guincho Total', 'Pronto Reboque', 'Resgate na Estrada', 'Guincho Confiança', 'Reboque Premium',
            'SOS Guincho', 'Guincho Brasil', 'Reboque Master', 'Auto Resgate', 'Guincho Líder',
            'Reboque Seguro', 'Guincho Direto', 'Resgate Express', 'Guincho Família', 'Reboque Garantido',
            'Guincho Hércules', 'Reboque Águia', 'Guincho Fênix', 'Resgate Olímpico', 'Guincho Atlas',
            'Reboque Trovão', 'Guincho Apollo', 'Resgate Spartan', 'Guincho Titan', 'Reboque Olimpo',
        ];

        $contador = 0;
        foreach ($estados as $uf => $cidades) {
            $emp = rand(2, 4); // 2 a 4 empresas por estado
            for ($i = 0; $i < $emp && $contador < count($nomesBase); $i++) {
                $cidade = $faker->randomElement($cidades);
                $atendidas = $faker->randomElements($cidades, min(rand(2, 4), count($cidades)));

                EmpresaGuincho::create([
                    'nome' => $nomesBase[$contador],
                    'cnpj' => $faker->cnpj(),
                    'logo' => null, // sem upload no seed; o módulo aceita null
                    'telefone' => $faker->phoneNumber(),
                    'whatsapp' => $faker->cellphoneNumber(),
                    'email' => 'contato@' . strtolower(str_replace(' ', '', $nomesBase[$contador])) . '.com.br',
                    'site' => 'https://www.' . strtolower(str_replace(' ', '', $nomesBase[$contador])) . '.com.br',
                    'estado' => $uf,
                    'cidade' => $cidade,
                    'cidades_atendidas' => array_values($atendidas),
                    'cep' => $faker->postcode(),
                    'endereco' => $faker->streetName(),
                    'numero' => (string) rand(1, 9999),
                    'complemento' => rand(0, 100) < 30 ? 'Sala ' . rand(1, 200) : null,
                    'bairro' => $faker->citySuffix() . ' ' . $faker->lastName(),
                    'descricao' => $faker->paragraph(rand(2, 4)),
                    'ativo' => rand(0, 100) < 90,
                ]);
                $contador++;
            }
            if ($contador >= count($nomesBase)) break;
        }
    }
}
