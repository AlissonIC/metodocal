@extends('layouts/layoutMaster')

@section('title', 'Comissões')

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
  'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
])
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
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
    background-color: var(--bs-primary);
    border: 0;
  }
</style>
@endsection

@section('content')
<div class="card">
  <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <h5 class="card-title mb-0">Lançamentos de Comissões</h5>
      <p class="text-muted mb-0 mt-1 small">Comissões dos licenciados por cliente e período.</p>
    </div>
    <a href="{{ route('admin.comissoes.create') }}" class="btn btn-primary">
      <i class="icon-base ti tabler-plus me-1"></i> Nova Comissão
    </a>
  </div>

  @if (session('status'))
    <div class="card-body pb-0"><div class="alert alert-success mb-0">{{ session('status') }}</div></div>
  @endif

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
    <table class="datatables-comissoes-admin table border-top dt-responsive" style="width:100%">
      <thead>
        <tr>
          <th>Usuário</th>
          <th>Tipo</th>
          <th>Descrição</th>
          <th>Processo</th>
          <th>Data</th>
          <th>Valor</th>
          <th>Status</th>
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
  const csrf = document.querySelector('meta[name="csrf-token"]').content;
  const baseUrl = "{{ url('/painel/admin/comissoes') }}";

  flatpickr('.flatpickr-filtro', {
    altInput: true,
    altFormat: 'd/m/Y',
    dateFormat: 'Y-m-d',
    allowInput: true,
  });

  const dt = new DataTable('.datatables-comissoes-admin', {
    processing: true,
    serverSide: true,
    responsive: true,
    ajax: {
      url: baseUrl + '/datatable',
      data: function (d) {
        d.tipo     = document.getElementById('filtro-tipo').value;
        d.status   = document.getElementById('filtro-status').value;
        d.data_de  = document.getElementById('filtro-de').value;
        d.data_ate = document.getElementById('filtro-ate').value;
      },
    },
    columns: [
      { data: 'licenciado_nome', responsivePriority: 1 },
      { data: 'tipo_badge',      responsivePriority: 2 },
      { data: 'descricao',       responsivePriority: 5 },
      { data: 'processo_label',  responsivePriority: 4, className: 'text-nowrap' },
      { data: 'data_formatada',  responsivePriority: 4, className: 'text-nowrap' },
      { data: 'valor_formatado', responsivePriority: 1, className: 'text-nowrap fw-semibold' },
      { data: 'status_badge',    responsivePriority: 2 },
      {
        data: 'id',
        responsivePriority: 1,
        orderable: false,
        searchable: false,
        className: 'text-end text-nowrap',
        render: id => `
          <div class="d-inline-flex flex-nowrap gap-1 justify-content-end">
            <a href="${baseUrl}/${id}/editar" class="btn btn-sm btn-icon" title="Editar"><i class="icon-base ti tabler-edit icon-22px"></i></a>
            <button class="btn btn-sm btn-icon delete-com text-danger" data-id="${id}" title="Excluir"><i class="icon-base ti tabler-trash icon-22px"></i></button>
          </div>`
      }
    ],
    order: [[4, 'desc']],
    language: {
      processing: 'Carregando...',
      search: 'Buscar:',
      lengthMenu: '_MENU_ por página',
      info: 'Exibindo _START_ a _END_ de _TOTAL_',
      infoEmpty: 'Nenhum registro',
      infoFiltered: '(filtrado de _MAX_)',
      zeroRecords: 'Nenhuma comissão encontrada',
      emptyTable: 'Nenhuma comissão cadastrada',
      paginate: { first: '«', previous: '‹', next: '›', last: '»' }
    },
    layout: {
      topStart: { features: [{ pageLength: { menu: [10, 25, 50] } }] },
      topEnd: { features: [{ search: { placeholder: 'Buscar comissão' } }] }
    }
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

  document.addEventListener('click', function (e) {
    const delBtn = e.target.closest('.delete-com');
    if (! delBtn) return;
    Swal.fire({
      title: 'Excluir comissão?',
      text: 'Esta ação não pode ser desfeita.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sim, excluir',
      cancelButtonText: 'Cancelar',
      customClass: { confirmButton: 'btn btn-danger me-3', cancelButton: 'btn btn-label-secondary' },
      buttonsStyling: false
    }).then(result => {
      if (! result.value) return;
      fetch(`${baseUrl}/${delBtn.dataset.id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' } })
        .then(r => r.json().then(b => ({ ok: r.ok, body: b })))
        .then(({ ok, body }) => {
          if (! ok) throw new Error(body.message || 'Erro ao excluir');
          dt.draw(false);
          Swal.fire({ icon: 'success', title: 'Removida', text: body.message, customClass: { confirmButton: 'btn btn-success' }, buttonsStyling: false });
        })
        .catch(err => Swal.fire({ icon: 'error', title: 'Erro', text: err.message, customClass: { confirmButton: 'btn btn-danger' }, buttonsStyling: false }));
    });
  });
});
</script>
@endsection
