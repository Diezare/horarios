<?php
require_once __DIR__ . '/../../configs/init.php';
include_once APP_SERVER_PATH . '/models/header.php';
?>
<main id="page-prof-aulas">
	<div class="page-header">
		<h1>Professores x Turmas</h1>

		<button id="btnImprimir" class="btnImprimir" aria-label="Imprimir">
			<span class="icon"><i class="fa-solid fa-print"></i></span>
			<span class="text">Imprimir</span>
		</button>		
	</div>

	<ul class="breadcrumbs">
		<li>Relatórios</li><li> / </li><li>Professores x Turmas</li>
	</ul>

	<div class="card-header">
		<div class="form-group">
			<label for="selectAnoLetivo">Ano Letivo</label>
			<select id="selectAnoLetivo">
				<option value="">-- Selecione --</option>
			</select>
		</div>

		<div class="form-group">
			<label for="selectNivelEnsino">Nível de Ensino</label>
			<select id="selectNivelEnsino" disabled>
				<option value="">-- Selecione o Nível --</option>
			</select>
		</div>

		<div class="form-group">
			<label for="selectTurma">Turma</label>
			<select id="selectTurma" disabled>
				<option value="">-- Todas --</option>
			</select>
		</div>

		<div class="form-group">
			<label for="selectDetalhe">Detalhes</label>
			<select id="selectDetalhe" disabled>
				<option value="geral">Geral</option>
				<option value="dias">Dias da Semana</option>
				<option value="quantidade">Quantidade de Aulas</option>
			</select>
		</div>

		<div class="form-group">
			<label for="selectProfessor">Professor(a)</label>
			<select id="selectProfessor" disabled>
				<option value="">-- Geral --</option>
			</select>
		</div>
	</div>

	<div class="data">
		<div class="content-data table-scroll-x"><!-- scroll só aqui -->
			<div class="table-data" style="min-width:100%;">
				<table id="profAulasTable">
					<thead id="theadProfAulas"></thead>
					<tbody id="tbodyProfAulas"></tbody>
				</table>
				<div id="noDataMessage" class="noDataMessage">
					Nenhum registro encontrado.
				</div>
			</div>
		</div>
	</div>
</main>

<!-- MODAL IMPRESSÃO (com fade/slide) -->
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
			<button id="btnImprimirPaisagem"><i class="fa-solid fa-print"></i>Imprimir</button>
			<button id="btnCancelarImpressao">Cancelar</button>
		</div>
	</div>
</div>

<script src="<?php echo JS_PATH; ?>/script.js"></script>
<script src="<?php echo JS_PATH; ?>/prof-aulas.js"></script>
</body>
</html>
