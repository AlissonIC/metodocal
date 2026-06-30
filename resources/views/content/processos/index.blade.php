@extends('layouts/layoutMaster')

@section('title', 'Processos')

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
<div class="card">
  <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <h5 class="card-title mb-0">Processos</h5>
      <p class="text-muted mb-0 mt-1">
        @if ($isAdmin)
          Processos de todos os clientes. Gerencie status, documentos, faturas e observações.
        @else
          Acompanhe seus processos contratados.
        @endif
      </p>
    </div>
    <a href="{{ route('processos.create') }}" class="btn btn-primary">
      <i class="icon-base ti tabler-plus me-1"></i> Novo processo
    </a>
  </div>

  <div class="card-body filtros-bar">
    <div class="row g-2 g-md-3">
      <div class="col-6 col-md-4 col-lg-3">
        <label class="form-label small mb-1">Status</label>
        <select id="filter-status" class="form-select form-select-sm">
          <option value="">Todos</option>
          @foreach ($statuses as $v => [$label, $color])
            <option value="{{ $v }}">{{ $label }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-6 col-md-4 col-lg-3">
        <label class="form-label small mb-1">Serviço</label>
        <select id="filter-servico" class="form-select form-select-sm">
          <option value="">Todos</option>
          @foreach ($servicos as $servico)
            <option value="{{ $servico->id }}">{{ $servico->nome }}</option>
          @endforeach
        </select>
      </div>
      @if ($isAdmin)
        <div class="col-12 col-md-4 col-lg-2">
          <label class="form-label small mb-1">Cliente</label>
          <input type="text" id="filter-cliente" class="form-control form-control-sm" placeholder="Nome do cliente">
        </div>
      @endif
      <div class="col-6 col-md-4 col-lg-{{ $isAdmin ? 2 : 3 }}">
        <label class="form-label small mb-1">Cadastrado de</label>
        <input type="text" id="filter-de" class="form-control form-control-sm flatpickr-filtro" placeholder="dd/mm/aaaa">
      </div>
      <div class="col-6 col-md-4 col-lg-{{ $isAdmin ? 2 : 3 }}">
        <label class="form-label small mb-1">Cadastrado até</label>
        <input type="text" id="filter-ate" class="form-control form-control-sm flatpickr-filtro" placeholder="dd/mm/aaaa">
      </div>
    </div>
  </div>

  <div class="card-datatable">
    <table class="datatables-processos table border-top dt-responsive" style="width:100%">
      <thead>
        <tr>
          @if ($isAdmin)<th>Cliente</th>@endif
          <th>Pessoa</th>
          <th>Documento</th>
          <th>Serviço</th>
          <th>Status</th>
          <th>Cadastrado em</th>
          <th class="text-end">Ações</th>
        </tr>
      </thead>
    </table>
  </div>
</div>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const baseUrl = "{{ url('/painel/processos') }}";
  const isAdmin = @json($isAdmin);

  flatpickr('.flatpickr-filtro', {
    altInput: true,
    altFormat: 'd/m/Y',
    dateFormat: 'Y-m-d',
    allowInput: true,
  });

  const columns = [];
  if (isAdmin) columns.push({ data: 'cliente', responsivePriority: 1, orderable: false });
  columns.push(
    { data: 'nome_completo',        responsivePriority: 1, orderable: false },
    { data: 'documento_formatado',  responsivePriority: 4, orderable: false, className: 'text-nowrap' },
    { data: 'servico_nome',         responsivePriority: 3, orderable: false },
    { data: 'status_badge',         responsivePriority: 2, orderable: false },
    { data: 'criado_em',            responsivePriority: 5, orderable: false, className: 'text-nowrap' },
    {
      data: 'id',
      responsivePriority: 1,
      orderable: false,
      searchable: false,
      className: 'text-end text-nowrap',
      render: id => `
        <a href="${baseUrl}/${id}" class="btn btn-sm btn-icon btn-label-primary" title="Ver detalhes"><i class="icon-base ti tabler-eye icon-22px"></i></a>`
    }
  );

  const dt = new DataTable('.datatables-processos', {
    processing: true,
    serverSide: true,
    responsive: true,
    ajax: {
      url: baseUrl + '/datatable',
      data: function (d) {
        d.status = document.getElementById('filter-status').value;
        d.servico_id = document.getElementById('filter-servico').value;
        d.cadastrado_de = document.getElementById('filter-de').value;
        d.cadastrado_ate = document.getElementById('filter-ate').value;
        const c = document.getElementById('filter-cliente');
        if (c) d.cliente = c.value;
      }
    },
    columns: columns,
    order: [],
    language: { processing: 'Carregando...', search: 'Buscar:', lengthMenu: '_MENU_', info: '_START_-_END_ de _TOTAL_', infoEmpty: '0', zeroRecords: 'Nenhum', emptyTable: 'Nenhum processo cadastrado', paginate: { first: '«', previous: '‹', next: '›', last: '»' } },
    layout: { topStart: { features: [{ pageLength: { menu: [10, 25, 50] } }] }, topEnd: { features: [{ search: { placeholder: 'Buscar' } }] } }
  });

  ['filter-status', 'filter-servico', 'filter-cliente', 'filter-de', 'filter-ate'].forEach(id => {
    const el = document.getElementById(id);
    if (! el) return;
    const evt = el.tagName === 'SELECT' ? 'change' : 'input';
    let timeout;
    el.addEventListener(evt, () => {
      clearTimeout(timeout);
      timeout = setTimeout(() => dt.draw(), 250);
    });
  });
});
</script>
@endsection
