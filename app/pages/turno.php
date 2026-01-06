<?php
// app/pages/turno.php
require_once __DIR__ . '/../../configs/init.php';

// VERIFICA NÍVEL DE USUÁRIO:
if ($_SESSION['nivel_usuario'] !== 'Administrador') {
		echo "<script>
				alert('Você não tem permissão para acessar esta página.');
				window.location = 'dashboard.php';
		</script>";
		exit;
}

include_once APP_SERVER_PATH . '/models/header.php';
?>
<main>
	<div class="page-header">
		<h1>Turnos</h1>
		<button id="btn-add-turno" class="btn-add">
			<i class="fa-solid fa-plus"></i> Adicionar
		</button>
	</div>

	<ul class="breadcrumbs">
		<li>Cadastros Gerais</li>
		<li> / </li>
		<li>Turnos</li>
	</ul>
	
	<!-- CARD-HEADER COM FILTROS + BOTÃO IMPRIMIR -->
	<div class="card-header">
		<div class="form-group">
			<div class="card-title"><h3>Pesquisar</h3></div>
		</div>

		<div class="card-search">
			<input type="text" id="search-input" placeholder="Pesquisar...">
		</div>

		<!-- Botão de imprimir geral
		<button id="btnImprimir">Imprimir Geral</button> -->
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
							<th>Turno
								<span class="sort-icons">
									<i id="sort-asc" class="fa-solid fa-sort-up"></i>
									<i id="sort-desc" class="fa-solid fa-sort-down"></i>
								</span>
							</th>
							<th>Ações</th>
						</tr>
					</thead>
					<tbody id="turnoTable"></tbody>
				</table>
				<div id="no-data-message" class="no-data-message">
					Não há nenhuma informação cadastrada.
				</div>
			</div>
		</div>
	</div>
</main>

<!-- MODAL: Adicionar/Editar Turno -->
<div id="modal-turno" class="modal">
	<div class="modal-content">
		<div class="modal-header">
			<h2 id="modal-title">Adicionar Turno</h2>
			<span class="close-modal" id="close-modal">&times;</span>
		</div>
		<div class="modal-body">
			<input type="hidden" id="turnoId">

			<div class="form-group">
				<label for="nomeTurno">Nome do Turno *</label>
				<input
					type="text"
					id="nomeTurno"
					placeholder="Ex: Matutino, Vespertino..."
					required
					maxlength="100"
				>
			</div>
			<div class="form-group">
				<label for="descricaoTurno">Descrição</label>
				<input
					id="descricaoTurno"
					placeholder="Ex: Turno para aulas matinais..."
					maxlength="255"
				>
			</div>
			<div class="form-row">
				<div class="form-group">
					<label for="horaInicio">Horário de Início *</label>
					<input type="time" id="horaInicio" required>
				</div>
				<div class="form-group">
					<label for="horaFim">Horário de Fim *</label>
					<input type="time" id="horaFim" required>
				</div>
			</div>
			<em>* Campos obrigatórios.</em>
		</div>
		
		<div class="modal-footer">
			<button id="save-btn">Salvar</button>
			<button id="cancel-btn">Cancelar</button>
		</div>
	</div>
</div>

<!-- MODAL DE CONFIRMAÇÃO DE EXCLUSÃO -->
<div id="modal-delete" class="modal">
	<div class="modal-content delete-modal">
		<div class="modal-header">
			<h2>Confirmação de Exclusão</h2>
			<span class="close-delete-modal" id="close-delete-modal">&times;</span>
		</div>
		<div class="modal-body">
			<p>Deseja realmente excluir este registro?</p>
		</div>
		<div class="modal-footer">
			<button id="confirm-delete-btn">Sim, excluir</button>
			<button id="cancel-delete-btn">Cancelar</button>
		</div>
	</div>
</div>

<!-- MODAL: VINCULAR DIAS (turno_dias) -->
<div id="modal-turno-dias" class="modal">
	<div class="modal-content">
		<div class="modal-header">
			<h2>Vincular Dias</h2>
			<span class="close-modal" id="close-turno-dias-modal">&times;</span>
		</div>
		<div class="modal-body">
			<!-- Nome do Turno (read-only) -->
			<div class="form-group">
				<label for="nome-turno-dias">Nome do Turno</label>
				<input type="text" id="nome-turno-dias" readonly>
				<!-- hidden com o ID do turno -->
				<input type="hidden" id="select-turno-dias">
			</div>

			<!-- Tabela para 7 dias da semana e 7 inputs (2 dígitos) -->
			<div class="form-group">
				<label>Dias da Semana *</label>
				<div class="turno-dias-grid">
					<!-- Será preenchido via JS -->
				</div>
			</div>	
			<em>* Campos obrigatórios.</em>

		</div>
		<div class="modal-footer">
			<button id="save-turno-dias-btn">Salvar</button>
			<button id="cancel-turno-dias-btn">Cancelar</button>
		</div>
	</div>
