<?php
require_once __DIR__ . '/../../configs/init.php';
include_once APP_SERVER_PATH . '/models/header.php';
?>
<main>
	<div class="page-header">
		<h1>Relatório de Hora/Aula - Escolinha</h1>
		
		<button id="btnImprimir" class="btnImprimir" aria-label="Imprimir Geral">
			<span class="icon"><i class="fa-solid fa-print"></i></span>
			<span class="text">Imprimir Geral</span>
		</button>		
		
	</div>

	<ul class="breadcrumbs">
		<li>Relatórios</li>
		<li>/</li>
		<li>Hora/Aula - Escolinha</li>
	</ul>

	<!-- CARD-HEADER COM OS FILTROS -->
	<div class="card-header">
		<div class="form-group">
			<label for="selectAnoLetivo">Ano Letivo:</label>
			<select id="selectAnoLetivo">
				<option value="">-- Selecione --</option>
				<!-- Populado via JS -->
			</select>
		</div>
		<div class="form-group">
			<label for="selectTurno">Turno:</label>
			<select id="selectTurno" disabled>
				<option value="">-- Selecione o Turno --</option>
			</select>
		</div>
	</div>

	<div class="data">
		<div class="content-data">
			<div class="table-data">
				<table>
					<thead>
						<tr>
							<th class="sortable" data-column="professor">
								Professor(a)
								<span class="sort-icons">
									<i class="fa-solid fa-sort-up sort-asc" data-column="professor" data-order="asc"></i>
									<i class="fa-solid fa-sort-down sort-desc" data-column="professor" data-order="desc"></i>
								</span>
							</th>
							<th class="sortable" data-column="modalidade">
								Modalidade
								<span class="sort-icons">
									<i class="fa-solid fa-sort-up sort-asc" data-column="modalidade" data-order="asc"></i>
									<i class="fa-solid fa-sort-down sort-desc" data-column="modalidade" data-order="desc"></i>
								</span>
							</th>
							<th class="sortable" data-column="categoria">
								Categoria
								<span class="sort-icons">
									<i class="fa-solid fa-sort-up sort-asc" data-column="categoria" data-order="asc"></i>
									<i class="fa-solid fa-sort-down sort-desc" data-column="categoria" data-order="desc"></i>
								</span>
							</th>
							<th class="sortable" data-column="total-aulas">
								Total de Aulas Semanal
								<span class="sort-icons">
									<i class="fa-solid fa-sort-up sort-asc" data-column="total-aulas" data-order="asc"></i>
									<i class="fa-solid fa-sort-down sort-desc" data-column="total-aulas" data-order="desc"></i>
								</span>
							</th>
							<th>Ações</th>
						</tr>
					</thead>
					<tbody id="relatorio-tbody"></tbody>
				</table>
				<div id="no-data-message" class="no-data-message">
					Nenhum dado encontrado.
				</div>
			</div>
		</div>
	</div>
</main>

<!-- MODAL DE IMPRESSÃO GERAL -->
<div id="modal-print-relatorio-geral" class="modal">
	<div class="modal-content">
		<div class="modal-header">
			<h2>Imprimir Relatório - Hora/Aula</h2>
			<span class="close-modal" id="close-print-modal">&times;</span>
		</div>
		<div class="modal-body">
			<div class="form-row">
				<div class="form-group">
					<label for="select-ano-print">Ano Letivo *</label>
					<select id="select-ano-print">
						<option value="">Selecione</option>
					</select>
				</div>
				<div class="form-group">
					<label for="select-turno-print">Turno</label>
					<select id="select-turno-print">
						<option value="">Todos</option>
					</select>
				</div>
			</div>

<div class="form-row">
  <div class="form-group">
    <label class="radio-label">
      <input type="radio" name="tipo-relatorio-print" value="tudo" checked style="width: auto; margin-right: 5px;">
      <strong>Geral</strong>
    </label>
  </div>
  <div class="form-group">
    <label class="radio-label">
      <input type="radio" name="tipo-relatorio-print" value="dia" style="width: auto; margin-right: 5px;">
      <strong>Hora Aula por Dia</strong>
    </label>
  </div>
</div>

<div class="form-row">
  <div class="form-group">
    <label class="radio-label">
      <input type="radio" name="tipo-relatorio-print" value="semana" style="width: auto; margin-right: 5px;">
      <strong>Hora Aula por Semana</strong>
    </label>
  </div>
  <div class="form-group">
    <label class="radio-label">
      <input type="radio" name="tipo-relatorio-print" value="mes" style="width: auto; margin-right: 5px;">
      <strong>Hora Aula por Mês</strong>
    </label>
  </div>
</div>

<div class="form-row">
  <div class="form-group">
    <label class="radio-label">
      <input type="radio" name="tipo-relatorio-print" value="semestre" style="width: auto; margin-right: 5px;">
      <strong>Hora Aula por Semestre</strong>
    </label>
  </div>
  <div class="form-group">
    <label class="radio-label">
      <input type="radio" name="tipo-relatorio-print" value="ano" style="width: auto; margin-right: 5px;">
      <strong>Hora Aula por Ano</strong>
    </label>
  </div>
</div>
		<div class="modal-footer">
			<button id="btn-imprimir-geral-confirm"><i class="fa-solid fa-print"></i>Imprimir</button>
			<button id="btn-cancelar-geral">Cancelar</button>
		</div>
	</div>
</div>

<script src="<?php echo JS_PATH; ?>/script.js"></script>
<script src="<?php echo JS_PATH; ?>/relatorio-hora-aula-escolinha.js"></script>

</body>
</html>


