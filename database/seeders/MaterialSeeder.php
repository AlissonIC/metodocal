<?php

namespace Database\Seeders;

use App\Models\Material;
use Illuminate\Database\Seeder;

class MaterialSeeder extends Seeder
{
    public function run(): void
    {
        $materiais = [
            // Vendas
            ['titulo' => 'Apresentação Institucional',          'categoria' => 'Vendas',      'arquivo' => 'materiais/apresentacao-institucional.pdf',  'tamanho_bytes' => 819200,    'descricao' => 'Slides oficiais para apresentar o método a prospects.'],
            ['titulo' => 'Pitch Comercial 2 min',               'categoria' => 'Vendas',      'arquivo' => 'materiais/pitch-comercial.pdf',             'tamanho_bytes' => 327680,    'descricao' => 'Script enxuto para abordagem rápida.'],
            ['titulo' => 'Roteiro de Reunião Comercial',        'categoria' => 'Vendas',      'arquivo' => 'materiais/roteiro-reuniao.pdf',             'tamanho_bytes' => 245760,    'descricao' => 'Estrutura sugerida para a primeira reunião com cliente.'],
            ['titulo' => 'Objeções e Respostas',                'categoria' => 'Vendas',      'arquivo' => 'materiais/objecoes-respostas.pdf',          'tamanho_bytes' => 153600,    'descricao' => 'Top 20 objeções e como contornar.'],

            // Operacional
            ['titulo' => 'Manual do Método (v3)',               'categoria' => 'Operacional', 'arquivo' => 'materiais/manual-metodo-v3.pdf',            'tamanho_bytes' => 1536000,   'descricao' => 'Documento completo da metodologia.'],
            ['titulo' => 'Checklist Diário do Licenciado',      'categoria' => 'Operacional', 'arquivo' => 'materiais/checklist-diario.pdf',            'tamanho_bytes' => 81920,     'descricao' => 'Rotina sugerida pra manter ritmo de operação.'],
            ['titulo' => 'Fluxograma de Processo',              'categoria' => 'Operacional', 'arquivo' => 'materiais/fluxograma.pdf',                  'tamanho_bytes' => 204800,    'descricao' => 'Desenho do fluxo completo, do lead à venda.'],

            // Jurídico
            ['titulo' => 'Modelo de Contrato — Licenciamento',  'categoria' => 'Jurídico',    'arquivo' => 'materiais/contrato-licenciamento.docx',     'tamanho_bytes' => 61440,     'descricao' => 'Template editável de contrato.'],
            ['titulo' => 'Modelo de Procuração',                'categoria' => 'Jurídico',    'arquivo' => 'materiais/procuracao.docx',                 'tamanho_bytes' => 40960,     'descricao' => 'Procuração padrão para representação em leilão.'],
            ['titulo' => 'Termo de Sigilo (NDA)',               'categoria' => 'Jurídico',    'arquivo' => 'materiais/nda.docx',                        'tamanho_bytes' => 35840,     'descricao' => 'Acordo de confidencialidade.'],

            // Marketing
            ['titulo' => 'Identidade Visual — Manual',          'categoria' => 'Marketing',   'arquivo' => 'materiais/manual-visual.pdf',               'tamanho_bytes' => 3145728,   'descricao' => 'Cores, tipografia, regras de uso da marca.'],
            ['titulo' => 'Pack de Posts Instagram',             'categoria' => 'Marketing',   'arquivo' => 'materiais/posts-instagram.zip',             'tamanho_bytes' => 8388608,   'descricao' => 'PSDs + PNGs prontos para postar.'],
            ['titulo' => 'Banner para WhatsApp',                'categoria' => 'Marketing',   'arquivo' => 'materiais/banner-whatsapp.png',             'tamanho_bytes' => 524288,    'descricao' => 'Banner pronto para divulgar.'],

            // Inativo
            ['titulo' => '[ARQUIVADO] Contrato versão 2020',    'categoria' => 'Arquivo',     'arquivo' => 'materiais/contrato-v2020.pdf',              'tamanho_bytes' => 102400,    'descricao' => 'Versão antiga, mantida por contratos legados.', 'ativo' => false],
        ];

        foreach ($materiais as $m) {
            Material::firstOrCreate(
                ['titulo' => $m['titulo']],
                array_merge(['ativo' => true], $m)
            );
        }
    }
}
