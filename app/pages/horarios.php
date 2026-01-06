<?php
// app/pages/horarios.php
require_once __DIR__ . '/../../configs/init.php';
include_once APP_SERVER_PATH . '/models/header.php';
?>

<main>
	<div class="page-header">
		<h1>Horários de Aulas</h1>
		<button id="btn-automatic" class="btn-automatic" disabled>
			<i class="fa-solid fa-arrows-rotate"></i> Automático
		</button>
	</div>
	<ul class="breadcrumbs">
		<li>Painel de Horários</li>
		<li> / </li>
		<li>Gerar Horários de Aulas</li>
	</ul>

	<div class="card-header">
		<div class="form-group">
			<label for="selectAnoLetivo">Ano Letivo:</label>
			<select id="selectAnoLetivo">
				<option value="">-- Selecione --</option>
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

		<div class="form-group">
			<label for="selectTurma">Turma:</label>
			<select id="selectTurma" disabled>
				<option value="">-- Selecione a Turma --</option>
			</select>
		</div>

		<button id="btnImprimir" class="btnImprimir" aria-label="Imprimir Horário">
			<span class="icon"><i class="fa-solid fa-print"></i></span>
			<span class="text">Imprimir Horário</span>
		</button>
	</div>

	<div class="data">
		<div class="content-data" id="content-data-horarios">
			<div id="grade-container"></div>
			<div id="quadro-disciplinas"></div>

			<!-- placeholder dentro de .data -->
			<div id="no-data-message" style="display:none; text-align:center; ">
				Nenhuma informação encontrada.
			</div>
		</div>
	</div>
</main>

<!-- MODAL PARA GERAÇÃO AUTOMÁTICA -->
<div id="modal-automatico" class="modal" style="display:none;">
	<div class="modal-content">
		<div class="modal-header">
			<h2>Geração Automática</h2>
			<span class="close-modal-auto">&times;</span>
		</div>
		<div class="modal-body">
			<p>Deseja realmente gerar os horários para todas as turmas deste nível de ensino?
				 <br>Isso irá substituir todos os horários já preenchidos.</p>
		</div>
		<div class="modal-footer">
			<button id="btnConfirmarAutomatico">Confirmar</button>
			<button id="btnCancelarAutomatico">Cancelar</button>
		</div>
	</div>
</div>

<!-- MODAL PARA AULA EXTRA -->
<div id="modal-extra" class="modal" style="display:none;">
	<div class="modal-content">
		<div class="modal-header">
			<h2>Aula Extra</h2>
			<span class="close-modal-extra">&times;</span>
		</div>
		<div class="modal-content-extra"></div>
		<div class="modal-footer">
			<button id="btnConfirmarExtra">Confirmar</button>
			<button id="btnCancelarExtra">Cancelar</button>
		</div>
	</div>
</div>

<!-- ✅ O modal de fixação será criado dinamicamente pelo JavaScript -->

<script src="<?php echo JS_PATH; ?>/script.js"></script>
<script src="<?php echo JS_PATH; ?>/horarios.js"></script>
<!-- ✅ ADICIONAR ESTA LINHA -->
<script src="<?php echo JS_PATH; ?>/horarios_modal_fixacao.js"></script>
</body>
</html>