@php
  $labelMap = [
    'attributes' => 'Novos valores',
    'old' => 'Valores anteriores',
    'acao' => 'Ação',
    'de' => 'De',
    'para' => 'Para',
    'motivo' => 'Motivo',
    'mp_configurado' => 'MP configurado',
    'tinha_gateway_payment_id' => 'Tinha gateway payment ID',
    'refund' => 'Dados do estorno',
    'sem_gateway' => 'Sem gateway',
    'status' => 'Status',
    'valor' => 'Valor',
    'pago_em' => 'Pago em',
    'estornada_em' => 'Estornada em',
    'gateway_payment_id' => 'Payment ID (MP)',
    'gateway_refund_id' => 'Refund ID (MP)',
  ];
  $descMap = [
    'created' => 'Registro criado',
    'updated' => 'Registro atualizado',
    'deleted' => 'Registro removido',
  ];
  $acaoMap = [
    'estorno_manual' => 'Estorno manual',
    'estorno_gateway' => 'Estorno via gateway',
    'mudanca_status' => 'Mudança de status',
  ];
@endphp

<h5 class="card-header d-flex justify-content-between align-items-center">
  <span>Histórico de alterações</span>
  <span class="badge bg-label-warning">{{ $auditoria->total() }}</span>
</h5>
<div class="card-body">
  @forelse ($auditoria as $log)
    <div class="d-flex mb-3 pb-3 {{ ! $loop->last ? 'border-bottom' : '' }}">
      <div class="me-3">
        <span class="avatar avatar-sm">
          <span class="avatar-initial rounded-circle bg-label-{{ $log->log_name === 'financeiro_manual' ? 'warning' : 'secondary' }}">
            <i class="ti tabler-user-edit"></i>
          </span>
        </span>
      </div>
      <div class="flex-grow-1">
        <div class="fw-medium">
          {{ $descMap[$log->description] ?? $log->description }}
        </div>
        <small class="text-muted">
          {{ $log->created_at->format('d/m/Y H:i:s') }}
          · por <strong>{{ $log->causer?->name ?? 'sistema' }}</strong>
        </small>
        @if ($log->properties && $log->properties->count())
          <div class="mt-2 small">
            @foreach ($log->properties as $k => $v)
              @php
                $labelPt = $labelMap[$k] ?? ucfirst(str_replace('_', ' ', $k));
                if ($k === 'acao' && is_string($v) && isset($acaoMap[$v])) {
                  $v = $acaoMap[$v];
                }
              @endphp
              @if (is_array($v) || is_object($v))
                <div><strong>{{ $labelPt }}:</strong> <code>{{ json_encode($v, JSON_UNESCAPED_UNICODE) }}</code></div>
              @elseif (is_bool($v))
                <div><strong>{{ $labelPt }}:</strong> {{ $v ? 'sim' : 'não' }}</div>
              @else
                <div><strong>{{ $labelPt }}:</strong> {{ $v }}</div>
              @endif
            @endforeach
          </div>
        @endif
      </div>
    </div>
  @empty
    <p class="text-muted mb-0">Nenhuma alteração registrada.</p>
  @endforelse

  @if ($auditoria->hasPages())
    <div class="mt-5 pt-3 border-top">
      {{ $auditoria->links() }}
    </div>
  @endif
</div>
