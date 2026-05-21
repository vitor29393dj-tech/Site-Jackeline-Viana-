<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../controllers/DashboardController.php';

$controller = new DashboardController();
$dados      = $controller->dadosFuncionario();

if (isset($dados['erro'])) {
  echo '<div class="p-8 text-center text-red-500">' . htmlspecialchars($dados['erro']) . '</div>';
  exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Minha Agenda · Atelier de Costura</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet"/>
  <style>
    body { font-family: 'DM Sans', sans-serif; background: #fdf6f0; }
    .card { background: white; border-radius: 16px; box-shadow: 0 1px 8px rgba(0,0,0,0.06); }
    .badge-pendente   { background:#fef3c7; color:#92400e; }
    .badge-confirmado { background:#dcfce7; color:#166534; }
    .badge-concluido  { background:#dbeafe; color:#1e40af; }
    .badge-cancelado  { background:#fee2e2; color:#991b1b; }
    .ag-card { border-left: 4px solid #e91e8c; }
  </style>
</head>
<body class="min-h-screen">

<!-- Header -->
<header style="background: linear-gradient(135deg, #e91e8c, #c2186e);" class="text-white px-4 py-4 flex items-center justify-between">
  <div>
    <h1 class="font-bold text-lg" style="font-family:'Playfair Display',serif;">Minha Agenda</h1>
    <p class="text-pink-200 text-xs">Olá, <?= htmlspecialchars($dados['usuario_nome']) ?>!</p>
  </div>
  <a href="<?= BASE_URL ?>/controllers/logica_logout.php"
     class="text-xs bg-white/20 hover:bg-white/30 px-3 py-1.5 rounded-full transition">
    Sair
  </a>
</header>

<main class="max-w-lg mx-auto px-4 py-6">

  <!-- Seletor de data -->
  <div class="card p-4 mb-5">
    <form method="GET" class="flex gap-3 items-end">
      <div class="flex-1">
        <label class="text-xs text-gray-500 block mb-1">Ver agenda do dia</label>
        <input type="date" name="data" value="<?= htmlspecialchars($dados['data_selecionada']) ?>"
               class="border border-gray-200 rounded-lg px-3 py-2 text-sm w-full" />
      </div>
      <button type="submit"
              style="background:linear-gradient(135deg,#e91e8c,#c2186e);"
              class="text-white font-semibold px-4 py-2 rounded-lg text-sm">Ver</button>
    </form>
  </div>

  <!-- Agendamentos do dia -->
  <?php
    $dt = new DateTime($dados['data_selecionada']);
    $diasSemana = ['Domingo','Segunda','Terça','Quarta','Quinta','Sexta','Sábado'];
  ?>
  <h2 class="font-semibold text-gray-700 mb-3 text-sm">
    <?= $diasSemana[(int)$dt->format('w')] ?>,
    <?= $dt->format('d/m/Y') ?>
    <span class="ml-2 text-gray-400">(<?= count($dados['agendamentos_dia']) ?> atendimento<?= count($dados['agendamentos_dia']) !== 1 ? 's' : '' ?>)</span>
  </h2>

  <?php if (empty($dados['agendamentos_dia'])): ?>
  <div class="card p-8 text-center text-gray-400">
    <div class="text-4xl mb-3">📅</div>
    <p>Nenhum agendamento neste dia.</p>
  </div>
  <?php else: ?>
  <div class="space-y-3">
    <?php foreach ($dados['agendamentos_dia'] as $ag):
      $ini = new DateTime($ag['data_hora_inicio']);
      $fim = new DateTime($ag['data_hora_fim']);
    ?>
    <div class="card p-4 ag-card">
      <div class="flex items-center justify-between mb-2">
        <span class="font-bold text-gray-800"><?= $ini->format('H:i') ?> – <?= $fim->format('H:i') ?></span>
        <span class="badge-<?= $ag['status'] ?> text-xs font-medium px-2.5 py-1 rounded-full">
          <?= ucfirst($ag['status']) ?>
        </span>
      </div>
      <p class="font-medium text-gray-800 text-sm"><?= htmlspecialchars($ag['nome_cliente']) ?></p>
      <p class="text-gray-500 text-xs mt-0.5"><?= htmlspecialchars($ag['servico_nome']) ?></p>
      <div class="flex gap-3 mt-3">
        <?php $wpp = preg_replace('/\D/', '', $ag['whatsapp_cliente']); ?>
        <a href="https://wa.me/55<?= $wpp ?>" target="_blank"
           class="text-green-600 text-xs hover:underline flex items-center gap-1">
          💬 <?= htmlspecialchars($ag['whatsapp_cliente']) ?>
        </a>
        <form method="POST" action="<?= BASE_URL ?>/api/status-agendamento.php" class="ml-auto">
          <input type="hidden" name="id" value="<?= $ag['id'] ?>" />
          <select name="status" onchange="this.form.submit()"
                  class="text-xs border border-gray-200 rounded-lg px-2 py-1">
            <?php foreach (['pendente','confirmado','concluido','cancelado'] as $st): ?>
            <option value="<?= $st ?>" <?= $ag['status'] === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
            <?php endforeach; ?>
          </select>
        </form>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

</main>
</body>
</html>