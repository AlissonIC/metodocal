@extends('layouts/layoutMaster')

@section('title', 'Financeiro · Relatórios')

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/apex-charts/apex-charts.scss',
  'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
  'resources/assets/vendor/libs/select2/select2.scss',
])
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/apex-charts/apexcharts.js',
  'resources/assets/vendor/libs/flatpickr/flatpickr.js',
  'resources/assets/vendor/libs/select2/select2.js',
])
@endsection

@section('page-style')
<style>
  .kpi-card {
    border-left: 3px solid var(--bs-border-color);
    transition: border-color .2s;
  }
  .kpi-card .kpi-label { font-size: .75rem; text-transform: uppercase; letter-spacing: .05em; color: var(--bs-secondary-color); margin-bottom: .25rem; }
  .kpi-card .kpi-value { font-size: 1.4rem; font-weight: 700; line-height: 1.2; }
  .kpi-card .kpi-sub { font-size: .78rem; color: var(--bs-secondary-color); }
  .kpi-card.kpi-receita { border-left-color: #16a34a; }
  .kpi-card.kpi-pendente { border-left-color: #f59e0b; }
  .kpi-card.kpi-atrasado { border-left-color: #dc2626; }
  .kpi-card.kpi-a-receber { border-left-color: #16a34a; }
  .kpi-card.kpi-a-pagar { border-left-color: #f59e0b; }
  .kpi-card.kpi-saldo { border-left-color: var(--md-brand-1); }

  .relatorio-spinner {
    position: absolute; inset: 0; background: rgba(255,255,255,.7);
    display: none; align-items: center; justify-content: center; z-index: 5; border-radius: var(--bs-card-border-radius);
  }
  .relatorio-spinner.is-loading { display: flex; }
  .chart-wrap { position: relative; min-height: 280px; }
</style>
@endsection

@section('content')
<div class="card mb-4">
  <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <h5 class="card-title mb-0">Relatórios consolidados</h5>
      <p class="text-muted mb-0 mt-1 small">Receita de faturas, comissões a pagar/receber e saldo estimado.</p>
    </div>
    <small class="text-muted" id="periodo-info">—</small>
  </div>

  <div class="card-body filtros-bar">
    <div class="row g-2 g-md-3">
      <div class="col-6 col-md-3 col-lg-2">
        <label class="form-label small mb-1">De</label>
        <input type="text" id="f-de" class="form-control form-control-sm flatpickr-filtro" placeholder="dd/mm/aaaa">
      </div>
      <div class="col-6 col-md-3 col-lg-2">
        <label class="form-label small mb-1">Até</label>
        <input type="text" id="f-ate" class="form-control form-control-sm flatpickr-filtro" placeholder="dd/mm/aaaa">
      </div>
      <div class="col-6 col-md-3 col-lg-2">
        <label class="form-label small mb-1">Status fatura</label>
        <select id="f-status-fatura" class="form-select form-select-sm">
          <option value="">Todos</option>
          <option value="pendente">Pendente</option>
          <option value="paga">Paga</option>
          <option value="atrasada">Atrasada</option>
          <option value="cancelada">Cancelada</option>
          <option value="estornada">Estornada</option>
        </select>
      </div>
      <div class="col-6 col-md-3 col-lg-2">
        <label class="form-label small mb-1">Status comissão</label>
        <select id="f-status-comissao" class="form-select form-select-sm">
          <option value="">Todos</option>
          <option value="pendente">Pendente</option>
          <option value="paga">Paga</option>
          <option value="cancelada">Cancelada</option>
        </select>
      </div>
      <div class="col-6 col-md-3 col-lg-2">
        <label class="form-label small mb-1">Tipo comissão</label>
        <select id="f-tipo-comissao" class="form-select form-select-sm">
          <option value="">Ambos</option>
          <option value="a_receber">A receber</option>
          <option value="a_pagar">A pagar</option>
        </select>
      </div>
      <div class="col-6 col-md-3 col-lg-2">
        <label class="form-label small mb-1">Plano</label>
        <select id="f-plano" class="form-select form-select-sm">
          <option value="">Todos</option>
          @foreach ($plans as $p)
            <option value="{{ $p->id }}">{{ $p->nome }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-12 d-flex gap-2 align-items-end mt-2">
        <button id="btn-aplicar" class="btn btn-primary btn-sm"><i class="icon-base ti tabler-refresh me-1"></i> Aplicar</button>
        <button id="btn-limpar" class="btn btn-label-secondary btn-sm"><i class="icon-base ti tabler-eraser me-1"></i> Limpar</button>

        <div class="ms-auto btn-group btn-group-sm" role="group" aria-label="Período rápido">
          <button type="button" class="btn btn-outline-secondary preset-period" data-preset="7d">7 dias</button>
          <button type="button" class="btn btn-outline-secondary preset-period active" data-preset="month">Mês</button>
          <button type="button" class="btn btn-outline-secondary preset-period" data-preset="quarter">Trimestre</button>
          <button type="button" class="btn btn-outline-secondary preset-period" data-preset="year">Ano</button>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- ==================== KPIs ==================== --}}
<div class="row g-3 mb-4" id="kpis-wrap">
  <div class="col-6 col-md-4 col-xl-2">
    <div class="card kpi-card kpi-receita p-3">
      <div class="kpi-label">Receita (período)</div>
      <div class="kpi-value" data-kpi="faturas_recebidas">R$ 0,00</div>
      <div class="kpi-sub">Faturas pagas</div>
    </div>
  </div>
  <div class="col-6 col-md-4 col-xl-2">
    <div class="card kpi-card kpi-pendente p-3">
      <div class="kpi-label">Pendente</div>
      <div class="kpi-value" data-kpi="faturas_pendentes">R$ 0,00</div>
      <div class="kpi-sub">A receber</div>
    </div>
  </div>
  <div class="col-6 col-md-4 col-xl-2">
    <div class="card kpi-card kpi-atrasado p-3">
      <div class="kpi-label">Atrasado</div>
      <div class="kpi-value" data-kpi="faturas_atrasadas">R$ 0,00</div>
      <div class="kpi-sub">Vencidas</div>
    </div>
  </div>
  <div class="col-6 col-md-4 col-xl-2">
    <div class="card kpi-card kpi-a-receber p-3">
      <div class="kpi-label">Comissões a receber</div>
      <div class="kpi-value" data-kpi="comissoes_a_receber">R$ 0,00</div>
      <div class="kpi-sub">Período</div>
    </div>
  </div>
  <div class="col-6 col-md-4 col-xl-2">
    <div class="card kpi-card kpi-a-pagar p-3">
      <div class="kpi-label">Comissões a pagar</div>
      <div class="kpi-value" data-kpi="comissoes_a_pagar">R$ 0,00</div>
      <div class="kpi-sub">Período</div>
    </div>
  </div>
  <div class="col-6 col-md-4 col-xl-2">
    <div class="card kpi-card kpi-saldo p-3">
      <div class="kpi-label">Saldo estimado</div>
      <div class="kpi-value" data-kpi="saldo_estimado">R$ 0,00</div>
      <div class="kpi-sub">Receita - comissões</div>
    </div>
  </div>
</div>

{{-- ==================== Gráficos ==================== --}}
<div class="row g-3 mb-4">
  <div class="col-lg-8">
    <div class="card position-relative">
      <div class="card-header border-bottom d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0">Receita de faturas pagas</h6>
        <small class="text-muted">Linha do tempo</small>
      </div>
      <div class="card-body chart-wrap">
        <div class="relatorio-spinner" data-chart-spinner="receita"><div class="spinner-border text-primary"></div></div>
        <div id="chart-receita"></div>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card position-relative h-100">
      <div class="card-header border-bottom"><h6 class="card-title mb-0">Status das faturas</h6></div>
      <div class="card-body chart-wrap">
        <div class="relatorio-spinner" data-chart-spinner="status"><div class="spinner-border text-primary"></div></div>
        <div id="chart-status"></div>
      </div>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="card position-relative">
      <div class="card-header border-bottom d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0">Comissões a receber vs a pagar</h6>
        <small class="text-muted">Por dia/mês</small>
      </div>
      <div class="card-body chart-wrap">
        <div class="relatorio-spinner" data-chart-spinner="comissoes"><div class="spinner-border text-primary"></div></div>
        <div id="chart-comissoes"></div>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-header border-bottom"><h6 class="card-title mb-0">Top usuários (receita)</h6></div>
      <div class="card-body">
        <ul class="list-group list-group-flush" id="top-usuarios">
          <li class="list-group-item text-muted small text-center py-4">Sem dados no período.</li>
        </ul>
      </div>
    </div>
  </div>
</div>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const dataUrl = "{{ route('admin.financeiro.relatorios.data') }}";
  const brandPrimary = '#B8860B';
  const brandSecondary = '#D4AF37';

  // ---- Flatpickr ----
  flatpickr('.flatpickr-filtro', {
    altInput: true,
    altFormat: 'd/m/Y',
    dateFormat: 'Y-m-d',
    allowInput: true,
  });

  // ---- Helpers ----
  const fmtMoney = v => 'R$ ' + Number(v || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

  function setPreset(preset) {
    const today = new Date();
    let de = new Date(today), ate = new Date(today);
    if (preset === '7d')      { de.setDate(today.getDate() - 6); }
    if (preset === 'month')   { de = new Date(today.getFullYear(), today.getMonth(), 1); ate = new Date(today.getFullYear(), today.getMonth() + 1, 0); }
    if (preset === 'quarter') { de.setMonth(today.getMonth() - 2); de.setDate(1); }
    if (preset === 'year')    { de = new Date(today.getFullYear(), 0, 1); ate = new Date(today.getFullYear(), 11, 31); }
    document.getElementById('f-de')._flatpickr?.setDate(de);
    document.getElementById('f-ate')._flatpickr?.setDate(ate);
  }

  // ---- ApexCharts: instâncias ----
  const chartReceita = new ApexCharts(document.getElementById('chart-receita'), {
    chart: { type: 'area', height: 280, toolbar: { show: false }, sparkline: { enabled: false } },
    colors: [brandPrimary],
    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05 } },
    series: [{ name: 'Receita', data: [] }],
    xaxis: { categories: [], labels: { style: { fontSize: '11px' } } },
    yaxis: { labels: { formatter: v => fmtMoney(v) } },
    stroke: { curve: 'smooth', width: 3 },
    dataLabels: { enabled: false },
    tooltip: { y: { formatter: v => fmtMoney(v) } },
    grid: { borderColor: 'rgba(0,0,0,.05)' },
    noData: { text: 'Sem dados no período' },
  });
  chartReceita.render();

  const chartStatus = new ApexCharts(document.getElementById('chart-status'), {
    chart: { type: 'donut', height: 280 },
    colors: ['#f59e0b', '#16a34a', '#dc2626', '#82868b', '#0ea5e9'],
    series: [],
    labels: [],
    legend: { position: 'bottom', fontSize: '12px' },
    dataLabels: { enabled: true, formatter: (val, opts) => opts.w.config.series[opts.seriesIndex] + ' (' + Math.round(val) + '%)' },
    plotOptions: { pie: { donut: { size: '65%' } } },
    tooltip: { y: { formatter: v => v + ' fatura(s)' } },
    noData: { text: 'Sem dados no período' },
  });
  chartStatus.render();

  const chartComissoes = new ApexCharts(document.getElementById('chart-comissoes'), {
    chart: { type: 'bar', height: 280, stacked: false, toolbar: { show: false } },
    colors: ['#16a34a', '#f59e0b'],
    series: [
      { name: 'A receber', data: [] },
      { name: 'A pagar', data: [] },
    ],
    xaxis: { categories: [], labels: { style: { fontSize: '11px' } } },
    yaxis: { labels: { formatter: v => fmtMoney(v) } },
    dataLabels: { enabled: false },
    legend: { position: 'top' },
    tooltip: { y: { formatter: v => fmtMoney(v) } },
    plotOptions: { bar: { columnWidth: '55%', borderRadius: 4 } },
    grid: { borderColor: 'rgba(0,0,0,.05)' },
    noData: { text: 'Sem dados no período' },
  });
  chartComissoes.render();

  // ---- Loader visual ----
  function setLoading(on) {
    document.querySelectorAll('.relatorio-spinner').forEach(el => {
      el.classList.toggle('is-loading', !!on);
    });
  }

  // ---- AJAX: carrega dados ----
  function carregar() {
    setLoading(true);
    const params = new URLSearchParams({
      de:               document.getElementById('f-de').value || '',
      ate:              document.getElementById('f-ate').value || '',
      status_fatura:    document.getElementById('f-status-fatura').value || '',
      status_comissao:  document.getElementById('f-status-comissao').value || '',
      tipo_comissao:    document.getElementById('f-tipo-comissao').value || '',
      plan_id:          document.getElementById('f-plano').value || '',
    });

    fetch(`${dataUrl}?${params.toString()}`, { headers: { Accept: 'application/json' } })
      .then(r => r.json())
      .then(d => {
        // KPIs
        Object.entries(d.kpis).forEach(([k, v]) => {
          const el = document.querySelector(`[data-kpi="${k}"]`);
          if (el) el.textContent = fmtMoney(v);
        });

        // Info do período
        document.getElementById('periodo-info').textContent =
          `Período: ${d.periodo.de.split('-').reverse().join('/')} a ${d.periodo.ate.split('-').reverse().join('/')} · ${d.periodo.granularidade === 'mes' ? 'agrupado por mês' : 'agrupado por dia'}`;

        // Gráfico receita
        chartReceita.updateOptions({
          xaxis: { categories: d.serie_receita.labels },
        });
        chartReceita.updateSeries([{ name: 'Receita', data: d.serie_receita.valores }]);

        // Gráfico status
        const statusMap = { pendente: 'Pendente', paga: 'Paga', atrasada: 'Atrasada', cancelada: 'Cancelada', estornada: 'Estornada' };
        const labels = d.status_faturas.map(r => statusMap[r.status] || r.status);
        const series = d.status_faturas.map(r => r.qtd);
        chartStatus.updateOptions({ labels: labels });
        chartStatus.updateSeries(series);

        // Gráfico comissões
        chartComissoes.updateOptions({
          xaxis: { categories: d.serie_comissoes.a_receber.labels },
        });
        chartComissoes.updateSeries([
          { name: 'A receber', data: d.serie_comissoes.a_receber.valores },
          { name: 'A pagar', data: d.serie_comissoes.a_pagar.valores },
        ]);

        // Top usuários
        const ul = document.getElementById('top-usuarios');
        if (d.top_usuarios.length === 0) {
          ul.innerHTML = '<li class="list-group-item text-muted small text-center py-4">Sem dados no período.</li>';
        } else {
          ul.innerHTML = d.top_usuarios.map((r, i) => `
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <div>
                <span class="badge bg-label-secondary me-2">${i + 1}</span>
                <span class="fw-medium">${r.user}</span>
                <small class="text-muted d-block ms-4 mt-1">${r.qtd} fatura(s)</small>
              </div>
              <span class="fw-semibold text-success">${fmtMoney(r.total)}</span>
            </li>
          `).join('');
        }
      })
      .catch(err => {
        console.error('Falha ao carregar relatório:', err);
      })
      .finally(() => setLoading(false));
  }

  // ---- Eventos ----
  document.getElementById('btn-aplicar').addEventListener('click', carregar);

  // Preset de período
  document.querySelectorAll('.preset-period').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.preset-period').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      setPreset(btn.dataset.preset);
      carregar();
    });
  });

  document.getElementById('btn-limpar').addEventListener('click', () => {
    document.getElementById('f-de')._flatpickr?.clear();
    document.getElementById('f-ate')._flatpickr?.clear();
    ['f-status-fatura', 'f-status-comissao', 'f-tipo-comissao', 'f-plano'].forEach(id => {
      document.getElementById(id).value = '';
    });
    document.querySelectorAll('.preset-period').forEach(b => b.classList.remove('active'));
    document.querySelector('.preset-period[data-preset="month"]').classList.add('active');
    carregar();
  });

  // Filtros disparam auto-update após meio segundo
  let timer;
  ['f-status-fatura', 'f-status-comissao', 'f-tipo-comissao', 'f-plano', 'f-de', 'f-ate'].forEach(id => {
    document.getElementById(id).addEventListener('change', () => {
      clearTimeout(timer);
      timer = setTimeout(carregar, 350);
    });
  });

  // Carga inicial: mês corrente
  setPreset('month');
  carregar();
});
</script>
@endsection
