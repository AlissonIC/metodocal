@extends('layouts/layoutMaster')

@section('title', 'Bancos')

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
])
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/cleave-zen/cleave-zen.js',
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
])
@endsection

@section('page-style')
<style>
  /* Em mobile, a tabela vira "cards" empilhados — cada td mostra o label antes do valor */
  @media (max-width: 575.98px) {
    .bancos-table thead { display: none; }
    .bancos-table, .bancos-table tbody, .bancos-table tr, .bancos-table td { display: block; width: 100%; }
    .bancos-table tr {
      border: 1px solid var(--bs-border-color);
      border-radius: .5rem;
      margin-bottom: .75rem;
      padding: .75rem;
      background: var(--bs-card-bg);
    }
    .bancos-table td {
      border: 0 !important;
      padding: .35rem 0 !important;
      text-align: left !important;
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: .75rem;
    }
    .bancos-table td::before {
      content: attr(data-label);
      font-weight: 600;
      color: var(--bs-secondary-color);
      font-size: .78rem;
      text-transform: uppercase;
      letter-spacing: .03em;
    }
    .bancos-table td.bancos-acoes { justify-content: flex-end; padding-top: .5rem !important; border-top: 1px dashed var(--bs-border-color) !important; margin-top: .35rem; }
    .bancos-table td.bancos-acoes::before { display: none; }
  }
</style>
@endsection

@section('content')
<div class="card">
  <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <h5 class="card-title mb-0">Bancos</h5>
      <p class="text-muted mb-0 mt-1 small">Catálogo de bancos parceiros com CNPJ e taxa de comissão (%).</p>
    </div>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#bancoModal" data-mode="create">
      <i class="icon-base ti tabler-plus me-1"></i> Novo banco
    </button>
  </div>

  @if (session('status'))
    <div class="card-body pb-0"><div class="alert alert-success mb-0">{{ session('status') }}</div></div>
  @endif

  @if ($errors->any())
    <div class="card-body pb-0">
      <div class="alert alert-danger mb-0">
        <ul class="mb-0">@foreach ($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
      </div>
    </div>
  @endif

  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0 bancos-table">
      <thead class="table-light">
        <tr>
          <th>Banco</th>
          <th>CNPJ</th>
          <th class="text-end">Taxa</th>
          <th class="text-center">Status</th>
          <th class="text-end">Ações</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($bancos as $banco)
          <tr>
            <td class="fw-medium" data-label="Banco">{{ $banco->nome }}</td>
            <td data-label="CNPJ"><code class="small">{{ $banco->cnpjFormatado() }}</code></td>
            <td class="text-end fw-semibold" data-label="Taxa">{{ number_format((float) $banco->taxa, 2, ',', '.') }}%</td>
            <td class="text-center" data-label="Status">
              @if ($banco->ativo)
                <span class="badge bg-label-success">Ativo</span>
              @else
                <span class="badge bg-label-secondary">Inativo</span>
              @endif
            </td>
            <td class="text-end text-nowrap bancos-acoes">
              <button type="button" class="btn btn-sm btn-icon btn-edit-banco"
                data-bs-toggle="modal" data-bs-target="#bancoModal" data-mode="edit"
                data-id="{{ $banco->id }}"
                data-nome="{{ $banco->nome }}"
                data-cnpj="{{ $banco->cnpj }}"
                data-taxa="{{ $banco->taxa }}"
                data-ativo="{{ $banco->ativo ? '1' : '0' }}"
                title="Editar">
                <i class="icon-base ti tabler-edit icon-22px"></i>
              </button>
              <button type="button" class="btn btn-sm btn-icon text-danger btn-delete-banco" data-id="{{ $banco->id }}" data-nome="{{ $banco->nome }}" title="Excluir">
                <i class="icon-base ti tabler-trash icon-22px"></i>
              </button>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="5" class="text-center text-muted py-4">Nenhum banco cadastrado ainda.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

{{-- Modal compartilhado: cadastro / edição --}}
<div class="modal fade" id="bancoModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form id="banco-form" method="POST" action="{{ route('admin.bancos.store') }}">
        @csrf
        <input type="hidden" name="_method" id="banco-method" value="POST">
        <div class="modal-header">
          <h5 class="modal-title" id="banco-modal-title">Novo banco</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Nome *</label>
            <input type="text" name="nome" id="banco-nome" class="form-control" required maxlength="120" placeholder="Ex.: BRADESCO">
          </div>
          <div class="row">
            <div class="col-md-8 mb-3">
              <label class="form-label">CNPJ *</label>
              <input type="text" name="cnpj" id="banco-cnpj" class="form-control mask-cnpj" required placeholder="00.000.000/0000-00">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Taxa (%) *</label>
              <input type="number" name="taxa" id="banco-taxa" class="form-control" required min="0" max="100" step="0.01" placeholder="80">
            </div>
          </div>
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="banco-ativo" name="ativo" value="1" checked>
            <label class="form-check-label" for="banco-ativo">Banco ativo</label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary"><i class="icon-base ti tabler-device-floppy me-1"></i> Salvar</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const csrf = document.querySelector('meta[name="csrf-token"]').content;
  const baseStoreUrl = "{{ route('admin.bancos.store') }}";
  const baseUpdateUrl = "{{ url('/painel/bancos') }}";
  const modal = document.getElementById('bancoModal');
  const form = document.getElementById('banco-form');
  const title = document.getElementById('banco-modal-title');
  const methodInput = document.getElementById('banco-method');

  modal.addEventListener('show.bs.modal', function (e) {
    const trigger = e.relatedTarget;
    const mode = trigger?.dataset.mode || 'create';

    if (mode === 'edit') {
      title.textContent = 'Editar banco';
      methodInput.value = 'PATCH';
      form.action = `${baseUpdateUrl}/${trigger.dataset.id}`;
      document.getElementById('banco-nome').value = trigger.dataset.nome;
      document.getElementById('banco-cnpj').value = trigger.dataset.cnpj;
      document.getElementById('banco-taxa').value = trigger.dataset.taxa;
      document.getElementById('banco-ativo').checked = trigger.dataset.ativo === '1';
    } else {
      title.textContent = 'Novo banco';
      methodInput.value = 'POST';
      form.action = baseStoreUrl;
      form.reset();
      document.getElementById('banco-ativo').checked = true;
    }
    document.dispatchEvent(new CustomEvent('mask:refresh'));
  });

  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.btn-delete-banco');
    if (! btn) return;
    Swal.fire({
      title: 'Excluir banco?',
      text: `${btn.dataset.nome} será removido. Esta ação não pode ser desfeita.`,
      icon: 'warning', showCancelButton: true,
      confirmButtonText: 'Sim, excluir', cancelButtonText: 'Cancelar',
      customClass: { confirmButton: 'btn btn-danger me-3', cancelButton: 'btn btn-label-secondary' }, buttonsStyling: false
    }).then(r => {
      if (! r.value) return;
      fetch(`${baseUpdateUrl}/${btn.dataset.id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' } })
        .then(r => r.json().then(b => ({ ok: r.ok, body: b })))
        .then(({ ok, body }) => {
          if (! ok) throw new Error(body.message || 'Erro ao excluir');
          Swal.fire({ icon: 'success', title: 'Excluído', text: body.message, customClass: { confirmButton: 'btn btn-success' }, buttonsStyling: false })
            .then(() => window.location.reload());
        })
        .catch(err => Swal.fire({ icon: 'error', title: 'Erro', text: err.message, customClass: { confirmButton: 'btn btn-danger' }, buttonsStyling: false }));
    });
  });
});
</script>
@include('_partials._masks-script')
@endsection
