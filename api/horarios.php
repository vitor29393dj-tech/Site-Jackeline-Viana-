<?php
/**
 * api/horarios.php
 * Endpoint: GET /api/horarios.php?profissional_id=X&data=Y-m-d&servico_id=Z
 */
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php'; // Adicionado para garantir conexão com o banco
require_once __DIR__ . '/../controllers/AgendamentoController.php';

// CORREÇÃO CRUCIAL: Verificar e inicializar horários padrão se o profissional for novo
$profissionalId = filter_input(INPUT_GET, 'profissional_id', FILTER_VALIDATE_INT);

if ($profissionalId) {
    try {
        $pdo = Database::getInstance();
        
        // 1. Verifica se o profissional já tem algum horário cadastrado
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM horarios_funcionamento WHERE profissional_id = :pid");
        $stmtCheck->execute([':pid' => $profissionalId]);
        $possuiHorarios = (int)$stmtCheck->fetchColumn() > 0;

        // 2. Se não possuir nenhum registro, cria a estrutura padrão de segunda a sexta
        if (!$possuiHorarios) {
            $stmtInsert = $pdo->prepare("
                INSERT INTO horarios_funcionamento (profissional_id, dia_semana, hora_inicio, hora_fim, ativo)
                VALUES (:pid, :dia, :inicio, :fim, 1)
            ");
            
            // Dias da semana: 1 (Segunda) até 5 (Sexta)
            // Horário padrão espelhado na Bianca: Manhã (08:00-12:00) e Tarde (14:00-18:00)
            // Nota: Se sua tabela aceita apenas 1 turno por dia, inserimos o período cheio.
            // Ajustado para o padrão de 08:00 às 18:00 (você altera na tela depois)
            for ($dia = 1; $dia <= 5; $dia++) {
                $stmtInsert->execute([
                    ':pid'    => $profissionalId,
                    ':dia'    => $dia,
                    ':inicio' => '08:00:00',
                    ':fim'    => '18:00:00'
                ]);
            }
        }
    } catch (\Throwable $e) {
        // Ignora falhas silenciosamente para não quebrar a API caso a tabela mude
    }
}

// Executa o fluxo normal do controller
$controller = new AgendamentoController();
$controller->getHorarios();