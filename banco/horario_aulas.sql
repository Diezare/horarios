-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 13/01/2026 às 02:04
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

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
-- Estrutura para tabela `ano_letivo`
--

CREATE TABLE `ano_letivo` (
  `id_ano_letivo` int(11) NOT NULL,
  `ano` year(4) NOT NULL,
  `data_inicio` date NOT NULL,
  `data_fim` date NOT NULL,
  `data_cadastro` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `ano_letivo`
--

INSERT INTO `ano_letivo` (`id_ano_letivo`, `ano`, `data_inicio`, `data_fim`, `data_cadastro`) VALUES
(5, '2025', '2025-01-01', '2025-12-31', '2025-02-12 20:48:26'),
(7, '2026', '2026-01-01', '2026-12-31', '2025-12-16 10:31:22');

-- --------------------------------------------------------

--
-- Estrutura para tabela `categoria`
--

CREATE TABLE `categoria` (
  `id_categoria` int(11) NOT NULL,
  `id_modalidade` int(11) NOT NULL,
  `nome_categoria` varchar(100) NOT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  `data_cadastro` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `categoria`
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
-- Estrutura para tabela `configuracao_hora_aula_escolinha`
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
-- Estrutura para tabela `disciplina`
--

CREATE TABLE `disciplina` (
  `id_disciplina` int(11) NOT NULL,
  `nome_disciplina` varchar(100) NOT NULL,
  `sigla_disciplina` varchar(20) NOT NULL,
  `data_cadastro_disciplina` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `disciplina`
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
(23, 'Projeto de Vida', 'PJV', '2025-03-27 14:30:06'),
(25, 'Matemática ¹', 'MAA', '2026-01-12 20:43:45'),
(26, 'Matemática ²', 'MAB', '2026-01-12 20:43:56');

-- --------------------------------------------------------

--
-- Estrutura para tabela `eventos_calendario_escolar`
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
-- Despejando dados para a tabela `eventos_calendario_escolar`
--

INSERT INTO `eventos_calendario_escolar` (`id_evento`, `id_ano_letivo`, `tipo_evento`, `nome_evento`, `data_inicio`, `data_fim`, `observacoes`) VALUES
(1, 5, 'feriado', 'Aniversário de Apucarana - 28 de janeiro', '2025-01-28', '2025-01-28', 'Aniversário de Apucarana'),
(2, 5, 'feriado', 'Padroeira de Apucara', '2025-02-11', '2025-02-11', ''),
(3, 5, 'ferias', 'Férias Escolares', '2025-01-01', '2025-01-31', '');

-- --------------------------------------------------------

--
-- Estrutura para tabela `historico_horario`
--

CREATE TABLE `historico_horario` (
  `id_historico` int(11) NOT NULL,
  `id_horario_original` int(11) NOT NULL,
  `id_turma` int(11) NOT NULL,
  `id_turno` int(11) NOT NULL DEFAULT 1,
  `id_ano_letivo` int(11) NOT NULL,
  `dia_semana` enum('Domingo','Segunda','Terca','Quarta','Quinta','Sexta','Sabado') NOT NULL,
  `numero_aula` int(11) NOT NULL,
  `id_disciplina` int(11) NOT NULL,
  `id_professor` int(11) NOT NULL,
  `data_criacao_original` datetime NOT NULL,
  `data_arquivamento` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `horario`
--

CREATE TABLE `horario` (
  `id_horario` int(11) NOT NULL,
  `id_turma` int(11) NOT NULL,
  `id_turno` int(11) NOT NULL,
  `id_ano_letivo` int(11) NOT NULL,
  `dia_semana` enum('Domingo','Segunda','Terca','Quarta','Quinta','Sexta','Sabado') NOT NULL,
  `numero_aula` int(11) NOT NULL,
  `id_disciplina` int(11) NOT NULL,
  `id_professor` int(11) NOT NULL,
  `data_criacao` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `horario`
--

INSERT INTO `horario` (`id_horario`, `id_turma`, `id_turno`, `id_ano_letivo`, `dia_semana`, `numero_aula`, `id_disciplina`, `id_professor`, `data_criacao`) VALUES
(23038, 17, 1, 7, 'Segunda', 1, 17, 19, '2026-01-12 21:56:03'),
(23039, 17, 1, 7, 'Segunda', 2, 3, 10, '2026-01-12 21:56:03'),
(23040, 17, 1, 7, 'Segunda', 3, 8, 58, '2026-01-12 21:56:03'),
(23041, 17, 1, 7, 'Segunda', 4, 4, 22, '2026-01-12 21:56:03'),
(23042, 17, 1, 7, 'Segunda', 5, 5, 6, '2026-01-12 21:56:03'),
(23043, 17, 1, 7, 'Segunda', 6, 17, 19, '2026-01-12 21:56:03'),
(23044, 17, 1, 7, 'Terca', 1, 2, 2, '2026-01-12 21:56:03'),
(23045, 17, 1, 7, 'Terca', 2, 8, 58, '2026-01-12 21:56:03'),
(23046, 17, 1, 7, 'Terca', 3, 9, 57, '2026-01-12 21:56:03'),
(23047, 17, 1, 7, 'Terca', 4, 3, 10, '2026-01-12 21:56:03'),
(23048, 17, 1, 7, 'Terca', 5, 5, 6, '2026-01-12 21:56:03'),
(23049, 17, 1, 7, 'Terca', 6, 17, 19, '2026-01-12 21:56:03'),
(23050, 17, 1, 7, 'Quarta', 1, 17, 19, '2026-01-12 21:56:03'),
(23051, 17, 1, 7, 'Quarta', 2, 16, 21, '2026-01-12 21:56:03'),
(23052, 17, 1, 7, 'Quarta', 3, 3, 10, '2026-01-12 21:56:03'),
(23053, 17, 1, 7, 'Quarta', 4, 2, 2, '2026-01-12 21:56:03'),
(23054, 17, 1, 7, 'Quarta', 5, 4, 22, '2026-01-12 21:56:03'),
(23055, 17, 1, 7, 'Quarta', 6, 2, 2, '2026-01-12 21:56:03'),
(23056, 17, 1, 7, 'Quinta', 1, 18, 2, '2026-01-12 21:56:03'),
(23057, 17, 1, 7, 'Quinta', 2, 2, 2, '2026-01-12 21:56:03'),
(23058, 17, 1, 7, 'Quinta', 3, 5, 6, '2026-01-12 21:56:03'),
(23059, 17, 1, 7, 'Quinta', 4, 3, 10, '2026-01-12 21:56:03'),
(23060, 17, 1, 7, 'Quinta', 5, 9, 57, '2026-01-12 21:56:03'),
(23061, 17, 1, 7, 'Quinta', 6, 2, 2, '2026-01-12 21:56:03'),
(23062, 17, 1, 7, 'Sexta', 1, 6, 8, '2026-01-12 21:56:03'),
(23063, 17, 1, 7, 'Sexta', 2, 3, 10, '2026-01-12 21:56:03'),
(23064, 17, 1, 7, 'Sexta', 3, 2, 2, '2026-01-12 21:56:03'),
(23065, 17, 1, 7, 'Sexta', 4, 16, 21, '2026-01-12 21:56:03'),
(23066, 17, 1, 7, 'Sexta', 5, 19, 15, '2026-01-12 21:56:03'),
(23067, 17, 1, 7, 'Sexta', 6, 8, 58, '2026-01-12 21:56:03'),
(23068, 16, 1, 7, 'Segunda', 1, 3, 10, '2026-01-12 21:56:03'),
(23069, 16, 1, 7, 'Segunda', 2, 8, 58, '2026-01-12 21:56:03'),
(23070, 16, 1, 7, 'Segunda', 3, 4, 22, '2026-01-12 21:56:03'),
(23071, 16, 1, 7, 'Segunda', 4, 17, 19, '2026-01-12 21:56:03'),
(23072, 16, 1, 7, 'Segunda', 5, 16, 21, '2026-01-12 21:56:03'),
(23073, 16, 1, 7, 'Segunda', 6, 5, 6, '2026-01-12 21:56:03'),
(23074, 16, 1, 7, 'Terca', 1, 3, 10, '2026-01-12 21:56:03'),
(23075, 16, 1, 7, 'Terca', 2, 17, 19, '2026-01-12 21:56:03'),
(23076, 16, 1, 7, 'Terca', 3, 9, 9, '2026-01-12 21:56:03'),
(23077, 16, 1, 7, 'Terca', 4, 8, 58, '2026-01-12 21:56:03'),
(23078, 16, 1, 7, 'Terca', 5, 17, 19, '2026-01-12 21:56:03'),
(23079, 16, 1, 7, 'Terca', 6, 2, 2, '2026-01-12 21:56:03'),
(23080, 16, 1, 7, 'Quarta', 1, 2, 2, '2026-01-12 21:56:03'),
(23081, 16, 1, 7, 'Quarta', 2, 3, 10, '2026-01-12 21:56:03'),
(23082, 16, 1, 7, 'Quarta', 3, 2, 2, '2026-01-12 21:56:03'),
(23083, 16, 1, 7, 'Quarta', 4, 17, 19, '2026-01-12 21:56:03'),
(23084, 16, 1, 7, 'Quarta', 5, 5, 6, '2026-01-12 21:56:03'),
(23085, 16, 1, 7, 'Quarta', 6, 4, 22, '2026-01-12 21:56:03'),
(23086, 16, 1, 7, 'Quinta', 1, 18, 21, '2026-01-12 21:56:03'),
(23087, 16, 1, 7, 'Quinta', 2, 5, 6, '2026-01-12 21:56:03'),
(23088, 16, 1, 7, 'Quinta', 3, 3, 10, '2026-01-12 21:56:03'),
(23089, 16, 1, 7, 'Quinta', 4, 2, 2, '2026-01-12 21:56:03'),
(23090, 16, 1, 7, 'Quinta', 5, 9, 9, '2026-01-12 21:56:03'),
(23091, 16, 1, 7, 'Quinta', 6, 16, 21, '2026-01-12 21:56:03'),
(23092, 16, 1, 7, 'Sexta', 1, 3, 10, '2026-01-12 21:56:03'),
(23093, 16, 1, 7, 'Sexta', 2, 2, 2, '2026-01-12 21:56:03'),
(23094, 16, 1, 7, 'Sexta', 3, 19, 15, '2026-01-12 21:56:03'),
(23095, 16, 1, 7, 'Sexta', 4, 2, 2, '2026-01-12 21:56:03'),
(23096, 16, 1, 7, 'Sexta', 5, 8, 58, '2026-01-12 21:56:03'),
(23097, 16, 1, 7, 'Sexta', 6, 6, 8, '2026-01-12 21:56:03'),
(23098, 14, 1, 7, 'Segunda', 1, 8, 16, '2026-01-12 21:56:03'),
(23099, 14, 1, 7, 'Segunda', 2, 17, 19, '2026-01-12 21:56:03'),
(23100, 14, 1, 7, 'Segunda', 3, 5, 6, '2026-01-12 21:56:03'),
(23101, 14, 1, 7, 'Segunda', 4, 5, 6, '2026-01-12 21:56:03'),
(23102, 14, 1, 7, 'Segunda', 5, 17, 19, '2026-01-12 21:56:03'),
(23103, 14, 1, 7, 'Segunda', 6, 8, 16, '2026-01-12 21:56:03'),
(23104, 14, 1, 7, 'Terca', 1, 17, 19, '2026-01-12 21:56:03'),
(23105, 14, 1, 7, 'Terca', 2, 3, 10, '2026-01-12 21:56:03'),
(23106, 14, 1, 7, 'Terca', 3, 3, 10, '2026-01-12 21:56:03'),
(23107, 14, 1, 7, 'Terca', 4, 2, 2, '2026-01-12 21:56:03'),
(23108, 14, 1, 7, 'Terca', 5, 16, 21, '2026-01-12 21:56:03'),
(23109, 14, 1, 7, 'Terca', 6, 9, 57, '2026-01-12 21:56:03'),
(23110, 14, 1, 7, 'Quarta', 1, 3, 10, '2026-01-12 21:56:03'),
(23111, 14, 1, 7, 'Quarta', 2, 2, 2, '2026-01-12 21:56:03'),
(23112, 14, 1, 7, 'Quarta', 3, 4, 22, '2026-01-12 21:56:03'),
(23113, 14, 1, 7, 'Quarta', 4, 4, 22, '2026-01-12 21:56:03'),
(23114, 14, 1, 7, 'Quarta', 5, 17, 19, '2026-01-12 21:56:03'),
(23115, 14, 1, 7, 'Quarta', 6, 16, 21, '2026-01-12 21:56:03'),
(23116, 14, 1, 7, 'Quinta', 1, 18, 48, '2026-01-12 21:56:03'),
(23117, 14, 1, 7, 'Quinta', 2, 19, 15, '2026-01-12 21:56:03'),
(23118, 14, 1, 7, 'Quinta', 3, 2, 2, '2026-01-12 21:56:03'),
(23119, 14, 1, 7, 'Quinta', 4, 9, 57, '2026-01-12 21:56:03'),
(23120, 14, 1, 7, 'Quinta', 5, 3, 10, '2026-01-12 21:56:03'),
(23121, 14, 1, 7, 'Quinta', 6, 5, 6, '2026-01-12 21:56:03'),
(23122, 14, 1, 7, 'Sexta', 1, 2, 2, '2026-01-12 21:56:03'),
(23123, 14, 1, 7, 'Sexta', 2, 6, 8, '2026-01-12 21:56:04'),
(23124, 14, 1, 7, 'Sexta', 3, 3, 10, '2026-01-12 21:56:04'),
(23125, 14, 1, 7, 'Sexta', 4, 4, 22, '2026-01-12 21:56:04'),
(23126, 14, 1, 7, 'Sexta', 5, 8, 16, '2026-01-12 21:56:04'),
(23127, 14, 1, 7, 'Sexta', 6, 2, 2, '2026-01-12 21:56:04'),
(23128, 15, 1, 7, 'Segunda', 2, 16, 21, '2026-01-12 21:56:04'),
(23129, 15, 1, 7, 'Segunda', 3, 3, 10, '2026-01-12 21:56:04'),
(23130, 15, 1, 7, 'Segunda', 4, 3, 10, '2026-01-12 21:56:04'),
(23131, 15, 1, 7, 'Segunda', 5, 4, 22, '2026-01-12 21:56:04'),
(23132, 15, 1, 7, 'Terca', 2, 2, 2, '2026-01-12 21:56:04'),
(23133, 15, 1, 7, 'Terca', 3, 2, 2, '2026-01-12 21:56:04'),
(23134, 15, 1, 7, 'Terca', 4, 5, 6, '2026-01-12 21:56:04'),
(23135, 15, 1, 7, 'Terca', 5, 2, 2, '2026-01-12 21:56:04'),
(23136, 15, 1, 7, 'Terca', 6, 9, 9, '2026-01-12 21:56:04'),
(23137, 15, 1, 7, 'Quarta', 1, 4, 22, '2026-01-12 21:56:04'),
(23138, 15, 1, 7, 'Quarta', 2, 17, 19, '2026-01-12 21:56:04'),
(23139, 15, 1, 7, 'Quarta', 3, 5, 6, '2026-01-12 21:56:04'),
(23140, 15, 1, 7, 'Quarta', 4, 5, 6, '2026-01-12 21:56:04'),
(23141, 15, 1, 7, 'Quarta', 5, 2, 2, '2026-01-12 21:56:04'),
(23142, 15, 1, 7, 'Quarta', 6, 17, 19, '2026-01-12 21:56:04'),
(23143, 15, 1, 7, 'Quinta', 1, 18, 49, '2026-01-12 21:56:04'),
(23144, 15, 1, 7, 'Quinta', 3, 4, 22, '2026-01-12 21:56:04'),
(23145, 15, 1, 7, 'Quinta', 4, 9, 9, '2026-01-12 21:56:04'),
(23146, 15, 1, 7, 'Quinta', 5, 2, 2, '2026-01-12 21:56:04'),
(23147, 15, 1, 7, 'Quinta', 6, 3, 10, '2026-01-12 21:56:04'),
(23148, 15, 1, 7, 'Sexta', 3, 16, 21, '2026-01-12 21:56:04'),
(23149, 15, 1, 7, 'Sexta', 4, 3, 10, '2026-01-12 21:56:04'),
(23150, 15, 1, 7, 'Sexta', 5, 6, 8, '2026-01-12 21:56:04'),
(23151, 15, 1, 7, 'Sexta', 6, 19, 15, '2026-01-12 21:56:04'),
(23152, 13, 1, 7, 'Segunda', 1, 25, 14, '2026-01-12 21:56:04'),
(23153, 13, 1, 7, 'Segunda', 2, 25, 14, '2026-01-12 21:56:04'),
(23154, 13, 1, 7, 'Segunda', 3, 16, 4, '2026-01-12 21:56:04'),
(23155, 13, 1, 7, 'Segunda', 4, 17, 21, '2026-01-12 21:56:04'),
(23156, 13, 1, 7, 'Segunda', 5, 12, 23, '2026-01-12 21:56:04'),
(23157, 13, 1, 7, 'Segunda', 6, 16, 4, '2026-01-12 21:56:04'),
(23158, 13, 1, 7, 'Terca', 1, 17, 21, '2026-01-12 21:56:04'),
(23159, 13, 1, 7, 'Terca', 2, 6, 8, '2026-01-12 21:56:04'),
(23160, 13, 1, 7, 'Terca', 3, 25, 14, '2026-01-12 21:56:04'),
(23161, 13, 1, 7, 'Terca', 4, 9, 57, '2026-01-12 21:56:04'),
(23162, 13, 1, 7, 'Terca', 5, 26, 3, '2026-01-12 21:56:04'),
(23163, 13, 1, 7, 'Terca', 6, 5, 6, '2026-01-12 21:56:04'),
(23164, 13, 1, 7, 'Quarta', 1, 25, 14, '2026-01-12 21:56:04'),
(23165, 13, 1, 7, 'Quarta', 2, 5, 6, '2026-01-12 21:56:04'),
(23166, 13, 1, 7, 'Quarta', 3, 8, 16, '2026-01-12 21:56:04'),
(23167, 13, 1, 7, 'Quarta', 4, 26, 3, '2026-01-12 21:56:04'),
(23168, 13, 1, 7, 'Quarta', 5, 17, 21, '2026-01-12 21:56:04'),
(23169, 13, 1, 7, 'Quarta', 6, 26, 3, '2026-01-12 21:56:04'),
(23170, 13, 1, 7, 'Quinta', 1, 18, 6, '2026-01-12 21:56:04'),
(23171, 13, 1, 7, 'Quinta', 2, 9, 57, '2026-01-12 21:56:04'),
(23172, 13, 1, 7, 'Quinta', 3, 3, 18, '2026-01-12 21:56:04'),
(23173, 13, 1, 7, 'Quinta', 4, 4, 22, '2026-01-12 21:56:04'),
(23174, 13, 1, 7, 'Quinta', 5, 5, 6, '2026-01-12 21:56:04'),
(23175, 13, 1, 7, 'Quinta', 6, 19, 15, '2026-01-12 21:56:04'),
(23176, 13, 1, 7, 'Sexta', 1, 4, 22, '2026-01-12 21:56:04'),
(23177, 13, 1, 7, 'Sexta', 2, 8, 16, '2026-01-12 21:56:04'),
(23178, 13, 1, 7, 'Sexta', 3, 4, 22, '2026-01-12 21:56:04'),
(23179, 13, 1, 7, 'Sexta', 4, 3, 18, '2026-01-12 21:56:04'),
(23180, 13, 1, 7, 'Sexta', 5, 17, 21, '2026-01-12 21:56:04'),
(23181, 13, 1, 7, 'Sexta', 6, 8, 16, '2026-01-12 21:56:04'),
(23182, 10, 1, 7, 'Segunda', 1, 17, 21, '2026-01-12 21:56:04'),
(23183, 10, 1, 7, 'Segunda', 2, 8, 16, '2026-01-12 21:56:04'),
(23184, 10, 1, 7, 'Segunda', 3, 25, 14, '2026-01-12 21:56:04'),
(23185, 10, 1, 7, 'Segunda', 4, 12, 23, '2026-01-12 21:56:04'),
(23186, 10, 1, 7, 'Segunda', 5, 16, 4, '2026-01-12 21:56:04'),
(23187, 10, 1, 7, 'Segunda', 6, 4, 22, '2026-01-12 21:56:04'),
(23188, 10, 1, 7, 'Terca', 1, 25, 14, '2026-01-12 21:56:04'),
(23189, 10, 1, 7, 'Terca', 2, 25, 14, '2026-01-12 21:56:04'),
(23190, 10, 1, 7, 'Terca', 3, 5, 6, '2026-01-12 21:56:04'),
(23191, 10, 1, 7, 'Terca', 4, 9, 9, '2026-01-12 21:56:04'),
(23192, 10, 1, 7, 'Terca', 5, 25, 14, '2026-01-12 21:56:04'),
(23193, 10, 1, 7, 'Terca', 6, 17, 21, '2026-01-12 21:56:04'),
(23194, 10, 1, 7, 'Quarta', 1, 17, 21, '2026-01-12 21:56:04'),
(23195, 10, 1, 7, 'Quarta', 2, 26, 3, '2026-01-12 21:56:04'),
(23196, 10, 1, 7, 'Quarta', 3, 26, 3, '2026-01-12 21:56:04'),
(23197, 10, 1, 7, 'Quarta', 4, 8, 16, '2026-01-12 21:56:04'),
(23198, 10, 1, 7, 'Quarta', 5, 26, 3, '2026-01-12 21:56:04'),
(23199, 10, 1, 7, 'Quarta', 6, 5, 6, '2026-01-12 21:56:04'),
(23200, 10, 1, 7, 'Quinta', 1, 18, 51, '2026-01-12 21:56:04'),
(23201, 10, 1, 7, 'Quinta', 2, 9, 9, '2026-01-12 21:56:04'),
(23202, 10, 1, 7, 'Quinta', 3, 19, 15, '2026-01-12 21:56:04'),
(23203, 10, 1, 7, 'Quinta', 4, 5, 6, '2026-01-12 21:56:04'),
(23204, 10, 1, 7, 'Quinta', 5, 4, 22, '2026-01-12 21:56:04'),
(23205, 10, 1, 7, 'Quinta', 6, 4, 22, '2026-01-12 21:56:04'),
(23206, 10, 1, 7, 'Sexta', 1, 8, 16, '2026-01-12 21:56:04'),
(23207, 10, 1, 7, 'Sexta', 3, 3, 18, '2026-01-12 21:56:04'),
(23208, 10, 1, 7, 'Sexta', 4, 6, 8, '2026-01-12 21:56:04'),
(23209, 10, 1, 7, 'Sexta', 5, 3, 18, '2026-01-12 21:56:04'),
(23210, 10, 1, 7, 'Sexta', 6, 17, 21, '2026-01-12 21:56:04'),
(23211, 11, 1, 7, 'Segunda', 1, 16, 4, '2026-01-12 21:56:04'),
(23212, 11, 1, 7, 'Segunda', 2, 4, 22, '2026-01-12 21:56:04'),
(23213, 11, 1, 7, 'Segunda', 3, 17, 21, '2026-01-12 21:56:04'),
(23214, 11, 1, 7, 'Segunda', 4, 13, 17, '2026-01-12 21:56:04'),
(23215, 11, 1, 7, 'Segunda', 5, 25, 14, '2026-01-12 21:56:04'),
(23216, 11, 1, 7, 'Segunda', 6, 13, 17, '2026-01-12 21:56:04'),
(23217, 11, 1, 7, 'Terca', 1, 6, 8, '2026-01-12 21:56:04'),
(23218, 11, 1, 7, 'Terca', 2, 17, 21, '2026-01-12 21:56:04'),
(23219, 11, 1, 7, 'Terca', 3, 26, 3, '2026-01-12 21:56:04'),
(23220, 11, 1, 7, 'Terca', 4, 26, 3, '2026-01-12 21:56:04'),
(23221, 11, 1, 7, 'Terca', 5, 9, 57, '2026-01-12 21:56:04'),
(23222, 11, 1, 7, 'Terca', 6, 25, 14, '2026-01-12 21:56:04'),
(23223, 11, 1, 7, 'Quarta', 1, 26, 3, '2026-01-12 21:56:04'),
(23224, 11, 1, 7, 'Quarta', 2, 4, 22, '2026-01-12 21:56:04'),
(23225, 11, 1, 7, 'Quarta', 3, 5, 12, '2026-01-12 21:56:04'),
(23226, 11, 1, 7, 'Quarta', 4, 25, 14, '2026-01-12 21:56:04'),
(23227, 11, 1, 7, 'Quarta', 5, 5, 12, '2026-01-12 21:56:04'),
(23228, 11, 1, 7, 'Quarta', 6, 16, 4, '2026-01-12 21:56:04'),
(23229, 11, 1, 7, 'Quinta', 1, 18, 12, '2026-01-12 21:56:04'),
(23230, 11, 1, 7, 'Quinta', 2, 3, 20, '2026-01-12 21:56:04'),
(23231, 11, 1, 7, 'Quinta', 3, 9, 57, '2026-01-12 21:56:04'),
(23232, 11, 1, 7, 'Quinta', 4, 5, 12, '2026-01-12 21:56:04'),
(23233, 11, 1, 7, 'Quinta', 5, 17, 21, '2026-01-12 21:56:04'),
(23234, 11, 1, 7, 'Quinta', 6, 3, 20, '2026-01-12 21:56:04'),
(23235, 11, 1, 7, 'Sexta', 1, 19, 15, '2026-01-12 21:56:04'),
(23236, 11, 1, 7, 'Sexta', 2, 17, 21, '2026-01-12 21:56:04'),
(23237, 11, 1, 7, 'Sexta', 3, 7, 16, '2026-01-12 21:56:04'),
(23238, 11, 1, 7, 'Sexta', 4, 12, 23, '2026-01-12 21:56:04'),
(23239, 11, 1, 7, 'Sexta', 5, 4, 22, '2026-01-12 21:56:04'),
(23240, 11, 1, 7, 'Sexta', 6, 12, 23, '2026-01-12 21:56:04'),
(23241, 12, 1, 7, 'Segunda', 1, 4, 22, '2026-01-12 21:56:04'),
(23242, 12, 1, 7, 'Segunda', 2, 16, 4, '2026-01-12 21:56:04'),
(23243, 12, 1, 7, 'Segunda', 3, 13, 17, '2026-01-12 21:56:04'),
(23244, 12, 1, 7, 'Segunda', 4, 25, 14, '2026-01-12 21:56:04'),
(23245, 12, 1, 7, 'Segunda', 5, 13, 17, '2026-01-12 21:56:04'),
(23246, 12, 1, 7, 'Segunda', 6, 12, 23, '2026-01-12 21:56:04'),
(23247, 12, 1, 7, 'Terca', 1, 26, 3, '2026-01-12 21:56:04'),
(23248, 12, 1, 7, 'Terca', 2, 26, 3, '2026-01-12 21:56:04'),
(23249, 12, 1, 7, 'Terca', 3, 17, 21, '2026-01-12 21:56:04'),
(23250, 12, 1, 7, 'Terca', 4, 25, 14, '2026-01-12 21:56:04'),
(23251, 12, 1, 7, 'Terca', 5, 9, 9, '2026-01-12 21:56:04'),
(23252, 12, 1, 7, 'Terca', 6, 26, 3, '2026-01-12 21:56:04'),
(23253, 12, 1, 7, 'Quarta', 1, 5, 12, '2026-01-12 21:56:04'),
(23254, 12, 1, 7, 'Quarta', 2, 7, 16, '2026-01-12 21:56:04'),
(23255, 12, 1, 7, 'Quarta', 3, 25, 14, '2026-01-12 21:56:04'),
(23256, 12, 1, 7, 'Quarta', 4, 17, 21, '2026-01-12 21:56:04'),
(23257, 12, 1, 7, 'Quarta', 5, 16, 4, '2026-01-12 21:56:04'),
(23258, 12, 1, 7, 'Quarta', 6, 5, 12, '2026-01-12 21:56:04'),
(23259, 12, 1, 7, 'Quinta', 1, 18, 15, '2026-01-12 21:56:04'),
(23260, 12, 1, 7, 'Quinta', 2, 17, 21, '2026-01-12 21:56:04'),
(23261, 12, 1, 7, 'Quinta', 3, 9, 9, '2026-01-12 21:56:04'),
(23262, 12, 1, 7, 'Quinta', 4, 3, 20, '2026-01-12 21:56:04'),
(23263, 12, 1, 7, 'Quinta', 5, 3, 20, '2026-01-12 21:56:04'),
(23264, 12, 1, 7, 'Quinta', 6, 5, 12, '2026-01-12 21:56:04'),
(23265, 12, 1, 7, 'Sexta', 1, 17, 21, '2026-01-12 21:56:04'),
(23266, 12, 1, 7, 'Sexta', 2, 4, 22, '2026-01-12 21:56:04'),
(23267, 12, 1, 7, 'Sexta', 3, 6, 8, '2026-01-12 21:56:04'),
(23268, 12, 1, 7, 'Sexta', 4, 19, 15, '2026-01-12 21:56:04'),
(23269, 12, 1, 7, 'Sexta', 5, 12, 23, '2026-01-12 21:56:04'),
(23270, 12, 1, 7, 'Sexta', 6, 4, 22, '2026-01-12 21:56:04');

-- --------------------------------------------------------

--
-- Estrutura para tabela `horario_escolinha`
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
-- Despejando dados para a tabela `horario_escolinha`
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
-- Estrutura para tabela `horario_fixos`
--

CREATE TABLE `horario_fixos` (
  `id_horario_fixo` int(11) NOT NULL,
  `id_turma` int(11) NOT NULL,
  `dia_semana` enum('Segunda','Terca','Quarta','Quinta','Sexta','Sabado','Domingo') NOT NULL,
  `numero_aula` int(11) NOT NULL,
  `id_disciplina` int(11) DEFAULT NULL,
  `id_professor` int(11) DEFAULT NULL,
  `id_ano_letivo` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `instituicao`
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
-- Despejando dados para a tabela `instituicao`
--

INSERT INTO `instituicao` (`id_instituicao`, `nome_instituicao`, `cnpj_instituicao`, `endereco_instituicao`, `telefone_instituicao`, `email_instituicao`, `data_cadastro_instituicao`, `imagem_instituicao`) VALUES
(1, 'Colégio Mater Dei', '04.940.720/0001-98', 'Rua Professora Talita Bresolin, 1139', '(43) 3423-0500', 'secretaria@materdeiapucarana.com.br', '2025-02-07 22:52:00', 'http://localhost/horarios/app/assets/imgs/logo/1740345212_1740159747_LOGO MATER DEI.png');

-- --------------------------------------------------------

--
-- Estrutura para tabela `log_atividade`
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
-- Despejando dados para a tabela `log_atividade`
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
(249, 1, 'Diezare.Conde', '192.168.0.22', 'sucesso_login', '2025-10-06 10:49:59'),
(250, NULL, 'Desconhecido', '192.168.0.22', 'saida_sistema', '2025-10-06 12:34:13'),
(251, NULL, 'Desconhecido', '192.168.0.146', 'acesso_negado_login', '2025-10-22 13:16:39'),
(252, NULL, 'Diézare.Conde', '192.168.0.146', 'acesso_negado_login', '2025-10-22 13:17:03'),
(253, 1, 'Diezare.Conde', '192.168.0.146', 'sucesso_login', '2025-10-22 13:17:10'),
(254, 1, 'Diezare.Conde', '192.168.0.146', 'saida_sistema', '2025-10-22 13:45:59'),
(255, NULL, 'Desconhecido', '192.168.0.146', 'acesso_negado_login', '2025-10-22 13:46:17'),
(256, 1, 'Diezare.Conde', '192.168.0.146', 'sucesso_login', '2025-10-22 13:46:33'),
(257, 1, 'Diezare.Conde', '192.168.0.146', 'saida_sistema', '2025-10-22 14:11:22'),
(258, 1, 'Diezare.Conde', '192.168.0.146', 'sucesso_login', '2025-10-22 14:11:42'),
(259, NULL, 'Desconhecido', '192.168.0.146', 'acesso_negado_login', '2025-10-23 17:34:05'),
(260, 1, 'Diezare.Conde', '192.168.0.146', 'sucesso_login', '2025-10-23 17:34:19'),
(261, 1, 'Diezare.Conde', '192.168.0.146', 'saida_sistema', '2025-10-23 17:35:41'),
(262, NULL, 'adsd', '192.168.0.22', 'acesso_negado_login', '2025-10-23 19:08:53'),
(263, 1, 'Diezare.Conde', '189.39.108.218', 'sucesso_login', '2025-10-23 21:55:45'),
(264, 1, 'Diezare.Conde', '189.39.108.218', 'sucesso_login', '2025-10-23 22:00:09'),
(265, 1, 'Diezare.Conde', '189.39.108.218', 'saida_sistema', '2025-10-23 22:00:32'),
(266, NULL, 'SemUsuario', '189.39.108.218', 'falha_login', '2025-10-23 22:02:08'),
(267, NULL, 'SemUsuario', '189.39.108.218', 'falha_login', '2025-10-23 22:02:11'),
(268, NULL, 'SemUsuario', '189.39.108.218', 'falha_login', '2025-10-23 22:02:14'),
(269, NULL, 'SemUsuario', '201.22.74.22', 'falha_login', '2025-10-24 11:00:45'),
(270, NULL, 'SemUsuario', '201.22.74.22', 'falha_login', '2025-10-24 11:00:47'),
(271, NULL, 'd', '189.85.155.90', 'acesso_negado_login', '2025-10-26 20:12:00'),
(272, 1, 'Diezare.Conde', '189.85.155.90', 'sucesso_login', '2025-10-26 20:13:24'),
(273, 1, 'Diezare.Conde', '189.85.155.90', 'saida_sistema', '2025-10-26 20:28:32'),
(274, 1, 'Diezare.Conde', '201.22.74.22', 'sucesso_login', '2025-10-27 07:36:24'),
(275, 1, 'Diezare.Conde', '201.22.74.22', 'sucesso_login', '2025-10-27 07:54:54'),
(276, 1, 'Diezare.Conde', '189.85.155.90', 'sucesso_login', '2025-10-28 22:13:24'),
(277, 1, 'Diezare.Conde', '189.85.155.90', 'saida_sistema', '2025-10-28 22:19:21'),
(278, NULL, 'ZAP', '201.22.74.22', 'acesso_negado_login', '2025-10-29 09:36:55'),
(279, 1, 'Diezare.Conde', '201.22.74.22', 'sucesso_login', '2025-10-29 10:59:06'),
(280, 1, 'Diezare.Conde', '201.22.74.22', 'saida_sistema', '2025-10-29 11:02:13'),
(281, 1, 'Diezare.Conde', '201.22.74.22', 'sucesso_login', '2025-10-29 11:02:24'),
(282, 1, 'Diezare.Conde', '201.22.74.22', 'saida_sistema', '2025-10-29 11:02:57'),
(283, 1, 'Diezare.Conde', '201.22.74.22', 'sucesso_login', '2025-10-29 11:04:13'),
(284, 1, 'Diezare.Conde', '201.22.74.22', 'saida_sistema', '2025-10-29 11:12:40'),
(285, 1, 'Diezare.Conde', '201.22.74.22', 'sucesso_login', '2025-10-29 11:18:39'),
(286, NULL, 'Diezare.COnde', '201.22.74.22', 'acesso_negado_login', '2025-10-29 11:40:39'),
(287, 1, 'Diezare.Conde', '201.22.74.22', 'sucesso_login', '2025-10-29 11:40:44'),
(288, NULL, 'Desconhecido', '201.22.74.22', 'saida_sistema', '2025-10-29 13:32:57'),
(289, NULL, 'Desconhecido', '201.22.74.22', 'saida_sistema', '2025-10-29 13:34:15'),
(290, 1, 'Diezare.Conde', '201.22.74.22', 'sucesso_login', '2025-10-29 15:05:20'),
(291, 1, 'Diezare.Conde', '201.22.74.22', 'saida_sistema', '2025-10-29 15:28:17'),
(292, NULL, 'ZAP', '201.22.74.22', 'acesso_negado_login', '2025-10-29 15:28:27'),
(293, NULL, 'SemUsuario', '201.22.74.22', 'falha_login', '2025-10-29 15:29:24'),
(294, NULL, 'SemUsuario', '189.85.155.90', 'falha_login', '2025-10-29 21:50:43'),
(295, NULL, 'V', '189.85.155.90', 'acesso_negado_login', '2025-10-29 21:50:51'),
(296, NULL, 'SemUsuario', '189.85.155.90', 'falha_login', '2025-10-29 21:51:11'),
(297, 1, 'Diezare.Conde', '189.85.155.90', 'sucesso_login', '2025-10-29 21:51:31'),
(298, NULL, 'SemUsuario', '189.85.155.90', 'falha_login', '2025-10-29 22:41:54'),
(299, 1, 'Diezare.Conde', '189.85.155.90', 'sucesso_login', '2025-10-29 22:42:06'),
(300, 1, 'Diezare.Conde', '189.85.155.90', 'sucesso_login', '2025-10-29 22:47:39'),
(301, 1, 'Diezare.Conde', '189.85.155.90', 'saida_sistema', '2025-10-29 23:31:05'),
(302, NULL, 'SemUsuario', '189.85.155.90', 'falha_login', '2025-10-29 23:31:18'),
(303, 1, 'Diezare.Conde', '189.85.155.90', 'saida_sistema', '2025-10-29 23:45:11'),
(304, NULL, 'SemUsuario', '189.85.155.90', 'falha_login', '2025-10-29 23:48:02'),
(305, 1, 'Diezare.Conde', '189.85.155.90', 'sucesso_login', '2025-10-29 23:48:26'),
(306, 1, 'Diezare.Conde', '189.85.155.90', 'sucesso_login', '2025-10-30 00:10:30'),
(307, 1, 'Diezare.Conde', '189.85.155.90', 'saida_sistema', '2025-10-30 00:14:03'),
(308, NULL, 'SemUsuario', '189.85.155.90', 'falha_login', '2025-10-30 00:14:16'),
(309, 1, 'Diezare.Conde', '189.85.155.90', 'saida_sistema', '2025-10-30 00:22:19'),
(310, 1, 'Diezare.Conde', '201.22.74.22', 'sucesso_login', '2025-10-30 07:52:44'),
(311, 1, 'Diezare.Conde', '201.22.74.22', 'saida_sistema', '2025-10-30 07:52:57'),
(312, 1, 'Diezare.Conde', '201.22.74.22', 'sucesso_login', '2025-10-30 07:55:28'),
(313, 1, 'Diezare.Conde', '201.22.74.22', 'saida_sistema', '2025-10-30 07:56:52'),
(314, NULL, 'ZAP', '201.22.74.22', 'acesso_negado_login', '2025-10-30 09:33:40'),
(315, NULL, 'SemUsuario', '201.22.74.22', 'falha_login', '2025-10-31 09:42:57'),
(316, NULL, 'SemUsuario', '189.85.155.90', 'falha_login', '2025-10-31 12:58:10'),
(317, 1, 'Diezare.Conde', '201.22.74.22', 'sucesso_login', '2025-10-31 14:13:39'),
(318, NULL, 'Desconhecido', '201.22.74.22', 'saida_sistema', '2025-10-31 15:40:41'),
(319, 1, 'Diezare.Conde', '189.39.109.100', 'sucesso_login', '2025-11-16 19:40:13'),
(320, 1, 'Diezare.Conde', '189.39.109.100', 'saida_sistema', '2025-11-16 19:40:49'),
(321, NULL, 'SemUsuario', '189.39.109.33', 'falha_login', '2025-11-20 17:52:06'),
(322, NULL, 'SemUsuario', '189.39.109.33', 'falha_login', '2025-11-20 17:52:09'),
(323, NULL, 'DIez', '189.39.109.33', 'acesso_negado_login', '2025-11-20 17:52:13'),
(324, 1, 'Diezare.Conde', '201.22.74.22', 'sucesso_login', '2025-12-16 08:08:09'),
(325, 1, 'Diezare.Conde', '201.22.74.22', 'sucesso_login', '2025-12-16 09:30:57'),
(326, 1, 'Diezare.Conde', '201.21.155.8', 'sucesso_login', '2025-12-16 10:29:22'),
(327, 1, 'Diezare.Conde', '201.21.155.8', 'saida_sistema', '2025-12-16 10:29:46'),
(328, 3, 'Bruno.Lacerda', '201.21.155.8', 'sucesso_login', '2025-12-16 10:30:40'),
(329, 1, 'Diezare.Conde', '201.22.74.22', 'sucesso_login', '2025-12-16 15:36:31'),
(330, 1, 'Diezare.Conde', '201.22.74.22', 'sucesso_login', '2025-12-16 17:27:04'),
(331, 1, 'Diezare.Conde', '189.39.108.108', 'sucesso_login', '2025-12-16 20:36:21'),
(332, 1, 'Diezare.Conde', '189.39.108.108', 'falha_login', '2025-12-16 22:18:25'),
(333, 1, 'Diezare.Conde', '189.39.108.108', 'sucesso_login', '2025-12-16 22:18:29'),
(334, 1, 'Diezare.Conde', '189.39.108.108', 'saida_sistema', '2025-12-16 22:55:10'),
(335, 1, 'Diezare.Conde', '201.22.74.22', 'sucesso_login', '2025-12-17 07:41:32'),
(336, 1, 'Diezare.Conde', '201.22.74.22', 'sucesso_login', '2025-12-17 08:03:14'),
(337, 1, 'Diezare.Conde', '201.22.74.22', 'saida_sistema', '2025-12-17 08:03:30'),
(338, 3, 'Bruno.Lacerda', '201.21.155.8', 'sucesso_login', '2025-12-17 09:43:00'),
(339, 1, 'Diezare.Conde', '201.22.74.22', 'sucesso_login', '2025-12-17 10:26:00'),
(340, 3, 'Bruno.Lacerda', '201.22.74.22', 'sucesso_login', '2025-12-17 13:33:27'),
(341, 1, 'Diezare.Conde', '201.22.74.22', 'sucesso_login', '2025-12-17 14:21:11'),
(342, 1, 'Diezare.Conde', '201.22.74.22', 'sucesso_login', '2025-12-17 22:26:48'),
(343, 3, 'Bruno.Lacerda', '201.22.74.22', 'sucesso_login', '2025-12-18 10:04:47'),
(344, 3, 'Bruno.Lacerda', '201.22.74.22', 'sucesso_login', '2025-12-18 12:04:48'),
(345, 1, 'Diezare.Conde', '201.22.74.22', 'sucesso_login', '2025-12-19 09:16:18'),
(346, 1, 'Diezare.Conde', '201.22.74.22', 'saida_sistema', '2025-12-19 09:47:28'),
(347, 1, 'Diezare.Conde', '189.39.108.108', 'sucesso_login', '2025-12-19 22:11:31'),
(348, 1, 'Diezare.Conde', '189.39.108.108', 'sucesso_login', '2025-12-19 22:18:03'),
(349, 1, 'Diezare.Conde', '189.39.108.108', 'sucesso_login', '2025-12-20 00:00:33'),
(350, 1, 'Diezare.Conde', '189.39.108.108', 'saida_sistema', '2025-12-20 00:00:48'),
(351, NULL, 'Desconhecido', '189.39.108.108', 'saida_sistema', '2025-12-20 00:02:07'),
(352, 1, 'Diezare.Conde', '::1', 'sucesso_login', '2025-12-20 12:00:44'),
(353, 1, 'Diezare.Conde', '::1', 'sucesso_login', '2025-12-20 18:54:59'),
(354, 1, 'Diezare.Conde', '::1', 'sucesso_login', '2025-12-21 09:01:38'),
(355, 1, 'Diezare.Conde', '::1', 'sucesso_login', '2025-12-21 20:09:16'),
(356, 1, 'Diezare.Conde', '::1', 'saida_sistema', '2025-12-21 23:02:48'),
(357, 1, 'Diezare.Conde', '::1', 'sucesso_login', '2025-12-25 20:27:59'),
(358, 1, 'Diezare.Conde', '::1', 'sucesso_login', '2025-12-27 09:11:58'),
(359, 1, 'Diezare.Conde', '::1', 'sucesso_login', '2025-12-28 21:36:40'),
(360, 1, 'Diezare.Conde', '::1', 'sucesso_login', '2025-12-30 10:32:21'),
(361, 1, 'Diezare.Conde', '::1', 'sucesso_login', '2025-12-31 08:52:15'),
(362, 1, 'Diezare.Conde', '::1', 'sucesso_login', '2026-01-02 16:51:26'),
(363, 1, 'Diezare.Conde', '::1', 'sucesso_login', '2026-01-03 09:46:41'),
(364, 1, 'Diezare.Conde', '::1', 'falha_login', '2026-01-03 21:53:54'),
(365, 1, 'Diezare.Conde', '::1', 'sucesso_login', '2026-01-03 21:54:14'),
(366, 1, 'Diezare.Conde', '::1', 'sucesso_login', '2026-01-04 21:18:15'),
(367, 1, 'Diezare.Conde', '::1', 'sucesso_login', '2026-01-05 09:37:36'),
(368, 1, 'Diezare.Conde', '::1', 'sucesso_login', '2026-01-05 21:11:48'),
(369, 1, 'Diezare.Conde', '::1', 'sucesso_login', '2026-01-10 17:01:05'),
(370, 1, 'Diezare.Conde', '::1', 'sucesso_login', '2026-01-11 18:09:05'),
(371, 1, 'Diezare.Conde', '::1', 'saida_sistema', '2026-01-11 22:53:26'),
(372, NULL, 'Desconhecido', '::1', 'saida_sistema', '2026-01-11 22:53:30'),
(373, 1, 'Diezare.Conde', '::1', 'sucesso_login', '2026-01-12 20:42:42');

-- --------------------------------------------------------

--
-- Estrutura para tabela `modalidade`
--

CREATE TABLE `modalidade` (
  `id_modalidade` int(11) NOT NULL,
  `nome_modalidade` varchar(100) NOT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  `data_cadastro` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `modalidade`
--

INSERT INTO `modalidade` (`id_modalidade`, `nome_modalidade`, `descricao`, `data_cadastro`) VALUES
(1, 'Futsal Masculino', NULL, '2025-04-01 10:40:32'),
(2, 'Futsal Feminino', NULL, '2025-05-08 19:34:05'),
(3, 'Vôlei Feminino', NULL, '2025-05-08 19:35:30'),
(4, 'Vôlei Masculino', NULL, '2025-05-08 19:35:47'),
(5, 'Basquete', NULL, '2025-05-08 19:35:58');

-- --------------------------------------------------------

--
-- Estrutura para tabela `nivel_ensino`
--

CREATE TABLE `nivel_ensino` (
  `id_nivel_ensino` int(11) NOT NULL,
  `nome_nivel_ensino` varchar(100) NOT NULL,
  `data_cadastro_nivel` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `nivel_ensino`
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
-- Estrutura para tabela `professor`
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
-- Despejando dados para a tabela `professor`
--

INSERT INTO `professor` (`id_professor`, `nome_completo`, `nome_exibicao`, `data_cadastro_professor`, `sexo`, `limite_aulas_fixa_semana`, `telefone`) VALUES
(2, 'Alessandra dos Santos', 'Alessandra', '2025-02-12 19:45:30', 'Feminino', 23, '(99) 99999-9999'),
(3, 'Gleyson Alves', 'Gleyson', '2025-02-15 16:01:18', 'Masculino', 18, '(99) 99999-9999'),
(4, 'Guilherme Homenhuki', 'Guilherme', '2025-02-15 16:01:43', 'Masculino', 8, '(99) 99999-9999'),
(5, 'Bruno César', 'Bruno', '2025-03-20 18:43:01', 'Masculino', 26, '(43) 9928-8428'),
(6, 'Gizely Koscianski', 'Gizely', '2025-03-20 18:43:48', 'Feminino', 19, '(99) 99999-9999'),
(7, 'José Fernando Perini', 'Perini', '2025-03-20 18:48:42', 'Masculino', 4, '(43) 99108-8273'),
(8, 'Daniele Leiroz', 'Daniele', '2025-05-08 19:37:19', 'Feminino', 8, '(99) 99999-9999'),
(9, 'Tamires / Wilton', 'Tamires / Wilton', '2025-05-08 19:37:54', 'Outro', 9, '(99) 9999-9999'),
(10, 'Beatriz Leão', 'Beatriz', '2025-05-08 19:38:24', 'Feminino', 20, '(99) 99999-9999'),
(11, 'Lindberg Mendonça', 'Berg', '2025-05-08 19:38:54', 'Masculino', 8, '(99) 9999-9999'),
(12, 'Alexandre Mendonça', 'Alexandre', '2025-05-08 19:39:26', 'Masculino', 12, '(99) 9999-9999'),
(13, 'Diogo Saturno', 'Diogo Saturno', '2025-12-16 10:35:31', 'Masculino', 4, '(99) 9999-9999'),
(14, 'Karolyne Cristyane Ishii', 'Karol', '2025-12-17 10:58:37', 'Feminino', 18, '(99) 99999-9999'),
(15, 'Maria Eduarda', 'Duda', '2025-12-17 11:00:41', 'Feminino', 9, '(99) 99999-9999'),
(16, 'Mônica Moraes', 'Mônica', '2025-12-17 11:10:58', 'Feminino', 14, '(99) 99999-9999'),
(17, 'Paulo Lorin', 'Paulo', '2025-12-17 11:15:03', 'Masculino', 6, '(99) 99999-9999'),
(18, 'Priscila Danieli da Silva', 'Priscila', '2025-12-17 11:17:16', 'Feminino', 4, '(99) 99999-9999'),
(19, 'Rafaela Pichinini', 'Rafaela', '2025-12-17 11:18:59', 'Feminino', 16, '(99) 99999-9999'),
(20, 'Raphael Zanardo Ally', 'Raphael', '2025-12-17 11:21:07', 'Masculino', 4, '(99) 99999-9999'),
(21, 'Solange Gomes', 'Solange', '2025-12-17 11:22:53', 'Feminino', 25, '(99) 99999-9999'),
(22, 'Tiago Nogueira', 'Tiago Nogueira', '2025-12-17 11:25:04', 'Masculino', 22, '(99) 99999-9999'),
(23, 'Willian Alvares', 'Willian', '2025-12-17 11:27:26', 'Masculino', 6, '(99) 99999-9999'),
(24, 'Guilherme Bomba', 'Bomba', '2025-12-17 11:38:24', 'Masculino', 16, '(99) 99999-9999'),
(25, 'Bruno Augusto', 'Bruno Augusto', '2025-12-17 11:42:39', 'Masculino', 8, '(99) 99999-9999'),
(26, 'Carlos Henrique Vici', 'Carlos', '2025-12-17 11:44:48', 'Masculino', 6, '(99) 99999-9999'),
(27, 'Euzéias', 'Euzéias', '2025-12-17 11:48:02', 'Masculino', 3, '(99) 99999-9999'),
(28, 'Pricila / Euzéias', 'Pricila / Euzéias', '2025-12-17 11:50:11', 'Outro', 2, '(99) 99999-9999'),
(29, 'Sidney Guerra', 'Guerra', '2025-12-17 11:51:53', 'Masculino', 4, '(99) 99999-9999'),
(30, 'Juba', 'Juba', '2025-12-17 11:55:01', 'Masculino', 8, '(99) 99999-9999'),
(31, 'Leandro Aranda', 'Leandro', '2025-12-17 11:57:42', 'Masculino', 3, '(99) 99999-9999'),
(32, 'Maykon Tognon', 'Maykon', '2025-12-17 12:00:23', 'Masculino', 10, '(99) 99999-9999'),
(33, 'Murilo Serviuc Mori', 'Murilo', '2025-12-17 12:04:10', 'Masculino', 4, '(99) 99999-9999'),
(34, 'Newton Morgado', 'Newton', '2025-12-17 13:36:15', 'Masculino', 6, '(99) 99999-9999'),
(35, 'Raggi Feguri Filho', 'Raggi', '2025-12-17 13:39:22', 'Masculino', 8, '(99) 99999-9999'),
(36, 'Reginaldo Cavalcante Agostini', 'Reginaldo', '2025-12-17 13:42:14', 'Masculino', 4, '(99) 99999-9999'),
(37, 'Ricardo Big', 'Ricardo Big', '2025-12-17 13:44:07', 'Masculino', 4, '(99) 99999-9999'),
(38, 'Rosana Gasparotti', 'Rosana Gasparotti', '2025-12-17 13:45:35', 'Feminino', 3, '(99) 99999-9999'),
(39, 'Rosana Silva', 'Rosana Silva', '2025-12-17 13:46:59', 'Feminino', 6, '(99) 99999-9999'),
(40, 'Rúbia Cherritte', 'Rúbia', '2025-12-17 13:48:25', 'Feminino', 8, '(99) 99999-9999'),
(41, 'Tiago Henrique', 'Tiago Henrique', '2025-12-17 13:50:31', 'Masculino', 4, '(99) 99999-9999'),
(42, 'Wellington Garcia', 'Ton', '2025-12-17 13:52:13', 'Masculino', 6, '(99) 99999-9999'),
(43, 'Vívian Tavares', 'Vívian', '2025-12-17 13:53:48', 'Feminino', 14, '(99) 99999-9999'),
(44, 'Wanderson Aceti', 'Wanderson', '2025-12-17 13:56:22', 'Masculino', 4, '(99) 99999-9999'),
(45, 'José Mário Gomes', 'Zé Mário', '2025-12-17 13:57:46', 'Masculino', 2, '(99) 99999-9999'),
(48, 'Wilton Batista dos Santos', 'Wilton', '2025-12-18 12:08:23', 'Masculino', 1, '(99) 99999-9999'),
(49, 'Tamires Gobato', 'Tamires', '2025-12-18 12:08:44', 'Feminino', 1, '(99) 99999-9999'),
(51, 'Sandra Lazarini', 'Sandra', '2025-12-18 12:09:24', 'Feminino', 1, '(99) 99999-9999'),
(56, 'Ana Clara Avanci', 'Clara', '2025-12-18 12:11:43', 'Feminino', 1, '(99) 99999-9999'),
(57, 'Wilton / Tamires', 'Wilton / Tamires', '2025-12-19 23:20:47', 'Outro', 10, '(99) 99999-9999'),
(58, 'Andressa Fonseca', 'Andressa', '2025-12-27 10:20:22', 'Feminino', 6, '(99) 99999-9999');

-- --------------------------------------------------------

--
-- Estrutura para tabela `professor_categoria`
--

CREATE TABLE `professor_categoria` (
  `id_professor` int(11) NOT NULL,
  `id_categoria` int(11) NOT NULL,
  `data_cadastro` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `professor_categoria`
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
-- Estrutura para tabela `professor_disciplinas`
--

CREATE TABLE `professor_disciplinas` (
  `id_professor` int(11) NOT NULL,
  `id_disciplina` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `professor_disciplinas`
--

INSERT INTO `professor_disciplinas` (`id_professor`, `id_disciplina`) VALUES
(2, 2),
(2, 18),
(3, 2),
(3, 26),
(4, 16),
(5, 12),
(5, 18),
(6, 5),
(6, 18),
(7, 2),
(8, 6),
(9, 9),
(10, 13),
(11, 13),
(12, 5),
(12, 18),
(13, 7),
(14, 2),
(14, 25),
(15, 18),
(15, 19),
(16, 7),
(16, 8),
(17, 13),
(18, 3),
(19, 17),
(20, 3),
(21, 16),
(21, 17),
(21, 18),
(22, 4),
(23, 12),
(24, 4),
(24, 11),
(24, 21),
(25, 16),
(25, 20),
(26, 13),
(27, 3),
(28, 22),
(29, 4),
(30, 10),
(30, 11),
(31, 5),
(32, 1),
(33, 7),
(34, 2),
(35, 2),
(35, 13),
(36, 5),
(37, 12),
(38, 23),
(39, 20),
(40, 12),
(41, 2),
(41, 13),
(42, 16),
(43, 7),
(43, 18),
(44, 12),
(45, 2),
(48, 18),
(49, 18),
(51, 18),
(56, 18),
(57, 9),
(58, 8);

-- --------------------------------------------------------

--
-- Estrutura para tabela `professor_disciplinas_turmas`
--

CREATE TABLE `professor_disciplinas_turmas` (
  `id_professor` int(11) NOT NULL,
  `id_disciplina` int(11) NOT NULL,
  `id_turma` int(11) NOT NULL,
  `aulas_semana` int(11) DEFAULT NULL,
  `prioridade` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `professor_disciplinas_turmas`
--

INSERT INTO `professor_disciplinas_turmas` (`id_professor`, `id_disciplina`, `id_turma`, `aulas_semana`, `prioridade`) VALUES
(2, 2, 14, NULL, NULL),
(2, 2, 15, NULL, NULL),
(2, 2, 16, NULL, NULL),
(2, 2, 17, NULL, NULL),
(2, 18, 17, NULL, NULL),
(3, 2, 20, NULL, NULL),
(3, 3, 1, NULL, NULL),
(3, 3, 2, NULL, NULL),
(3, 26, 10, NULL, NULL),
(3, 26, 11, NULL, NULL),
(3, 26, 12, NULL, NULL),
(3, 26, 13, NULL, NULL),
(4, 16, 10, NULL, NULL),
(4, 16, 11, NULL, NULL),
(4, 16, 12, NULL, NULL),
(4, 16, 13, NULL, NULL),
(5, 12, 8, NULL, NULL),
(5, 12, 9, NULL, NULL),
(5, 12, 18, NULL, NULL),
(5, 12, 19, NULL, NULL),
(5, 12, 22, NULL, NULL),
(5, 12, 23, NULL, NULL),
(5, 12, 24, NULL, NULL),
(5, 12, 25, NULL, NULL),
(6, 5, 10, NULL, NULL),
(6, 5, 13, NULL, NULL),
(6, 5, 14, NULL, NULL),
(6, 5, 15, NULL, NULL),
(6, 5, 16, NULL, NULL),
(6, 5, 17, NULL, NULL),
(6, 18, 13, NULL, NULL),
(7, 2, 22, NULL, NULL),
(7, 2, 24, NULL, NULL),
(8, 6, 10, NULL, NULL),
(8, 6, 11, NULL, NULL),
(8, 6, 12, NULL, NULL),
(8, 6, 13, NULL, NULL),
(8, 6, 14, NULL, NULL),
(8, 6, 15, NULL, NULL),
(8, 6, 16, NULL, NULL),
(8, 6, 17, NULL, NULL),
(9, 9, 10, NULL, NULL),
(9, 9, 12, NULL, NULL),
(9, 9, 15, NULL, NULL),
(9, 9, 16, NULL, NULL),
(9, 9, 18, NULL, NULL),
(10, 3, 14, NULL, NULL),
(10, 3, 15, NULL, NULL),
(10, 3, 16, NULL, NULL),
(10, 3, 17, NULL, NULL),
(11, 13, 8, NULL, NULL),
(11, 13, 18, NULL, NULL),
(11, 13, 22, NULL, NULL),
(11, 13, 24, NULL, NULL),
(12, 5, 8, NULL, NULL),
(12, 5, 9, NULL, NULL),
(12, 5, 11, NULL, NULL),
(12, 5, 12, NULL, NULL),
(12, 5, 18, NULL, NULL),
(12, 5, 19, NULL, NULL),
(12, 18, 11, NULL, NULL),
(13, 7, 22, NULL, NULL),
(13, 7, 24, NULL, NULL),
(14, 2, 8, NULL, NULL),
(14, 2, 18, NULL, NULL),
(14, 25, 10, NULL, NULL),
(14, 25, 11, NULL, NULL),
(14, 25, 12, NULL, NULL),
(14, 25, 13, NULL, NULL),
(15, 18, 12, NULL, NULL),
(15, 19, 10, NULL, NULL),
(15, 19, 11, NULL, NULL),
(15, 19, 12, NULL, NULL),
(15, 19, 13, NULL, NULL),
(15, 19, 14, NULL, NULL),
(15, 19, 15, NULL, NULL),
(15, 19, 16, NULL, NULL),
(15, 19, 17, NULL, NULL),
(16, 7, 11, NULL, NULL),
(16, 7, 12, NULL, NULL),
(16, 8, 10, NULL, NULL),
(16, 8, 13, NULL, NULL),
(16, 8, 14, NULL, NULL),
(16, 8, 15, NULL, NULL),
(17, 13, 11, NULL, NULL),
(17, 13, 12, NULL, NULL),
(17, 13, 20, NULL, NULL),
(18, 3, 10, NULL, NULL),
(18, 3, 13, NULL, NULL),
(19, 17, 14, NULL, NULL),
(19, 17, 15, NULL, NULL),
(19, 17, 16, NULL, NULL),
(19, 17, 17, NULL, NULL),
(20, 3, 11, NULL, NULL),
(20, 3, 12, NULL, NULL),
(21, 16, 14, NULL, NULL),
(21, 16, 15, NULL, NULL),
(21, 16, 16, NULL, NULL),
(21, 16, 17, NULL, NULL),
(21, 17, 10, NULL, NULL),
(21, 17, 11, NULL, NULL),
(21, 17, 12, NULL, NULL),
(21, 17, 13, NULL, NULL),
(21, 18, 16, NULL, NULL),
(22, 4, 10, NULL, NULL),
(22, 4, 11, NULL, NULL),
(22, 4, 12, NULL, NULL),
(22, 4, 13, NULL, NULL),
(22, 4, 14, NULL, NULL),
(22, 4, 15, NULL, NULL),
(22, 4, 16, NULL, NULL),
(22, 4, 17, NULL, NULL),
(23, 12, 10, NULL, NULL),
(23, 12, 11, NULL, NULL),
(23, 12, 12, NULL, NULL),
(23, 12, 13, NULL, NULL),
(24, 4, 8, NULL, NULL),
(24, 4, 9, NULL, NULL),
(24, 4, 18, NULL, NULL),
(24, 4, 19, NULL, NULL),
(24, 4, 20, NULL, NULL),
(24, 4, 21, NULL, NULL),
(24, 11, 22, NULL, NULL),
(24, 11, 23, NULL, NULL),
(24, 11, 24, NULL, NULL),
(24, 11, 25, NULL, NULL),
(24, 21, 8, NULL, NULL),
(24, 21, 9, NULL, NULL),
(24, 21, 18, NULL, NULL),
(24, 21, 19, NULL, NULL),
(24, 21, 20, NULL, NULL),
(24, 21, 21, NULL, NULL),
(24, 21, 22, NULL, NULL),
(24, 21, 23, NULL, NULL),
(24, 21, 24, NULL, NULL),
(24, 21, 25, NULL, NULL),
(25, 16, 22, NULL, NULL),
(25, 16, 24, NULL, NULL),
(25, 20, 22, NULL, NULL),
(25, 20, 24, NULL, NULL),
(26, 13, 8, NULL, NULL),
(26, 13, 18, NULL, NULL),
(26, 13, 22, NULL, NULL),
(26, 13, 24, NULL, NULL),
(27, 3, 8, NULL, NULL),
(27, 3, 18, NULL, NULL),
(27, 3, 20, NULL, NULL),
(28, 22, 22, NULL, NULL),
(28, 22, 24, NULL, NULL),
(29, 4, 22, NULL, NULL),
(29, 4, 24, NULL, NULL),
(30, 10, 8, NULL, NULL),
(30, 10, 18, NULL, NULL),
(30, 10, 20, NULL, NULL),
(30, 10, 22, NULL, NULL),
(30, 10, 24, NULL, NULL),
(30, 11, 8, NULL, NULL),
(30, 11, 18, NULL, NULL),
(30, 11, 20, NULL, NULL),
(31, 5, 20, NULL, NULL),
(31, 5, 21, NULL, NULL),
(32, 1, 8, NULL, NULL),
(32, 1, 9, NULL, NULL),
(32, 1, 18, NULL, NULL),
(32, 1, 19, NULL, NULL),
(32, 1, 20, NULL, NULL),
(32, 1, 21, NULL, NULL),
(32, 1, 22, NULL, NULL),
(32, 1, 23, NULL, NULL),
(32, 1, 24, NULL, NULL),
(32, 1, 25, NULL, NULL),
(33, 7, 23, NULL, NULL),
(33, 7, 25, NULL, NULL),
(34, 2, 8, NULL, NULL),
(34, 2, 18, NULL, NULL),
(34, 2, 24, NULL, NULL),
(35, 2, 22, NULL, NULL),
(35, 2, 24, NULL, NULL),
(35, 13, 22, NULL, NULL),
(35, 13, 24, NULL, NULL),
(36, 5, 22, NULL, NULL),
(36, 5, 24, NULL, NULL),
(37, 12, 22, NULL, NULL),
(37, 12, 24, NULL, NULL),
(38, 23, 8, NULL, NULL),
(38, 23, 18, NULL, NULL),
(38, 23, 20, NULL, NULL),
(39, 20, 8, NULL, NULL),
(39, 20, 18, NULL, NULL),
(39, 20, 20, NULL, NULL),
(40, 12, 8, NULL, NULL),
(40, 12, 18, NULL, NULL),
(40, 12, 20, NULL, NULL),
(41, 2, 20, NULL, NULL),
(41, 13, 20, NULL, NULL),
(42, 16, 8, NULL, NULL),
(42, 16, 18, NULL, NULL),
(42, 16, 20, NULL, NULL),
(43, 7, 8, NULL, NULL),
(43, 7, 18, NULL, NULL),
(43, 7, 20, NULL, NULL),
(43, 7, 22, NULL, NULL),
(43, 7, 24, NULL, NULL),
(43, 18, 18, NULL, NULL),
(44, 12, 23, NULL, NULL),
(44, 12, 25, NULL, NULL),
(45, 2, 22, NULL, NULL),
(48, 18, 14, NULL, NULL),
(49, 18, 15, NULL, NULL),
(51, 18, 10, NULL, NULL),
(56, 18, 20, NULL, NULL),
(57, 9, 8, NULL, NULL),
(57, 9, 11, NULL, NULL),
(57, 9, 13, NULL, NULL),
(57, 9, 14, NULL, NULL),
(57, 9, 17, NULL, NULL),
(57, 9, 20, NULL, NULL),
(58, 8, 16, NULL, NULL),
(58, 8, 17, NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `professor_restricoes`
--

CREATE TABLE `professor_restricoes` (
  `id_professor` int(11) NOT NULL,
  `id_ano_letivo` int(11) NOT NULL,
  `dia_semana` enum('Domingo','Segunda','Terca','Quarta','Quinta','Sexta','Sabado') NOT NULL,
  `numero_aula` int(11) NOT NULL,
  `id_turno` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `professor_restricoes`
--

INSERT INTO `professor_restricoes` (`id_professor`, `id_ano_letivo`, `dia_semana`, `numero_aula`, `id_turno`) VALUES
(2, 7, 'Segunda', 1, 1),
(2, 7, 'Segunda', 2, 1),
(2, 7, 'Segunda', 3, 1),
(2, 7, 'Segunda', 4, 1),
(2, 7, 'Segunda', 5, 1),
(2, 7, 'Segunda', 6, 1),
(3, 7, 'Segunda', 1, 1),
(3, 7, 'Segunda', 2, 1),
(3, 7, 'Segunda', 3, 1),
(3, 7, 'Segunda', 4, 1),
(3, 7, 'Quinta', 1, 1),
(3, 7, 'Quinta', 2, 1),
(3, 7, 'Quinta', 3, 1),
(3, 7, 'Quinta', 4, 1),
(3, 7, 'Quinta', 5, 1),
(3, 7, 'Quinta', 6, 1),
(3, 7, 'Sexta', 1, 1),
(3, 7, 'Sexta', 2, 1),
(3, 7, 'Sexta', 3, 1),
(3, 7, 'Sexta', 4, 1),
(3, 7, 'Sexta', 5, 1),
(3, 7, 'Sexta', 6, 1),
(4, 7, 'Terca', 1, 1),
(4, 7, 'Terca', 2, 1),
(4, 7, 'Terca', 3, 1),
(4, 7, 'Terca', 4, 1),
(4, 7, 'Terca', 5, 1),
(4, 7, 'Terca', 6, 1),
(4, 7, 'Quarta', 1, 1),
(4, 7, 'Quarta', 2, 1),
(4, 7, 'Quarta', 3, 1),
(4, 7, 'Quarta', 4, 1),
(4, 7, 'Quinta', 1, 1),
(4, 7, 'Quinta', 2, 1),
(4, 7, 'Quinta', 3, 1),
(4, 7, 'Quinta', 4, 1),
(4, 7, 'Quinta', 5, 1),
(4, 7, 'Quinta', 6, 1),
(4, 7, 'Sexta', 1, 1),
(4, 7, 'Sexta', 2, 1),
(4, 7, 'Sexta', 3, 1),
(4, 7, 'Sexta', 4, 1),
(4, 7, 'Sexta', 5, 1),
(4, 7, 'Sexta', 6, 1),
(5, 7, 'Domingo', 1, 2),
(5, 7, 'Domingo', 2, 2),
(5, 7, 'Domingo', 3, 2),
(5, 7, 'Domingo', 4, 2),
(5, 7, 'Domingo', 5, 2),
(5, 7, 'Domingo', 6, 2),
(5, 7, 'Segunda', 1, 2),
(5, 7, 'Segunda', 2, 2),
(5, 7, 'Segunda', 3, 2),
(5, 7, 'Segunda', 4, 2),
(5, 7, 'Segunda', 5, 2),
(5, 7, 'Segunda', 6, 2),
(5, 7, 'Terca', 1, 2),
(5, 7, 'Terca', 2, 2),
(5, 7, 'Terca', 3, 2),
(5, 7, 'Terca', 4, 2),
(5, 7, 'Terca', 5, 2),
(5, 7, 'Terca', 6, 2),
(5, 7, 'Quarta', 1, 2),
(5, 7, 'Quarta', 2, 2),
(5, 7, 'Quarta', 3, 2),
(5, 7, 'Quarta', 4, 2),
(5, 7, 'Quarta', 5, 2),
(5, 7, 'Quarta', 6, 2),
(5, 7, 'Quinta', 1, 2),
(5, 7, 'Quinta', 2, 2),
(5, 7, 'Quinta', 3, 2),
(5, 7, 'Quinta', 4, 2),
(5, 7, 'Quinta', 5, 2),
(5, 7, 'Quinta', 6, 2),
(5, 7, 'Sexta', 6, 2),
(5, 7, 'Sabado', 1, 2),
(5, 7, 'Sabado', 2, 2),
(5, 7, 'Sabado', 3, 2),
(5, 7, 'Sabado', 4, 2),
(5, 7, 'Sabado', 5, 2),
(5, 7, 'Sabado', 6, 2),
(6, 7, 'Segunda', 1, 1),
(6, 7, 'Segunda', 2, 1),
(6, 7, 'Terca', 1, 1),
(6, 7, 'Terca', 2, 1),
(6, 7, 'Quarta', 1, 1),
(6, 7, 'Sexta', 1, 1),
(6, 7, 'Sexta', 2, 1),
(6, 7, 'Sexta', 3, 1),
(6, 7, 'Sexta', 4, 1),
(6, 7, 'Sexta', 5, 1),
(6, 7, 'Sexta', 6, 1),
(7, 7, 'Segunda', 1, 1),
(7, 7, 'Segunda', 2, 1),
(7, 7, 'Segunda', 3, 1),
(7, 7, 'Segunda', 4, 1),
(7, 7, 'Terca', 1, 1),
(7, 7, 'Terca', 2, 1),
(7, 7, 'Terca', 3, 1),
(7, 7, 'Terca', 4, 1),
(7, 7, 'Terca', 5, 1),
(7, 7, 'Terca', 6, 1),
(7, 7, 'Quarta', 1, 1),
(7, 7, 'Quarta', 2, 1),
(7, 7, 'Quarta', 3, 1),
(7, 7, 'Quarta', 4, 1),
(7, 7, 'Quinta', 1, 1),
(7, 7, 'Quinta', 2, 1),
(7, 7, 'Quinta', 3, 1),
(7, 7, 'Quinta', 4, 1),
(7, 7, 'Quinta', 5, 1),
(7, 7, 'Quinta', 6, 1),
(7, 7, 'Sexta', 1, 1),
(7, 7, 'Sexta', 2, 1),
(7, 7, 'Sexta', 3, 1),
(7, 7, 'Sexta', 4, 1),
(7, 7, 'Sexta', 5, 1),
(7, 7, 'Sexta', 6, 1),
(8, 7, 'Segunda', 1, 1),
(8, 7, 'Segunda', 2, 1),
(8, 7, 'Segunda', 3, 1),
(8, 7, 'Segunda', 4, 1),
(8, 7, 'Segunda', 5, 1),
(8, 7, 'Segunda', 6, 1),
(8, 7, 'Terca', 3, 1),
(8, 7, 'Terca', 4, 1),
(8, 7, 'Terca', 5, 1),
(8, 7, 'Terca', 6, 1),
(8, 7, 'Quarta', 1, 1),
(8, 7, 'Quarta', 2, 1),
(8, 7, 'Quarta', 3, 1),
(8, 7, 'Quarta', 4, 1),
(8, 7, 'Quarta', 5, 1),
(8, 7, 'Quarta', 6, 1),
(8, 7, 'Quinta', 1, 1),
(8, 7, 'Quinta', 2, 1),
(8, 7, 'Quinta', 3, 1),
(8, 7, 'Quinta', 4, 1),
(8, 7, 'Quinta', 5, 1),
(8, 7, 'Quinta', 6, 1),
(9, 7, 'Segunda', 1, 1),
(9, 7, 'Segunda', 2, 1),
(9, 7, 'Segunda', 3, 1),
(9, 7, 'Segunda', 4, 1),
(9, 7, 'Segunda', 5, 1),
(9, 7, 'Segunda', 6, 1),
(9, 7, 'Quarta', 1, 1),
(9, 7, 'Quarta', 2, 1),
(9, 7, 'Quarta', 3, 1),
(9, 7, 'Quarta', 4, 1),
(9, 7, 'Quarta', 5, 1),
(9, 7, 'Quarta', 6, 1),
(9, 7, 'Quinta', 1, 1),
(9, 7, 'Quinta', 6, 1),
(9, 7, 'Sexta', 1, 1),
(9, 7, 'Sexta', 2, 1),
(9, 7, 'Sexta', 3, 1),
(9, 7, 'Sexta', 4, 1),
(9, 7, 'Sexta', 5, 1),
(9, 7, 'Sexta', 6, 1),
(10, 7, 'Segunda', 5, 1),
(10, 7, 'Segunda', 6, 1),
(10, 7, 'Terca', 5, 1),
(10, 7, 'Terca', 6, 1),
(10, 7, 'Quarta', 5, 1),
(10, 7, 'Quarta', 6, 1),
(10, 7, 'Quinta', 1, 1),
(10, 7, 'Quinta', 2, 1),
(10, 7, 'Sexta', 5, 1),
(10, 7, 'Sexta', 6, 1),
(11, 7, 'Segunda', 1, 1),
(11, 7, 'Segunda', 2, 1),
(11, 7, 'Segunda', 3, 1),
(11, 7, 'Segunda', 4, 1),
(11, 7, 'Segunda', 5, 1),
(11, 7, 'Segunda', 6, 1),
(11, 7, 'Terca', 1, 1),
(11, 7, 'Terca', 2, 1),
(11, 7, 'Terca', 3, 1),
(11, 7, 'Terca', 4, 1),
(11, 7, 'Terca', 5, 1),
(11, 7, 'Terca', 6, 1),
(11, 7, 'Quarta', 1, 1),
(11, 7, 'Quarta', 2, 1),
(11, 7, 'Quarta', 3, 1),
(11, 7, 'Quarta', 4, 1),
(11, 7, 'Quarta', 5, 1),
(11, 7, 'Quarta', 6, 1),
(11, 7, 'Quinta', 1, 1),
(11, 7, 'Quinta', 2, 1),
(11, 7, 'Quinta', 3, 1),
(11, 7, 'Quinta', 4, 1),
(11, 7, 'Quinta', 5, 1),
(11, 7, 'Quinta', 6, 1),
(11, 7, 'Sexta', 5, 1),
(11, 7, 'Sexta', 6, 1),
(11, 7, 'Domingo', 1, 2),
(11, 7, 'Domingo', 2, 2),
(11, 7, 'Domingo', 3, 2),
(11, 7, 'Domingo', 4, 2),
(11, 7, 'Domingo', 5, 2),
(11, 7, 'Domingo', 6, 2),
(11, 7, 'Segunda', 1, 2),
(11, 7, 'Segunda', 2, 2),
(11, 7, 'Segunda', 3, 2),
(11, 7, 'Segunda', 4, 2),
(11, 7, 'Segunda', 5, 2),
(11, 7, 'Segunda', 6, 2),
(11, 7, 'Terca', 1, 2),
(11, 7, 'Terca', 2, 2),
(11, 7, 'Terca', 3, 2),
(11, 7, 'Terca', 4, 2),
(11, 7, 'Terca', 5, 2),
(11, 7, 'Terca', 6, 2),
(11, 7, 'Quarta', 1, 2),
(11, 7, 'Quarta', 2, 2),
(11, 7, 'Quarta', 3, 2),
(11, 7, 'Quarta', 4, 2),
(11, 7, 'Quarta', 5, 2),
(11, 7, 'Quarta', 6, 2),
(11, 7, 'Quinta', 1, 2),
(11, 7, 'Quinta', 2, 2),
(11, 7, 'Quinta', 3, 2),
(11, 7, 'Quinta', 4, 2),
(11, 7, 'Quinta', 5, 2),
(11, 7, 'Quinta', 6, 2),
(11, 7, 'Sexta', 6, 2),
(11, 7, 'Sabado', 1, 2),
(11, 7, 'Sabado', 2, 2),
(11, 7, 'Sabado', 3, 2),
(11, 7, 'Sabado', 4, 2),
(11, 7, 'Sabado', 5, 2),
(11, 7, 'Sabado', 6, 2),
(12, 7, 'Segunda', 1, 1),
(12, 7, 'Segunda', 2, 1),
(12, 7, 'Segunda', 3, 1),
(12, 7, 'Segunda', 4, 1),
(12, 7, 'Segunda', 5, 1),
(12, 7, 'Segunda', 6, 1),
(12, 7, 'Terca', 1, 1),
(12, 7, 'Terca', 2, 1),
(12, 7, 'Terca', 3, 1),
(12, 7, 'Terca', 4, 1),
(12, 7, 'Terca', 5, 1),
(12, 7, 'Terca', 6, 1),
(12, 7, 'Sexta', 1, 1),
(12, 7, 'Sexta', 2, 1),
(12, 7, 'Sexta', 3, 1),
(12, 7, 'Sexta', 4, 1),
(12, 7, 'Sexta', 5, 1),
(12, 7, 'Sexta', 6, 1),
(12, 7, 'Domingo', 1, 2),
(12, 7, 'Domingo', 2, 2),
(12, 7, 'Domingo', 3, 2),
(12, 7, 'Domingo', 4, 2),
(12, 7, 'Domingo', 5, 2),
(12, 7, 'Domingo', 6, 2),
(12, 7, 'Segunda', 1, 2),
(12, 7, 'Segunda', 2, 2),
(12, 7, 'Segunda', 3, 2),
(12, 7, 'Segunda', 4, 2),
(12, 7, 'Segunda', 5, 2),
(12, 7, 'Segunda', 6, 2),
(12, 7, 'Terca', 1, 2),
(12, 7, 'Terca', 2, 2),
(12, 7, 'Terca', 3, 2),
(12, 7, 'Terca', 4, 2),
(12, 7, 'Terca', 5, 2),
(12, 7, 'Terca', 6, 2),
(12, 7, 'Quarta', 1, 2),
(12, 7, 'Quarta', 2, 2),
(12, 7, 'Quarta', 3, 2),
(12, 7, 'Quarta', 4, 2),
(12, 7, 'Quarta', 5, 2),
(12, 7, 'Quarta', 6, 2),
(12, 7, 'Quinta', 1, 2),
(12, 7, 'Quinta', 2, 2),
(12, 7, 'Quinta', 3, 2),
(12, 7, 'Quinta', 4, 2),
(12, 7, 'Quinta', 5, 2),
(12, 7, 'Quinta', 6, 2),
(12, 7, 'Sexta', 4, 2),
(12, 7, 'Sexta', 5, 2),
(12, 7, 'Sexta', 6, 2),
(12, 7, 'Sabado', 1, 2),
(12, 7, 'Sabado', 2, 2),
(12, 7, 'Sabado', 3, 2),
(12, 7, 'Sabado', 4, 2),
(12, 7, 'Sabado', 5, 2),
(12, 7, 'Sabado', 6, 2),
(13, 7, 'Segunda', 5, 1),
(13, 7, 'Segunda', 6, 1),
(13, 7, 'Terca', 1, 1),
(13, 7, 'Terca', 2, 1),
(13, 7, 'Terca', 3, 1),
(13, 7, 'Terca', 4, 1),
(13, 7, 'Terca', 5, 1),
(13, 7, 'Terca', 6, 1),
(13, 7, 'Quarta', 1, 1),
(13, 7, 'Quarta', 2, 1),
(13, 7, 'Quarta', 3, 1),
(13, 7, 'Quarta', 4, 1),
(13, 7, 'Quarta', 5, 1),
(13, 7, 'Quarta', 6, 1),
(13, 7, 'Quinta', 1, 1),
(13, 7, 'Quinta', 2, 1),
(13, 7, 'Quinta', 3, 1),
(13, 7, 'Quinta', 4, 1),
(13, 7, 'Quinta', 5, 1),
(13, 7, 'Quinta', 6, 1),
(13, 7, 'Sexta', 1, 1),
(13, 7, 'Sexta', 2, 1),
(13, 7, 'Sexta', 3, 1),
(13, 7, 'Sexta', 4, 1),
(13, 7, 'Sexta', 5, 1),
(13, 7, 'Sexta', 6, 1),
(14, 7, 'Quinta', 1, 1),
(14, 7, 'Quinta', 2, 1),
(14, 7, 'Quinta', 3, 1),
(14, 7, 'Quinta', 4, 1),
(14, 7, 'Quinta', 5, 1),
(14, 7, 'Quinta', 6, 1),
(14, 7, 'Sexta', 1, 1),
(14, 7, 'Sexta', 2, 1),
(14, 7, 'Sexta', 3, 1),
(14, 7, 'Sexta', 4, 1),
(14, 7, 'Sexta', 5, 1),
(14, 7, 'Sexta', 6, 1),
(15, 7, 'Segunda', 1, 1),
(15, 7, 'Segunda', 2, 1),
(15, 7, 'Segunda', 3, 1),
(15, 7, 'Segunda', 4, 1),
(15, 7, 'Segunda', 5, 1),
(15, 7, 'Segunda', 6, 1),
(15, 7, 'Terca', 1, 1),
(15, 7, 'Terca', 2, 1),
(15, 7, 'Terca', 3, 1),
(15, 7, 'Terca', 4, 1),
(15, 7, 'Terca', 5, 1),
(15, 7, 'Terca', 6, 1),
(15, 7, 'Quarta', 1, 1),
(15, 7, 'Quarta', 2, 1),
(15, 7, 'Quarta', 3, 1),
(15, 7, 'Quarta', 4, 1),
(15, 7, 'Quarta', 5, 1),
(15, 7, 'Quarta', 6, 1),
(16, 7, 'Terca', 1, 1),
(16, 7, 'Terca', 2, 1),
(16, 7, 'Terca', 3, 1),
(16, 7, 'Terca', 4, 1),
(16, 7, 'Terca', 5, 1),
(16, 7, 'Terca', 6, 1),
(16, 7, 'Quarta', 1, 1),
(16, 7, 'Quinta', 1, 1),
(16, 7, 'Quinta', 2, 1),
(16, 7, 'Quinta', 3, 1),
(16, 7, 'Quinta', 4, 1),
(16, 7, 'Quinta', 5, 1),
(16, 7, 'Quinta', 6, 1),
(17, 7, 'Terca', 1, 1),
(17, 7, 'Terca', 2, 1),
(17, 7, 'Terca', 3, 1),
(17, 7, 'Terca', 4, 1),
(17, 7, 'Terca', 5, 1),
(17, 7, 'Terca', 6, 1),
(17, 7, 'Quarta', 1, 1),
(17, 7, 'Quarta', 2, 1),
(17, 7, 'Quarta', 3, 1),
(17, 7, 'Quarta', 4, 1),
(17, 7, 'Quarta', 5, 1),
(17, 7, 'Quarta', 6, 1),
(17, 7, 'Quinta', 1, 1),
(17, 7, 'Quinta', 2, 1),
(17, 7, 'Quinta', 3, 1),
(17, 7, 'Quinta', 4, 1),
(17, 7, 'Quinta', 5, 1),
(17, 7, 'Quinta', 6, 1),
(17, 7, 'Sexta', 1, 1),
(17, 7, 'Sexta', 2, 1),
(17, 7, 'Sexta', 3, 1),
(17, 7, 'Sexta', 4, 1),
(17, 7, 'Sexta', 5, 1),
(17, 7, 'Sexta', 6, 1),
(18, 7, 'Segunda', 1, 1),
(18, 7, 'Segunda', 2, 1),
(18, 7, 'Segunda', 3, 1),
(18, 7, 'Segunda', 4, 1),
(18, 7, 'Segunda', 5, 1),
(18, 7, 'Segunda', 6, 1),
(18, 7, 'Terca', 1, 1),
(18, 7, 'Terca', 2, 1),
(18, 7, 'Terca', 3, 1),
(18, 7, 'Terca', 4, 1),
(18, 7, 'Terca', 5, 1),
(18, 7, 'Terca', 6, 1),
(18, 7, 'Quarta', 1, 1),
(18, 7, 'Quarta', 2, 1),
(18, 7, 'Quarta', 3, 1),
(18, 7, 'Quarta', 4, 1),
(18, 7, 'Quarta', 5, 1),
(18, 7, 'Quarta', 6, 1),
(18, 7, 'Quinta', 1, 1),
(18, 7, 'Quinta', 2, 1),
(18, 7, 'Quinta', 6, 1),
(18, 7, 'Sexta', 1, 1),
(18, 7, 'Sexta', 2, 1),
(18, 7, 'Sexta', 6, 1),
(19, 7, 'Quinta', 1, 1),
(19, 7, 'Quinta', 2, 1),
(19, 7, 'Quinta', 3, 1),
(19, 7, 'Quinta', 4, 1),
(19, 7, 'Quinta', 5, 1),
(19, 7, 'Quinta', 6, 1),
(19, 7, 'Sexta', 1, 1),
(19, 7, 'Sexta', 2, 1),
(19, 7, 'Sexta', 3, 1),
(19, 7, 'Sexta', 4, 1),
(19, 7, 'Sexta', 5, 1),
(19, 7, 'Sexta', 6, 1),
(20, 7, 'Segunda', 1, 1),
(20, 7, 'Segunda', 2, 1),
(20, 7, 'Segunda', 3, 1),
(20, 7, 'Segunda', 4, 1),
(20, 7, 'Segunda', 5, 1),
(20, 7, 'Segunda', 6, 1),
(20, 7, 'Terca', 1, 1),
(20, 7, 'Terca', 2, 1),
(20, 7, 'Terca', 3, 1),
(20, 7, 'Terca', 4, 1),
(20, 7, 'Terca', 5, 1),
(20, 7, 'Terca', 6, 1),
(20, 7, 'Quarta', 1, 1),
(20, 7, 'Quarta', 2, 1),
(20, 7, 'Quarta', 3, 1),
(20, 7, 'Quarta', 4, 1),
(20, 7, 'Quarta', 5, 1),
(20, 7, 'Quarta', 6, 1),
(20, 7, 'Quinta', 1, 1),
(20, 7, 'Sexta', 1, 1),
(20, 7, 'Sexta', 2, 1),
(20, 7, 'Sexta', 3, 1),
(20, 7, 'Sexta', 4, 1),
(20, 7, 'Sexta', 5, 1),
(20, 7, 'Sexta', 6, 1),
(22, 7, 'Terca', 1, 1),
(22, 7, 'Terca', 2, 1),
(22, 7, 'Terca', 3, 1),
(22, 7, 'Terca', 4, 1),
(22, 7, 'Terca', 5, 1),
(22, 7, 'Terca', 6, 1),
(22, 7, 'Quinta', 1, 1),
(22, 7, 'Quinta', 2, 1),
(23, 7, 'Segunda', 1, 1),
(23, 7, 'Segunda', 2, 1),
(23, 7, 'Segunda', 3, 1),
(23, 7, 'Terca', 1, 1),
(23, 7, 'Terca', 2, 1),
(23, 7, 'Terca', 3, 1),
(23, 7, 'Terca', 4, 1),
(23, 7, 'Terca', 5, 1),
(23, 7, 'Terca', 6, 1),
(23, 7, 'Quarta', 1, 1),
(23, 7, 'Quarta', 2, 1),
(23, 7, 'Quarta', 3, 1),
(23, 7, 'Quarta', 4, 1),
(23, 7, 'Quarta', 5, 1),
(23, 7, 'Quarta', 6, 1),
(23, 7, 'Quinta', 1, 1),
(23, 7, 'Quinta', 2, 1),
(23, 7, 'Quinta', 3, 1),
(23, 7, 'Quinta', 4, 1),
(23, 7, 'Quinta', 5, 1),
(23, 7, 'Quinta', 6, 1),
(23, 7, 'Sexta', 1, 1),
(23, 7, 'Sexta', 2, 1),
(23, 7, 'Sexta', 3, 1),
(24, 7, 'Segunda', 1, 1),
(24, 7, 'Segunda', 2, 1),
(24, 7, 'Segunda', 3, 1),
(24, 7, 'Segunda', 4, 1),
(24, 7, 'Segunda', 5, 1),
(24, 7, 'Segunda', 6, 1),
(24, 7, 'Quarta', 1, 1),
(24, 7, 'Quarta', 2, 1),
(24, 7, 'Quarta', 3, 1),
(24, 7, 'Quarta', 4, 1),
(24, 7, 'Quarta', 5, 1),
(24, 7, 'Quarta', 6, 1),
(24, 7, 'Quinta', 1, 1),
(24, 7, 'Quinta', 2, 1),
(24, 7, 'Quinta', 3, 1),
(24, 7, 'Quinta', 4, 1),
(24, 7, 'Quinta', 5, 1),
(24, 7, 'Quinta', 6, 1),
(25, 7, 'Segunda', 1, 1),
(25, 7, 'Segunda', 2, 1),
(25, 7, 'Segunda', 3, 1),
(25, 7, 'Segunda', 4, 1),
(25, 7, 'Segunda', 5, 1),
(25, 7, 'Segunda', 6, 1),
(25, 7, 'Terca', 1, 1),
(25, 7, 'Terca', 2, 1),
(25, 7, 'Quarta', 1, 1),
(25, 7, 'Quarta', 2, 1),
(25, 7, 'Quarta', 3, 1),
(25, 7, 'Quarta', 4, 1),
(25, 7, 'Quarta', 5, 1),
(25, 7, 'Quarta', 6, 1),
(25, 7, 'Quinta', 1, 1),
(25, 7, 'Quinta', 2, 1),
(25, 7, 'Sexta', 1, 1),
(25, 7, 'Sexta', 2, 1),
(25, 7, 'Sexta', 3, 1),
(25, 7, 'Sexta', 4, 1),
(25, 7, 'Sexta', 5, 1),
(25, 7, 'Sexta', 6, 1),
(26, 7, 'Segunda', 1, 1),
(26, 7, 'Segunda', 2, 1),
(26, 7, 'Segunda', 3, 1),
(26, 7, 'Segunda', 4, 1),
(26, 7, 'Segunda', 5, 1),
(26, 7, 'Segunda', 6, 1),
(26, 7, 'Quarta', 1, 1),
(26, 7, 'Quarta', 2, 1),
(26, 7, 'Quarta', 3, 1),
(26, 7, 'Quarta', 4, 1),
(26, 7, 'Quarta', 5, 1),
(26, 7, 'Quarta', 6, 1),
(26, 7, 'Quinta', 1, 1),
(26, 7, 'Quinta', 2, 1),
(26, 7, 'Quinta', 3, 1),
(26, 7, 'Quinta', 4, 1),
(26, 7, 'Quinta', 5, 1),
(26, 7, 'Quinta', 6, 1),
(26, 7, 'Sexta', 1, 1),
(26, 7, 'Sexta', 2, 1),
(26, 7, 'Sexta', 3, 1),
(26, 7, 'Sexta', 4, 1),
(26, 7, 'Sexta', 5, 1),
(26, 7, 'Sexta', 6, 1),
(27, 7, 'Segunda', 1, 1),
(27, 7, 'Segunda', 2, 1),
(27, 7, 'Segunda', 3, 1),
(27, 7, 'Segunda', 4, 1),
(27, 7, 'Segunda', 5, 1),
(27, 7, 'Segunda', 6, 1),
(27, 7, 'Terca', 1, 1),
(27, 7, 'Terca', 2, 1),
(27, 7, 'Quarta', 1, 1),
(27, 7, 'Quarta', 2, 1),
(27, 7, 'Quarta', 3, 1),
(27, 7, 'Quarta', 4, 1),
(27, 7, 'Quarta', 5, 1),
(27, 7, 'Quarta', 6, 1),
(27, 7, 'Quinta', 1, 1),
(27, 7, 'Quinta', 2, 1),
(27, 7, 'Quinta', 3, 1),
(27, 7, 'Quinta', 4, 1),
(27, 7, 'Quinta', 5, 1),
(27, 7, 'Quinta', 6, 1),
(27, 7, 'Sexta', 1, 1),
(27, 7, 'Sexta', 2, 1),
(27, 7, 'Sexta', 3, 1),
(27, 7, 'Sexta', 4, 1),
(27, 7, 'Sexta', 5, 1),
(27, 7, 'Sexta', 6, 1),
(28, 7, 'Segunda', 1, 1),
(28, 7, 'Segunda', 2, 1),
(28, 7, 'Segunda', 3, 1),
(28, 7, 'Segunda', 4, 1),
(28, 7, 'Segunda', 5, 1),
(28, 7, 'Segunda', 6, 1),
(28, 7, 'Terca', 3, 1),
(28, 7, 'Terca', 4, 1),
(28, 7, 'Terca', 5, 1),
(28, 7, 'Terca', 6, 1),
(28, 7, 'Quarta', 1, 1),
(28, 7, 'Quarta', 2, 1),
(28, 7, 'Quarta', 3, 1),
(28, 7, 'Quarta', 4, 1),
(28, 7, 'Quarta', 5, 1),
(28, 7, 'Quarta', 6, 1),
(28, 7, 'Quinta', 1, 1),
(28, 7, 'Quinta', 2, 1),
(28, 7, 'Quinta', 3, 1),
(28, 7, 'Quinta', 4, 1),
(28, 7, 'Quinta', 5, 1),
(28, 7, 'Quinta', 6, 1),
(28, 7, 'Sexta', 1, 1),
(28, 7, 'Sexta', 2, 1),
(28, 7, 'Sexta', 3, 1),
(28, 7, 'Sexta', 4, 1),
(28, 7, 'Sexta', 5, 1),
(28, 7, 'Sexta', 6, 1),
(29, 7, 'Segunda', 1, 1),
(29, 7, 'Segunda', 2, 1),
(29, 7, 'Segunda', 3, 1),
(29, 7, 'Segunda', 4, 1),
(29, 7, 'Segunda', 5, 1),
(29, 7, 'Segunda', 6, 1),
(29, 7, 'Terca', 1, 1),
(29, 7, 'Terca', 2, 1),
(29, 7, 'Terca', 3, 1),
(29, 7, 'Terca', 4, 1),
(29, 7, 'Terca', 5, 1),
(29, 7, 'Terca', 6, 1),
(29, 7, 'Quarta', 5, 1),
(29, 7, 'Quarta', 6, 1),
(29, 7, 'Quinta', 1, 1),
(29, 7, 'Quinta', 2, 1),
(29, 7, 'Quinta', 3, 1),
(29, 7, 'Quinta', 4, 1),
(29, 7, 'Quinta', 5, 1),
(29, 7, 'Quinta', 6, 1),
(29, 7, 'Sexta', 1, 1),
(29, 7, 'Sexta', 2, 1),
(29, 7, 'Sexta', 3, 1),
(29, 7, 'Sexta', 4, 1),
(29, 7, 'Sexta', 5, 1),
(29, 7, 'Sexta', 6, 1),
(30, 7, 'Segunda', 1, 1),
(30, 7, 'Segunda', 2, 1),
(30, 7, 'Segunda', 3, 1),
(30, 7, 'Segunda', 4, 1),
(30, 7, 'Segunda', 5, 1),
(30, 7, 'Segunda', 6, 1),
(30, 7, 'Terca', 1, 1),
(30, 7, 'Terca', 2, 1),
(30, 7, 'Terca', 3, 1),
(30, 7, 'Terca', 4, 1),
(30, 7, 'Terca', 5, 1),
(30, 7, 'Terca', 6, 1),
(30, 7, 'Quinta', 1, 1),
(30, 7, 'Quinta', 2, 1),
(30, 7, 'Quinta', 3, 1),
(30, 7, 'Quinta', 4, 1),
(30, 7, 'Quinta', 5, 1),
(30, 7, 'Quinta', 6, 1),
(30, 7, 'Sexta', 1, 1),
(30, 7, 'Sexta', 2, 1),
(30, 7, 'Sexta', 3, 1),
(30, 7, 'Sexta', 4, 1),
(31, 7, 'Segunda', 2, 1),
(31, 7, 'Segunda', 3, 1),
(31, 7, 'Segunda', 5, 1),
(31, 7, 'Segunda', 6, 1),
(31, 7, 'Terca', 1, 1),
(31, 7, 'Terca', 2, 1),
(31, 7, 'Terca', 3, 1),
(31, 7, 'Terca', 4, 1),
(31, 7, 'Terca', 5, 1),
(31, 7, 'Terca', 6, 1),
(31, 7, 'Quarta', 1, 1),
(31, 7, 'Quarta', 2, 1),
(31, 7, 'Quarta', 3, 1),
(31, 7, 'Quarta', 4, 1),
(31, 7, 'Quarta', 5, 1),
(31, 7, 'Quarta', 6, 1),
(31, 7, 'Quinta', 1, 1),
(31, 7, 'Quinta', 2, 1),
(31, 7, 'Quinta', 3, 1),
(31, 7, 'Quinta', 4, 1),
(31, 7, 'Quinta', 5, 1),
(31, 7, 'Quinta', 6, 1),
(31, 7, 'Sexta', 1, 1),
(31, 7, 'Sexta', 2, 1),
(31, 7, 'Sexta', 3, 1),
(31, 7, 'Sexta', 4, 1),
(31, 7, 'Sexta', 5, 1),
(31, 7, 'Sexta', 6, 1),
(31, 7, 'Domingo', 1, 2),
(31, 7, 'Domingo', 2, 2),
(31, 7, 'Domingo', 3, 2),
(31, 7, 'Domingo', 4, 2),
(31, 7, 'Domingo', 5, 2),
(31, 7, 'Domingo', 6, 2),
(31, 7, 'Segunda', 1, 2),
(31, 7, 'Segunda', 2, 2),
(31, 7, 'Segunda', 3, 2),
(31, 7, 'Segunda', 4, 2),
(31, 7, 'Segunda', 5, 2),
(31, 7, 'Segunda', 6, 2),
(31, 7, 'Terca', 1, 2),
(31, 7, 'Terca', 2, 2),
(31, 7, 'Terca', 3, 2),
(31, 7, 'Terca', 4, 2),
(31, 7, 'Terca', 5, 2),
(31, 7, 'Terca', 6, 2),
(31, 7, 'Quarta', 1, 2),
(31, 7, 'Quarta', 2, 2),
(31, 7, 'Quarta', 3, 2),
(31, 7, 'Quarta', 4, 2),
(31, 7, 'Quarta', 5, 2),
(31, 7, 'Quarta', 6, 2),
(31, 7, 'Quinta', 1, 2),
(31, 7, 'Quinta', 2, 2),
(31, 7, 'Quinta', 3, 2),
(31, 7, 'Quinta', 4, 2),
(31, 7, 'Quinta', 5, 2),
(31, 7, 'Quinta', 6, 2),
(31, 7, 'Sexta', 1, 2),
(31, 7, 'Sexta', 2, 2),
(31, 7, 'Sexta', 3, 2),
(31, 7, 'Sexta', 6, 2),
(31, 7, 'Sabado', 1, 2),
(31, 7, 'Sabado', 2, 2),
(31, 7, 'Sabado', 3, 2),
(31, 7, 'Sabado', 4, 2),
(31, 7, 'Sabado', 5, 2),
(31, 7, 'Sabado', 6, 2),
(32, 7, 'Segunda', 1, 1),
(32, 7, 'Segunda', 2, 1),
(32, 7, 'Segunda', 3, 1),
(32, 7, 'Segunda', 4, 1),
(32, 7, 'Segunda', 5, 1),
(32, 7, 'Segunda', 6, 1),
(32, 7, 'Terca', 1, 1),
(32, 7, 'Terca', 2, 1),
(32, 7, 'Terca', 3, 1),
(32, 7, 'Terca', 4, 1),
(32, 7, 'Terca', 5, 1),
(32, 7, 'Terca', 6, 1),
(32, 7, 'Quarta', 1, 1),
(32, 7, 'Quarta', 2, 1),
(32, 7, 'Quarta', 3, 1),
(32, 7, 'Quarta', 4, 1),
(32, 7, 'Quarta', 5, 1),
(32, 7, 'Quarta', 6, 1),
(32, 7, 'Quinta', 1, 1),
(32, 7, 'Quinta', 2, 1),
(32, 7, 'Quinta', 3, 1),
(32, 7, 'Quinta', 4, 1),
(32, 7, 'Quinta', 5, 1),
(32, 7, 'Quinta', 6, 1),
(32, 7, 'Domingo', 1, 2),
(32, 7, 'Domingo', 2, 2),
(32, 7, 'Domingo', 3, 2),
(32, 7, 'Domingo', 4, 2),
(32, 7, 'Domingo', 5, 2),
(32, 7, 'Domingo', 6, 2),
(32, 7, 'Segunda', 1, 2),
(32, 7, 'Segunda', 2, 2),
(32, 7, 'Segunda', 3, 2),
(32, 7, 'Segunda', 4, 2),
(32, 7, 'Segunda', 5, 2),
(32, 7, 'Segunda', 6, 2),
(32, 7, 'Terca', 1, 2),
(32, 7, 'Terca', 2, 2),
(32, 7, 'Terca', 3, 2),
(32, 7, 'Terca', 4, 2),
(32, 7, 'Terca', 5, 2),
(32, 7, 'Terca', 6, 2),
(32, 7, 'Quarta', 1, 2),
(32, 7, 'Quarta', 2, 2),
(32, 7, 'Quarta', 3, 2),
(32, 7, 'Quarta', 4, 2),
(32, 7, 'Quarta', 5, 2),
(32, 7, 'Quarta', 6, 2),
(32, 7, 'Quinta', 1, 2),
(32, 7, 'Quinta', 2, 2),
(32, 7, 'Quinta', 3, 2),
(32, 7, 'Quinta', 4, 2),
(32, 7, 'Quinta', 5, 2),
(32, 7, 'Quinta', 6, 2),
(32, 7, 'Sexta', 6, 2),
(32, 7, 'Sabado', 1, 2),
(32, 7, 'Sabado', 2, 2),
(32, 7, 'Sabado', 3, 2),
(32, 7, 'Sabado', 4, 2),
(32, 7, 'Sabado', 5, 2),
(32, 7, 'Sabado', 6, 2),
(33, 7, 'Domingo', 1, 2),
(33, 7, 'Domingo', 2, 2),
(33, 7, 'Domingo', 3, 2),
(33, 7, 'Domingo', 4, 2),
(33, 7, 'Domingo', 5, 2),
(33, 7, 'Domingo', 6, 2),
(33, 7, 'Segunda', 1, 2),
(33, 7, 'Segunda', 2, 2),
(33, 7, 'Segunda', 3, 2),
(33, 7, 'Segunda', 4, 2),
(33, 7, 'Segunda', 5, 2),
(33, 7, 'Segunda', 6, 2),
(33, 7, 'Terca', 1, 2),
(33, 7, 'Terca', 2, 2),
(33, 7, 'Terca', 3, 2),
(33, 7, 'Terca', 4, 2),
(33, 7, 'Terca', 5, 2),
(33, 7, 'Terca', 6, 2),
(33, 7, 'Quarta', 1, 2),
(33, 7, 'Quarta', 2, 2),
(33, 7, 'Quarta', 3, 2),
(33, 7, 'Quarta', 4, 2),
(33, 7, 'Quarta', 5, 2),
(33, 7, 'Quarta', 6, 2),
(33, 7, 'Quinta', 1, 2),
(33, 7, 'Quinta', 2, 2),
(33, 7, 'Quinta', 3, 2),
(33, 7, 'Quinta', 4, 2),
(33, 7, 'Quinta', 5, 2),
(33, 7, 'Quinta', 6, 2),
(33, 7, 'Sexta', 5, 2),
(33, 7, 'Sexta', 6, 2),
(33, 7, 'Sabado', 1, 2),
(33, 7, 'Sabado', 2, 2),
(33, 7, 'Sabado', 3, 2),
(33, 7, 'Sabado', 4, 2),
(33, 7, 'Sabado', 5, 2),
(33, 7, 'Sabado', 6, 2),
(34, 7, 'Segunda', 1, 1),
(34, 7, 'Segunda', 2, 1),
(34, 7, 'Segunda', 3, 1),
(34, 7, 'Segunda', 4, 1),
(34, 7, 'Segunda', 5, 1),
(34, 7, 'Segunda', 6, 1),
(34, 7, 'Terca', 1, 1),
(34, 7, 'Terca', 2, 1),
(34, 7, 'Terca', 3, 1),
(34, 7, 'Terca', 4, 1),
(34, 7, 'Terca', 5, 1),
(34, 7, 'Terca', 6, 1),
(34, 7, 'Quarta', 1, 1),
(34, 7, 'Quarta', 2, 1),
(34, 7, 'Quarta', 3, 1),
(34, 7, 'Quarta', 4, 1),
(34, 7, 'Quarta', 5, 1),
(34, 7, 'Quarta', 6, 1),
(34, 7, 'Sexta', 1, 1),
(34, 7, 'Sexta', 2, 1),
(34, 7, 'Sexta', 3, 1),
(34, 7, 'Sexta', 4, 1),
(34, 7, 'Sexta', 5, 1),
(34, 7, 'Sexta', 6, 1),
(35, 7, 'Segunda', 4, 1),
(35, 7, 'Segunda', 5, 1),
(35, 7, 'Segunda', 6, 1),
(35, 7, 'Terca', 1, 1),
(35, 7, 'Terca', 2, 1),
(35, 7, 'Terca', 3, 1),
(35, 7, 'Terca', 4, 1),
(35, 7, 'Terca', 5, 1),
(35, 7, 'Terca', 6, 1),
(35, 7, 'Quarta', 1, 1),
(35, 7, 'Quarta', 2, 1),
(35, 7, 'Quarta', 3, 1),
(35, 7, 'Quarta', 4, 1),
(35, 7, 'Quinta', 1, 1),
(35, 7, 'Quinta', 2, 1),
(35, 7, 'Quinta', 3, 1),
(35, 7, 'Quinta', 4, 1),
(35, 7, 'Quinta', 5, 1),
(35, 7, 'Quinta', 6, 1),
(35, 7, 'Sexta', 1, 1),
(35, 7, 'Sexta', 5, 1),
(35, 7, 'Sexta', 6, 1),
(36, 7, 'Segunda', 1, 1),
(36, 7, 'Segunda', 2, 1),
(36, 7, 'Segunda', 3, 1),
(36, 7, 'Segunda', 4, 1),
(36, 7, 'Segunda', 5, 1),
(36, 7, 'Segunda', 6, 1),
(36, 7, 'Terca', 1, 1),
(36, 7, 'Terca', 2, 1),
(36, 7, 'Terca', 3, 1),
(36, 7, 'Terca', 4, 1),
(36, 7, 'Terca', 5, 1),
(36, 7, 'Terca', 6, 1),
(36, 7, 'Quarta', 1, 1),
(36, 7, 'Quarta', 2, 1),
(36, 7, 'Quarta', 3, 1),
(36, 7, 'Quarta', 4, 1),
(36, 7, 'Quarta', 5, 1),
(36, 7, 'Quarta', 6, 1),
(36, 7, 'Quinta', 1, 1),
(36, 7, 'Quinta', 2, 1),
(36, 7, 'Sexta', 1, 1),
(36, 7, 'Sexta', 2, 1),
(36, 7, 'Sexta', 3, 1),
(36, 7, 'Sexta', 4, 1),
(36, 7, 'Sexta', 5, 1),
(36, 7, 'Sexta', 6, 1),
(37, 7, 'Segunda', 1, 1),
(37, 7, 'Segunda', 2, 1),
(37, 7, 'Segunda', 3, 1),
(37, 7, 'Segunda', 4, 1),
(37, 7, 'Segunda', 5, 1),
(37, 7, 'Segunda', 6, 1),
(37, 7, 'Terca', 1, 1),
(37, 7, 'Terca', 2, 1),
(37, 7, 'Terca', 3, 1),
(37, 7, 'Terca', 4, 1),
(37, 7, 'Terca', 5, 1),
(37, 7, 'Terca', 6, 1),
(37, 7, 'Quarta', 1, 1),
(37, 7, 'Quarta', 2, 1),
(37, 7, 'Quarta', 3, 1),
(37, 7, 'Quarta', 4, 1),
(37, 7, 'Quarta', 5, 1),
(37, 7, 'Quarta', 6, 1),
(37, 7, 'Quinta', 1, 1),
(37, 7, 'Quinta', 2, 1),
(37, 7, 'Sexta', 1, 1),
(37, 7, 'Sexta', 2, 1),
(37, 7, 'Sexta', 3, 1),
(37, 7, 'Sexta', 4, 1),
(37, 7, 'Sexta', 5, 1),
(37, 7, 'Sexta', 6, 1),
(38, 7, 'Segunda', 1, 1),
(38, 7, 'Segunda', 2, 1),
(38, 7, 'Segunda', 3, 1),
(38, 7, 'Segunda', 4, 1),
(38, 7, 'Segunda', 5, 1),
(38, 7, 'Segunda', 6, 1),
(38, 7, 'Terca', 1, 1),
(38, 7, 'Terca', 2, 1),
(38, 7, 'Terca', 3, 1),
(38, 7, 'Terca', 4, 1),
(38, 7, 'Terca', 5, 1),
(38, 7, 'Terca', 6, 1),
(38, 7, 'Quarta', 1, 1),
(38, 7, 'Quarta', 2, 1),
(38, 7, 'Quarta', 3, 1),
(38, 7, 'Quarta', 4, 1),
(38, 7, 'Quarta', 5, 1),
(38, 7, 'Quarta', 6, 1),
(38, 7, 'Quinta', 1, 1),
(38, 7, 'Sexta', 1, 1),
(38, 7, 'Sexta', 2, 1),
(38, 7, 'Sexta', 3, 1),
(38, 7, 'Sexta', 4, 1),
(38, 7, 'Sexta', 5, 1),
(38, 7, 'Sexta', 6, 1),
(39, 7, 'Segunda', 1, 1),
(39, 7, 'Segunda', 2, 1),
(39, 7, 'Segunda', 3, 1),
(39, 7, 'Segunda', 4, 1),
(39, 7, 'Segunda', 5, 1),
(39, 7, 'Segunda', 6, 1),
(39, 7, 'Quarta', 1, 1),
(39, 7, 'Quarta', 2, 1),
(39, 7, 'Quarta', 3, 1),
(39, 7, 'Quarta', 4, 1),
(39, 7, 'Quarta', 5, 1),
(39, 7, 'Quarta', 6, 1),
(39, 7, 'Quinta', 1, 1),
(39, 7, 'Quinta', 2, 1),
(39, 7, 'Quinta', 3, 1),
(39, 7, 'Quinta', 4, 1),
(39, 7, 'Quinta', 5, 1),
(39, 7, 'Quinta', 6, 1),
(39, 7, 'Sexta', 1, 1),
(39, 7, 'Sexta', 2, 1),
(39, 7, 'Sexta', 3, 1),
(39, 7, 'Sexta', 4, 1),
(39, 7, 'Sexta', 5, 1),
(39, 7, 'Sexta', 6, 1),
(40, 7, 'Segunda', 3, 1),
(40, 7, 'Segunda', 4, 1),
(40, 7, 'Segunda', 5, 1),
(40, 7, 'Segunda', 6, 1),
(40, 7, 'Terca', 3, 1),
(40, 7, 'Terca', 4, 1),
(40, 7, 'Terca', 5, 1),
(40, 7, 'Terca', 6, 1),
(40, 7, 'Quarta', 3, 1),
(40, 7, 'Quarta', 4, 1),
(40, 7, 'Quarta', 5, 1),
(40, 7, 'Quarta', 6, 1),
(40, 7, 'Quinta', 1, 1),
(40, 7, 'Quinta', 2, 1),
(40, 7, 'Quinta', 3, 1),
(40, 7, 'Quinta', 4, 1),
(40, 7, 'Quinta', 5, 1),
(40, 7, 'Quinta', 6, 1),
(40, 7, 'Sexta', 3, 1),
(40, 7, 'Sexta', 4, 1),
(40, 7, 'Sexta', 5, 1),
(40, 7, 'Sexta', 6, 1),
(41, 7, 'Segunda', 3, 1),
(41, 7, 'Segunda', 4, 1),
(41, 7, 'Segunda', 5, 1),
(41, 7, 'Segunda', 6, 1),
(41, 7, 'Terca', 1, 1),
(41, 7, 'Terca', 2, 1),
(41, 7, 'Terca', 3, 1),
(41, 7, 'Terca', 4, 1),
(41, 7, 'Terca', 5, 1),
(41, 7, 'Terca', 6, 1),
(41, 7, 'Quarta', 1, 1),
(41, 7, 'Quarta', 2, 1),
(41, 7, 'Quarta', 3, 1),
(41, 7, 'Quarta', 4, 1),
(41, 7, 'Quarta', 5, 1),
(41, 7, 'Quarta', 6, 1),
(41, 7, 'Quinta', 1, 1),
(41, 7, 'Quinta', 2, 1),
(41, 7, 'Quinta', 3, 1),
(41, 7, 'Quinta', 4, 1),
(41, 7, 'Sexta', 1, 1),
(41, 7, 'Sexta', 2, 1),
(41, 7, 'Sexta', 3, 1),
(41, 7, 'Sexta', 4, 1),
(41, 7, 'Sexta', 5, 1),
(41, 7, 'Sexta', 6, 1),
(42, 7, 'Terca', 1, 1),
(42, 7, 'Terca', 2, 1),
(42, 7, 'Terca', 3, 1),
(42, 7, 'Terca', 4, 1),
(42, 7, 'Terca', 5, 1),
(42, 7, 'Terca', 6, 1),
(42, 7, 'Quarta', 1, 1),
(42, 7, 'Quarta', 2, 1),
(42, 7, 'Quarta', 3, 1),
(42, 7, 'Quarta', 4, 1),
(42, 7, 'Quarta', 5, 1),
(42, 7, 'Quarta', 6, 1),
(42, 7, 'Quinta', 1, 1),
(42, 7, 'Quinta', 2, 1),
(42, 7, 'Quinta', 3, 1),
(42, 7, 'Quinta', 4, 1),
(42, 7, 'Quinta', 5, 1),
(42, 7, 'Quinta', 6, 1),
(42, 7, 'Sexta', 1, 1),
(42, 7, 'Sexta', 2, 1),
(42, 7, 'Sexta', 3, 1),
(42, 7, 'Sexta', 4, 1),
(42, 7, 'Sexta', 5, 1),
(42, 7, 'Sexta', 6, 1),
(43, 7, 'Segunda', 1, 1),
(43, 7, 'Segunda', 2, 1),
(43, 7, 'Segunda', 3, 1),
(43, 7, 'Segunda', 4, 1),
(43, 7, 'Terca', 1, 1),
(43, 7, 'Terca', 2, 1),
(43, 7, 'Terca', 3, 1),
(43, 7, 'Terca', 4, 1),
(43, 7, 'Terca', 5, 1),
(43, 7, 'Terca', 6, 1),
(43, 7, 'Quarta', 1, 1),
(43, 7, 'Quarta', 2, 1),
(43, 7, 'Sexta', 5, 1),
(43, 7, 'Sexta', 6, 1),
(44, 7, 'Domingo', 1, 2),
(44, 7, 'Domingo', 2, 2),
(44, 7, 'Domingo', 3, 2),
(44, 7, 'Domingo', 4, 2),
(44, 7, 'Domingo', 5, 2),
(44, 7, 'Domingo', 6, 2),
(44, 7, 'Segunda', 1, 2),
(44, 7, 'Segunda', 2, 2),
(44, 7, 'Segunda', 3, 2),
(44, 7, 'Segunda', 4, 2),
(44, 7, 'Segunda', 5, 2),
(44, 7, 'Segunda', 6, 2),
(44, 7, 'Terca', 1, 2),
(44, 7, 'Terca', 2, 2),
(44, 7, 'Terca', 3, 2),
(44, 7, 'Terca', 4, 2),
(44, 7, 'Terca', 5, 2),
(44, 7, 'Terca', 6, 2),
(44, 7, 'Quarta', 1, 2),
(44, 7, 'Quarta', 2, 2),
(44, 7, 'Quarta', 3, 2),
(44, 7, 'Quarta', 4, 2),
(44, 7, 'Quarta', 5, 2),
(44, 7, 'Quarta', 6, 2),
(44, 7, 'Quinta', 1, 2),
(44, 7, 'Quinta', 2, 2),
(44, 7, 'Quinta', 3, 2),
(44, 7, 'Quinta', 4, 2),
(44, 7, 'Quinta', 5, 2),
(44, 7, 'Quinta', 6, 2),
(44, 7, 'Sexta', 1, 2),
(44, 7, 'Sexta', 6, 2),
(44, 7, 'Sabado', 1, 2),
(44, 7, 'Sabado', 2, 2),
(44, 7, 'Sabado', 3, 2),
(44, 7, 'Sabado', 4, 2),
(44, 7, 'Sabado', 5, 2),
(44, 7, 'Sabado', 6, 2),
(45, 7, 'Segunda', 1, 1),
(45, 7, 'Segunda', 2, 1),
(45, 7, 'Segunda', 3, 1),
(45, 7, 'Segunda', 4, 1),
(45, 7, 'Terca', 1, 1),
(45, 7, 'Terca', 2, 1),
(45, 7, 'Terca', 3, 1),
(45, 7, 'Terca', 4, 1),
(45, 7, 'Terca', 5, 1),
(45, 7, 'Terca', 6, 1),
(45, 7, 'Quarta', 1, 1),
(45, 7, 'Quarta', 2, 1),
(45, 7, 'Quarta', 3, 1),
(45, 7, 'Quarta', 4, 1),
(45, 7, 'Quarta', 5, 1),
(45, 7, 'Quarta', 6, 1),
(45, 7, 'Quinta', 1, 1),
(45, 7, 'Quinta', 2, 1),
(45, 7, 'Quinta', 3, 1),
(45, 7, 'Quinta', 4, 1),
(45, 7, 'Quinta', 5, 1),
(45, 7, 'Quinta', 6, 1),
(45, 7, 'Sexta', 1, 1),
(45, 7, 'Sexta', 2, 1),
(45, 7, 'Sexta', 3, 1),
(45, 7, 'Sexta', 4, 1),
(45, 7, 'Sexta', 5, 1),
(45, 7, 'Sexta', 6, 1),
(48, 7, 'Segunda', 1, 1),
(48, 7, 'Segunda', 2, 1),
(48, 7, 'Segunda', 3, 1),
(48, 7, 'Segunda', 4, 1),
(48, 7, 'Segunda', 5, 1),
(48, 7, 'Segunda', 6, 1),
(48, 7, 'Terca', 1, 1),
(48, 7, 'Terca', 2, 1),
(48, 7, 'Terca', 3, 1),
(48, 7, 'Terca', 4, 1),
(48, 7, 'Terca', 5, 1),
(48, 7, 'Terca', 6, 1),
(48, 7, 'Quarta', 1, 1),
(48, 7, 'Quarta', 2, 1),
(48, 7, 'Quarta', 3, 1),
(48, 7, 'Quarta', 4, 1),
(48, 7, 'Quarta', 5, 1),
(48, 7, 'Quarta', 6, 1),
(48, 7, 'Quinta', 2, 1),
(48, 7, 'Quinta', 3, 1),
(48, 7, 'Quinta', 4, 1),
(48, 7, 'Quinta', 5, 1),
(48, 7, 'Quinta', 6, 1),
(48, 7, 'Sexta', 1, 1),
(48, 7, 'Sexta', 2, 1),
(48, 7, 'Sexta', 3, 1),
(48, 7, 'Sexta', 4, 1),
(48, 7, 'Sexta', 5, 1),
(48, 7, 'Sexta', 6, 1),
(49, 7, 'Segunda', 1, 1),
(49, 7, 'Segunda', 2, 1),
(49, 7, 'Segunda', 3, 1),
(49, 7, 'Segunda', 4, 1),
(49, 7, 'Segunda', 5, 1),
(49, 7, 'Segunda', 6, 1),
(49, 7, 'Terca', 1, 1),
(49, 7, 'Terca', 2, 1),
(49, 7, 'Terca', 3, 1),
(49, 7, 'Terca', 4, 1),
(49, 7, 'Terca', 5, 1),
(49, 7, 'Terca', 6, 1),
(49, 7, 'Quarta', 1, 1),
(49, 7, 'Quarta', 2, 1),
(49, 7, 'Quarta', 3, 1),
(49, 7, 'Quarta', 4, 1),
(49, 7, 'Quarta', 5, 1),
(49, 7, 'Quarta', 6, 1),
(49, 7, 'Quinta', 2, 1),
(49, 7, 'Quinta', 3, 1),
(49, 7, 'Quinta', 4, 1),
(49, 7, 'Quinta', 5, 1),
(49, 7, 'Quinta', 6, 1),
(49, 7, 'Sexta', 1, 1),
(49, 7, 'Sexta', 2, 1),
(49, 7, 'Sexta', 3, 1),
(49, 7, 'Sexta', 4, 1),
(49, 7, 'Sexta', 5, 1),
(49, 7, 'Sexta', 6, 1),
(51, 7, 'Segunda', 1, 1),
(51, 7, 'Segunda', 2, 1),
(51, 7, 'Segunda', 3, 1),
(51, 7, 'Segunda', 4, 1),
(51, 7, 'Segunda', 5, 1),
(51, 7, 'Segunda', 6, 1),
(51, 7, 'Terca', 1, 1),
(51, 7, 'Terca', 2, 1),
(51, 7, 'Terca', 3, 1),
(51, 7, 'Terca', 4, 1),
(51, 7, 'Terca', 5, 1),
(51, 7, 'Terca', 6, 1),
(51, 7, 'Quarta', 1, 1),
(51, 7, 'Quarta', 2, 1),
(51, 7, 'Quarta', 3, 1),
(51, 7, 'Quarta', 4, 1),
(51, 7, 'Quarta', 5, 1),
(51, 7, 'Quarta', 6, 1),
(51, 7, 'Quinta', 2, 1),
(51, 7, 'Quinta', 3, 1),
(51, 7, 'Quinta', 4, 1),
(51, 7, 'Quinta', 5, 1),
(51, 7, 'Quinta', 6, 1),
(51, 7, 'Sexta', 1, 1),
(51, 7, 'Sexta', 2, 1),
(51, 7, 'Sexta', 3, 1),
(51, 7, 'Sexta', 4, 1),
(51, 7, 'Sexta', 5, 1),
(51, 7, 'Sexta', 6, 1),
(56, 7, 'Segunda', 1, 1),
(56, 7, 'Segunda', 2, 1),
(56, 7, 'Segunda', 3, 1),
(56, 7, 'Segunda', 4, 1),
(56, 7, 'Segunda', 5, 1),
(56, 7, 'Segunda', 6, 1),
(56, 7, 'Terca', 1, 1),
(56, 7, 'Terca', 2, 1),
(56, 7, 'Terca', 3, 1),
(56, 7, 'Terca', 4, 1),
(56, 7, 'Terca', 5, 1),
(56, 7, 'Terca', 6, 1),
(56, 7, 'Quarta', 1, 1),
(56, 7, 'Quarta', 2, 1),
(56, 7, 'Quarta', 3, 1),
(56, 7, 'Quarta', 4, 1),
(56, 7, 'Quarta', 5, 1),
(56, 7, 'Quarta', 6, 1),
(56, 7, 'Quinta', 2, 1),
(56, 7, 'Quinta', 3, 1),
(56, 7, 'Quinta', 4, 1),
(56, 7, 'Quinta', 5, 1),
(56, 7, 'Quinta', 6, 1),
(56, 7, 'Sexta', 1, 1),
(56, 7, 'Sexta', 2, 1),
(56, 7, 'Sexta', 3, 1),
(56, 7, 'Sexta', 4, 1),
(56, 7, 'Sexta', 5, 1),
(56, 7, 'Sexta', 6, 1),
(57, 7, 'Segunda', 1, 1),
(57, 7, 'Segunda', 2, 1),
(57, 7, 'Segunda', 3, 1),
(57, 7, 'Segunda', 4, 1),
(57, 7, 'Segunda', 5, 1),
(57, 7, 'Segunda', 6, 1),
(57, 7, 'Quarta', 1, 1),
(57, 7, 'Quarta', 2, 1),
(57, 7, 'Quarta', 3, 1),
(57, 7, 'Quarta', 4, 1),
(57, 7, 'Quarta', 5, 1),
(57, 7, 'Quarta', 6, 1),
(57, 7, 'Quinta', 1, 1),
(57, 7, 'Quinta', 6, 1),
(57, 7, 'Sexta', 1, 1),
(57, 7, 'Sexta', 2, 1),
(57, 7, 'Sexta', 3, 1),
(57, 7, 'Sexta', 4, 1),
(57, 7, 'Sexta', 5, 1),
(57, 7, 'Sexta', 6, 1),
(58, 7, 'Segunda', 6, 1),
(58, 7, 'Terca', 6, 1),
(58, 7, 'Quarta', 1, 1),
(58, 7, 'Quarta', 2, 1),
(58, 7, 'Quarta', 3, 1),
(58, 7, 'Quarta', 4, 1),
(58, 7, 'Quarta', 5, 1),
(58, 7, 'Quarta', 6, 1),
(58, 7, 'Quinta', 1, 1),
(58, 7, 'Quinta', 2, 1),
(58, 7, 'Quinta', 3, 1),
(58, 7, 'Quinta', 4, 1),
(58, 7, 'Quinta', 5, 1),
(58, 7, 'Quinta', 6, 1),
(58, 7, 'Sexta', 1, 1),
(58, 7, 'Sexta', 2, 1),
(58, 7, 'Sexta', 3, 1),
(58, 7, 'Sexta', 4, 1);

--
-- Acionadores `professor_restricoes`
--
DELIMITER $$
CREATE TRIGGER `trg_prof_restricoes_turno_bi` BEFORE INSERT ON `professor_restricoes` FOR EACH ROW BEGIN
  IF NEW.id_turno IS NULL OR NEW.id_turno = 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'id_turno inválido (0/NULL) em professor_restricoes';
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_prof_restricoes_turno_bu` BEFORE UPDATE ON `professor_restricoes` FOR EACH ROW BEGIN
  IF NEW.id_turno IS NULL OR NEW.id_turno = 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'id_turno inválido (0/NULL) em professor_restricoes';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `professor_turnos`
--

CREATE TABLE `professor_turnos` (
  `id_professor` int(11) NOT NULL,
  `id_turno` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `professor_turnos`
--

INSERT INTO `professor_turnos` (`id_professor`, `id_turno`) VALUES
(2, 1),
(3, 1),
(4, 1),
(5, 1),
(5, 2),
(6, 1),
(7, 1),
(8, 1),
(9, 1),
(10, 1),
(11, 1),
(11, 2),
(12, 1),
(12, 2),
(13, 1),
(14, 1),
(15, 1),
(16, 1),
(17, 1),
(18, 1),
(19, 1),
(20, 1),
(21, 1),
(22, 1),
(23, 1),
(24, 1),
(24, 2),
(25, 1),
(26, 1),
(27, 1),
(28, 1),
(29, 1),
(30, 1),
(31, 1),
(31, 2),
(32, 1),
(32, 2),
(33, 2),
(34, 1),
(35, 1),
(36, 1),
(37, 1),
(38, 1),
(39, 1),
(40, 1),
(41, 1),
(42, 1),
(43, 1),
(44, 2),
(45, 1),
(48, 1),
(49, 1),
(51, 1),
(56, 1),
(57, 1),
(58, 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `remember_me_tokens`
--

CREATE TABLE `remember_me_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `selector` varchar(255) NOT NULL,
  `token_hash` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `sala`
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
-- Despejando dados para a tabela `sala`
--

INSERT INTO `sala` (`id_sala`, `id_ano_letivo`, `nome_sala`, `max_carteiras`, `max_cadeiras`, `capacidade_alunos`, `localizacao`, `recursos`, `data_cadastro`) VALUES
(1, 5, '101', 30, 30, 30, 'Térreo', 'Projetor', '2025-03-26 20:40:50'),
(2, 7, '204', 30, 30, 30, 'Edifício; 1 Andar', '', '2025-12-16 10:44:31');

-- --------------------------------------------------------

--
-- Estrutura para tabela `sala_turno`
--

CREATE TABLE `sala_turno` (
  `id_sala` int(11) NOT NULL,
  `id_turno` int(11) NOT NULL,
  `id_turma` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `sala_turno`
--

INSERT INTO `sala_turno` (`id_sala`, `id_turno`, `id_turma`) VALUES
(1, 2, 1),
(2, 1, 17);

-- --------------------------------------------------------

--
-- Estrutura para tabela `serie`
--

CREATE TABLE `serie` (
  `id_serie` int(11) NOT NULL,
  `id_nivel_ensino` int(11) NOT NULL,
  `nome_serie` varchar(50) NOT NULL,
  `total_aulas_semana` int(11) NOT NULL,
  `data_cadastro_serie` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `serie`
--

INSERT INTO `serie` (`id_serie`, `id_nivel_ensino`, `nome_serie`, `total_aulas_semana`, `data_cadastro_serie`) VALUES
(1, 3, '6º Ano', 30, '2025-02-14 23:00:07'),
(2, 3, '7º Ano', 30, '2025-02-15 16:06:46'),
(3, 2, '1º Ano', 40, '2025-03-19 17:33:44'),
(4, 3, '8º Ano', 30, '2025-03-20 18:45:55'),
(5, 3, '9º Ano', 30, '2025-03-20 18:56:28'),
(6, 4, '1ª Série', 35, '2025-12-16 10:39:29'),
(7, 4, '2ª Série', 35, '2025-12-16 20:38:19'),
(8, 4, '3ª Série', 35, '2025-12-16 20:38:58'),
(9, 2, '2º Ano', 35, '2025-12-16 20:39:26'),
(10, 2, '3º Ano', 35, '2025-12-16 20:39:36'),
(11, 2, '4º Ano', 35, '2025-12-16 20:39:48'),
(12, 2, '5º Ano', 35, '2025-12-16 20:39:57');

-- --------------------------------------------------------

--
-- Estrutura para tabela `serie_disciplinas`
--

CREATE TABLE `serie_disciplinas` (
  `id_serie` int(11) NOT NULL,
  `id_disciplina` int(11) NOT NULL,
  `aulas_semana` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `serie_disciplinas`
--

INSERT INTO `serie_disciplinas` (`id_serie`, `id_disciplina`, `aulas_semana`) VALUES
(1, 2, 6),
(1, 3, 5),
(1, 4, 2),
(1, 5, 3),
(1, 6, 1),
(1, 8, 3),
(1, 9, 2),
(1, 16, 2),
(1, 17, 4),
(1, 18, 1),
(1, 19, 1),
(2, 2, 5),
(2, 3, 5),
(2, 4, 3),
(2, 5, 3),
(2, 6, 1),
(2, 8, 3),
(2, 9, 2),
(2, 16, 2),
(2, 17, 4),
(2, 18, 1),
(2, 19, 1),
(4, 3, 2),
(4, 4, 3),
(4, 5, 3),
(4, 6, 1),
(4, 8, 3),
(4, 9, 2),
(4, 12, 1),
(4, 16, 2),
(4, 17, 4),
(4, 18, 1),
(4, 19, 1),
(4, 25, 4),
(4, 26, 3),
(5, 3, 2),
(5, 4, 3),
(5, 5, 3),
(5, 6, 1),
(5, 7, 1),
(5, 9, 2),
(5, 12, 2),
(5, 13, 2),
(5, 16, 2),
(5, 17, 4),
(5, 18, 1),
(5, 19, 1),
(5, 25, 3),
(5, 26, 3),
(6, 1, 2),
(6, 2, 4),
(6, 3, 1),
(6, 4, 3),
(6, 5, 3),
(6, 7, 4),
(6, 9, 1),
(6, 10, 1),
(6, 11, 1),
(6, 12, 4),
(6, 13, 4),
(6, 16, 2),
(6, 18, 1),
(6, 20, 2),
(6, 21, 1),
(6, 23, 1),
(7, 1, 2),
(7, 2, 4),
(7, 3, 1),
(7, 4, 3),
(7, 5, 3),
(7, 7, 4),
(7, 9, 1),
(7, 10, 1),
(7, 11, 1),
(7, 12, 4),
(7, 13, 4),
(7, 16, 2),
(7, 18, 1),
(7, 20, 2),
(7, 21, 1),
(7, 23, 1),
(8, 1, 2),
(8, 2, 6),
(8, 4, 2),
(8, 5, 2),
(8, 7, 5),
(8, 10, 1),
(8, 11, 1),
(8, 12, 5),
(8, 13, 5),
(8, 16, 2),
(8, 20, 2),
(8, 21, 1),
(8, 22, 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `turma`
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
-- Despejando dados para a tabela `turma`
--

INSERT INTO `turma` (`id_turma`, `id_ano_letivo`, `id_serie`, `id_turno`, `nome_turma`, `data_cadastro_turma`, `intervalos_por_dia`, `intervalos_positions`) VALUES
(1, 5, 1, 1, 'A', '2025-02-16 12:01:55', 2, '3,5'),
(2, 5, 1, 1, 'B', '2025-02-17 20:12:58', 2, '3,5'),
(3, 5, 2, 1, 'A', '2025-02-18 19:58:37', 2, '3,5'),
(4, 5, 2, 1, 'B', '2025-02-18 19:58:46', 2, '3,5'),
(5, 5, 3, 2, 'A', '2025-03-19 17:34:32', 1, '4'),
(6, 5, 3, 2, 'B', '2025-03-19 17:35:03', 1, '4'),
(7, 5, 4, 1, 'A', '2025-03-20 19:03:06', 2, '3,5'),
(8, 7, 6, 1, 'A', '2025-12-16 11:22:35', 2, '3,5'),
(9, 7, 6, 2, 'A', '2025-12-16 11:22:35', 2, '3,5'),
(10, 7, 4, 1, 'B', '2025-12-16 20:41:28', 2, '3,5'),
(11, 7, 5, 1, 'A', '2025-12-16 20:41:44', 2, '3,5'),
(12, 7, 5, 1, 'B', '2025-12-16 20:41:56', 2, '3,5'),
(13, 7, 4, 1, 'A', '2025-12-16 20:42:36', 2, '3,5'),
(14, 7, 2, 1, 'A', '2025-12-16 20:42:47', 2, '3,5'),
(15, 7, 2, 1, 'B', '2025-12-16 20:43:09', 2, '3,5'),
(16, 7, 1, 1, 'B', '2025-12-16 20:43:21', 2, '3,5'),
(17, 7, 1, 1, 'A', '2025-12-16 20:43:30', 2, '3,5'),
(18, 7, 6, 1, 'B', '2025-12-17 09:46:48', 2, '3,5'),
(19, 7, 6, 2, 'B', '2025-12-17 09:46:48', 1, '3'),
(20, 7, 7, 1, 'U', '2025-12-17 09:47:42', 2, '3,5'),
(21, 7, 7, 2, 'U', '2025-12-17 09:47:42', 1, '3'),
(22, 7, 8, 1, 'A', '2025-12-17 09:48:32', 2, '3,5'),
(23, 7, 8, 2, 'A', '2025-12-17 09:48:32', 1, '3'),
(24, 7, 8, 1, 'B', '2025-12-17 09:49:57', 2, '3,5'),
(25, 7, 8, 2, 'B', '2025-12-17 09:49:57', 1, '3');

-- --------------------------------------------------------

--
-- Estrutura para tabela `turno`
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
-- Despejando dados para a tabela `turno`
--

INSERT INTO `turno` (`id_turno`, `nome_turno`, `descricao_turno`, `horario_inicio_turno`, `horario_fim_turno`, `data_cadastro_turno`) VALUES
(1, 'Matutino', 'Turno para as aulas no período da manhã', '07:10:00', '12:10:00', '2025-02-08 10:40:15'),
(2, 'Vespertino', 'Turno para as aulas no período da tarde', '13:15:00', '17:50:00', '2025-02-16 12:15:49'),
(3, 'Noturno', 'Turno para as aulas no período da noite', '18:50:00', '22:50:00', '2025-02-16 12:16:29');

-- --------------------------------------------------------

--
-- Estrutura para tabela `turno_dias`
--

CREATE TABLE `turno_dias` (
  `id_turno_dia` int(11) NOT NULL,
  `id_turno` int(11) NOT NULL,
  `dia_semana` enum('Domingo','Segunda','Terca','Quarta','Quinta','Sexta','Sabado') NOT NULL,
  `aulas_no_dia` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `turno_dias`
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
-- Estrutura para tabela `usuario`
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
-- Despejando dados para a tabela `usuario`
--

INSERT INTO `usuario` (`id_usuario`, `nome_usuario`, `email_usuario`, `senha_usuario`, `nivel_usuario`, `situacao_usuario`, `data_cadastro_usuario`, `data_alteracao_senha_usuario`, `imagem_usuario`) VALUES
(1, 'Diezare.Conde', 'diezare@proton.com', '$2y$10$zQY0rXZz3p2XBw7xBODt4upUTeRr0EMoa/s55hXqXQP9860QUFjzW', 'Administrador', 'Ativo', '2025-03-11 13:37:33', NULL, 'http://localhost/horarios/app/assets/imgs/perfil/1741733963_1741711053_Eu.jpg'),
(2, 'Eduardo', 'dudi86dudi@gmail.com', '$2y$10$Z1qrHwdbqmPuJ1XXeeItC.1E7H/5VkTvYdKY3Y21oqFEfIb1yoPI6', 'Usuário', 'Ativo', '2025-05-08 19:30:27', NULL, 'https://estrategiaslegais.com.br/horarios/app/assets/imgs/perfil/1765883931_Captura de tela 2025-12-16 080950.png'),
(3, 'Bruno.Lacerda', 'bruno@localhost.com', '$2y$10$s.lXvkwR8SMwu7GPuz2X/uyvoIZTzbfF6muRGsgLpYPC3VRkF/bZy', 'Usuário', 'Ativo', '2025-12-16 08:46:54', NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuario_niveis`
--

CREATE TABLE `usuario_niveis` (
  `id_usuario` int(11) NOT NULL,
  `id_nivel_ensino` int(11) NOT NULL,
  `data_cadastro` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuario_niveis`
--

INSERT INTO `usuario_niveis` (`id_usuario`, `id_nivel_ensino`, `data_cadastro`) VALUES
(1, 1, '2025-04-03 14:27:36'),
(1, 2, '2025-04-03 14:27:36'),
(1, 3, '2025-04-03 14:27:36'),
(1, 4, '2025-04-03 14:27:36'),
(1, 6, '2025-04-03 14:27:36'),
(1, 8, '2025-04-03 14:27:36'),
(2, 8, '2025-05-08 19:30:36'),
(3, 3, '2025-12-16 10:29:38'),
(3, 4, '2025-12-16 10:29:38'),
(3, 6, '2025-12-16 10:29:38');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `ano_letivo`
--
ALTER TABLE `ano_letivo`
  ADD PRIMARY KEY (`id_ano_letivo`),
  ADD UNIQUE KEY `ano` (`ano`);

--
-- Índices de tabela `categoria`
--
ALTER TABLE `categoria`
  ADD PRIMARY KEY (`id_categoria`),
  ADD KEY `id_modalidade` (`id_modalidade`);

--
-- Índices de tabela `configuracao_hora_aula_escolinha`
--
ALTER TABLE `configuracao_hora_aula_escolinha`
  ADD PRIMARY KEY (`id_configuracao`),
  ADD UNIQUE KEY `uk_ano_modalidade` (`id_ano_letivo`,`id_modalidade`),
  ADD KEY `id_modalidade` (`id_modalidade`),
  ADD KEY `fk_categoria` (`id_categoria`);

--
-- Índices de tabela `disciplina`
--
ALTER TABLE `disciplina`
  ADD PRIMARY KEY (`id_disciplina`);

--
-- Índices de tabela `eventos_calendario_escolar`
--
ALTER TABLE `eventos_calendario_escolar`
  ADD PRIMARY KEY (`id_evento`),
  ADD KEY `id_ano_letivo` (`id_ano_letivo`);

--
-- Índices de tabela `historico_horario`
--
ALTER TABLE `historico_horario`
  ADD PRIMARY KEY (`id_historico`),
  ADD KEY `id_ano_letivo` (`id_ano_letivo`),
  ADD KEY `idx_hist_ano_turno_turma_data` (`id_ano_letivo`,`id_turno`,`id_turma`,`data_arquivamento`);

--
-- Índices de tabela `horario`
--
ALTER TABLE `horario`
  ADD PRIMARY KEY (`id_horario`),
  ADD UNIQUE KEY `unq_turma_dia_aula` (`id_turma`,`dia_semana`,`numero_aula`),
  ADD UNIQUE KEY `uq_prof_slot` (`id_professor`,`id_turno`,`dia_semana`,`numero_aula`),
  ADD UNIQUE KEY `uq_turma_slot` (`id_turma`,`id_turno`,`dia_semana`,`numero_aula`),
  ADD UNIQUE KEY `uq_slot` (`id_ano_letivo`,`id_turno`,`id_turma`,`dia_semana`,`numero_aula`),
  ADD UNIQUE KEY `uq_horario_slot` (`id_ano_letivo`,`id_turma`,`id_turno`,`dia_semana`,`numero_aula`),
  ADD KEY `fk_horario_disciplina` (`id_disciplina`),
  ADD KEY `fk_horario_professor` (`id_professor`),
  ADD KEY `idx_h_turma_dia_aula` (`id_turma`,`dia_semana`,`numero_aula`),
  ADD KEY `idx_h_prof_dia_aula` (`id_professor`,`dia_semana`,`numero_aula`),
  ADD KEY `idx_h_disc` (`id_disciplina`),
  ADD KEY `ix_turma` (`id_turma`),
  ADD KEY `ix_prof` (`id_professor`),
  ADD KEY `idx_horario_ano_turno_turma` (`id_ano_letivo`,`id_turno`,`id_turma`),
  ADD KEY `idx_horario_ano_turno_dia_aula` (`id_ano_letivo`,`id_turno`,`dia_semana`,`numero_aula`),
  ADD KEY `idx_horario_prof_ano_turno_dia_aula` (`id_ano_letivo`,`id_turno`,`id_professor`,`dia_semana`,`numero_aula`);

--
-- Índices de tabela `horario_escolinha`
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
-- Índices de tabela `horario_fixos`
--
ALTER TABLE `horario_fixos`
  ADD PRIMARY KEY (`id_horario_fixo`),
  ADD UNIQUE KEY `uq_turma_slot` (`id_turma`,`dia_semana`,`numero_aula`,`id_ano_letivo`);

--
-- Índices de tabela `instituicao`
--
ALTER TABLE `instituicao`
  ADD PRIMARY KEY (`id_instituicao`);

--
-- Índices de tabela `log_atividade`
--
ALTER TABLE `log_atividade`
  ADD PRIMARY KEY (`id_log`),
  ADD KEY `fk_log_atividade_usuario` (`id_usuario`);

--
-- Índices de tabela `modalidade`
--
ALTER TABLE `modalidade`
  ADD PRIMARY KEY (`id_modalidade`);

--
-- Índices de tabela `nivel_ensino`
--
ALTER TABLE `nivel_ensino`
  ADD PRIMARY KEY (`id_nivel_ensino`);

--
-- Índices de tabela `professor`
--
ALTER TABLE `professor`
  ADD PRIMARY KEY (`id_professor`);

--
-- Índices de tabela `professor_categoria`
--
ALTER TABLE `professor_categoria`
  ADD PRIMARY KEY (`id_professor`,`id_categoria`),
  ADD KEY `fk_pc_categoria` (`id_categoria`);

--
-- Índices de tabela `professor_disciplinas`
--
ALTER TABLE `professor_disciplinas`
  ADD PRIMARY KEY (`id_professor`,`id_disciplina`),
  ADD KEY `fk_pd_disciplina` (`id_disciplina`);

--
-- Índices de tabela `professor_disciplinas_turmas`
--
ALTER TABLE `professor_disciplinas_turmas`
  ADD PRIMARY KEY (`id_professor`,`id_disciplina`,`id_turma`),
  ADD UNIQUE KEY `uq_pdt` (`id_professor`,`id_disciplina`,`id_turma`),
  ADD KEY `fk_pdt_disciplina` (`id_disciplina`),
  ADD KEY `fk_pdt_turma` (`id_turma`),
  ADD KEY `idx_pdt_turma` (`id_turma`),
  ADD KEY `idx_pdt_prof_disc` (`id_professor`,`id_disciplina`);

--
-- Índices de tabela `professor_restricoes`
--
ALTER TABLE `professor_restricoes`
  ADD PRIMARY KEY (`id_professor`,`id_ano_letivo`,`id_turno`,`dia_semana`,`numero_aula`),
  ADD KEY `fk_pr_ano_letivo` (`id_ano_letivo`);

--
-- Índices de tabela `professor_turnos`
--
ALTER TABLE `professor_turnos`
  ADD PRIMARY KEY (`id_professor`,`id_turno`),
  ADD KEY `fk_pt_turno` (`id_turno`);

--
-- Índices de tabela `remember_me_tokens`
--
ALTER TABLE `remember_me_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `selector` (`selector`),
  ADD KEY `idx_rmt_user` (`user_id`),
  ADD KEY `idx_rmt_expires` (`expires_at`);

--
-- Índices de tabela `sala`
--
ALTER TABLE `sala`
  ADD PRIMARY KEY (`id_sala`),
  ADD KEY `fk_sala_ano_letivo` (`id_ano_letivo`);

--
-- Índices de tabela `sala_turno`
--
ALTER TABLE `sala_turno`
  ADD PRIMARY KEY (`id_sala`,`id_turno`),
  ADD KEY `fk_sala_turno_turno` (`id_turno`),
  ADD KEY `fk_sala_turno_turma` (`id_turma`);

--
-- Índices de tabela `serie`
--
ALTER TABLE `serie`
  ADD PRIMARY KEY (`id_serie`),
  ADD KEY `fk_serie_nivel_ensino` (`id_nivel_ensino`);

--
-- Índices de tabela `serie_disciplinas`
--
ALTER TABLE `serie_disciplinas`
  ADD PRIMARY KEY (`id_serie`,`id_disciplina`),
  ADD KEY `fk_sd_disciplina` (`id_disciplina`);

--
-- Índices de tabela `turma`
--
ALTER TABLE `turma`
  ADD PRIMARY KEY (`id_turma`),
  ADD UNIQUE KEY `unq_turma_ano` (`id_serie`,`id_turno`,`nome_turma`,`id_ano_letivo`),
  ADD KEY `fk_turma_ano_letivo` (`id_ano_letivo`),
  ADD KEY `fk_turma_turno` (`id_turno`);

--
-- Índices de tabela `turno`
--
ALTER TABLE `turno`
  ADD PRIMARY KEY (`id_turno`);

--
-- Índices de tabela `turno_dias`
--
ALTER TABLE `turno_dias`
  ADD PRIMARY KEY (`id_turno_dia`),
  ADD KEY `fk_turno_dias_turno` (`id_turno`);

--
-- Índices de tabela `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `email_usuario` (`email_usuario`);

--
-- Índices de tabela `usuario_niveis`
--
ALTER TABLE `usuario_niveis`
  ADD PRIMARY KEY (`id_usuario`,`id_nivel_ensino`),
  ADD KEY `fk_usuario_niveis_nivel` (`id_nivel_ensino`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `ano_letivo`
--
ALTER TABLE `ano_letivo`
  MODIFY `id_ano_letivo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

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
  MODIFY `id_disciplina` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT de tabela `eventos_calendario_escolar`
--
ALTER TABLE `eventos_calendario_escolar`
  MODIFY `id_evento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `historico_horario`
--
ALTER TABLE `historico_horario`
  MODIFY `id_historico` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=329;

--
-- AUTO_INCREMENT de tabela `horario`
--
ALTER TABLE `horario`
  MODIFY `id_horario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23271;

--
-- AUTO_INCREMENT de tabela `horario_escolinha`
--
ALTER TABLE `horario_escolinha`
  MODIFY `id_horario_escolinha` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT de tabela `horario_fixos`
--
ALTER TABLE `horario_fixos`
  MODIFY `id_horario_fixo` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `instituicao`
--
ALTER TABLE `instituicao`
  MODIFY `id_instituicao` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `log_atividade`
--
ALTER TABLE `log_atividade`
  MODIFY `id_log` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=374;

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
  MODIFY `id_professor` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT de tabela `remember_me_tokens`
--
ALTER TABLE `remember_me_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `sala`
--
ALTER TABLE `sala`
  MODIFY `id_sala` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `serie`
--
ALTER TABLE `serie`
  MODIFY `id_serie` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de tabela `turma`
--
ALTER TABLE `turma`
  MODIFY `id_turma` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

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
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `categoria`
--
ALTER TABLE `categoria`
  ADD CONSTRAINT `categoria_ibfk_1` FOREIGN KEY (`id_modalidade`) REFERENCES `modalidade` (`id_modalidade`);

--
-- Restrições para tabelas `configuracao_hora_aula_escolinha`
--
ALTER TABLE `configuracao_hora_aula_escolinha`
  ADD CONSTRAINT `configuracao_hora_aula_escolinha_ibfk_1` FOREIGN KEY (`id_ano_letivo`) REFERENCES `ano_letivo` (`id_ano_letivo`),
  ADD CONSTRAINT `configuracao_hora_aula_escolinha_ibfk_2` FOREIGN KEY (`id_modalidade`) REFERENCES `modalidade` (`id_modalidade`),
  ADD CONSTRAINT `fk_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categoria` (`id_categoria`);

--
-- Restrições para tabelas `eventos_calendario_escolar`
--
ALTER TABLE `eventos_calendario_escolar`
  ADD CONSTRAINT `eventos_calendario_escolar_ibfk_1` FOREIGN KEY (`id_ano_letivo`) REFERENCES `ano_letivo` (`id_ano_letivo`);

--
-- Restrições para tabelas `historico_horario`
--
ALTER TABLE `historico_horario`
  ADD CONSTRAINT `fk_historico_ano_letivo` FOREIGN KEY (`id_ano_letivo`) REFERENCES `ano_letivo` (`id_ano_letivo`);

--
-- Restrições para tabelas `horario`
--
ALTER TABLE `horario`
  ADD CONSTRAINT `fk_horario_disciplina` FOREIGN KEY (`id_disciplina`) REFERENCES `disciplina` (`id_disciplina`),
  ADD CONSTRAINT `fk_horario_professor` FOREIGN KEY (`id_professor`) REFERENCES `professor` (`id_professor`),
  ADD CONSTRAINT `fk_horario_turma` FOREIGN KEY (`id_turma`) REFERENCES `turma` (`id_turma`);

--
-- Restrições para tabelas `horario_escolinha`
--
ALTER TABLE `horario_escolinha`
  ADD CONSTRAINT `fk_he_ano_letivo` FOREIGN KEY (`id_ano_letivo`) REFERENCES `ano_letivo` (`id_ano_letivo`),
  ADD CONSTRAINT `fk_he_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categoria` (`id_categoria`),
  ADD CONSTRAINT `fk_he_modalidade` FOREIGN KEY (`id_modalidade`) REFERENCES `modalidade` (`id_modalidade`),
  ADD CONSTRAINT `fk_he_nivel_ensino` FOREIGN KEY (`id_nivel_ensino`) REFERENCES `nivel_ensino` (`id_nivel_ensino`),
  ADD CONSTRAINT `fk_he_professor` FOREIGN KEY (`id_professor`) REFERENCES `professor` (`id_professor`),
  ADD CONSTRAINT `fk_he_turno` FOREIGN KEY (`id_turno`) REFERENCES `turno` (`id_turno`);

--
-- Restrições para tabelas `log_atividade`
--
ALTER TABLE `log_atividade`
  ADD CONSTRAINT `fk_log_atividade_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`);

--
-- Restrições para tabelas `professor_categoria`
--
ALTER TABLE `professor_categoria`
  ADD CONSTRAINT `fk_pc_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categoria` (`id_categoria`),
  ADD CONSTRAINT `fk_pc_professor` FOREIGN KEY (`id_professor`) REFERENCES `professor` (`id_professor`);

--
-- Restrições para tabelas `professor_disciplinas`
--
ALTER TABLE `professor_disciplinas`
  ADD CONSTRAINT `fk_pd_disciplina` FOREIGN KEY (`id_disciplina`) REFERENCES `disciplina` (`id_disciplina`),
  ADD CONSTRAINT `fk_pd_professor` FOREIGN KEY (`id_professor`) REFERENCES `professor` (`id_professor`);

--
-- Restrições para tabelas `professor_disciplinas_turmas`
--
ALTER TABLE `professor_disciplinas_turmas`
  ADD CONSTRAINT `fk_pdt_disciplina` FOREIGN KEY (`id_disciplina`) REFERENCES `disciplina` (`id_disciplina`),
  ADD CONSTRAINT `fk_pdt_professor` FOREIGN KEY (`id_professor`) REFERENCES `professor` (`id_professor`),
  ADD CONSTRAINT `fk_pdt_turma` FOREIGN KEY (`id_turma`) REFERENCES `turma` (`id_turma`);

--
-- Restrições para tabelas `professor_restricoes`
--
ALTER TABLE `professor_restricoes`
  ADD CONSTRAINT `fk_pr_ano_letivo` FOREIGN KEY (`id_ano_letivo`) REFERENCES `ano_letivo` (`id_ano_letivo`),
  ADD CONSTRAINT `fk_pr_professor` FOREIGN KEY (`id_professor`) REFERENCES `professor` (`id_professor`);

--
-- Restrições para tabelas `professor_turnos`
--
ALTER TABLE `professor_turnos`
  ADD CONSTRAINT `fk_pt_professor` FOREIGN KEY (`id_professor`) REFERENCES `professor` (`id_professor`),
  ADD CONSTRAINT `fk_pt_turno` FOREIGN KEY (`id_turno`) REFERENCES `turno` (`id_turno`);

--
-- Restrições para tabelas `remember_me_tokens`
--
ALTER TABLE `remember_me_tokens`
  ADD CONSTRAINT `remember_me_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE;

--
-- Restrições para tabelas `sala`
--
ALTER TABLE `sala`
  ADD CONSTRAINT `fk_sala_ano_letivo` FOREIGN KEY (`id_ano_letivo`) REFERENCES `ano_letivo` (`id_ano_letivo`);

--
-- Restrições para tabelas `sala_turno`
--
ALTER TABLE `sala_turno`
  ADD CONSTRAINT `fk_sala_turno_sala` FOREIGN KEY (`id_sala`) REFERENCES `sala` (`id_sala`),
  ADD CONSTRAINT `fk_sala_turno_turma` FOREIGN KEY (`id_turma`) REFERENCES `turma` (`id_turma`),
  ADD CONSTRAINT `fk_sala_turno_turno` FOREIGN KEY (`id_turno`) REFERENCES `turno` (`id_turno`);

--
-- Restrições para tabelas `serie`
--
ALTER TABLE `serie`
  ADD CONSTRAINT `fk_serie_nivel_ensino` FOREIGN KEY (`id_nivel_ensino`) REFERENCES `nivel_ensino` (`id_nivel_ensino`);

--
-- Restrições para tabelas `serie_disciplinas`
--
ALTER TABLE `serie_disciplinas`
  ADD CONSTRAINT `fk_sd_disciplina` FOREIGN KEY (`id_disciplina`) REFERENCES `disciplina` (`id_disciplina`),
  ADD CONSTRAINT `fk_sd_serie` FOREIGN KEY (`id_serie`) REFERENCES `serie` (`id_serie`);

--
-- Restrições para tabelas `turma`
--
ALTER TABLE `turma`
  ADD CONSTRAINT `fk_turma_ano_letivo` FOREIGN KEY (`id_ano_letivo`) REFERENCES `ano_letivo` (`id_ano_letivo`),
  ADD CONSTRAINT `fk_turma_serie` FOREIGN KEY (`id_serie`) REFERENCES `serie` (`id_serie`),
  ADD CONSTRAINT `fk_turma_turno` FOREIGN KEY (`id_turno`) REFERENCES `turno` (`id_turno`);

--
-- Restrições para tabelas `turno_dias`
--
ALTER TABLE `turno_dias`
  ADD CONSTRAINT `fk_turno_dias_turno` FOREIGN KEY (`id_turno`) REFERENCES `turno` (`id_turno`);

--
-- Restrições para tabelas `usuario_niveis`
--
ALTER TABLE `usuario_niveis`
  ADD CONSTRAINT `fk_usuario_niveis_nivel` FOREIGN KEY (`id_nivel_ensino`) REFERENCES `nivel_ensino` (`id_nivel_ensino`),
  ADD CONSTRAINT `fk_usuario_niveis_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
