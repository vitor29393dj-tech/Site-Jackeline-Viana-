<?php
declare(strict_types=1);
require_once dirname(__DIR__, 3) . '/config/config.php';
require_once dirname(__DIR__, 3) . '/controllers/AutenticacaoController.php';
require_once dirname(__DIR__, 3) . '/config/Database.php';

AutenticacaoController::exigirAutenticacao('admin');

$pdo = Database::getInstance();

/* ── Clientes agrupados da tabela de agendamentos ── */
$stmt = $pdo->query("
    SELECT
        MAX(a.id)             AS id,
        a.nome_cliente        AS nome,
        a.email_cliente       AS email,
        a.whatsapp_cliente    AS whatsapp,
        MIN(a.data_hora_inicio) AS primeiro_contato,
        MAX(a.data_hora_inicio) AS ultimo_contato,
        COUNT(*)              AS total_ag,
        SUM(CASE WHEN a.status = 'confirmado' THEN 1 ELSE 0 END) AS confirmados,
        SUM(CASE WHEN a.status = 'concluido'  THEN 1 ELSE 0 END) AS concluidos,
        SUM(CASE WHEN a.status = 'cancelado'  THEN 1 ELSE 0 END) AS cancelados,
        GROUP_CONCAT(DISTINCT p.apelido ORDER BY p.apelido SEPARATOR ', ') AS consultoras
    FROM agendamentos a
    LEFT JOIN profissionais p ON p.id = a.profissional_id
    WHERE a.nome_cliente IS NOT NULL AND a.nome_cliente != ''
    GROUP BY a.email_cliente, a.whatsapp_cliente, a.nome_cliente
    ORDER BY ultimo_contato DESC
    LIMIT 200
");
$clientes = $stmt->fetchAll();

$total    = count($clientes);
$comEmail = count(array_filter($clientes, fn($c) => !empty($c['email'])));
$recentes = count(array_filter($clientes, fn($c) =>
    $c['ultimo_contato'] && strtotime($c['ultimo_contato']) >= strtotime('-30 days')
));

/* Busca texto do filtro via GET (server-side só para demonstração — o filtro principal é JS) */
$busca = filter_input(INPUT_GET, 'q', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
?>

<!-- ════════════════════════════════════════════════════
     ESTILOS
════════════════════════════════════════════════════ -->
<style>
/* ── KPIs ── */
.cl-kpi-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-bottom:24px; }
.cl-kpi {
    background:white; border-radius:16px; padding:16px 18px;
    border:1.5px solid #f0e4ec; display:flex; align-items:center; gap:14px;
    box-shadow:0 2px 10px rgba(0,0,0,.04);
}
.cl-kpi-icon { width:40px; height:40px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.1rem; flex-shrink:0; }
.cl-kpi-num  { font-size:1.6rem; font-weight:800; line-height:1; font-family:'Playfair Display',serif; }
.cl-kpi-lbl  { font-size:.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.08em; color:#b0809a; margin-top:2px; }

/* ── Barra de busca ── */
.cl-search-wrap { position:relative; }
.cl-search-icon { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#c4a0b4; font-size:.9rem; pointer-events:none; }
.cl-search {
    width:100%; border:1.5px solid #f0e4ec; border-radius:50px;
    padding:10px 16px 10px 40px; font-family:'DM Sans',sans-serif;
    font-size:.85rem; color:#1a0a12; background:white; outline:none;
    transition:border-color .2s, box-shadow .2s;
}
.cl-search:focus { border-color:#e91e8c; box-shadow:0 0 0 3px rgba(233,30,140,.08); }
.cl-search::placeholder { color:#c4a0b4; }

/* ── Card container da tabela ── */
.cl-card {
    background:white; border-radius:20px;
    border:1.5px solid #f0e4ec;
    box-shadow:0 2px 14px rgba(0,0,0,.05);
    overflow:hidden;
}

/* ── Tabela ── */
.cl-table { width:100%; border-collapse:collapse; }

.cl-table thead th {
    background:#faf5f8;
    color:#b0809a; font-weight:700;
    text-transform:uppercase; font-size:.62rem; letter-spacing:.08em;
    padding:13px 20px; border-bottom:1.5px solid #f0e4ec;
    text-align:left; white-space:nowrap;
}

.cl-table tbody tr {
    transition:background .15s ease;
    cursor:pointer;
}
.cl-table tbody tr:hover { background:#fff8fc; }
.cl-table tbody tr.expandida { background:#fdf5fb; }

.cl-table tbody td {
    padding:14px 20px;
    border-bottom:1px solid #fdf0f6;
    vertical-align:middle;
}
.cl-table tbody tr:last-child td { border-bottom:none; }

/* ── Avatar / inicial do cliente ── */
.cl-av {
    width:36px; height:36px; border-radius:50%; flex-shrink:0;
    display:flex; align-items:center; justify-content:center;
    font-size:.85rem; font-weight:800; color:white;
    background:linear-gradient(135deg,#e91e8c,#c2186e);
    box-shadow:0 2px 8px rgba(233,30,140,.3);
}

/* ── Nome ── */
.cl-nome {
    font-family:'Playfair Display',serif;
    font-weight:700; font-size:.92rem; color:#1a0a12;
    display:block; line-height:1.2;
}
.cl-meta { font-size:.72rem; color:#b0809a; margin-top:2px; }

/* ── Email ── */
.cl-email { font-size:.78rem; color:#6b5760; font-weight:500; }
.cl-email-empty { font-size:.75rem; color:#ddd; font-style:italic; }

/* ── WhatsApp button ── */
.cl-wpp {
    display:inline-flex; align-items:center; gap:6px;
    background:#dcfce7; color:#166534;
    padding:5px 12px; border-radius:50px;
    font-weight:700; font-size:.75rem;
    transition:all .18s; white-space:nowrap;
    text-decoration:none; border:none; cursor:pointer;
}
.cl-wpp:hover { background:#bbf7d0; transform:scale(1.04); }

/* ── Badge de agendamentos ── */
.cl-ag-badge {
    display:inline-flex; align-items:center; justify-content:center;
    width:28px; height:28px; border-radius:50%;
    background:linear-gradient(135deg,#e91e8c,#c2186e);
    color:white; font-weight:800; font-size:.78rem;
    box-shadow:0 2px 8px rgba(233,30,140,.3);
}
.cl-ag-badge.zero { background:#f3f4f6; color:#9ca3af; box-shadow:none; }

/* ── Linha de data ── */
.cl-data { font-size:.75rem; color:#b0809a; font-weight:500; }
.cl-data-label { font-size:.62rem; color:#c4a0b4; text-transform:uppercase; letter-spacing:.05em; display:block; }

/* ── Consultoras tags ── */
.cl-prof-tag {
    display:inline-flex; align-items:center; gap:4px;
    background:#fdf0f6; color:#e91e8c;
    padding:2px 8px; border-radius:20px;
    font-size:.65rem; font-weight:700;
    white-space:nowrap;
}

/* ── Mini barra de status ── */
.cl-status-bar { display:flex; gap:3px; align-items:center; }
.cl-status-seg {
    height:5px; border-radius:3px;
    transition:width .3s ease;
}

/* ── Detalhe expandido ── */
.cl-detalhe {
    display:none;
    background:#fdf8fc;
    border-top:1px solid #fce7f3;
}
.cl-detalhe.aberto { display:table-row; }
.cl-detalhe td { padding:14px 20px; }

/* ── Botão expandir ── */
.cl-expand-btn {
    width:24px; height:24px; border-radius:50%;
    border:1.5px solid #f0e4ec; background:white;
    display:flex; align-items:center; justify-content:center;
    cursor:pointer; transition:all .2s; font-size:.7rem; color:#b0809a;
    flex-shrink:0;
}
.cl-expand-btn:hover { border-color:#e91e8c; color:#e91e8c; background:#fdf0f6; }
.cl-expand-btn.aberto { background:#e91e8c; border-color:#e91e8c; color:white; transform:rotate(180deg); }

/* ── Empty state ── */
.cl-empty { text-align:center; padding:60px 20px; }
.cl-empty-icon { font-size:3rem; margin-bottom:12px; }

/* ── Contador de resultados ── */
.cl-count { font-size:.75rem; color:#b0809a; font-weight:500; }

/* Responsividade */
@media (max-width:640px) {
    .cl-kpi-grid { grid-template-columns:1fr; }
    .cl-table thead th:nth-child(4),
    .cl-table tbody td:nth-child(4),
    .cl-table thead th:nth-child(5),
    .cl-table tbody td:nth-child(5) { display:none; }
}
</style>


<!-- ════════════════════════════════════════════════════
     CABEÇALHO
════════════════════════════════════════════════════ -->
<div class="flex flex-wrap items-center justify-between gap-3 mb-5">
  <div>
    <div class="painel-titulo">Clientes</div>
    <p class="painel-sub" style="margin-bottom:0">Base de clientes identificados pelos agendamentos</p>
  </div>
</div>

<!-- KPIs -->
<div class="cl-kpi-grid">
  <div class="cl-kpi" style="border-left:3px solid #e91e8c">
    <div class="cl-kpi-icon" style="background:#fdf0f6">👤</div>
    <div>
      <p class="cl-kpi-num" style="color:#e91e8c"><?= $total ?></p>
      <p class="cl-kpi-lbl">Total de Clientes</p>
    </div>
  </div>
  <div class="cl-kpi" style="border-left:3px solid #10b981">
    <div class="cl-kpi-icon" style="background:#f0fdf4">📆</div>
    <div>
      <p class="cl-kpi-num" style="color:#10b981"><?= $recentes ?></p>
      <p class="cl-kpi-lbl">Últimos 30 dias</p>
    </div>
  </div>
  <div class="cl-kpi" style="border-left:3px solid #3b82f6">
    <div class="cl-kpi-icon" style="background:#eff6ff">✉️</div>
    <div>
      <p class="cl-kpi-num" style="color:#3b82f6"><?= $comEmail ?></p>
      <p class="cl-kpi-lbl">Com e-mail</p>
    </div>
  </div>
</div>

<!-- Busca + contador -->
<div class="flex flex-wrap items-center gap-3 mb-4">
  <div class="cl-search-wrap flex-1" style="min-width:220px">
    <span class="cl-search-icon">🔍</span>
    <input type="text" id="clBusca" class="cl-search"
           placeholder="Buscar por nome, e-mail ou telefone..."
           oninput="filtrarClientes()"/>
  </div>
  <span class="cl-count" id="clContador">
    <?= $total ?> cliente<?= $total !== 1 ? 's' : '' ?>
  </span>
</div>

<!-- ════════════════════════════════════════════════════
     TABELA
════════════════════════════════════════════════════ -->
<div class="cl-card">
  <div style="overflow-x:auto">
    <table class="cl-table" id="clTable">
      <thead>
        <tr>
          <th style="width:48px"></th>
          <th>Cliente</th>
          <th>Contato</th>
          <th>Consultora(s)</th>
          <th style="text-align:center">Agend.</th>
          <th>Atividade</th>
          <th style="width:36px"></th>
        </tr>
      </thead>
      <tbody id="clTbody">

        <?php if (empty($clientes)): ?>
        <tr>
          <td colspan="7">
            <div class="cl-empty">
              <div class="cl-empty-icon">🌸</div>
              <p style="font-weight:700;color:#1a0a12;font-size:.9rem;margin-bottom:4px">Nenhum cliente encontrado</p>
              <p style="color:#b0809a;font-size:.8rem">Os clientes aparecerão aqui após o primeiro agendamento.</p>
            </div>
          </td>
        </tr>
        <?php else: foreach ($clientes as $i => $c):
          $wpp      = preg_replace('/\D/', '', $c['whatsapp'] ?? '');
          $wppFmt   = '';
          if (strlen($wpp) === 11)     $wppFmt = '('.substr($wpp,0,2).') '.substr($wpp,2,5).'-'.substr($wpp,7);
          elseif (strlen($wpp) === 10) $wppFmt = '('.substr($wpp,0,2).') '.substr($wpp,2,4).'-'.substr($wpp,6);
          else                         $wppFmt = $c['whatsapp'] ?? '';

          $ini    = mb_strtoupper(mb_substr(trim($c['nome']), 0, 1));
          $dtPrim = $c['primeiro_contato'] ? (new DateTime($c['primeiro_contato']))->format('d/m/Y') : '—';
          $dtUlt  = $c['ultimo_contato']   ? (new DateTime($c['ultimo_contato']))->format('d/m/Y')   : '—';
          $rowId  = 'cl-row-'.$i;
          $detId  = 'cl-det-'.$i;

          /* Cores da barra de status */
          $tot  = max(1, (int)$c['total_ag']);
          $pConf= round(((int)$c['confirmados'] / $tot) * 100);
          $pConc= round(((int)$c['concluidos']  / $tot) * 100);
          $pCanc= round(((int)$c['cancelados']  / $tot) * 100);

          /* Paleta de avatares — varia por inicial */
          $cores = ['#e91e8c','#9c27b0','#3f51b5','#2196f3','#009688','#ff5722','#795548','#e91e63'];
          $corAv = $cores[ord($ini) % count($cores)];
        ?>
        <!-- Linha principal -->
        <tr id="<?=$rowId?>"
            data-busca="<?= strtolower(htmlspecialchars($c['nome'].' '.$c['email'].' '.$c['whatsapp'])) ?>"
            onclick="toggleDetalhe(<?=$i?>)">

          <!-- Avatar -->
          <td>
            <div class="cl-av" style="background:<?=$corAv?>"><?=$ini?></div>
          </td>

          <!-- Nome + email -->
          <td>
            <span class="cl-nome"><?= htmlspecialchars($c['nome']) ?></span>
            <?php if ($c['email']): ?>
              <span class="cl-meta"><?= htmlspecialchars($c['email']) ?></span>
            <?php else: ?>
              <span class="cl-meta" style="color:#ddd;font-style:italic">Sem e-mail</span>
            <?php endif; ?>
          </td>

          <!-- WhatsApp -->
          <td>
            <?php if ($wpp): ?>
              <a href="https://wa.me/55<?=$wpp?>" target="_blank"
                 class="cl-wpp" onclick="event.stopPropagation()"
                 title="Abrir conversa no WhatsApp">
                💬 <?=$wppFmt?>
              </a>
            <?php else: ?>
              <span style="color:#ddd;font-size:.78rem;font-style:italic">Não informado</span>
            <?php endif; ?>
          </td>

          <!-- Consultoras -->
          <td>
            <?php if ($c['consultoras']): ?>
              <div style="display:flex;flex-wrap:wrap;gap:4px">
                <?php foreach (explode(', ', $c['consultoras']) as $cons): ?>
                <span class="cl-prof-tag">✂️ <?= htmlspecialchars(trim($cons)) ?></span>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <span style="color:#ddd;font-size:.75rem">—</span>
            <?php endif; ?>
          </td>

          <!-- Total agendamentos -->
          <td style="text-align:center">
            <span class="cl-ag-badge <?= (int)$c['total_ag'] === 0 ? 'zero' : '' ?>">
              <?= $c['total_ag'] ?>
            </span>
          </td>

          <!-- Atividade (datas + barra) -->
          <td>
            <div style="display:flex;flex-direction:column;gap:4px">
              <div>
                <span class="cl-data-label">Último</span>
                <span class="cl-data"><?= $dtUlt ?></span>
              </div>
              <!-- Mini barra de status -->
              <div class="cl-status-bar" title="<?=$c['confirmados']?> confirmados · <?=$c['concluidos']?> concluídos · <?=$c['cancelados']?> cancelados">
                <?php if($pConf > 0): ?><div class="cl-status-seg" style="width:<?=$pConf?>%;background:#bfdbfe;flex-basis:<?=$pConf?>%"></div><?php endif; ?>
                <?php if($pConc > 0): ?><div class="cl-status-seg" style="width:<?=$pConc?>%;background:#10b981;flex-basis:<?=$pConc?>%"></div><?php endif; ?>
                <?php if($pCanc > 0): ?><div class="cl-status-seg" style="width:<?=$pCanc?>%;background:#fecaca;flex-basis:<?=$pCanc?>%"></div><?php endif; ?>
                <?php if($pConf+$pConc+$pCanc < 100): ?><div class="cl-status-seg" style="flex:1;background:#f3f4f6"></div><?php endif; ?>
              </div>
            </div>
          </td>

          <!-- Botão expandir -->
          <td>
            <div class="cl-expand-btn" id="btn-<?=$i?>" title="Ver detalhes">▾</div>
          </td>
        </tr>

        <!-- Linha de detalhes expandida -->
        <tr class="cl-detalhe" id="<?=$detId?>">
          <td colspan="7">
            <div style="display:flex;flex-wrap:wrap;gap:20px;align-items:flex-start">

              <!-- Estatísticas de agendamentos -->
              <div style="flex:1;min-width:200px">
                <p style="font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#c4a0b4;margin-bottom:8px">Histórico de Agendamentos</p>
                <div style="display:flex;flex-direction:column;gap:5px">
                  <?php foreach ([
                    ['Confirmados', $c['confirmados'], '#3b82f6', '#eff6ff'],
                    ['Concluídos',  $c['concluidos'],  '#10b981', '#f0fdf4'],
                    ['Cancelados',  $c['cancelados'],  '#ef4444', '#fef2f2'],
                  ] as [$lbl,$val,$cor,$bg]): ?>
                  <div style="display:flex;align-items:center;justify-content:space-between;padding:6px 10px;background:<?=$bg?>;border-radius:8px;font-size:.75rem">
                    <span style="color:<?=$cor?>;font-weight:600"><?=$lbl?></span>
                    <span style="color:<?=$cor?>;font-weight:800"><?=$val?></span>
                  </div>
                  <?php endforeach; ?>
                </div>
              </div>

              <!-- Datas -->
              <div style="flex:1;min-width:180px">
                <p style="font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#c4a0b4;margin-bottom:8px">Linha do Tempo</p>
                <div style="display:flex;flex-direction:column;gap:6px">
                  <div style="font-size:.78rem">
                    <span style="color:#c4a0b4;font-size:.65rem;text-transform:uppercase;letter-spacing:.05em;display:block">Primeiro contato</span>
                    <span style="font-weight:700;color:#1a0a12"><?=$dtPrim?></span>
                  </div>
                  <div style="font-size:.78rem">
                    <span style="color:#c4a0b4;font-size:.65rem;text-transform:uppercase;letter-spacing:.05em;display:block">Último agendamento</span>
                    <span style="font-weight:700;color:#1a0a12"><?=$dtUlt?></span>
                  </div>
                </div>
              </div>

              <!-- Ações rápidas -->
              <div style="flex-shrink:0;display:flex;flex-direction:column;gap:6px">
                <p style="font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#c4a0b4;margin-bottom:2px">Ações</p>
                <?php if ($wpp): ?>
                <a href="https://wa.me/55<?=$wpp?>" target="_blank"
                   class="cl-wpp" style="border-radius:10px;padding:8px 14px">
                  💬 Enviar mensagem
                </a>
                <?php endif; ?>
                <?php if ($c['email']): ?>
                <a href="mailto:<?= htmlspecialchars($c['email']) ?>"
                   style="display:inline-flex;align-items:center;gap:6px;background:#eff6ff;color:#1d4ed8;padding:8px 14px;border-radius:10px;font-weight:700;font-size:.75rem;text-decoration:none;transition:opacity .2s"
                   onmouseover="this.style.opacity='.8'" onmouseout="this.style.opacity='1'">
                  ✉️ Enviar e-mail
                </a>
                <?php endif; ?>
              </div>

            </div>
          </td>
        </tr>

        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Sem resultados de busca -->
<div id="clSemResultados" style="display:none;text-align:center;padding:48px 20px;color:#c4a0b4">
  <div style="font-size:2.5rem;margin-bottom:10px">🔍</div>
  <p style="font-weight:600;color:#1a0a12;font-size:.9rem">Nenhum cliente encontrado</p>
  <p style="font-size:.78rem;margin-top:4px">Tente outro termo de busca.</p>
</div>


<script>
/* ── Expande / recolhe detalhe ── */
function toggleDetalhe(i) {
    const det = document.getElementById('cl-det-' + i);
    const btn = document.getElementById('btn-' + i);
    const row = document.getElementById('cl-row-' + i);
    if (!det) return;
    const aberto = det.classList.contains('aberto');
    det.classList.toggle('aberto', !aberto);
    btn.classList.toggle('aberto', !aberto);
    row.classList.toggle('expandida', !aberto);
}

/* ── Filtro de busca em tempo real ── */
function filtrarClientes() {
    const termo = document.getElementById('clBusca').value.toLowerCase().trim();
    const linhas = document.querySelectorAll('#clTbody tr[data-busca]');
    let vis = 0;

    linhas.forEach(tr => {
        const match = !termo || tr.dataset.busca.includes(termo);
        tr.style.display = match ? '' : 'none';

        // Também esconde a linha de detalhe do item oculto
        const id = tr.id.replace('cl-row-', '');
        const det = document.getElementById('cl-det-' + id);
        if (det) det.style.display = match ? '' : 'none';

        if (match) vis++;
    });

    document.getElementById('clContador').textContent =
        vis + ' cliente' + (vis !== 1 ? 's' : '');

    document.getElementById('clSemResultados').style.display = vis === 0 ? 'block' : 'none';
    document.querySelector('.cl-card').style.display          = vis === 0 ? 'none'  : '';
}
</script>