@extends('layouts/layoutMaster')

@section('title', 'Aguardando pagamento')

@section('content')
<div class="card" id="aguardando-card">
  <div class="card-body text-center py-5">
    <div class="avatar avatar-xl mx-auto mb-4" id="aguardando-avatar">
      <span class="avatar-initial rounded-circle bg-label-warning">
        <i class="icon-base ti tabler-clock icon-26px"></i>
      </span>
    </div>
    <h4 class="mb-2" id="aguardando-titulo">Aguardando pagamento</h4>
    <p class="text-muted mb-4" id="aguardando-mensagem">
      Fatura <strong>#{{ $fatura->id }}</strong> no valor de
      <strong>R$ {{ number_format((float) $fatura->valor, 2, ',', '.') }}</strong>
      do plano <strong>{{ $fatura->plan?->nome }}</strong>.
    </p>

    <div class="mb-3" id="aguardando-progress">
      <div class="d-inline-flex align-items-center gap-2 text-muted small">
        <div class="spinner-border spinner-border-sm" role="status"></div>
        Verificando confirmação do pagamento automaticamente…
      </div>
    </div>

    @if ($fatura->link_pagamento)
      <div id="aguardando-actions">
        <a href="{{ $fatura->link_pagamento }}" target="_blank" class="btn btn-primary me-2">
          <i class="icon-base ti tabler-credit-card me-1"></i> Ir para o pagamento
        </a>
      </div>
    @elseif (! $mp_configurado)
      <div class="alert alert-warning text-start mx-auto" style="max-width: 600px;">
        <strong>Modo manual.</strong> O gateway de pagamento (Mercado Pago) não está configurado neste ambiente.
        Sua assinatura ficará pendente até que um administrador marque a fatura como paga em
        <em>Financeiro</em>.
      </div>
    @endif

    <div class="mt-4">
      <a href="{{ route('faturas.index') }}" class="btn btn-label-secondary">Ver minhas faturas</a>
      <a href="{{ route('subscription.view') }}" class="btn btn-label-primary">Minha Assinatura</a>
    </div>
  </div>
</div>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const statusUrl = "{{ route('checkout.status', $fatura) }}";
  const subscriptionUrl = "{{ route('subscription.view') }}";

  const avatar = document.getElementById('aguardando-avatar');
  const titulo = document.getElementById('aguardando-titulo');
  const mensagem = document.getElementById('aguardando-mensagem');
  const progress = document.getElementById('aguardando-progress');

  function setEstado({ icon, color, titulo: t, mensagem: m, hideProgress }) {
    avatar.querySelector('.avatar-initial').className = `avatar-initial rounded-circle bg-label-${color}`;
    avatar.querySelector('i').className = `icon-base ti tabler-${icon} icon-26px`;
    if (t) titulo.textContent = t;
    if (m) mensagem.innerHTML = m;
    if (hideProgress) progress.classList.add('d-none');
  }

  let tentativas = 0;
  const MAX_TENTATIVAS = 120; // ~10 minutos com intervalo de 5s

  async function checar() {
    tentativas++;
    try {
      const r = await fetch(statusUrl, { headers: { Accept: 'application/json' } });
      if (! r.ok) return;
      const d = await r.json();

      if (d.status === 'paga') {
        setEstado({
          icon: 'check', color: 'success',
          titulo: 'Pagamento confirmado!',
          mensagem: 'Sua assinatura foi ativada. Você já tem acesso aos módulos contratados.',
          hideProgress: true,
        });
        setTimeout(() => window.location.href = subscriptionUrl, 2500);
        return true;
      }
      if (d.status === 'cancelada') {
        setEstado({
          icon: 'x', color: 'danger',
          titulo: 'Pagamento não concluído',
          mensagem: 'O pagamento foi cancelado ou recusado. Tente novamente em <a href="' + subscriptionUrl + '">Minha Assinatura</a>.',
          hideProgress: true,
        });
        return true;
      }
      if (d.status === 'estornada') {
        setEstado({
          icon: 'arrow-back-up', color: 'secondary',
          titulo: 'Pagamento estornado',
          mensagem: 'O valor foi devolvido.',
          hideProgress: true,
        });
        return true;
      }
    } catch (e) {
      // silencioso — tenta de novo no próximo tick
    }
    return false;
  }

  async function loop() {
    const pronto = await checar();
    if (pronto) return;
    if (tentativas >= MAX_TENTATIVAS) {
      progress.innerHTML = '<small class="text-muted">A confirmação está demorando. Você pode fechar esta página. Assim que pagarmos, será refletido nas suas faturas. <a href="javascript:location.reload()">Atualizar</a></small>';
      return;
    }
    setTimeout(loop, 5000);
  }

  // 1ª verificação imediata, depois polling a cada 5s
  loop();
});
</script>
@endsection
