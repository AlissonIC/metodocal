@extends('layouts/layoutMaster')

@section('title', 'Usuários')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
'resources/assets/vendor/libs/select2/select2.scss',
'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
'resources/assets/vendor/libs/select2/select2.js',
'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
'resources/assets/vendor/libs/cleave-zen/cleave-zen.js'])
@endsection

@section('content')
<div class="card">
  <div class="card-header border-bottom">
    <h5 class="card-title mb-0">Usuários</h5>
    <p class="text-muted mb-0 small">Gerenciamento de contas de admin, mentorados e licenciados.</p>
  </div>
  <div class="card-datatable">
    <table class="datatables-users table border-top">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nome</th>
          <th>E-mail</th>
          <th>Nível</th>
          <th>Plano</th>
          <th>Status</th>
          <th>Ações</th>
        </tr>
      </thead>
    </table>
  </div>

  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasUser">
    <div class="offcanvas-header border-bottom">
      <h5 id="offcanvasUserLabel" class="offcanvas-title">Novo Usuário</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-6">
      <form id="userForm" class="pt-0">
        @csrf
        <input type="hidden" name="id" id="user_id">

        <div class="mb-6">
          <label class="form-label" for="user_name">Nome</label>
          <input type="text" class="form-control" id="user_name" name="name">
        </div>

        <div class="mb-6">
          <label class="form-label" for="user_email">E-mail</label>
          <input type="email" class="form-control" id="user_email" name="email">
        </div>

        <div class="row">
          <div class="mb-6 col-md-6">
            <label class="form-label" for="user_phone">Telefone</label>
            <input type="text" class="form-control mask-phone" id="user_phone" name="phone" placeholder="(11) 99999-9999">
          </div>
          <div class="mb-6 col-md-6">
            <label class="form-label" for="user_cpf_cnpj">CPF / CNPJ</label>
            <input type="text" class="form-control mask-cpf-cnpj" id="user_cpf_cnpj" name="cpf_cnpj" placeholder="000.000.000-00">
          </div>
        </div>

        <div class="row">
          <div class="mb-6 col-md-6">
            <label class="form-label" for="user_role">Nível</label>
            <select id="user_role" name="role" class="form-select">
              <option value="mentorado">Mentorado</option>
              <option value="licenciado">Licenciado</option>
              <option value="admin">Admin</option>
            </select>
          </div>
          <div class="mb-6 col-md-6">
            <label class="form-label" for="user_status">Status</label>
            <select id="user_status" name="status" class="form-select">
              <option value="ativo">Ativo</option>
              <option value="inativo">Inativo</option>
              <option value="bloqueado">Bloqueado</option>
            </select>
          </div>
        </div>

        <div class="mb-6">
          <label class="form-label" for="user_plan_id">Plano (opcional)</label>
          <select id="user_plan_id" name="plan_id" class="select2 form-select">
            <option value="">Sem plano</option>
            @foreach ($plans as $p)
              <option value="{{ $p->id }}" data-tipo="{{ $p->tipo }}">{{ $p->nome }} ({{ ucfirst($p->tipo) }})</option>
            @endforeach
          </select>
          <small class="text-muted">Atribuir um plano cria uma assinatura ativa imediatamente.</small>
        </div>

        <div class="mb-6">
          <label class="form-label" for="user_password">Senha <span id="passwordHint" class="text-muted small"></span></label>
          <input type="password" class="form-control" id="user_password" name="password" autocomplete="new-password">
        </div>

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
  const baseUrl = "{{ url('/painel/usuarios') }}";
  const offcanvasEl = document.getElementById('offcanvasUser');
  const offcanvas = new bootstrap.Offcanvas(offcanvasEl);

  const $plan = $('#user_plan_id');
  $plan.wrap('<div class="position-relative"></div>').select2({
    placeholder: 'Selecione...',
    allowClear: true,
    dropdownParent: $plan.parent(),
  });

  const dt = new DataTable('.datatables-users', {
    processing: true,
    serverSide: true,
    ajax: { url: baseUrl + '/datatable' },
    columns: [
      { data: 'id' },
      { data: 'name' },
      { data: 'email' },
      { data: 'role' },
      { data: 'plano' },
      { data: 'status_badge' },
      {
        data: 'actions',
        orderable: false,
        searchable: false,
        render: id => `
          <button class="btn btn-sm btn-icon edit-user" data-id="${id}">
            <i class="icon-base ti tabler-edit icon-22px"></i>
          </button>
          <button class="btn btn-sm btn-icon delete-user text-danger" data-id="${id}">
            <i class="icon-base ti tabler-trash icon-22px"></i>
          </button>`
      }
    ],
    order: [[0, 'desc']],
    language: {
      processing: 'Carregando...',
      search: 'Buscar:',
      lengthMenu: '_MENU_ por página',
      info: 'Exibindo _START_ a _END_ de _TOTAL_',
      infoEmpty: 'Nenhum registro',
      zeroRecords: 'Nenhum usuário encontrado',
      emptyTable: 'Nenhum usuário cadastrado',
      paginate: { first: '«', previous: '‹', next: '›', last: '»' }
    },
    layout: {
      topStart: { features: [{ pageLength: { menu: [10, 25, 50] } }] },
      topEnd: {
        features: [
          { search: { placeholder: 'Buscar usuário' } },
          {
            buttons: [{
              text: '<i class="ti tabler-plus me-1"></i> Novo Usuário',
              className: 'btn btn-primary add-new-user'
            }]
          }
        ]
      }
    }
  });

  document.addEventListener('click', function (e) {
    if (e.target.closest('.add-new-user')) {
      resetForm();
      document.getElementById('offcanvasUserLabel').textContent = 'Novo Usuário';
      document.getElementById('passwordHint').textContent = '';
      offcanvas.show();
    }
    const editBtn = e.target.closest('.edit-user');
    if (editBtn) {
      fetch(`${baseUrl}/${editBtn.dataset.id}`, { headers: { Accept: 'application/json' } })
        .then(r => r.json())
        .then(u => {
          resetForm();
          document.getElementById('offcanvasUserLabel').textContent = 'Editar Usuário';
          document.getElementById('passwordHint').textContent = '(deixe em branco para manter)';
          document.getElementById('user_id').value = u.id;
          document.getElementById('user_name').value = u.name || '';
          document.getElementById('user_email').value = u.email || '';
          document.getElementById('user_phone').value = u.phone || '';
          document.getElementById('user_cpf_cnpj').value = u.cpf_cnpj || '';
          document.getElementById('user_role').value = u.role || 'mentorado';
          document.getElementById('user_status').value = u.status || 'ativo';
          $plan.val(u.plan_id ? String(u.plan_id) : '').trigger('change');
          offcanvas.show();
          document.dispatchEvent(new Event('mask:refresh'));
        });
    }
    const delBtn = e.target.closest('.delete-user');
    if (delBtn) {
      Swal.fire({
        title: 'Excluir usuário?',
        text: 'Esta ação não pode ser desfeita.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sim, excluir',
        cancelButtonText: 'Cancelar',
        customClass: { confirmButton: 'btn btn-danger me-3', cancelButton: 'btn btn-label-secondary' },
        buttonsStyling: false
      }).then(r => {
        if (!r.value) return;
        fetch(`${baseUrl}/${delBtn.dataset.id}`, {
          method: 'DELETE',
          headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' }
        })
          .then(r => r.json().then(b => ({ ok: r.ok, body: b })))
          .then(({ ok, body }) => {
            if (!ok) throw new Error(body.message || 'Erro');
            dt.draw(false);
            Swal.fire({ icon: 'success', title: 'Excluído', text: body.message, customClass: { confirmButton: 'btn btn-success' }, buttonsStyling: false });
          })
          .catch(err => Swal.fire({ icon: 'error', title: 'Erro', text: err.message, customClass: { confirmButton: 'btn btn-danger' }, buttonsStyling: false }));
      });
    }
  });

  document.getElementById('userForm').addEventListener('submit', function (e) {
    e.preventDefault();
    const id = document.getElementById('user_id').value;
    const url = id ? `${baseUrl}/${id}` : baseUrl;
    const method = id ? 'PATCH' : 'POST';
    const fd = new FormData(this);
    const payload = {};
    fd.forEach((v, k) => { if (k !== '_token' && k !== 'id') payload[k] = v; });
    if (!payload.password) delete payload.password;
    if (!payload.plan_id) payload.plan_id = null;

    fetch(url, {
      method,
      headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', Accept: 'application/json' },
      body: JSON.stringify(payload)
    })
      .then(r => r.json().then(b => ({ ok: r.ok, body: b })))
      .then(({ ok, body }) => {
        if (!ok) {
          let msg = body.message || 'Erro';
          if (body.errors) msg = Object.values(body.errors).flat().join('\n');
          throw new Error(msg);
        }
        offcanvas.hide();
        dt.draw(false);
        Swal.fire({ icon: 'success', title: 'Sucesso', text: body.message, customClass: { confirmButton: 'btn btn-success' }, buttonsStyling: false });
      })
      .catch(err => Swal.fire({ icon: 'error', title: 'Erro', text: err.message, customClass: { confirmButton: 'btn btn-danger' }, buttonsStyling: false }));
  });

  function resetForm() {
    document.getElementById('userForm').reset();
    document.getElementById('user_id').value = '';
    $plan.val('').trigger('change');
  }
});
</script>
@include('_partials._masks-script')
@endsection
