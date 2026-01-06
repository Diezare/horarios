<?php
// app/pages/sala.php 
require_once __DIR__ . '/../../configs/init.php';
include_once APP_SERVER_PATH . '/models/header.php';
?>

<main>
	<div class="page-header">
		<h1>Salas</h1>
		<button id="btn-add-sala" class="btn-add">
			<i class="fa-solid fa-plus"></i> Adicionar
		</button>
	</div>

	<ul class="breadcrumbs">
		<li>Cadastros Gerais</li>
		<li> / </li>
		<li>Salas</li>
	</ul>

	<!-- CARD-HEADER COM FILTROS + BOTÃO IMPRIMIR -->
	<div class="card-header">
		<div class="form-group">
			<div class="card-title"><h3>Pesquisar</h3></div>
		</div>
		<div class="card-search">
			<input type="text" id="search-input-sala" placeholder="Pesquisar...">
		</div>
		<!-- <button id="btnImprimir">Imprimir Geral</button> -->
		<button id="btnImprimir" class="btnImprimir" aria-label="Imprimir Geral">
			<span class="icon"><i class="fa-solid fa-print"></i></span>
			<span class="text">Imprimir Geral</span>
		</button>
	</div>

	<div class="data">
		<div class="content-data">
			<div class="table-data">
				<table>
					<thead>
						<tr>
							<th>Ano Letivo
								<span class="sort-icons">
									<i class="fa-solid fa-sort-up" id="sort-ano-asc"></i>
									<i class="fa-solid fa-sort-down" id="sort-no-desc"></i>
								</span>
							</th>
							<th>Sala
								<span class="sort-icons">
									<i class="fa-solid fa-sort-up" id="sort-nome-sala-asc"></i>
									<i class="fa-solid fa-sort-down" id="sort-nome-sala-desc"></i>
								</span>
							</th>
							<th>Turmas Vinculadas
								<span class="sort-icons">
									<i class="fa-solid fa-sort-up" id="sort-turmas-asc"></i>
									<i class="fa-solid fa-sort-down" id="sort-turmas-desc"></i>
								</span>
							</th>
							<th>Ações</th>
						</tr>
					</thead>
					<tbody id="salaTable"></tbody>
				</table>
				<div id="no-data-message-sala" style="display: none; text-align: center; margin-top: 10px;">
					Não há nenhuma sala cadastrada.
				</div>
			</div>
		</div>
	</div>
</main>

<!-- MODAL: Adicionar/Editar Sala -->
<div id="modal-sala" class="modal">
	<div class="modal-content">
		<div class="modal-header">
			<h2 id="modal-sala-title">Adicionar Sala</h2>
			<span class="close-modal" id="close-sala-modal">&times;</span>
		</div>
		<div class="modal-body">
			<input type="hidden" id="salaId">
			
			<!-- Linha com Nome da Sala e Ano Letivo -->
			<div class="form-row">
				<div class="form-group" style="flex: 1;">
					<label for="anoLetivo">Ano Letivo *</label>
					<select id="anoLetivo" required>
						<option value="">Selecione</option>
						<!-- Opções serão preenchidas via JavaScript -->
					</select>
				</div>
				<div class="form-group" style="flex: 2;">
					<label for="nomeSala">Nome da Sala *</label>
					<input type="text" id="nomeSala" placeholder="Ex: Sala 101" required>
				</div>
			</div>

			<div class="form-row">
				<div class="form-group">
					<label for="maxCarteiras">Máximo de Carteiras *</label>
					<input type="number" id="maxCarteiras" placeholder="Ex: 30" required min="0">
				</div>
				<div class="form-group">
					<label for="maxCadeiras">Máximo de Cadeiras *</label>
					<input type="number" id="maxCadeiras" placeholder="Ex: 30" required min="0">
				</div>
				<div class="form-group">
					<label for="capacidadeAlunos">Capacidade de Alunos *</label>
					<input type="number" id="capacidadeAlunos" placeholder="Ex: 30" required min="0">
				</div>
			</div>

			<div class="form-group">
				<label for="localizacao">Localização *</label>
				<input type="text" id="localizacao" placeholder="Ex: Edifício A, 2º andar">
			</div>
			
			<div class="form-group">
				<label for="recursos">Recursos</label>
				<textarea id="recursos" placeholder="Ex: Projetor, lousa digital, etc." maxlength="254"></textarea> 
			</div>
			<em>* Campos obrigatórios.</em>
		</div>
		
		<div class="modal-footer">
			<button id="saveSalaBtn">Salvar</button>
			<button id="cancelSalaBtn">Cancelar</button>
		</div>
	</div>
</div>

