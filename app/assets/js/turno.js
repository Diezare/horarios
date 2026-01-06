// app/assets/js/turno.js
document.addEventListener('DOMContentLoaded', function() {
	// === REFERÊNCIAS DOS ELEMENTOS ===
	const modal = document.getElementById('modal-turno');
	const btnAdd = document.getElementById('btn-add-turno');
	const closeModalElements = document.querySelectorAll('.close-modal');
	const cancelBtn = document.getElementById('cancel-btn');
	const saveBtn = document.getElementById('save-btn');

	// Modal de exclusão
	const modalDelete = document.getElementById('modal-delete');
	const closeDeleteModalBtn = document.getElementById('close-delete-modal');
	const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
	const cancelDeleteBtn = document.getElementById('cancel-delete-btn');

	// Tabela e mensagem "sem dados"
	const turnoTableBody = document.getElementById('turnoTable');
	const noDataMessage = document.getElementById('no-data-message');

	// === MODAL DE IMPRESSÃO INDIVIDUAL ===
	const modalPrintTurno = document.getElementById('modal-print-turno');
	const closePrintTurnoModalBtn = document.getElementById('close-print-modal-turno');
	const btnImprimirTurno = document.getElementById('btn-imprimir-turno');
	const btnCancelarTurno = document.getElementById('btn-cancelar-turno');
	const selectedTurnoInput = document.getElementById('selected-turno');

	// Filtros do modal de impressão individual
	const chkTurmasTurno = document.getElementById('chk-turmas-turno');
	const chkProfRestricaoTurno = document.getElementById('chk-prof-restricao-turno');
	const selectProfRestricaoTurno = document.getElementById('select-prof-restricao-turno');
	const professorSelectRowTurno = document.getElementById('professor-select-row-turno');

	// === MODAL DE IMPRESSÃO GERAL ===
	const btnImprimirTurnoGeral = document.getElementById('btnImprimir'); // botão de impressão geral
	const modalPrintTurnoGeral = document.getElementById('modal-print-turno-geral');
	const closePrintTurnoGeralModalBtn = document.getElementById('close-print-modal-turno-geral');
	const btnImprimirTurnoGeralConfirm = document.getElementById('btn-imprimir-turno-geral-confirm');
	const btnCancelarTurnoGeral = document.getElementById('btn-cancelar-turno-geral');

	// Filtros do modal de impressão geral
	const chkTurmasTurnoGeral = document.getElementById('chk-turmas-turno-geral');
	const chkProfRestricaoTurnoGeral = document.getElementById('chk-prof-restricao-turno-geral');
	const selectProfRestricaoTurnoGeral = document.getElementById('select-prof-restricao-turno-geral');
	const professorSelectRowTurnoGeral = document.getElementById('professor-select-row-turno-geral');

	// Ícones de sort (para o turno)
	const sortAscIcon = document.getElementById('sort-asc');
	const sortDescIcon = document.getElementById('sort-desc');

	// Simulando níveis permitidos
	const userAllowedLevels = ['1','2']; // Exemplo

	let isEditMode = false;
	let currentEditId = null;
	let currentPrintTurnoId = null; // Para impressão individual
	let allTurnos = [];

	/* ============================================================
			 1) LISTAR TURNOS
	============================================================ */
	function fetchTurno() {
		fetch('/horarios/app/controllers/turno/listTurno.php')
			.then(r => r.json())
			.then(data => {
				if (data.status === 'success') {
					allTurnos = data.data;
					renderTable(allTurnos);
				} else {
					console.error(data.message);
				}
			})
			.catch(err => console.error(err));
	}

	function renderTable(rows) {
		turnoTableBody.innerHTML = '';
		if (!rows || rows.length === 0) {
			noDataMessage.style.display = 'block';
			return;
		}
		noDataMessage.style.display = 'none';

		rows.forEach(row => {
			const tr = document.createElement('tr');
			tr.dataset.id = row.id_turno;

			// Nome do Turno
			const tdNome = document.createElement('td');
			tdNome.textContent = row.nome_turno;
			tr.appendChild(tdNome);

			// Ações
			const tdActions = document.createElement('td');

			// Botão Editar
			const btnEdit = document.createElement('button');
			btnEdit.classList.add('btn-edit');
			btnEdit.dataset.id = row.id_turno;
			btnEdit.innerHTML = `
				<span class="icon"><i class="fa-solid fa-pen-to-square"></i></span>
				<span class="text">Editar</span>`;
			tdActions.appendChild(btnEdit);

			// Botão Deletar
			const btnDelete = document.createElement('button');
			btnDelete.classList.add('btn-delete');
			btnDelete.dataset.id = row.id_turno;
			btnDelete.innerHTML = `
				<span class="icon"><i class="fa-solid fa-trash"></i></span>
				<span class="text">Deletar</span>`;
			tdActions.appendChild(btnDelete);

			// Botão Imprimir Individual
			const btnPrint = document.createElement('button');
			btnPrint.classList.add('btn-print');
			btnPrint.dataset.id = row.id_turno;
			btnPrint.innerHTML = `
				<span class="icon"><i class="fa-solid fa-print"></i></span>
				<span class="text">Imprimir</span>`;
			tdActions.appendChild(btnPrint);

			// Botão "Qtd. Aulas" (abre modal turno-dias.js)
			const btnDias = document.createElement('button');
			btnDias.classList.add('btn-turno-dias');
			btnDias.dataset.id = row.id_turno;
			btnDias.dataset.nome = row.nome_turno;
			btnDias.innerHTML = `
				<span class="icon"><i class="fa-solid fa-calendar-day"></i></span>
				<span class="text">Qtd. Aulas</span>`;
			tdActions.appendChild(btnDias);

			tr.appendChild(tdActions);
			turnoTableBody.appendChild(tr);
		});
	}

	/* ============================================================
			 2) ORDENAÇÃO (SORT)
	============================================================ */
	function sortTable(property, asc = true) {
		const sorted = [...allTurnos].sort((a, b) => {
			const valA = a[property] ? a[property].toLowerCase() : '';
			const valB = b[property] ? b[property].toLowerCase() : '';
			return asc ? valA.localeCompare(valB) : valB.localeCompare(valA);
		});
		renderTable(sorted);
	}
	sortAscIcon.addEventListener('click', () => sortTable('nome_turno', true));
	sortDescIcon.addEventListener('click', () => sortTable('nome_turno', false));

	/* ============================================================
			 3) ABRIR/FECHAR MODAL (CADASTRO/EDIÇÃO)
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
			document.getElementById('modal-title').innerText = 'Adicionar Turno';
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
		document.getElementById('turnoId').value = '';
		document.getElementById('nomeTurno').value = '';
		document.getElementById('descricaoTurno').value = '';
		document.getElementById('horaInicio').value = '';
		document.getElementById('horaFim').value = '';
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
			 4) SALVAR (INSERT/UPDATE)
	============================================================ */
	saveBtn.addEventListener('click', () => {
		const id = document.getElementById('turnoId').value;
		const nome = document.getElementById('nomeTurno').value.trim();
		const descricao = document.getElementById('descricaoTurno').value.trim();
		const horaInicio = document.getElementById('horaInicio').value.trim();
		const horaFim = document.getElementById('horaFim').value.trim();

		if (!nome || !horaInicio || !horaFim) {
			alert('Preencha os campos obrigatórios (Nome, Horários).');
			return;
		}
		const regexHora = /^(2[0-3]|[01]\d):([0-5]\d)$/;
		if (!regexHora.test(horaInicio)) {
			alert('Horário de início inválido. Formato HH:MM.');
			return;
		}
		if (!regexHora.test(horaFim)) {
			alert('Horário de término inválido. Formato HH:MM.');
			return;
		}
		const [hIni, mIni] = horaInicio.split(':').map(Number);
		const [hFim, mFim] = horaFim.split(':').map(Number);
		const totalIni = hIni * 60 + mIni;
		const totalFim = hFim * 60 + mFim;
		if (totalIni > totalFim) {
			alert('O horário de início não pode ser maior que o horário de término.');
			return;
		}

		const params = new URLSearchParams({
			nome_turno: nome,
			descricao_turno: descricao,
			horario_inicio_turno: horaInicio,
			horario_fim_turno: horaFim
		});

		if (isEditMode) {
			params.append('id_turno', id);
			fetch('/horarios/app/controllers/turno/updateTurno.php', {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: params
			})
			.then(r => r.json())
			.then(data => {
				alert(data.message);
				if (data.status === 'success') {
					closeModal();
					fetchTurno();
				}
			})
			.catch(err => console.error(err));
		} else {
			fetch('/horarios/app/controllers/turno/insertTurno.php', {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: params
			})
			.then(r => r.json())
			.then(data => {
				alert(data.message);
				if (data.status === 'success') {
					closeModal();
					fetchTurno();
				}
			})
			.catch(err => console.error(err));
		}
	});

	/* ============================================================
			 5) AÇÕES NA TABELA
	============================================================ */
	turnoTableBody.addEventListener('click', e => {
		const btn = e.target.closest('button');
		if (!btn) return;

		if (btn.classList.contains('btn-edit')) {
			// Editar
			isEditMode = true;
			currentEditId = btn.dataset.id;
			fetch('/horarios/app/controllers/turno/listTurno.php')
				.then(r => r.json())
				.then(data => {
					if (data.status === 'success') {
						const found = data.data.find(item => item.id_turno == currentEditId);
						if (found) {
							document.getElementById('turnoId').value = found.id_turno;
							document.getElementById('nomeTurno').value = found.nome_turno;
							document.getElementById('descricaoTurno').value = found.descricao_turno ?? '';
							document.getElementById('horaInicio').value = found.horario_inicio_turno;
							document.getElementById('horaFim').value = found.horario_fim_turno;
							document.getElementById('modal-title').innerText = 'Editar Turno';
							saveBtn.innerText = 'Alterar';
							openModal();
						}
					}
				});
		} else if (btn.classList.contains('btn-delete')) {
			// Excluir
			currentEditId = btn.dataset.id;
			openDeleteModal();
		} else if (btn.classList.contains('btn-print')) {
			// Impressão individual
			currentPrintTurnoId = btn.dataset.id;
			openPrintModalTurno(currentPrintTurnoId);
		} else if (btn.classList.contains('btn-turno-dias')) {
			// Abre modal "Qtd. Aulas" (turno-dias.js)
			const turnoId = btn.dataset.id;
			const turnoName = btn.dataset.nome;
			openTurnoDiasModal(turnoId, turnoName);
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
		fetch('/horarios/app/controllers/turno/deleteTurno.php', {
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
				if (turnoTableBody.children.length === 0) {
					noDataMessage.style.display = 'block';
				}
			}
			closeDeleteModal();
		})
		.catch(err => console.error(err));
	});

	/* ============================================================
			 7) MODAL DE IMPRESSÃO INDIVIDUAL
	============================================================ */
	// Mostrar/ocultar <select> de professores quando "Professores" for marcado
	chkProfRestricaoTurno.addEventListener('change', function() {
		if (this.checked) {
			professorSelectRowTurno.style.display = 'block';
			// Carregar professores (filtrados por níveis) via AJAX
			const niveisParam = userAllowedLevels.join(',');
			fetch('/horarios/app/controllers/professor/listProfessor.php?nivel=' + encodeURIComponent(niveisParam))
				.then(r => r.json())
				.then(data => {
					if (data.status === 'success') {
						selectProfRestricaoTurno.innerHTML = '<option value="todas">Todos</option>';
						data.data.forEach(prof => {
							const opt = document.createElement('option');
							opt.value = prof.id_professor;
							opt.textContent = prof.nome_completo;
							selectProfRestricaoTurno.appendChild(opt);
						});
					}
				})
				.catch(err => console.error(err));
		} else {
			professorSelectRowTurno.style.display = 'none';
		}
	});

	function openPrintModalTurno(turnoId) {
		selectedTurnoInput.value = '';
		chkTurmasTurno.checked = false;
		chkProfRestricaoTurno.checked = false;
		professorSelectRowTurno.style.display = 'none';
		selectProfRestricaoTurno.innerHTML = '<option value="todas">Todos</option>';

		// Busca o nome do turno
		fetch('/horarios/app/controllers/turno/listTurno.php')
			.then(r => r.json())
			.then(data => {
				if (data.status === 'success') {
					const found = data.data.find(t => t.id_turno == turnoId);
					if (found) {
						selectedTurnoInput.value = found.nome_turno;
					}
				}
			})
			.catch(err => console.error(err));

		// Abre modal
		modalPrintTurno.style.display = 'block';
		modalPrintTurno.classList.remove('fade-out');
		modalPrintTurno.classList.add('fade-in');
		const content = modalPrintTurno.querySelector('.modal-content');
		content.classList.remove('slide-up');
		content.classList.add('slide-down');
	}
	function closePrintModalTurno() {
		const content = modalPrintTurno.querySelector('.modal-content');
		content.classList.remove('slide-down');
		content.classList.add('slide-up');
		modalPrintTurno.classList.remove('fade-in');
		modalPrintTurno.classList.add('fade-out');
		setTimeout(() => {
			modalPrintTurno.style.display = 'none';
			content.classList.remove('slide-up');
			modalPrintTurno.classList.remove('fade-out');
		}, 300);
	}
	btnCancelarTurno.addEventListener('click', closePrintModalTurno);
	closePrintTurnoModalBtn.addEventListener('click', closePrintModalTurno);

	btnImprimirTurno.addEventListener('click', () => {
		let url = '/horarios/app/views/turno.php?id_turno=' + currentPrintTurnoId;
		// Se Professores foi marcado, adicionamos &prof=1
		if (chkProfRestricaoTurno.checked) {
			url += '&prof=1';
		}
		// Se Turmas foi marcado, vamos usar ?nivel=todos (poderia enviar um ID de nível)
		if (chkTurmasTurno.checked) {
			// Exemplo: enviamos nivel=todos para exibir as turmas de qualquer nível
			url += (url.indexOf('?') === -1 ? '?' : '&') + 'nivel=todos';
		}
		// (Se quisesse filtrar por nível específico, seria algo como &nivel=2)

		window.open(url, '_blank');
		closePrintModalTurno();
	});

	/* ============================================================
			 8) MODAL DE IMPRESSÃO GERAL
	============================================================ */
	// Mostrar/ocultar <select> de professores quando "Professores" for marcado
	chkProfRestricaoTurnoGeral.addEventListener('change', function() {
		if (this.checked) {
			professorSelectRowTurnoGeral.style.display = 'block';
			const niveisParam = userAllowedLevels.join(',');
			fetch('/horarios/app/controllers/professor/listProfessor.php?nivel=' + encodeURIComponent(niveisParam))
				.then(r => r.json())
				.then(data => {
					if (data.status === 'success') {
						selectProfRestricaoTurnoGeral.innerHTML = '<option value="todas">Todos</option>';
						data.data.forEach(prof => {
							const opt = document.createElement('option');
							opt.value = prof.id_professor;
							opt.textContent = prof.nome_completo;
							selectProfRestricaoTurnoGeral.appendChild(opt);
						});
					}
				})
				.catch(err => console.error(err));
		} else {
			professorSelectRowTurnoGeral.style.display = 'none';
		}
	});

	btnImprimirTurnoGeral.addEventListener('click', () => {
		// Reseta filtros
		chkTurmasTurnoGeral.checked = false;
		chkProfRestricaoTurnoGeral.checked = false;
		professorSelectRowTurnoGeral.style.display = 'none';
		selectProfRestricaoTurnoGeral.innerHTML = '<option value="todas">Todos</option>';
		openPrintModalTurnoGeral();
	});

	function openPrintModalTurnoGeral() {
		modalPrintTurnoGeral.style.display = 'block';
		modalPrintTurnoGeral.classList.remove('fade-out');
		modalPrintTurnoGeral.classList.add('fade-in');
		const content = modalPrintTurnoGeral.querySelector('.modal-content');
		content.classList.remove('slide-up');
		content.classList.add('slide-down');
	}
	function closePrintModalTurnoGeral() {
		const content = modalPrintTurnoGeral.querySelector('.modal-content');
		content.classList.remove('slide-down');
		content.classList.add('slide-up');
		modalPrintTurnoGeral.classList.remove('fade-in');
		modalPrintTurnoGeral.classList.add('fade-out');
		setTimeout(() => {
			modalPrintTurnoGeral.style.display = 'none';
			content.classList.remove('slide-up');
			modalPrintTurnoGeral.classList.remove('fade-out');
		}, 300);
	}
	btnCancelarTurnoGeral.addEventListener('click', closePrintModalTurnoGeral);
	closePrintTurnoGeralModalBtn.addEventListener('click', closePrintModalTurnoGeral);

	btnImprimirTurnoGeralConfirm.addEventListener('click', () => {
		// Monta a URL para turno-geral.php
		let url = '/horarios/app/views/turno-geral.php';
		// Se Professores foi marcado, &prof=1
		if (chkProfRestricaoTurnoGeral.checked) {
			url += '?prof=1';
		}
		// Se Turmas foi marcado, &nivel=todos (por exemplo)
		if (chkTurmasTurnoGeral.checked) {
			url += (url.indexOf('?') === -1 ? '?' : '&') + 'nivel=todos';
		}
		// Exemplo: se também quiser filtrar professor específico:
		// let selectedProf = selectProfRestricaoTurnoGeral.value;
		// if (selectedProf !== 'todas') {
		//	 url += '&professorId=' + encodeURIComponent(selectedProf);
		// }

		window.open(url, '_blank');
		closePrintModalTurnoGeral();
	});

	/* ============================================================
			 9) PESQUISA
	============================================================ */
	document.getElementById('search-input').addEventListener('input', function() {
		const searchValue = this.value.toLowerCase();
		const rows = turnoTableBody.querySelectorAll('tr');
		let count = 0;
		rows.forEach(tr => {
			const tdNome = tr.querySelector('td:nth-child(1)').textContent.toLowerCase();
			if (tdNome.includes(searchValue)) {
				tr.style.display = '';
				count++;
			} else {
				tr.style.display = 'none';
			}
		});
		noDataMessage.style.display = count === 0 ? 'block' : 'none';
	});

	/* ============================================================
			 10) EVITA FECHAR MODAL AO CLICAR FORA (opcional)
	============================================================ */
	window.addEventListener('click', e => {
		// Se necessário, fechar modal ao clicar fora
	});

	// Inicializa
	fetchTurno();
});
