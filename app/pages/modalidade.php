<?php
// /app/pages/modalidade.php
require_once __DIR__ . '/../../configs/init.php';
include_once APP_SERVER_PATH . '/models/header.php';
?>
<main>
	<div class="page-header">
		<h1>Modalidades</h1>
		<button id="btn-add-modalidade" class="btn-add">
			<i class="fa-solid fa-plus"></i> Adicionar
		</button>
	</div>
	<ul class="breadcrumbs">
		<li>Gestão Esportiva</li>
		<li> / </li>
		<li>Modalidades</li>
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
							<th>Modalidade
								<span class="sort-icons">
									<i id="sort-asc" class="fa-solid fa-sort-up"></i>
									<i id="sort-desc" class="fa-solid fa-sort-down"></i>
								</span>
							</th>
							<th>Ações</th>
						</tr>
					</thead>
					<tbody id="modalidadeTable"></tbody>
				</table>
				<div id="no-data-message" class="no-data-message">
					Não há nenhuma informação cadastrada.
				</div>
			</div>
		</div>
	</div>
</main>

<!-- MODAL DE CADASTRO/EDIÇÃO DE MODALIDADE -->
<div id="modal-modalidade" class="modal">
	<div class="modal-content">
		<div class="modal-header">
			<h2 id="modal-title">Adicionar Modalidade</h2>
			<span class="close-modal" id="close-modal">&times;</span>
		</div>
		<div class="modal-body">
			<input type="hidden" id="modalidadeId">
			<div class="form-group">
				<label for="nomeModalidade">Modalidade *</label>
				<input 
					type="text"
					id="nomeModalidade"
					placeholder="Digite o nome da modalidade"
					required
					maxlength="100"
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

<!-- MODAL DE EXCLUSÃO -->
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

<!-- MODAL DE IMPRESSÃO INDIVIDUAL DE MODALIDADE -->
<div id="modal-print-modalidade" class="modal">
	<div class="modal-content">
		<div class="modal-header">
			<h2>Imprimir Modalidade</h2>
			<span class="close-modal" id="close-print-modal">&times;</span>
		</div>
		<div class="modal-body">
			<div class="form-group">
				<label for="selected-modalidade">Modalidade</label>
				<input type="text" id="selected-modalidade" readonly>
			</div>

			<!-- Checkboxes: Categoria, etc. -->
			<div class="filter-section" style="margin-top:10px;">
				<div class="form-row">
					<div class="form-group inline-checkbox">
						<label for="chk-categoria" class="radio-like" style="display: flex; align-items: center;">
							<input type="checkbox" id="chk-categoria" style="margin-right:8px;">
							<span><strong>Incluir Categorias</strong></span>
						</label>
					</div>
					<!-- Se quiser mais checkboxes, siga o padrão -->
				</div>
			</div>
		</div>
		<div class="modal-footer">
			<button id="btn-imprimir"><i class="fa-solid fa-print"></i>Imprimir</button>
			<button id="btn-cancelar">Cancelar</button>
		</div>
	</div>
</div>

<!-- MODAL DE IMPRESSÃO GERAL DE MODALIDADES -->
<div id="modal-print-modalidade-geral" class="modal">
	<div class="modal-content">
		<div class="modal-header">
			<h2>Imprimir Modalidades - Geral</h2>
			<span class="close-modal" id="close-print-geral">&times;</span>
		</div>
		<div class="modal-body">
			<div class="form-group">
				<label for="select-modalidade-geral">Modalidade</label>
				<select id="select-modalidade-geral">
					<!-- Preenchido via AJAX (pode ter valor "todos" ou IDs específicos) -->
					<option value="todos">Todas</option>
				</select>
			</div>

			<!-- Checkboxes para Categoria, etc. -->
			<div class="filter-section" style="margin-top:10px;">
				<div class="form-row">
					<div class="form-group inline-checkbox">
						<label for="chk-categoria-geral" class="radio-like" style="display: flex; align-items: center;">
							<input type="checkbox" id="chk-categoria-geral" style="margin-right:8px;">
							<span><strong>Incluir Categorias</strong></span>
						</label>
					</div>
				</div>
			</div>
		</div>
		<div class="modal-footer">
			<button id="btn-imprimir-geral-confirm"><i class="fa-solid fa-print"></i>Imprimir</button>
			<button id="btn-cancelar-geral">Cancelar</button>
		</div>
	</div>
</div>

<script src="<?php echo JS_PATH; ?>/script.js"></script>
<script src="<?php echo JS_PATH; ?>/modalidade.js"></script>

</body>
</html>
