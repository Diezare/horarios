// app/assets/js/sala.js
document.addEventListener('DOMContentLoaded', function() {
	const modalSala = document.getElementById('modal-sala');
	const btnAddSala = document.getElementById('btn-add-sala');
	const closeSalaModalElements = document.querySelectorAll('#close-sala-modal, .close-modal#close-sala-modal');
	const cancelSalaBtn = document.getElementById('cancelSalaBtn');
	const saveSalaBtn = document.getElementById('saveSalaBtn');
	const salaTableBody = document.getElementById('salaTable');
	const noDataMessageSala = document.getElementById('no-data-message-sala');
	const searchInputSala = document.getElementById('search-input-sala');
	const sortNomeSalaAsc = document.getElementById('sort-nome-sala-asc');
	const sortNomeSalaDesc = document.getElementById('sort-nome-sala-desc');
	const sortAnoAsc = document.getElementById('sort-ano-asc');
	const sortAnoDesc = document.getElementById('sort-no-desc');
	const btnImprimirSala = document.getElementById('btnImprimir');

	let isEditModeSala = false;
	let currentEditSalaId = null;
	let salaData = [];

	// ----------------------------------------------------------------
	// Carrega anos letivos no <select> do modal
	// ----------------------------------------------------------------
	function loadAnoLetivo() {
		fetch('/horarios/app/controllers/ano-letivo/listAnoLetivo.php')
			.then(r => r.json())
			.then(data => {
				if(data.status === 'success') {
					const selectAno = document.getElementById('anoLetivo');
					selectAno.innerHTML = '<option value="">-- Selecione o Ano --</option>';
					data.data.forEach(item => {
						const option = document.createElement('option');
						option.value = item.id_ano_letivo;
						option.textContent = item.ano || item.id_ano_letivo;
						selectAno.appendChild(option);
					});
				} else {
					console.error(data.message);
				}
			})
			.catch(err => console.error(err));
	}
	loadAnoLetivo();

	// ----------------------------------------------------------------
	// Função global: carrega lista de salas e exibe na tabela
	// ----------------------------------------------------------------
	function fetchSalas() {
		fetch('/horarios/app/controllers/sala/listSala.php')
			.then(r => r.json())
			.then(data => {
				if(data.status === 'success') {
					if (Array.isArray(data.data)) {
						salaData = data.data;
					} else if (data.data) {
						// Se veio um único objeto, transforma em array
						salaData = [data.data];
					} else {
						salaData = [];
					}
					renderSalaTable(salaData);
				} else {
					console.error(data.message);
				}
			})
			.catch(err => console.error(err));
	}

	function renderSalaTable(rows) {
		salaTableBody.innerHTML = '';
		if(!rows || rows.length === 0) {
			noDataMessageSala.style.display = 'block';
			return;
		}
		noDataMessageSala.style.display = 'none';

		rows.forEach(row => {
			const tr = document.createElement('tr');
			tr.dataset.id = row.id_sala;
			
			// Coluna Ano Letivo
			const tdAno = document.createElement('td');
			tdAno.textContent = row.ano ? row.ano : row.id_ano_letivo;
			tr.appendChild(tdAno);
			
			// Coluna Nome da Sala
			const tdNome = document.createElement('td');
			tdNome.textContent = row.nome_sala;
			tr.appendChild(tdNome);

			// Coluna Turmas Vinculadas
			const tdTurmas = document.createElement('td');
			tdTurmas.textContent = row.turmas_vinculadas || '';
			tr.appendChild(tdTurmas);

			// Coluna Ações
			const tdActions = document.createElement('td');
			
			// Botão Editar
			const btnEdit = document.createElement('button');
			btnEdit.classList.add('btn-edit');
			btnEdit.dataset.id = row.id_sala;
			btnEdit.innerHTML = '<span class="icon"><i class="fa-solid fa-pen-to-square"></i></span><span class="text">Editar</span>';
			tdActions.appendChild(btnEdit);
			
			// Botão Deletar
			const btnDelete = document.createElement('button');
			btnDelete.classList.add('btn-delete');
			btnDelete.dataset.id = row.id_sala;
			btnDelete.innerHTML = '<span class="icon"><i class="fa-solid fa-trash"></i></span><span class="text">Deletar</span>';
			tdActions.appendChild(btnDelete);
			
			// Botão Imprimir
			const btnPrint = document.createElement('button');
			btnPrint.classList.add('btn-print');
			btnPrint.dataset.id = row.id_sala;
			btnPrint.innerHTML = '<span class="icon"><i class="fa-solid fa-print"></i></span><span class="text">Imprimir</span>';
			tdActions.appendChild(btnPrint);
			
			// Botão Vincular Turma
			const btnVincular = document.createElement('button');
			btnVincular.classList.add('btn-vincular');
			btnVincular.dataset.id = row.id_sala;
			btnVincular.dataset.nome = row.nome_sala;
			btnVincular.innerHTML = '<span class="icon"><i class="fa-solid fa-link"></i></span><span class="text">Turmas</span>';
			tdActions.appendChild(btnVincular);

			tr.appendChild(tdActions);
			salaTableBody.appendChild(tr);
		});
	}

	// ----------------------------------------------------------------
	// ABRIR/FECHAR MODAL DE CADASTRO/EDIÇÃO
	// ----------------------------------------------------------------
	function openSalaModal() {
		modalSala.style.display = 'block';
		modalSala.classList.remove('fade-out');
		modalSala.classList.add('fade-in');

		const content = modalSala.querySelector('.modal-content');
		content.classList.remove('slide-up');
		content.classList.add('slide-down');

		// Como a tabela "sala" não possui o campo Nível de Ensino, removemos essa parte
		// e apenas exibimos o modal normalmente.

		if(!isEditModeSala) {
			clearSalaForm();
			document.getElementById('modal-sala-title').innerText = 'Adicionar Sala';
			saveSalaBtn.innerText = 'Salvar';
		}
	}

	function closeSalaModal() {
		const content = modalSala.querySelector('.modal-content');
		content.classList.remove('slide-down');
		content.classList.add('slide-up');
		modalSala.classList.remove('fade-in');
		modalSala.classList.add('fade-out');

		setTimeout(() => {
			modalSala.style.display = 'none';
			content.classList.remove('slide-up');
			modalSala.classList.remove('fade-out');
			isEditModeSala = false;
			currentEditSalaId = null;
		}, 300);
	}

	function clearSalaForm() {
		document.getElementById('salaId').value = '';
		document.getElementById('anoLetivo').value = '';
		document.getElementById('nomeSala').value = '';
		document.getElementById('maxCarteiras').value = '';
		document.getElementById('maxCadeiras').value = '';
		document.getElementById('capacidadeAlunos').value = '';
		document.getElementById('localizacao').value = '';
		document.getElementById('recursos').value = '';
		// Não há select de Nível de Ensino para limpar
	}

	btnAddSala.addEventListener('click', () => {
		isEditModeSala = false;
		openSalaModal();
	});
	closeSalaModalElements.forEach(el => el.addEventListener('click', closeSalaModal));
	cancelSalaBtn.addEventListener('click', () => {
		clearSalaForm();
		closeSalaModal();
	});

	// ----------------------------------------------------------------
	// SALVAR (INSERT/UPDATE) SALA
	// ----------------------------------------------------------------
	saveSalaBtn.addEventListener('click', () => {
		const id = document.getElementById('salaId').value;
		const anoLetivo = document.getElementById('anoLetivo').value.trim();
		const nomeSala = document.getElementById('nomeSala').value.trim();
		const maxCarteiras = document.getElementById('maxCarteiras').value.trim();
		const maxCadeiras = document.getElementById('maxCadeiras').value.trim();
		const capacidadeAlunos = document.getElementById('capacidadeAlunos').value.trim();
		const localizacao = document.getElementById('localizacao').value.trim();
		const recursos = document.getElementById('recursos').value.trim();

		if(!anoLetivo) {
			alert('Selecione o ano letivo.');
			return;
		}
		if(!nomeSala || maxCarteiras === '' || maxCadeiras === '' || capacidadeAlunos === '') {
			alert('Preencha os campos obrigatórios.');
			return;
		}

		const params = new URLSearchParams({
			id_ano_letivo: anoLetivo,
			nome_sala: nomeSala,
			max_carteiras: maxCarteiras,
			max_cadeiras: maxCadeiras,
			capacidade_alunos: capacidadeAlunos,
			localizacao: localizacao,
			recursos: recursos
		});

		let url = '';
		if(isEditModeSala) {
			url = '/horarios/app/controllers/sala/updateSala.php';
			params.append('id', id);
		} else {
			url = '/horarios/app/controllers/sala/insertSala.php';
		}

		fetch(url, {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: params.toString()
		})
		.then(r => r.json())
		.then(data => {
			alert(data.message);
			if(data.status === 'success') {
				closeSalaModal();
				fetchSalas();
			}
		})
		.catch(err => console.error(err));
	});

	// ----------------------------------------------------------------
	// CLIQUE NOS BOTÕES (EDITAR, DELETAR, IMPRIMIR, VINCULAR)
	// ----------------------------------------------------------------
	salaTableBody.addEventListener('click', e => {
		const btn = e.target.closest('button');
		if(!btn) return;
		const salaId = btn.dataset.id;
		
		if(btn.classList.contains('btn-edit')) {
			// EDIÇÃO
			isEditModeSala = true;
			currentEditSalaId = salaId;
			fetch('/horarios/app/controllers/sala/listSala.php?id=' + salaId)
				.then(r => r.json())
				.then(data => {
					if(data.status === 'success' && data.data) {
						document.getElementById('salaId').value = data.data.id_sala;
						document.getElementById('anoLetivo').value = data.data.id_ano_letivo;
						document.getElementById('nomeSala').value = data.data.nome_sala;
						document.getElementById('maxCarteiras').value = data.data.max_carteiras;
						document.getElementById('maxCadeiras').value = data.data.max_cadeiras;
						document.getElementById('capacidadeAlunos').value = data.data.capacidade_alunos;
						document.getElementById('localizacao').value = data.data.localizacao;
						document.getElementById('recursos').value = data.data.recursos;
						document.getElementById('modal-sala-title').innerText = 'Editar Sala';
						saveSalaBtn.innerText = 'Alterar';
						openSalaModal();
					}
				});
		} 
		else if(btn.classList.contains('btn-delete')) {
			// EXCLUIR
			currentEditSalaId = salaId;
			openDeleteSalaModal();
		} 
		else if(btn.classList.contains('btn-print')) {
			// IMPRIMIR (abrir modal “Imprimir Sala”)
			fetch('/horarios/app/controllers/sala/listSala.php?id=' + salaId)
				.then(r => r.json())
				.then(data => {
					if(data.status === 'success' && data.data) {
						document.getElementById('selected-sala').value = data.data.nome_sala;
						document.getElementById('salaId').value = data.data.id_sala;
						openPrintSalaModal();
					}
				});
		} 
		else if(btn.classList.contains('btn-vincular')) {
			// VINCULAR TURMA
			const nomeSala = btn.dataset.nome;
			document.getElementById('nomeSalaTurno').value = nomeSala;
			document.getElementById('salaIdTurno').value = salaId;
			
			if(typeof window.openSalaTurnoModal === 'function') {
				window.openSalaTurnoModal(salaId);
			} else {
				console.error('Função openSalaTurnoModal não encontrada.');
			}
		}
	});

	// ----------------------------------------------------------------
	// MODAL DE EXCLUIR SALA
	// ----------------------------------------------------------------
	const modalDeleteSala = document.getElementById('modal-delete-sala');
	const closeDeleteSalaBtn = document.getElementById('close-delete-sala-modal');
	const cancelDeleteSalaBtn = document.getElementById('cancel-delete-sala-btn');
	const confirmDeleteSalaBtn = document.getElementById('confirm-delete-sala-btn');

	function openDeleteSalaModal() {
		modalDeleteSala.style.display = 'block';
		modalDeleteSala.classList.remove('fade-out');
		modalDeleteSala.classList.add('fade-in');
		const content = modalDeleteSala.querySelector('.modal-content');
		content.classList.remove('slide-up');
		content.classList.add('slide-down');
	}

	function closeDeleteSalaModal() {
		const content = modalDeleteSala.querySelector('.modal-content');
		content.classList.remove('slide-down');
		content.classList.add('slide-up');
		modalDeleteSala.classList.remove('fade-in');
		modalDeleteSala.classList.add('fade-out');
		setTimeout(() => {
			modalDeleteSala.style.display = 'none';
			content.classList.remove('slide-up');
			modalDeleteSala.classList.remove('fade-out');
		}, 300);
	}

	closeDeleteSalaBtn.addEventListener('click', closeDeleteSalaModal);
	cancelDeleteSalaBtn.addEventListener('click', closeDeleteSalaModal);

	confirmDeleteSalaBtn.addEventListener('click', () => {
		fetch('/horarios/app/controllers/sala/deleteSala.php', {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: new URLSearchParams({ id: currentEditSalaId }).toString()
		})
		.then(r => r.json())
		.then(data => {
			alert(data.message);
			if(data.status === 'success') {
				fetchSalas();
			}
			closeDeleteSalaModal();
		})
		.catch(err => console.error(err));
	});

	// ----------------------------------------------------------------
	// CAMPO DE PESQUISA / FILTRO
	// ----------------------------------------------------------------
	searchInputSala.addEventListener('input', function() {
		const value = this.value.toLowerCase();
		const rows = salaTableBody.querySelectorAll('tr');
		let count = 0;
		rows.forEach(tr => {
			const ano = tr.children[0].textContent.toLowerCase();
			const nome = tr.children[1].textContent.toLowerCase();
			if(ano.includes(value) || nome.includes(value)) {
				tr.style.display = '';
				count++;
			} else {
				tr.style.display = 'none';
			}
		});
		noDataMessageSala.style.display = count === 0 ? 'block' : 'none';
	});

	// ----------------------------------------------------------------
	// ORDENAÇÃO (Ano / Nome)
	// ----------------------------------------------------------------
	sortNomeSalaAsc.addEventListener('click', () => {
		salaData.sort((a, b) => a.nome_sala.localeCompare(b.nome_sala));
		renderSalaTable(salaData);
	});
	sortNomeSalaDesc.addEventListener('click', () => {
		salaData.sort((a, b) => b.nome_sala.localeCompare(a.nome_sala));
		renderSalaTable(salaData);
	});
	sortAnoAsc.addEventListener('click', () => {
		salaData.sort((a, b) => {
			const anoA = a.ano ? a.ano : a.id_ano_letivo;
			const anoB = b.ano ? b.ano : b.id_ano_letivo;
			return anoA.toString().localeCompare(anoB.toString());
		});
		renderSalaTable(salaData);
	});
	sortAnoDesc.addEventListener('click', () => {
		salaData.sort((a, b) => {
			const anoA = a.ano ? a.ano : a.id_ano_letivo;
			const anoB = b.ano ? b.ano : b.id_ano_letivo;
			return anoB.toString().localeCompare(anoA.toString());
		});
		renderSalaTable(salaData);
	});

	// Botão "Imprimir Geral"
	btnImprimirSala.addEventListener('click', () => {
		openPrintSalaGeralModal();
	});

	// Disponibiliza fetchSalas globalmente (usado em sala-turno.js)
	window.fetchSalas = fetchSalas;

	// ----------------------------------------------------------------
	// MODAL DE IMPRESSÃO (INDIVIDUAL)
	// ----------------------------------------------------------------
	const modalPrintSala = document.getElementById('modal-print-sala');
	const closePrintSalaBtn = document.getElementById('close-print-sala');
	const btnImprimirSalaModal = document.getElementById('btn-imprimir-sala');
	const btnCancelarSalaModal = document.getElementById('btn-cancelar-sala');
	const chkTurmasSala = document.getElementById('chk-turmas-sala');

	function openPrintSalaModal() {
		modalPrintSala.style.display = 'block';
		modalPrintSala.classList.remove('fade-out');
		modalPrintSala.classList.add('fade-in');
		const content = modalPrintSala.querySelector('.modal-content');
		content.classList.remove('slide-up');
		content.classList.add('slide-down');
	}

	function closePrintSalaModal() {
		const content = modalPrintSala.querySelector('.modal-content');
		content.classList.remove('slide-down');
		content.classList.add('slide-up');
		modalPrintSala.classList.remove('fade-in');
		modalPrintSala.classList.add('fade-out');
		setTimeout(() => {
			modalPrintSala.style.display = 'none';
			content.classList.remove('slide-up');
			modalPrintSala.classList.remove('fade-out');
		}, 300);
	}

	btnImprimirSalaModal.addEventListener('click', () => {
		const salaIdParaImprimir = document.getElementById('salaId').value;  
		let url = '';

		if(chkTurmasSala.checked) {
			// Com turmas
			url = '/horarios/app/views/sala.php?id_sala=' + salaIdParaImprimir + '&turmas=1';
		} else {
			// Sem turmas
			url = '/horarios/app/views/sala.php?id_sala=' + salaIdParaImprimir;
		}
		window.open(url, '_blank');
		closePrintSalaModal();
	});
	btnCancelarSalaModal.addEventListener('click', closePrintSalaModal);

	// ----------------------------------------------------------------
	// MODAL DE IMPRESSÃO (GERAL)
	// ----------------------------------------------------------------
	const modalPrintSalaGeral = document.getElementById('modal-print-sala-geral');
	const closePrintSalaGeralBtn = document.getElementById('close-print-sala-geral');
	const btnImprimirSalaGeral = document.getElementById('btn-imprimir-sala-geral-confirm');
	const btnCancelarSalaGeral = document.getElementById('btn-cancelar-sala-geral');
	// Checkbox de turmas no relatório geral
	const chkTurmasSalaGeral = document.getElementById('chk-turmas-sala-geral');

	function openPrintSalaGeralModal() {
		modalPrintSalaGeral.style.display = 'block';
		modalPrintSalaGeral.classList.remove('fade-out');
		modalPrintSalaGeral.classList.add('fade-in');
		const content = modalPrintSalaGeral.querySelector('.modal-content');
		content.classList.remove('slide-up');
		content.classList.add('slide-down');
		// Carrega os níveis de ensino para o modal de impressão geral
		loadNivelEnsinoModal('select-nivel-geral-sala');
	}

	function closePrintSalaGeralModal() {
		const content = modalPrintSalaGeral.querySelector('.modal-content');
		content.classList.remove('slide-down');
		content.classList.add('slide-up');
		modalPrintSalaGeral.classList.remove('fade-in');
		modalPrintSalaGeral.classList.add('fade-out');
		setTimeout(() => {
			modalPrintSalaGeral.style.display = 'none';
			content.classList.remove('slide-up');
			modalPrintSalaGeral.classList.remove('fade-out');
			// Ao fechar, desmarca a checkbox de Turmas (corrige o problema reportado)
			chkTurmasSalaGeral.checked = false;
		}, 300);
	}

	closePrintSalaGeralBtn.addEventListener('click', closePrintSalaGeralModal);
	btnCancelarSalaGeral.addEventListener('click', closePrintSalaGeralModal);

	btnImprimirSalaGeral.addEventListener('click', () => {
		// Monta a URL com parâmetros conforme escolha do usuário
		let url = '/horarios/app/views/sala-geral.php';
		let params = [];

		// Pega o nível de ensino escolhido
		const nivelGeral = document.getElementById('select-nivel-geral-sala').value;
		if(nivelGeral && nivelGeral !== 'todos') {
			params.push('id_nivel_ensino=' + nivelGeral);
		}

		// Se estiver marcado para exibir Turmas
		if(chkTurmasSalaGeral.checked) {
			params.push('turmas=1');
		}

		if(params.length > 0) {
			url += '?' + params.join('&');
		}

		window.open(url, '_blank');
		closePrintSalaGeralModal();
	});

	// Inicializa a tabela
	fetchSalas();
});
