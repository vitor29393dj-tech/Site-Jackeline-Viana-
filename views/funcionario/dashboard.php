<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../controllers/DashboardController.php';

$controller = new DashboardController();
$dados      = $controller->dadosFuncionario();

if (isset($dados['erro'])) {
    echo '<div style="padding:40px;text-align:center;color:#e91e8c;font-family:DM Sans,sans-serif">' . htmlspecialchars($dados['erro']) . '</div>';
    exit;
}

$dt         = new DateTime($dados['data_selecionada']);
$diasSemana = ['Domingo','Segunda','Terça','Quarta','Quinta','Sexta','Sábado'];
$diaNome    = $diasSemana[(int)$dt->format('w')];
$dataFmt    = $dt->format('d/m/Y');
$agendamentos = $dados['agendamentos_dia'];
$total      = count($agendamentos);

// Agrupa para KPIs do dia
$pendentes   = count(array_filter($agendamentos, fn($a) => $a['status'] === 'pendente'));
$confirmados = count(array_filter($agendamentos, fn($a) => $a['status'] === 'confirmado'));
$concluidos  = count(array_filter($agendamentos, fn($a) => $a['status'] === 'concluido'));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Minha Agenda · <?= htmlspecialchars(LOJA_NOME) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet"/>

  <style>
    :root { --rosa:#e91e8c; --magenta:#c2186e; --creme:#fdf6f0; }
    *, *::before, *::after { box-sizing: border-box; }
    body { font-family:'DM Sans',sans-serif; background:var(--creme); min-height:100vh; margin:0; }

    /* ── Header ── */
    .hdr {
      background: linear-gradient(135deg, var(--rosa), var(--magenta));
      padding: 0 20px;
      height: 64px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: sticky;
      top: 0;
      z-index: 50;
      box-shadow: 0 4px 20px rgba(233,30,140,.3);
    }
    .hdr-logo { display:flex; align-items:center; gap:10px; }
    .hdr-av {
      width: 36px; height: 36px; border-radius: 50%;
      background: rgba(255,255,255,.22);
      display: flex; align-items:center; justify-content:center;
      font-weight: 800; font-size: .9rem; color: white;
      border: 2px solid rgba(255,255,255,.35);
      flex-shrink: 0;
    }
    .hdr-sair {
      font-size:.75rem; font-weight:600; color:white;
      background:rgba(255,255,255,.18);
      border:1.5px solid rgba(255,255,255,.25);
      padding:6px 16px; border-radius:50px;
      text-decoration:none; transition:all .2s;
    }
    .hdr-sair:hover { background:rgba(255,255,255,.3); }

    /* ── Navegação de data ── */
    .date-nav {
      background: white;
      border-radius: 18px;
      padding: 16px 20px;
      box-shadow: 0 2px 14px rgba(0,0,0,.05);
      border: 1.5px solid #f0e4ec;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .btn-nav {
      width: 36px; height: 36px; border-radius: 50%;
      border: 1.5px solid #f0e4ec; background: white;
      display: flex; align-items: center; justify-content: center;
      cursor: pointer; transition: all .18s;
      color: #b0809a; font-size: .9rem; text-decoration:none;
      flex-shrink: 0;
    }
    .btn-nav:hover { border-color:var(--rosa); color:var(--rosa); background:#fdf0f6; }
    .date-input {
      flex: 1; border: none; outline: none;
      font-family:'DM Sans',sans-serif; font-size:.9rem;
      font-weight: 600; color: #1a0a12;
      text-align: center; cursor: pointer;
      background: transparent;
    }
    .btn-ver {
      background: linear-gradient(135deg,var(--rosa),var(--magenta));
      color:white; border:none; border-radius:50px;
      padding:8px 20px; font-size:.82rem; font-weight:700;
      cursor:pointer; transition:all .2s;
      box-shadow:0 3px 12px rgba(233,30,140,.3);
      font-family:'DM Sans',sans-serif;
      white-space:nowrap;
    }
    .btn-ver:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(233,30,140,.45); }

    /* ── KPIs ── */
    .kpi-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:10px; }
    .kpi {
      background:white; border-radius:14px; padding:12px 14px;
      border:1.5px solid #f0e4ec; border-top-width:3px;
      box-shadow:0 2px 8px rgba(0,0,0,.04);
      text-align:center;
    }
    .kpi-num { font-size:1.5rem; font-weight:800; line-height:1; }
    .kpi-lbl { font-size:.6rem; font-weight:700; text-transform:uppercase; letter-spacing:.08em; color:#b0809a; margin-top:3px; }

    /* ── Card de agendamento ── */
    .ag-card {
      background: white;
      border-radius: 18px;
      border: 1.5px solid #f0e4ec;
      border-left: 4px solid var(--rosa);
      box-shadow: 0 2px 14px rgba(0,0,0,.05);
      overflow: hidden;
      transition: all .2s ease;
    }
    .ag-card:hover { box-shadow:0 6px 24px rgba(233,30,140,.12); transform:translateY(-1px); }

    /* Topo colorido do card */
    .ag-card-top {
      padding: 14px 18px 10px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
    }
    .ag-hora {
      font-size: 1rem; font-weight: 800;
      color: #1a0a12; font-family:'Playfair Display',serif;
      display: flex; align-items: center; gap: 6px;
    }
    .ag-hora-dot {
      width:8px; height:8px; border-radius:50%;
      background:var(--rosa); flex-shrink:0;
      box-shadow:0 0 0 3px rgba(233,30,140,.2);
    }
    .ag-duracao { font-size:.7rem; color:#b0809a; font-weight:500; }

    /* Status badge */
    .status-badge {
      font-size:.65rem; font-weight:700; padding:4px 11px;
      border-radius:20px; letter-spacing:.04em; text-transform:uppercase;
      flex-shrink:0;
    }
    .st-pendente   { background:#fef3c7; color:#92400e; }
    .st-confirmado { background:#dcfce7; color:#166534; }
    .st-concluido  { background:#dbeafe; color:#1e40af; }
    .st-cancelado  { background:#fee2e2; color:#991b1b; }

    /* Corpo do card */
    .ag-card-body { padding: 0 18px 14px; }

    .ag-nome {
      font-family:'Playfair Display',serif;
      font-size:1rem; font-weight:700; color:#1a0a12;
      margin-bottom:3px;
    }
    .ag-servico {
      font-size:.78rem; color:#b0809a;
      line-height:1.4; margin-bottom:12px;
    }

    /* Rodapé do card */
    .ag-card-footer {
      border-top: 1px solid #fdf0f6;
      padding: 10px 18px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
    }

    /* Botão WhatsApp */
    .wpp-btn {
      display: inline-flex; align-items:center; gap:6px;
      background:#dcfce7; color:#166534;
      padding:7px 14px; border-radius:50px;
      font-weight:700; font-size:.75rem;
      text-decoration:none; transition:all .18s;
      border:none; cursor:pointer;
    }
    .wpp-btn:hover { background:#bbf7d0; transform:scale(1.03); }

    /* Select de status */
    .status-select {
      border: 1.5px solid #f0e4ec; border-radius:10px;
      padding:6px 10px; font-family:'DM Sans',sans-serif;
      font-size:.75rem; font-weight:600; color:#1a0a12;
      background:white; outline:none; cursor:pointer;
      transition:border-color .2s;
    }
    .status-select:focus { border-color:var(--rosa); }

    /* ── Empty state ── */
    .empty {
      background:white; border-radius:20px;
      border:1.5px solid #f0e4ec;
      padding: 48px 20px; text-align:center;
      box-shadow:0 2px 14px rgba(0,0,0,.04);
    }
    .empty-icon { font-size:3rem; margin-bottom:12px; }

    /* ── Toast ── */
    #toast {
      position:fixed; bottom:24px; right:24px; z-index:999;
      background:#1a0a12; color:white;
      padding:10px 18px; border-radius:12px;
      font-size:.82rem; font-weight:600;
      box-shadow:0 8px 24px rgba(0,0,0,.2);
      transform:translateY(60px); opacity:0;
      transition:all .3s ease; pointer-events:none;
    }
    #toast.show { transform:translateY(0); opacity:1; }
    #toast.ok   { border-left:4px solid #22c55e; }
    #toast.erro { border-left:4px solid var(--rosa); }

    /* ── Navegação anterior/próximo (links de data) ── */
    .nav-date-link {
      display:flex; align-items:center; gap:4px;
      font-size:.72rem; color:#b0809a; font-weight:500;
      text-decoration:none; transition:color .15s;
    }
    .nav-date-link:hover { color:var(--rosa); }

    @media (max-width:400px) {
      .kpi-grid { grid-template-columns:repeat(3,1fr); gap:6px; }
      .kpi-num  { font-size:1.2rem; }
    }
  </style>
</head>

<body>

<!-- ══════════════════════════════════════
     HEADER
══════════════════════════════════════ -->
<header class="hdr">
  <div class="hdr-logo">
    <div class="hdr-av">
      <?= mb_strtoupper(mb_substr($dados['usuario_nome'], 0, 1)) ?>
    </div>
    <div>
      <p style="font-family:'Playfair Display',serif;font-weight:700;font-size:.95rem;color:white;line-height:1.2">
        Minha Agenda
      </p>
      <p style="font-size:.7rem;color:rgba(255,255,255,.75)">
        Olá, <?= htmlspecialchars(explode(' ', $dados['usuario_nome'])[0]) ?>! 🌸
      </p>
    </div>
  </div>
  <a href="<?= BASE_URL ?>/controllers/logica_logout.php" class="hdr-sair">Sair</a>
</header>


<!-- ══════════════════════════════════════
     CONTEÚDO
══════════════════════════════════════ -->
<main style="max-width:520px;margin:0 auto;padding:20px 16px 48px">

  <?php
  $dtAnt  = (clone $dt)->modify('-1 day');
  $dtProx = (clone $dt)->modify('+1 day');
?>
<div style="display:flex;align-items:center;justify-content:center;gap:12px;margin-bottom:16px">
  <a href="?data=<?= $dtAnt->format('Y-m-d') ?>" class="btn-nav" title="Dia anterior">‹</a>
  <span style="font-size:.9rem;font-weight:600;color:#1a0a12;min-width:130px;text-align:center">
    <?= $diaNome ?>, <?= $dataFmt ?>
  </span>
  <a href="?data=<?= $dtProx->format('Y-m-d') ?>" class="btn-nav" title="Próximo dia">›</a>
</div>

  <!-- Título do dia -->
  <div style="display:flex;align-items:baseline;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:6px">
    <div>
      <h2 style="font-family:'Playfair Display',serif;font-weight:700;font-size:1.1rem;color:#1a0a12">
        <?= $diaNome ?>, <?= $dataFmt ?>
      </h2>
      <p style="font-size:.75rem;color:#b0809a;margin-top:2px">
        <?= $total ?> atendimento<?= $total !== 1 ? 's' : '' ?> no dia
      </p>
    </div>
    <!-- Link para hoje se não for hoje -->
    <?php if ($dados['data_selecionada'] !== date('Y-m-d')): ?>
    <a href="?" class="nav-date-link">
      📅 Ir para hoje
    </a>
    <?php endif; ?>
  </div>

  <!-- KPIs do dia -->
  <?php if ($total > 0): ?>
  <div class="kpi-grid" style="margin-bottom:18px">
    <div class="kpi" style="border-top-color:#f59e0b">
      <p class="kpi-num" style="color:#f59e0b"><?= $pendentes ?></p>
      <p class="kpi-lbl">Pendentes</p>
    </div>
    <div class="kpi" style="border-top-color:#10b981">
      <p class="kpi-num" style="color:#10b981"><?= $confirmados ?></p>
      <p class="kpi-lbl">Confirmados</p>
    </div>
    <div class="kpi" style="border-top-color:#3b82f6">
      <p class="kpi-num" style="color:#3b82f6"><?= $concluidos ?></p>
      <p class="kpi-lbl">Concluídos</p>
    </div>
  </div>
  <?php endif; ?>

  <!-- Lista de agendamentos -->
  <?php if (empty($agendamentos)): ?>
  <div class="empty">
    <div class="empty-icon">🌸</div>
    <p style="font-family:'Playfair Display',serif;font-weight:700;font-size:1rem;color:#1a0a12;margin-bottom:6px">
      Dia livre!
    </p>
    <p style="font-size:.82rem;color:#b0809a;line-height:1.5">
      Nenhum atendimento agendado para <?= $dataFmt ?>.
    </p>
  </div>

  <?php else: ?>
  <div style="display:flex;flex-direction:column;gap:12px">

    <?php foreach ($agendamentos as $ag):
      $ini = new DateTime($ag['data_hora_inicio']);
      $fim = new DateTime($ag['data_hora_fim']);

      // Calcula duração
      $diffMin  = (int)(($fim->getTimestamp() - $ini->getTimestamp()) / 60);
      $duracao  = $diffMin >= 60
          ? floor($diffMin/60).'h'.($diffMin%60 > 0 ? str_pad($diffMin%60,2,'0',STR_PAD_LEFT).'min':'')
          : $diffMin.'min';

      $wpp    = preg_replace('/\D/', '', $ag['whatsapp_cliente']);
      $wppFmt = '';
      if (strlen($wpp) === 11)     $wppFmt = '('.substr($wpp,0,2).') '.substr($wpp,2,5).'-'.substr($wpp,7);
      elseif (strlen($wpp) === 10) $wppFmt = '('.substr($wpp,0,2).') '.substr($wpp,2,4).'-'.substr($wpp,6);
      else                         $wppFmt = $ag['whatsapp_cliente'];

      $statusMap = [
          'pendente'   => ['label'=>'Pendente',   'class'=>'st-pendente'],
          'confirmado' => ['label'=>'Confirmado', 'class'=>'st-confirmado'],
          'concluido'  => ['label'=>'Concluído',  'class'=>'st-concluido'],
          'cancelado'  => ['label'=>'Cancelado',  'class'=>'st-cancelado'],
      ];
      $st = $statusMap[$ag['status']] ?? ['label'=>ucfirst($ag['status']),'class'=>'st-pendente'];
    ?>
    <div class="ag-card" id="card-<?= $ag['id'] ?>">

      <!-- Topo: hora + status -->
      <div class="ag-card-top">
        <div>
          <div class="ag-hora">
            <span class="ag-hora-dot"></span>
            <?= $ini->format('H:i') ?> – <?= $fim->format('H:i') ?>
          </div>
          <span class="ag-duracao">⏱ <?= $duracao ?></span>
        </div>
        <span class="status-badge <?= $st['class'] ?>"><?= $st['label'] ?></span>
      </div>

      <!-- Corpo: apenas nome e serviço -->
      <div class="ag-card-body">
        <p class="ag-nome"><?= htmlspecialchars($ag['nome_cliente']) ?></p>
        <p class="ag-servico">✂️ <?= htmlspecialchars($ag['servico_nome']) ?></p>
      </div>

      <!-- Rodapé: WhatsApp + alterar status -->
      <div class="ag-card-footer">
        <!-- Botão WhatsApp -->
        <?php if ($wpp): ?>
        <a href="https://wa.me/55<?= $wpp ?>" target="_blank" class="wpp-btn">
          💬 <?= $wppFmt ?>
        </a>
        <?php else: ?>
        <span style="font-size:.75rem;color:#ddd;font-style:italic">Sem WhatsApp</span>
        <?php endif; ?>

        <!-- Select de status -->
        <select class="status-select"
                onchange="alterarStatus(<?= $ag['id'] ?>, this.value, this)"
                id="sel-<?= $ag['id'] ?>">
          <?php foreach (['pendente','confirmado','concluido','cancelado'] as $stOpt): ?>
          <option value="<?= $stOpt ?>" <?= $ag['status'] === $stOpt ? 'selected' : '' ?>>
            <?= ucfirst($stOpt) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>

    </div>
    <?php endforeach; ?>

  </div>
  <?php endif; ?>

</main>

<!-- Toast de feedback -->
<div id="toast"></div>


<script>
const BASE_URL = '<?= BASE_URL ?>';

/* ── Altera status via Fetch (sem reload de página) ── */
async function alterarStatus(id, novoStatus, selectEl) {
    const fd = new FormData();
    fd.append('id',     id);
    fd.append('status', novoStatus);

    try {
        const res = await fetch(BASE_URL + '/api/status-agendamento.php', {
            method: 'POST',
            body: fd
        });

        // A API redireciona (302), então qualquer resposta é OK
        // Atualiza o badge de status no card sem recarregar
        const card  = document.getElementById('card-' + id);
        const badge = card?.querySelector('.status-badge');

        const statusMap = {
            pendente:   { label:'Pendente',   cls:'st-pendente' },
            confirmado: { label:'Confirmado', cls:'st-confirmado' },
            concluido:  { label:'Concluído',  cls:'st-concluido' },
            cancelado:  { label:'Cancelado',  cls:'st-cancelado' },
        };

        if (badge && statusMap[novoStatus]) {
            badge.className = 'status-badge ' + statusMap[novoStatus].cls;
            badge.textContent = statusMap[novoStatus].label;
        }

        mostrarToast('✅ Status atualizado!', 'ok');

    } catch {
        mostrarToast('Erro ao atualizar status.', 'erro');
        // Reverte o select para o valor anterior
        selectEl.value = selectEl.dataset.anterior || selectEl.value;
    }
}

/* Guarda o valor anterior do select ao focar */
document.querySelectorAll('.status-select').forEach(sel => {
    sel.addEventListener('focus', function() {
        this.dataset.anterior = this.value;
    });
});

/* ── Toast ── */
function mostrarToast(msg, tipo = 'ok') {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className   = 'show ' + tipo;
    setTimeout(() => { t.className = ''; }, 2800);
}
</script>

</body>
</html>