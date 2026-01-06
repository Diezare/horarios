<?php
// app/pages/professor.php
require_once __DIR__ . '/../../configs/init.php';
include_once APP_SERVER_PATH . '/models/header.php';
?>
<main>
	<div class="page-header">
		<h1>Professores</h1>
		<button id="btn-add" class="btn-add"><i class="fa-solid fa-plus"></i> Adicionar</button>
	</div>
	<ul class="breadcrumbs">
		<li>Cadastros Gerais</li>
		<li> / </li>
		<li>Professores</li>
	</ul>
	
	<!-- CARD-HEADER COM OS 3 FILTROS + BOTÃO IMPRIMIR -->
	<div class="card-header">
		<div class="form-group">
			<div class="card-title"><h3>Pesquisar</h3></div>
		</div>

		<div class="card-search">
			<input type="text" id="search-input" placeholder="Pesquisar...">
		</div>

		<!-- <button id="btnImprimir">Imprimir Geral</button> -->
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
							<th>Nome Completo
								<span class="sort-icons">
									<i class="fa-solid fa-sort-up"   id="sort-nome-asc"></i>
									<i class="fa-solid fa-sort-down" id="sort-nome-desc"></i>
								</span>
							</th>
							<th>Nome de Exibição
								<span class="sort-icons">
									<i class="fa-solid fa-sort-up"   id="sort-nome-exibicao-asc"></i>
									<i class="fa-solid fa-sort-down" id="sort-nome-exibicao-desc"></i>
								</span>
							</th>
							<th>Ações</th>
						</tr>
					</thead>
					<tbody id="professorTable"></tbody>
				</table>
				<div id="no-data-message" class="no-data-message">
					Não há nenhuma informação cadastrada.
				</div>
			</div>
		</div>
	</div>
</main>

<!-- MODAL: Adicionar/Editar Professor -->
<div id="modal-professor" class="modal">
	<div class="modal-content">
		<div class="modal-header">
			<h2 id="modal-title">Adicionar Professor</h2>
			<span class="close-modal" id="close-modal">&times;</span>
		</div>
		<div class="modal-body">
			<input type="hidden" id="professorId">
	
			<!-- Primeira linha: Nome Completo -->
			<div class="form-row">
				<div class="form-group" style="flex: 1;">
					<label for="nome-completo">Nome Completo *</label>
					<input type="text" id="nome-completo" placeholder="Digite o nome completo" required maxlength="150">
				</div>
			</div>

			<!-- Segunda linha: Nome Exibição e Telefone -->
			<div class="form-row">
				<div class="form-group">
					<label for="nome-exibicao">Nome Exibição *</label>
					<input type="text" id="nome-exibicao" placeholder="Digite o nome de exibição" maxlength="100">
				</div>
				<div class="form-group">
					<label for="telefone">Telefone *</label>
					<input type="text" id="telefone" placeholder="(xx) xxxxx-xxxx" maxlength="20">
				</div>
			</div>
	
			<!-- Terceira linha (duas colunas): Limite e Sexo -->
			<div class="form-row">
			<!-- Coluna 1: Limite de aulas -->
				<div class="form-group" style="flex: 1; margin-right: 15px;">
					<label for="limite-aulas">Limite de Aulas/Semana *</label>
					<input type="text" id="limite-aulas" placeholder="Ex: 20" maxlength="2">
				</div>
			<!-- Coluna 2: Sexo -->
				<div class="form-group" style="flex: 1;">
					<label>Sexo *</label>
					<div class="radio-group">
						<label>
							<input type="radio" name="sexo" value="Masculino" id="sexo-masc">
							Masc.
						</label>
						<label>
							<input type="radio" name="sexo" value="Feminino" id="sexo-fem">
							Femin.
						</label>
						<label>
							<input type="radio" name="sexo" value="Outro" id="sexo-outro">
							Outro
						</label>
					</div>
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

