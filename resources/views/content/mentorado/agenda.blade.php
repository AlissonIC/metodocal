@extends('layouts/layoutMaster')

@section('title', 'Agenda de Sessões')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/fullcalendar/fullcalendar.scss',
'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/fullcalendar/fullcalendar.js',
'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('content')
<div class="row g-6 mb-6">
  <div class="col-md-4">
    <div class="card h-100">
      <h5 class="card-header">Próximas sessões</h5>
      <div class="card-body">
        @forelse ($proximas as $s)
          <div class="d-flex align-items-start mb-4 pb-4 border-bottom">
            <div class="avatar me-3">
              <span class="avatar-initial rounded bg-label-primary">
                <i class="icon-base ti tabler-calendar"></i>
              </span>
            </div>
            <div class="flex-grow-1">
              <h6 class="mb-1">{{ $s->titulo }}</h6>
              <small class="text-muted d-block">{{ $s->scheduled_at->format('d/m/Y H:i') }} · {{ $s->duracao_minutos }} min</small>
              @if ($s->link_reuniao)
                <a href="{{ $s->link_reuniao }}" target="_blank" class="small">Entrar na reunião</a>
              @endif
            </div>
          </div>
        @empty
          <p class="text-muted mb-0">Nenhuma sessão agendada.</p>
        @endforelse
      </div>
    </div>
  </div>
  <div class="col-md-8">
    <div class="card h-100">
      <div class="card-body">
        <div id="agenda-calendar"></div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="sessaoModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="sessaoModalTitle">Detalhes da sessão</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="sessaoModalBody">
        <p class="mb-2"><strong>Quando:</strong> <span id="sessaoQuando"></span></p>
        <p class="mb-2"><strong>Status:</strong> <span id="sessaoStatus"></span></p>
        <p id="sessaoDescricaoBlock" class="mb-2"><strong>Descrição:</strong> <span id="sessaoDescricao"></span></p>
        <p id="sessaoLinkBlock" class="mb-0"><strong>Link:</strong> <a id="sessaoLink" href="#" target="_blank"></a></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-success" id="btnConcluir">Marcar como concluída</button>
        <button type="button" class="btn btn-label-danger" id="btnCancelar">Cancelar sessão</button>
        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
</div>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const csrf = document.querySelector('meta[name="csrf-token"]').content;
  const baseUrl = "{{ url('/painel/agenda') }}";
  const modal = new bootstrap.Modal(document.getElementById('sessaoModal'));
  let currentId = null;

  const cal = new FullCalendar.Calendar(document.getElementById('agenda-calendar'), {
    locale: 'pt-br',
    initialView: 'dayGridMonth',
    headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,listMonth' },
    buttonText: { today: 'Hoje', month: 'Mês', week: 'Semana', list: 'Lista' },
    events: { url: baseUrl + '/events' },
    eventClick: function (info) {
      currentId = info.event.id;
      document.getElementById('sessaoModalTitle').textContent = info.event.title;
      document.getElementById('sessaoQuando').textContent = info.event.start.toLocaleString('pt-BR');
      document.getElementById('sessaoStatus').textContent = info.event.extendedProps.status;
      const desc = info.event.extendedProps.descricao;
      document.getElementById('sessaoDescricaoBlock').style.display = desc ? '' : 'none';
      document.getElementById('sessaoDescricao').textContent = desc || '';
      const link = info.event.extendedProps.link_reuniao;
      document.getElementById('sessaoLinkBlock').style.display = link ? '' : 'none';
      if (link) {
        document.getElementById('sessaoLink').href = link;
        document.getElementById('sessaoLink').textContent = link;
      }
      const ativa = info.event.extendedProps.status === 'agendada';
      document.getElementById('btnConcluir').style.display = ativa ? '' : 'none';
      document.getElementById('btnCancelar').style.display = ativa ? '' : 'none';
      modal.show();
    }
  });
  cal.render();

  document.getElementById('btnConcluir').addEventListener('click', () => action('complete'));
  document.getElementById('btnCancelar').addEventListener('click', () => action('cancel'));

  function action(verb) {
    if (!currentId) return;
    fetch(`${baseUrl}/${currentId}/${verb}`, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' }
    })
      .then(r => r.json().then(b => ({ ok: r.ok, body: b })))
      .then(({ ok, body }) => {
        modal.hide();
        if (!ok) throw new Error(body.message);
        Swal.fire({ icon: 'success', title: 'Pronto', text: body.message, customClass: { confirmButton: 'btn btn-success' }, buttonsStyling: false });
        cal.refetchEvents();
      })
      .catch(err => Swal.fire({ icon: 'error', title: 'Erro', text: err.message, customClass: { confirmButton: 'btn btn-danger' }, buttonsStyling: false }));
  }
});
</script>
@endsection
