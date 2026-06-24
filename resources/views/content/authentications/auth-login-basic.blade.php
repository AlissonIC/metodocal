@php
$customizerHidden = 'customizer-hide';
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Entrar')

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
          <h4 class="mb-1">Bem-vindo ao {{ config('variables.templateName') }}!</h4>
          <p class="mb-6">Entre na sua conta para acessar o sistema.</p>

          @if (session('status'))
            <div class="alert alert-success" role="alert">{{ session('status') }}</div>
          @endif

          <form id="formAuthentication" class="mb-4" action="{{ route('login.attempt') }}" method="POST">
            @csrf
            <div class="mb-6 form-control-validation">
              <label for="email" class="form-label">E-mail</label>
              <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" placeholder="seu@email.com" autofocus />
              @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="mb-6 form-password-toggle form-control-validation">
              <label class="form-label" for="password">Senha</label>
              <div class="input-group input-group-merge">
                <input type="password" id="password" class="form-control @error('password') is-invalid @enderror" name="password" placeholder="············" aria-describedby="password" />
                <span class="input-group-text cursor-pointer"><i class="icon-base ti tabler-eye-off"></i></span>
                @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
            </div>
            <div class="my-8">
              <div class="d-flex justify-content-between">
                <div class="form-check mb-0 ms-2">
                  <input class="form-check-input" type="checkbox" id="remember-me" name="remember" value="1" />
                  <label class="form-check-label" for="remember-me">Lembrar-me</label>
                </div>
                <a href="{{ route('password.request') }}">
                  <p class="mb-0">Esqueci a senha</p>
                </a>
              </div>
            </div>
            <div class="mb-6">
              <button class="btn btn-primary d-grid w-100" type="submit">Entrar</button>
            </div>
          </form>

          <p class="text-center">
            <span>Ainda não tem conta?</span>
            <a href="{{ route('register') }}">
              <span>Criar uma conta</span>
            </a>
          </p>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
