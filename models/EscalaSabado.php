<?php
declare(strict_types=1);

/**
 * models/EscalaSabado.php
 */

require_once __DIR__ . '/../config/Database.php';

class EscalaSabado
{
    public bool $trabalha;
    public string $turno;
    public string $horaInicio;
    public string $horaFim;
    public bool $ehExcecao;
    public string $origem;

    public function __construct(bool $trabalha, string $turno, string $horaInicio, string $horaFim, bool $ehExcecao, string $origem)
    {
        $this->trabalha = $trabalha;
        $this->turno = $turno;
        $this->horaInicio = $horaInicio;
        $this->horaFim = $horaFim;
        $this->ehExcecao = $ehExcecao;
        $this->origem = $origem;
    }

    public function toArray(): array
    {
        return [
            'trabalha'  => $this->trabalha,
            'turno'     => $this->turno,
            'horaInicio'=> $this->horaInicio,
            'horaFim'   => $this->horaFim,
            'ehExcecao' => $this->ehExcecao,
            'origem'    => $this->origem,
        ];
    }

    public static function getTurnosEfetivos(int $profissionalId, string $data): array
    {
        $pdo = Database::getInstance();
        $diaSemana = (int)(new DateTimeImmutable($data))->format('w');

        if ($diaSemana === 0) {
            return [];
        }

        if ($diaSemana === 6) {
            $stmtEx = $pdo->prepare("SELECT tipo, hora_inicio, hora_fim FROM escala_sabado_excecao WHERE profissional_id = :pid AND data_sabado = :data");
            $stmtEx->execute([':pid' => $profissionalId, ':data' => $data]);
            $excecao = $stmtEx->fetch(PDO::FETCH_ASSOC);

            if ($excecao) {
                if ($excecao['tipo'] === 'folga') {
                    return [];
                }
                if ($excecao['tipo'] === 'personalizado') {
                    return [[
                        'hora_inicio' => $excecao['hora_inicio'],
                        'hora_fim'    => $excecao['hora_fim'],
                        'turno_label' => 'Alteração Manual',
                    ]];
                }
                $turnoForcado = $excecao['tipo'];
            }

            $stmtConfig = $pdo->prepare("SELECT * FROM escala_sabado_config WHERE profissional_id = :pid AND ativa = 1");
            $stmtConfig->execute([':pid' => $profissionalId]);
            $config = $stmtConfig->fetch(PDO::FETCH_ASSOC);

            if (!$config && !isset($turnoForcado)) {
                return [];
            }

            $turnoEfetivo = $turnoForcado ?? ($config['turno_inicial'] ?? 'manha');
            if ($config && !isset($turnoForcado)) {
                $dataRef = new DateTimeImmutable($config['data_referencia']);
                $dataAtual = new DateTimeImmutable($data);
                $diferencaDias = $dataRef->diff($dataAtual)->days;
                $semanasDesdeReferencia = (int) floor($diferencaDias / 7);
                if ($semanasDesdeReferencia % 2 !== 0) {
                    $turnoEfetivo = ($config['turno_inicial'] === 'manha') ? 'tarde' : 'manha';
                }
            }

            if ($turnoEfetivo === 'manha') {
                return [[
                    'hora_inicio' => $config['manha_inicio'] ?? '08:00:00',
                    'hora_fim'    => $config['manha_fim'] ?? '12:00:00',
                    'turno_label' => 'Sábado (Manhã)',
                ]];
            }

            return [[
                'hora_inicio' => $config['tarde_inicio'] ?? '14:00:00',
                'hora_fim'    => $config['tarde_fim'] ?? '18:00:00',
                'turno_label' => 'Sábado (Tarde)',
            ]];
        }

        $stmt = $pdo->prepare("SELECT hora_inicio, hora_fim, turno_label FROM horarios_funcionamento WHERE profissional_id = :pid AND dia_semana = :dia AND ativo = 1 ORDER BY turno ASC");
        $stmt->execute([':pid' => $profissionalId, ':dia' => $diaSemana]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function buscarConfig(int $profissionalId): ?array
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM escala_sabado_config WHERE profissional_id = :pid LIMIT 1");
        $stmt->execute([':pid' => $profissionalId]);
        $config = $stmt->fetch(PDO::FETCH_ASSOC);
        return $config ?: null;
    }

    public static function mapearProximosSabados(int $profissionalId, int $quantidade): array
    {
        $resultado = [];
        $data = new DateTimeImmutable('today');
        while ((int) $data->format('w') !== 6) {
            $data = $data->modify('+1 day');
        }

        for ($i = 0; $i < $quantidade; $i++) {
            $proximo = $data->modify("+{$i} weeks");
            $resultado[$proximo->format('Y-m-d')] = self::calcularParaData($profissionalId, $proximo->format('Y-m-d'));
        }

        return $resultado;
    }

    public static function calcularParaData(int $profissionalId, string $data): self
    {
        $pdo = Database::getInstance();
        $diaSemana = (int)(new DateTimeImmutable($data))->format('w');

        if ($diaSemana === 0) {
            return new self(false, 'folga', '', '', false, 'domingo');
        }

        if ($diaSemana === 6) {
            $stmtEx = $pdo->prepare("SELECT tipo, hora_inicio, hora_fim FROM escala_sabado_excecao WHERE profissional_id = :pid AND data_sabado = :data");
            $stmtEx->execute([':pid' => $profissionalId, ':data' => $data]);
            $excecao = $stmtEx->fetch(PDO::FETCH_ASSOC);

            if ($excecao) {
                if ($excecao['tipo'] === 'folga') {
                    return new self(false, 'folga', '', '', true, 'manual');
                }
                if ($excecao['tipo'] === 'personalizado') {
                    return new self(true, 'personalizado', substr($excecao['hora_inicio'], 0, 5), substr($excecao['hora_fim'], 0, 5), true, 'manual');
                }
                $turno = $excecao['tipo'];
            }

            $config = self::buscarConfig($profissionalId);
            if (!$config && empty($turno)) {
                return new self(false, 'folga', '', '', false, 'automatico');
            }

            if (empty($turno)) {
                $turno = $config['turno_inicial'] ?? 'manha';
                $dataRef = new DateTimeImmutable($config['data_referencia']);
                $diferencaDias = $dataRef->diff(new DateTimeImmutable($data))->days;
                $semanasDesdeReferencia = (int) floor($diferencaDias / 7);
                if ($semanasDesdeReferencia % 2 !== 0) {
                    $turno = ($config['turno_inicial'] === 'manha') ? 'tarde' : 'manha';
                }
            }

            $horaInicio = substr(($turno === 'tarde' ? ($config['tarde_inicio'] ?? '14:00') : ($config['manha_inicio'] ?? '08:00')), 0, 5);
            $horaFim = substr(($turno === 'tarde' ? ($config['tarde_fim'] ?? '18:00') : ($config['manha_fim'] ?? '12:00')), 0, 5);
            return new self(true, $turno, $horaInicio, $horaFim, !empty($excecao), !empty($excecao) ? 'manual' : 'automatico');
        }

        $turnos = self::getTurnosEfetivos($profissionalId, $data);
        if (empty($turnos)) {
            return new self(false, 'folga', '', '', false, 'normal');
        }

        $primeiro = $turnos[0];
        $turno = $primeiro['turno_label'] ?? 'turno';
        $horaInicio = substr($primeiro['hora_inicio'], 0, 5);
        $horaFim = substr($primeiro['hora_fim'], 0, 5);
        return new self(true, $turno, $horaInicio, $horaFim, false, 'normal');
    }

    public static function salvarConfig(int $profissionalId, bool $ativa, string $dataReferencia, string $turnoInicial, string $manhaInicio, string $manhaFim, string $tardeInicio, string $tardeFim): void
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT id FROM escala_sabado_config WHERE profissional_id = :pid LIMIT 1");
        $stmt->execute([':pid' => $profissionalId]);
        $exists = $stmt->fetchColumn();

        if ($exists) {
            $stmt = $pdo->prepare("UPDATE escala_sabado_config SET ativa = :ativa, data_referencia = :data_ref, turno_inicial = :turno_ini, manha_inicio = :manha_ini, manha_fim = :manha_fim, tarde_inicio = :tarde_ini, tarde_fim = :tarde_fim WHERE profissional_id = :pid");
        } else {
            $stmt = $pdo->prepare("INSERT INTO escala_sabado_config (profissional_id, ativa, data_referencia, turno_inicial, manha_inicio, manha_fim, tarde_inicio, tarde_fim) VALUES (:pid, :ativa, :data_ref, :turno_ini, :manha_ini, :manha_fim, :tarde_ini, :tarde_fim)");
        }

        $stmt->execute([
            ':pid'      => $profissionalId,
            ':ativa'    => $ativa ? 1 : 0,
            ':data_ref' => $dataReferencia,
            ':turno_ini'=> $turnoInicial,
            ':manha_ini'=> $manhaInicio,
            ':manha_fim'=> $manhaFim,
            ':tarde_ini'=> $tardeInicio,
            ':tarde_fim'=> $tardeFim,
        ]);
    }

