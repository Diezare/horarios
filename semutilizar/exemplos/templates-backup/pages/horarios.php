<?php
// app/pages/horarios.php
require_once __DIR__ . '/../../configs/init.php';
include_once APP_SERVER_PATH . '/models/header.php';
?>

<main>
	<div class="page-header">
		<h1>Gerar Horários</h1>
		<button id="btn-automatic" class="btn-automatic"><i class="fa-solid fa-arrows-rotate"></i> Automático </button>
	</div>
	<ul class="breadcrumbs">
		<li><a href="#">Horários</a></li>
		<li class="divider">/</li>
		<li><a href="#" class="active">Gerar Horários</a></li>
	</ul>

	<div class="card-header">
		<div class="form-group">
			<label for="selectAnoLetivo">Ano Letivo:</label>
			<select id="selectAnoLetivo">
				<option value="">-- Selecione --</option>
			</select>
		</div>
		
		<div class="form-group">
			<label for="selectTurma">Turma:</label>
			<select id="selectTurma" disabled>
				<option value="">-- Selecione a Turma --</option>
			</select>
		</div>

		<!-- <button id="btnCarregar" disabled>Carregar Grade</button> -->
		<button id="btnImprimir">Imprimir Horário</button>
	</div>

	<div class="data">
		<div class="content-data">
			<div id="grade-container"></div>
			<div id="quadro-disciplinas"></div>
		</div>
	</div>
</main>

<!-- MODAL PARA DEFINIR INTERVALOS -->
<div id="modal-intervalos" class="modal" style="display:none;">
    <div class="modal-content delete-modal">
        <div class="modal-header">
            <h2 id="modal-title">Definir Intervalos</h2>
            <span class="close-modal" id="close-intervalos-modal">&times;</span>
        </div>
        <div class="modal-body">
            <p>Posições padrões para 2 intervalos: <strong>[3,6].</strong> Se quiser customizar, altere abaixo (valores separados por vírgula):</p>
            <input type="text" id="intervalos-input" value="3,6" />
        </div>
        <div class="modal-footer">
            <button id="btnConfirmarIntervalos">Confirmar</button>
            <button id="btnCancelarIntervalos">Cancelar</button>
        </div>
    </div>
</div>

<script src="<?php echo JS_PATH; ?>/script.js"></script>
<script src="<?php echo JS_PATH; ?>/horarios.js"></script>
</body>
</html>
