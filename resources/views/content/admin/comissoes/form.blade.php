@extends('layouts/layoutMaster')

@section('title', $comissao->exists ? 'Editar comissão' : 'Nova comissão')

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/select2/select2.scss',
  'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
])
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/select2/select2.js',
  'resources/assets/vendor/libs/flatpickr/flatpickr.js',
  'resources/assets/vendor/libs/cleave-zen/cleave-zen.js',
])
@endsection

@section('content')
@php
  $editing = $comissao->exists;
  $action = $editing ? url('/painel/admin/comissoes/' . $comissao->id) : url('/painel/admin/comissoes');
  $dataRef = old('data_referencia', optional($comissao->data_referencia)->format('Y-m-d'));
  $tipoAtual = old('tipo', $comissao->tipo ?? 'a_receber');
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">{{ $editing ? 'Editar comissão' : 'Nova comissão' }}</h4>
  <a href="{{ route('admin.comissoes') }}" class="btn btn-label-secondary">
    <i class="icon-base ti tabler-arrow-left me-1"></i> Voltar
  </a>
</div>

@if ($errors->any())
  <div class="alert alert-danger">
    <ul class="mb-0">@foreach ($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
  </div>
@endif

<form method="POST" action="{{ $action }}">
  @csrf
  @if ($editing) @method('PATCH') @endif

  <div class="row g-4">
    <div class="col-lg-8">
      <div class="card mb-4">
        <div class="card-header border-bottom"><h5 class="card-title mb-0">Informações</h5></div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-6 mb-4">
              <label class="form-label">Usuário do sistema *</label>
              <select name="licensed_by_user_id" id="usuario" class="select2 form-select">
                <option value="">Selecione...</option>
                @foreach ($usuarios as $u)
                  <option value="{{ $u->id }}" @selected(old('licensed_by_user_id', $comissao->licensed_by_user_id) == $u->id)>{{ $u->name }} · {{ $u->email }}</option>
                @endforeach
              </select>
              <small class="text-muted">A quem essa comissão se refere (a pagar ou a receber).</small>
            </div>
            <div class="col-md-6 mb-4">
              <label class="form-label">Cliente do licenciado (opcional)</label>
              <select name="cliente_id" id="cliente" class="select2 form-select">
                <option value="">Sem cliente</option>
                @foreach ($clientes as $c)
                  <option value="{{ $c->id }}" data-licenciado="{{ $c->licensed_by_user_id }}" @selected(old('cliente_id', $comissao->cliente_id) == $c->id)>{{ $c->nome }}</option>
                @endforeach
              </select>
            </div>
          </div>

          <div class="mb-4">
            <label class="form-label">Processo vinculado (opcional)</label>
            <select name="processo_id" id="processo" class="select2 form-select">
              <option value="">— sem processo —</option>
              @foreach ($processos as $p)
                <option value="{{ $p->id }}" @selected(old('processo_id', $comissao->processo_id) == $p->id)>#{{ $p->id }} · {{ $p->nome_completo }}</option>
              @endforeach
            </select>
          </div>

          <div class="mb-4">
            <label class="form-label">Descrição *</label>
            <input type="text" class="form-control" name="descricao" required maxlength="160" placeholder="Comissão de fechamento do processo X" value="{{ old('descricao', $comissao->descricao) }}">
          </div>

          <div class="row">
            <div class="col-md-6 mb-0">
              <label class="form-label">Valor (R$) *</label>
              <input type="text" inputmode="numeric" class="form-control mask-money" name="valor" placeholder="0,00" value="{{ old('valor', $comissao->valor) }}">
            </div>
            <div class="col-md-6 mb-0">
              <label class="form-label">Data referência *</label>
              <input type="text" class="form-control flatpickr-date" name="data_referencia" value="{{ $dataRef }}" placeholder="dd/mm/aaaa">
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card mb-4">
        <div class="card-header border-bottom"><h5 class="card-title mb-0">Tipo</h5></div>
        <div class="card-body">
          <div class="form-check custom-option custom-option-basic mb-2">
            <label class="form-check-label custom-option-content d-flex align-items-center w-100" style="padding-block: .65rem;">
              <input class="form-check-input me-2 mt-0" type="radio" name="tipo" value="a_receber" @checked($tipoAtual === 'a_receber')>
              <span class="fw-medium"><i class="icon-base ti tabler-arrow-down-right text-success me-1"></i> A receber</span>
            </label>
          </div>
          <div class="form-check custom-option custom-option-basic">
            <label class="form-check-label custom-option-content d-flex align-items-center w-100" style="padding-block: .65rem;">
              <input class="form-check-input me-2 mt-0" type="radio" name="tipo" value="a_pagar" @checked($tipoAtual === 'a_pagar')>
              <span class="fw-medium"><i class="icon-base ti tabler-arrow-up-right text-warning me-1"></i> A pagar</span>
            </label>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header border-bottom"><h5 class="card-title mb-0">Status</h5></div>
        <div class="card-body">
          <label class="form-label">Situação *</label>
          <select name="status" class="form-select">
            @php $statusAtual = old('status', $comissao->status ?? 'pendente'); @endphp
            <option value="pendente" @selected($statusAtual === 'pendente')>Pendente</option>
            <option value="paga" @selected($statusAtual === 'paga')>Paga</option>
            <option value="cancelada" @selected($statusAtual === 'cancelada')>Cancelada</option>
          </select>
          <small class="text-muted d-block mt-2">Ao marcar como "Paga", a data de pagamento é registrada automaticamente.</small>
        </div>
      </div>
    </div>
  </div>

  <div class="d-flex justify-content-end gap-2">
    <a href="{{ route('admin.comissoes') }}" class="btn btn-label-secondary">Cancelar</a>
    <button type="submit" class="btn btn-primary"><i class="icon-base ti tabler-device-floppy me-1"></i> {{ $editing ? 'Salvar alterações' : 'Lançar comissão' }}</button>
  </div>
</form>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  $('#usuario, #cliente, #processo').each(function () {
    const $s = $(this);
    $s.wrap('<div class="position-relative"></div>').select2({
      placeholder: $s.find('option:first').text() || 'Selecione...',
      allowClear: $s.attr('id') !== 'usuario',
      dropdownParent: $s.parent(),
      width: '100%',
    });
  });

  flatpickr('.flatpickr-date', {
    altInput: true,
    altFormat: 'd/m/Y',
    dateFormat: 'Y-m-d',
    allowInput: true,
  });
});
</script>
@include('_partials._masks-script')
@endsection
