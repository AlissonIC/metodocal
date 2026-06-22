@extends('layouts/layoutMaster')

@section('title', $material->exists ? 'Editar material' : 'Novo material')

@section('content')
@php
  $editing = $material->exists;
  $action = $editing ? url('/painel/materiais-admin/' . $material->id) : url('/painel/materiais-admin');
  $arquivoAtual = $material->arquivo ? basename($material->arquivo) : null;
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">{{ $editing ? 'Editar material' : 'Novo material' }}</h4>
  <a href="{{ route('admin.materiais') }}" class="btn btn-label-secondary">
    <i class="icon-base ti tabler-arrow-left me-1"></i> Voltar
  </a>
</div>

@if ($errors->any())
  <div class="alert alert-danger">
    <ul class="mb-0">@foreach ($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
  </div>
@endif

<form method="POST" action="{{ $action }}" enctype="multipart/form-data">
  @csrf
  @if ($editing) @method('PATCH') @endif

  <div class="row g-4">
    <div class="col-lg-8">
      <div class="card mb-4">
        <div class="card-header border-bottom"><h5 class="card-title mb-0">Informações</h5></div>
        <div class="card-body">
          <div class="mb-4">
            <label class="form-label">Título *</label>
            <input type="text" class="form-control" name="titulo" required maxlength="160" placeholder="Título do material" value="{{ old('titulo', $material->titulo) }}">
          </div>
          <div class="mb-4">
            <label class="form-label">Categoria</label>
            <input type="text" class="form-control" name="categoria" maxlength="80" placeholder="Ex.: Apresentações, Manuais" value="{{ old('categoria', $material->categoria) }}">
          </div>
          <div class="mb-0">
            <label class="form-label">Descrição</label>
            <textarea class="form-control" name="descricao" rows="4" maxlength="2000">{{ old('descricao', $material->descricao) }}</textarea>
          </div>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header border-bottom"><h5 class="card-title mb-0">Arquivo</h5></div>
        <div class="card-body">
          @if ($editing && $arquivoAtual)
            <div class="mb-4">
              <label class="form-label d-block">Arquivo atual</label>
              <a href="{{ Storage::disk('public')->url($material->arquivo) }}" target="_blank" class="d-inline-flex align-items-center gap-2">
                <i class="icon-base ti tabler-file icon-22px"></i>
                <span>{{ $arquivoAtual }}</span>
              </a>
              @if ($material->tamanho_bytes)
                <small class="text-muted d-block mt-1">{{ round($material->tamanho_bytes / 1024, 1) }} KB</small>
              @endif
            </div>
          @endif

          <div class="mb-0">
            <label class="form-label">{{ $editing ? 'Substituir arquivo' : 'Arquivo *' }}</label>
            <input type="file" class="form-control" name="arquivo" @if (! $editing) required @endif>
            <small class="text-muted">Tamanho máximo: 20 MB.{{ $editing ? ' Deixe em branco para manter o arquivo atual.' : '' }}</small>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card">
        <div class="card-header border-bottom"><h5 class="card-title mb-0">Status</h5></div>
        <div class="card-body">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="ativo" name="ativo" value="1" @checked(old('ativo', $editing ? $material->ativo : true))>
            <label class="form-check-label" for="ativo">Material ativo</label>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="d-flex justify-content-end gap-2">
    <a href="{{ route('admin.materiais') }}" class="btn btn-label-secondary">Cancelar</a>
    <button type="submit" class="btn btn-primary"><i class="icon-base ti tabler-device-floppy me-1"></i> {{ $editing ? 'Salvar alterações' : 'Cadastrar material' }}</button>
  </div>
</form>
@endsection