<!-- MODAL: Vincular Turma -->
<div id="modal-sala-turno" class="modal">
	<div class="modal-content">
		<div class="modal-header">
			<h2>Vincular Turma</h2>
			<span class="close-modal" id="close-sala-turno-modal">&times;</span>
		</div>
		<div class="modal-body">
			<!-- Nome da sala (readonly) e id da sala; campo oculto único para o ano letivo -->
			<div class="form-group">
				<label for="nomeSalaTurno">Nome da Sala</label>
				<input type="text" id="nomeSalaTurno" readonly>
				<input type="hidden" id="salaIdTurno">
				<input type="hidden" id="idAnoLetivo">
			</div>

			<!-- Checkboxes dos turnos (3 colunas via .checkbox-grid) -->
			<div class="form-group">
				<label>Turnos</label>
				<!-- A classe "checkbox-grid" será usada para organizar 3 colunas -->
				<div id="turnos-checkboxes" class="checkbox-grid">
					<!-- Checkboxes são criados via JS (sala-turno.js) -->
				</div>
			</div>

			<!-- Bloco para criação dinâmica (dropdown do nível + radio buttons da turma) -->
			<div id="turnos-detalhes"><!-- preenchemos via JS --></div>
		</div>
		<div class="modal-footer">
			<button id="saveSalaTurnoBtn">Salvar</button>
			<button id="cancelSalaTurnoBtn">Cancelar</button>
		</div>
	</div>
</div>

<!-- MODAL DE CONFIRMAÇÃO DE EXCLUSÃO DE SALA -->
<div id="modal-delete-sala" class="modal">
	<div class="modal-content delete-modal">
		<div class="modal-header">
			<h2>Confirmação de Exclusão</h2>
			<span class="close-modal" id="close-delete-sala-modal">&times;</span>
		</div>
		<div class="modal-body">
			<p>Deseja realmente excluir esta sala?</p>
		</div>
		<div class="modal-footer">
			<button id="confirm-delete-sala-btn">Sim, excluir</button>
			<button id="cancel-delete-sala-btn">Cancelar</button>
		</div>
	</div>
</div>

<!-- MODAL DE IMPRESSÃO INDIVIDUAL PARA SALA -->
<div id="modal-print-sala" class="modal">
	<div class="modal-content">
		<div class="modal-header">
			<h2>Imprimir Sala</h2>
			<span class="close-modal" id="close-print-sala">&times;</span>
		</div>
		<div class="modal-body">
			<!-- Campo com o nome da sala (readonly) -->
			<div class="form-group">
				<label for="selected-sala">Sala</label>
				<input type="text" id="selected-sala" readonly>
			</div>
			<!-- Checkbox para definir se incluir turmas vinculadas -->
			<div class="form-group inline-checkbox">
				<label for="chk-turmas-sala" class="radio-like">
					<input type="checkbox" id="chk-turmas-sala">
					<span><strong>Turmas Vinculadas</strong></span>
				</label>
			</div>
		</div>
		<div class="modal-footer">
			<button id="btn-imprimir-sala"><i class="fa-solid fa-print"></i>Imprimir</button>
			<button id="btn-cancelar-sala">Cancelar</button>
		</div>
	</div>
</div>

<!-- MODAL DE IMPRESSÃO GERAL PARA SALA -->
<div id="modal-print-sala-geral" class="modal">
	<div class="modal-content">
		<div class="modal-header">
			<h2>Imprimir Sala - Geral</h2>
			<span class="close-modal" id="close-print-sala-geral">&times;</span>
		</div>
		<div class="modal-body">
			<!-- Aqui não exibe o campo Nome da Sala, pois é relatório geral -->
			<div class="form-group">
				<label for="select-nivel-geral-sala">Nível de Ensino</label>
				<select id="select-nivel-geral-sala">
					<option value="todos">Todos</option>
					<!-- Options via AJAX -->
				</select>
			</div>
			<div class="filter-section">
				<div class="form-row">
					<div class="form-group inline-checkbox">
						<label for="chk-turmas-sala-geral" class="radio-like">
							<input type="checkbox" id="chk-turmas-sala-geral">
							<span><strong>Vincular Turmas</strong></span>
						</label>
					</div>
				</div>
			</div>
		</div>
		<div class="modal-footer">
			<button id="btn-imprimir-sala-geral-confirm"><i class="fa-solid fa-print"></i>Imprimir</button>
			<button id="btn-cancelar-sala-geral">Cancelar</button>
		</div>
	</div>
</div>

<script src="<?php echo JS_PATH; ?>/script.js"></script>
<script src="<?php echo JS_PATH; ?>/sala.js"></script>
<script src="<?php echo JS_PATH; ?>/sala-turno.js"></script>

</body>
</html>
