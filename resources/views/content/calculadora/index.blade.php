@extends('layouts/layoutMaster')

@section('title', 'Calculadora de Quitação')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/select2/select2.js',
  'resources/assets/vendor/libs/cleave-zen/cleave-zen.js',
])
@endsection

@section('page-style')
<style>
  .calc-kpi {
    border-left: 3px solid var(--bs-border-color);
    background: var(--bs-body-bg);
    transition: transform .15s;
  }
  .calc-kpi .kpi-label { font-size: .72rem; text-transform: uppercase; letter-spacing: .05em; color: var(--bs-secondary-color); }
  .calc-kpi .kpi-value { font-size: 1.5rem; font-weight: 700; line-height: 1.2; }
  .calc-kpi .kpi-sub { font-size: .78rem; color: var(--bs-secondary-color); }
  .calc-kpi.min-highlight { border-left-color: #16a34a; }
  .calc-kpi.max-highlight { border-left-color: #dc2626; }
  .calc-kpi.mid-highlight { border-left-color: var(--md-brand-1); }

  .banco-linha td { vertical-align: middle; }
  .banco-linha .melhor-badge { font-size: .65rem; }
  .banco-linha.is-melhor { background: rgba(22, 163, 74, .06); }
  .banco-linha.is-pior { background: rgba(220, 38, 38, .04); }

  .taxa-badge { font-variant-numeric: tabular-nums; }
</style>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
  <div>
    <h4 class="mb-0"><i class="icon-base ti tabler-calculator me-1"></i> Calculadora de Quitação</h4>
    <p class="text-muted mb-0 small">Simule o valor de quitação do veículo em diferentes bancos, considerando as taxas de cada instituição e a comissão do serviço.</p>
  </div>
</div>

<div class="row g-4">
  {{-- ==================== FORMULÁRIO ==================== --}}
  <div class="col-lg-4">
    <div class="card">
      <div class="card-header border-bottom"><h5 class="card-title mb-0">Parâmetros</h5></div>
      <div class="card-body">
        <div class="mb-3">
          <label class="form-label">Tipo de serviço *</label>
          <select id="calc-servico" class="form-select">
            @foreach ($servicos as $s)
              <option value="{{ $s->id }}"
                      data-comissao="{{ (float) ($s->valor_padrao ?? 0) }}"
                      data-descricao="{{ $s->descricao }}">
                {{ $s->nome }}
                @if ($s->valor_padrao) — R$ {{ number_format((float) $s->valor_padrao, 2, ',', '.') }} @endif
              </option>
            @endforeach
          </select>
          <small class="text-muted" id="servico-desc"></small>
        </div>

        <div class="mb-3">
          <label class="form-label">Valor da dívida do carro (R$) *</label>
          <input type="text" inputmode="numeric" class="form-control mask-money" id="calc-divida" placeholder="0,00">
          <small class="text-muted">Saldo devedor atual junto ao banco.</small>
        </div>

        <div class="mb-3">
          <label class="form-label">Bancos <span class="text-muted small">(um ou mais)</span></label>
          <select id="calc-bancos" class="form-select" multiple>
            @foreach ($bancos as $b)
              <option value="{{ $b->id }}"
                      data-nome="{{ $b->nome }}"
                      data-taxa="{{ (float) $b->taxa }}">
                {{ $b->nome }} · {{ number_format((float) $b->taxa, 2, ',', '.') }}%
              </option>
            @endforeach
          </select>
          <small class="text-muted">A taxa representa o <strong>% da dívida</strong> que o banco aceita para quitar.</small>
        </div>

        <div class="row">
          <div class="col-6 mb-0">
            <label class="form-label">Parcelas</label>
            <input type="number" class="form-control" id="calc-parcelas" min="1" max="120" value="12">
          </div>
          <div class="col-6 mb-0 d-flex align-items-end">
            <button id="btn-todos-bancos" class="btn btn-label-secondary btn-sm w-100">
              <i class="icon-base ti tabler-list-check me-1"></i> Selecionar todos
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- ==================== RESULTADOS ==================== --}}
  <div class="col-lg-8">
    {{-- Placeholder inicial --}}
    <div class="card text-center" id="calc-vazio">
      <div class="card-body py-5">
        <i class="icon-base ti tabler-calculator icon-48px text-muted"></i>
        <h5 class="mt-3">Preencha os campos ao lado</h5>
        <p class="text-muted mb-0">Informe a dívida e selecione ao menos um banco para ver a simulação em tempo real.</p>
      </div>
    </div>

    {{-- Resumo global --}}
    <div id="calc-resumo" class="row g-3 mb-3" style="display: none;">
      <div class="col-md-4">
        <div class="card calc-kpi min-highlight p-3">
          <div class="kpi-label"><i class="icon-base ti tabler-trending-down"></i> Melhor cenário</div>
          <div class="kpi-value text-success" data-resumo="min">R$ 0,00</div>
          <div class="kpi-sub" data-resumo="min-banco">—</div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card calc-kpi max-highlight p-3">
          <div class="kpi-label"><i class="icon-base ti tabler-trending-up"></i> Pior cenário</div>
          <div class="kpi-value text-danger" data-resumo="max">R$ 0,00</div>
          <div class="kpi-sub" data-resumo="max-banco">—</div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card calc-kpi mid-highlight p-3">
          <div class="kpi-label"><i class="icon-base ti tabler-scale"></i> Economia média</div>
          <div class="kpi-value" data-resumo="economia-media">R$ 0,00</div>
          <div class="kpi-sub" data-resumo="economia-media-pct">— vs. dívida cheia</div>
        </div>
      </div>
    </div>

    {{-- Tabela comparativa por banco --}}
    <div class="card" id="calc-tabela-wrap" style="display: none;">
      <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="card-title mb-0">Simulação por banco</h5>
        <small class="text-muted" id="calc-info-parcelas">—</small>
      </div>
      <div class="table-responsive text-nowrap">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>Banco</th>
              <th class="text-end">Taxa</th>
              <th class="text-end">Mínimo (banco)</th>
              <th class="text-end">Comissão</th>
              <th class="text-end">Valor final</th>
              <th class="text-end">Parcela</th>
              <th class="text-end">Desconto</th>
            </tr>
          </thead>
          <tbody id="calc-tbody"></tbody>
          <tfoot class="table-light">
            <tr class="fw-semibold">
              <td colspan="4" class="text-end text-muted small">Ticket médio de quitação</td>
              <td class="text-end" id="calc-media-final">R$ 0,00</td>
              <td class="text-end" id="calc-media-parcela">R$ 0,00</td>
              <td class="text-end" id="calc-media-desconto">R$ 0,00</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const $servico  = $('#calc-servico');
  const $bancos   = $('#calc-bancos');
  const dividaEl  = document.getElementById('calc-divida');
  const parcelaEl = document.getElementById('calc-parcelas');
  const descEl    = document.getElementById('servico-desc');
  const vazio     = document.getElementById('calc-vazio');
  const resumo    = document.getElementById('calc-resumo');
  const tabWrap   = document.getElementById('calc-tabela-wrap');
  const tbody     = document.getElementById('calc-tbody');
  const infoParc  = document.getElementById('calc-info-parcelas');

  // Select2
  $servico.select2({ width: '100%', minimumResultsForSearch: 5 });
  $bancos.select2({
    width: '100%',
    closeOnSelect: false,
    placeholder: 'Selecione um ou mais bancos',
  });

  document.getElementById('btn-todos-bancos').addEventListener('click', function () {
    const todos = Array.from($bancos.find('option')).map(o => o.value);
    $bancos.val(todos).trigger('change');
  });

  // Helpers
  const fmtMoney = v => 'R$ ' + Number(v || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  const escapeHtml = (s) => String(s ?? '').replace(/[&<>"']/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]));

  function parseMoney(v) {
    const s = String(v || '').trim();
    if (! s) return 0;
    // Sempre BR aqui (mask-money aplicada ao input)
    return parseFloat(s.replace(/\./g, '').replace(',', '.')) || 0;
  }

  function atualizarDescServico() {
    const opt = $servico.find('option:selected');
    descEl.textContent = opt.data('descricao') || '';
  }
  atualizarDescServico();
  $servico.on('change', () => { atualizarDescServico(); calcular(); });

  // ---- Cálculo principal ----
  function calcular() {
    const divida = parseMoney(dividaEl.value);
    const comissao = parseFloat($servico.find('option:selected').data('comissao') || 0);
    const parcelas = Math.max(1, parseInt(parcelaEl.value || '12', 10));

    const bancosSel = $bancos.find('option:selected').toArray().map(o => ({
      id: o.value,
      nome: $(o).data('nome'),
      taxa: parseFloat($(o).data('taxa') || 0),
    }));

    if (! divida || bancosSel.length === 0) {
      vazio.style.display = '';
      resumo.style.display = 'none';
      tabWrap.style.display = 'none';
      return;
    }
    vazio.style.display = 'none';
    resumo.style.display = '';
    tabWrap.style.display = '';

    infoParc.textContent = `Parcelamento em ${parcelas}x · comissão do serviço: ${fmtMoney(comissao)}`;

    // Calcula linhas
    const linhas = bancosSel.map(b => {
      const minimo = divida * (b.taxa / 100);
      const final = minimo + comissao;
      const parcela = final / parcelas;
      const desconto = divida - final;
      return { ...b, minimo, final, parcela, desconto };
    });

    // Ordena do MENOR final para o MAIOR (melhor primeiro)
    linhas.sort((a, b) => a.final - b.final);

    const melhor = linhas[0];
    const pior = linhas[linhas.length - 1];

    // KPIs resumo
    document.querySelector('[data-resumo="min"]').textContent = fmtMoney(melhor.final);
    document.querySelector('[data-resumo="min-banco"]').textContent = melhor.nome + ' (' + melhor.taxa.toFixed(2).replace('.', ',') + '%)';
    document.querySelector('[data-resumo="max"]').textContent = fmtMoney(pior.final);
    document.querySelector('[data-resumo="max-banco"]').textContent = pior.nome + ' (' + pior.taxa.toFixed(2).replace('.', ',') + '%)';

    const somaDesconto = linhas.reduce((s, l) => s + l.desconto, 0);
    const economiaMedia = somaDesconto / linhas.length;
    document.querySelector('[data-resumo="economia-media"]').textContent = fmtMoney(economiaMedia);
    const pctEconomia = divida > 0 ? (economiaMedia / divida * 100) : 0;
    document.querySelector('[data-resumo="economia-media-pct"]').textContent =
      pctEconomia.toFixed(1).replace('.', ',') + '% em média vs. dívida cheia';

    // Tabela
    tbody.innerHTML = linhas.map((l, i) => {
      const isMin = i === 0 && linhas.length > 1;
      const isMax = i === linhas.length - 1 && linhas.length > 1;
      const cls = isMin ? 'is-melhor' : (isMax ? 'is-pior' : '');
      const badge = isMin
        ? '<span class="badge bg-label-success melhor-badge ms-1">Melhor</span>'
        : (isMax ? '<span class="badge bg-label-danger melhor-badge ms-1">Pior</span>' : '');
      const descontoCls = l.desconto >= 0 ? 'text-success' : 'text-danger';
      const descontoSinal = l.desconto >= 0 ? '' : '-';
      return `
        <tr class="banco-linha ${cls}">
          <td class="fw-medium">${escapeHtml(l.nome)}${badge}</td>
          <td class="text-end taxa-badge">${l.taxa.toFixed(2).replace('.', ',')}%</td>
          <td class="text-end">${fmtMoney(l.minimo)}</td>
          <td class="text-end text-muted">${fmtMoney(comissao)}</td>
          <td class="text-end fw-semibold">${fmtMoney(l.final)}</td>
          <td class="text-end">${fmtMoney(l.parcela)}</td>
          <td class="text-end ${descontoCls}">${descontoSinal}${fmtMoney(Math.abs(l.desconto))}</td>
        </tr>
      `;
    }).join('');

    // Rodapé — médias
    const mediaFinal = linhas.reduce((s, l) => s + l.final, 0) / linhas.length;
    const mediaParcela = linhas.reduce((s, l) => s + l.parcela, 0) / linhas.length;
    document.getElementById('calc-media-final').textContent = fmtMoney(mediaFinal);
    document.getElementById('calc-media-parcela').textContent = fmtMoney(mediaParcela);
    document.getElementById('calc-media-desconto').textContent = fmtMoney(economiaMedia);
  }

  // Eventos: qualquer mudança dispara cálculo
  $bancos.on('change', calcular);
  dividaEl.addEventListener('input', calcular);
  parcelaEl.addEventListener('input', calcular);
});
</script>
@include('_partials._masks-script')
@endsection
