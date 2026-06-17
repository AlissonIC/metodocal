@extends('layouts/layoutMaster')

@section('title', 'Materiais')

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
  <div class="card-header border-bottom"><h5 class="card-title mb-0">Materiais para licenciados</h5></div>
  <div class="card-datatable">
    <table class="datatables-materiais table border-top">
      <thead><tr><th>ID</th><th>Título</th><th>Categoria</th><th>Tamanho</th><th>Status</th><th>Ações</th></tr></thead>
    </table>
  </div>

  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasMat">
    <div class="offcanvas-header border-bottom">
      <h5 id="offcanvasMatLabel" class="offcanvas-title">Novo Material</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-6">
      <form id="matForm" class="pt-0" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="id" id="mat_id">
        <div class="mb-6"><label class="form-label">Título</label><input class="form-control" name="titulo"></div>
        <div class="mb-6"><label class="form-label">Descrição</label><textarea class="form-control" name="descricao" rows="2"></textarea></div>
        <div class="mb-6"><label class="form-label">Categoria</label><input class="form-control" name="categoria"></div>
        <div class="mb-6">
          <label class="form-label">Arquivo</label>
          <input type="file" class="form-control" name="arquivo" id="mat_arquivo">
          <small class="text-muted" id="mat_arquivo_hint"></small>
        </div>
        <div class="mb-6 form-check form-switch"><input class="form-check-input" type="checkbox" id="mat_ativo" name="ativo" value="1" checked><label class="form-check-label" for="mat_ativo">Ativo</label></div>
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
  const baseUrl = "{{ url('/painel/materiais-admin') }}";
  const offcanvas = new bootstrap.Offcanvas(document.getElementById('offcanvasMat'));
  const dt = new DataTable('.datatables-materiais', {
    processing: true, serverSide: true, ajax: { url: baseUrl + '/datatable' },
    columns: [
      { data: 'id' }, { data: 'titulo' }, { data: 'categoria', render: v => v || '—' },
      { data: 'tamanho_formatado' }, { data: 'status_badge' },
      { data: 'id', orderable: false, searchable: false, render: id => `
        <button class="btn btn-sm btn-icon edit-mat" data-id="${id}"><i class="ti tabler-edit icon-22px"></i></button>
        <button class="btn btn-sm btn-icon delete-mat text-danger" data-id="${id}"><i class="ti tabler-trash icon-22px"></i></button>` }
    ],
    order: [[0, 'desc']],
    language: { processing: 'Carregando...', search: 'Buscar:', lengthMenu: '_MENU_', info: '_START_-_END_ de _TOTAL_', infoEmpty: '0', zeroRecords: 'Nenhum', emptyTable: 'Nenhum material', paginate: { first: '«', previous: '‹', next: '›', last: '»' } },
    layout: { topStart: { features: [{ pageLength: { menu: [10, 25, 50] } }] }, topEnd: { features: [{ search: { placeholder: 'Buscar' } }, { buttons: [{ text: '<i class="ti tabler-plus me-1"></i> Novo Material', className: 'btn btn-primary add-new-mat' }] }] } }
  });

  document.addEventListener('click', function (e) {
    if (e.target.closest('.add-new-mat')) {
      resetForm();
      document.getElementById('offcanvasMatLabel').textContent = 'Novo Material';
      document.getElementById('mat_arquivo').required = true;
      document.getElementById('mat_arquivo_hint').textContent = '';
      offcanvas.show();
    }
    const editBtn = e.target.closest('.edit-mat');
    if (editBtn) fetch(`${baseUrl}/${editBtn.dataset.id}`).then(r => r.json()).then(m => {
      resetForm(); document.getElementById('offcanvasMatLabel').textContent = 'Editar Material';
      document.getElementById('mat_id').value = m.id;
      for (const k of ['titulo','descricao','categoria']) {
        const el = document.querySelector(`#matForm [name="${k}"]`);
        if (el) el.value = m[k] || '';
      }
      document.getElementById('mat_ativo').checked = !!m.ativo;
      document.getElementById('mat_arquivo').required = false;
      document.getElementById('mat_arquivo_hint').textContent = 'Deixe em branco para manter o arquivo atual';
      offcanvas.show();
    });
    const delBtn = e.target.closest('.delete-mat');
    if (delBtn) Swal.fire({ title: 'Excluir material?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Sim', cancelButtonText: 'Não', customClass: { confirmButton: 'btn btn-danger me-3', cancelButton: 'btn btn-label-secondary' }, buttonsStyling: false }).then(r => {
      if (!r.value) return;
      fetch(`${baseUrl}/${delBtn.dataset.id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' } })
        .then(r => r.json()).then(b => { dt.draw(false); Swal.fire({ icon: 'success', title: 'Removido', text: b.message, customClass: { confirmButton: 'btn btn-success' }, buttonsStyling: false }); });
    });
  });

  document.getElementById('matForm').addEventListener('submit', function (e) {
    e.preventDefault();
    const id = document.getElementById('mat_id').value;
    const fd = new FormData(this);
    fd.set('ativo', document.getElementById('mat_ativo').checked ? '1' : '0');
    if (!fd.get('arquivo') || !fd.get('arquivo').size) fd.delete('arquivo');

    let url = baseUrl, method = 'POST';
    if (id) { url = `${baseUrl}/${id}`; fd.append('_method', 'PATCH'); }

    fetch(url, { method, headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' }, body: fd })
      .then(r => r.json().then(b => ({ ok: r.ok, body: b })))
      .then(({ ok, body }) => {
        if (!ok) { const msg = body.errors ? Object.values(body.errors).flat().join('\n') : body.message; throw new Error(msg); }
        offcanvas.hide(); dt.draw(false);
        Swal.fire({ icon: 'success', title: 'Sucesso', text: body.message, customClass: { confirmButton: 'btn btn-success' }, buttonsStyling: false });
      })
      .catch(err => Swal.fire({ icon: 'error', title: 'Erro', text: err.message, customClass: { confirmButton: 'btn btn-danger' }, buttonsStyling: false }));
  });

  function resetForm() {
    document.getElementById('matForm').reset();
    document.getElementById('mat_id').value = '';
    document.getElementById('mat_ativo').checked = true;
  }
});
</script>
@endsection
