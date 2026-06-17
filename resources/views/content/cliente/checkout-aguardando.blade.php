@extends('layouts/layoutMaster')

@section('title', 'Aguardando pagamento')

@section('content')
<div class="card">
  <div class="card-body text-center py-5">
    <div class="avatar avatar-xl mx-auto mb-4">
      <span class="avatar-initial rounded-circle bg-label-warning"><i class="ti tabler-clock icon-26px"></i></span>
    </div>
    <h4 class="mb-2">Aguardando pagamento</h4>
    <p class="text-muted mb-4">
      Fatura <strong>#{{ $fatura->id }}</strong> no valor de
      <strong>R$ {{ number_format((float) $fatura->valor, 2, ',', '.') }}</strong>
      do plano <strong>{{ $fatura->plan?->nome }}</strong>.
    </p>

    @if ($fatura->link_pagamento)
      <a href="{{ $fatura->link_pagamento }}" target="_blank" class="btn btn-primary me-2">
        <i class="ti tabler-credit-card me-1"></i> Ir para o pagamento
      </a>
    @elseif (! $mp_configurado)
      <div class="alert alert-warning text-start mx-auto" style="max-width: 600px;">
        <strong>Modo manual.</strong> O gateway de pagamento (Mercado Pago) não está configurado neste ambiente.
        Sua assinatura ficará pendente até que um administrador marque a fatura como paga em
        <em>Financeiro</em>.
      </div>
    @endif

    <div class="mt-4">
      <a href="{{ route('faturas.index') }}" class="btn btn-label-secondary">Ver minhas faturas</a>
      <a href="{{ route('subscription.view') }}" class="btn btn-label-primary">Minha Assinatura</a>
    </div>
  </div>
</div>
@endsection
