<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../controllers/AutenticacaoController.php';
require_once __DIR__ . '/../../../config/Database.php';
AutenticacaoController::exigirAutenticacao('admin');

$pdo  = Database::getInstance();
$msgs = $pdo->query("SELECT * FROM mensagens_automaticas ORDER BY tipo")->fetchAll();
?>
<div class="painel-titulo">Mensagens Automáticas</div>
<p class="painel-sub">Modelos de mensagens enviadas via WhatsApp para os clientes</p>

<div class="space-y-4">
<?php foreach($msgs as $m): ?>
<div class="card-painel p-5">
  <div class="flex items-center justify-between mb-3">
    <div class="flex items-center gap-2">
      <span class="badge <?=$m['ativo']?'badge-ativo':'badge-inativo'?>"><?=$m['ativo']?'Ativa':'Inativa'?></span>
      <span class="font-semibold text-sm" style="color:#1a0a12"><?=ucfirst($m['tipo'])?></span>
      <span class="badge" style="background:#f3e8ee;color:#b0809a"><?=strtoupper($m['canal'])?></span>
    </div>
    <span class="text-xs" style="color:#b0809a">ID #<?=$m['id']?></span>
  </div>
  <div class="p-3 rounded-xl text-sm leading-relaxed whitespace-pre-wrap" style="background:#fdf8fc;color:#5a3d4f;font-size:.82rem"><?=htmlspecialchars($m['corpo'])?></div>
  <p class="text-xs mt-3" style="color:#b0809a">Variáveis disponíveis: <code style="background:#f5eef3;padding:1px 5px;border-radius:4px">{nome}</code> <code style="background:#f5eef3;padding:1px 5px;border-radius:4px">{servico}</code> <code style="background:#f5eef3;padding:1px 5px;border-radius:4px">{data}</code> <code style="background:#f5eef3;padding:1px 5px;border-radius:4px">{hora}</code> <code style="background:#f5eef3;padding:1px 5px;border-radius:4px">{profissional}</code></p>
</div>
<?php endforeach; ?>
</div>
