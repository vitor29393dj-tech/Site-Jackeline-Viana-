<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../controllers/AutenticacaoController.php';
require_once __DIR__ . '/../../../models/Servico.php';
AutenticacaoController::exigirAutenticacao('admin');
$servicos = Servico::listarTodos();
?>
<div class="painel-titulo">Serviços</div>
<p class="painel-sub">Ative ou desative serviços exibidos para o cliente no agendamento</p>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
<?php foreach($servicos as $sv): ?>
<div class="card-painel p-4">
  <div class="flex items-start justify-between gap-2 mb-2">
    <p class="font-semibold text-sm leading-tight" style="color:#1a0a12;flex:1"><?=htmlspecialchars($sv->getNome())?></p>
    <button onclick="toggleServico(<?=$sv->getId()?>,this)"
            class="badge <?=$sv->isAtivo()?'badge-ativo':'badge-inativo'?> flex-shrink-0 cursor-pointer hover:opacity-80 transition">
      <?=$sv->isAtivo()?'● Ativo':'○ Inativo'?>
    </button>
  </div>
  <?php if($sv->getDescricao()): ?>
  <p class="text-xs mb-2" style="color:#b0809a"><?=htmlspecialchars($sv->getDescricao())?></p>
  <?php endif; ?>
  <p class="text-xs font-medium" style="color:var(--rosa)">⏱ <?=$sv->getDuracaoMin()?> min
    <?php if($sv->getPreco()>0): ?> · R$ <?=number_format($sv->getPreco(),2,',','.')?><?php endif; ?>
  </p>
</div>
<?php endforeach; ?>
</div>

<script>
async function toggleServico(id, btn) {
  try {
    const res  = await fetch(`${BASE}/api/servicos.php?acao=toggle&id=${id}`, {method:'POST'});
    const data = await res.json();
    if (data.sucesso) {
      btn.textContent = data.ativo ? '● Ativo' : '○ Inativo';
      btn.className   = `badge ${data.ativo ? 'badge-ativo' : 'badge-inativo'} flex-shrink-0 cursor-pointer hover:opacity-80 transition`;
      mostrarToast(data.ativo ? 'Serviço ativado.' : 'Serviço desativado.');
    }
  } catch { mostrarToast('Erro ao alterar serviço.','error'); }
}
</script>
