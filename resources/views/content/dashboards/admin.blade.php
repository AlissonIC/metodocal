@extends('layouts/layoutMaster')

@section('title', 'Painel do Administrador')

@section('content')
@php
  $totalUsuarios = \App\Models\User::count();
  $totalMentorados = \App\Models\User::role('mentorado')->count();
  $totalLicenciados = \App\Models\User::role('licenciado')->count();
  $totalPlanos = \App\Models\Plan::count();
  $assinaturasAtivas = \App\Models\Subscription::where('status', 'ativa')->count();
  $receitaMensal = \App\Models\Subscription::where('status', 'ativa')
      ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
      ->where('plans.recorrencia', 'mensal')
      ->sum('plans.preco');
@endphp

<div class="row g-6 mb-6">
  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading">Total de Usuários</span>
            <h4 class="my-1">{{ $totalUsuarios }}</h4>
            <small class="mb-0">Cadastrados na plataforma</small>
          </div>
          <div class="avatar"><span class="avatar-initial rounded bg-label-primary"><i class="icon-base ti tabler-users icon-26px"></i></span></div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading">Mentorados</span>
            <h4 class="my-1">{{ $totalMentorados }}</h4>
            <small class="mb-0">Clientes do tipo mentorado</small>
          </div>
          <div class="avatar"><span class="avatar-initial rounded bg-label-success"><i class="icon-base ti tabler-user-star icon-26px"></i></span></div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading">Licenciados</span>
            <h4 class="my-1">{{ $totalLicenciados }}</h4>
            <small class="mb-0">Parceiros licenciados</small>
          </div>
          <div class="avatar"><span class="avatar-initial rounded bg-label-info"><i class="icon-base ti tabler-license icon-26px"></i></span></div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading">Planos cadastrados</span>
            <h4 class="my-1">{{ $totalPlanos }}</h4>
            <small class="mb-0">Disponíveis no sistema</small>
          </div>
          <div class="avatar"><span class="avatar-initial rounded bg-label-warning"><i class="icon-base ti tabler-package icon-26px"></i></span></div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row g-6 mb-6">
  <div class="col-md-6">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading">Assinaturas ativas</span>
            <h4 class="my-1">{{ $assinaturasAtivas }}</h4>
            <small class="mb-0">Status "ativa" no banco</small>
          </div>
          <div class="avatar"><span class="avatar-initial rounded bg-label-success"><i class="icon-base ti tabler-shield-check icon-26px"></i></span></div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading">Receita recorrente mensal (estimada)</span>
            <h4 class="my-1">R$ {{ number_format((float) $receitaMensal, 2, ',', '.') }}</h4>
            <small class="mb-0">Soma dos planos mensais ativos · Fase 4 confirma via pagamento</small>
          </div>
          <div class="avatar"><span class="avatar-initial rounded bg-label-primary"><i class="icon-base ti tabler-cash icon-26px"></i></span></div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <h5 class="card-title">Bem-vindo, {{ auth()->user()->name }}!</h5>
    <p class="mb-0">Use o menu lateral para gerenciar <a href="{{ route('admin.users') }}">usuários</a> e <a href="{{ route('admin.plans') }}">planos</a>.</p>
  </div>
</div>
@endsection
