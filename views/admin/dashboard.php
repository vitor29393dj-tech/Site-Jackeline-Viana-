<?php
/**
 * views/admin/dashboard.php
 * Shell SPA do painel administrativo com padrão de cores do Atelier.
 */
declare(strict_types=1);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../controllers/AutenticacaoController.php';

AutenticacaoController::exigirAutenticacao('admin');
$nomeAdmin = $_SESSION['user_nome'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Painel Admin · <?= htmlspecialchars(LOJA_NOME) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet"/>
  <style>
    :root { --rosa:#e91e8c; --magenta:#c2186e; --creme:#fdf6f0; }
    * { box-sizing: border-box; }
    body { font-family:'DM Sans',sans-serif; background:#f8fafc; margin:0; }

    /* RESTAURAÇÃO: Sidebar com degradê rosa original do Atelier */
    #sidebar {
      width:220px; flex-shrink:0;
      background: linear-gradient(180deg, #e91e8c 0%, #c2186e 100%);
      min-height:100vh; display:flex; flex-direction:column;
      position:fixed; top:0; left:0; bottom:0; z-index:40;
      transition:transform 0.3s ease;
    }
    .sidebar-logo { padding:20px 18px 16px; border-bottom:1px solid rgba(255,255,255,0.2); }
    
    /* RESTAURAÇÃO: Cores dos itens do menu para combinar com o fundo rosa */
    .nav-item {
      display:flex; align-items:center; gap:11px; padding:10px 16px;
      margin:2px 10px; border-radius:10px; cursor:pointer;
      color:rgba(255,255,255,0.75); font-size:0.85rem; font-weight:500;
      text-decoration:none; transition:all 0.2s ease;
      border:none; background:transparent; width:calc(100% - 20px); text-align:left;
    }
    .nav-item:hover { background:rgba(255,255,255,0.12); color:white; }
    .nav-item.active { background:rgba(255,255,255,0.2); color:white; font-weight:600; box-shadow:inset 0 0 0 1px rgba(255,255,255,0.25); }
    .nav-item .icon { font-size:1rem; width:20px; text-align:center; flex-shrink:0; }
    .nav-sep { height:1px; background:rgba(255,255,255,0.15); margin:8px 16px; }
    .nav-section { font-size:.65rem; text-transform:uppercase; letter-spacing:.1em; color:rgba(255,255,255,0.5); padding:8px 16px 2px; font-weight:600; }

    #mainArea { margin-left:220px; min-height:100vh; display:flex; flex-direction:column; transition:margin-left 0.3s; }

    #topbar {
      background:white; border-bottom:1px solid #f0e4ec;
      padding:0 24px; height:58px; display:flex; align-items:center;
      justify-content:space-between; position:sticky; top:0; z-index:30;
      box-shadow:0 1px 8px rgba(0,0,0,0.04);
    }

    #conteudo-painel { flex:1; padding:24px; opacity:1; transition:opacity 0.2s ease; }
    #conteudo-painel.fade-out { opacity:0; }
    #conteudo-painel.fade-in  { animation:fadeIn 0.28s ease forwards; }
    @keyframes fadeIn { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:translateY(0)} }
    @keyframes spin   { to{transform:rotate(360deg)} }

    #loadingBar {
      position:fixed; top:0; left:220px; right:0; height:3px; z-index:100;
      background:linear-gradient(90deg,var(--rosa),var(--magenta),var(--rosa));
      background-size:200% 100%; animation:loadSlide 1.2s linear infinite; display:none;
    }
    #loadingBar.visible { display:block; }
    @keyframes loadSlide { 0%{background-position:200% 0} 100%{background-position:0 0} }

    #toast {
      position:fixed; bottom:28px; right:28px; z-index:200;
      background:#c2186e; color:white; padding:12px 20px; border-radius:12px;
      font-size:0.85rem; font-weight:500; box-shadow:0 8px 24px rgba(194,24,110,0.2);
      transform:translateY(80px); opacity:0; transition:all 0.3s ease; pointer-events:none;
    }
    #toast.show { transform:translateY(0); opacity:1; }
    #toast.success { border-left:4px solid #4ade80; }
    #toast.error   { border-left:4px solid #f472b6; }

    /* Estilos globais das tabelas e abas */
    .painel-titulo { font-family:'Playfair Display',serif; font-size:1.35rem; font-weight:700; color:#1a0a12; margin-bottom:4px; }
    .painel-sub    { font-size:0.82rem; color:#b0809a; margin-bottom:20px; }
    .card-painel   { background:white; border-radius:16px; box-shadow:0 1px 8px rgba(0,0,0,0.06); border:1px solid #f0e4ec; }
    .badge { display:inline-flex; align-items:center; font-size:0.72rem; font-weight:600; padding:3px 10px; border-radius:20px; }
    .badge-pendente   { background:#fef3c7; color:#92400e; }
    .badge-confirmado { background:#dcfce7; color:#166534; }
    .badge-concluido  { background:#dbeafe; color:#1e40af; }
    .badge-cancelado  { background:#fee2e2; color:#991b1b; }
    .badge-ativo      { background:#dcfce7; color:#166534; }
    .badge-inativo    { background:#fee2e2; color:#991b1b; }
    
    .btn-rosa-sm { background:linear-gradient(135deg,var(--rosa),var(--magenta)); color:white; font-size:0.8rem; font-weight:600; padding:8px 18px; border-radius:20px; border:none; cursor:pointer; transition:all 0.2s; white-space:nowrap; }
    .btn-rosa-sm:hover { opacity:0.88; transform:translateY(-1px); }
    .btn-outline-sm { background:white; color:var(--rosa); border:1.5px solid var(--rosa); font-size:0.8rem; font-weight:600; padding:7px 16px; border-radius:20px; cursor:pointer; transition:all 0.2s; }
    .input-admin { border:1.5px solid #f0e4ec; border-radius:10px; padding:9px 14px; font-family:'DM Sans',sans-serif; font-size:0.85rem; outline:none; transition:border-color 0.2s; background:white; }
    .input-admin:focus { border-color:var(--rosa); box-shadow:0 0 0 3px rgba(233,30,140,0.08); }
    table { width:100%; border-collapse:collapse; }
    th { font-size:0.7rem; text-transform:uppercase; letter-spacing:.06em; color:#b0809a; font-weight:600; padding:10px 16px; text-align:left; border-bottom:1px solid #f5eef3; }
    td { padding:11px 16px; font-size:0.83rem; color:#3d2030; border-bottom:1px solid #faf5f8; }
    tr:last-child td { border-bottom:none; }
    tr:hover td { background:#fdf8fc; }

    #sidebarOverlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:39; }

    @media (max-width:768px) {
      #sidebar { transform:translateX(-220px); }
      #sidebar.mobile-open { transform:translateX(0); }
      #sidebarOverlay.visible { display:block; }
      #mainArea { margin-left:0; }
      #loadingBar { left:0; }
      #conteudo-painel { padding:16px; }
    }
  </style>
</head>
<body>

<div id="loadingBar"></div>
<div id="toast"></div>
<div id="sidebarOverlay" onclick="fecharSidebarMobile()"></div>

<nav id="sidebar">
  <div class="sidebar-logo">
    <div class="flex items-center gap-2.5">
      <div class="w-8 h-8 rounded-lg flex items-center justify-center text-base bg-white/20 text-white">✂️</div>
      <div>
        <p class="text-white font-bold text-sm leading-tight" style="font-family:'Playfair Display',serif"><?= htmlspecialchars(LOJA_NOME) ?></p>
        <p class="text-pink-200 text-xs opacity-80">Painel Admin</p>
      </div>
    </div>
  </div>

  <div class="flex-1 py-3 overflow-y-auto">
    <p class="nav-section">Principal</p>
    <button class="nav-item active" data-aba="agenda"        data-titulo="Agenda Geral"    onclick="carregarAba(this)"><span class="icon">📅</span> Agenda Geral</button>
    <div class="nav-sep"></div>
    <p class="nav-section">Cadastros</p>
    <button class="nav-item"        data-aba="servicos"      data-titulo="Serviços"        onclick="carregarAba(this)"><span class="icon">✂️</span> Serviços</button>
    <button class="nav-item"        data-aba="profissionais" data-titulo="Profissionais"   onclick="carregarAba(this)"><span class="icon">👗</span> Profissionais</button>
    <button class="nav-item"        data-aba="clientes"      data-titulo="Clientes"        onclick="carregarAba(this)"><span class="icon">👤</span> Clientes</button>
    <div class="nav-sep"></div>
    <p class="nav-section">Configurações</p>
    <button class="nav-item"        data-aba="horarios"      data-titulo="Horários"        onclick="carregarAba(this)"><span class="icon">🕐</span> Horários</button>
    <button class="nav-item"        data-aba="mensagens"     data-titulo="Mensagens Auto." onclick="carregarAba(this)"><span class="icon">💬</span> Mensagens</button>
    <div class="nav-sep"></div>
    <a href="<?= BASE_URL ?>/controllers/logica_logout.php" class="nav-item"><span class="icon">🚪</span> Sair</a>
  </div>

  <div class="p-4" style="border-top:1px solid rgba(255,255,255,0.15)">
    <div class="flex items-center gap-2.5">
      <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold text-pink-600 bg-white flex-shrink-0"><?= mb_strtoupper(mb_substr($nomeAdmin,0,1)) ?></div>
      <div class="min-w-0">
        <p class="text-white text-xs font-medium truncate"><?= htmlspecialchars($nomeAdmin) ?></p>
        <p class="text-pink-200 text-xs opacity-70">Administrador</p>
      </div>
    </div>
  </div>
</nav>

<div id="mainArea">
  <header id="topbar">
    <div class="flex items-center gap-3">
      <button onclick="toggleSidebarMobile()" class="md:hidden w-8 h-8 rounded-lg flex items-center justify-center hover:bg-pink-50 transition" style="color:var(--rosa)">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
      </button>
      <div>
        <h1 id="topbarTitulo" class="font-bold text-gray-800 text-sm" style="font-family:'Playfair Display',serif">Agenda Geral</h1>
        <p id="topbarData" class="text-xs" style="color:#b0809a"><?= date('d/m/Y') ?></p>
      </div>
    </div>
    <div class="flex items-center gap-2">
      <div id="topbarActions"></div>
      <a href="<?= BASE_URL ?>/" target="_blank" class="text-xs flex items-center gap-1.5 px-3 py-1.5 rounded-full hover:bg-pink-50 transition" style="color:var(--rosa)">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
        Ver site
      </a>
    </div>
  </header>

  <div id="conteudo-painel">
    <div style="display:flex;justify-content:center;padding:80px 0">
      <div style="width:32px;height:32px;border:3px solid #fce7f3;border-top-color:var(--rosa);border-radius:50%;animation:spin 0.7s linear infinite"></div>
    </div>
  </div>
</div>

<script>
const BASE = '<?= BASE_URL ?>';
let abaAtual = null;

async function carregarAba(btn, abaForcada) {
  const aba = abaForcada || btn?.dataset?.aba;
  if (!aba || aba === abaAtual) return;

  document.querySelectorAll('.nav-item[data-aba]').forEach(el => el.classList.remove('active'));
  const btnAtivo = document.querySelector(`[data-aba="${aba}"]`);
  if (btnAtivo) {
    btnAtivo.classList.add('active');
    document.getElementById('topbarTitulo').textContent = btnAtivo.dataset.titulo || aba;
  }
  document.getElementById('topbarActions').innerHTML = '';

  const painel = document.getElementById('conteudo-painel');
  painel.classList.remove('fade-in');
  painel.classList.add('fade-out');
  document.getElementById('loadingBar').classList.add('visible');
  abaAtual = aba;

  try {
    const res  = await fetch(`${BASE}/views/admin/abas/${aba}.php`, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const html = await res.text();

    painel.innerHTML = html;
    painel.querySelectorAll('script').forEach(s => {
      const n = document.createElement('script');
      n.textContent = s.textContent;
      document.body.appendChild(n);
      n.remove();
    });
    painel.classList.remove('fade-out');
    painel.classList.add('fade-in');
    history.pushState({ aba }, '', `?aba=${aba}`);
    fecharSidebarMobile();
  } catch (err) {
    painel.innerHTML = `<div style="text-align:center;padding:80px 20px">
      <div style="font-size:3rem;margin-bottom:16px">⚠️</div>
      <p style="font-weight:600;color:#3d2030;margin-bottom:8px">Erro ao carregar "${aba}"</p>
      <p style="font-size:0.83rem;color:#b0809a;margin-bottom:20px">${err.message}</p>
      <button class="btn-rosa-sm" onclick="carregarAba(null,'${aba}')">Tentar novamente</button>
    </div>`;
    painel.classList.remove('fade-out');
    painel.classList.add('fade-in');
    mostrarToast('Erro ao carregar o conteúdo.', 'error');
  } finally {
    document.getElementById('loadingBar').classList.remove('visible');
  }
}

window.addEventListener('popstate', e => {
  const aba = e.state?.aba || 'agenda';
  carregarAba(null, aba);
});

function mostrarToast(msg, tipo = 'success') {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.className   = `show ${tipo}`;
  setTimeout(() => { t.className = ''; }, 3200);
}

function toggleSidebarMobile() {
  document.getElementById('sidebar').classList.toggle('mobile-open');
  document.getElementById('sidebarOverlay').classList.toggle('visible');
}
function fecharSidebarMobile() {
  document.getElementById('sidebar').classList.remove('mobile-open');
  document.getElementById('sidebarOverlay').classList.remove('visible');
}

document.addEventListener('DOMContentLoaded', () => {
  const aba = new URLSearchParams(location.search).get('aba') || 'agenda';
  carregarAba(null, aba);
});
</script>
</body>
</html>