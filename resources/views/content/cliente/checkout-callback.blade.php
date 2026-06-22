@extends('layouts/layoutMaster')

@section('title', $titulo)

@section('content')
@php
  $statusEfetivo = $fatura ? ($fatura->isAtrasada() ? 'atrasada' : $fatura->status) : null;
  $statusLabels = [
    'pendente' => ['Pagamento pendente', 'warning', 'clock'],
    'paga' => ['Pagamento confirmado', 'success', 'check'],
    'atrasada' => ['Em atraso', 'danger', 'alert-triangle'],
    'cancelada' => ['Cancelado', 'secondary', 'x'],
    'estornada' => ['Estornado', 'secondary', 'arrow-back-up'],
  ];
@endphp

<div class="card">
  <div class="card-body text-center py-5">
    <div class="avatar avatar-xl mx-auto mb-4">
      <span class="avatar-initial rounded-circle bg-label-{{ $color }}">
        <i class="icon-base ti tabler-{{ $icon }} icon-26px"></i>
      </span>
    </div>
    <h4 class="mb-2">{{ $titulo }}</h4>
    <p class="text-muted mb-4">{{ $mensagem }}</p>

    @if ($fatura)
      <div class="card border mx-auto mb-4 text-start" style="max-width: 540px;">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h6 class="mb-0">Fatura #{{ $fatura->id }} · {{ $fatura->plan?->nome }}</h6>
            @if ($statusEfetivo && isset($statusLabels[$statusEfetivo]))
              @php [$lbl, $clr, $ico] = $statusLabels[$statusEfetivo]; @endphp
              <span class="badge bg-label-{{ $clr }}">
                <i class="icon-base ti tabler-{{ $ico }} me-1"></i> {{ $lbl }}
              </span>
            @endif
          </div>
          <dl class="row mb-0 small">
            <dt class="col-5 text-muted">Valor</dt>
            <dd class="col-7">R$ {{ number_format((float) $fatura->valor, 2, ',', '.') }}</dd>

            <dt class="col-5 text-muted">Vencimento</dt>
            <dd class="col-7">{{ $fatura->vencimento->format('d/m/Y') }}</dd>

            @if ($fatura->pago_em)
              <dt class="col-5 text-muted">Pago em</dt>
              <dd class="col-7">{{ $fatura->pago_em->format('d/m/Y H:i') }}</dd>
            @endif

            @if ($fatura->metodo)
              <dt class="col-5 text-muted">Método</dt>
              <dd class="col-7">{{ ucfirst($fatura->metodo) }}</dd>
            @endif

            @if ($mp_payment_id)
              <dt class="col-5 text-muted">ID do pagamento</dt>
              <dd class="col-7"><code class="small">{{ $mp_payment_id }}</code></dd>
            @endif
          </dl>
        </div>
      </div>

      @if ($tipo === 'pendente' && $fatura->status === 'pendente')
        <div class="alert alert-info mx-auto" style="max-width: 540px;">
          <i class="icon-base ti tabler-info-circle me-1"></i>
          A confirmação chega assim que o Mercado Pago processar. Você pode acompanhar em tempo real
          na página de detalhes da fatura.
        </div>
        <a href="{{ route('faturas.show', $fatura) }}" class="btn btn-primary me-2">
          <i class="icon-base ti tabler-eye me-1"></i> Acompanhar pagamento
        </a>
      @endif

      @if ($tipo === 'falha' && $fatura->link_pagamento)
        <a href="{{ $fatura->link_pagamento }}" target="_blank" class="btn btn-primary me-2">
          <i class="icon-base ti tabler-credit-card me-1"></i> Tentar novamente
        </a>
      @endif
    @endif

    <a href="{{ route('subscription.view') }}" class="btn @if ($fatura && $tipo !== 'sucesso') btn-label-secondary @else btn-primary @endif">
      Minha Assinatura
    </a>
    <a href="{{ route('faturas.index') }}" class="btn btn-label-secondary">Minhas faturas</a>
  </div>
</div>
@endsection
