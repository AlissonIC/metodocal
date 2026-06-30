import flatpickr from 'flatpickr/dist/flatpickr';
import { Portuguese } from 'flatpickr/dist/l10n/pt';

// Tudo que usa flatpickr no app é em pt-BR.
flatpickr.localize(Portuguese);

try {
  window.flatpickr = flatpickr;
} catch (e) {}

export { flatpickr };
