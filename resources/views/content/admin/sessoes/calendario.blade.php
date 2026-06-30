@extends('layouts/layoutMaster')

@section('title', 'Agenda · Calendário')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/fullcalendar/fullcalendar.scss'])
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/fullcalendar/fullcalendar.js',
  'resources/assets/vendor/libs/moment/moment.js',
])
@endsection

@section('page-style')
<style>
  /* Header (botões nav + título) com respiro e alinhamento */
  #calendar .fc-toolbar-title { font-size: 1.15rem; font-weight: 600; }

  /* Botões da toolbar: estilo "ghost" com leve toque dourado no estado ativo */
  #calendar .fc-button-primary {
    background: #fff !important;
    border-color: var(--bs-border-color) !important;
    color: #5b6478 !important;
    box-shadow: none !important;
    font-weight: 500;
    text-transform: none;
  }
  #calendar .fc-button-primary:hover,
  #calendar .fc-button-primary:focus {
    background: rgba(184, 134, 11, 0.06) !important;
    border-color: var(--md-brand-1) !important;
    color: var(--md-brand-1) !important;
  }
  #calendar .fc-button-primary:disabled {
    background: #fff !important;
    border-color: var(--bs-border-color) !important;
    color: #adb5bd !important;
    opacity: 1;
  }
  #calendar .fc-button-primary.fc-button-active,
  #calendar .fc-button-active {
    background: rgba(184, 134, 11, 0.12) !important;
    border-color: var(--md-brand-1) !important;
    color: var(--md-brand-1) !important;
  }
  /* Ícones prev/next em tom neutro pra acompanhar */
  #calendar .fc-icon { color: inherit; }
  /* Eventos */
  #calendar .fc-event { border-radius: .35rem; padding: 1px 4px; font-size: .78rem; cursor: pointer; }
  #calendar .fc-event:hover { filter: brightness(1.08); }
  /* Dia "hoje" */
  #calendar .fc-day-today { background: rgba(184, 134, 11, 0.06) !important; }
  /* Mobile: header empilha */
  @media (max-width: 575.98px) {
    #calendar .fc-toolbar { flex-direction: column; gap: .5rem; align-items: stretch; }
    #calendar .fc-toolbar-chunk { display: flex; justify-content: center; }
    #calendar .fc-toolbar-title { text-align: center; }
  }

  /* Legend de status */
  .calendar-legend { display: flex; gap: 1rem; flex-wrap: wrap; font-size: .85rem; }
  .calendar-legend .dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-right: 6px; vertical-align: middle; }
</style>
@endsection

@section('content')
<div class="card">
  <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <h5 class="card-title mb-0">Agenda</h5>
      <p class="text-muted mb-0 mt-1 small">Calendário de todas as sessões agendadas, concluídas e canceladas.</p>
    </div>
    <a href="{{ route('admin.sessoes.create') }}" class="btn btn-primary">
      <i class="icon-base ti tabler-plus me-1"></i> Novo evento
    </a>
  </div>

  <div class="card-body border-bottom">
    <div class="calendar-legend">
      <span><span class="dot" style="background:#B8860B"></span> Agendada</span>
      <span><span class="dot" style="background:#16a34a"></span> Concluída</span>
      <span><span class="dot" style="background:#82868b"></span> Cancelada</span>
      <span class="text-muted ms-auto small">Clique em um evento para editar. Clique em um dia vazio para criar.</span>
    </div>
  </div>

  <div class="card-body">
    <div id="calendar"></div>
  </div>
</div>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const calendarEl = document.getElementById('calendar');
  const createUrl  = "{{ route('admin.sessoes.create') }}";
  const eventsUrl  = "{{ route('admin.sessoes.events') }}";

  // O wrapper do Vuexy expõe Calendar e os plugins diretamente no window —
  // FullCalendar v5+ exige declarar os plugins na instanciação.
  const calendar = new Calendar(calendarEl, {
    plugins: [dayGridPlugin, interactionPlugin, listPlugin, timegridPlugin],
    locale: 'pt-br',
    initialView: 'dayGridMonth',
    headerToolbar: {
      start: 'prev,next today',
      center: 'title',
      end: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth',
    },
    buttonText: {
      today: 'Hoje',
      month: 'Mês',
      week: 'Semana',
      day: 'Dia',
      list: 'Lista',
    },
    height: 'auto',
    nowIndicator: true,
    navLinks: true,
    selectable: true,
    editable: false,
    events: {
      url: eventsUrl,
      method: 'GET',
      failure: (err) => console.error('Falha ao carregar eventos da agenda:', err),
    },
    eventTimeFormat: { hour: '2-digit', minute: '2-digit', meridiem: false },
    dateClick: function (info) {
      // Clicar num dia vazio leva ao form com a data pré-preenchida
      const d = info.dateStr.length === 10 ? info.dateStr + 'T09:00' : info.dateStr;
      window.location.href = createUrl + '?scheduled_at=' + encodeURIComponent(d);
    },
    eventClick: function (info) {
      // Eventos já têm `url` configurada; navega na mesma aba
      info.jsEvent.preventDefault();
      if (info.event.url) {
        window.location.href = info.event.url;
      }
    },
  });

  calendar.render();
});
</script>
@endsection
