<?php
// app/pages/historico-horarios.php
require_once __DIR__ . '/../../configs/init.php';
include_once APP_SERVER_PATH . '/models/header.php';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
	<meta charset="UTF-8">
	<title>Histórico de Horários</title>
	<link rel="stylesheet" href="<?php echo CSS_PATH; ?>/style.css">
</head>
<body>
<main>
	<!-- TÍTULO E BREADCRUMB -->
	<div class="page-header">
		<h1>Histórico de Horários</h1>
			<a href="<?php echo PAGES_PATH; ?>/horarios.php" class="btn-add" aria-label="Gerar Horários" title="Gerar Horários">
				<i class="fa-solid fa-arrows-rotate"></i> Gerar Horários
			</a>
	</div>

 	<ul class="breadcrumbs">
		<li>Histórico</li>
		<li> / </li>
		<li>Histórico de Horários</li>
	</ul>
	<!-- CARD-HEADER COM OS FILTROS -->
	<div class="card-header">
		<div class="form-group">
			<label for="selectAnoLetivo">Ano Letivo:</label>
			<select id="selectAnoLetivo">
				<option value="">-- Selecione --</option>
				<!-- Populado via JS -->
			</select>
		</div>
 
		<div class="form-group">
			<label for="selectNivelEnsino">Nível de Ensino:</label>
			<select id="selectNivelEnsino" disabled>
				<option value="">-- Selecione o Nível --</option>
			</select>
		</div>

		<div class="form-group">
			<label for="selectTurma">Turma:</label>
			<select id="selectTurma" disabled>
				<option value="">-- Selecione a Turma --</option>
			</select>
		</div>

		<div class="form-group">
			<label for="selectTurno">Turno:</label>
			<select id="selectTurno" disabled>
				<option value="">-- Selecione o Turno --</option>
			</select>
		</div>
	</div>

	<!-- TABELA DE DADOS -->
	<div class="data">
		<div class="content-data">
			<div class="table-data">
				<table>
					<thead>
						<tr>
							<th>Ano Letivo
								<span class="sort-icons">
									<i class="fa-solid fa-sort-up" id="sort-ano-asc"></i>
									<i class="fa-solid fa-sort-down" id="sort-ano-desc"></i>
								</span>
							</th>
							
							<th>Alteração
								<span class="sort-icons">
									<i class="fa-solid fa-sort-up" id="sort-alteracao-asc"></i>
									<i class="fa-solid fa-sort-down" id="sort-alteracao-desc"></i>
								</span>
							</th>

							<th>Série
								<span class="sort-icons">
									<i class="fa-solid fa-sort-up" id="sort-serie-asc"></i>
									<i class="fa-solid fa-sort-down" id="sort-serie-desc"></i>
								</span>
							</th>

							<th>Turma
								<span class="sort-icons">
									<i class="fa-solid fa-sort-up" id="sort-turma-asc"></i>
									<i class="fa-solid fa-sort-down" id="sort-turma-desc"></i>
								</span>
							</th>

							<th>Turno
								<span class="sort-icons">
									<i class="fa-solid fa-sort-up" id="sort-turno-asc"></i>
									<i class="fa-solid fa-sort-down" id="sort-turno-desc"></i>
								</span>
							</th>

							<th>Ações</th>
						</tr>
					</thead>
					<tbody id="tbodyRelatorioHorarios">
						<!-- Preenchido dinamicamente via JS -->
					</tbody>
				</table>
				<div id="noDataMessage" class="noDataMessage">
					Nenhuma turma encontrada.
				</div>
			</div>
		</div>
	</div>

	<!-- CONTROLES DE PAGINAÇÃO (exibidos via JS) -->
	<div id="paginationContainer" style="text-align:center; margin-top:10px; display:none;">
		<button id="prevPageBtn">Anterior</button>
		<span id="paginationStatus"></span>
		<button id="nextPageBtn">Próximo</button>
	</div>
</main>

<!-- MODAL IMPRESSÃO -->
<div id="modalImpressao" class="modal" style="display:none;">
	<div class="modal-content">
		<div class="modal-header">
			<h2>Imprimir Relatório</h2>
			<span class="close-modal" id="closeModalImpressao">&times;</span>
		</div>
		<div class="modal-body">
			<p>Deseja imprimir de qual maneira o relatório?</p>
		</div>
		<div class="modal-footer">
			<button id="btnImprimirRetrato">
				<i class="fa-solid fa-file-image"></i> Retrato
			</button>
			<button id="btnImprimirPaisagem">
				<i class="fa-solid fa-image"></i> Paisagem
			</button>
			<button id="btnCancelarImpressao">Cancelar</button>
		</div>
	</div>
</div>

<script src="<?php echo JS_PATH; ?>/script.js"></script>
<script src="<?php echo JS_PATH; ?>/historico-horarios.js"></script>

</body>
</html>
