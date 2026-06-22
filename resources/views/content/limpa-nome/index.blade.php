@extends('layouts/layoutMaster')

@section('title', 'Limpa Nome')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'])
@endsection

@section('content')
<div class="card">
  <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <h5 class="card-title mb-0">Limpa Nome</h5>
      <p class="text-muted mb-0 mt-1">
        @if ($isAdmin)
          Processos de todos os clientes — gerencie status, documentos e observações.
        @else
          Acompanhe seus processos de limpa nome, aquisição e negociação de dívidas.
        @endif
      </p>
    </div>
    @unless ($isAdmin)
      <a href="{{ route('limpa-nome.create') }}" class="btn btn-primary">
        <i class="icon-base ti tabler-plus me-1"></i> Novo processo
      </a>
    @endunless
  </div>

  <div class="card-body">
    <div class="row g-2">
      <div class="col-md-{{ $isAdmin ? 4 : 6 }}">
        <label class="form-label small">Status</label>
        <select id="filter-status" class="form-select form-select-sm">
          <option value="">Todos</option>
          @foreach ($statuses as $v => [$label, $color])
            <option value="{{ $v }}">{{ $label }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-{{ $isAdmin ? 4 : 6 }}">
        <label class="form-label small">Tipo</label>
        <select id="filter-tipo" class="form-select form-select-sm">
          <option value="">Todos</option>
          @foreach ($tipos as $v => $l)
            <option value="{{ $v }}">{{ $l }}</option>
          @endforeach
        </select>
      </div>
      @if ($isAdmin)
        <div class="col-md-4">
          <label class="form-label small">Cliente</label>
          <input type="text" id="filter-cliente" class="form-control form-control-sm" placeholder="Buscar por nome do cliente">
        </div>
      @endif
    </div>
  </div>

  <div class="card-datatable">
    <table class="datatables-limpa-nome table border-top">
      <thead>
        <tr>
          <th>ID</th>
          @if ($isAdmin)<th>Cliente</th>@endif
          <th>Pessoa</th>
          <th>Documento</th>
          <th>Tipo</th>
          <th>Status</th>
          <th>Cadastrado em</th>
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
  const baseUrl = "{{ url('/painel/limpa-nome') }}";
  const isAdmin = @json($isAdmin);

  const columns = [
    { data: 'id' },
  ];
  if (isAdmin) columns.push({ data: 'cliente' });
  columns.push(
    { data: 'nome_completo' },
    { data: 'documento_formatado', orderable: false },
    { data: 'tipo_label' },
    { data: 'status_badge' },
    { data: 'criado_em' },
    { data: 'id', orderable: false, searchable: false, render: id => `
      <a href="${baseUrl}/${id}" class="btn btn-sm btn-icon btn-label-primary" title="Ver detalhes"><i class="icon-base ti tabler-eye icon-22px"></i></a>` }
  );

  const dt = new DataTable('.datatables-limpa-nome', {
    processing: true, serverSide: true,
    ajax: {
      url: baseUrl + '/datatable',
      data: function (d) {
        d.status = document.getElementById('filter-status').value;
        d.tipo = document.getElementById('filter-tipo').value;
        const c = document.getElementById('filter-cliente');
        if (c) d.cliente = c.value;
      }
    },
    columns: columns,
    order: [[0, 'desc']],
    language: { processing: 'Carregando...', search: 'Buscar:', lengthMenu: '_MENU_', info: '_START_-_END_ de _TOTAL_', infoEmpty: '0', zeroRecords: 'Nenhum', emptyTable: 'Nenhum processo cadastrado', paginate: { first: '«', previous: '‹', next: '›', last: '»' } },
    layout: { topStart: { features: [{ pageLength: { menu: [10, 25, 50] } }] }, topEnd: { features: [{ search: { placeholder: 'Buscar' } }] } }
  });

  ['filter-status', 'filter-tipo', 'filter-cliente'].forEach(id => {
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
