// app/assets/js/modalidade.js
document.addEventListener('DOMContentLoaded', function() {
	/* ============================================================
		 1) Referências Gerais
	============================================================ */
	const modal = document.getElementById('modal-modalidade');
	const btnAdd = document.getElementById('btn-add-modalidade');
	const closeModalEls = document.querySelectorAll('#modal-modalidade .close-modal');
	const cancelBtn = document.getElementById('cancel-btn');
	const saveBtn = document.getElementById('save-btn');

	// Modal de exclusão
	const modalDelete = document.getElementById('modal-delete');
	const closeDeleteModalBtn = document.getElementById('close-delete-modal'); // Renomeado para evitar conflito
	const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
	const cancelDeleteBtn = document.getElementById('cancel-delete-btn');

	// Tabela de Modalidades
	const modalidadeTableBody = document.getElementById('modalidadeTable');
	const noDataMessage = document.getElementById('no-data-message');

	// Campos do formulário
	const inputModalidadeId = document.getElementById('modalidadeId');
	const inputNomeModalidade = document.getElementById('nomeModalidade');

	let isEditMode = false;
	let currentEditId  = null;
	let currentPrintId = null; // Para impressão individual

	/* ============================================================
		 2) Modal de Impressão INDIVIDUAL
	============================================================ */
	const modalPrintModalidade = document.getElementById('modal-print-modalidade');
	const closePrintModalidadeBtn = document.getElementById('close-print-modal');
	const btnImprimirModalidade = document.getElementById('btn-imprimir');
	const btnCancelarPrintModalidade = document.getElementById('btn-cancelar');
	const selectedModalidadeInput = document.getElementById('selected-modalidade');
	const chkCategoria = document.getElementById('chk-categoria');

	/* ============================================================
		 3) Modal de Impressão GERAL
	============================================================ */
	const btnImprimirGeral = document.getElementById('btnImprimir');
	const modalPrintModalidadeGeral = document.getElementById('modal-print-modalidade-geral');
	const closePrintModalidadeGeralBtn = document.getElementById('close-print-geral');
	const btnImprimirModalidadeGeral = document.getElementById('btn-imprimir-geral-confirm');
	const btnCancelarModalidadeGeral = document.getElementById('btn-cancelar-geral');
	const selectModalidadeGeral = document.getElementById('select-modalidade-geral');
	const chkCategoriaGeral = document.getElementById('chk-categoria-geral');

	/* ============================================================
		 4) Sort Icons (Para a coluna nome da Modalidade)
	============================================================ */
	const sortAscIcon = document.getElementById('sort-asc');
	const sortDescIcon = document.getElementById('sort-desc');

	function sortTable(asc = true) {
		const rows = Array.from(modalidadeTableBody.querySelectorAll('tr'));
		rows.sort((a, b) => {
			const valA = a.querySelector('td').textContent.toLowerCase();
			const valB = b.querySelector('td').textContent.toLowerCase();
			return asc ? valA.localeCompare(valB) : valB.localeCompare(valA);
		});
		modalidadeTableBody.innerHTML = '';
		rows.forEach(row => modalidadeTableBody.appendChild(row));
	}
	sortAscIcon.addEventListener('click', () => sortTable(true));
	sortDescIcon.addEventListener('click', () => sortTable(false));

	/* ============================================================
		 5) Carregar Modalidades (Listagem)
	============================================================ */
	function loadModalidades() {
		// Substitua pelo seu endpoint de listagem
		// Se você criou 'listModalidade.php' sem parâmetros, retorna tudo
		fetch('/horarios/app/controllers/modalidade/listModalidade.php')
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
		modalidadeTableBody.innerHTML = '';
		if (!rows || rows.length === 0) {
			noDataMessage.style.display = 'block';
			return;
		}
		noDataMessage.style.display = 'none';

		rows.forEach(row => {
			const tr = document.createElement('tr');
			tr.dataset.id = row.id_modalidade;

			// 1a coluna: Nome da Modalidade
			const tdNome = document.createElement('td');
			tdNome.textContent = row.nome_modalidade;
			tr.appendChild(tdNome);

			// 2a coluna: Ações
			const tdActions = document.createElement('td');
			// Botão Editar
			const btnEdit = document.createElement('button');
			btnEdit.classList.add('btn-edit');
			btnEdit.dataset.id = row.id_modalidade;
			btnEdit.innerHTML = `
				<span class="icon"><i class="fa-solid fa-pen-to-square"></i></span>
				<span class="text">Editar</span>`;
			tdActions.appendChild(btnEdit);

			// Botão Deletar
			const btnDelete = document.createElement('button');
			btnDelete.classList.add('btn-delete');
			btnDelete.dataset.id = row.id_modalidade;
			btnDelete.innerHTML = `
				<span class="icon"><i class="fa-solid fa-trash"></i></span>
				<span class="text">Deletar</span>`;
			tdActions.appendChild(btnDelete);

			// Botão Imprimir (individual)
			const btnPrint = document.createElement('button');
			btnPrint.classList.add('btn-print');
			btnPrint.dataset.id = row.id_modalidade;
			btnPrint.innerHTML = `
				<span class="icon"><i class="fa-solid fa-print"></i></span>
				<span class="text">Imprimir</span>`;
			tdActions.appendChild(btnPrint);

			tr.appendChild(tdActions);
			modalidadeTableBody.appendChild(tr);
		});
	}

	/* ============================================================
		 6) Modal de Cadastro/Edição
	============================================================ */
	function openModal() {
		modal.style.display = 'block';
		modal.classList.remove('fade-out');
		modal.classList.add('fade-in');
		const content = modal.querySelector('.modal-content');
		content.classList.remove('slide-up');
		content.classList.add('slide-down');

		if (!isEditMode) {
			clearForm();
			document.getElementById('modal-title').innerText = 'Adicionar Modalidade';
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
		inputModalidadeId.value   = '';
		inputNomeModalidade.value = '';
	}

	btnAdd.addEventListener('click', () => {
		isEditMode = false;
		openModal();
	});

	closeModalEls.forEach(el => el.addEventListener('click', closeModal));
	cancelBtn.addEventListener('click', () => {
		clearForm();
		closeModal();
	});

	/* ============================================================
		 7) Salvar (Insert ou Update)
	============================================================ */
	saveBtn.addEventListener('click', () => {
		const id   = inputModalidadeId.value;
		const nome = inputNomeModalidade.value.trim();

		if (!nome) {
			alert('Preencha o nome da modalidade.');
			return;
		}

		const data = new URLSearchParams({
			id_modalidade: id,
			nome_modalidade: nome
		});

		if (isEditMode) {
			// UPDATE
			fetch('/horarios/app/controllers/modalidade/updateModalidade.php', {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: data
			})
			.then(r => r.json())
			.then(response => {
				alert(response.message);
				if (response.status === 'success') {
					closeModal();
					loadModalidades();
				}
			})
			.catch(err => console.error(err));
		} else {
			// INSERT
			fetch('/horarios/app/controllers/modalidade/insertModalidade.php', {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: data
			})
			.then(r => r.json())
			.then(response => {
				alert(response.message);
				if (response.status === 'success') {
					closeModal();
					loadModalidades();
				}
			})
			.catch(err => console.error(err));
		}
	});

	/* ============================================================
		 8) Ações na Tabela (Editar, Deletar, Imprimir)
	============================================================ */
	modalidadeTableBody.addEventListener('click', e => {
		const btn = e.target.closest('button');
		if (!btn) return;

		const id = btn.dataset.id;

		if (btn.classList.contains('btn-edit')) {
			isEditMode	= true;
			currentEditId = id;

			// Carrega a lista completa (ou apenas 1) para encontrar o registro
			fetch('/horarios/app/controllers/modalidade/listModalidade.php')
				.then(r => r.json())
				.then(data => {
					if (data.status === 'success') {
						const found = data.data.find(item => item.id_modalidade == currentEditId);
						if (found) {
							inputModalidadeId.value   = found.id_modalidade;
							inputNomeModalidade.value = found.nome_modalidade;

							document.getElementById('modal-title').innerText = 'Editar Modalidade';
							saveBtn.innerText = 'Alterar';
							openModal();
						}
					}
				});

		} else if (btn.classList.contains('btn-delete')) {
			currentEditId = id;
			openDeleteModal();

		} else if (btn.classList.contains('btn-print')) {
			currentPrintId = id;
			const row	  = btn.closest('tr');
			const modNome  = row.querySelector('td:nth-child(1)').textContent;

			selectedModalidadeInput.value = modNome;
			chkCategoria.checked		  = false;
			openPrintModal();
		}
	});

	/* ============================================================
		 9) Modal de Exclusão
	============================================================ */
	function openDeleteModal() {
		modalDelete.style.display = 'block';
		modalDelete.classList.remove('fade-out');
		modalDelete.classList.add('fade-in');
		const content = modalDelete.querySelector('.modal-content');
		content.classList.remove('slide-up');
		content.classList.add('slide-down');
	}

	// Renomear a função de fechamento do modal de exclusão
	function closeDeleteModalFn() {
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

	// Ajustar o listener do botão (não chama a função com o mesmo nome da const)
	closeDeleteModalBtn.addEventListener('click', closeDeleteModalFn);
	cancelDeleteBtn.addEventListener('click', closeDeleteModalFn);

	confirmDeleteBtn.addEventListener('click', () => {
		fetch('/horarios/app/controllers/modalidade/deleteModalidade.php', {
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
				if (modalidadeTableBody.children.length === 0) {
					noDataMessage.style.display = 'block';
				}
			}
			closeDeleteModalFn();
		})
		.catch(err => console.error(err));
	});

	/* ============================================================
		10) Modal de Impressão (Individual)
	============================================================ */
	function openPrintModal() {
		modalPrintModalidade.style.display = 'block';
		modalPrintModalidade.classList.remove('fade-out');
		modalPrintModalidade.classList.add('fade-in');
		const content = modalPrintModalidade.querySelector('.modal-content');
		content.classList.remove('slide-up');
		content.classList.add('slide-down');
	}

	function closePrintModal() {
		const content = modalPrintModalidade.querySelector('.modal-content');
		content.classList.remove('slide-down');
		content.classList.add('slide-up');
		modalPrintModalidade.classList.remove('fade-in');
		modalPrintModalidade.classList.add('fade-out');
		setTimeout(() => {
			modalPrintModalidade.style.display = 'none';
			content.classList.remove('slide-up');
			modalPrintModalidade.classList.remove('fade-out');
			// Limpa checkbox
			chkCategoria.checked = false;
		}, 300);
	}

	btnCancelarPrintModalidade.addEventListener('click', closePrintModal);
	closePrintModalidadeBtn.addEventListener('click', closePrintModal);

	btnImprimirModalidade.addEventListener('click', () => {
		// Monta a URL do relatório de Modalidade (individual)
		let url = '/horarios/app/views/modalidade.php?id_modalidade=' + currentPrintId;
		if (chkCategoria.checked) {
			url += '&categoria=1';
		}
		window.open(url, '_blank');
		closePrintModal();
	});

	/* ============================================================
		 11) Modal de Impressão (Geral)
	============================================================ */
	btnImprimirGeral.addEventListener('click', () => {
		// Carrega novamente as modalidades para preencher o select
		fetch('/horarios/app/controllers/modalidade/listModalidade.php')
			.then(r => r.json())
			.then(data => {
				if (data.status === 'success') {
					selectModalidadeGeral.innerHTML = '<option value="todos">Todas</option>';
					data.data.forEach(mod => {
						const option = document.createElement('option');
						option.value = mod.id_modalidade;
						option.textContent = mod.nome_modalidade;
						selectModalidadeGeral.appendChild(option);
					});
				}
			})
			.catch(err => console.error(err));

		chkCategoriaGeral.checked = false;
		openPrintModalGeral();
	});

	function openPrintModalGeral() {
		modalPrintModalidadeGeral.style.display = 'block';
		modalPrintModalidadeGeral.classList.remove('fade-out');
		modalPrintModalidadeGeral.classList.add('fade-in');
		const content = modalPrintModalidadeGeral.querySelector('.modal-content');
		content.classList.remove('slide-up');
		content.classList.add('slide-down');
	}

	function closePrintModalGeral() {
		const content = modalPrintModalidadeGeral.querySelector('.modal-content');
		content.classList.remove('slide-down');
		content.classList.add('slide-up');
		modalPrintModalidadeGeral.classList.remove('fade-in');
		modalPrintModalidadeGeral.classList.add('fade-out');
		setTimeout(() => {
			modalPrintModalidadeGeral.style.display = 'none';
			content.classList.remove('slide-up');
			modalPrintModalidadeGeral.classList.remove('fade-out');
			// Limpa checkbox
			chkCategoriaGeral.checked = false;
		}, 300);
	}

	btnCancelarModalidadeGeral.addEventListener('click', closePrintModalGeral);
	closePrintModalidadeGeralBtn.addEventListener('click', closePrintModalGeral);

	btnImprimirModalidadeGeral.addEventListener('click', () => {
		// Monta a URL do relatório geral
		let url = '/horarios/app/views/modalidade-geral.php?modalidade=' + encodeURIComponent(selectModalidadeGeral.value);
		if (chkCategoriaGeral.checked) {
			url += '&categoria=1';
		}
		window.open(url, '_blank');
		closePrintModalGeral();
	});

	/* ============================================================
		 12) Pesquisa (Filtro na Tabela)
	============================================================ */
	document.getElementById('search-input').addEventListener('input', function() {
		const searchValue = this.value.toLowerCase();
		const rows = modalidadeTableBody.querySelectorAll('tr');
		let count = 0;

		rows.forEach(tr => {
			const nome = tr.querySelector('td:nth-child(1)').textContent.toLowerCase();
			if (nome.includes(searchValue)) {
				tr.style.display = '';
				count++;
			} else {
				tr.style.display = 'none';
			}
		});
		noDataMessage.style.display = count === 0 ? 'block' : 'none';
	});

	/* ============================================================
		 13) Inicialização
	============================================================ */
	function init() {
		loadModalidades();
	}
	init();
});
