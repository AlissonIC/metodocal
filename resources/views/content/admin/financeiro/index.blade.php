@extends('layouts/layoutMaster')

@section('title', 'Financeiro')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('content')
<div class="row g-6 mb-6">
  <div class="col-md-3 col-sm-6">
    <div class="card"><div class="card-body">
      <span class="text-heading">MRR estimado</span>
      <h4 class="my-1">R$ {{ number_format($kpi_mrr, 2, ',', '.') }}</h4>
      <small class="text-muted">{{ $qtd_assinaturas_ativas }} assinaturas ativas</small>
    </div></div>
  </div>
  <div class="col-md-3 col-sm-6">
    <div class="card"><div class="card-body">
      <span class="text-heading">Recebido no mês</span>
      <h4 class="my-1">R$ {{ number_format($kpi_recebido_mes, 2, ',', '.') }}</h4>
      <small class="text-success">Faturas pagas</small>
    </div></div>
  </div>
  <div class="col-md-2 col-sm-6">
    <div class="card"><div class="card-body">
      <span class="text-heading">Pendente</span>
      <h4 class="my-1">R$ {{ number_format($kpi_pendente, 2, ',', '.') }}</h4>
      <small class="text-warning">A receber</small>
    </div></div>
  </div>
  <div class="col-md-2 col-sm-6">
    <div class="card"><div class="card-body">
      <span class="text-heading">Atrasado</span>
      <h4 class="my-1 text-danger">R$ {{ number_format($kpi_atrasado, 2, ',', '.') }}</h4>
      <small class="text-muted">Faturas vencidas</small>
    </div></div>
  </div>
  <div class="col-md-2 col-sm-6">
    <div class="card"><div class="card-body">
      <span class="text-heading">Estornado no mês</span>
      <h4 class="my-1 text-info">R$ {{ number_format($kpi_estornado_mes, 2, ',', '.') }}</h4>
      <small class="text-muted">Refunds processados</small>
    </div></div>
  </div>
</div>

<div class="card">
  <div class="card-header border-bottom"><h5 class="card-title mb-0">Faturas</h5></div>
  <div class="card-datatable">
    <table class="datatables-faturas table border-top">
      <thead><tr>
        <th>ID</th><th>Usuário</th><th>Plano</th><th>Valor</th><th>Vencimento</th>
        <th>Método</th><th>Status</th><th>Ações</th>
      </tr></thead>
    </table>
  </div>
</div>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const csrf = document.querySelector('meta[name="csrf-token"]').content;
  const baseUrl = "{{ url('/painel/financeiro') }}";

  const dt = new DataTable('.datatables-faturas', {
    processing: true, serverSide: true, ajax: { url: baseUrl + '/datatable' },
    columns: [
      { data: 'id' }, { data: 'user_name' }, { data: 'plan_nome' },
      { data: 'valor_formatado' }, { data: 'vencimento_formatado' },
      { data: 'metodo_label' }, { data: 'status_badge' },
      { data: 'id', orderable: false, searchable: false, render: id => `
        <a href="${baseUrl}/${id}" class="btn btn-sm btn-icon" title="Ver detalhes"><i class="ti tabler-eye icon-22px"></i></a>
        <button class="btn btn-sm btn-icon mark-paid" data-id="${id}" title="Marcar como paga"><i class="ti tabler-circle-check icon-22px text-success"></i></button>
        <button class="btn btn-sm btn-icon cancel-inv" data-id="${id}" title="Cancelar"><i class="ti tabler-x icon-22px text-danger"></i></button>` }
    ],
    order: [[4, 'desc']],
    language: { processing: 'Carregando...', search: 'Buscar:', lengthMenu: '_MENU_', info: '_START_-_END_ de _TOTAL_', infoEmpty: '0', zeroRecords: 'Nenhuma fatura', emptyTable: 'Nenhuma fatura gerada', paginate: { first: '«', previous: '‹', next: '›', last: '»' } },
    layout: { topStart: { features: [{ pageLength: { menu: [10, 25, 50] } }] }, topEnd: { features: [{ search: { placeholder: 'Buscar' } }] } }
  });

  document.addEventListener('click', function (e) {
    const paidBtn = e.target.closest('.mark-paid');
    if (paidBtn) confirm('Marcar como paga?', `${baseUrl}/${paidBtn.dataset.id}/marcar-paga`, 'PATCH');
    const cancBtn = e.target.closest('.cancel-inv');
    if (cancBtn) confirm('Cancelar fatura?', `${baseUrl}/${cancBtn.dataset.id}/cancelar`, 'PATCH');
  });

  function confirm(title, url, method) {
    Swal.fire({
      title, icon: 'question', showCancelButton: true,
      confirmButtonText: 'Sim', cancelButtonText: 'Não',
      customClass: { confirmButton: 'btn btn-primary me-3', cancelButton: 'btn btn-label-secondary' },
      buttonsStyling: false
    }).then(r => {
      if (!r.value) return;
      fetch(url, { method, headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' } })
        .then(r => r.json().then(b => ({ ok: r.ok, body: b })))
        .then(({ ok, body }) => {
          if (!ok) throw new Error(body.message);
          dt.draw(false);
          Swal.fire({ icon: 'success', title: 'Pronto', text: body.message, customClass: { confirmButton: 'btn btn-success' }, buttonsStyling: false });
        })
        .catch(err => Swal.fire({ icon: 'error', title: 'Erro', text: err.message, customClass: { confirmButton: 'btn btn-danger' }, buttonsStyling: false }));
    });
  }
});
</script>
@endsection
