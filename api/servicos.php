<?php
/**
 * api/servicos.php
 * Endpoints operacionais da SPA para o Catálogo de Serviços
 */
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Servico.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../controllers/AutenticacaoController.php';

header('Content-Type: application/json; charset=utf-8');
AutenticacaoController::exigirAutenticacao('admin');

$acao = filter_input(INPUT_GET, 'acao', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';

/* ── OBTER DADOS DO SERVIÇO (Para popular o modal de edição) ── */
if ($acao === 'obter') {
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if (!$id) { echo json_encode(['erro' => 'ID inválido.']); exit; }

    $sv = Servico::buscarPorId($id);
    if (!$sv) { echo json_encode(['erro' => 'Serviço não encontrado.']); exit; }

    // Coleta os profissionais habilitados vinculados à tabela muitos-para-muitos
    $profissionaisIds = [];
    $pdo = Database::getInstance();
    try {
        $stmt = $pdo->prepare("SELECT profesional_id FROM servico_profissional WHERE servico_id = :id");
        $stmt->execute([':id' => $id]);
        while($row = $stmt->fetch()) {
            $profissionaisIds[] = (int)$row['profesional_id'];
        }
    } catch(\Throwable) {}

    echo json_encode([
        'id' => $sv->getId(),
        'nome' => $sv->getNome(),
        'descricao' => $sv->getDescricao(),
        'duracao_min' => $sv->getDuracaoMin(),
        'preco' => $sv->getPreco(),
        'ativo' => $sv->isAtivo(),
        'foto_url' => $sv->getFotoUrl(),
        'profissionais' => $profissionaisIds
    ]);
    exit;
}

/* ── TOGGLE STATUS ─────────────────────────────────── */
if ($acao === 'toggle') {
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if (!$id) { echo json_encode(['erro' => 'ID inválido.']); exit; }

    $sv = Servico::buscarPorId($id);
    if ($sv && $sv->alternarStatus()) {
        echo json_encode(['sucesso' => true, 'ativo' => $sv->isAtivo()]);
    } else {
        http_response_code(404);
        echo json_encode(['erro' => 'Serviço não encontrado.']);
    }
    exit;
}

/* ── DELETAR SERVIÇO ───────────────────────────────── */
if ($acao === 'deletar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if (!$id) { echo json_encode(['erro' => 'ID inválido.']); exit; }

    $sv = Servico::buscarPorId($id);
    if ($sv && $sv->excluir()) {
        echo json_encode(['sucesso' => true]);
    } else {
        http_response_code(404);
        echo json_encode(['erro' => 'Não foi possível excluir o serviço.']);
    }
    exit;
}

/* ── SALVAR (INSERT / UPDATE) ────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id        = filter_input(INPUT_POST, 'id',         FILTER_VALIDATE_INT) ?: null;
    $nome      = filter_input(INPUT_POST, 'nome',       FILTER_SANITIZE_SPECIAL_CHARS);
    $descricao = filter_input(INPUT_POST, 'descricao',  FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
    $duracao   = filter_input(INPUT_POST, 'duracao_min', FILTER_VALIDATE_INT) ?: 60;
    $preco     = filter_input(INPUT_POST, 'preco',       FILTER_VALIDATE_FLOAT) ?: 0.0;
    $ativo     = filter_input(INPUT_POST, 'ativo',       FILTER_VALIDATE_INT) ?? 1;
    $fotoUrl   = filter_input(INPUT_POST, 'foto_url',    FILTER_SANITIZE_URL) ?? '';
    $profIds   = $_POST['profissionais'] ?? [];

    if (!$nome) {
        http_response_code(422);
        echo json_encode(['erro' => 'Nome do serviço é obrigatório.']);
        exit;
    }

    $sv = $id ? (Servico::buscarPorId($id) ?? new Servico()) : new Servico();
    $sv->setNome($nome);
    $sv->setDescricao($descricao);
    $sv->setDuracaoMin($duracao);
    $sv->setPreco((float)$preco);
    $sv->setAtivo((bool)$ativo);
    $sv->setFotoUrl($fotoUrl);

    if (!$sv->salvar()) {
        http_response_code(500);
        echo json_encode(['erro' => 'Erro ao salvar no banco de dados.']);
        exit;
    }

    $savedId = $sv->getId();

    /* Salva relação serviço ↔ profissionais */
    $pdo = Database::getInstance();
    try {
        $pdo->prepare("DELETE FROM servico_profissional WHERE servico_id = :id")
            ->execute([':id' => $savedId]);

        if (!empty($profIds)) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO servico_profissional (servico_id, profesional_id) VALUES (:sid, :pid)");
            foreach ($profIds as $pid) {
                $pid = (int)$pid;
                if ($pid > 0) {
                    $stmt->execute([':sid' => $savedId, ':pid' => $pid]);
                }
            }
        }
    } catch (\Throwable) {}

    echo json_encode(['sucesso' => true, 'id' => $savedId]);
    exit;
}

http_response_code(405);
echo json_encode(['erro' => 'Método não permitido.']);