<!-- MODAL: Vincular Disciplinas -->
<div id="modal-professor-disciplina" class="modal">
	<div class="modal-content">
		<div class="modal-header">
			<h2>Vincular Disciplinas</h2>
			<span class="close-modal" id="close-professor-disciplina-modal">&times;</span>
		</div>
		<div class="modal-body">

			<!-- Campo de texto readonly para o professor + hidden para o ID -->
			<div class="form-group">
				<label for="prof-nome-disciplina">Professor</label>
				<input type="text" id="prof-nome-disciplina" readonly>
				<input type="hidden" id="select-professor-disciplina">
			</div>

			<div class="form-group">
				<label>Disciplinas</label>
				<!-- A classe "checkbox-grid" será usada para organizar 3 colunas -->
				<div id="disciplinas-checkboxes" class="checkbox-grid">
					<!-- Conteúdo gerado via JS -->
				</div>
			</div>

		</div>
		<div class="modal-footer">
			<button id="save-professor-disciplina-btn">Salvar</button>
			<button id="cancel-professor-disciplina-btn">Cancelar</button>
		</div>
	</div>
</div>

<!-- MODAL: Definir Restrições -->
<div id="modal-professor-restricoes" class="modal">
	<div class="modal-content">
		<div class="modal-header">
			<h2>Definir Restrições de Horário</h2>
			<span class="close-modal" id="close-professor-restricoes-modal">&times;</span>
		</div>
		<div class="modal-body">


			<!-- Primeira linha: Nome do professor e ano letivo -->
			<!-- <div class="form-row">
				<div class="form-group">
					<label>Professor(a)</label>
					 Nome do professor (read-only)  //essa linha é comentada
					<input type="text" id="prof-restricoes-nome" readonly>
					 Hidden com ID do professor  //essa linha é comentada
					<input type="hidden" id="select-professor-restricoes">
				</div>
				<div class="form-group">
					<label for="select-ano-letivo">Ano Letivo *</label>
					<select id="select-ano-letivo">
						 Carregado via loadAnoLetivo()  //essa linha é comentada
					</select>
				</div>
			</div> -->

			<!-- Nome do professor em linha separada -->
		<div class="form-row">
			<div class="form-group" style="flex: 1;">
				<label>Professor(a)</label>
				<input type="text" id="prof-restricoes-nome" readonly>
				<input type="hidden" id="select-professor-restricoes">
			</div>
		</div>

		<!-- Ano Letivo e Turno lado a lado -->
		<div class="form-row">
			<div class="form-group" style="flex: 1; margin-right: 10px;">
				<label for="select-ano-letivo">Ano Letivo *</label>
				<select id="select-ano-letivo">
					<option value="">-- Selecione o Ano --</option>
				<!-- opções carregadas dinamicamente -->
				</select>
			</div>
			<div class="form-group" style="flex: 1;">
 				<label for="select-turno">Turno *</label>
				<select id="select-turno">
					<option value="">-- Selecione o Turno --</option>
					<!-- opções carregadas dinamicamente -->
				</select>
			</div>
		</div>


			<!-- A grade de dias será criada dinamicamente dentro deste .restricoes-grid -->
			<div class="restricoes-grid"> 
				<!-- Conteúdo gerado via renderDiasRestricoes() -->
			</div>
			<em>* Campos obrigatórios.</em>
		</div>
		<div class="modal-footer">
			<button id="save-professor-restricoes-btn">Salvar</button>
			<button id="cancel-professor-restricoes-btn">Cancelar</button>
		</div>
	</div>
</div>
 
<!-- Modal: Vincular Turnos -->
<div id="modal-professor-turno" class="modal">
	<div class="modal-content">
		<div class="modal-header">
			<h2>Vincular Turnos</h2>
			<span class="close-modal" id="close-professor-turno-modal">&times;</span>
		</div>
		<div class="modal-body">
			<div class="form-group">
				<label for="prof-nome-turno">Professor(a)</label>
				<!-- Campo de texto readonly para exibir o nome do professor -->
				<input type="text" id="prof-nome-turno" readonly>
				<!-- Campo hidden para enviar o ID do professor -->
				<input type="hidden" id="select-professor-turno">
			</div>
			<div class="form-group">
				<label>Turnos</label>
				<div id="turnos-checkboxes" class="checkbox-grid">
					<!-- checkboxes gerados via JS -->
				</div>
			</div>
		</div>
		<div class="modal-footer">
			<button id="save-professor-turno-btn">Salvar</button>
			<button id="cancel-professor-turno-btn">Cancelar</button>
		</div>
	</div>
</div>

