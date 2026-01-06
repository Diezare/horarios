<?php
// app/pages/nivel-ensino.php
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
		<h1>Níveis de Ensino</h1>
		<button id="btn-add-nivel" class="btn-add">
			<i class="fa-solid fa-plus"></i> Adicionar
		</button>
	</div>
	<ul class="breadcrumbs">
		<li>Cadastros Gerais</li>
		<li> / </li>
		<li>Níveis de Ensino</li>
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
							<th>Nível de Ensino
								<span class="sort-icons">
									<i id="sort-asc" class="fa-solid fa-sort-up"></i>
									<i id="sort-desc" class="fa-solid fa-sort-down"></i>
								</span>
							</th>
							<th>Ações</th>
						</tr>
					</thead>
					<tbody id="nivelEnsinoTable"></tbody>
				</table>
				<div id="no-data-message" class="no-data-message">
					Não há nenhuma informação cadastrada.
				</div>
			</div>
		</div>
	</div>
</main>

<div id="modal-nivel-ensino" class="modal">
	<div class="modal-content">
		<div class="modal-header">
			<h2 id="modal-title">Adicionar Nível de Ensino</h2>
			<span class="close-modal" id="close-modal">&times;</span>
		</div>
		<div class="modal-body">
			<input type="hidden" id="nivelEnsinoId">
			<div class="form-group">
				<label for="nomeNivelEnsino">Nível de Ensino *</label>
				<input 
					type="text"
					id="nomeNivelEnsino"
					placeholder="Digite o nível de ensino"
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

<div id="modal-print-nivel" class="modal">
	<div class="modal-content">
		<div class="modal-header">
			<h2>Imprimir Nível de Ensino</h2>
			<span class="close-modal" id="close-print-nivel">&times;</span>
		</div>
		<div class="modal-body">
			<div class="form-group">
				<label for="selected-nivel">Nível de Ensino</label>
				<input type="text" id="selected-nivel" readonly>
			</div>

			<div class="filter-section" style="margin-top:10px;">
				<div class="form-row">
					<div class="form-group inline-checkbox">
						<label for="chk-series" class="radio-like" style="display: flex; align-items: center;">
							<input type="checkbox" id="chk-series" style="margin-right:8px;">
							<span><strong>Incluir Séries e Turmas</strong></span>
						</label>
					</div>
					<div class="form-group inline-checkbox">
						<label for="chk-usuarios" class="radio-like" style="display: flex; align-items: center;">
							<input type="checkbox" id="chk-usuarios" style="margin-right:8px;">
							<span><strong>Incluir Usuários</strong></span>
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

<!-- MODAL DE IMPRESSÃO GERAL PARA NÍVEL DE ENSINO -->
<div id="modal-print-nivel-geral" class="modal">
	<div class="modal-content">
		<div class="modal-header">
			<h2>Imprimir Nível - Geral</h2>
			<span class="close-modal" id="close-print-nivel-geral">&times;</span>
		</div>
		<div class="modal-body">
			<!-- Dropdown para selecionar Nível de Ensino -->
			<div class="form-group">
				<label for="select-nivel-geral">Nível de Ensino</label>
				<select id="select-nivel-geral">
					<!-- Options serão preenchidas via AJAX (ex: listNivelEnsinoByUser.php) -->
					<option value="todos">Todos</option>
				</select>
			</div>
			<!-- Filtros: checkboxes para Incluir Séries/Turmas e Incluir Usuários -->
			<div class="filter-section">
				<div class="form-row">
					<div class="form-group inline-checkbox">
						<label for="chk-series-geral" class="radio-like">
							<input type="checkbox" id="chk-series-geral">
							<span><strong>Incluir Séries e Turmas</strong></span>
						</label>
					</div>
					<div class="form-group inline-checkbox">
						<label for="chk-usuarios-geral" class="radio-like">
							<input type="checkbox" id="chk-usuarios-geral">
							<span><strong>Incluir Usuários</strong></span>
						</label>
					</div>
				</div>
			</div>
		</div>
		<div class="modal-footer">
			<button id="btn-imprimir-nivel-geral-confirm"><i class="fa-solid fa-print"></i>Imprimir</button>
			<button id="btn-cancelar-nivel-geral">Cancelar</button>
		</div>
	</div>
</div>

<script src="<?php echo JS_PATH; ?>/script.js"></script>
<script src="<?php echo JS_PATH; ?>/nivel-ensino.js"></script>

</body>
</html>
