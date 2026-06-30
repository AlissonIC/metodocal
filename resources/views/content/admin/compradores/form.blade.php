@extends('layouts/layoutMaster')

@section('title', $comprador->exists ? 'Editar comprador' : 'Novo comprador')

@section('vendor-script')
@vite(['resources/assets/vendor/libs/cleave-zen/cleave-zen.js'])
@endsection

@section('content')
@php
  $editing = $comprador->exists;
  $action = $editing ? url('/painel/admin/compradores/' . $comprador->id) : url('/painel/admin/compradores');
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">{{ $editing ? 'Editar comprador' : 'Novo comprador' }}</h4>
  <a href="{{ route('admin.compradores') }}" class="btn btn-label-secondary">
    <i class="icon-base ti tabler-arrow-left me-1"></i> Voltar
  </a>
</div>

@if ($errors->any())
  <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul></div>
@endif

<form method="POST" action="{{ $action }}">
  @csrf
  @if ($editing) @method('PATCH') @endif

  <div class="row g-4">
    <div class="col-lg-8">
      <div class="card mb-4">
        <div class="card-header border-bottom"><h5 class="card-title mb-0">Dados do comprador</h5></div>
        <div class="card-body">
          <div class="mb-4">
            <label class="form-label">Nome completo *</label>
            <input type="text" class="form-control" name="nome" required maxlength="160" value="{{ old('nome', $comprador->nome) }}" placeholder="João da Silva">
          </div>
          <div class="row">
            <div class="col-md-4 mb-4">
              <label class="form-label">Tipo de documento *</label>
              <select name="tipo_documento" class="form-select" required>
                @foreach (['cpf' => 'CPF', 'cnpj' => 'CNPJ'] as $v => $l)
                  <option value="{{ $v }}" @selected(old('tipo_documento', $comprador->tipo_documento) === $v)>{{ $l }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-8 mb-4">
              <label class="form-label">Documento *</label>
              <input type="text" class="form-control mask-cpf-cnpj" name="documento" required maxlength="20" value="{{ old('documento', $comprador->documento) }}" placeholder="000.000.000-00">
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-4">
              <label class="form-label">E-mail</label>
              <input type="email" class="form-control" name="email" maxlength="160" value="{{ old('email', $comprador->email) }}">
            </div>
            <div class="col-md-6 mb-4">
              <label class="form-label">Telefone</label>
              <input type="text" class="form-control mask-phone" name="telefone" maxlength="40" value="{{ old('telefone', $comprador->telefone) }}" placeholder="(00) 00000-0000">
            </div>
          </div>
          <div class="mb-0">
            <label class="form-label">Observações</label>
            <textarea name="observacoes" class="form-control" rows="4" maxlength="3000" placeholder="Notas internas sobre o comprador...">{{ old('observacoes', $comprador->observacoes) }}</textarea>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card">
        <div class="card-header border-bottom"><h5 class="card-title mb-0">Status</h5></div>
        <div class="card-body">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="ativo" name="ativo" value="1" @checked(old('ativo', $editing ? $comprador->ativo : true))>
            <label class="form-check-label" for="ativo">Comprador ativo</label>
          </div>
          <small class="text-muted d-block mt-2">Compradores inativos não aparecem na seleção ao vincular a processos.</small>
        </div>
      </div>
    </div>
  </div>

  <div class="d-flex justify-content-end gap-2">
    <a href="{{ route('admin.compradores') }}" class="btn btn-label-secondary">Cancelar</a>
    <button type="submit" class="btn btn-primary"><i class="icon-base ti tabler-device-floppy me-1"></i> {{ $editing ? 'Salvar alterações' : 'Cadastrar comprador' }}</button>
  </div>
</form>
@endsection

@section('page-script')
@include('_partials._masks-script')
@endsection
