<?php
/**
 * views/client/agendamento.php
 * Tela de agendamento de horários para o cliente.
 */
declare(strict_types=1);

// Importa o arquivo de configuração e ativa as regras de sessão/buffer antes de renderizar o HTML
require_once __DIR__ . '/../../config/config.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Agendar Horário · Atelier de Costura</title>

  <!-- Tailwind CSS -->
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

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet" />

  <style>
    /* ── Variáveis de cor ── */
    :root {
      --rosa:    #e91e8c;
      --magenta: #c2186e;
      --creme:   #fdf6f0;
    }

    body { font-family: 'DM Sans', sans-serif; background: var(--creme); }

    /* ── Header gradiente ── */
    .header-gradient {
      background: linear-gradient(135deg, var(--rosa) 0%, var(--magenta) 100%);
    }

    /* ── Progress bar ── */
    .progress-bar {
      height: 4px;
      background: rgba(255,255,255,0.3);
      border-radius: 2px;
      overflow: hidden;
    }
    .progress-fill {
      height: 100%;
      background: white;
      border-radius: 2px;
      transition: width 0.4s ease;
    }

    /* ── Step dots ── */
    .step-dot {
      width: 8px; height: 8px;
      border-radius: 50%;
      background: rgba(255,255,255,0.4);
      transition: all 0.3s ease;
    }
    .step-dot.active {
      width: 24px; border-radius: 4px;
      background: white;
    }
    .step-dot.done {
      background: rgba(255,255,255,0.85);
    }

    /* ── Card de serviço ── */
    .service-card {
      transition: all 0.2s ease;
      border: 2px solid transparent;
      cursor: pointer;
    }
    .service-card:hover  { border-color: var(--rosa); box-shadow: 0 4px 20px rgba(233,30,140,0.15); }
    .service-card.selected { border-color: var(--rosa); background: #fdf2f8; }

    /* ── Card de profissional ── */
    .prof-card {
      border: 2px solid #f3e8ff;
      cursor: pointer;
      transition: all 0.2s ease;
    }
    .prof-card:hover  { border-color: var(--rosa); transform: translateY(-2px); }
    .prof-card.selected { border-color: var(--rosa); background: #fdf2f8; box-shadow: 0 4px 20px rgba(233,30,140,0.2); }

    /* ── Calendário ── */
    .cal-day {
      aspect-ratio: 1;
      border-radius: 8px;
      cursor: pointer;
      font-size: 0.8rem;
      font-weight: 500;
      transition: all 0.2s ease;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .cal-day.disponivel { background: #dcfce7; color: #166534; }
    .cal-day.disponivel:hover { background: var(--rosa); color: white; transform: scale(1.05); }
    .cal-day.disponivel.selected { background: var(--rosa); color: white; box-shadow: 0 4px 12px rgba(233,30,140,0.4); }
    .cal-day.indisponivel { background: #f3f4f6; color: #9ca3af; cursor: not-allowed; }
    .cal-day.passado { background: #f9fafb; color: #d1d5db; cursor: not-allowed; }
    .cal-day.empty { background: transparent; cursor: default; }

    /* ── Slot de horário ── */
    .time-slot {
      border: 2px solid #fce7f3;
      border-radius: 10px;
      padding: 10px 8px;
      text-align: center;
      cursor: pointer;
      font-weight: 500;
      font-size: 0.85rem;
      transition: all 0.2s ease;
    }
    .time-slot:hover { border-color: var(--rosa); background: #fdf2f8; }
    .time-slot.selected {
      background: var(--rosa);
      border-color: var(--rosa);
      color: white;
      box-shadow: 0 4px 12px rgba(233,30,140,0.4);
    }
    .time-slot.ocupado { background: #f3f4f6; color: #9ca3af; cursor: not-allowed; border-color: #e5e7eb; }

    /* ── Botão principal ── */
    .btn-rosa {
      background: linear-gradient(135deg, var(--rosa), var(--magenta));
      color: white;
      font-weight: 600;
      padding: 14px 28px;
      border-radius: 50px;
      border: none;
      cursor: pointer;
      width: 100%;
      font-size: 1rem;
      transition: all 0.2s ease;
      box-shadow: 0 4px 15px rgba(233,30,140,0.35);
    }
    .btn-rosa:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(233,30,140,0.45); }
    .btn-rosa:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

    .btn-outline {
      background: white;
      color: var(--rosa);
      border: 2px solid var(--rosa);
      font-weight: 600;
      padding: 12px 24px;
      border-radius: 50px;
      cursor: pointer;
      transition: all 0.2s ease;
    }
    .btn-outline:hover { background: #fdf2f8; }

    /* ── Input ── */
    .input-field {
      border: 2px solid #f3e8ee;
      border-radius: 12px;
      padding: 12px 16px;
      width: 100%;
      font-family: 'DM Sans', sans-serif;
      font-size: 0.95rem;
      outline: none;
      transition: border-color 0.2s ease;
      background: white;
    }
    .input-field:focus { border-color: var(--rosa); box-shadow: 0 0 0 3px rgba(233,30,140,0.1); }

    /* ── Animação de entrada ── */
    .step-panel { animation: fadeSlideIn 0.35s ease forwards; }
    @keyframes fadeSlideIn {
      from { opacity: 0; transform: translateX(20px); }
      to   { opacity: 1; transform: translateX(0); }
    }

    /* ── Loading spinner ── */
    .spinner {
      width: 28px; height: 28px;
      border: 3px solid #fce7f3;
      border-top-color: var(--rosa);
      border-radius: 50%;
      animation: spin 0.7s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* ── Sucesso ── */
    .success-circle {
      width: 80px; height: 80px;
      background: linear-gradient(135deg, var(--rosa), var(--magenta));
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto;
      box-shadow: 0 8px 25px rgba(233,30,140,0.4);
    }
  </style>
</head>

<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Servico.php';
require_once __DIR__ . '/../../models/Profissional.php';
require_once __DIR__ . '/../../controllers/AutenticacaoController.php';

$servicos      = Servico::listarAtivos();
$profissionais = Profissional::listarTodos();
$clienteLogado = AutenticacaoController::estaLogado();
$clienteNome   = $_SESSION['user_nome'] ?? '';
?>

<body class="min-h-screen" style="background: var(--creme);">

<!-- ══════════════════════════════════════════
     HEADER FIXO
══════════════════════════════════════════ -->
<header class="header-gradient text-white sticky top-0 z-50 shadow-lg">
  <div class="max-w-lg mx-auto px-4 py-4">

    <!-- Topo: logo + link área do cliente -->
    <div class="flex items-center justify-between mb-3">
      <div>
        <h1 class="font-display text-xl font-bold tracking-wide">Atelier de Costura</h1>
        <p class="text-xs opacity-80 tracking-widest uppercase">Agende seu horário online</p>
      </div>
      <?php if ($clienteLogado): ?>
        <a href="<?php echo BASE_URL; ?>/views/client/area-cliente.php"
           class="text-xs bg-white/20 hover:bg-white/30 px-3 py-1.5 rounded-full transition">
          👗 Minha Área
        </a>
      <?php else: ?>
        <a href="<?php echo BASE_URL; ?>/views/login.php"
           class="text-xs bg-white/20 hover:bg-white/30 px-3 py-1.5 rounded-full transition">
          Entrar
        </a>
      <?php endif; ?>
    </div>

    <!-- Barra de progresso -->
    <div class="progress-bar mb-2">
      <div class="progress-fill" id="progressFill" style="width: 20%"></div>
    </div>

    <!-- Dots dos passos -->
    <div class="flex items-center gap-2">
      <div class="step-dot active" id="dot1"></div>
      <div class="step-dot" id="dot2"></div>
      <div class="step-dot" id="dot3"></div>
      <div class="step-dot" id="dot4"></div>
      <div class="step-dot" id="dot5"></div>
      <span class="text-xs opacity-70 ml-auto" id="stepLabel">Passo 1 de 5 — Serviço</span>
    </div>

  </div>
</header>


<!-- ══════════════════════════════════════════
     ÁREA DE CONTEÚDO
══════════════════════════════════════════ -->
<main class="max-w-lg mx-auto px-4 py-6 pb-32">


  <!-- ─────────────────────────────────────
       PASSO 1 — SELEÇÃO DE SERVIÇO
  ───────────────────────────────────── -->
  <section id="step1" class="step-panel">
    <h2 class="font-display text-2xl font-bold text-gray-800 mb-1">Qual serviço você precisa?</h2>
    <p class="text-gray-500 text-sm mb-6">Selecione uma opção para continuar</p>

    <div class="space-y-3" id="listaServicos">
      <?php foreach ($servicos as $s): ?>
      <div class="service-card bg-white rounded-2xl p-4 flex items-center gap-4 shadow-sm"
           data-id="<?= $s->getId() ?>"
           data-nome="<?= htmlspecialchars($s->getNome()) ?>"
           data-duracao="<?= $s->getDuracaoMin() ?>"
           onclick="selecionarServico(this)">
        <!-- Avatar circular com ícone -->
        <div class="w-12 h-12 rounded-full flex-shrink-0 flex items-center justify-center text-xl"
             style="background: linear-gradient(135deg, #fce7f3, #fdf2f8);">
          <?php
          $icones = ['Noiva' => '👰', 'noiva' => '👰', 'Debutante' => '🌸', 'debutante' => '🌸',
                     'Festa' => '✨', 'festa' => '✨', 'Masculino' => '🤵', 'masculino' => '🤵',
                     'Infantil' => '🎀', 'infantil' => '🎀', 'Madrinha' => '💐', 'Recepção' => '🌹',
                     'Civil' => '💍', 'Mãe' => '👩', 'Pai' => '👨'];
          $icon = '✂️';
          foreach ($icones as $key => $val) {
              if (str_contains($s->getNome(), $key)) { $icon = $val; break; }
          }
          echo $icon;
          ?>
        </div>
        <div class="flex-1 min-w-0">
          <p class="font-semibold text-gray-800 text-sm leading-tight">
            <?= htmlspecialchars($s->getNome()) ?>
          </p>
          <?php if ($s->getDescricao()): ?>
          <p class="text-gray-400 text-xs mt-0.5 truncate">
            <?= htmlspecialchars($s->getDescricao()) ?>
          </p>
          <?php endif; ?>
          <p class="text-xs mt-1" style="color: var(--rosa);">
            ⏱ <?= $s->getDuracaoMin() ?> min
            <?php if ($s->getPreco() > 0): ?>
              · R$ <?= number_format($s->getPreco(), 2, ',', '.') ?>
            <?php endif; ?>
          </p>
        </div>
        <!-- Check -->
        <div class="w-6 h-6 rounded-full border-2 flex-shrink-0 flex items-center justify-center check-icon"
             style="border-color: #e5e7eb;">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
          </svg>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </section>


  <!-- ─────────────────────────────────────
       PASSO 2 — SELEÇÃO DE PROFISSIONAL
  ───────────────────────────────────── -->
  <section id="step2" class="step-panel hidden">
    <button onclick="voltarPasso(1)" class="flex items-center gap-1 text-sm mb-4" style="color: var(--rosa);">
      ← Voltar
    </button>
    <h2 class="font-display text-2xl font-bold text-gray-800 mb-1">Escolha sua atendente</h2>
    <p class="text-gray-500 text-sm mb-6">Ou deixe o sistema escolher automaticamente</p>

    <div class="grid grid-cols-2 gap-3 mb-4" id="listaProfissionais">

      <!-- Opção automática -->
      <div class="prof-card bg-white rounded-2xl p-5 text-center shadow-sm selected"
           data-id="0" data-nome="Qualquer atendente" onclick="selecionarProfissional(this)">
        <div class="w-14 h-14 rounded-full mx-auto mb-3 flex items-center justify-center text-2xl"
             style="background: linear-gradient(135deg, #fce7f3, #fdf2f8);">🌸</div>
        <p class="font-semibold text-gray-800 text-sm">Automático</p>
        <p class="text-xs text-gray-400 mt-1">Próxima disponível</p>
      </div>

      <?php foreach ($profissionais as $p): ?>
      <div class="prof-card bg-white rounded-2xl p-5 text-center shadow-sm"
           data-id="<?= $p->getId() ?>"
           data-nome="<?= htmlspecialchars($p->getApelido()) ?>"
           onclick="selecionarProfissional(this)">
        <?php if ($p->getFotoUrl()): ?>
          <img src="<?= htmlspecialchars($p->getFotoUrl()) ?>"
               class="w-14 h-14 rounded-full mx-auto mb-3 object-cover" />
        <?php else: ?>
          <div class="w-14 h-14 rounded-full mx-auto mb-3 flex items-center justify-center text-xl font-bold text-white"
               style="background: <?= htmlspecialchars($p->getCorAgenda()) ?>;">
            <?= mb_strtoupper(mb_substr($p->getApelido(), 0, 1)) ?>
          </div>
        <?php endif; ?>
        <p class="font-semibold text-gray-800 text-sm"><?= htmlspecialchars($p->getApelido()) ?></p>
        <p class="text-xs text-gray-400 mt-1">Disponível</p>
      </div>
      <?php endforeach; ?>
    </div>

    <button class="btn-rosa" onclick="irParaPasso(3)">Continuar →</button>
  </section>


  <!-- ─────────────────────────────────────
       PASSO 3 — CALENDÁRIO E HORÁRIO
  ───────────────────────────────────── -->
  <section id="step3" class="step-panel hidden">
    <button onclick="voltarPasso(2)" class="flex items-center gap-1 text-sm mb-4" style="color: var(--rosa);">
      ← Voltar
    </button>
    <h2 class="font-display text-2xl font-bold text-gray-800 mb-1">Escolha a data</h2>
    <p class="text-gray-500 text-sm mb-5">Dias em verde possuem horários disponíveis</p>

    <!-- Navegação do mês -->
    <div class="flex items-center justify-between mb-4">
      <button onclick="mudarMes(-1)" class="w-9 h-9 rounded-full bg-white shadow flex items-center justify-center text-gray-600 hover:bg-pink-50 transition">‹</button>
      <span class="font-semibold text-gray-700 text-sm" id="mesLabel">—</span>
      <button onclick="mudarMes(1)"  class="w-9 h-9 rounded-full bg-white shadow flex items-center justify-center text-gray-600 hover:bg-pink-50 transition">›</button>
    </div>

    <!-- Dias da semana -->
    <div class="grid grid-cols-7 gap-1 mb-2 text-center text-xs text-gray-400 font-medium">
      <span>Dom</span><span>Seg</span><span>Ter</span><span>Qua</span><span>Qui</span><span>Sex</span><span>Sáb</span>
    </div>

    <!-- Grid de dias -->
    <div id="calGrid" class="grid grid-cols-7 gap-1 mb-6">
      <div class="flex justify-center py-4"><div class="spinner"></div></div>
    </div>

    <!-- Legenda -->
    <div class="flex items-center gap-4 text-xs text-gray-500 mb-6">
      <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-green-100 inline-block"></span> Disponível</span>
      <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-gray-200 inline-block"></span> Indisponível</span>
    </div>

    <!-- Horários disponíveis -->
    <div id="horariosArea" class="hidden">
      <h3 class="font-semibold text-gray-700 mb-3" id="horariosTitle">Horários disponíveis</h3>
      <div id="horariosLoading" class="hidden flex justify-center py-4">
        <div class="spinner"></div>
      </div>
      <div id="horariosGrid" class="grid grid-cols-3 gap-2 mb-6"></div>
    </div>

    <button class="btn-rosa" id="btnContinuarHorario" disabled onclick="irParaPasso(4)">
      Continuar →
    </button>
  </section>


  <!-- ─────────────────────────────────────
       PASSO 4 — INFORMAÇÕES PESSOAIS
  ───────────────────────────────────── -->
  <section id="step4" class="step-panel hidden">
    <button onclick="voltarPasso(3)" class="flex items-center gap-1 text-sm mb-4" style="color: var(--rosa);">
      ← Voltar
    </button>
    <h2 class="font-display text-2xl font-bold text-gray-800 mb-1">Suas informações</h2>
    <p class="text-gray-500 text-sm mb-6">Preencha para confirmar seu agendamento</p>

    <!-- Resumo escolhido -->
    <div class="bg-white rounded-2xl p-4 mb-6 border border-pink-100">
      <p class="text-xs text-gray-400 uppercase tracking-wide mb-2">Resumo do agendamento</p>
      <div class="space-y-1.5 text-sm">
        <div class="flex gap-2"><span>✂️</span><span class="font-medium" id="resumoServico">—</span></div>
        <div class="flex gap-2"><span>👗</span><span class="text-gray-600" id="resumoProfissional">—</span></div>
        <div class="flex gap-2"><span>📅</span><span class="text-gray-600" id="resumoDataHora">—</span></div>
      </div>
    </div>

    <!-- Formulário -->
    <div class="space-y-4">
      <div>
        <label class="text-sm font-medium text-gray-700 block mb-1.5">Nome completo *</label>
        <input type="text" id="inputNome" class="input-field" placeholder="Seu nome completo"
               <?= $clienteLogado ? 'value="' . htmlspecialchars($clienteNome) . '"' : '' ?> />
      </div>
      <div>
        <label class="text-sm font-medium text-gray-700 block mb-1.5">WhatsApp *</label>
        <input type="tel" id="inputWhatsapp" class="input-field" placeholder="(00) 00000-0000" />
      </div>
      <div>
        <label class="text-sm font-medium text-gray-700 block mb-1.5">E-mail</label>
        <input type="email" id="inputEmail" class="input-field" placeholder="seu@email.com" />
      </div>

      <!-- Criar conta (opcional) -->
      <?php if (!$clienteLogado): ?>
      <div class="bg-pink-50 rounded-2xl p-4 border border-pink-100">
        <label class="flex items-start gap-3 cursor-pointer">
          <input type="checkbox" id="checkCriarConta" class="mt-0.5 accent-pink-500"
                 onchange="toggleCampoSenha()" />
          <div>
            <p class="text-sm font-medium text-gray-800">Criar minha área do cliente</p>
            <p class="text-xs text-gray-500 mt-0.5">Acesse histórico de agendamentos e catálogo de peças</p>
          </div>
        </label>
        <div id="campoCriarConta" class="hidden mt-3">
          <input type="password" id="inputSenha" class="input-field" placeholder="Crie uma senha" />
        </div>
      </div>
      <?php endif; ?>

      <div>
        <label class="text-sm font-medium text-gray-700 block mb-1.5">Observações (opcional)</label>
        <textarea id="inputObs" class="input-field" rows="2"
                  placeholder="Ex: traje específico, ocasião..."></textarea>
      </div>
    </div>

    <div id="erroStep4" class="hidden mt-4 text-sm text-red-600 bg-red-50 rounded-xl p-3"></div>

    <button class="btn-rosa mt-6" id="btnConfirmar" onclick="confirmarAgendamento()">
      Confirmar Agendamento 🌸
    </button>
  </section>


  <!-- ─────────────────────────────────────
       PASSO 5 — SUCESSO
  ───────────────────────────────────── -->
  <section id="step5" class="step-panel hidden text-center py-8">
    <div class="success-circle mb-5">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-9 h-9 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
      </svg>
    </div>

    <h2 class="font-display text-2xl font-bold text-gray-800 mb-2">Agendamento confirmado!</h2>
    <p class="text-gray-500 text-sm mb-6">Te esperamos no Atelier 🌸</p>

    <!-- Resumo final -->
    <div class="bg-white rounded-2xl p-5 mb-6 text-left shadow-sm border border-pink-100">
      <p class="text-xs text-gray-400 uppercase tracking-wide mb-3">Detalhes do agendamento</p>
      <div class="space-y-2 text-sm">
        <div class="flex items-center gap-2 text-gray-700">
          <span class="text-base">✂️</span>
          <span id="sucesso_servico" class="font-medium">—</span>
        </div>
        <div class="flex items-center gap-2 text-gray-600">
          <span class="text-base">👗</span>
          <span id="sucesso_profissional">—</span>
        </div>
        <div class="flex items-center gap-2 text-gray-600">
          <span class="text-base">📅</span>
          <span id="sucesso_datahora">—</span>
        </div>
        <div class="flex items-center gap-2 text-gray-600">
          <span class="text-base">👤</span>
          <span id="sucesso_nome">—</span>
        </div>
      </div>
    </div>

    <!-- Ações -->
    <div class="space-y-3">
      <a id="btnGcal" href="#" target="_blank"
         class="btn-outline flex items-center justify-center gap-2 py-3">
        <span>📆</span> Adicionar à Agenda do Google
      </a>

      <a id="btnWppLoja" href="#" target="_blank"
         class="btn-rosa flex items-center justify-center gap-2 py-3 no-underline rounded-full">
        <span>💬</span> Confirmar via WhatsApp
      </a>

      <button onclick="novoAgendamento()"
              class="w-full text-sm text-gray-400 py-2 hover:text-gray-600 transition">
        + Fazer novo agendamento
      </button>
    </div>
  </section>


</main><!-- /main -->


<!-- ══════════════════════════════════════════
     JAVASCRIPT — FLUXO DOS 5 PASSOS
══════════════════════════════════════════ -->
<script>
// ─── Estado global do agendamento ────────────────────────────
const state = {
  passoAtual:    1,
  servicoId:     null,
  servicoNome:   '',
  duracaoMin:    60,
  profissionalId: 0,
  profissionalNome: 'Qualquer atendente',
  anoAtual:      new Date().getFullYear(),
  mesAtual:      new Date().getMonth() + 1, // 1-12
  diaSelecionado: null,
  horaSelecionada: null,
  gcalLink:      '',
  wppLojaLink:   '',
};

// Labels dos passos
const STEP_LABELS = [
  '', 'Serviço', 'Atendente', 'Data & Hora', 'Seus Dados', 'Confirmado'
];

// ─── Navegação entre passos ───────────────────────────────────
function irParaPasso(passo) {
  document.getElementById(`step${state.passoAtual}`)?.classList.add('hidden');
  document.getElementById(`step${passo}`)?.classList.remove('hidden');
  state.passoAtual = passo;
  atualizarProgress();
  window.scrollTo({ top: 0, behavior: 'smooth' });

  if (passo === 3) iniciarCalendario();
  if (passo === 4) preencherResumo();
}

function voltarPasso(passo) {
  irParaPasso(passo);
}

function atualizarProgress() {
  const p = state.passoAtual;
  document.getElementById('progressFill').style.width = `${p * 20}%`;
  document.getElementById('stepLabel').textContent = `Passo ${p} de 5 — ${STEP_LABELS[p]}`;

  for (let i = 1; i <= 5; i++) {
    const dot = document.getElementById(`dot${i}`);
    dot.className = 'step-dot' + (i < p ? ' done' : i === p ? ' active' : '');
  }
}

// ─── PASSO 1: Seleção de serviço ─────────────────────────────
function selecionarServico(el) {
  document.querySelectorAll('.service-card').forEach(c => {
    c.classList.remove('selected');
    c.querySelector('.check-icon').style.borderColor = '#e5e7eb';
    c.querySelector('.check-icon svg').classList.add('hidden');
  });

  el.classList.add('selected');
  const icon = el.querySelector('.check-icon');
  icon.style.borderColor = 'var(--rosa)';
  icon.style.background  = 'var(--rosa)';
  icon.querySelector('svg').classList.remove('hidden');
  icon.querySelector('svg').style.color = 'white';

  state.servicoId   = parseInt(el.dataset.id);
  state.servicoNome = el.dataset.nome;
  state.duracaoMin  = parseInt(el.dataset.duracao);

  // Pequeno delay para feedback visual
  setTimeout(() => irParaPasso(2), 200);
}

// ─── PASSO 2: Seleção de profissional ────────────────────────
function selecionarProfissional(el) {
  document.querySelectorAll('.prof-card').forEach(c => c.classList.remove('selected'));
  el.classList.add('selected');
  state.profissionalId   = parseInt(el.dataset.id);
  state.profissionalNome = el.dataset.nome;
}

// ─── PASSO 3: Calendário ─────────────────────────────────────
const MESES = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho',
               'Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];

function iniciarCalendario() {
  renderizarCalendario();
}

function mudarMes(delta) {
  state.mesAtual += delta;
  if (state.mesAtual > 12) { state.mesAtual = 1;  state.anoAtual++; }
  if (state.mesAtual < 1)  { state.mesAtual = 12; state.anoAtual--; }
  state.diaSelecionado  = null;
  state.horaSelecionada = null;
  document.getElementById('horariosArea').classList.add('hidden');
  document.getElementById('btnContinuarHorario').disabled = true;
  renderizarCalendario();
}

async function renderizarCalendario() {
  document.getElementById('mesLabel').textContent =
    `${MESES[state.mesAtual - 1]} ${state.anoAtual}`;

  const grid = document.getElementById('calGrid');
  grid.innerHTML = `<div class="col-span-7 flex justify-center py-4"><div class="spinner"></div></div>`;

  // Busca dias disponíveis
  const profId = state.profissionalId || getQualquerProfissionalId();
  const res  = await fetch(
    `<?php echo BASE_URL; ?>/api/dias-disponiveis.php?profissional_id=${profId}&ano=${state.anoAtual}&mes=${state.mesAtual}&servico_id=${state.servicoId}`
  );
  const data = await res.json();
  const dias  = data.dias || {};

  // Dia da semana do 1º do mês (0=Dom)
  const primeiroDia = new Date(state.anoAtual, state.mesAtual - 1, 1).getDay();
  const totalDias   = new Date(state.anoAtual, state.mesAtual, 0).getDate();

  let html = '';
  // Células vazias antes do dia 1
  for (let i = 0; i < primeiroDia; i++) {
    html += `<div class="cal-day empty"></div>`;
  }
  // Dias do mês
  for (let d = 1; d <= totalDias; d++) {
    const status = dias[d] || 'passado';
    const isSelected = state.diaSelecionado === d ? ' selected' : '';
    if (status === 'disponivel') {
      html += `<div class="cal-day disponivel${isSelected}" onclick="selecionarDia(${d})">${d}</div>`;
    } else if (status === 'indisponivel') {
      html += `<div class="cal-day indisponivel">${d}</div>`;
    } else {
      html += `<div class="cal-day passado">${d}</div>`;
    }
  }

  grid.innerHTML = html;
}

function getQualquerProfissionalId() {
  // Pega o primeiro profissional disponível (fallback quando "Automático" foi selecionado)
  const cards = document.querySelectorAll('#listaProfissionais .prof-card[data-id]:not([data-id="0"])');
  return cards.length ? parseInt(cards[0].dataset.id) : 1;
}

async function selecionarDia(dia) {
  state.diaSelecionado  = dia;
  state.horaSelecionada = null;
  document.getElementById('btnContinuarHorario').disabled = true;

  // Atualiza highlight
  document.querySelectorAll('.cal-day.disponivel').forEach(el => el.classList.remove('selected'));
  event.target.classList.add('selected');

  // Mostra área de horários
  const area = document.getElementById('horariosArea');
  area.classList.remove('hidden');

  const dataStr = `${state.anoAtual}-${String(state.mesAtual).padStart(2,'0')}-${String(dia).padStart(2,'0')}`;
  document.getElementById('horariosTitle').textContent =
    `Horários — ${dia}/${String(state.mesAtual).padStart(2,'0')}`;

  const grid    = document.getElementById('horariosGrid');
  const loading = document.getElementById('horariosLoading');
  grid.innerHTML = '';
  loading.classList.remove('hidden');

  const profId = state.profissionalId || getQualquerProfissionalId();
  const res  = await fetch(
    `<?php echo BASE_URL; ?>/api/horarios.php?profissional_id=${profId}&data=${dataStr}&servico_id=${state.servicoId}`
  );
  const data = await res.json();
  loading.classList.add('hidden');

  const slots = data.slots || [];
  if (!slots.length) {
    grid.innerHTML = '<p class="col-span-3 text-sm text-gray-400 text-center py-4">Nenhum horário disponível neste dia.</p>';
    return;
  }

  grid.innerHTML = slots.map(s => {
    if (s.disponivel) {
      return `<div class="time-slot" onclick="selecionarHorario(this, '${s.hora}')">${s.hora}</div>`;
    } else {
      return `<div class="time-slot ocupado">${s.hora}</div>`;
    }
  }).join('');
}

function selecionarHorario(el, hora) {
  document.querySelectorAll('.time-slot').forEach(s => s.classList.remove('selected'));
  el.classList.add('selected');
  state.horaSelecionada = hora;
  document.getElementById('btnContinuarHorario').disabled = false;
}

// ─── PASSO 4: Resumo e formulário ────────────────────────────
function preencherResumo() {
  const dataFormatada = `${String(state.diaSelecionado).padStart(2,'0')}/${String(state.mesAtual).padStart(2,'0')}/${state.anoAtual}`;
  document.getElementById('resumoServico').textContent      = state.servicoNome;
  document.getElementById('resumoProfissional').textContent = state.profissionalNome;
  document.getElementById('resumoDataHora').textContent     = `${dataFormatada} às ${state.horaSelecionada}`;
}

function toggleCampoSenha() {
  const check  = document.getElementById('checkCriarConta');
  const campo  = document.getElementById('campoCriarConta');
  if (check.checked) campo.classList.remove('hidden');
  else campo.classList.add('hidden');
}

async function confirmarAgendamento() {
  const nome     = document.getElementById('inputNome').value.trim();
  const whatsapp = document.getElementById('inputWhatsapp').value.trim();
  const email    = document.getElementById('inputEmail').value.trim();
  const obs      = document.getElementById('inputObs')?.value.trim() || '';

  const erroEl = document.getElementById('erroStep4');

  if (!nome || !whatsapp) {
    erroEl.textContent = 'Preencha nome e WhatsApp para continuar.';
    erroEl.classList.remove('hidden');
    return;
  }
  erroEl.classList.add('hidden');

  const btn = document.getElementById('btnConfirmar');
  btn.disabled     = true;
  btn.textContent  = 'Confirmando...';

  const profId = state.profissionalId || getQualquerProfissionalId();
  const data   = `${state.anoAtual}-${String(state.mesAtual).padStart(2,'0')}-${String(state.diaSelecionado).padStart(2,'0')}`;

  const formData = new FormData();
  formData.append('profissional_id', profId);
  formData.append('servico_id',      state.servicoId);
  formData.append('data',            data);
  formData.append('hora',            state.horaSelecionada);
  formData.append('nome',            nome);
  formData.append('whatsapp',        whatsapp);
  formData.append('email',           email);
  formData.append('observacoes',     obs);

  const criarConta = document.getElementById('checkCriarConta');
  if (criarConta?.checked) {
    formData.append('criar_conta', '1');
    formData.append('senha', document.getElementById('inputSenha')?.value || '');
  }

  try {
    const res  = await fetch('<?php echo BASE_URL; ?>/api/agendar.php', { method: 'POST', body: formData });
    const resp = await res.json();

    if (!res.ok || resp.erro || resp.erros) {
      erroEl.textContent = resp.erro || (resp.erros || []).join(' ');
      erroEl.classList.remove('hidden');
      btn.disabled    = false;
      btn.textContent = 'Confirmar Agendamento 🌸';
      return;
    }

    // Sucesso!
    state.gcalLink    = resp.gcal_link;
    state.wppLojaLink = resp.wpp_loja_link;

    const r = resp.resumo;
    document.getElementById('sucesso_servico').textContent      = r.servico;
    document.getElementById('sucesso_profissional').textContent = r.profissional;
    document.getElementById('sucesso_datahora').textContent     = r.data_hora;
    document.getElementById('sucesso_nome').textContent         = r.nome;
    document.getElementById('btnGcal').href                     = resp.gcal_link;
    document.getElementById('btnWppLoja').href                  = resp.wpp_loja_link;

    irParaPasso(5);

  } catch (err) {
    erroEl.textContent = 'Erro de conexão. Tente novamente.';
    erroEl.classList.remove('hidden');
    btn.disabled    = false;
    btn.textContent = 'Confirmar Agendamento 🌸';
  }
}

// ─── Novo agendamento ─────────────────────────────────────────
function novoAgendamento() {
  Object.assign(state, {
    passoAtual: 1, servicoId: null, servicoNome: '', duracaoMin: 60,
    profissionalId: 0, profissionalNome: 'Qualquer atendente',
    anoAtual: new Date().getFullYear(), mesAtual: new Date().getMonth() + 1,
    diaSelecionado: null, horaSelecionada: null,
  });
  document.querySelectorAll('[id^="step"]').forEach(el => el.classList.add('hidden'));
  document.getElementById('step1').classList.remove('hidden');
  atualizarProgress();
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ─── Init ─────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  atualizarProgress();
});
</script>

</body>
</html>