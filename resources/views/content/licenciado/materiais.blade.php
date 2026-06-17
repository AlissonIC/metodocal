@extends('layouts/layoutMaster')

@section('title', 'Materiais para Revenda')

@section('content')
@if ($porCategoria->isEmpty())
  <div class="card"><div class="card-body text-center py-5 text-muted">
    <i class="icon-base ti tabler-files-off icon-48px mb-3"></i>
    <p class="mb-0">Nenhum material disponível ainda.</p>
  </div></div>
@endif

@foreach ($porCategoria as $categoria => $itens)
<div class="card mb-6">
  <h5 class="card-header">{{ $categoria }}</h5>
  <div class="card-body">
    <div class="row g-4">
      @foreach ($itens as $m)
        <div class="col-md-4">
          <div class="card border h-100">
            <div class="card-body">
              <div class="avatar mb-3"><span class="avatar-initial rounded bg-label-primary"><i class="ti tabler-file"></i></span></div>
              <h6 class="mb-1">{{ $m->titulo }}</h6>
              @if ($m->descricao)
                <p class="text-muted small mb-3">{{ $m->descricao }}</p>
              @endif
              <small class="text-muted d-block mb-3">{{ $m->tamanho_bytes ? round($m->tamanho_bytes / 1024, 1) . ' KB' : '' }}</small>
              <a href="{{ route('licenciado.materiais.download', $m) }}" class="btn btn-sm btn-primary w-100">
                <i class="ti tabler-download me-1"></i> Baixar
              </a>
            </div>
          </div>
        </div>
      @endforeach
    </div>
  </div>
</div>
@endforeach
@endsection
