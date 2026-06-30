<div class="divida-row border rounded p-3 mb-3">
  <div class="row g-2">
    <div class="col-md-5">
      <label class="form-label small mb-1">Credor</label>
      <input type="text" name="dividas[{{ $idx }}][credor]" class="form-control form-control-sm" maxlength="160" value="{{ $divida->credor ?? '' }}">
    </div>
    <div class="col-md-3">
      <label class="form-label small mb-1">Valor (R$)</label>
      <input type="text" inputmode="numeric" name="dividas[{{ $idx }}][valor]" class="form-control form-control-sm mask-money" value="{{ $divida && $divida->valor ? number_format((float) $divida->valor, 2, ',', '.') : '' }}" placeholder="0,00">
    </div>
    <div class="col-md-3">
      <label class="form-label small mb-1">Descrição</label>
      <input type="text" name="dividas[{{ $idx }}][descricao]" class="form-control form-control-sm" maxlength="500" value="{{ $divida->descricao ?? '' }}">
    </div>
    <div class="col-md-1 d-flex align-items-end">
      <button type="button" class="btn btn-sm btn-icon btn-label-danger btn-remove-divida w-100"><i class="ti tabler-trash"></i></button>
    </div>
  </div>
</div>
