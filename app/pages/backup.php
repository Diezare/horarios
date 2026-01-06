<?php
// app/pages/backup.php
require_once __DIR__ . '/../../configs/init.php';
include_once APP_SERVER_PATH . '/models/header.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
	<meta charset="UTF-8">
	<title>Backup do Sistema</title>
	<link rel="stylesheet" href="<?php echo CSS_PATH; ?>/style.css">
</head>
<body>
<main>
	<div class="page-header">
		<h1>Backup</h1>
		<button id="btn-bkp" class="btn-bkp">
			<i class="fa-solid fa-floppy-disk"></i> Gerar Backup
		</button>
	</div>

 	<ul class="breadcrumbs">
		<li>Segurança</li>
		<li> / </li>
		<li>Backup</li>
	</ul>

	<div class="data">
		<div class="content-data">
			<div class="table-data">
				<table>
					<thead>
						<tr>
							<th>Data e Hora do Backup
								<span class="sort-icons">
									<i id="sort-data-asc" class="fa-solid fa-sort-up sort-btn"></i>
									<i id="sort-data-desc" class="fa-solid fa-sort-down sort-btn"></i>
								</span>
							</th>
							<th>Arquivo
								<span class="sort-icons">
									<i id="sort-arquivo-asc" class="fa-solid fa-sort-up sort-btn"></i>
									<i id="sort-arquivo-desc" class="fa-solid fa-sort-down sort-btn"></i>
								</span>
							</th>
						</tr>
					</thead>
					<tbody id="tbodyBackup">
						<!-- Os registros de backup serão inseridos aqui via JS -->
					</tbody>
				</table>
				<div id="noDataMessage" style="display: none; text-align: center; margin-top: 10px;">
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

<!-- Exemplo de Modal de Backup (com efeitos, se desejar) -->
<div id="modal-backup" class="modal" style="display:none;">
	<div class="modal-content">
		<div class="modal-header">
			<h2>Backup</h2>
			<span class="close-modal" id="closeModalBackup">&times;</span>
		</div>
		<div class="modal-body">
			<p>Backup gerado com sucesso!</p>
		</div>
		<div class="modal-footer">
			<button id="btn-ok-backup">OK</button>
		</div>
	</div>
</div>

<script src="<?php echo JS_PATH; ?>/script.js"></script>
<script src="<?php echo JS_PATH; ?>/backup.js"></script>
</body>
</html>
