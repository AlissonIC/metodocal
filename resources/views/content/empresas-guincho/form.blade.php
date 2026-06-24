@extends('layouts/layoutMaster')

@section('title', $empresa->exists ? 'Editar empresa' : 'Nova empresa')

@section('vendor-script')
@vite(['resources/assets/vendor/libs/cleave-zen/cleave-zen.js'])
@endsection

@section('content')
@php
  $editing = $empresa->exists;
  $action = $editing ? route('guincho.update', $empresa) : route('guincho.store');
  $logoUrl = $empresa->logo ? asset('storage/' . $empresa->logo) : null;
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">{{ $editing ? 'Editar empresa de guincho' : 'Nova empresa de guincho' }}</h4>
  <a href="{{ route('guincho.index') }}" class="btn btn-label-secondary">
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
    <div class="col-lg-4">
      <div class="card mb-4">
        <div class="card-header border-bottom"><h5 class="card-title mb-0">Logo</h5></div>
        <div class="card-body text-center">
          <img id="logo_preview" src="{{ $logoUrl ?? '' }}" alt="" class="rounded mb-3 {{ $logoUrl ? '' : 'd-none' }}" style="height:120px;max-width:240px;object-fit:contain;background:#f5f5f9;padding:6px;">
          @unless ($logoUrl)
            <div class="rounded mb-3 d-flex align-items-center justify-content-center mx-auto" style="height:120px;max-width:240px;background:#f5f5f9;" id="logo_placeholder">
              <i class="icon-base ti tabler-photo icon-48px text-muted"></i>
            </div>
          @endunless
          <div>
            <label for="logo" class="btn btn-label-primary btn-sm">
              <i class="icon-base ti tabler-upload me-1"></i> {{ $editing ? 'Trocar logo' : 'Selecionar logo' }}
              <input type="file" class="d-none" name="logo" id="logo" accept="image/*">
            </label>
            <small class="d-block text-muted mt-2">PNG/JPG/SVG até 2 MB</small>
            @if ($editing)
              <small class="d-block text-muted">Deixe em branco para manter a logo atual.</small>
            @endif
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header border-bottom"><h5 class="card-title mb-0">Status</h5></div>
        <div class="card-body">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="ativo" name="ativo" value="1" @checked(old('ativo', $editing ? $empresa->ativo : true))>
            <label class="form-check-label" for="ativo">Empresa ativa (aparece na busca do cliente)</label>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-8">
      <div class="card mb-4">
        <div class="card-header border-bottom"><h5 class="card-title mb-0">Identificação</h5></div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-7 mb-4">
              <label class="form-label">Nome *</label>
              <input type="text" class="form-control" name="nome" required maxlength="160" value="{{ old('nome', $empresa->nome) }}">
            </div>
            <div class="col-md-5 mb-4">
              <label class="form-label">CNPJ</label>
              <input type="text" class="form-control mask-cnpj" name="cnpj" maxlength="20" placeholder="00.000.000/0000-00" value="{{ old('cnpj', $empresa->cnpj) }}">
            </div>
          </div>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header border-bottom"><h5 class="card-title mb-0">Contatos</h5></div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-6 mb-4">
              <label class="form-label">Telefone</label>
              <input type="text" class="form-control mask-phone" name="telefone" maxlength="40" placeholder="(00) 0000-0000" value="{{ old('telefone', $empresa->telefone) }}">
            </div>
            <div class="col-md-6 mb-4">
              <label class="form-label">WhatsApp</label>
              <input type="text" class="form-control mask-phone" name="whatsapp" maxlength="40" placeholder="(00) 00000-0000" value="{{ old('whatsapp', $empresa->whatsapp) }}">
            </div>
            <div class="col-md-7 mb-4">
              <label class="form-label">E-mail</label>
              <input type="email" class="form-control" name="email" maxlength="160" value="{{ old('email', $empresa->email) }}">
            </div>
            <div class="col-md-5 mb-4">
              <label class="form-label">Site</label>
              <input type="text" class="form-control" name="site" maxlength="200" placeholder="https://..." value="{{ old('site', $empresa->site) }}">
            </div>
          </div>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header border-bottom"><h5 class="card-title mb-0">Endereço da sede</h5></div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-4 mb-4">
              <label class="form-label">CEP</label>
              <input type="text" class="form-control mask-cep" name="cep" maxlength="10" placeholder="00000-000" value="{{ old('cep', $empresa->cep) }}">
            </div>
            <div class="col-md-8 mb-4">
              <label class="form-label">Logradouro</label>
              <input type="text" class="form-control" name="endereco" maxlength="255" placeholder="Rua, Avenida..." value="{{ old('endereco', $empresa->endereco) }}">
            </div>
            <div class="col-md-3 mb-4">
              <label class="form-label">Número</label>
              <input type="text" class="form-control" name="numero" maxlength="20" value="{{ old('numero', $empresa->numero) }}">
            </div>
            <div class="col-md-9 mb-4">
              <label class="form-label">Complemento</label>
              <input type="text" class="form-control" name="complemento" maxlength="80" value="{{ old('complemento', $empresa->complemento) }}">
            </div>
            <div class="col-md-6 mb-4">
              <label class="form-label">Bairro</label>
              <input type="text" class="form-control" name="bairro" maxlength="100" value="{{ old('bairro', $empresa->bairro) }}">
            </div>
            <div class="col-md-6 mb-4">
              <label class="form-label">Cidade</label>
              <input type="text" class="form-control" name="cidade" maxlength="120" value="{{ old('cidade', $empresa->cidade) }}">
            </div>
            <div class="col-md-12 mb-4">
              <label class="form-label">Estado</label>
              <select class="form-select" name="estado">
                <option value="">Selecione...</option>
                @foreach ($estados as $uf => $nome)
                  <option value="{{ $uf }}" @selected(old('estado', $empresa->estado) === $uf)>{{ $nome }}</option>
                @endforeach
              </select>
            </div>
          </div>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header border-bottom"><h5 class="card-title mb-0">Cidades atendidas</h5></div>
        <div class="card-body">
          @php
            $cidadesAtuais = old('cidades_atendidas') ?? ($empresa->cidades_atendidas ? implode(', ', $empresa->cidades_atendidas) : '');
          @endphp
          <input type="text" class="form-control" name="cidades_atendidas" placeholder="São Paulo, Guarulhos, Osasco" value="{{ $cidadesAtuais }}">
          <small class="text-muted">Separe as cidades por vírgula.</small>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header border-bottom"><h5 class="card-title mb-0">Descrição</h5></div>
        <div class="card-body">
          <textarea class="form-control" name="descricao" rows="4" maxlength="2000" placeholder="Informações adicionais sobre a empresa...">{{ old('descricao', $empresa->descricao) }}</textarea>
        </div>
      </div>
    </div>
  </div>

  <div class="d-flex justify-content-end gap-2">
    <a href="{{ route('guincho.index') }}" class="btn btn-label-secondary">Cancelar</a>
    <button type="submit" class="btn btn-primary"><i class="icon-base ti tabler-device-floppy me-1"></i> {{ $editing ? 'Salvar alterações' : 'Cadastrar empresa' }}</button>
  </div>
</form>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const input = document.getElementById('logo');
  const preview = document.getElementById('logo_preview');
  const placeholder = document.getElementById('logo_placeholder');
  input.addEventListener('change', function (e) {
    const f = e.target.files[0];
    if (! f) return;
    preview.src = URL.createObjectURL(f);
    preview.classList.remove('d-none');
    if (placeholder) placeholder.classList.add('d-none');
  });
});
</script>
@include('_partials._masks-script')
@endsection
