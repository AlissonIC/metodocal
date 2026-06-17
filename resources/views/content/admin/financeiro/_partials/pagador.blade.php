<div class="card-body">
  @if ($fatura->payer_name || $fatura->payer_email || $fatura->payer_document || $fatura->payer_address)
    <div class="row g-4">
      @if ($fatura->payer_name)
        <div class="col-md-6">
          <small class="text-muted">Nome</small>
          <div class="fw-medium">{{ $fatura->payer_name }}</div>
        </div>
      @endif
      @if ($fatura->payer_email)
        <div class="col-md-6">
          <small class="text-muted">E-mail</small>
          <div>{{ $fatura->payer_email }}</div>
        </div>
      @endif
      @if ($fatura->payer_document)
        <div class="col-md-6">
          <small class="text-muted">Documento</small>
          <div><code>{{ $fatura->payer_document }}</code></div>
        </div>
      @endif
      @if ($fatura->payer_address)
        <div class="col-md-12">
          <small class="text-muted">Endereço</small>
          <div>
            {{ trim(($fatura->payer_address['street_name'] ?? '') . ', ' . ($fatura->payer_address['street_number'] ?? ''), ', ') }}
            @if ($fatura->payer_address['zip_code'] ?? null)
              · CEP {{ $fatura->payer_address['zip_code'] }}
            @endif
          </div>
        </div>
      @endif
    </div>
    <hr>
    <small class="text-muted d-block">Dados sensíveis (número do cartão, CVV, etc.) <strong>nunca</strong> são armazenados — eles ficam apenas com o gateway de pagamento.</small>
  @else
    <p class="text-muted mb-0">Nenhum dado de pagador disponível.</p>
  @endif
</div>
