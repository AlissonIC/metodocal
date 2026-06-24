@php
  // Macro de logo do MetodoCal usado em navbar, sidebar, login, faturas, etc.
  // Mantém os mesmos props ($width, $height) que o SVG original usava para
  // não quebrar os 21 lugares que incluem este partial.
  $width = $width ?? '32';
  $height = $height ?? '32';
  $alt = $alt ?? 'MetodoCal';
@endphp

<img src="{{ asset('assets/img/branding/logo_transparente.png') }}"
  alt="{{ $alt }}"
  width="{{ $width }}"
  height="{{ $height }}"
  style="object-fit: contain;" />
