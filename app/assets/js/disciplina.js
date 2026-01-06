// app/assets/js/disciplina.js
document.addEventListener('DOMContentLoaded', function() {
	/* ============================================================
		0) REFERÊNCIAS
	============================================================ */
	const modal = document.getElementById('modal-disciplina');
	const btnAdd = document.getElementById('btn-add');
	const closeModalElements = document.querySelectorAll('.close-modal');
	const cancelBtn = document.getElementById('cancel-btn');
	const saveBtn = document.getElementById('save-btn');
	
	// Modal de exclusão
	const modalDelete = document.getElementById('modal-delete');
	const closeDeleteModalBtn = document.getElementById('close-delete-modal');
	const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
	const cancelDeleteBtn = document.getElementById('cancel-delete-btn');
	
	// Tabela e mensagem
	const disciplinaTableBody = document.getElementById('disciplinaTable');
	const noDataMessage = document.getElementById('no-data-message');
	
	// Modal de impressão (individual)
	const modalPrintDisc = document.getElementById('modal-print-disciplina');
	const closePrintDiscModalBtn = document.getElementById('close-print-modal-disc');
	const btnImprimirDisc = document.getElementById('btn-imprimir');
	const btnCancelarDisc = document.getElementById('btn-cancelar');
	
	// Campos do modal individual
	const selectedDiscInput = document.getElementById('selected-disciplina');
	const chkNivel = document.getElementById('chk-nivel');
	const selectNivel = document.getElementById('select-nivel');
	const chkProfDT = document.getElementById('chk-prof-dt');
	const selectProfDT = document.getElementById('select-prof-dt');
	
	// Modal de impressão GERAL
	const modalPrintGeralDisc = document.getElementById('modal-print-disciplina-geral');
	const closePrintGeralDiscModalBtn = document.getElementById('close-print-modal-disc-geral');
	const btnImprimirGeralDisc = document.getElementById('btn-imprimir-geral-disc');
	const btnCancelarGeralDisc = document.getElementById('btn-cancelar-geral-disc');
	const btnImprimirGeralDiscTrigger = document.getElementById('btnImprimir');
	
	// Check + selects do modal geral
	const chkNivelGeral = document.getElementById('chk-nivel-geral');
	const selectNivelGeral = document.getElementById('select-nivel-geral');
	const chkProfDTGeral = document.getElementById('chk-prof-dt-geral');
	const selectProfDTGeral = document.getElementById('select-prof-dt-geral');
	
	// Setas de ordenação (nome disciplina)
	const sortDiscAscBtn	= document.getElementById('sort-disc-asc');
	const sortDiscDescBtn = document.getElementById('sort-disc-desc');
	
	// Setas de ordenação (sigla)
	const sortSiglaAscBtn	= document.getElementById('sort-sigla-asc');
	const sortSiglaDescBtn = document.getElementById('sort-sigla-desc');
	
	// Arrays ou variáveis de controle
	let disciplinaData = []; // Para armazenar as disciplinas e poder ordenar
	let isEditMode = false;
	let currentEditId = null;			// Para edição
	let currentPrintDiscId = null; // Para impressão individual

	/* ============================================================
		0.1) HELPERS DE FETCH E PREENCHIMENTO
	============================================================ */
	async function fetchJSON(url) {
		const r = await fetch(url, { credentials: 'same-origin' });
		return r.json();
	}

	function resetSelect(el, placeholder = 'Todos') {
		el.innerHTML = `<option value="todas">${placeholder}</option>`;
	}

	function fillSelect(el, rows, valueKey, labelKey, includeAll = true, placeholder = 'Todos') {
		el.innerHTML = '';
		if (includeAll) {
			const o = document.createElement('option');
			o.value = 'todas';
			o.textContent = placeholder;
			el.appendChild(o);
		}
		rows.forEach(r => {
			const o = document.createElement('option');
			o.value = r[valueKey];
			o.textContent = r[labelKey];
			el.appendChild(o);
		});
	}

	// Níveis que o usuário tem acesso
	async function loadNiveisInto(selectEl) {
		const data = await fetchJSON('/horarios/app/controllers/nivel-ensino/listNivelEnsinoByUser.php');
		if (data.status === 'success') {
			const rows = (data.data || []).map(x => ({ nome: x.nome_nivel_ensino }));
			fillSelect(selectEl, rows, 'nome', 'nome', true, 'Todas');
		}
	}

	// Professores por nível
	async function loadProfessoresByNivelInto(selectEl, nivelValor) {
		let url = '/horarios/app/controllers/professor/listProfessorByNivel.php';
		if (nivelValor && nivelValor !== 'todas') {
			url += '?nivel=' + encodeURIComponent(nivelValor);
		}
		const data = await fetchJSON(url);
		if (data.status === 'success') {
			const rows = (data.data || []).map(x => ({ id: x.id_professor, nome: x.nome_completo }));
			fillSelect(selectEl, rows, 'id', 'nome', true, 'Todos');
		}
	}

	// Níveis onde o professor atua
	async function loadNiveisByProfessorInto(selectEl, profId) {
		if (!profId || profId === 'todas') { // volta ao padrão do usuário
			await loadNiveisInto(selectEl);
			return;
		}
		const url = '/horarios/app/controllers/nivel-ensino/listNivelByProfessor.php?prof=' + encodeURIComponent(profId);
		const data = await fetchJSON(url);
		if (data.status === 'success') {
			const rows = (data.data || []).map(x => ({ nome: x.nome_nivel_ensino }));
			fillSelect(selectEl, rows, 'nome', 'nome', true, 'Todas');
		}
	}

	/* ============================================================
		1) LISTAR DISCIPLINAS (READ)
	============================================================ */
	function fetchDisciplina() {
		fetch('/horarios/app/controllers/disciplina/listDisciplina.php')
			.then(r => r.json())
			.then(data => {
				if (data.status === 'success') {
					disciplinaData = data.data || [];
					renderTable(disciplinaData);
				} else {
					console.error(data.message);
				}
			})
			.catch(err => console.error(err));
	}
	
	function renderTable(rows) {
		disciplinaTableBody.innerHTML = '';
		if (!rows || rows.length === 0) {
			noDataMessage.style.display = 'block';
			return;
		}
		noDataMessage.style.display = 'none';
	
		rows.forEach(row => {
			const tr = document.createElement('tr');
			tr.dataset.id = row.id_disciplina;
	
			const tdNome = document.createElement('td');
			tdNome.textContent = row.nome_disciplina;
			tr.appendChild(tdNome);
	
			const tdSigla = document.createElement('td');
			tdSigla.textContent = row.sigla_disciplina;
			tr.appendChild(tdSigla);
	
			const tdActions = document.createElement('td');
			
			// Botão Editar
			const btnEdit = document.createElement('button');
			btnEdit.classList.add('btn-edit');
			btnEdit.dataset.id = row.id_disciplina;
			btnEdit.innerHTML = `
				<span class="icon"><i class="fa-solid fa-pen-to-square"></i></span>
				<span class="text">Editar</span>`;
			tdActions.appendChild(btnEdit);
	
			// Botão Deletar
			const btnDelete = document.createElement('button');
			btnDelete.classList.add('btn-delete');
			btnDelete.dataset.id = row.id_disciplina;
			btnDelete.innerHTML = `
				<span class="icon"><i class="fa-solid fa-trash"></i></span>
				<span class="text">Deletar</span>`;
			tdActions.appendChild(btnDelete);
	
			// Botão Imprimir (individual)
			const btnPrint = document.createElement('button');
			btnPrint.classList.add('btn-print');
			btnPrint.dataset.id = row.id_disciplina;
			btnPrint.innerHTML = `
				<span class="icon"><i class="fa-solid fa-print"></i></span>
				<span class="text">Imprimir</span>`;
			tdActions.appendChild(btnPrint);
	
			tr.appendChild(tdActions);
			disciplinaTableBody.appendChild(tr);
		});
	}
	
	// Chama ao carregar a página
	fetchDisciplina();
	
	/* ============================================================
		2) ABRIR/FECHAR MODAL (CADASTRO/EDIÇÃO)
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
			document.getElementById('modal-title').innerText = 'Adicionar Disciplina';
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
		document.getElementById('disciplinaId').value = '';
		document.getElementById('disciplina').value = '';
		document.getElementById('sigla').value = '';
	}
	
	// Botão para abrir modal de adicionar
	btnAdd.addEventListener('click', () => {
		isEditMode = false;
		openModal();
	});
	
	// Fecha modal ao clicar em .close-modal
	closeModalElements.forEach(el => {
		el.addEventListener('click', closeModal);
	});
	
	// Botão CANCELAR
	cancelBtn.addEventListener('click', () => {
		if (!isEditMode) clearForm();
		closeModal();
	});
	
	/* ============================================================
		3) SIGLA -> MAIÚSCULAS
	============================================================ */
	const siglaInput = document.getElementById('sigla');
	siglaInput.addEventListener('input', function() {
		this.value = this.value.toUpperCase();
	});
	
	/* ============================================================
		4) SALVAR (INSERT/UPDATE)
	============================================================ */
	saveBtn.addEventListener('click', () => {
		const id	 = document.getElementById('disciplinaId').value;
		const nome = document.getElementById('disciplina').value.trim();
		const sigl = siglaInput.value.trim().toUpperCase();
	
		if (!nome || !sigl) {
			alert('Preencha todos os campos.');
			return;
		}
		if (!/^[A-Z]{3}$/.test(sigl)) {
			alert('A sigla deve conter exatamente 3 letras maiúsculas.');
			return;
		}
	
		const formData = new URLSearchParams({
			id_disciplina: id,
			nome_disciplina: nome,
			sigla_disciplina: sigl
		});
	
		if (isEditMode) {
			fetch('/horarios/app/controllers/disciplina/updateDisciplina.php', {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: formData
			})
			.then(r => r.json())
			.then(data => {
				alert(data.message);
				if (data.status === 'success') {
					closeModal();
					fetchDisciplina();
				}
			})
			.catch(err => console.error(err));
		} else {
			formData.delete('id_disciplina');
			fetch('/horarios/app/controllers/disciplina/insertDisciplina.php', {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: formData
			})
			.then(r => r.json())
			.then(data => {
				if (data.status === 'success') {
					fetchDisciplina();
					const resp = confirm('Disciplina inserida com sucesso! Deseja inserir outra?');
					if (resp) {
						clearForm();
					} else {
						closeModal();
					}
				} else {
					alert(data.message);
				}
			})
			.catch(err => console.error(err));
		}
	});
	
	/* ============================================================
		5) AÇÕES NA TABELA (EDITAR, DELETAR, IMPRIMIR)
	============================================================ */
	disciplinaTableBody.addEventListener('click', e => {
		const btn = e.target.closest('button');
		if (!btn) return;
	
		if (btn.classList.contains('btn-edit')) {
			isEditMode = true;
			currentEditId = btn.dataset.id;
			fetch('/horarios/app/controllers/disciplina/listDisciplina.php')
				.then(r => r.json())
				.then(data => {
					if (data.status === 'success') {
						const found = data.data.find(item => item.id_disciplina == currentEditId);
						if (found) {
							document.getElementById('disciplinaId').value = found.id_disciplina;
							document.getElementById('disciplina').value	 = found.nome_disciplina;
							document.getElementById('sigla').value				= found.sigla_disciplina.toUpperCase();
							document.getElementById('modal-title').innerText = 'Editar Disciplina';
							saveBtn.innerText = 'Alterar';
							openModal();
						}
					}
				});
		} else if (btn.classList.contains('btn-delete')) {
			currentEditId = btn.dataset.id;
			openDeleteModal();
		} else if (btn.classList.contains('btn-print')) {
			currentPrintDiscId = btn.dataset.id;
			const discName = btn.closest('tr').querySelector('td:nth-child(1)').textContent;
			selectedDiscInput.value = discName;

			// reset filtros individuais
			chkNivel.checked = false;
			selectNivel.style.display = 'none';
			resetSelect(selectNivel, 'Todas');

			chkProfDT.checked = false;
			selectProfDT.style.display = 'none';
			resetSelect(selectProfDT, 'Todos');

			openPrintDiscModal();
		}
	});
	
	/* ============================================================
		6) MODAL DE EXCLUSÃO
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
		fetch('/horarios/app/controllers/disciplina/deleteDisciplina.php', {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: new URLSearchParams({ id: currentEditId })
		})
		.then(r => r.json())
		.then(data => {
			alert(data.message);
			if (data.status === 'success') {
				const row = document.querySelector(`tr[data-id="${currentEditId}"]`);
				if (row) row.remove();
				if (disciplinaTableBody.children.length === 0) {
					noDataMessage.style.display = 'block';
				}
			}
			closeDeleteModal();
		})
		.catch(err => console.error(err));
	});
	
	/* ============================================================
		7) PESQUISA
	============================================================ */
	document.getElementById('search-input').addEventListener('input', function() {
		const searchValue = this.value.toLowerCase();
		const rows = disciplinaTableBody.querySelectorAll('tr');
		let count = 0;
		rows.forEach(tr => {
			const tdNome = tr.querySelector('td:nth-child(1)').textContent.toLowerCase();
			const tdSigla= tr.querySelector('td:nth-child(2)').textContent.toLowerCase();
			if (tdNome.includes(searchValue) || tdSigla.includes(searchValue)) {
				tr.style.display = '';
				count++;
			} else {
				tr.style.display = 'none';
			}
		});
		noDataMessage.style.display = (count === 0) ? 'block' : 'none';
	});
	
	/* ============================================================
		8) MODAL DE IMPRESSÃO INDIVIDUAL
	============================================================ */
	function openPrintDiscModal() {
		modalPrintDisc.style.display = 'block';
		modalPrintDisc.classList.remove('fade-out');
		modalPrintDisc.classList.add('fade-in');
		const content = modalPrintDisc.querySelector('.modal-content');
		content.classList.remove('slide-up');
		content.classList.add('slide-down');
	}
	
	function closePrintDiscModal() {
		const content = modalPrintDisc.querySelector('.modal-content');
		content.classList.remove('slide-down');
		content.classList.add('slide-up');
		modalPrintDisc.classList.remove('fade-in');
		modalPrintDisc.classList.add('fade-out');
		setTimeout(() => {
			modalPrintDisc.style.display = 'none';
			content.classList.remove('slide-up');
			modalPrintDisc.classList.remove('fade-out');
		}, 300);
	}
	
	btnCancelarDisc.addEventListener('click', closePrintDiscModal);
	closePrintDiscModalBtn.addEventListener('click', closePrintDiscModal);
	
	// Nível de Ensino no modal individual
	chkNivel.addEventListener('change', async function() {
		if (this.checked) {
			selectNivel.style.display = 'block';
			// se Professor estiver ativo, restringe níveis pelos níveis do professor
			if (chkProfDT.checked) {
				await loadNiveisByProfessorInto(selectNivel, selectProfDT.value);
			} else {
				await loadNiveisInto(selectNivel);
			}
		} else {
			selectNivel.style.display = 'none';
			resetSelect(selectNivel, 'Todas');
		}
	});

	// Professor no modal individual
	chkProfDT.addEventListener('change', async function() {
		if (this.checked) {
			selectProfDT.style.display = 'block';
			const nivelAtual = chkNivel.checked ? selectNivel.value : 'todas';
			await loadProfessoresByNivelInto(selectProfDT, nivelAtual);
			// se nível já está marcado, sincroniza níveis do professor escolhido
			if (chkNivel.checked) {
				await loadNiveisByProfessorInto(selectNivel, selectProfDT.value);
			}
		} else {
			selectProfDT.style.display = 'none';
			resetSelect(selectProfDT, 'Todos');
			// se o nível estava filtrado pelo professor, reabre níveis padrão
			if (chkNivel.checked) {
				await loadNiveisInto(selectNivel);
			}
		}
	});

	// Ao trocar o professor, sincroniza níveis se necessário
	selectProfDT.addEventListener('change', async function() {
		if (chkNivel.checked) {
			await loadNiveisByProfessorInto(selectNivel, this.value);
		}
	});

	// Ao trocar o nível, se professor estiver ativo, atualiza lista de professores
	selectNivel.addEventListener('change', async function() {
		if (chkProfDT.checked) {
			await loadProfessoresByNivelInto(selectProfDT, this.value);
		}
	});
	
	// Imprimir individual
	btnImprimirDisc.addEventListener('click', () => {
		let url = '/horarios/app/views/disciplina.php?id_disc=' + currentPrintDiscId;
		if (chkNivel.checked) {
			url += '&nivel=' + encodeURIComponent(selectNivel.value);
		}
		if (chkProfDT.checked) {
			url += '&profdt=' + encodeURIComponent(selectProfDT.value);
		}
		window.open(url, '_blank');
		closePrintDiscModal();
	});
	
	/* ============================================================
		9) MODAL DE IMPRESSÃO GERAL
	============================================================ */
	function openPrintGeralDiscModal() {
		// Zera checkboxes e selects
		chkNivelGeral.checked = false;
		selectNivelGeral.style.display = 'none';
		resetSelect(selectNivelGeral, 'Todas');
	
		chkProfDTGeral.checked = false;
		selectProfDTGeral.style.display = 'none';
		resetSelect(selectProfDTGeral, 'Todos');
	
		// Abre modal
		modalPrintGeralDisc.style.display = 'block';
		modalPrintGeralDisc.classList.remove('fade-out');
		modalPrintGeralDisc.classList.add('fade-in');
		const content = modalPrintGeralDisc.querySelector('.modal-content');
		content.classList.remove('slide-up');
		content.classList.add('slide-down');
	}
	
	function closePrintGeralDiscModal() {
		const content = modalPrintGeralDisc.querySelector('.modal-content');
		content.classList.remove('slide-down');
		content.classList.add('slide-up');
		modalPrintGeralDisc.classList.remove('fade-in');
		modalPrintGeralDisc.classList.add('fade-out');
		setTimeout(() => {
			modalPrintGeralDisc.style.display = 'none';
			content.classList.remove('slide-up');
			modalPrintGeralDisc.classList.remove('fade-out');
		}, 300);
	}
	
	// Botão principal que abre o modal
	btnImprimirGeralDiscTrigger.addEventListener('click', openPrintGeralDiscModal);
	// Botões do modal
	btnCancelarGeralDisc.addEventListener('click', closePrintGeralDiscModal);
	closePrintGeralDiscModalBtn.addEventListener('click', closePrintGeralDiscModal);
	
	// Nível no geral
	chkNivelGeral.addEventListener('change', async function() {
		if (this.checked) {
			selectNivelGeral.style.display = 'block';
			// se Professor geral estiver ativo, restringe níveis aos níveis do professor
			if (chkProfDTGeral.checked) {
				await loadNiveisByProfessorInto(selectNivelGeral, selectProfDTGeral.value);
			} else {
				await loadNiveisInto(selectNivelGeral);
			}
		} else {
			selectNivelGeral.style.display = 'none';
			resetSelect(selectNivelGeral, 'Todas');
		}
	});
	
	// Professor no geral
	chkProfDTGeral.addEventListener('change', async function() {
		if (this.checked) {
			selectProfDTGeral.style.display = 'block';
			const nivelAtual = chkNivelGeral.checked ? selectNivelGeral.value : 'todas';
			await loadProfessoresByNivelInto(selectProfDTGeral, nivelAtual);
			// se nível já estiver marcado, sincroniza níveis do professor escolhido
			if (chkNivelGeral.checked) {
				await loadNiveisByProfessorInto(selectNivelGeral, selectProfDTGeral.value);
			}
		} else {
			selectProfDTGeral.style.display = 'none';
			resetSelect(selectProfDTGeral, 'Todos');
			// se o nível estava filtrado pelo professor, reabre níveis padrão
			if (chkNivelGeral.checked) {
				await loadNiveisInto(selectNivelGeral);
			}
		}
	});

	// Ao trocar o professor no geral, sincroniza níveis se necessário
	selectProfDTGeral.addEventListener('change', async function() {
		if (chkNivelGeral.checked) {
			await loadNiveisByProfessorInto(selectNivelGeral, this.value);
		}
	});

	// Ao trocar o nível no geral, atualiza professores se necessário
	selectNivelGeral.addEventListener('change', async function() {
		if (chkProfDTGeral.checked) {
			await loadProfessoresByNivelInto(selectProfDTGeral, this.value);
		}
	});
	
	// Imprimir geral
	btnImprimirGeralDisc.addEventListener('click', () => {
		let url = '/horarios/app/views/disciplina-geral.php?';
		if (chkNivelGeral.checked) {
			url += 'nivel=' + encodeURIComponent(selectNivelGeral.value) + '&';
		}
		if (chkProfDTGeral.checked) {
			url += 'profdt=' + encodeURIComponent(selectProfDTGeral.value) + '&';
		}
		window.open(url, '_blank');
		closePrintGeralDiscModal();
	});
	
	/* ============================================================
		10) ORDENAR COLUNAS (Disciplina / Sigla)
	============================================================ */
	sortDiscAscBtn.addEventListener('click', () => {
		disciplinaData.sort((a, b) => a.nome_disciplina.localeCompare(b.nome_disciplina));
		renderTable(disciplinaData);
	});
	sortDiscDescBtn.addEventListener('click', () => {
		disciplinaData.sort((a, b) => b.nome_disciplina.localeCompare(a.nome_disciplina));
		renderTable(disciplinaData);
	});

	sortSiglaAscBtn.addEventListener('click', () => {
		disciplinaData.sort((a, b) => a.sigla_disciplina.localeCompare(b.sigla_disciplina));
		renderTable(disciplinaData);
	});
	sortSiglaDescBtn.addEventListener('click', () => {
		disciplinaData.sort((a, b) => b.sigla_disciplina.localeCompare(a.sigla_disciplina));
		renderTable(disciplinaData);
	});
});
