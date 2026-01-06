<?php
// /app/pages/categoria.php
require_once __DIR__ . '/../../configs/init.php';
include_once APP_SERVER_PATH . '/models/header.php';
?>
<main>
	<div class="page-header">
		<h1>Categorias</h1>
		<button id="btn-add-categoria" class="btn-add">
			<i class="fa-solid fa-plus"></i> Adicionar
		</button>
	</div>
	<ul class="breadcrumbs">
		<li>Gestão Esportiva</li>
		<li> / </li>
		<li>Categorias</li>
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
									<i class="fa-solid fa-sort-up" id="sort-modalidade-asc"></i>
									<i class="fa-solid fa-sort-down" id="sort-modalidade-desc"></i>
								</span>
							</th> 

							<th>Categoria 
								<span class="sort-icons">
									<i class="fa-solid fa-sort-up" id="sort-categoria-asc"></i>
									<i class="fa-solid fa-sort-down" id="sort-categoria-desc"></i>
								</span>
							</th>
							
							<th>Ações</th>
						</tr>
					</thead>
					<tbody id="categoriaTable"></tbody>
				</table>
				<div id="no-data-message" class="no-data-message">
					Não há nenhuma informação cadastrada.
				</div>
			</div>
		</div>
	</div>
</main>

<!-- MODAL DE CADASTRO/EDIÇÃO -->
<div id="modal-categoria" class="modal">
	<div class="modal-content">
		<div class="modal-header">
			<h2 id="modal-title">Adicionar Categoria</h2>
			<span class="close-modal" id="close-modal">&times;</span>
		</div>
		<div class="modal-body">
			<input type="hidden" id="categoriaId">
			
			<div class="form-group">
				<label for="selectModalidade">Modalidade *</label>
				<select id="selectModalidade">
					<!-- Preenchido via AJAX (listAllModalidade.php) -->
				</select>
			</div>
			<div class="form-group">
				<label for="nomeCategoria">Nome da Categoria *</label>
				<input 
					type="text"
					id="nomeCategoria"
					placeholder="Digite o nome da categoria"
					required
					maxlength="100"
				>
			</div>
			<div class="form-group">
				<label for="descricaoCategoria">Descrição</label>
				<textarea id="descricaoCategoria" rows="2"></textarea>
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
			<p>Deseja realmente excluir esta categoria?</p>
		</div>
		<div class="modal-footer">
			<button id="confirm-delete-btn">Sim, excluir</button>
			<button id="cancel-delete-btn">Cancelar</button>
		</div>
	</div>
</div>

<!-- MODAL DE IMPRESSÃO (INDIVIDUAL) -->
<div id="modal-print-categoria" class="modal">
	<div class="modal-content">
		<div class="modal-header">
			<h2>Imprimir Categoria</h2>
			<span class="close-modal" id="close-print-categoria">&times;</span>
		</div>
		<div class="modal-body">
			<div class="form-group">
				<label for="selected-categoria">Categoria</label>
				<input type="text" id="selected-categoria" readonly>
			</div>
			
			<!-- Exemplo de checkbox -->
			<div class="filter-section">
				<div class="form-row">
					<div class="form-group inline-checkbox">
						<label for="chk-modalidade" class="radio-like">
							<input type="checkbox" id="chk-modalidade">
							<span><strong>Incluir Modalidade</strong></span>
						</label>
					</div>

					<!-- Novo checkbox "Incluir Professores" se desejar -->
					<div class="form-group inline-checkbox">
						<label for="chk-professores" class="radio-like">
							<input type="checkbox" id="chk-professores">
							<span><strong>Incluir Professores</strong></span>
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

<!-- MODAL DE IMPRESSÃO GERAL -->
<div id="modal-print-categoria-geral" class="modal">
	<div class="modal-content">
		<div class="modal-header">
			<h2>Imprimir Categorias - Geral</h2>
			<span class="close-modal" id="close-print-categoria-geral">&times;</span>
		</div>
		<div class="modal-body">
			<!-- Selecionar Categoria ESPECÍFICA ou Todas -->
			<div class="form-group">
				<label for="select-categoria-geral">Categoria</label>
				<select id="select-categoria-geral">
					<option value="todas">Todas</option>
				</select>
			</div>

			<!-- Checkbox para incluir Modalidade no relatório -->
			<div class="filter-section">
				<div class="form-row">
					<div class="form-group inline-checkbox">
						<label for="chk-modalidade-geral" class="radio-like">
							<input type="checkbox" id="chk-modalidade-geral">
							<span><strong>Incluir Modalidade</strong></span>
						</label>
					</div>

					<!-- Novo checkbox "Incluir Professores" se desejar -->
					<div class="form-group inline-checkbox">
						<label for="chk-professores-geral" class="radio-like">
							<input type="checkbox" id="chk-professores-geral">
							<span><strong>Incluir Professores</strong></span>
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
 
<!-- NOVO MODAL "VINCULAR PROFESSORES" -->
<div id="modal-vincular-professores" class="modal">
	<div class="modal-content">
		<div class="modal-header">
			<h2>Vincular Professores</h2>
			<span class="close-modal" id="close-vincular-prof-modal">&times;</span>
		</div>
		<div class="modal-body">
			<!-- Nome da categoria, somente leitura -->
			<div class="form-group">
				<label for="categoria-nome-readonly">Categoria</label>
				<input type="text" id="categoria-nome-readonly" readonly>
			</div>

			<!-- Select Disciplinas -->
			<div class="form-group">
				<label for="select-disciplina-prof">Disciplina *</label>
				<select id="select-disciplina-prof">
					<!-- preenchido via professor-categoria.js -->
				</select>
			</div>

			<!-- Lista de Professores (2 colunas) -->
			<!-- Cada label com class="radio-like" para estilizar 
			     e "inline-checkbox" se quiser manter o mesmo estilo 
			-->
			<div id="professors-checkbox-container" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
				<!-- gerado via professor-categoria.js -->
			</div>
			
		</div>
		<em>* Campos obrigatórios.</em>
		<div class="modal-footer" style="padding-top: 30px;">
			<button id="btn-save-vinculo-prof">Salvar</button>
			<button id="btn-cancel-vinculo-prof">Cancelar</button>
		</div>
	</div>
</div>

<script src="<?php echo JS_PATH; ?>/script.js"></script>
<script src="<?php echo JS_PATH; ?>/categoria.js"></script>
<script src="<?php echo JS_PATH; ?>/professor-categoria.js"></script>
</body>
</html>
