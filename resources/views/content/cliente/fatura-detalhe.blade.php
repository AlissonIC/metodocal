@extends('layouts/layoutMaster')

@section('title', 'Fatura #' . $fatura->id)

@section('content')
@php
  $colors = ['pendente' => 'warning', 'paga' => 'success', 'atrasada' => 'danger', 'cancelada' => 'secondary'];
  $statusEffective = $fatura->isAtrasada() ? 'atrasada' : $fatura->status;
@endphp

<div class="row">
  <div class="col-md-8">
    <div class="card">
      <h5 class="card-header d-flex justify-content-between align-items-center">
        <span>Fatura #{{ $fatura->id }}</span>
        <span class="badge bg-label-{{ $colors[$statusEffective] ?? 'secondary' }}">{{ ucfirst($statusEffective) }}</span>
      </h5>
      <div class="card-body">
        <dl class="row mb-0">
          <dt class="col-sm-4">Plano</dt><dd class="col-sm-8">{{ $fatura->plan?->nome ?? '—' }}</dd>
          <dt class="col-sm-4">Valor</dt><dd class="col-sm-8">R$ {{ number_format((float) $fatura->valor, 2, ',', '.') }}</dd>
          <dt class="col-sm-4">Vencimento</dt><dd class="col-sm-8">{{ $fatura->vencimento->format('d/m/Y') }}</dd>
          @if ($fatura->metodo)
            <dt class="col-sm-4">Método</dt><dd class="col-sm-8">{{ ucfirst($fatura->metodo) }}</dd>
          @endif
          @if ($fatura->pago_em)
            <dt class="col-sm-4">Pago em</dt><dd class="col-sm-8">{{ $fatura->pago_em->format('d/m/Y H:i') }}</dd>
          @endif
          @if ($fatura->gateway_payment_id)
            <dt class="col-sm-4">ID do pagamento</dt><dd class="col-sm-8"><code>{{ $fatura->gateway_payment_id }}</code></dd>
          @endif
        </dl>
      </div>
      @if ($fatura->link_pagamento && in_array($fatura->status, ['pendente', 'atrasada']))
        <div class="card-footer">
          <a href="{{ $fatura->link_pagamento }}" target="_blank" class="btn btn-primary">
            <i class="ti tabler-credit-card me-1"></i> Pagar agora
          </a>
        </div>
      @endif
    </div>
  </div>
  <div class="col-md-4">
    <a href="{{ route('faturas.index') }}" class="btn btn-label-secondary"><i class="ti tabler-arrow-left me-1"></i> Voltar</a>
  </div>
</div>
@endsection
