@extends('layouts/layoutMaster')

@section('title', 'Materiais')

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
])
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
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
      <h5 class="card-title mb-0">Materiais para licenciados</h5>
      <p class="text-muted mb-0 mt-1 small">Arquivos disponibilizados para download pelos licenciados.</p>
    </div>
    <a href="{{ route('admin.materiais.create') }}" class="btn btn-primary">
      <i class="icon-base ti tabler-plus me-1"></i> Novo Material
    </a>
  </div>

  @if (session('status'))
    <div class="card-body pb-0"><div class="alert alert-success mb-0">{{ session('status') }}</div></div>
  @endif

  <div class="card-datatable">
    <table class="datatables-materiais table border-top dt-responsive" style="width:100%">
      <thead>
        <tr>
          <th>Título</th>
          <th>Categoria</th>
          <th>Tamanho</th>
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
  const baseUrl = "{{ url('/painel/admin/materiais') }}";

  const dt = new DataTable('.datatables-materiais', {
    processing: true,
    serverSide: true,
    responsive: true,
    ajax: { url: baseUrl + '/datatable' },
    columns: [
      { data: 'titulo',            responsivePriority: 1 },
      { data: 'categoria',         responsivePriority: 3, render: v => v || '<span class="text-muted">—</span>' },
      { data: 'tamanho_formatado', responsivePriority: 4, className: 'text-nowrap' },
      { data: 'status_badge',      responsivePriority: 2 },
      {
        data: 'id',
        responsivePriority: 1,
        orderable: false,
        searchable: false,
        className: 'text-end text-nowrap',
        render: id => `
          <div class="d-inline-flex flex-nowrap gap-1 justify-content-end">
            <a href="${baseUrl}/${id}/editar" class="btn btn-sm btn-icon" title="Editar"><i class="icon-base ti tabler-edit icon-22px"></i></a>
            <button class="btn btn-sm btn-icon delete-mat text-danger" data-id="${id}" title="Excluir"><i class="icon-base ti tabler-trash icon-22px"></i></button>
          </div>`
      }
    ],
    order: [[0, 'asc']],
    language: {
      processing: 'Carregando...',
      search: 'Buscar:',
      lengthMenu: '_MENU_ por página',
      info: 'Exibindo _START_ a _END_ de _TOTAL_',
      infoEmpty: 'Nenhum registro',
      infoFiltered: '(filtrado de _MAX_)',
      zeroRecords: 'Nenhum material encontrado',
      emptyTable: 'Nenhum material cadastrado',
      paginate: { first: '«', previous: '‹', next: '›', last: '»' }
    },
    layout: {
      topStart: { features: [{ pageLength: { menu: [10, 25, 50] } }] },
      topEnd: { features: [{ search: { placeholder: 'Buscar material' } }] }
    }
  });

  document.addEventListener('click', function (e) {
    const delBtn = e.target.closest('.delete-mat');
    if (! delBtn) return;
    Swal.fire({
      title: 'Excluir material?',
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
          Swal.fire({ icon: 'success', title: 'Removido', text: body.message, customClass: { confirmButton: 'btn btn-success' }, buttonsStyling: false });
        })
        .catch(err => Swal.fire({ icon: 'error', title: 'Erro', text: err.message, customClass: { confirmButton: 'btn btn-danger' }, buttonsStyling: false }));
    });
  });
});
</script>
@endsection
