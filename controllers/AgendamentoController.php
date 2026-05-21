<?php
/**
 * controllers/AgendamentoController.php
 * Gerencia o fluxo do agendamento público e requisições Fetch do cliente.
 */
declare(strict_types=1);

require_once __DIR__ . '/../models/Agendamento.php';
require_once __DIR__ . '/../models/Servico.php';
require_once __DIR__ . '/../models/Profissional.php';
require_once __DIR__ . '/../models/Usuario.php';

class AgendamentoController
{
    /**
     * Retorna JSON com os horários disponíveis para o Passo 3.
     * Chamado via Fetch: GET /api/horarios.php?profissional_id=X&data=Y-m-d&servico_id=Z
     */
    public function getHorarios(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $profissionalId = filter_input(INPUT_GET, 'profissional_id', FILTER_VALIDATE_INT);
        $data           = filter_input(INPUT_GET, 'data', FILTER_SANITIZE_SPECIAL_CHARS);
        $servicoId      = filter_input(INPUT_GET, 'servico_id', FILTER_VALIDATE_INT);

        if (!$profissionalId || !$data || !$servicoId) {
            echo json_encode(['erro' => 'Parâmetros inválidos.']);
            return;
        }

        // Valida formato da data
        $dt = DateTime::createFromFormat('Y-m-d', $data);
        if (!$dt || $dt->format('Y-m-d') !== $data) {
            echo json_encode(['erro' => 'Data inválida.']);
            return;
        }

        // Não permite datas passadas
        if ($dt < new DateTime('today')) {
            echo json_encode(['slots' => [], 'mensagem' => 'Data no passado.']);
            return;
        }

        $servico = Servico::buscarPorId($servicoId);
        if (!$servico || !$servico->isAtivo()) {
            echo json_encode(['erro' => 'Serviço não encontrado.']);
            return;
        }

        $slots = Agendamento::getHorariosDisponiveis(
            $profissionalId,
            $data,
            $servico->getDuracaoMin()
        );

        echo json_encode(['slots' => $slots]);
    }

