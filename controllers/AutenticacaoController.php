<?php
/**
 * controllers/AutenticacaoController.php
 */
declare(strict_types=1);

require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Profissional.php';

class AutenticacaoController
{
    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/views/login.php');
            exit;
        }

        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $senha = $_POST['senha'] ?? '';

        if (!$email || !$senha) {
            $this->redirecionar('/views/login.php', 'Preencha e-mail e senha.');
            return;
        }

        $usuario = Usuario::autenticar($email, $senha);

        if (!$usuario) {
            $this->redirecionar('/views/login.php', 'E-mail ou senha incorretos.');
            return;
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        session_regenerate_id(true);

        $_SESSION['user_id']    = $usuario->getId();
        $_SESSION['user_nome']  = $usuario->getNome();
        $_SESSION['user_tipo']  = $usuario->getTipo();
        $_SESSION['user_email'] = $usuario->getEmail();

        if ($usuario->getTipo() === 'funcionario') {
            $prof = Profissional::buscarPorUsuarioId($usuario->getId());
            $_SESSION['profissional_id'] = $prof?->getId();
        }

        match ($usuario->getTipo()) {
            'admin'       => header('Location: ' . BASE_URL . '/views/admin/dashboard.php'),
            'funcionario' => header('Location: ' . BASE_URL . '/views/funcionario/dashboard.php'),
            default       => header('Location: ' . BASE_URL . '/'),
        };
        exit;
    }

    public function logout(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION = [];
        session_destroy();
        header('Location: ' . BASE_URL . '/views/login.php');
        exit;
    }

    public static function exigirAutenticacao(array|string $tiposPermitidos = []): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (empty($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . '/views/login.php?msg=acesso_negado');
            exit;
        }

        $tipos = (array)$tiposPermitidos;
        if (!empty($tipos) && !in_array($_SESSION['user_tipo'], $tipos, true)) {
            header('Location: ' . BASE_URL . '/views/login.php?msg=sem_permissao');
            exit;
        }
    }

    public static function estaLogado(): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        return !empty($_SESSION['user_id']);
    }

    public static function tipoUsuario(): ?string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        return $_SESSION['user_tipo'] ?? null;
    }

    private function redirecionar(string $url, string $msg = ''): void
    {
        $query = $msg ? '?msg=' . urlencode($msg) : '';
        header('Location: ' . BASE_URL . $url . $query);
        exit;
    }
}