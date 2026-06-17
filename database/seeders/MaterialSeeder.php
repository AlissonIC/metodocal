<?php

namespace Database\Seeders;

use App\Models\Material;
use Illuminate\Database\Seeder;

class MaterialSeeder extends Seeder
{
    public function run(): void
    {
        $materiais = [
            ['titulo' => 'Apresentação Institucional', 'arquivo' => 'materiais/apresentacao-institucional.pdf', 'categoria' => 'Vendas', 'tamanho_bytes' => 1024 * 800],
            ['titulo' => 'Pitch Comercial', 'arquivo' => 'materiais/pitch-comercial.pdf', 'categoria' => 'Vendas', 'tamanho_bytes' => 1024 * 320],
            ['titulo' => 'Manual do Método', 'arquivo' => 'materiais/manual-metodo.pdf', 'categoria' => 'Operacional', 'tamanho_bytes' => 1024 * 1500],
            ['titulo' => 'Modelo de Contrato', 'arquivo' => 'materiais/modelo-contrato.docx', 'categoria' => 'Jurídico', 'tamanho_bytes' => 1024 * 60],
        ];

        foreach ($materiais as $m) {
            Material::firstOrCreate(['titulo' => $m['titulo']], array_merge($m, ['ativo' => true]));
        }
    }
}
