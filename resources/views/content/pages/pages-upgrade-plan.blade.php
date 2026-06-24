@php
$configData = Helper::appClasses();
$isAdmin = auth()->user()?->hasRole('admin') ?? false;
@endphp

@extends('layouts/layoutMaster')

@section('title', $isAdmin ? 'Área de cliente' : 'Upgrade necessário')

@section('page-style')
@vite(['resources/assets/vendor/scss/pages/page-misc.scss'])
@endsection

@section('content')
<div class="container-xxl container-p-y">
  <div class="misc-wrapper text-center">
    <div class="mb-4">
      <i class="icon-base ti {{ $isAdmin ? 'tabler-lock' : 'tabler-rocket' }}"
         style="font-size: 4rem; color: var(--bs-primary);"></i>
    </div>

    @if ($isAdmin)
      <h4 class="mb-2">Esta é uma área exclusiva de cliente</h4>
      <p class="mb-6 text-muted" style="max-width: 540px; margin: 0 auto;">
        <strong>{{ $featureName }}</strong> é uma experiência destinada a mentorados e licenciados. Como administrador, você gerencia esses recursos pelas telas específicas de admin (Sessões, Conteúdos, Materiais, Comissões), não pela visão do cliente.
      </p>

      <div class="d-flex gap-2 justify-content-center flex-wrap mb-8">
        <a href="{{ route('dashboard') }}" class="btn btn-primary">
          <i class="icon-base ti tabler-arrow-left me-1"></i> Voltar ao painel
        </a>
      </div>
    @else
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
    @endif

    <div class="mt-6">
      <img src="{{ asset('assets/img/illustrations/page-misc-error.png') }}" alt="upgrade" width="180" class="img-fluid opacity-75" />
    </div>
  </div>
</div>
@endsection
