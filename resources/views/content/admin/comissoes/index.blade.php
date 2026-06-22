@extends('layouts/layoutMaster')

@section('title', 'Comissões')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
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

  <div class="card-datatable">
    <table class="datatables-comissoes-admin table border-top">
      <thead>
        <tr>
          <th>ID</th>
          <th>Licenciado</th>
          <th>Cliente</th>
          <th>Descrição</th>
          <th>Data</th>
          <th>Valor</th>
          <th>Status</th>
          <th>Ações</th>
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
  const baseUrl = "{{ url('/painel/comissoes-admin') }}";

  const dt = new DataTable('.datatables-comissoes-admin', {
    processing: true,
    serverSide: true,
    ajax: { url: baseUrl + '/datatable' },
    columns: [
      { data: 'id' },
      { data: 'licenciado_nome' },
      { data: 'cliente_nome' },
      { data: 'descricao' },
      { data: 'data_formatada' },
      { data: 'valor_formatado' },
      { data: 'status_badge' },
      {
        data: 'id',
        orderable: false,
        searchable: false,
        render: id => `
          <a href="${baseUrl}/${id}/editar" class="btn btn-sm btn-icon"><i class="icon-base ti tabler-edit icon-22px"></i></a>
          <button class="btn btn-sm btn-icon delete-com text-danger" data-id="${id}"><i class="icon-base ti tabler-trash icon-22px"></i></button>`
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
