@extends('layouts/layoutMaster')

@section('title', 'Financiamentos')

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
  .linha-atrasada { background-color: rgba(var(--bs-danger-rgb), .04); }
</style>
@endsection

@section('content')

{{-- ==================== KPIs ==================== --}}
<div class="row g-3 mb-4">
  <div class="col-md-3 col-sm-6">
    <div class="card h-100"><div class="card-body">
      <span class="text-heading">A receber</span>
      <h4 class="my-1 text-warning">R$ {{ number_format($kpi_a_receber, 2, ',', '.') }}</h4>
      <small class="text-muted">Parcelas em aberto</small>
    </div></div>
  </div>
  <div class="col-md-3 col-sm-6">
    <div class="card h-100"><div class="card-body">
      <span class="text-heading">Atrasado</span>
      <h4 class="my-1 text-danger">R$ {{ number_format($kpi_atrasado, 2, ',', '.') }}</h4>
      <small class="text-muted">{{ $qtd_atrasadas }} parcela(s) vencida(s)</small>
    </div></div>
  </div>
  <div class="col-md-3 col-sm-6">
    <div class="card h-100"><div class="card-body">
      <span class="text-heading">Vence neste mês</span>
      <h4 class="my-1 text-info">R$ {{ number_format($kpi_vence_mes, 2, ',', '.') }}</h4>
      <small class="text-muted">{{ now()->translatedFormat('F/Y') }}</small>
    </div></div>
  </div>
  <div class="col-md-3 col-sm-6">
    <div class="card h-100"><div class="card-body">
      <span class="text-heading">Recebido</span>
      <h4 class="my-1 text-success">R$ {{ number_format($kpi_recebido, 2, ',', '.') }}</h4>
      <small class="text-muted">Parcelas já pagas</small>
    </div></div>
  </div>
</div>

