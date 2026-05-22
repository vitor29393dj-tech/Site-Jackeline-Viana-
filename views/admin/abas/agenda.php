<?php
/**
 * views/admin/abas/agenda.php
 * Central Operacional de Agendamentos — fragmento SPA.
 */
declare(strict_types=1);
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../controllers/AutenticacaoController.php';
require_once __DIR__ . '/../../../models/Agendamento.php';
require_once __DIR__ . '/../../../models/Profissional.php';

AutenticacaoController::exigirAutenticacao('admin');

$mesSel     = filter_input(INPUT_GET, 'mes', FILTER_VALIDATE_INT) ?: (int)date('m');
$anoSel     = filter_input(INPUT_GET, 'ano', FILTER_VALIDATE_INT) ?: (int)date('Y');
$filtroProf = filter_input(INPUT_GET, 'profissional_id', FILTER_VALIDATE_INT) ?: 0;

$dtAtual = new DateTime(sprintf('%04d-%02d-01', $anoSel, $mesSel));
$dtAnt   = (clone $dtAtual)->modify('-1 month');
$dtProx  = (clone $dtAtual)->modify('+1 month');

$mesAnt = (int)$dtAnt->format('m');  $anoAnt  = (int)$dtAnt->format('Y');
$mesProx= (int)$dtProx->format('m'); $anoProx = (int)$dtProx->format('Y');

$dataIni         = $dtAtual->format('Y-m-01');
$dataFim         = $dtAtual->format('Y-m-t');
$agendamentosRaw = Agendamento::listarTodos($dataIni, $dataFim);
$profissionais   = Profissional::listarTodos();

if ($filtroProf) {
    $agendamentosRaw = array_values(
        array_filter($agendamentosRaw, fn($a) => (int)$a['profissional_id'] === $filtroProf)
    );
}

$agendaPorDia = [];
foreach ($agendamentosRaw as $ag) {
    $dia = (int)substr($ag['data_hora_inicio'], 8, 2);
    $agendaPorDia[$dia][] = $ag;
}
foreach ($agendaPorDia as $d => &$lista) {
    usort($lista, fn($a,$b) => strcmp($a['data_hora_inicio'], $b['data_hora_inicio']));
}
unset($lista);

$total       = count($agendamentosRaw);
$pendentes   = count(array_filter($agendamentosRaw, fn($a) => $a['status'] === 'pendente'));
$confirmados = count(array_filter($agendamentosRaw, fn($a) => $a['status'] === 'confirmado'));
$concluidos  = count(array_filter($agendamentosRaw, fn($a) => $a['status'] === 'concluido'));
$cancelados  = count(array_filter($agendamentosRaw, fn($a) => $a['status'] === 'cancelado'));

$hojeStr  = date('Y-m-d');
$proximos = array_filter($agendamentosRaw, fn($a) =>
    substr($a['data_hora_inicio'], 0, 10) >= $hojeStr && $a['status'] !== 'cancelado');
usort($proximos, fn($a,$b) => strcmp($a['data_hora_inicio'], $b['data_hora_inicio']));
$proximos = array_slice(array_values($proximos), 0, 8);

$mesesNomes = [1=>'Janeiro',2=>'Fevereiro',3=>'Março',4=>'Abril',5=>'Maio',6=>'Junho',
               7=>'Julho',8=>'Agosto',9=>'Setembro',10=>'Outubro',11=>'Novembro',12=>'Dezembro'];
$hojeDia = (int)date('d'); $hojeMes = (int)date('m'); $hojeAno = (int)date('Y');
$primeiroDiaSemana = (int)date('w', strtotime($dataIni));
$totalDias = (int)date('t', strtotime($dataIni));

function statusLabel(string $s): string {
    return match($s){'pendente'=>'Pendente','confirmado'=>'Confirmado','concluido'=>'Concluído','cancelado'=>'Cancelado',default=>ucfirst($s)};
}
?>

