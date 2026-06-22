@extends('layouts/layoutMaster')

@section('title', 'CRM de Clientes')

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
      <h5 class="card-title mb-0">Meus Clientes</h5>
      <p class="text-muted mb-0 mt-1 small">Cadastre e acompanhe seus leads e clientes ativos.</p>
    </div>
    <a href="{{ route('licenciado.crm.create') }}" class="btn btn-primary">
      <i class="icon-base ti tabler-plus me-1"></i> Novo Cliente
    </a>
  </div>

  @if (session('status'))
    <div class="card-body pb-0"><div class="alert alert-success mb-0">{{ session('status') }}</div></div>
  @endif

  <div class="card-datatable">
    <table class="datatables-crm table border-top">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nome</th>
          <th>E-mail</th>
          <th>Telefone</th>
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
  const baseUrl = "{{ url('/painel/crm') }}";

  const dt = new DataTable('.datatables-crm', {
    processing: true,
    serverSide: true,
    ajax: { url: baseUrl + '/datatable' },
    columns: [
      { data: 'id' },
      { data: 'nome' },
      { data: 'email', render: v => v || '—' },
      { data: 'telefone', render: v => v || '—' },
      { data: 'status_badge' },
      {
        data: 'actions',
        orderable: false,
        searchable: false,
        render: id => `
          <a href="${baseUrl}/${id}/editar" class="btn btn-sm btn-icon"><i class="icon-base ti tabler-edit icon-22px"></i></a>
          <button class="btn btn-sm btn-icon delete-cli text-danger" data-id="${id}"><i class="icon-base ti tabler-trash icon-22px"></i></button>`
      }
    ],
    order: [[0, 'desc']],
    language: {
      processing: 'Carregando...',
      search: 'Buscar:',
      lengthMenu: '_MENU_ por página',
      info: 'Exibindo _START_ a _END_ de _TOTAL_',
      infoEmpty: 'Nenhum registro',
      infoFiltered: '(filtrado de _MAX_)',
      zeroRecords: 'Nenhum cliente encontrado',
      emptyTable: 'Nenhum cliente cadastrado',
      paginate: { first: '«', previous: '‹', next: '›', last: '»' }
    },
    layout: {
      topStart: { features: [{ pageLength: { menu: [10, 25, 50] } }] },
      topEnd: { features: [{ search: { placeholder: 'Buscar cliente' } }] }
    }
  });

  document.addEventListener('click', function (e) {
    const delBtn = e.target.closest('.delete-cli');
    if (! delBtn) return;
    Swal.fire({
      title: 'Excluir cliente?',
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
