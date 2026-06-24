<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>{{ config('variables.templateName', 'MetodoCal') }} — Compre carros pelo preço de leilão, sem leilão</title>
  <meta name="description" content="O método que ensina a encontrar, avaliar e comprar veículos antes que vão a leilão — adquirindo carros até 60% abaixo do valor de mercado, com segurança jurídica.">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
  <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('assets/img/favicon/favicon-16x16.png') }}">
  <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/img/favicon/favicon-32x32.png') }}">
  <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/img/favicon/apple-touch-icon.png') }}">
  <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('assets/img/favicon/android-chrome-192x192.png') }}">
  <link rel="icon" type="image/png" sizes="512x512" href="{{ asset('assets/img/favicon/android-chrome-512x512.png') }}">

  @vite([
    'resources/assets/vendor/fonts/iconify/iconify.css',
    'resources/assets/vendor/scss/core.scss',
    'resources/css/app.css',
  ])

  <style>
    :root {
      --md-1: #007da8;
      --md-2: #09d2e8;
      --md-3: #11141a;
      --md-grad: linear-gradient(135deg, #007da8 0%, #09d2e8 100%);
      --md-grad-soft: linear-gradient(135deg, rgba(0,125,168,.08) 0%, rgba(9,210,232,.08) 100%);
      --md-grad-dark: linear-gradient(135deg, #0a1929 0%, #11141a 100%);
    }

    html { scroll-behavior: smooth; }
    body {
      background: #ffffff;
      color: #2f2b3d;
      font-family: 'Public Sans', system-ui, -apple-system, sans-serif;
      overflow-x: hidden;
    }

    /* ===== NAV ===== */
    .lp-nav {
      position: sticky; top: 0; z-index: 50;
      backdrop-filter: saturate(180%) blur(14px);
      -webkit-backdrop-filter: saturate(180%) blur(14px);
      background: rgba(255,255,255,.85);
      border-bottom: 1px solid rgba(0,0,0,.05);
    }
    .lp-nav .nav-link { color: #4b465c; font-weight: 500; }
    .lp-nav .nav-link:hover { color: var(--md-1); }
    .lp-logo {
      font-weight: 800; font-size: 1.5rem; letter-spacing: -0.02em;
      background: var(--md-grad);
      -webkit-background-clip: text; background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    .lp-logo-img { height: 38px; width: auto; display: block; }
    .lp-footer .lp-logo-img { height: 42px; }

    /* ===== BUTTONS ===== */
    .lp-btn {
      display: inline-flex; align-items: center; justify-content: center; gap: .5rem;
      padding: .9rem 1.6rem; border-radius: .7rem; font-weight: 700;
      transition: all .2s ease; border: 0; text-decoration: none; cursor: pointer;
      font-size: .98rem;
    }
    .lp-btn-primary { background: var(--md-grad); color: #fff; box-shadow: 0 10px 30px -10px rgba(0,125,168,.55); }
    .lp-btn-primary:hover { color: #fff; transform: translateY(-2px); box-shadow: 0 14px 34px -10px rgba(0,125,168,.7); }
    .lp-btn-ghost { background: transparent; color: #2f2b3d; border: 1px solid rgba(0,0,0,.1); }
    .lp-btn-ghost:hover { background: rgba(0,0,0,.03); color: var(--md-1); border-color: var(--md-1); }
    .lp-btn-white { background: #fff; color: var(--md-1); }
    .lp-btn-white:hover { color: var(--md-1); transform: translateY(-2px); box-shadow: 0 12px 30px -10px rgba(0,0,0,.35); }
    .lp-btn-dark { background: var(--md-3); color: #fff; }
    .lp-btn-dark:hover { color: #fff; transform: translateY(-2px); }

    /* ===== HERO ===== */
    .lp-hero {
      position: relative;
      padding: 6rem 0 5rem;
      overflow: hidden;
      background: #fafbff;
    }
    .lp-hero::before {
      content: ''; position: absolute; inset: 0;
      background:
        radial-gradient(900px circle at 8% 5%, rgba(9,210,232,.22), transparent 55%),
        radial-gradient(800px circle at 95% 25%, rgba(0,125,168,.20), transparent 60%),
        radial-gradient(600px circle at 50% 100%, rgba(0,125,168,.10), transparent 50%);
      z-index: 0; pointer-events: none;
    }
    .lp-hero > .container { position: relative; z-index: 1; }
    .lp-eyebrow {
      display: inline-flex; align-items: center; gap: .5rem;
      padding: .45rem 1rem; border-radius: 999px;
      background: rgba(220, 38, 38, .08); color: #dc2626;
      font-size: .82rem; font-weight: 700; letter-spacing: .02em;
    }
    .lp-eyebrow .dot {
      width: 7px; height: 7px; border-radius: 50%; background: #dc2626;
      animation: pulse 1.6s ease-in-out infinite;
    }
    @keyframes pulse {
      0%,100% { opacity: 1; transform: scale(1); }
      50% { opacity: .5; transform: scale(1.4); }
    }
    .lp-h1 {
      font-size: clamp(2.5rem, 5.5vw, 4.6rem);
      font-weight: 800; line-height: 1.04; letter-spacing: -0.035em;
      color: #0a1929; margin: 1.25rem 0 1.5rem;
    }
    .lp-h1 .grad {
      background: var(--md-grad);
      -webkit-background-clip: text; background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    .lp-h1 .strike {
      text-decoration: line-through; text-decoration-color: #dc2626; text-decoration-thickness: 3px;
      color: #94a3b8;
    }
    .lp-sub { font-size: 1.18rem; color: #5b6478; max-width: 580px; line-height: 1.6; }

    .lp-hero-trust { display: flex; flex-wrap: wrap; gap: 1.5rem; align-items: center; margin-top: 2rem; color: #5b6478; font-size: .9rem; }
    .lp-hero-trust .check { color: #16a34a; }

    /* Hero visual: comparativo */
    .lp-compare {
      background: #fff; border-radius: 1.5rem; padding: 1.75rem;
      box-shadow: 0 30px 60px -25px rgba(0,30,80,.2);
      border: 1px solid rgba(0,0,0,.04); position: relative;
    }
    .lp-compare::after {
      content: ''; position: absolute; top: -16px; right: -16px;
      width: 80px; height: 80px; border-radius: 50%;
      background: var(--md-grad); opacity: .15; filter: blur(20px);
    }
    .lp-compare-row {
      display: flex; justify-content: space-between; align-items: center;
      padding: 1rem 1.25rem; border-radius: .9rem;
      margin-bottom: .65rem;
    }
    .lp-compare-row.bad {
      background: linear-gradient(90deg, rgba(220,38,38,.08), rgba(220,38,38,.02));
      border: 1px solid rgba(220,38,38,.15);
    }
    .lp-compare-row.good {
      background: linear-gradient(90deg, rgba(22,163,74,.08), rgba(22,163,74,.02));
      border: 1px solid rgba(22,163,74,.18);
    }
    .lp-compare-row .lbl { display: flex; align-items: center; gap: .65rem; font-weight: 600; color: #1a1a2e; }
    .lp-compare-row .val { font-weight: 800; font-size: 1.4rem; letter-spacing: -0.02em; }
    .lp-compare-row.bad .val { color: #dc2626; }
    .lp-compare-row.good .val { color: #16a34a; }
    .lp-compare-icon {
      width: 36px; height: 36px; border-radius: 10px;
      display: inline-flex; align-items: center; justify-content: center;
      font-size: 1.1rem;
    }
    .lp-compare-icon.bad { background: rgba(220,38,38,.12); color: #dc2626; }
    .lp-compare-icon.good { background: rgba(22,163,74,.12); color: #16a34a; }
    .lp-compare-diff {
      background: var(--md-grad); color: #fff;
      border-radius: .9rem; padding: 1rem 1.25rem; margin-top: .75rem;
      display: flex; justify-content: space-between; align-items: center;
    }
    .lp-compare-diff .lbl { font-weight: 600; opacity: .9; }
    .lp-compare-diff .val { font-weight: 800; font-size: 1.6rem; }

    /* ===== TRUST STRIP ===== */
    .lp-trust { background: #fafbff; padding: 2.5rem 0; border-top: 1px solid rgba(0,0,0,.04); }
    .lp-stat-num { font-size: 2.6rem; font-weight: 800;
      background: var(--md-grad);
      -webkit-background-clip: text; background-clip: text;
      -webkit-text-fill-color: transparent; line-height: 1;
    }
    .lp-stat-lbl { color: #5b6478; font-size: .95rem; margin-top: .4rem; font-weight: 500; }

    /* ===== SECTIONS ===== */
    section { scroll-margin-top: 100px; }
    .lp-section { padding: 6rem 0; }
    .lp-section-title { font-size: clamp(1.85rem, 3.8vw, 2.8rem); font-weight: 800; letter-spacing: -0.025em; color: #0a1929; }
    .lp-section-sub { color: #5b6478; font-size: 1.1rem; max-width: 680px; margin: 0 auto; line-height: 1.6; }
    .lp-tag {
      display: inline-block; padding: .4rem 1rem; border-radius: 999px;
      background: var(--md-grad-soft); color: var(--md-1);
      font-size: .8rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase;
      margin-bottom: 1.2rem;
    }
    .lp-bg-alt { background: #fafbff; }
    .lp-bg-dark { background: var(--md-grad-dark); color: rgba(255,255,255,.85); position: relative; overflow: hidden; }
    .lp-bg-dark::before {
      content: ''; position: absolute; inset: 0;
      background:
        radial-gradient(700px circle at 10% 50%, rgba(9,210,232,.15), transparent 50%),
        radial-gradient(700px circle at 90% 50%, rgba(0,125,168,.12), transparent 50%);
      pointer-events: none;
    }
    .lp-bg-dark > .container { position: relative; z-index: 1; }
    .lp-bg-dark .lp-section-title { color: #fff; }
    .lp-bg-dark .lp-section-sub { color: rgba(255,255,255,.7); }

    /* ===== PROBLEMA ===== */
    .lp-pain {
      background: #fff; border-radius: 1rem; padding: 1.75rem;
      border: 1px solid rgba(220,38,38,.15);
      height: 100%; position: relative;
    }
    .lp-pain-icon {
      width: 52px; height: 52px; border-radius: 14px;
      display: inline-flex; align-items: center; justify-content: center;
      background: rgba(220,38,38,.1); color: #dc2626;
      font-size: 1.5rem; margin-bottom: 1rem;
    }
    .lp-pain h5 { font-weight: 700; color: #0a1929; margin-bottom: .5rem; }
    .lp-pain p { color: #5b6478; margin: 0; font-size: .95rem; line-height: 1.6; }

    /* ===== MÉTODO (4 PASSOS) ===== */
    .lp-method-grid {
      position: relative;
    }
    .lp-step-card {
      background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.1);
      border-radius: 1rem; padding: 1.75rem; height: 100%;
      backdrop-filter: blur(10px);
      transition: all .3s ease;
    }
    .lp-step-card:hover { background: rgba(255,255,255,.08); transform: translateY(-3px); border-color: rgba(9,210,232,.4); }
    .lp-step-num {
      display: inline-block;
      width: 52px; height: 52px; border-radius: 14px;
      background: var(--md-grad);
      color: #fff; font-weight: 800; font-size: 1.3rem;
      line-height: 52px; text-align: center;
      margin-bottom: 1rem;
      box-shadow: 0 10px 25px -8px rgba(9,210,232,.5);
    }
    .lp-step-card h5 { font-weight: 700; color: #fff; margin-bottom: .5rem; font-size: 1.15rem; }
    .lp-step-card p { color: rgba(255,255,255,.65); margin: 0; line-height: 1.6; font-size: .95rem; }

    /* ===== FEATURES (módulos auxiliares) ===== */
    .lp-feature {
      background: #fff; padding: 1.85rem; border-radius: 1.1rem;
      border: 1px solid rgba(0,0,0,.06);
      transition: all .3s ease; height: 100%; position: relative; overflow: hidden;
    }
    .lp-feature::before {
      content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
      background: var(--md-grad); opacity: 0;
      transition: opacity .3s ease;
    }
    .lp-feature:hover { transform: translateY(-5px); border-color: rgba(0,125,168,.25); box-shadow: 0 25px 50px -25px rgba(0,125,168,.25); }
    .lp-feature:hover::before { opacity: 1; }
    .lp-feature-head { display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem; }
    .lp-feature-icon {
      width: 52px; height: 52px; border-radius: 13px;
      display: inline-flex; align-items: center; justify-content: center;
      background: var(--md-grad-soft); color: var(--md-1); font-size: 1.45rem;
      flex-shrink: 0;
    }
    .lp-feature h5 { font-weight: 700; color: #0a1929; margin: 0; font-size: 1.1rem; line-height: 1.3; }
    .lp-feature p { color: #5b6478; margin: 0; font-size: .95rem; line-height: 1.6; }
    .lp-feature .pill {
      position: absolute; top: 1.1rem; right: 1.1rem;
      padding: .2rem .65rem; border-radius: 999px;
      background: rgba(22,163,74,.1); color: #16a34a;
      font-size: .68rem; font-weight: 700; letter-spacing: .04em;
    }

    /* ===== ANTES vs DEPOIS ===== */
    .lp-compare-side {
      background: #fff; border-radius: 1.2rem; padding: 2rem;
      border: 1px solid rgba(0,0,0,.06); height: 100%;
    }
    .lp-compare-side.bad { border-color: rgba(220,38,38,.2); }
    .lp-compare-side.good { border: 2px solid var(--md-1); box-shadow: 0 25px 50px -25px rgba(0,125,168,.3); position: relative; }
    .lp-compare-side.good::before {
      content: 'Com o Método'; position: absolute; top: -14px; left: 2rem;
      background: var(--md-grad); color: #fff;
      padding: .25rem .85rem; border-radius: 999px;
      font-size: .75rem; font-weight: 700; letter-spacing: .04em;
    }
    .lp-compare-side h4 { font-weight: 800; color: #0a1929; margin-bottom: 1.25rem; }
    .lp-compare-side ul { list-style: none; padding: 0; margin: 0; }
    .lp-compare-side li { padding: .65rem 0; display: flex; align-items: flex-start; gap: .65rem; color: #4b465c; line-height: 1.5; border-bottom: 1px solid rgba(0,0,0,.04); }
    .lp-compare-side li:last-child { border-bottom: 0; }
    .lp-compare-side.bad li i { color: #dc2626; margin-top: 2px; }
    .lp-compare-side.good li i { color: #16a34a; margin-top: 2px; }

    /* ===== PRICING ===== */
    .lp-plan {
      background: #fff; border-radius: 1.2rem; padding: 2rem;
      border: 1px solid rgba(0,0,0,.08);
      transition: all .3s ease; height: 100%; position: relative;
    }
    .lp-plan:hover { transform: translateY(-5px); box-shadow: 0 25px 50px -25px rgba(0,125,168,.2); }
    .lp-plan-featured { border: 2px solid var(--md-1); transform: scale(1.03); box-shadow: 0 30px 60px -25px rgba(0,125,168,.3); }
    .lp-plan-featured::before {
      content: 'Mais escolhido'; position: absolute; top: -14px; left: 50%; transform: translateX(-50%);
      background: var(--md-grad); color: #fff;
      padding: .3rem .9rem; border-radius: 999px;
      font-size: .75rem; font-weight: 700; letter-spacing: .04em;
    }
    .lp-plan h4 { font-weight: 700; color: #0a1929; }
    .lp-plan-price { font-size: 2.8rem; font-weight: 800; color: #0a1929; line-height: 1; }
    .lp-plan-price .currency { font-size: 1.1rem; vertical-align: top; color: #5b6478; font-weight: 600; }
    .lp-plan-price .period { font-size: .9rem; color: #5b6478; font-weight: 500; }

    /* ===== TESTIMONIAL ===== */
    .lp-testimonial {
      background: #fff; border-radius: 1.2rem; padding: 1.85rem;
      border: 1px solid rgba(0,0,0,.06); height: 100%;
      transition: all .3s ease;
    }
    .lp-testimonial:hover { transform: translateY(-3px); box-shadow: 0 20px 40px -20px rgba(0,30,80,.12); }
    .lp-testimonial p { color: #4b465c; line-height: 1.7; }
    .lp-testimonial .saving {
      display: inline-block; padding: .25rem .65rem; border-radius: .5rem;
      background: rgba(22,163,74,.1); color: #16a34a;
      font-size: .82rem; font-weight: 700; margin-bottom: .85rem;
    }
    .lp-avatar {
      width: 48px; height: 48px; border-radius: 50%;
      background: var(--md-grad); color: #fff;
      display: inline-flex; align-items: center; justify-content: center;
      font-weight: 800;
    }

    /* ===== FAQ ===== */
    .lp-faq .accordion-item { border: 1px solid rgba(0,0,0,.06); border-radius: .85rem !important; margin-bottom: .75rem; overflow: hidden; background: #fff; }
    .lp-faq .accordion-button { font-weight: 600; color: #0a1929; padding: 1.25rem 1.35rem; background: #fff; }
    .lp-faq .accordion-button:not(.collapsed) { background: var(--md-grad-soft); color: var(--md-1); box-shadow: none; }
    .lp-faq .accordion-button:focus { box-shadow: none; border-color: transparent; }
    .lp-faq .accordion-body { color: #5b6478; line-height: 1.7; padding: 1rem 1.35rem 1.5rem; }

    /* ===== CTA ===== */
    .lp-cta {
      background: var(--md-grad); color: #fff;
      border-radius: 1.5rem; padding: 4.5rem 2.5rem; text-align: center;
      position: relative; overflow: hidden;
    }
    .lp-cta::before {
      content: ''; position: absolute; top: -50%; right: -10%; width: 60%; height: 200%;
      background: radial-gradient(circle, rgba(255,255,255,.15) 0%, transparent 70%);
      pointer-events: none;
    }
    .lp-cta > * { position: relative; z-index: 1; }
    .lp-cta h3 { font-weight: 800; font-size: clamp(1.7rem, 3.2vw, 2.5rem); letter-spacing: -0.025em; }
    .lp-cta p { color: rgba(255,255,255,.9); max-width: 580px; margin: 0 auto 2rem; font-size: 1.05rem; }

    /* ===== FOOTER ===== */
    .lp-footer { background: #11141a; color: rgba(255,255,255,.7); padding: 4rem 0 1.5rem; }
    .lp-footer h6 { color: #fff; font-weight: 700; margin-bottom: 1rem; }
    .lp-footer a { color: rgba(255,255,255,.7); text-decoration: none; transition: color .2s ease; }
    .lp-footer a:hover { color: var(--md-2); }
    .lp-footer .lp-logo { -webkit-text-fill-color: initial; color: #fff; background: none; }
    .lp-footer-desc { max-width: 320px; }

    /* ===== TABLET / DOWN ===== */
    @media (max-width: 991px) {
      .lp-hero { padding: 3rem 0 3rem; text-align: center; }
      .lp-hero .lp-sub { margin-left: auto; margin-right: auto; }
      /* CTAs do hero centralizam (d-flex precisa de justify-content explícito) */
      .lp-hero .d-flex.flex-wrap.gap-3 { justify-content: center; }
      .lp-hero-trust { justify-content: center; }
      .lp-plan-featured { transform: none; }
      .lp-compare-side.good { margin-top: 2rem; }
      .lp-section { padding: 4rem 0; }
      .lp-cta { padding: 3.5rem 1.5rem; }
    }

    /* ===== MOBILE (≤ 575px) ===== */
    @media (max-width: 575px) {
      /* Nav: logo menor, botão principal compacto */
      .lp-nav .container { padding-left: 1rem; padding-right: 1rem; }
      .lp-logo { font-size: 1.2rem; }
      .lp-logo-img { height: 30px; }
      .lp-footer .lp-logo-img { height: 34px; }
      .lp-nav .lp-btn { padding: .55rem .9rem; font-size: .85rem; }

      /* Hero — tipografia contida + respiro entre blocos */
      .lp-hero { padding: 2.5rem 0 3rem; }
      .lp-eyebrow { font-size: .72rem; padding: .4rem .85rem; margin-bottom: .5rem; }
      .lp-h1 {
        font-size: clamp(1.85rem, 8vw, 2.3rem);
        line-height: 1.2;
        letter-spacing: -0.02em;
        margin: 1.25rem 0 1.5rem;
      }
      .lp-h1 .strike { text-decoration-thickness: 2px; }
      .lp-sub { font-size: 1rem; line-height: 1.55; margin-bottom: 0; }

      /* Botões grandes (hero, cta, planos): full-width pra dar peso visual */
      .lp-hero .lp-btn,
      .lp-cta .lp-btn,
      .lp-plan .lp-btn { width: 100%; padding: .85rem 1.2rem; font-size: .95rem; }
      .lp-hero .d-flex.flex-wrap.gap-3 { gap: .65rem !important; margin-top: 2rem !important; }
      .lp-cta .d-flex { flex-direction: column; gap: .6rem !important; }

      /* Trust badges em coluna, com mais respiro do bloco anterior */
      .lp-hero-trust { gap: .75rem; flex-direction: column; align-items: center; font-size: .85rem; margin-top: 2rem; }

      /* Card comparativo do hero — header em coluna, badge oculto, valores menores */
      .lp-compare { padding: 1.25rem; border-radius: 1.1rem; margin-top: 2.5rem; }
      .lp-compare > .d-flex { flex-direction: column; align-items: flex-start !important; gap: .25rem; margin-bottom: 1rem !important; }
      .lp-compare > .d-flex .badge { display: none; }
      .lp-compare h6 { font-size: .82rem; line-height: 1.4; }
      .lp-compare-row { padding: .85rem 1rem; }
      .lp-compare-row .lbl { font-size: .9rem; gap: .5rem; }
      .lp-compare-row .val { font-size: 1.15rem; }
      .lp-compare-icon { width: 32px; height: 32px; font-size: 1rem; }
      .lp-compare-diff { padding: .85rem 1rem; }
      .lp-compare-diff .lbl { font-size: .85rem; }
      .lp-compare-diff .val { font-size: 1.3rem; }

      /* Trust strip */
      .lp-trust { padding: 2rem 0; }
      .lp-stat-num { font-size: 1.85rem; }
      .lp-stat-lbl { font-size: .82rem; margin-top: .25rem; }

      /* Sections */
      .lp-section { padding: 3rem 0; }
      .lp-section-title { font-size: 1.55rem; line-height: 1.25; }
      .lp-section-sub { font-size: .98rem; line-height: 1.55; }
      .lp-tag { font-size: .72rem; padding: .3rem .8rem; margin-bottom: .85rem; }

      /* Cards (pain, feature, step, plan, testimonial) */
      .lp-pain, .lp-feature, .lp-testimonial { padding: 1.4rem; border-radius: .9rem; }
      .lp-pain h5, .lp-feature h5 { font-size: 1.05rem; }
      .lp-pain p, .lp-feature p, .lp-testimonial p { font-size: .92rem; line-height: 1.55; }
      .lp-pain-icon { width: 48px; height: 48px; font-size: 1.3rem; margin-bottom: .9rem; }
      .lp-feature .pill { top: .85rem; right: .85rem; }
      .lp-feature-head { gap: .85rem; margin-bottom: .85rem; padding-right: 3.5rem; }
      .lp-feature-icon { width: 44px; height: 44px; font-size: 1.2rem; border-radius: 11px; }
      .lp-step-card { padding: 1.4rem; }
      .lp-step-num { width: 46px; height: 46px; line-height: 46px; font-size: 1.15rem; border-radius: 12px; margin-bottom: .85rem; }
      .lp-step-card h5 { font-size: 1.05rem; }

      /* Antes/depois */
      .lp-compare-side { padding: 1.4rem; }
      .lp-compare-side h4 { font-size: 1.1rem; }
      .lp-compare-side li { padding: .55rem 0; font-size: .92rem; }

      /* Plans */
      .lp-plan { padding: 1.6rem 1.4rem; }
      .lp-plan h4 { font-size: 1.2rem; }
      .lp-plan-price { font-size: 2.2rem; }
      .lp-plan-price .currency { font-size: .95rem; }

      /* Filtros de plano: caso quebrem em mobile */
      #filter-recorrencia { flex-wrap: wrap; gap: .35rem; }
      #filter-recorrencia .btn { border-radius: .5rem !important; flex: 1 1 auto; min-width: 30%; }

      /* Testimonials */
      .lp-testimonial .saving { font-size: .75rem; }
      .lp-avatar { width: 40px; height: 40px; font-size: .9rem; }

      /* FAQ */
      .lp-faq .accordion-button { padding: 1rem 1.1rem; font-size: .95rem; }
      .lp-faq .accordion-body { padding: .85rem 1.1rem 1.2rem; font-size: .92rem; }

      /* CTA final */
      .lp-cta { padding: 2.75rem 1.25rem; border-radius: 1.2rem; }
      .lp-cta h3 { font-size: 1.4rem; line-height: 1.25; }
      .lp-cta p { font-size: .95rem; }

      /* Footer */
      .lp-footer { padding: 2.5rem 0 1rem; text-align: center; }
      .lp-footer-desc { max-width: 100%; margin-left: auto; margin-right: auto; }
      .lp-footer .col-md-2.offset-md-2 { margin-top: 1rem; }
    }
  </style>
</head>
<body>

<!-- ============ NAV ============ -->
<nav class="lp-nav">
  <div class="container py-3 d-flex align-items-center justify-content-between">
    <a href="{{ route('home') }}" class="text-decoration-none d-inline-flex align-items-center">
      <img src="{{ asset('assets/img/branding/logo_transparente.png') }}" alt="{{ config('variables.templateName', 'MetodoCal') }}" class="lp-logo-img">
    </a>

    <ul class="nav d-none d-lg-flex">
      <li class="nav-item"><a class="nav-link" href="#oportunidade">A oportunidade</a></li>
      <li class="nav-item"><a class="nav-link" href="#metodo">O método</a></li>
      <li class="nav-item"><a class="nav-link" href="#recursos">Recursos</a></li>
      @if ($planos->count())
        <li class="nav-item"><a class="nav-link" href="#planos">Planos</a></li>
      @endif
      <li class="nav-item"><a class="nav-link" href="#faq">FAQ</a></li>
    </ul>

    <div class="d-flex gap-2 align-items-center">
      @auth
        <a href="{{ route('dashboard') }}" class="lp-btn lp-btn-ghost">Painel</a>
      @else
        <a href="{{ route('login') }}" class="lp-btn lp-btn-ghost d-none d-sm-inline-flex">Entrar</a>
        <a href="{{ route('register') }}" class="lp-btn lp-btn-primary">Quero aprender</a>
      @endauth
    </div>
  </div>
</nav>

<!-- ============ HERO ============ -->
<header class="lp-hero">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-6">
        <span class="lp-eyebrow"><span class="dot"></span> Acesso a um mercado que ninguém te contou</span>
        <h1 class="lp-h1">
          Compre carros pelo <span class="strike">preço de mercado</span>.<br>
          <span class="grad">Pague preço de leilão — sem leiloar.</span>
        </h1>
        <p class="lp-sub">
          O método que ensina a <strong>identificar, avaliar e negociar veículos antes que vão a leilão</strong> — comprando direto do proprietário com até <strong class="text-success">60% de desconto</strong> sobre o valor de mercado.
        </p>

        <div class="d-flex flex-wrap gap-3 mt-4">
          <a href="{{ route('register') }}" class="lp-btn lp-btn-primary">
            <i class="icon-base ti tabler-target"></i> Quero aprender o método
          </a>
          <a href="#metodo" class="lp-btn lp-btn-ghost">
            <i class="icon-base ti tabler-player-play"></i> Como funciona
          </a>
        </div>

        <div class="lp-hero-trust">
          <span><i class="icon-base ti tabler-shield-check check"></i> Sem cartão</span>
          <span><i class="icon-base ti tabler-clock check"></i> Acesso imediato</span>
          <span><i class="icon-base ti tabler-arrow-back-up check"></i> Cancele quando quiser</span>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="lp-compare">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0 fw-bold" style="color:#0a1929;">Exemplo · Honda Civic 2019 · Tab. R$ 95.000</h6>
            <span class="badge bg-label-secondary">Comparativo</span>
          </div>

          <div class="lp-compare-row bad">
            <span class="lbl">
              <span class="lp-compare-icon bad"><i class="icon-base ti tabler-shopping-cart"></i></span>
              Mercado tradicional
            </span>
            <span class="val">R$ 92.000</span>
          </div>

          <div class="lp-compare-row good">
            <span class="lbl">
              <span class="lp-compare-icon good"><i class="icon-base ti tabler-target-arrow"></i></span>
              Com o Método
            </span>
            <span class="val">R$ 48.000</span>
          </div>

          <div class="lp-compare-diff">
            <span class="lbl"><i class="icon-base ti tabler-coin me-1"></i> Economia</span>
            <span class="val">+R$ 44.000</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</header>

<!-- ============ TRUST STRIP ============ -->
<section class="lp-trust">
  <div class="container">
    <div class="row text-center g-4">
      <div class="col-6 col-md-3"><div class="lp-stat-num">+1.200</div><div class="lp-stat-lbl">Veículos adquiridos pelos alunos</div></div>
      <div class="col-6 col-md-3"><div class="lp-stat-num">60%</div><div class="lp-stat-lbl">Desconto médio sobre o mercado</div></div>
      <div class="col-6 col-md-3"><div class="lp-stat-num">15 dias</div><div class="lp-stat-lbl">Pra fechar a primeira compra</div></div>
      <div class="col-6 col-md-3"><div class="lp-stat-num">98%</div><div class="lp-stat-lbl">De aprovação dos alunos</div></div>
    </div>
  </div>
</section>

<!-- ============ OPORTUNIDADE ============ -->
<section id="oportunidade" class="lp-section">
  <div class="container">
    <div class="text-center mb-5">
      <span class="lp-tag" style="background: rgba(220,38,38,.08); color: #dc2626;">A oportunidade que poucos veem</span>
      <h2 class="lp-section-title">Existe um mercado paralelo ao leilão</h2>
      <p class="lp-section-sub mt-3">Todo dia, milhares de proprietários estão prestes a perder seus veículos pra leilão — e estariam dispostos a vender por uma fração do valor pra evitar a perda total. Esse é o seu mercado.</p>
    </div>

    <div class="row g-4">
      <div class="col-md-6 col-lg-3">
        <div class="lp-pain">
          <div class="lp-pain-icon"><i class="icon-base ti tabler-trending-down"></i></div>
          <h5>Quem compra no mercado, paga caro</h5>
          <p>Tabela FIPE, OLX, Webmotors — todos cobram <strong>preço de varejo</strong>. Margem zero pra revenda, custo alto pra uso próprio.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="lp-pain">
          <div class="lp-pain-icon"><i class="icon-base ti tabler-gavel"></i></div>
          <h5>Leilão oficial não é solução</h5>
          <p>Concorrência alta, taxas de até <strong>15%</strong>, risco jurídico e veículos sem inspeção prévia. Margem apertada.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="lp-pain">
          <div class="lp-pain-icon"><i class="icon-base ti tabler-search-off"></i></div>
          <h5>Veículos pré-leilão são invisíveis</h5>
          <p>Não tem site nem feirão pra esses carros. <strong>Quem não sabe onde olhar, não acha</strong>. E quem acha, fecha o negócio.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="lp-pain">
          <div class="lp-pain-icon"><i class="icon-base ti tabler-alert-triangle"></i></div>
          <h5>Comprar sozinho é arriscado</h5>
          <p>Dívidas atreladas ao veículo, transferência travada, golpes documentais. <strong>Sem método, sem segurança</strong>.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============ MÉTODO ============ -->
<section id="metodo" class="lp-section lp-bg-dark">
  <div class="container">
    <div class="text-center mb-5">
      <span class="lp-tag" style="background: rgba(9,210,232,.15); color: #09d2e8;">O método</span>
      <h2 class="lp-section-title">Como comprar carros antes do leilão em 4 passos</h2>
      <p class="lp-section-sub mt-3">O processo replicável que mais de mil alunos já usaram pra montar frota, revender ou comprar o veículo dos sonhos por uma fração do preço.</p>
    </div>

    <div class="row g-4 lp-method-grid">
      <div class="col-md-6 col-lg-3">
        <div class="lp-step-card">
          <div class="lp-step-num">1</div>
          <h5>Identifique oportunidades</h5>
          <p>Aprenda os canais (formais e informais) onde encontrar proprietários em situação pré-leilão dispostos a negociar.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="lp-step-card">
          <div class="lp-step-num">2</div>
          <h5>Avalie o veículo</h5>
          <p>Cheque pendências, débitos, restrições e estado real do carro. Saiba o valor justo e o teto da negociação.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="lp-step-card">
          <div class="lp-step-num">3</div>
          <h5>Negocie com técnica</h5>
          <p>Scripts de abordagem, gatilhos de fechamento e estrutura de oferta que faz o proprietário aceitar — ganho mútuo.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="lp-step-card">
          <div class="lp-step-num">4</div>
          <h5>Regularize e use (ou revenda)</h5>
          <p>Resolva pendências, faça a transferência segura e decida: use, monte frota ou revenda pelo valor de mercado.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============ ANTES vs DEPOIS ============ -->
<section class="lp-section lp-bg-alt">
  <div class="container">
    <div class="text-center mb-5">
      <span class="lp-tag">Antes vs depois</span>
      <h2 class="lp-section-title">A diferença entre comprar e investir</h2>
    </div>

    <div class="row g-4 justify-content-center" style="max-width: 1100px; margin: 0 auto;">
      <div class="col-md-6">
        <div class="lp-compare-side bad">
          <h4><i class="icon-base ti tabler-mood-sad text-danger me-2"></i> Comprando do jeito comum</h4>
          <ul>
            <li><i class="icon-base ti tabler-x"></i> Paga preço de tabela ou perto disso</li>
            <li><i class="icon-base ti tabler-x"></i> Margem de revenda quase zero</li>
            <li><i class="icon-base ti tabler-x"></i> Concorrência alta em leilão e marketplaces</li>
            <li><i class="icon-base ti tabler-x"></i> Compra cara também o financiamento</li>
            <li><i class="icon-base ti tabler-x"></i> Sem suporte jurídico pra resolver pendências</li>
            <li><i class="icon-base ti tabler-x"></i> Demora meses pra encontrar bom negócio</li>
          </ul>
        </div>
      </div>

      <div class="col-md-6">
        <div class="lp-compare-side good">
          <h4><i class="icon-base ti tabler-mood-happy text-success me-2"></i> Com o método</h4>
          <ul>
            <li><i class="icon-base ti tabler-check"></i> Veículos com até <strong>60% de desconto</strong></li>
            <li><i class="icon-base ti tabler-check"></i> Margem real de revenda — lucro garantido</li>
            <li><i class="icon-base ti tabler-check"></i> Acesso a veículos que ninguém vê</li>
            <li><i class="icon-base ti tabler-check"></i> Negociação direto com o dono — sem intermediários</li>
            <li><i class="icon-base ti tabler-check"></i> Limpa nome e regularização inclusos</li>
            <li><i class="icon-base ti tabler-check"></i> Primeira compra em <strong>15 dias</strong>, em média</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============ RECURSOS / FERRAMENTAS ============ -->
<section id="recursos" class="lp-section">
  <div class="container">
    <div class="text-center mb-5">
      <span class="lp-tag">Ferramentas inclusas</span>
      <h2 class="lp-section-title">Tudo que você precisa pra fechar o primeiro negócio</h2>
      <p class="lp-section-sub mt-3">A plataforma reúne aulas, mentoria, scripts e parceiros — pra você executar o método sem sair do painel.</p>
    </div>

    <div class="row g-4">
      <div class="col-md-6 col-lg-4">
        <div class="lp-feature">
          <span class="pill">Núcleo</span>
          <div class="lp-feature-head">
            <div class="lp-feature-icon"><i class="icon-base ti tabler-book"></i></div>
            <h5>Trilha de conteúdo</h5>
          </div>
          <p>Aulas em vídeo do zero ao avançado: prospecção, avaliação, negociação, fechamento e revenda.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-4">
        <div class="lp-feature">
          <span class="pill">Núcleo</span>
          <div class="lp-feature-head">
            <div class="lp-feature-icon"><i class="icon-base ti tabler-calendar"></i></div>
            <h5>Mentoria 1-a-1</h5>
          </div>
          <p>Sessões com especialistas pra revisar oportunidades reais, scripts de abordagem e fechamentos.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-4">
        <div class="lp-feature">
          <span class="pill">Núcleo</span>
          <div class="lp-feature-head">
            <div class="lp-feature-icon"><i class="icon-base ti tabler-files"></i></div>
            <h5>Scripts e contratos</h5>
          </div>
          <p>Modelos de oferta, contratos de compra e venda e planilhas de avaliação — testados e aprovados.</p>
        </div>
      </div>

      <div class="col-md-6 col-lg-4">
        <div class="lp-feature">
          <span class="pill" style="background: rgba(9,210,232,.12); color: var(--md-1);">Plus</span>
          <div class="lp-feature-head">
            <div class="lp-feature-icon"><i class="icon-base ti tabler-shield-check"></i></div>
            <h5>Limpa Nome integrado</h5>
          </div>
          <p>Resolva pendências do CPF do vendedor (ou do seu) em até 45 dias — destrava negócios travados.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-4">
        <div class="lp-feature">
          <span class="pill" style="background: rgba(9,210,232,.12); color: var(--md-1);">Plus</span>
          <div class="lp-feature-head">
            <div class="lp-feature-icon"><i class="icon-base ti tabler-truck"></i></div>
            <h5>Rede de guincho</h5>
          </div>
          <p>Parceiros em todo o Brasil pra remover o veículo comprado com segurança e custo baixo.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-4">
        <div class="lp-feature">
          <span class="pill" style="background: rgba(9,210,232,.12); color: var(--md-1);">Plus</span>
          <div class="lp-feature-head">
            <div class="lp-feature-icon"><i class="icon-base ti tabler-users"></i></div>
            <h5>CRM de oportunidades</h5>
          </div>
          <p>Organize seus contatos, negociações em andamento e fechamentos — tudo em um painel só.</p>
        </div>
      </div>
    </div>
  </div>
</section>

@if ($planos->count())
@php
  $recorrenciaLabels = ['mensal' => 'mês', 'anual' => 'ano', 'vitalicio' => 'pgto único', 'trimestral' => 'trimestre', 'semestral' => 'semestre'];
  $recorrenciaTabs = ['mensal' => 'Mensal', 'trimestral' => 'Trimestral', 'semestral' => 'Semestral', 'anual' => 'Anual', 'vitalicio' => 'Vitalício'];
  $recorrenciaPadrao = $recorrenciasDisponiveis->first();

  // Conta planos por recorrência pra calcular o "Mais escolhido" por aba
  $featuredPorRec = [];
  foreach ($recorrenciasDisponiveis as $rec) {
      $idsDaRec = $planos->where('recorrencia', $rec)->pluck('id')->values();
      if ($idsDaRec->count() > 1) {
          $featuredPorRec[$rec] = $idsDaRec->get((int) floor($idsDaRec->count() / 2));
      }
  }
@endphp
<!-- ============ PLANOS ============ -->
<section id="planos" class="lp-section lp-bg-alt">
  <div class="container">
    <div class="text-center mb-5">
      <span class="lp-tag">Planos</span>
      <h2 class="lp-section-title">Quanto custa começar a comprar bem?</h2>
      <p class="lp-section-sub mt-3">O investimento se paga na primeira negociação. Sem fidelidade — cancele quando quiser.</p>
    </div>

    @if ($recorrenciasDisponiveis->count() > 1)
      <div class="d-flex justify-content-center mb-5">
        <div class="btn-group" role="group" id="filter-recorrencia">
          @foreach ($recorrenciasDisponiveis as $rec)
            <button type="button" class="btn btn-outline-primary {{ $rec === $recorrenciaPadrao ? 'active' : '' }}" data-recorrencia="{{ $rec }}">
              {{ $recorrenciaTabs[$rec] ?? ucfirst($rec) }}
            </button>
          @endforeach
        </div>
      </div>
    @endif

    <div class="row g-4 justify-content-center" id="planos-grid">
      @foreach ($planos as $p)
        @php $featured = ($featuredPorRec[$p->recorrencia] ?? null) === $p->id; @endphp
        <div class="col-md-6 col-lg-4 plano-item" data-recorrencia="{{ $p->recorrencia }}" @if ($p->recorrencia !== $recorrenciaPadrao) style="display:none;" @endif>
          <div class="lp-plan @if ($featured) lp-plan-featured @endif">
            <h4 class="mb-1">{{ $p->nome }}</h4>
            <small class="text-muted text-uppercase fw-semibold" style="letter-spacing: .06em;">{{ ucfirst($p->tipo) }}</small>
            <div class="my-4">
              <span class="lp-plan-price">
                <span class="currency">R$</span>{{ number_format((float) $p->preco, 0, ',', '.') }}<span class="period">/{{ $recorrenciaLabels[$p->recorrencia] ?? $p->recorrencia }}</span>
              </span>
            </div>
            @if ($p->descricao)
              <p class="text-muted mb-4">{{ $p->descricao }}</p>
            @endif
            <a href="{{ route('register') }}" class="lp-btn @if ($featured) lp-btn-primary @else lp-btn-ghost @endif w-100">
              Começar agora
            </a>
          </div>
        </div>
      @endforeach
    </div>
  </div>
</section>
@endif

<!-- ============ DEPOIMENTOS ============ -->
<section class="lp-section">
  <div class="container">
    <div class="text-center mb-5">
      <span class="lp-tag">Histórias reais</span>
      <h2 class="lp-section-title">Eles compraram bem antes do leilão</h2>
    </div>

    <div class="row g-4">
      <div class="col-md-4">
        <div class="lp-testimonial">
          <span class="saving">Comprou R$ 48k · Tab. R$ 95k</span>
          <div class="text-warning mb-2"><i class="icon-base ti tabler-star-filled"></i><i class="icon-base ti tabler-star-filled"></i><i class="icon-base ti tabler-star-filled"></i><i class="icon-base ti tabler-star-filled"></i><i class="icon-base ti tabler-star-filled"></i></div>
          <p>"Sempre comprei carro pra revender pagando perto da tabela. Com o método peguei um Civic pela metade — revendi em 20 dias e tirei R$ 38 mil de lucro líquido."</p>
          <div class="d-flex align-items-center gap-3 mt-3">
            <span class="lp-avatar">JC</span>
            <div><strong class="d-block">Juliano Cardoso</strong><small class="text-muted">Curitiba · PR · Investidor</small></div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="lp-testimonial">
          <span class="saving">3 carros em 6 meses</span>
          <div class="text-warning mb-2"><i class="icon-base ti tabler-star-filled"></i><i class="icon-base ti tabler-star-filled"></i><i class="icon-base ti tabler-star-filled"></i><i class="icon-base ti tabler-star-filled"></i><i class="icon-base ti tabler-star-filled"></i></div>
          <p>"Comecei como hobby, virou renda principal. Os scripts de abordagem fazem toda diferença — o dono cede porque entende que ganha ao evitar o leilão."</p>
          <div class="d-flex align-items-center gap-3 mt-3">
            <span class="lp-avatar">AS</span>
            <div><strong class="d-block">Ana Souza</strong><small class="text-muted">Salvador · BA · Revendedora</small></div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="lp-testimonial">
          <span class="saving">Economizou R$ 41.000</span>
          <div class="text-warning mb-2"><i class="icon-base ti tabler-star-filled"></i><i class="icon-base ti tabler-star-filled"></i><i class="icon-base ti tabler-star-filled"></i><i class="icon-base ti tabler-star-filled"></i><i class="icon-base ti tabler-star-filled"></i></div>
          <p>"Queria uma SUV mas tava cara demais. Pelo método achei um Compass com pendência simples, paguei o limpa nome do dono e fechei tudo regularizado."</p>
          <div class="d-flex align-items-center gap-3 mt-3">
            <span class="lp-avatar">RM</span>
            <div><strong class="d-block">Ricardo Martins</strong><small class="text-muted">São Paulo · SP · Uso próprio</small></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============ FAQ ============ -->
<section id="faq" class="lp-section lp-bg-alt">
  <div class="container" style="max-width: 820px;">
    <div class="text-center mb-5">
      <span class="lp-tag">Perguntas frequentes</span>
      <h2 class="lp-section-title">Suas dúvidas, respondidas</h2>
    </div>

    <div class="accordion lp-faq" id="faqAccordion">
      <div class="accordion-item">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
            Preciso ter CNPJ ou loja pra usar o método?
          </button>
        </h2>
        <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
          <div class="accordion-body">Não. O método funciona pra pessoa física que queira economizar na compra do próprio veículo, montar uma frota ou começar a revender — sem precisar de loja ou CNPJ.</div>
        </div>
      </div>
      <div class="accordion-item">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
            E se o veículo tiver dívida ou pendência?
          </button>
        </h2>
        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
          <div class="accordion-body">É justamente o cenário do método: muitos veículos pré-leilão têm pendência. Ensinamos como avaliar se vale a pena, negociar o débito embutido no preço e usar o Limpa Nome integrado pra destravar a transferência.</div>
        </div>
      </div>
      <div class="accordion-item">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
            É legalizado? Tem risco jurídico?
          </button>
        </h2>
        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
          <div class="accordion-body">Totalmente. A compra é uma operação regular entre proprietário e comprador, com contrato e transferência via Detran. O método cobre exatamente como blindar a operação juridicamente.</div>
        </div>
      </div>
      <div class="accordion-item">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
            Em quanto tempo fecho a primeira compra?
          </button>
        </h2>
        <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
          <div class="accordion-body">A média dos nossos alunos é <strong>15 dias</strong> entre começar o método e fechar a primeira negociação. Depende da sua disponibilidade e do quanto você aplica o conteúdo.</div>
        </div>
      </div>
      <div class="accordion-item">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
            Qual o investimento médio pra começar?
          </button>
        </h2>
        <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
          <div class="accordion-body">Depende do veículo que você quer. O método ensina a achar oportunidades em todas as faixas de preço, de populares até SUVs premium. O importante é o desconto sobre o mercado, não o tíquete.</div>
        </div>
      </div>
      <div class="accordion-item">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq6">
            Posso cancelar a assinatura?
          </button>
        </h2>
        <div id="faq6" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
          <div class="accordion-body">A qualquer momento, sem multa, direto pelo painel. Sem fidelidade, sem letra miúda.</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============ CTA FINAL ============ -->
<section class="lp-section">
  <div class="container">
    <div class="lp-cta">
      <h3>O próximo carro pelo preço de leilão é seu.</h3>
      <p>Em menos de 1 minuto você está dentro. Aulas, mentoria, scripts e ferramentas — tudo pronto pra você fechar o primeiro negócio em 15 dias.</p>
      <div class="d-flex flex-wrap gap-3 justify-content-center">
        <a href="{{ route('register') }}" class="lp-btn lp-btn-white">
          <i class="icon-base ti tabler-target"></i> Quero aprender o método
        </a>
        <a href="{{ route('login') }}" class="lp-btn lp-btn-dark">
          Já tenho conta
        </a>
      </div>
    </div>
  </div>
</section>

<!-- ============ FOOTER ============ -->
<footer class="lp-footer">
  <div class="container">
    <div class="row g-4 mb-4">
      <div class="col-md-4">
        <div class="mb-3">
          <img src="{{ asset('assets/img/branding/logo_transparente.png') }}" alt="{{ config('variables.templateName', 'MetodoCal') }}" class="lp-logo-img">
        </div>
        <p class="small mb-0 lp-footer-desc">O método que ensina a comprar veículos antes do leilão — pagando preço de oportunidade, com segurança jurídica e ferramentas integradas.</p>
      </div>
      <div class="col-md-2 offset-md-2">
        <h6>O método</h6>
        <ul class="list-unstyled small">
          <li class="mb-2"><a href="#oportunidade">A oportunidade</a></li>
          <li class="mb-2"><a href="#metodo">Como funciona</a></li>
          <li class="mb-2"><a href="#recursos">Ferramentas</a></li>
          @if ($planos->count())
            <li class="mb-2"><a href="#planos">Planos</a></li>
          @endif
        </ul>
      </div>
      <div class="col-md-2">
        <h6>Conta</h6>
        <ul class="list-unstyled small">
          <li class="mb-2"><a href="{{ route('login') }}">Entrar</a></li>
          <li class="mb-2"><a href="{{ route('register') }}">Cadastrar</a></li>
          <li class="mb-2"><a href="#faq">FAQ</a></li>
        </ul>
      </div>
      <div class="col-md-2">
        <h6>Legal</h6>
        <ul class="list-unstyled small">
          <li class="mb-2"><a href="#" data-bs-toggle="modal" data-bs-target="#modalTermos">Termos de uso</a></li>
          <li class="mb-2"><a href="#" data-bs-toggle="modal" data-bs-target="#modalLgpd">Privacidade · LGPD</a></li>
        </ul>
      </div>
    </div>
    <hr style="border-color: rgba(255,255,255,.1);">
    <div class="small text-center pt-3" style="color: rgba(255,255,255,.5);">
      © {{ date('Y') }} {{ config('variables.templateName', 'MetodoCal') }}. Todos os direitos reservados.
    </div>
  </div>
</footer>

@include('_partials._legal-modals')

@vite(['resources/assets/vendor/libs/popper/popper.js', 'resources/assets/vendor/js/bootstrap.js'])

<script>
document.addEventListener('DOMContentLoaded', function () {
  const filter = document.getElementById('filter-recorrencia');
  if (! filter) return;
  const items = document.querySelectorAll('.plano-item');

  filter.addEventListener('click', function (e) {
    const btn = e.target.closest('button[data-recorrencia]');
    if (! btn) return;
    filter.querySelectorAll('button').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    const rec = btn.dataset.recorrencia;
    items.forEach(item => {
      item.style.display = item.dataset.recorrencia === rec ? '' : 'none';
    });
  });
});
</script>
</body>
</html>
