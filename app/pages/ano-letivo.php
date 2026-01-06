<?php
require_once __DIR__ . '/../../configs/init.php';
include_once APP_SERVER_PATH . '/models/header.php';
?>
<main>
	<div class="page-header">
		<h1>Anos Letivos</h1>
		<button id="btn-add" class="btn-add"> 
			<i class="fa-solid fa-plus"></i> Adicionar
		</button>
	</div>

	<ul class="breadcrumbs">
		<li>Cadastros Gerais</li>
		<li> / </li>
		<li>Anos Letivos</li>
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
		<button id="btnImprimir">Imprimir Geral</button>-->
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
									<i id="sort-asc" class="fa-solid fa-sort-up"></i>
									<i id="sort-desc" class="fa-solid fa-sort-down"></i>
								</span>
							</th>

							<th>Ações </th>
						</tr>
					</thead>
					<tbody id="anoLetivoTable"></tbody>
				</table>
				<div id="no-data-message" class="no-data-message">
					Nenhum dado cadastrado.
				</div>
			</div>
		</div>
	</div>
</main>

<!-- MODAL (Adicionar/Editar) -->
<div id="modal-anoLetivo" class="modal">
	<div class="modal-content">
		<div class="modal-header">
			<h2 id="modal-title">Adicionar Ano Letivo</h2>
			<span class="close-modal" id="close-modal">&times;</span>
		</div>
		<div class="modal-body">
			<input type="hidden" id="anoLetivoId">
			
			<!-- Campo ano Letivo com pattern e maxlength -->
			<div class="form-group">
				<label for="ano">Ano Letivo *</label>
				<input type="text" 
						 id="ano" 
						 placeholder="Ex: 2025" 
						 required 
						 pattern="\d{4}" 
						 maxlength="4" 
						 title="Digite o ano letivo">
			</div>

			<!-- Segunda linha: Data inicial e final -->
			<div class="form-row">
				<div class="form-group">
					<label for="data_inicio">Data de Início *</label>
					<input type="date" id="data_inicio" required>
				</div>
				<div class="form-group">
					<label for="data_fim">Data de Encerramento *</label>
					<input type="date" id="data_fim" required>
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
	 
<!-- MODAL DE IMPRESSÃO (INDIVIDUAL) -->
<div id="modal-print" class="modal">
	<div class="modal-content">
		<div class="modal-header">
			<h2>Imprimir Ano Letivo</h2>
			<span class="close-modal" id="close-print-modal">&times;</span>
		</div>
		<div class="modal-body">
			<!-- Campo de texto ineditável com o Ano Letivo -->
			<div class="form-group">
				<label for="selected-ano">Ano Letivo</label>
				<input type="text" id="selected-ano" readonly>
			</div>
			
			<!-- Seção de Filtros -->
			<div class="filter-section">
				
				<!-- Primeira linha: Checkboxes lado a lado -->
				<div class="form-row">
					<!-- Checkbox Turmas -->
					<div class="form-group inline-checkbox">
						<label for="chk-turmas" class="radio-like">
							<input type="checkbox" id="chk-turmas">
							<span><strong>Turmas</strong></span>
						</label>
					</div>

					<!-- Checkbox Professor Restrição -->
					<div class="form-group inline-checkbox">
						<label for="chk-prof-restricao" class="radio-like">
							<input type="checkbox" id="chk-prof-restricao">
							<span><strong>Professores Restrições</strong></span>
						</label>
					</div>
				</div>

				<!-- Segunda linha: Select de Professores (só aparece se marcar o checkbox) -->
				<div class="form-row" id="professor-select-row" style="display: none;">
					<div class="form-group">
						<label for="select-prof-restricao">Selecione o Professor(a)</label>
						<select id="select-prof-restricao">
							<option value="todas">Todos</option>
						</select>
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

<!-- MODAL DE IMPRESSÃO GERAL (SEM CAMPO DE ANO) -->
<div id="modal-print-geral" class="modal">
	<div class="modal-content">
		<div class="modal-header">
			<h2>Imprimir Todos os Anos Letivos</h2>
			<span class="close-modal" id="close-print-geral-modal">&times;</span>
		</div>
		<div class="modal-body">
			<!-- Seção de Filtros (as mesmas opções, mas sem o ano) -->
			<div class="filter-section">
				
				<!-- Primeira linha: Checkboxes lado a lado -->
				<div class="form-row">
					<!-- Checkbox Turmas -->
					<div class="form-group inline-checkbox">
						<label for="chk-turmas-geral" class="radio-like">
							<input type="checkbox" id="chk-turmas-geral">
							<span><strong>Turmas</strong></span>
						</label>
					</div>

					<!-- Checkbox Professor Restrição -->
					<div class="form-group inline-checkbox">
						<label for="chk-prof-restricao-geral" class="radio-like">
							<input type="checkbox" id="chk-prof-restricao-geral">
							<span><strong>Professores Restrições</strong></span>
						</label>
					</div>
				</div>

				<!-- Segunda linha: Select de Professores (só aparece se marcar o checkbox) -->
				<div class="form-row" id="professor-select-row-geral" style="display: none;">
					<div class="form-group">
						<label for="select-prof-restricao-geral">Selecione o professor:</label>
						<select id="select-prof-restricao-geral">
							<option value="todas">Todos</option>
						</select>
					</div>
				</div>

			</div>
		</div>
		<div class="modal-footer">
			<button id="btn-imprimir-geral"><i class="fa-solid fa-print"></i>Imprimir</button>                
			<button id="btn-cancelar-geral">Cancelar</button>
		</div>
	</div>
</div>

<script src="<?php echo JS_PATH; ?>/script.js"></script>
<script src="<?php echo JS_PATH; ?>/ano-letivo.js"></script>
</body>
</html>
