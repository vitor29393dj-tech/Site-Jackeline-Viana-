<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../controllers/AutenticacaoController.php';
require_once __DIR__ . '/../../../models/Agendamento.php';
AutenticacaoController::exigirAutenticacao('admin');

$dataInicio = filter_input(INPUT_GET,'data_inicio',FILTER_SANITIZE_SPECIAL_CHARS) ?? date('Y-m-01');
$dataFim    = filter_input(INPUT_GET,'data_fim',   FILTER_SANITIZE_SPECIAL_CHARS) ?? date('Y-m-t');
$statusFiltro = filter_input(INPUT_GET,'status',   FILTER_SANITIZE_SPECIAL_CHARS) ?? '';

$agendamentos = Agendamento::listarTodos($dataInicio, $dataFim);
if ($statusFiltro) $agendamentos = array_values(array_filter($agendamentos, fn($a)=>$a['status']===$statusFiltro));
?>
<div class="painel-titulo">Agendamentos</div>
<p class="painel-sub">Gerencie e atualize o status de cada atendimento</p>

<div class="card-painel p-4 mb-5">
  <form method="GET" class="flex flex-wrap gap-3 items-end">
    <input type="hidden" name="aba" value="agendamentos"/>
    <div><label class="block text-xs text-gray-500 mb-1">De</label><input type="date" name="data_inicio" value="<?=htmlspecialchars($dataInicio)?>" class="input-admin"/></div>
    <div><label class="block text-xs text-gray-500 mb-1">Até</label><input type="date" name="data_fim" value="<?=htmlspecialchars($dataFim)?>" class="input-admin"/></div>
    <div>
      <label class="block text-xs text-gray-500 mb-1">Status</label>
      <select name="status" class="input-admin">
        <option value="">Todos</option>
        <?php foreach(['pendente','confirmado','concluido','cancelado'] as $st): ?>
        <option value="<?=$st?>" <?=$statusFiltro===$st?'selected':''?>><?=ucfirst($st)?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button type="submit" class="btn-rosa-sm">Filtrar</button>
  </form>
</div>

<div class="card-painel overflow-hidden">
  <div class="overflow-x-auto">
    <table>
      <thead><tr><th>Data/Hora</th><th>Cliente</th><th>Serviço</th><th>Consultora</th><th>Status</th><th>Alterar Status</th></tr></thead>
      <tbody>
      <?php if(empty($agendamentos)): ?><tr><td colspan="6" class="text-center py-10" style="color:#b0809a">Nenhum agendamento encontrado.</td></tr>
      <?php else: foreach($agendamentos as $ag): $ini=new DateTime($ag['data_hora_inicio']); ?>
      <tr>
        <td><p class="font-semibold"><?=$ini->format('d/m/Y')?></p><p class="text-xs" style="color:#b0809a"><?=$ini->format('H:i')?></p></td>
        <td><p class="font-medium"><?=htmlspecialchars($ag['nome_cliente'])?></p><p class="text-xs" style="color:#b0809a"><?=htmlspecialchars($ag['whatsapp_cliente'])?></p></td>
        <td class="max-w-xs"><span style="font-size:.78rem"><?=htmlspecialchars($ag['servico_nome'])?></span></td>
        <td><?=htmlspecialchars($ag['profissional_apelido'])?></td>
        <td><span class="badge badge-<?=$ag['status']?>"><?=ucfirst($ag['status'])?></span></td>
        <td>
          <form method="POST" action="<?=BASE_URL?>/api/status-agendamento.php">
            <input type="hidden" name="id" value="<?=$ag['id']?>"/>
            <select name="status" onchange="this.form.submit()" class="input-admin" style="padding:5px 10px;font-size:.78rem">
              <?php foreach(['pendente','confirmado','concluido','cancelado'] as $st): ?>
              <option value="<?=$st?>" <?=$ag['status']===$st?'selected':''?>><?=ucfirst($st)?></option>
              <?php endforeach; ?>
            </select>
          </form>
        </td>
      </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
