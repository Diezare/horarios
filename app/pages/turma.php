<?php
// app/pages/turma.php
require_once __DIR__ . '/../../configs/init.php';
include_once APP_SERVER_PATH . '/models/header.php';
?>
<main>
	<div class="page-header">
		<h1>Turmas</h1>
		<button id="btn-add-turma" class="btn-add">
			<i class="fa-solid fa-plus"></i> Adicionar
		</button>
	</div>

	<ul class="breadcrumbs">
		<li>Cadastros Gerais</li>
		<li> / </li>
		<li>Turmas</li>
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
									<i class="fa-solid fa-sort-down" id="sort-no-desc"></i>
								</span>
							</th>
							
							<th>Série
								<span class="sort-icons">
									<i class="fa-solid fa-sort-up" id="sort-serie-asc"></i>
									<i class="fa-solid fa-sort-down" id="sort-serie-desc"></i>
								</span>
							</th>
							
							<th>Turma
								<span class="sort-icons">
									<i class="fa-solid fa-sort-up" id="sort-turma-asc"></i>
									<i class="fa-solid fa-sort-down" id="sort-turma-desc"></i>
								</span>
							</th>

							<th>Turno
								<span class="sort-icons">
									<i class="fa-solid fa-sort-up" id="sort-turno-asc"></i>
									<i class="fa-solid fa-sort-down" id="sort-turno-desc"></i>
								</span>
							</th>

							<th>Ações</th>
						</tr>
					</thead>
					<tbody id="turmaTable"></tbody>
				</table>
				<div id="no-data-message-turma" style="display: none; text-align: center; margin-top: 10px;">
					Não há nenhuma turma cadastrada.
				</div>
			</div>
		</div>
	</div>
</main>

<!-- MODAL: Adicionar/Editar Turma -->
<div id="modal-turma" class="modal">
	<div class="modal-content">
		<div class="modal-header">
			<h2 id="modal-turma-title">Adicionar Turma</h2>
			<span class="close-modal" id="close-turma-modal">&times;</span>
		</div>
		<div class="modal-body">
			<input type="hidden" id="turmaId">
			
			<!-- Primeira linha: Ano Letivo e Série (lado a lado) -->
			<div class="form-row">
				<div class="form-group">
					<label for="selecionarAnoLetivo">Ano Letivo *</label>
					<select id="selecionarAnoLetivo">
						<!-- Populado via JS -->
					</select>
				</div>
				<div class="form-group">
					<label for="selectSerie">Série *</label>
					<select id="selectSerie">
						<!-- Populado via JS -->
					</select>
				</div>
			</div>

			<!-- Segunda linha: Turnos (checkbox) e Nome da Turma -->
			<div class="form-row">
				<div class="form-group">
					<label>Turnos *</label>
					<div id="turnos-container" class="checkbox-grid-turmas">
						<!-- Checkboxes dos turnos serão gerados via JS -->
					</div>
				</div>
				<div class="form-group">
					<label for="nomeTurma">Nome da Turma *</label>
					<input type="text" id="nomeTurma" placeholder="Digite o nome da turma" maxlength="50" required>
				</div>
			</div>
 
			<!-- Terceira linha: INTERVALOS (AGORA DINÂMICO) -->
			<div id="intervalos-container">
				<!-- Os campos de intervalo serão gerados dinamicamente aqui -->
			</div>
			
			<!-- Explicação dos intervalos (mantém fixa) -->
			<div style="margin-top: 15px;">
				<em>No exemplo da posição dos intervalos, o 3 é entre a 2ª e 3ª aula e o 5 é entre a 4ª e 5ª aula.</em>
				<br><br>
				<em>* Campos obrigatórios.</em>
			</div>
		</div>
		<div class="modal-footer">
			<button id="save-btn">Salvar</button>
			<button id="cancel-btn">Cancelar</button>
		</div>
	</div>
</div>

<!-- MODAL DE CONFIRMAÇÃO DE EXCLUSÃO -->
<div id="modal-delete-turma" class="modal">
	<div class="modal-content delete-modal">
		<div class="modal-header">
			<h2>Confirmação de Exclusão</h2>
			<span class="close-modal" id="close-delete-turma-modal">&times;</span>
		</div>
		<div class="modal-body">
			<p>Deseja realmente excluir esta turma?</p>
		</div>
		<div class="modal-footer">
			<button id="confirm-delete-turma-btn">Sim, excluir</button>
			<button id="cancel-delete-turma-btn">Cancelar</button>
		</div>
	</div>
</div>

<!-- MODAL DE IMPRESSÃO DE TURMA -->
<div id="modal-print-turma" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Imprimir Turma</h2>
            <span class="close-modal" id="close-print-modal-turma">&times;</span>
        </div>
        <div class="modal-body">
            
            <!-- Três campos somente leitura: Ano Letivo, Série, Turma -->
            <div class="form-group triple-readonly" style="display: flex; gap: 10px;">
                <div style="flex: 1;">
                    <label for="readOnlyAno">Ano Letivo</label>
                    <input type="text" id="readOnlyAno" readonly>
                </div>
                <div style="flex: 1;">
                    <label for="readOnlySerie">Série</label>
                    <input type="text" id="readOnlySerie" readonly>
                </div>
                <div style="flex: 1;">
                    <label for="readOnlyTurma">Turma</label>
                    <input type="text" id="readOnlyTurma" readonly>
                </div>
            </div>

            <!-- Checkbox para incluir professores -->
            <div class="form-group" style="margin-top: 10px;">
                <label for="chk-prof-turma" class="radio-like">
                    <input type="checkbox" id="chk-prof-turma">
                    <span><strong>Incluir Professores</strong></span>
                </label>
            </div>

        </div> <!-- /.modal-body -->
        <div class="modal-footer">
            <!-- 3 botões: Imprimir, Geral, Cancelar -->
            <button id="btn-imprimir"><i class="fa-solid fa-print"></i>Imprimir</button>
            <button id="btn-cancelar">Cancelar</button>
        </div>
    </div>
</div>

<!-- MODAL DE IMPRESSÃO GERAL DA TURMA -->
<div id="modal-print-turma-geral" class="modal">
	<div class="modal-content">
			<div class="modal-header">
				<h2>Imprimir Turmas - Geral</h2>
				<span class="close-modal" id="close-print-modal-turma-geral">&times;</span>
			</div>
		<div class="modal-body">
			<!-- Apenas o checkbox para incluir professores -->
			<div class="form-group" style="margin-top: 10px;">
				<label for="chk-prof-turma-geral" class="radio-like">
					<input type="checkbox" id="chk-prof-turma-geral">
					<span><strong>Incluir Professores</strong></span>
				</label>
			</div>
		</div>
		<div class="modal-footer">
			<button id="btn-imprimir-turma-geral-confirm"><i class="fa-solid fa-print"></i>Imprimir</button>
			<button id="btn-cancelar-turma-geral">Cancelar</button>
		</div>
	</div>
</div>

<script src="<?php echo JS_PATH; ?>/script.js"></script>
<script src="<?php echo JS_PATH; ?>/turma.js"></script>

</body>
</html> 
