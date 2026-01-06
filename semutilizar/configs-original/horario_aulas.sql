-- phpMyAdmin SQL Dump
-- version 5.1.1deb5ubuntu1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Tempo de geração: 06-Out-2025 às 08:25
-- Versão do servidor: 10.6.22-MariaDB-0ubuntu0.22.04.1
-- versão do PHP: 8.2.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `horario_aulas`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `ano_letivo`
--

CREATE TABLE `ano_letivo` (
  `id_ano_letivo` int(11) NOT NULL,
  `ano` year(4) NOT NULL,
  `data_inicio` date NOT NULL,
  `data_fim` date NOT NULL,
  `data_cadastro` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `ano_letivo`
--

INSERT INTO `ano_letivo` (`id_ano_letivo`, `ano`, `data_inicio`, `data_fim`, `data_cadastro`) VALUES
(5, 2025, '2025-01-01', '2025-12-31', '2025-02-12 20:48:26');

-- --------------------------------------------------------

--
-- Estrutura da tabela `categoria`
--

CREATE TABLE `categoria` (
  `id_categoria` int(11) NOT NULL,
  `id_modalidade` int(11) NOT NULL,
  `nome_categoria` varchar(100) NOT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  `data_cadastro` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `categoria`
--

INSERT INTO `categoria` (`id_categoria`, `id_modalidade`, `nome_categoria`, `descricao`, `data_cadastro`) VALUES
(1, 1, '1° e 2° Ano / Sub-7', 'Escolinha de futsal masculino sub-7', '2025-04-01 10:41:23'),
(2, 5, '2º ao 5º ano', '', '2025-05-08 19:32:57'),
(3, 1, '4 e 5 Anos / Sub-5', '', '2025-05-08 19:33:38'),
(4, 1, '5º e 6º ano / Sub-11', '', '2025-05-08 19:43:49'),
(5, 1, '7º e 9º ano', '', '2025-05-08 19:44:08'),
(6, 1, 'Ensino Médio', '', '2025-05-08 19:44:37'),
(7, 2, 'B', '', '2025-05-08 22:27:52'),
(8, 5, 'A', '', '2025-05-08 22:28:09'),
(9, 1, '3° e 4° Ano / Sub-9', '', '2025-05-13 17:44:48'),
(10, 3, '2º ao 5º ano', '', '2025-05-13 18:08:11'),
(11, 4, 'A e B', '', '2025-05-13 18:20:46'),
(12, 3, '6º e 7º ano / Sub12', '', '2025-05-13 18:21:34'),
(13, 3, 'A e B', '', '2025-05-13 18:22:06'),
(14, 2, 'A', 'ensino médio', '2025-06-03 16:36:33');

-- --------------------------------------------------------

--
-- Estrutura da tabela `configuracao_hora_aula_escolinha`
--

CREATE TABLE `configuracao_hora_aula_escolinha` (
  `id_configuracao` int(11) NOT NULL,
  `id_ano_letivo` int(11) NOT NULL,
  `id_modalidade` int(11) NOT NULL,
  `id_categoria` int(11) DEFAULT NULL,
  `duracao_aula_minutos` int(11) DEFAULT 50,
  `tolerancia_quebra` tinyint(1) DEFAULT 1,
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp(),
  `ativo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `disciplina`
--

CREATE TABLE `disciplina` (
  `id_disciplina` int(11) NOT NULL,
  `nome_disciplina` varchar(100) NOT NULL,
  `sigla_disciplina` varchar(20) NOT NULL,
  `data_cadastro_disciplina` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `disciplina`
--

INSERT INTO `disciplina` (`id_disciplina`, `nome_disciplina`, `sigla_disciplina`, `data_cadastro_disciplina`) VALUES
(1, 'Português', 'POR', '2025-02-07 15:58:47'),
(2, 'Matemática', 'MAT', '2025-02-13 14:17:11'),
(3, 'Inglês', 'ING', '2025-02-15 15:43:10'),
(4, 'História', 'HIS', '2025-02-15 15:58:09'),
(5, 'Geografia', 'GEO', '2025-02-15 15:58:22'),
(6, 'Artes', 'ART', '2025-03-20 18:22:55'),
(7, 'Biologia', 'BIO', '2025-03-20 18:23:08'),
(8, 'Ciências', 'CIE', '2025-03-20 18:23:23'),
(9, 'Educação Física', 'EDF', '2025-03-20 18:23:41'),
(10, 'Filosofia', 'FIL', '2025-03-20 18:23:54'),
(11, 'Sociologia', 'SOC', '2025-03-20 18:24:16'),
(12, 'Química', 'QUI', '2025-03-20 18:24:33'),
(13, 'Física', 'FIS', '2025-03-20 18:24:43'),
(15, 'Espanhol', 'ESP', '2025-03-20 18:25:34'),
(16, 'Literatura', 'LIT', '2025-03-20 18:40:06'),
(17, 'Gramática', 'GRA', '2025-03-20 18:44:33'),
(18, 'Prova', 'PRO', '2025-03-27 14:23:59'),
(19, 'Escola da Inteligência', 'EIN', '2025-03-27 14:25:02'),
(20, 'Redação', 'RED', '2025-03-27 14:27:42'),
(21, 'Arte', 'ARE', '2025-03-27 14:28:14'),
(22, 'Língua Estrangeira', 'LIE', '2025-03-27 14:29:07'),
(23, 'Projeto de Vida', 'PJV', '2025-03-27 14:30:06');

-- --------------------------------------------------------

--
-- Estrutura da tabela `eventos_calendario_escolar`
--

CREATE TABLE `eventos_calendario_escolar` (
  `id_evento` int(11) NOT NULL,
  `id_ano_letivo` int(11) NOT NULL,
  `tipo_evento` enum('feriado','recesso','ferias') NOT NULL,
  `nome_evento` varchar(100) NOT NULL,
  `data_inicio` date NOT NULL,
  `data_fim` date NOT NULL,
  `observacoes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `eventos_calendario_escolar`
--

INSERT INTO `eventos_calendario_escolar` (`id_evento`, `id_ano_letivo`, `tipo_evento`, `nome_evento`, `data_inicio`, `data_fim`, `observacoes`) VALUES
(1, 5, 'feriado', 'Aniversário de Apucarana - 28 de janeiro', '2025-01-28', '2025-01-28', 'Aniversário de Apucarana'),
(2, 5, 'feriado', 'Padroeira de Apucara', '2025-02-11', '2025-02-11', ''),
(3, 5, 'ferias', 'Férias Escolares', '2025-01-01', '2025-01-31', '');

-- --------------------------------------------------------

--
-- Estrutura da tabela `historico_horario`
--

CREATE TABLE `historico_horario` (
  `id_historico` int(11) NOT NULL,
  `id_horario_original` int(11) NOT NULL,
  `id_turma` int(11) NOT NULL,
  `id_ano_letivo` int(11) NOT NULL,
  `dia_semana` enum('Domingo','Segunda','Terca','Quarta','Quinta','Sexta','Sabado') NOT NULL,
  `numero_aula` int(11) NOT NULL,
  `id_disciplina` int(11) NOT NULL,
  `id_professor` int(11) NOT NULL,
  `data_criacao_original` datetime NOT NULL,
  `data_arquivamento` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `historico_horario`
--

INSERT INTO `historico_horario` (`id_historico`, `id_horario_original`, `id_turma`, `id_ano_letivo`, `dia_semana`, `numero_aula`, `id_disciplina`, `id_professor`, `data_criacao_original`, `data_arquivamento`) VALUES
(1, 21, 1, 5, 'Terca', 1, 2, 2, '2025-03-03 23:50:26', '2025-03-03 23:50:26'),
(2, 22, 1, 5, 'Quarta', 1, 2, 2, '2025-03-03 23:50:27', '2025-03-03 23:50:27'),
(3, 23, 1, 5, 'Quinta', 1, 2, 2, '2025-03-03 23:50:30', '2025-03-03 23:50:30'),
(4, 27, 1, 5, 'Quinta', 2, 4, 1, '2025-03-03 23:50:38', '2025-03-03 23:50:38'),
(5, 26, 1, 5, 'Quarta', 2, 4, 1, '2025-03-03 23:50:39', '2025-03-03 23:50:39'),
(6, 25, 1, 5, 'Terca', 2, 4, 1, '2025-03-03 23:50:41', '2025-03-03 23:50:41'),
(7, 24, 1, 5, 'Segunda', 2, 4, 1, '2025-03-03 23:50:44', '2025-03-03 23:50:44'),
(8, 20, 1, 5, 'Segunda', 1, 2, 2, '2025-03-03 23:50:46', '2025-03-03 23:50:46'),
(9, 32, 2, 5, 'Segunda', 2, 4, 1, '2025-03-04 00:05:09', '2025-03-04 00:05:09'),
(10, 34, 2, 5, 'Quarta', 2, 4, 1, '2025-03-04 00:05:12', '2025-03-04 00:05:12'),
(11, 35, 2, 5, 'Quinta', 2, 4, 1, '2025-03-04 00:05:14', '2025-03-04 00:05:14'),
(12, 31, 2, 5, 'Quinta', 1, 2, 2, '2025-03-04 00:05:15', '2025-03-04 00:05:15'),
(13, 30, 2, 5, 'Quarta', 1, 2, 2, '2025-03-04 00:05:16', '2025-03-04 00:05:16'),
(14, 29, 2, 5, 'Terca', 1, 2, 2, '2025-03-04 00:05:17', '2025-03-04 00:05:17'),
(15, 28, 2, 5, 'Segunda', 1, 2, 2, '2025-03-04 00:05:18', '2025-03-04 00:05:18'),
(16, 33, 2, 5, 'Terca', 2, 4, 1, '2025-03-04 00:05:25', '2025-03-04 00:05:25'),
(17, 41, 1, 5, 'Segunda', 6, 4, 1, '2025-03-04 19:12:00', '2025-03-04 19:12:00'),
(18, 40, 1, 5, 'Segunda', 5, 4, 1, '2025-03-04 19:12:05', '2025-03-04 19:12:05'),
(19, 39, 1, 5, 'Segunda', 4, 4, 1, '2025-03-04 19:12:08', '2025-03-04 19:12:08'),
(20, 38, 1, 5, 'Segunda', 3, 4, 1, '2025-03-04 19:12:12', '2025-03-04 19:12:12'),
(21, 42, 2, 5, 'Sexta', 1, 2, 2, '2025-03-10 16:06:29', '2025-03-10 16:06:29');

-- --------------------------------------------------------

--
-- Estrutura da tabela `horario`
--

CREATE TABLE `horario` (
  `id_horario` int(11) NOT NULL,
  `id_turma` int(11) NOT NULL,
  `dia_semana` enum('Domingo','Segunda','Terca','Quarta','Quinta','Sexta','Sabado') NOT NULL,
  `numero_aula` int(11) NOT NULL,
  `id_disciplina` int(11) NOT NULL,
  `id_professor` int(11) NOT NULL,
  `data_criacao` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `horario`
--

INSERT INTO `horario` (`id_horario`, `id_turma`, `dia_semana`, `numero_aula`, `id_disciplina`, `id_professor`, `data_criacao`) VALUES
(36, 1, 'Segunda', 1, 4, 1, '2025-03-04 00:04:59'),
(37, 1, 'Segunda', 2, 4, 1, '2025-03-04 00:05:32'),
(43, 2, 'Segunda', 1, 3, 3, '2025-03-06 09:44:41');

-- --------------------------------------------------------

--
-- Estrutura da tabela `horario_escolinha`
--

CREATE TABLE `horario_escolinha` (
  `id_horario_escolinha` int(11) NOT NULL,
  `id_ano_letivo` int(11) NOT NULL,
  `id_nivel_ensino` int(11) NOT NULL,
  `id_modalidade` int(11) NOT NULL,
  `id_categoria` int(11) NOT NULL,
  `id_professor` int(11) NOT NULL,
  `id_turno` int(11) NOT NULL,
  `dia_semana` enum('Domingo','Segunda','Terca','Quarta','Quinta','Sexta','Sabado') NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fim` time NOT NULL,
  `data_cadastro` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `horario_escolinha`
--

INSERT INTO `horario_escolinha` (`id_horario_escolinha`, `id_ano_letivo`, `id_nivel_ensino`, `id_modalidade`, `id_categoria`, `id_professor`, `id_turno`, `dia_semana`, `hora_inicio`, `hora_fim`, `data_cadastro`) VALUES
(2, 5, 8, 1, 1, 8, 2, 'Segunda', '18:10:00', '19:00:00', '2025-05-08 19:49:25'),
(3, 5, 8, 1, 1, 8, 2, 'Quarta', '18:10:00', '19:00:00', '2025-05-13 17:38:57'),
(6, 5, 8, 1, 4, 12, 2, 'Sexta', '18:10:00', '19:00:00', '2025-05-13 17:39:39'),
(7, 5, 8, 1, 3, 12, 2, 'Terca', '18:10:00', '19:00:00', '2025-05-13 17:40:31'),
(8, 5, 8, 1, 3, 12, 2, 'Quinta', '18:10:00', '19:00:00', '2025-05-13 17:40:39'),
(9, 5, 8, 1, 9, 12, 2, 'Segunda', '18:10:00', '19:00:00', '2025-05-13 17:58:27'),
(10, 5, 8, 1, 9, 12, 2, 'Quarta', '18:10:00', '19:00:00', '2025-05-13 17:58:33'),
(11, 5, 8, 1, 1, 8, 2, 'Sexta', '18:10:00', '19:00:00', '2025-05-13 17:59:39'),
(12, 5, 8, 2, 7, 10, 2, 'Segunda', '16:20:00', '17:10:00', '2025-05-13 18:02:46'),
(13, 5, 8, 2, 7, 10, 2, 'Quarta', '16:20:00', '17:10:00', '2025-05-13 18:02:51'),
(14, 5, 8, 2, 7, 10, 2, 'Sexta', '16:20:00', '17:10:00', '2025-05-13 18:02:51'),
(15, 5, 8, 2, 14, 10, 2, 'Terca', '16:20:00', '17:35:00', '2025-05-13 18:03:45'),
(16, 5, 8, 2, 14, 10, 2, 'Quinta', '15:30:00', '16:45:00', '2025-05-13 18:03:50'),
(17, 5, 8, 5, 2, 11, 2, 'Terca', '18:10:00', '19:00:00', '2025-05-13 18:06:57'),
(18, 5, 8, 5, 2, 11, 2, 'Quinta', '18:10:00', '19:00:00', '2025-05-13 18:07:02'),
(19, 5, 8, 3, 10, 11, 2, 'Quarta', '18:10:00', '19:00:00', '2025-05-13 18:09:39'),
(20, 5, 8, 3, 10, 11, 2, 'Sexta', '18:10:00', '19:00:00', '2025-05-13 18:09:44'),
(21, 5, 8, 1, 5, 9, 2, 'Terca', '13:30:00', '14:45:00', '2025-05-13 18:14:06'),
(22, 5, 8, 1, 5, 9, 2, 'Quinta', '13:30:00', '14:45:00', '2025-05-13 18:14:11'),
(23, 5, 8, 1, 6, 9, 2, 'Quarta', '15:30:00', '16:45:00', '2025-05-13 18:15:10'),
(24, 5, 8, 1, 6, 9, 2, 'Quinta', '16:20:00', '17:35:00', '2025-05-13 18:15:15'),
(25, 5, 8, 1, 4, 8, 2, 'Terca', '18:10:00', '19:00:00', '2025-05-13 18:19:54'),
(26, 5, 8, 1, 4, 8, 2, 'Quinta', '18:10:00', '19:00:00', '2025-05-13 18:19:59'),
(27, 5, 8, 3, 13, 8, 2, 'Segunda', '16:20:00', '17:35:00', '2025-05-13 18:23:11'),
(28, 5, 8, 3, 13, 8, 2, 'Quarta', '16:20:00', '17:35:00', '2025-05-13 18:23:18'),
(29, 5, 8, 3, 12, 8, 2, 'Terca', '15:30:00', '16:45:00', '2025-05-13 18:23:57'),
(30, 5, 8, 3, 12, 8, 2, 'Quinta', '15:30:00', '16:45:00', '2025-05-13 18:24:05'),
(31, 5, 8, 4, 11, 8, 2, 'Terca', '16:45:00', '18:05:00', '2025-05-13 18:24:37'),
(32, 5, 8, 4, 11, 8, 2, 'Quinta', '16:45:00', '18:05:00', '2025-05-13 18:24:46');

-- --------------------------------------------------------

--
-- Estrutura da tabela `instituicao`
--

CREATE TABLE `instituicao` (
  `id_instituicao` int(11) NOT NULL,
  `nome_instituicao` varchar(255) NOT NULL,
  `cnpj_instituicao` varchar(18) NOT NULL,
  `endereco_instituicao` varchar(255) DEFAULT NULL,
  `telefone_instituicao` varchar(20) DEFAULT NULL,
  `email_instituicao` varchar(100) DEFAULT NULL,
  `data_cadastro_instituicao` datetime DEFAULT current_timestamp(),
  `imagem_instituicao` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `instituicao`
--

INSERT INTO `instituicao` (`id_instituicao`, `nome_instituicao`, `cnpj_instituicao`, `endereco_instituicao`, `telefone_instituicao`, `email_instituicao`, `data_cadastro_instituicao`, `imagem_instituicao`) VALUES
(1, 'Colégio Mater Dei', '04.940.720/0001-98', 'Rua Professora Talita Bresolin, 1139', '(43) 3423-0500', 'secretaria@materdeiapucarana.com.br', '2025-02-07 22:52:00', 'http://localhost/horarios/app/assets/imgs/logo/1740345212_1740159747_LOGO MATER DEI.png');

-- --------------------------------------------------------

--
-- Estrutura da tabela `log_atividade`
--

CREATE TABLE `log_atividade` (
  `id_log` int(11) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `nome_usuario` varchar(255) DEFAULT NULL,
  `ip_usuario` varchar(50) NOT NULL,
  `status_atividade` enum('sucesso_login','falha_login','acesso_negado_login','saida_sistema') NOT NULL,
  `data_hora_atividade` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `log_atividade`
--

INSERT INTO `log_atividade` (`id_log`, `id_usuario`, `nome_usuario`, `ip_usuario`, `status_atividade`, `data_hora_atividade`) VALUES
(1, 1, 'Diezare.Conde', '::1', 'sucesso_login', '2025-03-13 21:16:47'),
(2, 1, 'Diezare.Conde', '::1', 'saida_sistema', '2025-03-13 22:41:49'),
(3, 1, 'Diezare.Conde', '::1', 'sucesso_login', '2025-03-13 23:53:45'),
(4, 1, 'Diezare.Conde', '::1', 'saida_sistema', '2025-03-14 00:02:59'),
(5, 1, 'Diezare.Conde', '::1', 'sucesso_login', '2025-03-14 00:03:11'),
(6, 1, 'Diezare.Conde', '::1', 'saida_sistema', '2025-03-14 00:06:20'),
(7, 1, 'Diezare.Conde', '::1', 'sucesso_login', '2025-03-14 19:22:58'),
(8, 1, 'Diezare.Conde', '::1', 'saida_sistema', '2025-03-14 23:16:52'),
(9, 1, 'Diezare.Conde', '::1', 'sucesso_login', '2025-03-14 23:17:08'),
(10, 1, 'Diezare.Conde', '::1', 'saida_sistema', '2025-03-14 23:17:13'),
(11, 1, 'Diezare.Conde', '::1', 'sucesso_login', '2025-03-14 23:17:20'),
(12, 1, 'Diezare.Conde', '::1', 'saida_sistema', '2025-03-14 23:28:17'),
(13, 1, 'Diezare.Conde', '::1', 'sucesso_login', '2025-03-16 22:13:36'),
(14, 1, 'Diezare.Conde', '::1', 'saida_sistema', '2025-03-16 22:20:46'),
(15, NULL, 'Desconhecido', '::1', 'acesso_negado_login', '2025-03-16 22:23:56'),
(16, 1, 'Diezare.Conde', '::1', 'sucesso_login', '2025-03-16 22:24:03'),
(17, 1, 'Diezare.Conde', '::1', 'saida_sistema', '2025-03-16 22:24:36'),
(18, NULL, 'Desconhecido', '::1', 'acesso_negado_login', '2025-03-16 22:24:51'),
(19, 1, 'Diezare.Conde', '::1', 'sucesso_login', '2025-03-16 22:25:13'),
(20, 1, 'Diezare.Conde', '::1', 'saida_sistema', '2025-03-16 22:25:23'),
(21, NULL, 'Desconhecido', '::1', 'acesso_negado_login', '2025-03-18 20:16:47'),
(22, 1, 'Diezare.Conde', '::1', 'sucesso_login', '2025-03-18 20:16:52'),
(23, 1, 'Diezare.Conde', '::1', 'sucesso_login', '2025-03-19 10:57:22'),
(24, 1, 'Diezare.Conde', '::1', 'saida_sistema', '2025-03-19 11:45:57'),
(25, 1, 'Diezare.Conde', '::1', 'sucesso_login', '2025-03-19 11:46:30'),
(26, 1, 'Diezare.Conde', '::1', 'saida_sistema', '2025-03-19 11:46:49'),
(27, 1, 'Diezare.Conde', '::1', 'sucesso_login', '2025-03-19 11:46:57'),
(28, 1, 'Diezare.Conde', '::1', 'saida_sistema', '2025-03-19 11:47:21'),
(29, 1, 'Diezare.Conde', '::1', 'sucesso_login', '2025-03-19 11:47:26'),
(30, NULL, 'Desconhecido', '::1', 'acesso_negado_login', '2025-03-19 17:15:30'),
(31, 1, 'Diezare.Conde', '::1', 'saida_sistema', '2025-03-19 17:28:39'),
(32, 1, 'Diezare.Conde', '::1', 'sucesso_login', '2025-03-19 17:28:45'),
(33, 1, 'Diezare.Conde', '::1', 'saida_sistema', '2025-03-19 17:40:01'),
(34, NULL, 'Desconhecido', '192.168.0.22', 'acesso_negado_login', '2025-03-20 10:53:11'),
(35, 1, 'Diezare.Conde', '192.168.0.22', 'sucesso_login', '2025-03-20 10:53:17'),
(36, 1, 'Diezare.Conde', '192.168.0.22', 'saida_sistema', '2025-03-20 11:06:59'),
(37, NULL, 'Desconhecido', '192.168.0.22', 'acesso_negado_login', '2025-03-20 11:07:01'),
(38, NULL, 'Desconhecido', '192.168.0.22', 'acesso_negado_login', '2025-03-20 17:59:44'),
(39, 1, 'Diezare.Conde', '192.168.0.22', 'sucesso_login', '2025-03-20 18:02:57'),
(40, NULL, 'Desconhecido', '192.168.0.22', 'acesso_negado_login', '2025-03-20 18:02:57'),
(41, 1, 'Diezare.Conde', '192.168.0.22', 'sucesso_login', '2025-03-20 18:03:06'),
(42, NULL, 'Desconhecido', '192.168.0.22', 'acesso_negado_login', '2025-03-20 18:03:06'),
(43, 1, 'Diezare.Conde', '192.168.0.22', 'sucesso_login', '2025-03-20 18:09:33'),
(44, NULL, 'Desconhecido', '192.168.0.22', 'acesso_negado_login', '2025-03-20 18:09:33'),
(45, 1, 'Diezare.Conde', '192.168.0.22', 'sucesso_login', '2025-03-20 18:11:47'),
(46, NULL, 'Desconhecido', '192.168.0.22', 'acesso_negado_login', '2025-03-20 18:11:47'),
(47, 1, 'Diezare.Conde', '192.168.0.22', 'sucesso_login', '2025-03-20 18:11:59'),
(48, NULL, 'Desconhecido', '192.168.0.22', 'acesso_negado_login', '2025-03-20 18:11:59'),
(49, 1, 'Diezare.Conde', '192.168.0.22', 'sucesso_login', '2025-03-20 18:13:45'),
(50, NULL, 'Desconhecido', '192.168.0.22', 'acesso_negado_login', '2025-03-20 18:13:45'),
(51, 1, 'Diezare.Conde', '192.168.0.22', 'sucesso_login', '2025-03-20 18:20:26'),
(52, NULL, 'Desconhecido', '192.168.0.22', 'acesso_negado_login', '2025-03-20 18:20:27'),
(53, 1, 'Diezare.Conde', '192.168.0.22', 'sucesso_login', '2025-03-20 18:20:41'),
(54, NULL, 'Desconhecido', '192.168.0.22', 'acesso_negado_login', '2025-03-20 20:11:23'),
(55, NULL, 'Desconhecido', '192.168.0.22', 'acesso_negado_login', '2025-03-20 20:11:23'),
(56, NULL, 'Desconhecido', '192.168.0.22', 'acesso_negado_login', '2025-03-20 20:11:23'),
(57, NULL, 'Desconhecido', '192.168.0.22', 'acesso_negado_login', '2025-03-20 20:11:32'),
(58, 1, 'Diezare.Conde', '192.168.0.22', 'sucesso_login', '2025-03-20 20:11:45'),
(59, 1, 'Diezare.Conde', '192.168.0.22', 'saida_sistema', '2025-03-20 20:28:12'),
(60, NULL, 'Desconhecido', '192.168.0.22', 'acesso_negado_login', '2025-03-21 17:01:12'),
(61, 1, 'Diezare.Conde', '192.168.0.22', 'sucesso_login', '2025-03-21 17:01:20'),
(62, 1, 'Diezare.Conde', '192.168.0.22', 'saida_sistema', '2025-03-21 17:08:11'),
(63, NULL, 'Desconhecido', '192.168.0.22', 'acesso_negado_login', '2025-03-24 17:22:22'),
(64, 1, 'Diezare.Conde', '192.168.0.22', 'sucesso_login', '2025-03-24 17:22:28'),
(65, 1, 'Diezare.Conde', '192.168.0.22', 'saida_sistema', '2025-03-24 17:24:54'),
(66, NULL, 'Desconhecido', '192.168.0.22', 'acesso_negado_login', '2025-03-24 19:21:51'),
(67, 1, 'Diezare.Conde', '192.168.0.22', 'sucesso_login', '2025-03-24 19:21:57'),
(68, 1, 'Diezare.Conde', '192.168.0.22', 'saida_sistema', '2025-03-24 19:36:37'),
(69, NULL, 'Desconhecido', '192.168.0.22', 'acesso_negado_login', '2025-03-24 20:15:40'),
(70, 1, 'Diezare.Conde', '192.168.0.22', 'sucesso_login', '2025-03-24 20:16:00'),
(71, NULL, 'Desconhecido', '192.168.0.22', 'acesso_negado_login', '2025-03-24 20:16:00'),
(72, 1, 'Diezare.Conde', '192.168.0.22', 'sucesso_login', '2025-03-24 20:16:10'),
(73, NULL, 'Desconhecido', '192.168.0.22', 'acesso_negado_login', '2025-03-24 20:16:10'),
(74, 1, 'Diezare.Conde', '192.168.0.22', 'sucesso_login', '2025-03-24 20:21:16'),
(75, 1, 'Diezare.Conde', '192.168.0.22', 'saida_sistema', '2025-03-24 20:37:59'),
(76, NULL, 'Desconhecido', '192.168.0.22', 'acesso_negado_login', '2025-03-26 20:29:00'),
(77, 1, 'Diezare.Conde', '192.168.0.22', 'sucesso_login', '2025-03-26 20:29:06'),
(78, 1, 'Diezare.Conde', '192.168.0.22', 'saida_sistema', '2025-03-26 20:41:06'),
(79, NULL, 'Desconhecido', '10.147.20.137', 'acesso_negado_login', '2025-03-26 22:11:13'),
(80, 1, 'Diezare.Conde', '10.147.20.137', 'sucesso_login', '2025-03-26 22:11:18'),
(81, 1, 'Diezare.Conde', '10.147.20.137', 'saida_sistema', '2025-03-26 22:14:36'),
(82, NULL, 'Desconhecido', '192.168.0.22', 'acesso_negado_login', '2025-03-27 12:48:04'),
(83, 1, 'Diezare.Conde', '192.168.0.22', 'sucesso_login', '2025-03-27 12:48:12'),
(84, NULL, 'Desconhecido', '192.168.0.22', 'acesso_negado_login', '2025-03-27 14:01:57'),
(85, 1, 'Diezare.Conde', '192.168.0.22', 'sucesso_login', '2025-03-27 14:20:14'),
(86, NULL, 'Desconhecido', '192.168.0.22', 'acesso_negado_login', '2025-03-27 15:48:58'),
(87, 1, 'Diezare.Conde', '192.168.0.22', 'falha_login', '2025-03-27 17:32:21'),
(88, 1, 'Diezare.Conde', '192.168.0.22', 'falha_login', '2025-03-27 17:32:27'),
(89, NULL, 'Desconhecido', '192.168.0.22', 'acesso_negado_login', '2025-03-27 17:57:45'),
(90, NULL, 'dsad', '192.168.0.22', 'acesso_negado_login', '2025-03-27 17:57:50'),
(91, NULL, 'sadsa', '192.168.0.22', 'acesso_negado_login', '2025-03-27 17:57:59'),
(92, NULL, 'sadsa', '192.168.0.22', 'acesso_negado_login', '2025-03-27 17:59:04'),
(93, NULL, 'sadsa', '192.168.0.22', 'acesso_negado_login', '2025-03-27 17:59:10'),
(94, NULL, 'sadsa', '192.168.0.22', 'acesso_negado_login', '2025-03-27 19:02:35'),
(95, NULL, 'Desconhecido', '192.168.0.22', 'acesso_negado_login', '2025-03-28 16:55:11'),
(96, NULL, 'Desconhecido', '192.168.0.22', 'acesso_negado_login', '2025-03-28 16:55:23'),
(97, 1, 'Diezare.Conde', '192.168.0.22', 'sucesso_login', '2025-03-28 16:55:29'),
(98, 1, 'Diezare.Conde', '192.168.0.22', 'saida_sistema', '2025-03-28 17:15:01'),
(99, NULL, 'Desconhecido', '192.168.0.22', 'acesso_negado_login', '2025-03-28 17:51:04'),
(100, NULL, 'Desconhecido', '192.168.0.22', 'acesso_negado_login', '2025-03-31 20:30:10'),
(101, NULL, 'Desconhecido', '192.168.0.22', 'acesso_negado_login', '2025-04-01 10:39:20'),
(102, 1, 'Diezare.Conde', '192.168.0.22', 'sucesso_login', '2025-04-01 10:39:26'),
(103, 1, 'Diezare.Conde', '192.168.0.22', 'saida_sistema', '2025-04-01 10:42:01'),
(104, NULL, 'Desconhecido', '192.168.0.22', 'acesso_negado_login', '2025-04-03 14:12:04'),
(105, 1, 'Diezare.Conde', '192.168.0.22', 'sucesso_login', '2025-04-03 14:12:12'),
(106, 1, 'Diezare.Conde', '192.168.0.22', 'saida_sistema', '2025-04-03 14:28:36'),
(107, NULL, 'Desconhecido', '192.168.0.22', 'acesso_negado_login', '2025-04-07 13:03:30'),
(108, NULL, 'Desconhecido', '192.168.0.22', 'acesso_negado_login', '2025-04-07 18:51:03'),
(109, NULL, 'Desconhecido', '192.168.0.22', 'acesso_negado_login', '2025-04-11 13:33:20'),
(110, 1, 'Diezare.Conde', '10.147.20.137', 'sucesso_login', '2025-04-28 23:21:31'),
(111, 1, 'Diezare.Conde', '10.147.20.137', 'sucesso_login', '2025-04-28 23:21:31'),
(112, 1, 'Diezare.Conde', '10.147.20.137', 'sucesso_login', '2025-04-28 23:21:32'),
(113, 1, 'Diezare.Conde', '10.147.20.137', 'sucesso_login', '2025-04-28 23:21:32'),
(114, 1, 'Diezare.Conde', '10.147.20.137', 'sucesso_login', '2025-04-28 23:21:32'),
(115, 1, 'Diezare.Conde', '10.147.20.137', 'sucesso_login', '2025-04-28 23:21:34'),
(116, 1, 'Diezare.Conde', '10.147.20.137', 'saida_sistema', '2025-04-28 23:21:50'),
(117, 1, 'Diezare.Conde', '10.147.20.137', 'sucesso_login', '2025-04-28 23:21:58'),
(118, 1, 'Diezare.Conde', '10.147.20.137', 'saida_sistema', '2025-04-28 23:22:05'),
(119, 1, 'Diezare.Conde', '10.147.20.137', 'sucesso_login', '2025-04-28 23:39:22'),
(120, 1, 'Diezare.Conde', '10.147.20.137', 'sucesso_login', '2025-04-28 23:39:31'),
(121, 1, 'Diezare.Conde', '10.147.20.137', 'sucesso_login', '2025-04-28 23:39:38'),
(122, 1, 'Diezare.Conde', '10.147.20.137', 'sucesso_login', '2025-04-28 23:42:31'),
(123, 1, 'Diezare.Conde', '10.147.20.137', 'sucesso_login', '2025-04-28 23:43:04'),
(124, 1, 'Diezare.Conde', '10.147.20.137', 'saida_sistema', '2025-04-28 23:43:32'),
(125, 1, 'Diezare.Conde', '10.147.20.137', 'sucesso_login', '2025-04-28 23:43:41'),
(126, 1, 'Diezare.Conde', '10.147.20.137', 'saida_sistema', '2025-04-28 23:44:20'),
(127, 1, 'Diezare.Conde', '10.147.20.137', 'falha_login', '2025-04-28 23:44:29'),
(128, NULL, 'DIez4re.COnde', '10.147.20.137', 'acesso_negado_login', '2025-04-28 23:44:36'),
(129, 1, 'Diezare.Conde', '10.147.20.137', 'sucesso_login', '2025-04-29 01:25:35'),
(130, 1, 'Diezare.Conde', '10.147.20.137', 'sucesso_login', '2025-04-29 01:28:45'),
(131, 1, 'Diezare.Conde', '10.147.20.137', 'sucesso_login', '2025-04-29 01:28:59'),
(132, 1, 'Diezare.Conde', '10.147.20.137', 'sucesso_login', '2025-04-29 01:36:12'),
(133, NULL, 'Diezare.COnde', '10.147.20.137', 'acesso_negado_login', '2025-04-29 01:39:40'),
(134, NULL, 'Diezare.COnde', '10.147.20.137', 'acesso_negado_login', '2025-04-29 01:42:04'),
(135, NULL, 'Diezare.COnde', '10.147.20.137', 'acesso_negado_login', '2025-04-29 01:46:10'),
(136, 1, 'Diezare.Conde', '10.147.20.137', 'sucesso_login', '2025-04-29 01:46:59'),
(137, 1, 'Diezare.Conde', '10.147.20.137', 'sucesso_login', '2025-04-29 01:48:02'),
(138, 1, 'Diezare.Conde', '10.147.20.137', 'sucesso_login', '2025-04-29 01:48:18'),
(139, 1, 'Diezare.Conde', '10.147.20.137', 'sucesso_login', '2025-04-29 01:48:42'),
(140, NULL, 'Diezare.COnde', '10.147.20.137', 'acesso_negado_login', '2025-04-29 01:48:48'),
(141, NULL, 'Diezare.COnde', '10.147.20.137', 'acesso_negado_login', '2025-04-29 01:49:04'),
(142, NULL, 'Diezare.COnde', '10.147.20.137', 'acesso_negado_login', '2025-04-29 01:49:55'),
(143, NULL, 'Diezare.COnde', '10.147.20.137', 'acesso_negado_login', '2025-04-29 01:50:09'),
(144, 1, 'Diezare.Conde', '10.147.20.137', 'sucesso_login', '2025-04-29 01:50:27'),
(145, NULL, 'Diezare.COnde', '10.147.20.137', 'acesso_negado_login', '2025-04-29 01:50:31'),
(146, 1, 'Diezare.Conde', '10.147.20.137', 'sucesso_login', '2025-04-29 01:51:35'),
(147, NULL, 'Diezare.COnde', '10.147.20.137', 'acesso_negado_login', '2025-04-29 01:51:39'),
(148, NULL, 'Desconhecido', '10.147.20.137', 'acesso_negado_login', '2025-04-29 01:52:01'),
(149, 1, 'Diezare.Conde', '10.147.20.137', 'sucesso_login', '2025-04-29 01:52:49'),
(150, NULL, 'Diezare.COnde', '10.147.20.137', 'acesso_negado_login', '2025-04-29 01:52:54'),
(151, NULL, 'Diezare.COnde', '10.147.20.137', 'acesso_negado_login', '2025-04-29 01:53:12'),
(152, 1, 'Diezare.Conde', '10.147.20.137', 'sucesso_login', '2025-04-29 01:53:21'),
(153, NULL, 'Diezare.COnde', '10.147.20.137', 'acesso_negado_login', '2025-04-29 01:53:25'),
(154, NULL, 'Desconhecido', '10.147.20.144', 'acesso_negado_login', '2025-04-29 19:45:52'),
(155, NULL, 'Desconhecido', '10.147.20.144', 'acesso_negado_login', '2025-04-30 21:37:04'),
(156, 1, 'Diezare.Conde', '10.147.20.144', 'sucesso_login', '2025-05-02 22:29:01'),
(157, NULL, 'Desconhecido', '192.168.0.22', 'acesso_negado_login', '2025-05-05 16:46:58'),
(158, 1, 'Diezare.Conde', '192.168.0.22', 'sucesso_login', '2025-05-05 16:47:05'),
(159, 1, 'Diezare.Conde', '192.168.0.22', 'saida_sistema', '2025-05-05 17:22:44'),
(160, NULL, 'Desconhecido', '10.147.20.144', 'acesso_negado_login', '2025-05-06 01:14:20'),
(161, NULL, 'Diezare.CondE', '10.147.20.144', 'acesso_negado_login', '2025-05-06 01:14:27'),
(162, NULL, 'Desconhecido', '192.168.0.27', 'acesso_negado_login', '2025-05-08 19:28:58'),
(163, 1, 'Diezare.Conde', '192.168.0.27', 'sucesso_login', '2025-05-08 19:29:12'),
(164, 1, 'Diezare.Conde', '192.168.0.27', 'saida_sistema', '2025-05-08 19:30:50'),
(165, 2, 'Eduardo', '192.168.0.27', 'sucesso_login', '2025-05-08 19:31:04'),
(166, NULL, 'Desconhecido', '192.168.0.22', 'acesso_negado_login', '2025-05-08 19:52:33'),
(167, 1, 'Diezare.Conde', '192.168.0.22', 'sucesso_login', '2025-05-08 19:52:44'),
(168, NULL, 'dudi@local.com', '192.168.0.27', 'acesso_negado_login', '2025-05-08 22:23:07'),
(169, 2, 'Eduardo', '192.168.0.27', 'sucesso_login', '2025-05-08 22:23:15'),
(170, NULL, 'Desconhecido', '192.168.0.22', 'acesso_negado_login', '2025-05-12 13:41:42'),
(171, 2, 'Eduardo', '192.168.0.27', 'sucesso_login', '2025-05-13 17:37:14'),
(172, NULL, 'Desconhecido', '192.168.0.22', 'acesso_negado_login', '2025-05-13 19:01:02'),
(173, 1, 'Diezare.Conde', '192.168.0.22', 'sucesso_login', '2025-05-13 19:01:08'),
(174, 1, 'Diezare.Conde', '192.168.0.22', 'saida_sistema', '2025-05-13 20:27:46'),
(175, NULL, 'Desconhecido', '10.147.20.144', 'acesso_negado_login', '2025-05-13 22:44:01'),
(176, NULL, 'Diezare.CondE', '10.147.20.144', 'acesso_negado_login', '2025-05-13 22:44:07'),
(177, NULL, 'Desconhecido', '10.147.20.144', 'acesso_negado_login', '2025-05-14 01:23:57'),
(178, 1, 'Diezare.Conde', '10.147.20.144', 'sucesso_login', '2025-05-14 01:29:23'),
(179, 1, 'Diezare.Conde', '10.147.20.144', 'saida_sistema', '2025-05-14 01:52:49'),
(180, NULL, 'Desconhecido', '192.168.0.22', 'acesso_negado_login', '2025-05-14 10:21:43'),
(181, 1, 'Diezare.Conde', '192.168.0.22', 'sucesso_login', '2025-05-14 10:21:52'),
(182, NULL, 'Desconhecido', '192.168.0.22', 'acesso_negado_login', '2025-05-14 11:29:09'),
(183, 1, 'Diezare.Conde', '192.168.0.22', 'sucesso_login', '2025-05-14 11:29:16'),
(184, 1, 'Diezare.Conde', '192.168.0.22', 'saida_sistema', '2025-05-14 11:34:40'),
(185, NULL, 'Desconhecido', '192.168.0.22', 'acesso_negado_login', '2025-05-14 12:04:22'),
(186, NULL, 'Desconhecido', '192.168.0.22', 'acesso_negado_login', '2025-05-14 12:04:23'),
(187, 1, 'Diezare.Conde', '192.168.0.22', 'sucesso_login', '2025-05-14 12:04:29'),
(188, NULL, 'Desconhecido', '192.168.0.22', 'acesso_negado_login', '2025-05-14 16:27:11'),
(189, 1, 'Diezare.Conde', '192.168.0.22', 'sucesso_login', '2025-05-14 16:27:18'),
(190, NULL, 'Desconhecido', '192.168.0.22', 'saida_sistema', '2025-05-14 19:45:38'),
(191, 2, 'Eduardo', '192.168.0.27', 'sucesso_login', '2025-05-15 20:36:42'),
(192, NULL, 'Desconhecido', '192.168.0.22', 'acesso_negado_login', '2025-05-16 16:09:09'),
(193, NULL, 'Desconhecido', '192.168.0.22', 'acesso_negado_login', '2025-05-23 16:37:59'),
(194, 1, 'Diezare.Conde', '192.168.0.22', 'sucesso_login', '2025-05-23 16:38:06'),
(195, NULL, 'Desconhecido', '192.168.0.22', 'acesso_negado_login', '2025-05-23 18:48:08'),
(196, 1, 'Diezare.Conde', '192.168.0.22', 'sucesso_login', '2025-05-23 18:48:22'),
(197, 1, 'Diezare.Conde', '192.168.0.22', 'saida_sistema', '2025-05-23 18:55:09'),
(198, NULL, 'dudi@local.com', '192.168.0.27', 'acesso_negado_login', '2025-06-03 16:28:28'),
(199, NULL, 'eduardo', '192.168.0.27', 'acesso_negado_login', '2025-06-03 16:28:43'),
(200, NULL, 'dudi', '192.168.0.27', 'acesso_negado_login', '2025-06-03 16:28:55'),
(201, NULL, 'dudi@local.com', '192.168.0.27', 'acesso_negado_login', '2025-06-03 16:29:57'),
(202, 2, 'Eduardo', '192.168.0.27', 'sucesso_login', '2025-06-03 16:30:18'),
(203, NULL, 'Desconhecido', '192.168.0.22', 'acesso_negado_login', '2025-06-03 17:48:05'),
(204, 1, 'Diezare.Conde', '192.168.0.22', 'sucesso_login', '2025-06-03 17:48:11'),
(205, NULL, 'Desconhecido', '192.168.0.22', 'saida_sistema', '2025-06-03 19:00:53'),
(206, NULL, 'Desconhecido', '192.168.0.22', 'acesso_negado_login', '2025-06-04 14:49:04'),
(207, 1, 'Diezare.Conde', '192.168.0.22', 'sucesso_login', '2025-06-04 14:49:12'),
(208, 1, 'Diezare.Conde', '192.168.0.22', 'saida_sistema', '2025-06-04 14:54:45'),
(209, 2, 'Eduardo', '192.168.0.27', 'sucesso_login', '2025-06-04 16:38:32'),
(210, NULL, 'Desconhecido', '192.168.0.22', 'acesso_negado_login', '2025-06-04 17:13:32'),
(211, 1, 'Diezare.Conde', '192.168.0.22', 'sucesso_login', '2025-06-04 17:13:39'),
(212, 1, 'Diezare.Conde', '192.168.0.22', 'saida_sistema', '2025-06-04 17:19:32'),
(213, NULL, 'Desconhecido', '192.168.0.27', 'acesso_negado_login', '2025-06-04 18:31:23'),
(214, NULL, 'Desconhecido', '192.168.0.27', 'acesso_negado_login', '2025-06-04 18:31:23'),
(215, NULL, 'Desconhecido', '192.168.0.27', 'acesso_negado_login', '2025-06-04 18:31:24'),
(216, NULL, 'Desconhecido', '192.168.0.27', 'acesso_negado_login', '2025-06-04 18:31:33'),
(217, NULL, 'Desconhecido', '192.168.0.27', 'acesso_negado_login', '2025-06-04 18:31:33'),
(218, NULL, 'Desconhecido', '192.168.0.27', 'acesso_negado_login', '2025-06-04 18:31:33'),
(219, NULL, 'Desconhecido', '192.168.0.27', 'acesso_negado_login', '2025-06-04 18:31:46'),
(220, NULL, 'Desconhecido', '192.168.0.27', 'acesso_negado_login', '2025-06-04 18:31:46'),
(221, NULL, 'Desconhecido', '192.168.0.27', 'acesso_negado_login', '2025-06-04 18:31:46'),
(222, NULL, 'Desconhecido', '192.168.0.27', 'acesso_negado_login', '2025-06-04 18:31:58'),
(223, NULL, 'Desconhecido', '192.168.0.27', 'acesso_negado_login', '2025-06-04 18:31:58'),
(224, NULL, 'Desconhecido', '192.168.0.27', 'acesso_negado_login', '2025-06-04 18:31:58'),
(225, 2, 'Eduardo', '192.168.0.27', 'sucesso_login', '2025-06-04 18:32:43'),
(226, NULL, 'Desconhecido', '192.168.0.22', 'acesso_negado_login', '2025-06-06 13:17:17'),
(227, 1, 'Diezare.Conde', '192.168.0.22', 'sucesso_login', '2025-06-06 13:17:23'),
(228, NULL, 'Desconhecido', '192.168.0.22', 'acesso_negado_login', '2025-06-06 16:52:08'),
(229, 1, 'Diezare.Conde', '192.168.0.22', 'sucesso_login', '2025-06-06 16:52:14'),
(230, NULL, 'Desconhecido', '192.168.0.22', 'acesso_negado_login', '2025-06-06 20:13:47'),
(231, 1, 'Diezare.Conde', '192.168.0.22', 'sucesso_login', '2025-06-09 14:08:20'),
(232, 1, 'Diezare.Conde', '192.168.0.22', 'saida_sistema', '2025-06-09 14:19:57'),
(233, NULL, 'Desconhecido', '192.168.0.22', 'acesso_negado_login', '2025-06-09 19:57:40'),
(234, 1, 'Diezare.Conde', '192.168.0.22', 'sucesso_login', '2025-06-09 19:57:48'),
(235, 1, 'Diezare.Conde', '192.168.0.22', 'saida_sistema', '2025-06-09 20:00:55'),
(236, NULL, 'Desconhecido', '192.168.0.22', 'acesso_negado_login', '2025-06-11 10:33:59'),
(237, 1, 'Diezare.Conde', '192.168.0.22', 'sucesso_login', '2025-06-11 10:34:14'),
(238, NULL, 'Desconhecido', '192.168.0.22', 'saida_sistema', '2025-06-11 11:53:35'),
(239, NULL, 'Desconhecido', '10.147.20.144', 'acesso_negado_login', '2025-06-15 21:31:05'),
(240, NULL, 'Desconhecido', '192.168.0.22', 'acesso_negado_login', '2025-07-08 17:47:38'),
(241, NULL, 'Desconhecido', '10.147.20.144', 'acesso_negado_login', '2025-07-09 01:28:51'),
(242, NULL, 'Desconhecido', '10.147.20.144', 'acesso_negado_login', '2025-07-21 21:42:11'),
(243, 1, 'Diezare.Conde', '192.168.0.22', 'sucesso_login', '2025-09-23 18:11:29'),
(244, 1, 'Diezare.Conde', '192.168.0.22', 'saida_sistema', '2025-09-23 19:23:07'),
(245, 1, 'Diezare.Conde', '192.168.0.22', 'sucesso_login', '2025-10-03 16:43:41'),
(246, 1, 'Diezare.Conde', '192.168.0.22', 'saida_sistema', '2025-10-03 16:44:01'),
(247, 1, 'Diezare.Conde', '192.168.0.22', 'sucesso_login', '2025-10-03 16:52:54'),
(248, NULL, 'Desconhecido', '192.168.0.22', 'saida_sistema', '2025-10-03 20:01:57'),
(249, 1, 'Diezare.Conde', '192.168.0.22', 'sucesso_login', '2025-10-06 10:49:59');

-- --------------------------------------------------------

--
-- Estrutura da tabela `modalidade`
--

CREATE TABLE `modalidade` (
  `id_modalidade` int(11) NOT NULL,
  `nome_modalidade` varchar(100) NOT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  `data_cadastro` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `modalidade`
--

INSERT INTO `modalidade` (`id_modalidade`, `nome_modalidade`, `descricao`, `data_cadastro`) VALUES
(1, 'Futsal Masculino', NULL, '2025-04-01 10:40:32'),
(2, 'Futsal Feminino', NULL, '2025-05-08 19:34:05'),
(3, 'Vôlei Feminino', NULL, '2025-05-08 19:35:30'),
(4, 'Vôlei Masculino', NULL, '2025-05-08 19:35:47'),
(5, 'Basquete', NULL, '2025-05-08 19:35:58');

-- --------------------------------------------------------

--
-- Estrutura da tabela `nivel_ensino`
--

CREATE TABLE `nivel_ensino` (
  `id_nivel_ensino` int(11) NOT NULL,
  `nome_nivel_ensino` varchar(100) NOT NULL,
  `data_cadastro_nivel` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `nivel_ensino`
--

INSERT INTO `nivel_ensino` (`id_nivel_ensino`, `nome_nivel_ensino`, `data_cadastro_nivel`) VALUES
(1, 'Educação Infantil', '2025-02-08 10:04:45'),
(2, 'Ensino Fundamental I', '2025-02-14 22:57:46'),
(3, 'Ensino Fundamental II', '2025-02-14 22:57:55'),
(4, 'Ensino Médio', '2025-02-14 22:58:06'),
(6, 'Curso Pré-Vestibular', '2025-02-23 12:36:28'),
(8, 'Escolinhas', '2025-04-03 14:27:30');

-- --------------------------------------------------------

--
-- Estrutura da tabela `professor`
--

CREATE TABLE `professor` (
  `id_professor` int(11) NOT NULL,
  `nome_completo` varchar(150) NOT NULL,
  `nome_exibicao` varchar(100) DEFAULT NULL,
  `data_cadastro_professor` datetime DEFAULT current_timestamp(),
  `sexo` enum('Masculino','Feminino','Outro') NOT NULL DEFAULT 'Masculino',
  `limite_aulas_fixa_semana` int(11) NOT NULL DEFAULT 0,
  `telefone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `professor`
--

INSERT INTO `professor` (`id_professor`, `nome_completo`, `nome_exibicao`, `data_cadastro_professor`, `sexo`, `limite_aulas_fixa_semana`, `telefone`) VALUES
(1, 'Diézare Conde', 'Diézare', '2025-02-12 19:45:17', 'Masculino', 0, '(43) 99951-4950'),
(2, 'Beatriz Guerra', 'Bia', '2025-02-12 19:45:30', 'Feminino', 0, NULL),
(3, 'Pedro Conde Filho', 'Pedrinho', '2025-02-15 16:01:18', 'Masculino', 30, '(43) 99936-7673'),
(4, 'Rosana Aparecida Conde', 'Rosana Conde', '2025-02-15 16:01:43', 'Feminino', 16, '(43) 99924-3010'),
(5, 'Bruno César', 'Bruno', '2025-03-20 18:43:01', 'Masculino', 26, '(43) 9928-8428'),
(6, 'Osvaldo Massaji Ohya', 'Ohya', '2025-03-20 18:43:48', 'Masculino', 10, '(43) 99974-0319'),
(7, 'José Fernando Perini', 'Perini', '2025-03-20 18:48:42', 'Masculino', 4, '(43) 99108-8273'),
(8, 'Luciano Miranda', 'Luciano', '2025-05-08 19:37:19', 'Masculino', 14, '(99) 9999-9999'),
(9, 'Wilton Batista', 'Wilton', '2025-05-08 19:37:54', 'Masculino', 4, '(99) 9999-9999'),
(10, 'Eduardo Campana', 'Dudi', '2025-05-08 19:38:24', 'Masculino', 10, '(43) 99931-5714'),
(11, 'Jennifer Maia', 'Jennifer', '2025-05-08 19:38:54', 'Feminino', 5, '(99) 9999-9999'),
(12, 'Daniel / Dudi', 'Daniel / Dudi', '2025-05-08 19:39:26', 'Masculino', 4, '(99) 9999-9999');

-- --------------------------------------------------------

--
-- Estrutura da tabela `professor_categoria`
--

CREATE TABLE `professor_categoria` (
  `id_professor` int(11) NOT NULL,
  `id_categoria` int(11) NOT NULL,
  `data_cadastro` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `professor_categoria`
--

INSERT INTO `professor_categoria` (`id_professor`, `id_categoria`, `data_cadastro`) VALUES
(8, 1, '2025-05-13 17:55:45'),
(8, 2, '2025-05-13 17:55:16'),
(8, 4, '2025-05-13 17:56:05'),
(8, 9, '2025-05-13 17:55:50'),
(8, 11, '2025-05-13 18:20:52'),
(8, 12, '2025-05-13 18:21:44'),
(8, 13, '2025-05-13 18:22:14'),
(9, 5, '2025-05-13 17:56:11'),
(9, 6, '2025-05-08 19:45:13'),
(10, 3, '2025-05-13 17:55:57'),
(10, 4, '2025-05-13 17:56:05'),
(10, 7, '2025-05-13 17:55:31'),
(10, 8, '2025-05-13 17:55:27'),
(10, 14, '2025-06-03 16:37:49'),
(11, 2, '2025-05-13 17:55:16'),
(11, 10, '2025-05-13 18:09:13'),
(12, 1, '2025-05-13 17:55:45'),
(12, 3, '2025-05-13 17:55:57'),
(12, 4, '2025-05-13 17:56:05'),
(12, 9, '2025-05-13 17:55:50');

-- --------------------------------------------------------

--
-- Estrutura da tabela `professor_disciplinas`
--

CREATE TABLE `professor_disciplinas` (
  `id_professor` int(11) NOT NULL,
  `id_disciplina` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `professor_disciplinas`
--

INSERT INTO `professor_disciplinas` (`id_professor`, `id_disciplina`) VALUES
(1, 4),
(2, 2),
(3, 3),
(4, 5),
(8, 9),
(9, 9),
(10, 9),
(11, 9),
(12, 9);

-- --------------------------------------------------------

--
-- Estrutura da tabela `professor_disciplinas_turmas`
--

CREATE TABLE `professor_disciplinas_turmas` (
  `id_professor` int(11) NOT NULL,
  `id_disciplina` int(11) NOT NULL,
  `id_turma` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `professor_disciplinas_turmas`
--

INSERT INTO `professor_disciplinas_turmas` (`id_professor`, `id_disciplina`, `id_turma`) VALUES
(1, 4, 1),
(1, 4, 2),
(1, 4, 3),
(1, 4, 4),
(2, 2, 1),
(2, 2, 2),
(3, 3, 1),
(3, 3, 2);

-- --------------------------------------------------------

--
-- Estrutura da tabela `professor_restricoes`
--

CREATE TABLE `professor_restricoes` (
  `id_professor` int(11) NOT NULL,
  `id_ano_letivo` int(11) NOT NULL,
  `dia_semana` enum('Domingo','Segunda','Terca','Quarta','Quinta','Sexta','Sabado') NOT NULL,
  `numero_aula` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `professor_restricoes`
--

INSERT INTO `professor_restricoes` (`id_professor`, `id_ano_letivo`, `dia_semana`, `numero_aula`) VALUES
(1, 5, 'Domingo', 1),
(1, 5, 'Domingo', 2),
(1, 5, 'Domingo', 3),
(1, 5, 'Domingo', 4),
(1, 5, 'Domingo', 5),
(1, 5, 'Domingo', 6),
(1, 5, 'Sexta', 1),
(1, 5, 'Sexta', 2),
(1, 5, 'Sexta', 3),
(1, 5, 'Sexta', 4),
(1, 5, 'Sexta', 5),
(1, 5, 'Sexta', 6),
(1, 5, 'Sabado', 1),
(1, 5, 'Sabado', 2),
(1, 5, 'Sabado', 3),
(1, 5, 'Sabado', 4),
(1, 5, 'Sabado', 5),
(1, 5, 'Sabado', 6),
(2, 5, 'Segunda', 1),
(2, 5, 'Segunda', 2),
(2, 5, 'Segunda', 3),
(2, 5, 'Segunda', 4),
(2, 5, 'Segunda', 5),
(2, 5, 'Segunda', 6),
(2, 5, 'Terca', 1),
(2, 5, 'Terca', 2),
(2, 5, 'Terca', 3),
(2, 5, 'Terca', 4),
(2, 5, 'Terca', 5),
(2, 5, 'Terca', 6),
(2, 5, 'Quarta', 1),
(2, 5, 'Quarta', 2),
(2, 5, 'Quarta', 3),
(2, 5, 'Quarta', 4),
(2, 5, 'Quarta', 5),
(2, 5, 'Quarta', 6),
(2, 5, 'Quinta', 1),
(2, 5, 'Quinta', 2),
(2, 5, 'Quinta', 3),
(2, 5, 'Quinta', 4),
(2, 5, 'Quinta', 5),
(2, 5, 'Quinta', 6),
(2, 5, 'Sexta', 4),
(3, 5, 'Terca', 1),
(3, 5, 'Terca', 2),
(3, 5, 'Terca', 3),
(3, 5, 'Terca', 4),
(3, 5, 'Terca', 5),
(3, 5, 'Terca', 6),
(3, 5, 'Quarta', 1),
(3, 5, 'Quarta', 2),
(3, 5, 'Quarta', 3),
(3, 5, 'Quarta', 4),
(3, 5, 'Quarta', 5),
(3, 5, 'Quarta', 6),
(3, 5, 'Quinta', 1),
(3, 5, 'Quinta', 2),
(3, 5, 'Quinta', 3),
(3, 5, 'Quinta', 4),
(3, 5, 'Quinta', 5),
(3, 5, 'Quinta', 6),
(3, 5, 'Sexta', 1),
(3, 5, 'Sexta', 2),
(3, 5, 'Sexta', 3),
(3, 5, 'Sexta', 4),
(3, 5, 'Sexta', 5),
(3, 5, 'Sexta', 6);

-- --------------------------------------------------------

--
-- Estrutura da tabela `professor_turnos`
--

CREATE TABLE `professor_turnos` (
  `id_professor` int(11) NOT NULL,
  `id_turno` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `professor_turnos`
--

INSERT INTO `professor_turnos` (`id_professor`, `id_turno`) VALUES
(1, 1),
(2, 1),
(8, 2),
(8, 3),
(9, 2),
(9, 3),
(10, 2),
(10, 3),
(11, 2),
(11, 3),
(12, 2),
(12, 3);

-- --------------------------------------------------------

--
-- Estrutura da tabela `sala`
--

CREATE TABLE `sala` (
  `id_sala` int(11) NOT NULL,
  `id_ano_letivo` int(11) NOT NULL,
  `nome_sala` varchar(100) NOT NULL,
  `max_carteiras` int(11) DEFAULT NULL,
  `max_cadeiras` int(11) DEFAULT NULL,
  `capacidade_alunos` int(11) NOT NULL,
  `localizacao` varchar(255) DEFAULT NULL,
  `recursos` varchar(255) DEFAULT NULL,
  `data_cadastro` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `sala`
--

INSERT INTO `sala` (`id_sala`, `id_ano_letivo`, `nome_sala`, `max_carteiras`, `max_cadeiras`, `capacidade_alunos`, `localizacao`, `recursos`, `data_cadastro`) VALUES
(1, 5, '101', 30, 30, 30, 'Térreo', 'Projetor', '2025-03-26 20:40:50');

-- --------------------------------------------------------

--
-- Estrutura da tabela `sala_turno`
--

CREATE TABLE `sala_turno` (
  `id_sala` int(11) NOT NULL,
  `id_turno` int(11) NOT NULL,
  `id_turma` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `sala_turno`
--

INSERT INTO `sala_turno` (`id_sala`, `id_turno`, `id_turma`) VALUES
(1, 2, 1);

-- --------------------------------------------------------

--
-- Estrutura da tabela `serie`
--

CREATE TABLE `serie` (
  `id_serie` int(11) NOT NULL,
  `id_nivel_ensino` int(11) NOT NULL,
  `nome_serie` varchar(50) NOT NULL,
  `total_aulas_semana` int(11) NOT NULL,
  `data_cadastro_serie` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `serie`
--

INSERT INTO `serie` (`id_serie`, `id_nivel_ensino`, `nome_serie`, `total_aulas_semana`, `data_cadastro_serie`) VALUES
(1, 3, '6º Ano', 50, '2025-02-14 23:00:07'),
(2, 3, '7º Ano', 25, '2025-02-15 16:06:46'),
(3, 2, '1º Ano', 40, '2025-03-19 17:33:44'),
(4, 3, '8º Ano', 40, '2025-03-20 18:45:55'),
(5, 3, '9º Ano', 40, '2025-03-20 18:56:28');

-- --------------------------------------------------------

--
-- Estrutura da tabela `serie_disciplinas`
--

CREATE TABLE `serie_disciplinas` (
  `id_serie` int(11) NOT NULL,
  `id_disciplina` int(11) NOT NULL,
  `aulas_semana` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `serie_disciplinas`
--

INSERT INTO `serie_disciplinas` (`id_serie`, `id_disciplina`, `aulas_semana`) VALUES
(1, 1, 6),
(1, 2, 4),
(1, 3, 2),
(1, 4, 4),
(1, 5, 4);

-- --------------------------------------------------------

--
-- Estrutura da tabela `turma`
--

CREATE TABLE `turma` (
  `id_turma` int(11) NOT NULL,
  `id_ano_letivo` int(11) NOT NULL,
  `id_serie` int(11) NOT NULL,
  `id_turno` int(11) NOT NULL,
  `nome_turma` varchar(50) NOT NULL,
  `data_cadastro_turma` datetime DEFAULT current_timestamp(),
  `intervalos_por_dia` int(11) NOT NULL,
  `intervalos_positions` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `turma`
--

INSERT INTO `turma` (`id_turma`, `id_ano_letivo`, `id_serie`, `id_turno`, `nome_turma`, `data_cadastro_turma`, `intervalos_por_dia`, `intervalos_positions`) VALUES
(1, 5, 1, 1, 'A', '2025-02-16 12:01:55', 2, '3,5'),
(2, 5, 1, 1, 'B', '2025-02-17 20:12:58', 2, '3,5'),
(3, 5, 2, 1, 'A', '2025-02-18 19:58:37', 2, '3,5'),
(4, 5, 2, 1, 'B', '2025-02-18 19:58:46', 2, '3,5'),
(5, 5, 3, 2, 'A', '2025-03-19 17:34:32', 1, '4'),
(6, 5, 3, 2, 'B', '2025-03-19 17:35:03', 1, '4'),
(7, 5, 4, 1, 'A', '2025-03-20 19:03:06', 2, '3,5');

-- --------------------------------------------------------

--
-- Estrutura da tabela `turno`
--

CREATE TABLE `turno` (
  `id_turno` int(11) NOT NULL,
  `nome_turno` varchar(100) NOT NULL,
  `descricao_turno` varchar(255) DEFAULT NULL,
  `horario_inicio_turno` time NOT NULL,
  `horario_fim_turno` time NOT NULL,
  `data_cadastro_turno` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `turno`
--

INSERT INTO `turno` (`id_turno`, `nome_turno`, `descricao_turno`, `horario_inicio_turno`, `horario_fim_turno`, `data_cadastro_turno`) VALUES
(1, 'Matutino', 'Turno para as aulas no período da manhã', '07:10:00', '12:10:00', '2025-02-08 10:40:15'),
(2, 'Vespertino', 'Turno para as aulas no período da tarde', '13:15:00', '17:50:00', '2025-02-16 12:15:49'),
(3, 'Noturno', 'Turno para as aulas no período da noite', '18:50:00', '22:50:00', '2025-02-16 12:16:29');

-- --------------------------------------------------------

--
-- Estrutura da tabela `turno_dias`
--

CREATE TABLE `turno_dias` (
  `id_turno_dia` int(11) NOT NULL,
  `id_turno` int(11) NOT NULL,
  `dia_semana` enum('Domingo','Segunda','Terca','Quarta','Quinta','Sexta','Sabado') NOT NULL,
  `aulas_no_dia` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `turno_dias`
--

INSERT INTO `turno_dias` (`id_turno_dia`, `id_turno`, `dia_semana`, `aulas_no_dia`) VALUES
(15, 1, 'Domingo', 0),
(16, 1, 'Segunda', 6),
(17, 1, 'Terca', 6),
(18, 1, 'Quarta', 6),
(19, 1, 'Quinta', 6),
(20, 1, 'Sexta', 6),
(21, 1, 'Sabado', 0);

-- --------------------------------------------------------

--
-- Estrutura da tabela `usuario`
--

CREATE TABLE `usuario` (
  `id_usuario` int(11) NOT NULL,
  `nome_usuario` varchar(50) NOT NULL,
  `email_usuario` varchar(100) NOT NULL,
  `senha_usuario` varchar(255) NOT NULL,
  `nivel_usuario` varchar(25) NOT NULL,
  `situacao_usuario` enum('Ativo','Inativo') NOT NULL DEFAULT 'Ativo',
  `data_cadastro_usuario` datetime NOT NULL DEFAULT current_timestamp(),
  `data_alteracao_senha_usuario` datetime DEFAULT NULL,
  `imagem_usuario` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `usuario`
--

INSERT INTO `usuario` (`id_usuario`, `nome_usuario`, `email_usuario`, `senha_usuario`, `nivel_usuario`, `situacao_usuario`, `data_cadastro_usuario`, `data_alteracao_senha_usuario`, `imagem_usuario`) VALUES
(1, 'Diezare.Conde', 'diezare@localhost.com', '$2y$10$zQY0rXZz3p2XBw7xBODt4upUTeRr0EMoa/s55hXqXQP9860QUFjzW', 'Administrador', 'Ativo', '2025-03-11 13:37:33', NULL, 'http://localhost/horarios/app/assets/imgs/perfil/1741733963_1741711053_Eu.jpg'),
(2, 'Eduardo', 'dudi@localhost.com', '$2y$10$Z1qrHwdbqmPuJ1XXeeItC.1E7H/5VkTvYdKY3Y21oqFEfIb1yoPI6', 'Usuário', 'Ativo', '2025-05-08 19:30:27', NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura da tabela `usuario_niveis`
--

CREATE TABLE `usuario_niveis` (
  `id_usuario` int(11) NOT NULL,
  `id_nivel_ensino` int(11) NOT NULL,
  `data_cadastro` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `usuario_niveis`
--

INSERT INTO `usuario_niveis` (`id_usuario`, `id_nivel_ensino`, `data_cadastro`) VALUES
(1, 1, '2025-04-03 14:27:36'),
(1, 2, '2025-04-03 14:27:36'),
(1, 3, '2025-04-03 14:27:36'),
(1, 4, '2025-04-03 14:27:36'),
(1, 6, '2025-04-03 14:27:36'),
(1, 8, '2025-04-03 14:27:36'),
(2, 8, '2025-05-08 19:30:36');

--
-- Índices para tabelas despejadas
--

--
-- Índices para tabela `ano_letivo`
--
ALTER TABLE `ano_letivo`
  ADD PRIMARY KEY (`id_ano_letivo`),
  ADD UNIQUE KEY `ano` (`ano`);

--
-- Índices para tabela `categoria`
--
ALTER TABLE `categoria`
  ADD PRIMARY KEY (`id_categoria`),
  ADD KEY `id_modalidade` (`id_modalidade`);

--
-- Índices para tabela `configuracao_hora_aula_escolinha`
--
ALTER TABLE `configuracao_hora_aula_escolinha`
  ADD PRIMARY KEY (`id_configuracao`),
  ADD UNIQUE KEY `uk_ano_modalidade` (`id_ano_letivo`,`id_modalidade`),
  ADD KEY `id_modalidade` (`id_modalidade`),
  ADD KEY `fk_categoria` (`id_categoria`);

--
-- Índices para tabela `disciplina`
--
ALTER TABLE `disciplina`
  ADD PRIMARY KEY (`id_disciplina`);

--
-- Índices para tabela `eventos_calendario_escolar`
--
ALTER TABLE `eventos_calendario_escolar`
  ADD PRIMARY KEY (`id_evento`),
  ADD KEY `id_ano_letivo` (`id_ano_letivo`);

--
-- Índices para tabela `historico_horario`
--
ALTER TABLE `historico_horario`
  ADD PRIMARY KEY (`id_historico`),
  ADD KEY `id_ano_letivo` (`id_ano_letivo`);

--
-- Índices para tabela `horario`
--
ALTER TABLE `horario`
  ADD PRIMARY KEY (`id_horario`),
  ADD UNIQUE KEY `unq_turma_dia_aula` (`id_turma`,`dia_semana`,`numero_aula`),
  ADD KEY `fk_horario_disciplina` (`id_disciplina`),
  ADD KEY `fk_horario_professor` (`id_professor`);

--
-- Índices para tabela `horario_escolinha`
--
ALTER TABLE `horario_escolinha`
  ADD PRIMARY KEY (`id_horario_escolinha`),
  ADD KEY `fk_he_ano_letivo` (`id_ano_letivo`),
  ADD KEY `fk_he_nivel_ensino` (`id_nivel_ensino`),
  ADD KEY `fk_he_modalidade` (`id_modalidade`),
  ADD KEY `fk_he_categoria` (`id_categoria`),
  ADD KEY `fk_he_professor` (`id_professor`),
  ADD KEY `fk_he_turno` (`id_turno`);

--
-- Índices para tabela `instituicao`
--
ALTER TABLE `instituicao`
  ADD PRIMARY KEY (`id_instituicao`);

--
-- Índices para tabela `log_atividade`
--
ALTER TABLE `log_atividade`
  ADD PRIMARY KEY (`id_log`),
  ADD KEY `fk_log_atividade_usuario` (`id_usuario`);

--
-- Índices para tabela `modalidade`
--
ALTER TABLE `modalidade`
  ADD PRIMARY KEY (`id_modalidade`);

--
-- Índices para tabela `nivel_ensino`
--
ALTER TABLE `nivel_ensino`
  ADD PRIMARY KEY (`id_nivel_ensino`);

--
-- Índices para tabela `professor`
--
ALTER TABLE `professor`
  ADD PRIMARY KEY (`id_professor`);

--
-- Índices para tabela `professor_categoria`
--
ALTER TABLE `professor_categoria`
  ADD PRIMARY KEY (`id_professor`,`id_categoria`),
  ADD KEY `fk_pc_categoria` (`id_categoria`);

--
-- Índices para tabela `professor_disciplinas`
--
ALTER TABLE `professor_disciplinas`
  ADD PRIMARY KEY (`id_professor`,`id_disciplina`),
  ADD KEY `fk_pd_disciplina` (`id_disciplina`);

--
-- Índices para tabela `professor_disciplinas_turmas`
--
ALTER TABLE `professor_disciplinas_turmas`
  ADD PRIMARY KEY (`id_professor`,`id_disciplina`,`id_turma`),
  ADD KEY `fk_pdt_disciplina` (`id_disciplina`),
  ADD KEY `fk_pdt_turma` (`id_turma`);

--
-- Índices para tabela `professor_restricoes`
--
ALTER TABLE `professor_restricoes`
  ADD PRIMARY KEY (`id_professor`,`id_ano_letivo`,`dia_semana`,`numero_aula`),
  ADD KEY `fk_pr_ano_letivo` (`id_ano_letivo`);

--
-- Índices para tabela `professor_turnos`
--
ALTER TABLE `professor_turnos`
  ADD PRIMARY KEY (`id_professor`,`id_turno`),
  ADD KEY `fk_pt_turno` (`id_turno`);

--
-- Índices para tabela `sala`
--
ALTER TABLE `sala`
  ADD PRIMARY KEY (`id_sala`),
  ADD KEY `fk_sala_ano_letivo` (`id_ano_letivo`);

--
-- Índices para tabela `sala_turno`
--
ALTER TABLE `sala_turno`
  ADD PRIMARY KEY (`id_sala`,`id_turno`),
  ADD KEY `fk_sala_turno_turno` (`id_turno`),
  ADD KEY `fk_sala_turno_turma` (`id_turma`);

--
-- Índices para tabela `serie`
--
ALTER TABLE `serie`
  ADD PRIMARY KEY (`id_serie`),
  ADD KEY `fk_serie_nivel_ensino` (`id_nivel_ensino`);

--
-- Índices para tabela `serie_disciplinas`
--
ALTER TABLE `serie_disciplinas`
  ADD PRIMARY KEY (`id_serie`,`id_disciplina`),
  ADD KEY `fk_sd_disciplina` (`id_disciplina`);

--
-- Índices para tabela `turma`
--
ALTER TABLE `turma`
  ADD PRIMARY KEY (`id_turma`),
  ADD UNIQUE KEY `unq_turma_ano` (`id_serie`,`id_turno`,`nome_turma`,`id_ano_letivo`),
  ADD KEY `fk_turma_ano_letivo` (`id_ano_letivo`),
  ADD KEY `fk_turma_turno` (`id_turno`);

--
-- Índices para tabela `turno`
--
ALTER TABLE `turno`
  ADD PRIMARY KEY (`id_turno`);

--
-- Índices para tabela `turno_dias`
--
ALTER TABLE `turno_dias`
  ADD PRIMARY KEY (`id_turno_dia`),
  ADD KEY `fk_turno_dias_turno` (`id_turno`);

--
-- Índices para tabela `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `email_usuario` (`email_usuario`);

--
-- Índices para tabela `usuario_niveis`
--
ALTER TABLE `usuario_niveis`
  ADD PRIMARY KEY (`id_usuario`,`id_nivel_ensino`),
  ADD KEY `fk_usuario_niveis_nivel` (`id_nivel_ensino`);

--
-- AUTO_INCREMENT de tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `ano_letivo`
--
ALTER TABLE `ano_letivo`
  MODIFY `id_ano_letivo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `categoria`
--
ALTER TABLE `categoria`
  MODIFY `id_categoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de tabela `configuracao_hora_aula_escolinha`
--
ALTER TABLE `configuracao_hora_aula_escolinha`
  MODIFY `id_configuracao` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `disciplina`
--
ALTER TABLE `disciplina`
  MODIFY `id_disciplina` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT de tabela `eventos_calendario_escolar`
--
ALTER TABLE `eventos_calendario_escolar`
  MODIFY `id_evento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `historico_horario`
--
ALTER TABLE `historico_horario`
  MODIFY `id_historico` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de tabela `horario`
--
ALTER TABLE `horario`
  MODIFY `id_horario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT de tabela `horario_escolinha`
--
ALTER TABLE `horario_escolinha`
  MODIFY `id_horario_escolinha` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT de tabela `instituicao`
--
ALTER TABLE `instituicao`
  MODIFY `id_instituicao` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `log_atividade`
--
ALTER TABLE `log_atividade`
  MODIFY `id_log` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=250;

--
-- AUTO_INCREMENT de tabela `modalidade`
--
ALTER TABLE `modalidade`
  MODIFY `id_modalidade` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `nivel_ensino`
--
ALTER TABLE `nivel_ensino`
  MODIFY `id_nivel_ensino` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `professor`
--
ALTER TABLE `professor`
  MODIFY `id_professor` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de tabela `sala`
--
ALTER TABLE `sala`
  MODIFY `id_sala` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `serie`
--
ALTER TABLE `serie`
  MODIFY `id_serie` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `turma`
--
ALTER TABLE `turma`
  MODIFY `id_turma` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `turno`
--
ALTER TABLE `turno`
  MODIFY `id_turno` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `turno_dias`
--
ALTER TABLE `turno_dias`
  MODIFY `id_turno_dia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de tabela `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Restrições para despejos de tabelas
--

--
-- Limitadores para a tabela `categoria`
--
ALTER TABLE `categoria`
  ADD CONSTRAINT `categoria_ibfk_1` FOREIGN KEY (`id_modalidade`) REFERENCES `modalidade` (`id_modalidade`);

--
-- Limitadores para a tabela `configuracao_hora_aula_escolinha`
--
ALTER TABLE `configuracao_hora_aula_escolinha`
  ADD CONSTRAINT `configuracao_hora_aula_escolinha_ibfk_1` FOREIGN KEY (`id_ano_letivo`) REFERENCES `ano_letivo` (`id_ano_letivo`),
  ADD CONSTRAINT `configuracao_hora_aula_escolinha_ibfk_2` FOREIGN KEY (`id_modalidade`) REFERENCES `modalidade` (`id_modalidade`),
  ADD CONSTRAINT `fk_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categoria` (`id_categoria`);

--
-- Limitadores para a tabela `eventos_calendario_escolar`
--
ALTER TABLE `eventos_calendario_escolar`
  ADD CONSTRAINT `eventos_calendario_escolar_ibfk_1` FOREIGN KEY (`id_ano_letivo`) REFERENCES `ano_letivo` (`id_ano_letivo`);

--
-- Limitadores para a tabela `historico_horario`
--
ALTER TABLE `historico_horario`
  ADD CONSTRAINT `fk_historico_ano_letivo` FOREIGN KEY (`id_ano_letivo`) REFERENCES `ano_letivo` (`id_ano_letivo`);

--
-- Limitadores para a tabela `horario`
--
ALTER TABLE `horario`
  ADD CONSTRAINT `fk_horario_disciplina` FOREIGN KEY (`id_disciplina`) REFERENCES `disciplina` (`id_disciplina`),
  ADD CONSTRAINT `fk_horario_professor` FOREIGN KEY (`id_professor`) REFERENCES `professor` (`id_professor`),
  ADD CONSTRAINT `fk_horario_turma` FOREIGN KEY (`id_turma`) REFERENCES `turma` (`id_turma`);

--
-- Limitadores para a tabela `horario_escolinha`
--
ALTER TABLE `horario_escolinha`
  ADD CONSTRAINT `fk_he_ano_letivo` FOREIGN KEY (`id_ano_letivo`) REFERENCES `ano_letivo` (`id_ano_letivo`),
  ADD CONSTRAINT `fk_he_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categoria` (`id_categoria`),
  ADD CONSTRAINT `fk_he_modalidade` FOREIGN KEY (`id_modalidade`) REFERENCES `modalidade` (`id_modalidade`),
  ADD CONSTRAINT `fk_he_nivel_ensino` FOREIGN KEY (`id_nivel_ensino`) REFERENCES `nivel_ensino` (`id_nivel_ensino`),
  ADD CONSTRAINT `fk_he_professor` FOREIGN KEY (`id_professor`) REFERENCES `professor` (`id_professor`),
  ADD CONSTRAINT `fk_he_turno` FOREIGN KEY (`id_turno`) REFERENCES `turno` (`id_turno`);

--
-- Limitadores para a tabela `log_atividade`
--
ALTER TABLE `log_atividade`
  ADD CONSTRAINT `fk_log_atividade_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`);

--
-- Limitadores para a tabela `professor_categoria`
--
ALTER TABLE `professor_categoria`
  ADD CONSTRAINT `fk_pc_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categoria` (`id_categoria`),
  ADD CONSTRAINT `fk_pc_professor` FOREIGN KEY (`id_professor`) REFERENCES `professor` (`id_professor`);

--
-- Limitadores para a tabela `professor_disciplinas`
--
ALTER TABLE `professor_disciplinas`
  ADD CONSTRAINT `fk_pd_disciplina` FOREIGN KEY (`id_disciplina`) REFERENCES `disciplina` (`id_disciplina`),
  ADD CONSTRAINT `fk_pd_professor` FOREIGN KEY (`id_professor`) REFERENCES `professor` (`id_professor`);

--
-- Limitadores para a tabela `professor_disciplinas_turmas`
--
ALTER TABLE `professor_disciplinas_turmas`
  ADD CONSTRAINT `fk_pdt_disciplina` FOREIGN KEY (`id_disciplina`) REFERENCES `disciplina` (`id_disciplina`),
  ADD CONSTRAINT `fk_pdt_professor` FOREIGN KEY (`id_professor`) REFERENCES `professor` (`id_professor`),
  ADD CONSTRAINT `fk_pdt_turma` FOREIGN KEY (`id_turma`) REFERENCES `turma` (`id_turma`);

--
-- Limitadores para a tabela `professor_restricoes`
--
ALTER TABLE `professor_restricoes`
  ADD CONSTRAINT `fk_pr_ano_letivo` FOREIGN KEY (`id_ano_letivo`) REFERENCES `ano_letivo` (`id_ano_letivo`),
  ADD CONSTRAINT `fk_pr_professor` FOREIGN KEY (`id_professor`) REFERENCES `professor` (`id_professor`);

--
-- Limitadores para a tabela `professor_turnos`
--
ALTER TABLE `professor_turnos`
  ADD CONSTRAINT `fk_pt_professor` FOREIGN KEY (`id_professor`) REFERENCES `professor` (`id_professor`),
  ADD CONSTRAINT `fk_pt_turno` FOREIGN KEY (`id_turno`) REFERENCES `turno` (`id_turno`);

--
-- Limitadores para a tabela `sala`
--
ALTER TABLE `sala`
  ADD CONSTRAINT `fk_sala_ano_letivo` FOREIGN KEY (`id_ano_letivo`) REFERENCES `ano_letivo` (`id_ano_letivo`);

--
-- Limitadores para a tabela `sala_turno`
--
ALTER TABLE `sala_turno`
  ADD CONSTRAINT `fk_sala_turno_sala` FOREIGN KEY (`id_sala`) REFERENCES `sala` (`id_sala`),
  ADD CONSTRAINT `fk_sala_turno_turma` FOREIGN KEY (`id_turma`) REFERENCES `turma` (`id_turma`),
  ADD CONSTRAINT `fk_sala_turno_turno` FOREIGN KEY (`id_turno`) REFERENCES `turno` (`id_turno`);

--
-- Limitadores para a tabela `serie`
--
ALTER TABLE `serie`
  ADD CONSTRAINT `fk_serie_nivel_ensino` FOREIGN KEY (`id_nivel_ensino`) REFERENCES `nivel_ensino` (`id_nivel_ensino`);

--
-- Limitadores para a tabela `serie_disciplinas`
--
ALTER TABLE `serie_disciplinas`
  ADD CONSTRAINT `fk_sd_disciplina` FOREIGN KEY (`id_disciplina`) REFERENCES `disciplina` (`id_disciplina`),
  ADD CONSTRAINT `fk_sd_serie` FOREIGN KEY (`id_serie`) REFERENCES `serie` (`id_serie`);

--
-- Limitadores para a tabela `turma`
--
ALTER TABLE `turma`
  ADD CONSTRAINT `fk_turma_ano_letivo` FOREIGN KEY (`id_ano_letivo`) REFERENCES `ano_letivo` (`id_ano_letivo`),
  ADD CONSTRAINT `fk_turma_serie` FOREIGN KEY (`id_serie`) REFERENCES `serie` (`id_serie`),
  ADD CONSTRAINT `fk_turma_turno` FOREIGN KEY (`id_turno`) REFERENCES `turno` (`id_turno`);

--
-- Limitadores para a tabela `turno_dias`
--
ALTER TABLE `turno_dias`
  ADD CONSTRAINT `fk_turno_dias_turno` FOREIGN KEY (`id_turno`) REFERENCES `turno` (`id_turno`);

--
-- Limitadores para a tabela `usuario_niveis`
--
ALTER TABLE `usuario_niveis`
  ADD CONSTRAINT `fk_usuario_niveis_nivel` FOREIGN KEY (`id_nivel_ensino`) REFERENCES `nivel_ensino` (`id_nivel_ensino`),
  ADD CONSTRAINT `fk_usuario_niveis_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
