@extends('layouts/layoutMaster')

@section('title', 'Notificação #' . $n->id)

@section('vendor-style')
@vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('content')
@php
  $statusColors = [
    'pendente' => 'warning', 'enviando' => 'info',
    'enviada' => 'success', 'falhou' => 'danger', 'cancelada' => 'secondary',
  ];
@endphp

<div class="mb-4 d-flex align-items-center gap-2 flex-wrap">
  <a href="{{ route('admin.notificacoes') }}" class="btn btn-sm btn-label-secondary">
    <i class="ti tabler-arrow-left me-1"></i> Voltar
  </a>
  <h4 class="mb-0">Notificação #{{ $n->id }}</h4>
  <span class="badge bg-label-{{ $statusColors[$n->status] ?? 'secondary' }}">{{ ucfirst($n->status) }}</span>
  <span class="badge bg-label-info text-capitalize">{{ $n->channel }}</span>
</div>

<div class="row g-6 mb-6">
  <div class="col-lg-8">

    <div class="card mb-6">
      <h5 class="card-header">Dados</h5>
      <div class="card-body">
        <div class="row g-4">
          <div class="col-md-6">
            <small class="text-muted">Destinatário</small>
            <div class="fw-medium">{{ $n->to }}</div>
          </div>
          <div class="col-md-6">
            <small class="text-muted">Usuário relacionado</small>
            <div>{{ $n->user?->name ?? '—' }}</div>
          </div>
          @if ($n->subject)
            <div class="col-md-12">
              <small class="text-muted">Assunto</small>
              <div class="fw-medium">{{ $n->subject }}</div>
            </div>
          @endif
          <div class="col-md-3">
            <small class="text-muted">Tentativas</small>
            <div class="fw-medium">{{ $n->attempts }} / {{ \App\Services\NotificationQueueService::MAX_ATTEMPTS }}</div>
          </div>
          <div class="col-md-3">
            <small class="text-muted">Enfileirada em</small>
            <div class="fw-medium">{{ $n->created_at->format('d/m/Y H:i:s') }}</div>
          </div>
          <div class="col-md-3">
            <small class="text-muted">Enviada em</small>
            <div class="fw-medium">{{ $n->sent_at?->format('d/m/Y H:i:s') ?? '—' }}</div>
          </div>
          <div class="col-md-3">
            <small class="text-muted">Próxima tentativa</small>
            <div class="fw-medium">
              @if ($n->next_attempt_at)
                <span class="text-warning">{{ $n->next_attempt_at->format('d/m/Y H:i:s') }}</span>
                <div class="small text-muted">{{ $n->next_attempt_at->diffForHumans() }}</div>
              @else
                —
              @endif
            </div>
          </div>
          @if ($n->last_error)
            <div class="col-md-12">
              <small class="text-muted">Último erro</small>
              <pre class="bg-light p-3 rounded mt-1 small text-danger" style="max-height:160px; overflow:auto;">{{ $n->last_error }}</pre>
            </div>
          @endif
        </div>
      </div>
    </div>

    @if ($n->channel === 'email')
      <div class="card mb-6">
        <h5 class="card-header d-flex justify-content-between align-items-center">
          <span>Preview do e-mail</span>
          <a href="{{ route('admin.notificacoes.preview', $n) }}" target="_blank" class="btn btn-sm btn-label-secondary">
            <i class="ti tabler-external-link me-1"></i> Abrir em nova aba
          </a>
        </h5>
        <div class="card-body p-0">
          {{-- iframe carrega a URL /preview (mesma origem, X-Frame-Options: SAMEORIGIN) --}}
          <iframe src="{{ route('admin.notificacoes.preview', $n) }}"
                  sandbox="allow-same-origin"
                  style="width:100%; height:600px; border:0; border-radius:0 0 .375rem .375rem;"
                  title="Preview do e-mail"
                  loading="lazy"></iframe>
        </div>
      </div>
    @else
      <div class="card mb-6">
        <h5 class="card-header">Conteúdo da mensagem</h5>
        <div class="card-body">
          <pre class="bg-light p-3 rounded small" style="max-height:400px; overflow:auto; white-space:pre-wrap;">{{ $n->body }}</pre>
        </div>
      </div>
    @endif

    @if ($n->data)
      <div class="card mb-6">
        <h5 class="card-header">Dados extras (payload)</h5>
        <div class="card-body">
          <pre class="bg-light p-3 rounded small" style="max-height:300px; overflow:auto;">{{ json_encode($n->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
      </div>
    @endif
  </div>

  <div class="col-lg-4">
    <div class="card mb-6 sticky-top" style="top: 1rem;">
      <h5 class="card-header">Ações</h5>
      <div class="card-body d-grid gap-3">
        <button class="btn btn-primary w-100" id="btnResend">
          <i class="ti tabler-send me-1"></i> Reenviar agora
        </button>
        @if (in_array($n->status, ['pendente', 'falhou']))
          <button class="btn btn-outline-danger w-100" id="btnCancel">
            <i class="ti tabler-x me-1"></i> Cancelar
          </button>
        @endif
        <small class="text-muted d-block">
          O reenvio força o processamento imediato.
          @if ($n->channel === 'email')
            Configure SMTP no <code>.env</code> antes de tentar com e-mails reais — em ambiente de dev, o envio vai pro log.
          @endif
        </small>
      </div>
    </div>
  </div>
</div>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const csrf = document.querySelector('meta[name="csrf-token"]').content;
  const baseUrl = "{{ url('/painel/notificacoes') }}";
  const id = {{ $n->id }};

  function act(title, url) {
    Swal.fire({
      title, icon: 'question', showCancelButton: true,
      confirmButtonText: 'Sim', cancelButtonText: 'Não',
      customClass: { confirmButton: 'btn btn-primary me-3', cancelButton: 'btn btn-label-secondary' },
      buttonsStyling: false
    }).then(r => {
      if (!r.value) return;
      fetch(url, { method: 'PATCH', headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' } })
        .then(r => r.json().then(b => ({ ok: r.ok, body: b })))
        .then(({ ok, body }) => {
          if (!ok) throw new Error(body.message);
          Swal.fire({ icon: 'success', title: 'Pronto', text: body.message, timer: 1500, showConfirmButton: false })
            .then(() => location.reload());
        })
        .catch(err => Swal.fire({ icon: 'error', title: 'Erro', text: err.message, customClass: { confirmButton: 'btn btn-danger' }, buttonsStyling: false }));
    });
  }

  document.getElementById('btnResend').addEventListener('click', () => act('Reenviar agora?', `${baseUrl}/${id}/resend`));
  const c = document.getElementById('btnCancel');
  if (c) c.addEventListener('click', () => act('Cancelar notificação?', `${baseUrl}/${id}/cancel`));
});
</script>
@endsection
