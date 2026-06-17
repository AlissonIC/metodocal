@extends('layouts/layoutMaster')

@section('title', 'Painel do Mentorado')

@section('content')
<div class="row g-6 mb-6">
  <div class="col-md-6 col-xl-4">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading">Meu Plano</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ optional(optional(auth()->user()->currentSubscription)->plan)->nome ?? 'Sem plano ativo' }}</h4>
            </div>
            <small class="mb-0">Plano atualmente contratado</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-primary">
              <i class="icon-base ti tabler-package icon-26px"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-6 col-xl-4">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading">Status</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">
                <span class="badge bg-label-{{ optional(auth()->user()->currentSubscription)->status === 'ativa' ? 'success' : 'warning' }}">
                  {{ ucfirst(optional(auth()->user()->currentSubscription)->status ?? 'Sem assinatura') }}
                </span>
              </h4>
            </div>
            <small class="mb-0">Situação da sua assinatura</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-success">
              <i class="icon-base ti tabler-shield-check icon-26px"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-12 col-xl-4">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading">Próximos Passos</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">Em breve</h4>
            </div>
            <small class="mb-0">Agenda e conteúdos chegam na Fase 3</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-info">
              <i class="icon-base ti tabler-calendar icon-26px"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <h5 class="card-title">Olá, {{ auth()->user()->name }}!</h5>
    <p class="mb-0">Você está no painel do <strong>Mentorado</strong>. Aqui você acompanhará sua trilha de conteúdos, agenda de sessões e materiais exclusivos.</p>
  </div>
</div>
@endsection
