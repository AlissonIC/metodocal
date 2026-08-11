@extends('layouts/layoutMaster')

@section('title', 'Financeiro')

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
  'resources/assets/vendor/libs/select2/select2.scss',
  'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
])
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
  'resources/assets/vendor/libs/select2/select2.js',
  'resources/assets/vendor/libs/flatpickr/flatpickr.js',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
])
@endsection

@section('page-style')
<style>
  @media (max-width: 575.98px) {
    .dt-responsive td, .dt-responsive th { font-size: .82rem; }
    .dt-responsive .badge { font-size: .68rem; }
  }
  table.dataTable.dtr-inline.collapsed > tbody > tr > td.dtr-control:before {
    background-color: var(--bs-primary); border: 0;
  }
  .filtros-bar .select2-container { width: 100% !important; }
</style>
@endsection

@section('content')
<div class="row g-6 mb-6">
  <div class="col-md-3 col-sm-6">
    <div class="card"><div class="card-body">
      <span class="text-heading">MRR estimado</span>
      <h4 class="my-1">R$ {{ number_format($kpi_mrr, 2, ',', '.') }}</h4>
      <small class="text-muted">{{ $qtd_assinaturas_ativas }} assinaturas ativas</small>
    </div></div>
  </div>
  <div class="col-md-3 col-sm-6">
    <div class="card"><div class="card-body">
      <span class="text-heading">Recebido no mês</span>
      <h4 class="my-1">R$ {{ number_format($kpi_recebido_mes, 2, ',', '.') }}</h4>
      <small class="text-success">Faturas pagas</small>
    </div></div>
  </div>
  <div class="col-md-2 col-sm-6">
    <div class="card"><div class="card-body">
      <span class="text-heading">Pendente</span>
      <h4 class="my-1">R$ {{ number_format($kpi_pendente, 2, ',', '.') }}</h4>
      <small class="text-warning">A receber</small>
    </div></div>
  </div>
  <div class="col-md-2 col-sm-6">
    <div class="card"><div class="card-body">
      <span class="text-heading">Atrasado</span>
      <h4 class="my-1 text-danger">R$ {{ number_format($kpi_atrasado, 2, ',', '.') }}</h4>
      <small class="text-muted">Faturas vencidas</small>
    </div></div>
  </div>
  <div class="col-md-2 col-sm-6">
    <div class="card"><div class="card-body">
      <span class="text-heading">Estornado no mês</span>
      <h4 class="my-1 text-info">R$ {{ number_format($kpi_estornado_mes, 2, ',', '.') }}</h4>
      <small class="text-muted">Refunds processados</small>
    </div></div>
  </div>
</div>

<div class="card">
  <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h5 class="card-title mb-0">Faturas</h5>
    <button type="button" id="btn-exportar" class="btn btn-label-success btn-sm">
      <i class="icon-base ti tabler-file-spreadsheet me-1"></i> Exportar
    </button>
  </div>

  {{-- ==================== FILTROS ==================== --}}
  <div class="card-body filtros-bar">
    <div class="row g-2 g-md-3">
      <div class="col-6 col-md-4 col-lg-2">
        <label class="form-label small mb-1">Status</label>
        <select id="filtro-status" class="form-select form-select-sm" data-placeholder="Todos">
          <option value=""></option>
          <option value="pendente">Pendente</option>
          <option value="paga">Paga</option>
          <option value="atrasada">Atrasada</option>
          <option value="cancelada">Cancelada</option>
          <option value="estornada">Estornada</option>
        </select>
      </div>
      <div class="col-6 col-md-4 col-lg-2">
        <label class="form-label small mb-1">Método</label>
        <select id="filtro-metodo" class="form-select form-select-sm" data-placeholder="Todos">
          <option value=""></option>
          <option value="pix">Pix</option>
          <option value="boleto">Boleto</option>
          <option value="cartao">Cartão</option>
          <option value="manual">Manual</option>
        </select>
      </div>
      <div class="col-12 col-md-4 col-lg-3">
        <label class="form-label small mb-1">Plano</label>
        <select id="filtro-plano" class="form-select form-select-sm" data-placeholder="Todos">
          <option value=""></option>
          @foreach ($plans as $p)
            <option value="{{ $p->id }}">{{ $p->nome }} ({{ ucfirst($p->tipo) }})</option>
          @endforeach
        </select>
      </div>
      <div class="col-6 col-md-4 col-lg-2">
        <label class="form-label small mb-1">Venc. de</label>
        <input type="text" id="filtro-de" class="form-control form-control-sm flatpickr-filtro" placeholder="dd/mm/aaaa">
      </div>
      <div class="col-6 col-md-4 col-lg-2">
        <label class="form-label small mb-1">Venc. até</label>
        <input type="text" id="filtro-ate" class="form-control form-control-sm flatpickr-filtro" placeholder="dd/mm/aaaa">
      </div>
      <div class="col-12 col-md-4 col-lg-1 d-flex align-items-end">
        <button id="btn-limpar-filtros" class="btn btn-label-secondary btn-sm w-100" title="Limpar filtros">
          <i class="icon-base ti tabler-eraser"></i>
          <span class="d-md-none d-lg-none ms-1">Limpar</span>
        </button>
      </div>
    </div>
  </div>

  <div class="card-datatable">
    <table class="datatables-faturas table border-top dt-responsive" style="width:100%">
      <thead><tr>
        <th>Usuário</th>
        <th>Valor</th>
        <th>Status</th>
        <th>Vencimento</th>
        <th>Plano</th>
        <th>Método</th>
        <th class="text-end">Ações</th>
      </tr></thead>
    </table>
  </div>
