<?php
/**
 * api/escala_sabado.php
 * Endpoints para gravação e remoção de exceções manuais de sábado.
 *
 *   POST (sem acao)          — grava exceção (INSERT OR REPLACE)
 *   POST ?acao=remover       — remove exceção (restaura automático)
 *   POST ?acao=salvar_config — salva configuração de escala do profissional
 *   GET  ?profissional_id=X&data=Y  — retorna o horário calculado (JSON)
 */
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/EscalaSabado.php';
require_once __DIR__ . '/../controllers/AutenticacaoController.php';

header('Content-Type: application/json; charset=utf-8');
AutenticacaoController::exigirAutenticacao(['admin', 'funcionario']);

$acao = filter_input(INPUT_GET, 'acao', FILTER_SANITIZE_SPECIAL_CHARS)
     ?? filter_input(INPUT_POST, 'acao', FILTER_SANITIZE_SPECIAL_CHARS)
     ?? '';

/* ── GET: consulta horário de um sábado ──────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $pid  = filter_input(INPUT_GET, 'profissional_id', FILTER_VALIDATE_INT);
    $data = filter_input(INPUT_GET, 'data', FILTER_SANITIZE_SPECIAL_CHARS);
    if (!$pid || !$data) { echo json_encode(['erro' => 'Parâmetros inválidos.']); exit; }

    $escala = EscalaSabado::calcularParaData($pid, $data);
    echo json_encode(['sucesso' => true, 'escala' => $escala->toArray()]);
    exit;
}

/* ── POST: remover exceção ───────────────────────────────── */
if ($acao === 'remover') {
    AutenticacaoController::exigirAutenticacao('admin');
    $pid  = filter_input(INPUT_POST, 'profissional_id', FILTER_VALIDATE_INT);
    $data = filter_input(INPUT_POST, 'data_sabado',     FILTER_SANITIZE_SPECIAL_CHARS);
    if (!$pid || !$data) { echo json_encode(['erro' => 'Parâmetros inválidos.']); exit; }

    EscalaSabado::removerExcecao($pid, $data);
    echo json_encode(['sucesso' => true]);
    exit;
}

/* ── POST: salvar config de escala ───────────────────────── */
if ($acao === 'salvar_config') {
    AutenticacaoController::exigirAutenticacao('admin');
    $pid      = filter_input(INPUT_POST, 'profissional_id', FILTER_VALIDATE_INT);
    $ativa    = filter_input(INPUT_POST, 'ativa',           FILTER_VALIDATE_BOOLEAN) ?? false;
    $dataRef  = filter_input(INPUT_POST, 'data_referencia', FILTER_SANITIZE_SPECIAL_CHARS);
    $turnoIni = filter_input(INPUT_POST, 'turno_inicial',   FILTER_SANITIZE_SPECIAL_CHARS) ?? 'manha';
    $manhaIni = filter_input(INPUT_POST, 'manha_inicio',    FILTER_SANITIZE_SPECIAL_CHARS) ?? '08:00';
    $manhaFim = filter_input(INPUT_POST, 'manha_fim',       FILTER_SANITIZE_SPECIAL_CHARS) ?? '12:00';
    $tardeIni = filter_input(INPUT_POST, 'tarde_inicio',    FILTER_SANITIZE_SPECIAL_CHARS) ?? '14:00';
    $tardeFim = filter_input(INPUT_POST, 'tarde_fim',       FILTER_SANITIZE_SPECIAL_CHARS) ?? '18:00';

    if (!$pid || !$dataRef) { echo json_encode(['erro' => 'Parâmetros inválidos.']); exit; }
    if ((int)date('w', strtotime($dataRef)) !== 6) {
        echo json_encode(['erro' => 'A data de referência deve ser um sábado.']);
        exit;
    }

    EscalaSabado::salvarConfig($pid, $ativa, $dataRef, $turnoIni, $manhaIni, $manhaFim, $tardeIni, $tardeFim);
    echo json_encode(['sucesso' => true]);
    exit;
}

/* ── POST padrão: salvar exceção ─────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    AutenticacaoController::exigirAutenticacao('admin');
    $pid   = filter_input(INPUT_POST, 'profissional_id', FILTER_VALIDATE_INT);
    $data  = filter_input(INPUT_POST, 'data_sabado',     FILTER_SANITIZE_SPECIAL_CHARS);
    $tipo  = filter_input(INPUT_POST, 'tipo',            FILTER_SANITIZE_SPECIAL_CHARS);
    $ini   = filter_input(INPUT_POST, 'hora_inicio',     FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
    $fim   = filter_input(INPUT_POST, 'hora_fim',        FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
    $motivo= filter_input(INPUT_POST, 'motivo',          FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
    $uid   = (int)($_SESSION['user_id'] ?? 0);

    $tiposValidos = ['manha', 'tarde', 'folga', 'personalizado'];
    if (!$pid || !$data || !in_array($tipo, $tiposValidos)) {
        http_response_code(422);
        echo json_encode(['erro' => 'Dados inválidos.']);
        exit;
    }
    if ($tipo === 'personalizado' && (!$ini || !$fim)) {
        echo json_encode(['erro' => 'Informe hora_inicio e hora_fim para turno personalizado.']);
        exit;
    }

    EscalaSabado::salvarExcecao($pid, $data, $tipo, $ini, $fim, $motivo, $uid ?: null);
    echo json_encode(['sucesso' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['erro' => 'Método não permitido.']);