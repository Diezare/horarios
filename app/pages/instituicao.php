<?php 
// app/pages/instituicao.php 
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
		<h1>Instituição</h1>
		<button id="btn-add" class="btn-add"> <i class="fa-solid fa-plus"></i> Adicionar </button>
	</div>
	<ul class="breadcrumbs">
		<li>Administração</li>
		<li> / </li>
		<li>Instituição</li>
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
							<!-- Título com as setas de ordenação -->
							<th>Instituição
								<span class="sort-icons">
									<i id="sort-asc" class="fa-solid fa-sort-up"></i>
									<i id="sort-desc" class="fa-solid fa-sort-down"></i>
								</span>
							</th>							
							<th>Ações</th>
						</tr>
					</thead>
					<tbody id="instituicaoTable"></tbody>
				</table>
				<div id="no-data-message" class="no-data-message">
					Nenhuma instituição cadastrada.
				</div>
			</div>
		</div>
	</div>
</main>

<!-- MODAL (Adicionar/Editar) -->
<div id="modal-instituicao" class="modal">
	<div class="modal-content">
		<div class="modal-header">
			<h2 id="modal-title">Adicionar Instituição</h2>
			<span class="close-modal" id="close-modal">&times;</span>
		</div>
		<div class="modal-body">
			<input type="hidden" id="instituicaoId">

			<!-- Primeira linha: Nome e CNPJ -->
			<div class="form-row">
				<div class="form-group">
					<label for="nome_instituicao">Nome *</label>
					<input type="text" id="nome_instituicao" placeholder="Nome da instituição" maxlength="255">
				</div>
				<div class="form-group">
					<label for="cnpj_instituicao">CNPJ *</label>
					<input type="text" id="cnpj_instituicao" placeholder="xx.xxx.xxx/0001-xx" maxlength="18">
				</div>
			</div>

			<!-- Segunda linha: Endereço (coluna inteira) -->
			<div class="form-row">
				<div class="form-group" style="flex: 1;">
					<label for="endereco_instituicao">Endereço *</label>
					<input type="text" id="endereco_instituicao" placeholder="Endereço completo" maxlength="255">
				</div>
			</div>

			<!-- Terceira linha: Telefone e Email -->
			<div class="form-row">
				<div class="form-group">
					<label for="telefone_instituicao">Telefone *</label>
					<input type="text" id="telefone_instituicao" placeholder="(xx) xxxxx-xxxx" maxlength="20">
				</div>
				<div class="form-group">
					<label for="email_instituicao">E-mail *</label>
					<input type="email" id="email_instituicao" placeholder="exemplo@email.com">
				</div>
			</div>
			
			<!-- Quarta linha: Imagem (coluna inteira) -->
			<div class="form-row">
				<div class="form-group" style="flex: 1;">
					<label for="imagem_instituicao">Imagem da Instituição (máx. 1MB)</label>
					<input type="file" id="imagem_instituicao" name="imagem_instituicao" accept="image/*" onchange="validarImagem(this)">
					<!-- Container para exibir o preview da logo -->
					<label for="imagem_instituicao" style="margin-top:30px;">Imagem</label>
					<div id="logo-preview" style="margin-top:30px;"></div>
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

<!-- MODAL DE IMPRESSÃO -->
<div id="modal-print" class="modal">
	<div class="modal-content">
		<div class="modal-header">
			<h2>Imprimir Instituição</h2>
			<span class="close-modal" id="close-print-modal">&times;</span>
		</div>
		<div class="modal-body">
			<!-- Campo de texto ineditável com a Instituiçãoo -->
			<div class="form-group">
				<label for="selected-insti">Instituição</label>
				<input type="text" id="selected-insti" readonly>
			</div>			
		</div>
		<div class="modal-footer">
			<button id="btn-imprimir"><i class="fa-solid fa-print"></i>Imprimir</button>
			<button id="btn-cancelar">Cancelar</button>
		</div>
	</div>
</div>

<script src="<?php echo JS_PATH; ?>/script.js"></script>
<script src="<?php echo JS_PATH; ?>/instituicao.js"></script>
</body>
</html>