<!-- ESTILOS LOCAIS -->
<style>
.cal-cell{min-height:118px;border-radius:14px;padding:8px 7px;border:1.5px solid #f0e4ec;background:white;cursor:pointer;transition:all .18s ease;display:flex;flex-direction:column;position:relative;overflow:hidden;}
.cal-cell:hover{border-color:#e91e8c;box-shadow:0 4px 16px rgba(233,30,140,.12);transform:translateY(-1px);}
.cal-cell.hoje{border-color:#e91e8c;box-shadow:0 0 0 2px rgba(233,30,140,.18);background:#fff8fc;}
.cal-cell.selecionado{border-color:#c2186e;box-shadow:0 0 0 3px rgba(194,24,110,.15);}
.cal-cell.vazio{background:#fafafa;border-color:#f5f0f3;cursor:default;opacity:.45;}
.cal-cell.vazio:hover{transform:none;box-shadow:none;}
.dia-numero{width:22px;height:22px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:700;flex-shrink:0;transition:background .15s;}
.dia-numero.hoje-num{background:#e91e8c;color:white;border-radius:50%;}
.chip-ag{display:flex;align-items:center;gap:4px;border-radius:6px;padding:2px 5px;font-size:.68rem;font-weight:600;color:white;overflow:hidden;white-space:nowrap;margin-bottom:2px;flex-shrink:0;box-shadow:0 1px 3px rgba(0,0,0,.15);}
.chip-hora{opacity:.85;font-size:.62rem;font-weight:700;padding-right:4px;border-right:1px solid rgba(255,255,255,.3);flex-shrink:0;}
.chip-nome{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1;}
.chip-mais{font-size:.65rem;color:#b0809a;font-weight:600;padding:1px 4px;background:#fdf0f6;border-radius:5px;display:inline-block;margin-top:1px;}
.timeline-card{background:white;border-radius:16px;padding:16px;border:1.5px solid #f0e4ec;box-shadow:0 2px 12px rgba(233,30,140,.06);display:flex;flex-direction:column;gap:10px;transition:box-shadow .2s;}
.timeline-card:hover{box-shadow:0 6px 20px rgba(233,30,140,.12);}
.timeline-hora-pill{background:#1a0a12;color:white;font-size:.72rem;font-weight:700;padding:3px 10px;border-radius:8px;font-family:monospace;flex-shrink:0;}
.timeline-status{font-size:.65rem;font-weight:700;letter-spacing:.04em;text-transform:uppercase;padding:2px 9px;border-radius:20px;}
.ts-pendente{background:#fef3c7;color:#92400e;}.ts-confirmado{background:#dcfce7;color:#166534;}.ts-concluido{background:#dbeafe;color:#1e40af;}.ts-cancelado{background:#fee2e2;color:#991b1b;}
.prof-badge{display:inline-flex;align-items:center;gap:6px;border-radius:20px;padding:3px 10px 3px 4px;font-size:.72rem;font-weight:600;color:white;}
.prof-badge-dot{width:18px;height:18px;border-radius:50%;background:rgba(255,255,255,.25);display:flex;align-items:center;justify-content:center;font-size:.65rem;font-weight:800;}
.legenda-prof{display:inline-flex;align-items:center;gap:6px;padding:5px 12px 5px 6px;border-radius:20px;font-size:.75rem;font-weight:600;color:white;cursor:pointer;transition:all .2s;opacity:1;border:2px solid transparent;}
.legenda-prof.inativa{opacity:.32;}
.legenda-prof-dot{width:20px;height:20px;border-radius:50%;background:rgba(255,255,255,.22);display:flex;align-items:center;justify-content:center;font-size:.68rem;font-weight:800;}
.kpi-card{background:white;border-radius:16px;padding:14px 16px;border:1.5px solid #f0e4ec;border-left-width:4px;box-shadow:0 2px 10px rgba(0,0,0,.04);display:flex;align-items:center;gap:12px;}
.kpi-icon{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;}
.thin-scroll::-webkit-scrollbar{width:3px;}.thin-scroll::-webkit-scrollbar-thumb{background:#e0c8d8;border-radius:4px;}
@keyframes slideDown{from{opacity:0;transform:translateY(-10px)}to{opacity:1;transform:translateY(0)}}
.slide-down{animation:slideDown .28s ease forwards;}
</style>

<!-- BARRA SUPERIOR -->
<div class="flex flex-wrap items-center justify-between gap-3 mb-5">
  <div class="flex items-center gap-2 flex-wrap">
    <button onclick="navegarCalendario(<?=$mesAnt?>,<?=$anoAnt?>)" class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 transition">‹ Anterior</button>
    <button onclick="navegarCalendario(<?=(int)date('m')?>,<?=(int)date('Y')?>)" class="px-3 py-1.5 text-xs font-bold rounded-lg bg-pink-50 text-pink-700 hover:bg-pink-100 transition">Hoje</button>
    <button onclick="navegarCalendario(<?=$mesProx?>,<?=$anoProx?>)" class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 transition">Próximo ›</button>
    <h2 class="text-base font-bold ml-1" style="font-family:'Playfair Display',serif;color:#1a0a12"><?=$mesesNomes[$mesSel]?> de <?=$anoSel?></h2>
  </div>
  <div class="flex items-center gap-2">
    <label class="text-xs font-medium hidden sm:block" style="color:#b0809a">Consultora:</label>
    <select id="filtro_profissional_id" onchange="navegarCalendario(<?=$mesSel?>,<?=$anoSel?>)" class="input-admin text-xs py-1.5 px-3 rounded-lg min-w-[160px]">
      <option value="0">Todas as Consultoras</option>
      <?php foreach($profissionais as $p): ?>
      <option value="<?=$p->getId()?>" <?=$filtroProf===$p->getId()?'selected':''?>><?=htmlspecialchars($p->getApelido())?></option>
      <?php endforeach; ?>
    </select>
  </div>
</div>

<!-- KPIs -->
<div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-5">
<?php foreach([['Total',$total,'#e91e8c','#fdf0f6','📅'],['Pendentes',$pendentes,'#f59e0b','#fffbeb','⏳'],['Confirmados',$confirmados,'#10b981','#f0fdf4','✅'],['Concluídos',$concluidos,'#3b82f6','#eff6ff','🎀'],['Cancelados',$cancelados,'#ef4444','#fef2f2','✕']] as [$lbl,$val,$cor,$bg,$ic]): ?>
<div class="kpi-card" style="border-left-color:<?=$cor?>">
  <div class="kpi-icon" style="background:<?=$bg?>"><?=$ic?></div>
  <div>
    <p class="text-[10px] font-bold uppercase tracking-wider" style="color:#b0809a"><?=$lbl?></p>
    <p class="text-xl font-black leading-tight" style="color:<?=$cor?>"><?=$val?></p>
  </div>
</div>
<?php endforeach; ?>
</div>

<!-- LEGENDA DE CONSULTORAS -->
<?php if(count($profissionais) > 0): ?>
<div class="flex flex-wrap gap-2 mb-5 items-center">
  <span class="text-[10px] font-bold uppercase tracking-widest" style="color:#b0809a">Consultoras:</span>
  <button onclick="filtrarPorConsultora(0)" id="leg-0" class="legenda-prof <?=$filtroProf!==0?'inativa':''?>" style="background:#1a0a12">
    <span class="legenda-prof-dot">✦</span> Todas
  </button>
  <?php foreach($profissionais as $p):
    $ativa = ($filtroProf===0||$filtroProf===$p->getId());
    $ini   = mb_strtoupper(mb_substr($p->getApelido(),0,1));
    $qtdP  = count(array_filter($agendamentosRaw, fn($a)=>(int)$a['profissional_id']===$p->getId()));
  ?>
  <button onclick="filtrarPorConsultora(<?=$p->getId()?>)" id="leg-<?=$p->getId()?>" class="legenda-prof <?=!$ativa?'inativa':''?>" style="background:<?=htmlspecialchars($p->getCorAgenda())?>">
    <span class="legenda-prof-dot"><?=$ini?></span>
    <?=htmlspecialchars($p->getApelido())?>
    <span style="background:rgba(255,255,255,.2);border-radius:10px;padding:1px 6px;font-size:.62rem"><?=$qtdP?></span>
  </button>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- CALENDÁRIO -->
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 mb-5">
  <div class="grid grid-cols-7 gap-1.5 mb-2.5 text-center">
    <?php foreach(['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'] as $i=>$ds): ?>
    <div class="py-1.5 rounded-lg text-[10px] font-bold uppercase tracking-wider <?=$i===0?'bg-red-50 text-red-400':'bg-slate-50 text-gray-400'?>"><?=$ds?></div>
    <?php endforeach; ?>
  </div>
  <div class="grid grid-cols-7 gap-1.5" id="calGrid">
    <?php for($i=0;$i<$primeiroDiaSemana;$i++): ?><div class="cal-cell vazio"></div><?php endfor; ?>
    <?php for($dia=1;$dia<=$totalDias;$dia++):
      $isHoje=($dia===$hojeDia&&$mesSel===$hojeMes&&$anoSel===$hojeAno);
      $ags=$agendaPorDia[$dia]??[];
      $qtd=count($ags);
      $mostrar=array_slice($ags,0,3);
      $extras=$qtd-count($mostrar);
      $dataFmtPHP=sprintf('%02d/%02d/%04d',$dia,$mesSel,$anoSel);
    ?>
    <div class="cal-cell <?=$isHoje?'hoje':''?>" id="dia-<?=$dia?>" onclick="selecionarDia(<?=$dia?>,'<?=$dataFmtPHP?>')">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px">
        <span class="dia-numero <?=$isHoje?'hoje-num':'text-gray-700'?>"><?=$dia?></span>
        <?php if($qtd>0): ?><span style="font-size:.65rem;padding:1px 5px;background:#1a0a12;color:white;border-radius:6px;font-weight:700"><?=$qtd?></span><?php endif; ?>
      </div>
      <div style="display:flex;flex-direction:column;flex:1;justify-content:flex-start;overflow:hidden">
        <?php foreach($mostrar as $ag):
          $t=new DateTime($ag['data_hora_inicio']);
          $cor=$ag['cor_agenda']??'#e91e8c';
          $fn=explode(' ',$ag['nome_cliente'])[0];
        ?>
        <div class="chip-ag" style="background:<?=$cor?>" title="<?=$t->format('H:i')?> · <?=htmlspecialchars($ag['nome_cliente'])?> · <?=htmlspecialchars($ag['profissional_apelido'])?>">
          <span class="chip-hora"><?=$t->format('H:i')?></span>
          <span class="chip-nome"><?=htmlspecialchars($fn)?></span>
        </div>
        <?php endforeach; ?>
        <?php if($extras>0): ?><span class="chip-mais">+<?=$extras?> mais</span><?php endif; ?>
      </div>
    </div>
    <?php endfor; ?>
  </div>
</div>

<!-- TIMELINE DE DETALHES -->
<div id="blocoDetalhes" class="hidden mb-6">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
    <div style="display:flex;align-items:center;gap:10px">
      <span style="width:8px;height:8px;border-radius:50%;background:#e91e8c;display:inline-block;animation:pulse 1.5s infinite"></span>
      <h3 style="font-family:'Playfair Display',serif;font-weight:700;font-size:.9rem;color:#1a0a12">
        Atendimentos de <span id="txtDataSel" style="color:#e91e8c"></span>
      </h3>
      <span id="txtQtdSel" style="font-size:.72rem;padding:2px 10px;border-radius:20px;background:#fdf0f6;color:#e91e8c;font-weight:700"></span>
    </div>
    <button onclick="fecharDetalhes()" style="font-size:.75rem;font-weight:700;padding:6px 14px;border-radius:8px;background:#f5f0f3;color:#b0809a;border:none;cursor:pointer">✕ Fechar</button>
  </div>
  <div id="listaDetalhes" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 slide-down"></div>
</div>

<!-- PRÓXIMOS ATENDIMENTOS -->
<?php if(count($proximos)>0): ?>
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
    <h3 style="font-family:'Playfair Display',serif;font-weight:700;font-size:.9rem;color:#1a0a12">Próximos Atendimentos</h3>
    <span style="font-size:.72rem;padding:2px 10px;border-radius:20px;background:#fdf0f6;color:#e91e8c;font-weight:700"><?=count($proximos)?> agendados</span>
  </div>
  <div style="display:flex;flex-direction:column;gap:6px">
    <?php foreach($proximos as $ag):
      $dt=new DateTime($ag['data_hora_inicio']);
      $cor=$ag['cor_agenda']??'#e91e8c';
      $wpp=preg_replace('/\D/','',$ag['whatsapp_cliente']);
      $ini=mb_strtoupper(mb_substr($ag['profissional_apelido']??'?',0,1));
    ?>
    <div style="display:flex;align-items:center;gap:12px;padding:10px;border-radius:12px;transition:background .15s" onmouseover="this.style.background='#fdf8fc'" onmouseout="this.style.background='transparent'">
      <div style="width:3px;align-self:stretch;border-radius:4px;flex-shrink:0;background:<?=$cor?>"></div>
      <div style="text-align:center;min-width:40px;flex-shrink:0">
        <p style="font-size:.65rem;font-weight:700;text-transform:uppercase;color:#b0809a"><?=$dt->format('d/m')?></p>
        <p style="font-size:.78rem;font-weight:800;color:#1a0a12;font-family:monospace"><?=$dt->format('H:i')?></p>
      </div>
      <div style="flex:1;min-width:0">
        <p style="font-size:.82rem;font-weight:700;color:#1a0a12;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?=htmlspecialchars($ag['nome_cliente'])?></p>
        <p style="font-size:.72rem;color:#b0809a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?=htmlspecialchars($ag['servico_nome'])?></p>
      </div>
      <span style="background:<?=$cor?>;color:white;font-size:.68rem;font-weight:700;padding:3px 10px;border-radius:20px;flex-shrink:0"><?=$ini?> <?=htmlspecialchars(explode(' ',$ag['profissional_apelido'])[0])?></span>
      <span class="timeline-status ts-<?=$ag['status']?> hidden sm:inline"><?=statusLabel($ag['status'])?></span>
      <a href="https://wa.me/55<?=$wpp?>" target="_blank" style="font-size:.85rem;flex-shrink:0;text-decoration:none">💬</a>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<style>
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.4}}
</style>

<!-- JAVASCRIPT -->
<script>
var dadosAg = <?=json_encode($agendaPorDia, JSON_UNESCAPED_UNICODE)?>;
var mesSel  = <?=$mesSel?>; var anoSel = <?=$anoSel?>;
var diaSel  = null;

function navegarCalendario(mes, ano) {
  const profId = document.getElementById('filtro_profissional_id')?.value ?? 0;
  _carregarAgenda(mes, ano, profId);
}
function filtrarPorConsultora(profId) { _carregarAgenda(mesSel, anoSel, profId); }

function _carregarAgenda(mes, ano, profId) {
  document.getElementById('loadingBar').classList.add('visible');
  fetch(`${BASE}/views/admin/abas/agenda.php?mes=${mes}&ano=${ano}&profissional_id=${profId}`, {
    headers:{'X-Requested-With':'XMLHttpRequest'}
  })
  .then(r => r.text())
  .then(html => {
    const p = document.getElementById('conteudo-painel');
    p.innerHTML = html;
    p.querySelectorAll('script').forEach(s=>{const n=document.createElement('script');n.textContent=s.textContent;document.body.appendChild(n);n.remove();});
    document.getElementById('loadingBar').classList.remove('visible');
  })
  .catch(()=>{ document.getElementById('loadingBar').classList.remove('visible'); mostrarToast('Erro ao carregar.','error'); });
}

function selecionarDia(dia, dataFmt) {
  if (diaSel) document.getElementById(`dia-${diaSel}`)?.classList.remove('selecionado');
  diaSel = dia;
  document.getElementById(`dia-${dia}`)?.classList.add('selecionado');

  document.getElementById('txtDataSel').textContent = dataFmt;
  const lista = dadosAg[dia] || [];
  document.getElementById('txtQtdSel').textContent = lista.length===0 ? 'Sem atendimentos' : `${lista.length} atendimento${lista.length!==1?'s':''}`;

  const container = document.getElementById('listaDetalhes');
  container.innerHTML = '';

  if (lista.length === 0) {
    container.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:28px;border:1.5px dashed #fce7f3;border-radius:14px;color:#c4a0b4;font-size:.82rem">Nenhum atendimento para este dia.</div>`;
  } else {
    lista.forEach(ag => {
      const dt   = new Date(ag.data_hora_inicio);
      const hora = String(dt.getHours()).padStart(2,'0')+':'+String(dt.getMinutes()).padStart(2,'0');
      const dtF  = new Date(ag.data_hora_fim);
      const horaF= String(dtF.getHours()).padStart(2,'0')+':'+String(dtF.getMinutes()).padStart(2,'0');
      const wpp  = ag.whatsapp_cliente.replace(/\D/g,'');
      const cor  = ag.cor_agenda||'#e91e8c';
      const ini  = (ag.profissional_apelido||'?')[0].toUpperCase();
      container.innerHTML += `
        <div class="timeline-card">
          <div style="display:flex;align-items:center;justify-content:space-between;gap:8px">
            <div style="display:flex;align-items:center;gap:6px">
              <span class="timeline-hora-pill">${hora}</span>
              <span style="font-size:.7rem;color:#b0809a;font-weight:500">até ${horaF}</span>
            </div>
            <span class="timeline-status ts-${ag.status}">${ag.status}</span>
          </div>
          <div>
            <p style="font-weight:700;font-size:.9rem;color:#1a0a12;font-family:'Playfair Display',serif;line-height:1.3">${ag.nome_cliente}</p>
            <p style="font-size:.75rem;color:#b0809a;margin-top:2px">${ag.servico_nome}</p>
          </div>
          <span class="prof-badge" style="background:${cor};align-self:flex-start">
            <span class="prof-badge-dot">${ini}</span>${ag.profissional_apelido}
          </span>
          <div style="display:flex;align-items:center;justify-content:space-between;border-top:1px solid #fdf0f6;padding-top:8px">
            <span style="font-size:.7rem;color:#c4a0b4">${ag.whatsapp_cliente}</span>
            <a href="https://wa.me/55${wpp}" target="_blank" style="font-size:.78rem;font-weight:700;color:#16a34a;text-decoration:none">💬 WhatsApp</a>
          </div>
        </div>`;
    });
  }

  const bloco = document.getElementById('blocoDetalhes');
  bloco.classList.remove('hidden');
  const grid = document.getElementById('listaDetalhes');
  grid.classList.remove('slide-down'); void grid.offsetWidth; grid.classList.add('slide-down');
  setTimeout(()=>bloco.scrollIntoView({behavior:'smooth',block:'nearest'}),50);
}

function fecharDetalhes() {
  document.getElementById('blocoDetalhes').classList.add('hidden');
  if(diaSel){document.getElementById(`dia-${diaSel}`)?.classList.remove('selecionado');diaSel=null;}
}

// Abre hoje automaticamente se estivermos no mês atual e houver agendamentos
(function(){
  const hoje=<?=$hojeDia?>;
  const mAtual=(<?=$mesSel?>===<?=$hojeMes?> && <?=$anoSel?>===<?=$hojeAno?>);
  if(mAtual && dadosAg[hoje] && dadosAg[hoje].length>0){
    const df=String(hoje).padStart(2,'0')+'/'+String(<?=$mesSel?>).padStart(2,'0')+'/'+<?=$anoSel?>;
    setTimeout(()=>selecionarDia(hoje,df),150);
  }
})();
</script>