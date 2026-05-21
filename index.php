<?php
/**
 * index.php — Ponto de entrada público.
 * Redireciona para a tela de agendamento do cliente.
 */
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

header('Location: views/client/agendamento.php');
exit;
