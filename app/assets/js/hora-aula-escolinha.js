// app/assets/js/hora-aula-escolinha.js
document.addEventListener('DOMContentLoaded', function() {
	/* ============================================================
		0) REFERÊNCIAS
	============================================================ */
	const modal = document.getElementById('modal-hora-aula-escolinha');
	const btnAdd = document.getElementById('btn-add');
	const closeModalElements = document.querySelectorAll('.close-modal');
	const cancelBtn = document.getElementById('cancel-btn');
	const saveBtn = document.getElementById('save-btn');
	
	// Modal de exclusão
	const modalDelete = document.getElementById('modal-delete');
	const closeDeleteModalBtn = document.getElementById('close-delete-modal');
	const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
	const cancelDeleteBtn = document.getElementById('cancel-delete-btn');
	
	// Modais de impressão
	const modalPrintIndividual = document.getElementById('modal-print-hora-aula');
	const modalPrintGeral = document.getElementById('modal-print-hora-aula-geral');
	const btnImprimir = document.getElementById('btnImprimir');
	const closePrintIndividual = document.getElementById('close-print-hora-aula');
	const closePrintGeral = document.getElementById('close-print-hora-aula-geral');
	
	// Tabela e mensagem
	const horaAulaTableBody = document.getElementById('horaAulaTable');
	const noDataMessage = document.getElementById('no-data-message');
	
	// Campos do modal
	const selectAnoLetivoModal = document.getElementById('select-ano-letivo-modal');
	const selectModalidades = document.getElementById('select-modalidades');
	const inputDuracao = document.getElementById('duracao-aula');
	const chkToleranciaQuebra = document.getElementById('tolerancia-quebra');
	const btnSelecionarTodas = document.getElementById('btn-selecionar-todas');
	const btnLimparSelecao = document.getElementById('btn-limpar-selecao');
	
	// Arrays de controle
	let horaAulaData = [];
	let modalidadesData = [];
	let anosLetivosData = [];
	
	let isEditMode = false;
	let currentEditId = null;
	let currentDeleteId = null;
	let currentPrintId = null;
	
	/* ============================================================
		1) CARREGAR DADOS INICIAIS
	============================================================ */
	function carregarAnosLetivos() {
		fetch('/horarios/app/controllers/ano-letivo/listAnoLetivo.php')
			.then(r => r.json())
			.then(data => {
				if (data.status === 'success') {
					anosLetivosData = data.data || [];
					renderSelectAnosLetivos();
				} else {
					console.error(data.message);
				}
			})
			.catch(err => console.error(err));
	}
	
	function renderSelectAnosLetivos() {
		// Select do modal
		selectAnoLetivoModal.innerHTML = '<option value="">Selecione o Ano Letivo</option>';
		
		// Select do modal de impressão geral
		const selectAnoGeral = document.getElementById('select-ano-geral');
		if (selectAnoGeral) {
			selectAnoGeral.innerHTML = '<option value="todos">Todos</option>';
		}
		
		anosLetivosData.forEach(ano => {
			const optModal = document.createElement('option');
			optModal.value = ano.id_ano_letivo;
			optModal.textContent = ano.ano;
			selectAnoLetivoModal.appendChild(optModal);
			
			// Para o modal de impressão geral
			if (selectAnoGeral) {
				const optPrint = document.createElement('option');
				optPrint.value = ano.id_ano_letivo;
				optPrint.textContent = ano.ano;
				selectAnoGeral.appendChild(optPrint);
			}
		});
	}
	
	function carregarModalidades() {
		fetch('/horarios/app/controllers/modalidade/listModalidadeComCategoria.php')
			.then(r => r.json())
			.then(data => {
				if (data.status === 'success') {
					modalidadesData = data.data || [];
					renderSelectModalidades();
					renderSelectModalidadesGeral();
				} else {
					console.error(data.message);
				}
			})
			.catch(err => console.error(err));
	}

	// Substitua a função renderSelectModalidades() existente por esta versão:

	function renderSelectModalidades() {
		selectModalidades.innerHTML = '';
		
		// Remove qualquer classe de grid existente e adiciona estilo inline para duas colunas
		selectModalidades.classList.remove('checkbox-grid');
		selectModalidades.style.display = 'grid';
		selectModalidades.style.gridTemplateColumns = '1fr 1fr';
		selectModalidades.style.gap = '8px';
		selectModalidades.style.maxHeight = '300px';
		selectModalidades.style.overflowY = 'auto';
		
		// Ordena modalidades por ordem alfabética do nome completo
		const modalidadesOrdenadas = [...modalidadesData].sort((a, b) => 
			a.nome_completo.localeCompare(b.nome_completo)
		);
		
		modalidadesOrdenadas.forEach(modalidade => {
			const div = document.createElement('div');
			div.style.display = 'flex';
			div.style.alignItems = 'center';
			div.style.gap = '8px';
			//div.style.padding = '4px';
			
			const checkbox = document.createElement('input');
			checkbox.type = 'checkbox';
			checkbox.id = `categoria_${modalidade.id_categoria}`;
			checkbox.value = modalidade.id_categoria;
			checkbox.name = 'categorias[]';
			// Mantém o tamanho original do checkbox
			checkbox.style.width = '16px';
			checkbox.style.height = '16px';
			checkbox.style.transform = 'none';
			
			const label = document.createElement('label');
			label.htmlFor = `categoria_${modalidade.id_categoria}`;
			label.textContent = modalidade.nome_completo; // Modalidade - Categoria
			label.style.fontWeight = 'normal'; // Remove negrito
			label.style.cursor = 'pointer';
			label.style.fontSize = '16px';
			
			div.appendChild(checkbox);
			div.appendChild(label);
			selectModalidades.appendChild(div);
		});
	}
	
	function renderSelectModalidadesGeral() {
		const selectModalidadeGeral = document.getElementById('select-modalidade-geral');
		if (selectModalidadeGeral) {
			selectModalidadeGeral.innerHTML = '<option value="todas">Todas</option>';
			
			// Agrupa por modalidade para não duplicar no select
			const modalidadesUnicas = [];
			const modalidadesAdicionadas = new Set();
			
			modalidadesData.forEach(item => {
				if (!modalidadesAdicionadas.has(item.id_modalidade)) {
					modalidadesUnicas.push({
						id_modalidade: item.id_modalidade,
						nome_modalidade: item.nome_modalidade
					});
					modalidadesAdicionadas.add(item.id_modalidade);
				}
			});
			
			modalidadesUnicas.forEach(modalidade => {
				const option = document.createElement('option');
				option.value = modalidade.id_modalidade;
				option.textContent = modalidade.nome_modalidade;
				selectModalidadeGeral.appendChild(option);
			});
		}
	}
	
	/* ============================================================
		2) LISTAR CONFIGURAÇÕES (READ)
	============================================================ */
	function fetchHoraAulaEscolinha(anoLetivoId = '') {
		let url = '/horarios/app/controllers/hora-aula-escolinha/listHoraAulaEscolinha.php';
		if (anoLetivoId) {
			url += `?id_ano_letivo=${anoLetivoId}`;
		}
		
		fetch(url)
			.then(r => r.json())
			.then(data => {
				if (data.status === 'success') {
					horaAulaData = data.data || [];
					renderTable(horaAulaData);
				} else {
					console.error(data.message);
				}
			})
			.catch(err => console.error(err));
	}
	
	function renderTable(rows) {
		horaAulaTableBody.innerHTML = '';
		if (!rows || rows.length === 0) {
			noDataMessage.style.display = 'block';
			return;
		}
		noDataMessage.style.display = 'none';

		rows.forEach(row => {
			const tr = document.createElement('tr');
			tr.dataset.id = row.id_configuracao;

			const tdAno = document.createElement('td');
			tdAno.textContent = row.ano_letivo || 'N/A';
			tr.appendChild(tdAno);

			const tdModalidade = document.createElement('td');
			// Assumindo que o backend retorna o nome completo "Modalidade - Categoria"
			tdModalidade.textContent = row.nome_modalidade_categoria || row.nome_modalidade || 'N/A';
			tr.appendChild(tdModalidade);

			const tdDuracao = document.createElement('td');
			tdDuracao.textContent = `${row.duracao_aula_minutos} min`;
			tr.appendChild(tdDuracao);

			const tdTolerancia = document.createElement('td');
			const spanTolerancia = document.createElement('span');
			spanTolerancia.classList.add('status-badge');
			if (row.tolerancia_quebra == '1') {
				spanTolerancia.classList.add('status-ativo');
				spanTolerancia.textContent = 'Sim';
			} else {
				spanTolerancia.classList.add('status-inativo');
				spanTolerancia.textContent = 'Não';
			}
			tdTolerancia.appendChild(spanTolerancia);
			tr.appendChild(tdTolerancia);

			// Coluna Status (assumindo que existe um campo status)
			const tdStatus = document.createElement('td');
			const spanStatus = document.createElement('span');
			spanStatus.classList.add('status-badge', 'status-ativo');
			spanStatus.textContent = 'Ativo';
			tdStatus.appendChild(spanStatus);
			tr.appendChild(tdStatus);

			const tdActions = document.createElement('td');
			
			// Botão Imprimir
			const btnPrint = document.createElement('button');
			btnPrint.classList.add('btn-print');
			btnPrint.dataset.id = row.id_configuracao;
			btnPrint.innerHTML = `
				<span class="icon"><i class="fa-solid fa-print"></i></span>
				<span class="text">Imprimir</span>`;
			tdActions.appendChild(btnPrint);
			
			// Botão Editar
			const btnEdit = document.createElement('button');
			btnEdit.classList.add('btn-edit');
			btnEdit.dataset.id = row.id_configuracao;
			btnEdit.innerHTML = `
				<span class="icon"><i class="fa-solid fa-pen-to-square"></i></span>
				<span class="text">Editar</span>`;
			tdActions.appendChild(btnEdit);

			// Botão Deletar
			const btnDelete = document.createElement('button');
			btnDelete.classList.add('btn-delete');
			btnDelete.dataset.id = row.id_configuracao;
			btnDelete.innerHTML = `
				<span class="icon"><i class="fa-solid fa-trash"></i></span>
				<span class="text">Deletar</span>`;
			tdActions.appendChild(btnDelete);

			tr.appendChild(tdActions);
			horaAulaTableBody.appendChild(tr);
		});
	}
	
	/* ============================================================
		3) EVENTOS DE ORDENAÇÃO
	============================================================ */
	// Ordenação da tabela
	function sortTable(column, order) {
		const tbody = document.getElementById('horaAulaTable');
		const rows = Array.from(tbody.querySelectorAll('tr'));
		
		rows.sort((a, b) => {
			let aVal, bVal;
			
			switch(column) {
				case 'ano':
					aVal = a.querySelector('td:nth-child(1)').textContent;
					bVal = b.querySelector('td:nth-child(1)').textContent;
					break;
				case 'modalidade':
					aVal = a.querySelector('td:nth-child(2)').textContent;
					bVal = b.querySelector('td:nth-child(2)').textContent;
					break;
				case 'duracao':
					aVal = parseInt(a.querySelector('td:nth-child(3)').textContent);
					bVal = parseInt(b.querySelector('td:nth-child(3)').textContent);
					break;
				default:
					return 0;
			}
			
			if (order === 'asc') {
				return aVal > bVal ? 1 : -1;
			} else {
				return aVal < bVal ? 1 : -1;
			}
		});
		
		tbody.innerHTML = '';
		rows.forEach(row => tbody.appendChild(row));
	}
	
	// Event listeners para ordenação
	document.getElementById('sort-ano-asc')?.addEventListener('click', () => sortTable('ano', 'asc'));
	document.getElementById('sort-ano-desc')?.addEventListener('click', () => sortTable('ano', 'desc'));
	document.getElementById('sort-modalidade-asc')?.addEventListener('click', () => sortTable('modalidade', 'asc'));
	document.getElementById('sort-modalidade-desc')?.addEventListener('click', () => sortTable('modalidade', 'desc'));
	document.getElementById('sort-duracao-asc')?.addEventListener('click', () => sortTable('duracao', 'asc'));
	document.getElementById('sort-duracao-desc')?.addEventListener('click', () => sortTable('duracao', 'desc'));
	
	/* ============================================================
		4) FUNÇÕES PARA LIMPAR CAMPOS DOS MODAIS DE IMPRESSÃO
	============================================================ */
	function clearPrintIndividualForm() {
		const selectApenasAtivasIndividual = document.getElementById('select-apenas-ativas-individual');
		const selectedConfiguracao = document.getElementById('selected-configuracao');
		
		if (selectApenasAtivasIndividual) {
			selectApenasAtivasIndividual.value = 'todos';
		}
		if (selectedConfiguracao) {
			selectedConfiguracao.value = '';
		}
	}
	
	function clearPrintGeralForm() {
		const selectAnoGeral = document.getElementById('select-ano-geral');
		const selectModalidadeGeral = document.getElementById('select-modalidade-geral');
		const selectApenasAtivasGeral = document.getElementById('select-apenas-ativas-geral');
		
		if (selectAnoGeral) {
			selectAnoGeral.value = 'todos';
		}
		if (selectModalidadeGeral) {
			selectModalidadeGeral.value = 'todas';
		}
		if (selectApenasAtivasGeral) {
			selectApenasAtivasGeral.value = 'todos';
		}
	}
	
	/* ============================================================
		5) ABRIR/FECHAR MODAIS
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
			//document.getElementById('modal-title').innerText = 'Configurar Hora/Aula - Escolinha';
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
	
	function openPrintModal(id = null) {
		if (id) {
			// Impressão individual - limpa o formulário antes de abrir
			clearPrintIndividualForm();
			
			currentPrintId = id;
			const configAtual = horaAulaData.find(item => item.id_configuracao == id);
			if (configAtual) {
				document.getElementById('selected-configuracao').value = 
					`${configAtual.ano_letivo} - ${configAtual.nome_modalidade} (${configAtual.duracao_aula_minutos}min)`;
			}
			modalPrintIndividual.style.display = 'block';
			modalPrintIndividual.classList.remove('fade-out');
			modalPrintIndividual.classList.add('fade-in');
			const content = modalPrintIndividual.querySelector('.modal-content');
			content.classList.remove('slide-up');
			content.classList.add('slide-down');
		} else {
			// Impressão geral - limpa o formulário antes de abrir
			clearPrintGeralForm();
			
			modalPrintGeral.style.display = 'block';
			modalPrintGeral.classList.remove('fade-out');
			modalPrintGeral.classList.add('fade-in');
			const content = modalPrintGeral.querySelector('.modal-content');
			content.classList.remove('slide-up');
			content.classList.add('slide-down');
		}
	}
	
	function closePrintModal(isGeral = false) {
		const targetModal = isGeral ? modalPrintGeral : modalPrintIndividual;
		const content = targetModal.querySelector('.modal-content');
		content.classList.remove('slide-down');
		content.classList.add('slide-up');
		targetModal.classList.remove('fade-in');
		targetModal.classList.add('fade-out');
		setTimeout(() => {
			targetModal.style.display = 'none';
			content.classList.remove('slide-up');
			targetModal.classList.remove('fade-out');
			
			// Limpa os campos quando o modal é fechado
			if (isGeral) {
				clearPrintGeralForm();
			} else {
				clearPrintIndividualForm();
				currentPrintId = null;
			}
		}, 300);
	}
	
	function clearForm() {
		selectAnoLetivoModal.value = '';
		inputDuracao.value = '50';
		chkToleranciaQuebra.checked = true;
		// Desmarca todas as categorias
		const checkboxes = selectModalidades.querySelectorAll('input[type="checkbox"]');
		checkboxes.forEach(cb => cb.checked = false);
	}
	
	/* ============================================================
		6) EVENTOS DOS BOTÕES PRINCIPAIS
	============================================================ */
	btnAdd.addEventListener('click', () => {
		isEditMode = false;
		openModal();
	});
	
	closeModalElements.forEach(el => {
		el.addEventListener('click', (e) => {
			const modal = e.target.closest('.modal');
			if (modal.id === 'modal-print-hora-aula') {
				closePrintModal(false);
			} else if (modal.id === 'modal-print-hora-aula-geral') {
				closePrintModal(true);
			} else {
				closeModal();
			}
		});
	});
	
	cancelBtn.addEventListener('click', () => {
		if (!isEditMode) clearForm();
		closeModal();
	});
	
	// Eventos dos modais de impressão
	btnImprimir?.addEventListener('click', () => openPrintModal());
	closePrintIndividual?.addEventListener('click', () => closePrintModal(false));
	closePrintGeral?.addEventListener('click', () => closePrintModal(true));
	
	closeDeleteModalBtn.addEventListener('click', closeDeleteModal);
	cancelDeleteBtn.addEventListener('click', closeDeleteModal);
	
	// Botões de cancelar dos modais de impressão
	document.getElementById('btn-cancelar')?.addEventListener('click', () => closePrintModal(false));
	document.getElementById('btn-cancelar-geral')?.addEventListener('click', () => closePrintModal(true));
	
	/* ============================================================
		7) SELEÇÃO MÚLTIPLA DE MODALIDADES
	============================================================ */
	btnSelecionarTodas.addEventListener('click', () => {
		const checkboxes = selectModalidades.querySelectorAll('input[type="checkbox"]');
		checkboxes.forEach(cb => cb.checked = true);
	});
	
	btnLimparSelecao.addEventListener('click', () => {
		const checkboxes = selectModalidades.querySelectorAll('input[type="checkbox"]');
		checkboxes.forEach(cb => cb.checked = false);
	});
	
	/* ============================================================
		8) SALVAR CONFIGURAÇÕES
	============================================================ */
	saveBtn.addEventListener('click', () => {
		const anoLetivo = selectAnoLetivoModal.value;
		const duracao = inputDuracao.value.trim();
		const toleranciaQuebra = chkToleranciaQuebra.checked ? 1 : 0;
		
		// Pega categorias selecionadas (ao invés de modalidades)
		const categoriasSelecionadas = [];
		const checkboxes = selectModalidades.querySelectorAll('input[type="checkbox"]:checked');
		checkboxes.forEach(cb => {
			categoriasSelecionadas.push(cb.value);
		});

		// Validações
		if (!anoLetivo) {
			alert('Selecione o Ano Letivo.');
			return;
		}
		if (!duracao || isNaN(duracao) || parseInt(duracao) <= 0) {
			alert('Informe uma duração válida em minutos.');
			return;
		}
		if (categoriasSelecionadas.length === 0) {
			alert('Selecione pelo menos uma categoria.');
			return;
		}

		const formData = new URLSearchParams({
			id_ano_letivo: anoLetivo,
			duracao_aula_minutos: duracao,
			tolerancia_quebra: toleranciaQuebra,
			categorias: JSON.stringify(categoriasSelecionadas) // Mudança aqui
		});

		if (isEditMode) {
			// UPDATE
			formData.append('id_configuracao', currentEditId);
			fetch('/horarios/app/controllers/hora-aula-escolinha/updateHoraAulaEscolinha.php', {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: formData
			})
			.then(r => r.json())
			.then(data => {
				alert(data.message);
				if (data.status === 'success') {
					closeModal();
					fetchHoraAulaEscolinha(''); // Carrega todos os dados sem filtro
				}
			})
			.catch(err => console.error(err));
		} else {
			// INSERT
			fetch('/horarios/app/controllers/hora-aula-escolinha/insertHoraAulaEscolinha.php', {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: formData
			})
			.then(r => r.json())
			.then(data => {
				if (data.status === 'success') {
					fetchHoraAulaEscolinha(''); // Carrega todos os dados sem filtro
					const resp = confirm('Salvo com sucesso! Deseja configurar outras categorias?');
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
		9) AÇÕES NA TABELA (EDITAR, DELETAR, IMPRIMIR)
	============================================================ */
	horaAulaTableBody.addEventListener('click', e => {
		const btn = e.target.closest('button');
		if (!btn) return;

		if (btn.classList.contains('btn-edit')) {
			// EDITAR
			isEditMode = true;
			currentEditId = btn.dataset.id;
			
			// Busca os dados da configuração específica
			const configAtual = horaAulaData.find(item => item.id_configuracao == currentEditId);
			if (configAtual) {
				selectAnoLetivoModal.value = configAtual.id_ano_letivo;
				inputDuracao.value = configAtual.duracao_aula_minutos;
				chkToleranciaQuebra.checked = configAtual.tolerancia_quebra == '1';
				
				// Marca apenas a categoria correspondente
				const checkboxes = selectModalidades.querySelectorAll('input[type="checkbox"]');
				checkboxes.forEach(cb => {
					cb.checked = (cb.value == configAtual.id_categoria);
				});
				
				document.getElementById('modal-title').innerText = 'Editar Configuração de Hora/Aula';
				saveBtn.innerText = 'Alterar';
				openModal();
			}
		} else if (btn.classList.contains('btn-delete')) {
			// DELETAR
			currentDeleteId = btn.dataset.id;
			openDeleteModal();
		} else if (btn.classList.contains('btn-print')) {
			// IMPRIMIR INDIVIDUAL
			openPrintModal(btn.dataset.id);
		}
	});
	
	/* ============================================================
		10) CONFIRMAÇÃO DE EXCLUSÃO
	============================================================ */
	confirmDeleteBtn.addEventListener('click', () => {
		fetch('/horarios/app/controllers/hora-aula-escolinha/deleteHoraAulaEscolinha.php', {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: new URLSearchParams({ id: currentDeleteId })
		})
		.then(r => r.json())
		.then(data => {
			alert(data.message);
			if (data.status === 'success') {
				fetchHoraAulaEscolinha(''); // Carrega todos os dados sem filtro
			}
			closeDeleteModal();
		})
		.catch(err => console.error(err));
	});
	
	/* ============================================================
		11) MODAL DE IMPRESSÃO (INDIVIDUAL E GERAL)
	============================================================ */

	// Abrir o modal de impressão geral
	//const btnImprimir = document.getElementById("btnImprimir");
	if (btnImprimir) {
		btnImprimir.addEventListener("click", () => openPrintModal(null));
	}

	// Fechar os modais de impressão
	document.getElementById("close-print-hora-aula")?.addEventListener("click", () => closePrintModal(false));
	document.getElementById("btn-cancelar")?.addEventListener("click", () => closePrintModal(false));
	document.getElementById("close-print-hora-aula-geral")?.addEventListener("click", () => closePrintModal(true));
	document.getElementById("btn-cancelar-geral")?.addEventListener("click", () => closePrintModal(true));

	// Impressão individual
	const btnImprimirIndividualConfirm = document.getElementById("btn-imprimir");
	const selectApenasAtivasIndividual = document.getElementById("select-apenas-ativas-individual");

	if (btnImprimirIndividualConfirm) {
		btnImprimirIndividualConfirm.addEventListener("click", () => {
			let url = `/horarios/app/views/hora-aula-escolinha-individual.php?id_configuracao=${currentPrintId}`;
			const apenasAtivas = selectApenasAtivasIndividual?.value;
			if (apenasAtivas && apenasAtivas !== "todos") {
				url += `&apenas_ativas=${apenasAtivas === "sim" ? 1 : 0}`;
			}
			window.open(url, "_blank");
			// Limpa o formulário após a impressão
			clearPrintIndividualForm();
			closePrintModal(false);
		});
	}

	// Impressão geral
	const btnImprimirGeralConfirm = document.getElementById("btn-imprimir-geral-confirm");
	const selectAnoGeral = document.getElementById("select-ano-geral");
	const selectApenasAtivasGeral = document.getElementById("select-apenas-ativas-geral");

	if (btnImprimirGeralConfirm) {
		btnImprimirGeralConfirm.addEventListener("click", () => {
			let url = "/horarios/app/views/hora-aula-escolinha.php?";
			const anoLetivo = selectAnoGeral?.value || "todos";
			const apenasAtivas = selectApenasAtivasGeral?.value || "todos";

			if (anoLetivo !== "todos") {
				url += `id_ano_letivo=${anoLetivo}`;
			}

			if (apenasAtivas !== "todos") {
				url += `${anoLetivo !== "todos" ? "&" : ""}apenas_ativas=${apenasAtivas === "sim" ? 1 : 0}`;
			}

			window.open(url, "_blank");
			closePrintModal(true);
		});
	}

	/* ============================================================
		11) PESQUISA
	============================================================ */
	document.getElementById('search-input').addEventListener('input', function() {
		const searchValue = this.value.toLowerCase();
		const rows = horaAulaTableBody.querySelectorAll('tr');
		let count = 0;
		rows.forEach(tr => {
			const tdModalidade = tr.querySelector('td:nth-child(2)').textContent.toLowerCase();
			const tdAno = tr.querySelector('td:nth-child(1)').textContent.toLowerCase();
			const tdDuracao = tr.querySelector('td:nth-child(3)').textContent.toLowerCase();
			if (tdModalidade.includes(searchValue) || tdAno.includes(searchValue) || tdDuracao.includes(searchValue)) {
				tr.style.display = '';
				count++;
			} else {
				tr.style.display = 'none';
			}
		});
		noDataMessage.style.display = (count === 0 && rows.length > 0) ? 'block' : 'none';
	});
	
	/* ============================================================
		12) INICIALIZAÇÃO
	============================================================ */
	carregarAnosLetivos();
	carregarModalidades();
	fetchHoraAulaEscolinha(''); // Carrega todos os dados inicialmente
});