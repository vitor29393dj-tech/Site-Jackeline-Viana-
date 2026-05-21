<?php
/**
 * api/dias-disponiveis.php
 */
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../controllers/AgendamentoController.php';

$controller = new AgendamentoController();
$controller->getDiasDisponiveis();