<div class="card">
  <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <h5 class="card-title mb-0"><i class="icon-base ti tabler-calendar-dollar me-1"></i> Financiamentos</h5>
      <p class="text-muted mb-0 mt-1 small">
        Parcelas geradas a partir da <strong>primeira parcela</strong> cadastrada em cada processo.
        O status <strong>Atrasada</strong> é calculado pela data de hoje.
      </p>
    </div>
    <button type="button" id="btn-exportar" class="btn btn-label-success">
      <i class="icon-base ti tabler-file-spreadsheet me-1"></i> Exportar
    </button>
  </div>

  {{-- ==================== FILTROS ==================== --}}
  <div class="card-body filtros-bar">
    <div class="row g-2 g-md-3">
      <div class="col-6 col-md-4 col-lg-2">
        <label class="form-label small mb-1">Status</label>
        <select id="filtro-status" class="form-select form-select-sm" data-placeholder="Todos">
          <option value=""></option>
          <option value="pendente">Pendente (no prazo)</option>
          <option value="atrasada">Atrasada</option>
          <option value="paga">Paga</option>
          <option value="cancelada">Cancelada</option>
        </select>
      </div>
      <div class="col-6 col-md-4 col-lg-3">
        <label class="form-label small mb-1">Banco</label>
        <select id="filtro-banco" class="form-select form-select-sm" data-placeholder="Todos">
          <option value=""></option>
          @foreach ($bancos as $b)
            <option value="{{ $b->id }}">{{ $b->nome }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-6 col-md-4 col-lg-2">
        <label class="form-label small mb-1">Venc. de</label>
        <input type="text" id="filtro-de" class="form-control form-control-sm flatpickr-filtro" placeholder="dd/mm/aaaa">
      </div>
      <div class="col-6 col-md-4 col-lg-2">
        <label class="form-label small mb-1">Venc. até</label>
        <input type="text" id="filtro-ate" class="form-control form-control-sm flatpickr-filtro" placeholder="dd/mm/aaaa">
      </div>
      <div class="col-6 col-md-4 col-lg-2">
        <label class="form-label small mb-1">Processo nº</label>
        <input type="number" id="filtro-processo" class="form-control form-control-sm" placeholder="Ex.: 58"
               value="{{ $processoFiltrado }}">
      </div>
      <div class="col-6 col-md-4 col-lg-1 d-flex align-items-end">
        <button id="btn-limpar-filtros" class="btn btn-label-secondary btn-sm w-100" title="Limpar filtros">
          <i class="icon-base ti tabler-eraser"></i>
        </button>
      </div>
    </div>
  </div>

  <div class="card-datatable">
    <table class="datatables-financiamentos table border-top dt-responsive" style="width:100%">
      <thead>
        <tr>
          <th>Processo / Cliente</th>
          <th>Parcela</th>
          <th>Vencimento</th>
          <th>Valor</th>
          <th>Status / última alteração</th>
          <th class="text-end">Ações</th>
        </tr>
      </thead>
    </table>
  </div>
</div>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const csrf = document.querySelector('meta[name="csrf-token"]').content;
  const baseUrl = "{{ url('/painel/financeiro/financiamentos') }}";

  $('#filtro-status, #filtro-banco').select2({ allowClear: true, width: '100%',
    placeholder: function () { return $(this).data('placeholder'); } });

  flatpickr('.flatpickr-filtro', { altInput: true, altFormat: 'd/m/Y', dateFormat: 'Y-m-d', allowInput: true });

  const escapeHtml = (s) => String(s ?? '').replace(/[&<>"']/g, c =>
    ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]));

  function filtros() {
    return {
      status:         $('#filtro-status').val() || '',
      banco_id:       $('#filtro-banco').val() || '',
      vencimento_de:  document.getElementById('filtro-de').value,
      vencimento_ate: document.getElementById('filtro-ate').value,
      processo_id:    document.getElementById('filtro-processo').value,
    };
  }

  const dt = new DataTable('.datatables-financiamentos', {
    processing: true, serverSide: true, responsive: true,
    ajax: {
      url: baseUrl + '/datatable',
      data: function (d) { Object.assign(d, filtros()); },
    },
    columns: [
      {
        data: null, responsivePriority: 1, orderable: false,
        render: (row) => {
          const linha2 = [row.cliente_nome, row.banco_nome].filter(Boolean).map(escapeHtml).join(' · ');
          return `<a href="${row.processo_url}" class="fw-medium text-body text-decoration-none">${escapeHtml(row.processo_label)}</a>`
               + (linha2 ? `<div class="small text-muted">${linha2}</div>` : '');
        },
      },
      { data: 'parcela_label',  responsivePriority: 3, className: 'text-nowrap' },
      {
        data: null, responsivePriority: 2, className: 'text-nowrap',
        render: (row) => row.dias_atraso > 0
          ? `${row.vencimento_fmt}<div class="small text-danger">${row.dias_atraso} dia(s) em atraso</div>`
          : row.vencimento_fmt + (row.pago_em_fmt ? `<div class="small text-success">pago em ${row.pago_em_fmt}</div>` : ''),
      },
      { data: 'valor_fmt',      responsivePriority: 1, className: 'text-nowrap fw-semibold' },
      {
        data: null, responsivePriority: 2, orderable: false, searchable: false,
        render: (row) => {
          if (! row.alterado_por_nome) return row.status_badge;
          // Quem mexeu por último no status — leva ao cadastro do usuário
          const quem = `<a href="${row.alterado_por_url}" class="text-muted text-decoration-none"
                           title="Abrir cadastro de ${escapeHtml(row.alterado_por_nome)}">
                          <i class="icon-base ti tabler-user-check"></i> ${escapeHtml(row.alterado_por_nome)}
                        </a>`;
          const quando = row.alterado_em_fmt ? `<span class="text-muted"> · ${escapeHtml(row.alterado_em_fmt)}</span>` : '';
          return `${row.status_badge}<div class="small mt-1">${quem}${quando}</div>`;
        },
      },
      {
        data: null, responsivePriority: 1, orderable: false, searchable: false, className: 'text-end text-nowrap',
        render: (row) => {
          const paga = row.status_atual === 'paga';
          const cancelada = row.status_atual === 'cancelada';
          const btnPaga = paga
            ? `<button class="btn btn-sm btn-icon parc-pendente" data-id="${row.id}" title="Desfazer pagamento"><i class="icon-base ti tabler-arrow-back-up icon-22px"></i></button>`
            : `<button class="btn btn-sm btn-icon text-success parc-paga" data-id="${row.id}" title="Marcar como paga"><i class="icon-base ti tabler-check icon-22px"></i></button>`;
          const btnCancelar = cancelada
            ? `<button class="btn btn-sm btn-icon text-info parc-pendente" data-id="${row.id}" title="Reativar parcela"><i class="icon-base ti tabler-rotate icon-22px"></i></button>`
            : `<button class="btn btn-sm btn-icon text-secondary parc-cancelar" data-id="${row.id}" title="Cancelar parcela"><i class="icon-base ti tabler-ban icon-22px"></i></button>`;
          return `
            <div class="d-inline-flex flex-nowrap gap-1 justify-content-end">
              ${btnPaga}
              ${btnCancelar}
              <a href="${row.processo_url}" class="btn btn-sm btn-icon" title="Abrir processo"><i class="icon-base ti tabler-external-link icon-22px"></i></a>
            </div>`;
        },
      },
    ],
    createdRow: function (tr, data) {
      if (data.status_atual === 'atrasada') tr.classList.add('linha-atrasada');
    },
    order: [],
    language: {
      processing: 'Carregando...', search: 'Buscar:', lengthMenu: '_MENU_ por página',
      info: 'Exibindo _START_ a _END_ de _TOTAL_', infoEmpty: 'Nenhum registro', infoFiltered: '(filtrado de _MAX_)',
      zeroRecords: 'Nenhuma parcela encontrada',
      emptyTable: 'Nenhuma parcela gerada. Cadastre banco, valor, quantidade e a data da primeira parcela em um processo.',
      paginate: { first: '«', previous: '‹', next: '›', last: '»' },
    },
    layout: {
      topStart: { features: [{ pageLength: { menu: [10, 25, 50, 100] } }] },
      topEnd: { features: [{ search: { placeholder: 'Buscar processo ou cliente' } }] },
    },
  });

  $('#filtro-status, #filtro-banco').on('change', () => dt.draw());
  ['filtro-de', 'filtro-ate', 'filtro-processo'].forEach(id => {
    document.getElementById(id).addEventListener('change', () => dt.draw());
  });
  document.getElementById('btn-limpar-filtros').addEventListener('click', () => {
    $('#filtro-status, #filtro-banco').val(null).trigger('change');
    document.getElementById('filtro-de')._flatpickr?.clear();
    document.getElementById('filtro-ate')._flatpickr?.clear();
    document.getElementById('filtro-processo').value = '';
    dt.draw();
  });

  document.getElementById('btn-exportar').addEventListener('click', () => {
    const params = new URLSearchParams({ ...filtros(), busca: dt.search() });
    [...params.keys()].forEach(k => { if (! params.get(k)) params.delete(k); });
    window.location = "{{ route('admin.financiamentos.export') }}?" + params.toString();
  });

  // ---- Ações de status (tudo na mesma tela, sem sair da listagem) ----
  function acao(url, okTitle) {
    fetch(url, {
      method: 'PATCH',
      headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json', 'Content-Type': 'application/json' },
      body: '{}',
    })
      .then(async r => { const b = await r.json().catch(() => ({})); if (! r.ok) throw new Error(b.message || 'Falha na operação.'); return b; })
      .then(b => {
        dt.draw(false);
        Swal.fire({ icon: 'success', title: okTitle, text: b.message, timer: 1600, showConfirmButton: false });
      })
      .catch(e => Swal.fire({ icon: 'error', title: 'Erro', text: e.message,
        customClass: { confirmButton: 'btn btn-danger' }, buttonsStyling: false }));
  }

  document.querySelector('.datatables-financiamentos').addEventListener('click', function (e) {
    const paga = e.target.closest('.parc-paga');
    if (paga) return acao(`${baseUrl}/parcelas/${paga.dataset.id}/paga`, 'Paga');

    const pendente = e.target.closest('.parc-pendente');
    if (pendente) return acao(`${baseUrl}/parcelas/${pendente.dataset.id}/pendente`, 'Reaberta');

    const cancelar = e.target.closest('.parc-cancelar');
    if (cancelar) {
      Swal.fire({
        title: 'Cancelar esta parcela?',
        text: 'Ela deixa de contar como valor a receber. Dá para reativar depois.',
        icon: 'warning', showCancelButton: true,
        confirmButtonText: 'Sim, cancelar', cancelButtonText: 'Voltar',
        customClass: { confirmButton: 'btn btn-warning me-2', cancelButton: 'btn btn-label-secondary' },
        buttonsStyling: false,
      }).then(r => { if (r.isConfirmed) acao(`${baseUrl}/parcelas/${cancelar.dataset.id}/cancelar`, 'Cancelada'); });
    }
  });
});
</script>
@endsection
