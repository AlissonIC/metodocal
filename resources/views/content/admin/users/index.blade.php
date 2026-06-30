@extends('layouts/layoutMaster')

@section('title', 'Usuários')

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
  /* Fonte reduzida em mobile pra caber mais info */
  @media (max-width: 575.98px) {
    .dt-responsive td, .dt-responsive th { font-size: .82rem; }
    .dt-responsive .badge { font-size: .68rem; }
  }
  /* Mostra o "+" expansível com cor de destaque */
  table.dataTable.dtr-inline.collapsed > tbody > tr > td.dtr-control:before {
    background-color: var(--bs-primary);
    border: 0;
  }
  /* Filtros: select2 ajustado ao bs5 */
  .filtros-bar .select2-container { width: 100% !important; }
</style>
@endsection

@section('content')
<div class="card">
  <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <h5 class="card-title mb-0">Usuários</h5>
      <p class="text-muted mb-0 mt-1 small">Gerenciamento de contas de admin, mentorados e licenciados.</p>
    </div>
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
      <i class="icon-base ti tabler-plus me-1"></i> Novo usuário
    </a>
  </div>

  @if (session('status'))
    <div class="card-body pb-0"><div class="alert alert-success mb-0">{{ session('status') }}</div></div>
  @endif

  {{-- ==================== FILTROS ==================== --}}
  <div class="card-body filtros-bar">
    <div class="row g-2 g-md-3">
      <div class="col-6 col-md-3">
        <label class="form-label small mb-1">Nível</label>
        <select id="filtro-role" class="form-select form-select-sm" data-placeholder="Todos">
          <option value=""></option>
          <option value="admin">Admin</option>
          <option value="mentorado">Mentorado</option>
          <option value="licenciado">Licenciado</option>
        </select>
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label small mb-1">Status</label>
        <select id="filtro-status" class="form-select form-select-sm" data-placeholder="Todos">
          <option value=""></option>
          <option value="ativo">Ativo</option>
          <option value="inativo">Inativo</option>
          <option value="bloqueado">Bloqueado</option>
        </select>
      </div>
      <div class="col-8 col-md-4">
        <label class="form-label small mb-1">Plano</label>
        <select id="filtro-plano" class="form-select form-select-sm" data-placeholder="Todos">
          <option value=""></option>
          @foreach ($plans as $p)
            <option value="{{ $p->id }}">{{ $p->nome }} ({{ ucfirst($p->tipo) }})</option>
          @endforeach
        </select>
      </div>
      <div class="col-4 col-md-2 d-flex align-items-end">
        <button id="btn-limpar-filtros" class="btn btn-label-secondary btn-sm w-100" title="Limpar filtros">
          <i class="icon-base ti tabler-eraser"></i>
          <span class="d-none d-md-inline ms-1">Limpar</span>
        </button>
      </div>
    </div>
  </div>

  <div class="card-datatable">
    <table class="datatables-users table border-top dt-responsive" style="width:100%">
      <thead>
        <tr>
          <th>Nome</th>
          <th>Status</th>
          <th>Nível</th>
          <th>E-mail</th>
          <th>Plano</th>
          <th>Criado em</th>
          <th class="text-end">Ações</th>
        </tr>
      </thead>
    </table>
  </div>
</div>

{{-- ==================== MODAL DETALHES ==================== --}}
<div class="modal fade" id="userDetailsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Detalhes do usuário</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3" id="user-details-body">
          <!-- preenchido via JS -->
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Fechar</button>
        <a href="#" id="user-details-edit" class="btn btn-primary">
          <i class="icon-base ti tabler-edit me-1"></i> Editar
        </a>
      </div>
    </div>
  </div>
