@extends('layouts/layoutMaster')

@section('title', $user->exists ? 'Editar usuário' : 'Novo usuário')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss'])
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/select2/select2.js',
  'resources/assets/vendor/libs/cleave-zen/cleave-zen.js',
  'resources/assets/vendor/libs/flatpickr/flatpickr.js',
])
@endsection

@section('content')
@php
  $editing = $user->exists;
  $action = $editing ? url('/painel/usuarios/' . $user->id) : url('/painel/usuarios');
  $currentRole = $editing ? $user->getRoleNames()->first() : 'mentorado';
  $currentPlanId = $editing ? $user->currentSubscription?->plan_id : null;
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
  <h4 class="mb-0">{{ $editing ? 'Editar usuário' : 'Novo usuário' }}</h4>
  <a href="{{ route('admin.users') }}" class="btn btn-label-secondary">
    <i class="icon-base ti tabler-arrow-left me-1"></i> Voltar
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

  <div class="row g-4">
    <div class="col-lg-8">
      <div class="card mb-4">
        <div class="card-header border-bottom"><h5 class="card-title mb-0">Dados pessoais</h5></div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-8 mb-4">
              <label class="form-label">Nome *</label>
              <input type="text" class="form-control" name="name" required maxlength="120" value="{{ old('name', $user->name) }}">
            </div>
            <div class="col-md-4 mb-4">
              <label class="form-label">Data de nascimento</label>
              <input type="text" class="form-control flatpickr-date" name="data_nascimento" value="{{ old('data_nascimento', $user->data_nascimento?->toDateString()) }}" placeholder="dd/mm/aaaa">
            </div>
            <div class="col-md-6 mb-4">
              <label class="form-label">E-mail *</label>
              <input type="email" class="form-control" name="email" required maxlength="180" value="{{ old('email', $user->email) }}">
            </div>
            <div class="col-md-6 mb-4">
              <label class="form-label">Telefone</label>
              <input type="text" class="form-control mask-phone" name="phone" placeholder="(11) 99999-9999" value="{{ old('phone', $user->phone) }}">
            </div>
            <div class="col-md-4 mb-4">
              <label class="form-label">Tipo de documento</label>
              <select name="tipo_documento" class="form-select">
                <option value="cpf" @selected(old('tipo_documento', $user->tipo_documento ?? 'cpf') === 'cpf')>CPF</option>
                <option value="cnpj" @selected(old('tipo_documento', $user->tipo_documento) === 'cnpj')>CNPJ</option>
              </select>
            </div>
            <div class="col-md-8 mb-0">
              <label class="form-label">CPF / CNPJ <span id="cpf-obrigatorio" class="text-danger" style="display:none;">*</span></label>
              <input type="text" class="form-control mask-cpf-cnpj" name="cpf_cnpj" id="cpf_cnpj" placeholder="000.000.000-00" value="{{ old('cpf_cnpj', $user->cpf_cnpj) }}">
              <small class="text-muted" id="cpf-ajuda" style="display:none;">Obrigatório para o nível Comprador.</small>
            </div>
          </div>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header border-bottom"><h5 class="card-title mb-0">Endereço</h5></div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-3 mb-4">
              <label class="form-label">CEP</label>
              <input type="text" class="form-control mask-cep" name="cep" maxlength="10" placeholder="00000-000" value="{{ old('cep', $user->cep) }}" data-cep-autocomplete>
            </div>
            <div class="col-md-7 mb-4">
              <label class="form-label">Logradouro</label>
              <input type="text" class="form-control" name="logradouro" maxlength="160" value="{{ old('logradouro', $user->logradouro) }}" placeholder="Rua, avenida...">
            </div>
            <div class="col-md-2 mb-4">
              <label class="form-label">Número</label>
              <input type="text" class="form-control" name="numero" maxlength="20" value="{{ old('numero', $user->numero) }}">
            </div>
            <div class="col-md-4 mb-4">
              <label class="form-label">Complemento</label>
              <input type="text" class="form-control" name="complemento" maxlength="80" value="{{ old('complemento', $user->complemento) }}" placeholder="Apto, sala, bloco...">
            </div>
            <div class="col-md-3 mb-4">
              <label class="form-label">Bairro</label>
              <input type="text" class="form-control" name="bairro" maxlength="80" value="{{ old('bairro', $user->bairro) }}">
            </div>
            <div class="col-md-3 mb-4">
              <label class="form-label">Cidade</label>
              <input type="text" class="form-control" name="cidade" maxlength="80" value="{{ old('cidade', $user->cidade) }}">
            </div>
            <div class="col-md-2 mb-0">
              <label class="form-label">Estado</label>
              <select name="uf" class="form-select">
                <option value="">—</option>
                @foreach (['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'] as $uf)
                  <option value="{{ $uf }}" @selected(strtoupper(old('uf', $user->uf)) === $uf)>{{ $uf }}</option>
                @endforeach
              </select>
            </div>
          </div>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header border-bottom"><h5 class="card-title mb-0">Acesso</h5></div>
        <div class="card-body">
          <div class="mb-0">
            <label class="form-label">Senha {!! $editing ? '<span class="text-muted small">(deixe em branco para manter)</span>' : '*' !!}</label>
            <input type="password" class="form-control" name="password" autocomplete="new-password" {{ $editing ? '' : 'required' }}>
          </div>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header border-bottom"><h5 class="card-title mb-0">Observações</h5></div>
        <div class="card-body">
          <textarea class="form-control" name="observacoes" rows="4" maxlength="5000" placeholder="Notas internas sobre este usuário...">{{ old('observacoes', $user->observacoes) }}</textarea>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card mb-4">
        <div class="card-header border-bottom"><h5 class="card-title mb-0">Nível e status</h5></div>
        <div class="card-body">
          <div class="mb-4">
            <label class="form-label">Nível</label>
            <select name="role" id="role" class="form-select">
              <option value="mentorado" @selected(old('role', $currentRole) === 'mentorado')>Mentorado</option>
              <option value="licenciado" @selected(old('role', $currentRole) === 'licenciado')>Licenciado</option>
              <option value="cliente" @selected(old('role', $currentRole) === 'cliente')>Cliente</option>
              <option value="comprador" @selected(old('role', $currentRole) === 'comprador')>Comprador</option>
              <option value="admin" @selected(old('role', $currentRole) === 'admin')>Admin</option>
            </select>
            <small class="text-muted d-block" id="ajuda-nivel-cliente" style="display:none;">
              Titular do processo. Entra no painel e acompanha apenas os processos em que
              estiver vinculado: andamento, parcelas, link de pagamento, documentos e PDF.
            </small>
            <small class="text-muted d-block" id="ajuda-nivel-padrao">Compradores podem ser vinculados como parte destino em processos.</small>
          </div>
          <div class="mb-0">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
              <option value="ativo" @selected(old('status', $user->status ?? 'ativo') === 'ativo')>Ativo</option>
              <option value="inativo" @selected(old('status', $user->status) === 'inativo')>Inativo</option>
              <option value="bloqueado" @selected(old('status', $user->status) === 'bloqueado')>Bloqueado</option>
            </select>
          </div>
        </div>
      </div>

      {{-- Só mentorado e licenciado assinam plano. Para admin e comprador o bloco
           some da tela (e o plan_id é descartado no servidor). --}}
      <div class="card" id="bloco-plano" style="{{ in_array(old('role', $currentRole), ['mentorado', 'licenciado'], true) ? '' : 'display:none;' }}">
        <div class="card-header border-bottom"><h5 class="card-title mb-0">Plano</h5></div>
        <div class="card-body">
          <label class="form-label">Plano (opcional)</label>
          <select name="plan_id" id="plan_id" class="select2 form-select">
            <option value="">Sem plano</option>
            @foreach ($plans as $p)
              <option value="{{ $p->id }}" @selected(old('plan_id', $currentPlanId) == $p->id)>{{ $p->nome }} ({{ ucfirst($p->tipo) }})</option>
            @endforeach
          </select>
          <small class="text-muted">Atribuir um plano cria uma assinatura ativa imediatamente.</small>
        </div>
      </div>
    </div>
  </div>

  <div class="d-flex justify-content-end gap-2">
    <a href="{{ route('admin.users') }}" class="btn btn-label-secondary">Cancelar</a>
    <button type="submit" class="btn btn-primary"><i class="icon-base ti tabler-device-floppy me-1"></i> {{ $editing ? 'Salvar alterações' : 'Cadastrar usuário' }}</button>
  </div>
