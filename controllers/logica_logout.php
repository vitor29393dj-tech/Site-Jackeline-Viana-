<?php
/**
 * controllers/logica_logout.php
 */
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../controllers/AutenticacaoController.php';
header('Location: ' . BASE_URL . '/views/login.php');

$controller = new AutenticacaoController();
$controller->logout();
