@extends('layouts/layoutMaster')

@section('title', $sessao->exists ? 'Editar sessão' : 'Nova sessão')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('content')
@php
  $editing = $sessao->exists;
  $action = $editing ? url('/painel/sessoes/' . $sessao->id) : url('/painel/sessoes');
  $scheduledValue = old('scheduled_at', $editing && $sessao->scheduled_at ? $sessao->scheduled_at->format('Y-m-d\TH:i') : '');
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">{{ $editing ? 'Editar sessão' : 'Nova sessão' }}</h4>
  <a href="{{ route('admin.sessoes') }}" class="btn btn-label-secondary">
    <i class="icon-base ti tabler-arrow-left me-1"></i> Voltar
  </a>
</div>

@if ($errors->any())
  <div class="alert alert-danger">
    <ul class="mb-0">@foreach ($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
  </div>
@endif

<form method="POST" action="{{ $action }}">
  @csrf
  @if ($editing) @method('PATCH') @endif

  <div class="row g-4">
    <div class="col-lg-8">
      <div class="card mb-4">
        <div class="card-header border-bottom"><h5 class="card-title mb-0">Informações</h5></div>
        <div class="card-body">
          <div class="mb-4">
            <label class="form-label">Mentorado *</label>
            <select name="user_id" id="user_id" class="select2 form-select" required>
              <option value="">Selecione...</option>
              @foreach ($mentorados as $m)
                <option value="{{ $m->id }}" @selected((int) old('user_id', $sessao->user_id) === (int) $m->id)>{{ $m->name }}</option>
              @endforeach
            </select>
          </div>

          <div class="mb-4">
            <label class="form-label">Título *</label>
            <input type="text" class="form-control" name="titulo" required maxlength="120" placeholder="Sessão de alinhamento" value="{{ old('titulo', $sessao->titulo) }}">
          </div>

          <div class="mb-4">
            <label class="form-label">Descrição</label>
            <textarea class="form-control" name="descricao" rows="3" maxlength="2000">{{ old('descricao', $sessao->descricao) }}</textarea>
          </div>

          <div class="row">
            <div class="col-md-7 mb-4">
              <label class="form-label">Data e hora *</label>
              <input type="datetime-local" class="form-control" name="scheduled_at" required value="{{ $scheduledValue }}">
            </div>
            <div class="col-md-5 mb-4">
              <label class="form-label">Duração (min) *</label>
              <input type="number" min="15" max="480" class="form-control" name="duracao_minutos" required value="{{ old('duracao_minutos', $sessao->duracao_minutos ?? 60) }}">
            </div>
          </div>

          <div class="mb-0">
            <label class="form-label">Link da reunião</label>
            <input type="url" class="form-control" name="link_reuniao" maxlength="300" placeholder="https://meet..." value="{{ old('link_reuniao', $sessao->link_reuniao) }}">
          </div>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header border-bottom"><h5 class="card-title mb-0">Notas internas</h5></div>
        <div class="card-body">
          <textarea class="form-control" name="notas" rows="4" maxlength="2000" placeholder="Anotações privadas sobre a sessão">{{ old('notas', $sessao->notas) }}</textarea>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card">
        <div class="card-header border-bottom"><h5 class="card-title mb-0">Status</h5></div>
        <div class="card-body">
          <label class="form-label">Situação *</label>
          <select name="status" class="form-select">
            @php $currentStatus = old('status', $sessao->status ?? 'agendada'); @endphp
            <option value="agendada" @selected($currentStatus === 'agendada')>Agendada</option>
            <option value="concluida" @selected($currentStatus === 'concluida')>Concluída</option>
            <option value="cancelada" @selected($currentStatus === 'cancelada')>Cancelada</option>
          </select>
        </div>
      </div>
    </div>
  </div>

  <div class="d-flex justify-content-end gap-2">
    <a href="{{ route('admin.sessoes') }}" class="btn btn-label-secondary">Cancelar</a>
    <button type="submit" class="btn btn-primary"><i class="icon-base ti tabler-device-floppy me-1"></i> {{ $editing ? 'Salvar alterações' : 'Agendar sessão' }}</button>
  </div>
</form>
@endsection

@section('page-script')<script>
document.addEventListener('DOMContentLoaded', function () {
  const $user = $('#user_id');
  $user.wrap('<div class="position-relative"></div>').select2({
    placeholder: 'Selecione o mentorado',
    dropdownParent: $user.parent(),
  });
});
</script>
@endsection
