{{-- Máscaras de telefone, CPF, CNPJ, CEP e dinheiro usando cleave-zen.
     Coloque este @include dentro de @section('page-script') APÓS o @vite do cleave-zen.

     Uso nos campos:
       <input class="mask-phone">                    → (XX) XXXXX-XXXX ou (XX) XXXX-XXXX (auto)
       <input class="mask-cpf">                      → XXX.XXX.XXX-XX
       <input class="mask-cnpj">                     → XX.XXX.XXX/XXXX-XX
       <input class="mask-cpf-cnpj">                 → alterna entre CPF e CNPJ conforme o tamanho
       <input class="mask-cep">                      → XXXXX-XXX
       <input class="mask-money">                    → 1.234,56 (use junto com prefixo "R$" se quiser)
--}}
<script>
// cleave-zen é carregado via @@vite como module (defer implícito), então este
// script clássico executa ANTES dele. Esperamos o DOMContentLoaded — que só
// dispara após todos os scripts module executarem — para ter acesso aos
// window.formatGeneral / window.formatNumeral.
function initMetodocalMasks() {
  const { formatGeneral, formatNumeral, registerCursorTracker } = window;

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

  const cep = function (digits) {
    digits = digits.slice(0, 8);
    return formatGeneral(digits, { blocks: [5, 3], delimiters: ['-'] });
  };

  // Dinheiro: usa centavos sempre (2 casas). Digite "100" → 1,00 ; "12345" → 123,45.
  function applyMoney(selector) {
    document.querySelectorAll(selector).forEach(function (input) {
      if (input.dataset.maskApplied) return;
      input.dataset.maskApplied = '1';

      const format = function (digits) {
        digits = digits.replace(/\D/g, '').slice(0, 13);
        if (! digits) return '';
        if (digits.length === 1) digits = '00' + digits;
        if (digits.length === 2) digits = '0' + digits;
        const inteiro = digits.slice(0, -2);
        const centavos = digits.slice(-2);
        const inteiroFmt = typeof formatNumeral === 'function'
          ? formatNumeral(inteiro, { numeralThousandsGroupStyle: 'thousand', delimiter: '.' })
          : inteiro.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        return inteiroFmt + ',' + centavos;
      };

      const initial = (input.value || '').trim();
      if (initial !== '') {
        // Aceita "1234.56" (en) ou "1.234,56" (pt-BR) no valor inicial.
        let normalized;
        if (initial.includes(',')) {
          normalized = initial.replace(/\./g, '').replace(',', '.');
        } else {
          normalized = initial;
        }
        const numeric = parseFloat(normalized);
        if (! isNaN(numeric)) {
          input.value = format(Math.round(numeric * 100).toString());
        }
      }

      input.addEventListener('input', function () {
        input.value = format(input.value);
      });
    });
  }

  // Antes do submit, converte campos money para valor numérico bruto (1234.56).
  document.addEventListener('submit', function (e) {
    const form = e.target;
    if (! form || form.tagName !== 'FORM') return;
    form.querySelectorAll('.mask-money').forEach(function (input) {
      const v = (input.value || '').replace(/\./g, '').replace(',', '.');
      input.value = v === '' ? '' : (parseFloat(v) || 0).toFixed(2);
    });
  }, true);

  function applyAll() {
    apply('.mask-phone', phone);
    apply('.mask-cpf', cpf);
    apply('.mask-cnpj', cnpj);
    apply('.mask-cpf-cnpj', cpfCnpj);
    apply('.mask-cep', cep);
    applyMoney('.mask-money');
  }

  applyAll();

  // Reaplica máscaras quando o conteúdo do form for repopulado (ex.: edit no offcanvas)
  document.addEventListener('mask:refresh', function () {
    document.querySelectorAll('[data-mask-applied]').forEach(i => { delete i.dataset.maskApplied; });
    applyAll();
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initMetodocalMasks);
} else {
  // Documento já carregado (improvável aqui, mas garante robustez)
  initMetodocalMasks();
}
</script>
