// app/assets/js/serie.js
document.addEventListener('DOMContentLoaded', function() {
	/* ============================================================
		 1) Referências Gerais
	============================================================ */
	const modal = document.getElementById('modal-serie');
	const btnAdd = document.getElementById('btn-add-serie');
	const closeModalElements = document.querySelectorAll('#modal-serie .close-modal');
	const cancelBtn = document.getElementById('cancel-btn');
	const saveBtn = document.getElementById('save-btn');
	
	// Modal de exclusão
	const modalDelete = document.getElementById('modal-delete-serie');
	const closeDeleteModalBtn = document.getElementById('close-delete-serie-modal');
	const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
	const cancelDeleteBtn = document.getElementById('cancel-delete-btn');
	
	// Tabela de Séries
	const serieTableBody = document.getElementById('serieTable');
	const noDataMessage = document.getElementById('no-data-message-serie');
	
	// Campos do formulário
	const inputSerieId = document.getElementById('serieId');
	const selectNivelEnsino = document.getElementById('nivelEnsino');
	const inputNomeSerie = document.getElementById('nomeSerie');
	const inputTotalAulas = document.getElementById('totalAulasSemana');
	
	let isEditMode = false;
	let currentEditId = null;
	let currentPrintSerieId = null;
	let allSeries = []; // Para sort
	
	/* ============================================================
		 2) Modal de Impressão INDIVIDUAL de Série
	============================================================ */
	const modalPrintSerie = document.getElementById('modal-print-serie');
	const closePrintModalSerieBtn = document.getElementById('close-print-serie');
	const btnImprimirSerie = document.getElementById('btn-imprimir');
	const btnCancelarSerie = document.getElementById('btn-cancelar');
	const selectedSerieInput = document.getElementById('selected-serie');
	const selectedTotalInput = document.getElementById('selected-total');
	const selectedNivelInput = document.getElementById('selected-nivel');
	
	/* ============================================================
		 3) Modal de Impressão GERAL de Série
		 - O modal usa um dropdown (id="select-nivel-geral-serie") para escolher o nível,
			 e os checkboxes (ids "chk-turmas-serie" e "chk-disc-serie") para os filtros.
	============================================================ */
	const btnImprimirGeral = document.getElementById('btnImprimir'); // Botão na card-header
	const modalPrintSerieGeral = document.getElementById('modal-print-serie-geral');
	const closePrintSerieGeralBtn = document.getElementById('close-print-serie-geral');
	const btnImprimirSerieGeralConfirm = document.getElementById('btn-imprimir-serie-geral-confirm');
	const btnCancelarSerieGeral = document.getElementById('btn-cancelar-serie-geral');
	const selectNivelGeralSerie = document.getElementById('select-nivel-geral-serie');
	const chkTurmasSerieGeral = document.getElementById('chk-turmas-serie-geral');
	const chkDiscSerieGeral = document.getElementById('chk-disc-serie-geral');
	
	// Os checkboxes de Turmas e Disciplinas são os mesmos dos modais individuais
	const chkTurmasSerie = document.getElementById('chk-turmas-serie');
	const chkDiscSerie = document.getElementById('chk-disc-serie');
	
	/* ============================================================
		 4) Sort Icons
			 - Série: #sort-serie-asc, #sort-serie-desc
			 - Nível:	#sort-nivel-asc, #sort-nivel-desc
	============================================================ */
	const sortSerieAsc = document.getElementById('sort-serie-asc');
	const sortSerieDesc = document.getElementById('sort-serie-desc');
	const sortNivelAsc = document.getElementById('sort-nivel-asc');
	const sortNivelDesc = document.getElementById('sort-nivel-desc');
	
	function sortTable(property, asc = true) {
		const sorted = [...allSeries].sort((a, b) => {
		const valA = (a[property] || '').toString().toLowerCase();
		const valB = (b[property] || '').toString().toLowerCase();
		return asc ? valA.localeCompare(valB) : valB.localeCompare(valA);
		});
		renderTable(sorted);
	}
	
	sortSerieAsc.addEventListener('click', () => sortTable('nome_serie', true));
	sortSerieDesc.addEventListener('click', () => sortTable('nome_serie', false));
	sortNivelAsc.addEventListener('click', () => sortTable('nome_nivel_ensino', true));
	sortNivelDesc.addEventListener('click', () => sortTable('nome_nivel_ensino', false));
	
	/* ============================================================
		 5) Regras de Preenchimento do Formulário
	============================================================ */
	// (Caso seja necessário limitar ou formatar algum campo, faça aqui)
	
	/* ============================================================
		 6) Carregar Níveis de Ensino para o Formulário e para o Modal Geral
	============================================================ */
	function loadNiveisEnsino() {
		fetch('/horarios/app/controllers/nivel-ensino/listNivelEnsinoByUser.php')
			.then(r => r.json())
			.then(data => {
				if (data.status === 'success') {
					// Preenche o select do formulário com o placeholder primeiro
					selectNivelEnsino.innerHTML = '<option value="">-- Selecione um Nível de Ensino --</option>';
					data.data.forEach(nivel => {
						const option = document.createElement('option');
						option.value = nivel.id_nivel_ensino;
						option.textContent = nivel.nome_nivel_ensino;
						selectNivelEnsino.appendChild(option);
					});
					// Preenche o dropdown do modal geral
					selectNivelGeralSerie.innerHTML = '<option value="todos">Todos</option>';
					data.data.forEach(nivel => {
						const option = document.createElement('option');
						option.value = nivel.id_nivel_ensino;
						option.textContent = nivel.nome_nivel_ensino;
						selectNivelGeralSerie.appendChild(option);
					});
				} else {
					console.error(data.message);
				}
			})
			.catch(err => console.error(err));
	}
	
	/* ============================================================
		 7) Carregar Séries
	============================================================ */
	function fetchSeries() {
		fetch('/horarios/app/controllers/serie/listSerieByUser.php')
		.then(r => r.json())
		.then(data => {
			if (data.status === 'success') {
			allSeries = data.data;
			renderTable(allSeries);
			} else {
			console.error(data.message);
			}
		})
		.catch(err => console.error(err));
	}
	
	function renderTable(rows) {
		serieTableBody.innerHTML = '';
		if (!rows || rows.length === 0) {
		noDataMessage.style.display = 'block';
		return;
		}
		noDataMessage.style.display = 'none';
		rows.forEach(row => {
		const tr = document.createElement('tr');
		tr.dataset.id = row.id_serie;
		tr.dataset.total = row.total_aulas_semana;
		tr.dataset.nivel = row.nome_nivel_ensino;
	
		// Coluna Série
		const tdSerie = document.createElement('td');
		tdSerie.textContent = row.nome_serie;
		tr.appendChild(tdSerie);
	
		// Coluna Nível de Ensino
		const tdNivel = document.createElement('td');
		tdNivel.textContent = row.nome_nivel_ensino || '';
		tr.appendChild(tdNivel);
	
		// Ações
		const tdActions = document.createElement('td');
		// Botão Editar
		const btnEdit = document.createElement('button');
		btnEdit.classList.add('btn-edit');
		btnEdit.dataset.id = row.id_serie;
		btnEdit.innerHTML = `<span class="icon"><i class="fa-solid fa-pen-to-square"></i></span>
							 <span class="text">Editar</span>`;
		tdActions.appendChild(btnEdit);
		// Botão Deletar
		const btnDelete = document.createElement('button');
		btnDelete.classList.add('btn-delete');
		btnDelete.dataset.id = row.id_serie;
		btnDelete.innerHTML = `<span class="icon"><i class="fa-solid fa-trash"></i></span>
								 <span class="text">Deletar</span>`;
		tdActions.appendChild(btnDelete);
		// Botão Imprimir
		const btnPrint = document.createElement('button');
		btnPrint.classList.add('btn-print');
		btnPrint.dataset.id = row.id_serie;
		btnPrint.innerHTML = `<span class="icon"><i class="fa-solid fa-print"></i></span>
								<span class="text">Imprimir</span>`;
		tdActions.appendChild(btnPrint);
		// Botão Vincular Disciplinas
		const btnVincularDisc = document.createElement('button');
		btnVincularDisc.classList.add('btn-vincular-disciplina');
		btnVincularDisc.dataset.id = row.id_serie;
		btnVincularDisc.innerHTML = `<span class="icon"><i class="fa-solid fa-book"></i></span>
									 <span class="text">Disciplinas</span>`;
		tdActions.appendChild(btnVincularDisc);
	
		tr.appendChild(tdActions);
		serieTableBody.appendChild(tr);
		});
	}
	
	/* ============================================================
		 8) Modal de Cadastro/Edição de Série
	============================================================ */
	/*function openModal() {
		modal.style.display = 'block';
		modal.classList.remove('fade-out');
		modal.classList.add('fade-in');
		const content = modal.querySelector('.modal-content');
		content.classList.remove('slide-up');
		content.classList.add('slide-down');
		if (!isEditMode) {
		clearForm();
		document.getElementById('modal-serie-title').innerText = 'Adicionar Série';
		saveBtn.innerText = 'Salvar';
		}
	}*/

	// Ao abrir o modal de série (novo cadastro), reseta o select
	function openModal() {
		modal.style.display = 'block';
		modal.classList.remove('fade-out');
		modal.classList.add('fade-in');
		const content = modal.querySelector('.modal-content');
		content.classList.remove('slide-up');
		content.classList.add('slide-down');
		if (!isEditMode) {
			clearForm();
			document.getElementById('modal-serie-title').innerText = 'Adicionar Série';
			saveBtn.innerText = 'Salvar';
			// Garante que o select aparece com placeholder, SEM valor selecionado
			selectNivelEnsino.selectedIndex = 0;
		}
	}


	function closeModal() {
		const content = modal.querySelector('.modal-content');
		content.classList.remove('slide-down');
		content.classList.add('slide-up');
		modal.classList.remove('fade-in');
		modal.classList.add('fade-out');
		setTimeout(() => {
		modal.style.display = 'none';
		content.classList.remove('slide-up');
		modal.classList.remove('fade-out');
		isEditMode = false;
		currentEditId = null;
		}, 300);
	}
	function clearForm() {
		inputSerieId.value = '';
		selectNivelEnsino.selectedIndex = 0; // Valor "default" (placeholder)
		inputNomeSerie.value = '';
		inputTotalAulas.value = '';
	}

	btnAdd.addEventListener('click', () => {
		isEditMode = false;
		openModal();
	});
	closeModalElements.forEach(el => el.addEventListener('click', closeModal));
	cancelBtn.addEventListener('click', () => {
		clearForm();
		closeModal();
	});
	
	/* ============================================================
		 9) Salvar Série (Insert/Update)
	============================================================ */
	saveBtn.addEventListener('click', () => {
		const id = inputSerieId.value;
		const idNivel = selectNivelEnsino.value;
		const nomeSerie = inputNomeSerie.value.trim();
		const totalAulas = inputTotalAulas.value.trim();

		if (!idNivel) {
			alert('Selecione o Nível de Ensino antes de salvar.');
			selectNivelEnsino.focus();
			return;
		}
		if (!nomeSerie || !totalAulas) {
			alert('Preencha todos os campos.');
			return;
		}
	
		const data = new URLSearchParams({
		id_serie: id,
		id_nivel_ensino: idNivel,
		nome_serie: nomeSerie,
		total_aulas_semana: totalAulas
		});
	
		if (isEditMode) {
		fetch('/horarios/app/controllers/serie/updateSerie.php', {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: data
		})
		.then(r => r.json())
		.then(response => {
			alert(response.message);
			if (response.status === 'success') {
			closeModal();
			fetchSeries();
			}
		})
		.catch(err => console.error(err));
		} else {
		fetch('/horarios/app/controllers/serie/insertSerie.php', {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: data
		})
		.then(r => r.json())
		.then(response => {
			if (response.status === 'success') {
			fetchSeries();
			const resp = confirm('Série inserida com sucesso! Deseja inserir outra série?');
			if (resp) {
				clearForm();
			} else {
				closeModal();
			}
			} else {
			alert(response.message);
			}
		})
		.catch(err => console.error(err));
		}
	});
	
	/* ============================================================
		 10) Ações na Tabela (Editar, Deletar, Imprimir, Vincular Disciplinas)
	============================================================ */
	serieTableBody.addEventListener('click', e => {
		const btn = e.target.closest('button');
		if (!btn) return;
		const id = btn.dataset.id;
		if (btn.classList.contains('btn-edit')) {
		isEditMode = true;
		currentEditId = id;
		document.getElementById('modal-serie-title').innerText = 'Editar Série';
		saveBtn.innerText = 'Alterar';
		fetch('/horarios/app/controllers/serie/listSerieByUser.php')
			.then(r => r.json())
			.then(data => {
			if (data.status === 'success') {
				const serie = data.data.find(item => item.id_serie == currentEditId);
				if (serie) {
				inputSerieId.value = serie.id_serie;
				selectNivelEnsino.value = serie.id_nivel_ensino;
				inputNomeSerie.value = serie.nome_serie || '';
				inputTotalAulas.value = serie.total_aulas_semana;
				openModal();
				}
			}
			});
		} else if (btn.classList.contains('btn-delete')) {
		currentEditId = id;
		openDeleteModal();
		} else if (btn.classList.contains('btn-print')) {
		// Impressão individual
		currentPrintSerieId = id;
		const tr = btn.closest('tr');
		const nomeSerie = tr.querySelector('td:nth-child(1)').textContent;
		const nivelEnsino = tr.querySelector('td:nth-child(2)').textContent;
		const totalAulas = tr.dataset.total;
		selectedSerieInput.value = nomeSerie;
		selectedTotalInput.value = totalAulas;
		selectedNivelInput.value = nivelEnsino;
		openPrintModalSerie();
		} else if (btn.classList.contains('btn-vincular-disciplina')) {
		const tr = btn.closest('tr');
		const serieName = tr.querySelector('td:nth-child(1)').textContent.trim();
		document.getElementById('nome-serie-disciplina').value = serieName;
		document.getElementById('select-serie-disciplina').value = id;
		openSerieDisciplinaModal();
		}
	});
	
	/* ============================================================
		 11) Modal de Exclusão
	============================================================ */
	function openDeleteModal() {
		modalDelete.style.display = 'block';
		modalDelete.classList.remove('fade-out');
		modalDelete.classList.add('fade-in');
		const content = modalDelete.querySelector('.modal-content');
		content.classList.remove('slide-up');
		content.classList.add('slide-down');
	}
	function closeDeleteModal() {
		const content = modalDelete.querySelector('.modal-content');
		content.classList.remove('slide-down');
		content.classList.add('slide-up');
		modalDelete.classList.remove('fade-in');
		modalDelete.classList.add('fade-out');
		setTimeout(() => {
		modalDelete.style.display = 'none';
		content.classList.remove('slide-up');
		modalDelete.classList.remove('fade-out');
		}, 300);
	}
	closeDeleteModalBtn.addEventListener('click', closeDeleteModal);
	cancelDeleteBtn.addEventListener('click', closeDeleteModal);
	confirmDeleteBtn.addEventListener('click', () => {
		fetch('/horarios/app/controllers/serie/deleteSerie.php', {
		method: 'POST',
		headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
		body: new URLSearchParams({ id: currentEditId })
		})
		.then(r => r.json())
		.then(response => {
		alert(response.message);
		if (response.status === 'success') {
			const row = document.querySelector(`tr[data-id="${currentEditId}"]`);
			if (row) row.remove();
			if (serieTableBody.children.length === 0) {
			noDataMessage.style.display = 'block';
			}
		}
		closeDeleteModal();
		})
		.catch(err => console.error(err));
	});
	
	/* ============================================================
		 12) Modal de Impressão INDIVIDUAL de Série
	============================================================ */
	function openPrintModalSerie() {
		modalPrintSerie.style.display = 'block';
		modalPrintSerie.classList.remove('fade-out');
		modalPrintSerie.classList.add('fade-in');
		const content = modalPrintSerie.querySelector('.modal-content');
		content.classList.remove('slide-up');
		content.classList.add('slide-down');
	}
	function closePrintModalSerie() {
		const content = modalPrintSerie.querySelector('.modal-content');
		content.classList.remove('slide-down');
		content.classList.add('slide-up');
		modalPrintSerie.classList.remove('fade-in');
		modalPrintSerie.classList.add('fade-out');
		setTimeout(() => {
		modalPrintSerie.style.display = 'none';
		content.classList.remove('slide-up');
		modalPrintSerie.classList.remove('fade-out');
		}, 300);
	}
	btnCancelarSerie.addEventListener('click', closePrintModalSerie);
	closePrintModalSerieBtn.addEventListener('click', closePrintModalSerie);
	btnImprimirSerie.addEventListener('click', () => {
		let url = '/horarios/app/views/serie.php?id_serie=' + currentPrintSerieId;
		if (chkTurmasSerie.checked) {
		url += '&turmas=1';
		}
		if (chkDiscSerie.checked) {
		url += '&disc=1';
		}
		window.open(url, '_blank');
		closePrintModalSerie();
	});
	
	/* ============================================================
		 13) Modal de Impressão GERAL de Série
	============================================================ */
	btnImprimirGeral.addEventListener('click', () => {
		// Reseta o dropdown e os checkboxes do modal geral
		selectNivelGeralSerie.value = "todos";
		chkTurmasSerieGeral.checked = false;
		chkDiscSerieGeral.checked = false;
		openPrintModalSerieGeral();
		});
	function openPrintModalSerieGeral() {
		modalPrintSerieGeral.style.display = 'block';
		modalPrintSerieGeral.classList.remove('fade-out');
		modalPrintSerieGeral.classList.add('fade-in');
		const content = modalPrintSerieGeral.querySelector('.modal-content');
		content.classList.remove('slide-up');
		content.classList.add('slide-down');
		}
		function closePrintModalSerieGeral() {
		const content = modalPrintSerieGeral.querySelector('.modal-content');
		content.classList.remove('slide-down');
		content.classList.add('slide-up');
		modalPrintSerieGeral.classList.remove('fade-in');
		modalPrintSerieGeral.classList.add('fade-out');
		setTimeout(() => {
			modalPrintSerieGeral.style.display = 'none';
			content.classList.remove('slide-up');
			modalPrintSerieGeral.classList.remove('fade-out');
		}, 300);
		}
		btnCancelarSerieGeral.addEventListener('click', closePrintModalSerieGeral);
		closePrintSerieGeralBtn.addEventListener('click', closePrintModalSerieGeral);
	btnImprimirSerieGeralConfirm.addEventListener('click', () => {
		let url = '/horarios/app/views/serie-geral.php?';
		// Envia o nível selecionado
		url += 'nivel=' + encodeURIComponent(selectNivelGeralSerie.value);
		if (chkTurmasSerieGeral.checked) {
			url += '&turmas=1';
		}
		if (chkDiscSerieGeral.checked) {
			url += '&disc=1';
		}
		window.open(url, '_blank');
		closePrintModalSerieGeral();
		});
	
	/* ============================================================
		 14) Pesquisa
	============================================================ */
	document.getElementById('search-input').addEventListener('input', function() {
		const searchValue = this.value.toLowerCase();
		const rows = serieTableBody.querySelectorAll('tr');
		let count = 0;
		rows.forEach(tr => {
		const nome = tr.querySelector('td:nth-child(1)').textContent.toLowerCase();
		const nivel = tr.querySelector('td:nth-child(2)').textContent.toLowerCase();
		if (nome.includes(searchValue) || nivel.includes(searchValue)) {
			tr.style.display = '';
			count++;
		} else {
			tr.style.display = 'none';
		}
		});
		noDataMessage.style.display = count === 0 ? 'block' : 'none';
	});
	
	/* ============================================================
		 15) Inicialização
	============================================================ */
	function init() {
		loadNiveisEnsino();
		fetchSeries();
	}
	init();
	});
	