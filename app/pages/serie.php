<?php
// app/pages/serie.php 
require_once __DIR__ . '/../../configs/init.php';
include_once APP_SERVER_PATH . '/models/header.php';
?>
<main>
	<div class="page-header">
		<h1>Séries</h1>
		<button id="btn-add-serie" class="btn-add">
			<i class="fa-solid fa-plus"></i> Adicionar
		</button>
	</div>

	<ul class="breadcrumbs">
		<li>Cadastros Gerais</li>
		<li> / </li>
		<li>Séries</li>
	</ul>
	
	<!-- CARD-HEADER COM FILTROS + BOTÃO IMPRIMIR -->
	<div class="card-header">
		<div class="form-group">
			<div class="card-title"><h3>Pesquisar</h3></div>
		</div>

		<div class="card-search">
			<input type="text" id="search-input" placeholder="Pesquisar...">
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
							<th>Série
								<span class="sort-icons">
									<i class="fa-solid fa-sort-up" id="sort-serie-asc"></i>
									<i class="fa-solid fa-sort-down" id="sort-serie-desc"></i>
								</span>
							</th>
							
							<th>Nível de Ensino
								<span class="sort-icons">
									<i class="fa-solid fa-sort-up" id="sort-nivel-asc"></i>
									<i class="fa-solid fa-sort-down" id="sort-nivel-desc"></i>
								</span>
							</th>

							<th>Ações</th>
						</tr>
					</thead>
					<tbody id="serieTable"></tbody>
				</table>
				<div id="no-data-message-serie" style="display: none; text-align: center; margin-top: 10px;">
					Não há nenhuma série cadastrada.
				</div>
			</div>
		</div>
	</div>
</main>

<!-- MODAL: Adicionar/Editar Série -->
<div id="modal-serie" class="modal">
	<div class="modal-content">
		<div class="modal-header">
			<h2 id="modal-serie-title">Adicionar Série</h2>
			<span class="close-modal" id="close-serie-modal">&times;</span>
		</div>
		<div class="modal-body">
			<input type="hidden" id="serieId">
			<div class="form-group">
				<label for="nivelEnsino">Nível de Ensino *</label>
				<select id="nivelEnsino">
					<!-- Options serão preenchidas via JS (via listNivelEnsino.php) -->
				</select>
			</div>

			<!-- Segunda linha: Nome da Série e Total de Aulas na Semana -->
			<div class="form-row">
				<div class="form-group">
					<label for="nomeSerie">Nome da Série *</label>
					<input type="text" id="nomeSerie" placeholder="Ex: 1º Ano, 2º Ano..." maxlength="50" required>
				</div>
				<div class="form-group">
					<label for="totalAulasSemana">Total de Aulas Semanais *</label>
					<input type="number" id="totalAulasSemana" placeholder="Ex: 20" required min="0">
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
<div id="modal-delete-serie" class="modal">
	<div class="modal-content delete-modal">
		<div class="modal-header">
			<h2>Confirmação de Exclusão</h2>
			<span class="close-modal" id="close-delete-serie-modal">&times;</span>
		</div>
		<div class="modal-body">
			<p>Deseja realmente excluir esta série?</p>
		</div>
		<div class="modal-footer">
			<button id="confirm-delete-btn">Sim, excluir</button>
			<button id="cancel-delete-btn">Cancelar</button>
		</div>
	</div>
</div>

<!-- MODAL: Vincular Disciplinas -->
<div id="modal-serie-disciplina" class="modal">
	<div class="modal-content">
		<div class="modal-header">
			<h2>Vincular Disciplinas</h2>
			<span class="close-modal" id="close-serie-disciplina-modal">&times;</span>
		</div>
		<div class="modal-body">
			<!-- Nome da Série (read-only) -->
			<div class="form-group">
				<label for="nome-serie-disciplina">Nome da Série</label>
				<input type="text" id="nome-serie-disciplina" readonly>
				<!-- hidden com o ID da série -->
				<input type="hidden" id="select-serie-disciplina">
			</div>
			<div class="form-group">
				<label>Disciplina e Quantidade de Aulas na Semana</label>
				<!-- Checkboxes para disciplinas (3 colunas – use o CSS já definido para .checkbox-grid) -->
				<div id="disciplinas-checkboxes" class="checkbox-grid">
					<!-- Checkboxes serão preenchidos via JS -->
				</div>
			</div>

		</div>
		<div class="modal-footer">
			<button id="save-serie-disciplina-btn">Salvar</button>
			<button id="cancel-serie-disciplina-btn">Cancelar</button>
		</div>
	</div>
