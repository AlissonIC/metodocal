@extends('layouts/layoutMaster')

@section('title', $processo->exists ? 'Editar processo' : 'Novo processo')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/cleave-zen/cleave-zen.js',
  'resources/assets/vendor/libs/select2/select2.js',
])
@endsection

@section('content')
@php
  $editing = $processo->exists;
  $action = $editing ? route('processos.update', $processo) : route('processos.store');
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">{{ $editing ? 'Editar processo' : 'Novo processo' }}</h4>
  <a href="{{ $editing ? route('processos.show', $processo) : route('processos.index') }}" class="btn btn-label-secondary">
    <i class="ti tabler-arrow-left me-1"></i> Voltar
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

  @if ($isAdmin)
    <div class="card mb-4">
      <div class="card-header"><h5 class="card-title mb-0">Vínculos internos (admin)</h5></div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6 mb-3 mb-md-0">
            <label class="form-label">Cliente *</label>
            <select name="user_id" id="user_id" class="form-select select2" required>
              <option value=""></option>
              @foreach ($clientes as $cliente)
                <option value="{{ $cliente->id }}" @selected(old('user_id', $processo->user_id) == $cliente->id)>
                  {{ $cliente->name }} · {{ $cliente->email }}
                </option>
              @endforeach
            </select>
            <small class="text-muted">Cliente da plataforma dono do processo.</small>
          </div>
          <div class="col-md-6">
            <label class="form-label">Comprador (opcional)</label>
            <select name="comprador_id" id="comprador_id" class="form-select select2">
              <option value="">— sem comprador vinculado —</option>
              @foreach ($compradores as $cmp)
                <option value="{{ $cmp->id }}" @selected(old('comprador_id', $processo->comprador_id) == $cmp->id)>
                  {{ $cmp->nome }} · {{ strtoupper($cmp->tipo_documento) }} {{ $cmp->documento }}
                </option>
              @endforeach
            </select>
            <small class="text-muted">Destino da operação. Visível apenas para admin.</small>
          </div>
        </div>
      </div>
    </div>
  @endif

  <div class="card mb-4">
    <div class="card-header"><h5 class="card-title mb-0">Serviço contratado</h5></div>
    <div class="card-body">
      <div class="row">
        @foreach ($servicos as $servico)
          <div class="col-md-4 mb-3">
            <div class="form-check custom-option custom-option-basic">
              <label class="form-check-label custom-option-content w-100 d-flex align-items-center" style="padding-block: 0.75rem;">
                <input class="form-check-input me-2 mt-0" type="radio" name="servico_id" value="{{ $servico->id }}" @checked((int) old('servico_id', $processo->servico_id) === $servico->id)>
                <span class="h6 mb-0">{{ $servico->nome }}</span>
              </label>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </div>

  <div class="card mb-4">
    <div class="card-header"><h5 class="card-title mb-0">Dados da pessoa</h5></div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-8 mb-4">
          <label class="form-label">Nome completo *</label>
          <input type="text" name="nome_completo" class="form-control" required maxlength="160" value="{{ old('nome_completo', $processo->nome_completo) }}">
        </div>
        <div class="col-md-4 mb-4">
          <label class="form-label">Tipo de documento *</label>
          <select name="tipo_documento" class="form-select" required>
            @foreach (['cpf' => 'CPF', 'cnpj' => 'CNPJ'] as $v => $l)
              <option value="{{ $v }}" @selected(old('tipo_documento', $processo->tipo_documento) === $v)>{{ $l }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-4 mb-4">
          <label class="form-label">Documento *</label>
          <input type="text" name="documento" class="form-control mask-cpf-cnpj" required maxlength="20" value="{{ old('documento', $processo->documento) }}" placeholder="000.000.000-00">
        </div>
        <div class="col-md-4 mb-4">
          <label class="form-label">E-mail de contato</label>
          <input type="email" name="email_contato" class="form-control" maxlength="160" value="{{ old('email_contato', $processo->email_contato) }}">
        </div>
        <div class="col-md-4 mb-4">
          <label class="form-label">Telefone de contato</label>
          <input type="text" name="telefone_contato" class="form-control mask-phone" maxlength="40" value="{{ old('telefone_contato', $processo->telefone_contato) }}" placeholder="(00) 00000-0000">
        </div>
      </div>
    </div>
  </div>

  <div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="card-title mb-0">Dívidas</h5>
      <button type="button" class="btn btn-sm btn-primary" id="btn-add-divida">
        <i class="ti tabler-plus me-1"></i> Adicionar dívida
      </button>
    </div>
    <div class="card-body">
      <div id="dividas-container">
        @forelse ($dividas as $i => $divida)
          @include('content.processos.partials.divida-row', ['idx' => $i, 'divida' => $divida])
        @empty
          @include('content.processos.partials.divida-row', ['idx' => 0, 'divida' => null])
        @endforelse
      </div>
      <small class="text-muted">Preencha apenas as dívidas que quiser registrar. Linhas vazias serão ignoradas.</small>
    </div>
  </div>

  <div class="card mb-4">
    <div class="card-header"><h5 class="card-title mb-0">Observações do cliente</h5></div>
    <div class="card-body">
      <textarea name="observacoes_cliente" class="form-control" rows="4" maxlength="3000" placeholder="Informações adicionais que possam ajudar...">{{ old('observacoes_cliente', $processo->observacoes_cliente) }}</textarea>
    </div>
  </div>

  @if ($isAdmin)
    <div class="card mb-4">
      <div class="card-header"><h5 class="card-title mb-0">Observações internas (admin)</h5></div>
      <div class="card-body">
        <textarea name="observacoes_admin" class="form-control" rows="3" maxlength="5000" placeholder="Notas visíveis apenas para a equipe.">{{ old('observacoes_admin', $processo->observacoes_admin) }}</textarea>
      </div>
    </div>
  @endif

  <div class="d-flex justify-content-end gap-2">
    <a href="{{ $editing ? route('processos.show', $processo) : route('processos.index') }}" class="btn btn-label-secondary">Cancelar</a>
    <button type="submit" class="btn btn-primary"><i class="ti tabler-device-floppy me-1"></i> {{ $editing ? 'Salvar alterações' : 'Cadastrar processo' }}</button>
  </div>
</form>

<template id="divida-template">
  @include('content.processos.partials.divida-row', ['idx' => '__IDX__', 'divida' => null])
</template>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  if (window.jQuery && jQuery('#user_id').length) {
    jQuery('#user_id').select2({ placeholder: 'Selecione o cliente', allowClear: true, width: '100%' });
  }
  if (window.jQuery && jQuery('#comprador_id').length) {
    jQuery('#comprador_id').select2({ placeholder: 'Selecione o comprador', allowClear: true, width: '100%' });
  }

  const container = document.getElementById('dividas-container');
  const template = document.getElementById('divida-template').innerHTML;
  let idx = container.querySelectorAll('.divida-row').length;

  document.getElementById('btn-add-divida').addEventListener('click', function () {
    const html = template.replaceAll('__IDX__', idx++);
    container.insertAdjacentHTML('beforeend', html);
    document.dispatchEvent(new CustomEvent('mask:refresh'));
  });

  container.addEventListener('click', function (e) {
    const btn = e.target.closest('.btn-remove-divida');
    if (! btn) return;
    const rows = container.querySelectorAll('.divida-row');
    if (rows.length <= 1) {
      btn.closest('.divida-row').querySelectorAll('input, textarea').forEach(el => el.value = '');
    } else {
      btn.closest('.divida-row').remove();
    }
  });
});
</script>
@include('_partials._masks-script')
@endsection
