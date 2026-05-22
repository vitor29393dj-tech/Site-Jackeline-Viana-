<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../controllers/AutenticacaoController.php';
require_once __DIR__ . '/../../../models/Profissional.php';
require_once __DIR__ . '/../../../config/Database.php';
AutenticacaoController::exigirAutenticacao('admin');

$profissionais = Profissional::listarTodos();
$pdo = Database::getInstance();
$dias = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'];
?>
<div class="painel-titulo">Horários de Funcionamento</div>
<p class="painel-sub">Configure os dias e horários disponíveis para agendamento de cada consultora</p>

<?php foreach($profissionais as $p): 
  $stmt = $pdo->prepare("SELECT * FROM horarios_funcionamento WHERE profissional_id=:pid ORDER BY dia_semana");
  $stmt->execute([':pid'=>$p->getId()]);
  $horarios = $stmt->fetchAll();
  $mapH = [];
  foreach($horarios as $h) $mapH[$h['dia_semana']] = $h;
?>
<div class="card-painel p-5 mb-5">
  <div class="flex items-center gap-3 mb-4">
    <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold text-white flex-shrink-0"
         style="background:<?=htmlspecialchars($p->getCorAgenda())?>">
      <?=mb_strtoupper(mb_substr($p->getApelido(),0,1))?>
    </div>
    <p class="font-semibold" style="color:#1a0a12"><?=htmlspecialchars($p->getApelido())?></p>
  </div>
  <div class="grid grid-cols-7 gap-2 text-center">
    <?php for($d=0;$d<=6;$d++): $h=$mapH[$d]??null; $ativo=$h&&$h['ativo']; ?>
    <div class="p-2 rounded-xl <?=$ativo?'bg-green-50 border border-green-200':'bg-gray-50 border border-gray-200'?>">
      <p class="text-xs font-bold mb-1 <?=$ativo?'text-green-700':'text-gray-400'?>"><?=$dias[$d]?></p>
      <?php if($ativo): ?>
      <p class="text-xs text-green-600"><?=substr($h['hora_inicio'],0,5)?></p>
      <p class="text-xs text-green-600"><?=substr($h['hora_fim'],0,5)?></p>
      <?php else: ?>
      <p class="text-xs text-gray-400">—</p>
      <?php endif; ?>
    </div>
    <?php endfor; ?>
  </div>
  <p class="text-xs mt-3" style="color:#b0809a">Para editar os horários, acesse o banco de dados ou solicite ao desenvolvedor.</p>
</div>
<?php endforeach; ?>
