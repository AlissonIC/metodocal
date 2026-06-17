@extends('layouts/layoutMaster')

@section('title', 'Fatura #' . $fatura->id)

@section('vendor-style')
@vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('content')
<div id="fin-header" class="mb-4 d-flex align-items-center gap-2 flex-wrap">
  @include('content.admin.financeiro._partials.header', ['fatura' => $fatura])
</div>

<div class="row g-6 mb-6">

  {{-- Coluna esquerda --}}
  <div class="col-lg-8">

    <div class="card mb-6">
      <h5 class="card-header">Resumo</h5>
      <div id="fin-resumo">
        @include('content.admin.financeiro._partials.resumo', ['fatura' => $fatura])
      </div>
    </div>

    <div class="card mb-6">
      <h5 class="card-header">Dados informados no pagamento</h5>
      <div id="fin-pagador">
        @include('content.admin.financeiro._partials.pagador', ['fatura' => $fatura])
      </div>
    </div>

    <div id="fin-eventos" class="card mb-6">
      @include('content.admin.financeiro._partials.eventos', ['eventos' => $eventos])
    </div>

    <div id="fin-auditoria" class="card mb-6">
      @include('content.admin.financeiro._partials.auditoria', ['auditoria' => $auditoria])
    </div>
  </div>

  {{-- Coluna direita --}}
  <div class="col-lg-4">
    <div id="fin-acoes" class="card mb-6 sticky-top" style="top: 1rem;">
      @include('content.admin.financeiro._partials.acoes', ['fatura' => $fatura])
    </div>
  </div>
</div>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const csrf = document.querySelector('meta[name="csrf-token"]').content;
  const faturaId = {{ $fatura->id }};
  const baseUrl = "{{ url('/painel/financeiro') }}";
  const refreshUrl = `${baseUrl}/${faturaId}/refresh`;

  function swalError(msg) {
    return Swal.fire({ icon: 'error', title: 'Erro', text: msg, customClass: { confirmButton: 'btn btn-danger' }, buttonsStyling: false });
  }
  function swalOk(title, msg) {
    return Swal.fire({ icon: 'success', title, text: msg, timer: 1800, showConfirmButton: false });
  }
  function setHtml(selector, html) {
    const el = document.querySelector(selector);
    if (el) el.innerHTML = html;
  }

  /** Atualiza todas as seções via AJAX, sem recarregar a página. */
  function refresh(extraParams = '') {
    const url = refreshUrl + (extraParams ? `?${extraParams}` : '');
    return fetch(url, { headers: { Accept: 'application/json' } })
      .then(r => r.json())
      .then(data => {
        setHtml('#fin-header', data.header);
        setHtml('#fin-resumo', data.resumo);
        setHtml('#fin-pagador', data.pagador);
        document.querySelector('#fin-eventos').innerHTML = data.eventos;
        document.querySelector('#fin-auditoria').innerHTML = data.auditoria;
        setHtml('#fin-acoes', data.acoes);
        bindAll();
      });
  }

  function refreshEventos(pageEv) {
    return fetch(`${refreshUrl}?pageEv=${pageEv}`, { headers: { Accept: 'application/json' } })
      .then(r => r.json())
      .then(data => {
        document.querySelector('#fin-eventos').innerHTML = data.eventos;
        bindEventos();
      });
  }

  function refreshAuditoria(pageAud) {
    return fetch(`${refreshUrl}?pageAud=${pageAud}`, { headers: { Accept: 'application/json' } })
      .then(r => r.json())
      .then(data => {
        document.querySelector('#fin-auditoria').innerHTML = data.auditoria;
        bindAuditoria();
      });
  }

  function bindEventos() {
    document.querySelectorAll('#fin-eventos .toggle-payload').forEach(btn => {
      btn.addEventListener('click', () => {
        const el = document.getElementById(btn.dataset.target);
        if (el) el.style.display = el.style.display === 'none' ? 'block' : 'none';
      });
    });
    document.querySelectorAll('#fin-eventos a.page-link').forEach(link => {
      link.addEventListener('click', (e) => {
        e.preventDefault();
        const url = new URL(link.href, location.origin);
        const pageEv = url.searchParams.get('pageEv') || 1;
        refreshEventos(pageEv);
      });
    });
  }

  function bindAuditoria() {
    document.querySelectorAll('#fin-auditoria a.page-link').forEach(link => {
      link.addEventListener('click', (e) => {
        e.preventDefault();
        const url = new URL(link.href, location.origin);
        const pageAud = url.searchParams.get('pageAud') || 1;
        refreshAuditoria(pageAud);
      });
    });
  }

  function bindAcoes() {
    const btnMS = document.querySelector('#fin-acoes #btnMudarStatus');
    if (btnMS) btnMS.addEventListener('click', onMudarStatus);
    const btnE = document.querySelector('#fin-acoes #btnEstornar');
    if (btnE) btnE.addEventListener('click', onEstornar);
  }

  function bindAll() {
    bindEventos();
    bindAuditoria();
    bindAcoes();
  }

  function onMudarStatus() {
    const status = document.querySelector('#fin-acoes #novoStatus').value;
    const motivo = document.querySelector('#fin-acoes #motivo').value;
    if (!status) {
      Swal.fire({ icon: 'warning', title: 'Selecione um status', customClass: { confirmButton: 'btn btn-primary' }, buttonsStyling: false });
      return;
    }
    Swal.fire({
      title: 'Confirmar alteração?',
      text: `Status será trocado para "${status}".`,
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Sim, alterar',
      cancelButtonText: 'Cancelar',
      customClass: { confirmButton: 'btn btn-warning me-3', cancelButton: 'btn btn-label-secondary' },
      buttonsStyling: false
    }).then(r => {
      if (!r.value) return;
      fetch(`${baseUrl}/${faturaId}/status`, {
        method: 'PATCH',
        headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify({ status, motivo })
      })
        .then(r => r.json().then(b => ({ ok: r.ok, body: b })))
        .then(({ ok, body }) => {
          if (!ok) throw new Error(body.message);
          return refresh().then(() => swalOk('Pronto', body.message));
        })
        .catch(err => swalError(err.message));
    });
  }

  function onEstornar() {
    Swal.fire({
      title: 'Estornar pagamento?',
      text: 'Esta ação não pode ser desfeita.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sim, estornar',
      cancelButtonText: 'Cancelar',
      customClass: { confirmButton: 'btn btn-danger me-3', cancelButton: 'btn btn-label-secondary' },
      buttonsStyling: false
    }).then(r => {
      if (!r.value) return;
      fetch(`${baseUrl}/${faturaId}/estornar`, {
        method: 'PATCH',
        headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' }
      })
        .then(r => r.json().then(b => ({ ok: r.ok, body: b })))
        .then(({ ok, body }) => {
          if (!ok) throw new Error(body.message);
          return refresh().then(() => swalOk('Estorno solicitado', body.message));
        })
        .catch(err => swalError(err.message));
    });
  }

  bindAll();
});
</script>
@endsection
