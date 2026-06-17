<?php

namespace Database\Seeders;

use App\Models\Conteudo;
use Illuminate\Database\Seeder;

class ConteudoSeeder extends Seeder
{
    public function run(): void
    {
        $conteudos = [
            ['titulo' => 'Boas-vindas ao Método', 'tipo' => 'video', 'url' => 'https://example.com/video/intro', 'categoria' => 'Introdução', 'ordem' => 1],
            ['titulo' => 'Fundamentos – Aula 1', 'tipo' => 'video', 'url' => 'https://example.com/video/fund1', 'categoria' => 'Fundamentos', 'ordem' => 2],
            ['titulo' => 'Fundamentos – Aula 2', 'tipo' => 'video', 'url' => 'https://example.com/video/fund2', 'categoria' => 'Fundamentos', 'ordem' => 3],
            ['titulo' => 'Apostila de apoio', 'tipo' => 'pdf', 'url' => 'https://example.com/pdf/apostila.pdf', 'categoria' => 'Materiais', 'ordem' => 4],
            ['titulo' => 'Aprofundamento – Conceitos avançados', 'tipo' => 'video', 'url' => 'https://example.com/video/avanc1', 'categoria' => 'Avançado', 'ordem' => 5],
        ];

        foreach ($conteudos as $c) {
            Conteudo::firstOrCreate(['titulo' => $c['titulo']], array_merge($c, ['ativo' => true]));
        }
    }
}