</div>

<!-- MODAL DE IMPRESSÃO PARA SÉRIE -->
<div id="modal-print-serie" class="modal">
		<div class="modal-content">
				<div class="modal-header">
						<h2>Imprimir Série</h2>
						<span class="close-modal" id="close-print-serie">&times;</span>
				</div>
				<div class="modal-body">
						<!-- Linha com duas colunas: Série e Total de Aulas -->
						<div class="form-row" style="display: flex; gap: 10px;">
								<div class="form-group" style="flex: 1;">
										<label for="selected-serie">Série</label>
										<input type="text" id="selected-serie" readonly>
								</div>
								<div class="form-group" style="flex: 1;">
										<label for="selected-total">Total de Aulas</label>
										<input type="text" id="selected-total" readonly>
								</div>
						</div>
						<!-- Linha separada para Nível de Ensino -->
						<div class="form-group">
								<label for="selected-nivel">Nível de Ensino</label>
								<input type="text" id="selected-nivel" readonly>
						</div>
						<!-- Filtros -->
						<div class="filter-section">
								<div class="form-row">
										<div class="form-group inline-checkbox">
												<label for="chk-turmas-serie" class="radio-like">
														<input type="checkbox" id="chk-turmas-serie">
														<span><strong>Turmas</strong></span>
												</label>
										</div>
										<div class="form-group inline-checkbox">
												<label for="chk-disc-serie" class="radio-like">
														<input type="checkbox" id="chk-disc-serie">
														<span><strong>Disciplinas</strong></span>
												</label>
										</div>
								</div>
						</div>
				</div>
				<div class="modal-footer">
						<button id="btn-imprimir"><i class="fa-solid fa-print"></i>Imprimir</button>
						<button id="btn-cancelar">Cancelar</button>
				</div>
		</div>
</div>

<!-- MODAL DE IMPRESSÃO GERAL PARA SÉRIE -->
<div id="modal-print-serie-geral" class="modal">
	<div class="modal-content">
		<div class="modal-header">
			<h2>Imprimir Série - Geral</h2>
			<span class="close-modal" id="close-print-serie-geral">&times;</span>
		</div>
		<div class="modal-body">
			<!-- Dropdown para selecionar Nível de Ensino (IDs com sufixo -geral) -->
			<div class="form-group">
				<label for="select-nivel-geral-serie">Nível de Ensino</label>
				<select id="select-nivel-geral-serie">
					<!-- Options serão preenchidas via AJAX (ex: listNivelEnsinoByUser.php) -->
					<option value="todos">Todos</option>
				</select>
			</div>
			<!-- Filtros: checkboxes para Turmas e Disciplinas (IDs com sufixo -geral) -->
			<div class="filter-section">
				<div class="form-row">
					<div class="form-group inline-checkbox">
						<label for="chk-turmas-serie-geral" class="radio-like">
							<input type="checkbox" id="chk-turmas-serie-geral">
							<span><strong>Turmas</strong></span>
						</label>
					</div>
					<div class="form-group inline-checkbox">
						<label for="chk-disc-serie-geral" class="radio-like">
							<input type="checkbox" id="chk-disc-serie-geral">
							<span><strong>Disciplinas</strong></span>
						</label>
					</div>
				</div>
			</div>
		</div>
		<div class="modal-footer">
			<button id="btn-imprimir-serie-geral-confirm"><i class="fa-solid fa-print"></i>Imprimir</button>
			<button id="btn-cancelar-serie-geral">Cancelar</button>
		</div>
	</div>
</div>

<script src="<?php echo JS_PATH; ?>/script.js"></script>
<script src="<?php echo JS_PATH; ?>/serie.js"></script>
<script src="<?php echo JS_PATH; ?>/serie-disciplina.js"></script>

</body>
</html>
 