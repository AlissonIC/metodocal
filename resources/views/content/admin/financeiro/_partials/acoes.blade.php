<h5 class="card-header">Ações</h5>
<div class="card-body d-grid gap-3">

  <div>
    <label class="form-label">Trocar status manualmente</label>
    <select class="form-select mb-2" id="novoStatus">
      <option value="">— selecione —</option>
      <option value="pendente" {{ $fatura->status === 'pendente' ? 'disabled' : '' }}>Pendente</option>
      <option value="paga" {{ $fatura->status === 'paga' ? 'disabled' : '' }}>Paga</option>
      <option value="atrasada" {{ $fatura->status === 'atrasada' ? 'disabled' : '' }}>Atrasada</option>
      <option value="cancelada" {{ $fatura->status === 'cancelada' ? 'disabled' : '' }}>Cancelada</option>
      <option value="estornada" {{ $fatura->status === 'estornada' ? 'disabled' : '' }}>Estornada</option>
    </select>
    <input type="text" id="motivo" class="form-control form-control-sm mb-2" placeholder="Motivo (opcional)" maxlength="500">
    <button class="btn btn-warning w-100" id="btnMudarStatus">
      <i class="ti tabler-replace me-1"></i> Alterar status
    </button>
    <small class="text-muted d-block mt-1 text-center">A alteração fica registrada na auditoria.</small>
  </div>

  @if ($fatura->status === 'paga')
    <hr class="my-2">
    <button class="btn btn-outline-danger w-100" id="btnEstornar">
      <i class="ti tabler-arrow-back-up me-1"></i> Estornar pagamento
    </button>
    <small class="text-muted d-block text-center">
      @if ($fatura->gateway_payment_id)
        Solicita estorno via Mercado Pago.
      @else
        Sem gateway — registra estorno manual.
      @endif
    </small>
  @endif

  @if ($fatura->link_pagamento && in_array($fatura->status, ['pendente', 'atrasada']))
    <hr class="my-2">
    <a href="{{ $fatura->link_pagamento }}" target="_blank" class="btn btn-label-primary w-100">
      <i class="ti tabler-external-link me-1"></i> Abrir link de pagamento
    </a>
  @endif
</div>
