<?php
// app/pages/disciplina.php
require_once __DIR__ . '/../../configs/init.php';
include_once APP_SERVER_PATH . '/models/header.php';
?>
<main>
	<div class="page-header">
		<h1>Disciplinas</h1> 
		<button id="btn-add" class="btn-add"> <i class="fa-solid fa-plus"></i> Adicionar </button>
	</div>
 	<ul class="breadcrumbs">
		<li>Cadastros Gerais</li>
		<li> / </li>
		<li>Disciplinas</li>
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
							<th>Disciplina
								<span class="sort-icons">
									<i class="fa-solid fa-sort-up" id="sort-disc-asc"></i>
									<i class="fa-solid fa-sort-down" id="sort-disc-desc"></i>
								</span>
							</th>
							
							<th>Sigla
								<span class="sort-icons">
									<i class="fa-solid fa-sort-up" id="sort-sigla-asc"></i>
									<i class="fa-solid fa-sort-down" id="sort-sigla-desc"></i>
								</span>
							</th>
							
							<th>Ações</th>
						</tr>
					</thead>
					<tbody id="disciplinaTable"></tbody>
				</table>
				<div id="no-data-message" class="no-data-message">
					Não há nenhuma informação cadastrada.
				</div>
			</div>
		</div>
	</div>
</main>

<!-- MODAL (Adicionar/Editar) -->
<div id="modal-disciplina" class="modal">
	<div class="modal-content">
		<div class="modal-header">
			<h2 id="modal-title">Adicionar Disciplina</h2>
			<span class="close-modal" id="close-modal">&times;</span>
		</div>
		<div class="modal-body">
			<input type="hidden" id="disciplinaId">
			
			<div class="form-group">
				<label for="disciplina">Disciplina *</label>
				<!-- Máximo 100 caracteres -->
				<input 
					type="text" 
					id="disciplina" 
					placeholder="Digite a disciplina" 
					required 
					maxlength="100"
				>
			</div>
			
			<div class="form-group">
				<label for="sigla">Sigla *</label>
				<!-- Apenas 3 letras -->
				<input
					type="text"
					id="sigla"
					placeholder="Ex: MAT"
					required
					maxlength="3"
					pattern="[A-Za-z]{3}"
				>
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

<!-- MODAL DE IMPRESSÃO DISCIPLINA -->
<div id="modal-print-disciplina" class="modal">
	<div class="modal-content">
		<div class="modal-header">
			<h2>Imprimir Disciplina</h2>
			<span class="close-modal" id="close-print-modal-disc">&times;</span>
		</div>
		<div class="modal-body">
			
			<!-- Campo de texto ineditável com a Disciplina -->
			<div class="form-group">
				<label for="selected-disciplina">Disciplina</label>
				<input type="text" id="selected-disciplina" readonly>
			</div>
			
			<!-- Seção de Filtros -->
			<div class="filter-section">

				<!-- Linha 1: Nível de Ensino -->
				<div class="form-row">
					<!-- Checkbox -->
					<div class="form-group inline-checkbox">
						<label for="chk-nivel" class="radio-like">
							<input type="checkbox" id="chk-nivel">
							<span><strong>Nível de Ensino</strong></span>
						</label>
					</div>
					<!-- Select (oculto inicialmente) -->
					<div class="form-group">
						<select id="select-nivel" style="display: none;">
							<option value="todas">Todas</option>
						</select>
					</div>
				</div>

				<!-- Linha 2: Professor e Turma -->
				<div class="form-row">
					<!-- Checkbox -->
					<div class="form-group inline-checkbox">
						<label for="chk-prof-dt" class="radio-like">
							<input type="checkbox" id="chk-prof-dt">
							<span><strong>Professor e Turma</strong></span>
						</label>
					</div>
					<!-- Select (oculto inicialmente) -->
					<div class="form-group">
						<select id="select-prof-dt" style="display: none;">
							<option value="todas">Todos</option>
						</select>
					</div>
				</div>

			</div>
			<!-- /filter-section -->
		</div>
		<!-- /modal-body -->

		<div class="modal-footer">
			<button id="btn-imprimir"><i class="fa-solid fa-print"></i>Imprimir</button>
			<button id="btn-cancelar">Cancelar</button>
		</div>
	</div>
</div>

<!-- MODAL DE IMPRESSÃO GERAL (SEM O CAMPO DE DISCIPLINA) -->
<div id="modal-print-disciplina-geral" class="modal">
	<div class="modal-content">
		<div class="modal-header">
			<h2>Imprimir Geral de Disciplinas</h2>
			<span class="close-modal" id="close-print-modal-disc-geral">&times;</span>
		</div>
		<div class="modal-body">
			<!-- Filtros iguais: Nível de Ensino / Professor e Turma -->
			<div class="filter-section">
				<div class="form-row">
					<div class="form-group inline-checkbox">
						<label for="chk-nivel-geral" class="radio-like">
							<input type="checkbox" id="chk-nivel-geral">
							<span><strong>Nível de Ensino</strong></span>
						</label>
					</div>
					<div class="form-group">
						<select id="select-nivel-geral" style="display: none;">
							<option value="todas">Todas</option>
						</select>
					</div>
				</div>

				<div class="form-row">
					<div class="form-group inline-checkbox">
						<label for="chk-prof-dt-geral" class="radio-like">
							<input type="checkbox" id="chk-prof-dt-geral">
							<span><strong>Professor e Turma</strong></span>
						</label>
					</div>
					<div class="form-group">
						<select id="select-prof-dt-geral" style="display: none;">
							<option value="todas">Todos</option>
						</select>
					</div>
				</div>
			</div>
			<!-- /filter-section -->
		</div>
		<div class="modal-footer">
			<button id="btn-imprimir-geral-disc"><i class="fa-solid fa-print"></i>Imprimir</button>
			<button id="btn-cancelar-geral-disc">Cancelar</button>
		</div>
	</div>
</div>

<script src="<?php echo JS_PATH; ?>/script.js"></script>
<script src="<?php echo JS_PATH; ?>/disciplina.js"></script>
</body>
</html>
