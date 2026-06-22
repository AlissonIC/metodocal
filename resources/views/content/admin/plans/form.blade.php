@extends('layouts/layoutMaster')

@section('title', $plan->exists ? 'Editar plano' : 'Novo plano')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('content')
@php
  $editing = $plan->exists;
  $action = $editing ? url('/painel/planos/' . $plan->id) : url('/painel/planos');
  $selectedPerms = old('permissions', $plan->permissions ?? []);
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">{{ $editing ? 'Editar plano' : 'Novo plano' }}</h4>
  <a href="{{ route('admin.plans') }}" class="btn btn-label-secondary">
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
            <input type="text" class="form-control" name="nome" required maxlength="120" placeholder="Mentoria Premium" value="{{ old('nome', $plan->nome) }}">
          </div>
          <div class="row">
            <div class="col-md-4 mb-4">
              <label class="form-label">Tipo *</label>
              <select name="tipo" class="form-select">
                <option value="mentorado" @selected(old('tipo', $plan->tipo) === 'mentorado')>Mentorado</option>
                <option value="licenciado" @selected(old('tipo', $plan->tipo) === 'licenciado')>Licenciado</option>
              </select>
            </div>
            <div class="col-md-4 mb-4">
              <label class="form-label">Preço (R$) *</label>
              <input type="text" inputmode="numeric" class="form-control mask-money" name="preco" placeholder="0,00" value="{{ old('preco', $plan->preco) }}">
            </div>
            <div class="col-md-4 mb-4">
              <label class="form-label">Recorrência *</label>
              <select name="recorrencia" class="form-select">
                <option value="mensal" @selected(old('recorrencia', $plan->recorrencia) === 'mensal')>Mensal</option>
                <option value="anual" @selected(old('recorrencia', $plan->recorrencia) === 'anual')>Anual</option>
                <option value="vitalicio" @selected(old('recorrencia', $plan->recorrencia) === 'vitalicio')>Vitalício</option>
              </select>
            </div>
          </div>
          <div class="mb-0">
            <label class="form-label">Descrição</label>
            <textarea class="form-control" name="descricao" rows="3" maxlength="1000">{{ old('descricao', $plan->descricao) }}</textarea>
          </div>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header border-bottom"><h5 class="card-title mb-0">Módulos liberados</h5></div>
        <div class="card-body">
          <select name="permissions[]" id="permissions" class="select2 form-select" multiple>
            @foreach ($permissions as $perm)
              <option value="{{ $perm }}" @selected(in_array($perm, $selectedPerms))>{{ $perm }}</option>
            @endforeach
          </select>
          <small class="text-muted">Estes módulos serão liberados para o usuário quando a assinatura estiver ativa.</small>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card">
        <div class="card-header border-bottom"><h5 class="card-title mb-0">Status</h5></div>
        <div class="card-body">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="ativo" name="ativo" value="1" @checked(old('ativo', $editing ? $plan->ativo : true))>
            <label class="form-check-label" for="ativo">Plano ativo</label>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="d-flex justify-content-end gap-2">
    <a href="{{ route('admin.plans') }}" class="btn btn-label-secondary">Cancelar</a>
    <button type="submit" class="btn btn-primary"><i class="icon-base ti tabler-device-floppy me-1"></i> {{ $editing ? 'Salvar alterações' : 'Cadastrar plano' }}</button>
  </div>
</form>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const $perm = $('#permissions');
  $perm.wrap('<div class="position-relative"></div>').select2({
    placeholder: 'Selecione os módulos',
    dropdownParent: $perm.parent(),
  });
});
</script>
@include('_partials._masks-script')
@endsection
