@extends('layouts/layoutMaster')

@section('title', 'Trilha de Conteúdos')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('content')
<div class="card mb-6">
  <div class="card-body">
    <div class="row align-items-center">
      <div class="col-md-9">
        <h5 class="mb-1">Sua trilha</h5>
        <p class="text-muted mb-2">{{ $totalConcluidos }} de {{ $totalConteudos }} conteúdos concluídos</p>
        <div class="progress" style="height:8px">
          <div class="progress-bar bg-success" style="width: {{ $totalConteudos > 0 ? round($totalConcluidos / $totalConteudos * 100) : 0 }}%"></div>
        </div>
      </div>
      <div class="col-md-3 text-md-end">
        <h2 class="mb-0">{{ $totalConteudos > 0 ? round($totalConcluidos / $totalConteudos * 100) : 0 }}%</h2>
        <small class="text-muted">Progresso geral</small>
      </div>
    </div>
  </div>
</div>

@foreach ($porCategoria as $categoria => $itens)
<div class="card mb-6">
  <h5 class="card-header">{{ $categoria ?? 'Outros' }}</h5>
  <div class="list-group list-group-flush">
    @foreach ($itens as $c)
      @php $isDone = $concluidos->has($c->id); @endphp
      <div class="list-group-item d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
          <div class="avatar me-3">
            <span class="avatar-initial rounded bg-label-{{ $isDone ? 'success' : 'primary' }}">
              @switch($c->tipo)
                @case('video') <i class="ti tabler-player-play"></i> @break
                @case('pdf') <i class="ti tabler-file-text"></i> @break
                @case('link') <i class="ti tabler-link"></i> @break
                @default <i class="ti tabler-book"></i>
              @endswitch
            </span>
          </div>
          <div>
            <h6 class="mb-0 {{ $isDone ? 'text-decoration-line-through text-muted' : '' }}">{{ $c->titulo }}</h6>
            <small class="text-muted">{{ ucfirst($c->tipo) }}</small>
          </div>
        </div>
        <div class="d-flex gap-2 align-items-center">
          <a href="{{ $c->url }}" target="_blank" class="btn btn-sm btn-label-primary">Abrir</a>
          <button class="btn btn-sm {{ $isDone ? 'btn-label-success' : 'btn-outline-success' }} toggle-complete" data-id="{{ $c->id }}">
            <i class="ti tabler-circle-check me-1"></i>
            <span class="label">{{ $isDone ? 'Concluído' : 'Marcar concluído' }}</span>
          </button>
        </div>
      </div>
    @endforeach
  </div>
</div>
@endforeach

@if ($porCategoria->isEmpty())
  <div class="card"><div class="card-body text-center py-5 text-muted"><i class="icon-base ti tabler-book icon-48px mb-3"></i><p class="mb-0">Nenhum conteúdo disponível ainda.</p></div></div>
@endif
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const csrf = document.querySelector('meta[name="csrf-token"]').content;
  const baseUrl = "{{ url('/painel/conteudos') }}";
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.toggle-complete');
    if (!btn) return;
    fetch(`${baseUrl}/${btn.dataset.id}/toggle`, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' }
    })
      .then(r => r.json())
      .then(b => {
        if (b.status !== 'success') throw new Error(b.message);
        location.reload();
      })
      .catch(err => Swal.fire({ icon: 'error', title: 'Erro', text: err.message, customClass: { confirmButton: 'btn btn-danger' }, buttonsStyling: false }));
  });
});
</script>
@endsection
