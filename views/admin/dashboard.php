<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Painel Admin · Atelier de Costura</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet"/>
  <style>
    body { font-family: 'DM Sans', sans-serif; background: #f8fafc; }
    .sidebar { background: linear-gradient(180deg, #e91e8c 0%, #c2186e 100%); min-height: 100vh; }
    .nav-item { color: rgba(255,255,255,0.75); border-radius: 10px; transition: all 0.2s; }
    .nav-item:hover, .nav-item.active { background: rgba(255,255,255,0.15); color: white; }
    .card { background: white; border-radius: 16px; box-shadow: 0 1px 8px rgba(0,0,0,0.06); }
    .badge-pendente    { background:#fef3c7; color:#92400e; }
    .badge-confirmado  { background:#dcfce7; color:#166534; }
    .badge-concluido   { background:#dbeafe; color:#1e40af; }
    .badge-cancelado   { background:#fee2e2; color:#991b1b; }
    .btn-rosa { background: linear-gradient(135deg,#e91e8c,#c2186e); color:white; border:none; border-radius:10px; padding:8px 18px; font-weight:600; cursor:pointer; font-size:0.875rem; transition:all 0.2s; }
    .btn-rosa:hover { opacity:0.9; }
    .toggle-ativo { cursor:pointer; border-radius:20px; padding:4px 12px; font-size:0.75rem; font-weight:600; transition:all 0.2s; }
    .ativo   { background:#dcfce7; color:#166534; }
    .inativo { background:#fee2e2; color:#991b1b; }
  </style>
</head>
<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../controllers/DashboardController.php';

$controller = new DashboardController();
$dados      = $controller->dadosAdmin();

$aba = filter_input(INPUT_GET, 'aba', FILTER_SANITIZE_SPECIAL_CHARS) ?? 'agenda';
?>
<body class="flex">

<!-- ── Sidebar ── -->
<aside class="sidebar w-56 flex-shrink-0 p-4 sticky top-0 h-screen overflow-y-auto hidden md:block">
  <div class="mb-8">
    <div class="text-white font-serif text-lg font-bold" style="font-family:'Playfair Display',serif;">
      ✂️ Atelier
    </div>
    <div class="text-pink-200 text-xs mt-0.5">Painel Administrativo</div>
  </div>

  <nav class="space-y-1 text-sm">
    <?php
    $abas = [
      'agenda'        => ['📅', 'Agenda Geral'],
      'agendamentos'  => ['📋', 'Agendamentos'],
      'servicos'      => ['✂️', 'Serviços'],
      'profissionais' => ['👗', 'Profissionais'],
      'clientes'      => ['👤', 'Clientes'],
      'horarios'      => ['🕐', 'Horários'],
      'mensagens'     => ['💬', 'Mensagens'],
    ];
    foreach ($abas as $key => [$icon, $label]):
      $cls = $aba === $key ? 'nav-item active' : 'nav-item';
    ?>
    <a href="?aba=<?= $key ?>" class="<?= $cls ?> flex items-center gap-2.5 px-3 py-2.5">
      <span><?= $icon ?></span> <?= $label ?>
    </a>
    <?php endforeach; ?>
    <hr class="border-pink-400/30 my-3" />
    <a href="<?= BASE_URL ?>/controllers/logica_logout.php" class="nav-item flex items-center gap-2.5 px-3 py-2.5">
      <span>🚪</span> Sair
    </a>
  </nav>
</aside>

<!-- ── Conteúdo principal ── -->
<main class="flex-1 p-6 max-h-screen overflow-y-auto">

  <!-- Header da página -->
  <div class="flex items-center justify-between mb-6">
    <div>
      <h1 class="text-xl font-bold text-gray-800" style="font-family:'Playfair Display',serif;">
        <?= ['agenda'=>'Agenda Geral','agendamentos'=>'Agendamentos','servicos'=>'Serviços',
             'profissionais'=>'Profissionais','clientes'=>'Clientes','horarios'=>'Horários',
             'mensagens'=>'Mensagens'][$aba] ?? 'Dashboard' ?>
      </h1>
      <p class="text-gray-500 text-sm">Olá, <?= htmlspecialchars($dados['usuario_nome']) ?> 👋</p>
    </div>
    <button class="btn-rosa">+ Novo Agendamento</button>
  </div>


  <!-- ── ABA: AGENDA ── -->
  <?php if ($aba === 'agenda'): ?>
  <!-- Filtro de datas -->
  <div class="card p-4 mb-6 flex flex-wrap gap-3 items-end">
    <form class="flex gap-3 flex-wrap" method="GET">
      <input type="hidden" name="aba" value="agenda" />
      <div>
        <label class="text-xs text-gray-500 block mb-1">De</label>
        <input type="date" name="data_inicio" value="<?= htmlspecialchars($dados['data_inicio']) ?>"
               class="border border-gray-200 rounded-lg px-3 py-2 text-sm" />
      </div>
      <div>
        <label class="text-xs text-gray-500 block mb-1">Até</label>
        <input type="date" name="data_fim" value="<?= htmlspecialchars($dados['data_fim']) ?>"
               class="border border-gray-200 rounded-lg px-3 py-2 text-sm" />
      </div>
      <button type="submit" class="btn-rosa self-end">Filtrar</button>
    </form>
  </div>

  <!-- Tabela de agendamentos -->
  <div class="card overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wide">
          <tr>
            <th class="px-4 py-3 text-left">Data/Hora</th>
            <th class="px-4 py-3 text-left">Cliente</th>
            <th class="px-4 py-3 text-left">Serviço</th>
            <th class="px-4 py-3 text-left">Atendente</th>
            <th class="px-4 py-3 text-left">Status</th>
            <th class="px-4 py-3 text-left">WhatsApp</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <?php foreach ($dados['agendamentos'] as $ag): ?>
          <?php
            $dtInicio = new DateTime($ag['data_hora_inicio']);
            $cor = $ag['cor_agenda'] ?? '#e91e8c';
          ?>
          <tr class="hover:bg-gray-50 transition">
            <td class="px-4 py-3 font-medium text-gray-800">
              <?= $dtInicio->format('d/m/Y') ?><br />
              <span class="text-xs text-gray-400"><?= $dtInicio->format('H:i') ?></span>
            </td>
            <td class="px-4 py-3">
              <div class="font-medium text-gray-800"><?= htmlspecialchars($ag['nome_cliente']) ?></div>
              <div class="text-xs text-gray-400"><?= htmlspecialchars($ag['whatsapp_cliente']) ?></div>
            </td>
            <td class="px-4 py-3 text-gray-600 max-w-xs">
              <span class="text-xs"><?= htmlspecialchars($ag['servico_nome']) ?></span>
            </td>
            <td class="px-4 py-3">
              <span class="inline-flex items-center gap-1.5">
                <span class="w-2.5 h-2.5 rounded-full" style="background:<?= $cor ?>"></span>
                <?= htmlspecialchars($ag['profissional_apelido']) ?>
              </span>
            </td>
            <td class="px-4 py-3">
              <span class="badge-<?= $ag['status'] ?> text-xs font-medium px-2.5 py-1 rounded-full">
                <?= ucfirst($ag['status']) ?>
              </span>
            </td>
            <td class="px-4 py-3">
              <?php $wpp = preg_replace('/\D/','',$ag['whatsapp_cliente']); ?>
              <a href="https://wa.me/55<?= $wpp ?>" target="_blank"
                 class="text-green-600 hover:underline text-xs">💬 Abrir</a>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($dados['agendamentos'])): ?>
          <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400 text-sm">
            Nenhum agendamento no período.
          </td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>


  <!-- ── ABA: SERVIÇOS ── -->
  <?php if ($aba === 'servicos'): ?>
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
    <?php foreach ($dados['servicos'] as $sv): ?>
    <div class="card p-4">
      <div class="flex items-start justify-between mb-2">
        <h3 class="font-semibold text-gray-800 text-sm flex-1 leading-tight">
          <?= htmlspecialchars($sv->getNome()) ?>
        </h3>
        <button onclick="toggleServico(<?= $sv->getId() ?>, this)"
                class="toggle-ativo <?= $sv->isAtivo() ? 'ativo' : 'inativo' ?> ml-2 flex-shrink-0">
          <?= $sv->isAtivo() ? '● Ativo' : '○ Inativo' ?>
        </button>
      </div>
      <p class="text-xs text-gray-400 mb-3">
        ⏱ <?= $sv->getDuracaoMin() ?> min
        <?php if ($sv->getPreco() > 0): ?>
          · R$ <?= number_format($sv->getPreco(), 2, ',', '.') ?>
        <?php endif; ?>
      </p>
      <div class="flex gap-2">
        <button class="text-xs text-blue-600 hover:underline">Editar</button>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>


  <!-- ── ABA: PROFISSIONAIS ── -->
  <?php if ($aba === 'profissionais'): ?>
  <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <?php foreach ($dados['profissionais'] as $p): ?>
    <div class="card p-5 text-center">
      <div class="w-14 h-14 rounded-full mx-auto mb-3 flex items-center justify-center text-xl font-bold text-white"
           style="background:<?= htmlspecialchars($p->getCorAgenda()) ?>;">
        <?= mb_strtoupper(mb_substr($p->getApelido(), 0, 1)) ?>
      </div>
      <p class="font-semibold text-gray-800"><?= htmlspecialchars($p->getApelido()) ?></p>
      <p class="text-xs text-gray-400 mt-1 truncate"><?= htmlspecialchars($p->email) ?></p>
    </div>
    <?php endforeach; ?>
    <!-- Botão adicionar -->
    <div class="card p-5 text-center flex flex-col items-center justify-center border-2 border-dashed border-pink-200 cursor-pointer hover:border-pink-400 transition">
      <div class="w-14 h-14 rounded-full mb-3 flex items-center justify-center text-2xl bg-pink-50">+</div>
      <p class="text-sm text-gray-400">Adicionar</p>
    </div>
  </div>
  <?php endif; ?>

</main>
</body>
</html>

<script>
async function toggleServico(id, btn) {
  const res  = await fetch(`/api/servicos.php?acao=toggle&id=${id}`, { method: 'POST' });
  const data = await res.json();
  if (data.sucesso) {
    const ativo = data.ativo;
    btn.textContent = ativo ? '● Ativo' : '○ Inativo';
    btn.className = 'toggle-ativo ' + (ativo ? 'ativo' : 'inativo') + ' ml-2 flex-shrink-0';
  }
}
</script>
