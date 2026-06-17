@extends('layouts/layoutMaster')

@section('title', 'Planos')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
'resources/assets/vendor/libs/select2/select2.scss',
'resources/assets/vendor/libs/@form-validation/form-validation.scss',
'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
'resources/assets/vendor/libs/select2/select2.js',
'resources/assets/vendor/libs/@form-validation/popular.js',
'resources/assets/vendor/libs/@form-validation/bootstrap5.js',
'resources/assets/vendor/libs/@form-validation/auto-focus.js',
'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('content')
<div class="card">
  <div class="card-header border-bottom">
    <h5 class="card-title mb-0">Planos</h5>
    <p class="text-muted mb-0 small">Cadastros de planos contratáveis por mentorados e licenciados.</p>
  </div>
  <div class="card-datatable">
    <table class="datatables-plans table border-top">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nome</th>
          <th>Tipo</th>
          <th>Preço</th>
          <th>Recorrência</th>
          <th>Ativas</th>
          <th>Status</th>
          <th>Ações</th>
        </tr>
      </thead>
    </table>
  </div>

  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasPlan" aria-labelledby="offcanvasPlanLabel">
    <div class="offcanvas-header border-bottom">
      <h5 id="offcanvasPlanLabel" class="offcanvas-title">Novo Plano</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-6">
      <form id="planForm" class="pt-0">
        @csrf
        <input type="hidden" name="id" id="plan_id">

        <div class="mb-6">
          <label class="form-label" for="plan_nome">Nome</label>
          <input type="text" class="form-control" id="plan_nome" name="nome" placeholder="Mentoria Premium">
        </div>

        <div class="mb-6">
          <label class="form-label" for="plan_tipo">Tipo</label>
          <select id="plan_tipo" name="tipo" class="form-select">
            <option value="mentorado">Mentorado</option>
            <option value="licenciado">Licenciado</option>
          </select>
        </div>

        <div class="row">
          <div class="mb-6 col-md-6">
            <label class="form-label" for="plan_preco">Preço (R$)</label>
            <input type="number" step="0.01" min="0" class="form-control" id="plan_preco" name="preco" placeholder="0.00">
          </div>
          <div class="mb-6 col-md-6">
            <label class="form-label" for="plan_recorrencia">Recorrência</label>
            <select id="plan_recorrencia" name="recorrencia" class="form-select">
              <option value="mensal">Mensal</option>
              <option value="anual">Anual</option>
              <option value="vitalicio">Vitalício</option>
            </select>
          </div>
        </div>

        <div class="mb-6">
          <label class="form-label" for="plan_descricao">Descrição</label>
          <textarea class="form-control" id="plan_descricao" name="descricao" rows="2"></textarea>
        </div>

        <div class="mb-6">
          <label class="form-label" for="plan_permissions">Módulos liberados</label>
          <select id="plan_permissions" name="permissions[]" class="select2 form-select" multiple>
            @foreach ($permissions as $perm)
              <option value="{{ $perm }}">{{ $perm }}</option>
            @endforeach
          </select>
          <small class="text-muted">Estes módulos serão liberados para o usuário quando a assinatura estiver ativa.</small>
        </div>

        <div class="mb-6 form-check form-switch">
          <input class="form-check-input" type="checkbox" id="plan_ativo" name="ativo" value="1" checked>
          <label class="form-check-label" for="plan_ativo">Plano ativo</label>
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
  const baseUrl = "{{ url('/painel/planos') }}";
  const $perm = $('#plan_permissions');
  const offcanvasEl = document.getElementById('offcanvasPlan');
  const offcanvas = new bootstrap.Offcanvas(offcanvasEl);

  $perm.wrap('<div class="position-relative"></div>').select2({
    placeholder: 'Selecione os módulos',
    dropdownParent: $perm.parent(),
  });

  const dt = new DataTable('.datatables-plans', {
    processing: true,
    serverSide: true,
    ajax: { url: baseUrl + '/datatable' },
    columns: [
      { data: 'id' },
      { data: 'nome' },
      { data: 'tipo_label' },
      { data: 'preco_formatado' },
      { data: 'recorrencia_label' },
      { data: 'ativas_count' },
      { data: 'status_badge' },
      {
        data: 'actions',
        orderable: false,
        searchable: false,
        render: function (id) {
          return `
            <button class="btn btn-sm btn-icon edit-plan" data-id="${id}">
              <i class="icon-base ti tabler-edit icon-22px"></i>
            </button>
            <button class="btn btn-sm btn-icon delete-plan text-danger" data-id="${id}">
              <i class="icon-base ti tabler-trash icon-22px"></i>
            </button>`;
        }
      }
    ],
    order: [[0, 'desc']],
    language: {
      processing: 'Carregando...',
      search: 'Buscar:',
      lengthMenu: '_MENU_ por página',
      info: 'Exibindo _START_ a _END_ de _TOTAL_',
      infoEmpty: 'Nenhum registro',
      infoFiltered: '(filtrado de _MAX_)',
      zeroRecords: 'Nenhum plano encontrado',
      emptyTable: 'Nenhum plano cadastrado',
      paginate: { first: '«', previous: '‹', next: '›', last: '»' }
    },
    layout: {
      topStart: { features: [{ pageLength: { menu: [10, 25, 50] } }] },
      topEnd: {
        features: [
          { search: { placeholder: 'Buscar plano' } },
          {
            buttons: [{
              text: '<i class="ti tabler-plus me-1"></i> Novo Plano',
              className: 'btn btn-primary add-new-plan'
            }]
          }
        ]
      }
    }
  });

  document.addEventListener('click', function (e) {
    const addBtn = e.target.closest('.add-new-plan');
    if (addBtn) { resetForm(); document.getElementById('offcanvasPlanLabel').textContent = 'Novo Plano'; offcanvas.show(); }

    const editBtn = e.target.closest('.edit-plan');
    if (editBtn) {
      const id = editBtn.dataset.id;
      fetch(`${baseUrl}/${id}`, { headers: { Accept: 'application/json' } })
        .then(r => r.json())
        .then(p => {
          resetForm();
          document.getElementById('offcanvasPlanLabel').textContent = 'Editar Plano';
          document.getElementById('plan_id').value = p.id;
          document.getElementById('plan_nome').value = p.nome;
          document.getElementById('plan_tipo').value = p.tipo;
          document.getElementById('plan_preco').value = p.preco;
          document.getElementById('plan_recorrencia').value = p.recorrencia;
          document.getElementById('plan_descricao').value = p.descricao || '';
          document.getElementById('plan_ativo').checked = !!p.ativo;
          $perm.val(p.permissions || []).trigger('change');
          offcanvas.show();
        });
    }

    const delBtn = e.target.closest('.delete-plan');
    if (delBtn) {
      const id = delBtn.dataset.id;
      Swal.fire({
        title: 'Excluir plano?',
        text: 'Esta ação não pode ser desfeita.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sim, excluir',
        cancelButtonText: 'Cancelar',
        customClass: { confirmButton: 'btn btn-danger me-3', cancelButton: 'btn btn-label-secondary' },
        buttonsStyling: false
      }).then(result => {
        if (!result.value) return;
        fetch(`${baseUrl}/${id}`, {
          method: 'DELETE',
          headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' }
        })
          .then(r => r.json().then(b => ({ ok: r.ok, body: b })))
          .then(({ ok, body }) => {
            if (!ok) throw new Error(body.message || 'Erro ao excluir');
            dt.draw(false);
            Swal.fire({ icon: 'success', title: 'Excluído', text: body.message, customClass: { confirmButton: 'btn btn-success' }, buttonsStyling: false });
          })
          .catch(err => Swal.fire({ icon: 'error', title: 'Erro', text: err.message, customClass: { confirmButton: 'btn btn-danger' }, buttonsStyling: false }));
      });
    }
  });

  document.getElementById('planForm').addEventListener('submit', function (e) {
    e.preventDefault();
    const id = document.getElementById('plan_id').value;
    const url = id ? `${baseUrl}/${id}` : baseUrl;
    const method = id ? 'PATCH' : 'POST';

    const fd = new FormData(this);
    const payload = {};
    fd.forEach((v, k) => {
      if (k === 'permissions[]') {
        payload['permissions'] = payload['permissions'] || [];
        payload['permissions'].push(v);
      } else if (k !== '_token') {
        payload[k] = v;
      }
    });
    payload.ativo = document.getElementById('plan_ativo').checked ? 1 : 0;

    fetch(url, {
      method,
      headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', Accept: 'application/json' },
      body: JSON.stringify(payload)
    })
      .then(r => r.json().then(b => ({ ok: r.ok, body: b })))
      .then(({ ok, body }) => {
        if (!ok) {
          let msg = body.message || 'Erro ao salvar';
          if (body.errors) {
            msg = Object.values(body.errors).flat().join('\n');
          }
          throw new Error(msg);
        }
        offcanvas.hide();
        dt.draw(false);
        Swal.fire({ icon: 'success', title: 'Sucesso', text: body.message, customClass: { confirmButton: 'btn btn-success' }, buttonsStyling: false });
      })
      .catch(err => Swal.fire({ icon: 'error', title: 'Erro', text: err.message, customClass: { confirmButton: 'btn btn-danger' }, buttonsStyling: false }));
  });

  function resetForm() {
    document.getElementById('planForm').reset();
    document.getElementById('plan_id').value = '';
    $perm.val(null).trigger('change');
    document.getElementById('plan_ativo').checked = true;
  }
});
</script>
@endsection
