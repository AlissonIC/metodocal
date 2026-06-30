@extends('layouts/layoutMaster')

@section('title', 'Sessões')

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
      <h5 class="card-title mb-0">Sessões agendadas</h5>
      <p class="text-muted mb-0 mt-1 small">Sessões de mentoria agendadas com mentorados.</p>
    </div>
    <a href="{{ route('admin.sessoes.create') }}" class="btn btn-primary">
      <i class="icon-base ti tabler-plus me-1"></i> Nova sessão
    </a>
  </div>

  @if (session('status'))
    <div class="card-body pb-0"><div class="alert alert-success mb-0">{{ session('status') }}</div></div>
  @endif

  <div class="card-body filtros-bar">
    <div class="row g-2 g-md-3">
      <div class="col-6 col-md-4">
        <label class="form-label small mb-1">Status</label>
        <select id="filtro-status" class="form-select form-select-sm" data-placeholder="Todos">
          <option value=""></option>
          <option value="agendada">Agendada</option>
          <option value="concluida">Concluída</option>
          <option value="cancelada">Cancelada</option>
        </select>
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label small mb-1">De</label>
        <input type="date" id="filtro-de" class="form-control form-control-sm">
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label small mb-1">Até</label>
        <input type="date" id="filtro-ate" class="form-control form-control-sm">
      </div>
      <div class="col-6 col-md-2 d-flex align-items-end">
        <button id="btn-limpar-filtros" class="btn btn-label-secondary btn-sm w-100" title="Limpar filtros"><i class="icon-base ti tabler-eraser"></i><span class="ms-1">Limpar</span></button>
      </div>
    </div>
  </div>

  <div class="card-datatable">
    <table class="datatables-sessoes table border-top dt-responsive" style="width:100%">
      <thead>
        <tr>
          <th>Mentorado</th>
          <th>Quando</th>
          <th>Status</th>
          <th>Título</th>
          <th>Duração</th>
          <th class="text-end">Ações</th>
        </tr>
      </thead>
    </table>
  </div>
</div>

<div class="modal fade" id="sessaoDetailsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Detalhes da sessão</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body"><div class="row g-3" id="sessao-details-body"></div></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Fechar</button>
        <a href="#" id="sessao-details-edit" class="btn btn-primary"><i class="icon-base ti tabler-edit me-1"></i> Editar</a>
      </div>
    </div>
  </div>
