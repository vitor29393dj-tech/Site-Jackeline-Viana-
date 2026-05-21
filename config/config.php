<?php
/**
 * config/config.php
 * Constantes globais do sistema.
 */
declare(strict_types=1);

// Ativa o buffer de saída para segurar qualquer espaço acidental na memória
ob_start();

// ─── Dados da loja ───────────────────────────────────────────
define('LOJA_NOME',       'Jackeline Viana Noivas & Festas');
define('WHATSAPP_LOJA',   '5596999990000'); 
define('LOJA_EMAIL',      'contato@jackelineviana.com');
define('LOJA_ENDERECO',   'Macapá – AP');

// ─── URLs base ───────────────────────────────────────────────
// Foco exclusivo na pasta do projeto atual
define('BASE_URL',  'http://localhost/Site_agendamento');   // Sem barra final
define('ASSET_URL', BASE_URL . '/assets');

// ─── Fuso horário ────────────────────────────────────────────
date_default_timezone_set('America/Sao_Paulo');

// ─── Segurança de sessão ─────────────────────────────────────
ini_set('session.cookie_httponly', '1');
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_samesite', 'Lax');

// ─── Erros (desative em produção) ────────────────────────────
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

