<?php
/**
 * config/Database.php
 * Classe de conexão ao banco de dados via PDO (padrão Singleton).
 */
declare(strict_types=1);

class Database
{
    private static ?PDO $instance = null;

    // ─── Configurações ───────────────────────────────────────────
    private const HOST    = 'localhost';
    private const DBNAME  = 'site_agendamento'; // Deve bater com o phpMyAdmin
    private const USER    = 'root';       
    private const PASS    = '';           
    private const CHARSET = 'utf8mb4';
    // ─────────────────────────────────────────────────────────────

    /** Construtor privado — impede instâncias externas. */
    private function __construct() {}

    /** Clonagem proibida. */
    private function __clone() {}

    /**
     * Retorna a instância única de PDO.
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                self::HOST,
                self::DBNAME,
                self::CHARSET
            );

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            ];

            try {
                self::$instance = new PDO($dsn, self::USER, self::PASS, $options);
            } catch (PDOException $e) {
                error_log('[DB ERROR] ' . $e->getMessage());
                http_response_code(500);
                die(json_encode(['erro' => 'Falha na conexão com o banco de dados.']));
            }
        }

        return self::$instance;
    }
}