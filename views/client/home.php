<?php
/**
 * views/client/home.php
 * Landing Page de Alta Costura + Modal de Agendamento com Passo Zero.
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Servico.php';
require_once __DIR__ . '/../../models/Profissional.php';
require_once __DIR__ . '/../../controllers/AutenticacaoController.php';

$servicos      = Servico::listarAtivos();
$profissionais = Profissional::listarTodos();
$clienteLogado = AutenticacaoController::estaLogado();
$clienteNome   = $_SESSION['user_nome'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars(LOJA_NOME) ?> · Agendamento Online</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            rosa:    { DEFAULT: '#e91e8c', light: '#f472b6', dark: '#c2186e' },
            magenta: { DEFAULT: '#c2186e', dark: '#9c1256' },
            creme:   '#fdf6f0',
          },
          fontFamily: {
            display: ['"Playfair Display"', 'serif'],
            body:    ['"DM Sans"', 'sans-serif'],
          },
        }
      }
    }
  </script>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet"/>

  <style>
    /* ════════════════════════════════════════
       VARIÁVEIS E BASE
    ════════════════════════════════════════ */
    :root {
      --rosa:    #e91e8c;
      --magenta: #c2186e;
      --creme:   #fdf6f0;
      --gold:    #c9a84c;
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html { scroll-behavior: smooth; }
    body {
      font-family: 'DM Sans', sans-serif;
      background: var(--creme);
      color: #2d1f28;
      min-height: 100vh;
      overflow-x: hidden;
    }

    /* ════════════════════════════════════════
       NAVBAR
    ════════════════════════════════════════ */
    .navbar {
      background: rgba(253,246,240,0.92);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      border-bottom: 1px solid rgba(201,168,76,0.15);
    }

    /* ════════════════════════════════════════
       BLOBS DE FUNDO
    ════════════════════════════════════════ */
    .blob {
      position: fixed; border-radius: 50%;
      filter: blur(100px); opacity: 0.10;
      pointer-events: none; z-index: 0;
    }
    .blob-1 { width:600px; height:600px; background:var(--rosa);    top:-150px; right:-120px; }
    .blob-2 { width:400px; height:400px; background:var(--magenta); bottom:-50px; left:-100px; }
    .blob-3 { width:280px; height:280px; background:#f9a8d4;        top:40%; right:15%; }

    /* ════════════════════════════════════════
       HERO
    ════════════════════════════════════════ */
    .hero { position: relative; z-index: 1; }

    .gradient-text {
      background: linear-gradient(135deg, var(--rosa), var(--magenta));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    .gold-text { color: var(--gold); }

    /* Divisor dourado decorativo */
    .divider-gold {
      display: flex; align-items: center; gap: 12px;
      margin: 18px 0;
    }
    .divider-gold::before, .divider-gold::after {
      content: ''; flex: 1;
      height: 1px;
      background: linear-gradient(to right, transparent, var(--gold), transparent);
    }
    .divider-gold span { color: var(--gold); font-size: 0.7rem; letter-spacing: 0.25em; text-transform: uppercase; white-space: nowrap; }

    /* ════════════════════════════════════════
       BOTÕES HERO
    ════════════════════════════════════════ */
    .btn-hero {
      display: inline-flex; align-items: center; gap: 10px;
      background: linear-gradient(135deg, var(--rosa), var(--magenta));
      color: white; font-family: 'DM Sans', sans-serif;
      font-weight: 600; font-size: 1rem;
      padding: 16px 40px; border-radius: 50px; border: none;
      cursor: pointer; text-decoration: none;
      box-shadow: 0 10px 32px rgba(233,30,140,0.38);
      transition: all 0.3s ease;
      letter-spacing: 0.02em;
    }
    .btn-hero:hover { transform: translateY(-3px); box-shadow: 0 16px 40px rgba(233,30,140,0.52); }

    /* Pills */
    .pill {
      display: inline-flex; align-items: center; gap: 7px;
      background: white; border: 1px solid rgba(201,168,76,0.25);
      border-radius: 50px; padding: 7px 16px;
      font-size: 0.78rem; font-weight: 500; color: #6b5760;
      box-shadow: 0 2px 12px rgba(0,0,0,0.04);
    }
    .pill-dot { width: 6px; height: 6px; border-radius: 50%; background: var(--gold); flex-shrink: 0; }

    /* Cards de depoimentos */
    .depo-card {
      background: white; border-radius: 20px;
      border: 1px solid rgba(201,168,76,0.15);
      box-shadow: 0 4px 24px rgba(233,30,140,0.06);
    }

    /* Cards flutuantes ilustração */
    .float-card {
      background: white; border-radius: 18px; position: absolute;
      box-shadow: 0 8px 36px rgba(233,30,140,0.13);
      padding: 14px 18px;
    }
    @keyframes float  { 0%,100%{transform:translateY(0)}    50%{transform:translateY(-12px)} }
    @keyframes float2 { 0%,100%{transform:translateY(-8px)} 50%{transform:translateY(4px)} }
    @keyframes float3 { 0%,100%{transform:translateY(4px)}  50%{transform:translateY(-10px)} }
    .float-card:nth-child(2) { animation: float  4.2s ease-in-out infinite; }
    .float-card:nth-child(3) { animation: float2 3.8s ease-in-out infinite; }
    .float-card:nth-child(4) { animation: float3 4.6s ease-in-out infinite; }

    /* Fade-up de entrada */
    .fade-up { opacity:0; transform:translateY(26px); animation:fadeUp 0.7s ease forwards; }
    .d1{animation-delay:.08s}.d2{animation-delay:.2s}.d3{animation-delay:.32s}
    .d4{animation-delay:.44s}.d5{animation-delay:.56s}
    @keyframes fadeUp { to { opacity:1; transform:translateY(0); } }

    /* ════════════════════════════════════════
       OVERLAY DO MODAL
    ════════════════════════════════════════ */
    #modalOverlay {
      position: fixed; inset: 0; z-index: 1000;
      background: rgba(20,5,15,0.60);
      backdrop-filter: blur(6px);
      display: flex; align-items: flex-end; justify-content: center;
      opacity: 0; pointer-events: none;
      transition: opacity 0.35s ease;
    }
    #modalOverlay.open { opacity: 1; pointer-events: all; }

    @media (min-width: 640px) {
      #modalOverlay { align-items: center; }
      #modalBox { border-radius: 28px !important; max-height: 92vh; }
    }

    /* ════════════════════════════════════════
       CAIXA DO MODAL (layout em coluna)
    ════════════════════════════════════════ */
    #modalBox {
      background: var(--creme);
      width: 100%; max-width: 860px;   /* mais largo em desktop p/ sidebar */
      border-radius: 28px 28px 0 0;
      max-height: 94vh;
      display: flex; flex-direction: column;
      transform: translateY(48px);
      transition: transform 0.38s cubic-bezier(.22,.68,0,1.18);
      overflow: hidden;
    }
    #modalOverlay.open #modalBox { transform: translateY(0); }

    /* Header do modal */
    .modal-header {
      background: linear-gradient(135deg, var(--rosa), var(--magenta));
      color: white; padding: 18px 24px; flex-shrink: 0;
    }
    .modal-header h2 { font-family: 'Playfair Display', serif; }

    /* Área de conteúdo (formulário + sidebar) */
    #modalContent {
      display: flex; flex: 1; overflow: hidden;
    }

    /* Corpo principal do formulário */
    #modalBody {
      flex: 1; overflow-y: auto;
      -webkit-overflow-scrolling: touch;
      padding: 24px 24px 36px;
    }

    /* ════════════════════════════════════════
       SIDEBAR DE RESUMO
    ════════════════════════════════════════ */
    #modalSidebar {
      width: 220px; flex-shrink: 0;
      background: white;
      border-left: 1px solid #fce7f3;
      padding: 24px 18px;
      overflow-y: auto;
      display: none; /* escondida em mobile */
    }
    @media (min-width: 700px) {
      #modalSidebar { display: block; }
    }

    /* Versão mobile: faixa colapsada no topo do body */
    #mobileSummary {
      display: none;
      background: white;
      border-bottom: 1px solid #fce7f3;
      padding: 10px 20px;
      flex-shrink: 0;
    }
    @media (max-width: 699px) {
      #mobileSummary { display: block; }
    }

    .summary-item {
      display: flex; align-items: flex-start; gap: 10px;
      padding: 10px 0;
      border-bottom: 1px solid #fdf2f8;
    }
    .summary-item:last-child { border-bottom: none; }
    .summary-icon {
      width: 30px; height: 30px; border-radius: 8px; flex-shrink: 0;
      display: flex; align-items: center; justify-content: center;
      font-size: 0.9rem;
      background: linear-gradient(135deg, #fce7f3, #fdf2f8);
    }
    .summary-label { font-size: 0.68rem; color: #b0809a; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 2px; }
    .summary-value { font-size: 0.78rem; font-weight: 600; color: #2d1f28; line-height: 1.3; }
    .summary-value.pending { color: #c4a0b4; font-weight: 400; font-style: italic; }

    /* ════════════════════════════════════════
       PROGRESS BAR E DOTS
    ════════════════════════════════════════ */
    .progress-bar  { height: 3px; background: rgba(255,255,255,0.25); border-radius: 2px; overflow: hidden; }
    .progress-fill { height: 100%; background: white; border-radius: 2px; transition: width 0.45s ease; }
    .step-dot { width: 7px; height: 7px; border-radius: 50%; background: rgba(255,255,255,0.35); transition: all 0.3s; }
    .step-dot.active { width: 22px; border-radius: 4px; background: white; }
    .step-dot.done   { background: rgba(255,255,255,0.8); }

    /* ════════════════════════════════════════
       PASSO ZERO — TERMOS
    ════════════════════════════════════════ */
    #step0 .termos-box {
      background: white; border: 1px solid rgba(201,168,76,0.2);
      border-radius: 20px; padding: 24px 22px;
      box-shadow: 0 4px 24px rgba(233,30,140,0.06);
    }
    #step0 .termos-titulo {
      font-family: 'Playfair Display', serif;
      font-size: 1.35rem; font-weight: 700;
      color: #2d1f28; margin-bottom: 6px;
    }
    #step0 .termos-linha {
      display: flex; align-items: flex-start; gap: 10px;
      padding: 10px 0; border-bottom: 1px solid #fdf0f6;
      font-size: 0.855rem; color: #5a3d4f; line-height: 1.6;
    }
    #step0 .termos-linha:last-of-type { border-bottom: none; }
    #step0 .termos-linha .ti { flex-shrink: 0; font-size: 1rem; margin-top: 1px; }

    /* Botão concordar */
    .btn-concordar {
      width: 100%; margin-top: 22px;
      background: transparent;
      border: 2px solid var(--gold);
      color: #7a5a30;
      font-family: 'Playfair Display', serif;
      font-size: 0.95rem; font-weight: 600;
      letter-spacing: 0.05em;
      padding: 14px 28px; border-radius: 50px;
      cursor: pointer;
      transition: all 0.35s ease;
    }
    .btn-concordar:hover {
      background: var(--rosa);
      border-color: var(--rosa);
      color: white;
      box-shadow: 0 8px 24px rgba(233,30,140,0.35);
      transform: translateY(-1px);
    }

    /* ════════════════════════════════════════
       CARDS DE SERVIÇO
    ════════════════════════════════════════ */
    .service-card {
      border: 1.5px solid #f3e8ee;
      border-radius: 18px;
      background: white;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 2px 10px rgba(0,0,0,0.04);
    }
    .service-card:hover   { border-color: var(--rosa); box-shadow: 0 6px 24px rgba(233,30,140,0.14); transform: translateY(-1px); }
    .service-card.selected{ border-color: var(--rosa); background: #fdf8fc; box-shadow: 0 6px 24px rgba(233,30,140,0.18); }

    /* ════════════════════════════════════════
       CARDS DE PROFISSIONAL
    ════════════════════════════════════════ */
    .prof-card {
      border: 1.5px solid #f3e8ee; border-radius: 18px;
      background: white; cursor: pointer; transition: all 0.3s ease;
      box-shadow: 0 2px 10px rgba(0,0,0,0.04);
    }
    .prof-card:hover    { border-color: var(--rosa); transform: translateY(-3px); box-shadow: 0 8px 24px rgba(233,30,140,0.14); }
    .prof-card.selected { border-color: var(--rosa); background: #fdf8fc; box-shadow: 0 6px 20px rgba(233,30,140,0.2); }

    /* ════════════════════════════════════════
       CALENDÁRIO
    ════════════════════════════════════════ */
    .cal-day {
      aspect-ratio: 1; border-radius: 10px; cursor: pointer;
      font-size: 0.8rem; font-weight: 500; transition: all 0.2s ease;
      display: flex; align-items: center; justify-content: center;
    }
    .cal-day.disponivel  { background: #dcfce7; color: #166534; }
    .cal-day.disponivel:hover { background: var(--rosa); color: white; transform: scale(1.08); }
    .cal-day.disponivel.selected { background: var(--rosa); color: white; box-shadow: 0 4px 14px rgba(233,30,140,0.4); }
    .cal-day.indisponivel{ background: #f5f5f5; color: #c4b4bc; cursor: not-allowed; }
    .cal-day.passado     { background: #fafafa; color: #ddd; cursor: not-allowed; }
    .cal-day.empty       { background: transparent; cursor: default; }

    /* ════════════════════════════════════════
       SLOTS DE HORÁRIO
    ════════════════════════════════════════ */
    .time-slot {
      border: 1.5px solid #fce7f3; border-radius: 12px;
      padding: 11px 8px; text-align: center;
      cursor: pointer; font-weight: 500; font-size: 0.85rem;
      transition: all 0.25s ease; background: white;
    }
    .time-slot:hover   { border-color: var(--rosa); background: #fdf8fc; transform: scale(1.04); }
    .time-slot.selected{ background: var(--rosa); border-color: var(--rosa); color: white; box-shadow: 0 4px 14px rgba(233,30,140,0.4); }
    .time-slot.ocupado { background: #f5f5f5; color: #ccc; cursor: not-allowed; border-color: #eee; }

    /* ════════════════════════════════════════
       BOTÕES DO MODAL
    ════════════════════════════════════════ */
    .btn-rosa {
      background: linear-gradient(135deg, var(--rosa), var(--magenta));
      color: white; font-family: 'DM Sans', sans-serif;
      font-weight: 600; padding: 14px 28px;
      border-radius: 50px; border: none; cursor: pointer;
      width: 100%; font-size: 0.95rem;
      transition: all 0.25s ease;
      box-shadow: 0 4px 16px rgba(233,30,140,0.32);
      letter-spacing: 0.02em;
    }
    .btn-rosa:hover    { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(233,30,140,0.45); }
    .btn-rosa:disabled { opacity: 0.45; cursor: not-allowed; transform: none; box-shadow: none; }

    .btn-outline {
      background: white; color: var(--rosa);
      border: 1.5px solid var(--rosa); font-family: 'DM Sans', sans-serif;
      font-weight: 600; padding: 12px 24px;
      border-radius: 50px; cursor: pointer; transition: all 0.25s ease;
    }
    .btn-outline:hover { background: #fdf0f6; }

    /* Botão voltar */
    .btn-back {
      display: inline-flex; align-items: center; gap: 6px;
      font-size: 0.82rem; font-weight: 500; color: var(--rosa);
      background: none; border: none; cursor: pointer;
      padding: 6px 0; margin-bottom: 18px;
      transition: opacity 0.2s;
      letter-spacing: 0.02em;
    }
    .btn-back:hover { opacity: 0.7; }

    /* ════════════════════════════════════════
       INPUT
    ════════════════════════════════════════ */
    .input-field {
      border: 1.5px solid #f0e4ec; border-radius: 14px;
      padding: 13px 16px; width: 100%;
      font-family: 'DM Sans', sans-serif; font-size: 0.9rem;
      outline: none; transition: border-color 0.25s, box-shadow 0.25s;
      background: white; color: #2d1f28;
    }
    .input-field:focus { border-color: var(--rosa); box-shadow: 0 0 0 3px rgba(233,30,140,0.08); }
    .input-field::placeholder { color: #c4a8b8; }

    /* ════════════════════════════════════════
       ANIMAÇÃO DE PASSO
    ════════════════════════════════════════ */
    .step-panel { animation: slideIn 0.3s ease forwards; }
    @keyframes slideIn { from{opacity:0;transform:translateX(16px)} to{opacity:1;transform:translateX(0)} }

    /* ════════════════════════════════════════
       SPINNER
    ════════════════════════════════════════ */
    .spinner {
      width: 26px; height: 26px;
      border: 3px solid #fce7f3; border-top-color: var(--rosa);
      border-radius: 50%; animation: spin 0.7s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* ════════════════════════════════════════
       SUCESSO
    ════════════════════════════════════════ */
    .success-circle {
      width: 76px; height: 76px; margin: 0 auto;
      background: linear-gradient(135deg, var(--rosa), var(--magenta));
      border-radius: 50%; display: flex; align-items: center; justify-content: center;
      box-shadow: 0 10px 30px rgba(233,30,140,0.4);
    }

    /* ════════════════════════════════════════
       RESPONSIVO MOBILE
    ════════════════════════════════════════ */
    @media (max-width: 640px) {
      .blob-1{width:320px;height:320px}.blob-3{display:none}
      .btn-hero{width:100%;justify-content:center}
      #modalBody{padding:20px 16px 32px}
    }
  </style>
</head>

<body>

<!-- Blobs -->
<div class="blob blob-1"></div>
<div class="blob blob-2"></div>
<div class="blob blob-3"></div>

<!-- ════════════════════════════════════════════
     NAVBAR
════════════════════════════════════════════ -->
<nav class="navbar fixed top-0 left-0 right-0 z-50">
  <div class="max-w-6xl mx-auto px-6 py-3 flex items-center justify-between">

    <a href="<?= BASE_URL ?>/" class="flex items-center gap-3 no-underline">
      <div class="w-9 h-9 rounded-xl flex items-center justify-center text-lg"
           style="background:linear-gradient(135deg,var(--rosa),var(--magenta));box-shadow:0 4px 12px rgba(233,30,140,0.3)">✂️</div>
      <div class="hidden sm:block">
        <p class="text-gray-800 font-semibold text-sm leading-tight" style="font-family:'Playfair Display',serif;">
          <?= htmlspecialchars(LOJA_NOME) ?>
        </p>
        <p class="text-xs tracking-widest uppercase" style="color:var(--gold);font-size:0.6rem">Noivas & Festas</p>
      </div>
    </a>

    <a href="<?= BASE_URL ?>/views/login.php"
       class="flex items-center gap-2 text-sm font-medium text-gray-600 hover:text-pink-600 transition-all px-4 py-2 rounded-full hover:bg-pink-50">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
      </svg>
      <span class="hidden sm:inline">Painel do Profissional</span>
      <span class="sm:hidden">Entrar</span>
    </a>

  </div>
</nav>

<!-- ════════════════════════════════════════════
     HERO
════════════════════════════════════════════ -->
<main class="hero min-h-screen flex items-center pt-16">
  <div class="max-w-6xl mx-auto px-6 py-16 w-full">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-12 lg:gap-20 items-center">

      <!-- ESQUERDA -->
      <div>
        <div class="fade-up d1 mb-6">
          <span class="pill"><span class="pill-dot"></span>Alta Costura · Noivas & Debutantes</span>
        </div>

        <h1 class="fade-up d2 font-bold text-gray-900 leading-tight mb-3"
            style="font-family:'Playfair Display',serif; font-size:clamp(2.4rem,5vw,3.6rem); line-height:1.15;">
          O seu momento<br/>
          merece ser<br/>
          <em class="gradient-text not-italic">inesquecível</em>
        </h1>

        <div class="divider-gold fade-up d2">
          <span>Jackeline Viana Noivas e Festas</span>
        </div>

        <p class="fade-up d3 text-gray-500 leading-relaxed mb-8 max-w-md" style="font-size:1rem; font-weight:300;">
          Agende seu atendimento de vestido de noiva, debutante ou traje de festa com elegância — escolha o dia, o horário e receba a confirmação no WhatsApp.
        </p>

        <div class="fade-up d3 flex flex-wrap gap-2 mb-10">
          <span class="pill"><span class="pill-dot"></span>Vestidos de Noiva</span>
          <span class="pill"><span class="pill-dot"></span>Debutantes</span>
          <span class="pill"><span class="pill-dot"></span>Trajes de Festa</span>
        </div>

        <div class="fade-up d4">
          <button onclick="abrirModal()" class="btn-hero">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            Agendar meu atendimento
          </button>
        </div>

        <p class="fade-up d5 mt-6 text-xs tracking-wide" style="color:#b0809a">
          📍 <?= htmlspecialchars(LOJA_ENDERECO) ?> &nbsp;·&nbsp; Sem cadastro obrigatório
        </p>
      </div>

      <!-- DIREITA — Ilustração -->
      <div class="relative hidden md:flex items-center justify-center fade-up d2" style="min-height:480px">
        <!-- Calendário SVG -->
        <svg viewBox="0 0 400 440" xmlns="http://www.w3.org/2000/svg" class="w-full max-w-sm"
             style="filter:drop-shadow(0 24px 60px rgba(233,30,140,0.13))">
          <defs>
            <linearGradient id="gH" x1="0" y1="0" x2="1" y2="0">
              <stop offset="0%" stop-color="#e91e8c"/><stop offset="100%" stop-color="#c2186e"/>
            </linearGradient>
          </defs>
          <rect x="20" y="20" width="360" height="400" rx="26" fill="white" stroke="#fce7f3" stroke-width="1.5"/>
          <rect x="20" y="20" width="360" height="70" rx="26" fill="url(#gH)"/>
          <rect x="20" y="62" width="360" height="28" fill="url(#gH)"/>
          <text x="200" y="63" text-anchor="middle" fill="white" font-family="'Playfair Display',serif" font-size="20" font-weight="700">Junho 2025</text>
          <circle cx="50" cy="55" r="15" fill="rgba(255,255,255,0.18)"/>
          <text x="50" y="61" text-anchor="middle" fill="white" font-size="15">‹</text>
          <circle cx="350" cy="55" r="15" fill="rgba(255,255,255,0.18)"/>
          <text x="350" y="61" text-anchor="middle" fill="white" font-size="15">›</text>
          <?php foreach(['D','S','T','Q','Q','S','S'] as $i=>$d): ?>
          <text x="<?= 55+$i*48 ?>" y="112" text-anchor="middle" fill="#c4a0b4" font-family="'DM Sans',sans-serif" font-size="11" font-weight="600"><?= $d ?></text>
          <?php endforeach; ?>
          <?php
          $diasSvg=[
            [null,null,null,null,null,null,'1'],
            ['2','3','4','5','6','7','8'],
            ['9','10','11','12','13','14','15'],
            ['16','17','18','19','20','21','22'],
            ['23','24','25','26','27','28','29'],
            ['30',null,null,null,null,null,null],
          ];
          $dispSvg=['5','13','19','26','27']; $selSvg='12';
          foreach($diasSvg as $ri=>$sem): foreach($sem as $ci=>$d):
            if(!$d) continue;
            $cx=55+$ci*48; $cy=148+$ri*48;
            $tipo=in_array($d,$dispSvg)?'d':($d===$selSvg?'s':'n');
            $fill=$tipo==='d'?'#dcfce7':($tipo==='s'?'#e91e8c':'#f5f5f5');
            $txt=$tipo==='d'?'#166534':($tipo==='s'?'white':'#c4b4bc');
            $r=$tipo==='s'?19:17;
          ?>
          <circle cx="<?=$cx?>" cy="<?=$cy?>" r="<?=$r?>" fill="<?=$fill?>"/>
          <text x="<?=$cx?>" y="<?=$cy+5?>" text-anchor="middle" fill="<?=$txt?>" font-family="'DM Sans',sans-serif" font-size="<?=$tipo==='s'?13:11?>" font-weight="<?=$tipo==='s'?700:500?>"><?=$d?></text>
          <?php endforeach; endforeach; ?>
          <circle cx="50"  cy="408" r="7" fill="#dcfce7"/><text x="62"  y="413" fill="#c4a0b4" font-family="'DM Sans',sans-serif" font-size="10">Disponível</text>
          <circle cx="140" cy="408" r="7" fill="#e91e8c"/><text x="152" y="413" fill="#c4a0b4" font-family="'DM Sans',sans-serif" font-size="10">Selecionado</text>
          <circle cx="238" cy="408" r="7" fill="#f5f5f5"/>  <text x="250" y="413" fill="#c4a0b4" font-family="'DM Sans',sans-serif" font-size="10">Ocupado</text>
        </svg>
        <!-- Float cards -->
        <div class="float-card" style="bottom:50px;left:-8px;min-width:188px">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-full flex-shrink-0 flex items-center justify-center text-white text-sm"
                 style="background:linear-gradient(135deg,var(--rosa),var(--magenta))">✓</div>
            <div><p class="text-xs font-bold text-gray-800">Agendado! 🌸</p><p class="text-xs mt-0.5" style="color:#c4a0b4">12/06 · 14:30 · Bianca</p></div>
          </div>
        </div>
        <div class="float-card" style="top:32px;right:-8px;min-width:162px">
          <div class="flex items-center gap-2.5">
            <span class="text-xl">💬</span>
            <div><p class="text-xs font-bold text-gray-800">Confirmar via</p><p class="text-xs font-bold" style="color:var(--rosa)">WhatsApp</p></div>
          </div>
        </div>
        <div class="float-card" style="top:150px;right:-22px;min-width:155px">
          <div class="flex items-center gap-2.5">
            <span class="text-xl">📆</span>
            <div><p class="text-xs font-bold text-gray-800">Adicionar à</p><p class="text-xs" style="color:#c4a0b4">Agenda do Google</p></div>
          </div>
        </div>
      </div>

    </div><!-- /grid -->

    <!-- Depoimentos -->
    <div class="mt-20 fade-up d5">
      <div class="divider-gold"><span>O que dizem nossas clientes</span></div>
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-6">
        <?php foreach([
          ['💍','Marquei minha prova de noiva em 2 minutos e recebi a confirmação no WhatsApp na hora. Perfeito!','Ana Carolina, Noiva'],
          ['🌸','Minha filha escolheu o vestido da debutante com toda a calma. Adoramos o atendimento!','Patrícia, Mãe da Debutante'],
          ['✨','Facilíssimo agendar! A plataforma é linda e o atendimento superou as expectativas.','Renata, Madrinha'],
        ] as [$em,$txt,$aut]): ?>
        <div class="depo-card p-5">
          <div class="text-2xl mb-3"><?= $em ?></div>
          <p class="text-sm leading-relaxed mb-3" style="color:#5a3d4f;font-weight:300">"<?= $txt ?>"</p>
          <p class="text-xs font-semibold tracking-wide" style="color:var(--rosa)"><?= $aut ?></p>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

  </div>
</main>

<footer class="relative z-10 text-center py-8 text-xs tracking-wide" style="color:#c4a0b4">
  © <?= date('Y') ?> <?= htmlspecialchars(LOJA_NOME) ?> · Todos os direitos reservados
</footer>


<!-- ════════════════════════════════════════════
     MODAL
════════════════════════════════════════════ -->
<div id="modalOverlay" role="dialog" aria-modal="true">
  <div id="modalBox">

    <!-- HEADER DO MODAL -->
    <div class="modal-header">
      <div class="flex items-center justify-between mb-3">
        <div>
          <h2 class="font-bold text-lg tracking-wide">Agendar Atendimento</h2>
          <p class="text-xs opacity-70 tracking-widest uppercase mt-0.5"><?= htmlspecialchars(LOJA_NOME) ?></p>
        </div>
        <button onclick="fecharModal()"
                class="w-8 h-8 rounded-full bg-white/20 hover:bg-white/35 flex items-center justify-center text-white text-xl transition"
                aria-label="Fechar">×</button>
      </div>
      <!-- Progress + dots (oculto no passo 0) -->
      <div id="progressArea">
        <div class="progress-bar mb-2">
          <div class="progress-fill" id="progressFill" style="width:0%"></div>
        </div>
        <div class="flex items-center gap-2">
          <div class="step-dot" id="dot1"></div>
          <div class="step-dot" id="dot2"></div>
          <div class="step-dot" id="dot3"></div>
          <div class="step-dot" id="dot4"></div>
          <div class="step-dot" id="dot5"></div>
          <span class="text-xs opacity-65 ml-auto tracking-wide" id="stepLabel"></span>
        </div>
      </div>
    </div>

    <!-- RESUMO MOBILE (faixa fina) -->
    <div id="mobileSummary">
      <div class="flex gap-4 text-xs overflow-x-auto pb-1">
        <div class="flex-shrink-0"><span class="block" style="color:#c4a0b4;text-transform:uppercase;letter-spacing:.06em;font-size:.62rem">Serviço</span><span id="ms_servico" class="font-medium text-gray-700" style="font-size:.75rem">—</span></div>
        <div class="flex-shrink-0"><span class="block" style="color:#c4a0b4;text-transform:uppercase;letter-spacing:.06em;font-size:.62rem">Atendente</span><span id="ms_prof" class="font-medium text-gray-700" style="font-size:.75rem">—</span></div>
        <div class="flex-shrink-0"><span class="block" style="color:#c4a0b4;text-transform:uppercase;letter-spacing:.06em;font-size:.62rem">Data & Hora</span><span id="ms_data" class="font-medium text-gray-700" style="font-size:.75rem">—</span></div>
      </div>
    </div>

    <!-- CONTEÚDO: FORMULÁRIO + SIDEBAR -->
    <div id="modalContent">

      <!-- SIDEBAR DESKTOP -->
      <aside id="modalSidebar">
        <p class="text-xs uppercase tracking-widest mb-4" style="color:var(--gold);letter-spacing:.12em">Seu Resumo</p>

        <div class="summary-item">
          <div class="summary-icon">✂️</div>
          <div>
            <p class="summary-label">Serviço</p>
            <p class="summary-value pending" id="sb_servico">Aguardando seleção...</p>
          </div>
        </div>

        <div class="summary-item">
          <div class="summary-icon">👗</div>
          <div>
            <p class="summary-label">Atendente</p>
            <p class="summary-value pending" id="sb_prof">Aguardando seleção...</p>
          </div>
        </div>

        <div class="summary-item">
          <div class="summary-icon">📅</div>
          <div>
            <p class="summary-label">Data</p>
            <p class="summary-value pending" id="sb_data">Aguardando seleção...</p>
          </div>
        </div>

        <div class="summary-item">
          <div class="summary-icon">⏰</div>
          <div>
            <p class="summary-label">Horário</p>
            <p class="summary-value pending" id="sb_hora">Aguardando seleção...</p>
          </div>
        </div>

        <!-- Divisor dourado decorativo -->
        <div class="mt-6 pt-5" style="border-top:1px solid #fce7f3">
          <p class="text-xs leading-relaxed" style="color:#c4a0b4;font-weight:300">
            📍 <?= htmlspecialchars(LOJA_ENDERECO) ?>
          </p>
          <p class="text-xs mt-2" style="color:#c4a0b4;font-weight:300">
            Atendimentos individuais. Máximo 1 acompanhante.
          </p>
        </div>
      </aside>

      <!-- CORPO DO FORMULÁRIO -->
      <div id="modalBody">

        <!-- ─── PASSO 0: TERMOS ─── -->
        <section id="step0" class="step-panel">
          <div class="termos-box">
            <p class="termos-titulo">Atenção</p>
            <p class="text-xs tracking-widest uppercase mb-4" style="color:var(--gold);letter-spacing:.1em">Leia antes de continuar</p>

            <div class="termos-linha">
              <span class="ti">👋</span>
              <span>Seja <strong>Bem-vinda(o)</strong> à <strong>Jackeline Viana Noivas e Festas</strong>! Aqui você poderá agendar seu atendimento para conhecer nossos modelos ou fazer a escolha de seus trajes já locados.</span>
            </div>

            <div class="termos-linha">
              <span class="ti">📞</span>
              <span>Se precisar agendar <strong>prova de medidas ou prova final</strong>, entre em contato exclusivamente com <strong>(96) 99102-1241</strong>.</span>
            </div>

            <div class="termos-linha">
              <span class="ti">👤</span>
              <span>Os agendamentos são <strong>individuais</strong>. Se houver mais alguém para ser atendido, deverá fazer outro agendamento.</span>
            </div>

            <div class="termos-linha">
              <span class="ti">💑</span>
              <span>O cliente pode levar apenas <strong>1 acompanhante</strong> para ajudar na escolha do traje.</span>
            </div>

            <div class="termos-linha">
              <span class="ti">📍</span>
              <span>Em Macapá – AP, <strong>Rua Mendonça Furtado, 103 – Centro</strong>.</span>
            </div>

            <button class="btn-concordar" onclick="concordarEContinuar()">
              ✦ &nbsp; Concordo e Desejo Continuar &nbsp; ✦
            </button>
          </div>
        </section>

        <!-- ─── PASSO 1: SERVIÇO ─── -->
        <section id="step1" class="step-panel hidden">
          <h3 class="font-bold text-gray-800 mb-1" style="font-family:'Playfair Display',serif;font-size:1.3rem">Qual serviço você precisa?</h3>
          <p class="text-sm mb-5" style="color:#b0809a;font-weight:300">Selecione uma opção para continuar</p>

          <div class="space-y-3" id="listaServicos">
            <?php foreach ($servicos as $s):
              $icones=['Noiva'=>'👰','noiva'=>'👰','NOIVA'=>'👰',
                       'Debutante'=>'🌸','debutante'=>'🌸','DEBUTANTE'=>'🌸',
                       'FESTA'=>'✨','Festa'=>'✨','festa'=>'✨',
                       'MASCULINO'=>'🤵','Masculino'=>'🤵','masculino'=>'🤵',
                       'INFANTIL'=>'🎀','Infantil'=>'🎀','infantil'=>'🎀'];
              $icon='✂️';
              foreach($icones as $k=>$v){if(str_contains($s->getNome(),$k)){$icon=$v;break;}}
            ?>
            <div class="service-card p-4 flex items-center gap-4"
                 data-id="<?= $s->getId() ?>"
                 data-nome="<?= htmlspecialchars($s->getNome()) ?>"
                 data-duracao="<?= $s->getDuracaoMin() ?>"
                 onclick="selecionarServico(this)">
              <div class="w-11 h-11 rounded-full flex-shrink-0 flex items-center justify-center text-xl"
                   style="background:linear-gradient(135deg,#fce7f3,#fdf2f8)"><?= $icon ?></div>
              <div class="flex-1 min-w-0">
                <p class="font-medium text-gray-800 leading-tight" style="font-size:.88rem"><?= htmlspecialchars($s->getNome()) ?></p>
                <?php if($s->getDescricao()): ?>
                <p class="text-xs mt-0.5 truncate" style="color:#c4a0b4"><?= htmlspecialchars($s->getDescricao()) ?></p>
                <?php endif; ?>
                <p class="text-xs mt-1 font-medium" style="color:var(--rosa)">⏱ <?= $s->getDuracaoMin() ?> min</p>
              </div>
              <div class="w-6 h-6 rounded-full border-2 flex-shrink-0 flex items-center justify-center check-icon" style="border-color:#ead8e4">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                </svg>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </section>

        <!-- ─── PASSO 2: PROFISSIONAL ─── -->
        <section id="step2" class="step-panel hidden">
          <button class="btn-back" onclick="voltarPasso(1)">← Voltar</button>
          <h3 class="font-bold text-gray-800 mb-1" style="font-family:'Playfair Display',serif;font-size:1.3rem">Escolha sua consultora</h3>
          <p class="text-sm mb-5" style="color:#b0809a;font-weight:300">Ou deixe o sistema escolher automaticamente</p>

          <div class="grid grid-cols-2 gap-3 mb-5" id="listaProfissionais">
            <div class="prof-card p-5 text-center selected"
                 data-id="0" data-nome="Qualquer atendente" onclick="selecionarProfissional(this)">
              <div class="w-14 h-14 rounded-full mx-auto mb-3 flex items-center justify-center text-2xl"
                   style="background:linear-gradient(135deg,#fce7f3,#fdf2f8)">🌸</div>
              <p class="font-medium text-gray-800 text-sm">Automático</p>
              <p class="text-xs mt-1" style="color:#c4a0b4">Próxima disponível</p>
            </div>
            <?php foreach($profissionais as $p): ?>
            <div class="prof-card p-5 text-center"
                 data-id="<?= $p->getId() ?>"
                 data-nome="<?= htmlspecialchars($p->getApelido()) ?>"
                 onclick="selecionarProfissional(this)">
              <?php if($p->getFotoUrl()): ?>
              <img src="<?= htmlspecialchars($p->getFotoUrl()) ?>" class="w-14 h-14 rounded-full mx-auto mb-3 object-cover"/>
              <?php else: ?>
              <div class="w-14 h-14 rounded-full mx-auto mb-3 flex items-center justify-center text-xl font-bold text-white"
                   style="background:<?= htmlspecialchars($p->getCorAgenda()) ?>">
                <?= mb_strtoupper(mb_substr($p->getApelido(),0,1)) ?>
              </div>
              <?php endif; ?>
              <p class="font-medium text-gray-800 text-sm"><?= htmlspecialchars($p->getApelido()) ?></p>
              <p class="text-xs mt-1" style="color:#c4a0b4">Disponível</p>
            </div>
            <?php endforeach; ?>
          </div>
          <button class="btn-rosa" onclick="irParaPasso(3)">Continuar →</button>
        </section>

        <!-- ─── PASSO 3: CALENDÁRIO ─── -->
        <section id="step3" class="step-panel hidden">
          <button class="btn-back" onclick="voltarPasso(2)">← Voltar</button>
          <h3 class="font-bold text-gray-800 mb-1" style="font-family:'Playfair Display',serif;font-size:1.3rem">Escolha a data</h3>
          <p class="text-sm mb-5" style="color:#b0809a;font-weight:300">Dias em verde possuem horários disponíveis</p>

          <div class="flex items-center justify-between mb-4">
            <button onclick="mudarMes(-1)" class="w-9 h-9 rounded-full bg-white shadow flex items-center justify-center text-gray-500 hover:bg-pink-50 transition">‹</button>
            <span class="font-semibold text-gray-700 text-sm tracking-wide" id="mesLabel">—</span>
            <button onclick="mudarMes(1)"  class="w-9 h-9 rounded-full bg-white shadow flex items-center justify-center text-gray-500 hover:bg-pink-50 transition">›</button>
          </div>

          <div class="grid grid-cols-7 gap-1 mb-2 text-center text-xs font-medium" style="color:#c4a0b4">
            <span>Dom</span><span>Seg</span><span>Ter</span><span>Qua</span><span>Qui</span><span>Sex</span><span>Sáb</span>
          </div>
          <div id="calGrid" class="grid grid-cols-7 gap-1 mb-5">
            <div class="col-span-7 flex justify-center py-4"><div class="spinner"></div></div>
          </div>

          <div class="flex items-center gap-4 text-xs mb-5" style="color:#c4a0b4">
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-green-100 inline-block"></span>Disponível</span>
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-gray-100 inline-block"></span>Indisponível</span>
          </div>

          <div id="horariosArea" class="hidden">
            <h4 class="font-semibold text-gray-700 mb-3 text-sm tracking-wide" id="horariosTitle">Horários disponíveis</h4>
            <div id="horariosLoading" class="hidden flex justify-center py-4"><div class="spinner"></div></div>
            <div id="horariosGrid" class="grid grid-cols-3 gap-2 mb-5"></div>
          </div>

          <button class="btn-rosa" id="btnContinuarHorario" disabled onclick="irParaPasso(4)">Continuar →</button>
        </section>

        <!-- ─── PASSO 4: DADOS ─── -->
        <section id="step4" class="step-panel hidden">
          <button class="btn-back" onclick="voltarPasso(3)">← Voltar</button>
          <h3 class="font-bold text-gray-800 mb-1" style="font-family:'Playfair Display',serif;font-size:1.3rem">Suas informações</h3>
          <p class="text-sm mb-5" style="color:#b0809a;font-weight:300">Preencha para confirmar seu agendamento</p>

          <!-- Resumo inline (step 4) -->
          <div class="mb-5 p-4 rounded-2xl border" style="background:white;border-color:#fce7f3">
            <p class="text-xs uppercase tracking-widest mb-3" style="color:#c4a0b4;letter-spacing:.08em">Resumo do agendamento</p>
            <div class="space-y-2 text-sm">
              <div class="flex gap-2"><span>✂️</span><span class="font-medium text-gray-800" id="resumoServico">—</span></div>
              <div class="flex gap-2"><span>👗</span><span style="color:#6b5760" id="resumoProfissional">—</span></div>
              <div class="flex gap-2"><span>📅</span><span style="color:#6b5760" id="resumoDataHora">—</span></div>
            </div>
          </div>

          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5 tracking-wide">Nome completo *</label>
              <input type="text" id="inputNome" class="input-field" placeholder="Seu nome completo"
                     <?= $clienteLogado ? 'value="'.htmlspecialchars($clienteNome).'"':'' ?>/>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5 tracking-wide">WhatsApp *</label>
              <input type="tel" id="inputWhatsapp" class="input-field" placeholder="(00) 00000-0000"/>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5 tracking-wide">E-mail</label>
              <input type="email" id="inputEmail" class="input-field" placeholder="seu@email.com"/>
            </div>
            <?php if(!$clienteLogado): ?>
            <div class="rounded-2xl p-4 border" style="background:#fdf8fc;border-color:#fce7f3">
              <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" id="checkCriarConta" class="mt-0.5 accent-pink-500" onchange="toggleCampoSenha()"/>
                <div>
                  <p class="text-sm font-medium text-gray-800">Criar minha área do cliente</p>
                  <p class="text-xs mt-0.5" style="color:#c4a0b4">Acesse histórico de agendamentos e catálogo de peças</p>
                </div>
              </label>
              <div id="campoCriarConta" class="hidden mt-3">
                <input type="password" id="inputSenha" class="input-field" placeholder="Crie uma senha"/>
              </div>
            </div>
            <?php endif; ?>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5 tracking-wide">Observações (opcional)</label>
              <textarea id="inputObs" class="input-field" rows="2" placeholder="Ex: ocasião, traje específico..."></textarea>
            </div>
          </div>

          <div id="erroStep4" class="hidden mt-4 text-sm text-red-600 bg-red-50 rounded-xl p-3"></div>
          <button class="btn-rosa mt-5" id="btnConfirmar" onclick="confirmarAgendamento()">
            Confirmar Agendamento 🌸
          </button>
        </section>

        <!-- ─── PASSO 5: SUCESSO ─── -->
        <section id="step5" class="step-panel hidden text-center py-6">
          <div class="success-circle mb-5">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
            </svg>
          </div>
          <h3 class="font-bold text-gray-800 text-2xl mb-2" style="font-family:'Playfair Display',serif">Agendamento confirmado!</h3>
          <p class="text-sm mb-6" style="color:#b0809a;font-weight:300">Te esperamos com muito carinho 🌸</p>

          <div class="bg-white rounded-2xl p-5 mb-5 text-left border" style="border-color:#fce7f3">
            <p class="text-xs uppercase tracking-widest mb-3" style="color:#c4a0b4;letter-spacing:.08em">Detalhes do agendamento</p>
            <div class="space-y-2 text-sm">
              <div class="flex items-center gap-2 text-gray-800"><span>✂️</span><span id="sucesso_servico" class="font-medium">—</span></div>
              <div class="flex items-center gap-2" style="color:#6b5760"><span>👗</span><span id="sucesso_profissional">—</span></div>
              <div class="flex items-center gap-2" style="color:#6b5760"><span>📅</span><span id="sucesso_datahora">—</span></div>
              <div class="flex items-center gap-2" style="color:#6b5760"><span>👤</span><span id="sucesso_nome">—</span></div>
            </div>
          </div>

          <div class="space-y-3">
            <a id="btnGcal" href="#" target="_blank"
               class="btn-outline w-full flex items-center justify-center gap-2 py-3">
              <span>📆</span> Adicionar à Agenda do Google
            </a>
            <a id="btnWppLoja" href="#" target="_blank"
               class="btn-rosa flex items-center justify-center gap-2 py-3 no-underline rounded-full">
              <span>💬</span> Confirmar via WhatsApp
            </a>
            <button onclick="novoAgendamento()" class="w-full text-sm py-2 hover:opacity-70 transition" style="color:#c4a0b4">
              + Fazer novo agendamento
            </button>
          </div>
        </section>

      </div><!-- /modalBody -->
    </div><!-- /modalContent -->
  </div><!-- /modalBox -->
</div><!-- /modalOverlay -->


<!-- ════════════════════════════════════════════
     JAVASCRIPT
════════════════════════════════════════════ -->
<script>
const BASE = '<?php echo BASE_URL; ?>';

/* ── Estado ─────────────────────────────────── */
const state = {
  passoAtual: 0,               // 0 = termos
  servicoId: null, servicoNome: '', duracaoMin: 60,
  profissionalId: 0, profissionalNome: 'Qualquer atendente',
  anoAtual:  new Date().getFullYear(),
  mesAtual:  new Date().getMonth() + 1,
  diaSelecionado: null, horaSelecionada: null,
  gcalLink: '', wppLojaLink: '',
};
const STEP_LABELS = ['','Serviço','Consultora','Data & Hora','Seus Dados','Confirmado'];

/* ── Modal ───────────────────────────────────── */
function abrirModal() {
  document.getElementById('modalOverlay').classList.add('open');
  document.body.style.overflow = 'hidden';
  resetarModal();
}
function fecharModal() {
  document.getElementById('modalOverlay').classList.remove('open');
  document.body.style.overflow = '';
}
document.getElementById('modalOverlay').addEventListener('click', e => {
  if (e.target === document.getElementById('modalOverlay')) fecharModal();
});
document.addEventListener('keydown', e => { if (e.key === 'Escape') fecharModal(); });

function resetarModal() {
  Object.assign(state, {
    passoAtual:0, servicoId:null, servicoNome:'', duracaoMin:60,
    profissionalId:0, profissionalNome:'Qualquer atendente',
    anoAtual:new Date().getFullYear(), mesAtual:new Date().getMonth()+1,
    diaSelecionado:null, horaSelecionada:null,
  });
  // Esconde todos os passos e mostra passo 0
  document.querySelectorAll('[id^="step"]').forEach(el => el.classList.add('hidden'));
  document.getElementById('step0').classList.remove('hidden');
  // Esconde barra de progresso no passo 0
  document.getElementById('progressArea').style.opacity = '0';
  document.getElementById('mobileSummary').style.display = 'none';
  atualizarSidebar();
  document.getElementById('modalBody').scrollTop = 0;
}

/* ── Passo 0: Concordar ─────────────────────── */
function concordarEContinuar() {
  document.getElementById('step0').classList.add('hidden');
  document.getElementById('progressArea').style.opacity = '1';
  document.getElementById('mobileSummary').style.display = 'block';
  irParaPasso(1);
}

/* ── Navegação ───────────────────────────────── */
function irParaPasso(passo) {
  if (state.passoAtual >= 1)
    document.getElementById(`step${state.passoAtual}`)?.classList.add('hidden');
  document.getElementById(`step${passo}`)?.classList.remove('hidden');
  state.passoAtual = passo;
  atualizarProgress();
  atualizarSidebar();
  document.getElementById('modalBody').scrollTop = 0;
  if (passo === 3) iniciarCalendario();
  if (passo === 4) preencherResumo();
}
function voltarPasso(passo) { irParaPasso(passo); }

function atualizarProgress() {
  const p = state.passoAtual;
  document.getElementById('progressFill').style.width = `${p * 20}%`;
  document.getElementById('stepLabel').textContent = p >= 1 ? `Passo ${p} de 5 — ${STEP_LABELS[p]}` : '';
  for (let i = 1; i <= 5; i++) {
    const dot = document.getElementById(`dot${i}`);
    if (dot) dot.className = 'step-dot' + (i < p ? ' done' : i === p ? ' active' : '');
  }
}

/* ── Sidebar / Resumo ────────────────────────── */
function atualizarSidebar() {
  const fmt = (val, fallback='Aguardando seleção...') => val || fallback;
  const dataFmt = state.diaSelecionado
    ? `${String(state.diaSelecionado).padStart(2,'0')}/${String(state.mesAtual).padStart(2,'0')}/${state.anoAtual}`
    : '';

  // Sidebar desktop
  setSummaryEl('sb_servico',  state.servicoNome,       'Aguardando seleção...');
  setSummaryEl('sb_prof',     state.profissionalNome !== 'Qualquer atendente' ? state.profissionalNome : '', 'Aguardando seleção...');
  setSummaryEl('sb_data',     dataFmt,                 'Aguardando seleção...');
  setSummaryEl('sb_hora',     state.horaSelecionada,   'Aguardando seleção...');

  // Mobile summary strip
  document.getElementById('ms_servico').textContent = state.servicoNome       || '—';
  document.getElementById('ms_prof').textContent    = state.profissionalNome   || '—';
  document.getElementById('ms_data').textContent    = (dataFmt && state.horaSelecionada)
    ? `${dataFmt} · ${state.horaSelecionada}` : (dataFmt || '—');
}

function setSummaryEl(id, val, fallback) {
  const el = document.getElementById(id);
  if (!el) return;
  if (val) {
    el.textContent = val;
    el.classList.remove('pending');
    el.classList.add('font-semibold');
  } else {
    el.textContent = fallback;
    el.classList.add('pending');
    el.classList.remove('font-semibold');
  }
}

/* ── Passo 1: Serviço ────────────────────────── */
function selecionarServico(el) {
  document.querySelectorAll('.service-card').forEach(c => {
    c.classList.remove('selected');
    const ci = c.querySelector('.check-icon');
    ci.style.borderColor = '#ead8e4'; ci.style.background = '';
    ci.querySelector('svg').classList.add('hidden');
  });
  el.classList.add('selected');
  const icon = el.querySelector('.check-icon');
  icon.style.borderColor = 'var(--rosa)'; icon.style.background = 'var(--rosa)';
  icon.querySelector('svg').classList.remove('hidden');
  icon.querySelector('svg').style.color = 'white';
  state.servicoId   = parseInt(el.dataset.id);
  state.servicoNome = el.dataset.nome;
  state.duracaoMin  = parseInt(el.dataset.duracao);
  atualizarSidebar();
  setTimeout(() => irParaPasso(2), 200);
}

/* ── Passo 2: Profissional ───────────────────── */
function selecionarProfissional(el) {
  document.querySelectorAll('.prof-card').forEach(c => c.classList.remove('selected'));
  el.classList.add('selected');
  state.profissionalId   = parseInt(el.dataset.id);
  state.profissionalNome = el.dataset.nome;
  atualizarSidebar();
}

/* ── Passo 3: Calendário ─────────────────────── */
const MESES = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho',
               'Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];

function iniciarCalendario() { renderizarCalendario(); }

function mudarMes(delta) {
  state.mesAtual += delta;
  if (state.mesAtual > 12){ state.mesAtual=1; state.anoAtual++; }
  if (state.mesAtual < 1) { state.mesAtual=12; state.anoAtual--; }
  state.diaSelecionado=null; state.horaSelecionada=null;
  document.getElementById('horariosArea').classList.add('hidden');
  document.getElementById('btnContinuarHorario').disabled = true;
  atualizarSidebar();
  renderizarCalendario();
}

async function renderizarCalendario() {
  document.getElementById('mesLabel').textContent = `${MESES[state.mesAtual-1]} ${state.anoAtual}`;
  const grid = document.getElementById('calGrid');
  grid.innerHTML = `<div class="col-span-7 flex justify-center py-4"><div class="spinner"></div></div>`;
  const profId = state.profissionalId || getQualquerProfissionalId();
  const res  = await fetch(`${BASE}/api/dias-disponiveis.php?profissional_id=${profId}&ano=${state.anoAtual}&mes=${state.mesAtual}&servico_id=${state.servicoId}`);
  const data = await res.json();
  const dias  = data.dias || {};
  const prim  = new Date(state.anoAtual, state.mesAtual-1, 1).getDay();
  const total = new Date(state.anoAtual, state.mesAtual, 0).getDate();
  let html = '';
  for (let i=0; i<prim; i++) html += `<div class="cal-day empty"></div>`;
  for (let d=1; d<=total; d++) {
    const st  = dias[d] || 'passado';
    const sel = state.diaSelecionado===d ? ' selected':'';
    if (st==='disponivel')   html += `<div class="cal-day disponivel${sel}" onclick="selecionarDia(${d},event)">${d}</div>`;
    else if (st==='indisponivel') html += `<div class="cal-day indisponivel">${d}</div>`;
    else                          html += `<div class="cal-day passado">${d}</div>`;
  }
  grid.innerHTML = html;
}

function getQualquerProfissionalId() {
  const cards = document.querySelectorAll('#listaProfissionais .prof-card[data-id]:not([data-id="0"])');
  return cards.length ? parseInt(cards[0].dataset.id) : 1;
}

async function selecionarDia(dia, evt) {
  state.diaSelecionado=dia; state.horaSelecionada=null;
  document.getElementById('btnContinuarHorario').disabled = true;
  document.querySelectorAll('.cal-day.disponivel').forEach(el => el.classList.remove('selected'));
  evt.target.classList.add('selected');
  document.getElementById('horariosArea').classList.remove('hidden');
  const dataStr = `${state.anoAtual}-${String(state.mesAtual).padStart(2,'0')}-${String(dia).padStart(2,'0')}`;
  document.getElementById('horariosTitle').textContent = `Horários — ${dia}/${String(state.mesAtual).padStart(2,'0')}`;
  const grid=document.getElementById('horariosGrid'), loading=document.getElementById('horariosLoading');
  grid.innerHTML=''; loading.classList.remove('hidden');
  const profId = state.profissionalId || getQualquerProfissionalId();
  const res  = await fetch(`${BASE}/api/horarios.php?profissional_id=${profId}&data=${dataStr}&servico_id=${state.servicoId}`);
  const data = await res.json();
  loading.classList.add('hidden');
  const slots = data.slots || [];
  if (!slots.length) { grid.innerHTML='<p class="col-span-3 text-sm text-center py-4" style="color:#c4a0b4">Nenhum horário disponível.</p>'; return; }
  grid.innerHTML = slots.map(s =>
    s.disponivel
      ? `<div class="time-slot" onclick="selecionarHorario(this,'${s.hora}')">${s.hora}</div>`
      : `<div class="time-slot ocupado">${s.hora}</div>`
  ).join('');
  atualizarSidebar();
}

function selecionarHorario(el, hora) {
  document.querySelectorAll('.time-slot').forEach(s => s.classList.remove('selected'));
  el.classList.add('selected');
  state.horaSelecionada = hora;
  document.getElementById('btnContinuarHorario').disabled = false;
  atualizarSidebar();
}

/* ── Passo 4 ─────────────────────────────────── */
function preencherResumo() {
  const dataFmt = `${String(state.diaSelecionado).padStart(2,'0')}/${String(state.mesAtual).padStart(2,'0')}/${state.anoAtual}`;
  document.getElementById('resumoServico').textContent     = state.servicoNome;
  document.getElementById('resumoProfissional').textContent= state.profissionalNome;
  document.getElementById('resumoDataHora').textContent    = `${dataFmt} às ${state.horaSelecionada}`;
}

function toggleCampoSenha() {
  const campo = document.getElementById('campoCriarConta');
  document.getElementById('checkCriarConta').checked ? campo.classList.remove('hidden') : campo.classList.add('hidden');
}

async function confirmarAgendamento() {
  const nome     = document.getElementById('inputNome').value.trim();
  const whatsapp = document.getElementById('inputWhatsapp').value.trim();
  const email    = document.getElementById('inputEmail').value.trim();
  const obs      = document.getElementById('inputObs')?.value.trim() || '';
  const erroEl   = document.getElementById('erroStep4');
  if (!nome || !whatsapp) {
    erroEl.textContent = 'Preencha nome e WhatsApp para continuar.';
    erroEl.classList.remove('hidden'); return;
  }
  erroEl.classList.add('hidden');
  const btn = document.getElementById('btnConfirmar');
  btn.disabled=true; btn.textContent='Confirmando...';
  const profId = state.profissionalId || getQualquerProfissionalId();
  const data   = `${state.anoAtual}-${String(state.mesAtual).padStart(2,'0')}-${String(state.diaSelecionado).padStart(2,'0')}`;
  const fd = new FormData();
  fd.append('profissional_id',profId); fd.append('servico_id',state.servicoId);
  fd.append('data',data); fd.append('hora',state.horaSelecionada);
  fd.append('nome',nome); fd.append('whatsapp',whatsapp);
  fd.append('email',email); fd.append('observacoes',obs);
  const cc = document.getElementById('checkCriarConta');
  if (cc?.checked){ fd.append('criar_conta','1'); fd.append('senha',document.getElementById('inputSenha')?.value||''); }
  try {
    const res  = await fetch(`${BASE}/api/agendar.php`, {method:'POST',body:fd});
    const resp = await res.json();
    if (!res.ok || resp.erro || resp.erros) {
      erroEl.textContent = resp.erro||(resp.erros||[]).join(' ');
      erroEl.classList.remove('hidden');
      btn.disabled=false; btn.textContent='Confirmar Agendamento 🌸'; return;
    }
    const r = resp.resumo;
    document.getElementById('sucesso_servico').textContent     = r.servico;
    document.getElementById('sucesso_profissional').textContent= r.profissional;
    document.getElementById('sucesso_datahora').textContent    = r.data_hora;
    document.getElementById('sucesso_nome').textContent        = r.nome;
    document.getElementById('btnGcal').href    = resp.gcal_link;
    document.getElementById('btnWppLoja').href = resp.wpp_loja_link;
    irParaPasso(5);
  } catch {
    erroEl.textContent='Erro de conexão. Tente novamente.';
    erroEl.classList.remove('hidden');
    btn.disabled=false; btn.textContent='Confirmar Agendamento 🌸';
  }
}

/* ── Novo agendamento ────────────────────────── */
function novoAgendamento() { resetarModal(); }

/* ── Init ────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('progressArea').style.opacity = '0';
  document.getElementById('mobileSummary').style.display = 'none';
});
</script>

</body>
</html>