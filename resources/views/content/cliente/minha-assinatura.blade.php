@extends('layouts/layoutMaster')

@section('title', 'Minha Assinatura')

@section('content')
@php
  $sub = $subscription;
  $statusColors = ['ativa' => 'success', 'pendente' => 'warning', 'suspensa' => 'danger', 'cancelada' => 'secondary'];
  $recorrenciaLabels = ['mensal' => 'Mensal', 'trimestral' => 'Trimestral', 'semestral' => 'Semestral', 'anual' => 'Anual', 'vitalicio' => 'Vitalício'];
  $recorrenciaAtual = $sub?->plan?->recorrencia;
  $recorrenciaPadrao = $recorrenciaAtual && $recorrencias_disponiveis->contains($recorrenciaAtual)
      ? $recorrenciaAtual
      : ($recorrencias_disponiveis->first() ?? 'mensal');
@endphp

<div class="card mb-6">
  <div class="card-header border-bottom"><h5 class="card-title mb-0">Assinatura atual</h5></div>
  <div class="card-body">
    @if ($sub && $sub->plan)
      <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
          <h4 class="mb-1">{{ $sub->plan->nome }}</h4>
          <p class="text-muted mb-2">
            {{ ucfirst($sub->plan->tipo) }} ·
            {{ $recorrenciaLabels[$sub->plan->recorrencia] ?? ucfirst($sub->plan->recorrencia) }} ·
            R$ {{ number_format((float) $sub->plan->preco, 2, ',', '.') }}
          </p>
          <span class="badge bg-label-{{ $statusColors[$sub->status] ?? 'secondary' }}">{{ ucfirst($sub->status) }}</span>
        </div>
        <div class="text-end">
          @if ($sub->started_at)
            <div><small class="text-muted">Início:</small> {{ $sub->started_at->format('d/m/Y') }}</div>
          @endif
          @if ($sub->ends_at)
            <div><small class="text-muted">Vencimento:</small> <strong>{{ $sub->ends_at->format('d/m/Y') }}</strong></div>
          @elseif ($sub->plan->recorrencia === 'vitalicio')
            <div><small class="text-muted">Vencimento:</small> <strong>Sem vencimento</strong></div>
          @endif
        </div>
      </div>

      @if ($sub->plan->descricao)
        <hr class="my-4">
        <p class="mb-0">{{ $sub->plan->descricao }}</p>
      @endif
    @else
      <div class="text-center py-5">
        <i class="icon-base ti tabler-file-off icon-48px text-muted mb-3"></i>
        <h5>Você ainda não tem um plano contratado</h5>
        <p class="text-muted mb-0">Os planos abaixo estão disponíveis para você.</p>
      </div>
    @endif
  </div>
</div>

@if ($planos_disponiveis->count())
<div class="card mb-6" id="planos-card">
  <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
      <h5 class="card-title mb-0">{{ $sub ? 'Fazer upgrade ou trocar de plano' : 'Planos disponíveis' }}</h5>
      <p class="text-muted mb-0 mt-1 small">Escolha um plano e contrate em segundos.</p>
    </div>
    @if ($recorrencias_disponiveis->count() > 1)
      <div class="btn-group" role="group" id="filter-recorrencia">
        @foreach ($recorrencias_disponiveis as $rec)
          <button type="button" class="btn btn-sm btn-outline-primary {{ $rec === $recorrenciaPadrao ? 'active' : '' }}" data-recorrencia="{{ $rec }}">
            {{ $recorrenciaLabels[$rec] ?? ucfirst($rec) }}
          </button>
        @endforeach
      </div>
    @endif
  </div>

  <div class="card-body">
    <div class="row g-4" id="planos-grid">
      @foreach ($planos_disponiveis as $p)
        @php
          $isAtual = $sub && $sub->plan_id === $p->id;
        @endphp
        <div class="col-md-4 plano-item" data-recorrencia="{{ $p->recorrencia }}" @if ($p->recorrencia !== $recorrenciaPadrao) style="display:none;" @endif>
          <div class="card border h-100 @if ($isAtual) border-primary @endif">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <h5 class="mb-0">{{ $p->nome }}</h5>
                @if ($isAtual)
                  <span class="badge bg-label-primary">Plano atual</span>
                @endif
              </div>
              <p class="text-muted small mb-3">{{ $recorrenciaLabels[$p->recorrencia] ?? ucfirst($p->recorrencia) }}</p>
              <h3 class="mb-3">R$ {{ number_format((float) $p->preco, 2, ',', '.') }}</h3>
              @if ($p->descricao)
                <p class="small text-muted mb-3">{{ $p->descricao }}</p>
              @endif
              @if ($isAtual)
                <button class="btn btn-label-secondary w-100" disabled>
                  <i class="icon-base ti tabler-check me-1"></i> Plano atual
                </button>
              @else
                <form method="POST" action="{{ route('checkout.contratar', $p) }}">
                  @csrf
                  <button type="submit" class="btn btn-primary w-100">
                    <i class="icon-base ti tabler-credit-card me-1"></i> {{ $sub ? 'Trocar para este plano' : 'Contratar agora' }}
                  </button>
                </form>
              @endif
            </div>
          </div>
        </div>
      @endforeach
    </div>
    <div id="planos-empty" class="text-center py-5 d-none">
      <i class="icon-base ti tabler-mood-empty icon-48px text-muted mb-3"></i>
      <p class="text-muted mb-0">Nenhum plano disponível nesta recorrência.</p>
    </div>
  </div>
</div>
@endif

@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const filter = document.getElementById('filter-recorrencia');
  if (! filter) return;

  const items = document.querySelectorAll('.plano-item');
  const empty = document.getElementById('planos-empty');
  const grid = document.getElementById('planos-grid');

  filter.addEventListener('click', function (e) {
    const btn = e.target.closest('button[data-recorrencia]');
    if (! btn) return;

    filter.querySelectorAll('button').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    const rec = btn.dataset.recorrencia;
    let visible = 0;
    items.forEach(item => {
      const show = item.dataset.recorrencia === rec;
      item.style.display = show ? '' : 'none';
      if (show) visible++;
    });

    empty.classList.toggle('d-none', visible > 0);
    grid.classList.toggle('d-none', visible === 0);
  });
});
</script>
@endsection
