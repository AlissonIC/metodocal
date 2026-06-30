@extends('layouts/layoutMaster')

@section('title', 'Processo #' . $processo->id)

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
    @if ($isAdmin || ($isOwner && $processo->isEditavelPeloCliente()))
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

    <div class="card mb-4">
      <div class="card-header border-bottom"><h5 class="card-title mb-0">Dívidas</h5></div>
      <div class="card-body">
        @if ($processo->dividas->isEmpty())
          <p class="text-muted mb-0">Nenhuma dívida cadastrada.</p>
        @else
          <div class="table-responsive">
            <table class="table table-sm">
              <thead><tr><th>Credor</th><th>Valor</th><th>Descrição</th></tr></thead>
              <tbody>
                @foreach ($processo->dividas as $d)
                  <tr>
                    <td>{{ $d->credor }}</td>
                    <td>R$ {{ number_format((float) $d->valor, 2, ',', '.') }}</td>
                    <td>{{ $d->descricao ?: '—' }}</td>
                  </tr>
                @endforeach
                <tr class="table-light fw-semibold">
                  <td>Total</td>
                  <td colspan="2">R$ {{ number_format((float) $processo->dividas->sum('valor'), 2, ',', '.') }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        @endif
      </div>
    </div>

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
          <h5 class="card-title mb-0">Faturas do processo</h5>
          <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#nova-fatura-form">
            <i class="icon-base ti tabler-plus me-1"></i> Nova fatura
          </button>
        </div>
        <div class="collapse" id="nova-fatura-form">
          <div class="card-body border-bottom bg-light">
            <form method="POST" action="{{ route('processos.faturas.store', $processo) }}" class="row g-2">
              @csrf
              <div class="col-md-5">
                <label class="form-label small mb-1">Descrição</label>
                <input type="text" name="descricao" class="form-control form-control-sm" maxlength="255" placeholder="Ex.: Entrada do serviço">
              </div>
              <div class="col-md-2">
                <label class="form-label small mb-1">Valor (R$) *</label>
                <input type="text" inputmode="numeric" name="valor" class="form-control form-control-sm mask-money" required value="{{ $processo->servico?->valor_padrao ? number_format((float) $processo->servico->valor_padrao, 2, ',', '.') : '' }}" placeholder="0,00">
              </div>
              <div class="col-md-2">
                <label class="form-label small mb-1">Vencimento *</label>
                <input type="date" name="vencimento" class="form-control form-control-sm" required value="{{ now()->addDays(7)->toDateString() }}">
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
                <button type="submit" class="btn btn-sm btn-primary w-100"><i class="icon-base ti tabler-device-floppy"></i></button>
              </div>
            </form>
          </div>
        </div>
        <div class="card-body">
          @if ($processo->faturas->isEmpty())
            <p class="text-muted mb-0">Nenhuma fatura emitida para este processo.</p>
          @else
            <div class="table-responsive">
              <table class="table table-sm align-middle">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Descrição</th>
                    <th>Valor</th>
                    <th>Vencimento</th>
                    <th>Status</th>
                    <th class="text-end">Ações</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($processo->faturas as $f)
                    @php
                      $map = ['pendente' => 'warning', 'paga' => 'success', 'cancelada' => 'secondary', 'estornada' => 'info', 'atrasada' => 'danger'];
                      $color = $f->isAtrasada() ? 'danger' : ($map[$f->status] ?? 'secondary');
                      $label = $f->isAtrasada() ? 'Atrasada' : ucfirst($f->status);
                    @endphp
                    <tr>
                      <td>{{ $f->id }}</td>
                      <td>{{ $f->descricao ?: '—' }}</td>
                      <td>R$ {{ number_format((float) $f->valor, 2, ',', '.') }}</td>
                      <td>{{ $f->vencimento->format('d/m/Y') }}</td>
                      <td><span class="badge bg-label-{{ $color }}">{{ $label }}</span></td>
                      <td class="text-end">
                        <a href="{{ route('admin.financeiro.show', $f) }}" class="btn btn-sm btn-icon btn-label-primary" title="Detalhes"><i class="icon-base ti tabler-eye"></i></a>
                        <form method="POST" action="{{ route('processos.faturas.destroy', $f) }}" class="d-inline" onsubmit="return confirm('Excluir esta fatura?')">
                          @csrf @method('DELETE')
                          <button type="submit" class="btn btn-sm btn-icon btn-label-danger" title="Excluir"><i class="icon-base ti tabler-trash"></i></button>
                        </form>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @endif
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header border-bottom d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0">Comissões do processo</h5>
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
                <select name="licensed_by_user_id" class="form-select form-select-sm" required>
                  <option value="">Selecione...</option>
                  @foreach ($usuariosParaComissao as $u)
                    <option value="{{ $u->id }}">{{ $u->name }}</option>
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
                <input type="date" name="data_referencia" class="form-control form-control-sm" required value="{{ now()->toDateString() }}">
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
                <button type="submit" class="btn btn-sm btn-primary w-100"><i class="icon-base ti tabler-device-floppy"></i></button>
              </div>
            </form>
          </div>
        </div>
        <div class="card-body">
          @if ($processo->comissoes->isEmpty())
            <p class="text-muted mb-0">Nenhuma comissão vinculada a este processo.</p>
          @else
            <div class="table-responsive">
              <table class="table table-sm align-middle">
                <thead>
                  <tr>
                    <th>Usuário</th>
                    <th>Descrição</th>
                    <th>Tipo</th>
                    <th>Valor</th>
                    <th>Data</th>
                    <th>Status</th>
                    <th class="text-end">Ações</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($processo->comissoes as $c)
                    @php
                      $sMap = ['pendente' => 'warning', 'paga' => 'success', 'cancelada' => 'secondary'];
                      $sColor = $sMap[$c->status] ?? 'secondary';
                    @endphp
                    <tr>
                      <td>{{ $c->licenciado?->name ?? '—' }}</td>
                      <td>{{ $c->descricao }}</td>
                      <td><span class="badge bg-label-{{ $c->tipoColor() }}">{{ $c->tipoLabel() }}</span></td>
                      <td class="text-nowrap">R$ {{ number_format((float) $c->valor, 2, ',', '.') }}</td>
                      <td class="text-nowrap">{{ $c->data_referencia->format('d/m/Y') }}</td>
                      <td><span class="badge bg-label-{{ $sColor }}">{{ ucfirst($c->status) }}</span></td>
                      <td class="text-end text-nowrap">
                        <a href="{{ url('/painel/admin/comissoes/' . $c->id . '/editar') }}" class="btn btn-sm btn-icon" title="Editar"><i class="icon-base ti tabler-edit"></i></a>
                        <form method="POST" action="{{ route('processos.comissoes.destroy', $c) }}" class="d-inline" onsubmit="return confirm('Excluir esta comissão?')">
                          @csrf @method('DELETE')
                          <button type="submit" class="btn btn-sm btn-icon btn-label-danger" title="Excluir"><i class="icon-base ti tabler-trash"></i></button>
                        </form>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
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
        <form method="POST" action="{{ route('processos.documentos.store', $processo) }}" enctype="multipart/form-data" class="row g-2 mb-4">
          @csrf
          <div class="col-md-5"><input type="file" name="arquivo" class="form-control form-control-sm" required></div>
          <div class="col-md-4"><input type="text" name="categoria" class="form-control form-control-sm" maxlength="80" placeholder="Categoria (opcional)"></div>
          <div class="col-md-3"><button type="submit" class="btn btn-sm btn-primary w-100"><i class="icon-base ti tabler-upload me-1"></i> Enviar</button></div>
          <div class="col-12"><small class="text-muted">Tamanho máximo: 20 MB.</small></div>
        </form>

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
                  @if ($isAdmin || $doc->uploaded_by_user_id === auth()->id())
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
              <input type="date" name="data_protocolo_liminar" class="form-control" value="{{ $processo->data_protocolo_liminar?->toDateString() ?: now()->toDateString() }}">
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
});
</script>
@include('_partials._masks-script')
@endsection
@endif
