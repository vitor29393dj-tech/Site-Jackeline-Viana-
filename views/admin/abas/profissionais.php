<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../controllers/AutenticacaoController.php';
require_once __DIR__ . '/../../../models/Profissional.php';
require_once __DIR__ . '/../../../models/Agendamento.php';
AutenticacaoController::exigirAutenticacao('admin');
$profissionais = Profissional::listarTodos();
?>
<div class="painel-titulo">Profissionais</div>
<p class="painel-sub">Consultoras e atendentes cadastradas no sistema</p>

<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 mb-6">
<?php foreach($profissionais as $p): 
  $agHoje = Agendamento::listarPorProfissional($p->getId(), date('Y-m-d'));
?>
<div class="card-painel p-5 text-center">
  <?php if($p->getFotoUrl()): ?>
  <img src="<?=htmlspecialchars($p->getFotoUrl())?>" class="w-16 h-16 rounded-full mx-auto mb-3 object-cover"/>
  <?php else: ?>
  <div class="w-16 h-16 rounded-full mx-auto mb-3 flex items-center justify-center text-2xl font-bold text-white"
       style="background:<?=htmlspecialchars($p->getCorAgenda())?>">
    <?=mb_strtoupper(mb_substr($p->getApelido(),0,1))?>
  </div>
  <?php endif; ?>
  <p class="font-semibold text-sm" style="color:#1a0a12"><?=htmlspecialchars($p->getApelido())?></p>
  <p class="text-xs mt-0.5 mb-2 truncate" style="color:#b0809a"><?=htmlspecialchars($p->email)?></p>
  <span class="badge badge-confirmado"><?=count($agHoje)?> hoje</span>
</div>
<?php endforeach; ?>
<div class="card-painel p-5 text-center flex flex-col items-center justify-center" style="border:2px dashed #fce7f3;cursor:pointer" onclick="mostrarToast('Em breve: cadastro de profissional.')">
  <div class="w-16 h-16 rounded-full mb-3 flex items-center justify-center text-2xl" style="background:#fdf8fc">➕</div>
  <p class="text-sm" style="color:#b0809a">Adicionar</p>
</div>
</div>
