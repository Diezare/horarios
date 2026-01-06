// app/assets/js/turma.js - VERSÃO CORRIGIDA COM INTERVALOS DINÂMICOS

document.addEventListener('DOMContentLoaded', function() {
	/* ============================================================
		 1) Referências gerais
	============================================================ */
	const modalTurma = document.getElementById('modal-turma');
	const btnAddTurma = document.getElementById('btn-add-turma');
	const closeModalTurmaElements = document.querySelectorAll('#modal-turma .close-modal');
	const cancelTurmaBtn = document.getElementById('cancel-btn');
	const saveTurmaBtn = document.getElementById('save-btn');
	
	// Modal de exclusão
	const modalDeleteTurma = document.getElementById('modal-delete-turma');
	const closeDeleteTurmaModalBtn = document.getElementById('close-delete-turma-modal');
	const confirmDeleteTurmaBtn = document.getElementById('confirm-delete-turma-btn');
	const cancelDeleteTurmaBtn = document.getElementById('cancel-delete-turma-btn');
	
	// Tabela
	const turmaTableBody = document.getElementById('turmaTable');
	const noDataMessageTurma = document.getElementById('no-data-message-turma');
	
	// Campos do formulário (CRUD)
	const inputTurmaId = document.getElementById('turmaId');
	const selecionarAnoLetivo = document.getElementById('selecionarAnoLetivo');
	const selectSerie = document.getElementById('selectSerie');
	const turnosContainer = document.getElementById('turnos-container');
	const inputNomeTurma = document.getElementById('nomeTurma');
	const intervalosContainer = document.getElementById('intervalos-container'); // ✅ NOVO: Container dinâmico
	
	let isEditMode = false;
	let currentEditId = null;
	let allTurmas = []; // Para sort
	let turnosData = []; // Armazena dados dos turnos carregados
	let intervalosPorTurno = {}; // Armazena intervalos por turno {turnoId: {quantidade: '', posicoes: ''}}
	
	/* ============================================================
		 2) MODAL DE IMPRESSÃO INDIVIDUAL DE TURMA
	============================================================ */
	const modalPrintTurma = document.getElementById('modal-print-turma');
	const closePrintTurmaBtn = document.getElementById('close-print-modal-turma');
	const btnImprimirTurma = document.getElementById('btn-imprimir');
	const btnCancelarPrintTurma = document.getElementById('btn-cancelar');
	const chkProfTurma = document.getElementById('chk-prof-turma');
	const readOnlyAno = document.getElementById('readOnlyAno');
	const readOnlySerie = document.getElementById('readOnlySerie');
	const readOnlyTurma = document.getElementById('readOnlyTurma');
	
	let currentPrintTurmaId = null;
	let currentPrintAno = null;
	let currentPrintSerie = null;
	let currentPrintNomeTurma = null;
	
	/* ============================================================
		 3) MODAL DE IMPRESSÃO GERAL DE TURMA
	============================================================ */
	const btnImprimirGeral = document.getElementById('btnImprimir'); // Botão na card-header
	const modalPrintTurmaGeral = document.getElementById('modal-print-turma-geral');
	const closePrintTurmaGeralBtn = document.getElementById('close-print-modal-turma-geral');
	const btnImprimirTurmaGeralConfirm = document.getElementById('btn-imprimir-turma-geral-confirm');
	const btnCancelarTurmaGeral = document.getElementById('btn-cancelar-turma-geral');
	const chkProfTurmaGeral = document.getElementById('chk-prof-turma-geral');
	
	/* ============================================================
		 4) SORT ICONS
	============================================================ */
	const sortAnoAsc = document.getElementById('sort-ano-asc');
	const sortAnoDesc = document.getElementById('sort-no-desc');
	const sortSerieAsc = document.getElementById('sort-serie-asc');
	const sortSerieDesc = document.getElementById('sort-serie-desc');
	const sortTurmaAsc = document.getElementById('sort-turma-asc');
	const sortTurmaDesc = document.getElementById('sort-turma-desc');
	const sortTurnoAsc = document.getElementById('sort-turno-asc');
	const sortTurnoDesc = document.getElementById('sort-turno-desc');
	
	function sortTable(property, asc = true) {
		const sorted = [...allTurmas].sort((a, b) => {
		const valA = (a[property] || '').toString().toLowerCase();
		const valB = (b[property] || '').toString().toLowerCase();
		return asc ? valA.localeCompare(valB) : valB.localeCompare(valA);
		});
		renderTable(sorted);
	}
	sortAnoAsc.addEventListener('click', () => sortTable('ano', true));
	sortAnoDesc.addEventListener('click', () => sortTable('ano', false));
	sortSerieAsc.addEventListener('click', () => sortTable('nome_serie', true));
	sortSerieDesc.addEventListener('click', () => sortTable('nome_serie', false));
	sortTurmaAsc.addEventListener('click', () => sortTable('nome_turma', true));
	sortTurmaDesc.addEventListener('click', () => sortTable('nome_turma', false));
	sortTurnoAsc.addEventListener('click', () => sortTable('nome_turno', true));
	sortTurnoDesc.addEventListener('click', () => sortTable('nome_turno', false));
	
	/* ============================================================
		 5) Regras de preenchimento do formulário
	============================================================ */
	inputNomeTurma.addEventListener('input', function() {
		this.value = this.value.substring(0, 1).toUpperCase();
	});
	
	// ✅ NOVO: Função para aplicar validação nos campos de intervalo (VERSÃO ATUALIZADA)
	function aplicarValidacaoIntervalos() {
		const inputsQtd = intervalosContainer.querySelectorAll('.intervalos-quantidade');
		const inputsPos = intervalosContainer.querySelectorAll('.intervalos-posicoes');
		
		inputsQtd.forEach(input => {
			input.addEventListener('input', function() {
				let value = this.value.replace(/\D/g, '');
				if (value.length > 1) value = value.substring(0, 1);
				this.value = value;
			});
		});
		
		inputsPos.forEach(input => {
			input.addEventListener('input', function() {
				// ✅ AGORA ACEITA: "4" ou "4,2" (números simples ou com vírgula)
				let value = this.value.replace(/[^0-9,]/g, '');
				
				// Remove múltiplas vírgulas
				if ((value.match(/,/g) || []).length > 1) {
					const parts = value.split(',');
					value = parts[0] + ',' + parts[1];
				}
				
				// Limita cada parte a 1 dígito
				if (value.includes(',')) {
					const parts = value.split(',');
					if (parts[0] && parts[0].length > 1) parts[0] = parts[0].substring(0, 1);
					if (parts[1] && parts[1].length > 1) parts[1] = parts[1].substring(0, 1);
					value = parts[0] + ',' + parts[1];
				} else {
					// Se for apenas um número, limita a 1 dígito
					if (value.length > 1) value = value.substring(0, 1);
				}
				
				this.value = value;
			});
		});
	}
	
	/* ============================================================
		 6) Carregar Ano Letivo, Séries e Turnos
	============================================================ */
	function loadAnoLetivo() {
		fetch('/horarios/app/controllers/ano-letivo/listAnoLetivo.php')
		.then(r => r.json())
		.then(data => {
			if (data.status === 'success') {
				// Define a opção padrão "Selecione"
				selecionarAnoLetivo.innerHTML = '<option value="">Selecione</option>';
				data.data.forEach(item => {
					const option = document.createElement('option');
					option.value = item.id_ano_letivo;
					option.textContent = item.ano;
					selecionarAnoLetivo.appendChild(option);
				});
			}
		})
		.catch(err => console.error(err));
	}
	
	function loadSeries() {
		fetch('/horarios/app/controllers/serie/listSerieByUser.php')
		.then(r => r.json())
		.then(data => {
			if (data.status === 'success') {
				// Coloca a opção default antes das séries carregadas
				selectSerie.innerHTML = '<option value="">-- Selecione uma Série --</option>';
				data.data.forEach(serie => {
					const option = document.createElement('option');
					option.value = serie.id_serie;
					option.textContent = serie.nome_serie;
					selectSerie.appendChild(option);
				});
			} else {
				console.error(data.message);
			}
		})
		.catch(err => console.error(err));
	}
	
	function loadTurnos() {
		turnosContainer.innerHTML = '';
		turnosData = []; // Limpa dados anteriores
		fetch('/horarios/app/controllers/turno/listTurno.php')
		.then(r => r.json())
		.then(data => {
			if (data.status === 'success') {
				data.data.forEach(turno => {
					turnosData.push(turno); // Armazena dados completos do turno
					
					const abreviado = turno.nome_turno.substring(0, 3) + '.';
					const label = document.createElement('label');
					label.classList.add('checkbox-inline');
	
					const checkbox = document.createElement('input');
					checkbox.type = 'checkbox';
					checkbox.value = turno.id_turno;
					checkbox.dataset.nome = turno.nome_turno; // ✅ Armazena o nome completo
					
					// ✅ Adiciona evento para atualizar intervalos quando mudar
					checkbox.addEventListener('change', atualizarCamposIntervalos);
					
					label.appendChild(checkbox);
					label.appendChild(document.createTextNode(' ' + abreviado));
					turnosContainer.appendChild(label);
				});
			}
		})
		.catch(err => console.error(err));
	}

	/* ============================================================
		7) ✅ FUNÇÃO: Atualizar campos de intervalo dinamicamente (VERSÃO CORRIGIDA)
	============================================================ */
	function atualizarCamposIntervalos() {
		let checkboxes = turnosContainer.querySelectorAll('input[type="checkbox"]:checked');
		let turnosSelecionados = Array.from(checkboxes); // ✅ Mudei para 'let'
		
		// Limpa o container de intervalos
		intervalosContainer.innerHTML = '';
		
		if (turnosSelecionados.length === 0) {
			// Nenhum turno selecionado, mostra mensagem
			const mensagem = document.createElement('p');
			mensagem.style.color = '#666';
			mensagem.style.fontStyle = 'italic';
			mensagem.textContent = 'Selecione um ou mais turnos para configurar os intervalos.';
			intervalosContainer.appendChild(mensagem);
			return;
		}
		
		// ✅ NOVO: Para edição, só permite UM turno
		if (isEditMode && turnosSelecionados.length > 1) {
			// Desmarca todos exceto o primeiro
			for (let i = 1; i < turnosSelecionados.length; i++) {
				turnosSelecionados[i].checked = false;
			}
			
			// ✅ CORREÇÃO: Não reatribuir a variável, apenas refazer a query
			checkboxes = turnosContainer.querySelectorAll('input[type="checkbox"]:checked');
			turnosSelecionados = Array.from(checkboxes);
			
			// Mostra alerta
			alert('No modo de edição, você só pode selecionar um turno por vez.');
		}
		
		// Agora continua com a lógica normal (1 ou mais turnos)
		if (turnosSelecionados.length === 1) {
			// ✅ APENAS UM TURNO: Mostra com legenda específica
			const turno = turnosSelecionados[0];
			const nomeTurno = turno.dataset.nome || turno.value;
			const turnoId = turno.value;
			
			// Cria div para o turno
			const turnoDiv = document.createElement('div');
			turnoDiv.className = 'intervalos-turno';
			
			// Cria título específico do turno
			const titulo = document.createElement('h4');
			titulo.textContent = `Intervalos do período ${nomeTurno.toLowerCase()}`;
			titulo.style.marginBottom = '10px';
			titulo.style.color = '#0066cc';
			turnoDiv.appendChild(titulo);
			
			// Cria linha com os campos
			const linha = document.createElement('div');
			linha.className = 'form-row';
			
			// Campo quantidade
			const divQuantidade = document.createElement('div');
			divQuantidade.className = 'form-group';
			divQuantidade.innerHTML = `
				<label for="intervalos_quantidade_${turnoId}">Qtde. de Intervalos? *</label>
				<input type="number" 
					id="intervalos_quantidade_${turnoId}" 
					class="intervalos-quantidade"
					data-turno-id="${turnoId}"
					placeholder="Número de intervalos" 
					value="${intervalosPorTurno[turnoId]?.quantidade || ''}"
					required>
			`;
			linha.appendChild(divQuantidade);
			
			// Campo posições (agora permite números simples ou com vírgula)
			const divPosicoes = document.createElement('div');
			divPosicoes.className = 'form-group';
			divPosicoes.innerHTML = `
				<label for="intervalos_posicoes_${turnoId}">Posição dos Intervalos *</label>
				<input type="text" 
					id="intervalos_posicoes_${turnoId}" 
					class="intervalos-posicoes"
					data-turno-id="${turnoId}"
					placeholder="Ex.: 3,5 ou 4" 
					maxlength="5"
					value="${intervalosPorTurno[turnoId]?.posicoes || ''}"
					required>
			`;
			linha.appendChild(divPosicoes);
			
			turnoDiv.appendChild(linha);
			intervalosContainer.appendChild(turnoDiv);
			
		} else {
			// ✅ DOIS OU MAIS TURNOS (apenas para INSERÇÃO): Mostra campos separados para cada
			turnosSelecionados.forEach((turno, index) => {
				const nomeTurno = turno.dataset.nome || turno.value;
				const turnoId = turno.value;
				
				// Cria div para cada turno
				const turnoDiv = document.createElement('div');
				turnoDiv.className = 'intervalos-turno';
				turnoDiv.style.marginBottom = '20px';
				turnoDiv.style.padding = '15px';
				turnoDiv.style.border = '1px solid #e0e0e0';
				turnoDiv.style.borderRadius = '5px';
				turnoDiv.style.backgroundColor = '#f9f9f9';
				
				// Cria título do turno
				const titulo = document.createElement('h4');
				titulo.textContent = `Intervalos do período ${nomeTurno.toLowerCase()}`;
				titulo.style.marginBottom = '10px';
				titulo.style.color = '#0066cc';
				turnoDiv.appendChild(titulo);
				
				// Cria linha com os campos
				const linha = document.createElement('div');
				linha.className = 'form-row';
				
				// Campo quantidade
				const divQuantidade = document.createElement('div');
				divQuantidade.className = 'form-group';
				divQuantidade.innerHTML = `
					<label for="intervalos_quantidade_${turnoId}">Qtde. de Intervalos? *</label>
					<input type="number" 
						id="intervalos_quantidade_${turnoId}" 
						class="intervalos-quantidade"
						data-turno-id="${turnoId}"
						placeholder="Número de intervalos" 
						value="${intervalosPorTurno[turnoId]?.quantidade || ''}"
						required>
				`;
				linha.appendChild(divQuantidade);
				
				// Campo posições (permite números simples ou com vírgula)
				const divPosicoes = document.createElement('div');
				divPosicoes.className = 'form-group';
				divPosicoes.innerHTML = `
					<label for="intervalos_posicoes_${turnoId}">Posição dos Intervalos *</label>
					<input type="text" 
						id="intervalos_posicoes_${turnoId}" 
						class="intervalos-posicoes"
						data-turno-id="${turnoId}"
						placeholder="Ex.: 3,5 ou 4" 
						maxlength="5"
						value="${intervalosPorTurno[turnoId]?.posicoes || ''}"
						required>
				`;
				linha.appendChild(divPosicoes);
				
				turnoDiv.appendChild(linha);
				intervalosContainer.appendChild(turnoDiv);
				
				// Adiciona separador visual se não for o último
				if (index < turnosSelecionados.length - 1) {
					const separador = document.createElement('hr');
					separador.style.margin = '15px 0';
					separador.style.border = 'none';
					separador.style.borderTop = '1px dashed #ccc';
					intervalosContainer.appendChild(separador);
				}
			});
		}
		
		// Aplica validação aos novos campos
		aplicarValidacaoIntervalos();
	}
	
	/* ============================================================
		 8) ✅ NOVA FUNÇÃO: Coletar intervalos dos campos dinâmicos
	============================================================ */
	function coletarIntervalosDosCampos() {
		const intervalos = {};
		
		// Coleta dados dos campos visíveis
		const inputsQtd = intervalosContainer.querySelectorAll('.intervalos-quantidade');
		const inputsPos = intervalosContainer.querySelectorAll('.intervalos-posicoes');
		
		inputsQtd.forEach(input => {
			const turnoId = input.dataset.turnoId;
			const quantidade = input.value.trim();
			
			if (!intervalos[turnoId]) {
				intervalos[turnoId] = {};
			}
			intervalos[turnoId].quantidade = quantidade;
		});
		
		inputsPos.forEach(input => {
			const turnoId = input.dataset.turnoId;
			const posicoes = input.value.trim();
			
			if (!intervalos[turnoId]) {
				intervalos[turnoId] = {};
			}
			intervalos[turnoId].posicoes = posicoes;
		});
		
		return intervalos;
	}
	
	/* ============================================================
		 9) Listar Turmas
	============================================================ */
	function fetchTurmas() {
		fetch('/horarios/app/controllers/turma/listTurmaByUser.php')
		.then(r => r.json())
		.then(data => {
			if (data.status === 'success') {
			allTurmas = data.data;
			renderTable(allTurmas);
			} else {
			console.error(data.message);
			}
		})
		.catch(err => console.error(err));
	}
	
	function renderTable(rows) {
		turmaTableBody.innerHTML = '';
		if (!rows || rows.length === 0) {
		noDataMessageTurma.style.display = 'block';
		return;
		}
		noDataMessageTurma.style.display = 'none';
		rows.forEach(row => {
		const tr = document.createElement('tr');
		tr.dataset.id = row.id_turma;
	
		const tdAno = document.createElement('td');
		tdAno.textContent = row.ano;
		tr.appendChild(tdAno);
	
		const tdSerie = document.createElement('td');
		tdSerie.textContent = row.nome_serie;
		tr.appendChild(tdSerie);
	
		const tdTurma = document.createElement('td');
		tdTurma.textContent = row.nome_turma;
		tr.appendChild(tdTurma);
	
		const tdTurno = document.createElement('td');
		tdTurno.textContent = row.nome_turno;
		tr.appendChild(tdTurno);
	
		const tdActions = document.createElement('td');
		// Botão Editar
		const btnEdit = document.createElement('button');
		btnEdit.classList.add('btn-edit');
		btnEdit.dataset.id = row.id_turma;
		btnEdit.innerHTML = `<span class="icon"><i class="fa-solid fa-pen-to-square"></i></span>
							 <span class="text">Editar</span>`;
		tdActions.appendChild(btnEdit);
		// Botão Deletar
		const btnDelete = document.createElement('button');
		btnDelete.classList.add('btn-delete');
		btnDelete.dataset.id = row.id_turma;
		btnDelete.innerHTML = `<span class="icon"><i class="fa-solid fa-trash"></i></span>
								 <span class="text">Deletar</span>`;
		tdActions.appendChild(btnDelete);
		// Botão Imprimir
		const btnPrint = document.createElement('button');
		btnPrint.classList.add('btn-print');
		btnPrint.dataset.id = row.id_turma;
		btnPrint.innerHTML = `<span class="icon"><i class="fa-solid fa-print"></i></span>
								<span class="text">Imprimir</span>`;
		tdActions.appendChild(btnPrint);
	
		tr.appendChild(tdActions);
		turmaTableBody.appendChild(tr);
		});
	}
	
	/* ============================================================
		 10) Modal de Cadastro/Edição de Turma
	============================================================ */
	function openTurmaModal() {
		modalTurma.style.display = 'block';
		modalTurma.classList.remove('fade-out');
		modalTurma.classList.add('fade-in');
		const content = modalTurma.querySelector('.modal-content');
		content.classList.remove('slide-up');
		content.classList.add('slide-down');
		if (!isEditMode) {
		clearForm();
		document.getElementById('modal-turma-title').innerText = 'Adicionar Turma';
		saveTurmaBtn.innerText = 'Salvar';
		}
	}
	
	function closeTurmaModal() {
		const content = modalTurma.querySelector('.modal-content');
		content.classList.remove('slide-down');
		content.classList.add('slide-up');
		modalTurma.classList.remove('fade-in');
		modalTurma.classList.add('fade-out');
		setTimeout(() => {
		modalTurma.style.display = 'none';
		content.classList.remove('slide-up');
		modalTurma.classList.remove('fade-out');
		isEditMode = false;
		currentEditId = null;
		intervalosPorTurno = {}; // Limpa intervalos
		}, 300);
	}
	
	function clearForm() {
		inputTurmaId.value = '';
		selecionarAnoLetivo.selectedIndex = 0;
		selectSerie.value = ''; // Define a opção placeholder
		inputNomeTurma.value = '';
		intervalosContainer.innerHTML = '';
		intervalosPorTurno = {};
		const checkboxes = turnosContainer.querySelectorAll('input[type="checkbox"]');
		checkboxes.forEach(chk => chk.checked = false);
	}
	
	btnAddTurma.addEventListener('click', () => {
		isEditMode = false;
		clearForm();
		loadTurnos();
		openTurmaModal();
	});
	
	closeModalTurmaElements.forEach(el => el.addEventListener('click', closeTurmaModal));
	cancelTurmaBtn.addEventListener('click', () => {
		clearForm();
		closeTurmaModal();
	});
	
	/* ============================================================
		11) Salvar Turma (Insert/Update) - VERSÃO ATUALIZADA
	============================================================ */
	saveTurmaBtn.addEventListener('click', () => {
		const idAnoLetivo = selecionarAnoLetivo.value;
		const idSerie = selectSerie.value;
		const nomeTurma = inputNomeTurma.value.trim();
		
		// Validando seleção da série
		if (!idSerie) {
			alert('Por favor, selecione uma Série.');
			selectSerie.focus();
			return;
		}
		
		if (!idAnoLetivo || !nomeTurma) {
			alert('Preencha todos os campos.');
			return;
		}
		
		// ✅ COLETA OS TURNOS MARCADOS
		const checkboxes = turnosContainer.querySelectorAll('input[type="checkbox"]:checked');
		const checkedTurnos = Array.from(checkboxes).map(chk => chk.value);
		
		// Validação: pelo menos um turno deve ser marcado
		if (checkedTurnos.length === 0) {
			alert('Selecione pelo menos um turno.');
			return;
		}
		
		// ✅ COLETA INTERVALOS DOS CAMPOS DINÂMICOS
		const intervalos = coletarIntervalosDosCampos();
		
		// Validação: todos os turnos selecionados devem ter intervalos configurados
		for (const turnoId of checkedTurnos) {
			const intervalo = intervalos[turnoId];
			if (!intervalo || !intervalo.quantidade || !intervalo.posicoes) {
				// Encontra o nome do turno para mensagem mais informativa
				const checkbox = turnosContainer.querySelector(`input[type="checkbox"][value="${turnoId}"]:checked`);
				const nomeTurno = checkbox?.dataset.nome || 'este turno';
				alert(`Configure os intervalos para o turno ${nomeTurno}.`);
				return;
			}
			
			if (parseInt(intervalo.quantidade) <= 0) {
				const checkbox = turnosContainer.querySelector(`input[type="checkbox"][value="${turnoId}"]:checked`);
				const nomeTurno = checkbox?.dataset.nome || 'este turno';
				alert(`A quantidade de intervalos deve ser maior que zero para o turno ${nomeTurno}.`);
				return;
			}
		}
		
		if (isEditMode) {
			// ✅ MODO EDIÇÃO (mantém como estava para compatibilidade)
			const idTurma = inputTurmaId.value;
			const idTurnoMarcado = checkedTurnos[0]; // Pega o primeiro turno marcado
			const intervalo = intervalos[idTurnoMarcado] || {};
			
			// ✅ VERIFICAÇÃO EXTRA: Garante que só há um turno na edição
			if (checkedTurnos.length > 1) {
				alert('No modo de edição, você só pode selecionar um turno por vez.');
				return;
			}
			
			const data = new URLSearchParams({
				id_turma: idTurma,
				id_ano_letivo: idAnoLetivo,
				id_serie: idSerie,
				id_turno: idTurnoMarcado,
				intervalos_por_dia: intervalo.quantidade || '',
				intervalos_positions: intervalo.posicoes || '',
				nome_turma: nomeTurma
			});
			
			fetch('/horarios/app/controllers/turma/updateTurma.php', {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: data
			})
			.then(r => r.json())
			.then(response => {
				alert(response.message);
				if (response.status === 'success') {
					closeTurmaModal();
					fetchTurmas();
				}
			})
			.catch(err => console.error(err));
			
		} else {
			// ✅ MODO INSERÇÃO (COM MÚLTIPLOS TURNOS E INTERVALOS)
			const formData = new FormData();
			formData.append('id_ano_letivo', idAnoLetivo);
			formData.append('id_serie', idSerie);
			formData.append('nome_turma', nomeTurma);
			
			// Adiciona todos os turnos marcados
			checkedTurnos.forEach(turnoId => {
				formData.append('turnos[]', turnoId);
				
				// Adiciona intervalos para cada turno
				const intervalo = intervalos[turnoId];
				if (intervalo) {
					formData.append(`intervalos_quantidade[${turnoId}]`, intervalo.quantidade);
					formData.append(`intervalos_posicoes[${turnoId}]`, intervalo.posicoes);
				}
			});
			
			fetch('/horarios/app/controllers/turma/insertTurma.php', {
				method: 'POST',
				body: formData
			})
			.then(r => r.json())
			.then(response => {
				if (response.status === 'success') {
					fetchTurmas();
					const resp = confirm('Turma inserida com sucesso! Deseja inserir outra turma?');
					if (resp) {
						clearForm();
						loadTurnos();
					} else {
						closeTurmaModal();
					}
				} else {
					alert(response.message);
				}
			})
			.catch(err => console.error(err));
		}
	});
	
	/* ============================================================
		12) Ações na Tabela (Editar, Deletar, Imprimir) - VERSÃO CORRIGIDA
	============================================================ */
	turmaTableBody.addEventListener('click', e => {
		const btn = e.target.closest('button');
		if (!btn) return;
		const id = btn.dataset.id;

		if (btn.classList.contains('btn-edit')) {
			isEditMode = true;
			currentEditId = id;
			document.getElementById('modal-turma-title').innerText = 'Editar Turma';
			saveTurmaBtn.innerText = 'Alterar';
			
			fetch('/horarios/app/controllers/turma/listTurmaByUser.php')
			.then(r => r.json())
			.then(data => {
				if (data.status === 'success') {
					const turma = data.data.find(item => item.id_turma == currentEditId);
					if (turma) {
						inputTurmaId.value = turma.id_turma;
						selecionarAnoLetivo.value = turma.id_ano_letivo;
						selectSerie.value = turma.id_serie;
						inputNomeTurma.value = turma.nome_turma;
						
						// ✅ LIMPA intervalos anteriores
						intervalosPorTurno = {};
						
						// ✅ ARMAZENA intervalos para o turno da turma sendo editada
						intervalosPorTurno[turma.id_turno] = {
							quantidade: turma.intervalos_por_dia,
							posicoes: turma.intervalos_positions
						};
						
						loadTurnos();
						
						// Aguarda carregar os turnos antes de marcar e atualizar campos
						setTimeout(() => {
							const chkTurnos = turnosContainer.querySelectorAll('input[type="checkbox"]');
							chkTurnos.forEach(chk => {
								if (chk.value == turma.id_turno) {
									chk.checked = true;
									// Dispara evento para atualizar campos (mas apenas para um turno)
									chk.dispatchEvent(new Event('change'));
								}
							});
							openTurmaModal();
						}, 300);
					}
				}
			});
			
		} else if (btn.classList.contains('btn-delete')) {
			currentEditId = id;
			openDeleteTurmaModal();
		} else if (btn.classList.contains('btn-print')) {
			// Impressão individual: carrega dados readonly
			const tr = btn.closest('tr');
			const anoValue = tr.querySelector('td:nth-child(1)').textContent;
			const serieValue = tr.querySelector('td:nth-child(2)').textContent;
			const turmaValue = tr.querySelector('td:nth-child(3)').textContent;
			currentPrintTurmaId = id;
			currentPrintAno = anoValue;
			currentPrintSerie = serieValue;
			currentPrintNomeTurma = turmaValue;
			readOnlyAno.value = anoValue;
			readOnlySerie.value = serieValue;
			readOnlyTurma.value = turmaValue;
			openPrintTurmaModal();
		}
	});
	
	/* ============================================================
		 13) Modal de Exclusão
	============================================================ */
	function openDeleteTurmaModal() {
		modalDeleteTurma.style.display = 'block';
		modalDeleteTurma.classList.remove('fade-out');
		modalDeleteTurma.classList.add('fade-in');
		const content = modalDeleteTurma.querySelector('.modal-content');
		content.classList.remove('slide-up');
		content.classList.add('slide-down');
	}
	
	function closeDeleteTurmaModal() {
		const content = modalDeleteTurma.querySelector('.modal-content');
		content.classList.remove('slide-down');
		content.classList.add('slide-up');
		modalDeleteTurma.classList.remove('fade-in');
		modalDeleteTurma.classList.add('fade-out');
		setTimeout(() => {
		modalDeleteTurma.style.display = 'none';
		content.classList.remove('slide-up');
		modalDeleteTurma.classList.remove('fade-out');
		}, 300);
	}
	
	closeDeleteTurmaModalBtn.addEventListener('click', closeDeleteTurmaModal);
	cancelDeleteTurmaBtn.addEventListener('click', closeDeleteTurmaModal);
	
	confirmDeleteTurmaBtn.addEventListener('click', () => {
		fetch('/horarios/app/controllers/turma/deleteTurma.php', {
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
			if (turmaTableBody.children.length === 0) {
			noDataMessageTurma.style.display = 'block';
			}
		}
		closeDeleteTurmaModal();
		})
		.catch(err => console.error(err));
	});
	
	/* ============================================================
		 14) Modal de Impressão INDIVIDUAL DE TURMA
	============================================================ */
	function openPrintTurmaModal() {
		chkProfTurma.checked = false;
		modalPrintTurma.style.display = 'block';
		modalPrintTurma.classList.remove('fade-out');
		modalPrintTurma.classList.add('fade-in');
		const content = modalPrintTurma.querySelector('.modal-content');
		content.classList.remove('slide-up');
		content.classList.add('slide-down');
	}
	
	function closePrintTurmaModal() {
		const content = modalPrintTurma.querySelector('.modal-content');
		content.classList.remove('slide-down');
		content.classList.add('slide-up');
		modalPrintTurma.classList.remove('fade-in');
		modalPrintTurma.classList.add('fade-out');
		setTimeout(() => {
		modalPrintTurma.style.display = 'none';
		content.classList.remove('slide-up');
		modalPrintTurma.classList.remove('fade-out');
		}, 300);
	}
	
	btnCancelarPrintTurma.addEventListener('click', closePrintTurmaModal);
	closePrintTurmaBtn.addEventListener('click', closePrintTurmaModal);
	
	btnImprimirTurma.addEventListener('click', () => {
		let url = '/horarios/app/views/turma.php?id_turma=' + currentPrintTurmaId;
		if (chkProfTurma.checked) {
		url += '&prof=1';
		}
		window.open(url, '_blank');
		closePrintTurmaModal();
	});
	
	/* ============================================================
		 15) Modal de Impressão GERAL DE TURMA
	============================================================ */
	btnImprimirGeral.addEventListener('click', () => {
		// Abre modal geral (este modal NÃO possui campos de ano/série/turma)
		chkProfTurmaGeral.checked = false;
		openPrintTurmaGeralModal();
	});
	
	function openPrintTurmaGeralModal() {
		modalPrintTurmaGeral.style.display = 'block';
		modalPrintTurmaGeral.classList.remove('fade-out');
		modalPrintTurmaGeral.classList.add('fade-in');
		const content = modalPrintTurmaGeral.querySelector('.modal-content');
		content.classList.remove('slide-up');
		content.classList.add('slide-down');
	}
	
	function closePrintTurmaGeralModal() {
		const content = modalPrintTurmaGeral.querySelector('.modal-content');
		content.classList.remove('slide-down');
		content.classList.add('slide-up');
		modalPrintTurmaGeral.classList.remove('fade-in');
		modalPrintTurmaGeral.classList.add('fade-out');
		setTimeout(() => {
		modalPrintTurmaGeral.style.display = 'none';
		content.classList.remove('slide-up');
		modalPrintTurmaGeral.classList.remove('fade-out');
		}, 300);
	}
	
	btnCancelarTurmaGeral.addEventListener('click', closePrintTurmaGeralModal);
	closePrintTurmaGeralBtn.addEventListener('click', closePrintTurmaGeralModal);
	
	btnImprimirTurmaGeralConfirm.addEventListener('click', () => {
		let url = '/horarios/app/views/turma-geral.php';
		if (chkProfTurmaGeral.checked) {
		url += '?prof=1';
		}
		window.open(url, '_blank');
		closePrintTurmaGeralModal();
	});
	
	/* ============================================================
		 16) PESQUISA
	============================================================ */
	document.getElementById('search-input').addEventListener('input', function() {
		const searchValue = this.value.toLowerCase();
		const rows = turmaTableBody.querySelectorAll('tr');
		let count = 0;
		rows.forEach(tr => {
		const ano = tr.querySelector('td:nth-child(1)').textContent.toLowerCase();
		const serie = tr.querySelector('td:nth-child(2)').textContent.toLowerCase();
		const nome = tr.querySelector('td:nth-child(3)').textContent.toLowerCase();
		const turno = tr.querySelector('td:nth-child(4)').textContent.toLowerCase();
		if (ano.includes(searchValue) || serie.includes(searchValue) ||
			nome.includes(searchValue) || turno.includes(searchValue)) {
			tr.style.display = '';
			count++;
		} else {
			tr.style.display = 'none';
		}
		});
		noDataMessageTurma.style.display = (count === 0) ? 'block' : 'none';
	});
	
	/* ============================================================
		 17) Inicialização
	============================================================ */
	function init() {
		loadAnoLetivo();
		loadSeries();
		fetchTurmas();
	}
	
	init();
});