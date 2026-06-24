@php
$customizerHidden = 'customizer-hide';
$configData = Helper::appClasses();
$statusCode = $statusCode ?? 404;
$pageTitle = $pageTitle ?? 'Página não encontrada';
$pageMessage = $pageMessage ?? 'A página que você procura não existe ou foi movida.';
@endphp

@extends('layouts/blankLayout')

@section('title', $pageTitle)

@section('page-style')
@vite(['resources/assets/vendor/scss/pages/page-misc.scss'])
@endsection

@section('content')
<div class="container-xxl container-p-y">
  <div class="misc-wrapper">
    <h1 class="mb-2 mx-2" style="line-height: 6rem;font-size: 6rem;">{{ $statusCode }}</h1>
    <h4 class="mb-2 mx-2">{{ $pageTitle }}</h4>
    <p class="mb-6 mx-2">{{ $pageMessage }}</p>
    <a href="{{ url('/') }}" class="btn btn-primary mb-10">Voltar para o início</a>
    <div class="mt-4">
      <img src="{{ asset('assets/img/illustrations/page-misc-error.png') }}" alt="error" width="225" class="img-fluid" />
    </div>
  </div>
</div>
<div class="container-fluid misc-bg-wrapper">
  <img src="{{ asset('assets/img/illustrations/bg-shape-image-' . $configData['theme'] . '.png') }}" height="355" alt="background" data-app-light-img="illustrations/bg-shape-image-light.png" data-app-dark-img="illustrations/bg-shape-image-dark.png" />
</div>
@endsection