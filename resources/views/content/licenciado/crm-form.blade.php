@extends('layouts/layoutMaster')

@section('title', $cliente->exists ? 'Editar cliente' : 'Novo cliente')

@section('vendor-script')
@vite(['resources/assets/vendor/libs/cleave-zen/cleave-zen.js'])
@endsection

@section('content')
@php
  $editing = $cliente->exists;
  $action = $editing ? url('/painel/crm/' . $cliente->id) : url('/painel/crm');
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">{{ $editing ? 'Editar cliente' : 'Novo cliente' }}</h4>
  <a href="{{ route('licenciado.crm') }}" class="btn btn-label-secondary">
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
        <div class="card-header border-bottom"><h5 class="card-title mb-0">Dados do cliente</h5></div>
        <div class="card-body">
          <div class="mb-4">
            <label class="form-label">Nome *</label>
            <input type="text" class="form-control" name="nome" required maxlength="120" value="{{ old('nome', $cliente->nome) }}">
          </div>
          <div class="row">
            <div class="col-md-6 mb-4">
              <label class="form-label">E-mail</label>
              <input type="email" class="form-control" name="email" maxlength="180" value="{{ old('email', $cliente->email) }}">
            </div>
            <div class="col-md-6 mb-4">
              <label class="form-label">Telefone</label>
              <input type="text" class="form-control mask-phone" name="telefone" placeholder="(11) 99999-9999" value="{{ old('telefone', $cliente->telefone) }}">
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-4">
              <label class="form-label">CPF/CNPJ</label>
              <input type="text" class="form-control mask-cpf-cnpj" name="cpf_cnpj" placeholder="000.000.000-00" value="{{ old('cpf_cnpj', $cliente->cpf_cnpj) }}">
            </div>
          </div>
          <div class="mb-0">
            <label class="form-label">Endereço</label>
            <textarea class="form-control" name="endereco" rows="2" maxlength="1000">{{ old('endereco', $cliente->endereco) }}</textarea>
          </div>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header border-bottom"><h5 class="card-title mb-0">Notas</h5></div>
        <div class="card-body">
          <textarea class="form-control" name="notas" rows="4" maxlength="2000" placeholder="Observações internas sobre este cliente...">{{ old('notas', $cliente->notas) }}</textarea>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card">
        <div class="card-header border-bottom"><h5 class="card-title mb-0">Status</h5></div>
        <div class="card-body">
          <label class="form-label">Status *</label>
          <select name="status" class="form-select">
            <option value="lead" @selected(old('status', $cliente->status ?? 'lead') === 'lead')>Lead</option>
            <option value="ativo" @selected(old('status', $cliente->status) === 'ativo')>Ativo</option>
            <option value="perdido" @selected(old('status', $cliente->status) === 'perdido')>Perdido</option>
          </select>
        </div>
      </div>
    </div>
  </div>

  <div class="d-flex justify-content-end gap-2">
    <a href="{{ route('licenciado.crm') }}" class="btn btn-label-secondary">Cancelar</a>
    <button type="submit" class="btn btn-primary"><i class="icon-base ti tabler-device-floppy me-1"></i> {{ $editing ? 'Salvar alterações' : 'Cadastrar cliente' }}</button>
  </div>
</form>
@endsection

@section('page-script')
@include('_partials._masks-script')
@endsection
