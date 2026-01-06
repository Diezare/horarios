<?php
	require_once __DIR__ . '/../../configs/init.php';
	include_once APP_SERVER_PATH . '/models/header.php';
?>
<!-- CONTEÚDO PRINCIPAL -->
<main>
	<div class="page-header">
		<h1>Ano Letivo</h1>
		<button id="btn-add" class="btn-add"> <i class="fa-solid fa-plus"></i> Adicionar </button>
	</div>

	<!-- Card com Título e Barra de Pesquisa -->
	<div class="card-header">
		<div class="card-title">
			<h3>Pesquisar Ano Letivo</h3>
		</div>
		<!-- Barra de pesquisa que filtra os registros em tempo real -->
		<div class="card-search">
			<input type="text" id="search-input" placeholder="Pesquisar...">
		</div>
	</div>

	<div class="data">
		<div class="content-data">
			<div class="table-data">
				<table>
					<thead>
						<tr>
							<th>Ano Letivo</th>
							<th>Ações</th>
						</tr>
					</thead>
					<tbody id="anoLetivoTable">
						<tr data-id="1">
							<td>2025</td>
							<td>
								<button class="btn-edit" data-id="1" title="Editar">
								<span class="icon"><i class="fa-solid fa-pen-to-square"></i></span>
									<span class="text">Editar</span>
								</button>
								<button class="btn-delete" data-id="1" title="Deletar">
									<span class="icon"><i class="fa-solid fa-trash"></i></span>
									<span class="text">Deletar</span>
								</button>
								<button class="btn-print" data-id="1" title="Imprimir">
									<span class="icon"><i class="fa-solid fa-print"></i></span>
									<span class="text">Imprimir</span>
								</button>
							</td>
						</tr>
						<tr data-id="2">
							<td>2024</td>
							<td>
								<button class="btn-edit" data-id="1">Editar</button>
								<button class="btn-delete" data-id="1">Deletar</button>
								<button class="btn-print" data-id="1">Imprimir</button>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</main>
<!-- FIM DO CONTEÚDO PRINCIPAL -->

<!-- MODAL PARA ADICIONAR/EDITAR ANO LETIVO -->
<div id="modal-anoLetivo" class="modal">
	<div class="modal-content">
		<!-- Cabeçalho do Modal -->
		<div class="modal-header">
			<h2 id="modal-title">Adicionar Ano Letivo</h2>
			<!-- Botão "X" para fechar -->
			<span class="close-modal" id="close-modal">&times;</span>
		</div>
		<!-- Corpo do Modal -->
		<div class="modal-body">
			<input type="hidden" id="anoLetivoId">
			<div class="form-group">
				<label for="ano">Ano Letivo</label>
				<input type="number" id="ano" placeholder="Digite o ano" oninput="if(this.value.length > 4){this.value = this.value.slice(0,4)}" required>
			</div>
			<div class="form-group">
				<label for="data_inicio">Data de Início</label>
				<input type="date" id="data_inicio" required>
			</div>
			<div class="form-group">
				<label for="data_fim">Data de Encerramento</label>
				<input type="date" id="data_fim" required>
			</div>
		</div>
			<!-- Rodapé do Modal -->
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

	<script src="<?php echo JS_PATH; ?>/script.js"></script>
	<script src="<?php echo JS_PATH; ?>/ano-letivo.js"></script>
</body>
</html>
