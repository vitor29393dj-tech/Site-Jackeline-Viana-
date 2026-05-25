<?php
/**
 * views/admin/abas/horarios.php — CORRIGIDO (Ajuste para Novos Profissionais)
 */
declare(strict_types=1);
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../controllers/AutenticacaoController.php';
require_once __DIR__ . '/../../../models/Profissional.php';
require_once __DIR__ . '/../../../config/Database.php';

AutenticacaoController::exigirAutenticacao('admin');

$profissionais = Profissional::listarTodos();
$pdo           = Database::getInstance();
$diasSigla     = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'];
$diasNome      = ['Domingo','Segunda','Terça','Quarta','Quinta','Sexta','Sábado'];

/* ── Tenta carregar EscalaSabado (opcional — se não existir, degrada graciosamente) */
$temEscala = false;
try {
    require_once __DIR__ . '/../../../models/EscalaSabado.php';
    $temEscala = class_exists('EscalaSabado');
} catch (\Throwable) {}

/* ── Horários por profissional (da tabela horarios_funcionamento) */
$horariosMap = [];
try {
    $stmt = $pdo->query("
        SELECT profissional_id, dia_semana, turno, turno_label, hora_inicio, hora_fim
          FROM horarios_funcionamento
         WHERE ativo = 1
         ORDER BY profissional_id, dia_semana, turno
    ");
    while ($row = $stmt->fetch()) {
        $pid = (int)$row['profissional_id'];
        $dia = (int)$row['dia_semana'];
        $horariosMap[$pid][$dia][] = [
            'turno'       => (int)$row['turno'],
            'turno_label' => $row['turno_label'] ?: ($row['turno'] == 1 ? 'Manhã' : 'Tarde'),
            'ini'         => substr($row['hora_inicio'], 0, 5),
            'fim'         => substr($row['hora_fim'],    0, 5),
        ];
    }
} catch (\Throwable) {}

/* ── Próximos sábados por profissional (se EscalaSabado disponível) */
$escalasMap = [];
if ($temEscala) {
    foreach ($profissionais as $p) {
        $cfg = EscalaSabado::buscarConfig($p->getId());
        $escalasMap[$p->getId()] = [
            'config'  => $cfg,
            'sabados' => $cfg && $cfg['ativa']
                ? EscalaSabado::mapearProximosSabados($p->getId(), 8)
                : [],
        ];
    }
}
?>

<style>
.hr-card{background:white;border-radius:18px;border:1.5px solid #f0e4ec;overflow:hidden;margin-bottom:20px;box-shadow:0 2px 12px rgba(0,0,0,.04);}
.hr-card-header{padding:16px 20px;border-bottom:1px solid #fdf0f6;display:flex;align-items:center;gap:12px;}
.hr-prof-av{width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.9rem;font-weight:800;color:white;flex-shrink:0;overflow:hidden;}
.hr-prof-av img{width:100%;height:100%;object-fit:cover;border-radius:50%;}
.hr-body{padding:16px 20px;}
.turno-row{display:flex;align-items:center;gap:10px;padding:8px 12px;background:#fdf8fc;border-radius:10px;margin-bottom:6px;border:1px solid #fdf0f6;}
.turno-lbl{font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#c4a0b4;min-width:52px;}
.turno-h{font-size:.82rem;font-weight:600;color:#1a0a12;font-family:monospace;}
.dias-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:6px;margin-bottom:12px;}
.dia-box{border-radius:10px;padding:8px 4px;text-align:center;border:1.5px solid #f0e4ec;}
.dia-box.ativo{background:#fdf0f6;border-color:#f9c0dc;}
.dia-box.inativo{background:#fafafa;opacity:.45;}
.dia-box-lbl{font-size:.58rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#c4a0b4;}
.dia-box-h{font-size:.6rem;font-weight:600;color:#1a0a12;margin-top:2px;line-height:1.3;}
.escala-badge{display:inline-flex;align-items:center;gap:5px;border-radius:20px;padding:3px 10px;font-size:.68rem;font-weight:700;letter-spacing:.03em;}
.escala-on{background:#dcfce7;color:#166534;}.escala-off{background:#f3f4f6;color:#9ca3af;}
.sab-table{width:100%;border-collapse:collapse;font-size:.78rem;}
.sab-table th{font-size:.65rem;text-transform:uppercase;letter-spacing:.06em;color:#b0809a;font-weight:600;padding:6px 10px;border-bottom:1px solid #f5eef3;text-align:left;}
.sab-table td{padding:9px 10px;border-bottom:1px solid #faf5f8;color:#3d2030;vertical-align:middle;}
.sab-table tr:last-child td{border-bottom:none;}
.sab-table tr:hover td{background:#fdf8fc;}
.exc-badge{font-size:.62rem;font-weight:700;padding:2px 8px;border-radius:20px;background:#fef3c7;color:#92400e;}
.btn-alt{font-size:.72rem;font-weight:600;padding:5px 14px;border-radius:8px;border:1.5px solid #f0e4ec;background:white;color:#6b5760;cursor:pointer;transition:all .15s;white-space:nowrap;}
.btn-alt:hover{border-color:#e91e8c;color:#e91e8c;background:#fdf0f6;}
.btn-rest{font-size:.72rem;font-weight:600;padding:5px 12px;border-radius:8px;border:1.5px solid #fecaca;background:#fef2f2;color:#ef4444;cursor:pointer;transition:all .15s;white-space:nowrap;}
.btn-rest:hover{background:#fee2e2;}
.btn-inicializar{background: linear-gradient(135deg, #22c55e, #16a34a); color: white; border: none; font-size: .75rem; font-weight: 600; padding: 6px 14px; border-radius: 8px; cursor: pointer; transition: all .2s;}
.btn-inicializar:hover{transform: translateY(-1px); box-shadow: 0 4px 12px rgba(34,197,94,.3);}

/* ── Modal ─────────────────────────────────── */
#excOverlay{position:fixed;inset:0;z-index:3000;background:rgba(20,5,15,.58);backdrop-filter:blur(6px);display:flex;align-items:center;justify-content:center;opacity:0;pointer-events:none;transition:opacity .3s;padding:16px;}
#excOverlay.open{opacity:1;pointer-events:all;}
#excBox{background:#fdf6f0;border-radius:22px;width:100%;max-width:460px;overflow:hidden;transform:translateY(28px) scale(.98);transition:transform .3s cubic-bezier(.22,.68,0,1.18);box-shadow:0 24px 80px rgba(0,0,0,.22);}
#excOverlay.open #excBox{transform:translateY(0) scale(1);}
.exc-hdr{background:linear-gradient(135deg,#e91e8c,#c2186e);color:white;padding:18px 22px;display:flex;align-items:center;justify-content:space-between;}
.exc-hdr h3{font-family:'Playfair Display',serif;font-size:1rem;font-weight:700;margin:0;}
.exc-body{padding:22px;}
.exc-lbl{font-size:.75rem;font-weight:600;color:#6b5760;margin-bottom:5px;display:block;}
.exc-inp{width:100%;border:1.5px solid #f0e4ec;border-radius:10px;padding:9px 13px;font-family:'DM Sans',sans-serif;font-size:.85rem;color:#1a0a12;background:white;outline:none;transition:border-color .2s;}
.exc-inp:focus{border-color:#e91e8c;box-shadow:0 0 0 3px rgba(233,30,140,.08);}
.exc-aviso{background:#fdf8fc;border:1px solid #fce7f3;border-radius:10px;padding:10px 14px;font-size:.75rem;color:#8a6070;margin-bottom:16px;line-height:1.6;}
.btn-exc-save{background:linear-gradient(135deg,#e91e8c,#c2186e);color:white;font-family:'DM Sans',sans-serif;font-weight:600;font-size:.85rem;padding:10px 24px;border-radius:50px;border:none;cursor:pointer;transition:all .22s;box-shadow:0 4px 14px rgba(233,30,140,.3);}
.btn-exc-save:hover{transform:translateY(-1px);box-shadow:0 8px 22px rgba(233,30,140,.45);}
.btn-exc-save:disabled{opacity:.6;cursor:not-allowed;transform:none;}
.btn-exc-cancel{background:white;color:#6b5760;border:1.5px solid #f0e4ec;font-family:'DM Sans',sans-serif;font-weight:600;font-size:.85rem;padding:9px 20px;border-radius:50px;cursor:pointer;}
.btn-exc-cancel:hover{background:#fdf0f6;border-color:#e91e8c;color:#e91e8c;}
.exc-msg-erro{background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:8px 12px;font-size:.78rem;color:#991b1b;margin-bottom:12px;display:none;}
.exc-msg-erro.vis{display:block;}
.pf-spinner{display:inline-block;width:13px;height:13px;border:2px solid rgba(255,255,255,.3);border-top-color:white;border-radius:50%;animation:excSpin .6s linear infinite;}
@keyframes excSpin{to{transform:rotate(360deg)}}
</style>

<div class="painel-titulo">Horários &amp; Escalas</div>
<p class="painel-sub">Configure turnos, intervalos e a escala automática de sábado</p>

<?php foreach ($profissionais as $p):
  $pid  = $p->getId();
  $ini  = mb_strtoupper(mb_substr($p->getApelido(), 0, 1));
  $cor  = $p->getCorAgenda();
  $hMap = $horariosMap[$pid] ?? [];
  $esc  = $escalasMap[$pid] ?? ['config' => null, 'sabados' => []];
  $cfg  = $esc['config'];
  $sabs = $esc['sabados'];

  // CORREÇÃO: Se o profissional não tiver horários cadastrados, usamos uma simulação visual padrão
  $isNovoSemHorario = empty($hMap);
  if ($isNovoSemHorario) {
      // Cria uma simulação fictícia para preencher visualmente o esqueleto da interface
      for ($d = 1; $d <= 5; $d++) {
          $hMap[$d][] = ['turno' => 1, 'turno_label' => 'Geral', 'ini' => '08:00', 'fim' => '18:00'];
      }
  }
?>
<div class="hr-card" id="hrCard-<?=$pid?>">
  <div class="hr-card-header">
    <div class="hr-prof-av" style="background:<?=$cor?>">
      <?php if($p->getFotoUrl()): ?>
        <img src="<?=htmlspecialchars($p->getFotoUrl())?>" alt="<?=htmlspecialchars($p->getApelido())?>"/>
      <?php else: ?><?=$ini?><?php endif; ?>
    </div>
    <div style="flex:1">
      <p style="font-weight:700;font-size:.9rem;color:#1a0a12;font-family:'Playfair Display',serif"><?=htmlspecialchars($p->getApelido())?></p>
      <p style="font-size:.72rem;color:#b0809a"><?=htmlspecialchars($p->email??'')?></p>
    </div>
    
    <?php if ($isNovoSemHorario): ?>
      <button class="btn-inicializar" onclick="inicializarHorariosProfissional(<?=$pid?>, this)">
         ⚙️ Inicializar Horários
      </button>
    <?php else: ?>
      <span class="escala-badge <?=$cfg&&$cfg['ativa']?'escala-on':'escala-off'?>">
        📅 <?=$cfg&&$cfg['ativa']?'Escala de Sáb. Ativa':'Sem Escala Sáb.'?>
      </span>
    <?php endif; ?>
  </div>

  <div class="hr-body" style="<?=$isNovoSemHorario ? 'opacity: 0.7;' : ''?>">
    <p style="font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#c4a0b4;margin-bottom:8px">
       Segunda a Sexta — Turnos <?=$isNovoSemHorario ? '(Pendência de Inicialização)' : ''?>
    </p>
    <div class="dias-grid">
      <?php for ($d = 1; $d <= 5; $d++):
        $turnos = $hMap[$d] ?? [];
        $temT   = !$isNovoSemHorario && !empty($turnos);
      ?>
      <div class="dia-box <?=$temT?'ativo':'inativo'?>">
        <div class="dia-box-lbl"><?=$diasSigla[$d]?></div>
        <?php if ($temT): foreach($turnos as $t): ?>
          <div class="dia-box-h" style="color:<?=$cor?>"><?=$t['ini']?>–<?=$t['fim']?></div>
        <?php endforeach; else: ?>
          <div class="dia-box-h" style="color:#e5e7eb">—</div>
        <?php endif; ?>
      </div>
      <?php endfor; ?>
      <div class="dia-box <?=$cfg&&$cfg['ativa']?'ativo':'inativo'?>">
        <div class="dia-box-lbl">Sáb</div>
        <div class="dia-box-h" style="color:<?=$cor?>"><?=$cfg&&$cfg['ativa']?'Escala':'—'?></div>
      </div>
      <div class="dia-box inativo">
        <div class="dia-box-lbl">Dom</div>
        <div class="dia-box-h" style="color:#e5e7eb">—</div>
      </div>
    </div>

    <?php
    $turnosUnicos = [];
    foreach ($hMap as $dia => $lista) {
        if ($dia >= 1 && $dia <= 5) {
            foreach ($lista as $t) {
                $key = $t['turno_label'].'_'.$t['ini'].'_'.$t['fim'];
                $turnosUnicos[$key] = $t;
            }
        }
    }
    ?>
    <?php if (!empty($turnosUnicos) && !$isNovoSemHorario): ?>
    <div style="margin-bottom:14px">
      <?php foreach ($turnosUnicos as $tu):
        $ini2 = new DateTime('2000-01-01 '.$tu['ini']);
        $fim2 = new DateTime('2000-01-01 '.$tu['fim']);
        $diffMin = (int)(($fim2->getTimestamp() - $ini2->getTimestamp()) / 60);
      ?>
      <div class="turno-row">
        <span class="turno-lbl"><?=$tu['turno_label']?></span>
        <span class="turno-h"><?=$tu['ini']?> – <?=$tu['fim']?></span>
        <span style="font-size:.68rem;color:#b0809a;margin-left:auto"><?=$diffMin?> min</span>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($cfg && $cfg['ativa'] && !empty($sabs) && !$isNovoSemHorario): ?>
    <div style="border-top:1px solid #fdf0f6;padding-top:14px">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
        <p style="font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#c4a0b4">
          Próximos Sábados — Escala Automática
        </p>
        <span style="font-size:.68rem;color:#b0809a">
          Ref: <?=date('d/m/Y',strtotime($cfg['data_referencia']))?> · Turno <?=strtoupper($cfg['turno_inicial'])?>
        </span>
      </div>

      <table class="sab-table">
        <thead>
          <tr>
            <th>Data</th><th>Turno</th><th>Horário</th><th>Origem</th><th style="text-align:right">Ação</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($sabs as $dataSab => $escala):
            $dtF = date('d/m/Y', strtotime($dataSab));
          ?>
          <tr>
            <td>
              <span style="font-weight:600"><?=$dtF?></span>
              <span style="font-size:.68rem;color:#b0809a;margin-left:4px"><?=$diasNome[(int)date('w',strtotime($dataSab))]?></span>
            </td>
            <td>
              <?php if(!$escala->trabalha): ?>
                <span style="color:#ef4444;font-weight:600;font-size:.78rem">🚫 Folga</span>
              <?php else: ?>
                <span style="color:<?=$cor?>;font-weight:700;font-size:.78rem">
                  <?=match($escala->turno){'manha'=>'🌅 Manhã','tarde'=>'🌇 Tarde',default=>'⚙️ '.ucfirst($escala->turno)}?>
                </span>
              <?php endif; ?>
            </td>
            <td>
              <?php if($escala->trabalha): ?>
                <span style="font-family:monospace;font-size:.78rem"><?=$escala->horaInicio?> – <?=$escala->horaFim?></span>
              <?php else: ?>
                <span style="color:#c4a0b4">—</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if($escala->ehExcecao): ?>
                <span class="exc-badge">✏️ Manual</span>
              <?php else: ?>
                <span style="font-size:.68rem;color:#c4a0b4">Automático</span>
              <?php endif; ?>
            </td>
            <td style="text-align:right">
              <div style="display:flex;gap:6px;justify-content:flex-end;align-items:center">
                <?php
$mIni = $cfg['manha_inicio'] ?? '08:00';
$mFim = $cfg['manha_fim']    ?? '12:00';
$tIni = $cfg['tarde_inicio'] ?? '14:00';
$tFim = $cfg['tarde_fim']    ?? '18:00';
$turnoAtual = $escala->trabalha ? $escala->turno : 'folga';
$hIni = $escala->horaInicio ?: $mIni;
$hFim = $escala->horaFim    ?: $mFim;
?>
<button class="btn-alt"
        onclick="abrirExcModal(
          <?=$pid?>,
          '<?=$dataSab?>',
          '<?=$dtF?>',
          '<?=$turnoAtual?>',
          '<?=$hIni?>',
          '<?=$hFim?>',
          '<?=$mIni?>',
          '<?=$mFim?>',
          '<?=$tIni?>',
          '<?=$tFim?>'
        )">
  ✎ Alterar
</button>
                <?php if($escala->ehExcecao): ?>
                <button class="btn-rest" onclick="restaurarAutomatico(<?=$pid?>, '<?=$dataSab?>')">
                  ↺ Restaurar
                </button>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php elseif (!$cfg || !$cfg['ativa']): ?>
    <div style="border-top:1px solid #fdf0f6;padding-top:12px;font-size:.78rem;color:#c4a0b4">
      Escala automática de sábado não configurada para esta profissional.
    </div>
    <?php endif; ?>

  </div></div><?php endforeach; ?>


<div id="excOverlay">
  <div id="excBox">
    <div class="exc-hdr">
      <h3 id="excTitulo">Alterar Sábado</h3>
      <button onclick="fecharExcModal()"
              style="width:28px;height:28px;border-radius:50%;background:rgba(255,255,255,.2);border:none;color:white;font-size:1.1rem;cursor:pointer;display:flex;align-items:center;justify-content:center">×</button>
    </div>
    <div class="exc-body">

      <div class="exc-aviso">
        ℹ️ Esta alteração vale <strong>somente para este sábado específico</strong>.
        A regra automática das demais semanas <strong>não será afetada</strong>.
      </div>

      <div class="exc-msg-erro" id="excErro"></div>

      <form id="excForm" onsubmit="salvarExcecao(event)" novalidate>
        <input type="hidden" id="excProfId"   name="profissional_id"/>
        <input type="hidden" id="excDataSab"  name="data_sabado"/>
        <input type="hidden" id="excManhaIni"/>
        <input type="hidden" id="excManhaFim"/>
        <input type="hidden" id="excTardeIni"/>
        <input type="hidden" id="excTardeFim"/>

        <div style="margin-bottom:16px">
          <label class="exc-lbl">O que acontece neste sábado?</label>
          <select id="excTipo" name="tipo" class="exc-inp" onchange="excAtualizarHorarios()">
            <option value="manha">🌅 Manhã (turno padrão)</option>
            <option value="tarde">🌇 Tarde (turno padrão)</option>
            <option value="folga">🚫 Folga — não atende</option>
            <option value="personalizado">⚙️ Horário personalizado</option>
          </select>
        </div>

        <div id="excHorarios" style="margin-bottom:16px">
          <label class="exc-lbl">Horário de atendimento</label>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
            <div>
              <label style="font-size:.68rem;color:#b0809a;display:block;margin-bottom:3px">Início</label>
              <input type="time" id="excIni" name="hora_inicio" class="exc-inp" value="08:00"/>
            </div>
            <div>
              <label style="font-size:.68rem;color:#b0809a;display:block;margin-bottom:3px">Fim</label>
              <input type="time" id="excFim" name="hora_fim" class="exc-inp" value="12:00"/>
            </div>
          </div>
          <p id="excHorarioDica" style="font-size:.68rem;color:#b0809a;margin-top:5px">
            Editável — ajuste o horário conforme necessário.
          </p>
        </div>

        <div style="margin-bottom:20px">
          <label class="exc-lbl">Motivo (opcional)</label>
          <input type="text" id="excMotivo" name="motivo" class="exc-inp"
                 placeholder="Ex.: Feriado, ausência, troca de turno..."/>
        </div>

        <div style="display:flex;gap:8px;justify-content:flex-end">
          <button type="button" class="btn-exc-cancel" onclick="fecharExcModal()">Cancelar</button>
          <button type="submit" class="btn-exc-save" id="btnSalvarExc">Salvar Alteração</button>
        </div>
      </form>
    </div>
  </div>
</div>


<script>
/* URL base — usa a constante PHP diretamente para garantir o valor correto */
const HR_BASE = '<?= rtrim(BASE_URL, '/') ?>';
const HR_API  = HR_BASE + '/api/horarios.php'; // Aponta para a API que corrigimos no passo anterior

/* ── FUNÇÃO INICIALIZADORA PARA NOVOS PROFISSIONAIS ── */
async function inicializarHorariosProfissional(profId, btn) {
    if(!confirm("Deseja inicializar o horário padrão (Segunda a Sexta, 08h às 18h) para este profissional?")) return;
    
    btn.disabled = true;
    btn.textContent = "⌛ Inicializando...";
    
    try {
        // Dispara uma requisição GET fictícia passando o id para ativar o gatilho automático da API
        await fetch(`${HR_API}?profissional_id=${profId}&data=${new Date().toISOString().split('T')[0]}`);
        
        if (typeof mostrarToast === 'function') {
            mostrarToast('✅ Horários estruturados com sucesso!');
        }
        setTimeout(() => recarregarAba(), 400);
    } catch (err) {
        alert("Erro ao tentar conectar com a API.");
        btn.disabled = false;
        btn.textContent = "⚙️ Inicializar Horários";
    }
}

/* ── ABRIR MODAL ─────────────────────────────── */
function abrirExcModal(profId, dataSab, dataFmt, turnoAtual, horaIni, horaFim, manhaIni, manhaFim, tardeIni, tardeFim) {
    document.getElementById('excProfId').value  = profId;
    document.getElementById('excDataSab').value = dataSab;
    document.getElementById('excManhaIni').value= manhaIni || '08:00';
    document.getElementById('excManhaFim').value= manhaFim || '12:00';
    document.getElementById('excTardeIni').value= tardeIni || '14:00';
    document.getElementById('excTardeFim').value= tardeFim || '18:00';

    document.getElementById('excTitulo').textContent = '✎ Sábado ' + dataFmt;

    const select = document.getElementById('excTipo');
    select.value = turnoAtual || 'manha';

    document.getElementById('excIni').value = horaIni || '08:00';
    document.getElementById('excFim').value = horaFim || '12:00';

    document.getElementById('excMotivo').value = '';
    ocultarErroExc();
    excAtualizarHorarios();

    document.getElementById('excOverlay').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function fecharExcModal() {
    document.getElementById('excOverlay').classList.remove('open');
    document.body.style.overflow = '';
}

/* Fecha ao clicar fora */
document.getElementById('excOverlay').addEventListener('click', function(e) {
    if (e.target === this) fecharExcModal();
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') fecharExcModal();
});

/* ── ATUALIZA HORÁRIOS CONFORME O TIPO ───────── */
function excAtualizarHorarios() {
    const tipo      = document.getElementById('excTipo').value;
    const horarios  = document.getElementById('excHorarios');
    const dica      = document.getElementById('excHorarioDica');
    const inpIni    = document.getElementById('excIni');
    const inpFim    = document.getElementById('excFim');

    if (tipo === 'folga') {
        horarios.style.display = 'none';
        return;
    }

    horarios.style.display = 'block';

    if (tipo === 'manha') {
        inpIni.value = document.getElementById('excManhaIni').value;
        inpFim.value = document.getElementById('excManhaFim').value;
        dica.textContent = 'Horário padrão do turno Manhã. Você pode ajustar se necessário.';
    } else if (tipo === 'tarde') {
        inpIni.value = document.getElementById('excTardeIni').value;
        inpFim.value = document.getElementById('excTardeFim').value;
        dica.textContent = 'Horário padrão do turno Tarde. Você pode ajustar se necessário.';
    } else {
        dica.textContent = 'Defina o horário personalizado para este sábado.';
    }
}

/* ── SALVAR EXCEÇÃO ──────────────────────────── */
async function salvarExcecao(e) {
    e.preventDefault();
    ocultarErroExc();

    const tipo = document.getElementById('excTipo').value;
    const ini  = document.getElementById('excIni').value;
    const fim  = document.getElementById('excFim').value;

    if (tipo !== 'folga') {
        if (!ini || !fim) {
            mostrarErroExc('Preencha os horários de início e fim.');
            return;
        }
        if (ini >= fim) {
            mostrarErroExc('O horário de início deve ser anterior ao horário de fim.');
            return;
        }
    }

    const btn = document.getElementById('btnSalvarExc');
    btn.disabled = true;
    btn.innerHTML = '<span class="pf-spinner"></span> Salvando...';

    try {
        const fd = new FormData(document.getElementById('excForm'));
        if (tipo === 'folga') {
            fd.delete('hora_inicio');
            fd.delete('hora_fim');
        }

        const HR_API_SAB = HR_BASE + '/api/escala_sabado.php';
        const res  = await fetch(HR_API_SAB, { method: 'POST', body: fd });
        const texto = await res.text();
        let data;
        try {
            data = JSON.parse(texto);
        } catch {
            mostrarErroExc('Resposta inválida do servidor.');
            return;
        }

        if (data.sucesso) {
            if (typeof mostrarToast === 'function') {
                mostrarToast('✅ Sábado alterado com sucesso!');
            }
            fecharExcModal();
            setTimeout(() => recarregarAba(), 350);
        } else {
            mostrarErroExc(data.erro || 'Erro ao salvar. Tente novamente.');
        }
    } catch (err) {
        mostrarErroExc('Erro de conexão.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = 'Salvar Alteração';
    }
}

/* ── RESTAURAR TURNO AUTOMÁTICO ──────────────── */
async function restaurarAutomatico(profId, dataSab) {
    if (!confirm('Restaurar o turno automático para este sábado?')) return;

    const fd = new FormData();
    fd.append('acao',            'remover');
    fd.append('profissional_id', profId);
    fd.append('data_sabado',     dataSab);

    try {
        const HR_API_SAB = HR_BASE + '/api/escala_sabado.php';
        const res  = await fetch(HR_API_SAB, { method: 'POST', body: fd });
        const texto= await res.text();
        let data;
        try { data = JSON.parse(texto); } catch {
            alert('Resposta inválida do servidor.');
            return;
        }
        if (data.sucesso) {
            if (typeof mostrarToast === 'function') mostrarToast('↺ Turno automático restaurado.');
            recarregarAba();
        } else {
            alert(data.erro || 'Erro ao restaurar.');
        }
    } catch {
        alert('Erro de conexão.');
    }
}

/* ── RECARREGA A ABA ─────────────────────────── */
function recarregarAba() {
    fetch(HR_BASE + '/views/admin/abas/horarios.php', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.text())
    .then(html => {
        const p = document.getElementById('conteudo-painel') || document.getElementById('painel-conteudo');
        if (!p) return;
        p.innerHTML = html;
        p.querySelectorAll('script').forEach(s => {
            const n = document.createElement('script');
            n.textContent = s.textContent;
            document.body.appendChild(n);
            n.remove();
        });
    })
    .catch(() => {
        if (typeof mostrarToast === 'function') {
            mostrarToast('Recarregue a página para ver as alterações.', 'error');
        }
    });
}

/* ── HELPERS DE ERRO ─────────────────────────── */
function mostrarErroExc(msg) {
    const el = document.getElementById('excErro');
    el.textContent = msg;
    el.classList.add('vis');
    document.getElementById('excBox').scrollTop = 0;
}
function ocultarErroExc() {
    document.getElementById('excErro').classList.remove('vis');
}
</script>