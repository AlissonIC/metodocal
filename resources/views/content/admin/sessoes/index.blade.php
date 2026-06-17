@extends('layouts/layoutMaster')

@section('title', 'Sessões')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
'resources/assets/vendor/libs/select2/select2.scss',
'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
'resources/assets/vendor/libs/select2/select2.js',
'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('content')
<div class="card">
  <div class="card-header border-bottom">
    <h5 class="card-title mb-0">Sessões agendadas</h5>
  </div>
  <div class="card-datatable">
    <table class="datatables-sessoes table border-top">
      <thead><tr><th>ID</th><th>Mentorado</th><th>Título</th><th>Quando</th><th>Duração</th><th>Status</th><th>Ações</th></tr></thead>
    </table>
  </div>

  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasSes">
    <div class="offcanvas-header border-bottom">
      <h5 id="offcanvasSesLabel" class="offcanvas-title">Nova Sessão</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-6">
      <form id="sesForm" class="pt-0">
        @csrf
        <input type="hidden" name="id" id="ses_id">
        <div class="mb-6">
          <label class="form-label">Mentorado</label>
          <select class="select2 form-select" name="user_id">
            <option value="">Selecione...</option>
            @foreach ($mentorados as $m)<option value="{{ $m->id }}">{{ $m->name }}</option>@endforeach
          </select>
        </div>
        <div class="mb-6"><label class="form-label">Título</label><input class="form-control" name="titulo" required></div>
        <div class="mb-6"><label class="form-label">Descrição</label><textarea class="form-control" name="descricao" rows="2"></textarea></div>
        <div class="row">
          <div class="mb-6 col-md-7"><label class="form-label">Data e hora</label><input type="datetime-local" class="form-control" name="scheduled_at" required></div>
          <div class="mb-6 col-md-5"><label class="form-label">Duração (min)</label><input type="number" min="15" max="480" class="form-control" name="duracao_minutos" value="60"></div>
        </div>
        <div class="mb-6"><label class="form-label">Link da reunião</label><input type="url" class="form-control" name="link_reuniao" placeholder="https://meet..."></div>
        <div class="mb-6"><label class="form-label">Status</label>
          <select class="form-select" name="status">
            <option value="agendada">Agendada</option><option value="concluida">Concluída</option><option value="cancelada">Cancelada</option>
          </select>
        </div>
        <div class="mb-6"><label class="form-label">Notas</label><textarea class="form-control" name="notas" rows="2"></textarea></div>
        <button type="submit" class="btn btn-primary me-3">Salvar</button>
        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="offcanvas">Cancelar</button>
      </form>
    </div>
  </div>
</div>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const csrf = document.querySelector('meta[name="csrf-token"]').content;
  const baseUrl = "{{ url('/painel/sessoes') }}";
  const offcanvas = new bootstrap.Offcanvas(document.getElementById('offcanvasSes'));
  const $u = $('#sesForm [name="user_id"]');
  $u.wrap('<div class="position-relative"></div>').select2({ placeholder: 'Selecione...', dropdownParent: $u.parent() });

  const dt = new DataTable('.datatables-sessoes', {
    processing: true, serverSide: true,
    ajax: { url: baseUrl + '/datatable' },
    columns: [
      { data: 'id' }, { data: 'user_name' }, { data: 'titulo' },
      { data: 'scheduled_formatado' }, { data: 'duracao_minutos', render: v => v + ' min' },
      { data: 'status_badge' },
      { data: 'id', orderable: false, searchable: false, render: id => `
        <button class="btn btn-sm btn-icon edit-ses" data-id="${id}"><i class="ti tabler-edit icon-22px"></i></button>
        <button class="btn btn-sm btn-icon delete-ses text-danger" data-id="${id}"><i class="ti tabler-trash icon-22px"></i></button>` }
    ],
    order: [[3, 'desc']],
    language: { processing: 'Carregando...', search: 'Buscar:', lengthMenu: '_MENU_', info: '_START_-_END_ de _TOTAL_', infoEmpty: '0', zeroRecords: 'Nenhuma sessão', emptyTable: 'Nenhuma sessão', paginate: { first: '«', previous: '‹', next: '›', last: '»' } },
    layout: { topStart: { features: [{ pageLength: { menu: [10, 25, 50] } }] }, topEnd: { features: [{ search: { placeholder: 'Buscar' } }, { buttons: [{ text: '<i class="ti tabler-plus me-1"></i> Nova Sessão', className: 'btn btn-primary add-new-ses' }] }] } }
  });

  document.addEventListener('click', function (e) {
    if (e.target.closest('.add-new-ses')) { resetForm(); document.getElementById('offcanvasSesLabel').textContent = 'Nova Sessão'; offcanvas.show(); }
    const editBtn = e.target.closest('.edit-ses');
    if (editBtn) fetch(`${baseUrl}/${editBtn.dataset.id}`).then(r => r.json()).then(s => {
      resetForm(); document.getElementById('offcanvasSesLabel').textContent = 'Editar Sessão';
      document.getElementById('ses_id').value = s.id;
      $u.val(s.user_id).trigger('change');
      for (const k of ['titulo','descricao','scheduled_at','duracao_minutos','link_reuniao','status','notas']) {
        const el = document.querySelector(`#sesForm [name="${k}"]`);
        if (el) el.value = s[k] || '';
      }
      offcanvas.show();
    });
    const delBtn = e.target.closest('.delete-ses');
    if (delBtn) Swal.fire({ title: 'Excluir sessão?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Sim', cancelButtonText: 'Não', customClass: { confirmButton: 'btn btn-danger me-3', cancelButton: 'btn btn-label-secondary' }, buttonsStyling: false }).then(r => {
      if (!r.value) return;
      fetch(`${baseUrl}/${delBtn.dataset.id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' } })
        .then(r => r.json()).then(b => { dt.draw(false); Swal.fire({ icon: 'success', title: 'Removida', text: b.message, customClass: { confirmButton: 'btn btn-success' }, buttonsStyling: false }); });
    });
  });

  document.getElementById('sesForm').addEventListener('submit', function (e) {
    e.preventDefault();
    const id = document.getElementById('ses_id').value;
    const url = id ? `${baseUrl}/${id}` : baseUrl;
    const fd = new FormData(this); const payload = {};
    fd.forEach((v, k) => { if (k !== '_token' && k !== 'id') payload[k] = v; });
    fetch(url, { method: id ? 'PATCH' : 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', Accept: 'application/json' }, body: JSON.stringify(payload) })
      .then(r => r.json().then(b => ({ ok: r.ok, body: b })))
      .then(({ ok, body }) => {
        if (!ok) { const msg = body.errors ? Object.values(body.errors).flat().join('\n') : body.message; throw new Error(msg); }
        offcanvas.hide(); dt.draw(false);
        Swal.fire({ icon: 'success', title: 'Sucesso', text: body.message, customClass: { confirmButton: 'btn btn-success' }, buttonsStyling: false });
      })
      .catch(err => Swal.fire({ icon: 'error', title: 'Erro', text: err.message, customClass: { confirmButton: 'btn btn-danger' }, buttonsStyling: false }));
  });

  function resetForm() {
    document.getElementById('sesForm').reset();
    document.getElementById('ses_id').value = '';
    $u.val('').trigger('change');
  }
});
</script>
@endsection
