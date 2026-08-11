@extends('layouts/layoutMaster')

@section('title', 'Processo #' . $processo->id)

@if ($isAdmin)
@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
  'resources/assets/vendor/libs/select2/select2.scss',
  'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
])
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/flatpickr/flatpickr.js',
  'resources/assets/vendor/libs/select2/select2.js',
  'resources/assets/vendor/libs/cleave-zen/cleave-zen.js',
  'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
])
@endsection

@section('page-style')
<style>
  /* Item do dropdown "Usuário" (nova comissão) — badge, nome e email empilhados */
  .usuario-option {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    line-height: 1.3;
    overflow: hidden;
  }
  .usuario-badge {
    font-size: .62rem;
    padding: .15rem .4rem;
    line-height: 1.2;
  }
  .usuario-nome {
    font-size: .95rem;
    min-width: 0;
    max-width: 100%;
  }
  .usuario-email {
    font-size: .72rem;
    max-width: 100%;
  }
  /* Dropdown com largura mínima confortável mesmo em coluna estreita */
  .select2-container--open .select2-dropdown {
    min-width: 240px;
  }
  .select2-results__option { padding: .5rem .75rem; }
  /* No campo já fechado, mantém badge + nome em 1 linha compacta */
  .select2-selection__rendered .badge { flex-shrink: 0; }
  .select2-selection--single { overflow: hidden; }

  /* Sem busca / sem "por página" — remove o espaço vazio do dt-container em todas as tabelas do show */
  .dt-collapse-empty ~ .dt-layout-row:has(.dt-layout-start:empty):has(.dt-layout-end:empty),
  .datatables-observacoes_wrapper .dt-layout-row:has(.dt-layout-start:empty):has(.dt-layout-end:empty),
  .datatables-faturas_wrapper .dt-layout-row:has(.dt-layout-start:empty):has(.dt-layout-end:empty),
  .datatables-comissoes_wrapper .dt-layout-row:has(.dt-layout-start:empty):has(.dt-layout-end:empty),
  .datatables-negociacoes_wrapper .dt-layout-row:has(.dt-layout-start:empty):has(.dt-layout-end:empty) { display: none; }
</style>
@endsection
@endif

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
  <div>
    <a href="{{ route('processos.index') }}" class="text-muted small text-decoration-none">
      <i class="icon-base ti tabler-arrow-left"></i> Voltar para a lista
    </a>
    <h4 class="mb-0 mt-1">Processo #{{ $processo->id }} · {{ $processo->nome_completo }}</h4>
    @if ($isAdmin)
      <p class="text-muted mb-0">Cliente: <strong>{{ $processo->user?->name }}</strong> ({{ $processo->user?->email }})</p>
    @endif
  </div>
  <div class="d-flex align-items-center gap-2">
    <span class="badge bg-label-{{ $processo->statusColor() }} fs-6">{{ $processo->statusLabel() }}</span>
    <a href="{{ route('processos.imprimir', $processo) }}" target="_blank" rel="noopener" class="btn btn-label-secondary">
      <i class="icon-base ti tabler-file-type-pdf me-1"></i> Salvar em PDF
    </a>
    @if (! $isComprador && ($isAdmin || ($isOwner && $processo->isEditavelPeloCliente())))
      <a href="{{ route('processos.edit', $processo) }}" class="btn btn-label-primary"><i class="icon-base ti tabler-edit me-1"></i> Editar</a>
      <form method="POST" action="{{ route('processos.destroy', $processo) }}" onsubmit="return confirm('Excluir este processo?')">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-label-danger"><i class="icon-base ti tabler-trash me-1"></i> Excluir</button>
      </form>
    @endif
  </div>
</div>

@if (session('status'))
  <div class="alert alert-success">{{ session('status') }}</div>
@endif
@if ($errors->any())
  <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul></div>
@endif

