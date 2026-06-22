@extends('layouts/layoutMaster')

@section('title', 'Minhas Faturas')

@section('content')
@php
  $colors = ['pendente' => 'warning', 'paga' => 'success', 'atrasada' => 'danger', 'cancelada' => 'secondary'];
  $temFiltro = $filtros['status'] !== '' || $filtros['planId'] !== '' || $filtros['busca'] !== '';
@endphp

<div class="card">
  <div class="card-header border-bottom">
    <h5 class="card-title mb-0">Minhas Faturas</h5>
    <p class="text-muted mb-0 mt-1 small">Acompanhe e pague suas faturas.</p>
  </div>

  <div class="card-body">
    <form method="GET" action="{{ route('faturas.index') }}" class="row g-2">
      <div class="col-md-4">
        <label class="form-label small">Status</label>
        <select name="status" class="form-select form-select-sm">
          <option value="">Todos</option>
          <option value="pendente" @selected($filtros['status'] === 'pendente')>Pendente</option>
          <option value="atrasada" @selected($filtros['status'] === 'atrasada')>Atrasada</option>
          <option value="paga" @selected($filtros['status'] === 'paga')>Paga</option>
          <option value="cancelada" @selected($filtros['status'] === 'cancelada')>Cancelada</option>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label small">Plano</label>
        <select name="plan_id" class="form-select form-select-sm">
          <option value="">Todos</option>
          @foreach ($planos as $p)
            <option value="{{ $p->id }}" @selected((string) $filtros['planId'] === (string) $p->id)>{{ $p->nome }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label small">Buscar</label>
        <input type="text" name="q" class="form-control form-control-sm" placeholder="Nº da fatura ou nome do plano" value="{{ $filtros['busca'] }}">
      </div>
      <div class="col-12 d-flex gap-2 mt-3">
        <button type="submit" class="btn btn-sm btn-primary"><i class="icon-base ti tabler-search me-1"></i> Filtrar</button>
        @if ($temFiltro)
          <a href="{{ route('faturas.index') }}" class="btn btn-sm btn-label-secondary">Limpar</a>
        @endif
      </div>
    </form>
  </div>

  <div class="table-responsive border-top">
    <table class="table mb-0">
      <thead>
        <tr>
          <th>#</th><th>Plano</th><th>Valor</th><th>Vencimento</th><th>Status</th><th>Ações</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($faturas as $f)
          <tr>
            <td>{{ $f->id }}</td>
            <td>{{ $f->plan?->nome ?? '—' }}</td>
            <td>R$ {{ number_format((float) $f->valor, 2, ',', '.') }}</td>
            <td>{{ $f->vencimento->format('d/m/Y') }}</td>
            <td>
              @php $statusEffective = $f->isAtrasada() ? 'atrasada' : $f->status; @endphp
              <span class="badge bg-label-{{ $colors[$statusEffective] ?? 'secondary' }}">{{ ucfirst($statusEffective) }}</span>
            </td>
            <td>
              @if ($f->link_pagamento && in_array($f->status, ['pendente', 'atrasada']))
                <a href="{{ $f->link_pagamento }}" target="_blank" class="btn btn-sm btn-primary">
                  <i class="icon-base ti tabler-credit-card me-1"></i> Pagar
                </a>
              @endif
              <a href="{{ route('faturas.show', $f) }}" class="btn btn-sm btn-label-secondary">
                <i class="icon-base ti tabler-eye me-1"></i> Detalhes
              </a>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6" class="text-center text-muted py-5">
              <i class="icon-base ti tabler-receipt-off icon-48px text-muted mb-3 d-block"></i>
              {{ $temFiltro ? 'Nenhuma fatura encontrada com esses filtros.' : 'Você ainda não tem faturas.' }}
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if ($faturas->hasPages())
    <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
      <small class="text-muted">
        Mostrando {{ $faturas->firstItem() }}–{{ $faturas->lastItem() }} de {{ $faturas->total() }}
      </small>
      {{ $faturas->onEachSide(1)->links() }}
    </div>
  @endif
</div>
@endsection
