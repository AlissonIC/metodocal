<?php

namespace Database\Seeders;

use App\Models\Conteudo;
use Illuminate\Database\Seeder;

class ConteudoSeeder extends Seeder
{
    public function run(): void
    {
        $conteudos = [
            // Introdução
            ['titulo' => 'Boas-vindas ao MetodoCal',                       'tipo' => 'video', 'categoria' => 'Introdução',  'ordem' => 1,  'descricao' => 'Vídeo de boas-vindas e visão geral do método.'],
            ['titulo' => 'Como usar a plataforma',                         'tipo' => 'video', 'categoria' => 'Introdução',  'ordem' => 2,  'descricao' => 'Tour guiado pelo painel do mentorado.'],
            ['titulo' => 'Mentalidade do comprador inteligente',           'tipo' => 'texto', 'categoria' => 'Introdução',  'ordem' => 3,  'descricao' => 'Princípios fundamentais do mindset MetodoCal.'],

            // Fundamentos
            ['titulo' => 'Fundamentos do mercado de veículos',             'tipo' => 'video', 'categoria' => 'Fundamentos', 'ordem' => 10, 'descricao' => 'Como o mercado de seminovos funciona.'],
            ['titulo' => 'Avaliação técnica básica',                       'tipo' => 'video', 'categoria' => 'Fundamentos', 'ordem' => 11, 'descricao' => 'Aprenda os pontos essenciais de inspeção.'],
            ['titulo' => 'Tabela FIPE e referenciais de preço',            'tipo' => 'pdf',   'categoria' => 'Fundamentos', 'ordem' => 12, 'descricao' => 'Como interpretar a tabela e cruzar com mercado.'],
            ['titulo' => 'Documentação veicular: guia completo',           'tipo' => 'pdf',   'categoria' => 'Fundamentos', 'ordem' => 13, 'descricao' => 'CRV, CRLV, IPVA, multas — o que checar.'],

            // Leilão
            ['titulo' => 'Entendendo os tipos de leilão',                  'tipo' => 'video', 'categoria' => 'Leilão',      'ordem' => 20, 'descricao' => 'Judicial, particular, extrajudicial.'],
            ['titulo' => 'Análise de edital — passo a passo',              'tipo' => 'video', 'categoria' => 'Leilão',      'ordem' => 21, 'descricao' => 'Como ler edital e identificar oportunidades.'],
            ['titulo' => 'Estratégia de lance',                            'tipo' => 'video', 'categoria' => 'Leilão',      'ordem' => 22, 'descricao' => 'Quando aumentar, quando recuar.'],
            ['titulo' => 'Checklist pré-leilão',                           'tipo' => 'pdf',   'categoria' => 'Leilão',      'ordem' => 23, 'descricao' => 'Lista impressa pra usar no dia do pregão.'],

            // Avançado
            ['titulo' => 'Negociação antes do leilão',                     'tipo' => 'video', 'categoria' => 'Avançado',    'ordem' => 30, 'descricao' => 'Como abordar credor e fechar antes do bloco.'],
            ['titulo' => 'Calculando custos ocultos',                      'tipo' => 'video', 'categoria' => 'Avançado',    'ordem' => 31, 'descricao' => 'Honorários, débitos, transferência.'],
            ['titulo' => 'Revenda lucrativa',                              'tipo' => 'video', 'categoria' => 'Avançado',    'ordem' => 32, 'descricao' => 'Como precificar e vender em até 30 dias.'],

            // Jurídico
            ['titulo' => 'Aspectos jurídicos de aquisição em leilão',      'tipo' => 'texto', 'categoria' => 'Jurídico',    'ordem' => 40, 'descricao' => 'Riscos, garantias e prazos.'],
            ['titulo' => 'Limpa nome — quando faz sentido',                'tipo' => 'video', 'categoria' => 'Jurídico',    'ordem' => 41, 'descricao' => 'O que é, prazos, custos.'],

            // Links externos
            ['titulo' => 'Portal de leilões judiciais — referência',       'tipo' => 'link',  'categoria' => 'Recursos',    'ordem' => 50, 'descricao' => 'Lista curada de portais confiáveis.'],
            ['titulo' => 'Calculadora de viabilidade',                     'tipo' => 'link',  'categoria' => 'Recursos',    'ordem' => 51, 'descricao' => 'Planilha online para rodar números.'],

            // Inativo (pra testar UI)
            ['titulo' => '[ARQUIVADO] Aula antiga sobre fluxo legado',     'tipo' => 'video', 'categoria' => 'Arquivo',     'ordem' => 99, 'descricao' => 'Mantido por compatibilidade.', 'ativo' => false],
        ];

        foreach ($conteudos as $c) {
            $url = match ($c['tipo']) {
                'video' => 'https://www.youtube.com/embed/' . substr(md5($c['titulo']), 0, 11),
                'pdf'   => 'https://exemplo.metodocal.com.br/pdf/' . str_replace([' ', '—', ':', ','], '-', strtolower($c['titulo'])) . '.pdf',
                'link'  => 'https://exemplo.metodocal.com.br/recurso/' . substr(md5($c['titulo']), 0, 8),
                default => '#texto-' . substr(md5($c['titulo']), 0, 8),
            };
            Conteudo::firstOrCreate(
                ['titulo' => $c['titulo']],
                array_merge(['ativo' => true, 'url' => $url], $c)
            );
        }
    }
}