    public static function salvarExcecao(int $profissionalId, string $dataSabado, string $tipo, ?string $horaInicio, ?string $horaFim, string $motivo = '', ?int $usuarioId = null): void
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT id FROM escala_sabado_excecao WHERE profissional_id = :pid AND data_sabado = :data LIMIT 1");
        $stmt->execute([':pid' => $profissionalId, ':data' => $dataSabado]);
        $exists = $stmt->fetchColumn();

        if ($exists) {
            $stmt = $pdo->prepare("UPDATE escala_sabado_excecao SET tipo = :tipo, hora_inicio = :hora_inicio, hora_fim = :hora_fim, motivo = :motivo, usuario_id = :uid WHERE profissional_id = :pid AND data_sabado = :data");
        } else {
            $stmt = $pdo->prepare("INSERT INTO escala_sabado_excecao (profissional_id, data_sabado, tipo, hora_inicio, hora_fim, motivo, usuario_id) VALUES (:pid, :data, :tipo, :hora_inicio, :hora_fim, :motivo, :uid)");
        }

        $stmt->execute([
            ':pid'        => $profissionalId,
            ':data'       => $dataSabado,
            ':tipo'       => $tipo,
            ':hora_inicio'=> $horaInicio,
            ':hora_fim'   => $horaFim,
            ':motivo'     => $motivo,
            ':uid'        => $usuarioId,
        ]);
    }

    public static function removerExcecao(int $profissionalId, string $dataSabado): void
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("DELETE FROM escala_sabado_excecao WHERE profissional_id = :pid AND data_sabado = :data");
        $stmt->execute([':pid' => $profissionalId, ':data' => $dataSabado]);
    }
}
