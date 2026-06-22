@extends('layouts/layoutMaster')

@section('title', $conteudo->exists ? 'Editar conteúdo' : 'Novo conteúdo')

@section('content')
@php
  $editing = $conteudo->exists;
  $action = $editing ? url('/painel/conteudos-admin/' . $conteudo->id) : url('/painel/conteudos-admin');
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">{{ $editing ? 'Editar conteúdo' : 'Novo conteúdo' }}</h4>
  <a href="{{ route('admin.conteudos') }}" class="btn btn-label-secondary">
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
            <label class="form-label">Título *</label>
            <input type="text" class="form-control" name="titulo" required maxlength="160" placeholder="Ex.: Introdução ao método" value="{{ old('titulo', $conteudo->titulo) }}">
          </div>
          <div class="mb-4">
            <label class="form-label">Descrição</label>
            <textarea class="form-control" name="descricao" rows="3" maxlength="2000" placeholder="Resumo do conteúdo">{{ old('descricao', $conteudo->descricao) }}</textarea>
          </div>
          <div class="row">
            <div class="col-md-6 mb-4">
              <label class="form-label">Tipo *</label>
              <select name="tipo" class="form-select">
                <option value="video" @selected(old('tipo', $conteudo->tipo ?? 'video') === 'video')>Vídeo</option>
                <option value="pdf" @selected(old('tipo', $conteudo->tipo) === 'pdf')>PDF</option>
                <option value="texto" @selected(old('tipo', $conteudo->tipo) === 'texto')>Texto</option>
                <option value="link" @selected(old('tipo', $conteudo->tipo) === 'link')>Link</option>
              </select>
            </div>
            <div class="col-md-6 mb-4">
              <label class="form-label">Categoria</label>
              <input type="text" class="form-control" name="categoria" maxlength="80" placeholder="Ex.: Onboarding" value="{{ old('categoria', $conteudo->categoria) }}">
            </div>
          </div>
          <div class="mb-0">
            <label class="form-label">URL *</label>
            <input type="text" class="form-control" name="url" required maxlength="500" placeholder="https://..." value="{{ old('url', $conteudo->url) }}">
            <small class="text-muted">Link do vídeo, PDF, texto ou destino externo.</small>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card mb-4">
        <div class="card-header border-bottom"><h5 class="card-title mb-0">Exibição</h5></div>
        <div class="card-body">
          <div class="mb-4">
            <label class="form-label">Ordem</label>
            <input type="number" min="0" class="form-control" name="ordem" value="{{ old('ordem', $conteudo->ordem ?? 0) }}">
            <small class="text-muted">Menor número aparece primeiro.</small>
          </div>
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="ativo" name="ativo" value="1" @checked(old('ativo', $editing ? $conteudo->ativo : true))>
            <label class="form-check-label" for="ativo">Conteúdo ativo</label>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="d-flex justify-content-end gap-2">
    <a href="{{ route('admin.conteudos') }}" class="btn btn-label-secondary">Cancelar</a>
    <button type="submit" class="btn btn-primary"><i class="icon-base ti tabler-device-floppy me-1"></i> {{ $editing ? 'Salvar alterações' : 'Cadastrar conteúdo' }}</button>
  </div>
</form>
@endsection