<!-- Modal: Vincular Professor - Disciplinas - Turmas -->
<div id="modal-professor-disciplina-turma" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <h2>Vincular Professor(a) com Disciplinas e Turmas</h2>
      <span class="close-modal" id="close-professor-disciplina-turma-modal">&times;</span>
    </div>

    <div class="modal-body">
      <div class="form-row">
        <div class="form-group" style="flex:1; margin-right: 15px;">
          <label>Professor(a)</label>
          <input type="text" id="prof-dt-nome" readonly>
          <input type="hidden" id="prof-dt-id">
        </div>

        <div class="form-group" style="flex:1;">
          <label>Disciplina</label>
          <select id="sel-disciplina">
            <option value="">-- Selecione a Disciplina --</option>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label>Níveis de Ensino</label>
        <!--  CORRIGIDO: estava "nivel-ensino-checkboxes", agora é "niveis-ensino-checkboxes" -->
        <div id="niveis-ensino-checkboxes" class="checkbox-grid"></div>
      </div>

      <div class="form-group">
        <label>Turmas</label>
        <div id="turmas-dt-checkboxes" class="checkbox-grid"></div>
      </div>
    </div>

    <div class="modal-footer">
      <button id="save-professor-disciplina-turma-btn">Salvar</button>
      <button id="cancel-professor-disciplina-turma-btn">Cancelar</button>
    </div>
  </div>
</div>
 
<!-- MODAL DE IMPRESSÃO PARA PROFESSOR -->
<div id="modal-print-professor" class="modal">
	<div class="modal-content">
		<div class="modal-header">
			<h2>Imprimir Professor</h2>
			<span class="close-modal" id="close-print-professor">&times;</span>
		</div>
		<div class="modal-body">
			<div class="form-row">
				<!-- Campo do Professor -->
				<div class="form-group">
					<label for="selected-professor">Professor</label>
					<input type="text" id="selected-professor" readonly>
				</div>

				<!-- Campo do Ano Letivo -->
				<div class="form-group">
					<label for="select-ano-letivo-print">Ano Letivo</label>
					<select id="select-ano-letivo-print">
						<option value="">-- Selecione --</option>
					</select>
				</div>
			</div>

			<!-- Seção de Filtros (checkboxes) -->
			<div class="filter-section">
				<div class="form-row">
					<div class="form-group inline-checkbox">
						<label class="radio-like">
							<input type="checkbox" id="chk-prof-disciplinas">
							<span><strong>Disciplinas</strong></span>
						</label>
					</div>
					<div class="form-group inline-checkbox">
						<label class="radio-like">
							<input type="checkbox" id="chk-prof-restricoes">
							<span><strong>Restrições</strong></span>
						</label>
					</div>
				</div>
				<div class="form-row">
					<div class="form-group inline-checkbox">
						<label class="radio-like">
							<input type="checkbox" id="chk-prof-turnos">
							<span><strong>Turnos</strong></span>
						</label>
					</div>
					<div class="form-group inline-checkbox">
						<label class="radio-like">
							<input type="checkbox" id="chk-prof-turmas">
							<span><strong>Turmas</strong></span>
						</label>
					</div>
				</div>
				<!-- NOVO CHECKBOX "Horários" -->
				<div class="form-row">
					<div class="form-group inline-checkbox">
						<label class="radio-like">
							<input type="checkbox" id="chk-prof-horarios">
							<span><strong>Horários</strong></span>
						</label>
					</div>
				</div>
				<!-- Fim do bloco de checkboxes -->
			</div>
		</div>
		<div class="modal-footer">
			<button id="btn-imprimir"><i class="fa-solid fa-print"></i>Imprimir</button>
			<button id="btn-cancelar">Cancelar</button>
		</div>
	</div>
