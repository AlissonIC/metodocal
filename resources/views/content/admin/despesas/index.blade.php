@extends('layouts/layoutMaster')

@section('title', 'Despesas')

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
  'resources/assets/vendor/libs/select2/select2.scss',
  'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
])
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
  'resources/assets/vendor/libs/select2/select2.js',
  'resources/assets/vendor/libs/cleave-zen/cleave-zen.js',
  'resources/assets/vendor/libs/flatpickr/flatpickr.js',
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
    background-color: var(--bs-primary);
    border: 0;
  }
  .filtros-bar { overflow-x: hidden; }
  .filtros-bar .row { align-items: end; }
  .filtros-bar .select2-container { width: 100% !important; max-width: 100%; }
</style>
@endsection

@section('content')
<div class="card">
  <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <h5 class="card-title mb-0"><i class="icon-base ti tabler-cash-banknote me-1"></i> Despesas</h5>
      <p class="text-muted mb-0 mt-1 small">Cadastro de despesas <strong>fixas</strong> (repetem mensalmente) e <strong>únicas</strong>. As fixas continuam sendo geradas até você encerrá-las.</p>
    </div>
    <div class="d-flex gap-2">
      <button type="button" id="btn-exportar" class="btn btn-label-success">
        <i class="icon-base ti tabler-file-spreadsheet me-1"></i> Exportar
      </button>
      <button type="button" class="btn btn-primary" id="btn-nova-despesa">
        <i class="icon-base ti tabler-plus me-1"></i> Nova despesa
      </button>
    </div>
  </div>

  <div class="card-body filtros-bar">
    <div class="row g-2 g-md-3">
      <div class="col-6 col-md-3">
        <label class="form-label small mb-1">Competência (mês)</label>
        <input type="month" id="filtro-competencia" class="form-control form-control-sm" value="{{ now()->format('Y-m') }}">
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label small mb-1">Status</label>
        <select id="filtro-status" class="form-select form-select-sm" data-placeholder="Todos">
          <option value=""></option>
          <option value="pendente">Pendente</option>
          <option value="atrasada">Atrasada</option>
          <option value="paga">Paga</option>
          <option value="cancelada">Cancelada</option>
        </select>
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label small mb-1">Tipo</label>
        <select id="filtro-tipo" class="form-select form-select-sm" data-placeholder="Todos">
          <option value=""></option>
          <option value="fixa">Fixa</option>
          <option value="unica">Única</option>
        </select>
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label small mb-1">Categoria</label>
        <select id="filtro-categoria" class="form-select form-select-sm" data-placeholder="Todas">
          <option value=""></option>
          @foreach ($categorias as $cat)
            <option value="{{ $cat }}">{{ $cat }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-12 col-md-2 d-flex align-items-end">
        <button id="btn-limpar-filtros" class="btn btn-label-secondary btn-sm w-100">
          <i class="icon-base ti tabler-eraser me-1"></i> Limpar
        </button>
      </div>
    </div>
  </div>

  <div class="card-datatable">
    <table class="datatables-despesas table border-top dt-responsive" style="width:100%">
      <thead>
        <tr>
          <th>Despesa</th>
          <th>Categoria</th>
          <th>Competência</th>
          <th>Vencimento</th>
          <th>Valor</th>
          <th>Status</th>
          <th class="text-end">Ações</th>
        </tr>
      </thead>
    </table>
  </div>
</div>

{{-- ========== MODAL: criar/editar despesa ========== --}}
<div class="modal fade" id="despesaModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <form id="despesa-form" autocomplete="off">
        <div class="modal-header">
          <h5 class="modal-title" id="despesaModalTitle">Nova despesa</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div id="despesa-meta" class="alert alert-secondary py-2 small mb-3" style="display:none;"></div>
          <div id="despesa-error" class="alert alert-danger py-2 small mb-3" style="display:none;"></div>
          <input type="hidden" name="id" id="despesa-id">
          <div class="row">
            <div class="col-md-8 mb-3">
              <label class="form-label">Nome *</label>
              <input type="text" class="form-control" name="nome" id="despesa-nome" required maxlength="160" placeholder="Ex.: Aluguel, Internet, Salário do Fulano">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Categoria</label>
              <input type="text" class="form-control" name="categoria" id="despesa-categoria" maxlength="60" placeholder="Ex.: Aluguel, Software, RH" list="categorias-existentes">
              <datalist id="categorias-existentes">
                @foreach ($categorias as $cat)
                  <option value="{{ $cat }}">
                @endforeach
              </datalist>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Tipo *</label>
              <div class="d-flex gap-3 mt-1">
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="tipo" id="tipo-fixa" value="fixa" checked>
                  <label class="form-check-label" for="tipo-fixa"><i class="icon-base ti tabler-repeat me-1"></i> Fixa (mensal)</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="tipo" id="tipo-unica" value="unica">
                  <label class="form-check-label" for="tipo-unica"><i class="icon-base ti tabler-flag me-1"></i> Única</label>
                </div>
              </div>
              <small class="text-muted d-block mt-1" id="tipo-help">Fixa: gera uma cobrança todo mês até você encerrá-la.</small>
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label">Valor (R$) *</label>
              <input type="text" inputmode="numeric" class="form-control mask-money" name="valor" id="despesa-valor" required placeholder="0,00">
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label">Dia venc. <small class="text-muted" id="dia-help">(1-31)</small></label>
              <input type="number" class="form-control" name="dia_vencimento" id="despesa-dia" min="1" max="31" placeholder="Ex.: 5">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label" id="data-inicio-label">Data de início *</label>
              <input type="text" class="form-control flatpickr-date" name="data_inicio" id="despesa-data-inicio" required placeholder="dd/mm/aaaa">
              <small class="text-muted" id="data-inicio-help">Primeira competência da despesa fixa.</small>
            </div>
            <div class="col-12 mb-0">
              <label class="form-label">Descrição / observações</label>
              <textarea class="form-control" name="descricao" id="despesa-descricao" rows="3" maxlength="2000" placeholder="Detalhes opcionais..."></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary" id="despesa-save-btn">
            <i class="icon-base ti tabler-device-floppy me-1"></i> Salvar
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const csrf = document.querySelector('meta[name="csrf-token"]').content;
  const dtUrl        = @json(route('admin.despesas.datatable'));
  const storeUrl     = @json(route('admin.despesas.store'));
  const baseDespesa  = @json(url('painel/financeiro/despesas'));
  const baseOcorr    = @json(url('painel/financeiro/despesas/ocorrencias'));

  const escapeHtml = (s) => String(s ?? '').replace(/[&<>"']/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]));
  function moneyToRaw(v) {
    const s = String(v || '').trim();
    if (! s) return null;
    const cleaned = s.includes(',') ? s.replace(/\./g, '').replace(',', '.') : s;
    const n = parseFloat(cleaned);
    return isNaN(n) ? null : n.toFixed(2);
  }
  function refreshMasks() { document.dispatchEvent(new CustomEvent('mask:refresh')); }
  function setDate(input, iso) {
    if (! input) return;
    if (input._flatpickr) input._flatpickr.setDate(iso || null, true);
    else input.value = iso || '';
  }
  function readDate(input) {
    if (! input) return '';
    const fp = input._flatpickr;
    if (fp && fp.selectedDates[0]) return fp.formatDate(fp.selectedDates[0], 'Y-m-d');
    return input.value || '';
  }

  // Select2 nos filtros
  $('#filtro-status, #filtro-tipo, #filtro-categoria').select2({
    allowClear: true,
    placeholder: function () { return $(this).data('placeholder') || ''; },
    width: '100%',
  });

  // Flatpickr no modal
  if (window.flatpickr) {
    flatpickr('.flatpickr-date', {
      altInput: true, altFormat: 'd/m/Y', dateFormat: 'Y-m-d', allowInput: true,
    });
  }

  // ---- DataTable ----
  const dt = new DataTable('.datatables-despesas', {
    processing: true, serverSide: true, responsive: true,
    ajax: {
      url: dtUrl,
      data: function (d) {
        d.competencia = document.getElementById('filtro-competencia').value;
        d.status      = $('#filtro-status').val();
        d.tipo        = $('#filtro-tipo').val();
        d.categoria   = $('#filtro-categoria').val();
      },
    },
    columns: [
      { data: 'despesa_nome',   responsivePriority: 1 },
      { data: 'categoria',      responsivePriority: 4 },
      { data: 'competencia_fmt', responsivePriority: 3, className: 'text-nowrap text-capitalize' },
      { data: 'vencimento_fmt', responsivePriority: 3, className: 'text-nowrap' },
      { data: 'valor_fmt',      responsivePriority: 2, className: 'text-nowrap fw-semibold' },
      { data: 'status_badge',   responsivePriority: 2, orderable: false, searchable: false },
      {
        data: null, responsivePriority: 1, orderable: false, searchable: false, className: 'text-end text-nowrap',
        render: (row) => {
          const paga = row.status_badge && row.status_badge.includes('bg-label-success');
          const btnPaga = paga
            ? `<button class="btn btn-sm btn-icon oc-pendente" data-id="${row.id}" title="Marcar como pendente"><i class="icon-base ti tabler-arrow-back-up icon-22px"></i></button>`
            : `<button class="btn btn-sm btn-icon text-success oc-paga" data-id="${row.id}" title="Marcar como paga"><i class="icon-base ti tabler-check icon-22px"></i></button>`;
          const btnEncerrar = (row.is_fixa && ! row.encerrada)
            ? `<button class="btn btn-sm btn-icon text-warning desp-encerrar" data-id="${row.despesa_id}" title="Encerrar despesa fixa"><i class="icon-base ti tabler-lock icon-22px"></i></button>`
            : (row.is_fixa && row.encerrada
                ? `<button class="btn btn-sm btn-icon text-info desp-reabrir" data-id="${row.despesa_id}" title="Reabrir despesa fixa"><i class="icon-base ti tabler-lock-open icon-22px"></i></button>`
                : '');
          return `
            <div class="d-inline-flex flex-nowrap gap-1 justify-content-end">
              ${btnPaga}
              <button class="btn btn-sm btn-icon desp-edit" data-id="${row.despesa_id}" title="Editar despesa"><i class="icon-base ti tabler-edit icon-22px"></i></button>
              ${btnEncerrar}
              <button class="btn btn-sm btn-icon text-danger oc-delete" data-id="${row.id}" title="Remover ocorrência"><i class="icon-base ti tabler-trash icon-22px"></i></button>
            </div>`;
        },
      },
    ],
    order: [],
    language: { processing: 'Carregando...', search: 'Buscar:', lengthMenu: '_MENU_ por página', info: 'Exibindo _START_ a _END_ de _TOTAL_', infoEmpty: 'Nenhum registro', zeroRecords: 'Nenhuma despesa encontrada', emptyTable: 'Nenhuma despesa cadastrada', paginate: { first: '«', previous: '‹', next: '›', last: '»' } },
    layout: { topStart: { features: [{ pageLength: { menu: [10, 25, 50] } }] }, topEnd: { features: [{ search: { placeholder: 'Buscar nome da despesa' } }] } },
  });

  document.getElementById('filtro-competencia').addEventListener('change', () => dt.draw());
  $('#filtro-status, #filtro-tipo, #filtro-categoria').on('change', () => dt.draw());
  document.getElementById('btn-limpar-filtros').addEventListener('click', () => {
    document.getElementById('filtro-competencia').value = '';
    $('#filtro-status, #filtro-tipo, #filtro-categoria').val(null).trigger('change');
    dt.draw();
  });

  // Exportação: leva os mesmos filtros da tela e traz todas as linhas, sem paginação.
  document.getElementById('btn-exportar').addEventListener('click', () => {
    const params = new URLSearchParams({
      competencia: document.getElementById('filtro-competencia').value,
      status:      $('#filtro-status').val() || '',
      tipo:        $('#filtro-tipo').val() || '',
      categoria:   $('#filtro-categoria').val() || '',
      busca:       dt.search(),
    });
    [...params.keys()].forEach(k => { if (! params.get(k)) params.delete(k); });
    window.location = "{{ route('admin.despesas.export') }}?" + params.toString();
  });

  // ---- Modal handling ----
  const modalEl = document.getElementById('despesaModal');
  const modal   = new bootstrap.Modal(modalEl);
  const form    = document.getElementById('despesa-form');
  const idEl    = document.getElementById('despesa-id');
  const nomeEl  = document.getElementById('despesa-nome');
  const catEl   = document.getElementById('despesa-categoria');
  const valorEl = document.getElementById('despesa-valor');
  const diaEl   = document.getElementById('despesa-dia');
  const inicioEl= document.getElementById('despesa-data-inicio');
  const descEl  = document.getElementById('despesa-descricao');
  const metaEl  = document.getElementById('despesa-meta');
  const errEl   = document.getElementById('despesa-error');
  const saveBtn = document.getElementById('despesa-save-btn');
  const titleEl = document.getElementById('despesaModalTitle');
  const tipoHelp = document.getElementById('tipo-help');
  const diaHelp = document.getElementById('dia-help');

  function tipoAtual() { return document.querySelector('input[name="tipo"]:checked')?.value || 'fixa'; }
  function atualizarCamposTipo() {
    const t = tipoAtual();
    if (t === 'fixa') {
      tipoHelp.textContent = 'Fixa: gera uma cobrança todo mês até você encerrá-la.';
      diaEl.disabled = false;
      diaHelp.textContent = '(1-31)';
    } else {
      tipoHelp.textContent = 'Única: gera uma única cobrança na data informada.';
      diaEl.disabled = true;
      diaEl.value = '';
      diaHelp.textContent = '(não usado)';
    }
  }
  document.querySelectorAll('input[name="tipo"]').forEach(r => r.addEventListener('change', atualizarCamposTipo));

  function resetForm(defaults = {}) {
    idEl.value = '';
    nomeEl.value = defaults.nome || '';
    catEl.value = defaults.categoria || '';
    valorEl.value = defaults.valor || '';
    diaEl.value = defaults.dia_vencimento || '';
    setDate(inicioEl, defaults.data_inicio || '');
    descEl.value = defaults.descricao || '';
    document.getElementById('tipo-' + (defaults.tipo || 'fixa')).checked = true;
    atualizarCamposTipo();
    metaEl.style.display = 'none'; metaEl.innerHTML = '';
    errEl.style.display = 'none'; errEl.innerHTML = '';
    refreshMasks();
  }

  function openNew() {
    resetForm({ data_inicio: '{{ now()->toDateString() }}', tipo: 'fixa' });
    titleEl.textContent = 'Nova despesa';
    // Habilita/desabilita radios (novo cadastro pode escolher qualquer tipo)
    document.querySelectorAll('input[name="tipo"]').forEach(r => r.disabled = false);
    modal.show();
    setTimeout(() => nomeEl.focus(), 200);
  }

  function openEdit(id) {
    resetForm();
    titleEl.textContent = 'Editar despesa';
    idEl.value = id;
    fetch(`${baseDespesa}/${id}`, { headers: { Accept: 'application/json' } })
      .then(r => { if (! r.ok) throw new Error('Não foi possível carregar a despesa.'); return r.json(); })
      .then(d => {
        nomeEl.value = d.nome || '';
        catEl.value = d.categoria || '';
        valorEl.value = d.valor || '';
        diaEl.value = d.dia_vencimento || '';
        setDate(inicioEl, d.data_inicio);
        descEl.value = d.descricao || '';
        document.getElementById('tipo-' + (d.tipo || 'fixa')).checked = true;
        // Não permite trocar tipo na edição (evitaria drift entre template e ocorrências)
        document.querySelectorAll('input[name="tipo"]').forEach(r => r.disabled = true);
        atualizarCamposTipo();
        if (d.encerrada_em) {
          metaEl.innerHTML = `<i class="icon-base ti tabler-lock me-1"></i> Despesa encerrada em <strong>${escapeHtml(d.encerrada_em)}</strong>. Novos meses não serão gerados.`;
          metaEl.style.display = '';
        }
        refreshMasks();
        modal.show();
      })
      .catch(err => Swal.fire({ icon: 'error', title: 'Erro', text: err.message, customClass: { confirmButton: 'btn btn-danger' }, buttonsStyling: false }));
  }

  document.getElementById('btn-nova-despesa').addEventListener('click', openNew);

  // Delegação de eventos da tabela
  document.querySelector('.datatables-despesas').addEventListener('click', function (e) {
    const editBtn = e.target.closest('.desp-edit');
    if (editBtn) return openEdit(editBtn.dataset.id);

    const pagaBtn = e.target.closest('.oc-paga');
    if (pagaBtn) return marcarOcorrencia(pagaBtn.dataset.id, 'paga');

    const pendBtn = e.target.closest('.oc-pendente');
    if (pendBtn) return marcarOcorrencia(pendBtn.dataset.id, 'pendente');

    const delBtn = e.target.closest('.oc-delete');
    if (delBtn) return removerOcorrencia(delBtn.dataset.id);

    const encBtn = e.target.closest('.desp-encerrar');
    if (encBtn) return encerrarDespesa(encBtn.dataset.id);

    const reabrirBtn = e.target.closest('.desp-reabrir');
    if (reabrirBtn) return reabrirDespesa(reabrirBtn.dataset.id);
  });

  function marcarOcorrencia(id, novoStatus) {
    fetch(`${baseOcorr}/${id}/${novoStatus}`, { method: 'PATCH', headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' } })
      .then(r => r.json().then(b => ({ ok: r.ok, body: b })))
      .then(({ ok, body }) => {
        if (! ok) throw new Error(body.message || 'Erro');
        dt.draw(false);
        Swal.fire({ icon: 'success', title: body.message, timer: 1200, showConfirmButton: false });
      })
      .catch(err => Swal.fire({ icon: 'error', title: 'Erro', text: err.message, customClass: { confirmButton: 'btn btn-danger' }, buttonsStyling: false }));
  }

  function removerOcorrencia(id) {
    Swal.fire({
      title: 'Remover esta ocorrência?', text: 'Se for uma despesa fixa, o mês será regerado ao recarregar. Considere marcar como cancelada.',
      icon: 'warning', showCancelButton: true, confirmButtonText: 'Sim, remover', cancelButtonText: 'Cancelar',
      customClass: { confirmButton: 'btn btn-danger me-3', cancelButton: 'btn btn-label-secondary' }, buttonsStyling: false,
    }).then(r => {
      if (! r.value) return;
      fetch(`${baseOcorr}/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' } })
        .then(r => r.json().then(b => ({ ok: r.ok, body: b })))
        .then(({ ok, body }) => {
          if (! ok) throw new Error(body.message || 'Erro');
          dt.draw(false);
          Swal.fire({ icon: 'success', title: body.message, timer: 1200, showConfirmButton: false });
        })
        .catch(err => Swal.fire({ icon: 'error', title: 'Erro', text: err.message, customClass: { confirmButton: 'btn btn-danger' }, buttonsStyling: false }));
    });
  }

  function encerrarDespesa(id) {
    Swal.fire({
      title: 'Encerrar despesa fixa?', text: 'Novos meses deixarão de ser gerados. Ocorrências futuras pendentes serão canceladas. Pagas permanecem.',
      icon: 'warning', showCancelButton: true, confirmButtonText: 'Sim, encerrar', cancelButtonText: 'Voltar',
      customClass: { confirmButton: 'btn btn-warning me-3', cancelButton: 'btn btn-label-secondary' }, buttonsStyling: false,
    }).then(r => {
      if (! r.value) return;
      fetch(`${baseDespesa}/${id}/encerrar`, { method: 'PATCH', headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' } })
        .then(r => r.json().then(b => ({ ok: r.ok, body: b })))
        .then(({ ok, body }) => {
          if (! ok) throw new Error(body.message || 'Erro');
          dt.draw(false);
          Swal.fire({ icon: 'success', title: body.message, timer: 1600, showConfirmButton: false });
        })
        .catch(err => Swal.fire({ icon: 'error', title: 'Erro', text: err.message, customClass: { confirmButton: 'btn btn-danger' }, buttonsStyling: false }));
    });
  }

  function reabrirDespesa(id) {
    Swal.fire({
      title: 'Reabrir despesa?', text: 'Voltará a gerar cobranças mensais a partir do mês corrente.',
      icon: 'question', showCancelButton: true, confirmButtonText: 'Sim, reabrir', cancelButtonText: 'Voltar',
      customClass: { confirmButton: 'btn btn-info me-3', cancelButton: 'btn btn-label-secondary' }, buttonsStyling: false,
    }).then(r => {
      if (! r.value) return;
      fetch(`${baseDespesa}/${id}/reabrir`, { method: 'PATCH', headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' } })
        .then(r => r.json().then(b => ({ ok: r.ok, body: b })))
        .then(({ ok, body }) => {
          if (! ok) throw new Error(body.message || 'Erro');
          dt.draw(false);
          Swal.fire({ icon: 'success', title: body.message, timer: 1600, showConfirmButton: false });
        })
        .catch(err => Swal.fire({ icon: 'error', title: 'Erro', text: err.message, customClass: { confirmButton: 'btn btn-danger' }, buttonsStyling: false }));
    });
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    errEl.style.display = 'none'; errEl.innerHTML = '';
    const id = idEl.value;
    const isEdit = !! id;
    const url = isEdit ? `${baseDespesa}/${id}` : storeUrl;

    const payload = {
      nome: nomeEl.value.trim(),
      categoria: catEl.value.trim() || null,
      descricao: descEl.value.trim() || null,
      valor: moneyToRaw(valorEl.value),
      tipo: tipoAtual(),
      dia_vencimento: diaEl.value ? parseInt(diaEl.value, 10) : null,
      data_inicio: readDate(inicioEl),
    };

    saveBtn.disabled = true;
    fetch(url, {
      method: isEdit ? 'PATCH' : 'POST',
      headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', Accept: 'application/json' },
      body: JSON.stringify(payload),
    })
      .then(r => r.json().then(b => ({ ok: r.ok, status: r.status, body: b })))
      .then(({ ok, status, body }) => {
        if (! ok) {
          if (status === 422 && body.errors) {
            errEl.innerHTML = Object.values(body.errors).flat().map(escapeHtml).join('<br>');
            errEl.style.display = '';
            return;
          }
          throw new Error(body.message || 'Erro ao salvar');
        }
        modal.hide();
        dt.draw(false);
        Swal.fire({ icon: 'success', title: isEdit ? 'Atualizada' : 'Cadastrada', text: body.message, timer: 1600, showConfirmButton: false });
      })
      .catch(err => { errEl.textContent = err.message; errEl.style.display = ''; })
      .finally(() => { saveBtn.disabled = false; refreshMasks(); });
  });
});
</script>
@include('_partials._masks-script')
@endsection
