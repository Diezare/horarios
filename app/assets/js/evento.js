// app/assets/js/evento.js
document.addEventListener('DOMContentLoaded', function () {
	/* =================================================================
		 0) Helpers
	================================================================= */
	// Coloca um placeholder PRESERVANDO as opções existentes
	function setPlaceholderPreserve(selectEl, label, value = '') {
		if (!selectEl) return;
		// remove placeholder antigo (qualquer option vazia no topo)
		const first = selectEl.options[0];
		if (first && first.value === '') {
			selectEl.remove(0);
		}
		const opt = document.createElement('option');
		opt.value = value;
		opt.textContent = label;
		selectEl.insertBefore(opt, selectEl.firstChild);
		selectEl.value = value;
	}

	// Coloca placeholder LIMPA/RECARREGA (para selects populados via fetch)
	function setPlaceholderAndClear(selectEl, label, value = '') {
		if (!selectEl) return;
		selectEl.innerHTML = '';
		const opt = document.createElement('option');
		opt.value = value;
		opt.textContent = label;
		selectEl.appendChild(opt);
		selectEl.value = value;
	}

	/* =================================================================
		 1) Referências do Modal de Cadastro/Edição
	================================================================= */
	const modal = document.getElementById('modal-evento');
	const btnAdd = document.getElementById('btn-add-evento');
	const closeModalElements = document.querySelectorAll('#modal-evento .close-modal');
	const cancelBtn = document.getElementById('cancel-btn');
	const saveBtn = document.getElementById('save-btn');

	// Modal de exclusão
	const modalDelete = document.getElementById('modal-delete');
	const closeDeleteModalBtn = document.getElementById('close-delete-modal');
	const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
	const cancelDeleteBtn = document.getElementById('cancel-delete-btn');

	// Tabela
	const eventoTableBody = document.getElementById('eventoTable');
	const noDataMessage = document.getElementById('no-data-message');

	// Form
	const inputEventoId = document.getElementById('eventoId');
	const selecionarAnoLetivo = document.getElementById('selecionarAnoLetivo');
	const selectTipoEvento = document.getElementById('tipoEvento'); // tem opções no HTML
	const inputNomeEvento = document.getElementById('nomeEvento');
	const inputDataInicio = document.getElementById('dataInicio');
	const inputDataFim = document.getElementById('dataFim');
	const inputObservacoes = document.getElementById('observacoes');

	let isEditMode = false;
	let currentEditId = null;
	let currentPrintId = null;

	/* =================================================================
		 2) Modal de Impressão (Individual)
	================================================================= */
	const modalPrintEvento = document.getElementById('modal-print-evento');
	const closePrintEventoBtn = document.getElementById('close-print-evento');
	const btnImprimirEvento = document.getElementById('btn-imprimir');
	const btnCancelarPrintEvento = document.getElementById('btn-cancelar');
	const selectedEventoInput = document.getElementById('selected-evento');

	const chkAnoLetivo = document.getElementById('chk-ano-letivo');
	const chkDetalhado = document.getElementById('chk-detalhado');

	/* =================================================================
		 3) Modal de Impressão (Geral)
	================================================================= */
	const btnImprimirGeral = document.getElementById('btnImprimir');
	const modalPrintEventoGeral = document.getElementById('modal-print-evento-geral');
	const closePrintEventoGeralBtn = document.getElementById('close-print-evento-geral');
	const btnImprimirEventoGeral = document.getElementById('btn-imprimir-geral-confirm');
	const btnCancelarEventoGeral = document.getElementById('btn-cancelar-geral');
	const selectAnoLetivoGeral = document.getElementById('select-ano-letivo-geral');

	const chkAnoLetivoGeral = document.getElementById('chk-ano-letivo-geral');
	const chkDetalhadoGeral = document.getElementById('chk-detalhado-geral');

	/* =================================================================
		 4) Ícones de Ordenação
	================================================================= */
	const sortAnoLetivoAsc = document.getElementById('sort-ano-letivo-asc');
	const sortAnoLetivoDesc = document.getElementById('sort-ano-letivo-desc');
	const sortTipoAsc = document.getElementById('sort-tipo-asc');
	const sortTipoDesc = document.getElementById('sort-tipo-desc');
	const sortNomeAsc = document.getElementById('sort-nome-asc');
	const sortNomeDesc = document.getElementById('sort-nome-desc');
	const sortDataAsc = document.getElementById('sort-data-asc');
	const sortDataDesc = document.getElementById('sort-data-desc');

	function sortTableByAnoLetivo(asc = true) {
		const rows = Array.from(eventoTableBody.querySelectorAll('tr'));
		rows.sort((a, b) => {
			const valA = a.querySelector('td:nth-child(1)').textContent.toLowerCase();
			const valB = b.querySelector('td:nth-child(1)').textContent.toLowerCase();
			return asc ? valA.localeCompare(valB) : valB.localeCompare(valA);
		});
		eventoTableBody.innerHTML = '';
		rows.forEach(row => eventoTableBody.appendChild(row));
	}

	function sortTableByTipo(asc = true) {
		const rows = Array.from(eventoTableBody.querySelectorAll('tr'));
		rows.sort((a, b) => {
			const valA = a.querySelector('td:nth-child(2)').textContent.toLowerCase();
			const valB = b.querySelector('td:nth-child(2)').textContent.toLowerCase();
			return asc ? valA.localeCompare(valB) : valB.localeCompare(valA);
		});
		eventoTableBody.innerHTML = '';
		rows.forEach(row => eventoTableBody.appendChild(row));
	}

	function sortTableByNome(asc = true) {
		const rows = Array.from(eventoTableBody.querySelectorAll('tr'));
		rows.sort((a, b) => {
			const valA = a.querySelector('td:nth-child(3)').textContent.toLowerCase();
			const valB = b.querySelector('td:nth-child(3)').textContent.toLowerCase();
			return asc ? valA.localeCompare(valB) : valB.localeCompare(valA);
		});
		eventoTableBody.innerHTML = '';
		rows.forEach(row => eventoTableBody.appendChild(row));
	}

	function sortTableByData(asc = true) {
		const rows = Array.from(eventoTableBody.querySelectorAll('tr'));
		rows.sort((a, b) => {
			const valA = new Date(a.querySelector('td:nth-child(4)').textContent);
			const valB = new Date(b.querySelector('td:nth-child(4)').textContent);
			return asc ? valA - valB : valB - valA;
		});
		eventoTableBody.innerHTML = '';
		rows.forEach(row => eventoTableBody.appendChild(row));
	}

	if (sortAnoLetivoAsc) sortAnoLetivoAsc.addEventListener('click', () => sortTableByAnoLetivo(true));
	if (sortAnoLetivoDesc) sortAnoLetivoDesc.addEventListener('click', () => sortTableByAnoLetivo(false));
	if (sortTipoAsc) sortTipoAsc.addEventListener('click', () => sortTableByTipo(true));
	if (sortTipoDesc) sortTipoDesc.addEventListener('click', () => sortTableByTipo(false));
	if (sortNomeAsc) sortNomeAsc.addEventListener('click', () => sortTableByNome(true));
	if (sortNomeDesc) sortNomeDesc.addEventListener('click', () => sortTableByNome(false));
	if (sortDataAsc) sortDataAsc.addEventListener('click', () => sortTableByData(true));
	if (sortDataDesc) sortDataDesc.addEventListener('click', () => sortTableByData(false));

	/* =================================================================
		 5) Carregar e Exibir Eventos
	================================================================= */
	function loadEventos() {
		fetch('/horarios/app/controllers/evento/listEvento.php')
			.then(r => r.json())
			.then(data => {
				if (data.status === 'success') {
					renderTable(data.data);
				} else {
					console.error(data.message);
				}
			})
			.catch(err => console.error(err));
	}

	function renderTable(rows) {
		eventoTableBody.innerHTML = '';
		if (!rows || rows.length === 0) {
			noDataMessage.style.display = 'block';
			return;
		}
		noDataMessage.style.display = 'none';

		rows.forEach(row => {
			const tr = document.createElement('tr');
			tr.dataset.id = row.id_evento;

			const tdAnoLetivo = document.createElement('td');
			tdAnoLetivo.textContent = row.ano;
			tr.appendChild(tdAnoLetivo);

			const tdTipo = document.createElement('td');
			tdTipo.textContent = capitalizeFirst(row.tipo_evento);
			tr.appendChild(tdTipo);

			const tdNome = document.createElement('td');
			tdNome.textContent = row.nome_evento;
			tr.appendChild(tdNome);

			const tdDataInicio = document.createElement('td');
			tdDataInicio.textContent = formatDate(row.data_inicio);
			tr.appendChild(tdDataInicio);

			const tdDataFim = document.createElement('td');
			tdDataFim.textContent = formatDate(row.data_fim);
			tr.appendChild(tdDataFim);

			const tdActions = document.createElement('td');

			const btnEdit = document.createElement('button');
			btnEdit.classList.add('btn-edit');
			btnEdit.dataset.id = row.id_evento;
			btnEdit.innerHTML = `
				<span class="icon"><i class="fa-solid fa-pen-to-square"></i></span>
				<span class="text">Editar</span>`;
			tdActions.appendChild(btnEdit);

			const btnDelete = document.createElement('button');
			btnDelete.classList.add('btn-delete');
			btnDelete.dataset.id = row.id_evento;
			btnDelete.innerHTML = `
				<span class="icon"><i class="fa-solid fa-trash"></i></span>
				<span class="text">Deletar</span>`;
			tdActions.appendChild(btnDelete);

			const btnPrint = document.createElement('button');
			btnPrint.classList.add('btn-print');
			btnPrint.dataset.id = row.id_evento;
			btnPrint.innerHTML = `
				<span class="icon"><i class="fa-solid fa-print"></i></span>
				<span class="text">Imprimir</span>`;
			tdActions.appendChild(btnPrint);

			tr.appendChild(tdActions);
			eventoTableBody.appendChild(tr);
		});
	}

	/* =================================================================
		 6) Preencher SELECT de Ano Letivo (Cadastro/Edição)
	================================================================= */
	function loadAnosLetivosInSelect(preselectId = '') {
		// placeholder para Ano Letivo (este é carregado via fetch)
		setPlaceholderAndClear(selecionarAnoLetivo, '-- Selecione o Ano --', '');
		fetch('/horarios/app/controllers/ano-letivo/listAnoLetivo.php')
			.then(r => r.json())
			.then(data => {
				if (data.status === 'success') {
					(data.data || []).forEach(ano => {
						const option = document.createElement('option');
						option.value = ano.id_ano_letivo;
						option.textContent = ano.ano;
						selecionarAnoLetivo.appendChild(option);
					});
					if (preselectId) selecionarAnoLetivo.value = String(preselectId);
				} else {
					console.error(data.message);
				}
			})
			.catch(err => console.error(err));
	}

	/* =================================================================
		 7) Modal de Cadastro/Edição
	================================================================= */
	function openModal() {
		modal.style.display = 'block';
		modal.classList.remove('fade-out');
		modal.classList.add('fade-in');

		const content = modal.querySelector('.modal-content');
		content.classList.remove('slide-up');
		content.classList.add('slide-down');

		if (!isEditMode) {
			clearForm();
			document.getElementById('modal-title').innerText = 'Adicionar Evento';
			saveBtn.innerText = 'Salvar';
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
		inputEventoId.value = '';
		inputNomeEvento.value = '';
		inputDataInicio.value = '';
		inputDataFim.value = '';
		inputObservacoes.value = '';

		// ANO LETIVO (via fetch) → limpa e coloca placeholder
		setPlaceholderAndClear(selecionarAnoLetivo, '-- Selecione o Ano --', '');

		// TIPO DE EVENTO (opções estão no HTML) → preserva opções
		setPlaceholderPreserve(selectTipoEvento, '-- Selecione o Evento --', '');
	}

	// Abrir para adicionar
	btnAdd.addEventListener('click', () => {
		isEditMode = false;
		openModal();
		clearForm();
		loadAnosLetivosInSelect();
	});

	closeModalElements.forEach(el => el.addEventListener('click', closeModal));
	cancelBtn.addEventListener('click', () => {
		clearForm();
		closeModal();
	});

	/* =================================================================
		 8) Salvar (Insert / Update)
	================================================================= */
	saveBtn.addEventListener('click', () => {
		const idEvento = inputEventoId.value;
		const idAnoLetivo = selecionarAnoLetivo.value;
		const tipoEvento = selectTipoEvento.value;
		const nomeEvento = inputNomeEvento.value.trim();
		const dataInicio = inputDataInicio.value;
		const dataFim = inputDataFim.value;
		const observacoes = inputObservacoes.value.trim();

		// valida (não permitir placeholders vazios)
		if (!idAnoLetivo || !tipoEvento || !nomeEvento || !dataInicio || !dataFim) {
			alert('Preencha todos os campos obrigatórios.');
			return;
		}

		if (new Date(dataFim) < new Date(dataInicio)) {
			alert('A data de fim deve ser igual ou posterior à data de início.');
			return;
		}

		const data = new URLSearchParams({
			id_evento: idEvento,
			id_ano_letivo: idAnoLetivo,
			tipo_evento: tipoEvento,
			nome_evento: nomeEvento,
			data_inicio: dataInicio,
			data_fim: dataFim,
			observacoes: observacoes
		});

		if (isEditMode) {
			fetch('/horarios/app/controllers/evento/updateEvento.php', {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: data
			})
				.then(r => r.json())
				.then(resp => {
					alert(resp.message);
					if (resp.status === 'success') {
						closeModal();
						loadEventos();
					}
				})
				.catch(err => console.error(err));
		} else {
			fetch('/horarios/app/controllers/evento/insertEvento.php', {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: data
			})
				.then(r => r.json())
				.then(resp => {
					if (resp.status === 'success') {
						if (confirm('Evento cadastrado com sucesso! Deseja cadastrar outro evento?')) {
							clearForm();
							loadAnosLetivosInSelect();
						} else {
							closeModal();
						}
						loadEventos();
					} else {
						alert(resp.message);
					}
				})
				.catch(err => console.error(err));
		}
	});

	/* =================================================================
		 9) Ações na Tabela
	================================================================= */
	eventoTableBody.addEventListener('click', e => {
		const btn = e.target.closest('button');
		if (!btn) return;

		const id = btn.dataset.id;

		if (btn.classList.contains('btn-edit')) {
			isEditMode = true;
			currentEditId = id;

			fetch('/horarios/app/controllers/evento/listEvento.php')
				.then(r => r.json())
				.then(data => {
					if (data.status === 'success') {
						const found = data.data.find(x => x.id_evento == currentEditId);
						if (found) {
							inputEventoId.value = found.id_evento;
							inputNomeEvento.value = found.nome_evento;
							inputDataInicio.value = found.data_inicio;
							inputDataFim.value = found.data_fim;
							inputObservacoes.value = found.observacoes || '';

							// tipo de evento: preserva opções e seleciona a existente
							setPlaceholderPreserve(selectTipoEvento, '-- Selecione o Evento --', '');
							selectTipoEvento.value = found.tipo_evento;

							document.getElementById('modal-title').innerText = 'Editar Evento';
							saveBtn.innerText = 'Alterar';

							// ano letivo
							setPlaceholderAndClear(selecionarAnoLetivo, '-- Selecione o Ano --', '');
							loadAnosLetivosInSelect(found.id_ano_letivo);

							openModal();
						}
					}
				})
				.catch(err => console.error(err));
		} else if (btn.classList.contains('btn-delete')) {
			currentEditId = id;
			openDeleteModal();
		} else if (btn.classList.contains('btn-print')) {
			currentPrintId = id;
			const row = btn.closest('tr');
			const ano = row.querySelector('td:nth-child(1)').textContent;
			const tipo = row.querySelector('td:nth-child(2)').textContent;
			const nome = row.querySelector('td:nth-child(3)').textContent;
			selectedEventoInput.value = ano + ' - ' + tipo + ' - ' + nome;
			openPrintModal();
		}
	});

	/* =================================================================
		 10) Modal de Exclusão
	================================================================= */
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
		fetch('/horarios/app/controllers/evento/deleteEvento.php', {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: new URLSearchParams({ id: currentEditId })
		})
			.then(r => r.json())
			.then(resp => {
				alert(resp.message);
				if (resp.status === 'success') {
					const row = document.querySelector(`tr[data-id="${currentEditId}"]`);
					if (row) row.remove();
					if (eventoTableBody.children.length === 0) {
						noDataMessage.style.display = 'block';
					}
				}
				closeDeleteModal();
			})
			.catch(err => console.error(err));
	});

	/* =================================================================
		 11) Modal de Impressão (Individual)
	================================================================= */
	function openPrintModal() {
		modalPrintEvento.style.display = 'block';
		modalPrintEvento.classList.remove('fade-out');
		modalPrintEvento.classList.add('fade-in');

		const content = modalPrintEvento.querySelector('.modal-content');
		content.classList.remove('slide-up');
		content.classList.add('slide-down');
	}

	function closePrintModal() {
		const content = modalPrintEvento.querySelector('.modal-content');
		content.classList.remove('slide-down');
		content.classList.add('slide-up');
		modalPrintEvento.classList.remove('fade-in');
		modalPrintEvento.classList.add('fade-out');

		setTimeout(() => {
			modalPrintEvento.style.display = 'none';
			content.classList.remove('slide-up');
			modalPrintEvento.classList.remove('fade-out');
		}, 300);
	}

	btnCancelarPrintEvento.addEventListener('click', closePrintModal);
	closePrintEventoBtn.addEventListener('click', closePrintModal);

	btnImprimirEvento.addEventListener('click', () => {
		const url = '/horarios/app/views/evento.php?id_evento=' + currentPrintId;
		window.open(url, '_blank');
		closePrintModal();
	});

	/* =================================================================
		 12) Modal de Impressão (Geral)
	================================================================= */
	btnImprimirGeral.addEventListener('click', () => {
		setPlaceholderAndClear(selectAnoLetivoGeral, '-- Selecione o Ano --', '');
		fetch('/horarios/app/controllers/ano-letivo/listAnoLetivo.php')
			.then(r => r.json())
			.then(data => {
				if (data.status === 'success') {
					(data.data || []).forEach(ano => {
						const option = document.createElement('option');
						option.value = ano.id_ano_letivo;
						option.textContent = ano.ano;
						selectAnoLetivoGeral.appendChild(option);
					});
				}
			})
			.catch(err => console.error(err));

		openPrintModalGeral();
	});

	function openPrintModalGeral() {
		modalPrintEventoGeral.style.display = 'block';
		modalPrintEventoGeral.classList.remove('fade-out');
		modalPrintEventoGeral.classList.add('fade-in');

		const content = modalPrintEventoGeral.querySelector('.modal-content');
		content.classList.remove('slide-up');
		content.classList.add('slide-down');
	}

	function closePrintModalGeral() {
		const content = modalPrintEventoGeral.querySelector('.modal-content');
		content.classList.remove('slide-down');
		content.classList.add('slide-up');
		modalPrintEventoGeral.classList.remove('fade-in');
		modalPrintEventoGeral.classList.add('fade-out');

		setTimeout(() => {
			modalPrintEventoGeral.style.display = 'none';
			content.classList.remove('slide-up');
			modalPrintEventoGeral.classList.remove('fade-out');
		}, 300);
	}

	btnCancelarEventoGeral.addEventListener('click', closePrintModalGeral);
	closePrintEventoGeralBtn.addEventListener('click', closePrintModalGeral);

	btnImprimirEventoGeral.addEventListener('click', () => {
		const anoLetivoValue = selectAnoLetivoGeral.value;
		const tipoEventoValue = document.getElementById('tipoEventoFiltro').value;

		if (!anoLetivoValue) {
			alert('Selecione um ano letivo para imprimir.');
			return;
		}

		const params = new URLSearchParams();
		params.append('id_ano_letivo', anoLetivoValue);

		if (tipoEventoValue && tipoEventoValue !== 'todos') {
			params.append('tipo_evento', tipoEventoValue);
		} else {
			params.append('tipo_evento', 'todos');
		}

		const url = `/horarios/app/views/evento-geral.php?${params.toString()}`;
		window.open(url, '_blank');
		closePrintModalGeral();
	});

	/* =================================================================
		 13) Filtro de Pesquisa na Tabela
	================================================================= */
	document.getElementById('search-input').addEventListener('input', function () {
		const searchValue = this.value.toLowerCase();
		const rows = eventoTableBody.querySelectorAll('tr');
		let count = 0;

		rows.forEach(tr => {
			const anoLetivo = tr.querySelector('td:nth-child(1)').textContent.toLowerCase();
			const tipoEvento = tr.querySelector('td:nth-child(2)').textContent.toLowerCase();
			const nomeEvento = tr.querySelector('td:nth-child(3)').textContent.toLowerCase();

			if (anoLetivo.includes(searchValue) ||
					tipoEvento.includes(searchValue) ||
					nomeEvento.includes(searchValue)) {
				tr.style.display = '';
				count++;
			} else {
				tr.style.display = 'none';
			}
		});
		noDataMessage.style.display = (count === 0) ? 'block' : 'none';
	});

	/* =================================================================
		 14) Auxiliares
	================================================================= */
	function capitalizeFirst(str) {
		if (!str) return '';
		return str.charAt(0).toUpperCase() + str.slice(1);
	}

	function formatDate(dateStr) {
		if (!dateStr) return '';
		const date = new Date(dateStr + 'T00:00:00');
		return date.toLocaleDateString('pt-BR');
	}

	/* =================================================================
		 15) Inicialização
	================================================================= */
	function init() {
		loadEventos();

		// Garante placeholders iniciais sem sumir com as opções
		setPlaceholderPreserve(selectTipoEvento, '-- Selecione o Evento --', '');
		setPlaceholderAndClear(selecionarAnoLetivo, '-- Selecione o Ano --', '');
	}
	init();
});
 