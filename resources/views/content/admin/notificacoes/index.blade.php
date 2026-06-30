@extends('layouts/layoutMaster')

@section('title', 'Notificações')

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
  @media (max-width: 575.98px) {
    .dt-responsive td, .dt-responsive th { font-size: .82rem; }
    .dt-responsive .badge { font-size: .68rem; }
  }
  table.dataTable.dtr-inline.collapsed > tbody > tr > td.dtr-control:before {
    background-color: var(--bs-primary); border: 0;
  }
  .filtros-bar .select2-container { width: 100% !important; }
</style>
@endsection

@section('content')
<div class="row g-6 mb-6">
  <div class="col-md-3 col-sm-6">
    <div class="card"><div class="card-body">
      <span class="text-heading">Total</span>
      <h4 class="my-1">{{ $kpi_total }}</h4>
      <small class="text-muted">Na fila desde sempre</small>
    </div></div>
  </div>
  <div class="col-md-3 col-sm-6">
    <div class="card"><div class="card-body">
      <span class="text-heading">Pendentes</span>
      <h4 class="my-1 text-warning">{{ $kpi_pendentes }}</h4>
      <small class="text-muted">Aguardando envio</small>
    </div></div>
  </div>
  <div class="col-md-3 col-sm-6">
    <div class="card"><div class="card-body">
      <span class="text-heading">Enviadas</span>
      <h4 class="my-1 text-success">{{ $kpi_enviadas }}</h4>
      <small class="text-muted">Concluídas com sucesso</small>
    </div></div>
  </div>
  <div class="col-md-3 col-sm-6">
    <div class="card"><div class="card-body">
      <span class="text-heading">Falharam</span>
      <h4 class="my-1 text-danger">{{ $kpi_falhadas }}</h4>
      <small class="text-muted">Precisam ser reenviadas</small>
    </div></div>
  </div>
</div>

<div class="card">
  <div class="card-header border-bottom">
    <h5 class="card-title mb-0">Fila de Notificações</h5>
    <p class="text-muted mb-0 small">E-mails, WhatsApp e outros canais enfileirados pelo sistema.</p>
  </div>

  <div class="card-body filtros-bar">
    <div class="row g-2 g-md-3">
      <div class="col-6 col-md-3 col-lg-3">
        <label class="form-label small mb-1">Status</label>
        <select id="filtro-status" class="form-select form-select-sm" data-placeholder="Todos">
          <option value=""></option>
          <option value="pendente">Pendente</option>
          <option value="enviando">Enviando</option>
          <option value="enviada">Enviada</option>
          <option value="falhou">Falhou</option>
          <option value="cancelada">Cancelada</option>
        </select>
      </div>
      <div class="col-6 col-md-3 col-lg-3">
        <label class="form-label small mb-1">Canal</label>
        <select id="filtro-canal" class="form-select form-select-sm" data-placeholder="Todos">
          <option value=""></option>
          <option value="email">E-mail</option>
          <option value="whatsapp">WhatsApp</option>
          <option value="sms">SMS</option>
          <option value="push">Push</option>
        </select>
      </div>
      <div class="col-6 col-md-2 col-lg-2">
        <label class="form-label small mb-1">De</label>
        <input type="date" id="filtro-de" class="form-control form-control-sm">
      </div>
      <div class="col-6 col-md-2 col-lg-2">
        <label class="form-label small mb-1">Até</label>
        <input type="date" id="filtro-ate" class="form-control form-control-sm">
      </div>
      <div class="col-12 col-md-2 col-lg-2 d-flex align-items-end">
        <button id="btn-limpar-filtros" class="btn btn-label-secondary btn-sm w-100" title="Limpar filtros">
          <i class="icon-base ti tabler-eraser"></i>
          <span class="ms-1">Limpar</span>
        </button>
      </div>
    </div>
  </div>

  <div class="card-datatable">
    <table class="datatables-notif table border-top dt-responsive" style="width:100%">
      <thead><tr>
        <th>Destinatário</th>
        <th>Canal</th>
        <th>Status</th>
        <th>Assunto</th>
        <th>Quando</th>
        <th class="text-end">Ações</th>
      </tr></thead>
    </table>
  </div>
</div>

