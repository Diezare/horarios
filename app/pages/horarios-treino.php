<?php
// app/pages/horarios-treino.php
require_once __DIR__ . '/../../configs/init.php';
include_once APP_SERVER_PATH . '/models/header.php';
?>
<main>
    <div class="page-header">
        <h1>Horários de Treinos</h1>
        <!--<button id="btnImprimir" class="btnImprimir">Imprimir</button>-->
		<button id="btnImprimir" class="btnImprimir" aria-label="Imprimir">
			<span class="icon"><i class="fa-solid fa-print"></i></span>
			<span class="text">Imprimir</span>
		</button>
    </div>
    <ul class="breadcrumbs">
        <li>Painel de Horários</li>
        <li> / </li>
        <li>Treinos</li>
    </ul>

    <!-- Filtro (igual ao do exemplo de horários) -->
    <div class="card-header">
        <!-- Ano Letivo -->
        <div class="form-group">
            <label for="selectAnoLetivo">Ano Letivo:</label>
            <select id="selectAnoLetivo">
                <option value="">-- Selecione --</option>
            </select>
        </div>

        <!-- Nível de Ensino -->
        <div class="form-group">
            <label for="selectNivelEnsino">Nível de Ensino:</label>
            <select id="selectNivelEnsino" disabled>
                <option value="">-- Selecione o Nível --</option>
            </select>
        </div>

        <!-- Turno -->
        <div class="form-group">
            <label for="selectTurno">Turno:</label>
            <select id="selectTurno" disabled>
                <option value="">-- Selecione o Turno --</option>
            </select>
        </div>

        <!-- Botão "Selecionar Dias" 
        <button id="btnSelectDays" disabled>Selecionar Dias</button> <i class="fa-solid fa-check-double"></i> -->
        <button id="btnSelectDays" disabled aria-label="Selecionar dias">
            <span class="icon"><i class="fa-solid fa-check-double"></i></span>
            <span class="text">Selecionar Dias</span>
        </button>

    </div>

    <div class="data">
        <div class="content-data" id="content-data-treino">
            <div id="dias-selecionados-container"></div>

            <div id="no-data-message" style="display:none; text-align:center;">
                Nenhuma informação encontrada.
            </div>
        </div>
    </div>
	
</main>

<!-- MODAL Selecionar Dias da Semana -->
<div id="modal-dias-semana" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Selecione os Dias de Treino</h2>
            <span class="close-modal" id="close-modal-dias">&times;</span>
        </div>
        <div class="modal-body">
            <div class="checkbox-grid" id="dias-checkboxes">
                <!-- Exemplo de 3 colunas:
                     <label class="radio-like"><input type="checkbox" value="Domingo"><span>Domingo</span></label>
                     <label class="radio-like"><input type="checkbox" value="Segunda"><span>Segunda</span></label>
                     ... -->
            </div>
        </div>
        <div class="modal-footer">
            <button id="btn-ok-dias">Ok</button>
            <button id="btn-cancel-dias">Cancelar</button>
        </div>
    </div>
</div>

<!-- MODAL Adicionar/Editar Horário de Treino (horario_escolinha) -->
<div id="modal-horario-treino" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="title-horario-treino">Adicionar Horário de Treino</h2>
            <span class="close-modal" id="close-modal-horario-treino">&times;</span>
        </div>
        <div class="modal-body">
            <input type="hidden" id="horarioTreinoId" value="">

            <!-- Primeira linha: Modalidade e Categoria -->
            <div class="form-row">
                <div class="form-group" style="flex:1; margin-right:10px;">
                    <label for="selectModalidade">Modalidade *</label>
                    <select id="selectModalidade" class="horarios-treino" disabled>
                        <option value="">-- Selecione --</option>
                    </select>
                </div>
                <div class="form-group" style="flex:1;">
                    <label for="selectCategoria">Categoria *</label>
                    <select id="selectCategoria" disabled>
                        <option value="">-- Selecione --</option>
                    </select>
                </div>
            </div>

            <!-- Segunda linha: Professor -->
            <div class="form-row">
                <div class="form-group" style="flex:1;">
                    <label for="selectProfessor">Professor *</label>
                    <select id="selectProfessor" disabled>
                        <option value="">-- Selecione --</option>
                    </select>
                </div>
            </div>

            <!-- Terceira linha: Início e Término -->
            <div class="form-row">
                <div class="form-group" style="flex:1; margin-right:10px;">
                    <label for="inputHoraInicio">Início *</label>
                    <input type="time" id="inputHoraInicio" disabled>
                </div>
                <div class="form-group" style="flex:1;">
                    <label for="inputHoraFim">Término *</label>
                    <input type="time" id="inputHoraFim" disabled>
                </div>
            </div>
			<em>* Campos obrigatórios.</em>
        </div>
        <div class="modal-footer">
            <button id="btnSalvarTreino">Salvar</button>
            <button id="btnCancelarTreino">Cancelar</button>
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

<!-- MODAL Replicar Horário de Treino -->
<div id="modal-replicar-horario" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Replicar Horário de Treino</h2>
            <span class="close-modal" id="close-modal-replicar">&times;</span>
        </div>
        <div class="modal-body">
            <p>Selecione os dias para replicar este horário de treino:</p>
            <div class="checkbox-grid" id="dias-replicacao-checkboxes">
                <!-- Dias disponíveis serão inseridos via JavaScript -->
            </div>
        </div>
        <div class="modal-footer">
            <button id="btn-ok-replicar">Replicar</button>
            <button id="btn-cancelar-replicar">Cancelar</button>
        </div>
    </div>
</div>

<!-- MODAL DE IMPRESSÃO (HORÁRIOS DE TREINO) -->
<div id="modal-print-treino" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Imprimir Horários de Treino</h2>
            <span class="close-modal" id="close-print-treino">&times;</span>
        </div>
        <div class="modal-body">
            <div class="form-row" style="display:flex; gap:10px;">
                <div class="form-group" style="flex:1;">
                    <label for="print-selected-ano">Ano Letivo</label>
                    <input type="text" id="print-selected-ano" readonly>
                </div>
                <div class="form-group" style="flex:1;">
                    <label for="print-selected-turno">Turno</label>
                    <input type="text" id="print-selected-turno" readonly>
                </div>
            </div>
            <p style="margin-top:10px;">Deseja realmente imprimir?</p>
        </div>
        <div class="modal-footer">
            <button id="btn-imprimir-treino-confirm"><i class="fa-solid fa-print"></i> Imprimir</button>
            <button id="btn-cancelar-imprimir-treino">Cancelar</button>
        </div>
    </div>
</div>

<script src="<?php echo JS_PATH; ?>/script.js"></script>
<script src="<?php echo JS_PATH; ?>/horarios-treino.js"></script>
</body>
</html>
  