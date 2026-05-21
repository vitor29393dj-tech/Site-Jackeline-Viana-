<?php
/**
 * api/horarios.php
 * Endpoint: GET /api/horarios.php?profissional_id=X&data=Y-m-d&servico_id=Z
 */
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../controllers/AgendamentoController.php';

$controller = new AgendamentoController();
$controller->getHorarios();