    /**
     * Salva o agendamento vindo do Passo 4/5 (POST).
     * POST /api/agendar.php
     */
    public function salvar(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['erro' => 'Método não permitido.']);
            return;
        }

        // Lê e valida os campos
        $profissionalId = filter_input(INPUT_POST, 'profissional_id', FILTER_VALIDATE_INT);
        $servicoId      = filter_input(INPUT_POST, 'servico_id',      FILTER_VALIDATE_INT);
        $data           = filter_input(INPUT_POST, 'data',            FILTER_SANITIZE_SPECIAL_CHARS);
        $hora           = filter_input(INPUT_POST, 'hora',            FILTER_SANITIZE_SPECIAL_CHARS);
        $nome           = filter_input(INPUT_POST, 'nome',            FILTER_SANITIZE_SPECIAL_CHARS);
        $whatsapp       = filter_input(INPUT_POST, 'whatsapp',        FILTER_SANITIZE_SPECIAL_CHARS);
        $email          = filter_input(INPUT_POST, 'email',           FILTER_VALIDATE_EMAIL) ?: '';
        $criarConta     = filter_input(INPUT_POST, 'criar_conta',     FILTER_VALIDATE_BOOLEAN);
        $senha          = $_POST['senha'] ?? '';
        $observacoes    = filter_input(INPUT_POST, 'observacoes',     FILTER_SANITIZE_SPECIAL_CHARS) ?: '';

        // Validações básicas
        $erros = [];
        if (!$profissionalId) $erros[] = 'Profissional inválido.';
        if (!$servicoId)      $erros[] = 'Serviço inválido.';
        if (!$data || !$hora) $erros[] = 'Data/hora inválida.';
        if (!$nome)           $erros[] = 'Nome é obrigatório.';
        if (!$whatsapp)       $erros[] = 'WhatsApp é obrigatório.';

        if (!empty($erros)) {
            http_response_code(422);
            echo json_encode(['erros' => $erros]);
            return;
        }

        $servico = Servico::buscarPorId($servicoId);
        if (!$servico) {
            echo json_encode(['erro' => 'Serviço não encontrado.']);
            return;
        }

        $dataHoraInicio = "{$data} {$hora}:00";
        $dtInicio       = new DateTime($dataHoraInicio);
        $dtFim          = clone $dtInicio;
        $dtFim->add(new DateInterval('PT' . $servico->getDuracaoMin() . 'M'));
        $dataHoraFim    = $dtFim->format('Y-m-d H:i:s');

        // Checa disponibilidade
        if (!Agendamento::checarDisponibilidade($profissionalId, $dataHoraInicio, $dataHoraFim)) {
            http_response_code(409);
            echo json_encode(['erro' => 'Horário não disponível. Escolha outro.']);
            return;
        }

        // Cria conta de cliente se solicitado
        $usuarioId = null;
        if ($criarConta && $email && $senha) {
            $usuarioId = Usuario::cadastrarCliente($nome, $whatsapp, $email, $senha);
        }

        // Monta e salva o agendamento
        $ag = new Agendamento();
        $ag->setUsuarioId($usuarioId);
        $ag->setProfissionalId($profissionalId);
        $ag->setServicoId($servicoId);
        $ag->setNomeCliente($nome);
        $ag->setWhatsappCliente($whatsapp);
        $ag->setEmailCliente($email);
        $ag->setDataHoraInicio($dataHoraInicio);
        $ag->setDataHoraFim($dataHoraFim);
        $ag->setObservacoes($observacoes);

        if (!$ag->salvarAgendamento()) {
            http_response_code(500);
            echo json_encode(['erro' => 'Erro ao salvar agendamento.']);
            return;
        }

        // Monta links de confirmação
        $prof = Profissional::buscarPorId($profissionalId);
        $dataFormatada = $dtInicio->format('d/m/Y \à\s H:i');
        $msgCliente = urlencode(
            "Olá! 🌸 Confirmo meu agendamento no Atelier de Costura.\n" .
            "📅 Data: {$dataFormatada}\n" .
            "✂️ Serviço: {$servico->getNome()}\n" .
            "👗 Profissional: " . ($prof?->getApelido() ?? 'Atendente') . "\n" .
            "Nome: {$nome}"
        );

        $wppLoja   = preg_replace('/\D/', '', WHATSAPP_LOJA ?? '5596999990000');
        $wppCliente = preg_replace('/\D/', '', $whatsapp);

        echo json_encode([
            'sucesso'          => true,
            'agendamento_id'   => $ag->getId(),
            'gcal_link'        => $ag->getGcalLink(),
            'wpp_loja_link'    => "https://wa.me/{$wppLoja}?text={$msgCliente}",
            'wpp_cliente_link' => "https://wa.me/55{$wppCliente}?text={$msgCliente}",
            'resumo' => [
                'nome'         => $nome,
                'servico'      => $servico->getNome(),
                'profissional' => $prof?->getApelido() ?? 'Atendente',
                'data_hora'    => $dataFormatada,
            ],
        ]);
    }

    /**
     * Retorna JSON com dias disponíveis no mês para o calendário (Passo 3).
     * GET /api/dias-disponiveis.php?profissional_id=X&ano=Y&mes=M&servico_id=Z
     */
    public function getDiasDisponiveis(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $profissionalId = filter_input(INPUT_GET, 'profissional_id', FILTER_VALIDATE_INT);
        $ano            = filter_input(INPUT_GET, 'ano',  FILTER_VALIDATE_INT);
        $mes            = filter_input(INPUT_GET, 'mes',  FILTER_VALIDATE_INT);
        $servicoId      = filter_input(INPUT_GET, 'servico_id', FILTER_VALIDATE_INT);

        if (!$profissionalId || !$ano || !$mes || !$servicoId) {
            echo json_encode(['erro' => 'Parâmetros inválidos.']);
            return;
        }

        $servico = Servico::buscarPorId($servicoId);
        if (!$servico) {
            echo json_encode(['erro' => 'Serviço inválido.']);
            return;
        }

        $diasNoMes = cal_days_in_month(CAL_GREGORIAN, $mes, $ano);
        $hoje      = new DateTime('today');
        $dias      = [];

        for ($d = 1; $d <= $diasNoMes; $d++) {
            $data = sprintf('%04d-%02d-%02d', $ano, $mes, $d);
            $dt   = new DateTime($data);

            if ($dt < $hoje) {
                $dias[$d] = 'passado';
                continue;
            }

            $slots = Agendamento::getHorariosDisponiveis($profissionalId, $data, $servico->getDuracaoMin());
            $temDisponivel = count(array_filter($slots, fn($s) => $s['disponivel'])) > 0;
            $dias[$d]  = $temDisponivel ? 'disponivel' : 'indisponivel';
        }

        echo json_encode(['dias' => $dias]);
    }
}
