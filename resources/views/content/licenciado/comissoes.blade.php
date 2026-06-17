@extends('layouts/layoutMaster')

@section('title', 'Minhas Comissões')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'])
@endsection

@section('content')
<div class="row g-6 mb-6">
  <div class="col-md-4">
    <div class="card"><div class="card-body">
      <span class="text-heading">Total recebido</span>
      <h4 class="my-1">R$ {{ number_format($total_recebido, 2, ',', '.') }}</h4>
      <small class="text-success">Comissões pagas</small>
    </div></div>
  </div>
  <div class="col-md-4">
    <div class="card"><div class="card-body">
      <span class="text-heading">Pendente</span>
      <h4 class="my-1">R$ {{ number_format($total_pendente, 2, ',', '.') }}</h4>
      <small class="text-warning">Aguardando pagamento</small>
    </div></div>
  </div>
  <div class="col-md-4">
    <div class="card"><div class="card-body">
      <span class="text-heading">Lançamentos</span>
      <h4 class="my-1">{{ $qtd_total }}</h4>
      <small class="text-muted">Total no histórico</small>
    </div></div>
  </div>
</div>

<div class="card">
  <h5 class="card-header">Histórico</h5>
  <div class="card-datatable">
    <table class="datatables-comissoes table border-top">
      <thead><tr><th>ID</th><th>Descrição</th><th>Cliente</th><th>Data</th><th>Valor</th><th>Status</th></tr></thead>
    </table>
  </div>
</div>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  new DataTable('.datatables-comissoes', {
    processing: true, serverSide: true,
    ajax: { url: "{{ url('/painel/comissoes/datatable') }}" },
    columns: [
      { data: 'id' }, { data: 'descricao' }, { data: 'cliente_nome' },
      { data: 'data_formatada' }, { data: 'valor_formatado' }, { data: 'status_badge' }
    ],
    order: [[3, 'desc']],
    language: { processing: 'Carregando...', search: 'Buscar:', lengthMenu: '_MENU_', info: '_START_-_END_ de _TOTAL_', infoEmpty: '0', zeroRecords: 'Nenhuma comissão', emptyTable: 'Nenhuma comissão', paginate: { first: '«', previous: '‹', next: '›', last: '»' } }
  });
});
</script>
@endsection