</div>

<!-- MODAL DE IMPRESSÃO DO TURNO -->
<div id="modal-print-turno" class="modal">
	<div class="modal-content">
		<div class="modal-header">
			<h2>Imprimir Turno</h2>
			<span class="close-modal" id="close-print-modal-turno">&times;</span>
		</div>
		<div class="modal-body">
			<!-- Campo de texto ineditável com o Turno -->
			<div class="form-group">
				<label for="selected-turno">Turno</label>
				<input type="text" id="selected-turno" readonly>
			</div>
			
			<!-- Seção de Filtros -->
			<div class="filter-section">
				<!-- Primeira linha: Checkboxes lado a lado -->
				<div class="form-row">
					<!-- Checkbox Turmas -->
					<div class="form-group inline-checkbox">
						<label for="chk-turmas-turno" class="radio-like">
							<input type="checkbox" id="chk-turmas-turno">
							<span><strong>Turmas</strong></span>
						</label>
					</div>

					<!-- Checkbox Professores -->
					<div class="form-group inline-checkbox">
						<label for="chk-prof-restricao-turno" class="radio-like">
							<input type="checkbox" id="chk-prof-restricao-turno">
							<span><strong>Professores </strong></span>
						</label>
					</div>
				</div>

				<!-- Segunda linha: Select de Professores (apenas se o checkbox "Turmas" for marcado) -->
				<div class="form-row" id="professor-select-row-turno" style="display: none;">
					<div class="form-group">
						<label for="select-prof-restricao-turno">Selecione o professor:</label>
						<select id="select-prof-restricao-turno">
							<option value="todas">Todos</option>
							<!-- Será populado via AJAX -->
						</select>
					</div>
				</div>
			</div>
		</div>
		
		<div class="modal-footer">
			<button id="btn-imprimir-turno"><i class="fa-solid fa-print"></i>Imprimir</button>
			<button id="btn-cancelar-turno">Cancelar</button>
		</div>
	</div>
</div>

<!-- MODAL DE IMPRESSÃO GERAL DO TURNO -->
<div id="modal-print-turno-geral" class="modal">
	<div class="modal-content">
		<div class="modal-header">
			<h2>Imprimir Turno - Geral</h2>
			<span class="close-modal" id="close-print-modal-turno-geral">&times;</span>
		</div>
		<div class="modal-body">
			<!-- Seção de Filtros (mesmo padrão do modal individual) -->
			<div class="filter-section">
				<div class="form-row">
					<!-- Checkbox Turmas -->
					<div class="form-group inline-checkbox">
						<label for="chk-turmas-turno-geral" class="radio-like">
							<input type="checkbox" id="chk-turmas-turno-geral">
							<span><strong>Turmas</strong></span>
						</label>
					</div>
					<!-- Checkbox Professores -->
					<div class="form-group inline-checkbox">
						<label for="chk-prof-restricao-turno-geral" class="radio-like">
							<input type="checkbox" id="chk-prof-restricao-turno-geral">
							<span><strong>Professores</strong></span>
						</label>
					</div>
				</div>
				<!-- Segunda linha: Select de Professores (apenas se "Turmas" for marcado) -->
				<div class="form-row" id="professor-select-row-turno-geral" style="display: none;">
					<div class="form-group">
						<label for="select-prof-restricao-turno-geral">Selecione o professor:</label>
						<select id="select-prof-restricao-turno-geral">
							<option value="todas">Todos</option>
							<!-- Será populado via AJAX, considerando os níveis permitidos -->
						</select>
					</div>
				</div>
			</div>
		</div>
		<div class="modal-footer">
			<button id="btn-imprimir-turno-geral-confirm"><i class="fa-solid fa-print"></i>Imprimir</button>
			<button id="btn-cancelar-turno-geral">Cancelar</button>
		</div>
	</div>
</div>

<script src="<?php echo JS_PATH; ?>/script.js"></script>
<script src="<?php echo JS_PATH; ?>/turno.js"></script>
<script src="<?php echo JS_PATH; ?>/turno-dias.js"></script>

</body>
</html>
