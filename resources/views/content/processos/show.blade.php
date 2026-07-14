@extends('layouts/layoutMaster')

@section('title', 'Processo #' . $processo->id)

@if ($isAdmin)
@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
  'resources/assets/vendor/libs/select2/select2.scss',
])
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/flatpickr/flatpickr.js',
  'resources/assets/vendor/libs/select2/select2.js',
  'resources/assets/vendor/libs/cleave-zen/cleave-zen.js',
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

      <div class="card mb-4">
        <div class="card-header border-bottom d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0"><i class="icon-base ti tabler-receipt me-1"></i> Dívidas do processo</h5>
          <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#nova-fatura-form">
            <i class="icon-base ti tabler-plus me-1"></i> Nova dívida
          </button>
        </div>
        <div class="collapse" id="nova-fatura-form">
          <div class="card-body border-bottom bg-light">
            <form method="POST" action="{{ route('processos.faturas.store', $processo) }}" class="row g-2">
              @csrf
              <div class="col-md-5">
                <label class="form-label small mb-1">Descrição</label>
                <input type="text" name="descricao" class="form-control form-control-sm" maxlength="255" placeholder="Ex.: Entrada do serviço, dívida assumida do banco X">
              </div>
              <div class="col-md-2">
                <label class="form-label small mb-1">Valor (R$) *</label>
                <input type="text" inputmode="numeric" name="valor" class="form-control form-control-sm mask-money" required value="{{ $processo->servico?->valor_padrao ? number_format((float) $processo->servico->valor_padrao, 2, ',', '.') : '' }}" placeholder="0,00">
              </div>
              <div class="col-md-2">
                <label class="form-label small mb-1">Vencimento *</label>
                <input type="text" name="vencimento" class="form-control form-control-sm flatpickr-date" required value="{{ now()->addDays(7)->toDateString() }}" placeholder="dd/mm/aaaa">
              </div>
              <div class="col-md-2">
                <label class="form-label small mb-1">Status *</label>
                <select name="status" class="form-select form-select-sm" required>
                  <option value="pendente">Pendente</option>
                  <option value="paga">Paga</option>
                  <option value="cancelada">Cancelada</option>
                </select>
              </div>
              <div class="col-md-1 d-flex align-items-end">
                <button type="submit" class="btn btn-sm btn-primary w-100" title="Salvar"><i class="icon-base ti tabler-device-floppy"></i></button>
              </div>
            </form>
          </div>
        </div>
        <div class="card-body pt-2">
          @php
            // Consolida "dívidas com credores" (Divida) e "cobranças" (Fatura) num único stream ordenado
            $itensDivida = collect();
            foreach ($processo->dividas as $d) {
              $itensDivida->push((object) [
                'tipo' => 'credor',
                'valor' => $d->valor,
                'descricao' => $d->descricao ?: 'Dívida com credor',
                'origem' => $d->credor,
                'status' => 'assumida',
                'status_label' => 'Assumida',
                'status_color' => 'info',
                'data' => $processo->created_at,
                'data_label' => 'Registrada em ' . $processo->created_at->format('d/m/Y'),
                'icon' => 'tabler-building-bank',
                'actions' => null,
              ]);
            }
            foreach ($processo->faturas as $f) {
              $map = ['pendente' => 'warning', 'paga' => 'success', 'cancelada' => 'secondary', 'estornada' => 'info', 'atrasada' => 'danger'];
              $color = $f->isAtrasada() ? 'danger' : ($map[$f->status] ?? 'secondary');
              $label = $f->isAtrasada() ? 'Atrasada' : ucfirst($f->status);
              $itensDivida->push((object) [
                'tipo' => 'fatura',
                'valor' => $f->valor,
                'descricao' => $f->descricao ?: 'Cobrança do processo',
                'origem' => null,
                'status' => $f->status,
                'status_label' => $label,
                'status_color' => $color,
                'data' => $f->vencimento,
                'data_label' => 'Vence ' . $f->vencimento->format('d/m/Y'),
                'icon' => 'tabler-file-invoice',
                'actions' => $f,
              ]);
            }
            $totalDividas = $itensDivida->sum('valor');
          @endphp
          @if ($itensDivida->isEmpty())
            <p class="text-muted mb-0 text-center py-3">Nenhuma dívida vinculada a este processo.</p>
          @else
            <div class="list-group list-group-flush">
              @foreach ($itensDivida as $item)
                <div class="list-group-item px-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                  <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-sm">
                      <span class="avatar-initial rounded-circle bg-label-{{ $item->status_color }}">
                        <i class="icon-base ti {{ $item->icon }}"></i>
                      </span>
                    </div>
                    <div>
                      <div class="fw-semibold" style="font-size: 1.05rem;">R$ {{ number_format((float) $item->valor, 2, ',', '.') }}</div>
                      <div class="small text-muted">{{ $item->descricao }}</div>
                      @if ($item->origem)<div class="small text-muted"><i class="icon-base ti tabler-building-bank" style="font-size:.75rem;"></i> {{ $item->origem }}</div>@endif
                    </div>
                  </div>
                  <div class="d-flex align-items-center gap-3">
                    <div class="text-end">
                      <span class="badge bg-label-{{ $item->status_color }}">{{ $item->status_label }}</span>
                      <div class="small text-muted mt-1">{{ $item->data_label }}</div>
                    </div>
                    <div class="d-flex gap-1">
                      @if ($item->actions)
                        <a href="{{ route('admin.financeiro.show', $item->actions) }}" class="btn btn-sm btn-icon btn-label-primary" title="Detalhes"><i class="icon-base ti tabler-eye"></i></a>
                        <form method="POST" action="{{ route('processos.faturas.destroy', $item->actions) }}" class="d-inline" onsubmit="return confirm('Excluir esta dívida?')">
                          @csrf @method('DELETE')
                          <button type="submit" class="btn btn-sm btn-icon btn-label-danger" title="Excluir"><i class="icon-base ti tabler-trash"></i></button>
                        </form>
                      @endif
                    </div>
                  </div>
                </div>
              @endforeach
              <div class="list-group-item px-0 pt-3 d-flex justify-content-between align-items-center">
                <span class="text-muted small">Total consolidado</span>
                <span class="fw-bold" style="font-size: 1.1rem;">R$ {{ number_format((float) $totalDividas, 2, ',', '.') }}</span>
              </div>
            </div>
          @endif
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header border-bottom d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0"><i class="icon-base ti tabler-cash me-1"></i> Comissões do processo</h5>
          <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#nova-comissao-form">
            <i class="icon-base ti tabler-plus me-1"></i> Nova comissão
          </button>
        </div>
        <div class="collapse" id="nova-comissao-form">
          <div class="card-body border-bottom bg-light">
            <form method="POST" action="{{ route('processos.comissoes.store', $processo) }}" class="row g-2">
              @csrf
              <div class="col-md-4">
                <label class="form-label small mb-1">Usuário *</label>
                <select name="licensed_by_user_id" id="comissao-usuario-select" class="form-select form-select-sm select2-role" required>
                  <option value=""></option>
                  @foreach ($usuariosParaComissao as $u)
                    <option value="{{ $u['id'] }}" data-role="{{ $u['role'] }}" data-email="{{ $u['email'] }}">{{ $u['name'] }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label small mb-1">Descrição *</label>
                <input type="text" name="descricao" class="form-control form-control-sm" required maxlength="160" placeholder="Comissão do fechamento">
              </div>
              <div class="col-md-2">
                <label class="form-label small mb-1">Valor (R$) *</label>
                <input type="text" inputmode="numeric" name="valor" class="form-control form-control-sm mask-money" required placeholder="0,00">
              </div>
              <div class="col-md-2">
                <label class="form-label small mb-1">Tipo *</label>
                <select name="tipo" class="form-select form-select-sm" required>
                  <option value="a_receber">A receber</option>
                  <option value="a_pagar">A pagar</option>
                </select>
              </div>
              <div class="col-md-2">
                <label class="form-label small mb-1">Data *</label>
                <input type="text" name="data_referencia" class="form-control form-control-sm flatpickr-date" required value="{{ now()->toDateString() }}" placeholder="dd/mm/aaaa">
              </div>
              <div class="col-md-2">
                <label class="form-label small mb-1">Status *</label>
                <select name="status" class="form-select form-select-sm" required>
                  <option value="pendente">Pendente</option>
                  <option value="paga">Paga</option>
                  <option value="cancelada">Cancelada</option>
                </select>
              </div>
              <div class="col-md-1 d-flex align-items-end">
                <button type="submit" class="btn btn-sm btn-primary w-100" title="Salvar"><i class="icon-base ti tabler-device-floppy"></i></button>
              </div>
            </form>
          </div>
        </div>
        <div class="card-body pt-2">
          @if ($processo->comissoes->isEmpty())
            <p class="text-muted mb-0 text-center py-3">Nenhuma comissão vinculada a este processo.</p>
          @else
            <div class="list-group list-group-flush">
              @foreach ($processo->comissoes as $c)
                @php
                  $sMap = ['pendente' => 'warning', 'paga' => 'success', 'cancelada' => 'secondary'];
                  $sColor = $sMap[$c->status] ?? 'secondary';
                  $isReceber = $c->tipo === 'a_receber';
                @endphp
                <div class="list-group-item px-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                  <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-sm">
                      <span class="avatar-initial rounded-circle bg-label-{{ $isReceber ? 'success' : 'warning' }}">
                        <i class="icon-base ti tabler-{{ $isReceber ? 'arrow-down-right' : 'arrow-up-right' }}"></i>
                      </span>
                    </div>
                    <div>
                      <div class="d-flex align-items-center gap-2">
                        <span class="fw-semibold" style="font-size: 1.05rem;">R$ {{ number_format((float) $c->valor, 2, ',', '.') }}</span>
                        <span class="badge bg-label-{{ $c->tipoColor() }}">{{ $c->tipoLabel() }}</span>
                      </div>
                      <div class="small text-muted">{{ $c->descricao }}</div>
                      <div class="small text-muted">{{ $c->licenciado?->name ?? '—' }}</div>
                    </div>
                  </div>
                  <div class="d-flex align-items-center gap-3">
                    <div class="text-end">
                      <span class="badge bg-label-{{ $sColor }}">{{ ucfirst($c->status) }}</span>
                      <div class="small text-muted mt-1">{{ $c->data_referencia->format('d/m/Y') }}</div>
                    </div>
                    <div class="d-flex gap-1">
                      <a href="{{ url('/painel/admin/comissoes/' . $c->id . '/editar') }}" class="btn btn-sm btn-icon" title="Editar"><i class="icon-base ti tabler-edit"></i></a>
                      <form method="POST" action="{{ route('processos.comissoes.destroy', $c) }}" class="d-inline" onsubmit="return confirm('Excluir esta comissão?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-icon btn-label-danger" title="Excluir"><i class="icon-base ti tabler-trash"></i></button>
                      </form>
                    </div>
                  </div>
                </div>
              @endforeach
            </div>
          @endif
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
          <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#nova-negociacao-form">
            <i class="icon-base ti tabler-plus me-1"></i> Registrar negociação
          </button>
        </div>
        <div class="collapse" id="nova-negociacao-form">
          <div class="card-body border-bottom bg-light">
            <form method="POST" action="{{ route('processos.negociacoes.store', $processo) }}" class="row g-2">
              @csrf
              <div class="col-md-2">
                <label class="form-label small mb-1">Data *</label>
                <input type="text" name="data" class="form-control form-control-sm flatpickr-date" required value="{{ now()->toDateString() }}" placeholder="dd/mm/aaaa">
              </div>
              <div class="col-md-4">
                <label class="form-label small mb-1">Assessoria</label>
                <input type="text" name="assessoria" class="form-control form-control-sm" maxlength="120" placeholder="Ex.: JCS, Banco XYZ...">
              </div>
              <div class="col-md-2">
                <label class="form-label small mb-1">Val. atualizado</label>
                <input type="text" inputmode="numeric" name="val_atualizado" class="form-control form-control-sm mask-money" placeholder="0,00">
              </div>
              <div class="col-md-2">
                <label class="form-label small mb-1">Val. análise</label>
                <input type="text" inputmode="numeric" name="val_analise" class="form-control form-control-sm mask-money" placeholder="0,00">
              </div>
              <div class="col-md-2">
                <label class="form-label small mb-1">Val. em mãos</label>
                <input type="text" inputmode="numeric" name="val_em_maos" class="form-control form-control-sm mask-money" placeholder="0,00">
              </div>
              <div class="col-12">
                <label class="form-label small mb-1">Resumo da negociação *</label>
                <textarea name="resumo" class="form-control form-control-sm" rows="2" required maxlength="2000" placeholder="Plano de quitação: R$ 6.000,00 a R$ 6.500,00. Proposta de 10 mil para pré-análise..."></textarea>
              </div>
              <div class="col-12">
                <label class="form-label small mb-1">Feedback (opcional)</label>
                <textarea name="feedback" class="form-control form-control-sm" rows="1" maxlength="1000" placeholder="Retorno do banco, próxima ação..."></textarea>
              </div>
              <div class="col-12 d-flex justify-content-end">
                <button type="submit" class="btn btn-sm btn-primary"><i class="icon-base ti tabler-device-floppy me-1"></i> Registrar</button>
              </div>
            </form>
          </div>
        </div>
        <div class="card-body pt-2">
          @if ($processo->negociacoes->isEmpty())
            <p class="text-muted mb-0 text-center py-3">Nenhuma negociação registrada. Clique em "Registrar negociação" para iniciar o histórico.</p>
          @else
            <div class="list-group list-group-flush">
              @foreach ($processo->negociacoes as $n)
                <div class="list-group-item px-0">
                  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                    <div class="d-flex align-items-center gap-2">
                      <span class="badge bg-label-secondary">{{ $n->data->format('d/m/Y') }}</span>
                      @if ($n->assessoria)<span class="badge bg-label-info">{{ $n->assessoria }}</span>@endif
                    </div>
                    <form method="POST" action="{{ route('processos.negociacoes.destroy', $n) }}" onsubmit="return confirm('Excluir esta negociação?')">
                      @csrf @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-icon btn-label-danger" title="Excluir"><i class="icon-base ti tabler-trash"></i></button>
                    </form>
                  </div>
                  <p class="mb-2">{{ $n->resumo }}</p>
                  <div class="row g-2 mb-2 small">
                    @if ($n->val_atualizado)
                      <div class="col-md-4"><span class="text-muted">Val. atualizado:</span> <strong>R$ {{ number_format((float) $n->val_atualizado, 2, ',', '.') }}</strong></div>
                    @endif
                    @if ($n->val_analise)
                      <div class="col-md-4"><span class="text-muted">Val. análise:</span> <strong>R$ {{ number_format((float) $n->val_analise, 2, ',', '.') }}</strong></div>
                    @endif
                    @if ($n->val_em_maos)
                      <div class="col-md-4"><span class="text-muted">Val. em mãos:</span> <strong class="text-success">R$ {{ number_format((float) $n->val_em_maos, 2, ',', '.') }}</strong></div>
                    @endif
                  </div>
                  @if ($n->feedback)
                    <div class="alert alert-info py-2 mb-2 small">
                      <i class="icon-base ti tabler-message-circle me-1"></i>
                      <strong>Feedback:</strong> {{ $n->feedback }}
                    </div>
                  @endif
                  <small class="text-muted">Registrado por {{ $n->inseridaPor?->name ?? 'sistema' }} em {{ $n->created_at->format('d/m/Y H:i') }}</small>
                </div>
              @endforeach
            </div>
          @endif
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header border-bottom"><h5 class="card-title mb-0">Observações internas (admin)</h5></div>
        <div class="card-body">
          <form method="POST" action="{{ route('processos.observacoes', $processo) }}">
            @csrf @method('PATCH')
            <textarea name="observacoes_admin" class="form-control mb-3" rows="4" maxlength="5000" placeholder="Notas visíveis apenas para a equipe administrativa...">{{ old('observacoes_admin', $processo->observacoes_admin) }}</textarea>
            <button type="submit" class="btn btn-primary"><i class="icon-base ti tabler-device-floppy me-1"></i> Salvar</button>
          </form>
        </div>
      </div>
    @endif

    <div class="card mb-4">
      <div class="card-header border-bottom"><h5 class="card-title mb-0">Documentos</h5></div>
      <div class="card-body">
        @unless ($isComprador)
        <form method="POST" action="{{ route('processos.documentos.store', $processo) }}" enctype="multipart/form-data" class="row g-2 mb-4">
          @csrf
          <div class="col-md-5"><input type="file" name="arquivo" class="form-control form-control-sm" required></div>
          <div class="col-md-4"><input type="text" name="categoria" class="form-control form-control-sm" maxlength="80" placeholder="Categoria (opcional)"></div>
          <div class="col-md-3"><button type="submit" class="btn btn-sm btn-primary w-100"><i class="icon-base ti tabler-upload me-1"></i> Enviar</button></div>
          <div class="col-12"><small class="text-muted">Tamanho máximo: 20 MB.</small></div>
        </form>
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

    <div class="card">
      <div class="card-header border-bottom"><h5 class="card-title mb-0">Acompanhamento</h5></div>
      <div class="card-body">
        @if ($processo->historico->isEmpty())
          <p class="text-muted mb-0">Sem movimentações.</p>
        @else
          <ul class="timeline mb-0">
            @foreach ($processo->historico as $h)
              <li class="timeline-item timeline-item-transparent">
                <span class="timeline-point timeline-point-{{ $h->statusNovoColor() }}"></span>
                <div class="timeline-event">
                  <div class="timeline-header">
                    <h6 class="mb-0">{{ $h->statusNovoLabel() }}</h6>
                    <small class="text-muted">{{ $h->created_at->format('d/m/Y H:i') }}</small>
                  </div>
                  @if ($h->observacao)<p class="mb-0 small">{{ $h->observacao }}</p>@endif
                  @if ($h->user)<small class="text-muted">por {{ $h->user->name }}</small>@endif
                </div>
              </li>
            @endforeach
          </ul>
        @endif
      </div>
    </div>
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
    $sel.wrap('<div class="position-relative"></div>').select2({
      placeholder: 'Selecione o usuário',
      allowClear: true,
      width: '100%',
      dropdownParent: $sel.parent(),
      templateResult: renderResult,
      templateSelection: renderSelection,
      escapeMarkup: m => m,
    });
  }
});
</script>
@include('_partials._masks-script')
@endsection
@endif
