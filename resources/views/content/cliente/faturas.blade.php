@extends('layouts/layoutMaster')

@section('title', 'Minhas Faturas')

@section('content')
@php
  $colors = ['pendente' => 'warning', 'paga' => 'success', 'atrasada' => 'danger', 'cancelada' => 'secondary'];
@endphp

<div class="card">
  <h5 class="card-header">Minhas Faturas</h5>
  <div class="table-responsive">
    <table class="table">
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
                  <i class="ti tabler-credit-card me-1"></i> Pagar
                </a>
              @endif
              <a href="{{ route('faturas.show', $f) }}" class="btn btn-sm btn-label-secondary">
                <i class="ti tabler-eye me-1"></i> Detalhes
              </a>
            </td>
          </tr>
        @empty
          <tr><td colspan="6" class="text-center text-muted py-4">Você ainda não tem faturas.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div class="card-footer">{{ $faturas->links() }}</div>
</div>
@endsection
