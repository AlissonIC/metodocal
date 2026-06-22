@extends('layouts/layoutMaster')

@section('title', 'Conteúdos')

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
      <h5 class="card-title mb-0">Biblioteca de Conteúdos</h5>
      <p class="text-muted mb-0 mt-1 small">Vídeos, PDFs, textos e links liberados para os mentorados.</p>
    </div>
    <a href="{{ route('admin.conteudos.create') }}" class="btn btn-primary">
      <i class="icon-base ti tabler-plus me-1"></i> Novo Conteúdo
    </a>
  </div>

  @if (session('status'))
    <div class="card-body pb-0"><div class="alert alert-success mb-0">{{ session('status') }}</div></div>
  @endif

  <div class="card-datatable">
    <table class="datatables-conteudos table border-top">
      <thead>
        <tr>
          <th>ID</th>
          <th>Título</th>
          <th>Tipo</th>
          <th>Categoria</th>
          <th>Ordem</th>
          <th>Concluíram</th>
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
  const baseUrl = "{{ url('/painel/conteudos-admin') }}";

  const dt = new DataTable('.datatables-conteudos', {
    processing: true,
    serverSide: true,
    ajax: { url: baseUrl + '/datatable' },
    columns: [
      { data: 'id' },
      { data: 'titulo' },
      { data: 'tipo_label' },
      { data: 'categoria', render: v => v || '—' },
      { data: 'ordem' },
      { data: 'progressos_count' },
      { data: 'status_badge' },
      {
        data: 'id',
        orderable: false,
        searchable: false,
        render: id => `
          <a href="${baseUrl}/${id}/editar" class="btn btn-sm btn-icon"><i class="icon-base ti tabler-edit icon-22px"></i></a>
          <button class="btn btn-sm btn-icon delete-con text-danger" data-id="${id}"><i class="icon-base ti tabler-trash icon-22px"></i></button>`
      }
    ],
    order: [[4, 'asc']],
    language: {
      processing: 'Carregando...',
      search: 'Buscar:',
      lengthMenu: '_MENU_ por página',
      info: 'Exibindo _START_ a _END_ de _TOTAL_',
      infoEmpty: 'Nenhum registro',
      infoFiltered: '(filtrado de _MAX_)',
      zeroRecords: 'Nenhum conteúdo encontrado',
      emptyTable: 'Nenhum conteúdo cadastrado',
      paginate: { first: '«', previous: '‹', next: '›', last: '»' }
    },
    layout: {
      topStart: { features: [{ pageLength: { menu: [10, 25, 50] } }] },
      topEnd: { features: [{ search: { placeholder: 'Buscar conteúdo' } }] }
    }
  });

  document.addEventListener('click', function (e) {
    const delBtn = e.target.closest('.delete-con');
    if (! delBtn) return;
    Swal.fire({
      title: 'Excluir conteúdo?',
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
          Swal.fire({ icon: 'success', title: 'Excluído', text: body.message, customClass: { confirmButton: 'btn btn-success' }, buttonsStyling: false });
        })
        .catch(err => Swal.fire({ icon: 'error', title: 'Erro', text: err.message, customClass: { confirmButton: 'btn btn-danger' }, buttonsStyling: false }));
    });
  });
});
</script>
@endsection
