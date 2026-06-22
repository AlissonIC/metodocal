@extends('layouts/layoutMaster')

@section('title', $processo->exists ? 'Editar processo' : 'Novo processo')

@section('vendor-script')
@vite(['resources/assets/vendor/libs/cleave-zen/cleave-zen.js'])
@endsection

@section('content')
@php
  $editing = $processo->exists;
  $action = $editing ? route('limpa-nome.update', $processo) : route('limpa-nome.store');
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">{{ $editing ? 'Editar processo' : 'Novo processo de Limpa Nome' }}</h4>
  <a href="{{ $editing ? route('limpa-nome.show', $processo) : route('limpa-nome.index') }}" class="btn btn-label-secondary">
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
    <div class="card-header"><h5 class="card-title mb-0">Tipo de processo</h5></div>
    <div class="card-body">
      <div class="row">
        @foreach (\App\Models\ProcessoLimpaNome::TIPOS as $v => $l)
          <div class="col-md-4 mb-3">
            <div class="form-check custom-option custom-option-basic">
              <label class="form-check-label custom-option-content w-100 d-flex align-items-center" style="padding-block: 0.75rem;">
                <input class="form-check-input me-2 mt-0" type="radio" name="tipo" value="{{ $v }}" @checked(old('tipo', $processo->tipo) === $v)>
                <span class="h6 mb-0">{{ $l }}</span>
              </label>
            </div>
          </div>
        @endforeach
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
          @include('content.limpa-nome.partials.divida-row', ['idx' => $i, 'divida' => $divida])
        @empty
          @include('content.limpa-nome.partials.divida-row', ['idx' => 0, 'divida' => null])
        @endforelse
      </div>
      <small class="text-muted">Preencha apenas as dívidas que quiser registrar. Linhas vazias serão ignoradas.</small>
    </div>
  </div>

  <div class="card mb-4">
    <div class="card-header"><h5 class="card-title mb-0">Observações</h5></div>
    <div class="card-body">
      <textarea name="observacoes_cliente" class="form-control" rows="4" maxlength="3000" placeholder="Informações adicionais que possam ajudar...">{{ old('observacoes_cliente', $processo->observacoes_cliente) }}</textarea>
    </div>
  </div>

  <div class="d-flex justify-content-end gap-2">
    <a href="{{ $editing ? route('limpa-nome.show', $processo) : route('limpa-nome.index') }}" class="btn btn-label-secondary">Cancelar</a>
    <button type="submit" class="btn btn-primary"><i class="ti tabler-device-floppy me-1"></i> {{ $editing ? 'Salvar alterações' : 'Cadastrar processo' }}</button>
  </div>
</form>

<template id="divida-template">
  @include('content.limpa-nome.partials.divida-row', ['idx' => '__IDX__', 'divida' => null])
</template>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
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