</div>
<!-- MODAL DE IMPRESSÃO GERAL (NOVO) -->
<div id="modal-print-geral" class="modal">
	<div class="modal-content">
		<div class="modal-header">
			<h2>Imprimir Geral</h2>
			<span class="close-modal" id="close-print-geral">&times;</span>
		</div>
		<div class="modal-body">
			<!-- Campo do Ano Letivo (ocupa linha inteira) -->
			<div class="form-row">
				<div class="form-group" style="flex:1;">
					<label for="select-ano-letivo-print-geral">Ano Letivo</label>
					<select id="select-ano-letivo-print-geral" style="width: 100%;">
						<option value="">-- Selecione --</option>
					</select>
				</div>
			</div>

			<!-- Checkboxes iguais ao do professor -->
			<div class="filter-section">
				<div class="form-row">
					<div class="form-group inline-checkbox">
						<label class="radio-like">
							<input type="checkbox" id="chk-geral-disciplinas">
							<span><strong>Disciplinas</strong></span>
						</label>
					</div>
					<div class="form-group inline-checkbox">
						<label class="radio-like">
							<input type="checkbox" id="chk-geral-restricoes">
							<span><strong>Restrições</strong></span>
						</label>
					</div>
				</div>
				<div class="form-row">
					<div class="form-group inline-checkbox">
						<label class="radio-like">
							<input type="checkbox" id="chk-geral-turnos">
							<span><strong>Turnos</strong></span>
						</label>
					</div>
					<div class="form-group inline-checkbox">
						<label class="radio-like">
							<input type="checkbox" id="chk-geral-turmas">
							<span><strong>Turmas</strong></span>
						</label>
					</div>
				</div>
				<div class="form-row">
					<div class="form-group inline-checkbox">
						<label class="radio-like">
							<input type="checkbox" id="chk-geral-horarios">
							<span><strong>Horários</strong></span>
						</label>
					</div>
				</div>
			</div>

		</div>
		<div class="modal-footer">
			<button id="btn-imprimir-geral"><i class="fa-solid fa-print"></i>Imprimir</button>
			<button id="btn-cancelar-geral">Cancelar</button>
		</div>
	</div>
</div>

<!-- MODAL DE COMPARTILHAMENTO PARA PROFESSOR 
<div id="modal-compartilhar-professor" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Compartilhar Dados</h2>
            <span class="close-modal" id="close-compartilhar-professor">&times;</span>
        </div>
        <div class="modal-body">

			<div class="form-row">

				<div class="form-group">
					<label for="selected-professor-share">Professor</label>
					<input type="text" id="selected-professor-share" readonly>
				</div>
			

				<div class="form-group">
					<label for="select-ano-letivo-share">Ano Letivo</label>
					<select id="select-ano-letivo-share">
						<option value="">-- Selecione --</option>
						
					</select>
				</div>
			</div>

            <div class="filter-section">
                <div class="form-row" style="display: flex; gap: 10px;">
                    <div class="form-group inline-checkbox" style="flex: 1;">
                        <label for="chk-share-disciplinas" class="radio-like">
                            <input type="checkbox" id="chk-share-disciplinas">
                            <span><strong>Disciplinas</strong></span>
                        </label>
                    </div>
                    <div class="form-group inline-checkbox" style="flex: 1;">
                        <label for="chk-share-restricoes" class="radio-like">
                            <input type="checkbox" id="chk-share-restricoes">
                            <span><strong>Restrições</strong></span>
                        </label>
                    </div>
                </div>
                <div class="form-row" style="display: flex; gap: 10px;">
                    <div class="form-group inline-checkbox" style="flex: 1;">
                        <label for="chk-share-turnos" class="radio-like">
                            <input type="checkbox" id="chk-share-turnos">
                            <span><strong>Turnos</strong></span>
                        </label>
                    </div>
                    <div class="form-group inline-checkbox" style="flex: 1;">
                        <label for="chk-share-turmas" class="radio-like">
                            <input type="checkbox" id="chk-share-turmas">
                            <span><strong>Turmas</strong></span>
                        </label>
                    </div>
                </div>
                <div class="form-row" style="margin-top: 10px;">
                    <div class="form-group inline-checkbox" style="flex: 1;">
                        <label for="chk-share-horarios" class="radio-like">
                            <input type="checkbox" id="chk-share-horarios">
                            <span><strong>Horários</strong></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button id="btn-share-professor">Compartilhar</button>
            <button id="btn-cancelar-share-professor">Cancelar</button>
        </div>
    </div>
</div> -->

<script src="<?php echo JS_PATH; ?>/script.js"></script>
<script src="<?php echo JS_PATH; ?>/professor.js"></script>
<script src="<?php echo JS_PATH; ?>/professor-disciplina.js"></script>
<script src="<?php echo JS_PATH; ?>/professor-restricoes.js"></script>
<script src="<?php echo JS_PATH; ?>/professor-turno.js"></script>
<script src="<?php echo JS_PATH; ?>/professor-disciplina-turma.js"></script>

</body>
</html>
 