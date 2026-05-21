<?php
/**
 * controllers/DashboardController.php
 * Prepara os dados para o painel admin e o painel do funcionário.
 */
declare(strict_types=1);

require_once __DIR__ . '/../models/Agendamento.php';
require_once __DIR__ . '/../models/Profissional.php';
require_once __DIR__ . '/../models/Servico.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../controllers/AutenticacaoController.php';

class DashboardController
{
    /**
     * Dados para o painel ADMINISTRADOR.
     * Exige autenticação como admin.
     */
    public function dadosAdmin(): array
    {
        AutenticacaoController::exigirAutenticacao('admin');

        $dataInicio = filter_input(INPUT_GET, 'data_inicio', FILTER_SANITIZE_SPECIAL_CHARS)
            ?? date('Y-m-01');
        $dataFim    = filter_input(INPUT_GET, 'data_fim',    FILTER_SANITIZE_SPECIAL_CHARS)
            ?? date('Y-m-t');

        return [
            'agendamentos'  => Agendamento::listarTodos($dataInicio, $dataFim),
            'profissionais' => Profissional::listarTodos(),
            'servicos'      => Servico::listarTodos(),
            'data_inicio'   => $dataInicio,
            'data_fim'      => $dataFim,
            'usuario_nome'  => $_SESSION['user_nome'] ?? '',
        ];
    }

    /**
     * Dados para o painel FUNCIONÁRIO.
     * Escopo SQL restrito ao próprio profissional — funcionário jamais vê dados de colegas.
     */
    public function dadosFuncionario(): array
    {
        AutenticacaoController::exigirAutenticacao('funcionario');

        $profissionalId = (int)($_SESSION['profissional_id'] ?? 0);
        if ($profissionalId === 0) {
            // Funcionário sem perfil profissional cadastrado
            return ['erro' => 'Perfil profissional não configurado.'];
        }

        $data = filter_input(INPUT_GET, 'data', FILTER_SANITIZE_SPECIAL_CHARS)
            ?? date('Y-m-d');

        return [
            // Agendamentos DO DIA para este profissional (escopo seguro via SQL)
            'agendamentos_dia'  => Agendamento::listarPorProfissional($profissionalId, $data),
            // Todos os futuros agendamentos para o calendário
            'agendamentos_mes'  => Agendamento::listarPorProfissional($profissionalId),
            'profissional_id'   => $profissionalId,
            'data_selecionada'  => $data,
            'usuario_nome'      => $_SESSION['user_nome'] ?? '',
        ];
    }

    /**
     * API JSON: retorna agendamentos de um profissional para o calendário do admin.
     * GET /api/agendamentos.php?profissional_id=X&data_inicio=Y&data_fim=Z
     */
    public function getAgendamentosJson(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        AutenticacaoController::exigirAutenticacao(['admin', 'funcionario']);

        $tipo           = AutenticacaoController::tipoUsuario();
        $profissionalId = filter_input(INPUT_GET, 'profissional_id', FILTER_VALIDATE_INT);
        $dataInicio     = filter_input(INPUT_GET, 'data_inicio', FILTER_SANITIZE_SPECIAL_CHARS);
        $dataFim        = filter_input(INPUT_GET, 'data_fim',    FILTER_SANITIZE_SPECIAL_CHARS);

        // Funcionário só pode consultar a própria agenda
        if ($tipo === 'funcionario') {
            $profissionalId = (int)($_SESSION['profissional_id'] ?? 0);
        }

        $dados = Agendamento::listarTodos($dataInicio, $dataFim);

        // Filtra por profissional se informado
        if ($profissionalId) {
            $dados = array_filter($dados, fn($a) => (int)$a['profissional_id'] === $profissionalId);
        }

        echo json_encode(array_values($dados));
    }
}
