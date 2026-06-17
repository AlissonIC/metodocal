<h5 class="card-header d-flex justify-content-between align-items-center">
  <span>Notificações do Mercado Pago</span>
  <span class="badge bg-label-info">{{ $eventos->total() }}</span>
</h5>
<div class="card-body">
  @forelse ($eventos as $ev)
    <div class="d-flex mb-4 pb-4 {{ ! $loop->last ? 'border-bottom' : '' }}">
      <div class="me-3">
        <span class="avatar avatar-sm">
          <span class="avatar-initial rounded-circle bg-label-info">
            <i class="ti tabler-bell"></i>
          </span>
        </span>
      </div>
      <div class="flex-grow-1">
        <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
          <div>
            <div class="fw-medium">
              {{ $ev->provider }} · evento <code>{{ $ev->provider_event_id }}</code>
            </div>
            <small class="text-muted">
              Recebido em {{ $ev->created_at->format('d/m/Y H:i:s') }}
              @if ($ev->processed_at)
                · processado em {{ $ev->processed_at->format('d/m/Y H:i:s') }}
              @else
                · <span class="text-warning">não processado</span>
              @endif
            </small>
          </div>
          <button class="btn btn-sm btn-label-secondary toggle-payload" data-target="payload-{{ $ev->id }}">
            <i class="ti tabler-code me-1"></i> Payload
          </button>
        </div>
        <pre id="payload-{{ $ev->id }}" class="mt-3 p-3 bg-light rounded small" style="display:none; max-height:300px; overflow:auto;">{{ json_encode($ev->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
      </div>
    </div>
  @empty
    <p class="text-muted mb-0">Nenhuma notificação recebida do Mercado Pago para esta fatura.</p>
  @endforelse

  @if ($eventos->hasPages())
    <div class="mt-5 pt-3 border-top">
      {{ $eventos->links() }}
    </div>
  @endif
</div>
