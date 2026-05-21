<?php
/**
 * models/Usuario.php
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';

class Usuario
{
    private ?int   $id        = null;
    private string $nome      = '';
    private string $email     = '';
    private string $whatsapp  = '';
    private string $senhaHash = '';
    private string $tipo      = 'cliente'; // cliente | funcionario | admin
    private bool   $ativo     = true;

    // Getters
    public function getId(): ?int       { return $this->id; }
    public function getNome(): string   { return $this->nome; }
    public function getEmail(): string  { return $this->email; }
    public function getWhatsapp(): string { return $this->whatsapp; }
    public function getTipo(): string   { return $this->tipo; }
    public function isAtivo(): bool     { return $this->ativo; }

    // Setters
    public function setId(?int $v): void       { $this->id = $v; }
    public function setNome(string $v): void   { $this->nome = trim($v); }
    public function setEmail(string $v): void  { $this->email = strtolower(trim($v)); }
    public function setWhatsapp(string $v): void { $this->whatsapp = trim($v); }
    public function setTipo(string $v): void   { $this->tipo = $v; }
    public function setAtivo(bool $v): void    { $this->ativo = $v; }

    /** Define a senha (faz o hash automaticamente). */
    public function setSenha(string $senhaPlana): void
    {
        $this->senhaHash = password_hash($senhaPlana, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public static function fromArray(array $row): self
    {
        $obj = new self();
        $obj->setId((int)$row['id']);
        $obj->setNome($row['nome'] ?? '');
        $obj->setEmail($row['email'] ?? '');
        $obj->setWhatsapp($row['whatsapp'] ?? '');
        $obj->senhaHash = $row['senha_hash'] ?? '';
        $obj->setTipo($row['tipo'] ?? 'cliente');
        $obj->setAtivo((bool)($row['ativo'] ?? true));
        return $obj;
    }

    /**
     * Autentica usuário e retorna objeto ou null.
     */
    public static function autenticar(string $email, string $senhaPlana): ?self
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = :email AND ativo = 1 LIMIT 1");
        $stmt->execute([':email' => strtolower(trim($email))]);
        $row  = $stmt->fetch();

        if (!$row) return null;
        if (!password_verify($senhaPlana, $row['senha_hash'])) return null;

        return self::fromArray($row);
    }

    /**
     * Cadastra um novo cliente (vindo do Passo 4 do agendamento).
     * Retorna o ID inserido ou null em caso de e-mail duplicado.
     */
    public static function cadastrarCliente(string $nome, string $whatsapp, string $email, string $senhaPlana = ''): ?int
    {
        $pdo = Database::getInstance();

        // Verifica duplicidade
        $check = $pdo->prepare("SELECT id FROM usuarios WHERE email = :email LIMIT 1");
        $check->execute([':email' => strtolower(trim($email))]);
        if ($check->fetch()) return null;

        $hash = $senhaPlana !== ''
            ? password_hash($senhaPlana, PASSWORD_BCRYPT, ['cost' => 12])
            : '';

        $stmt = $pdo->prepare("
            INSERT INTO usuarios (nome, email, whatsapp, senha_hash, tipo)
            VALUES (:nome, :email, :wpp, :hash, 'cliente')
        ");
        $stmt->execute([
            ':nome'  => trim($nome),
            ':email' => strtolower(trim($email)),
            ':wpp'   => trim($whatsapp),
            ':hash'  => $hash,
        ]);

        return (int)$pdo->lastInsertId();
    }

    /**
     * Retorna histórico de agendamentos do cliente.
     */
    public static function verificarHistorico(int $usuarioId): array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare("
            SELECT a.id, a.data_hora_inicio, a.data_hora_fim, a.status,
                   s.nome AS servico, p.apelido AS profissional
              FROM agendamentos a
              JOIN servicos s     ON s.id = a.servico_id
              JOIN profissionais p ON p.id = a.profissional_id
             WHERE a.usuario_id = :uid
             ORDER BY a.data_hora_inicio DESC
             LIMIT 20
        ");
        $stmt->execute([':uid' => $usuarioId]);
        return $stmt->fetchAll();
    }

    /**
     * Busca usuário por ID.
     */
    public static function buscarPorId(int $id): ?self
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row  = $stmt->fetch();
        return $row ? self::fromArray($row) : null;
    }

    public function toArray(): array
    {
        return [
            'id'       => $this->id,
            'nome'     => $this->nome,
            'email'    => $this->email,
            'whatsapp' => $this->whatsapp,
            'tipo'     => $this->tipo,
            'ativo'    => $this->ativo,
        ];
    }
}
