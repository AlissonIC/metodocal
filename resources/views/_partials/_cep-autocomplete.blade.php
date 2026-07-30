{{-- Autocomplete de endereço a partir do CEP usando o proxy /painel/api/cep (BrasilAPI).
     Ativa automaticamente em qualquer <input data-cep-autocomplete> dentro da página.

     Uso:
       <input name="cep" class="mask-cep" data-cep-autocomplete>

     Mapeamento dos campos-alvo (opcional — padrões batem com o form de processos):
       data-target-street        → name do campo de logradouro   (default: "logradouro")
       data-target-neighborhood  → name do campo de bairro       (default: "bairro")
       data-target-city          → name do campo de cidade       (default: "cidade")
       data-target-state         → name do campo de UF/estado    (default: "uf")
       data-target-number        → name do campo de número       (default: "numero"; recebe foco)
       data-scope                → seletor pai onde procurar (default: form mais próximo)

     Comportamento:
       - Só sobrescreve campos VAZIOS (não apaga edição manual)
       - Foca no número quando o preenchimento termina
       - Falhas silenciosas (usuário pode digitar manualmente)
--}}
<script>
document.addEventListener('DOMContentLoaded', function () {
  const endpoint = @json(url('painel/api/cep'));

  function fillFromCep(cepInput) {
    const cep = (cepInput.value || '').replace(/\D/g, '');
    if (cep.length !== 8) return;

    const scopeSel = cepInput.dataset.scope;
    const scope = scopeSel ? document.querySelector(scopeSel) : (cepInput.closest('form') || document);

    const targets = {
      street:       cepInput.dataset.targetStreet       || 'logradouro',
      neighborhood: cepInput.dataset.targetNeighborhood || 'bairro',
      city:         cepInput.dataset.targetCity         || 'cidade',
      state:        cepInput.dataset.targetState        || 'uf',
      number:       cepInput.dataset.targetNumber       || 'numero',
    };

    fetch(endpoint + '/' + cep, { headers: { Accept: 'application/json' } })
      .then(function (r) { return r.ok ? r.json() : null; })
      .then(function (d) {
        if (! d) return;

        const setIfEmpty = function (name, value) {
          if (! value) return;
          const el = scope.querySelector('[name="' + name + '"]');
          if (el && ! el.value) {
            el.value = value;
            el.dispatchEvent(new Event('change', { bubbles: true }));
          }
        };

        setIfEmpty(targets.street,       d.street);
        setIfEmpty(targets.neighborhood, d.neighborhood);
        setIfEmpty(targets.city,         d.city);
        setIfEmpty(targets.state,        d.state);

        const numero = scope.querySelector('[name="' + targets.number + '"]');
        if (numero && ! numero.value) numero.focus();
      })
      .catch(function () {});
  }

  document.querySelectorAll('input[data-cep-autocomplete]').forEach(function (input) {
    if (input.dataset.cepAutocompleteBound) return;
    input.dataset.cepAutocompleteBound = '1';
    input.addEventListener('blur', function () { fillFromCep(input); });
  });
});
</script>
