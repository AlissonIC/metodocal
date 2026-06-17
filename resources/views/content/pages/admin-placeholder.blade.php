@extends('layouts/layoutMaster')

@section('title', $titulo)

@section('content')
<div class="card">
  <div class="card-body text-center py-5">
    <i class="icon-base ti tabler-tools icon-48px text-primary mb-3"></i>
    <h4>{{ $titulo }}</h4>
    <p class="text-muted mb-0">Esta tela será implementada na <strong>Fase 2</strong> do projeto.</p>
  </div>
</div>
@endsection
