<?php
// app/pages/horario-aulas.php
require_once __DIR__ . '/../../configs/init.php';
include_once APP_SERVER_PATH . '/models/header.php';
?>

<main>
	<!-- TÍTULO E BREADCRUMB -->
	<div class="page-header">
	<h1>Relatório de Horários</h1>
		<button id="btn-unir-horarios" class="btn-unir-horarios">
			<i class="fa-regular fa-clone"></i> Unir Horários 
		</button>
	</div>

	<ul class="breadcrumbs">
		<li>Relatórios</li>
		<li> / </li>
		<li>Horários de Aulas</li>
	</ul>

	<!-- CARD-HEADER COM OS 3 FILTROS + BOTÃO IMPRIMIR -->
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
			<label for="selectTurno">Turno:</label>
			<select id="selectTurno" disabled>
				<option value="">-- Selecione o Turno --</option>
			</select>
		</div>

		<!-- <button id="btnImprimir" >Imprimir Horários</button>-->
		<button id="btnImprimir" disabled class="btnImprimir" aria-label="Imprimir Horários">
			<span class="icon"><i class="fa-solid fa-print"></i></span>
			<span class="text">Imprimir Horários</span>
		</button>
	</div>

	<!-- TABELA DE DADOS (ESTRUTURA PADRÃO) -->
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
</main>

<!-- MODAL IMPRESSÃO (para escolher Retrato/Paisagem/Cancelar) -->
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

<!-- MODAL UNIR HORÁRIOS -->
<div id="modalUnirHorarios" class="modal" style="display:none;">
	<div class="modal-content">
		<div class="modal-header">
			<h2>Unir Horários</h2>
			<span class="close-modal" id="closeModalUnirHorarios">&times;</span>
		</div>
		<div class="modal-body">
			<p>Deseja unir de qual maneira os horários?</p>
		</div>
		<div class="modal-footer">
			<button id="btnUnirRetrato">
				<i class="fa-solid fa-file-image"></i> Retrato
			</button>
			<button id="btnUnirPaisagem">
				<i class="fa-solid fa-image"></i> Paisagem
			</button>
			<button id="btnCancelarUnir">Cancelar</button>
		</div>
	</div>
</div>

<!-- SCRIPTS -->
<script src="<?php echo JS_PATH; ?>/script.js"></script>
<script src="<?php echo JS_PATH; ?>/horario-aulas.js"></script>
</body>
</html>
