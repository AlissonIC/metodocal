<?php

namespace Database\Seeders;

use App\Models\Conteudo;
use App\Models\ProgressoConteudo;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ProgressoConteudoSeeder extends Seeder
{
    public function run(): void
    {
        $conteudos = Conteudo::where('ativo', true)->get();
        if ($conteudos->isEmpty()) {
            return;
        }

        $mentorados = User::role('mentorado')
            ->whereNotNull('current_subscription_id')
            ->get();

        foreach ($mentorados as $user) {
            // Cada mentorado completou uma parte aleatória dos conteúdos
            $qtd = rand(0, $conteudos->count());
            $subset = $conteudos->random(min($qtd, $conteudos->count()));

            foreach ($subset as $c) {
                ProgressoConteudo::firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'conteudo_id' => $c->id,
                    ],
                    [
                        'concluido_em' => Carbon::now()->subDays(rand(1, 180)),
                    ]
                );
            }
        }
    }
}
