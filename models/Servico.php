<?php
/**
 * models/Servico.php
 * Entidade + DAO para a tabela `servicos`.
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';

class Servico
{
    // ─── Atributos privados ───────────────────────────────────────
    private ?int    $id          = null;
    private ?int    $categoriaId = null;
    private string  $nome        = '';
    private string  $descricao   = '';
    private int     $duracaoMin  = 60;
    private float   $preco       = 0.0;
    private string  $fotoUrl     = '';
    private bool    $ativo       = true;
    private int     $ordem       = 0;
    public ?string  $categoriaNome = null;

    // ─── Getters ─────────────────────────────────────────────────
    public function getId(): ?int       { return $this->id; }
    public function getCategoriaId(): ?int { return $this->categoriaId; }
    public function getNome(): string   { return $this->nome; }
    public function getDescricao(): string { return $this->descricao; }
    public function getDuracaoMin(): int { return $this->duracaoMin; }
    public function getPreco(): float   { return $this->preco; }
    public function getFotoUrl(): string { return $this->fotoUrl; }
    public function isAtivo(): bool     { return $this->ativo; }
    public function getOrdem(): int     { return $this->ordem; }

    // ─── Setters ─────────────────────────────────────────────────
    public function setId(?int $id): void           { $this->id = $id; }
    public function setCategoriaId(?int $v): void   { $this->categoriaId = $v; }
    public function setNome(string $v): void        { $this->nome = trim($v); }
    public function setDescricao(string $v): void   { $this->descricao = trim($v); }
    public function setDuracaoMin(int $v): void     { $this->duracaoMin = max(1, $v); }
    public function setPreco(float $v): void        { $this->preco = max(0.0, $v); }
    public function setFotoUrl(string $v): void     { $this->fotoUrl = trim($v); }
    public function setAtivo(bool $v): void         { $this->ativo = $v; }
    public function setOrdem(int $v): void          { $this->ordem = $v; }

    // ─── Hidratação a partir de array (resultado PDO) ─────────────
    public static function fromArray(array $row): self
    {
        $obj = new self();
        $obj->setId((int)$row['id']);
        $obj->setCategoriaId($row['categoria_id'] !== null ? (int)$row['categoria_id'] : null);
        $obj->setNome($row['nome'] ?? '');
        $obj->setDescricao($row['descricao'] ?? '');
        $obj->setDuracaoMin((int)($row['duracao_min'] ?? 60));
        $obj->setPreco((float)($row['preco'] ?? 0));
        $obj->setFotoUrl($row['foto_url'] ?? '');
        $obj->setAtivo((bool)($row['ativo'] ?? true));
        $obj->setOrdem((int)($row['ordem'] ?? 0));
        return $obj;
    }

    // ─────────────────────────────────────────────────────────────
    // MÉTODOS DAO
    // ─────────────────────────────────────────────────────────────

    /**
     * Lista todos os serviços ativos (usados no Passo 1 do cliente).
     * Inclui nome da categoria para exibição agrupada.
     *
     * @return self[]
     */
    public static function listarAtivos(): array
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("
            SELECT s.*, c.nome AS categoria_nome
              FROM servicos s
              LEFT JOIN categorias_servico c ON c.id = s.categoria_id
             WHERE s.ativo = 1
             ORDER BY s.ordem ASC, s.nome ASC
        ");
        $stmt->execute();

        $servicos = [];
        while ($row = $stmt->fetch()) {
            $obj = self::fromArray($row);
            // Injeta campo extra não mapeado como propriedade pública temporária
            $obj->categoriaNome = $row['categoria_nome'] ?? '';
            $servicos[] = $obj;
        }
        return $servicos;
    }

    /**
     * Lista TODOS os serviços (admin/painel).
     *
     * @return self[]
     */
    public static function listarTodos(): array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare("
            SELECT s.*, c.nome AS categoria_nome
              FROM servicos s
              LEFT JOIN categorias_servico c ON c.id = s.categoria_id
             ORDER BY s.ativo DESC, s.ordem ASC, s.nome ASC
        ");
        $stmt->execute();

        $servicos = [];
        while ($row = $stmt->fetch()) {
            $obj = self::fromArray($row);
            $obj->categoriaNome = $row['categoria_nome'] ?? '';
            $servicos[] = $obj;
        }
        return $servicos;
    }

    /**
     * Busca serviço por ID.
     */
    public static function buscarPorId(int $id): ?self
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM servicos WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row  = $stmt->fetch();
        return $row ? self::fromArray($row) : null;
    }

    /**
     * Insere ou atualiza o serviço no banco.
     * Se $this->id for null, executa INSERT; caso contrário, UPDATE.
     */
    public function salvar(): bool
    {
        $pdo = Database::getInstance();

        if ($this->id === null) {
            // INSERT
            $stmt = $pdo->prepare("
                INSERT INTO servicos
                    (categoria_id, nome, descricao, duracao_min, preco, foto_url, ativo, ordem)
                VALUES
                    (:cat, :nome, :desc, :dur, :preco, :foto, :ativo, :ordem)
            ");
        } else {
            // UPDATE
            $stmt = $pdo->prepare("
                UPDATE servicos
                   SET categoria_id = :cat,
                       nome         = :nome,
                       descricao    = :desc,
                       duracao_min  = :dur,
                       preco        = :preco,
                       foto_url     = :foto,
                       ativo        = :ativo,
                       ordem        = :ordem
                 WHERE id = :id
            ");
            $stmt->bindValue(':id', $this->id, PDO::PARAM_INT);
        }

        $stmt->bindValue(':cat',   $this->categoriaId, $this->categoriaId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':nome',  $this->nome,   PDO::PARAM_STR);
        $stmt->bindValue(':desc',  $this->descricao, PDO::PARAM_STR);
        $stmt->bindValue(':dur',   $this->duracaoMin, PDO::PARAM_INT);
        $stmt->bindValue(':preco', $this->preco);
        $stmt->bindValue(':foto',  $this->fotoUrl, PDO::PARAM_STR);
        $stmt->bindValue(':ativo', (int)$this->ativo, PDO::PARAM_INT);
        $stmt->bindValue(':ordem', $this->ordem,  PDO::PARAM_INT);

        $ok = $stmt->execute();

        if ($ok && $this->id === null) {
            $this->id = (int)$pdo->lastInsertId();
        }

        return $ok;
    }

    /**
     * Alterna o status ativo/inativo do serviço.
     * Serviços inativos não aparecem no Passo 1 do cliente.
     */
    public function alternarStatus(): bool
    {
        if ($this->id === null) {
            return false;
        }

        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare("UPDATE servicos SET ativo = NOT ativo WHERE id = :id");
        $ok   = $stmt->execute([':id' => $this->id]);

        if ($ok) {
            $this->ativo = !$this->ativo;
        }

        return $ok;
    }

    /**
     * Remove o serviço (soft-delete preferido; use este apenas se necessário).
     */
    public function excluir(): bool
    {
        if ($this->id === null) return false;
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare("DELETE FROM servicos WHERE id = :id");
        return $stmt->execute([':id' => $this->id]);
    }

    /**
     * Serializa para array simples (útil para respostas JSON).
     */
    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'categoria_id'=> $this->categoriaId,
            'nome'        => $this->nome,
            'descricao'   => $this->descricao,
            'duracao_min' => $this->duracaoMin,
            'preco'       => $this->preco,
            'foto_url'    => $this->fotoUrl,
            'ativo'       => $this->ativo,
            'ordem'       => $this->ordem,
        ];
    }
}