</div>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const csrf = document.querySelector('meta[name="csrf-token"]').content;
  const baseUrl = "{{ url('/painel/usuarios') }}";

  // ---- Select2 nos filtros ----
  $('#filtro-role, #filtro-status, #filtro-plano').select2({
    allowClear: true,
    placeholder: function () { return $(this).data('placeholder') || ''; },
    width: '100%',
  });

  // ---- DataTable ----
  const dt = new DataTable('.datatables-users', {
    processing: true,
    serverSide: true,
    responsive: true,
    ajax: {
      url: baseUrl + '/datatable',
      data: function (d) {
        d.role     = $('#filtro-role').val();
        d.status   = $('#filtro-status').val();
        d.plan_id  = $('#filtro-plano').val();
      },
    },
    columns: [
      { data: 'name',         responsivePriority: 1 },
      { data: 'status_badge', responsivePriority: 1 },
      { data: 'role',         responsivePriority: 2 },
      { data: 'email',        responsivePriority: 3 },
      { data: 'plano',        responsivePriority: 4 },
      { data: 'criado_em',    responsivePriority: 5, className: 'text-nowrap' },
      {
        data: 'actions',
        responsivePriority: 1,
        orderable: false,
        searchable: false,
        className: 'text-end text-nowrap',
        render: id => `
          <div class="d-inline-flex flex-nowrap gap-1 justify-content-end">
            <button class="btn btn-sm btn-icon view-user" data-id="${id}" title="Detalhes">
              <i class="icon-base ti tabler-eye icon-22px"></i>
            </button>
            <a href="${baseUrl}/${id}/editar" class="btn btn-sm btn-icon" title="Editar">
              <i class="icon-base ti tabler-edit icon-22px"></i>
            </a>
            <button class="btn btn-sm btn-icon delete-user text-danger" data-id="${id}" title="Excluir">
              <i class="icon-base ti tabler-trash icon-22px"></i>
            </button>
          </div>`,
      },
    ],
    order: [[5, 'desc']], // criado em DESC
    language: {
      processing: 'Carregando...',
      search: 'Buscar:',
      lengthMenu: '_MENU_ por página',
      info: 'Exibindo _START_ a _END_ de _TOTAL_',
      infoEmpty: 'Nenhum registro',
      zeroRecords: 'Nenhum usuário encontrado',
      emptyTable: 'Nenhum usuário cadastrado',
      paginate: { first: '«', previous: '‹', next: '›', last: '»' },
    },
    layout: {
      topStart: { features: [{ pageLength: { menu: [10, 25, 50] } }] },
      topEnd:   { features: [{ search: { placeholder: 'Buscar nome ou e-mail' } }] },
    },
  });

  // ---- Aplicar filtros ----
  $('#filtro-role, #filtro-status, #filtro-plano').on('change', () => dt.draw());
  $('#btn-limpar-filtros').on('click', () => {
    $('#filtro-role, #filtro-status, #filtro-plano').val(null).trigger('change');
  });

  // ---- Modal de detalhes ----
  function renderField(label, value, opts = {}) {
    if (value === null || value === undefined || value === '') value = '<span class="text-muted">—</span>';
    return `
      <div class="col-md-6 ${opts.full ? 'col-md-12' : ''}">
        <div class="text-muted small text-uppercase mb-1" style="font-size:.72rem;letter-spacing:.05em;">${label}</div>
        <div class="${opts.bold ? 'fw-semibold' : ''}">${value}</div>
      </div>`;
  }
  function statusBadge(s) {
    const map = { ativo: 'success', inativo: 'secondary', bloqueado: 'danger' };
    return `<span class="badge bg-label-${map[s] || 'secondary'}">${s ? s[0].toUpperCase() + s.slice(1) : '—'}</span>`;
  }
  function fmtMoney(v) {
    if (v === null || v === undefined) return null;
    return 'R$ ' + Number(v).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.view-user');
    if (! btn) return;
    const row = dt.row(btn.closest('tr')).data();
    if (! row) return;
    const d = row.details;
    document.getElementById('user-details-edit').href = `${baseUrl}/${row.actions}/editar`;
    document.getElementById('user-details-body').innerHTML = [
      renderField('Nome', d.name, { bold: true }),
      renderField('E-mail', d.email),
      renderField('Telefone', d.phone),
      renderField('CPF / CNPJ', d.cpf_cnpj),
      renderField('Nível', d.role ? d.role[0].toUpperCase() + d.role.slice(1) : null),
      renderField('Status', statusBadge(d.status)),
      renderField('Cadastrado em', d.created_at),
      renderField('E-mail verificado em', d.email_verified_at),
      renderField('Último login', d.last_login_at),
      '<div class="col-12"><hr class="my-2"><h6 class="text-muted small text-uppercase mb-3">Assinatura</h6></div>',
      renderField('Plano', d.plan_nome),
      renderField('Tipo', d.plan_tipo ? d.plan_tipo[0].toUpperCase() + d.plan_tipo.slice(1) : null),
      renderField('Valor', fmtMoney(d.plan_preco)),
      renderField('Recorrência', d.plan_recorrencia ? d.plan_recorrencia[0].toUpperCase() + d.plan_recorrencia.slice(1) : null),
      renderField('Status da assinatura', d.sub_status ? d.sub_status[0].toUpperCase() + d.sub_status.slice(1) : null),
      renderField('Início', d.sub_started_at),
      renderField('Fim', d.sub_ends_at),
    ].join('');
    new bootstrap.Modal(document.getElementById('userDetailsModal')).show();
  });

  // ---- Excluir ----
  document.addEventListener('click', function (e) {
    const delBtn = e.target.closest('.delete-user');
    if (! delBtn) return;
    Swal.fire({
      title: 'Excluir usuário?',
      text: 'Esta ação não pode ser desfeita.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sim, excluir',
      cancelButtonText: 'Cancelar',
      customClass: { confirmButton: 'btn btn-danger me-3', cancelButton: 'btn btn-label-secondary' },
      buttonsStyling: false,
    }).then(r => {
      if (! r.value) return;
      fetch(`${baseUrl}/${delBtn.dataset.id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
      })
        .then(r => r.json().then(b => ({ ok: r.ok, body: b })))
        .then(({ ok, body }) => {
          if (! ok) throw new Error(body.message || 'Erro');
          dt.draw(false);
          Swal.fire({ icon: 'success', title: 'Excluído', text: body.message, customClass: { confirmButton: 'btn btn-success' }, buttonsStyling: false });
        })
        .catch(err => Swal.fire({ icon: 'error', title: 'Erro', text: err.message, customClass: { confirmButton: 'btn btn-danger' }, buttonsStyling: false }));
    });
  });
});
</script>
@endsection
