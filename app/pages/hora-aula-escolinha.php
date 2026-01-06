<?php
// /app/pages/hora-aula-escolinha.php
require_once __DIR__ . '/../../configs/init.php';
include_once APP_SERVER_PATH . '/models/header.php';
?>
<main>
	<div class="page-header">
		<h1>Hora-Aula de Escolinha</h1>
		<button id="btn-add" class="btn-add">
			<i class="fa-solid fa-plus"></i> Adicionar
		</button>
	</div>
	<ul class="breadcrumbs">
		<li>Gestão Esportiva</li>
		<li> / </li>
		<li>Hora-Aula de Escolinha</li>
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
							<th>Ano Letivo
								<span class="sort-icons">
									<i class="fa-solid fa-sort-up" id="sort-ano-asc"></i>
									<i class="fa-solid fa-sort-down" id="sort-ano-desc"></i>
								</span>
							</th> 

							<th>Modalidade e Categoria
								<span class="sort-icons">
									<i class="fa-solid fa-sort-up" id="sort-modalidade-asc"></i>
									<i class="fa-solid fa-sort-down" id="sort-modalidade-desc"></i>
								</span>
							</th>

							<th>Duração (min)
								<span class="sort-icons">
									<i class="fa-solid fa-sort-up" id="sort-duracao-asc"></i>
									<i class="fa-solid fa-sort-down" id="sort-duracao-desc"></i>
								</span>
							</th>

							<th>Tolerância Quebra</th>
							<th>Status</th>
							
							<th>Ações</th>
						</tr>
					</thead>
					<tbody id="horaAulaTable"></tbody>
				</table>
				<div id="no-data-message" class="no-data-message">
					Não há nenhuma informação cadastrada.
				</div>
			</div>
		</div>
	</div>
</main>

<!-- MODAL DE CADASTRO/EDIÇÃO -->
<div id="modal-hora-aula-escolinha" class="modal">
	<div class="modal-content">
		<div class="modal-header">
			<h2 id="modal-title">Configurar Hora/Aula de Escolinha</h2>
			<span class="close-modal" id="close-modal">&times;</span>
		</div>
		<div class="modal-body">
			<input type="hidden" id="horaAulaId">
			
			<!-- Primeira linha: Ano e minutos de aula -->
			<div class="form-row">
				<div class="form-group">
					<label for="select-ano-letivo-modal">Ano Letivo *</label>
					<select id="select-ano-letivo-modal">
						<!-- Preenchido via AJAX (listAnoLetivo.php) -->
					</select>
				</div>
				
				<div class="form-group">
					<label for="duracao-aula">Duração da Aula (min.) *</label>
					<input 
						type="number"
						id="duracao-aula"
						placeholder="Ex: 50"
						required
						min="1"
						max="300"
						value="50"
					>
				</div>
			</div>
			
			<div class="form-group">
				<div class="modalidade-header">
					<label for="select-modalidades">Modalidades e Categorias</label>
					<div class="selection-buttons">
						<button type="button" id="btn-selecionar-todas">Selecionar Todas</button>
						<button type="button" id="btn-limpar-selecao">Limpar Seleção</button>
					</div>
				</div>
				<div id="select-modalidades" class="modalidades-list">
					<!-- Preenchido via AJAX (listModalidadeCategoria.php) -->
				</div>
			</div>

			<div class="form-group">
				<label for="tolerancia-quebra" class="radio-like">
					<hr style="margin-bottom: 20px;">
					<input type="checkbox" id="tolerancia-quebra" checked>
					<span><strong>Permitir Tolerância de Quebra</strong></span>
				</label>
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
			<p>Deseja realmente excluir esta configuração de hora aula?</p>
		</div>
		<div class="modal-footer">
			<button id="confirm-delete-btn">Sim, excluir</button>
			<button id="cancel-delete-btn">Cancelar</button>
		</div>
	</div>
</div>

<!-- MODAL DE IMPRESSÃO (INDIVIDUAL) -->
<div id="modal-print-hora-aula" class="modal">
	<div class="modal-content">
		<div class="modal-header">
			<h2>Imprimir Configuração</h2>
			<span class="close-modal" id="close-print-hora-aula">&times;</span>
		</div>
		<div class="modal-body">
			<div class="form-group">
				<label for="selected-configuracao">Dados</label>
				<input type="text" id="selected-configuracao" readonly>
			</div>
		</div>
		<div class="modal-footer">
			<button id="btn-imprimir"><i class="fa-solid fa-print"></i>Imprimir</button>
			<button id="btn-cancelar">Cancelar</button>
		</div>
	</div>
</div>

<!-- MODAL DE IMPRESSÃO GERAL -->
<div id="modal-print-hora-aula-geral" class="modal">
	<div class="modal-content">
		<div class="modal-header">
			<h2>Imprimir Todos</h2>
			<span class="close-modal" id="close-print-hora-aula-geral">&times;</span>
		</div>
		<div class="modal-body">
			<!-- Selecionar Ano Letivo ESPECÍFICO ou Todos -->
			<div class="filter-section">
				<div class="form-row">
					<!-- Selecionar Ano Letivo ESPECÍFICO ou Todos -->
					<div class="form-group">
						<label for="select-ano-geral">Ano Letivo</label>
						<select id="select-ano-geral">
							<option value="todos">Todos</option>
						</select>
					</div>

					<div class="form-group">
						<label for="select-apenas-ativas-geral">Tolerância Ativa</label>
						<select id="select-apenas-ativas-geral">
							<option value="todos">Todos</option>
							<option value="sim">Sim</option>
							<option value="nao">Não</option>
						</select>
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
<script src="<?php echo JS_PATH; ?>/hora-aula-escolinha.js"></script>
</body>
</html>