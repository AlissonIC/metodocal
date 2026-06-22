@extends('layouts/layoutMaster')

@section('title', $user->exists ? 'Editar usuário' : 'Novo usuário')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/select2/select2.js',
'resources/assets/vendor/libs/cleave-zen/cleave-zen.js'])
@endsection

@section('content')
@php
  $editing = $user->exists;
  $action = $editing ? url('/painel/usuarios/' . $user->id) : url('/painel/usuarios');
  $currentRole = $editing ? $user->getRoleNames()->first() : 'mentorado';
  $currentPlanId = $editing ? $user->currentSubscription?->plan_id : null;
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">{{ $editing ? 'Editar usuário' : 'Novo usuário' }}</h4>
  <a href="{{ route('admin.users') }}" class="btn btn-label-secondary">
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
        <div class="card-header border-bottom"><h5 class="card-title mb-0">Dados pessoais</h5></div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-12 mb-4">
              <label class="form-label">Nome *</label>
              <input type="text" class="form-control" name="name" required maxlength="160" value="{{ old('name', $user->name) }}">
            </div>
            <div class="col-md-12 mb-4">
              <label class="form-label">E-mail *</label>
              <input type="email" class="form-control" name="email" required maxlength="160" value="{{ old('email', $user->email) }}">
            </div>
            <div class="col-md-6 mb-4">
              <label class="form-label">Telefone</label>
              <input type="text" class="form-control mask-phone" name="phone" placeholder="(11) 99999-9999" value="{{ old('phone', $user->phone) }}">
            </div>
            <div class="col-md-6 mb-4">
              <label class="form-label">CPF / CNPJ</label>
              <input type="text" class="form-control mask-cpf-cnpj" name="cpf_cnpj" placeholder="000.000.000-00" value="{{ old('cpf_cnpj', $user->cpf_cnpj) }}">
            </div>
          </div>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header border-bottom"><h5 class="card-title mb-0">Acesso</h5></div>
        <div class="card-body">
          <div class="mb-4">
            <label class="form-label">Senha {!! $editing ? '<span class="text-muted small">(deixe em branco para manter)</span>' : '*' !!}</label>
            <input type="password" class="form-control" name="password" autocomplete="new-password" {{ $editing ? '' : 'required' }}>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card mb-4">
        <div class="card-header border-bottom"><h5 class="card-title mb-0">Nível e status</h5></div>
        <div class="card-body">
          <div class="mb-4">
            <label class="form-label">Nível</label>
            <select name="role" class="form-select">
              <option value="mentorado" @selected(old('role', $currentRole) === 'mentorado')>Mentorado</option>
              <option value="licenciado" @selected(old('role', $currentRole) === 'licenciado')>Licenciado</option>
              <option value="admin" @selected(old('role', $currentRole) === 'admin')>Admin</option>
            </select>
          </div>
          <div class="mb-0">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
              <option value="ativo" @selected(old('status', $user->status ?? 'ativo') === 'ativo')>Ativo</option>
              <option value="inativo" @selected(old('status', $user->status) === 'inativo')>Inativo</option>
              <option value="bloqueado" @selected(old('status', $user->status) === 'bloqueado')>Bloqueado</option>
            </select>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header border-bottom"><h5 class="card-title mb-0">Plano</h5></div>
        <div class="card-body">
          <label class="form-label">Plano (opcional)</label>
          <select name="plan_id" id="plan_id" class="select2 form-select">
            <option value="">Sem plano</option>
            @foreach ($plans as $p)
              <option value="{{ $p->id }}" @selected(old('plan_id', $currentPlanId) == $p->id)>{{ $p->nome }} ({{ ucfirst($p->tipo) }})</option>
            @endforeach
          </select>
          <small class="text-muted">Atribuir um plano cria uma assinatura ativa imediatamente.</small>
        </div>
      </div>
    </div>
  </div>

  <div class="d-flex justify-content-end gap-2">
    <a href="{{ route('admin.users') }}" class="btn btn-label-secondary">Cancelar</a>
    <button type="submit" class="btn btn-primary"><i class="icon-base ti tabler-device-floppy me-1"></i> {{ $editing ? 'Salvar alterações' : 'Cadastrar usuário' }}</button>
  </div>
</form>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const $plan = $('#plan_id');
  $plan.wrap('<div class="position-relative"></div>').select2({
    placeholder: 'Selecione...',
    allowClear: true,
    dropdownParent: $plan.parent(),
  });
});
</script>
@include('_partials._masks-script')
@endsection
