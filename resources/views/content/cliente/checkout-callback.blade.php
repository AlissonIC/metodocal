@extends('layouts/layoutMaster')

@section('title', $titulo)

@section('content')
<div class="card">
  <div class="card-body text-center py-5">
    <div class="avatar avatar-xl mx-auto mb-4">
      <span class="avatar-initial rounded-circle bg-label-{{ $color }}"><i class="ti tabler-{{ $icon }} icon-26px"></i></span>
    </div>
    <h4 class="mb-2">{{ $titulo }}</h4>
    <p class="text-muted mb-4">{{ $mensagem }}</p>
    <a href="{{ route('subscription.view') }}" class="btn btn-primary">Minha Assinatura</a>
    <a href="{{ route('dashboard') }}" class="btn btn-label-secondary">Voltar ao Painel</a>
  </div>
</div>
@endsection
