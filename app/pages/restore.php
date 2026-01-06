<?php
// app/pages/restore.php
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
<!DOCTYPE html>
<html lang="pt-br">
<head>
	<meta charset="UTF-8">
	<title>Restaurar Dados</title>
	<link rel="stylesheet" href="<?php echo CSS_PATH; ?>/style.css">
</head>
<body>
<main>
	<div class="page-header">
		<h1>Restaurar Dados</h1>
		<button id="btn-restore" class="btn-restore">
			<i class="fa-solid fa-upload"></i> Restaurar
		</button>
	</div>

	<ul class="breadcrumbs">
		<li>Segurança</li>
		<li> / </li>
		<li>Restore</li>
	</ul>

	<div class="data">
		<div class="content-data">
			<div class="table-data">
				<table>
					<thead>
						<tr>
							<th>Data e Hora do Restore
								<span class="sort-icons">
									<i id="sort-data-asc" class="fa-solid fa-sort-up sort-btn"></i>
									<i id="sort-data-desc" class="fa-solid fa-sort-down sort-btn"></i>
								</span>
							</th>
							<th>Arquivo Restaurado
								<span class="sort-icons">
									<i id="sort-arquivo-asc" class="fa-solid fa-sort-up sort-btn"></i>
									<i id="sort-arquivo-desc" class="fa-solid fa-sort-down sort-btn"></i>
								</span>
							</th>
						</tr>
					</thead>
					<tbody id="tbodyRestore">
						<!-- Os logs de restauração serão inseridos aqui via JS -->
					</tbody>
				</table>
				<div id="noDataMessage" class="noDataMessage">
					Nenhum dado encontrado.
				</div>
			</div>
		</div>
	</div>

	<!-- CONTROLES DE PAGINAÇÃO (exibidos via JS) -->
	<div id="paginationContainer" style="text-align:center; margin-top:10px; display:none;">
		<button id="prevPageBtn">Anterior</button>
		<span id="paginationStatus"></span>
		<button id="nextPageBtn">Próximo</button>
	</div>
	
</main>

<!-- MODAL DE RESTAURAÇÃO -->
<div id="modal-restore" class="modal" style="display:none;">
	<div class="modal-content">
		<div class="modal-header">
			<h2>Confirmar Restauração</h2>
			<span class="close-modal" id="closeModalRestore">&times;</span>
		</div>
		<div class="modal-body">
			<p>Tem certeza que deseja restaurar os dados do banco? Esse processo substituirá os dados atuais pelo backup selecionado.</p>
			<form id="restoreForm" enctype="multipart/form-data">
				<div class="form-group">
					<label for="restoreFile">Selecione o arquivo (.sql):</label>
					<input type="file" id="restoreFile" name="restoreFile" accept=".sql" required>
				</div>
			</form>
		</div>
		<div class="modal-footer">
			<button id="confirmRestoreBtn">Confirmar</button>
			<button id="cancelRestoreBtn">Cancelar</button>
		</div>
	</div>
</div>

<script src="<?php echo JS_PATH; ?>/script.js"></script>
<script src="<?php echo JS_PATH; ?>/restore.js"></script>
</body>
</html>
