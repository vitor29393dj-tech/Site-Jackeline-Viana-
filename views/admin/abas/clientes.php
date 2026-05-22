<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../controllers/AutenticacaoController.php';
require_once __DIR__ . '/../../../config/Database.php';
AutenticacaoController::exigirAutenticacao('admin');

$pdo  = Database::getInstance();
$stmt = $pdo->query("SELECT id, nome, email, whatsapp, criado_em,
    (SELECT COUNT(*) FROM agendamentos WHERE usuario_id = usuarios.id) AS total_ag
    FROM usuarios WHERE tipo = 'cliente' ORDER BY criado_em DESC LIMIT 100");
$clientes = $stmt->fetchAll();
?>
<div class="painel-titulo">Clientes</div>
<p class="painel-sub">Base de clientes cadastrados no sistema</p>

<div class="card-painel overflow-hidden">
  <div class="overflow-x-auto">
    <table>
      <thead><tr><th>Nome</th><th>E-mail</th><th>WhatsApp</th><th>Agendamentos</th><th>Cadastro</th></tr></thead>
      <tbody>
      <?php if(empty($clientes)): ?><tr><td colspan="5" class="text-center py-10" style="color:#b0809a">Nenhum cliente cadastrado.</td></tr>
      <?php else: foreach($clientes as $c): ?>
      <tr>
        <td class="font-medium"><?=htmlspecialchars($c['nome'])?></td>
        <td style="color:#b0809a"><?=htmlspecialchars($c['email'])?></td>
        <td>
          <?php $wpp=preg_replace('/\D/','',$c['whatsapp']); ?>
          <a href="https://wa.me/55<?=$wpp?>" target="_blank" class="text-green-600 hover:underline text-xs">
            💬 <?=htmlspecialchars($c['whatsapp'])?>
          </a>
        </td>
        <td><span class="badge badge-confirmado"><?=$c['total_ag']?></span></td>
        <td style="color:#b0809a"><?=(new DateTime($c['criado_em']))->format('d/m/Y')?></td>
      </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
