<?php
// /app/pages/evento.php
require_once __DIR__ . '/../../configs/init.php';
include_once APP_SERVER_PATH . '/models/header.php';
?>
<main>
	<div class="page-header">
		<h1>Eventos do Calendário Escolar</h1>
		<button id="btn-add-evento" class="btn-add">
			<i class="fa-solid fa-plus"></i> Adicionar
		</button>
	</div>
	<ul class="breadcrumbs">
		<li>Administração</li>
		<li> / </li>
		<li>Eventos</li>
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
									<i class="fa-solid fa-sort-up" id="sort-ano-letivo-asc"></i>
									<i class="fa-solid fa-sort-down" id="sort-ano-letivo-desc"></i>
								</span>
							</th>

							<th>Tipo
								<span class="sort-icons">
									<i class="fa-solid fa-sort-up" id="sort-tipo-asc"></i>
									<i class="fa-solid fa-sort-down" id="sort-tipo-desc"></i>
								</span>
							</th>

							<th>Nome do Evento
								<span class="sort-icons">
									<i class="fa-solid fa-sort-up" id="sort-nome-asc"></i>
									<i class="fa-solid fa-sort-down" id="sort-nome-desc"></i>
								</span>
							</th>

							<th>Data Início
								<span class="sort-icons">
									<i class="fa-solid fa-sort-up" id="sort-data-asc"></i>
									<i class="fa-solid fa-sort-down" id="sort-data-desc"></i>
								</span>
							</th>

							<th>Data Fim</th>
							
							<th>Ações</th>
						</tr>
					</thead>
					<tbody id="eventoTable"></tbody>
				</table>
				<div id="no-data-message" class="no-data-message">
					Não há nenhuma informação cadastrada.
				</div>
			</div>
		</div>
	</div>
</main>

<!-- MODAL DE CADASTRO/EDIÇÃO -->
<div id="modal-evento" class="modal">
	<div class="modal-content">
		<div class="modal-header">
			<h2 id="modal-title">Adicionar Evento</h2>
			<span class="close-modal" id="close-modal">&times;</span>
		</div>
		<div class="modal-body">
			<input type="hidden" id="eventoId">
			
			<!-- Ano Letivo e Evento -->
			<div class="form-row">
				<div class="form-group">
					<label for="selecionarAnoLetivo">Ano Letivo *</label>
					<select id="selecionarAnoLetivo">
						<!-- Preenchido via AJAX (listAnoLetivo.php) -->
					</select>
				</div>

				<div class="form-group">
					<label for="tipoEvento">Tipo de Evento *</label>
					<select id="tipoEvento">
						<option value="feriado">Feriado</option>
						<option value="recesso">Recesso</option>
						<option value="ferias">Férias</option>
					</select>
				</div>
			</div>
			
			<div class="form-group">
				<label for="nomeEvento">Nome do Evento *</label>
				<input 
					type="text"
					id="nomeEvento"
					placeholder="Digite o nome do evento"
					required
					maxlength="100"
				>
			</div>

			<!-- data de inicio e data final do evento -->
			<div class="form-row">
				<div class="form-group">
					<label for="dataInicio">Data de Início *</label>
					<input type="date" id="dataInicio" required>
				</div>
				<div class="form-group">
					<label for="dataFim">Data de Fim *</label>
					<input type="date" id="dataFim" required>
				</div>
			</div>

			<div class="form-group">
				<label for="observacoes">Observações</label>
				<textarea id="observacoes" rows="3" placeholder="Digite observações sobre o evento (opcional)"></textarea>
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
			<p>Deseja realmente excluir este evento?</p>
		</div>
		<div class="modal-footer">
			<button id="confirm-delete-btn">Sim, excluir</button>
			<button id="cancel-delete-btn">Cancelar</button>
		</div>
	</div>
</div>

<!-- MODAL DE IMPRESSÃO (INDIVIDUAL) -->
<div id="modal-print-evento" class="modal">
	<div class="modal-content">
		<div class="modal-header">
			<h2>Imprimir Evento</h2>
			<span class="close-modal" id="close-print-evento">&times;</span>
		</div>
		<div class="modal-body">
			<div class="form-group">
				<label for="selected-evento">Evento</label>
				<input type="text" id="selected-evento" readonly>
			</div>
			
		</div>
		<div class="modal-footer">
			<button id="btn-imprimir"><i class="fa-solid fa-print"></i>Imprimir</button>
			<button id="btn-cancelar">Cancelar</button>
		</div>
	</div>
</div>

<!-- MODAL DE IMPRESSÃO GERAL -->
<div id="modal-print-evento-geral" class="modal">
	<div class="modal-content">
		<div class="modal-header">
			<h2>Imprimir Eventos - Geral</h2>
			<span class="close-modal" id="close-print-evento-geral">&times;</span>
		</div>
		<div class="modal-body">
			<!-- Ano Letivo e Evento -->
			<div class="form-row">
				<div class="form-group">
					<label for="select-ano-letivo-geral">Ano Letivo *</label>
					<select id="select-ano-letivo-geral">
						<!-- Preenchido via AJAX -->
					</select>
				</div>
				<div class="form-group">
					<label for="tipoEventoFiltro">Tipo de Evento *</label>
						<select id="tipoEventoFiltro">
							<option value="todos">Todos</option> <!-- Corrigido aqui -->
							<option value="feriado">Feriado</option>
							<option value="recesso">Recesso</option>
							<option value="ferias">Férias</option>
						</select>
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
<script src="<?php echo JS_PATH; ?>/evento.js"></script>
</body>
</html>