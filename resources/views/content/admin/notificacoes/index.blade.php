@extends('layouts/layoutMaster')

@section('title', 'Notificações')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
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
  <div class="card-datatable">
    <table class="datatables-notif table border-top">
      <thead><tr>
        <th style="width:60px;">ID</th>
        <th>Canal</th>
        <th>Destinatário</th>
        <th>Assunto</th>
        <th>Quando</th>
        <th>Status</th>
        <th style="width:140px;">Ações</th>
      </tr></thead>
    </table>
  </div>
</div>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const csrf = document.querySelector('meta[name="csrf-token"]').content;
  const baseUrl = "{{ url('/painel/notificacoes') }}";

  const dt = new DataTable('.datatables-notif', {
    processing: true, serverSide: true,
    ajax: { url: baseUrl + '/datatable' },
    columns: [
      { data: 'id', className: 'text-muted' },
      { data: 'channel_cell' },
      { data: 'destinatario_cell' },
      { data: 'subject_cell' },
      { data: 'data_cell' },
      { data: 'status_badge' },
      {
        data: 'id', orderable: false, searchable: false,
        render: id => `
          <div class="d-inline-flex align-items-center gap-1">
            <a href="${baseUrl}/${id}" class="btn btn-sm btn-icon btn-text-secondary rounded-pill" title="Ver detalhes"><i class="ti tabler-eye icon-20px"></i></a>
            <button class="btn btn-sm btn-icon btn-text-secondary rounded-pill resend-notif" data-id="${id}" title="Reenviar"><i class="ti tabler-send icon-20px text-primary"></i></button>
            <button class="btn btn-sm btn-icon btn-text-secondary rounded-pill cancel-notif" data-id="${id}" title="Cancelar"><i class="ti tabler-x icon-20px text-danger"></i></button>
          </div>`
      }
    ],
    order: [[0, 'desc']],
    language: { processing: 'Carregando...', search: 'Buscar:', lengthMenu: '_MENU_', info: '_START_-_END_ de _TOTAL_', infoEmpty: '0', zeroRecords: 'Nenhuma notificação', emptyTable: 'Nenhuma notificação na fila', paginate: { first: '«', previous: '‹', next: '›', last: '»' } },
    layout: { topStart: { features: [{ pageLength: { menu: [10, 25, 50] } }] }, topEnd: { features: [{ search: { placeholder: 'Buscar' } }] } }
  });

  document.addEventListener('click', function (e) {
    const rBtn = e.target.closest('.resend-notif');
    if (rBtn) confirm('Reenviar agora?', `${baseUrl}/${rBtn.dataset.id}/resend`);
    const cBtn = e.target.closest('.cancel-notif');
    if (cBtn) confirm('Cancelar notificação?', `${baseUrl}/${cBtn.dataset.id}/cancel`);
  });

  function confirm(title, url) {
    Swal.fire({
      title, icon: 'question', showCancelButton: true,
      confirmButtonText: 'Sim', cancelButtonText: 'Não',
      customClass: { confirmButton: 'btn btn-primary me-3', cancelButton: 'btn btn-label-secondary' },
      buttonsStyling: false
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
