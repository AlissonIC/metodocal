{{-- Página isolada para impressão/PDF do cadastro do processo.
     Não estende o layout do painel de propósito: menu, navbar e botões não devem
     ir para o papel, e um HTML próprio dá controle total sobre a quebra de página. --}}
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Processo #{{ $processo->id }} — {{ $processo->nome_completo }}</title>
  <style>
    :root { --linha: #d9dee3; --texto: #2f3349; --suave: #6f6b7d; }

    * { box-sizing: border-box; }
    body {
      margin: 0;
      padding: 24px;
      background: #f5f5f9;
      color: var(--texto);
      font-family: "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
      font-size: 12px;
      line-height: 1.5;
    }
    .folha {
      max-width: 820px;
      margin: 0 auto;
      background: #fff;
      padding: 32px 36px;
      border-radius: 6px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, .08);
    }

    .barra-acoes {
      max-width: 820px;
      margin: 0 auto 16px;
      display: flex;
      justify-content: flex-end;
      gap: 8px;
    }
    .barra-acoes button, .barra-acoes a {
      font: inherit;
      cursor: pointer;
      border: 1px solid var(--linha);
      background: #fff;
      color: var(--texto);
      padding: 8px 16px;
      border-radius: 5px;
      text-decoration: none;
    }
    .barra-acoes .primario { background: #7367f0; border-color: #7367f0; color: #fff; }

    header.topo {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 16px;
      border-bottom: 2px solid var(--texto);
      padding-bottom: 12px;
      margin-bottom: 20px;
    }
    header.topo h1 { font-size: 18px; margin: 0 0 2px; }
    header.topo .sub { color: var(--suave); font-size: 11px; }
    .status {
      border: 1px solid var(--linha);
      border-radius: 4px;
      padding: 4px 10px;
      font-weight: 600;
      white-space: nowrap;
    }

    section { margin-bottom: 18px; page-break-inside: avoid; }
    section > h2 {
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: .06em;
      color: var(--suave);
      margin: 0 0 8px;
      padding-bottom: 4px;
      border-bottom: 1px solid var(--linha);
    }

    .campos { display: grid; grid-template-columns: repeat(2, 1fr); gap: 6px 24px; }
    .campos.uma-coluna { grid-template-columns: 1fr; }
    .campo { display: flex; gap: 8px; align-items: baseline; }
    .campo .rotulo { color: var(--suave); min-width: 130px; flex-shrink: 0; }
    .campo .valor { font-weight: 500; word-break: break-word; }
    .campo.largo { grid-column: 1 / -1; }

    table { width: 100%; border-collapse: collapse; }
    th, td { text-align: left; padding: 6px 8px; border-bottom: 1px solid var(--linha); vertical-align: top; }
    th { color: var(--suave); font-weight: 600; font-size: 11px; text-transform: uppercase; }
    td.num, th.num { text-align: right; white-space: nowrap; }
    tfoot td { font-weight: 700; border-bottom: 0; }

    .texto-livre { white-space: pre-wrap; }
    .vazio { color: var(--suave); }

    footer.rodape {
      margin-top: 24px;
      padding-top: 10px;
      border-top: 1px solid var(--linha);
      color: var(--suave);
      font-size: 10px;
      display: flex;
      justify-content: space-between;
    }

    @media print {
      body { background: #fff; padding: 0; font-size: 11px; }
      /* O recuo fica na folha, e não só no @page: se o diálogo de impressão estiver
         com "Margens: nenhuma", o @page é descartado e sem isto o texto encostaria
         na borda do papel. Com as duas regras valendo, a margem final é ~20mm. */
      .folha { max-width: none; box-shadow: none; border-radius: 0; padding: 10mm; }
      .barra-acoes { display: none; }
      section { page-break-inside: avoid; }
      thead { display: table-header-group; }
    }

    @page { margin: 10mm; }
  </style>
</head>
<body>

@php
  $dinheiro = fn ($v) => $v === null ? null : 'R$ ' . number_format((float) $v, 2, ',', '.');
  $v = $processo->veiculo;
@endphp

<div class="barra-acoes">
  <a href="{{ route('processos.show', $processo) }}">Voltar</a>
  <button type="button" class="primario" onclick="window.print()">Salvar em PDF / Imprimir</button>
</div>

<div class="folha">
  <header class="topo">
    <div>
      <h1>Processo #{{ $processo->id }} — {{ $processo->nome_completo }}</h1>
      <div class="sub">
        {{ $processo->servico?->nome ?? 'Serviço não informado' }}
        · Cadastrado em {{ $processo->created_at?->format('d/m/Y') }}
        @if ($isAdmin && $processo->user)
          · Cliente: {{ $processo->user->name }}
        @endif
      </div>
    </div>
    <span class="status">{{ $processo->statusLabel() }}</span>
  </header>

  <section>
    <h2>Dados pessoais</h2>
    <div class="campos">
      <div class="campo"><span class="rotulo">Nome completo</span><span class="valor">{{ $processo->nome_completo }}</span></div>
      <div class="campo"><span class="rotulo">{{ strtoupper($processo->tipo_documento) }}</span><span class="valor">{{ $processo->documento }}</span></div>
      <div class="campo"><span class="rotulo">E-mail</span><span class="valor">{{ $processo->email_contato ?: '—' }}</span></div>
      <div class="campo"><span class="rotulo">Telefone</span><span class="valor">{{ $processo->telefone_contato ?: '—' }}</span></div>
      <div class="campo largo"><span class="rotulo">Endereço</span><span class="valor">{{ $processo->enderecoFormatado() }}</span></div>
    </div>
  </section>

  <section>
    <h2>Andamento</h2>
    <div class="campos">
      <div class="campo"><span class="rotulo">Status atual</span><span class="valor">{{ $processo->statusLabel() }}</span></div>
      <div class="campo"><span class="rotulo">Liminar protocolada</span><span class="valor">{{ $processo->data_protocolo_liminar?->format('d/m/Y') ?: '—' }}</span></div>
      <div class="campo"><span class="rotulo">Previsão de conclusão</span><span class="valor">{{ $processo->data_previsao_conclusao?->format('d/m/Y') ?: '—' }}</span></div>
      <div class="campo"><span class="rotulo">Concluído em</span><span class="valor">{{ $processo->data_conclusao?->format('d/m/Y') ?: '—' }}</span></div>
    </div>
  </section>

  {{-- Sempre presente, mesmo sem dados — o PDF é o retrato do cadastro inteiro. --}}
  <section>
    <h2>Financiamento</h2>
    <div class="campos">
      <div class="campo">
        <span class="rotulo">Banco</span>
        <span class="valor">{{ $processo->banco ? $processo->banco->nome . ' (' . number_format((float) $processo->banco->taxa, 2, ',', '.') . '%)' : '—' }}</span>
      </div>
      <div class="campo"><span class="rotulo">Valor financiado</span><span class="valor">{{ $dinheiro($processo->valor_financiamento) ?: '—' }}</span></div>
      <div class="campo"><span class="rotulo">Quantidade de parcelas</span><span class="valor">{{ $processo->qtd_parcelas ? $processo->qtd_parcelas . 'x' : '—' }}</span></div>
      <div class="campo"><span class="rotulo">Valor da parcela</span><span class="valor">{{ $dinheiro($processo->valor_parcela) ?: '—' }}</span></div>
      <div class="campo"><span class="rotulo">Primeira parcela</span><span class="valor">{{ $processo->data_primeira_parcela?->format('d/m/Y') ?: '—' }}</span></div>
      <div class="campo largo"><span class="rotulo">Pagamento mensal</span><span class="valor">{{ $processo->link_pagamento_mensal ?: '—' }}</span></div>
    </div>
  </section>

  @if ($v)
    <section>
      <h2>Veículo</h2>
      <div class="campos">
        <div class="campo"><span class="rotulo">Marca/Modelo</span><span class="valor">{{ $v->descricaoCurta() }}</span></div>
        <div class="campo"><span class="rotulo">Placa</span><span class="valor">{{ $v->placa ?: '—' }}</span></div>
        <div class="campo"><span class="rotulo">Ano fab./modelo</span><span class="valor">{{ $v->ano_fabricacao ?: '—' }} / {{ $v->ano_modelo ?: '—' }}</span></div>
        <div class="campo"><span class="rotulo">Cor</span><span class="valor">{{ $v->cor ?: '—' }}</span></div>
        <div class="campo"><span class="rotulo">Combustível</span><span class="valor">{{ $v->combustivel ? $v->combustivelLabel() : '—' }}</span></div>
        <div class="campo"><span class="rotulo">Quilometragem</span><span class="valor">{{ $v->quilometragem !== null ? number_format((int) $v->quilometragem, 0, ',', '.') . ' km' : '—' }}</span></div>
        <div class="campo"><span class="rotulo">Chassi</span><span class="valor">{{ $v->chassi ? strtoupper($v->chassi) : '—' }}</span></div>
        <div class="campo"><span class="rotulo">RENAVAM</span><span class="valor">{{ $v->renavam ?: '—' }}</span></div>
        <div class="campo"><span class="rotulo">Valor FIPE</span><span class="valor">{{ $dinheiro($v->valor_fipe) ?: '—' }}</span></div>
        @if ($v->observacoes)
          <div class="campo largo"><span class="rotulo">Observações</span><span class="valor texto-livre">{{ $v->observacoes }}</span></div>
        @endif
      </div>
    </section>
  @endif

  @if ($processo->dividas->isNotEmpty())
    <section>
      <h2>Dívidas</h2>
      <table>
        <thead>
          <tr><th>Credor</th><th>Descrição</th><th class="num">Valor</th></tr>
        </thead>
        <tbody>
          @foreach ($processo->dividas as $d)
            <tr>
              <td>{{ $d->credor }}</td>
              <td>{{ $d->descricao ?: '—' }}</td>
              <td class="num">{{ $dinheiro($d->valor) }}</td>
            </tr>
          @endforeach
        </tbody>
        <tfoot>
          <tr>
            <td colspan="2">Total</td>
            <td class="num">{{ $dinheiro($processo->dividas->sum('valor')) }}</td>
          </tr>
        </tfoot>
      </table>
    </section>
  @endif

  @if ($isAdmin && $processo->comprador)
    <section>
      <h2>Comprador vinculado</h2>
      <div class="campos">
        <div class="campo"><span class="rotulo">Nome</span><span class="valor">{{ $processo->comprador->nome }}</span></div>
        <div class="campo"><span class="rotulo">{{ strtoupper($processo->comprador->tipo_documento) }}</span><span class="valor">{{ $processo->comprador->documentoFormatado() }}</span></div>
        <div class="campo"><span class="rotulo">E-mail</span><span class="valor">{{ $processo->comprador->email ?: '—' }}</span></div>
        <div class="campo"><span class="rotulo">Telefone</span><span class="valor">{{ $processo->comprador->telefone ?: '—' }}</span></div>
      </div>
    </section>
  @endif

  @if ($isAdmin && $processo->negociacoes->isNotEmpty())
    <section>
      <h2>Negociações</h2>
      <table>
        <thead>
          <tr>
            <th>Data</th>
            <th>Assessoria / contato</th>
            <th class="num">Val. atual</th>
            <th class="num">Pré-análise</th>
            <th class="num">Em mãos</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($processo->negociacoes as $n)
            <tr>
              <td>{{ $n->data?->format('d/m/Y') }}</td>
              <td>
                {{ $n->assessoria ?: '—' }}
                @if ($n->contato_nome || $n->telefone)
                  <div class="vazio">
                    {{ $n->contato_nome ? 'Falou com ' . $n->contato_nome : '' }}{{ $n->contato_nome && $n->telefone ? ' · ' : '' }}{{ $n->telefone }}
                  </div>
                @endif
              </td>
              <td class="num">{{ $dinheiro($n->val_atualizado) ?: '—' }}</td>
              <td class="num">{{ $dinheiro($n->val_analise) ?: '—' }}</td>
              <td class="num">{{ $dinheiro($n->val_em_maos) ?: '—' }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </section>
  @endif

  @if ($processo->documentos->isNotEmpty())
    <section>
      <h2>Documentos anexados</h2>
      <table>
        <thead>
          <tr><th>Arquivo</th><th>Categoria</th><th>Enviado em</th></tr>
        </thead>
        <tbody>
          @foreach ($processo->documentos as $doc)
            <tr>
              <td>{{ $doc->nome_original }}</td>
              <td>{{ $doc->categoria ?: '—' }}</td>
              <td>{{ $doc->created_at?->format('d/m/Y H:i') }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </section>
  @endif

  @if ($processo->observacoes_cliente)
    <section>
      <h2>Observações do cliente</h2>
      <div class="texto-livre">{{ $processo->observacoes_cliente }}</div>
    </section>
  @endif

  @if ($isAdmin && $processo->observacoes_admin)
    <section>
      <h2>Observações internas</h2>
      <div class="texto-livre">{{ $processo->observacoes_admin }}</div>
    </section>
  @endif

  <footer class="rodape">
    <span>Processo #{{ $processo->id }} · {{ $processo->nome_completo }}</span>
    <span>Emitido em {{ now()->format('d/m/Y H:i') }}</span>
  </footer>
</div>

<script>
  // Abre o diálogo de impressão assim que a página carrega — o usuário escolhe
  // "Salvar como PDF" no destino. Cancelar mantém a página aberta.
  window.addEventListener('load', function () { window.print(); });
</script>

</body>
</html>
