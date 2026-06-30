@extends('layouts/layoutMaster')

@section('title', 'Planos')

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
      <h5 class="card-title mb-0">Planos</h5>
      <p class="text-muted mb-0 mt-1 small">Cadastros de planos contratáveis por mentorados e licenciados.</p>
    </div>
    <a href="{{ route('admin.plans.create') }}" class="btn btn-primary">
      <i class="icon-base ti tabler-plus me-1"></i> Novo plano
    </a>
  </div>

  @if (session('status'))
    <div class="card-body pb-0"><div class="alert alert-success mb-0">{{ session('status') }}</div></div>
  @endif

  <div class="card-body filtros-bar">
    <div class="row g-2 g-md-3">
      <div class="col-6 col-md-4">
        <label class="form-label small mb-1">Tipo</label>
        <select id="filtro-tipo" class="form-select form-select-sm" data-placeholder="Todos">
          <option value=""></option>
          <option value="mentorado">Mentorado</option>
          <option value="licenciado">Licenciado</option>
        </select>
      </div>
      <div class="col-6 col-md-4">
        <label class="form-label small mb-1">Recorrência</label>
        <select id="filtro-recorrencia" class="form-select form-select-sm" data-placeholder="Todas">
          <option value=""></option>
          <option value="mensal">Mensal</option>
          <option value="anual">Anual</option>
          <option value="vitalicio">Vitalício</option>
        </select>
      </div>
      <div class="col-8 col-md-3">
        <label class="form-label small mb-1">Status</label>
        <select id="filtro-status" class="form-select form-select-sm" data-placeholder="Todos">
          <option value=""></option>
          <option value="1">Ativos</option>
          <option value="0">Inativos</option>
        </select>
      </div>
      <div class="col-4 col-md-1 d-flex align-items-end">
        <button id="btn-limpar-filtros" class="btn btn-label-secondary btn-sm w-100" title="Limpar filtros"><i class="icon-base ti tabler-eraser"></i></button>
      </div>
    </div>
  </div>

  <div class="card-datatable">
    <table class="datatables-plans table border-top dt-responsive" style="width:100%">
      <thead>
        <tr>
          <th>Nome</th>
          <th>Preço</th>
          <th>Status</th>
          <th>Tipo</th>
          <th>Recorrência</th>
          <th>Ativas</th>
          <th class="text-end">Ações</th>
        </tr>
      </thead>
    </table>
  </div>
</div>

<div class="modal fade" id="planDetailsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Detalhes do plano</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body"><div class="row g-3" id="plan-details-body"></div></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Fechar</button>
        <a href="#" id="plan-details-edit" class="btn btn-primary"><i class="icon-base ti tabler-edit me-1"></i> Editar</a>
      </div>
    </div>
  </div>
</div>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const csrf = document.querySelector('meta[name="csrf-token"]').content;
  const baseUrl = "{{ url('/painel/planos') }}";

  $('#filtro-tipo, #filtro-recorrencia, #filtro-status').select2({ allowClear: true, placeholder: function(){return $(this).data('placeholder')||'';}, width:'100%' });

  const dt = new DataTable('.datatables-plans', {
    processing: true, serverSide: true, responsive: true,
    ajax: {
      url: baseUrl + '/datatable',
      data: function (d) {
        d.tipo        = $('#filtro-tipo').val();
        d.recorrencia = $('#filtro-recorrencia').val();
        d.ativo       = $('#filtro-status').val();
      },
    },
    columns: [
      { data: 'nome',              responsivePriority: 1 },
      { data: 'preco_formatado',   responsivePriority: 1, className: 'fw-semibold text-nowrap' },
      { data: 'status_badge',      responsivePriority: 1 },
      { data: 'tipo_label',        responsivePriority: 3 },
      { data: 'recorrencia_label', responsivePriority: 4 },
      { data: 'ativas_count',      responsivePriority: 5 },
      {
        data: 'actions', responsivePriority: 1,
        orderable: false, searchable: false, className: 'text-end text-nowrap',
        render: id => `
          <div class="d-inline-flex flex-nowrap gap-1 justify-content-end">
            <button class="btn btn-sm btn-icon view-plan" data-id="${id}" title="Detalhes"><i class="icon-base ti tabler-eye icon-22px"></i></button>
            <a href="${baseUrl}/${id}/editar" class="btn btn-sm btn-icon"><i class="icon-base ti tabler-edit icon-22px"></i></a>
            <button class="btn btn-sm btn-icon delete-plan text-danger" data-id="${id}"><i class="icon-base ti tabler-trash icon-22px"></i></button>
          </div>`,
      },
    ],
    order: [],
    language: { processing: 'Carregando...', search: 'Buscar:', lengthMenu: '_MENU_ por página', info: 'Exibindo _START_ a _END_ de _TOTAL_', infoEmpty: 'Nenhum registro', zeroRecords: 'Nenhum plano encontrado', emptyTable: 'Nenhum plano cadastrado', paginate: { first: '«', previous: '‹', next: '›', last: '»' } },
    layout: { topStart: { features: [{ pageLength: { menu: [10, 25, 50] } }] }, topEnd: { features: [{ search: { placeholder: 'Buscar plano' } }] } },
  });

  $('#filtro-tipo, #filtro-recorrencia, #filtro-status').on('change', () => dt.draw());
  $('#btn-limpar-filtros').on('click', () => $('#filtro-tipo, #filtro-recorrencia, #filtro-status').val(null).trigger('change'));

  function renderField(label, value, opts = {}) {
    if (value === null || value === undefined || value === '') value = '<span class="text-muted">—</span>';
    return `<div class="col-md-6 ${opts.full ? 'col-md-12' : ''}"><div class="text-muted small text-uppercase mb-1" style="font-size:.72rem;letter-spacing:.05em;">${label}</div><div>${value}</div></div>`;
  }

  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.view-plan');
    if (! btn) return;
    const row = dt.row(btn.closest('tr')).data();
    if (! row) return;
    const d = row.details || {};
    document.getElementById('plan-details-edit').href = `${baseUrl}/${row.actions}/editar`;
    document.getElementById('plan-details-body').innerHTML = [
      renderField('Nome', d.nome),
      renderField('Slug', d.slug ? `<code class="small">${d.slug}</code>` : null),
      renderField('Tipo', d.tipo),
      renderField('Preço', d.preco_formatado),
      renderField('Recorrência', d.recorrencia),
      renderField('Status', d.ativo ? '<span class="badge bg-label-success">Ativo</span>' : '<span class="badge bg-label-secondary">Inativo</span>'),
      renderField('Assinaturas ativas', d.ativas_count),
      renderField('Descrição', d.descricao, { full: true }),
      renderField('Permissions liberadas', Array.isArray(d.permissions) && d.permissions.length ? d.permissions.map(p => `<span class="badge bg-label-info me-1">${p}</span>`).join('') : null, { full: true }),
    ].join('');
    new bootstrap.Modal(document.getElementById('planDetailsModal')).show();
  });

  document.addEventListener('click', function (e) {
    const delBtn = e.target.closest('.delete-plan');
    if (! delBtn) return;
    Swal.fire({ title: 'Excluir plano?', text: 'Esta ação não pode ser desfeita.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Sim, excluir', cancelButtonText: 'Cancelar', customClass: { confirmButton: 'btn btn-danger me-3', cancelButton: 'btn btn-label-secondary' }, buttonsStyling: false }).then(r => {
      if (! r.value) return;
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
