@extends('layouts/layoutMaster')

@section('title', 'Comissões')

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
  <div class="card-header border-bottom"><h5 class="card-title mb-0">Lançamentos de Comissões</h5></div>
  <div class="card-datatable">
    <table class="datatables-comissoes-admin table border-top">
      <thead><tr><th>ID</th><th>Licenciado</th><th>Cliente</th><th>Descrição</th><th>Data</th><th>Valor</th><th>Status</th><th>Ações</th></tr></thead>
    </table>
  </div>

  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasCom">
    <div class="offcanvas-header border-bottom">
      <h5 id="offcanvasComLabel" class="offcanvas-title">Nova Comissão</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-6">
      <form id="comForm" class="pt-0">
        @csrf
        <input type="hidden" name="id" id="com_id">
        <div class="mb-6"><label class="form-label">Licenciado</label>
          <select class="select2 form-select" name="licensed_by_user_id" id="com_licenciado">
            <option value="">Selecione...</option>
            @foreach ($licenciados as $l)<option value="{{ $l->id }}">{{ $l->name }}</option>@endforeach
          </select>
        </div>
        <div class="mb-6"><label class="form-label">Cliente (opcional)</label>
          <select class="select2 form-select" name="cliente_id" id="com_cliente">
            <option value="">Sem cliente</option>
            @foreach ($clientes as $c)
              <option value="{{ $c->id }}" data-licenciado="{{ $c->licensed_by_user_id }}">{{ $c->nome }}</option>
            @endforeach
          </select>
        </div>
        <div class="mb-6"><label class="form-label">Descrição</label><input class="form-control" name="descricao"></div>
        <div class="row">
          <div class="mb-6 col-md-6"><label class="form-label">Valor (R$)</label><input type="number" step="0.01" min="0" class="form-control" name="valor"></div>
          <div class="mb-6 col-md-6"><label class="form-label">Data referência</label><input type="date" class="form-control" name="data_referencia"></div>
        </div>
        <div class="mb-6"><label class="form-label">Status</label>
          <select class="form-select" name="status">
            <option value="pendente">Pendente</option><option value="paga">Paga</option><option value="cancelada">Cancelada</option>
          </select>
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
  const baseUrl = "{{ url('/painel/comissoes-admin') }}";
  const offcanvas = new bootstrap.Offcanvas(document.getElementById('offcanvasCom'));
  $('#com_licenciado, #com_cliente').each(function () { const $s = $(this); $s.wrap('<div class="position-relative"></div>').select2({ dropdownParent: $s.parent() }); });

  const dt = new DataTable('.datatables-comissoes-admin', {
    processing: true, serverSide: true, ajax: { url: baseUrl + '/datatable' },
    columns: [
      { data: 'id' }, { data: 'licenciado_nome' }, { data: 'cliente_nome' },
      { data: 'descricao' }, { data: 'data_formatada' }, { data: 'valor_formatado' }, { data: 'status_badge' },
      { data: 'id', orderable: false, searchable: false, render: id => `
        <button class="btn btn-sm btn-icon edit-com" data-id="${id}"><i class="ti tabler-edit icon-22px"></i></button>
        <button class="btn btn-sm btn-icon delete-com text-danger" data-id="${id}"><i class="ti tabler-trash icon-22px"></i></button>` }
    ],
    order: [[4, 'desc']],
    language: { processing: 'Carregando...', search: 'Buscar:', lengthMenu: '_MENU_', info: '_START_-_END_ de _TOTAL_', infoEmpty: '0', zeroRecords: 'Nenhuma', emptyTable: 'Nenhuma comissão', paginate: { first: '«', previous: '‹', next: '›', last: '»' } },
    layout: { topStart: { features: [{ pageLength: { menu: [10, 25, 50] } }] }, topEnd: { features: [{ search: { placeholder: 'Buscar' } }, { buttons: [{ text: '<i class="ti tabler-plus me-1"></i> Nova Comissão', className: 'btn btn-primary add-new-com' }] }] } }
  });

  document.addEventListener('click', function (e) {
    if (e.target.closest('.add-new-com')) { resetForm(); document.getElementById('offcanvasComLabel').textContent = 'Nova Comissão'; offcanvas.show(); }
    const editBtn = e.target.closest('.edit-com');
    if (editBtn) fetch(`${baseUrl}/${editBtn.dataset.id}`).then(r => r.json()).then(c => {
      resetForm(); document.getElementById('offcanvasComLabel').textContent = 'Editar Comissão';
      document.getElementById('com_id').value = c.id;
      $('#com_licenciado').val(c.licensed_by_user_id).trigger('change');
      $('#com_cliente').val(c.cliente_id || '').trigger('change');
      for (const k of ['descricao','valor','data_referencia','status']) {
        const el = document.querySelector(`#comForm [name="${k}"]`);
        if (el) el.value = c[k] || '';
      }
      offcanvas.show();
    });
    const delBtn = e.target.closest('.delete-com');
    if (delBtn) Swal.fire({ title: 'Excluir comissão?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Sim', cancelButtonText: 'Não', customClass: { confirmButton: 'btn btn-danger me-3', cancelButton: 'btn btn-label-secondary' }, buttonsStyling: false }).then(r => {
      if (!r.value) return;
      fetch(`${baseUrl}/${delBtn.dataset.id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' } })
        .then(r => r.json()).then(b => { dt.draw(false); Swal.fire({ icon: 'success', title: 'Removida', text: b.message, customClass: { confirmButton: 'btn btn-success' }, buttonsStyling: false }); });
    });
  });

  document.getElementById('comForm').addEventListener('submit', function (e) {
    e.preventDefault();
    const id = document.getElementById('com_id').value;
    const url = id ? `${baseUrl}/${id}` : baseUrl;
    const fd = new FormData(this); const payload = {};
    fd.forEach((v, k) => { if (k !== '_token' && k !== 'id') payload[k] = v; });
    if (!payload.cliente_id) payload.cliente_id = null;
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
    document.getElementById('comForm').reset();
    document.getElementById('com_id').value = '';
    $('#com_licenciado, #com_cliente').val('').trigger('change');
  }
});
</script>
@endsection
