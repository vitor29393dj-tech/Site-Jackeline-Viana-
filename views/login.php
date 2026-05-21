<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Entrar · Atelier de Costura</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet"/>
  <style>
    body { font-family: 'DM Sans', sans-serif; background: #fdf6f0; }
    .input-field {
      border: 2px solid #f3e8ee; border-radius: 12px; padding: 12px 16px;
      width: 100%; font-family: 'DM Sans', sans-serif; font-size: 0.95rem;
      outline: none; transition: border-color 0.2s; background: white;
    }
    .input-field:focus { border-color: #e91e8c; box-shadow: 0 0 0 3px rgba(233,30,140,0.1); }
    .btn-rosa {
      background: linear-gradient(135deg, #e91e8c, #c2186e); color: white;
      font-weight: 600; padding: 14px 28px; border-radius: 50px; border: none;
      cursor: pointer; width: 100%; font-size: 1rem; transition: all 0.2s;
      box-shadow: 0 4px 15px rgba(233,30,140,0.35);
    }
    .btn-rosa:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(233,30,140,0.45); }
  </style>
</head>

<?php
require_once __DIR__ . '/../config/config.php';
$msg = filter_input(INPUT_GET, 'msg', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
$msgs = [
  'acesso_negado' => 'Faça login para continuar.',
  'sem_permissao' => 'Você não tem permissão para acessar essa área.',
  'logout'        => 'Sessão encerrada com sucesso.',
];
$msgTexto = $msgs[$msg] ?? htmlspecialchars($msg);
?>

<body class="min-h-screen flex items-center justify-center p-4">
  <div class="w-full max-w-sm">

    <!-- Logo -->
    <div class="text-center mb-8">
      <div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4 text-3xl shadow-lg"
           style="background: linear-gradient(135deg, #e91e8c, #c2186e);">✂️</div>
      <h1 class="text-2xl font-bold text-gray-800" style="font-family:'Playfair Display',serif;">
        Atelier de Costura
      </h1>
      <p class="text-gray-500 text-sm mt-1">Área restrita — funcionários e admin</p>
    </div>

    <?php if ($msgTexto): ?>
    <div class="bg-pink-50 border border-pink-200 text-pink-700 rounded-xl p-3 text-sm mb-5 text-center">
      <?= $msgTexto ?>
    </div>
    <?php endif; ?>

    <!-- Formulário -->
    <div class="bg-white rounded-3xl shadow-sm border border-pink-50 p-6">
      <form method="POST" action="<?= BASE_URL ?>/controllers/logica_login.php">
        <div class="space-y-4">
          <div>
            <label class="text-sm font-medium text-gray-700 block mb-1.5">E-mail</label>
            <input type="email" name="email" class="input-field" placeholder="seu@email.com" required />
          </div>
          <div>
            <label class="text-sm font-medium text-gray-700 block mb-1.5">Senha</label>
            <input type="password" name="senha" class="input-field" placeholder="••••••••" required />
          </div>
        </div>
        <button type="submit" class="btn-rosa mt-6">Entrar</button>
      </form>
    </div>

    <p class="text-center text-sm text-gray-400 mt-6">
      <a href="<?= BASE_URL ?>/" class="hover:text-pink-500 transition">← Voltar para o agendamento</a>
    </p>

  </div>
</body>
</html>