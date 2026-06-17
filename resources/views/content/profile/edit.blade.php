@extends('layouts/layoutMaster')

@section('title', 'Meu Perfil')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/@form-validation/form-validation.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/@form-validation/popular.js',
'resources/assets/vendor/libs/@form-validation/bootstrap5.js',
'resources/assets/vendor/libs/@form-validation/auto-focus.js',
'resources/assets/vendor/libs/cleave-zen/cleave-zen.js'])
@endsection

@section('content')
<h4 class="mb-1">Meu Perfil</h4>
<p class="text-muted mb-6">Atualize seus dados pessoais e sua senha de acesso.</p>

@if (session('status'))
  <div class="alert alert-success alert-dismissible" role="alert">
    {{ session('status') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
@endif

<div class="row">
  <div class="col-md-12">
    <div class="card mb-6">
      <h5 class="card-header">Dados pessoais</h5>
      <div class="card-body">
        <div class="d-flex align-items-start align-items-sm-center gap-6 pb-4 border-bottom">
          @php
            $avatarUrl = $user->avatar ? \Illuminate\Support\Facades\Storage::url($user->avatar) : asset('assets/img/avatars/1.png');
          @endphp
          <img src="{{ $avatarUrl }}" alt="user-avatar" class="d-block w-px-100 h-px-100 rounded" id="uploadedAvatar">
          <div class="button-wrapper">
            <form action="{{ route('profile.avatar.upload') }}" method="POST" enctype="multipart/form-data" class="d-inline">
              @csrf
              <label for="avatar-upload" class="btn btn-primary me-3 mb-4" tabindex="0">
                <span class="d-none d-sm-block">Trocar foto</span>
                <i class="ti tabler-upload d-block d-sm-none"></i>
                <input type="file" id="avatar-upload" name="avatar" hidden accept="image/png, image/jpeg" onchange="this.form.submit()">
              </label>
              @error('avatar') <div class="text-danger small mb-2">{{ $message }}</div> @enderror
            </form>
            @if ($user->avatar)
              <form action="{{ route('profile.avatar.remove') }}" method="POST" class="d-inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-label-secondary account-image-reset mb-4">
                  <i class="ti tabler-refresh-dot d-block d-sm-none"></i>
                  <span class="d-none d-sm-block">Remover</span>
                </button>
              </form>
            @endif
            <p class="text-muted mb-0">JPG ou PNG, máximo 2MB.</p>
          </div>
        </div>

        <form action="{{ route('profile.update') }}" method="POST" class="mt-6">
          @csrf
          @method('PATCH')
          <div class="row">
            <div class="mb-6 col-md-6">
              <label for="name" class="form-label">Nome completo</label>
              <input class="form-control @error('name') is-invalid @enderror" type="text" id="name" name="name" value="{{ old('name', $user->name) }}" autofocus>
              @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="mb-6 col-md-6">
              <label for="email" class="form-label">E-mail</label>
              <input class="form-control @error('email') is-invalid @enderror" type="email" id="email" name="email" value="{{ old('email', $user->email) }}">
              @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="mb-6 col-md-6">
              <label for="phone" class="form-label">Telefone</label>
              <input class="form-control mask-phone @error('phone') is-invalid @enderror" type="text" id="phone" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="(11) 99999-9999">
              @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="mb-6 col-md-6">
              <label for="cpf_cnpj" class="form-label">CPF / CNPJ</label>
              <input class="form-control mask-cpf-cnpj @error('cpf_cnpj') is-invalid @enderror" type="text" id="cpf_cnpj" name="cpf_cnpj" value="{{ old('cpf_cnpj', $user->cpf_cnpj) }}" placeholder="000.000.000-00">
              @error('cpf_cnpj') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
          </div>
          <div class="mt-2">
            <button type="submit" class="btn btn-primary me-3">Salvar alterações</button>
          </div>
        </form>
      </div>
    </div>

    <div class="card mb-6">
      <h5 class="card-header">Trocar senha</h5>
      <div class="card-body">
        <form action="{{ route('profile.password') }}" method="POST">
          @csrf
          @method('PATCH')
          <div class="row">
            <div class="mb-6 col-md-12">
              <label for="current_password" class="form-label">Senha atual</label>
              <input class="form-control @error('current_password') is-invalid @enderror" type="password" id="current_password" name="current_password">
              @error('current_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="mb-6 col-md-6">
              <label for="password" class="form-label">Nova senha</label>
              <input class="form-control @error('password') is-invalid @enderror" type="password" id="password" name="password">
              @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="mb-6 col-md-6">
              <label for="password_confirmation" class="form-label">Confirmar nova senha</label>
              <input class="form-control" type="password" id="password_confirmation" name="password_confirmation">
            </div>
          </div>
          <div class="mt-2">
            <button type="submit" class="btn btn-primary">Alterar senha</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@section('page-script')
@include('_partials._masks-script')
@endsection
