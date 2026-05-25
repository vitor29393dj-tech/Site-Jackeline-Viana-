<?php
/**
 * views/admin/abas/profissionais.php
 * Grid de Cards de Profissionais + Modal SPA (Ajustado para Ativar/Desativar).
 */
declare(strict_types=1);
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../controllers/AutenticacaoController.php';
require_once __DIR__ . '/../../../models/Profissional.php';
require_once __DIR__ . '/../../../models/Agendamento.php';
require_once __DIR__ . '/../../../config/Database.php';

AutenticacaoController::exigirAutenticacao('admin');

$profissionais = Profissional::listarTodos();

/* KPIs */
$total   = count($profissionais);
$ativos  = count(array_filter($profissionais, fn($p) => $p->isAtivo()));
$inativos= $total - $ativos;

$pdo = Database::getInstance();
$horariosMap = [];
try {
    $stmt = $pdo->query("SELECT profissional_id, dia_semana, hora_inicio, hora_fim FROM horarios_funcionamento WHERE ativo = 1 ORDER BY profissional_id, dia_semana");
    while ($row = $stmt->fetch()) {
        $horariosMap[(int)$row['profissional_id']][(int)$row['dia_semana']] = [
            'ini' => substr($row['hora_inicio'], 0, 5),
            'fim' => substr($row['hora_fim'], 0, 5),
        ];
    }
} catch (\Throwable $e) {}

/* Agendamentos de hoje por profissional */
$agHojeMap = [];
try {
    $hojeStr = date('Y-m-d');
    $stmtAg = $pdo->prepare("SELECT profissional_id, COUNT(*) as total FROM agendamentos WHERE DATE(data_hora_inicio) = :hoje AND status NOT IN ('cancelado') GROUP BY profissional_id");
    $stmtAg->execute([':hoje' => $hojeStr]);
    while ($row = $stmtAg->fetch()) {
        $agHojeMap[(int)$row['profissional_id']] = (int)$row['total'];
    }
} catch (\Throwable $e) {}

$diasSigla = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'];
$diaHoje   = (int)date('w');

$coresSugeridas = ['#e91e8c','#c2186e','#9c27b0','#3f51b5','#2196f3','#009688','#ff5722','#795548','#607d8b','#e91e63','#673ab7','#00bcd4'];
?>

