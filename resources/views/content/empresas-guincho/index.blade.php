@extends('layouts/layoutMaster')

@section('title', 'Guincho')

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
      <h5 class="card-title mb-0">Guincho</h5>
      <p class="text-muted mb-0 mt-1">
        @if ($isAdmin)
          Gerencie as empresas de guincho cadastradas no sistema.
        @else
          Encontre empresas de guincho que atendem na sua cidade.
        @endif
      </p>
    </div>
    @if ($isAdmin)
      <a href="{{ route('empresas-guincho.create') }}" class="btn btn-primary">
        <i class="icon-base ti tabler-plus me-1"></i> Nova empresa
      </a>
    @endif
  </div>

  @if (session('status'))
    <div class="card-body pb-0"><div class="alert alert-success mb-0">{{ session('status') }}</div></div>
  @endif

  <div class="card-body">
    <div class="row g-2">
      <div class="col-md-6">
        <label class="form-label small">Cidade atendida</label>
        <input type="text" id="filter-cidade" class="form-control form-control-sm" placeholder="Ex: São Paulo">
      </div>
      <div class="col-md-6">
        <label class="form-label small">Estado</label>
        <select id="filter-estado" class="form-select form-select-sm">
          <option value="">Todos</option>
          @foreach ($estadosFiltro as $uf => $nome)
            <option value="{{ $uf }}">{{ $nome }}</option>
          @endforeach
        </select>
      </div>
    </div>
  </div>

  <div class="card-datatable">
    <table class="datatables-empresas table border-top">
      <thead>
        <tr>
          <th>ID</th>
          <th>Logo</th>
          <th>Nome</th>
          @if ($isAdmin)<th>CNPJ</th>@endif
          <th>Estado</th>
          <th>Cidades atendidas</th>
          <th>Contatos</th>
          @if ($isAdmin)<th>Status</th>@endif
          @if ($isAdmin)<th>Ações</th>@endif
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
  const baseUrl = "{{ url('/painel/empresas-guincho') }}";
  const isAdmin = @json($isAdmin);

  const columns = [
    { data: 'id' },
    { data: 'logo_html', orderable: false, searchable: false },
    { data: 'nome' },
  ];
  if (isAdmin) columns.push({ data: 'cnpj', render: v => v || '—' });
  columns.push(
    { data: 'estado_nome', orderable: false },
    { data: 'cidades_resumo', orderable: false, searchable: false },
    { data: 'contatos_html', orderable: false, searchable: false }
  );
  if (isAdmin) {
    columns.push({ data: 'status_badge' });
    columns.push({ data: 'id', orderable: false, searchable: false, render: id => `
      <a href="${baseUrl}/${id}/editar" class="btn btn-sm btn-icon"><i class="icon-base ti tabler-edit icon-22px"></i></a>
      <button class="btn btn-sm btn-icon delete-emp text-danger" data-id="${id}"><i class="icon-base ti tabler-trash icon-22px"></i></button>` });
  }

  const dt = new DataTable('.datatables-empresas', {
    processing: true, serverSide: true,
    ajax: {
      url: baseUrl + '/datatable',
      data: function (d) {
        d.estado = document.getElementById('filter-estado').value;
        d.cidade = document.getElementById('filter-cidade').value;
      }
    },
    columns: columns,
    order: [[0, 'desc']],
    language: { processing: 'Carregando...', search: 'Buscar:', lengthMenu: '_MENU_', info: '_START_-_END_ de _TOTAL_', infoEmpty: '0', zeroRecords: 'Nenhum', emptyTable: 'Nenhuma empresa cadastrada', paginate: { first: '«', previous: '‹', next: '›', last: '»' } },
    layout: { topStart: { features: [{ pageLength: { menu: [10, 25, 50] } }] }, topEnd: { features: [{ search: { placeholder: 'Buscar' } }] } }
  });

  ['filter-estado', 'filter-cidade'].forEach(id => {
    const el = document.getElementById(id);
    const evt = el.tagName === 'SELECT' ? 'change' : 'input';
    let timeout;
    el.addEventListener(evt, () => {
      clearTimeout(timeout);
      timeout = setTimeout(() => dt.draw(), 250);
    });
  });

  if (! isAdmin) return;

  document.addEventListener('click', function (e) {
    const delBtn = e.target.closest('.delete-emp');
    if (! delBtn) return;
    Swal.fire({ title: 'Excluir empresa?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Sim', cancelButtonText: 'Não', customClass: { confirmButton: 'btn btn-danger me-3', cancelButton: 'btn btn-label-secondary' }, buttonsStyling: false }).then(r => {
      if (! r.value) return;
      fetch(`${baseUrl}/${delBtn.dataset.id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' } })
        .then(r => r.json()).then(b => { dt.draw(false); Swal.fire({ icon: 'success', title: 'Removida', text: b.message, customClass: { confirmButton: 'btn btn-success' }, buttonsStyling: false }); });
    });
  });
});
</script>
@endsection
