<?php
/**
 * api/profissionais.php
 * Endpoints operacionais da SPA para o gerenciamento de Profissionais.
 */
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Profissional.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../controllers/AutenticacaoController.php';

header('Content-Type: application/json; charset=utf-8');

try {
    AutenticacaoController::exigirAutenticacao('admin');
} catch (\Throwable $e) {
    http_response_code(401);
    echo json_encode(['erro' => 'Não autorizado.']);
    exit;
}

$acao = filter_input(INPUT_GET, 'acao', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
$pdo  = Database::getInstance();

/* ── OBTER DADOS PARA EDIÇÃO ── */
if ($acao === 'obter') {
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if (!$id) { echo json_encode(['erro' => 'ID inválido.']); exit; }

    $stmt = $pdo->prepare("
        SELECT p.id, p.usuario_id, u.nome, p.apelido, u.email, u.whatsapp, p.foto_url, p.cor_agenda, p.ativo 
        FROM profissionais p
        INNER JOIN usuarios u ON p.usuario_id = u.id
        WHERE p.id = :id
    ");
    $stmt->execute([':id' => $id]);
    $prof = $stmt->fetch(\PDO::FETCH_ASSOC);

    if (!$prof) { echo json_encode(['erro' => 'Profissional não encontrada.']); exit; }

    $nomeCompleto = $prof['nome'] ?? '';
    $partes = explode(' ', $nomeCompleto, 2);
    $prof['nome_simples'] = $partes[0] ?? '';
    $prof['sobrenome'] = $partes[1] ?? '';

    $prof['id'] = (int)$prof['id'];
    $prof['usuario_id'] = (int)$prof['usuario_id'];
    $prof['ativo'] = (int)$prof['ativo'];

    echo json_encode($prof, JSON_UNESCAPED_UNICODE);
    exit;
}

/* ── TOGGLE STATUS (BADGE) ── */
if ($acao === 'toggle') {
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if (!$id) { echo json_encode(['erro' => 'ID inválido.']); exit; }

    try {
        $stmt = $pdo->prepare("UPDATE profissionais SET ativo = NOT ativo WHERE id = :id");
        $stmt->execute([':id' => $id]);
        
        $row = $pdo->query("SELECT ativo, usuario_id FROM profissionais WHERE id = $id")->fetch(\PDO::FETCH_ASSOC);
        
        if ($row) {
            $pdo->prepare("UPDATE usuarios SET ativo = :at WHERE id = :uid")
                ->execute([':at' => $row['ativo'], ':uid' => $row['usuario_id']]);
            echo json_encode(['sucesso' => true, 'ativo' => (bool)$row['ativo']]);
        } else {
            echo json_encode(['erro' => 'Profissional não encontrada.']);
        }
    } catch (\Throwable $ex) {
        echo json_encode(['erro' => 'Erro ao alterar status operacional.']);
    }
    exit;
}

/* ── INTERRUPTOR DE STATUS INTEGRADO (SOFT DELETE OU REATIVAÇÃO) ── */
if ($acao === 'excluir') {
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    // Coleta o status enviado pelo front (1 para ativar, 0 para desativar). Padrão é 0.
    $novoStatus = filter_input(INPUT_GET, 'status', FILTER_VALIDATE_INT) ?? 0;
    
    if (!$id) { echo json_encode(['erro' => 'ID inválido.']); exit; }

    try {
        // Coleta o usuario_id associado ao profissional antes de alterar
        $row = $pdo->prepare("SELECT usuario_id FROM profissionais WHERE id = :id");
        $row->execute([':id' => $id]);
        $uid = $row->fetchColumn();

        // Altera para o status desejado (0 ou 1) na tabela profissionais
        $pdo->prepare("UPDATE profissionais SET ativo = :status WHERE id = :id")
            ->execute([':status' => $novoStatus, ':id' => $id]);
        
        // Altera o status na tabela usuarios correspondente
        if ($uid) {
            $pdo->prepare("UPDATE usuarios SET ativo = :status WHERE id = :uid")
                ->execute([':status' => $novoStatus, ':uid' => $uid]);
        }

        echo json_encode(['sucesso' => true]);
    } catch (\Throwable $ex) {
        echo json_encode(['erro' => 'Erro ao modificar status da profissional no banco de dados.']);
    }
    exit;
}

/* ── SALVAR (INSERT / UPDATE) ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id        = filter_input(INPUT_POST, 'id',         FILTER_VALIDATE_INT) ?: null;
    $nome      = filter_input(INPUT_POST, 'nome',       FILTER_DEFAULT);
    $sobrenome = filter_input(INPUT_POST, 'sobrenome',  FILTER_DEFAULT) ?? '';
    $apelido   = filter_input(INPUT_POST, 'apelido',    FILTER_DEFAULT);
    $email     = filter_input(INPUT_POST, 'email',       FILTER_VALIDATE_EMAIL);
    $whatsapp  = filter_input(INPUT_POST, 'whatsapp',    FILTER_DEFAULT) ?? '';
    $cor       = filter_input(INPUT_POST, 'cor_agenda',  FILTER_DEFAULT) ?? '#e91e8c';
    $foto      = filter_input(INPUT_POST, 'foto_url',    FILTER_VALIDATE_URL) ?: '';
    $ativo     = filter_input(INPUT_POST, 'ativo',       FILTER_VALIDATE_INT) ?? 1;
    $senha     = $_POST['senha'] ?? '';

    if (!$nome || !$apelido || !$email) {
        echo json_encode(['erro' => 'Nome, apelido e e-mail válidos são obrigatórios.']);
        exit;
    }

    $nomeCompleto = trim($nome . ' ' . $sobrenome);

    try {
        if ($id) {
            /* UPDATE */
            $pdo->prepare("UPDATE profissionais SET apelido=:ap, foto_url=:foto, cor_agenda=:cor, ativo=:at WHERE id=:id")
                ->execute([':ap' => $apelido, ':foto' => $foto, ':cor' => $cor, ':at' => $ativo, ':id' => $id]);

            $row = $pdo->prepare("SELECT usuario_id FROM profissionais WHERE id=:id");
            $row->execute([':id' => $id]);
            $uid = (int)$row->fetchColumn();
            
            if ($uid) {
                $pdo->prepare("UPDATE usuarios SET nome=:n, email=:e, whatsapp=:w, ativo=:a WHERE id=:id")
                    ->execute([':n' => $nomeCompleto, ':e' => $email, ':w' => $whatsapp, ':a' => $ativo, ':id' => $uid]);
                
                if ($senha && strlen(trim($senha)) >= 6) {
                    $hash = password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);
                    $pdo->prepare("UPDATE usuarios SET senha_hash=:h WHERE id=:id")
                        ->execute([':h' => $hash, ':id' => $uid]);
                }
            }
            $savedProfId = $id;

        } else {
            /* INSERT */
            if (!$senha || strlen(trim($senha)) < 6) {
                echo json_encode(['erro' => 'A senha é obrigatória para novos cadastros (mínimo 6 caracteres).']);
                exit;
            }

            $ck = $pdo->prepare("SELECT id FROM usuarios WHERE email=:e LIMIT 1");
            $ck->execute([':e' => $email]);
            if ($ck->fetch()) {
                echo json_encode(['erro' => 'Este e-mail já está cadastrado no sistema.']);
                exit;
            }

            $hash = password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);
            $pdo->prepare("INSERT INTO usuarios (nome, email, whatsapp, senha_hash, tipo, ativo) VALUES (:n, :e, :w, :h, 'funcionario', :a)")
                ->execute([':n' => $nomeCompleto, ':e' => $email, ':w' => $whatsapp, ':h' => $hash, ':a' => $ativo]);
            $uid = (int)$pdo->lastInsertId();

            $pdo->prepare("INSERT INTO profissionais (usuario_id, apelido, foto_url, cor_agenda, ativo) VALUES (:u, :ap, :foto, :cor, :at)")
                ->execute([':u' => $uid, ':ap' => $apelido, ':foto' => $foto, ':cor' => $cor, ':at' => $ativo]);
            $savedProfId = (int)$pdo->lastInsertId();
        }

        echo json_encode(['sucesso' => true, 'id' => $savedProfId], JSON_UNESCAPED_UNICODE);
    } catch (\Throwable $ex) {
        echo json_encode(['erro' => 'Erro interno no servidor ao processar os dados.']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['erro' => 'Método não permitido.']);