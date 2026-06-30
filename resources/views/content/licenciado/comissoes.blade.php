@extends('layouts/layoutMaster')

@section('title', 'Minhas Comissões')

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
  'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
])
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
  'resources/assets/vendor/libs/flatpickr/flatpickr.js',
])
@endsection

@section('page-style')
<style>
  @media (max-width: 575.98px) {
    .dt-responsive td, .dt-responsive th { font-size: .82rem; }
    .dt-responsive .badge { font-size: .68rem; }
  }
  table.dataTable.dtr-inline.collapsed > tbody > tr > td.dtr-control:before {
    background-color: var(--bs-primary);
    border: 0;
  }
</style>
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

  <div class="card-body filtros-bar">
    <div class="row g-2 g-md-3">
      <div class="col-6 col-md-3">
        <label class="form-label small mb-1">Tipo</label>
        <select id="filtro-tipo" class="form-select form-select-sm">
          <option value="">Todos</option>
          <option value="a_receber">A receber</option>
          <option value="a_pagar">A pagar</option>
        </select>
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label small mb-1">Status</label>
        <select id="filtro-status" class="form-select form-select-sm">
          <option value="">Todos</option>
          <option value="pendente">Pendente</option>
          <option value="paga">Paga</option>
          <option value="cancelada">Cancelada</option>
        </select>
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label small mb-1">Data de</label>
        <input type="text" id="filtro-de" class="form-control form-control-sm flatpickr-filtro" placeholder="dd/mm/aaaa">
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label small mb-1">Data até</label>
        <input type="text" id="filtro-ate" class="form-control form-control-sm flatpickr-filtro" placeholder="dd/mm/aaaa">
      </div>
      <div class="col-12 col-md-2 d-flex align-items-end">
        <button id="btn-limpar-filtros" class="btn btn-label-secondary btn-sm w-100" title="Limpar filtros">
          <i class="icon-base ti tabler-eraser"></i>
          <span class="ms-1">Limpar</span>
        </button>
      </div>
    </div>
  </div>

  <div class="card-datatable">
    <table class="datatables-comissoes table border-top dt-responsive" style="width:100%">
      <thead>
        <tr>
          <th>Descrição</th>
          <th>Tipo</th>
          <th>Cliente</th>
          <th>Data</th>
          <th>Valor</th>
          <th>Status</th>
        </tr>
      </thead>
    </table>
  </div>
</div>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  flatpickr('.flatpickr-filtro', {
    altInput: true,
    altFormat: 'd/m/Y',
    dateFormat: 'Y-m-d',
    allowInput: true,
  });

  const dt = new DataTable('.datatables-comissoes', {
    processing: true,
    serverSide: true,
    responsive: true,
    ajax: {
      url: "{{ url('/painel/comissoes/datatable') }}",
      data: function (d) {
        d.tipo     = document.getElementById('filtro-tipo').value;
        d.status   = document.getElementById('filtro-status').value;
        d.data_de  = document.getElementById('filtro-de').value;
        d.data_ate = document.getElementById('filtro-ate').value;
      },
    },
    columns: [
      { data: 'descricao',       responsivePriority: 2, orderable: false },
      { data: 'tipo_badge',      responsivePriority: 2, orderable: false },
      { data: 'cliente_nome',    responsivePriority: 3, orderable: false },
      { data: 'data_formatada',  responsivePriority: 4, orderable: false, className: 'text-nowrap' },
      { data: 'valor_formatado', responsivePriority: 1, orderable: false, className: 'text-nowrap fw-semibold' },
      { data: 'status_badge',    responsivePriority: 1, orderable: false }
    ],
    order: [],
    language: { processing: 'Carregando...', search: 'Buscar:', lengthMenu: '_MENU_', info: '_START_-_END_ de _TOTAL_', infoEmpty: '0', zeroRecords: 'Nenhuma comissão', emptyTable: 'Nenhuma comissão', paginate: { first: '«', previous: '‹', next: '›', last: '»' } }
  });

  ['filtro-tipo', 'filtro-status', 'filtro-de', 'filtro-ate'].forEach(id => {
    document.getElementById(id).addEventListener('change', () => dt.draw());
  });
  document.getElementById('btn-limpar-filtros').addEventListener('click', () => {
    document.getElementById('filtro-tipo').value = '';
    document.getElementById('filtro-status').value = '';
    document.getElementById('filtro-de')._flatpickr?.clear();
    document.getElementById('filtro-ate')._flatpickr?.clear();
    dt.draw();
  });
});
</script>
@endsection
