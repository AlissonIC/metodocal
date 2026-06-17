@extends('layouts/layoutMaster')

@section('title', 'Conteúdos')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('content')
<div class="card">
  <div class="card-header border-bottom"><h5 class="card-title mb-0">Biblioteca de Conteúdos</h5></div>
  <div class="card-datatable">
    <table class="datatables-conteudos table border-top">
      <thead><tr><th>ID</th><th>Título</th><th>Tipo</th><th>Categoria</th><th>Ordem</th><th>Concluíram</th><th>Status</th><th>Ações</th></tr></thead>
    </table>
  </div>

  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasCon">
    <div class="offcanvas-header border-bottom">
      <h5 id="offcanvasConLabel" class="offcanvas-title">Novo Conteúdo</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-6">
      <form id="conForm" class="pt-0">
        @csrf
        <input type="hidden" name="id" id="con_id">
        <div class="mb-6"><label class="form-label">Título</label><input class="form-control" name="titulo"></div>
        <div class="mb-6"><label class="form-label">Descrição</label><textarea class="form-control" name="descricao" rows="2"></textarea></div>
        <div class="row">
          <div class="mb-6 col-md-6"><label class="form-label">Tipo</label>
            <select class="form-select" name="tipo">
              <option value="video">Vídeo</option><option value="pdf">PDF</option><option value="texto">Texto</option><option value="link">Link</option>
            </select>
          </div>
          <div class="mb-6 col-md-6"><label class="form-label">Categoria</label><input class="form-control" name="categoria"></div>
        </div>
        <div class="mb-6"><label class="form-label">URL</label><input class="form-control" name="url" placeholder="https://..."></div>
        <div class="mb-6"><label class="form-label">Ordem</label><input type="number" min="0" class="form-control" name="ordem" value="0"></div>
        <div class="mb-6 form-check form-switch"><input class="form-check-input" type="checkbox" id="con_ativo" name="ativo" value="1" checked><label class="form-check-label" for="con_ativo">Ativo</label></div>
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
  const baseUrl = "{{ url('/painel/conteudos-admin') }}";
  const offcanvas = new bootstrap.Offcanvas(document.getElementById('offcanvasCon'));
  const dt = new DataTable('.datatables-conteudos', {
    processing: true, serverSide: true, ajax: { url: baseUrl + '/datatable' },
    columns: [
      { data: 'id' }, { data: 'titulo' }, { data: 'tipo_label' }, { data: 'categoria', render: v => v || '—' },
      { data: 'ordem' }, { data: 'progressos_count' }, { data: 'status_badge' },
      { data: 'id', orderable: false, searchable: false, render: id => `
        <button class="btn btn-sm btn-icon edit-con" data-id="${id}"><i class="ti tabler-edit icon-22px"></i></button>
        <button class="btn btn-sm btn-icon delete-con text-danger" data-id="${id}"><i class="ti tabler-trash icon-22px"></i></button>` }
    ],
    order: [[4, 'asc']],
    language: { processing: 'Carregando...', search: 'Buscar:', lengthMenu: '_MENU_', info: '_START_-_END_ de _TOTAL_', infoEmpty: '0', zeroRecords: 'Nenhum', emptyTable: 'Nenhum conteúdo', paginate: { first: '«', previous: '‹', next: '›', last: '»' } },
    layout: { topStart: { features: [{ pageLength: { menu: [10, 25, 50] } }] }, topEnd: { features: [{ search: { placeholder: 'Buscar' } }, { buttons: [{ text: '<i class="ti tabler-plus me-1"></i> Novo Conteúdo', className: 'btn btn-primary add-new-con' }] }] } }
  });

  document.addEventListener('click', function (e) {
    if (e.target.closest('.add-new-con')) { resetForm(); document.getElementById('offcanvasConLabel').textContent = 'Novo Conteúdo'; offcanvas.show(); }
    const editBtn = e.target.closest('.edit-con');
    if (editBtn) fetch(`${baseUrl}/${editBtn.dataset.id}`).then(r => r.json()).then(c => {
      resetForm(); document.getElementById('offcanvasConLabel').textContent = 'Editar Conteúdo';
      document.getElementById('con_id').value = c.id;
      for (const k of ['titulo','descricao','tipo','url','categoria','ordem']) {
        const el = document.querySelector(`#conForm [name="${k}"]`);
        if (el) el.value = c[k] || '';
      }
      document.getElementById('con_ativo').checked = !!c.ativo;
      offcanvas.show();
    });
    const delBtn = e.target.closest('.delete-con');
    if (delBtn) Swal.fire({ title: 'Excluir conteúdo?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Sim', cancelButtonText: 'Não', customClass: { confirmButton: 'btn btn-danger me-3', cancelButton: 'btn btn-label-secondary' }, buttonsStyling: false }).then(r => {
      if (!r.value) return;
      fetch(`${baseUrl}/${delBtn.dataset.id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' } })
        .then(r => r.json()).then(b => { dt.draw(false); Swal.fire({ icon: 'success', title: 'Removido', text: b.message, customClass: { confirmButton: 'btn btn-success' }, buttonsStyling: false }); });
    });
  });

  document.getElementById('conForm').addEventListener('submit', function (e) {
    e.preventDefault();
    const id = document.getElementById('con_id').value;
    const url = id ? `${baseUrl}/${id}` : baseUrl;
    const fd = new FormData(this); const payload = {};
    fd.forEach((v, k) => { if (k !== '_token' && k !== 'id') payload[k] = v; });
    payload.ativo = document.getElementById('con_ativo').checked ? 1 : 0;
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
    document.getElementById('conForm').reset();
    document.getElementById('con_id').value = '';
    document.getElementById('con_ativo').checked = true;
  }
});
</script>
@endsection
