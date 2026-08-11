@extends('layouts/layoutMaster')

@section('title', $processo->exists ? 'Editar processo' : 'Novo processo')

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/select2/select2.scss',
  'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
])
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/cleave-zen/cleave-zen.js',
  'resources/assets/vendor/libs/select2/select2.js',
  'resources/assets/vendor/libs/flatpickr/flatpickr.js',
])
@endsection

@section('page-style')
<style>
  /* Chevron rotaciona quando o collapse abre */
  .collapse-toggle[aria-expanded="true"] .toggle-icon { transform: rotate(180deg); }
  .collapse-toggle:hover { background-color: rgba(0,0,0,.02); }
</style>
@endsection

@section('content')
@php
  $editing = $processo->exists;
  $action = $editing ? route('processos.update', $processo) : route('processos.store');
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">{{ $editing ? 'Editar processo' : 'Novo processo' }}</h4>
  <a href="{{ $editing ? route('processos.show', $processo) : route('processos.index') }}" class="btn btn-label-secondary">
    <i class="ti tabler-arrow-left me-1"></i> Voltar
  </a>
</div>

@if ($errors->any())
  <div class="alert alert-danger">
    <ul class="mb-0">@foreach ($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
  </div>
@endif

<form method="POST" action="{{ $action }}">
  @csrf
  @if ($editing) @method('PATCH') @endif

  @if ($isAdmin)
    <div class="card mb-4">
      <div class="card-header"><h5 class="card-title mb-0">Vínculos internos (admin)</h5></div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6 mb-3 mb-md-0">
            <label class="form-label">Cliente *</label>
            <select name="user_id" id="user_id" class="form-select select2" required>
              <option value=""></option>
              @foreach ($clientes as $cliente)
                <option value="{{ $cliente->id }}" @selected(old('user_id', $processo->user_id) == $cliente->id)>
                  {{ $cliente->name }} · {{ $cliente->email }}
                </option>
              @endforeach
            </select>
            <small class="text-muted">Cliente da plataforma dono do processo.</small>
          </div>
          <div class="col-md-6">
            <label class="form-label">Comprador (opcional)</label>
            <select name="comprador_id" id="comprador_id" class="form-select select2">
              <option value="">— sem comprador vinculado —</option>
              @foreach ($compradores as $cmp)
                <option value="{{ $cmp->id }}" @selected(old('comprador_id', $processo->comprador_id) == $cmp->id)>
                  {{ $cmp->nome }} · {{ strtoupper($cmp->tipo_documento) }} {{ $cmp->documento }}
                </option>
              @endforeach
            </select>
            <small class="text-muted">Destino da operação. Visível apenas para admin.</small>
          </div>

          <div class="col-12 mt-4">
            <label class="form-label">Cliente titular com acesso ao painel (opcional)</label>
            <select name="cliente_user_id" id="cliente_user_id" class="form-select select2">
              <option value="">— sem acesso ao painel —</option>
              @foreach ($clientesFinais as $cf)
                <option value="{{ $cf->id }}" @selected(old('cliente_user_id', $processo->cliente_user_id) == $cf->id)>
                  {{ $cf->name }} · {{ $cf->email }}
                </option>
              @endforeach
            </select>
            <small class="text-muted">
              Usuário de nível <strong>Cliente</strong> que poderá entrar e acompanhar este processo:
              andamento, parcelas, link de pagamento, documentos e PDF. Cadastre-o antes em
              <a href="{{ route('admin.users.create') }}" target="_blank">Usuários → Novo usuário</a>.
            </small>
          </div>
        </div>
      </div>
    </div>
  @endif

  <div class="card mb-4">
    <div class="card-header"><h5 class="card-title mb-0">Serviço contratado</h5></div>
    <div class="card-body">
      <div class="row">
        @foreach ($servicos as $servico)
          <div class="col-md-4 mb-3">
            <div class="form-check custom-option custom-option-basic">
              <label class="form-check-label custom-option-content w-100 d-flex align-items-center" style="padding-block: 0.75rem;">
                <input class="form-check-input me-2 mt-0 servico-radio" type="radio" name="servico_id" value="{{ $servico->id }}"
                       data-comissao="{{ (float) ($servico->valor_padrao ?? 0) }}"
                       @checked((int) old('servico_id', $processo->servico_id) === $servico->id)>
                <span class="h6 mb-0">{{ $servico->nome }}</span>
              </label>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </div>

  {{-- =============== DADOS DA PESSOA + ENDEREÇO =============== --}}
  <div class="card mb-4">
    <div class="card-header"><h5 class="card-title mb-0">Dados pessoais e endereço</h5></div>
    <div class="card-body">
      {{-- Identificação --}}
      <h6 class="text-muted small text-uppercase mb-3" style="letter-spacing: .05em;">Identificação</h6>
      <div class="row">
        <div class="col-md-8 mb-4">
          <label class="form-label">Nome completo *</label>
          <input type="text" name="nome_completo" class="form-control" required maxlength="160" value="{{ old('nome_completo', $processo->nome_completo) }}">
        </div>
        <div class="col-md-4 mb-4">
          <label class="form-label">Tipo de documento *</label>
          <select name="tipo_documento" class="form-select" required>
            @foreach (['cpf' => 'CPF', 'cnpj' => 'CNPJ'] as $v => $l)
              <option value="{{ $v }}" @selected(old('tipo_documento', $processo->tipo_documento) === $v)>{{ $l }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-4 mb-4">
          <label class="form-label">Documento *</label>
          <input type="text" name="documento" class="form-control mask-cpf-cnpj" required maxlength="20" value="{{ old('documento', $processo->documento) }}" placeholder="000.000.000-00">
        </div>
        <div class="col-md-4 mb-4">
          <label class="form-label">E-mail de contato</label>
          <input type="email" name="email_contato" class="form-control" maxlength="160" value="{{ old('email_contato', $processo->email_contato) }}">
        </div>
        <div class="col-md-4 mb-4">
          <label class="form-label">Telefone de contato</label>
          <input type="text" name="telefone_contato" class="form-control mask-phone" maxlength="40" value="{{ old('telefone_contato', $processo->telefone_contato) }}" placeholder="(00) 00000-0000">
        </div>
      </div>

      <hr class="my-3">

      {{-- Endereço --}}
      <h6 class="text-muted small text-uppercase mb-3" style="letter-spacing: .05em;">Endereço</h6>
      <div class="row">
        <div class="col-md-3 mb-4">
          <label class="form-label">CEP</label>
          <input type="text" name="cep" class="form-control mask-cep" maxlength="10" value="{{ old('cep', $processo->cep) }}" placeholder="00000-000" data-cep-autocomplete>
        </div>
        <div class="col-md-7 mb-4">
          <label class="form-label">Logradouro</label>
          <input type="text" name="logradouro" class="form-control" maxlength="160" value="{{ old('logradouro', $processo->logradouro) }}" placeholder="Rua, avenida...">
        </div>
        <div class="col-md-2 mb-4">
          <label class="form-label">Número</label>
          <input type="text" name="numero" class="form-control" maxlength="20" value="{{ old('numero', $processo->numero) }}">
        </div>
        <div class="col-md-4 mb-4">
          <label class="form-label">Complemento</label>
          <input type="text" name="complemento" class="form-control" maxlength="80" value="{{ old('complemento', $processo->complemento) }}" placeholder="Apto, sala, bloco...">
        </div>
        <div class="col-md-3 mb-4">
          <label class="form-label">Bairro</label>
          <input type="text" name="bairro" class="form-control" maxlength="80" value="{{ old('bairro', $processo->bairro) }}">
        </div>
        <div class="col-md-3 mb-4">
          <label class="form-label">Cidade</label>
          <input type="text" name="cidade" class="form-control" maxlength="80" value="{{ old('cidade', $processo->cidade) }}">
        </div>
        <div class="col-md-2 mb-0">
          <label class="form-label">Estado</label>
          <select name="uf" class="form-select">
            <option value="">—</option>
            @foreach (['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'] as $uf)
              <option value="{{ $uf }}" @selected(strtoupper(old('uf', $processo->uf)) === $uf)>{{ $uf }}</option>
            @endforeach
          </select>
        </div>
      </div>
    </div>
  </div>

  {{-- =============== VEÍCULO (opcional / expandível) =============== --}}
  @php
    // Abre expandido se: houve validation error nos campos veiculo[*], se já existe veículo, ou se old() tem valores
    $veiculoAberto = (bool) ($veiculo && $veiculo->exists) || collect(old('veiculo', []))->filter()->isNotEmpty() || $errors->has('veiculo.*');
  @endphp
  <div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center collapse-toggle" role="button"
         data-bs-toggle="collapse" data-bs-target="#veiculo-section" aria-expanded="{{ $veiculoAberto ? 'true' : 'false' }}" aria-controls="veiculo-section" style="cursor: pointer;">
      <h5 class="card-title mb-0">
        <i class="icon-base ti tabler-car me-1"></i>
        Veículo
        <small class="text-muted fw-normal ms-1">(opcional)</small>
      </h5>
      <i class="icon-base ti tabler-chevron-down toggle-icon" style="transition: transform .2s;"></i>
    </div>
    <div class="collapse {{ $veiculoAberto ? 'show' : '' }}" id="veiculo-section">
      <div class="card-body">
        <div class="alert alert-primary py-2 mb-4 small d-flex align-items-center gap-2" role="alert">
          <i class="icon-base ti tabler-info-circle"></i>
          <div>Escolha o <strong>tipo</strong> e comece a digitar a marca — os modelos vêm direto da tabela FIPE (via BrasilAPI, gratuito).</div>
        </div>

        <div class="row">
          <div class="col-md-2 mb-4">
            <label class="form-label">Tipo *</label>
            <select id="veiculo-tipo" class="form-select">
              <option value="carros" selected>Carro</option>
              <option value="motos">Moto</option>
              <option value="caminhoes">Caminhão</option>
            </select>
          </div>
          <div class="col-md-3 mb-4">
            <label class="form-label">Placa</label>
            <input type="text" name="veiculo[placa]" class="form-control mask-placa" maxlength="10" value="{{ old('veiculo.placa', $veiculo?->placa) }}" placeholder="AAA-0A00">
          </div>
          <div class="col-md-3 mb-4">
            <label class="form-label">Marca (FIPE)</label>
            <select id="veiculo-marca-select" class="form-select"></select>
            <input type="hidden" name="veiculo[marca]" id="veiculo-marca-input" value="{{ old('veiculo.marca', $veiculo?->marca) }}">
          </div>
          <div class="col-md-4 mb-4">
            <label class="form-label">Modelo (FIPE)</label>
            <select id="veiculo-modelo-select" class="form-select"></select>
            <input type="hidden" name="veiculo[modelo]" id="veiculo-modelo-input" value="{{ old('veiculo.modelo', $veiculo?->modelo) }}">
          </div>
          <div class="col-md-2 mb-4">
            <label class="form-label">Ano fab.</label>
            <input type="number" name="veiculo[ano_fabricacao]" class="form-control" min="1900" max="{{ now()->year + 1 }}" value="{{ old('veiculo.ano_fabricacao', $veiculo?->ano_fabricacao) }}">
          </div>
          <div class="col-md-2 mb-4">
            <label class="form-label">Ano mod.</label>
            <input type="number" name="veiculo[ano_modelo]" class="form-control" min="1900" max="{{ now()->year + 2 }}" value="{{ old('veiculo.ano_modelo', $veiculo?->ano_modelo) }}">
          </div>
          <div class="col-md-2 mb-4">
            <label class="form-label">Cor</label>
            <input type="text" name="veiculo[cor]" class="form-control" maxlength="30" value="{{ old('veiculo.cor', $veiculo?->cor) }}">
          </div>
          <div class="col-md-3 mb-4">
            <label class="form-label">Combustível</label>
            <select name="veiculo[combustivel]" class="form-select">
              <option value="">—</option>
              @foreach (\App\Models\Veiculo::COMBUSTIVEIS as $v => $l)
                <option value="{{ $v }}" @selected(old('veiculo.combustivel', $veiculo?->combustivel) === $v)>{{ $l }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3 mb-4">
            <label class="form-label">KM</label>
            <input type="number" name="veiculo[quilometragem]" class="form-control" min="0" max="9999999" value="{{ old('veiculo.quilometragem', $veiculo?->quilometragem) }}">
          </div>
          <div class="col-md-4 mb-4">
            <label class="form-label">Chassi</label>
            <input type="text" name="veiculo[chassi]" class="form-control text-uppercase" maxlength="17" value="{{ old('veiculo.chassi', $veiculo?->chassi) }}" placeholder="17 caracteres">
          </div>
          <div class="col-md-4 mb-4">
            <label class="form-label">RENAVAM</label>
            <input type="text" name="veiculo[renavam]" class="form-control" maxlength="20" value="{{ old('veiculo.renavam', $veiculo?->renavam) }}">
          </div>
          <div class="col-md-4 mb-4">
            <label class="form-label">Valor FIPE (R$)</label>
            <input type="text" inputmode="numeric" name="veiculo[valor_fipe]" class="form-control mask-money" value="{{ old('veiculo.valor_fipe', $veiculo && $veiculo->valor_fipe ? number_format((float) $veiculo->valor_fipe, 2, ',', '.') : '') }}" placeholder="0,00">
          </div>
          <div class="col-12 mb-0">
            <label class="form-label">Observações do veículo</label>
            <textarea name="veiculo[observacoes]" class="form-control" rows="2" maxlength="2000" placeholder="Detalhes de conservação, itens de série, avarias...">{{ old('veiculo.observacoes', $veiculo?->observacoes) }}</textarea>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- =============== FINANCIAMENTO =============== --}}
  <div class="card mb-4">
    <div class="card-header">
      <h5 class="card-title mb-0"><i class="icon-base ti tabler-building-bank me-1"></i> Financiamento</h5>
      <small class="text-muted">Valor financiado e parcelamento contratado pelo cliente.</small>
    </div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-6 mb-4">
          <label class="form-label">Banco</label>
          <select name="banco_id" id="banco_id" class="form-select">
            <option value="" data-taxa="0">— sem banco definido —</option>
            @foreach ($bancos as $banco)
              <option value="{{ $banco->id }}" data-taxa="{{ (float) $banco->taxa }}"
                      @selected(old('banco_id', $processo->banco_id) == $banco->id)>
                {{ $banco->nome }} · {{ number_format((float) $banco->taxa, 2, ',', '.') }}%
              </option>
            @endforeach
          </select>
          <small class="text-muted">A taxa é o <strong>% da dívida</strong> que o banco aceita para quitar.</small>
        </div>
        <div class="col-md-6 mb-4">
          <label class="form-label">Valor do financiamento (R$)</label>
          <input type="text" inputmode="numeric" name="valor_financiamento" id="valor_financiamento" class="form-control mask-money"
                 value="{{ old('valor_financiamento', $processo->valor_financiamento ? number_format((float) $processo->valor_financiamento, 2, ',', '.') : '') }}" placeholder="0,00">
        </div>
        <div class="col-md-4 mb-4">
          <label class="form-label">Quantidade de parcelas</label>
          <input type="number" name="qtd_parcelas" id="qtd_parcelas" class="form-control" min="1" max="999"
                 value="{{ old('qtd_parcelas', $processo->qtd_parcelas) }}" placeholder="Ex.: 48">
        </div>
        <div class="col-md-4 mb-4">
          <label class="form-label">Primeira parcela</label>
          <input type="text" name="data_primeira_parcela" id="data_primeira_parcela" class="form-control flatpickr-date"
                 value="{{ old('data_primeira_parcela', $processo->data_primeira_parcela?->toDateString()) }}" placeholder="dd/mm/aaaa">
          <small class="text-muted">As demais vencem no mesmo dia dos meses seguintes.</small>
        </div>
        <div class="col-md-4 mb-4">
          <label class="form-label d-flex justify-content-between align-items-center">
            <span>Valor da parcela (R$)</span>
            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" id="btn-recalcular-parcela" style="display:none;">
              <i class="icon-base ti tabler-refresh"></i> recalcular
            </button>
          </label>
          <input type="text" inputmode="numeric" name="valor_parcela" id="valor_parcela" class="form-control mask-money"
                 value="{{ old('valor_parcela', $processo->valor_parcela ? number_format((float) $processo->valor_parcela, 2, ',', '.') : '') }}" placeholder="0,00">
          <small class="text-muted" id="parcela-memoria">Calculado automaticamente a partir do banco, do financiamento e do serviço.</small>
        </div>

        @if ($isAdmin)
          <div class="col-12 mt-4">
            <label class="form-label">Link para pagamento mensal</label>
            <input type="url" name="link_pagamento_mensal" class="form-control" maxlength="500"
                   value="{{ old('link_pagamento_mensal', $processo->link_pagamento_mensal) }}"
                   placeholder="https://...">
            <small class="text-muted">Endereço que o cliente usa para pagar a parcela do mês. Aparece como botão na tela do processo.</small>
          </div>
        @endif
      </div>
    </div>
  </div>

  <div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="card-title mb-0">Dívidas</h5>
      <button type="button" class="btn btn-sm btn-primary" id="btn-add-divida">
        <i class="ti tabler-plus me-1"></i> Adicionar dívida
      </button>
    </div>
    <div class="card-body">
      <div id="dividas-container">
        @forelse ($dividas as $i => $divida)
          @include('content.processos.partials.divida-row', ['idx' => $i, 'divida' => $divida])
        @empty
          @include('content.processos.partials.divida-row', ['idx' => 0, 'divida' => null])
        @endforelse
      </div>
      <small class="text-muted">Preencha apenas as dívidas que quiser registrar. Linhas vazias serão ignoradas.</small>
    </div>
  </div>

  <div class="card mb-4">
    <div class="card-header"><h5 class="card-title mb-0">Observações do cliente</h5></div>
    <div class="card-body">
      <textarea name="observacoes_cliente" class="form-control" rows="4" maxlength="3000" placeholder="Informações adicionais que possam ajudar...">{{ old('observacoes_cliente', $processo->observacoes_cliente) }}</textarea>
    </div>
  </div>

  @if ($isAdmin)
    <div class="card mb-4">
      <div class="card-header"><h5 class="card-title mb-0">Observações internas (admin)</h5></div>
      <div class="card-body">
        <textarea name="observacoes_admin" class="form-control" rows="3" maxlength="5000" placeholder="Notas visíveis apenas para a equipe.">{{ old('observacoes_admin', $processo->observacoes_admin) }}</textarea>
      </div>
    </div>
  @endif

  <div class="d-flex justify-content-end gap-2">
    <a href="{{ $editing ? route('processos.show', $processo) : route('processos.index') }}" class="btn btn-label-secondary">Cancelar</a>
    <button type="submit" class="btn btn-primary"><i class="ti tabler-device-floppy me-1"></i> {{ $editing ? 'Salvar alterações' : 'Cadastrar processo' }}</button>
  </div>
</form>

<template id="divida-template">
  @include('content.processos.partials.divida-row', ['idx' => '__IDX__', 'divida' => null])
</template>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  if (window.jQuery && jQuery('#user_id').length) {
    jQuery('#user_id').select2({ placeholder: 'Selecione o cliente', allowClear: true, width: '100%' });
  }
  if (window.jQuery && jQuery('#comprador_id').length) {
    jQuery('#comprador_id').select2({ placeholder: 'Selecione o comprador', allowClear: true, width: '100%' });
  }
  if (window.jQuery && jQuery('#cliente_user_id').length) {
    jQuery('#cliente_user_id').select2({ placeholder: 'Selecione o cliente titular', allowClear: true, width: '100%' });
  }

  // Data da primeira parcela: digita/exibe em dd/mm/aaaa, envia em Y-m-d
  if (window.flatpickr) {
    flatpickr('.flatpickr-date', { altInput: true, altFormat: 'd/m/Y', dateFormat: 'Y-m-d', allowInput: true });
  }

  // ================================================================
  // FINANCIAMENTO — valor da parcela calculado pelo mesmo critério da
  // Calculadora de quitação: o banco aceita um % da dívida, soma-se a
  // comissão do serviço e divide-se pelo número de parcelas.
  //   mínimo  = financiamento × (taxa do banco / 100)
  //   final   = mínimo + comissão do serviço
  //   parcela = final / parcelas
  // O campo continua editável: se o usuário digitar por cima, o cálculo
  // para de sobrescrever até ele clicar em "recalcular".
  // ================================================================
  (function () {
    const bancoEl    = document.getElementById('banco_id');
    const finEl      = document.getElementById('valor_financiamento');
    const parcelasEl = document.getElementById('qtd_parcelas');
    const parcelaEl  = document.getElementById('valor_parcela');
    const memoriaEl  = document.getElementById('parcela-memoria');
    const recalcBtn  = document.getElementById('btn-recalcular-parcela');
    if (! bancoEl || ! parcelaEl) return;

    // Em edição, um valor já gravado é tratado como manual — não sobrescreve o que
    // o usuário salvou antes só porque a tela abriu.
    let manual = parcelaEl.value.trim() !== '';

    const parseMoney = (v) => {
      const s = String(v || '').trim().replace(/\./g, '').replace(',', '.');
      const n = parseFloat(s);
      return isNaN(n) ? 0 : n;
    };
    const fmtMoney = (n) => Number(n || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    function comissaoServico() {
      const sel = document.querySelector('.servico-radio:checked');
      return sel ? parseFloat(sel.dataset.comissao || 0) : 0;
    }

    function calcular() {
      const financiamento = parseMoney(finEl.value);
      const parcelas = parseInt(parcelasEl.value || '0', 10);
      const opt = bancoEl.options[bancoEl.selectedIndex];
      const taxa = opt ? parseFloat(opt.dataset.taxa || 0) : 0;
      const comissao = comissaoServico();

      if (! financiamento || ! parcelas || parcelas < 1) {
        memoriaEl.textContent = 'Preencha banco, financiamento e parcelas para calcular.';
        return;
      }

      const minimo = financiamento * (taxa / 100);
      const final = minimo + comissao;
      const parcela = final / parcelas;

      memoriaEl.innerHTML = `R$ ${fmtMoney(financiamento)} × ${fmtMoney(taxa)}% = R$ ${fmtMoney(minimo)}`
        + ` + comissão R$ ${fmtMoney(comissao)} = <strong>R$ ${fmtMoney(final)}</strong>`
        + ` ÷ ${parcelas}x = <strong>R$ ${fmtMoney(parcela)}</strong>`;

      if (! manual) parcelaEl.value = fmtMoney(parcela);
    }

    function marcarManual() {
      manual = true;
      recalcBtn.style.display = '';
    }

    [bancoEl, finEl, parcelasEl].forEach(el => {
      el.addEventListener('input', calcular);
      el.addEventListener('change', calcular);
    });
    document.querySelectorAll('.servico-radio').forEach(el => el.addEventListener('change', calcular));

    parcelaEl.addEventListener('input', marcarManual);

    recalcBtn.addEventListener('click', function () {
      manual = false;
      recalcBtn.style.display = 'none';
      calcular();
    });

    if (manual) recalcBtn.style.display = '';
    calcular();
  })();

  const container = document.getElementById('dividas-container');
  const template = document.getElementById('divida-template').innerHTML;
  let idx = container.querySelectorAll('.divida-row').length;

  document.getElementById('btn-add-divida').addEventListener('click', function () {
    const html = template.replaceAll('__IDX__', idx++);
    container.insertAdjacentHTML('beforeend', html);
    document.dispatchEvent(new CustomEvent('mask:refresh'));
  });

  container.addEventListener('click', function (e) {
    const btn = e.target.closest('.btn-remove-divida');
    if (! btn) return;
    const rows = container.querySelectorAll('.divida-row');
    if (rows.length <= 1) {
      btn.closest('.divida-row').querySelectorAll('input, textarea').forEach(el => el.value = '');
    } else {
      btn.closest('.divida-row').remove();
    }
  });

  // ================================================================
  // AUTOCOMPLETE VEÍCULO (FIPE via BrasilAPI) — marca depois modelo
  // ================================================================
  if (window.jQuery && jQuery('#veiculo-marca-select').length) {
    const $tipo   = jQuery('#veiculo-tipo');
    const $marca  = jQuery('#veiculo-marca-select');
    const $modelo = jQuery('#veiculo-modelo-select');
    const marcaInput  = document.getElementById('veiculo-marca-input');
    const modeloInput = document.getElementById('veiculo-modelo-input');

    function setupSelect2($el, placeholder, urlFn) {
      $el.wrap('<div class="position-relative"></div>').select2({
        placeholder: placeholder,
        allowClear: true,
        dropdownParent: $el.parent(),
        width: '100%',
        minimumInputLength: 0,
        ajax: {
          delay: 250,
          transport: function (params, success, failure) {
            const url = urlFn();
            if (! url) { success({ results: [] }); return; }
            fetch(url, { headers: { Accept: 'application/json' } })
              .then(r => r.ok ? r.json() : [])
              .then(list => {
                const q = (params.data.term || '').toLowerCase();
                const filtered = q
                  ? list.filter(it => (it.nome || '').toLowerCase().includes(q))
                  : list;
                success({ results: filtered.map(it => ({ id: it.nome, text: it.nome, codigo: it.codigo })) });
              })
              .catch(failure);
          },
        },
      });
    }

    // Popula marca a partir do tipo escolhido
    setupSelect2($marca, 'Selecione a marca',
      () => `{{ url('painel/api/fipe/marcas') }}/${$tipo.val()}`);

    // Popula modelo a partir da marca escolhida (usa código FIPE da marca)
    let codigoMarcaAtual = null;
    setupSelect2($modelo, 'Selecione o modelo',
      () => codigoMarcaAtual ? `{{ url('painel/api/fipe/modelos') }}/${$tipo.val()}/${codigoMarcaAtual}` : null);

    // Se já existe marca salva (edição), semeia o select com uma option pré-selecionada
    if (marcaInput.value) {
      const opt = new Option(marcaInput.value, marcaInput.value, true, true);
      $marca.append(opt).trigger('change.select2');
    }
    if (modeloInput.value) {
      const opt = new Option(modeloInput.value, modeloInput.value, true, true);
      $modelo.append(opt).trigger('change.select2');
    }

    // Espelha seleção nos hidden inputs (é isso que vai pro backend)
    $marca.on('change', function () {
      const data = $marca.select2('data')[0];
      marcaInput.value = data ? data.text : '';
      codigoMarcaAtual = data ? data.codigo : null;
      // Ao trocar de marca, limpa modelo
      $modelo.val(null).trigger('change');
      // Modelo precisa reabrir para carregar da nova marca
      $modelo.empty();
    });

    $modelo.on('change', function () {
      const data = $modelo.select2('data')[0];
      modeloInput.value = data ? data.text : '';
    });

    // Ao trocar tipo, limpa marca e modelo
    $tipo.on('change', function () {
      $marca.val(null).trigger('change');
      $marca.empty();
      $modelo.val(null).trigger('change');
      $modelo.empty();
      marcaInput.value = '';
      modeloInput.value = '';
      codigoMarcaAtual = null;
    });
  }
});
</script>
@include('_partials._masks-script')
@include('_partials._cep-autocomplete')
@endsection