</form>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const $plan = $('#plan_id');
  $plan.wrap('<div class="position-relative"></div>').select2({
    placeholder: 'Selecione...',
    allowClear: true,
    dropdownParent: $plan.parent(),
  });

  if (window.flatpickr) {
    flatpickr('.flatpickr-date', {
      altInput: true,
      altFormat: 'd/m/Y',
      dateFormat: 'Y-m-d',
      allowInput: true,
    });
  }

  // Plano só faz sentido para quem assina: mentorado e licenciado.
  const roleEl = document.getElementById('role');
  const blocoPlano = document.getElementById('bloco-plano');
  const ASSINANTES = ['mentorado', 'licenciado'];

  const cpfEl = document.getElementById('cpf_cnpj');
  const cpfAsterisco = document.getElementById('cpf-obrigatorio');
  const cpfAjuda = document.getElementById('cpf-ajuda');

  function alternarPorNivel() {
    const assina = ASSINANTES.includes(roleEl.value);
    blocoPlano.style.display = assina ? '' : 'none';
    // Zera a seleção ao esconder, senão um plano escolhido antes seguiria no POST
    if (! assina) $plan.val(null).trigger('change');

    // Comprador gera um registro na tabela de compradores, que exige documento
    const exigeCpf = roleEl.value === 'comprador';
    cpfEl.required = exigeCpf;
    cpfAsterisco.style.display = exigeCpf ? '' : 'none';
    cpfAjuda.style.display = exigeCpf ? '' : 'none';

    const ehCliente = roleEl.value === 'cliente';
    document.getElementById('ajuda-nivel-cliente').style.display = ehCliente ? '' : 'none';
    document.getElementById('ajuda-nivel-padrao').style.display = ehCliente ? 'none' : '';
  }

  roleEl.addEventListener('change', alternarPorNivel);
  alternarPorNivel();
});
</script>
@include('_partials._masks-script')
@include('_partials._cep-autocomplete')
@endsection
