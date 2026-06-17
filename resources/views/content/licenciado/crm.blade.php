@extends('layouts/layoutMaster')

@section('title', 'CRM de Clientes')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
'resources/assets/vendor/libs/cleave-zen/cleave-zen.js'])
@endsection

@section('content')
<div class="card">
  <div class="card-header border-bottom">
    <h5 class="card-title mb-0">Meus Clientes</h5>
    <p class="text-muted mb-0 small">Cadastre e acompanhe seus leads e clientes ativos.</p>
  </div>
  <div class="card-datatable">
    <table class="datatables-crm table border-top">
      <thead><tr><th>ID</th><th>Nome</th><th>E-mail</th><th>Telefone</th><th>Status</th><th>Ações</th></tr></thead>
    </table>
  </div>

  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasCli">
    <div class="offcanvas-header border-bottom">
      <h5 id="offcanvasCliLabel" class="offcanvas-title">Novo Cliente</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-6">
      <form id="cliForm" class="pt-0">
        @csrf
        <input type="hidden" name="id" id="cli_id">
        <div class="mb-6"><label class="form-label">Nome</label><input type="text" class="form-control" name="nome" required></div>
        <div class="mb-6"><label class="form-label">E-mail</label><input type="email" class="form-control" name="email"></div>
        <div class="row">
          <div class="mb-6 col-md-6"><label class="form-label">Telefone</label><input type="text" class="form-control mask-phone" name="telefone" placeholder="(11) 99999-9999"></div>
          <div class="mb-6 col-md-6"><label class="form-label">CPF/CNPJ</label><input type="text" class="form-control mask-cpf-cnpj" name="cpf_cnpj" placeholder="000.000.000-00"></div>
        </div>
        <div class="mb-6"><label class="form-label">Endereço</label><textarea class="form-control" name="endereco" rows="2"></textarea></div>
        <div class="mb-6"><label class="form-label">Status</label>
          <select class="form-select" name="status">
            <option value="lead">Lead</option>
            <option value="ativo">Ativo</option>
            <option value="perdido">Perdido</option>
          </select>
        </div>
        <div class="mb-6"><label class="form-label">Notas</label><textarea class="form-control" name="notas" rows="3"></textarea></div>
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
  const baseUrl = "{{ url('/painel/crm') }}";
  const offcanvas = new bootstrap.Offcanvas(document.getElementById('offcanvasCli'));

  const dt = new DataTable('.datatables-crm', {
    processing: true, serverSide: true,
    ajax: { url: baseUrl + '/datatable' },
    columns: [
      { data: 'id' }, { data: 'nome' }, { data: 'email', render: v => v || '—' },
      { data: 'telefone', render: v => v || '—' }, { data: 'status_badge' },
      { data: 'actions', orderable: false, searchable: false, render: id => `
        <button class="btn btn-sm btn-icon edit-cli" data-id="${id}"><i class="ti tabler-edit icon-22px"></i></button>
        <button class="btn btn-sm btn-icon delete-cli text-danger" data-id="${id}"><i class="ti tabler-trash icon-22px"></i></button>` }
    ],
    order: [[0, 'desc']],
    language: { processing: 'Carregando...', search: 'Buscar:', lengthMenu: '_MENU_', info: '_START_-_END_ de _TOTAL_', infoEmpty: '0', zeroRecords: 'Nenhum cliente', emptyTable: 'Nenhum cliente cadastrado', paginate: { first: '«', previous: '‹', next: '›', last: '»' } },
    layout: {
      topStart: { features: [{ pageLength: { menu: [10, 25, 50] } }] },
      topEnd: { features: [{ search: { placeholder: 'Buscar' } }, { buttons: [{ text: '<i class="ti tabler-plus me-1"></i> Novo Cliente', className: 'btn btn-primary add-new-cli' }] }] }
    }
  });

  document.addEventListener('click', function (e) {
    if (e.target.closest('.add-new-cli')) {
      document.getElementById('cliForm').reset();
      document.getElementById('cli_id').value = '';
      document.getElementById('offcanvasCliLabel').textContent = 'Novo Cliente';
      offcanvas.show();
    }
    const editBtn = e.target.closest('.edit-cli');
    if (editBtn) {
      fetch(`${baseUrl}/${editBtn.dataset.id}`, { headers: { Accept: 'application/json' } })
        .then(r => r.json()).then(c => {
          document.getElementById('cliForm').reset();
          document.getElementById('offcanvasCliLabel').textContent = 'Editar Cliente';
          for (const k of ['id','nome','email','telefone','cpf_cnpj','endereco','status','notas']) {
            const el = document.querySelector(`#cliForm [name="${k}"], #cli_${k}`);
            if (el) el.value = c[k] || '';
          }
          document.getElementById('cli_id').value = c.id;
          offcanvas.show();
          document.dispatchEvent(new Event('mask:refresh'));
        });
    }
    const delBtn = e.target.closest('.delete-cli');
    if (delBtn) {
      Swal.fire({ title: 'Excluir cliente?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Sim', cancelButtonText: 'Não', customClass: { confirmButton: 'btn btn-danger me-3', cancelButton: 'btn btn-label-secondary' }, buttonsStyling: false }).then(r => {
        if (!r.value) return;
        fetch(`${baseUrl}/${delBtn.dataset.id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' } })
          .then(r => r.json().then(b => ({ ok: r.ok, body: b })))
          .then(({ ok, body }) => { if (!ok) throw new Error(body.message); dt.draw(false); Swal.fire({ icon: 'success', title: 'Removido', text: body.message, customClass: { confirmButton: 'btn btn-success' }, buttonsStyling: false }); })
          .catch(err => Swal.fire({ icon: 'error', title: 'Erro', text: err.message, customClass: { confirmButton: 'btn btn-danger' }, buttonsStyling: false }));
      });
    }
  });

  document.addEventListener('click', function (e) {
    if (e.target.closest('.add-new-cli')) {
      document.dispatchEvent(new Event('mask:refresh'));
    }
  });

  document.getElementById('cliForm').addEventListener('submit', function (e) {
    e.preventDefault();
    const id = document.getElementById('cli_id').value;
    const url = id ? `${baseUrl}/${id}` : baseUrl;
    const method = id ? 'PATCH' : 'POST';
    const fd = new FormData(this); const payload = {};
    fd.forEach((v, k) => { if (k !== '_token' && k !== 'id') payload[k] = v; });
    fetch(url, { method, headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', Accept: 'application/json' }, body: JSON.stringify(payload) })
      .then(r => r.json().then(b => ({ ok: r.ok, body: b })))
      .then(({ ok, body }) => {
        if (!ok) { const msg = body.errors ? Object.values(body.errors).flat().join('\n') : body.message; throw new Error(msg); }
        offcanvas.hide(); dt.draw(false);
        Swal.fire({ icon: 'success', title: 'Sucesso', text: body.message, customClass: { confirmButton: 'btn btn-success' }, buttonsStyling: false });
      })
      .catch(err => Swal.fire({ icon: 'error', title: 'Erro', text: err.message, customClass: { confirmButton: 'btn btn-danger' }, buttonsStyling: false }));
  });
});
</script>
@include('_partials._masks-script')
@endsection
