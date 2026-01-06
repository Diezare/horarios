// app/assets/js/nivel-ensino.js
document.addEventListener('DOMContentLoaded', function() {
	/* ============================================================
		 1) Referências Gerais
	============================================================ */
	const modal = document.getElementById('modal-nivel-ensino');
	const btnAdd = document.getElementById('btn-add-nivel');
	const closeModalElements = document.querySelectorAll('#modal-nivel-ensino .close-modal');
	const cancelBtn = document.getElementById('cancel-btn');
	const saveBtn = document.getElementById('save-btn');
	
	// Modal de exclusão
	const modalDelete = document.getElementById('modal-delete-serie'); // Ajuste se houver um modal exclusivo para nível
	const closeDeleteModalBtn = document.getElementById('close-delete-modal');
	const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
	const cancelDeleteBtn = document.getElementById('cancel-delete-btn');
	
	// Tabela de Níveis de Ensino
	const nivelEnsinoTableBody = document.getElementById('nivelEnsinoTable');
	const noDataMessage = document.getElementById('no-data-message');
	
	// Campos do formulário
	const inputNivelEnsinoId = document.getElementById('nivelEnsinoId');
	const inputNomeNivelEnsino = document.getElementById('nomeNivelEnsino');
	
	let isEditMode = false;
	let currentEditId = null;
	let currentPrintId = null; // Para impressão individual
	
	// Array para armazenar os IDs (em string) dos níveis aos quais o usuário tem acesso
	let allowedNiveis = [];
	
	/* ============================================================
		 2) Modal de Impressão INDIVIDUAL de Nível de Ensino
	============================================================ */
	const modalPrintNivel = document.getElementById('modal-print-nivel');
	const closePrintNivelModalBtn = document.getElementById('close-print-nivel');
	const btnImprimirNivel = document.getElementById('btn-imprimir');
	const btnCancelarPrintNivel = document.getElementById('btn-cancelar');
	const selectedNivelInput = document.getElementById('selected-nivel');
	const chkSeries = document.getElementById('chk-series');
	const chkUsuarios = document.getElementById('chk-usuarios');
	
	/* ============================================================
		 3) Modal de Impressão GERAL de Nível de Ensino
		 (IDs com sufixo "-geral" para evitar duplicidade)
	============================================================ */
	const btnImprimirGeral = document.getElementById('btnImprimir'); // Botão de imprimir geral na card-header
	const modalPrintNivelGeral = document.getElementById('modal-print-nivel-geral');
	const closePrintNivelGeralBtn = document.getElementById('close-print-nivel-geral');
	const btnImprimirNivelGeralConfirm = document.getElementById('btn-imprimir-nivel-geral-confirm');
	const btnCancelarNivelGeral = document.getElementById('btn-cancelar-nivel-geral');
	const selectNivelGeral = document.getElementById('select-nivel-geral');
	const chkSeriesGeral = document.getElementById('chk-series-geral');
	const chkUsuariosGeral = document.getElementById('chk-usuarios-geral');
	
	/* ============================================================
		 4) Sort Icons
	============================================================ */
	const sortAscIcon = document.getElementById('sort-asc');
	const sortDescIcon = document.getElementById('sort-desc');
	
	function sortTable(asc = true) {
		const rows = Array.from(nivelEnsinoTableBody.querySelectorAll('tr'));
		rows.sort((a, b) => {
		const valA = a.querySelector('td').textContent.toLowerCase();
		const valB = b.querySelector('td').textContent.toLowerCase();
		return asc ? valA.localeCompare(valB) : valB.localeCompare(valA);
		});
		nivelEnsinoTableBody.innerHTML = '';
		rows.forEach(row => nivelEnsinoTableBody.appendChild(row));
	}
	sortAscIcon.addEventListener('click', () => sortTable(true));
	sortDescIcon.addEventListener('click', () => sortTable(false));
	
	/* ============================================================
		 5) Carregar Níveis de Ensino
		 - Usa o endpoint listNivelEnsinoByUser.php para retornar somente os níveis permitidos
	============================================================ */
	function loadNiveisEnsino() {
		fetch('/horarios/app/controllers/nivel-ensino/listNivelEnsinoByUser.php')
		.then(r => r.json())
		.then(data => {
			if (data.status === 'success') {
			renderTable(data.data);
			// Armazena os IDs permitidos (como strings)
			allowedNiveis = data.data.map(nivel => nivel.id_nivel_ensino.toString());
			// Preenche o dropdown do modal geral com a opção "Todos" + os níveis autorizados
			selectNivelGeral.innerHTML = '<option value="todos">Todos</option>';
			data.data.forEach(nivel => {
				const option = document.createElement('option');
				option.value = nivel.id_nivel_ensino;
				option.textContent = nivel.nome_nivel_ensino;
				selectNivelGeral.appendChild(option);
			});
			} else {
			console.error(data.message);
			}
		})
		.catch(err => console.error(err));
	}
	
	function renderTable(rows) {
		nivelEnsinoTableBody.innerHTML = '';
		if (!rows || rows.length === 0) {
		noDataMessage.style.display = 'block';
		return;
		}
		noDataMessage.style.display = 'none';
		rows.forEach(row => {
		const tr = document.createElement('tr');
		tr.dataset.id = row.id_nivel_ensino;
		const tdNome = document.createElement('td');
		tdNome.textContent = row.nome_nivel_ensino;
		tr.appendChild(tdNome);
		const tdActions = document.createElement('td');
		// Botão Editar
		const btnEdit = document.createElement('button');
		btnEdit.classList.add('btn-edit');
		btnEdit.dataset.id = row.id_nivel_ensino;
		btnEdit.innerHTML = `<span class="icon"><i class="fa-solid fa-pen-to-square"></i></span>
							 <span class="text">Editar</span>`;
		tdActions.appendChild(btnEdit);
		// Botão Deletar
		const btnDelete = document.createElement('button');
		btnDelete.classList.add('btn-delete');
		btnDelete.dataset.id = row.id_nivel_ensino;
		btnDelete.innerHTML = `<span class="icon"><i class="fa-solid fa-trash"></i></span>
								 <span class="text">Deletar</span>`;
		tdActions.appendChild(btnDelete);
		// Botão Imprimir (individual)
		const btnPrint = document.createElement('button');
		btnPrint.classList.add('btn-print');
		btnPrint.dataset.id = row.id_nivel_ensino;
		btnPrint.innerHTML = `<span class="icon"><i class="fa-solid fa-print"></i></span>
								<span class="text">Imprimir</span>`;
		tdActions.appendChild(btnPrint);
		tr.appendChild(tdActions);
		nivelEnsinoTableBody.appendChild(tr);
		});
	}
	
	/* ============================================================
		 6) Modal de Cadastro/Edição de Nível de Ensino
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
		document.getElementById('modal-title').innerText = 'Adicionar Nível de Ensino';
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
		document.getElementById('nivelEnsinoId').value = '';
		document.getElementById('nomeNivelEnsino').value = '';
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
		 7) Salvar Nível de Ensino (Insert/Update)
	============================================================ */
	saveBtn.addEventListener('click', () => {
		const id = document.getElementById('nivelEnsinoId').value;
		const nome = document.getElementById('nomeNivelEnsino').value.trim();
		if (!nome) {
		alert('Preencha o nome do nível de ensino.');
		return;
		}
		const data = new URLSearchParams({
		id_nivel_ensino: id,
		nome_nivel_ensino: nome
		});
		if (isEditMode) {
		fetch('/horarios/app/controllers/nivel-ensino/updateNivelEnsino.php', {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: data
		})
		.then(r => r.json())
		.then(response => {
			alert(response.message);
			if (response.status === 'success') {
			closeModal();
			loadNiveisEnsino();
			}
		})
		.catch(err => console.error(err));
		} else {
		fetch('/horarios/app/controllers/nivel-ensino/insertNivelEnsino.php', {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: data
		})
		.then(r => r.json())
		.then(response => {
			alert(response.message);
			if (response.status === 'success') {
			closeModal();
			loadNiveisEnsino();
			}
		})
		.catch(err => console.error(err));
		}
	});
	
	/* ============================================================
		 8) Ações na Tabela (Editar, Deletar, Imprimir)
	============================================================ */
	nivelEnsinoTableBody.addEventListener('click', e => {
		const btn = e.target.closest('button');
		if (!btn) return;
		const id = btn.dataset.id;
		if (btn.classList.contains('btn-edit')) {
		isEditMode = true;
		currentEditId = id;
		fetch('/horarios/app/controllers/nivel-ensino/listNivelEnsino.php')
			.then(r => r.json())
			.then(data => {
			if (data.status === 'success') {
				const found = data.data.find(item => item.id_nivel_ensino == currentEditId);
				if (found) {
				document.getElementById('nivelEnsinoId').value = found.id_nivel_ensino;
				document.getElementById('nomeNivelEnsino').value = found.nome_nivel_ensino;
				document.getElementById('modal-title').innerText = 'Editar Nível de Ensino';
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
		const row = btn.closest('tr');
		const nivelNome = row.querySelector('td:nth-child(1)').textContent;
		// Verifica se o nível do registro está entre os níveis permitidos
		if (!allowedNiveis.includes(currentPrintId.toString())) {
			alert('Você não tem permissão para acessar o relatório deste nível de ensino.');
			return;
		}
		selectedNivelInput.value = nivelNome;
		chkSeries.checked = false;
		chkUsuarios.checked = false;
		openPrintModalNivel();
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
		fetch('/horarios/app/controllers/nivel-ensino/deleteNivelEnsino.php', {
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
			if (nivelEnsinoTableBody.children.length === 0) {
			noDataMessage.style.display = 'block';
			}
		}
		closeDeleteModal();
		})
		.catch(err => console.error(err));
	});
	
	/* ============================================================
		 10) Modal de Impressão INDIVIDUAL de Nível de Ensino
	============================================================ */
	function openPrintModalNivel() {
		modalPrintNivel.style.display = 'block';
		modalPrintNivel.classList.remove('fade-out');
		modalPrintNivel.classList.add('fade-in');
		const content = modalPrintNivel.querySelector('.modal-content');
		content.classList.remove('slide-up');
		content.classList.add('slide-down');
	}
	function closePrintModalNivel() {
		const content = modalPrintNivel.querySelector('.modal-content');
		content.classList.remove('slide-down');
		content.classList.add('slide-up');
		modalPrintNivel.classList.remove('fade-in');
		modalPrintNivel.classList.add('fade-out');
		setTimeout(() => {
		modalPrintNivel.style.display = 'none';
		content.classList.remove('slide-up');
		modalPrintNivel.classList.remove('fade-out');
		}, 300);
	}
	btnCancelarPrintNivel.addEventListener('click', closePrintModalNivel);
	closePrintNivelModalBtn.addEventListener('click', closePrintModalNivel);
	btnImprimirNivel.addEventListener('click', () => {
		let url = '/horarios/app/views/nivel-ensino.php?id_nivel=' + currentPrintId;
		if (chkSeries.checked) { url += '&series=1'; }
		if (chkUsuarios.checked) { url += '&usuarios=1'; }
		window.open(url, '_blank');
		closePrintModalNivel();
	});
	
	/* ============================================================
		 11) Modal de Impressão GERAL de Nível de Ensino
	============================================================ */
	btnImprimirGeral.addEventListener('click', () => {
		// Preenche o dropdown com a opção "Todos" seguida dos níveis permitidos
		selectNivelGeral.innerHTML = '<option value="todos">Todos</option>';
		allowedNiveis.forEach(id => {
		// Buscamos o nome correspondente na tabela (pode ser feito com um simples loop na tabela ou armazenado anteriormente)
		// Para simplicidade, se o dropdown já foi preenchido via loadNiveisEnsino, podemos reconstruí-lo:
		// (Aqui, vamos refazer a consulta para preencher o dropdown novamente com todos os níveis autorizados)
		// Se preferir, pode armazenar os dados completos em allowedNiveisData.
		});
		// Recarrega o dropdown com os níveis autorizados (usando o endpoint já usado)
		fetch('/horarios/app/controllers/nivel-ensino/listNivelEnsinoByUser.php')
		.then(r => r.json())
		.then(data => {
			if (data.status === 'success') {
			// Inicia com a opção "Todos"
			selectNivelGeral.innerHTML = '<option value="todos">Todos</option>';
			data.data.forEach(nivel => {
				const option = document.createElement('option');
				option.value = nivel.id_nivel_ensino;
				option.textContent = nivel.nome_nivel_ensino;
				selectNivelGeral.appendChild(option);
			});
			}
		})
		.catch(err => console.error(err));
		chkSeriesGeral.checked = false;
		chkUsuariosGeral.checked = false;
		openPrintModalNivelGeral();
	});
	function openPrintModalNivelGeral() {
		modalPrintNivelGeral.style.display = 'block';
		modalPrintNivelGeral.classList.remove('fade-out');
		modalPrintNivelGeral.classList.add('fade-in');
		const content = modalPrintNivelGeral.querySelector('.modal-content');
		content.classList.remove('slide-up');
		content.classList.add('slide-down');
	}
	function closePrintModalNivelGeral() {
		const content = modalPrintNivelGeral.querySelector('.modal-content');
		content.classList.remove('slide-down');
		content.classList.add('slide-up');
		modalPrintNivelGeral.classList.remove('fade-in');
		modalPrintNivelGeral.classList.add('fade-out');
		setTimeout(() => {
		modalPrintNivelGeral.style.display = 'none';
		content.classList.remove('slide-up');
		modalPrintNivelGeral.classList.remove('fade-out');
		}, 300);
	}
	btnCancelarNivelGeral.addEventListener('click', closePrintModalNivelGeral);
	closePrintNivelGeralBtn.addEventListener('click', closePrintModalNivelGeral);
	btnImprimirNivelGeralConfirm.addEventListener('click', () => {
		let url = '/horarios/app/views/nivel-ensino-geral.php?';
		url += 'nivel=' + encodeURIComponent(selectNivelGeral.value);
		if (chkSeriesGeral.checked) { url += '&series=1'; }
		if (chkUsuariosGeral.checked) { url += '&usuarios=1'; }
		window.open(url, '_blank');
		closePrintModalNivelGeral();
	});
	
	/* ============================================================
		 12) Pesquisa
	============================================================ */
	document.getElementById('search-input').addEventListener('input', function() {
		const searchValue = this.value.toLowerCase();
		const rows = nivelEnsinoTableBody.querySelectorAll('tr');
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
		loadNiveisEnsino();
	}
	init();
	});
	