<style>
/* Estilos preservados */
.pf-card { background: white; border-radius: 20px; border: 1.5px solid #f0e4ec; box-shadow: 0 2px 14px rgba(0,0,0,.05); display: flex; flex-direction: column; overflow: hidden; transition: all .22s ease; }
.pf-card:hover { box-shadow: 0 8px 30px rgba(233,30,140,.12); transform: translateY(-2px); border-color: #f9c0dc; }
.pf-card.inativo { opacity: .72; }
.pf-card-header { display: flex; align-items: flex-start; gap: 14px; padding: 18px 18px 0; }
.pf-avatar-wrap { position: relative; flex-shrink: 0; }
.pf-avatar { width: 56px; height: 56px; border-radius: 50%; object-fit: cover; border: 2.5px solid white; box-shadow: 0 2px 10px rgba(0,0,0,.12); display: flex; align-items: center; justify-content: center; font-size: 1.35rem; font-weight: 800; color: white; overflow: hidden; }
.pf-avatar img { width:100%; height:100%; object-fit:cover; border-radius:50%; }
.pf-status-dot { position: absolute; bottom: 2px; right: 2px; width: 12px; height: 12px; border-radius: 50%; border: 2px solid white; }
.pf-status-dot.ativo { background: #22c55e; }
.pf-status-dot.inativo { background: #ef4444; }
.pf-nome { font-weight: 700; font-size: .92rem; color: #1a0a12; line-height: 1.25; font-family: 'Playfair Display', serif; }
.pf-apelido { font-size: .72rem; color: #b0809a; margin-top: 2px; }
.pf-badge-ativo { background:#dcfce7; color:#166534; font-size:.62rem; font-weight:700; padding:2px 9px; border-radius:20px; letter-spacing:.03em; display:inline-flex; align-items:center; gap:3px; cursor:pointer; border:none; transition:all .2s; }
.pf-badge-inativo { background:#fee2e2; color:#991b1b; font-size:.62rem; font-weight:700; padding:2px 9px; border-radius:20px; letter-spacing:.03em; display:inline-flex; align-items:center; gap:3px; cursor:pointer; border:none; transition:all .2s; }
.pf-card-body { padding: 14px 18px; flex: 1; display: flex; flex-direction: column; gap: 10px; }
.pf-dias { display: flex; gap: 4px; align-items: center; }
.pf-dia-dot { display: flex; flex-direction: column; align-items: center; gap: 3px; flex: 1; }
.pf-dia-circulo { width: 9px; height: 9px; border-radius: 50%; transition: background .2s; }
.pf-dia-label { font-size: .52rem; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; color: #c4a0b4; }
.pf-dia-dot.ativo .pf-dia-circulo { background: #22c55e; }
.pf-dia-dot.inativo .pf-dia-circulo{ background: #e5e7eb; }
.pf-dia-dot.hoje .pf-dia-label { color: #e91e8c; }
.pf-info-row { display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; background: #fdf8fc; border-radius: 10px; font-size: .78rem; }
.pf-info-label { color: #b0809a; font-weight: 500; }
.pf-info-val { color: #1a0a12; font-weight: 600; }
.pf-cor-dot { width: 16px; height: 16px; border-radius: 50%; border: 2px solid white; box-shadow: 0 1px 4px rgba(0,0,0,.18); display: inline-block; flex-shrink: 0; cursor: pointer; }
.pf-card-footer { border-top: 1px solid #fdf0f6; padding: 12px 18px; display: flex; gap: 8px; }
.btn-pf-editar { flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px; font-size: .78rem; font-weight: 600; color: #6b5760; background: #faf5f8; border: 1.5px solid #f0e4ec; border-radius: 10px; padding: 8px 12px; cursor: pointer; transition: all .2s; }
.btn-pf-editar:hover { background: #fce7f3; border-color: #e91e8c; color: #e91e8c; }

/* Botões dinâmicos de Ativar / Desativar */
.btn-pf-desativar { display: flex; align-items: center; justify-content: center; gap: 4px; font-size: .78rem; font-weight: 600; background: #fef2f2; border: 1.5px solid #fecaca; border-radius: 10px; padding: 8px 14px; cursor: pointer; transition: all .2s; color: #ef4444; }
.btn-pf-desativar:hover { background: #fee2e2; border-color: #ef4444; }

.btn-pf-ativar { display: flex; align-items: center; justify-content: center; gap: 4px; font-size: .78rem; font-weight: 600; background: #f0fdf4; border: 1.5px solid #bbf7d0; border-radius: 10px; padding: 8px 14px; cursor: pointer; transition: all .2s; color: #16a34a; }
.btn-pf-ativar:hover { background: #dcfce7; border-color: #16a34a; }

.pf-card-novo { background: white; border-radius: 20px; border: 2px dashed #f9c0dc; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px; padding: 32px 16px; cursor: pointer; transition: all .22s ease; min-height: 240px; color: #c4a0b4; }
.pf-card-novo:hover { border-color: #e91e8c; color: #e91e8c; background: #fff8fc; transform: translateY(-2px); }
.pf-kpi { background:white; border-radius:14px; padding:12px 16px; border:1.5px solid #f0e4ec; display:flex; align-items:center; gap:10px; }
.pf-kpi-num { font-size:1.3rem; font-weight:800; line-height:1; }
.pf-kpi-lbl { font-size:.68rem; font-weight:600; text-transform:uppercase; letter-spacing:.06em; color:#b0809a; }
#pfModalOverlay { position: fixed; inset: 0; z-index: 2000; background: rgba(20,5,15,.58); backdrop-filter: blur(6px); display: flex; align-items: center; justify-content: center; opacity: 0; pointer-events: none; transition: opacity .3s ease; padding: 16px; }
#pfModalOverlay.open { opacity: 1; pointer-events: all; }
#pfModalBox { background: #fdf6f0; border-radius: 24px; width: 100%; max-width: 620px; max-height: 92vh; display: flex; flex-direction: column; overflow: hidden; transform: translateY(32px) scale(.98); transition: transform .32s cubic-bezier(.22,.68,0,1.18); box-shadow: 0 24px 80px rgba(0,0,0,.22); }
#pfModalOverlay.open #pfModalBox { transform: translateY(0) scale(1); }
.pf-modal-header { background: linear-gradient(135deg, #e91e8c, #c2186e); color: white; padding: 20px 24px; flex-shrink: 0; display: flex; align-items: center; justify-content: space-between; }
.pf-modal-header h2 { font-family:'Playfair Display',serif; font-size:1.1rem; font-weight:700; }
#pfModalBody { overflow-y: auto; flex: 1; padding: 24px; -webkit-overflow-scrolling: touch; }
.pf-label { font-size:.75rem; font-weight:600; color:#6b5760; margin-bottom:5px; display:block; letter-spacing:.02em; }
.pf-input { width: 100%; border: 1.5px solid #f0e4ec; border-radius: 12px; padding: 10px 14px; font-family:'DM Sans',sans-serif; font-size: .88rem; color: #1a0a12; background: white; outline: none; transition: border-color .2s, box-shadow .2s; }
.pf-input:focus { border-color: #e91e8c; box-shadow: 0 0 0 3px rgba(233,30,140,.08); }
.pf-cor-swatch { width: 30px; height: 30px; border-radius: 50%; border: 2.5px solid white; box-shadow: 0 1px 5px rgba(0,0,0,.2); cursor: pointer; transition: transform .15s; flex-shrink: 0; }
.pf-cor-swatch:hover { transform: scale(1.18); }
.pf-cor-swatch.selecionada { box-shadow: 0 0 0 3px #e91e8c; }
.pf-section-title { font-size:.7rem; font-weight:700; text-transform:uppercase; letter-spacing:.1em; color:#c4a0b4; margin-bottom:12px; margin-top:4px; }
.btn-pf-save { background: linear-gradient(135deg, #e91e8c, #c2186e); color: white; font-family:'DM Sans',sans-serif; font-weight:600; font-size:.88rem; padding: 12px 28px; border-radius: 50px; border: none; cursor: pointer; transition: all .22s; box-shadow: 0 4px 16px rgba(233,30,140,.3); }
.btn-pf-save:hover { transform: translateY(-1px); box-shadow: 0 8px 24px rgba(233,30,140,.45); }
.btn-pf-save:disabled { opacity:.6; cursor:not-allowed; transform:none; }
.btn-pf-cancel { background: white; color: #6b5760; border: 1.5px solid #f0e4ec; font-family:'DM Sans',sans-serif; font-weight:600; font-size:.88rem; padding: 11px 24px; border-radius: 50px; cursor: pointer; transition: all .22s; }
.btn-pf-cancel:hover { background:#fdf0f6; border-color:#e91e8c; color:#e91e8c; }
</style>

<div class="flex flex-wrap items-center justify-between gap-3 mb-5">
  <div>
    <div class="painel-titulo">Profissionais</div>
    <p class="painel-sub" style="margin-bottom:0">Consultoras e atendentes do atelier</p>
  </div>
  <button onclick="abrirModalNovoPf()" class="btn-rosa-sm flex items-center gap-2">
    <span style="font-size:1rem">+</span> Nova Profissional
  </button>
</div>

<div class="grid grid-cols-3 gap-3 mb-6">
  <div class="pf-kpi" style="border-left:3px solid #e91e8c">
    <div><p class="pf-kpi-num" style="color:#e91e8c"><?= $total ?></p><p class="pf-kpi-lbl">Total</p></div>
  </div>
  <div class="pf-kpi" style="border-left:3px solid #10b981">
    <div><p class="pf-kpi-num" style="color:#10b981"><?= $ativos ?></p><p class="pf-kpi-lbl">Ativas</p></div>
  </div>
  <div class="pf-kpi" style="border-left:3px solid #ef4444">
    <div><p class="pf-kpi-num" style="color:#ef4444"><?= $inativos ?></p><p class="pf-kpi-lbl">Inativas</p></div>
  </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5" id="pfGrid">

  <?php foreach ($profissionais as $p):
    $apelidoVal = $p->getApelido();
    
    $nomeCompletoVal = $apelidoVal;
    $emailVal = '';
    try {
        $uStmt = $pdo->prepare("SELECT nome, email FROM usuarios WHERE id = :uid");
        $uStmt->execute([':uid' => $p->getUsuarioId()]);
        $uData = $uStmt->fetch(\PDO::FETCH_ASSOC);
        if ($uData) {
            $nomeCompletoVal = $uData['nome'] ?: $apelidoVal;
            $emailVal = $uData['email'] ?: '';
        }
    } catch(\Throwable $th){}

    $ini       = mb_strtoupper(mb_substr($apelidoVal, 0, 1));
    $cor       = $p->getCorAgenda();
    $hMap      = $horariosMap[$p->getId()] ?? [];
    $agHoje    = $agHojeMap[$p->getId()] ?? 0;
    $horHoje   = $hMap[$diaHoje] ?? null;
    $cssStatus = $p->isAtivo() ? 'ativo' : 'inactive';
  ?>
  <div class="pf-card <?= !$p->isAtivo() ? 'inativo' : '' ?>" data-id="<?= $p->getId() ?>">

    <div class="pf-card-header">
      <div class="pf-avatar-wrap">
        <div class="pf-avatar" style="background:<?= $cor ?>">
          <?php if ($p->getFotoUrl()): ?>
            <img src="<?= htmlspecialchars($p->getFotoUrl()) ?>" alt="<?= htmlspecialchars($apelidoVal) ?>"/>
          <?php else: ?>
            <?= $ini ?>
          <?php endif; ?>
        </div>
        <span class="pf-status-dot <?= $p->isAtivo() ? 'ativo' : 'inativo' ?>"></span>
      </div>

      <div style="flex:1;min-width:0;padding-top:2px">
        <p class="pf-nome"><?= htmlspecialchars($nomeCompletoVal) ?></p>
        <p class="pf-apelido">@<?= htmlspecialchars($apelidoVal) ?></p>
        <div style="margin-top:6px;display:flex;align-items:center;gap:6px">
          <button onclick="toggleProfissional(<?= $p->getId() ?>, this)" class="pf-badge-<?= $p->isAtivo() ? 'ativo' : 'inativo' ?>">
            <span><?= $p->isAtivo() ? '●' : '○' ?></span>
            <?= $p->isAtivo() ? 'ATIVO' : 'INATIVO' ?>
          </button>
          <span class="pf-cor-dot" style="background:<?= $cor ?>"></span>
        </div>
      </div>
    </div>

    <div class="pf-card-body">
      <div class="pf-dias">
        <?php for ($d = 1; $d <= 7; $d++):
          $dIdx = $d % 7; 
          $temH = isset($hMap[$dIdx]);
          $eHoje= ($dIdx === $diaHoje);
        ?>
        <div class="pf-dia-dot <?= $temH ? 'ativo' : 'inativo' ?> <?= $eHoje ? 'hoje' : '' ?>">
          <div class="pf-dia-circulo" style="<?= $temH ? "background:$cor" : '' ?>"></div>
          <span class="pf-dia-label"><?= $diasSigla[$dIdx] ?></span>
        </div>
        <?php endfor; ?>
      </div>

      <div class="pf-info-row">
        <span class="pf-info-label">Hoje</span>
        <span class="pf-info-val"><?= $horHoje ? $horHoje['ini'].' – '.$horHoje['fim'] : 'Folga' ?></span>
      </div>

      <div class="pf-info-row">
        <span class="pf-info-label">Agendamentos hoje</span>
        <span class="pf-info-val" style="color:<?= $agHoje > 0 ? '#e91e8c' : '#b0809a' ?>"><?= $agHoje ?></span>
      </div>

      <div class="pf-info-row">
        <span class="pf-info-label">E-mail</span>
        <span class="pf-info-val" style="font-size:.72rem"><?= htmlspecialchars($emailVal) ?></span>
      </div>
    </div>

    <div class="pf-card-footer">
      <button class="btn-pf-editar" data-profissional-id="<?= $p->getId() ?>" onclick="abrirModalEditarPf(<?= $p->getId() ?>)">✏️ Editar</button>
      
      <?php if ($p->isAtivo()): ?>
        <button class="btn-pf-desativar" onclick="alterarStatusOperacional(<?= $p->getId() ?>, '<?= htmlspecialchars($apelidoVal) ?>', 0)">📴 Desativar</button>
      <?php else: ?>
        <button class="btn-pf-ativar" onclick="alterarStatusOperacional(<?= $p->getId() ?>, '<?= htmlspecialchars($apelidoVal) ?>', 1)">🔋 Ativar</button>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>

  <div class="pf-card-novo" onclick="abrirModalNovoPf()">
    <div style="width:48px;height:48px;border-radius:50%;border:2px dashed #f9c0dc;display:flex;align-items:center;justify-content:center;font-size:1.4rem">＋</div>
    <p style="font-size:.85rem;font-weight:600">Adicionar Profissional</p>
  </div>
</div>

<div id="pfModalOverlay">
  <div id="pfModalBox">
    <div class="pf-modal-header">
      <div>
        <h2 id="pfModalTitulo">Nova Profissional</h2>
        <p style="font-size:.72rem;opacity:.72" id="pfModalSub">Preencha os dados da consultora</p>
      </div>
      <button onclick="fecharModalPf()" style="background:none;border:none;color:white;font-size:1.2rem;cursor:pointer">×</button>
    </div>

    <div id="pfModalBody">
      <form id="pfForm" onsubmit="salvarProfissional(event)">
        <input type="hidden" id="pfId" name="id" value=""/>

        <div style="display:flex;align-items:center;gap:16px;margin-bottom:20px">
          <div id="pfImgPreview" style="width:64px;height:64px;border-radius:50%;background:#fce7f3;display:flex;align-items:center;justify-content:center;overflow:hidden;border:2.5px solid #f0e4ec">👤</div>
          <div style="flex:1">
            <label class="pf-label">URL da foto de perfil</label>
            <input type="text" id="pfFotoUrl" name="foto_url" class="pf-input" oninput="pfPreviewFoto()"/>
          </div>
        </div>

        <p class="pf-section-title">Informações Gerais</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
          <div>
            <label class="pf-label">Nome *</label>
            <input type="text" id="pfFormNome" name="nome" class="pf-input" required/>
          </div>
          <div>
            <label class="pf-label">Sobrenome *</label>
            <input type="text" id="pfFormSobrenome" name="sobrenome" class="pf-input" required/>
          </div>
          <div>
            <label class="pf-label">Apelido / Nome exibido *</label>
            <input type="text" id="pfFormApelido" name="apelido" class="pf-input" required/>
          </div>
          <div>
            <label class="pf-label">E-mail *</label>
            <input type="email" id="pfEmail" name="email" class="pf-input" required/>
          </div>
          <div>
            <label class="pf-label">WhatsApp</label>
            <input type="tel" id="pfWhatsapp" name="whatsapp" class="pf-input"/>
          </div>
          <div>
            <label class="pf-label">Status</label>
            <select id="pfStatus" name="ativo" class="pf-input">
              <option value="1">Ativo</option>
              <option value="0">Inativo</option>
            </select>
          </div>
        </div>

        <p class="pf-section-title">Cor na Agenda</p>
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:20px">
          <?php foreach ($coresSugeridas as $c): ?>
          <button type="button" class="pf-cor-swatch" style="background:<?=$c?>" data-cor="<?=$c?>" onclick="selecionarCorPf('<?=$c?>', this)"></button>
          <?php endforeach; ?>
          <input type="hidden" id="pfCorAgenda" name="cor_agenda" value="#e91e8c"/>
        </div>

        <p class="pf-section-title">Acesso ao Sistema</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-5">
          <div>
            <label class="pf-label" id="pfSenhaLabel">Senha inicial</label>
            <input type="password" id="pfSenha" name="senha" class="pf-input"/>
          </div>
          <div>
            <label class="pf-label">Confirmar senha</label>
            <input type="password" id="pfSenhaConfirm" class="pf-input"/>
          </div>
        </div>

        <div style="display:flex;gap:10px;justify-content:flex-end;padding-top:16px;border-top:1px solid #fdf0f6">
          <button type="button" class="btn-pf-cancel" onclick="fecharModalPf()">Cancelar</button>
          <button type="submit" class="btn-pf-save" id="btnSalvarPf">Salvar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function abrirModalNovoPf() {
    document.getElementById('pfModalTitulo').textContent = 'Nova Profissional';
    document.getElementById('pfModalSub').textContent = 'Preencha os dados da consultora';
    document.getElementById('pfForm').reset();
    document.getElementById('pfId').value = '';
    document.getElementById('pfSenhaLabel').textContent = 'Senha inicial *';
    document.getElementById('pfImgPreview').innerHTML = '👤';
    
    document.querySelectorAll('.pf-cor-swatch').forEach(s => s.classList.remove('selecionada'));
    const padrao = document.querySelector('.pf-cor-swatch[data-cor="#e91e8c"]');
    if(padrao) padrao.classList.add('selecionada');
    document.getElementById('pfCorAgenda').value = '#e91e8c';
    
    abrirModalPf();
}

async function abrirModalEditarPf(id) {
    try {
        const res = await fetch(`${BASE}/api/profissionais.php?acao=obter&id=${id}`);
        const pf = await res.json();
        
        if (pf.erro) { 
            if (typeof mostrarToast === 'function') mostrarToast(pf.erro, 'error'); 
            return; 
        }

        document.getElementById('pfModalTitulo').textContent = 'Editar Profissional';
        document.getElementById('pfModalSub').textContent = `ID Profissional: ${id}`;
        document.getElementById('pfId').value = pf.id;
        document.getElementById('pfFormNome').value = pf.nome_simples || '';
        document.getElementById('pfFormSobrenome').value = pf.sobrenome || '';
        document.getElementById('pfFormApelido').value = pf.apelido || '';
        document.getElementById('pfEmail').value = pf.email || '';
        document.getElementById('pfWhatsapp').value = pf.whatsapp || '';
        document.getElementById('pfStatus').value = pf.ativo ? "1" : "0";
        document.getElementById('pfFotoUrl').value = pf.foto_url || '';
        
        document.getElementById('pfSenhaLabel').textContent = 'Nova senha (vazio para manter)';
        document.getElementById('pfSenha').value = '';
        document.getElementById('pfSenhaConfirm').value = '';

        pfPreviewFoto();
        selecionarCorPf(pf.cor_agenda || '#e91e8c', null);
        abrirModalPf();
    } catch(e) { 
        if (typeof mostrarToast === 'function') mostrarToast('Erro ao obter dados.', 'error'); 
    }
}

function abrirModalPf() { document.getElementById('pfModalOverlay').classList.add('open'); }
function fecharModalPf() { document.getElementById('pfModalOverlay').classList.remove('open'); }

function selecionarCorPf(cor, btn) {
    document.getElementById('pfCorAgenda').value = cor;
    document.querySelectorAll('.pf-cor-swatch').forEach(s => s.classList.remove('selecionada'));
    if (btn) {
        btn.classList.add('selecionada');
    } else {
        const target = document.querySelector(`.pf-cor-swatch[data-cor="${cor}"]`);
        if (target) target.classList.add('selecionada');
    }
}

function pfPreviewFoto() {
    const url = document.getElementById('pfFotoUrl').value.trim();
    const preview = document.getElementById('pfImgPreview');
    if (url) {
        preview.innerHTML = `<img src="${url}" style="width:100%;height:100%;object-fit:cover;border-radius:50%">`;
    } else {
        preview.innerHTML = '👤';
    }
}

function initProfissionaisAba() {
    if (window.__pfAbaInicializado) return;
    window.__pfAbaInicializado = true;

    document.addEventListener('click', function(event) {
        const btnNovo = event.target.closest('.pf-card-novo');
        if (btnNovo) {
            abrirModalNovoPf();
            return;
        }

        const btnEditar = event.target.closest('.btn-pf-editar');
        if (btnEditar) {
            const id = btnEditar.dataset.profissionalId || btnEditar.closest('[data-id]')?.dataset?.id;
            if (id) abrirModalEditarPf(id);
            return;
        }

        const btnCor = event.target.closest('.pf-cor-swatch');
        if (btnCor) {
            selecionarCorPf(btnCor.dataset.cor, btnCor);
            return;
        }
    });
}

window.initProfissionaisAba = initProfissionaisAba;
window.abrirModalNovoPf = abrirModalNovoPf;
window.abrirModalEditarPf = abrirModalEditarPf;
window.fecharModalPf = fecharModalPf;
window.selecionarCorPf = selecionarCorPf;
window.toggleProfissional = toggleProfissional;
window.salvarProfissional = salvarProfissional;
window.alterarStatusOperacional = alterarStatusOperacional;
window.pfPreviewFoto = pfPreviewFoto;

initProfissionaisAba();

async function toggleProfissional(id, btn) {
    try {
        const res  = await fetch(`${BASE}/api/profissionais.php?acao=toggle&id=${id}`, { method: 'GET' });
        const data = await res.json();
        if (data.sucesso) { 
            if (typeof mostrarToast === 'function') mostrarToast('Status alterado!');
            recarregarAbaProfissionais();
        } else {
            if (typeof mostrarToast === 'function') mostrarToast(data.erro, 'error');
        }
    } catch { 
        if (typeof mostrarToast === 'function') mostrarToast('Erro ao alterar status.', 'error'); 
    }
}

async function salvarProfissional(e) {
    e.preventDefault();
    const id = document.getElementById('pfId').value;
    const senha = document.getElementById('pfSenha').value;
    const confirm = document.getElementById('pfSenhaConfirm').value;
    
    if (!id && !senha) {
        if (typeof mostrarToast === 'function') mostrarToast('A senha inicial é obrigatória.', 'error');
        return;
    }
    if (senha && senha !== confirm) { 
        if (typeof mostrarToast === 'function') mostrarToast('As senhas não coincidem.', 'error'); 
        return; 
    }

    const fd = new FormData(document.getElementById('pfForm'));
    try {
        const res = await fetch(`${BASE}/api/profissionais.php`, { method: 'POST', body: fd });
        const data = await res.json();
        if (data.sucesso) {
            if (typeof mostrarToast === 'function') mostrarToast('✅ Guardado com sucesso!');
            fecharModalPf();
            setTimeout(recarregarAbaProfissionais, 400);
        } else { 
            if (typeof mostrarToast === 'function') mostrarToast(data.erro, 'error'); 
        }
    } catch { 
        if (typeof mostrarToast === 'function') mostrarToast('Erro de conexão.', 'error'); 
    }
}

// CORREÇÃO E MELHORIA: Trata dinamicamente tanto a Ativação quanto a Desativação lógica
function alterarStatusOperacional(id, nome, novoStatus) {
    const acaoTexto = novoStatus === 1 ? 'ativar' : 'desativar';
    const acaoConfirmar = novoStatus === 1 ? 'Ela voltará a aparecer nos agendamentos ativos.' : 'Ela não aparecerá na listagem ativa.';
    
    if (!confirm(`Tem a certeza que deseja ${acaoTexto} "${nome}"?\n${acaoConfirmar}`)) return;
    
    fetch(`${BASE}/api/profissionais.php?acao=excluir&id=${id}&status=${novoStatus}`, { method: 'GET' })
    .then(r => r.json()).then(data => {
        if (data.sucesso) {
            const mensagem = novoStatus === 1 ? '🔋 Ativada com sucesso!' : '📴 Desativada com sucesso.';
            if (typeof mostrarToast === 'function') mostrarToast(mensagem);
            recarregarAbaProfissionais();
        } else { 
            if (typeof mostrarToast === 'function') mostrarToast(data.erro, 'error'); 
        }
    }).catch(() => {
        if (typeof mostrarToast === 'function') mostrarToast('Erro ao processar requisição.', 'error');
    });
}

function recarregarAbaProfissionais() {
    fetch(`${BASE}/views/admin/abas/profissionais.php`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then(r => r.text()).then(html => {
        const p = document.getElementById('painel-conteudo') || document.getElementById('conteudo-painel');
        if (p) {
            p.innerHTML = html;
            p.querySelectorAll('script').forEach(s => {
                const n = document.createElement('script');
                n.textContent = s.textContent;
                document.body.appendChild(n);
                n.remove();
            });
            if (typeof window.initProfissionaisAba === 'function') {
                window.initProfissionaisAba();
            }
        } else { 
            location.reload(); 
        }
    });
}
</script>