</div>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const csrf = document.querySelector('meta[name="csrf-token"]').content;
  const baseUrl = "{{ url('/painel/sessoes') }}";

  $('#filtro-status').select2({ allowClear: true, placeholder: function(){return $(this).data('placeholder')||'';}, width:'100%' });

  const dt = new DataTable('.datatables-sessoes', {
    processing: true, serverSide: true, responsive: true,
    ajax: {
      url: baseUrl + '/datatable',
      data: function (d) {
        d.status = $('#filtro-status').val();
        d.de = $('#filtro-de').val();
        d.ate = $('#filtro-ate').val();
      },
    },
    columns: [
      { data: 'user_name',           responsivePriority: 1 },
      { data: 'scheduled_formatado', responsivePriority: 1, className: 'text-nowrap' },
      { data: 'status_badge',        responsivePriority: 1 },
      { data: 'titulo',              responsivePriority: 3 },
      { data: 'duracao_minutos',     responsivePriority: 4, render: v => v + ' min', className: 'text-nowrap' },
      {
        data: 'actions', responsivePriority: 1,
        orderable: false, searchable: false, className: 'text-end text-nowrap',
        render: id => `
          <div class="d-inline-flex flex-nowrap gap-1 justify-content-end">
            <button class="btn btn-sm btn-icon view-sessao" data-id="${id}" title="Detalhes"><i class="icon-base ti tabler-eye icon-22px"></i></button>
            <a href="${baseUrl}/${id}/editar" class="btn btn-sm btn-icon"><i class="icon-base ti tabler-edit icon-22px"></i></a>
            <button class="btn btn-sm btn-icon delete-ses text-danger" data-id="${id}"><i class="icon-base ti tabler-trash icon-22px"></i></button>
          </div>`,
      },
    ],
    order: [[1, 'desc']],
    language: { processing: 'Carregando...', search: 'Buscar:', lengthMenu: '_MENU_ por página', info: 'Exibindo _START_ a _END_ de _TOTAL_', infoEmpty: 'Nenhum registro', zeroRecords: 'Nenhuma sessão encontrada', emptyTable: 'Nenhuma sessão cadastrada', paginate: { first: '«', previous: '‹', next: '›', last: '»' } },
    layout: { topStart: { features: [{ pageLength: { menu: [10, 25, 50] } }] }, topEnd: { features: [{ search: { placeholder: 'Buscar mentorado ou título' } }] } },
  });

  $('#filtro-status').on('change', () => dt.draw());
  $('#filtro-de, #filtro-ate').on('change', () => dt.draw());
  $('#btn-limpar-filtros').on('click', () => {
    $('#filtro-status').val(null).trigger('change');
    $('#filtro-de, #filtro-ate').val('');
    dt.draw();
  });

  function renderField(label, value, opts = {}) {
    if (value === null || value === undefined || value === '') value = '<span class="text-muted">—</span>';
    return `<div class="col-md-6 ${opts.full ? 'col-md-12' : ''}"><div class="text-muted small text-uppercase mb-1" style="font-size:.72rem;letter-spacing:.05em;">${label}</div><div>${value}</div></div>`;
  }

  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.view-sessao');
    if (! btn) return;
    const row = dt.row(btn.closest('tr')).data();
    if (! row) return;
    const d = row.details || {};
    document.getElementById('sessao-details-edit').href = `${baseUrl}/${row.actions}/editar`;
    document.getElementById('sessao-details-body').innerHTML = [
      renderField('Mentorado', d.user_name, { full: true }),
      renderField('Título', d.titulo, { full: true }),
      renderField('Quando', d.scheduled_at),
      renderField('Duração', d.duracao_minutos ? d.duracao_minutos + ' min' : null),
      renderField('Status', d.status),
      renderField('Link da reunião', d.link_reuniao ? `<a href="${d.link_reuniao}" target="_blank" class="text-truncate d-inline-block" style="max-width:100%">${d.link_reuniao}</a>` : null, { full: true }),
      renderField('Descrição', d.descricao, { full: true }),
      renderField('Notas', d.notas ? `<div class="bg-label-secondary p-2 rounded small">${d.notas}</div>` : null, { full: true }),
    ].join('');
    new bootstrap.Modal(document.getElementById('sessaoDetailsModal')).show();
  });

  document.addEventListener('click', function (e) {
    const delBtn = e.target.closest('.delete-ses');
    if (! delBtn) return;
    Swal.fire({ title: 'Excluir sessão?', text: 'Esta ação não pode ser desfeita.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Sim, excluir', cancelButtonText: 'Cancelar', customClass: { confirmButton: 'btn btn-danger me-3', cancelButton: 'btn btn-label-secondary' }, buttonsStyling: false }).then(r => {
      if (! r.value) return;
      fetch(`${baseUrl}/${delBtn.dataset.id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' } })
        .then(r => r.json().then(b => ({ ok: r.ok, body: b })))
        .then(({ ok, body }) => {
          if (! ok) throw new Error(body.message || 'Erro ao excluir');
          dt.draw(false);
          Swal.fire({ icon: 'success', title: 'Excluída', text: body.message, customClass: { confirmButton: 'btn btn-success' }, buttonsStyling: false });
        })
        .catch(err => Swal.fire({ icon: 'error', title: 'Erro', text: err.message, customClass: { confirmButton: 'btn btn-danger' }, buttonsStyling: false }));
    });
  });
});
</script>
@endsection
