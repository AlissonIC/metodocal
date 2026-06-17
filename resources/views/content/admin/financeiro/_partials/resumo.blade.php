<div class="card-body">
  <div class="row g-4">
    <div class="col-md-6">
      <small class="text-muted">Cliente</small>
      <div class="fw-medium">{{ $fatura->user?->name ?? '—' }}</div>
      <div class="small text-muted">{{ $fatura->user?->email ?? '' }}</div>
    </div>
    <div class="col-md-6">
      <small class="text-muted">Plano</small>
      <div class="fw-medium">{{ $fatura->plan?->nome ?? '—' }}</div>
      <div class="small text-muted">{{ ucfirst($fatura->plan?->tipo ?? '') }} · {{ ucfirst($fatura->plan?->recorrencia ?? '') }}</div>
    </div>
    <div class="col-md-4">
      <small class="text-muted">Valor</small>
      <div class="fw-medium fs-5">R$ {{ number_format((float) $fatura->valor, 2, ',', '.') }}</div>
    </div>
    <div class="col-md-4">
      <small class="text-muted">Vencimento</small>
      <div class="fw-medium">{{ $fatura->vencimento->format('d/m/Y') }}</div>
    </div>
    <div class="col-md-4">
      <small class="text-muted">Método</small>
      <div class="fw-medium">{{ $fatura->metodo ? ucfirst($fatura->metodo) : '—' }}</div>
    </div>
    @if ($fatura->pago_em)
      <div class="col-md-6">
        <small class="text-muted">Pago em</small>
        <div class="fw-medium">{{ $fatura->pago_em->format('d/m/Y H:i') }}</div>
      </div>
    @endif
    @if ($fatura->estornada_em)
      <div class="col-md-6">
        <small class="text-muted">Estornada em</small>
        <div class="fw-medium">{{ $fatura->estornada_em->format('d/m/Y H:i') }}</div>
      </div>
    @endif
    @if ($fatura->gateway_payment_id)
      <div class="col-md-6">
        <small class="text-muted">Payment ID (MP)</small>
        <div><code>{{ $fatura->gateway_payment_id }}</code></div>
      </div>
    @endif
    @if ($fatura->gateway_preference_id)
      <div class="col-md-6">
        <small class="text-muted">Preference ID (MP)</small>
        <div><code>{{ $fatura->gateway_preference_id }}</code></div>
      </div>
    @endif
    @if ($fatura->gateway_refund_id)
      <div class="col-md-6">
        <small class="text-muted">Refund ID (MP)</small>
        <div><code>{{ $fatura->gateway_refund_id }}</code></div>
      </div>
    @endif
  </div>
</div>
