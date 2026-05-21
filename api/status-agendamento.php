<?php
/**
 * api/status-agendamento.php
 * Endpoint: POST /api/status-agendamento.php
 */
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Agendamento.php';
require_once __DIR__ . '/../controllers/AutenticacaoController.php';

AutenticacaoController::exigirAutenticacao(['admin', 'funcionario']);

$id     = filter_input(INPUT_POST, 'id',     FILTER_VALIDATE_INT);
$status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_SPECIAL_CHARS);
$permitidos = ['pendente', 'confirmado', 'concluido', 'cancelado'];

if ($id && in_array($status, $permitidos, true)) {
    // Funcionário só pode alterar status de agendamentos próprios
    if ($_SESSION['user_tipo'] === 'funcionario') {
        $ag = Agendamento::listarPorProfissional((int)$_SESSION['profissional_id']);
        $ids = array_column($ag, 'id');
        if (!in_array($id, $ids)) {
            http_response_code(403);
            exit('Acesso negado.');
        }
    }
    Agendamento::atualizarStatus($id, $status);
}

// Redireciona de volta
header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
exit;
