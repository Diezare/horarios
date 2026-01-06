document.addEventListener('DOMContentLoaded', function() {
	/* ============================================================
		 Referências gerais
	============================================================ */
	const modal = document.getElementById('modal-professor');
	const btnAdd = document.getElementById('btn-add');
	const closeModalElements = document.querySelectorAll('#modal-professor .close-modal');
	const cancelBtn = document.getElementById('cancel-btn');
	const saveBtn = document.getElementById('save-btn');

	// Modal de exclusão (animado)
	const modalDelete = document.getElementById('modal-delete');
	const closeDeleteModalBtn = document.getElementById('close-delete-modal');
	const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
	const cancelDeleteBtn = document.getElementById('cancel-delete-btn');

	const professorTableBody = document.getElementById('professorTable');
	const noDataMessage = document.getElementById('no-data-message');

	let isEditMode = false;
	let currentEditId = null;

	/* Campos do formulário */
	const inputProfessorId	= document.getElementById('professorId');
	const inputNomeCompleto = document.getElementById('nome-completo');
	const inputNomeExibicao = document.getElementById('nome-exibicao');
	const inputTelefone		= document.getElementById('telefone');
	const radioSexoMasc		= document.getElementById('sexo-masc');
	const radioSexoFem		= document.getElementById('sexo-fem');
	const radioSexoOutro	= document.getElementById('sexo-outro');
	const inputLimiteAulas	= document.getElementById('limite-aulas');

	/* =========================================================
	   FUNÇÕES DE MÁSCARA (Telefone e campo de número de aulas)
	========================================================= */

    function maskTelefone(value) {
        let v = value.replace(/\D/g, '');
        v = v.substring(0, 11);

        if (v.length <= 10) {
            v = v.replace(/^(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
        } else {
            v = v.replace(/^(\d{2})(\d{5})(\d{0,4})/, '($1) $2-$3');
        }
        return v;
    }

    const telefoneInput = document.getElementById('telefone');

    // Aplica máscaras enquanto digita
    telefoneInput.addEventListener('input', function() {
        this.value = maskTelefone(this.value);
    });

	/* ============================================================
		 Formatação do campo limite-aulas (apenas 2 dígitos, 0..99)
	============================================================ */
	inputLimiteAulas.addEventListener('input', function() {
		// Remove tudo que não seja dígito
		let val = this.value.replace(/\D/g, '');
		// Limita a 2 caracteres
		if (val.length > 2) {
			val = val.substring(0, 2);
		}
		this.value = val;
	});

	/* ============================================================
		 1) LISTAR PROFESSORES
	============================================================ */
	function fetchProfessores() {
		fetch('/horarios/app/controllers/professor/listProfessor.php')
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

	/* ============================================================
		 2) MONTAR TABELA
	============================================================ */
	function renderTable(rows) {
		professorTableBody.innerHTML = '';
		if (!rows || rows.length === 0) {
			noDataMessage.style.display = 'block';
			return;
		}
		noDataMessage.style.display = 'none';

		rows.forEach(row => {
			const tr = document.createElement('tr');
			tr.dataset.id = row.id_professor;

			// Nome Completo
			const tdNomeCompleto = document.createElement('td');
			tdNomeCompleto.textContent = row.nome_completo;
			tr.appendChild(tdNomeCompleto);

			// Nome Exibicao
			const tdNomeExibicao = document.createElement('td');
			tdNomeExibicao.textContent = row.nome_exibicao || '';
			tr.appendChild(tdNomeExibicao);

			// Ações
			const tdActions = document.createElement('td');

			// Botão Editar
			const btnEdit = document.createElement('button');
			btnEdit.classList.add('btn-edit');
			btnEdit.dataset.id = row.id_professor;
			btnEdit.innerHTML = `
				<span class="icon"><i class="fa-solid fa-pen-to-square"></i></span>
				<span class="text">Editar</span>`;
			tdActions.appendChild(btnEdit);

			// Botão Deletar
			const btnDelete = document.createElement('button');
			btnDelete.classList.add('btn-delete');
			btnDelete.dataset.id = row.id_professor;
			btnDelete.innerHTML = `
				<span class="icon"><i class="fa-solid fa-trash"></i></span>
				<span class="text">Deletar</span>`;
			tdActions.appendChild(btnDelete);

			// Botão Imprimir
			const btnPrint = document.createElement('button');
			btnPrint.classList.add('btn-print');
			btnPrint.dataset.id = row.id_professor;
			btnPrint.innerHTML = `
				<span class="icon"><i class="fa-solid fa-print"></i></span>
				<span class="text">Imprimir</span>`;
			tdActions.appendChild(btnPrint);

			// Botão Vincular Disciplinas
			const btnVincularDisc = document.createElement('button');
			btnVincularDisc.classList.add('btn-vincular-disciplina');
			btnVincularDisc.dataset.id = row.id_professor;
			btnVincularDisc.innerHTML = `
				<span class="icon"><i class="fa-solid fa-book"></i></span>
				<span class="text">Disciplinas</span>`;
			tdActions.appendChild(btnVincularDisc);

			// Botão Definir Restrições
			const btnRestricoes = document.createElement('button');
			btnRestricoes.classList.add('btn-restricoes');
			btnRestricoes.dataset.id = row.id_professor;
			btnRestricoes.innerHTML = `
				<span class="icon"><i class="fa-solid fa-ban"></i></span>
				<span class="text">Restrições</span>`;
			tdActions.appendChild(btnRestricoes);

			// Botão Vincular Turnos
			const btnTurno = document.createElement('button');
			btnTurno.classList.add('btn-turno');
			btnTurno.dataset.id = row.id_professor;
			btnTurno.innerHTML = `
				<span class="icon"><i class="fa-solid fa-clock"></i></span>
				<span class="text">Turnos</span>`;
			tdActions.appendChild(btnTurno);

			// Botão Vincular Professor-Disciplinas-Turmas
			const btnVincularDiscTurma = document.createElement('button');
			btnVincularDiscTurma.classList.add('btn-vincular-disciplina-turma');
			btnVincularDiscTurma.dataset.id = row.id_professor;
			btnVincularDiscTurma.innerHTML = `
				<span class="icon"><i class="fa-solid fa-chalkboard"></i></span>
				<span class="text">Turmas</span>`;
			tdActions.appendChild(btnVincularDiscTurma);
			
			tr.appendChild(tdActions);
			professorTableBody.appendChild(tr);
		});
	}

	/* ============================================================
		 3) ABRIR/FECHAR MODAL DE PROFESSOR
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
			document.getElementById('modal-title').innerText = 'Adicionar Professor';
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
		inputProfessorId.value	= '';
		inputNomeCompleto.value = '';
		inputNomeExibicao.value = '';
		inputTelefone.value	= '';
		radioSexoMasc.checked	 = true; // default
		radioSexoFem.checked		= false;
		radioSexoOutro.checked	= false;
		inputLimiteAulas.value	= '';
	}

	/* ============================================================
		 4) BOTÃO ADICIONAR
	============================================================ */
	btnAdd.addEventListener('click', () => {
		isEditMode = false;
		openModal();
	});

	closeModalElements.forEach(el => {
		el.addEventListener('click', closeModal);
	});

	cancelBtn.addEventListener('click', () => {
		if (!isEditMode) {
			clearForm();
		}
		closeModal();
	});

	/* ============================================================
		 5) SALVAR (INSERT/UPDATE)
	============================================================ */
	saveBtn.addEventListener('click', () => {
		const id = inputProfessorId.value;
		const nomeCompleto = inputNomeCompleto.value.trim();
		const nomeExibicao = inputNomeExibicao.value.trim();
		const telefone = inputTelefone.value.trim();
	
		// Valida
		if (!nomeCompleto || !nomeExibicao || !telefone) {
			alert('Preencha todos os campos.');
			return;
		}
		if (nomeCompleto.length > 100) {
			alert('O nome completo não pode ultrapassar 100 caracteres.');
			return;
		}
	
		// Ver qual sexo está marcado
		let sexoSelecionado = 'Masculino';
		if (radioSexoFem.checked) sexoSelecionado = 'Feminino';
		if (radioSexoOutro.checked) sexoSelecionado = 'Outro';
	
		// Pegar limite de aulas (0..99)
		let limite = inputLimiteAulas.value.trim();
		if (!limite) limite = '0'; // default
		// Verifica se é inteiro e <= 99
		const num = parseInt(limite, 10);
		if (isNaN(num) || num < 0 || num > 99) {
			alert('O limite de aulas deve ser um número entre 0 e 99.');
			return;
		}
	
		// Monta dados
		const data = new URLSearchParams({
			id_professor: id,
			nome_completo: nomeCompleto,
			nome_exibicao: nomeExibicao,
			telefone: telefone,
			sexo: sexoSelecionado,
			limite_aulas: num // converte p/ string
		});
	
		if (isEditMode) {
			// UPDATE
			fetch('/horarios/app/controllers/professor/updateProfessor.php', {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: data
			})
			.then(r => r.json())
			.then(response => {
				alert(response.message);
				if (response.status === 'success') {
					closeModal();
					fetchProfessores();
				}
			})
			.catch(err => console.error(err));
		} else {
			// INSERT (remove 'id_professor' do body se preferir, mas OK se ignorado no back)
			data.delete('id_professor');
			
			fetch('/horarios/app/controllers/professor/insertProfessor.php', {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: data
			})
			.then(r => r.json())
			.then(response => {
				if (response.status === 'success') {
					// Exibe um confirm customizado usando a função nativa
					const resp = confirm('Professor inserido com sucesso! Deseja inserir outro professor?');
					if (resp) {
						// Se o usuário clicar em "OK" (Sim): limpa o formulário e mantém o modal aberto
						clearForm();
					} else {
						// Se o usuário clicar em "Cancelar" (Não): fecha o modal e recarrega a lista
						closeModal();
						fetchProfessores();
					}
				} else {
					alert(response.message);
				}
			})
			.catch(err => console.error(err));          
		}
	});

	/* ============================================================
		 6) AÇÕES NA TABELA (editar, deletar, etc.)
	============================================================ */
	professorTableBody.addEventListener('click', e => {
		const btn = e.target.closest('button');
		if (!btn) return;

		const id = btn.dataset.id;

		if (btn.classList.contains('btn-edit')) {
			// EDITAR
			isEditMode = true;
			currentEditId = id;

			fetch('/horarios/app/controllers/professor/listProfessor.php')
				.then(r => r.json())
				.then(data => {
					if (data.status === 'success') {
						const professor = data.data.find(item => item.id_professor == currentEditId);
						if (professor) {
							inputProfessorId.value	 = professor.id_professor;
							inputNomeCompleto.value	= professor.nome_completo || '';
							inputNomeExibicao.value	= professor.nome_exibicao || '';
							inputTelefone.value			= professor.telefone || '';

							// Marcar o sexo
							if (professor.sexo === 'Feminino') {
								radioSexoFem.checked = true;
							} else if (professor.sexo === 'Outro') {
								radioSexoOutro.checked = true;
							} else {
								radioSexoMasc.checked = true;
							}

							// Limite (0..99)
							let limitVal = parseInt(professor.limite_aulas_fixa_semana, 10);
							if (isNaN(limitVal)) limitVal = 0;
							inputLimiteAulas.value = limitVal.toString();

							document.getElementById('modal-title').innerText = 'Editar Professor';
							saveBtn.innerText = 'Alterar';
							openModal();
						}
					}
				});
		} else if (btn.classList.contains('btn-delete')) {
			// DELETAR
			currentEditId = id;
			openDeleteModal();
		} else if (btn.classList.contains('btn-print')) {
			alert('Função imprimir não implementada.');
		} else if (btn.classList.contains('btn-vincular-disciplina')) {
			// Vincular Disciplinas
			const tr = btn.closest('tr');
			const professorName = tr.querySelector('td:nth-child(1)').textContent.trim();
			document.getElementById('prof-nome-disciplina').value = professorName;
			document.getElementById('select-professor-disciplina').value = id;
			openProfessorDisciplinaModal(id);
		} else if (btn.classList.contains('btn-restricoes')) {
			const tr = btn.closest('tr');
			//1 - Exibe o nome completo do professor | 2 - Exibe o nome de exibição do professor
			const professorName = tr.querySelector('td:nth-child(1)').textContent.trim();
			const professorId	 = btn.dataset.id;
			// Chama a função que abre o modal e preenche
			openProfessorRestricoesModal(professorId, professorName);
		} else if (btn.classList.contains('btn-turno')) {
			// Turnos
			const tr = btn.closest('tr');
			const professorName = tr.querySelector('td:nth-child(1)').textContent.trim();
			document.getElementById('prof-nome-turno').value = professorName;
			document.getElementById('select-professor-turno').value = id;
			openProfessorTurnoModal(id);
		} else if (btn.classList.contains('btn-vincular-disciplina-turma')) {
			// Vincular Professor - Disciplinas - Turmas
			const tr = btn.closest('tr');
			const professorName = tr.querySelector('td:nth-child(1)').textContent.trim();
			openProfessorDisciplinaTurmaModal(id, professorName);
		}
	});

	/* ============================================================
		 7) MODAL DE EXCLUSÃO (ANIMADO)
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
		fetch('/horarios/app/controllers/professor/deleteProfessor.php', {
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
				if (professorTableBody.children.length === 0) {
					noDataMessage.style.display = 'block';
				}
			}
			closeDeleteModal();
		})
		.catch(err => console.error(err));
	});

	/* ============================================================
		 8) PESQUISA
	============================================================ */
	document.getElementById('search-input').addEventListener('input', function() {
		const searchValue = this.value.toLowerCase();
		const rows = professorTableBody.querySelectorAll('tr');
		let count = 0;
		rows.forEach(tr => {
			const nome = tr.querySelector('td:nth-child(1)').textContent.toLowerCase();
			const exibicao = tr.querySelector('td:nth-child(2)').textContent.toLowerCase();
			if (nome.includes(searchValue) || exibicao.includes(searchValue)) {
				tr.style.display = '';
				count++;
			} else {
				tr.style.display = 'none';
			}
		});
		noDataMessage.style.display = count === 0 ? 'block' : 'none';
	});

	/* ============================================================
		 9) FECHAR MODAL AO CLICAR FORA (opcional)
	============================================================ */
	window.addEventListener('click', e => {
		if (e.target === modal) {
			// closeModal();
		}
		if (e.target === modalDelete) {
			// closeDeleteModal();
		}
	});

	/* ============================================================
		 10) INICIALIZA
	============================================================ */
	fetchProfessores();
});

