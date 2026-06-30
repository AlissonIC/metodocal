@extends('layouts/layoutMaster')

@section('title', 'Guincho')

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
  'resources/assets/vendor/libs/select2/select2.scss',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
])
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
  'resources/assets/vendor/libs/select2/select2.js',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
])
@endsection

@section('page-style')
<style>
  @media (max-width: 575.98px) { .dt-responsive td, .dt-responsive th { font-size: .82rem; } .dt-responsive .badge { font-size: .68rem; } }
  table.dataTable.dtr-inline.collapsed > tbody > tr > td.dtr-control:before { background-color: var(--bs-primary); border: 0; }
  .filtros-bar .select2-container { width: 100% !important; }
</style>
@endsection

@section('content')
<div class="card">
  <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <h5 class="card-title mb-0">Guincho</h5>
      <p class="text-muted mb-0 mt-1 small">
        @if ($isAdmin) Gerencie as empresas de guincho cadastradas.
        @else Encontre empresas de guincho que atendem na sua cidade. @endif
      </p>
    </div>
    @if ($isAdmin)
      <a href="{{ route('guincho.create') }}" class="btn btn-primary">
        <i class="icon-base ti tabler-plus me-1"></i> Nova empresa
      </a>
    @endif
  </div>

  @if (session('status'))
    <div class="card-body pb-0"><div class="alert alert-success mb-0">{{ session('status') }}</div></div>
  @endif

  <div class="card-body filtros-bar">
    <div class="row g-2 g-md-3">
      <div class="col-6 col-md-4">
        <label class="form-label small mb-1">Estado</label>
        <select id="filtro-estado" class="form-select form-select-sm" data-placeholder="Todos">
          <option value=""></option>
          @foreach ($estadosFiltro as $uf => $nome)
            <option value="{{ $uf }}">{{ $nome }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-6 col-md-6">
        <label class="form-label small mb-1">Cidade atendida</label>
        <input type="text" id="filtro-cidade" class="form-control form-control-sm" placeholder="Ex: São Paulo">
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
    <table class="datatables-empresas table border-top dt-responsive" style="width:100%">
      <thead>
        <tr>
          <th>Logo</th>
          <th>Nome</th>
          @if ($isAdmin)<th>CNPJ</th>@endif
          <th>Estado</th>
          <th>Cidades atendidas</th>
          <th>Contatos</th>
          @if ($isAdmin)<th>Status</th>@endif
          <th class="text-end">Ações</th>
        </tr>
      </thead>
    </table>
  </div>
</div>

<div class="modal fade" id="empDetailsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Detalhes da empresa</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body"><div class="row g-3" id="emp-details-body"></div></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Fechar</button>
        @if ($isAdmin)<a href="#" id="emp-details-edit" class="btn btn-primary"><i class="icon-base ti tabler-edit me-1"></i> Editar</a>@endif
      </div>
    </div>
  </div>
</div>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const csrf = document.querySelector('meta[name="csrf-token"]').content;
  const baseUrl = "{{ url('/painel/guincho') }}";
  const isAdmin = @json($isAdmin);

  $('#filtro-estado').select2({ allowClear: true, placeholder: function(){return $(this).data('placeholder')||'';}, width:'100%' });

  const columns = [
    { data: 'logo_html', orderable: false, searchable: false, responsivePriority: 4 },
    { data: 'nome',                                                responsivePriority: 1 },
  ];
  if (isAdmin) columns.push({ data: 'cnpj', responsivePriority: 5, render: v => v || '—' });
  columns.push(
    { data: 'estado_nome',    orderable: false,                     responsivePriority: 3, className: 'text-nowrap' },
    { data: 'cidades_resumo', orderable: false, searchable: false,  responsivePriority: 2 },
    { data: 'contatos_html',  orderable: false, searchable: false,  responsivePriority: 1 },
  );
  if (isAdmin) columns.push({ data: 'status_badge', responsivePriority: 2 });

  columns.push({
    data: 'actions',
    responsivePriority: 1, orderable: false, searchable: false, className: 'text-end text-nowrap',
    render: id => {
      let inner = `<button class="btn btn-sm btn-icon view-emp" data-id="${id}" title="Detalhes"><i class="icon-base ti tabler-eye icon-22px"></i></button>`;
      if (isAdmin) {
        inner += `
          <a href="${baseUrl}/${id}/editar" class="btn btn-sm btn-icon" title="Editar"><i class="icon-base ti tabler-edit icon-22px"></i></a>
          <button class="btn btn-sm btn-icon delete-emp text-danger" data-id="${id}" title="Excluir"><i class="icon-base ti tabler-trash icon-22px"></i></button>`;
      }
      return `<div class="d-inline-flex flex-nowrap gap-1 justify-content-end">${inner}</div>`;
    }
  });

  const dt = new DataTable('.datatables-empresas', {
    processing: true, serverSide: true, responsive: true,
    ajax: {
      url: baseUrl + '/datatable',
      data: function (d) {
        d.estado = $('#filtro-estado').val();
        d.cidade = $('#filtro-cidade').val();
      },
    },
    columns: columns,
    order: [],
    language: { processing: 'Carregando...', search: 'Buscar:', lengthMenu: '_MENU_ por página', info: 'Exibindo _START_ a _END_ de _TOTAL_', infoEmpty: 'Nenhum registro', zeroRecords: 'Nenhuma empresa encontrada', emptyTable: 'Nenhuma empresa cadastrada', paginate: { first: '«', previous: '‹', next: '›', last: '»' } },
    layout: { topStart: { features: [{ pageLength: { menu: [10, 25, 50] } }] }, topEnd: { features: [{ search: { placeholder: 'Buscar' } }] } },
  });

  $('#filtro-estado').on('change', () => dt.draw());
  let timeout;
  $('#filtro-cidade').on('input', () => { clearTimeout(timeout); timeout = setTimeout(() => dt.draw(), 300); });
  $('#btn-limpar-filtros').on('click', () => {
    $('#filtro-estado').val(null).trigger('change');
    $('#filtro-cidade').val('');
    dt.draw();
  });

  function renderField(label, value, opts = {}) {
    if (value === null || value === undefined || value === '') value = '<span class="text-muted">—</span>';
    return `<div class="col-md-6 ${opts.full ? 'col-md-12' : ''}"><div class="text-muted small text-uppercase mb-1" style="font-size:.72rem;letter-spacing:.05em;">${label}</div><div>${value}</div></div>`;
  }

  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.view-emp');
    if (! btn) return;
    const row = dt.row(btn.closest('tr')).data();
    if (! row) return;
    const d = row.details || {};
    if (isAdmin) document.getElementById('emp-details-edit').href = `${baseUrl}/${row.actions}/editar`;
    document.getElementById('emp-details-body').innerHTML = [
      renderField('Nome', d.nome),
      isAdmin ? renderField('CNPJ', d.cnpj) : '',
      renderField('Telefone', d.telefone),
      renderField('WhatsApp', d.whatsapp),
      renderField('E-mail', d.email),
      renderField('Site', d.site ? `<a href="${d.site}" target="_blank">${d.site}</a>` : null),
      renderField('Estado / Cidade', d.estado && d.cidade ? `${d.cidade}/${d.estado}` : (d.estado || d.cidade)),
      renderField('CEP', d.cep),
      renderField('Endereço', [d.endereco, d.numero, d.complemento, d.bairro].filter(Boolean).join(', '), { full: true }),
      renderField('Cidades atendidas', Array.isArray(d.cidades_atendidas) ? d.cidades_atendidas.join(', ') : null, { full: true }),
      renderField('Descrição', d.descricao, { full: true }),
      isAdmin ? renderField('Status', d.ativo ? '<span class="badge bg-label-success">Ativa</span>' : '<span class="badge bg-label-secondary">Inativa</span>') : '',
    ].join('');
    new bootstrap.Modal(document.getElementById('empDetailsModal')).show();
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