</div>

@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const csrf = document.querySelector('meta[name="csrf-token"]').content;
  const baseUrl = "{{ url('/painel/financeiro') }}";

  $('#filtro-status, #filtro-metodo, #filtro-plano').select2({
    allowClear: true,
    placeholder: function () { return $(this).data('placeholder') || ''; },
    width: '100%',
  });

  flatpickr('.flatpickr-filtro', {
    altInput: true,
    altFormat: 'd/m/Y',
    dateFormat: 'Y-m-d',
    allowInput: true,
  });

  const dt = new DataTable('.datatables-faturas', {
    processing: true,
    serverSide: true,
    responsive: true,
    ajax: {
      url: baseUrl + '/datatable',
      data: function (d) {
        d.status         = $('#filtro-status').val();
        d.metodo         = $('#filtro-metodo').val();
        d.plan_id        = $('#filtro-plano').val();
        d.vencimento_de  = $('#filtro-de').val();
        d.vencimento_ate = $('#filtro-ate').val();
      },
    },
    columns: [
      { data: 'user_name',            responsivePriority: 1 },
      { data: 'valor_formatado',      responsivePriority: 1, className: 'fw-semibold text-nowrap' },
      { data: 'status_badge',         responsivePriority: 1 },
      { data: 'vencimento_formatado', responsivePriority: 3, className: 'text-nowrap' },
      { data: 'plan_nome',            responsivePriority: 4 },
      { data: 'metodo_label',         responsivePriority: 5 },
      {
        data: 'actions',
        responsivePriority: 1,
        orderable: false, searchable: false,
        className: 'text-end text-nowrap',
        render: id => `
          <a href="${baseUrl}/${id}" class="btn btn-sm btn-icon btn-label-primary" title="Ver fatura">
            <i class="icon-base ti tabler-eye icon-22px"></i>
          </a>`,
      },
    ],
    order: [[3, 'desc']], // Vencimento DESC
    language: {
      processing: 'Carregando...', search: 'Buscar:',
      lengthMenu: '_MENU_ por página', info: 'Exibindo _START_ a _END_ de _TOTAL_',
      infoEmpty: 'Nenhum registro', zeroRecords: 'Nenhuma fatura encontrada',
      emptyTable: 'Nenhuma fatura gerada',
      paginate: { first: '«', previous: '‹', next: '›', last: '»' },
    },
    layout: {
      topStart: { features: [{ pageLength: { menu: [10, 25, 50, 100] } }] },
      topEnd:   { features: [{ search: { placeholder: 'Buscar usuário ou plano' } }] },
    },
  });

  $('#filtro-status, #filtro-metodo, #filtro-plano').on('change', () => dt.draw());
  // flatpickr dispara `change` no input original; capturamos com event listener nativo
  document.getElementById('filtro-de').addEventListener('change', () => dt.draw());
  document.getElementById('filtro-ate').addEventListener('change', () => dt.draw());
  $('#btn-limpar-filtros').on('click', () => {
    $('#filtro-status, #filtro-metodo, #filtro-plano').val(null).trigger('change');
    document.getElementById('filtro-de')._flatpickr?.clear();
    document.getElementById('filtro-ate')._flatpickr?.clear();
    dt.draw();
  });

  // Exportação: leva os mesmos filtros da tela e traz todas as linhas, sem paginação.
  document.getElementById('btn-exportar').addEventListener('click', () => {
    const params = new URLSearchParams({
      status:         $('#filtro-status').val() || '',
      metodo:         $('#filtro-metodo').val() || '',
      plan_id:        $('#filtro-plano').val() || '',
      vencimento_de:  document.getElementById('filtro-de').value,
      vencimento_ate: document.getElementById('filtro-ate').value,
      busca:          dt.search(),
    });
    [...params.keys()].forEach(k => { if (! params.get(k)) params.delete(k); });
    window.location = "{{ route('admin.financeiro.export') }}?" + params.toString();
  });

  // Detalhes agora abrem numa página dedicada (admin.financeiro.show) — sem modal.
});
</script>
@endsection