<div class="row g-4">
  <div class="col-lg-8">
    <div class="card mb-4">
      <div class="card-header border-bottom"><h5 class="card-title mb-0">Resumo</h5></div>
      <div class="card-body">
        <dl class="row mb-0">
          <dt class="col-sm-4 text-muted">Serviço</dt>
          <dd class="col-sm-8">{{ $processo->servico?->nome ?? '—' }}</dd>

          <dt class="col-sm-4 text-muted">Documento</dt>
          <dd class="col-sm-8">{{ strtoupper($processo->tipo_documento) }}: {{ $processo->documento }}</dd>

          <dt class="col-sm-4 text-muted">E-mail</dt>
          <dd class="col-sm-8">{{ $processo->email_contato ?: '—' }}</dd>

          <dt class="col-sm-4 text-muted">Telefone</dt>
          <dd class="col-sm-8">{{ $processo->telefone_contato ?: '—' }}</dd>

          @if ($processo->data_protocolo_liminar)
            <dt class="col-sm-4 text-muted">Liminar protocolada em</dt>
            <dd class="col-sm-8">{{ $processo->data_protocolo_liminar->format('d/m/Y') }}</dd>
          @endif

          @if ($processo->data_previsao_conclusao)
            <dt class="col-sm-4 text-muted">Previsão de conclusão</dt>
            <dd class="col-sm-8">{{ $processo->data_previsao_conclusao->format('d/m/Y') }}</dd>
          @endif

          @if ($processo->data_conclusao)
            <dt class="col-sm-4 text-muted">Concluído em</dt>
            <dd class="col-sm-8">{{ $processo->data_conclusao->format('d/m/Y') }}</dd>
          @endif

          @if ($processo->observacoes_cliente)
            <dt class="col-sm-4 text-muted">{{ $isAdmin ? 'Observações do cliente' : 'Suas observações' }}</dt>
            <dd class="col-sm-8">{{ $processo->observacoes_cliente }}</dd>
          @endif
        </dl>
      </div>
    </div>

    @if ($isAdmin && $processo->user)
      <div class="card mb-4">
        <div class="card-header border-bottom d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0"><i class="icon-base ti tabler-user-circle me-1"></i> Cliente do sistema</h5>
          @php
            $roleCliente = optional($processo->user->roles->first())->name ?? '—';
            $roleColors  = ['admin' => 'danger', 'mentorado' => 'info', 'licenciado' => 'success', 'comprador' => 'primary'];
            $roleCor     = $roleColors[$roleCliente] ?? 'secondary';
          @endphp
          <span class="badge bg-label-{{ $roleCor }}">{{ ucfirst($roleCliente) }}</span>
        </div>
        <div class="card-body">
          <dl class="row mb-0">
            <dt class="col-sm-4 text-muted">Nome</dt>
            <dd class="col-sm-8 fw-medium">{{ $processo->user->name }}</dd>
            <dt class="col-sm-4 text-muted">E-mail</dt>
            <dd class="col-sm-8">{{ $processo->user->email }}</dd>
            @if ($processo->user->phone ?? null)
              <dt class="col-sm-4 text-muted">Telefone</dt>
              <dd class="col-sm-8">{{ $processo->user->phone }}</dd>
            @endif
            @if ($processo->user->cpf_cnpj ?? null)
              <dt class="col-sm-4 text-muted">CPF/CNPJ</dt>
              <dd class="col-sm-8">{{ $processo->user->cpf_cnpj }}</dd>
            @endif
            @if ($processo->user->created_at)
              <dt class="col-sm-4 text-muted">Cliente desde</dt>
              <dd class="col-sm-8">{{ $processo->user->created_at->format('d/m/Y') }}</dd>
            @endif
          </dl>
        </div>
      </div>
    @endif

    {{-- Sempre visível, mesmo sem dados: o bloco faz parte do cadastro e a ausência
         de valores é informação (mostra o que ainda falta preencher). --}}
    <div class="card mb-4">
      <div class="card-header border-bottom d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0"><i class="icon-base ti tabler-building-bank me-1"></i> Financiamento</h5>
        <div class="d-flex gap-2">
          @if ($isAdmin && $processo->parcelas_count)
            <a href="{{ route('admin.financiamentos', ['processo_id' => $processo->id]) }}" class="btn btn-sm btn-label-primary">
              <i class="icon-base ti tabler-calendar-dollar me-1"></i> Ver {{ $processo->parcelas_count }} parcelas
            </a>
          @endif
          @if (! $isComprador && ($isAdmin || ($isOwner && $processo->isEditavelPeloCliente())))
            <a href="{{ route('processos.edit', $processo) }}" class="btn btn-sm btn-label-secondary">
              <i class="icon-base ti tabler-edit me-1"></i> {{ $processo->temFinanciamento() ? 'Alterar' : 'Preencher' }}
            </a>
          @endif
        </div>
      </div>
      <div class="card-body">
        <dl class="row mb-0">
          <dt class="col-sm-4 text-muted">Banco</dt>
          <dd class="col-sm-8 {{ $processo->banco ? '' : 'text-muted' }}">
            @if ($processo->banco)
              {{ $processo->banco->nome }}
              <span class="badge bg-label-secondary ms-1">{{ number_format((float) $processo->banco->taxa, 2, ',', '.') }}%</span>
            @else
              —
            @endif
          </dd>

          <dt class="col-sm-4 text-muted">Valor financiado</dt>
          <dd class="col-sm-8 {{ $processo->valor_financiamento !== null ? 'fw-semibold' : 'text-muted' }}">
            {{ $processo->valor_financiamento !== null ? 'R$ ' . number_format((float) $processo->valor_financiamento, 2, ',', '.') : '—' }}
          </dd>

          <dt class="col-sm-4 text-muted">Quantidade de parcelas</dt>
          <dd class="col-sm-8 {{ $processo->qtd_parcelas ? '' : 'text-muted' }}">
            {{ $processo->qtd_parcelas ? $processo->qtd_parcelas . 'x' : '—' }}
          </dd>

          <dt class="col-sm-4 text-muted">Valor da parcela</dt>
          <dd class="col-sm-8 {{ $processo->valor_parcela !== null ? 'fw-semibold' : 'text-muted' }}">
            {{ $processo->valor_parcela !== null ? 'R$ ' . number_format((float) $processo->valor_parcela, 2, ',', '.') : '—' }}
          </dd>

          <dt class="col-sm-4 text-muted">Primeira parcela</dt>
          <dd class="col-sm-8 {{ $processo->data_primeira_parcela ? '' : 'text-muted' }}">
            {{ $processo->data_primeira_parcela?->format('d/m/Y') ?: '—' }}
            @if ($isAdmin && $processo->parcelas_count)
              <span class="text-muted small">
                · {{ $processo->parcelas_pagas_count }} de {{ $processo->parcelas_count }} paga(s)
              </span>
            @endif
          </dd>

          <dt class="col-sm-4 text-muted">Pagamento mensal</dt>
          <dd class="col-sm-8">
            @if ($processo->link_pagamento_mensal)
              <div class="d-flex flex-wrap align-items-center gap-2">
                <a href="{{ $processo->link_pagamento_mensal }}" target="_blank" rel="noopener" class="btn btn-sm btn-primary">
                  <i class="icon-base ti tabler-credit-card me-1"></i> Pagar parcela do mês
                </a>
                <button type="button" class="btn btn-sm btn-label-secondary" id="btn-copiar-link-pagamento"
                        data-link="{{ $processo->link_pagamento_mensal }}">
                  <i class="icon-base ti tabler-copy me-1"></i> Copiar link
                </button>
              </div>
              <small class="text-muted d-block text-break mt-1">{{ $processo->link_pagamento_mensal }}</small>
            @else
              <span class="text-muted">Nenhum link cadastrado.</span>
            @endif
          </dd>
        </dl>
      </div>
    </div>
    @if ($processo->link_pagamento_mensal)
      {{-- Inline: o bloco de financiamento aparece para todos os perfis, e a section
           page-script deste arquivo só existe para admin. --}}
      <script>
        document.getElementById('btn-copiar-link-pagamento')?.addEventListener('click', function () {
          const btn = this;
          navigator.clipboard.writeText(btn.dataset.link).then(function () {
            const original = btn.innerHTML;
            btn.innerHTML = '<i class="icon-base ti tabler-check me-1"></i> Copiado!';
            setTimeout(function () { btn.innerHTML = original; }, 2000);
          });
        });
      </script>
    @endif

    {{-- Carnê de parcelas: visível a quem enxerga o processo (inclusive o cliente titular).
         Somente leitura aqui — quem muda status é o admin, em Financeiro → Financiamentos. --}}
    @if ($processo->parcelas->isNotEmpty())
      @php
        $parcelas = $processo->parcelas;
        $pagas = $parcelas->where('status', 'paga');
        $emAberto = $parcelas->where('status', 'pendente');
        $atrasadas = $emAberto->filter(fn ($p) => $p->isAtrasada());
        $proxima = $emAberto->reject(fn ($p) => $p->isAtrasada())->first();
      @endphp
      <div class="card mb-4">
        <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
          <div>
            <h5 class="card-title mb-0"><i class="icon-base ti tabler-calendar-dollar me-1"></i> Parcelas do financiamento</h5>
            <small class="text-muted">
              {{ $pagas->count() }} de {{ $parcelas->count() }} paga(s)
              @if ($atrasadas->isNotEmpty())
                · <span class="text-danger fw-medium">{{ $atrasadas->count() }} em atraso</span>
              @elseif ($proxima)
                · próxima em {{ $proxima->vencimento->format('d/m/Y') }}
              @endif
            </small>
          </div>
          @if ($isAdmin)
            <a href="{{ route('admin.financiamentos', ['processo_id' => $processo->id]) }}" class="btn btn-sm btn-label-primary">
              <i class="icon-base ti tabler-settings me-1"></i> Gerenciar
            </a>
          @endif
        </div>
        <div class="card-body">
          @if ($atrasadas->isNotEmpty() && ! $isAdmin)
            <div class="alert alert-danger py-2 small d-flex align-items-center gap-2 mb-3">
              <i class="icon-base ti tabler-alert-triangle"></i>
              <div>
                Você tem <strong>{{ $atrasadas->count() }}</strong> parcela(s) vencida(s), somando
                <strong>R$ {{ number_format((float) $atrasadas->sum('valor'), 2, ',', '.') }}</strong>.
                @if ($processo->link_pagamento_mensal)
                  Use o botão de pagamento acima para regularizar.
                @endif
              </div>
            </div>
          @endif
          <div class="table-responsive">
            <table class="table table-sm mb-0" id="tabela-parcelas">
              <thead>
                <tr>
                  <th>Parcela</th>
                  <th>Vencimento</th>
                  <th class="text-end">Valor</th>
                  <th>Situação</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($parcelas as $p)
                  <tr data-parcela @class(['table-danger' => $p->isAtrasada()])>
                    <td class="text-nowrap">{{ $p->numero }}/{{ $parcelas->count() }}</td>
                    <td class="text-nowrap">
                      {{ $p->vencimento->format('d/m/Y') }}
                      @if ($p->isAtrasada())
                        <div class="small text-danger">{{ $p->diasAtraso() }} dia(s) em atraso</div>
                      @elseif ($p->pago_em)
                        <div class="small text-success">pago em {{ $p->pago_em->format('d/m/Y') }}</div>
                      @endif
                    </td>
                    <td class="text-end text-nowrap fw-semibold">R$ {{ number_format((float) $p->valor, 2, ',', '.') }}</td>
                    <td><span class="badge bg-label-{{ $p->statusColor() }}">{{ $p->statusLabel() }}</span></td>
                  </tr>
                @endforeach
              </tbody>
              <tfoot>
                <tr>
                  <th colspan="2">Total em aberto</th>
                  <th class="text-end text-nowrap">R$ {{ number_format((float) $emAberto->sum('valor'), 2, ',', '.') }}</th>
                  <th></th>
                </tr>
              </tfoot>
            </table>
          </div>

          @php
            // Abre já na página da primeira parcela ainda em aberto — num carnê de 48x,
            // é ali que está a informação que interessa, não na parcela 1 paga há dois anos.
            $indiceAtual = $parcelas->search(fn ($p) => $p->status === 'pendente');
          @endphp
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3"
               id="paginacao-parcelas" style="display:none !important;">
            <small class="text-muted" id="parcelas-resumo"></small>
            <div class="btn-group btn-group-sm" role="group" aria-label="Navegar pelas parcelas">
              <button type="button" class="btn btn-label-secondary" data-pagina="primeira" title="Primeira">&laquo;</button>
              <button type="button" class="btn btn-label-secondary" data-pagina="anterior" title="Anterior">&lsaquo;</button>
              <button type="button" class="btn btn-label-secondary" data-pagina="proxima" title="Próxima">&rsaquo;</button>
              <button type="button" class="btn btn-label-secondary" data-pagina="ultima" title="Última">&raquo;</button>
            </div>
          </div>
        </div>
      </div>

      <script>
      // Paginação sem dependência: os assets do DataTables só são carregados para
      // admin, e este card também aparece para o cliente e para o dono do processo.
      (function () {
        const tabela = document.getElementById('tabela-parcelas');
        const barra  = document.getElementById('paginacao-parcelas');
        if (! tabela || ! barra) return;

        const linhas = Array.from(tabela.querySelectorAll('tbody tr[data-parcela]'));
        const POR_PAGINA = 10;
        if (linhas.length <= POR_PAGINA) return;   // cabe inteiro, sem paginação

        const resumoEl = document.getElementById('parcelas-resumo');
        const totalPaginas = Math.ceil(linhas.length / POR_PAGINA);
        const indiceAtual = @json($indiceAtual === false ? null : $indiceAtual);
        let pagina = indiceAtual === null ? 1 : Math.floor(indiceAtual / POR_PAGINA) + 1;

        function render() {
          pagina = Math.min(Math.max(pagina, 1), totalPaginas);
          const ini = (pagina - 1) * POR_PAGINA;
          const fim = Math.min(ini + POR_PAGINA, linhas.length);

          linhas.forEach((tr, i) => { tr.style.display = (i >= ini && i < fim) ? '' : 'none'; });
          resumoEl.textContent = `Mostrando ${ini + 1}–${fim} de ${linhas.length} parcelas`
            + ` · página ${pagina} de ${totalPaginas}`;

          barra.querySelector('[data-pagina="primeira"]').disabled = pagina === 1;
          barra.querySelector('[data-pagina="anterior"]').disabled = pagina === 1;
          barra.querySelector('[data-pagina="proxima"]').disabled  = pagina === totalPaginas;
          barra.querySelector('[data-pagina="ultima"]').disabled   = pagina === totalPaginas;
        }

        barra.addEventListener('click', function (e) {
          const btn = e.target.closest('[data-pagina]');
          if (! btn || btn.disabled) return;
          const acao = btn.dataset.pagina;
          if (acao === 'primeira') pagina = 1;
          else if (acao === 'anterior') pagina--;
          else if (acao === 'proxima') pagina++;
          else if (acao === 'ultima') pagina = totalPaginas;
          render();
        });

        barra.style.removeProperty('display');
        render();
      })();
      </script>
    @endif

    @php $temEndereco = $processo->cep || $processo->logradouro || $processo->cidade; @endphp
    @if ($temEndereco)
      <div class="card mb-4">
        <div class="card-header border-bottom"><h5 class="card-title mb-0">Endereço</h5></div>
        <div class="card-body">
          <dl class="row mb-0">
            @if ($processo->cep)
              <dt class="col-sm-4 text-muted">CEP</dt>
              <dd class="col-sm-8">{{ $processo->cep }}</dd>
            @endif
            @if ($processo->logradouro || $processo->numero)
              <dt class="col-sm-4 text-muted">Logradouro</dt>
              <dd class="col-sm-8">{{ trim(($processo->logradouro ?? '') . ($processo->numero ? ', ' . $processo->numero : '')) }}</dd>
            @endif
            @if ($processo->complemento)
              <dt class="col-sm-4 text-muted">Complemento</dt>
              <dd class="col-sm-8">{{ $processo->complemento }}</dd>
            @endif
            @if ($processo->bairro)
              <dt class="col-sm-4 text-muted">Bairro</dt>
              <dd class="col-sm-8">{{ $processo->bairro }}</dd>
            @endif
            @if ($processo->cidade || $processo->uf)
              <dt class="col-sm-4 text-muted">Cidade/Estado</dt>
              <dd class="col-sm-8">{{ $processo->cidade }}{{ $processo->uf ? '/' . strtoupper($processo->uf) : '' }}</dd>
            @endif
          </dl>
        </div>
      </div>
    @endif

    @if ($processo->veiculo)
      @php $v = $processo->veiculo; @endphp
      <div class="card mb-4">
        <div class="card-header border-bottom d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0"><i class="icon-base ti tabler-car me-1"></i> Veículo</h5>
          @if ($v->placa)<span class="badge bg-label-primary">{{ preg_replace('/^([A-Z0-9]{3})([A-Z0-9]{4})$/', '$1-$2', strtoupper(preg_replace('/[^A-Z0-9]/', '', strtoupper($v->placa)))) }}</span>@endif
        </div>
        <div class="card-body">
          <dl class="row mb-0">
            <dt class="col-sm-4 text-muted">Marca/Modelo</dt>
            <dd class="col-sm-8 fw-medium">{{ $v->descricaoCurta() }}</dd>

            @if ($v->ano_fabricacao || $v->ano_modelo)
              <dt class="col-sm-4 text-muted">Ano fab./modelo</dt>
              <dd class="col-sm-8">{{ $v->ano_fabricacao ?: '—' }} / {{ $v->ano_modelo ?: '—' }}</dd>
            @endif
            @if ($v->cor)
              <dt class="col-sm-4 text-muted">Cor</dt>
              <dd class="col-sm-8">{{ $v->cor }}</dd>
            @endif
            @if ($v->combustivel)
              <dt class="col-sm-4 text-muted">Combustível</dt>
              <dd class="col-sm-8">{{ $v->combustivelLabel() }}</dd>
            @endif
            @if ($v->quilometragem !== null)
              <dt class="col-sm-4 text-muted">Quilometragem</dt>
              <dd class="col-sm-8">{{ number_format((int) $v->quilometragem, 0, ',', '.') }} km</dd>
            @endif
            @if ($v->chassi)
              <dt class="col-sm-4 text-muted">Chassi</dt>
              <dd class="col-sm-8"><code class="small">{{ strtoupper($v->chassi) }}</code></dd>
            @endif
            @if ($v->renavam)
              <dt class="col-sm-4 text-muted">RENAVAM</dt>
              <dd class="col-sm-8">{{ $v->renavam }}</dd>
            @endif
            @if ($v->valor_fipe)
              <dt class="col-sm-4 text-muted">Valor FIPE</dt>
              <dd class="col-sm-8 fw-semibold">R$ {{ number_format((float) $v->valor_fipe, 2, ',', '.') }}</dd>
            @endif
            @if ($v->observacoes)
              <dt class="col-sm-4 text-muted">Observações</dt>
              <dd class="col-sm-8">{{ $v->observacoes }}</dd>
            @endif
          </dl>
        </div>
      </div>
    @endif

    @if ($isAdmin)
      <div class="card mb-4">
        <div class="card-header border-bottom d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0">Comprador vinculado</h5>
          <a href="{{ route('processos.edit', $processo) }}" class="btn btn-sm btn-label-secondary">
            <i class="icon-base ti tabler-edit me-1"></i> Alterar
          </a>
        </div>
        <div class="card-body">
          @if ($processo->comprador)
            <dl class="row mb-0">
              <dt class="col-sm-4 text-muted">Nome</dt>
              <dd class="col-sm-8 fw-medium">{{ $processo->comprador->nome }}</dd>
              <dt class="col-sm-4 text-muted">Documento</dt>
              <dd class="col-sm-8">{{ strtoupper($processo->comprador->tipo_documento) }}: {{ $processo->comprador->documentoFormatado() }}</dd>
              <dt class="col-sm-4 text-muted">E-mail</dt>
              <dd class="col-sm-8">{{ $processo->comprador->email ?: '—' }}</dd>
              <dt class="col-sm-4 text-muted">Telefone</dt>
              <dd class="col-sm-8">{{ $processo->comprador->telefone ?: '—' }}</dd>
              @if ($processo->comprador->observacoes)
                <dt class="col-sm-4 text-muted">Observações</dt>
                <dd class="col-sm-8">{{ $processo->comprador->observacoes }}</dd>
              @endif
            </dl>
          @else
            <p class="text-muted mb-0">Nenhum comprador vinculado a este processo.</p>
          @endif
        </div>
      </div>

      {{-- ================= DÍVIDAS DO PROCESSO (Faturas) ================= --}}
      <div class="card mb-4">
        <div class="card-header border-bottom d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0"><i class="icon-base ti tabler-receipt me-1"></i> Dívidas do processo</h5>
          <button type="button" class="btn btn-sm btn-primary" id="btn-nova-fatura">
            <i class="icon-base ti tabler-plus me-1"></i> Nova dívida
          </button>
        </div>
        <div class="card-body">
          <table class="datatables-faturas dt-collapse-empty table dt-responsive" style="width:100%">
            <thead>
              <tr>
                <th>Descrição</th>
                <th>Valor</th>
                <th>Vencimento</th>
                <th>Status</th>
                <th class="text-end">Ações</th>
              </tr>
            </thead>
          </table>
        </div>
      </div>

      {{-- ================= COMISSÕES DO PROCESSO ================= --}}
      <div class="card mb-4">
        <div class="card-header border-bottom d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0"><i class="icon-base ti tabler-cash me-1"></i> Comissões do processo</h5>
          <button type="button" class="btn btn-sm btn-primary" id="btn-nova-comissao">
            <i class="icon-base ti tabler-plus me-1"></i> Nova comissão
          </button>
        </div>
        <div class="card-body">
          <table class="datatables-comissoes dt-collapse-empty table dt-responsive" style="width:100%">
            <thead>
              <tr>
                <th>Descrição</th>
                <th>Valor</th>
                <th>Tipo</th>
                <th>Data</th>
                <th>Status</th>
                <th class="text-end">Ações</th>
              </tr>
            </thead>
          </table>
        </div>
      </div>

      {{-- ================= NEGOCIAÇÃO DO CONTRATO ================= --}}
      @php
        $ultimaNegociacao = $processo->negociacoes->first();
        $valorEmMaosAtual = $ultimaNegociacao?->val_em_maos ?? 0;
      @endphp
      <div class="card mb-4">
        <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
          <div>
            <h5 class="card-title mb-0"><i class="icon-base ti tabler-message-2 me-1"></i> Negociação do contrato</h5>
            <small class="text-muted">Valor em mãos atual: <strong>R$ {{ number_format((float) $valorEmMaosAtual, 2, ',', '.') }}</strong></small>
          </div>
          <button type="button" class="btn btn-sm btn-primary" id="btn-nova-negociacao">
            <i class="icon-base ti tabler-plus me-1"></i> Registrar negociação
          </button>
        </div>
        <div class="card-body">
          <table class="datatables-negociacoes dt-collapse-empty table dt-responsive" style="width:100%">
            <thead>
              <tr>
                <th>Resumo</th>
                <th>Assessoria / contato</th>
                <th>Val. atual</th>
                <th>Pré-análise</th>
                <th>Val. em mãos</th>
                <th>Data</th>
                <th class="text-end">Ações</th>
              </tr>
            </thead>
          </table>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
          <div>
            <h5 class="card-title mb-0"><i class="icon-base ti tabler-notes me-1"></i> Observações internas (admin)</h5>
            <small class="text-muted">Notas visíveis apenas para a equipe administrativa.</small>
          </div>
          <button type="button" class="btn btn-sm btn-primary" id="btn-nova-observacao">
            <i class="icon-base ti tabler-plus me-1"></i> Nova observação
          </button>
        </div>
        <div class="card-body">
          <table class="datatables-observacoes table dt-responsive" style="width:100%">
            <thead>
              <tr>
                <th>Resumo</th>
                <th>Atualizado em</th>
                <th class="text-end">Ações</th>
              </tr>
            </thead>
          </table>
        </div>
      </div>

      {{-- Modal: criar/editar observação --}}
      <div class="modal fade" id="observacaoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
          <div class="modal-content">
            <form id="observacao-form" autocomplete="off" enctype="multipart/form-data">
              <div class="modal-header">
                <h5 class="modal-title" id="observacaoModalTitle">Nova observação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
              </div>
              <div class="modal-body">
                <div id="observacao-meta" class="alert alert-secondary py-2 small mb-3" style="display:none;"></div>
                <div id="observacao-error" class="alert alert-danger py-2 small mb-3" style="display:none;"></div>
                <input type="hidden" name="id" id="observacao-id">
                <input type="hidden" name="remover_anexo" id="observacao-remover-anexo" value="0">
                <div class="mb-3">
                  <label class="form-label">Resumo *</label>
                  <input type="text" class="form-control" name="resumo" id="observacao-resumo" required maxlength="200" placeholder="Ex.: Contato com credor; documento pendente">
                </div>
                <div class="mb-3">
                  <label class="form-label">Descrição *</label>
                  <textarea class="form-control" name="descricao" id="observacao-descricao" rows="6" required maxlength="5000" placeholder="Detalhes da observação..."></textarea>
                </div>

                {{-- Anexo existente (aparece só no editar quando há arquivo) --}}
                <div id="observacao-anexo-atual" class="mb-3" style="display:none;">
                  <label class="form-label small text-muted text-uppercase" style="letter-spacing:.05em;">Anexo atual</label>
                  <div class="d-flex align-items-center gap-2 p-2 border rounded bg-light">
                    <img id="observacao-anexo-thumb" src="" alt="" class="rounded" style="height:48px;width:48px;object-fit:cover;display:none;">
                    <i id="observacao-anexo-icon" class="icon-base ti tabler-file text-muted" style="font-size:1.75rem;display:none;"></i>
                    <div class="flex-grow-1 overflow-hidden">
                      <a id="observacao-anexo-link" href="#" target="_blank" class="fw-medium text-truncate d-block">arquivo</a>
                      <small id="observacao-anexo-meta" class="text-muted"></small>
                    </div>
                    <button type="button" class="btn btn-sm btn-label-danger" id="observacao-anexo-remover" title="Remover anexo">
                      <i class="icon-base ti tabler-trash"></i>
                    </button>
                  </div>
                </div>

                <div class="mb-0" id="observacao-anexo-upload">
                  <label class="form-label" for="observacao-anexo">
                    <i class="icon-base ti tabler-paperclip me-1"></i>
                    Anexar documento/imagem <span class="text-muted small">(opcional)</span>
                  </label>
                  <input type="file" class="form-control" name="anexo" id="observacao-anexo"
                         accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip">
                  <small class="text-muted">JPG, PNG, GIF, WEBP, PDF, DOC, XLS, TXT ou ZIP · até 10 MB</small>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="observacao-save-btn">
                  <i class="icon-base ti tabler-device-floppy me-1"></i> Salvar
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      {{-- Modal: criar/editar dívida (Fatura) --}}
      <div class="modal fade" id="faturaModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
          <div class="modal-content">
            <form id="fatura-form" autocomplete="off">
              <div class="modal-header">
                <h5 class="modal-title" id="faturaModalTitle">Nova dívida</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
              </div>
              <div class="modal-body">
                <div id="fatura-error" class="alert alert-danger py-2 small mb-3" style="display:none;"></div>
                <input type="hidden" name="id" id="fatura-id">
                <div class="mb-3">
                  <label class="form-label">Descrição</label>
                  <input type="text" class="form-control" name="descricao" id="fatura-descricao" maxlength="255" placeholder="Ex.: Entrada do serviço, dívida assumida do banco X">
                </div>
                <div class="row">
                  <div class="col-md-4 mb-3">
                    <label class="form-label">Valor da parcela (R$) *</label>
                    <input type="text" inputmode="numeric" class="form-control mask-money" name="valor" id="fatura-valor" required placeholder="0,00">
                  </div>
                  <div class="col-md-4 mb-3">
                    <label class="form-label">1º vencimento *</label>
                    <input type="text" class="form-control flatpickr-date" name="vencimento" id="fatura-vencimento" required placeholder="dd/mm/aaaa">
                  </div>
                  <div class="col-md-4 mb-3">
                    <label class="form-label">Status *</label>
                    <select class="form-select" name="status" id="fatura-status" required>
                      <option value="pendente">Pendente</option>
                      <option value="paga">Paga</option>
                      <option value="cancelada">Cancelada</option>
                    </select>
                  </div>
                </div>

                {{-- Parcelamento com a empresa: gera N cobranças mensais de uma vez --}}
                <div class="row" id="fatura-parcelamento-wrap">
                  <div class="col-md-4 mb-0">
                    <label class="form-label">Parcelar em</label>
                    <div class="input-group">
                      <input type="number" class="form-control" name="qtd_parcelas" id="fatura-qtd-parcelas"
                             min="1" max="120" value="1">
                      <span class="input-group-text">x</span>
                    </div>
                  </div>
                  <div class="col-md-8 mb-0 d-flex align-items-end">
                    <small class="text-muted" id="fatura-memoria">
                      Deixe em <strong>1</strong> para uma cobrança única.
                    </small>
                  </div>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="fatura-save-btn">
                  <i class="icon-base ti tabler-device-floppy me-1"></i> Salvar
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      {{-- Modal: criar/editar comissão --}}
      <div class="modal fade" id="comissaoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
          <div class="modal-content">
            <form id="comissao-form" autocomplete="off">
              <div class="modal-header">
                <h5 class="modal-title" id="comissaoModalTitle">Nova comissão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
              </div>
              <div class="modal-body">
                <div id="comissao-error" class="alert alert-danger py-2 small mb-3" style="display:none;"></div>
                <input type="hidden" name="id" id="comissao-id">
                <div class="row">
                  <div class="col-md-12 mb-3">
                    <label class="form-label">Usuário *</label>
                    <select name="licensed_by_user_id" id="comissao-usuario-select" class="form-select" required>
                      <option value=""></option>
                      @foreach ($usuariosParaComissao as $u)
                        <option value="{{ $u['id'] }}" data-role="{{ $u['role'] }}" data-email="{{ $u['email'] }}">{{ $u['name'] }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-md-8 mb-3">
                    <label class="form-label">Descrição *</label>
                    <input type="text" class="form-control" name="descricao" id="comissao-descricao" required maxlength="160" placeholder="Comissão do fechamento">
                  </div>
                  <div class="col-md-4 mb-3">
                    <label class="form-label">Valor (R$) *</label>
                    <input type="text" inputmode="numeric" class="form-control mask-money" name="valor" id="comissao-valor" required placeholder="0,00">
                  </div>
                  <div class="col-md-4 mb-0">
                    <label class="form-label">Tipo *</label>
                    <select class="form-select" name="tipo" id="comissao-tipo" required>
                      <option value="a_receber">A receber</option>
                      <option value="a_pagar">A pagar</option>
                    </select>
                  </div>
                  <div class="col-md-4 mb-0">
                    <label class="form-label">Data *</label>
                    <input type="text" class="form-control flatpickr-date" name="data_referencia" id="comissao-data" required placeholder="dd/mm/aaaa">
                  </div>
                  <div class="col-md-4 mb-0">
                    <label class="form-label">Status *</label>
                    <select class="form-select" name="status" id="comissao-status" required>
                      <option value="pendente">Pendente</option>
                      <option value="paga">Paga</option>
                      <option value="cancelada">Cancelada</option>
                    </select>
                  </div>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="comissao-save-btn">
                  <i class="icon-base ti tabler-device-floppy me-1"></i> Salvar
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      {{-- Modal: registrar/editar negociação --}}
      <div class="modal fade" id="negociacaoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
          <div class="modal-content">
            <form id="negociacao-form" autocomplete="off">
              <div class="modal-header">
                <h5 class="modal-title" id="negociacaoModalTitle">Registrar negociação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
              </div>
              <div class="modal-body">
                <div id="negociacao-meta" class="alert alert-secondary py-2 small mb-3" style="display:none;"></div>
                <div id="negociacao-error" class="alert alert-danger py-2 small mb-3" style="display:none;"></div>
                <input type="hidden" name="id" id="negociacao-id">
                <div class="row">
                  <div class="col-md-3 mb-3">
                    <label class="form-label">Data *</label>
                    <input type="text" class="form-control flatpickr-date" name="data" id="negociacao-data" required placeholder="dd/mm/aaaa">
                  </div>
                  <div class="col-md-9 mb-3">
                    <label class="form-label">Nome da assessoria</label>
                    <input type="text" class="form-control" name="assessoria" id="negociacao-assessoria" maxlength="120" placeholder="Ex.: JCS, Banco XYZ...">
                  </div>
                  <div class="col-md-4 mb-3">
                    <label class="form-label">Telefone</label>
                    <input type="text" class="form-control mask-phone" name="telefone" id="negociacao-telefone" maxlength="40" placeholder="(00) 00000-0000">
                  </div>
                  <div class="col-md-8 mb-3">
                    <label class="form-label">Com quem falou</label>
                    <input type="text" class="form-control" name="contato_nome" id="negociacao-contato" maxlength="120" placeholder="Nome do atendente/negociador">
                  </div>
                  <div class="col-md-4 mb-3">
                    <label class="form-label">Valor atual</label>
                    <input type="text" inputmode="numeric" class="form-control mask-money" name="val_atualizado" id="negociacao-val-atualizado" placeholder="0,00">
                    <small class="text-muted">Saldo devedor atualizado.</small>
                  </div>
                  <div class="col-md-4 mb-3">
                    <label class="form-label">Valor de pré-análise</label>
                    <input type="text" inputmode="numeric" class="form-control mask-money" name="val_analise" id="negociacao-val-analise" placeholder="0,00">
                    <small class="text-muted">Valor sugerido pela instituição.</small>
                  </div>
                  <div class="col-md-4 mb-3">
                    <label class="form-label">Valor em mãos</label>
                    <input type="text" inputmode="numeric" class="form-control mask-money" name="val_em_maos" id="negociacao-val-em-maos" placeholder="0,00">
                    <small class="text-muted">Proposta apresentada.</small>
                  </div>
                  <div class="col-12 mb-3">
                    <label class="form-label">Resumo da negociação *</label>
                    <textarea class="form-control" name="resumo" id="negociacao-resumo" rows="3" required maxlength="2000" placeholder="Plano de quitação..."></textarea>
                  </div>
                  <div class="col-12 mb-0">
                    <label class="form-label">Feedback (opcional)</label>
                    <textarea class="form-control" name="feedback" id="negociacao-feedback" rows="2" maxlength="1000" placeholder="Retorno do banco, próxima ação..."></textarea>
                  </div>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="negociacao-save-btn">
                  <i class="icon-base ti tabler-device-floppy me-1"></i> Salvar
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    @endif

    <div class="card mb-4">
      <div class="card-header border-bottom"><h5 class="card-title mb-0">Documentos</h5></div>
      <div class="card-body">
        @unless ($isComprador)
        <form method="POST" action="{{ route('processos.documentos.store', $processo) }}" enctype="multipart/form-data" class="row g-2 mb-4" id="form-documento">
          @csrf
          <div class="col-md-5">
            <input type="file" name="arquivo" id="documento-arquivo" class="form-control form-control-sm" required>
          </div>
          <div class="col-md-4">
            <input type="text" name="categoria" class="form-control form-control-sm" maxlength="80"
                   placeholder="Categoria (ex.: Contrato)" list="categorias-documento">
            <datalist id="categorias-documento">
              @foreach (['Contrato', 'Procuração', 'RG/CNH', 'CPF', 'Comprovante de residência', 'Comprovante de pagamento', 'Documento do veículo', 'Boletim de ocorrência', 'Petição', 'Outros'] as $cat)
                <option value="{{ $cat }}">
              @endforeach
            </datalist>
          </div>
          <div class="col-md-3"><button type="submit" class="btn btn-sm btn-primary w-100" id="documento-btn"><i class="icon-base ti tabler-upload me-1"></i> Enviar</button></div>
          <div class="col-12">
            <small class="text-muted" id="documento-info">Tamanho máximo: 20 MB por arquivo.</small>
          </div>
        </form>

        {{-- Barrar o arquivo grande AQUI evita o pior caso: acima do post_max_size o PHP
             descarta o corpo inteiro do POST, o token CSRF some junto e a tela devolve
             "página expirada" — que não diz ao usuário qual foi o problema real. --}}
        <script>
        (function () {
          const form = document.getElementById('form-documento');
          if (! form) return;
          const input = document.getElementById('documento-arquivo');
          const info  = document.getElementById('documento-info');
          const btn   = document.getElementById('documento-btn');
          const LIMITE = 20 * 1024 * 1024;

          const tamanho = (b) => b < 1024 * 1024
            ? (b / 1024).toFixed(1).replace('.', ',') + ' KB'
            : (b / 1024 / 1024).toFixed(2).replace('.', ',') + ' MB';

          function avaliar() {
            const f = input.files && input.files[0];
            if (! f) {
              info.className = 'text-muted';
              info.textContent = 'Tamanho máximo: 20 MB por arquivo.';
              btn.disabled = false;
              return true;
            }
            if (f.size > LIMITE) {
              info.className = 'text-danger fw-medium';
              info.textContent = `"${f.name}" tem ${tamanho(f.size)} e o limite é 20 MB. `
                + 'Comprima o arquivo ou envie em partes.';
              btn.disabled = true;
              return false;
            }
            info.className = 'text-muted';
            info.textContent = `${f.name} · ${tamanho(f.size)} — pronto para enviar.`;
            btn.disabled = false;
            return true;
          }

          input.addEventListener('change', avaliar);
          form.addEventListener('submit', function (e) {
            if (! avaliar()) { e.preventDefault(); return; }
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Enviando...';
          });
        })();
        </script>
        @endunless

        @if ($processo->documentos->isEmpty())
          <p class="text-muted mb-0">Nenhum documento enviado.</p>
        @else
          <ul class="list-group list-group-flush">
            @foreach ($processo->documentos as $doc)
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                  <i class="icon-base ti tabler-file me-2 text-muted"></i>
                  <span class="fw-medium">{{ $doc->nome_original }}</span>
                  @if ($doc->categoria)
                    <span class="badge bg-label-info ms-2">{{ $doc->categoria }}</span>
                  @endif
                  <div class="small text-muted">
                    Enviado por {{ $doc->uploadedBy?->name ?? 'sistema' }} • {{ $doc->tamanhoFormatado() }} • {{ $doc->created_at->format('d/m/Y H:i') }}
                  </div>
                </div>
                <div class="d-flex gap-1">
                  <a href="{{ route('processos.documentos.download', $doc) }}" class="btn btn-sm btn-icon btn-label-primary"><i class="icon-base ti tabler-download"></i></a>
                  @if (! $isComprador && ($isAdmin || $doc->uploaded_by_user_id === auth()->id()))
                    <form method="POST" action="{{ route('processos.documentos.destroy', $doc) }}" onsubmit="return confirm('Excluir documento?')">
                      @csrf @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-icon btn-label-danger"><i class="icon-base ti tabler-trash"></i></button>
                    </form>
                  @endif
                </div>
              </li>
            @endforeach
          </ul>
        @endif
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    @if ($isAdmin)
      <div class="card mb-4">
        <div class="card-header border-bottom"><h5 class="card-title mb-0">Alterar status</h5></div>
        <div class="card-body">
          <form method="POST" action="{{ route('processos.status', $processo) }}">
            @csrf @method('PATCH')
            <div class="mb-3">
              <label class="form-label">Novo status</label>
              <select name="status" id="status-select" class="form-select" required>
                @foreach ($statuses as $v => [$label, $color])
                  <option value="{{ $v }}" @selected($processo->status === $v)>{{ $label }}</option>
                @endforeach
              </select>
            </div>
            <div class="mb-3" id="liminar-date-wrap" style="display:none;">
              <label class="form-label">Data do protocolo da liminar</label>
              <input type="text" name="data_protocolo_liminar" class="form-control flatpickr-date" value="{{ $processo->data_protocolo_liminar?->toDateString() ?: now()->toDateString() }}" placeholder="dd/mm/aaaa">
              <small class="text-muted">A previsão de conclusão será definida para 45 dias depois.</small>
            </div>
            <div class="mb-3">
              <label class="form-label">Observação (opcional)</label>
              <textarea name="observacao" class="form-control" rows="3" maxlength="1000" placeholder="Detalhes sobre essa mudança..."></textarea>
            </div>
            <button type="submit" class="btn btn-primary w-100"><i class="icon-base ti tabler-refresh me-1"></i> Atualizar status</button>
          </form>
        </div>
      </div>
    @endif

    {{-- Log interno: registra comissões, negociações e observações da equipe.
         Fica fora do alcance de quem só acompanha o processo de fora (cliente/comprador). --}}
    @if ($isAdmin || $isOwner)
    <div class="card">
      <div class="card-header border-bottom d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0"><i class="icon-base ti tabler-history me-1"></i> Log de atividades</h5>
        <span class="badge bg-label-secondary">{{ $processo->historico->count() }}</span>
      </div>
      <div class="card-body" style="max-height: 700px; overflow-y: auto;">
        @if ($processo->historico->isEmpty())
          <p class="text-muted mb-0">Nenhuma atividade registrada.</p>
        @else
          <ul class="timeline mb-0">
            @foreach ($processo->historico as $h)
              <li class="timeline-item timeline-item-transparent">
                <span class="timeline-point timeline-point-{{ $h->cor() }}"></span>
                <div class="timeline-event pb-3">
                  <div class="timeline-header d-flex justify-content-between align-items-baseline gap-2 mb-1">
                    <h6 class="mb-0 small text-uppercase d-flex align-items-center gap-1" style="letter-spacing: .04em;">
                      <i class="icon-base ti {{ $h->icone() }} text-{{ $h->cor() }}"></i>
                      {{ $h->acaoLabel() }}
                    </h6>
                    <small class="text-muted text-nowrap">{{ $h->created_at->format('d/m/Y H:i') }}</small>
                  </div>
                  @if ($h->observacao)
                    <p class="mb-1 small">{{ $h->observacao }}</p>
                  @endif
                  <small class="text-muted">
                    <i class="icon-base ti tabler-user" style="font-size:.72rem;"></i>
                    {{ $h->user?->name ?? 'sistema' }}
                  </small>
                </div>
              </li>
            @endforeach
          </ul>
        @endif
      </div>
    </div>
    @endif
  </div>
</div>
@endsection

@if ($isAdmin)
@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const sel = document.getElementById('status-select');
  const wrap = document.getElementById('liminar-date-wrap');
  function toggle() { wrap.style.display = sel.value === 'liminar_protocolada' ? '' : 'none'; }
  sel.addEventListener('change', toggle);
  toggle();

  // Flatpickr nos inputs de data dos forms inline (Nova fatura, Nova comissão, Status)
  if (window.flatpickr) {
    flatpickr('.flatpickr-date, input[name="data_protocolo_liminar"]', {
      altInput: true,
      altFormat: 'd/m/Y',
      dateFormat: 'Y-m-d',
      allowInput: true,
    });
  }

  // Select2 do "Usuário" no form de comissão inline, com badge colorida por role
  if (window.jQuery && jQuery('#comissao-usuario-select').length) {
    const roleMap = {
      admin:      { color: 'danger',    label: 'Admin' },
      mentorado:  { color: 'info',      label: 'Mentorado' },
      licenciado: { color: 'success',   label: 'Licenciado' },
      comprador:  { color: 'primary',   label: 'Comprador' },
      sem_role:   { color: 'secondary', label: '—' },
    };

    // No DROPDOWN (item da lista): 3 linhas — badge, nome, email (empilhados)
    function renderResult(option) {
      if (! option.id) return option.text;
      const $opt  = jQuery(option.element);
      const role  = $opt.data('role') || 'sem_role';
      const email = $opt.data('email') || '';
      const info  = roleMap[role] || roleMap.sem_role;
      return jQuery(
        `<div class="usuario-option">
           <span class="badge bg-label-${info.color} usuario-badge mb-1">${info.label}</span>
           <div class="fw-medium text-truncate usuario-nome">${option.text}</div>
           ${email ? `<small class="text-muted d-block text-truncate usuario-email">${email}</small>` : ''}
         </div>`
      );
    }

    // NO CAMPO SELECIONADO (fechado): 1 linha compacta — badge + nome, sem email
    function renderSelection(option) {
      if (! option.id) return option.text;
      const $opt = jQuery(option.element);
      const role = $opt.data('role') || 'sem_role';
      const info = roleMap[role] || roleMap.sem_role;
      return jQuery(
        `<span class="d-inline-flex align-items-center gap-2">
           <span class="badge bg-label-${info.color} usuario-badge">${info.label}</span>
           <span class="fw-medium text-truncate">${option.text}</span>
         </span>`
      );
    }

    const $sel = jQuery('#comissao-usuario-select');
    $sel.select2({
      placeholder: 'Selecione o usuário',
      allowClear: true,
      width: '100%',
      dropdownParent: jQuery('#comissaoModal'),
      templateResult: renderResult,
      templateSelection: renderSelection,
      escapeMarkup: m => m,
    });
  }

  // ================================================================
  // OBSERVAÇÕES — DataTable + Modal (criar / editar / excluir)
  // ================================================================
  (function () {
    const table = document.querySelector('.datatables-observacoes');
    if (! table || ! window.DataTable) return;

    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const dtUrl     = @json(route('processos.observacoes.datatable', $processo));
    const storeUrl  = @json(route('processos.observacoes.store', $processo));
    const baseObs   = @json(url('painel/processos/observacoes'));

    const modalEl   = document.getElementById('observacaoModal');
    const modal     = new bootstrap.Modal(modalEl);
    const form      = document.getElementById('observacao-form');
    const titleEl   = document.getElementById('observacaoModalTitle');
    const idEl      = document.getElementById('observacao-id');
    const resumoEl  = document.getElementById('observacao-resumo');
    const descEl    = document.getElementById('observacao-descricao');
    const metaEl    = document.getElementById('observacao-meta');
    const errorEl   = document.getElementById('observacao-error');
    const saveBtn   = document.getElementById('observacao-save-btn');

    const escapeHtml = (s) => String(s ?? '').replace(/[&<>"']/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]));

    const dt = new DataTable(table, {
      processing: true, serverSide: true, responsive: true,
      searching: false, lengthChange: false,
      pageLength: 10,
      ajax: { url: dtUrl },
      columns: [
        {
          data: null, responsivePriority: 1,
          render: (row) => {
            const clip = row.tem_anexo
              ? `<i class="icon-base ti ${row.anexo_is_image ? 'tabler-photo' : 'tabler-paperclip'} text-primary me-1" title="Com anexo"></i>`
              : '';
            return clip + escapeHtml(row.resumo || '');
          },
        },
        {
          data: null, responsivePriority: 2, orderable: false, searchable: false, className: 'text-nowrap',
          render: (row) => {
            const foiEditada = !! row.editada_em_formatada;
            const data   = foiEditada ? row.editada_em_formatada : (row.criada_em || '');
            const autor  = (foiEditada ? row.editada_por_nome : row.inserida_por_nome) || '—';
            const rotulo = foiEditada ? 'editado por' : 'criado por';
            return `<div>${escapeHtml(data)}</div><small class="text-muted">${rotulo} ${escapeHtml(autor)}</small>`;
          },
        },
        {
          data: 'id', responsivePriority: 1,
          orderable: false, searchable: false, className: 'text-end text-nowrap',
          render: id => `
            <div class="d-inline-flex flex-nowrap gap-1 justify-content-end">
              <button class="btn btn-sm btn-icon obs-edit" data-id="${id}" title="Visualizar"><i class="icon-base ti tabler-eye icon-22px"></i></button>
              <button class="btn btn-sm btn-icon obs-delete text-danger" data-id="${id}" title="Remover"><i class="icon-base ti tabler-trash icon-22px"></i></button>
            </div>`,
        },
      ],
      order: [],
      language: { processing: 'Carregando...', info: 'Exibindo _START_ a _END_ de _TOTAL_', infoEmpty: 'Nenhum registro', zeroRecords: 'Nenhuma observação encontrada', emptyTable: 'Nenhuma observação cadastrada', paginate: { first: '«', previous: '‹', next: '›', last: '»' } },
      layout: { topStart: null, topEnd: null },
    });

    // Elementos do anexo
    const anexoInput   = document.getElementById('observacao-anexo');
    const anexoAtual   = document.getElementById('observacao-anexo-atual');
    const anexoThumb   = document.getElementById('observacao-anexo-thumb');
    const anexoIcon    = document.getElementById('observacao-anexo-icon');
    const anexoLink    = document.getElementById('observacao-anexo-link');
    const anexoMeta    = document.getElementById('observacao-anexo-meta');
    const anexoRemover = document.getElementById('observacao-anexo-remover');
    const anexoFlag    = document.getElementById('observacao-remover-anexo');

    function limparAnexoAtual() {
      anexoAtual.style.display = 'none';
      anexoThumb.style.display = 'none';
      anexoIcon.style.display = 'none';
      anexoThumb.src = '';
      anexoLink.href = '#';
      anexoLink.textContent = '';
      anexoMeta.textContent = '';
    }

    function preencherAnexoAtual(anexo) {
      anexoLink.href = anexo.url;
      anexoLink.textContent = anexo.nome;
      anexoMeta.textContent = anexo.tamanho + ' · ' + (anexo.mime || 'arquivo');
      if (anexo.is_image) {
        anexoThumb.src = anexo.url;
        anexoThumb.style.display = '';
        anexoIcon.style.display = 'none';
      } else {
        anexoIcon.style.display = '';
        anexoThumb.style.display = 'none';
      }
      anexoAtual.style.display = '';
    }

    anexoRemover.addEventListener('click', function () {
      anexoFlag.value = '1';
      limparAnexoAtual();
    });

    function resetForm() {
      idEl.value = '';
      resumoEl.value = '';
      descEl.value = '';
      metaEl.style.display = 'none';
      metaEl.innerHTML = '';
      errorEl.style.display = 'none';
      errorEl.innerHTML = '';
      anexoInput.value = '';
      anexoFlag.value = '0';
      limparAnexoAtual();
    }

    function openNew() {
      resetForm();
      titleEl.textContent = 'Nova observação';
      modal.show();
      setTimeout(() => resumoEl.focus(), 200);
    }

    async function openEdit(id) {
      resetForm();
      titleEl.textContent = 'Editar observação';
      idEl.value = id;
      try {
        const r = await fetch(`${baseObs}/${id}`, { headers: { Accept: 'application/json' } });
        if (! r.ok) throw new Error('Não foi possível carregar a observação.');
        const d = await r.json();
        resumoEl.value = d.resumo || '';
        descEl.value = d.descricao || '';
        const linhas = [];
        if (d.inserida_por || d.criada_em) linhas.push(`<strong>Inserida por:</strong> ${escapeHtml(d.inserida_por || '—')}${d.criada_em ? ' em ' + escapeHtml(d.criada_em) : ''}`);
        if (d.editada_por || d.editada_em) linhas.push(`<strong>Última edição:</strong> ${escapeHtml(d.editada_por || '—')}${d.editada_em ? ' em ' + escapeHtml(d.editada_em) : ''}`);
        if (linhas.length) {
          metaEl.innerHTML = linhas.join('<br>');
          metaEl.style.display = '';
        }
        if (d.anexo) preencherAnexoAtual(d.anexo);
        modal.show();
        setTimeout(() => resumoEl.focus(), 200);
      } catch (err) {
        Swal.fire({ icon: 'error', title: 'Erro', text: err.message, customClass: { confirmButton: 'btn btn-danger' }, buttonsStyling: false });
      }
    }

    document.getElementById('btn-nova-observacao').addEventListener('click', openNew);

    table.addEventListener('click', function (e) {
      const editBtn = e.target.closest('.obs-edit');
      if (editBtn) { openEdit(editBtn.dataset.id); return; }
      const delBtn = e.target.closest('.obs-delete');
      if (delBtn) {
        const id = delBtn.dataset.id;
        Swal.fire({
          title: 'Excluir observação?', text: 'Esta ação não pode ser desfeita.', icon: 'warning',
          showCancelButton: true, confirmButtonText: 'Sim, excluir', cancelButtonText: 'Cancelar',
          customClass: { confirmButton: 'btn btn-danger me-3', cancelButton: 'btn btn-label-secondary' }, buttonsStyling: false,
        }).then(r => {
          if (! r.value) return;
          fetch(`${baseObs}/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' } })
            .then(r => r.json().then(b => ({ ok: r.ok, body: b })))
            .then(({ ok, body }) => {
              if (! ok) throw new Error(body.message || 'Erro ao excluir');
              dt.draw(false);
              Swal.fire({ icon: 'success', title: 'Excluída', text: body.message, customClass: { confirmButton: 'btn btn-success' }, buttonsStyling: false });
            })
            .catch(err => Swal.fire({ icon: 'error', title: 'Erro', text: err.message, customClass: { confirmButton: 'btn btn-danger' }, buttonsStyling: false }));
        });
      }
    });

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      errorEl.style.display = 'none';
      errorEl.innerHTML = '';
      const id = idEl.value;
      const isEdit = !! id;
      const url = isEdit ? `${baseObs}/${id}` : storeUrl;

      // FormData + method spoofing (PATCH via POST) — necessário para upload multipart
      const fd = new FormData();
      fd.append('resumo', resumoEl.value.trim());
      fd.append('descricao', descEl.value.trim());
      fd.append('remover_anexo', anexoFlag.value);
      if (anexoInput.files[0]) fd.append('anexo', anexoInput.files[0]);
      if (isEdit) fd.append('_method', 'PATCH');

      saveBtn.disabled = true;
      fetch(url, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
        body: fd,
      })
        .then(r => r.json().then(b => ({ ok: r.ok, status: r.status, body: b })))
        .then(({ ok, status, body }) => {
          if (! ok) {
            if (status === 422 && body.errors) {
              const msgs = Object.values(body.errors).flat().map(escapeHtml).join('<br>');
              errorEl.innerHTML = msgs;
              errorEl.style.display = '';
              return;
            }
            throw new Error(body.message || 'Erro ao salvar');
          }
          modal.hide();
          dt.draw(false);
          Swal.fire({ icon: 'success', title: isEdit ? 'Atualizada' : 'Registrada', text: body.message, timer: 1600, showConfirmButton: false });
        })
        .catch(err => {
          errorEl.textContent = err.message;
          errorEl.style.display = '';
        })
        .finally(() => { saveBtn.disabled = false; });
    });
  })();

  // ================================================================
  // Helpers reutilizados nas três próximas seções
  // ================================================================
  const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
  const escapeHtml = (s) => String(s ?? '').replace(/[&<>"']/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]));

  // Aceita "1.234,56" (BR) OU "1234.56" (EN, já normalizado pelo listener global de submit)
  function moneyToRaw(v) {
    const s = String(v || '').trim();
    if (! s) return null;
    const cleaned = s.includes(',') ? s.replace(/\./g, '').replace(',', '.') : s;
    const n = parseFloat(cleaned);
    return isNaN(n) ? null : n.toFixed(2);
  }

  // Reaplica máscara em campos monetários após setar valores programaticamente
  function refreshMasks() {
    document.dispatchEvent(new CustomEvent('mask:refresh'));
  }

  function setDate(inputEl, iso) {
    if (! inputEl) return;
    if (inputEl._flatpickr) inputEl._flatpickr.setDate(iso || null, true);
    else inputEl.value = iso || '';
  }

  function readDate(inputEl) {
    if (! inputEl) return '';
    const fp = inputEl._flatpickr;
    if (fp && fp.selectedDates[0]) return fp.formatDate(fp.selectedDates[0], 'Y-m-d');
    return inputEl.value || '';
  }

  function commonSubmit({ url, method, payload, saveBtn, errorEl, onOk }) {
    saveBtn.disabled = true;
    return fetch(url, {
      method,
      headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', Accept: 'application/json' },
      body: JSON.stringify(payload),
    })
      .then(r => r.json().then(b => ({ ok: r.ok, status: r.status, body: b })))
      .then(({ ok, status, body }) => {
        if (! ok) {
          if (status === 422 && body.errors) {
            errorEl.innerHTML = Object.values(body.errors).flat().map(escapeHtml).join('<br>');
            errorEl.style.display = '';
            return;
          }
          throw new Error(body.message || 'Erro ao salvar');
        }
        onOk(body);
      })
      .catch(err => {
        errorEl.textContent = err.message;
        errorEl.style.display = '';
      })
      .finally(() => {
        saveBtn.disabled = false;
        // O listener global converteu campos mask-money para valor cru antes do submit;
        // restaura formatação visual caso o modal continue aberto (erro/validação).
        refreshMasks();
      });
  }

  function commonDelete({ url, titulo, texto, onOk }) {
    Swal.fire({
      title: titulo, text: texto, icon: 'warning',
      showCancelButton: true, confirmButtonText: 'Sim, excluir', cancelButtonText: 'Cancelar',
      customClass: { confirmButton: 'btn btn-danger me-3', cancelButton: 'btn btn-label-secondary' }, buttonsStyling: false,
    }).then(r => {
      if (! r.value) return;
      fetch(url, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' } })
        .then(r => r.json().then(b => ({ ok: r.ok, body: b })))
        .then(({ ok, body }) => {
          if (! ok) throw new Error(body.message || 'Erro ao excluir');
          onOk();
          Swal.fire({ icon: 'success', title: 'Excluída', text: body.message, timer: 1600, showConfirmButton: false });
        })
        .catch(err => Swal.fire({ icon: 'error', title: 'Erro', text: err.message, customClass: { confirmButton: 'btn btn-danger' }, buttonsStyling: false }));
    });
  }

  // ================================================================
  // DÍVIDAS (Faturas) — DataTable + Modal
  // ================================================================
  (function () {
    const table = document.querySelector('.datatables-faturas');
    if (! table || ! window.DataTable) return;

    const dtUrl    = @json(route('processos.faturas.datatable', $processo));
    const storeUrl = @json(route('processos.faturas.store', $processo));
    const baseUrl  = @json(url('painel/processos/faturas'));

    const modalEl  = document.getElementById('faturaModal');
    const modal    = new bootstrap.Modal(modalEl);
    const form     = document.getElementById('fatura-form');
    const titleEl  = document.getElementById('faturaModalTitle');
    const idEl     = document.getElementById('fatura-id');
    const descEl   = document.getElementById('fatura-descricao');
    const valorEl  = document.getElementById('fatura-valor');
    const vencEl   = document.getElementById('fatura-vencimento');
    const statusEl = document.getElementById('fatura-status');
    const errorEl  = document.getElementById('fatura-error');
    const saveBtn  = document.getElementById('fatura-save-btn');
    const qtdEl    = document.getElementById('fatura-qtd-parcelas');
    const parcWrap = document.getElementById('fatura-parcelamento-wrap');
    const memoriaEl = document.getElementById('fatura-memoria');

    const dt = new DataTable(table, {
      processing: true, serverSide: true, responsive: true,
      searching: false, lengthChange: false, pageLength: 10,
      ajax: { url: dtUrl },
      columns: [
        { data: 'descricao_fmt', responsivePriority: 1, render: v => escapeHtml(v) },
        { data: 'valor_fmt', responsivePriority: 2, className: 'fw-semibold text-nowrap' },
        { data: 'vencimento_fmt', responsivePriority: 3, className: 'text-nowrap' },
        { data: 'status_badge', responsivePriority: 2, orderable: false, searchable: false },
        {
          data: 'id', responsivePriority: 1, orderable: false, searchable: false, className: 'text-end text-nowrap',
          render: id => `
            <div class="d-inline-flex flex-nowrap gap-1 justify-content-end">
              <button class="btn btn-sm btn-icon fatura-edit" data-id="${id}" title="Visualizar"><i class="icon-base ti tabler-eye icon-22px"></i></button>
              <button class="btn btn-sm btn-icon fatura-delete text-danger" data-id="${id}" title="Remover"><i class="icon-base ti tabler-trash icon-22px"></i></button>
            </div>`,
        },
      ],
      order: [],
      language: { processing: 'Carregando...', info: 'Exibindo _START_ a _END_ de _TOTAL_', infoEmpty: 'Nenhum registro', zeroRecords: 'Nenhuma dívida encontrada', emptyTable: 'Nenhuma dívida cadastrada', paginate: { first: '«', previous: '‹', next: '›', last: '»' } },
      layout: { topStart: null, topEnd: null },
    });

    // Resumo do parcelamento com a empresa — total e mês do último vencimento
    function atualizarMemoria() {
      const qtd = parseInt(qtdEl.value || '1', 10);
      const valor = moneyToRaw(valorEl.value);
      if (! qtd || qtd < 2 || ! valor) {
        memoriaEl.innerHTML = 'Deixe em <strong>1</strong> para uma cobrança única.';
        return;
      }
      const total = (parseFloat(valor) || 0) * qtd;
      const fmt = (n) => 'R$ ' + Number(n).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      const iso = readDate(vencEl);
      let ultimo = '';
      if (iso) {
        const d = new Date(iso + 'T00:00:00');
        const alvo = new Date(d.getFullYear(), d.getMonth() + (qtd - 1), 1);
        // Dia original, limitado ao último dia do mês de destino
        const ultimoDia = new Date(alvo.getFullYear(), alvo.getMonth() + 1, 0).getDate();
        alvo.setDate(Math.min(d.getDate(), ultimoDia));
        ultimo = ' · última em ' + alvo.toLocaleDateString('pt-BR');
      }
      memoriaEl.innerHTML = `Serão criadas <strong>${qtd}</strong> cobranças mensais de `
        + `<strong>${fmt(valor)}</strong> — total ${fmt(total)}${ultimo}`;
    }

    function resetForm(defaults = {}) {
      idEl.value = '';
      descEl.value = defaults.descricao || '';
      valorEl.value = defaults.valor || '';
      setDate(vencEl, defaults.vencimento || '');
      statusEl.value = defaults.status || 'pendente';
      qtdEl.value = 1;
      errorEl.style.display = 'none'; errorEl.innerHTML = '';
      refreshMasks();
      atualizarMemoria();
    }

    [qtdEl, valorEl, vencEl].forEach(el => {
      el.addEventListener('input', atualizarMemoria);
      el.addEventListener('change', atualizarMemoria);
    });

    function openNew() {
      resetForm({
        vencimento: '{{ now()->addDays(7)->toDateString() }}',
        valor: {!! json_encode($processo->servico?->valor_padrao ? number_format((float) $processo->servico->valor_padrao, 2, ',', '.') : '') !!},
      });
      titleEl.textContent = 'Nova dívida';
      parcWrap.style.display = '';   // parcelar só ao criar
      modal.show();
    }

    function openEdit(id) {
      resetForm();
      titleEl.textContent = 'Editar dívida';
      idEl.value = id;
      parcWrap.style.display = 'none';   // editar mexe numa parcela só
      fetch(`${baseUrl}/${id}`, { headers: { Accept: 'application/json' } })
        .then(r => { if (! r.ok) throw new Error('Não foi possível carregar a dívida.'); return r.json(); })
        .then(d => {
          descEl.value = d.descricao || '';
          valorEl.value = d.valor || '';
          setDate(vencEl, d.vencimento);
          statusEl.value = d.status || 'pendente';
          refreshMasks();
          modal.show();
        })
        .catch(err => Swal.fire({ icon: 'error', title: 'Erro', text: err.message, customClass: { confirmButton: 'btn btn-danger' }, buttonsStyling: false }));
    }

    document.getElementById('btn-nova-fatura').addEventListener('click', openNew);

    table.addEventListener('click', function (e) {
      const editBtn = e.target.closest('.fatura-edit');
      if (editBtn) return openEdit(editBtn.dataset.id);
      const delBtn = e.target.closest('.fatura-delete');
      if (delBtn) {
        commonDelete({
          url: `${baseUrl}/${delBtn.dataset.id}`,
          titulo: 'Excluir dívida?',
          texto: 'Esta ação não pode ser desfeita.',
          onOk: () => dt.draw(false),
        });
      }
    });

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      const id = idEl.value;
      const isEdit = !! id;
      commonSubmit({
        url: isEdit ? `${baseUrl}/${id}` : storeUrl,
        method: isEdit ? 'PATCH' : 'POST',
        payload: {
          descricao: descEl.value.trim() || null,
          valor: moneyToRaw(valorEl.value),
          vencimento: readDate(vencEl),
          status: statusEl.value,
          ...(isEdit ? {} : { qtd_parcelas: parseInt(qtdEl.value || '1', 10) }),
        },
        saveBtn, errorEl,
        onOk: (body) => {
          modal.hide();
          dt.draw(false);
          Swal.fire({ icon: 'success', title: isEdit ? 'Atualizada' : 'Registrada', text: body.message, timer: 2200, showConfirmButton: false });
        },
      });
    });
  })();

  // ================================================================
  // COMISSÕES — DataTable + Modal
  // ================================================================
  (function () {
    const table = document.querySelector('.datatables-comissoes');
    if (! table || ! window.DataTable) return;

    const dtUrl    = @json(route('processos.comissoes.datatable', $processo));
    const storeUrl = @json(route('processos.comissoes.store', $processo));
    const baseUrl  = @json(url('painel/processos/comissoes'));

    const modalEl  = document.getElementById('comissaoModal');
    const modal    = new bootstrap.Modal(modalEl);
    const form     = document.getElementById('comissao-form');
    const titleEl  = document.getElementById('comissaoModalTitle');
    const idEl     = document.getElementById('comissao-id');
    const $userEl  = jQuery('#comissao-usuario-select');
    const descEl   = document.getElementById('comissao-descricao');
    const valorEl  = document.getElementById('comissao-valor');
    const tipoEl   = document.getElementById('comissao-tipo');
    const dataEl   = document.getElementById('comissao-data');
    const statusEl = document.getElementById('comissao-status');
    const errorEl  = document.getElementById('comissao-error');
    const saveBtn  = document.getElementById('comissao-save-btn');

    const dt = new DataTable(table, {
      processing: true, serverSide: true, responsive: true,
      searching: false, lengthChange: false, pageLength: 10,
      ajax: { url: dtUrl },
      columns: [
        {
          data: null, responsivePriority: 1, orderable: false, searchable: false,
          render: (row) => `<div>${escapeHtml(row.descricao_fmt || '')}</div><small class="text-muted">${escapeHtml(row.usuario_nome || '—')}</small>`,
        },
        { data: 'valor_fmt', responsivePriority: 2, className: 'fw-semibold text-nowrap' },
        { data: 'tipo_badge', responsivePriority: 3, orderable: false, searchable: false },
        { data: 'data_fmt', responsivePriority: 4, className: 'text-nowrap' },
        { data: 'status_badge', responsivePriority: 2, orderable: false, searchable: false },
        {
          data: 'id', responsivePriority: 1, orderable: false, searchable: false, className: 'text-end text-nowrap',
          render: id => `
            <div class="d-inline-flex flex-nowrap gap-1 justify-content-end">
              <button class="btn btn-sm btn-icon comissao-edit" data-id="${id}" title="Visualizar"><i class="icon-base ti tabler-eye icon-22px"></i></button>
              <button class="btn btn-sm btn-icon comissao-delete text-danger" data-id="${id}" title="Remover"><i class="icon-base ti tabler-trash icon-22px"></i></button>
            </div>`,
        },
      ],
      order: [],
      language: { processing: 'Carregando...', info: 'Exibindo _START_ a _END_ de _TOTAL_', infoEmpty: 'Nenhum registro', zeroRecords: 'Nenhuma comissão encontrada', emptyTable: 'Nenhuma comissão cadastrada', paginate: { first: '«', previous: '‹', next: '›', last: '»' } },
      layout: { topStart: null, topEnd: null },
    });

    function resetForm(defaults = {}) {
      idEl.value = '';
      $userEl.val(defaults.licensed_by_user_id || null).trigger('change');
      descEl.value = defaults.descricao || '';
      valorEl.value = defaults.valor || '';
      tipoEl.value = defaults.tipo || 'a_receber';
      setDate(dataEl, defaults.data_referencia || '');
      statusEl.value = defaults.status || 'pendente';
      errorEl.style.display = 'none'; errorEl.innerHTML = '';
      refreshMasks();
    }

    function openNew() {
      resetForm({ data_referencia: '{{ now()->toDateString() }}' });
      titleEl.textContent = 'Nova comissão';
      modal.show();
    }

    function openEdit(id) {
      resetForm();
      titleEl.textContent = 'Editar comissão';
      idEl.value = id;
      fetch(`${baseUrl}/${id}`, { headers: { Accept: 'application/json' } })
        .then(r => { if (! r.ok) throw new Error('Não foi possível carregar a comissão.'); return r.json(); })
        .then(d => {
          $userEl.val(d.licensed_by_user_id).trigger('change');
          descEl.value = d.descricao || '';
          valorEl.value = d.valor || '';
          tipoEl.value = d.tipo || 'a_receber';
          setDate(dataEl, d.data_referencia);
          statusEl.value = d.status || 'pendente';
          refreshMasks();
          modal.show();
        })
        .catch(err => Swal.fire({ icon: 'error', title: 'Erro', text: err.message, customClass: { confirmButton: 'btn btn-danger' }, buttonsStyling: false }));
    }

    document.getElementById('btn-nova-comissao').addEventListener('click', openNew);

    table.addEventListener('click', function (e) {
      const editBtn = e.target.closest('.comissao-edit');
      if (editBtn) return openEdit(editBtn.dataset.id);
      const delBtn = e.target.closest('.comissao-delete');
      if (delBtn) {
        commonDelete({
          url: `${baseUrl}/${delBtn.dataset.id}`,
          titulo: 'Excluir comissão?',
          texto: 'Esta ação não pode ser desfeita.',
          onOk: () => dt.draw(false),
        });
      }
    });

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      const id = idEl.value;
      const isEdit = !! id;
      commonSubmit({
        url: isEdit ? `${baseUrl}/${id}` : storeUrl,
        method: isEdit ? 'PATCH' : 'POST',
        payload: {
          licensed_by_user_id: $userEl.val() || null,
          descricao: descEl.value.trim(),
          valor: moneyToRaw(valorEl.value),
          tipo: tipoEl.value,
          data_referencia: readDate(dataEl),
          status: statusEl.value,
        },
        saveBtn, errorEl,
        onOk: (body) => {
          modal.hide();
          dt.draw(false);
          Swal.fire({ icon: 'success', title: isEdit ? 'Atualizada' : 'Registrada', text: body.message, timer: 1600, showConfirmButton: false });
        },
      });
    });
  })();

  // ================================================================
  // NEGOCIAÇÕES — DataTable + Modal
  // ================================================================
  (function () {
    const table = document.querySelector('.datatables-negociacoes');
    if (! table || ! window.DataTable) return;

    const dtUrl    = @json(route('processos.negociacoes.datatable', $processo));
    const storeUrl = @json(route('processos.negociacoes.store', $processo));
    const baseUrl  = @json(url('painel/processos/negociacoes'));

    const modalEl    = document.getElementById('negociacaoModal');
    const modal      = new bootstrap.Modal(modalEl);
    const form       = document.getElementById('negociacao-form');
    const titleEl    = document.getElementById('negociacaoModalTitle');
    const idEl       = document.getElementById('negociacao-id');
    const dataEl     = document.getElementById('negociacao-data');
    const assessEl   = document.getElementById('negociacao-assessoria');
    const telEl      = document.getElementById('negociacao-telefone');
    const contatoEl  = document.getElementById('negociacao-contato');
    const atualEl    = document.getElementById('negociacao-val-atualizado');
    const analiseEl  = document.getElementById('negociacao-val-analise');
    const maosEl     = document.getElementById('negociacao-val-em-maos');
    const resumoEl   = document.getElementById('negociacao-resumo');
    const feedbackEl = document.getElementById('negociacao-feedback');
    const metaEl     = document.getElementById('negociacao-meta');
    const errorEl    = document.getElementById('negociacao-error');
    const saveBtn    = document.getElementById('negociacao-save-btn');

    const dt = new DataTable(table, {
      processing: true, serverSide: true, responsive: true,
      searching: false, lengthChange: false, pageLength: 10,
      ajax: { url: dtUrl },
      columns: [
        {
          data: null, responsivePriority: 1, orderable: false, searchable: false,
          render: (row) => {
            const autor = row.autor_nome ? `<small class="text-muted">por ${escapeHtml(row.autor_nome)}</small>` : '';
            return `<div>${escapeHtml(row.resumo_curto || '')}</div>${autor}`;
          },
        },
        {
          data: null, responsivePriority: 3, orderable: false, searchable: false,
          render: (row) => {
            if (! row.assessoria_fmt && ! row.contato_fmt && ! row.telefone_fmt) {
              return '<span class="text-muted">—</span>';
            }
            const contato = [row.contato_fmt, row.telefone_fmt].filter(Boolean).map(escapeHtml).join(' · ');
            return `<div>${escapeHtml(row.assessoria_fmt || '—')}</div>` +
                   (contato ? `<small class="text-muted">${contato}</small>` : '');
          },
        },
        { data: 'val_atualizado_fmt', responsivePriority: 4, className: 'text-nowrap', render: v => v || '<span class="text-muted">—</span>' },
        { data: 'val_analise_fmt', responsivePriority: 4, className: 'text-nowrap', render: v => v || '<span class="text-muted">—</span>' },
        { data: 'val_em_maos_fmt', responsivePriority: 2, className: 'text-nowrap fw-semibold text-success', render: v => v || '<span class="text-muted fw-normal">—</span>' },
        { data: 'data_fmt', responsivePriority: 2, className: 'text-nowrap' },
        {
          data: 'id', responsivePriority: 1, orderable: false, searchable: false, className: 'text-end text-nowrap',
          render: id => `
            <div class="d-inline-flex flex-nowrap gap-1 justify-content-end">
              <button class="btn btn-sm btn-icon negociacao-edit" data-id="${id}" title="Visualizar"><i class="icon-base ti tabler-eye icon-22px"></i></button>
              <button class="btn btn-sm btn-icon negociacao-delete text-danger" data-id="${id}" title="Remover"><i class="icon-base ti tabler-trash icon-22px"></i></button>
            </div>`,
        },
      ],
      order: [],
      language: { processing: 'Carregando...', info: 'Exibindo _START_ a _END_ de _TOTAL_', infoEmpty: 'Nenhum registro', zeroRecords: 'Nenhuma negociação encontrada', emptyTable: 'Nenhuma negociação registrada', paginate: { first: '«', previous: '‹', next: '›', last: '»' } },
      layout: { topStart: null, topEnd: null },
    });

    function resetForm(defaults = {}) {
      idEl.value = '';
      setDate(dataEl, defaults.data || '');
      assessEl.value = defaults.assessoria || '';
      telEl.value = defaults.telefone || '';
      contatoEl.value = defaults.contato_nome || '';
      atualEl.value = defaults.val_atualizado || '';
      analiseEl.value = defaults.val_analise || '';
      maosEl.value = defaults.val_em_maos || '';
      resumoEl.value = defaults.resumo || '';
      feedbackEl.value = defaults.feedback || '';
      metaEl.style.display = 'none'; metaEl.innerHTML = '';
      errorEl.style.display = 'none'; errorEl.innerHTML = '';
      refreshMasks();
    }

    function openNew() {
      resetForm({ data: '{{ now()->toDateString() }}' });
      titleEl.textContent = 'Registrar negociação';
      modal.show();
    }

    function openEdit(id) {
      resetForm();
      titleEl.textContent = 'Editar negociação';
      idEl.value = id;
      fetch(`${baseUrl}/${id}`, { headers: { Accept: 'application/json' } })
        .then(r => { if (! r.ok) throw new Error('Não foi possível carregar a negociação.'); return r.json(); })
        .then(d => {
          setDate(dataEl, d.data);
          assessEl.value = d.assessoria || '';
          telEl.value = d.telefone || '';
          contatoEl.value = d.contato_nome || '';
          atualEl.value = d.val_atualizado || '';
          analiseEl.value = d.val_analise || '';
          maosEl.value = d.val_em_maos || '';
          resumoEl.value = d.resumo || '';
          feedbackEl.value = d.feedback || '';
          if (d.autor || d.criada_em) {
            metaEl.innerHTML = `<strong>Registrada por:</strong> ${escapeHtml(d.autor || '—')}${d.criada_em ? ' em ' + escapeHtml(d.criada_em) : ''}`;
            metaEl.style.display = '';
          }
          refreshMasks();
          modal.show();
        })
        .catch(err => Swal.fire({ icon: 'error', title: 'Erro', text: err.message, customClass: { confirmButton: 'btn btn-danger' }, buttonsStyling: false }));
    }

    document.getElementById('btn-nova-negociacao').addEventListener('click', openNew);

    table.addEventListener('click', function (e) {
      const editBtn = e.target.closest('.negociacao-edit');
      if (editBtn) return openEdit(editBtn.dataset.id);
      const delBtn = e.target.closest('.negociacao-delete');
      if (delBtn) {
        commonDelete({
          url: `${baseUrl}/${delBtn.dataset.id}`,
          titulo: 'Excluir negociação?',
          texto: 'Esta ação não pode ser desfeita.',
          onOk: () => dt.draw(false),
        });
      }
    });

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      const id = idEl.value;
      const isEdit = !! id;
      commonSubmit({
        url: isEdit ? `${baseUrl}/${id}` : storeUrl,
        method: isEdit ? 'PATCH' : 'POST',
        payload: {
          data: readDate(dataEl),
          assessoria: assessEl.value.trim() || null,
          telefone: telEl.value.trim() || null,
          contato_nome: contatoEl.value.trim() || null,
          val_atualizado: moneyToRaw(atualEl.value),
          val_analise: moneyToRaw(analiseEl.value),
          val_em_maos: moneyToRaw(maosEl.value),
          resumo: resumoEl.value.trim(),
          feedback: feedbackEl.value.trim() || null,
        },
        saveBtn, errorEl,
        onOk: (body) => {
          modal.hide();
          dt.draw(false);
          Swal.fire({ icon: 'success', title: isEdit ? 'Atualizada' : 'Registrada', text: body.message, timer: 1600, showConfirmButton: false });
        },
      });
    });
  })();
});
</script>
@include('_partials._masks-script')
@endsection
@endif
