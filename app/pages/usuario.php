<?php 
// app/pages/usuario.php 
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
	<title>Usuários</title>
	<link rel="stylesheet" href="<?php echo CSS_PATH; ?>/style.css">
</head>
<body>
<main>
	<div class="page-header">
		<h1>Usuário</h1>
		<button id="btn-add" class="btn-add">
			<i class="fa-solid fa-plus"></i> Adicionar
		</button>
	</div>
 	<ul class="breadcrumbs">
		<li>Administração</li>
		<li> / </li>
		<li>Usuários</li>
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
							<th>Nome
								<span class="sort-icons">
									<i class="fa-solid fa-sort-up" id="sort-nome-asc"></i>
									<i class="fa-solid fa-sort-down" id="sort-nome-desc"></i>
								</span>
							</th>
							
							<th>E-mail
								<span class="sort-icons">
									<i class="fa-solid fa-sort-up" id="sort-email-asc"></i>
									<i class="fa-solid fa-sort-down" id="sort-email-desc"></i>
								</span>
							</th>
							
							<th>Situação
								<span class="sort-icons">
									<i class="fa-solid fa-sort-up" id="sort-situacao-asc"></i>
									<i class="fa-solid fa-sort-down" id="sort-situacao-desc"></i>
								</span>
							</th>
														
							<th>Nível
								<span class="sort-icons">
									<i class="fa-solid fa-sort-up" id="sort-nivel-asc"></i>
									<i class="fa-solid fa-sort-down" id="sort-nivel-desc"></i>
								</span>
							</th>
							
							<th>Ações</th>
						</tr>
					</thead>

					<tbody id="usuarioTable"></tbody>
				</table>
				<div id="no-data-message" class="no-data-message">
					Nenhum usuário cadastrado.
				</div>
			</div>
		</div>
	</div>
</main>

<!-- MODAL (Adicionar/Editar Usuário) -->
<div id="modal-usuario" class="modal">
	<div class="modal-content">
		<div class="modal-header">
			<h2 id="modal-title">Adicionar Usuário</h2>
			<span class="close-modal" id="close-modal">&times;</span>
		</div>
		<div class="modal-body">
			<input type="hidden" id="usuarioId">

			<!-- Linha: Nome -->
			<div class="form-group">
				<label for="nome_usuario">Nome *</label>
				<input type="text" id="nome_usuario" placeholder="Nome do usuário" maxlength="50" required>
			</div>

			<!-- Linha: E-mail -->
			<div class="form-group">
				<label for="email_usuario">E-mail *</label>
				<input type="email" id="email_usuario" placeholder="exemplo@email.com"
					   maxlength="100" required>
			</div>

			<!-- Linha: Senha e Confirmar Senha -->
			<div class="form-row">
				<div class="form-group">
					<label for="senha_usuario">Senha *</label>
					<input type="password" id="senha_usuario" placeholder="Senha" required>
				</div>
				<div class="form-group">
					<label for="confirma_senha">Confirmar Senha *</label>
					<input type="password" id="confirma_senha" placeholder="Confirmar senha" required>
				</div>
			</div>

			<!-- Linha: Situação e Nível de Usuário (agora juntos) -->
			<div class="form-row">
				<!-- Coluna 1: Situação -->
				<div class="form-group" style="flex: 1;">
					<label>Situação *</label>
					<div class="radio-group">
						<label style="margin-right: 60px;">
							<input type="radio" name="situacao_usuario" value="Ativo" checked>
							Ativo
						</label>
						<label>
							<input type="radio" name="situacao_usuario" value="Inativo">
							Inativo
						</label>
					</div>
				</div>
				<!-- Coluna 2: Nível de Usuário -->
				<div class="form-group" style="flex: 1;">
					<label for="nivel_usuario">Nível de Usuário *</label>
					<select id="nivel_usuario">
						<option value="">-- Selecione --</option>
						<option value="Administrador">Administrador</option>
						<option value="Usuário">Usuário</option>
					</select>
				</div>
			</div>

			<!-- Linha: Upload da imagem -->
			<div class="form-group">
				<label for="imagem_usuario">Imagem do Perfil (opcional, máx. 1MB)</label>
				<input type="file" id="imagem_usuario" name="imagem_usuario" accept="image/*" onchange="validarImagem(this)">
			</div>

			<!-- Linha: Preview da imagem com botão para deletar -->
			<div class="form-group">
				<div id="perfil-preview" style="margin-top:30px;"></div>
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
			<p>Deseja realmente excluir este usuário?</p>
		</div>
		<div class="modal-footer">
			<button id="confirm-delete-btn">Sim, excluir</button>
			<button id="cancel-delete-btn">Cancelar</button>
		</div>
	</div>
