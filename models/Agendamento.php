<?php
/**
 * models/Agendamento.php — ATUALIZADO COM SUPORTE A TURNOS E ESCALAS
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/EscalaSabado.php';

class Agendamento
{
    private ?int    $id             = null;
    private ?int    $usuarioId      = null;
    private int     $profissionalId = 0;
    private int     $servicoId      = 0;
    private string  $nomeCliente    = '';
    private string  $whatsappCliente = '';
    private string  $emailCliente   = '';
    private ?string $dataHoraInicio = null;
    private ?string $dataHoraFim    = null;
    private string  $status         = 'pendente';
    private string  $observacoes    = '';
    private ?string $gcalLink       = null;

    // Getters
    public function getId(): ?int              { return $this->id; }
    public function getUsuarioId(): ?int       { return $this->usuarioId; }
    public function getProfissionalId(): int   { return $this->profissionalId; }
    public function getServicoId(): int        { return $this->servicoId; }
    public function getNomeCliente(): string   { return $this->nomeCliente; }
    public function getWhatsappCliente(): string { return $this->whatsappCliente; }
    public function getEmailCliente(): string  { return $this->emailCliente; }
    public function getDataHoraInicio(): ?string { return $this->dataHoraInicio; }
    public function getDataHoraFim(): ?string  { return $this->dataHoraFim; }
    public function getStatus(): string        { return $this->status; }
    public function getGcalLink(): ?string     { return $this->gcalLink; }

    // Setters
    public function setId(?int $v): void               { $this->id = $v; }
    public function setUsuarioId(?int $v): void        { $this->usuarioId = $v; }
    public function setProfissionalId(int $v): void    { $this->profissionalId = $v; }
    public function setServicoId(int $v): void         { $this->servicoId = $v; }
    public function setNomeCliente(string $v): void    { $this->nomeCliente = trim($v); }
    public function setWhatsappCliente(string $v): void { $this->whatsappCliente = trim($v); }
    public function setEmailCliente(string $v): void   { $this->emailCliente = trim($v); }
    public function setDataHoraInicio(string $v): void { $this->dataHoraInicio = $v; }
    public function setDataHoraFim(string $v): void    { $this->dataHoraFim = $v; }
    public function setStatus(string $v): void         { $this->status = $v; }
    public function setObservacoes(string $v): void    { $this->observacoes = trim($v); }
    public function setGcalLink(?string $v): void      { $this->gcalLink = $v; }

    public static function fromArray(array $row): self
    {
        $obj = new self();
        $obj->setId((int)$row['id']);
        $obj->setUsuarioId($row['usuario_id'] !== null ? (int)$row['usuario_id'] : null);
        $obj->setProfissionalId((int)$row['profissional_id']);
        $obj->setServicoId((int)$row['servico_id']);
        $obj->setNomeCliente($row['nome_cliente'] ?? '');
        $obj->setWhatsappCliente($row['whatsapp_cliente'] ?? '');
        $obj->setEmailCliente($row['email_cliente'] ?? '');
        $obj->setDataHoraInicio($row['data_hora_inicio'] ?? '');
        $obj->setDataHoraFim($row['data_hora_fim'] ?? '');
        $obj->setStatus($row['status'] ?? 'pendente');
        $obj->setObservacoes($row['observacoes'] ?? '');
        $obj->setGcalLink($row['gcal_link'] ?? null);
        return $obj;
    }

    /**
     * Verifica se um slot (profissional + data/hora) está disponível percorrendo múltiplos turnos.
     */
    public static function checarDisponibilidade(
        int    $profissionalId,
        string $dataHoraInicio,
        string $dataHoraFim,
        ?int   $ignorarAgId = null
    ): bool {
        $pdo = Database::getInstance();

        // 1. Verifica conflito com agendamentos existentes
        $sqlAg = "
            SELECT COUNT(*) FROM agendamentos
             WHERE profissional_id = :pid
               AND status NOT IN ('cancelado')
               AND data_hora_inicio < :fim
               AND data_hora_fim   > :ini
        ";
        if ($ignorarAgId !== null) {
            $sqlAg .= " AND id != :igid";
        }
        $stmtAg = $pdo->prepare($sqlAg);
        $stmtAg->bindValue(':pid', $profissionalId, PDO::PARAM_INT);
        $stmtAg->bindValue(':ini', $dataHoraInicio);
        $stmtAg->bindValue(':fim', $dataHoraFim);
        if ($ignorarAgId !== null) {
            $stmtAg->bindValue(':igid', $ignorarAgId, PDO::PARAM_INT);
        }
        $stmtAg->execute();
        if ((int)$stmtAg->fetchColumn() > 0) return false;

        // 2. Verifica bloqueios
        $stmtBl = $pdo->prepare("
            SELECT COUNT(*) FROM bloqueios
             WHERE (profissional_id = :pid OR profissional_id IS NULL)
               AND data_inicio < :fim
               AND data_fim    > :ini
        ");
        $stmtBl->execute([':pid' => $profissionalId, ':ini' => $dataHoraInicio, ':fim' => $dataHoraFim]);
        if ((int)$stmtBl->fetchColumn() > 0) return false;

        return true;
    }

    /**
     * Retorna os horários disponíveis considerando múltiplos turnos, intervalos e escala de sábado.
     */
    public static function getHorariosDisponiveis(
        int    $profissionalId,
        string $data,
        int    $duracaoMin = 60
    ): array {
        $slots = [];
        
        // Obtém os turnos de trabalho efetivos para o dia solicitado (Trata Seg-Sex e a lógica de Sábado)
        $turnos = EscalaSabado::getTurnosEfetivos($profissionalId, $data);
        if (empty($turnos)) return [];

        $intervalo = new DateInterval('PT' . $duracaoMin . 'M');

        foreach ($turnos as $turno) {
            $inicio = new DateTime($data . ' ' . $turno['hora_inicio']);
            $fim    = new DateTime($data . ' ' . $turno['hora_fim']);

            $cursor = clone $inicio;
            while (true) {
                $slotFim = clone $cursor;
                $slotFim->add($intervalo);
                if ($slotFim > $fim) break;

                $disponivel = self::checarDisponibilidade(
                    $profissionalId,
                    $cursor->format('Y-m-d H:i:s'),
                    $slotFim->format('Y-m-d H:i:s')
                );

                $slots[] = [
                    'hora'       => $cursor->format('H:i'),
                    'hora_fim'   => $slotFim->format('H:i'),
                    'disponivel' => $disponivel,
                    'turno_label'=> $turno['turno_label'] ?? ''
                ];

                $cursor->add($intervalo);
            }
        }

        return $slots;
    }

    /**
     * Persiste o agendamento no banco.
     */
    public function salvarAgendamento(): bool
    {
        $pdo = Database::getInstance();
        $this->gcalLink = $this->gerarGcalLink();

        $stmt = $pdo->prepare("
            INSERT INTO agendamentos
                (usuario_id, profissional_id, servico_id,
                 nome_cliente, whatsapp_cliente, email_cliente,
                 data_hora_inicio, data_hora_fim, status, observacoes, gcal_link)
            VALUES
                (:uid, :pid, :sid,
                 :nome, :wpp, :email,
                 :ini, :fim, 'pendente', :obs, :gcal)
        ");

        $ok = $stmt->execute([
            ':uid'   => $this->usuarioId,
            ':pid'   => $this->profissionalId,
            ':sid'   => $this->servicoId,
            ':nome'  => $this->nomeCliente,
            ':wpp'   => $this->whatsappCliente,
            ':email' => $this->emailCliente,
            ':ini'   => $this->dataHoraInicio,
            ':fim'   => $this->dataHoraFim,
            ':obs'   => $this->observacoes,
            ':gcal'  => $this->gcalLink,
        ]);

        if ($ok) {
            $this->id = (int)$pdo->lastInsertId();
        }

        return $ok;
    }

    /**
     * Lista agendamentos de um profissional específico.
     */
    public static function listarPorProfissional(int $profissionalId, ?string $data = null): array
    {
        $pdo  = Database::getInstance();
        $sql  = "
            SELECT a.*, s.nome AS servico_nome, s.duracao_min,
                   p.apelido AS profissional_apelido
              FROM agendamentos a
              JOIN servicos      s ON s.id = a.servico_id
              JOIN profissionais p ON p.id = a.profissional_id
             WHERE a.profissional_id = :pid
        ";

        $params = [':pid' => $profissionalId];

        if ($data !== null) {
            $sql .= " AND DATE(a.data_hora_inicio) = :data";
            $params[':data'] = $data;
        }

        $sql .= " ORDER BY a.data_hora_inicio ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Lista todos os agendamentos (admin).
     */
    public static function listarTodos(?string $dataInicio = null, ?string $dataFim = null): array
    {
        $pdo    = Database::getInstance();
        $sql    = "
            SELECT a.*, s.nome AS servico_nome,
                   p.apelido AS profissional_apelido, p.cor_agenda
              FROM agendamentos a
              JOIN servicos      s ON s.id = a.servico_id
              JOIN profissionais p ON p.id = a.profissional_id
             WHERE 1=1
        ";
        $params = [];

        if ($dataInicio) { $sql .= " AND a.data_hora_inicio >= :di"; $params[':di'] = $dataInicio . ' 00:00:00'; }
        if ($dataFim)    { $sql .= " AND a.data_hora_inicio <= :df"; $params[':df'] = $dataFim    . ' 23:59:59'; }

        $sql .= " ORDER BY a.data_hora_inicio ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Atualiza status de um agendamento. */
    public static function atualizarStatus(int $id, string $status): bool
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare("UPDATE agendamentos SET status = :s WHERE id = :id");
        return $stmt->execute([':s' => $status, ':id' => $id]);
    }

    /** Gera link de adicionar ao Google Calendar. */
    private function gerarGcalLink(): string
    {
        $ini   = str_replace(['-', ':', ' '], ['', '', 'T'], $this->dataHoraInicio ?? '') . 'Z';
        $fim   = str_replace(['-', ':', ' '], ['', '', 'T'], $this->dataHoraFim ?? '') . 'Z';
        $texto = urlencode('Agendamento - Jackeline Viana Noivas & Festas');
        return "https://calendar.google.com/calendar/render?action=TEMPLATE&text={$texto}&dates={$ini}/{$fim}";
    }

    public function toArray(): array
    {
        return [
            'id'               => $this->id,
            'usuario_id'       => $this->usuarioId,
            'profissional_id'  => $this->profissionalId,
            'servico_id'       => $this->servicoId,
            'nome_cliente'     => $this->nomeCliente,
            'whatsapp_cliente' => $this->whatsappCliente,
            'email_cliente'    => $this->emailCliente,
            'data_hora_inicio' => $this->dataHoraInicio,
            'data_hora_fim'    => $this->dataHoraFim,
            'status'           => $this->status,
            'gcal_link'        => $this->gcalLink,
        ];
    }
}