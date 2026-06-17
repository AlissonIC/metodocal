@extends('layouts/layoutMaster')

@section('title', 'Minha Assinatura')

@section('content')
@php
  $sub = $subscription;
  $statusColors = ['ativa' => 'success', 'pendente' => 'warning', 'suspensa' => 'danger', 'cancelada' => 'secondary'];
@endphp

<div class="row g-6 mb-6">
  <div class="col-md-8">
    <div class="card">
      <h5 class="card-header">Assinatura atual</h5>
      <div class="card-body">
        @if ($sub && $sub->plan)
          <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
              <h4 class="mb-1">{{ $sub->plan->nome }}</h4>
              <p class="text-muted mb-2">{{ ucfirst($sub->plan->tipo) }} · {{ ucfirst($sub->plan->recorrencia) }} · R$ {{ number_format((float) $sub->plan->preco, 2, ',', '.') }}</p>
              <span class="badge bg-label-{{ $statusColors[$sub->status] ?? 'secondary' }}">{{ ucfirst($sub->status) }}</span>
            </div>
            <div class="text-end">
              @if ($sub->started_at)
                <div><small class="text-muted">Início:</small> {{ $sub->started_at->format('d/m/Y') }}</div>
              @endif
              @if ($sub->ends_at)
                <div><small class="text-muted">Vencimento:</small> {{ $sub->ends_at->format('d/m/Y') }}</div>
              @endif
            </div>
          </div>

          @if ($sub->plan->descricao)
            <hr class="my-4">
            <p class="mb-0">{{ $sub->plan->descricao }}</p>
          @endif

          @if ($sub->plan->permissions)
            <hr class="my-4">
            <h6>Módulos liberados</h6>
            <ul class="list-unstyled mb-0">
              @foreach ($sub->plan->permissions as $perm)
                <li><i class="icon-base ti tabler-circle-check text-success me-2"></i>{{ $perm }}</li>
              @endforeach
            </ul>
          @endif
        @else
          <div class="text-center py-5">
            <i class="icon-base ti tabler-file-off icon-48px text-muted mb-3"></i>
            <h5>Você ainda não tem um plano contratado</h5>
            <p class="text-muted mb-0">Os planos abaixo estão disponíveis para você.</p>
          </div>
        @endif
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card h-100">
      <h5 class="card-header d-flex justify-content-between align-items-center">
        Faturas recentes
        <a href="{{ route('faturas.index') }}" class="small">Ver todas</a>
      </h5>
      <div class="card-body">
        @forelse ($faturas_recentes as $f)
          @php
            $colors = ['pendente' => 'warning', 'paga' => 'success', 'atrasada' => 'danger', 'cancelada' => 'secondary'];
            $statusEffective = $f->isAtrasada() ? 'atrasada' : $f->status;
          @endphp
          <div class="d-flex justify-content-between align-items-start mb-3 pb-3 border-bottom">
            <div>
              <h6 class="mb-1">#{{ $f->id }} · {{ $f->plan?->nome }}</h6>
              <small class="text-muted d-block">Vence {{ $f->vencimento->format('d/m/Y') }}</small>
              <span class="badge bg-label-{{ $colors[$statusEffective] ?? 'secondary' }} mt-1">{{ ucfirst($statusEffective) }}</span>
            </div>
            <div class="text-end">
              <strong>R$ {{ number_format((float) $f->valor, 2, ',', '.') }}</strong>
              @if ($f->link_pagamento && in_array($f->status, ['pendente', 'atrasada']))
                <div><a href="{{ $f->link_pagamento }}" target="_blank" class="btn btn-xs btn-primary mt-1">Pagar</a></div>
              @endif
            </div>
          </div>
        @empty
          <p class="text-muted mb-0 text-center py-3">Nenhuma fatura ainda.</p>
        @endforelse
      </div>
    </div>
  </div>
</div>

@if ($planos_disponiveis->count())
<div class="card mb-6">
  <h5 class="card-header">Planos disponíveis</h5>
  <div class="card-body">
    <div class="row g-4">
      @foreach ($planos_disponiveis as $p)
        <div class="col-md-4">
          <div class="card border h-100">
            <div class="card-body">
              <h5 class="mb-1">{{ $p->nome }}</h5>
              <p class="text-muted mb-3">{{ ucfirst($p->recorrencia) }}</p>
              <h3 class="mb-3">R$ {{ number_format((float) $p->preco, 2, ',', '.') }}</h3>
              @if ($p->descricao)
                <p class="small mb-3">{{ $p->descricao }}</p>
              @endif
              <form method="POST" action="{{ route('checkout.contratar', $p) }}">
                @csrf
                <button type="submit" class="btn btn-primary w-100">
                  <i class="ti tabler-credit-card me-1"></i> Contratar agora
                </button>
              </form>
            </div>
          </div>
        </div>
      @endforeach
    </div>
  </div>
</div>
@endif

@if ($historico->count() > 1)
<div class="card">
  <h5 class="card-header">Histórico de assinaturas</h5>
  <div class="table-responsive">
    <table class="table table-borderless mb-0">
      <thead>
        <tr><th>Plano</th><th>Status</th><th>Início</th><th>Encerramento</th></tr>
      </thead>
      <tbody>
        @foreach ($historico as $h)
          <tr>
            <td>{{ $h->plan?->nome ?? '—' }}</td>
            <td><span class="badge bg-label-{{ $statusColors[$h->status] ?? 'secondary' }}">{{ ucfirst($h->status) }}</span></td>
            <td>{{ $h->started_at?->format('d/m/Y') ?? '—' }}</td>
            <td>{{ $h->canceled_at?->format('d/m/Y') ?? $h->ends_at?->format('d/m/Y') ?? '—' }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endif
@endsection
