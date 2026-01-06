// app/assets/js/historico-horarios.js
document.addEventListener('DOMContentLoaded', function() {
	// ========================================================
	// 1) REFERÊNCIAS AOS ELEMENTOS DA PÁGINA
	// ========================================================
	const selectAnoLetivo = document.getElementById('selectAnoLetivo');
	const selectNivelEnsino	= document.getElementById('selectNivelEnsino');
	const selectTurma = document.getElementById('selectTurma');
	const selectTurno = document.getElementById('selectTurno');
	const tbodyHistorico = document.getElementById('tbodyRelatorioHorarios');
	const noDataMessage = document.getElementById('noDataMessage');

	// Modal de impressão
	const modalImpressao = document.getElementById('modalImpressao');
	const closeModalBtn	= document.getElementById('closeModalImpressao');
	const btnImprimirRetrato = document.getElementById('btnImprimirRetrato');
	const btnImprimirPaisagem = document.getElementById('btnImprimirPaisagem');
	const btnCancelarImpressao = document.getElementById('btnCancelarImpressao');

	// Paginação
	const paginationContainer = document.getElementById('paginationContainer');
	const prevPageBtn = document.getElementById('prevPageBtn');
	const nextPageBtn = document.getElementById('nextPageBtn');
	const paginationStatus = document.getElementById('paginationStatus');

	// Variáveis de estado (filtros)
	let idAnoSelecionado = null;
	let idNivelEnsinoSelecionado = null;
	let idTurmaSelecionada = null;
	let idTurnoSelecionado = null;

	// Para saber qual registro histórico iremos imprimir
	let idTurmaHistorico = null;	// Turma do registro histórico
	let historicoDate = null;	// Data de arquivamento do registro histórico

	// Dados atuais (todas as linhas recebidas do back-end)
	let allRowsData = [];	
	// Paginação
	let currentPage	= 1;	 
	const itemsPerPage = 25;

	// ========================================================
	// 2) FUNÇÕES DE CARREGAMENTO DOS FILTROS
	// ========================================================
	async function loadAnosLetivos() {
		try {
			let response = await fetch('/horarios/app/controllers/ano-letivo/listAnoLetivo.php');
			let data = await response.json();
			if (data.status === 'success') {
				data.data.forEach(ano => {
					const opt = document.createElement('option');
					opt.value = ano.id_ano_letivo;
					opt.textContent = ano.ano;
					selectAnoLetivo.appendChild(opt);
				});
			}
		} catch (error) {
			console.error('Erro ao carregar anos letivos:', error);
		}
	}

	async function loadNiveisPorAno(idAno) {
		selectNivelEnsino.innerHTML = '<option value="">-- Selecione o Nível --</option>';
		try {
			// Agora chama o endpoint filtrado por "usuario_niveis"
			let url = `/horarios/app/controllers/nivel-ensino/listNivelEnsinoByUserAndAno.php?id_ano_letivo=${idAno}`;
			let response = await fetch(url);
			let data = await response.json();
			if (data.status === 'success' && data.data.length > 0) {
				data.data.forEach(nivel => {
					const opt = document.createElement('option');
					opt.value = nivel.id_nivel_ensino;
					opt.textContent = nivel.nome_nivel_ensino;
					selectNivelEnsino.appendChild(opt);
				});
			}
			selectNivelEnsino.disabled = false;
		} catch (error) {
			console.error('Erro ao carregar níveis de ensino:', error);
		}
	}
	

	async function loadTurnos() {
		selectTurno.innerHTML = '<option value="">-- Selecione o Turno --</option>';
		try {
			let response = await fetch('/horarios/app/controllers/turno/listTurno.php');
			let data = await response.json();
			if (data.status === 'success' && data.data.length > 0) {
				data.data.forEach(turno => {
					const opt = document.createElement('option');
					opt.value = turno.id_turno;
					opt.textContent = turno.nome_turno;
					selectTurno.appendChild(opt);
				});
			}
			selectTurno.disabled = false;
		} catch (error) {
			console.error('Erro ao carregar turnos:', error);
		}
	}

	async function loadTurmas() {
		// Lista turmas filtradas por Ano e Nível
		selectTurma.innerHTML = '<option value="">-- Selecione a Turma --</option>';
		if (!idAnoSelecionado || !idNivelEnsinoSelecionado) {
			selectTurma.disabled = true;
			return;
		}
		try {
			let url = `/horarios/app/controllers/turma/listTurmaRelatorio.php?id_ano_letivo=${idAnoSelecionado}&id_nivel_ensino=${idNivelEnsinoSelecionado}`;
			let response = await fetch(url);
			let data = await response.json();
			if (data.status === 'success' && data.data.length > 0) {
				data.data.forEach(turma => {
					const opt = document.createElement('option');
					opt.value = turma.id_turma;
					opt.textContent = `${turma.nome_serie} ${turma.nome_turma} - ${turma.nome_turno}`;
					selectTurma.appendChild(opt);
				});
			}
			selectTurma.disabled = false;
		} catch (error) {
			console.error('Erro ao carregar turmas:', error);
		}
	}

	// ========================================================
	// 3) CARREGAR E RENDERIZAR A TABELA DE HISTÓRICO
	// ========================================================
	async function loadHistoricoTurmas() {
		// Filtro mínimo: Ano Letivo selecionado
		if (!idAnoSelecionado) {
			renderTabelaHistorico([]);
			return;
		}

		const params = new URLSearchParams();
		params.set('id_ano_letivo', idAnoSelecionado);

		if (idNivelEnsinoSelecionado) {
			params.set('id_nivel_ensino', idNivelEnsinoSelecionado);
		}
		if (idTurnoSelecionado) {
			params.set('id_turno', idTurnoSelecionado);
		}
		if (idTurmaSelecionada) {
			params.set('id_turma', idTurmaSelecionada);
		}

		// Endpoint para listar históricos
		const url = `/horarios/app/controllers/horarios/listHistoricoHorarios.php?${params.toString()}`;
		try {
			let response = await fetch(url);
			let data = await response.json();
			if (data.status === 'success') {
				// Salva todos os resultados e desenha
				allRowsData = data.data;
				currentPage	= 1;
				renderPage(); // Renderiza a página 1
			} else {
				renderTabelaHistorico([]);
			}
		} catch (error) {
			console.error('Erro ao carregar histórico:', error);
			renderTabelaHistorico([]);
		}
	}

	// Renderiza SOMENTE as linhas de uma dada página
	function renderPage() {
		if (!allRowsData || allRowsData.length === 0) {
			renderTabelaHistorico([]);
			return;
		}
		const startIndex = (currentPage - 1) * itemsPerPage;
		const endIndex	 = startIndex + itemsPerPage;
		const pageRows	 = allRowsData.slice(startIndex, endIndex);

		renderTabelaHistorico(pageRows);

		// Atualiza paginação
		const totalItems = allRowsData.length;
		const totalPages = Math.ceil(totalItems / itemsPerPage);
		paginationStatus.textContent = `Página ${currentPage} de ${totalPages}`;
		prevPageBtn.disabled = (currentPage <= 1);
		nextPageBtn.disabled = (currentPage >= totalPages);
		paginationContainer.style.display = totalItems > 0 ? 'block' : 'none';
	}

	// Exibe no DOM as linhas passadas
	function renderTabelaHistorico(rows) {
		tbodyHistorico.innerHTML = '';
		if (!rows || rows.length === 0) {
			noDataMessage.style.display = 'block';
			paginationContainer.style.display = 'none';
			return;
		}
		noDataMessage.style.display = 'none';

		rows.forEach(record => {
			const tr = document.createElement('tr');

			// Coluna: Ano
			const tdAno = document.createElement('td');
			tdAno.textContent = record.ano;
			tr.appendChild(tdAno);

			// Coluna: Data Arquivamento (Alteração)
			const tdData = document.createElement('td');
			tdData.textContent = record.data_arquivamento;
			tr.appendChild(tdData);

			// Coluna: Série
			const tdSerie = document.createElement('td');
			tdSerie.textContent = record.nome_serie;
			tr.appendChild(tdSerie);

			// Coluna: Turma
			const tdTurma = document.createElement('td');
			tdTurma.textContent = record.nome_turma;
			tr.appendChild(tdTurma);

			// Coluna: Turno
			const tdTurno = document.createElement('td');
			tdTurno.textContent = record.nome_turno;
			tr.appendChild(tdTurno);

			// Botão de imprimir
			const tdAcoes = document.createElement('td');
			const btnPrint = document.createElement('button');
			btnPrint.classList.add('btn-print');
			btnPrint.textContent = 'Imprimir';
			btnPrint.addEventListener('click', () => {
				idTurmaHistorico = record.id_turma;
				historicoDate		= record.data_arquivamento;
				openModal();
			});
			tdAcoes.appendChild(btnPrint);
			tr.appendChild(tdAcoes);

			tbodyHistorico.appendChild(tr);
		});
	}

	// Paginação: botões
	prevPageBtn.addEventListener('click', () => {
		if (currentPage > 1) {
			currentPage--;
			renderPage();
		}
	});
	nextPageBtn.addEventListener('click', () => {
		const totalItems = allRowsData.length;
		const totalPages = Math.ceil(totalItems / itemsPerPage);
		if (currentPage < totalPages) {
			currentPage++;
			renderPage();
		}
	});

	// ========================================================
	// 4) FUNÇÕES DO MODAL
	// ========================================================
	function openModal() {
		modalImpressao.style.display = 'block';
		modalImpressao.classList.remove('fade-out');
		modalImpressao.classList.add('fade-in');

		const content = modalImpressao.querySelector('.modal-content');
		content.classList.remove('slide-up');
		content.classList.add('slide-down');
	}

	function closeModal() {
		const content = modalImpressao.querySelector('.modal-content');
		content.classList.remove('slide-down');
		content.classList.add('slide-up');
		modalImpressao.classList.remove('fade-in');
		modalImpressao.classList.add('fade-out');

		setTimeout(() => {
			modalImpressao.style.display = 'none';
			content.classList.remove('slide-up');
			modalImpressao.classList.remove('fade-out');
		}, 300);
	}

	function clearForm() {
		// Se houver campos no modal, limpe-os
	}

	closeModalBtn.addEventListener('click', closeModal);
	btnCancelarImpressao.addEventListener('click', () => {
		clearForm();
		closeModal();
	});

	// ========================================================
	// 5) BOTÕES DE IMPRESSÃO (RETRATO / PAISAGEM)
	// ========================================================
	btnImprimirRetrato.addEventListener('click', () => {
		const params = new URLSearchParams();
		if (idAnoSelecionado)				 params.set('id_ano_letivo', idAnoSelecionado);
		if (idNivelEnsinoSelecionado) params.set('id_nivel_ensino', idNivelEnsinoSelecionado);
		if (idTurnoSelecionado)			 params.set('id_turno', idTurnoSelecionado);
		if (idTurmaHistorico)				 params.set('id_turma', idTurmaHistorico);
		params.set('data_arquivamento', historicoDate);

		// Ajuste o caminho caso mude o nome do arquivo
		const finalUrl = '/horarios/app/views/historico-horarios-retrato.php?' + params.toString();
		window.open(finalUrl, '_blank');
		closeModal();
	});

	btnImprimirPaisagem.addEventListener('click', () => {
		const params = new URLSearchParams();
		if (idAnoSelecionado)				 params.set('id_ano_letivo', idAnoSelecionado);
		if (idNivelEnsinoSelecionado) params.set('id_nivel_ensino', idNivelEnsinoSelecionado);
		if (idTurnoSelecionado)			 params.set('id_turno', idTurnoSelecionado);
		if (idTurmaHistorico)				 params.set('id_turma', idTurmaHistorico);
		params.set('data_arquivamento', historicoDate);

		const finalUrl = '/horarios/app/views/historico-horarios-paisagem.php?' + params.toString();
		window.open(finalUrl, '_blank');
		closeModal();
	});
 
	// ========================================================
	// 6) EVENTOS DOS FILTROS
	// ========================================================
	selectAnoLetivo.addEventListener('change', () => {
		idAnoSelecionado = selectAnoLetivo.value || null;
		// Ao mudar o ano, resetamos os demais filtros
		selectNivelEnsino.value		 = "";
		selectTurma.value					 = "";
		selectTurno.value					 = "";
		idNivelEnsinoSelecionado		= null;
		idTurmaSelecionada					= null;
		idTurnoSelecionado					= null;

		if (!idAnoSelecionado) {
			selectNivelEnsino.disabled = true;
			selectTurno.disabled			 = true;
			selectTurma.disabled			 = true;
			tbodyHistorico.innerHTML	 = '';
			noDataMessage.style.display= 'block';
			paginationContainer.style.display = 'none';
			return;
		}
		loadNiveisPorAno(idAnoSelecionado);
		loadTurnos();
		loadTurmas();
		loadHistoricoTurmas();
	});

	selectNivelEnsino.addEventListener('change', () => {
		idNivelEnsinoSelecionado = selectNivelEnsino.value || null;
		loadTurmas();
		loadHistoricoTurmas();
	});

	selectTurno.addEventListener('change', () => {
		idTurnoSelecionado = selectTurno.value || null;
		loadHistoricoTurmas();
	});

	selectTurma.addEventListener('change', () => {
		idTurmaSelecionada = selectTurma.value || null;
		loadHistoricoTurmas();
	});

	// ========================================================
	// 7) FUNÇÃO DE ORDENAÇÃO (SORT)
	// ========================================================
	function sortTableData(key, order) {
		if (!allRowsData || allRowsData.length === 0) return;
		allRowsData.sort(function(a, b) {
			let valA = a[key];
			let valB = b[key];
			// Tenta converter para número, se possível
			let numA = parseFloat(valA);
			let numB = parseFloat(valB);
			if (!isNaN(numA) && !isNaN(numB)) {
				valA = numA;
				valB = numB;
			} else {
				valA = valA.toString().toLowerCase();
				valB = valB.toString().toLowerCase();
			}
			if (valA < valB) return order === 'asc' ? -1 : 1;
			if (valA > valB) return order === 'asc' ? 1 : -1;
			return 0;
		});
		renderPage();
	}

	// Adiciona eventos de clique nos ícones de sort conforme o HTML
	document.getElementById('sort-ano-asc').addEventListener('click', function() {
		sortTableData('ano', 'asc');
	});
	document.getElementById('sort-ano-desc').addEventListener('click', function() {
		sortTableData('ano', 'desc');
	});
	document.getElementById('sort-alteracao-asc').addEventListener('click', function() {
		sortTableData('data_arquivamento', 'asc');
	});
	document.getElementById('sort-alteracao-desc').addEventListener('click', function() {
		sortTableData('data_arquivamento', 'desc');
	});
	document.getElementById('sort-serie-asc').addEventListener('click', function() {
		sortTableData('nome_serie', 'asc');
	});
	document.getElementById('sort-serie-desc').addEventListener('click', function() {
		sortTableData('nome_serie', 'desc');
	});
	document.getElementById('sort-turma-asc').addEventListener('click', function() {
		sortTableData('nome_turma', 'asc');
	});
	document.getElementById('sort-turma-desc').addEventListener('click', function() {
		sortTableData('nome_turma', 'desc');
	});
	document.getElementById('sort-turno-asc').addEventListener('click', function() {
		sortTableData('nome_turno', 'asc');
	});
	document.getElementById('sort-turno-desc').addEventListener('click', function() {
		sortTableData('nome_turno', 'desc');
	});

	// ========================================================
	// 8) INICIALIZAÇÃO
	// ========================================================
	loadAnosLetivos();
	allRowsData = [];
	renderTabelaHistorico([]); // Tabela vazia no início
});
