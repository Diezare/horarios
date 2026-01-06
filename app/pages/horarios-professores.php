<?php
// app/pages/horarios-professores.php
require_once __DIR__ . '/../../configs/init.php';
include_once APP_SERVER_PATH . '/models/header.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
	<meta charset="UTF-8">
	<title>Relatório de Horários de Aulas dos Professores</title>
	<link rel="stylesheet" href="<?php echo CSS_PATH; ?>/style.css">
</head>
<body>
<main>
	<!-- TÍTULO E BREADCRUMB -->
	<div class="page-header">
		<h1>Relatório de Horários de Aulas dos Professores</h1>
		<a href="<?php echo PAGES_PATH; ?>/professor.php" class="btn-add">
			<i class="fa-solid fa-plus"></i> Adicionar Professor
		</a>
	</div>
	
	<ul class="breadcrumbs">
		<li>Relatórios</li>
		<li> / </li>
		<li>Horários de Aulas de Professores</li>
	</ul>

	<!-- CARD-HEADER COM OS FILTROS E BOTÃO IMPRIMIR -->
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
		<!-- <button id="btnImprimir" disabled>Imprimir</button> -->
		
		<button id="btnImprimir" class="btnImprimir" aria-label="Imprimir">
			<span class="icon"><i class="fa-solid fa-print"></i></span>
			<span class="text">Imprimir</span>
		</button>		
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
							
							<th>Professor
								<span class="sort-icons">
									<i class="fa-solid fa-sort-up" id="sort-professor-asc"></i>
									<i class="fa-solid fa-sort-down" id="sort-professor-desc"></i>
								</span>
							</th>

							<th>Ações</th>
						</tr>
					</thead>
					<tbody id="tbodyRelatorioHorarios">
						<!-- Populado dinamicamente via JS -->
					</tbody>
				</table>
				<div id="noDataMessage" class="noDataMessage">
					Nenhum dado encontrado.
				</div>
			</div>
		</div>
	</div>
</main>

<!-- MODAL DE IMPRESSÃO -->
<div id="modalImpressao" class="modal" style="display:none;">
	<div class="modal-content">
		<div class="modal-header">
			<h2>Imprimir Relatório</h2>
			<span class="close-modal" id="closeModalImpressao">&times;</span>
		</div>
		<div class="modal-body">
			<p>Deseja imprimir este relatório?</p>
		</div>
		<div class="modal-footer">
			<button id="btnConfirmImprimir"><i class="fa-solid fa-print"></i>Imprimir</button>
			<button id="btnCancelar">Cancelar</button>
		</div>
	</div>
</div>

<!-- SCRIPTS -->
<script src="<?php echo JS_PATH; ?>/script.js"></script>
<script src="<?php echo JS_PATH; ?>/horarios-professores.js"></script>
</body>
</html>
