-- ============================================================
-- SISTEMA DE AGENDAMENTO - JACKELINE VIANA NOIVAS & FESTAS
-- Script SQL Completo para importar no phpMyAdmin
-- Nome do Banco de Dados: site_agendamento
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- ALTERADO: Criando o banco com o nome correto selecionado por você
CREATE DATABASE IF NOT EXISTS `site_agendamento`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

-- ALTERADO: Apontando o uso para o seu banco correto
USE `site_agendamento`;

-- ============================================================
-- TABELA: usuarios
-- ============================================================
CREATE TABLE `usuarios` (
  `id`          INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `nome`        VARCHAR(120)     NOT NULL,
  `email`       VARCHAR(180)     NOT NULL,
  `whatsapp`    VARCHAR(20)      NOT NULL DEFAULT '',
  `senha_hash`  VARCHAR(255)     NOT NULL DEFAULT '',
  `tipo`        ENUM('cliente','funcionario','admin') NOT NULL DEFAULT 'cliente',
  `ativo`       TINYINT(1)       NOT NULL DEFAULT 1,
  `criado_em`   DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA: profissionais
-- ============================================================
CREATE TABLE `profissionais` (
  `id`          INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `usuario_id`  INT UNSIGNED     NOT NULL,
  `apelido`     VARCHAR(80)      NOT NULL COMMENT 'Nome exibido ao cliente (ex: Bianca)',
  `foto_url`    VARCHAR(255)     NOT NULL DEFAULT '',
  `cor_agenda`  VARCHAR(7)       NOT NULL DEFAULT '#e91e8c' COMMENT 'Cor hex para diferenciar no calendário master',
  `ativo`       TINYINT(1)       NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_usuario` (`usuario_id`),
  CONSTRAINT `fk_prof_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA: categorias_servico
-- ============================================================
CREATE TABLE `categorias_servico` (
  `id`        INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `nome`      VARCHAR(100)  NOT NULL,
  `ordem`     TINYINT       NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA: servicos
-- ============================================================
CREATE TABLE `servicos` (
  `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `categoria_id`  INT UNSIGNED    NULL,
  `nome`          VARCHAR(180)    NOT NULL,
  `descricao`     TEXT            NULL,
  `duracao_min`   SMALLINT        NOT NULL DEFAULT 60  COMMENT 'Duração em minutos',
  `preco`         DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
  `foto_url`      VARCHAR(255)    NOT NULL DEFAULT '',
  `ativo`         TINYINT(1)      NOT NULL DEFAULT 1,
  `ordem`         SMALLINT        NOT NULL DEFAULT 0,
  `criado_em`     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_serv_cat` FOREIGN KEY (`categoria_id`) REFERENCES `categorias_servico` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA: horarios_funcionamento
-- (Define os slots disponíveis por dia da semana para cada profissional)
-- ============================================================
CREATE TABLE `horarios_funcionamento` (
  `id`              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `profissional_id` INT UNSIGNED  NOT NULL,
  `dia_semana`      TINYINT       NOT NULL COMMENT '0=Dom, 1=Seg ... 6=Sáb',
  `hora_inicio`     TIME          NOT NULL,
  `hora_fim`        TIME          NOT NULL,
  `ativo`           TINYINT(1)    NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_hf_prof_dia` (`profissional_id`, `dia_semana`),
  CONSTRAINT `fk_hf_prof` FOREIGN KEY (`profissional_id`) REFERENCES `profissionais` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA: bloqueios
-- (Folgas, feriados, bloqueios manuais de datas/horários)
-- ============================================================
CREATE TABLE `bloqueios` (
  `id`              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `profissional_id` INT UNSIGNED  NULL COMMENT 'NULL = bloqueia todos',
  `data_inicio`     DATETIME      NOT NULL,
  `data_fim`        DATETIME      NOT NULL,
  `motivo`          VARCHAR(255)  NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `idx_blq_data` (`data_inicio`, `data_fim`),
  CONSTRAINT `fk_blq_prof` FOREIGN KEY (`profissional_id`) REFERENCES `profissionais` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA: agendamentos
-- ============================================================
CREATE TABLE `agendamentos` (
  `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `usuario_id`      INT UNSIGNED    NULL COMMENT 'NULL = cliente não cadastrado',
  `profissional_id` INT UNSIGNED    NOT NULL,
  `servico_id`      INT UNSIGNED    NOT NULL,
  `nome_cliente`    VARCHAR(120)    NOT NULL,
  `whatsapp_cliente` VARCHAR(20)   NOT NULL,
  `email_cliente`   VARCHAR(180)    NOT NULL DEFAULT '',
  `data_hora_inicio` DATETIME      NOT NULL,
  `data_hora_fim`   DATETIME        NOT NULL,
  `status`          ENUM('pendente','confirmado','concluido','cancelado') NOT NULL DEFAULT 'pendente',
  `observacoes`     TEXT           NULL,
  `gcal_link`       TEXT           NULL COMMENT 'Link de adicionar ao Google Calendar',
  `criado_em`       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em`   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ag_prof_data` (`profissional_id`, `data_hora_inicio`),
  KEY `idx_ag_usuario` (`usuario_id`),
  CONSTRAINT `fk_ag_usuario`  FOREIGN KEY (`usuario_id`)      REFERENCES `usuarios`       (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_ag_prof`     FOREIGN KEY (`profissional_id`) REFERENCES `profissionais`  (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_ag_servico`  FOREIGN KEY (`servico_id`)      REFERENCES `servicos`       (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA: mensagens_automaticas
-- ============================================================
CREATE TABLE `mensagens_automaticas` (
  `id`        INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `tipo`      VARCHAR(60)   NOT NULL COMMENT 'confirmacao, lembrete, pos_atendimento',
  `canal`     ENUM('whatsapp','email') NOT NULL DEFAULT 'whatsapp',
  `assunto`   VARCHAR(200)  NOT NULL DEFAULT '',
  `corpo`     TEXT          NOT NULL COMMENT 'Suporta placeholders: {nome}, {servico}, {data}, {hora}, {profissional}',
  `ativo`     TINYINT(1)    NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA: catalogo_pecas
-- (Catálogo de roupas/peças da loja - visível para clientes autenticados)
-- ============================================================
CREATE TABLE `catalogo_pecas` (
  `id`          INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `nome`        VARCHAR(180)   NOT NULL,
  `descricao`   TEXT           NULL,
  `categoria`   VARCHAR(80)    NOT NULL DEFAULT '',
  `tamanho`     VARCHAR(30)    NOT NULL DEFAULT '',
  `cor`         VARCHAR(50)    NOT NULL DEFAULT '',
  `foto_url`    VARCHAR(255)   NOT NULL DEFAULT '',
  `disponivel`  TINYINT(1)     NOT NULL DEFAULT 1,
  `criado_em`   DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DADOS INICIAIS
-- ============================================================

-- Categorias
INSERT INTO `categorias_servico` (`nome`, `ordem`) VALUES
('Atendimento', 1),
('Prova de Vestido', 2),
('Prova de Traje', 3),
('Prova Final', 4),
('Outros', 5);

-- Serviços (baseados no fluxo de telas)
INSERT INTO `servicos` (`categoria_id`, `nome`, `descricao`, `duracao_min`, `preco`, `ativo`, `ordem`) VALUES
(1, 'Atendimento p/ Noiva', 'Atendimento para noiva, ainda não tem contrato', 60, 0.00, 1, 1),
(1, 'Atendimento para Debutante', 'Atendimento para debutante, ainda não tem contrato', 60, 0.00, 1, 2),
(1, 'Atendimento p/ Vestido de Festa', 'Atendimento para vestido de festa', 60, 0.00, 1, 3),
(1, 'Atendimento para Traje Masculino', 'Atendimento para traje masculino', 60, 0.00, 1, 4),
(1, 'Atendimento para Traje Infantil', 'Atendimento para traje infantil', 45, 0.00, 1, 5),
(2, 'Prova Final Noiva', 'Somente agendado pela vendedora', 90, 0.00, 1, 6),
(2, 'Prova Final Vestido de Festa', 'Somente agendado pela vendedora', 60, 0.00, 1, 7),
(2, 'Prova Final Traje Masculino', 'Somente agendado pela vendedora', 60, 0.00, 1, 8),
(2, 'Prova Final Traje Infantil', 'Somente agendado pela vendedora', 45, 0.00, 1, 9),
(2, 'Prova Final Noiva Especial', 'Somente agendado pela vendedora', 90, 0.00, 1, 10), -- CORRIGIDO
(2, 'Prova Final DEBUTANTE', 'Somente agendado pela vendedora', 90, 0.00, 1, 11),
(3, 'Prova de Medidas Noiva', 'Agendado pelo cliente com a loja', 45, 0.00, 1, 12),
(3, 'Prova de Medidas Traje Masculino', 'Agendado pelo cliente com a loja', 45, 0.00, 1, 13),
(3, 'Escolha de Vestido de Noiva', 'Cliente já tem contrato', 60, 0.00, 1, 14),
(3, 'Escolha de Traje do Pai', 'Prova de medidas do traje do pai ou para quem vai usar', 45, 0.00, 1, 15),
(3, 'Escolha de Traje do Padrinho', 'Agendado pelo cliente', 45, 0.00, 1, 16),
(3, 'Escolha de Macaquinho de Balada Debutante', 'Agendado pela vendedora', 60, 0.00, 1, 17),
(3, 'Escolha de Macaquinhos das Amigas da Debutante', 'Escolha individual para cada macaquinho', 45, 0.00, 1, 18),
(3, 'Escolha de Traje da Mãe', 'Já tem contrato', 60, 0.00, 1, 19),
(3, 'Prova de Medidas Mãe', 'Agendado pelo cliente', 45, 0.00, 1, 20),
(3, 'Escolha de Vestido de Madrinha', 'Já tem contrato', 60, 0.00, 1, 21),
(3, 'Prova de Medidas Madrinha', 'Agendado pelo cliente', 45, 0.00, 1, 22),
(3, 'Escolha de Vestido Principal', 'Somente agendado pela vendedora', 90, 0.00, 1, 23),
(3, 'Escolha de Vestido de Recepção', 'Agendado pela vendedora', 60, 0.00, 1, 24),
(3, 'Prova Final Recepção', 'Agendado pela vendedora', 60, 0.00, 1, 25),
(3, 'Prova de Medidas Civil', 'Agendado pelo cliente', 45, 0.00, 1, 26);

-- Usuário Admin padrão (senha: Admin@2025)
INSERT INTO `usuarios` (`nome`, `email`, `whatsapp`, `senha_hash`, `tipo`, `ativo`) VALUES
('Administrador', 'admin@atelier.com', '(96) 99999-0001', '$2y$12$placeholder_admin_hash_aqui_troque', 'admin', 1);

-- Funcionárias de exemplo (Configuradas com a identidade do seu projeto)
INSERT INTO `usuarios` (`nome`, `email`, `whatsapp`, `senha_hash`, `tipo`, `ativo`) VALUES
('Bianca Silva',   'bianca@atelier.com',  '(96) 99999-0002', '$2y$12$placeholder_hash_troque', 'funcionario', 1),
('Leticia Costa',  'leticia@atelier.com', '(96) 99999-0003', '$2y$12$placeholder_hash_troque', 'funcionario', 1),
('Mayane Ferreira','mayane@atelier.com',  '(96) 99999-0004', '$2y$12$placeholder_hash_troque', 'funcionario', 1);

INSERT INTO `profissionais` (`usuario_id`, `apelido`, `cor_agenda`) VALUES
(2, 'Bianca',  '#e91e8c'),
(3, 'Leticia', '#9c27b0'),
(4, 'Mayane',  '#ff5722');

-- Horários padrão (Seg-Sex 09:00-18:00, Sáb 09:00-13:00) para cada profissional
INSERT INTO `horarios_funcionamento` (`profissional_id`, `dia_semana`, `hora_inicio`, `hora_fim`) VALUES
(1,1,'09:00','18:00'),(1,2,'09:00','18:00'),(1,3,'09:00','18:00'),(1,4,'09:00','18:00'),(1,5,'09:00','18:00'),(1,6,'09:00','13:00'),
(2,1,'09:00','18:00'),(2,2,'09:00','18:00'),(2,3,'09:00','18:00'),(2,4,'09:00','18:00'),(2,5,'09:00','18:00'),(2,6,'09:00','13:00'),
(3,1,'09:00','18:00'),(3,2,'09:00','18:00'),(3,3,'09:00','18:00'),(3,4,'09:00','18:00'),(3,5,'09:00','18:00'),(3,6,'09:00','13:00');

-- Mensagem automática de confirmação padrão via WhatsApp
INSERT INTO `mensagens_automaticas` (`tipo`, `canal`, `assunto`, `corpo`, `ativo`) VALUES
('confirmacao', 'whatsapp', '', 'Olá, {nome}! 🌸 Seu agendamento na *Jackeline Viana Noivas & Festas* foi confirmado!\n\n📅 *Data:* {data}\n⏰ *Horário:* {hora}\n✂️ *Serviço:* {servico}\n👗 *Atendente:* {profissional}\n\nQualquer dúvida, estamos à disposição. Até lá! 💕', 1),
('lembrete',    'whatsapp', '', 'Olá, {nome}! 🌸 Lembramos que amanhã você tem um horário agendado conosco!\n\n📅 *Data:* {data} às {hora}\n✂️ *Serviço:* {servico}\n\nCaso precise reagendar, nos avise com antecedência. 💕', 1);