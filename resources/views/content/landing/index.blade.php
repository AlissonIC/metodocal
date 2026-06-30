<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>{{ config('variables.templateName', 'MetodoCal') }} · Método CAL · Comprando antes do leilão</title>
  <meta name="description" content="O método para comprar veículos com até 70% de desconto assumindo a dívida antes da retomada do banco e transformar isso em renda recorrente e patrimônio. Curso e sociedade estratégica.">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <link rel="icon" type="image/x-icon" href="{{ asset('favicon-v2.ico') }}">
  <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('assets/img/favicon/favicon-v2-16x16.png') }}">
  <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/img/favicon/favicon-v2-32x32.png') }}">
  <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/img/favicon/apple-touch-icon-v2.png') }}">
  <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('assets/img/favicon/android-chrome-v2-192x192.png') }}">
  <link rel="icon" type="image/png" sizes="512x512" href="{{ asset('assets/img/favicon/android-chrome-v2-512x512.png') }}">

  @vite([
    'resources/assets/vendor/fonts/iconify/iconify.css',
    'resources/assets/vendor/scss/core.scss',
    'resources/css/app.css',
  ])

  <style>
    :root {
      --md-1: #B8860B;
      --md-2: #D4AF37;
      --md-3: #11141a;
      --md-grad: linear-gradient(135deg, #B8860B 0%, #D4AF37 100%);
      --md-grad-soft: linear-gradient(135deg, rgba(184,134,11,.08) 0%, rgba(212,175,55,.08) 100%);
      --md-grad-dark: linear-gradient(135deg, #1a1408 0%, #11141a 100%);
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
    .lp-btn-primary { background: var(--md-grad); color: #fff; box-shadow: 0 10px 30px -10px rgba(184,134,11,.55); }
    .lp-btn-primary:hover { color: #fff; transform: translateY(-2px); box-shadow: 0 14px 34px -10px rgba(184,134,11,.7); }
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
      background: #fdfbf4;
    }
    .lp-hero::before {
      content: ''; position: absolute; inset: 0;
      background:
        radial-gradient(900px circle at 8% 5%, rgba(212,175,55,.22), transparent 55%),
        radial-gradient(800px circle at 95% 25%, rgba(184,134,11,.20), transparent 60%),
        radial-gradient(600px circle at 50% 100%, rgba(184,134,11,.10), transparent 50%);
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
      font-size: clamp(1.95rem, 3.4vw, 3rem);
      font-weight: 800; line-height: 1.15; letter-spacing: -0.025em;
      color: #11141a; margin: 1rem 0 1.25rem;
      max-width: 560px;
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
    .lp-sub { font-size: 1.05rem; color: #5b6478; max-width: 540px; line-height: 1.6; }

    .lp-hero-trust { display: flex; flex-wrap: wrap; gap: 1.5rem; align-items: center; margin-top: 2rem; color: #5b6478; font-size: .9rem; }
    .lp-hero-trust .check { color: #16a34a; }

    /* Hero visual: comparativo */
    .lp-compare {
      background: #fff; border-radius: 1.5rem; padding: 1.75rem;
      box-shadow: 0 30px 60px -25px rgba(60,40,0,.2);
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
    .lp-compare-row .lbl { display: flex; align-items: center; gap: .65rem; font-weight: 600; color: #11141a; }
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
    .lp-trust { background: #fdfbf4; padding: 2.5rem 0; border-top: 1px solid rgba(0,0,0,.04); }
    .lp-stat-num { font-size: 2.6rem; font-weight: 800;
      background: var(--md-grad);
      -webkit-background-clip: text; background-clip: text;
      -webkit-text-fill-color: transparent; line-height: 1;
    }
    .lp-stat-lbl { color: #5b6478; font-size: .95rem; margin-top: .4rem; font-weight: 500; }

    /* ===== SECTIONS ===== */
    section { scroll-margin-top: 100px; }
    .lp-section { padding: 6rem 0; }
    .lp-section-title { font-size: clamp(1.85rem, 3.8vw, 2.8rem); font-weight: 800; letter-spacing: -0.025em; color: #11141a; }
    .lp-section-sub { color: #5b6478; font-size: 1.1rem; max-width: 680px; margin: 0 auto; line-height: 1.6; }
    .lp-tag {
      display: inline-block; padding: .4rem 1rem; border-radius: 999px;
      background: var(--md-grad-soft); color: var(--md-1);
      font-size: .8rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase;
      margin-bottom: 1.2rem;
    }
    .lp-bg-alt { background: #fdfbf4; }
    .lp-bg-dark { background: var(--md-grad-dark); color: rgba(255,255,255,.85); position: relative; overflow: hidden; }
    .lp-bg-dark::before {
      content: ''; position: absolute; inset: 0;
      background:
        radial-gradient(700px circle at 10% 50%, rgba(212,175,55,.15), transparent 50%),
        radial-gradient(700px circle at 90% 50%, rgba(184,134,11,.12), transparent 50%);
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
    .lp-pain h5 { font-weight: 700; color: #11141a; margin-bottom: .5rem; }
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
    .lp-step-card:hover { background: rgba(255,255,255,.08); transform: translateY(-3px); border-color: rgba(212,175,55,.4); }
    .lp-step-num {
      display: inline-block;
      width: 52px; height: 52px; border-radius: 14px;
      background: var(--md-grad);
      color: #fff; font-weight: 800; font-size: 1.3rem;
      line-height: 52px; text-align: center;
      margin-bottom: 1rem;
      box-shadow: 0 10px 25px -8px rgba(212,175,55,.5);
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
    .lp-feature:hover { transform: translateY(-5px); border-color: rgba(184,134,11,.25); box-shadow: 0 25px 50px -25px rgba(184,134,11,.25); }
    .lp-feature:hover::before { opacity: 1; }
    .lp-feature-head { display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem; }
    .lp-feature-icon {
      width: 52px; height: 52px; border-radius: 13px;
      display: inline-flex; align-items: center; justify-content: center;
      background: var(--md-grad-soft); color: var(--md-1); font-size: 1.45rem;
      flex-shrink: 0;
    }
    .lp-feature h5 { font-weight: 700; color: #11141a; margin: 0; font-size: 1.1rem; line-height: 1.3; }
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
    .lp-compare-side.good { border: 2px solid var(--md-1); box-shadow: 0 25px 50px -25px rgba(184,134,11,.3); position: relative; }
    .lp-compare-side.good::before {
      content: 'Com o Método'; position: absolute; top: -14px; left: 2rem;
      background: var(--md-grad); color: #fff;
      padding: .25rem .85rem; border-radius: 999px;
      font-size: .75rem; font-weight: 700; letter-spacing: .04em;
    }
    .lp-compare-side h4 { font-weight: 800; color: #11141a; margin-bottom: 1.25rem; }
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
    .lp-plan:hover { transform: translateY(-5px); box-shadow: 0 25px 50px -25px rgba(184,134,11,.2); }
    .lp-plan-featured { border: 2px solid var(--md-1); transform: scale(1.03); box-shadow: 0 30px 60px -25px rgba(184,134,11,.3); }
    .lp-plan-featured::before {
      content: 'Mais escolhido'; position: absolute; top: -14px; left: 50%; transform: translateX(-50%);
      background: var(--md-grad); color: #fff;
      padding: .3rem .9rem; border-radius: 999px;
      font-size: .75rem; font-weight: 700; letter-spacing: .04em;
    }
    .lp-plan h4 { font-weight: 700; color: #11141a; }
    .lp-plan-price { font-size: 2.8rem; font-weight: 800; color: #11141a; line-height: 1; }
    .lp-plan-price .currency { font-size: 1.1rem; vertical-align: top; color: #5b6478; font-weight: 600; }
    .lp-plan-price .period { font-size: .9rem; color: #5b6478; font-weight: 500; }

    /* ===== TESTIMONIAL ===== */
    .lp-testimonial {
      background: #fff; border-radius: 1.2rem; padding: 1.85rem;
      border: 1px solid rgba(0,0,0,.06); height: 100%;
      transition: all .3s ease;
    }
    .lp-testimonial:hover { transform: translateY(-3px); box-shadow: 0 20px 40px -20px rgba(60,40,0,.12); }
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
    .lp-faq .accordion-button { font-weight: 600; color: #11141a; padding: 1.25rem 1.35rem; background: #fff; }
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
      .lp-hero .lp-h1,
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

      /* Hero - tipografia contida + respiro entre blocos */
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

      /* Card comparativo do hero - header em coluna, badge oculto, valores menores */
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
      <li class="nav-item"><a class="nav-link" href="#monetizar">Como lucra</a></li>
      <li class="nav-item"><a class="nav-link" href="#recursos">Plataforma</a></li>
      @if ($planos->count())
        <li class="nav-item"><a class="nav-link" href="#planos">Caminhos</a></li>
      @endif
      <li class="nav-item"><a class="nav-link" href="#faq">FAQ</a></li>
    </ul>

    <div class="d-flex gap-2 align-items-center">
      @auth
        <a href="{{ route('dashboard') }}" class="lp-btn lp-btn-ghost">Painel</a>
      @else
        <a href="{{ route('login') }}" class="lp-btn lp-btn-ghost d-none d-sm-inline-flex">Entrar</a>
        <a href="{{ route('register') }}" class="lp-btn lp-btn-primary">Quero operar</a>
      @endauth
    </div>
  </div>
</nav>

<!-- ============ HERO ============ -->
<header class="lp-hero">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-6">
        <span class="lp-eyebrow"><span class="dot"></span> Método CAL · Comprando antes do leilão</span>
        <h1 class="lp-h1">
          O método para comprar veículos com <span class="grad">até 70% de desconto</span> e transformar em renda e patrimônio.
        </h1>
        <p class="lp-sub">
          Você não compra carro. Você <strong>assume a dívida</strong> de um veículo financiado antes que o banco retome, e depois <strong>monetiza por venda, financiamento próprio ou locação</strong>. Operação validada em <strong>+9 anos</strong> de mercado real.
        </p>

        <div class="d-flex flex-wrap gap-3 mt-4">
          <a href="{{ route('register') }}" class="lp-btn lp-btn-primary">
            <i class="icon-base ti tabler-target"></i> Quero operar
          </a>
          <a href="#metodo" class="lp-btn lp-btn-ghost">
            <i class="icon-base ti tabler-player-play"></i> Ver como funciona
          </a>
        </div>

        <div class="lp-hero-trust">
          <span><i class="icon-base ti tabler-shield-check check"></i> Contrato + Procuração Pública</span>
          <span><i class="icon-base ti tabler-scale check"></i> Limpa Nome em até 10 dias</span>
          <span><i class="icon-base ti tabler-coin check"></i> 1ª operação em ~30 dias</span>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="lp-compare">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0 fw-bold" style="color:#11141a;">Exemplo real · Veículo FIPE R$ 100.000</h6>
            <span class="badge bg-label-secondary">Operação</span>
          </div>

          <div class="lp-compare-row bad">
            <span class="lbl">
              <span class="lp-compare-icon bad"><i class="icon-base ti tabler-shopping-cart"></i></span>
              Comprar no mercado
            </span>
            <span class="val">R$ 95.000</span>
          </div>

          <div class="lp-compare-row good">
            <span class="lbl">
              <span class="lp-compare-icon good"><i class="icon-base ti tabler-target-arrow"></i></span>
              Assumir a dívida (Método)
            </span>
            <span class="val">R$ 20.000</span>
          </div>

          <div class="lp-compare-row good" style="margin-top: .35rem;">
            <span class="lbl">
              <span class="lp-compare-icon good"><i class="icon-base ti tabler-cash-banknote"></i></span>
              Revenda · entrada de 30%
            </span>
            <span class="val">R$ 50.000</span>
          </div>

          <div class="lp-compare-diff">
            <span class="lbl"><i class="icon-base ti tabler-trending-up me-1"></i> Lucro só na entrada</span>
            <span class="val">+R$ 30.000</span>
          </div>
        </div>
        <p class="text-muted small mt-2 mb-0 text-center">+ até 60 parcelas mensais de recorrência no financiamento próprio.</p>
      </div>
    </div>
  </div>
</header>

<!-- ============ TRUST STRIP ============ -->
<section class="lp-trust">
  <div class="container">
    <div class="row text-center g-4">
      <div class="col-6 col-md-3"><div class="lp-stat-num">70%</div><div class="lp-stat-lbl">Desconto sobre o valor de mercado</div></div>
      <div class="col-6 col-md-3"><div class="lp-stat-num">90%</div><div class="lp-stat-lbl">Desconto possível no saldo devedor</div></div>
      <div class="col-6 col-md-3"><div class="lp-stat-num">+9 anos</div><div class="lp-stat-lbl">De operações reais no mercado</div></div>
      <div class="col-6 col-md-3"><div class="lp-stat-num">3 frentes</div><div class="lp-stat-lbl">De monetização do mesmo ativo</div></div>
    </div>
  </div>
</section>

<!-- ============ OPORTUNIDADE ============ -->
<section id="oportunidade" class="lp-section">
  <div class="container">
    <div class="text-center mb-5">
      <span class="lp-tag" style="background: rgba(212,175,55,.18); color: #B8860B;">A oportunidade antes da disputa</span>
      <h2 class="lp-section-title">Enquanto o mercado disputa preço, você cria margem</h2>
      <p class="lp-section-sub mt-3">Existe um mercado <strong>antes do leilão</strong> que ninguém vê: proprietários com financiamento atrasado, prestes a perder o carro pro banco. Em vez de perder tudo, eles cedem o veículo a quem assume a dívida. É aí que o método entra.</p>
    </div>

    <div class="row g-4">
      <div class="col-md-6 col-lg-3">
        <div class="lp-pain">
          <div class="lp-pain-icon"><i class="icon-base ti tabler-message-circle"></i></div>
          <h5>OLX e Marketplace</h5>
          <p>Proprietários anunciando o veículo com <strong>parcelas atrasadas</strong>, precisando vender rápido. Você chega antes da retomada.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="lp-pain">
          <div class="lp-pain-icon"><i class="icon-base ti tabler-gavel"></i></div>
          <h5>Tribunais de Justiça</h5>
          <p>Processos de <strong>busca e apreensão</strong> são públicos. Identificamos veículos prestes a ser retomados e abordamos o dono antes do fim.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="lp-pain">
          <div class="lp-pain-icon"><i class="icon-base ti tabler-target"></i></div>
          <h5>Tráfego pago direcionado</h5>
          <p>Anúncios para <strong>"está com parcelas atrasadas?"</strong>. Você atrai o problema, oferece a solução, compra com margem.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="lp-pain">
          <div class="lp-pain-icon"><i class="icon-base ti tabler-handshake"></i></div>
          <h5>Cliente sai ganhando</h5>
          <p>Sem o método, ele perde o carro <strong>e fica com a dívida no CPF</strong>. Com você, ele sai do problema com nome limpo em até 10 dias.</p>
        </div>
      </div>
    </div>

    <div class="text-center mt-5">
      <p class="text-muted mb-0" style="font-size: 1.05rem;">
        <i class="icon-base ti tabler-quote text-warning"></i>
        <em>"Você não compra carro. Compra <strong>oportunidade com margem embutida</strong>."</em>
      </p>
    </div>
  </div>
</section>

<!-- ============ MÉTODO ============ -->
<section id="metodo" class="lp-section lp-bg-dark">
  <div class="container">
    <div class="text-center mb-5">
      <span class="lp-tag" style="background: rgba(212,175,55,.18); color: #B8860B;">O método em 4 etapas</span>
      <h2 class="lp-section-title">Do primeiro contato à operação lucrativa</h2>
      <p class="lp-section-sub mt-3">Um processo replicável, com scripts, planilhas e documentação jurídica, testado em centenas de operações reais.</p>
    </div>

    <div class="row g-4 lp-method-grid">
      <div class="col-md-6 col-lg-3">
        <div class="lp-step-card">
          <div class="lp-step-num">1</div>
          <h5>Captação</h5>
          <p>Encontre veículos pré-leilão em <strong>OLX, marketplaces, tribunais e tráfego pago</strong>. Você atrai quem já está com problema. Não tem disputa.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="lp-step-card">
          <div class="lp-step-num">2</div>
          <h5>Negociação</h5>
          <p>Scripts de <strong>abordagem, diagnóstico e fechamento</strong>. Você não vende, posiciona como solução. Planilha inteligente calcula o teto da operação.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="lp-step-card">
          <div class="lp-step-num">3</div>
          <h5>Formalização</h5>
          <p><strong>Contrato</strong> + <strong>Laudo Cautelar</strong> + <strong>Procuração Pública</strong> de amplos poderes. O banco deixa de falar com o cliente e passa a tratar direto com você.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="lp-step-card">
          <div class="lp-step-num">4</div>
          <h5>Monetização</h5>
          <p>Quitação com desconto de <strong>até 90%</strong> e três frentes de receita: <strong>revenda, financiamento próprio ou locação</strong>. O mesmo ativo, três fontes de caixa.</p>
        </div>
      </div>
    </div>

    <div class="text-center mt-5">
      <a href="#monetizar" class="lp-btn lp-btn-primary">
        Ver as 3 formas de monetizar <i class="icon-base ti tabler-arrow-down"></i>
      </a>
    </div>
  </div>
</section>

<!-- ============ 3 FORMAS DE MONETIZAR ============ -->
<section id="monetizar" class="lp-section lp-bg-alt">
  <div class="container">
    <div class="text-center mb-5">
      <span class="lp-tag">3 frentes de monetização</span>
      <h2 class="lp-section-title">Um ativo. Três formas de virar dinheiro.</h2>
      <p class="lp-section-sub mt-3">Você compra a dívida entre <strong>10% e 30% da FIPE</strong>. A partir daí, escolhe qual modelo aplica, ou combina os três.</p>
    </div>

    <div class="row g-4">
      <div class="col-lg-4">
        <div class="lp-feature h-100">
          <span class="pill" style="background: rgba(212,175,55,.18); color: var(--md-1);">#1 · Recorrência</span>
          <div class="lp-feature-head">
            <div class="lp-feature-icon"><i class="icon-base ti tabler-cash-banknote"></i></div>
            <h5>Financiamento próprio</h5>
          </div>
          <p class="mb-3">Você vende com <strong>30% de entrada</strong> e parcela em <strong>até 60x no boleto</strong> (atendendo inclusive negativados, juros ~3,99% a.m.).</p>
          <ul class="list-unstyled small mb-0" style="line-height: 1.9;">
            <li><i class="icon-base ti tabler-check text-success me-2"></i> Recupera o capital só na entrada</li>
            <li><i class="icon-base ti tabler-check text-success me-2"></i> 5 anos de receita recorrente por carro</li>
            <li><i class="icon-base ti tabler-check text-success me-2"></i> Carteira que escala com o tempo</li>
          </ul>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="lp-feature h-100">
          <span class="pill" style="background: rgba(22,163,74,.12); color: #16a34a;">#2 · Lucro rápido</span>
          <div class="lp-feature-head">
            <div class="lp-feature-icon"><i class="icon-base ti tabler-coin"></i></div>
            <h5>Quitação futura</h5>
          </div>
          <p class="mb-3">Vende o veículo por <strong>50% da FIPE</strong> com o comprador assumindo o saldo a quitar. Caixa rápido pra reinvestir.</p>
          <ul class="list-unstyled small mb-0" style="line-height: 1.9;">
            <li><i class="icon-base ti tabler-check text-success me-2"></i> Ex.: FIPE 100k → comprou 20k</li>
            <li><i class="icon-base ti tabler-check text-success me-2"></i> Vende 50k, comprador quita 25k</li>
            <li><i class="icon-base ti tabler-check text-success me-2"></i> <strong>+R$ 30 mil</strong> em uma operação</li>
          </ul>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="lp-feature h-100">
          <span class="pill" style="background: rgba(184,134,11,.15); color: var(--md-1);">#3 · Renda passiva</span>
          <div class="lp-feature-head">
            <div class="lp-feature-icon"><i class="icon-base ti tabler-key"></i></div>
            <h5>Locação</h5>
          </div>
          <p class="mb-3">Aluga o veículo antes da venda. Gera receita semanal ou mensal enquanto o ativo está estacionado.</p>
          <ul class="list-unstyled small mb-0" style="line-height: 1.9;">
            <li><i class="icon-base ti tabler-check text-success me-2"></i> Fluxo de caixa imediato</li>
            <li><i class="icon-base ti tabler-check text-success me-2"></i> Reduz custo de carregar o ativo</li>
            <li><i class="icon-base ti tabler-check text-success me-2"></i> Maximiza ROI antes da revenda</li>
          </ul>
        </div>
      </div>
    </div>

    <div class="text-center mt-5">
      <p class="mb-0" style="font-size: 1.1rem;">
        Aquisição com margem → Proteção do ativo → Monetização → Lucro → <strong>Reinvestimento</strong> → Escala.
      </p>
    </div>
  </div>
</section>

<!-- ============ RECURSOS / FERRAMENTAS ============ -->
<section id="recursos" class="lp-section">
  <div class="container">
    <div class="text-center mb-5">
      <span class="lp-tag">A plataforma</span>
      <h2 class="lp-section-title">Tudo que você precisa pra operar, em um só lugar</h2>
      <p class="lp-section-sub mt-3">Conteúdo, mentoria, scripts, documentos jurídicos e parceiros operacionais. Você não precisa montar nada do zero.</p>
    </div>

    <div class="row g-4">
      <div class="col-md-6 col-lg-4">
        <div class="lp-feature">
          <span class="pill">Núcleo</span>
          <div class="lp-feature-head">
            <div class="lp-feature-icon"><i class="icon-base ti tabler-book"></i></div>
            <h5>Trilha Método CAL</h5>
          </div>
          <p>Aulas em vídeo cobrindo aquisição, negociação, formalização, gestão e as 3 formas de monetizar, do zero ao avançado.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-4">
        <div class="lp-feature">
          <span class="pill">Núcleo</span>
          <div class="lp-feature-head">
            <div class="lp-feature-icon"><i class="icon-base ti tabler-calendar"></i></div>
            <h5>Mentoria contínua</h5>
          </div>
          <p>Grupo de suporte no WhatsApp, <strong>encontros quinzenais por Zoom</strong> e encontro presencial no escritório pra esclarecimento de dúvidas.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-4">
        <div class="lp-feature">
          <span class="pill">Núcleo</span>
          <div class="lp-feature-head">
            <div class="lp-feature-icon"><i class="icon-base ti tabler-files"></i></div>
            <h5>Scripts e contratos</h5>
          </div>
          <p>Scripts de abordagem e quebra de objeção, contrato de compra, modelo de procuração e <strong>planilha inteligente</strong> de teto da operação.</p>
        </div>
      </div>

      <div class="col-md-6 col-lg-4">
        <div class="lp-feature">
          <span class="pill" style="background: rgba(212,175,55,.12); color: var(--md-1);">Integrado</span>
          <div class="lp-feature-head">
            <div class="lp-feature-icon"><i class="icon-base ti tabler-shield-check"></i></div>
            <h5>Limpa Nome em 10 dias</h5>
          </div>
          <p>Liminar para limpar todas as restrições do CPF do cliente em até 10 dias. Destrava a negociação e protege quem vendeu o carro.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-4">
        <div class="lp-feature">
          <span class="pill" style="background: rgba(212,175,55,.12); color: var(--md-1);">Integrado</span>
          <div class="lp-feature-head">
            <div class="lp-feature-icon"><i class="icon-base ti tabler-building-bank"></i></div>
            <h5>Análise de bancos</h5>
          </div>
          <p>Catálogo dos bancos com <strong>taxa real de desconto por instituição</strong>. Você sabe antes de comprar se a dívida vai render.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-4">
        <div class="lp-feature">
          <span class="pill" style="background: rgba(212,175,55,.12); color: var(--md-1);">Integrado</span>
          <div class="lp-feature-head">
            <div class="lp-feature-icon"><i class="icon-base ti tabler-truck"></i></div>
            <h5>Rede de guincho</h5>
          </div>
          <p>Parceiros em todo o Brasil pra remover o veículo logo após o fechamento, protegendo o ativo de bloqueios ou retomada relâmpago.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============ BANCOS ============ -->
<section class="lp-section lp-bg-alt">
  <div class="container" style="max-width: 1100px;">
    <div class="text-center mb-5">
      <span class="lp-tag">Análise estratégica</span>
      <h2 class="lp-section-title">O banco define seu lucro</h2>
      <p class="lp-section-sub mt-3">Em 9 anos de operação, descobrimos: <strong>o lucro está na negociação da dívida, não só no carro</strong>. Cada banco se comporta de um jeito, e isso muda a viabilidade da operação.</p>
    </div>

    <div class="row g-4 justify-content-center">
      <div class="col-md-6">
        <div class="lp-compare-side good">
          <h4><i class="icon-base ti tabler-thumb-up text-success me-2"></i> Bancos favoráveis à operação</h4>
          <p class="text-muted small mb-3">Maior volume, mais flexibilidade, descontos de até 90% no saldo devedor.</p>
          <ul>
            <li><i class="icon-base ti tabler-check"></i> PAN, Santander, Aymoré</li>
            <li><i class="icon-base ti tabler-check"></i> Itaú, Bradesco</li>
            <li><i class="icon-base ti tabler-check"></i> Digimais, BV</li>
            <li><i class="icon-base ti tabler-check"></i> Daycoval, Safra</li>
          </ul>
        </div>
      </div>
      <div class="col-md-6">
        <div class="lp-compare-side bad">
          <h4><i class="icon-base ti tabler-alert-octagon text-danger me-2"></i> Bancos rígidos (evitar ou precificar)</h4>
          <p class="text-muted small mb-3">Política dura, menos margem, processo mais demorado. Só entram se o desconto inicial compensar.</p>
          <ul>
            <li><i class="icon-base ti tabler-x"></i> Omni, Porto Seguro</li>
            <li><i class="icon-base ti tabler-x"></i> Volvo, Toyota, Honda</li>
            <li><i class="icon-base ti tabler-x"></i> C6, Creditas</li>
            <li><i class="icon-base ti tabler-x"></i> Hyundai, Yamaha, GM</li>
          </ul>
        </div>
      </div>
    </div>

    <div class="text-center mt-5">
      <p class="mb-0" style="font-size: 1.05rem;">
        <i class="icon-base ti tabler-quote text-warning"></i>
        <em>"Quem não analisa o banco, assume risco. Quem entende o banco, <strong>controla o lucro</strong>."</em>
      </p>
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
<section id="planos" class="lp-section">
  <div class="container">
    <div class="text-center mb-5">
      <span class="lp-tag">Dois caminhos</span>
      <h2 class="lp-section-title">Comece pelo curso ou entre direto na sociedade</h2>
      <p class="lp-section-sub mt-3"><strong>Formação do Operador</strong> para quem quer dominar o método e operar por conta. <strong>Sociedade Estratégica</strong> para quem quer entrar já com estrutura, suporte direto e operação rodando.</p>
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
<section class="lp-section lp-bg-alt">
  <div class="container">
    <div class="text-center mb-5">
      <span class="lp-tag">Histórias reais</span>
      <h2 class="lp-section-title">Operações que aconteceram com o método</h2>
    </div>

    <div class="row g-4">
      <div class="col-md-4">
        <div class="lp-testimonial">
          <span class="saving">FIPE 95k · Comprou 22k · Lucro 38k</span>
          <div class="text-warning mb-2"><i class="icon-base ti tabler-star-filled"></i><i class="icon-base ti tabler-star-filled"></i><i class="icon-base ti tabler-star-filled"></i><i class="icon-base ti tabler-star-filled"></i><i class="icon-base ti tabler-star-filled"></i></div>
          <p>"Civic de um cliente Santander prestes a ter o veículo retomado. Assumi a dívida, negociei com o banco e revendi por 60 mil em 20 dias. R$ 38 mil líquidos na operação."</p>
          <div class="d-flex align-items-center gap-3 mt-3">
            <span class="lp-avatar">JC</span>
            <div><strong class="d-block">Juliano Cardoso</strong><small class="text-muted">Curitiba · PR · Operador</small></div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="lp-testimonial">
          <span class="saving">3 operações em 6 meses · Frota própria</span>
          <div class="text-warning mb-2"><i class="icon-base ti tabler-star-filled"></i><i class="icon-base ti tabler-star-filled"></i><i class="icon-base ti tabler-star-filled"></i><i class="icon-base ti tabler-star-filled"></i><i class="icon-base ti tabler-star-filled"></i></div>
          <p>"Os scripts de quebra de objeção mudam o jogo. Quando o cliente entende que vai sair sem dívida e com nome limpo, ele cede. Hoje tenho 3 carros alugados gerando recorrência."</p>
          <div class="d-flex align-items-center gap-3 mt-3">
            <span class="lp-avatar">AS</span>
            <div><strong class="d-block">Ana Souza</strong><small class="text-muted">Salvador · BA · Sociedade</small></div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="lp-testimonial">
          <span class="saving">Compass · 12 boletos vendidos · Recorrência</span>
          <div class="text-warning mb-2"><i class="icon-base ti tabler-star-filled"></i><i class="icon-base ti tabler-star-filled"></i><i class="icon-base ti tabler-star-filled"></i><i class="icon-base ti tabler-star-filled"></i><i class="icon-base ti tabler-star-filled"></i></div>
          <p>"Vendi por financiamento próprio: 30% de entrada (já cobriu o que paguei) e 60 boletos de R$ 1.450. É renda passiva por 5 anos, só com um carro. Já estou montando o segundo."</p>
          <div class="d-flex align-items-center gap-3 mt-3">
            <span class="lp-avatar">RM</span>
            <div><strong class="d-block">Ricardo Martins</strong><small class="text-muted">São Paulo · SP · Operador</small></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============ FAQ ============ -->
<section id="faq" class="lp-section">
  <div class="container" style="max-width: 820px;">
    <div class="text-center mb-5">
      <span class="lp-tag">Perguntas frequentes</span>
      <h2 class="lp-section-title">Tudo que você precisa entender antes de operar</h2>
    </div>

    <div class="accordion lp-faq" id="faqAccordion">
      <div class="accordion-item">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
            Como assim "assumir a dívida" em vez de comprar o carro?
          </button>
        </h2>
        <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
          <div class="accordion-body">O proprietário com financiamento atrasado está prestes a perder o veículo via busca e apreensão. Você paga a ele uma quantia (10% a 30% da FIPE) para assumir a posse do carro e a negociação da dívida com o banco. Tudo formalizado por <strong>contrato + procuração pública de amplos poderes</strong>. A partir daí, você fala com o banco e o cliente sai do problema.</div>
        </div>
      </div>
      <div class="accordion-item">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
            Quanto tempo até o veículo ser totalmente meu?
          </button>
        </h2>
        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
          <div class="accordion-body">Mínimo de <strong>3 meses</strong>, podendo chegar a 24 meses dependendo do banco e da estratégia escolhida. A procuração pública permite que você opere e monetize o ativo já desde o primeiro dia: alugando, vendendo via financiamento próprio ou estruturando quitação futura, sem precisar esperar a transferência final.</div>
        </div>
      </div>
      <div class="accordion-item">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
            E o nome do cliente que vendeu? Fica sujo?
          </button>
        </h2>
        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
          <div class="accordion-body">Não. Em até <strong>10 dias</strong>, conseguimos limpar todas as restrições do CPF do cliente por meio de uma liminar. Esse é um dos pilares que faz o cliente aceitar a operação. A plataforma tem o módulo <strong>Limpa Nome</strong> integrado pra rodar isso pra cada caso.</div>
        </div>
      </div>
      <div class="accordion-item">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
            Por que o banco aceita até 90% de desconto na dívida?
          </button>
        </h2>
        <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
          <div class="accordion-body">Porque o banco prefere recuperar parte do valor a perder tudo. Com o tempo, o contrato entra em fase de recuperação de crédito, o custo de cobrança sobe e a inadimplência vira oportunidade de negociação. Quem sabe abordar (e tem procuração em mãos), <strong>transforma dívida em lucro</strong>.</div>
        </div>
      </div>
      <div class="accordion-item">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
            É legalizado? Tem risco jurídico?
          </button>
        </h2>
        <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
          <div class="accordion-body">Sim, totalmente. Toda operação roda em cima de três pilares jurídicos: <strong>contrato registrado</strong> (define direitos e deveres das partes), <strong>laudo cautelar</strong> (valida originalidade e procedência do veículo) e <strong>procuração pública de amplos poderes</strong> (te autoriza a negociar com o banco). Sem esses três, a operação não avança.</div>
        </div>
      </div>
      <div class="accordion-item">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq6">
            Preciso de capital alto para começar?
          </button>
        </h2>
        <div id="faq6" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
          <div class="accordion-body">Não. O método ensina a operar com tíquetes variados, de populares a premium. Como o preço de entrada é uma fração da FIPE (10% a 30%), o capital inicial pode ser bem menor do que o de uma compra tradicional. Além disso, o financiamento próprio devolve o capital já na entrada da revenda.</div>
        </div>
      </div>
      <div class="accordion-item">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq7">
            Qual a diferença entre o curso e a sociedade?
          </button>
        </h2>
        <div id="faq7" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
          <div class="accordion-body"><strong>Formação do Operador (curso):</strong> você aprende o método completo, recebe scripts e contratos, participa de mentoria em grupo (WhatsApp + Zoom quinzenal + presencial) e opera por conta própria. <strong>Sociedade Estratégica:</strong> você entra em uma unidade ou escritório com operação já rodando, suporte direto, know-how aplicado e participa do negócio. Caminho para escala mais acelerada.</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============ CTA FINAL ============ -->
<section class="lp-section lp-bg-alt">
  <div class="container">
    <div class="lp-cta">
      <h3>Quem entra antes, lucra mais.</h3>
      <p>Enquanto muitos ainda disputam carro em leilão, existem operadores entrando antes, comprando melhor, negociando melhor e lucrando muito mais. A única pergunta é: <strong>você vai assistir ou vai fazer parte?</strong></p>
      <div class="d-flex flex-wrap gap-3 justify-content-center">
        <a href="{{ route('register') }}" class="lp-btn lp-btn-white">
          <i class="icon-base ti tabler-target"></i> Quero operar
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
        <p class="small mb-0 lp-footer-desc">Método CAL · Comprando antes do leilão. Compra com margem, formalização jurídica e três frentes de monetização, em uma única plataforma de operação.</p>
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
