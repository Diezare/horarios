// app/assets/js/ano-letivo.js
document.addEventListener('DOMContentLoaded', function() {
	/* ===========================================================
		 0) REFERÊNCIAS DE ELEMENTOS
	=========================================================== */
	const modal = document.getElementById('modal-anoLetivo');
	const btnAdd = document.getElementById('btn-add');
	const closeModalElements = document.querySelectorAll('.close-modal');
	const cancelBtn = document.getElementById('cancel-btn');
	const saveBtn = document.getElementById('save-btn');

	// Modal de impressão individual
	const modalPrint = document.getElementById('modal-print');
	const closePrintModalBtn = document.getElementById('close-print-modal');
	const btnImprimir = document.getElementById('btn-imprimir');
	const btnCancelar = document.getElementById('btn-cancelar');
	
	// Modal de impressão geral
	const modalPrintGeral = document.getElementById('modal-print-geral');
	const closePrintGeralModalBtn = document.getElementById('close-print-geral-modal');
	const btnImprimirGeral = document.getElementById('btn-imprimir-geral');
	const btnCancelarGeral = document.getElementById('btn-cancelar-geral');
	const btnImprimirGeralTrigger = document.getElementById('btnImprimir'); // Botão na tela principal

	// Checkboxes e Select de professor (no modal de impressão individual)
	const chkTurmas = document.getElementById('chk-turmas');
	const chkProfRestricao = document.getElementById('chk-prof-restricao');
	const selectProfRestricao = document.getElementById('select-prof-restricao');
	const selectedAnoP = document.getElementById('selected-ano');
	const professorSelectRow = document.getElementById('professor-select-row'); 

	// Checkboxes e Select de professor (no modal de impressão geral)
	const chkTurmasGeral = document.getElementById('chk-turmas-geral');
	const chkProfRestricaoGeral = document.getElementById('chk-prof-restricao-geral');
	const selectProfRestricaoGeral = document.getElementById('select-prof-restricao-geral');
	const professorSelectRowGeral = document.getElementById('professor-select-row-geral'); 

	// Modal de exclusão
	const modalDelete = document.getElementById('modal-delete');
	const closeDeleteModalBtn = document.getElementById('close-delete-modal');
	const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
	const cancelDeleteBtn = document.getElementById('cancel-delete-btn');

	const anoLetivoTableBody = document.getElementById('anoLetivoTable');
	const noDataMessage = document.getElementById('no-data-message');

	// Setas para ordenação
	const sortAscBtn = document.getElementById('sort-asc');
	const sortDescBtn = document.getElementById('sort-desc');

	let isEditMode = false;
	let currentEditId = null;
	let currentPrintAnoId = null; // Guarda o id do ano letivo que será impresso

	// Guardar todos os anos para poder ordenar client-side
	let anoLetivoData = [];

	// Campo "ano" - somente números e no máximo 4 dígitos
	const anoInput = document.getElementById('ano');
	anoInput.addEventListener('input', function() {
		this.value = this.value.replace(/\D/g, '');
		if (this.value.length > 4) {
			this.value = this.value.slice(0, 4);
		}
	});

	/* ===========================================================
		 1) LISTAR DADOS (READ)
	=========================================================== */
	function fetchAnoLetivo() {
		fetch('/horarios/app/controllers/ano-letivo/listAnoLetivo.php')
			.then(r => r.json())
			.then(data => {
				if (data.status === 'success') {
					anoLetivoData = data.data || [];
					renderTable(anoLetivoData);
				} else {
					console.error(data.message);
				}
			})
			.catch(err => console.error(err));
	}

	/* ===========================================================
		 2) MONTAR TABELA
	=========================================================== */
	function renderTable(rows) {
		anoLetivoTableBody.innerHTML = '';
		if (!rows || rows.length === 0) {
			noDataMessage.style.display = 'block';
			return;
		}
		noDataMessage.style.display = 'none';

		rows.forEach(row => {
			const tr = document.createElement('tr');
			tr.dataset.id = row.id_ano_letivo;

			const tdAno = document.createElement('td');
			tdAno.textContent = row.ano;
			tr.appendChild(tdAno);

			const tdActions = document.createElement('td');
			
			// AÇÕES (Caso queira remover depois, só comentar)
			// Botão Editar
			const btnEdit = document.createElement('button');
			btnEdit.classList.add('btn-edit');
			btnEdit.dataset.id = row.id_ano_letivo;
			btnEdit.innerHTML = `
				<span class="icon"><i class="fa-solid fa-pen-to-square"></i></span>
				<span class="text">Editar</span>`;
			tdActions.appendChild(btnEdit);

			// Botão Deletar
			const btnDelete = document.createElement('button');
			btnDelete.classList.add('btn-delete');
			btnDelete.dataset.id = row.id_ano_letivo;
			btnDelete.innerHTML = `
				<span class="icon"><i class="fa-solid fa-trash"></i></span>
				<span class="text">Deletar</span>`;
			tdActions.appendChild(btnDelete);

			// Botão Imprimir
			const btnPrint = document.createElement('button');
			btnPrint.classList.add('btn-print');
			btnPrint.dataset.id = row.id_ano_letivo;
			btnPrint.innerHTML = `
				<span class="icon"><i class="fa-solid fa-print"></i></span>
				<span class="text">Imprimir</span>`;
			tdActions.appendChild(btnPrint);

			tr.appendChild(tdActions);
			anoLetivoTableBody.appendChild(tr);
		});
	}

	// Carrega a lista ao iniciar
	fetchAnoLetivo();

	/* ===========================================================
		 3) ABRIR E FECHAR MODAL (CADASTRO/EDIÇÃO)
	=========================================================== */
	function openModal() {
		modal.style.display = 'block';
		modal.classList.remove('fade-out');
		modal.classList.add('fade-in');

		const content = modal.querySelector('.modal-content');
		content.classList.remove('slide-up');
		content.classList.add('slide-down');

		if (!isEditMode) {
			clearForm();
			document.getElementById('modal-title').innerText = 'Adicionar Ano Letivo';
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
		document.getElementById('anoLetivoId').value = '';
		document.getElementById('ano').value = '';
		document.getElementById('data_inicio').value = '';
		document.getElementById('data_fim').value = '';
	}

	// Botão para abrir modal (ADICIONAR)
	btnAdd.addEventListener('click', () => {
		isEditMode = false;
		openModal();
	});

	// Fecha modal ao clicar no X
	closeModalElements.forEach(el => {
		el.addEventListener('click', function() {
			// Se este X pertencer ao modal principal, fecha
			if (el.id === 'close-modal') closeModal();
		});
	});

	// Botão CANCELAR no modal de cadastro/edição
	cancelBtn.addEventListener('click', () => {
		if (!isEditMode) {
			clearForm();
		}
		closeModal();
	});

	/* ===========================================================
		 4) SALVAR (INSERT ou UPDATE)
	=========================================================== */
	saveBtn.addEventListener('click', () => {
		const id = document.getElementById('anoLetivoId').value;
		const ano = document.getElementById('ano').value.trim();
		const dataInicio = document.getElementById('data_inicio').value;
		const dataFim = document.getElementById('data_fim').value;

		if (!ano || !dataInicio || !dataFim) {
			alert('Preencha todos os campos.');
			return;
		}

		if (ano.length !== 4 || !/^\d{4}$/.test(ano)) {
			alert('O campo ano deve conter exatamente 4 dígitos.');
			return;
		}

		if (isEditMode) {
			// UPDATE
			fetch('/horarios/app/controllers/ano-letivo/updateAnoLetivo.php', {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: new URLSearchParams({
					id,
					ano,
					data_inicio: dataInicio,
					data_fim: dataFim
				})
			})
			.then(r => r.json())
			.then(data => {
				alert(data.message);
				if (data.status === 'success') {
					closeModal();
					fetchAnoLetivo();
				}
			})
			.catch(err => console.error(err));
		} else {
			// INSERT
			fetch('/horarios/app/controllers/ano-letivo/insertAnoLetivo.php', {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: new URLSearchParams({
					ano,
					data_inicio: dataInicio,
					data_fim: dataFim
				})
			})
			.then(r => r.json())
			.then(data => {
				alert(data.message);
				if (data.status === 'success') {
					closeModal();
					fetchAnoLetivo();
				}
			})
			.catch(err => console.error(err));
		}
	});

	/* ===========================================================
		 5) AÇÕES NA TABELA (EDITAR, DELETAR, IMPRIMIR)
	=========================================================== */
	anoLetivoTableBody.addEventListener('click', e => {
		const btn = e.target.closest('button');
		if (!btn) return;

		if (btn.classList.contains('btn-edit')) {
			isEditMode = true;
			currentEditId = btn.dataset.id;

			// Busca dados para editar
			fetch('/horarios/app/controllers/ano-letivo/listAnoLetivo.php')
				.then(r => r.json())
				.then(data => {
					if (data.status === 'success') {
						const found = data.data.find(item => item.id_ano_letivo == currentEditId);
						if (found) {
							document.getElementById('anoLetivoId').value = found.id_ano_letivo;
							document.getElementById('ano').value = found.ano;
							document.getElementById('data_inicio').value = found.data_inicio;
							document.getElementById('data_fim').value = found.data_fim;

							document.getElementById('modal-title').innerText = 'Editar Ano Letivo';
							saveBtn.innerText = 'Alterar';
							openModal();
						}
					}
				});
		} else if (btn.classList.contains('btn-delete')) {
			currentEditId = btn.dataset.id;
			openDeleteModal();
		} else if (btn.classList.contains('btn-print')) {
			// Ao clicar em imprimir, captura o id e o ano letivo para exibir no modal (individual)
			currentPrintAnoId = btn.dataset.id;
			const anoValue = btn.closest('tr').querySelector('td').textContent;
			selectedAnoP.value = anoValue;

			// Reseta checkboxes e select
			chkTurmas.checked = false;
			chkProfRestricao.checked = false;
			professorSelectRow.style.display = 'none';
			selectProfRestricao.innerHTML = '<option value="todas">Todos</option>';

			openPrintModal();
		}
	});

	/* ===========================================================
		 6) MODAL DE EXCLUSÃO
	=========================================================== */
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
		fetch('/horarios/app/controllers/ano-letivo/deleteAnoLetivo.php', {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: new URLSearchParams({ id: currentEditId })
		})
		.then(r => r.json())
		.then(data => {
			alert(data.message);
			if (data.status === 'success') {
				// Remove da tabela
				const row = document.querySelector(`tr[data-id="${currentEditId}"]`);
				if (row) row.remove();
				if (anoLetivoTableBody.children.length === 0) {
					noDataMessage.style.display = 'block';
				}
			}
			closeDeleteModal();
		})
		.catch(err => console.error(err));
	});

	/* ===========================================================
		 7) MODAL DE IMPRESSÃO (INDIVIDUAL)
	=========================================================== */
	function openPrintModal() {
		modalPrint.style.display = 'block';
		modalPrint.classList.remove('fade-out');
		modalPrint.classList.add('fade-in');

		const content = modalPrint.querySelector('.modal-content');
		content.classList.remove('slide-up');
		content.classList.add('slide-down');
	}

	function closePrintModal() {
		const content = modalPrint.querySelector('.modal-content');
		content.classList.remove('slide-down');
		content.classList.add('slide-up');
		modalPrint.classList.remove('fade-in');
		modalPrint.classList.add('fade-out');

		setTimeout(() => {
			modalPrint.style.display = 'none';
			content.classList.remove('slide-up');
			modalPrint.classList.remove('fade-out');
		}, 300);
	}

	btnCancelar.addEventListener('click', closePrintModal);
	closePrintModalBtn.addEventListener('click', closePrintModal);

	/* ===========================================================
		 8) FILTROS NO MODAL DE IMPRESSÃO INDIVIDUAL
	=========================================================== */
	chkProfRestricao.addEventListener('change', function() {
		if (this.checked) {
			professorSelectRow.style.display = 'flex';
			// Carrega professores que tenham restrição para este ano letivo
			fetch('/horarios/app/controllers/professor-restricoes/listProfessorRestricoesPorAnoLetivo.php?id_ano=' + currentPrintAnoId)
				.then(r => r.json())
				.then(data => {
					if (data.status === 'success') {
						selectProfRestricao.innerHTML = '<option value="todas">Todos</option>';
						data.data.forEach(item => {
							const option = document.createElement('option');
							option.value = item.id_professor;
							option.textContent = item.nome_professor;
							selectProfRestricao.appendChild(option);
						});
					}
				})
				.catch(err => console.error(err));
		} else {
			professorSelectRow.style.display = 'none';
		}
	});

	btnImprimir.addEventListener('click', () => {
		// Monta a URL do PDF individual
		let url = '/horarios/app/views/ano-letivo.php?id_ano=' + currentPrintAnoId;

		if (chkTurmas.checked) {
			url += '&turma=1';
		}
		if (chkProfRestricao.checked) {
			const prof = selectProfRestricao.value;
			url += '&prof_restricao=' + encodeURIComponent(prof);
		}

		window.open(url, '_blank');
		closePrintModal();
	});

	/* ===========================================================
		 9) PESQUISA (FILTRAR NA TABELA)
	=========================================================== */
	document.getElementById('search-input').addEventListener('input', function() {
		const searchValue = this.value.toLowerCase();
		const rows = anoLetivoTableBody.querySelectorAll('tr');
		let count = 0;

		rows.forEach(tr => {
			const tdAno = tr.querySelector('td').textContent.toLowerCase();
			if (tdAno.includes(searchValue)) {
				tr.style.display = '';
				count++;
			} else {
				tr.style.display = 'none';
			}
		});
		noDataMessage.style.display = count === 0 ? 'block' : 'none';
	});

	/* ===========================================================
		10) IMPRESSÃO GERAL (NOVO MODAL)
	=========================================================== */
	btnImprimirGeralTrigger.addEventListener('click', openPrintGeralModal);

	function openPrintGeralModal() {
		// Resetar checkboxes e select
		chkTurmasGeral.checked = false;
		chkProfRestricaoGeral.checked = false;
		professorSelectRowGeral.style.display = 'none';
		selectProfRestricaoGeral.innerHTML = '<option value="todas">Todos</option>';

		// Abre modal
		modalPrintGeral.style.display = 'block';
		modalPrintGeral.classList.remove('fade-out');
		modalPrintGeral.classList.add('fade-in');

		const content = modalPrintGeral.querySelector('.modal-content');
		content.classList.remove('slide-up');
		content.classList.add('slide-down');
	}

	function closePrintGeralModal() {
		const content = modalPrintGeral.querySelector('.modal-content');
		content.classList.remove('slide-down');
		content.classList.add('slide-up');
		modalPrintGeral.classList.remove('fade-in');
		modalPrintGeral.classList.add('fade-out');

		setTimeout(() => {
			modalPrintGeral.style.display = 'none';
			content.classList.remove('slide-up');
			modalPrintGeral.classList.remove('fade-out');
		}, 300);
	}

	btnCancelarGeral.addEventListener('click', closePrintGeralModal);
	closePrintGeralModalBtn.addEventListener('click', closePrintGeralModal);

	chkProfRestricaoGeral.addEventListener('change', function() {
		if (this.checked) {
			professorSelectRowGeral.style.display = 'flex';
			// Carregar todos os professores utilizando o novo arquivo:
			fetch('/horarios/app/controllers/professor/listAllProfessor.php')
				.then(r => r.json())
				.then(data => {
					if (data.status === 'success') {
						selectProfRestricaoGeral.innerHTML = '<option value="todas">Todos</option>';
						data.data.forEach(item => {
							const option = document.createElement('option');
							option.value = item.id_professor;
							option.textContent = item.nome_completo;
							selectProfRestricaoGeral.appendChild(option);
						});
					}
				})
				.catch(err => console.error(err));
		} else {
			professorSelectRowGeral.style.display = 'none';
		}
	});
	

	btnImprimirGeral.addEventListener('click', () => {
		// Monta a URL para ano-letivo-geral.php
		let url = '/horarios/app/views/ano-letivo-geral.php?'; // sem id_ano

		// Verifica se turmas foi marcado
		if (chkTurmasGeral.checked) {
			url += 'turma=1&';
		}
		// Verifica se professor restrição foi marcado
		if (chkProfRestricaoGeral.checked) {
			const prof = selectProfRestricaoGeral.value;
			url += 'prof_restricao=' + encodeURIComponent(prof) + '&';
		}

		window.open(url, '_blank');
		closePrintGeralModal();
	});

	/* ===========================================================
		11) ORDENAÇÃO PELO ANO LETIVO (SETAS)
	=========================================================== */
	sortAscBtn.addEventListener('click', () => {
		// Ordena em ordem crescente
		anoLetivoData.sort((a, b) => parseInt(b.ano) - parseInt(a.ano));
		renderTable(anoLetivoData);
	});
	
	sortDescBtn.addEventListener('click', () => {
		// Ordena em ordem decrescente
		anoLetivoData.sort((a, b) => parseInt(a.ano) - parseInt(b.ano));
		renderTable(anoLetivoData);
	});
});
