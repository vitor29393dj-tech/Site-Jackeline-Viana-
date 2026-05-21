<?php
/**
 * models/Profissional.php
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';

class Profissional
{
    private ?int   $id         = null;
    private ?int   $usuarioId  = null;
    private string $apelido    = '';
    private string $fotoUrl    = '';
    private string $corAgenda  = '#e91e8c';
    private bool   $ativo      = true;
    // Campos extras do JOIN com usuarios
    public string  $nomeCompleto = '';
    public string  $email        = '';

    // Getters
    public function getId(): ?int       { return $this->id; }
    public function getUsuarioId(): ?int { return $this->usuarioId; }
    public function getApelido(): string { return $this->apelido; }
    public function getFotoUrl(): string { return $this->fotoUrl; }
    public function getCorAgenda(): string { return $this->corAgenda; }
    public function isAtivo(): bool     { return $this->ativo; }

    // Setters
    public function setId(?int $v): void       { $this->id = $v; }
    public function setUsuarioId(?int $v): void { $this->usuarioId = $v; }
    public function setApelido(string $v): void { $this->apelido = trim($v); }
    public function setFotoUrl(string $v): void { $this->fotoUrl = trim($v); }
    public function setCorAgenda(string $v): void { $this->corAgenda = trim($v); }
    public function setAtivo(bool $v): void    { $this->ativo = $v; }

    public static function fromArray(array $row): self
    {
        $obj = new self();
        $obj->setId((int)$row['id']);
        $obj->setUsuarioId((int)($row['usuario_id'] ?? 0));
        $obj->setApelido($row['apelido'] ?? '');
        $obj->setFotoUrl($row['foto_url'] ?? '');
        $obj->setCorAgenda($row['cor_agenda'] ?? '#e91e8c');
        $obj->setAtivo((bool)($row['ativo'] ?? true));
        $obj->nomeCompleto = $row['nome_completo'] ?? '';
        $obj->email        = $row['email'] ?? '';
        return $obj;
    }

    /** Lista todos os profissionais ativos com dados do usuário. */
    public static function listarTodos(): array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare("
            SELECT p.*, u.nome AS nome_completo, u.email
              FROM profissionais p
              JOIN usuarios u ON u.id = p.usuario_id
             WHERE p.ativo = 1
             ORDER BY p.apelido ASC
        ");
        $stmt->execute();
        $result = [];
        while ($row = $stmt->fetch()) {
            $result[] = self::fromArray($row);
        }
        return $result;
    }

    /** Busca profissional por ID. */
    public static function buscarPorId(int $id): ?self
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare("
            SELECT p.*, u.nome AS nome_completo, u.email
              FROM profissionais p
              JOIN usuarios u ON u.id = p.usuario_id
             WHERE p.id = :id LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ? self::fromArray($row) : null;
    }

    /** Busca profissional pelo usuario_id (para login). */
    public static function buscarPorUsuarioId(int $usuarioId): ?self
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare("
            SELECT p.*, u.nome AS nome_completo, u.email
              FROM profissionais p
              JOIN usuarios u ON u.id = p.usuario_id
             WHERE p.usuario_id = :uid LIMIT 1
        ");
        $stmt->execute([':uid' => $usuarioId]);
        $row = $stmt->fetch();
        return $row ? self::fromArray($row) : null;
    }

    public function toArray(): array
    {
        return [
            'id'           => $this->id,
            'apelido'      => $this->apelido,
            'foto_url'     => $this->fotoUrl,
            'cor_agenda'   => $this->corAgenda,
            'ativo'        => $this->ativo,
            'nome_completo'=> $this->nomeCompleto,
        ];
    }
}
