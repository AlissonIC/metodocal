@php
$customizerHidden = 'customizer-hide';
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Criar conta')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/@form-validation/form-validation.scss'])
@endsection

@section('page-style')
@vite(['resources/assets/vendor/scss/pages/page-auth.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/@form-validation/popular.js',
'resources/assets/vendor/libs/@form-validation/bootstrap5.js',
'resources/assets/vendor/libs/@form-validation/auto-focus.js'])
@endsection

@section('page-script')
@vite(['resources/assets/js/pages-auth.js'])
@endsection

@section('content')
<div class="container-xxl">
  <div class="authentication-wrapper authentication-basic container-p-y">
    <div class="authentication-inner py-6">
      <div class="card">
        <div class="card-body">
          <div class="app-brand justify-content-center mb-6">
            <a href="{{ url('/') }}" class="app-brand-link">
              <span class="app-brand-logo demo">@include('_partials.macros', ['width' => '80', 'height' => '80'])</span>
            </a>
          </div>
          <h4 class="mb-1">Comece sua jornada</h4>
          <p class="mb-6">Crie sua conta e contrate o plano ideal.</p>

          <form id="formAuthentication" class="mb-6" action="{{ route('register.store') }}" method="POST">
            @csrf
            <div class="mb-6 form-control-validation">
              <label for="name" class="form-label">Nome completo</label>
              <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" placeholder="Seu nome" autofocus />
              @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="mb-6 form-control-validation">
              <label for="email" class="form-label">E-mail</label>
              <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" placeholder="seu@email.com" />
              @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <input type="hidden" name="tipo" value="mentorado">
            <div class="mb-6 form-password-toggle form-control-validation">
              <label class="form-label" for="password">Senha</label>
              <div class="input-group input-group-merge">
                <input type="password" id="password" class="form-control @error('password') is-invalid @enderror" name="password" placeholder="············" />
                <span class="input-group-text cursor-pointer"><i class="icon-base ti tabler-eye-off"></i></span>
                @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <small class="text-muted">Mínimo 8 caracteres, com letras e números.</small>
            </div>
            <div class="my-8 form-control-validation">
              <div class="form-check mb-0 ms-2">
                <input class="form-check-input @error('terms') is-invalid @enderror" type="checkbox" id="terms" name="terms" value="1" {{ old('terms') ? 'checked' : '' }} />
                <label class="form-check-label" for="terms">
                  Aceito os <a href="javascript:void(0);">termos de uso e política de privacidade</a>
                </label>
                @error('terms') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
              </div>
            </div>
            <button class="btn btn-primary d-grid w-100">Criar conta</button>
          </form>

          <p class="text-center">
            <span>Já tem uma conta?</span>
            <a href="{{ route('login') }}">
              <span>Entrar</span>
            </a>
          </p>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
