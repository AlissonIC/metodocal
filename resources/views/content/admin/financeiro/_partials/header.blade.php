@php
  $statusColors = [
    'pendente' => 'warning', 'paga' => 'success', 'atrasada' => 'danger',
    'cancelada' => 'secondary', 'estornada' => 'info',
  ];
  $statusEffective = $fatura->isAtrasada() ? 'atrasada' : $fatura->status;
@endphp
<a href="{{ route('admin.financeiro') }}" class="btn btn-sm btn-label-secondary">
  <i class="ti tabler-arrow-left me-1"></i> Voltar
</a>
<h4 class="mb-0">Pagamento #{{ $fatura->id }}</h4>
<span class="badge bg-label-{{ $statusColors[$statusEffective] ?? 'secondary' }}">{{ ucfirst($statusEffective) }}</span>