</div> 
 
<!-- MODAL: Vincular Níveis -->
<div id="modal-usuario-nivel" class="modal">
	<div class="modal-content">
		<div class="modal-header">
			<h2>Vincular Níveis</h2>
			<span class="close-modal" id="close-usuario-nivel-modal">&times;</span>
		</div>
		<div class="modal-body">
			<div class="form-group">
				<label for="usuario_nome_vinculo">Nome do Usuário</label>
				<input type="text" id="usuario_nome_vinculo" readonly>
			</div>
			<div class="form-group">
				<label style="display: block; padding-bottom: 10px;">Vincular com quais níveis?</label>
				<div id="niveis-checkboxes" class="form-row">
					<!-- Checkboxes gerados dinamicamente -->
				</div>
			</div>
		</div>
		<div class="modal-footer">
			<button id="save-usuario-nivel-btn">Salvar</button>
			<button id="cancel-usuario-nivel-btn">Cancelar</button>
		</div>
	</div>
</div>

<!-- MODAL DE IMPRESSÃO -->
<div id="modal-print" class="modal">
	<div class="modal-content">
		<div class="modal-header">
			<h2>Imprimir Usuário</h2>
			<span class="close-modal" id="close-print-modal">&times;</span>
		</div>
		<div class="modal-body">
			<div class="form-group">
				<label for="selected-user">Usuário</label>
				<input type="text" id="selected-user" readonly>
			</div>			
		</div>
		<div class="modal-footer">
			<button id="btn-imprimir"><i class="fa-solid fa-print"></i>Imprimir</button>
			<button id="btn-cancelar">Cancelar</button>
		</div>
	</div>
</div>

<!-- MODAL DE IMPRESSÃO GERAL -->
<div id="modal-print-geral" class="modal">
	<div class="modal-content">
		<div class="modal-header">
			<h2>Imprimir Todos os Usuários</h2>
			<span class="close-modal" id="close-print-geral-modal">&times;</span>
		</div>
		<div class="modal-body">
			<p>Deseja imprimir a lista de todos os usuários?</p>
		</div>
		<div class="modal-footer">
			<button id="btn-imprimir-geral-confirm"><i class="fa-solid fa-print"></i>Imprimir</button>
			<button id="btn-cancelar-geral">Cancelar</button>
		</div>
	</div>
</div>


<!-- MODAL DE ALERTA -->
<div id="modal-alert" class="modal" style="display:none;">
	<div class="modal-content">
		<div class="modal-header">
			<h2>Atenção</h2>
			<span class="close-modal" id="close-alert-modal">&times;</span>
		</div>
		<div class="modal-body">
			<p id="alert-message"></p>
		</div>
		<div class="modal-footer">
			<button id="alert-ok-btn">OK</button>
		</div>
	</div>
</div>

<script src="<?php echo JS_PATH; ?>/script.js"></script>
<script src="<?php echo JS_PATH; ?>/usuario.js"></script>
<script src="<?php echo JS_PATH; ?>/usuario-nivel.js"></script>

</body>
</html>
