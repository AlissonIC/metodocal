@php
$configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Upgrade necessário')

@section('page-style')
@vite(['resources/assets/vendor/scss/pages/page-misc.scss'])
@endsection

@section('content')
<div class="container-xxl container-p-y">
  <div class="misc-wrapper text-center">
    <div class="mb-4">
      <i class="icon-base ti tabler-rocket" style="font-size: 4rem; color: var(--bs-primary);"></i>
    </div>

    <h4 class="mb-2">{{ $featureName }} faz parte de planos superiores</h4>
    <p class="mb-6 text-muted" style="max-width: 520px; margin: 0 auto;">
      Esse módulo não está incluído no seu plano atual. Faça upgrade para liberar o acesso e aproveitar todos os recursos disponíveis na plataforma.
    </p>

    <div class="d-flex gap-2 justify-content-center flex-wrap mb-8">
      <a href="{{ route('subscription.view') }}" class="btn btn-primary">
        <i class="icon-base ti tabler-arrow-up-circle me-1"></i> Ver meus planos
      </a>
      <a href="{{ route('dashboard') }}" class="btn btn-label-secondary">
        Voltar ao início
      </a>
    </div>

    <div class="mt-6">
      <img src="{{ asset('assets/img/illustrations/page-misc-error.png') }}" alt="upgrade" width="180" class="img-fluid opacity-75" />
    </div>
  </div>
</div>
@endsection
