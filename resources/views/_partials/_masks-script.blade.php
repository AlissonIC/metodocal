{{-- Máscaras de telefone, CPF e CNPJ usando cleave-zen.
     Coloque este @include dentro de @section('page-script') APÓS o @vite do cleave-zen.

     Uso nos campos:
       <input class="mask-phone">                    → (XX) XXXXX-XXXX ou (XX) XXXX-XXXX (auto)
       <input class="mask-cpf">                      → XXX.XXX.XXX-XX
       <input class="mask-cnpj">                     → XX.XXX.XXX/XXXX-XX
       <input class="mask-cpf-cnpj">                 → alterna entre CPF e CNPJ conforme o tamanho
--}}
<script>
(function () {
  const { formatGeneral, registerCursorTracker } = window;

  if (typeof formatGeneral !== 'function') {
    console.warn('cleave-zen não está carregado; máscaras desativadas.');
    return;
  }

  function apply(selector, format) {
    document.querySelectorAll(selector).forEach(function (input) {
      if (input.dataset.maskApplied) return;
      input.dataset.maskApplied = '1';

      const initial = (input.value || '').replace(/\D/g, '');
      if (initial) input.value = format(initial);

      input.addEventListener('input', function () {
        const digits = input.value.replace(/\D/g, '');
        input.value = format(digits);
      });

      try {
        registerCursorTracker({ input: input, delimiter: ' ' });
      } catch (e) {}
    });
  }

  const phone = function (digits) {
    digits = digits.slice(0, 11);
    if (digits.length <= 10) {
      return formatGeneral(digits, { blocks: [0, 2, 4, 4], delimiters: ['(', ') ', '-'] });
    }
    return formatGeneral(digits, { blocks: [0, 2, 5, 4], delimiters: ['(', ') ', '-'] });
  };

  const cpf = function (digits) {
    digits = digits.slice(0, 11);
    return formatGeneral(digits, { blocks: [3, 3, 3, 2], delimiters: ['.', '.', '-'] });
  };

  const cnpj = function (digits) {
    digits = digits.slice(0, 14);
    return formatGeneral(digits, { blocks: [2, 3, 3, 4, 2], delimiters: ['.', '.', '/', '-'] });
  };

  const cpfCnpj = function (digits) {
    return digits.length <= 11 ? cpf(digits) : cnpj(digits);
  };

  apply('.mask-phone', phone);
  apply('.mask-cpf', cpf);
  apply('.mask-cnpj', cnpj);
  apply('.mask-cpf-cnpj', cpfCnpj);

  // Reaplica máscaras quando o conteúdo do form for repopulado (ex.: edit no offcanvas)
  document.addEventListener('mask:refresh', function () {
    document.querySelectorAll('[data-mask-applied]').forEach(i => { delete i.dataset.maskApplied; });
    apply('.mask-phone', phone);
    apply('.mask-cpf', cpf);
    apply('.mask-cnpj', cnpj);
    apply('.mask-cpf-cnpj', cpfCnpj);
  });
})();
</script>
