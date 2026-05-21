<?php
/**
 * api/servicos.php
 * Endpoint: POST /api/servicos.php?acao=toggle&id=X
 */
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Servico.php';
require_once __DIR__ . '/../controllers/AutenticacaoController.php';

header('Content-Type: application/json; charset=utf-8');

AutenticacaoController::exigirAutenticacao('admin');

$acao = filter_input(INPUT_GET, 'acao', FILTER_SANITIZE_SPECIAL_CHARS);
$id   = filter_input(INPUT_GET, 'id',   FILTER_VALIDATE_INT);

if ($acao === 'toggle' && $id) {
    $servico = Servico::buscarPorId($id);
    if ($servico && $servico->alternarStatus()) {
        echo json_encode(['sucesso' => true, 'ativo' => $servico->isAtivo()]);
    } else {
        http_response_code(404);
        echo json_encode(['erro' => 'Serviço não encontrado.']);
    }
} else {
    http_response_code(400);
    echo json_encode(['erro' => 'Ação inválida.']);
}
