@extends('layouts/layoutMaster')

@section('title', $servico->exists ? 'Editar serviço' : 'Novo serviço')

@section('content')
@php
  $editing = $servico->exists;
  $action = $editing ? url('/painel/servicos/' . $servico->id) : url('/painel/servicos');
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">{{ $editing ? 'Editar serviço' : 'Novo serviço' }}</h4>
  <a href="{{ route('admin.servicos') }}" class="btn btn-label-secondary">
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
          <div class="mb-4">
            <label class="form-label">Nome *</label>
            <input type="text" class="form-control" name="nome" required maxlength="120" placeholder="Ex.: Limpa Nome" value="{{ old('nome', $servico->nome) }}">
            @if ($editing)
              <small class="text-muted">Slug atual: <code>{{ $servico->slug }}</code></small>
            @endif
          </div>
          <div class="mb-4">
            <label class="form-label">Valor padrão (R$)</label>
            <input type="text" inputmode="numeric" class="form-control mask-money" name="valor_padrao" placeholder="0,00" value="{{ old('valor_padrao', $servico->valor_padrao) }}">
            <small class="text-muted">Sugestão para uso ao criar faturas vinculadas ao serviço. Opcional.</small>
          </div>
          <div class="mb-0">
            <label class="form-label">Descrição</label>
            <textarea class="form-control" name="descricao" rows="4" maxlength="1000" placeholder="Descreva o serviço, etapas, prazos típicos...">{{ old('descricao', $servico->descricao) }}</textarea>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card">
        <div class="card-header border-bottom"><h5 class="card-title mb-0">Disponibilidade</h5></div>
        <div class="card-body">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="ativo" name="ativo" value="1" @checked(old('ativo', $editing ? $servico->ativo : true))>
            <label class="form-check-label" for="ativo">Serviço ativo</label>
          </div>
          <small class="text-muted d-block mt-2">Serviços inativos não aparecem para clientes ao criar um novo processo.</small>
        </div>
      </div>
    </div>
  </div>

  <div class="d-flex justify-content-end gap-2">
    <a href="{{ route('admin.servicos') }}" class="btn btn-label-secondary">Cancelar</a>
    <button type="submit" class="btn btn-primary"><i class="icon-base ti tabler-device-floppy me-1"></i> {{ $editing ? 'Salvar alterações' : 'Cadastrar serviço' }}</button>
  </div>
</form>
@endsection

@section('page-script')
@include('_partials._masks-script')
@endsection
