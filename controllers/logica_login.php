<?php
/**
 * controllers/logica_login.php
 */
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../controllers/AutenticacaoController.php';

$controller = new AutenticacaoController();
$controller->login();
