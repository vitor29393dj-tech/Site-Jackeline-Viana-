<?php
declare(strict_types=1);
require_once __DIR__ . '/config/config.php';
// Rota simples baseada em query string ou path
// Exemplo futuro: index.php?rota=agendamento
$rota = filter_input(INPUT_GET, 'rota', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';

match ($rota) {
    'agendamento' => require __DIR__ . '/views/client/agendamento.php',
    'login'       => require __DIR__ . '/views/login.php',
    default       => require __DIR__ . '/views/client/home.php',
};