<div class="modal fade" id="notifDetailsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Detalhes da notificação</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3" id="notif-details-body"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Fechar</button>
        <a href="#" id="notif-details-show" class="btn btn-primary">
          <i class="icon-base ti tabler-external-link me-1"></i> Página completa
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
  const baseUrl = "{{ url('/painel/notificacoes') }}";

  $('#filtro-status, #filtro-canal').select2({ allowClear: true, placeholder: function(){return $(this).data('placeholder')||'';}, width:'100%' });

  const dt = new DataTable('.datatables-notif', {
    processing: true, serverSide: true, responsive: true,
    ajax: {
      url: baseUrl + '/datatable',
      data: function (d) {
        d.status = $('#filtro-status').val();
        d.channel = $('#filtro-canal').val();
        d.de = $('#filtro-de').val();
        d.ate = $('#filtro-ate').val();
      },
    },
    columns: [
      { data: 'destinatario_cell', responsivePriority: 1 },
      { data: 'channel_cell',      responsivePriority: 2 },
      { data: 'status_badge',      responsivePriority: 1 },
      { data: 'subject_cell',      responsivePriority: 3 },
      { data: 'data_cell',         responsivePriority: 4, className: 'text-nowrap' },
      {
        data: 'actions',
        responsivePriority: 1, orderable: false, searchable: false, className: 'text-end text-nowrap',
        render: id => `
          <div class="d-inline-flex flex-nowrap gap-1 justify-content-end">
            <button class="btn btn-sm btn-icon view-notif" data-id="${id}" title="Detalhes"><i class="icon-base ti tabler-eye icon-22px"></i></button>
            <a href="${baseUrl}/${id}" class="btn btn-sm btn-icon" title="Página completa"><i class="icon-base ti tabler-external-link icon-22px"></i></a>
            <button class="btn btn-sm btn-icon resend-notif" data-id="${id}" title="Reenviar"><i class="icon-base ti tabler-send icon-22px text-primary"></i></button>
            <button class="btn btn-sm btn-icon cancel-notif" data-id="${id}" title="Cancelar"><i class="icon-base ti tabler-x icon-22px text-danger"></i></button>
          </div>`,
      },
    ],
    order: [[4, 'desc']],
    language: { processing: 'Carregando...', search: 'Buscar:', lengthMenu: '_MENU_ por página', info: 'Exibindo _START_ a _END_ de _TOTAL_', infoEmpty: 'Nenhum registro', zeroRecords: 'Nenhuma notificação encontrada', emptyTable: 'Nenhuma notificação na fila', paginate: { first: '«', previous: '‹', next: '›', last: '»' } },
    layout: { topStart: { features: [{ pageLength: { menu: [10, 25, 50] } }] }, topEnd: { features: [{ search: { placeholder: 'Buscar destinatário/assunto' } }] } },
  });

  $('#filtro-status, #filtro-canal').on('change', () => dt.draw());
  $('#filtro-de, #filtro-ate').on('change', () => dt.draw());
  $('#btn-limpar-filtros').on('click', () => {
    $('#filtro-status, #filtro-canal').val(null).trigger('change');
    $('#filtro-de, #filtro-ate').val('');
    dt.draw();
  });

  function renderField(label, value, opts = {}) {
    if (value === null || value === undefined || value === '') value = '<span class="text-muted">—</span>';
    return `
      <div class="col-md-6 ${opts.full ? 'col-md-12' : ''}">
        <div class="text-muted small text-uppercase mb-1" style="font-size:.72rem;letter-spacing:.05em;">${label}</div>
        <div>${value}</div>
      </div>`;
  }

  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.view-notif');
    if (! btn) return;
    const row = dt.row(btn.closest('tr')).data();
    if (! row) return;
    const d = row.details;
    document.getElementById('notif-details-show').href = `${baseUrl}/${row.actions}`;
    document.getElementById('notif-details-body').innerHTML = [
      renderField('Canal', d.channel),
      renderField('Status', d.status),
      renderField('Destinatário', d.to),
      renderField('Usuário', d.user_name),
      renderField('Assunto', d.subject, { full: true }),
      renderField('Tentativas', d.attempts),
      renderField('Próxima tentativa', d.next_attempt_at),
      renderField('Enviada em', d.sent_at),
      renderField('Criada em', d.created_at),
      renderField('Último erro', d.last_error ? `<pre class="small mb-0">${d.last_error}</pre>` : null, { full: true }),
      renderField('Corpo', d.body ? `<pre class="small mb-0" style="max-height:200px;overflow:auto;white-space:pre-wrap;">${d.body.replace(/</g,'&lt;')}</pre>` : null, { full: true }),
    ].join('');
    new bootstrap.Modal(document.getElementById('notifDetailsModal')).show();
  });

  document.addEventListener('click', function (e) {
    const rBtn = e.target.closest('.resend-notif');
    if (rBtn) confirmAction('Reenviar agora?', `${baseUrl}/${rBtn.dataset.id}/resend`);
    const cBtn = e.target.closest('.cancel-notif');
    if (cBtn) confirmAction('Cancelar notificação?', `${baseUrl}/${cBtn.dataset.id}/cancel`);
  });

  function confirmAction(title, url) {
    Swal.fire({
      title, icon: 'question', showCancelButton: true,
      confirmButtonText: 'Sim', cancelButtonText: 'Não',
      customClass: { confirmButton: 'btn btn-primary me-3', cancelButton: 'btn btn-label-secondary' },
      buttonsStyling: false,
    }).then(r => {
      if (!r.value) return;
      fetch(url, { method: 'PATCH', headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' } })
        .then(r => r.json().then(b => ({ ok: r.ok, body: b })))
        .then(({ ok, body }) => {
          if (!ok) throw new Error(body.message);
          dt.draw(false);
          Swal.fire({ icon: 'success', title: 'Pronto', text: body.message, timer: 1800, showConfirmButton: false });
        })
        .catch(err => Swal.fire({ icon: 'error', title: 'Erro', text: err.message, customClass: { confirmButton: 'btn btn-danger' }, buttonsStyling: false }));
    });
  }
});
</script>
@endsection
