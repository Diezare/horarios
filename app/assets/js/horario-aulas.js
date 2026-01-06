// app/assets/js/horario-aulas.js antigo relatorio-horarios.js
document.addEventListener('DOMContentLoaded', function() {
	/* ======================================
		 1) Referências gerais (ELEMENTOS)
	====================================== */
	const selectAnoLetivo = document.getElementById('selectAnoLetivo');
	const selectNivelEnsino = document.getElementById('selectNivelEnsino');
	const selectTurno = document.getElementById('selectTurno');
	const btnImprimir = document.getElementById('btnImprimir');
	const btnUnirHorarios = document.getElementById('btn-unir-horarios');

	const tbodyRelatorio = document.getElementById('tbodyRelatorioHorarios');
	const noDataMessage = document.getElementById('noDataMessage');

	// Modal de Impressão
	const modalImpressao = document.getElementById('modalImpressao');
	const closeModalImpressaoBtn = document.getElementById('closeModalImpressao');
	const btnImprimirRetrato = document.getElementById('btnImprimirRetrato');
	const btnImprimirPaisagem = document.getElementById('btnImprimirPaisagem');
	const btnCancelarImpressao = document.getElementById('btnCancelarImpressao');
	
	// Modal de Unir Horários
	const modalUnirHorarios = document.getElementById('modalUnirHorarios');
	const closeModalUnirHorariosBtn = document.getElementById('closeModalUnirHorarios');
	const btnUnirRetrato = document.getElementById('btnUnirRetrato');
	const btnUnirPaisagem = document.getElementById('btnUnirPaisagem');
	const btnCancelarUnir = document.getElementById('btnCancelarUnir');
 
	/* ======================================
		 2) VARIÁVEIS DE ESTADO / FILTRO
	====================================== */
	let idAnoSelecionado = null;
	let idNivelEnsinoSelecionado = null;
	let idTurnoSelecionado = null;

	// Se o usuário clicar no "Imprimir" de uma linha da tabela, guardamos o id_turma aqui:
	let idTurmaSelecionadaRel = null;

	// Variável para armazenar os dados das turmas (usada na ordenação)
	let turmasData = [];

	/* ======================================
		 3) Funções de Carregamento do Filtro
	====================================== */
	async function loadAnosLetivos() {
		try {
			let resp = await fetch('/horarios/app/controllers/ano-letivo/listAnoLetivo.php')
				.then(r => r.json());
			if (resp.status === 'success') {
				resp.data.forEach(ano => {
					const opt = document.createElement('option');
					opt.value = ano.id_ano_letivo;
					opt.textContent = ano.ano;
					selectAnoLetivo.appendChild(opt);
				});
			}
		} catch (err) {
			console.error(err);
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
			let resp = await fetch('/horarios/app/controllers/turno/listTurno.php')
				.then(r => r.json());
			if (resp.status === 'success' && resp.data.length > 0) {
				resp.data.forEach(turno => {
					const opt = document.createElement('option');
					opt.value = turno.id_turno;
					opt.textContent = turno.nome_turno;
					selectTurno.appendChild(opt);
				});
			}
			selectTurno.disabled = false;
		} catch (err) {
			console.error(err);
		}
	}

	/* ======================================
		 4) Carregar e Renderizar TABELA
	====================================== */
	async function loadTurmasFiltradas() {
		if (!idAnoSelecionado) {
			// sem ano letivo, não carrega nada => zera a tabela
			renderTabelaTurmas([]);
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

		const url = `/horarios/app/controllers/turma/listTurmaRelatorio.php?${params.toString()}`;
		try {
			let resp = await fetch(url).then(r => r.json());
			if (resp.status === 'success') {
				turmasData = resp.data; // Armazena os dados para uso na ordenação
				renderTabelaTurmas(resp.data);
			} else {
				turmasData = [];
				renderTabelaTurmas([]);
			}
		} catch (err) {
			console.error(err);
			turmasData = [];
			renderTabelaTurmas([]);
		}
	}

	function renderTabelaTurmas(rows) {
		tbodyRelatorio.innerHTML = '';
		if (!rows || rows.length === 0) {
			noDataMessage.style.display = 'block';
			return;
		}
		noDataMessage.style.display = 'none';

		rows.forEach(t => {
			const tr = document.createElement('tr');

			// Ano
			const tdAno = document.createElement('td');
			tdAno.textContent = t.ano;
			tr.appendChild(tdAno);

			// Série
			const tdSerie = document.createElement('td');
			tdSerie.textContent = t.nome_serie;
			tr.appendChild(tdSerie);

			// Turma
			const tdTurma = document.createElement('td');
			tdTurma.textContent = t.nome_turma;
			tr.appendChild(tdTurma);

			// Turno
			const tdTurno = document.createElement('td');
			tdTurno.textContent = t.nome_turno;
			tr.appendChild(tdTurno);

			// Ações: Botão "Imprimir"
			const tdAcoes = document.createElement('td');
			const btnPrint = document.createElement('button');
			btnPrint.classList.add('btn-print');
			btnPrint.dataset.id = t.id_turma;
			btnPrint.innerHTML = `
				<span class="icon"><i class="fa-solid fa-print"></i></span>
				<span class="text">Imprimir</span>
			`;
			btnPrint.addEventListener('click', () => {
				idTurmaSelecionadaRel = t.id_turma;
				openModalImpressao();
			});
			tdAcoes.appendChild(btnPrint);
			tr.appendChild(tdAcoes);

			tbodyRelatorio.appendChild(tr);
		});
	}

	/* ======================================
		 5) MODAL DE IMPRESSÃO
	====================================== */
	function openModalImpressao() {
		modalImpressao.style.display = 'block';
		modalImpressao.classList.remove('fade-out');
		modalImpressao.classList.add('fade-in');

		const content = modalImpressao.querySelector('.modal-content');
		content.classList.remove('slide-up');
		content.classList.add('slide-down');
	}

	function closeModalImpressao() {
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

	closeModalImpressaoBtn.addEventListener('click', closeModalImpressao);
	btnCancelarImpressao.addEventListener('click', closeModalImpressao);

	// Botões "Retrato" e "Paisagem"
	btnImprimirRetrato.addEventListener('click', () => {
		imprimirAcaoFinal('retrato');
	});
	btnImprimirPaisagem.addEventListener('click', () => {
		imprimirAcaoFinal('paisagem');
	});

	function imprimirAcaoFinal(modo) {
		// Monta a URL final
		let baseUrl = (modo === 'paisagem')
			? '/horarios/app/views/horarios-paisagem.php'
			: '/horarios/app/views/horarios-retrato.php';

		const params = new URLSearchParams();
		if (idAnoSelecionado) {
			params.set('id_ano_letivo', idAnoSelecionado);
		}
		if (idNivelEnsinoSelecionado) {
			params.set('id_nivel_ensino', idNivelEnsinoSelecionado);
		}
		if (idTurnoSelecionado) {
			params.set('id_turno', idTurnoSelecionado);
		}
		// se tivermos uma turma específica selecionada
		if (idTurmaSelecionadaRel) {
			params.set('id_turma', idTurmaSelecionadaRel);
		}

		const finalUrl = baseUrl + '?' + params.toString();
		window.open(finalUrl, '_blank');

		// Fecha modal
		closeModalImpressao();
	}

	/* ======================================
		 5.1) MODAL DE UNIR HORÁRIOS
	====================================== */
	function openModalUnirHorarios() {
		modalUnirHorarios.style.display = 'block';
		modalUnirHorarios.classList.remove('fade-out');
		modalUnirHorarios.classList.add('fade-in');

		const content = modalUnirHorarios.querySelector('.modal-content');
		content.classList.remove('slide-up');
		content.classList.add('slide-down');
	}

	function closeModalUnirHorarios() {
		const content = modalUnirHorarios.querySelector('.modal-content');
		content.classList.remove('slide-down');
		content.classList.add('slide-up');
		modalUnirHorarios.classList.remove('fade-in');
		modalUnirHorarios.classList.add('fade-out');

		setTimeout(() => {
			modalUnirHorarios.style.display = 'none';
			content.classList.remove('slide-up');
			modalUnirHorarios.classList.remove('fade-out');
		}, 300);
	}

	// Adiciona event listeners para o modal de Unir Horários
	closeModalUnirHorariosBtn.addEventListener('click', closeModalUnirHorarios);
	btnCancelarUnir.addEventListener('click', closeModalUnirHorarios);

	// Funções para os botões do modal Unir Horários
	btnUnirRetrato.addEventListener('click', () => {
		unirHorariosAcaoFinal('retrato');
	});
	
	btnUnirPaisagem.addEventListener('click', () => {
		unirHorariosAcaoFinal('paisagem');
	});

	function unirHorariosAcaoFinal(modo) {
		// Verifica se nível e turno foram selecionados
		if (!idNivelEnsinoSelecionado && !idTurnoSelecionado) {
			alert('O nível de ensino e o turno não podem estar vazios.');
			return;
		} else if (!idNivelEnsinoSelecionado) {
			alert('Selecione um nível de ensino.');
			return;
		} else if (!idTurnoSelecionado) {
			alert('Selecione um turno.');
			return;
		}

		// Monta a URL final
		let baseUrl = (modo === 'paisagem')
			? '/horarios/app/views/horarios-unidos-paisagem.php'
			: '/horarios/app/views/horarios-unidos.php';

		const params = new URLSearchParams();
		params.set('id_ano_letivo', idAnoSelecionado);
		params.set('id_nivel_ensino', idNivelEnsinoSelecionado);
		params.set('id_turno', idTurnoSelecionado);

		const finalUrl = baseUrl + '?' + params.toString();
		window.open(finalUrl, '_blank');

		// Fecha modal
		closeModalUnirHorarios();
	}

	/* ======================================
		 6) EVENTOS DO FILTRO
	====================================== */
	selectAnoLetivo.addEventListener('change', () => {
		idAnoSelecionado = selectAnoLetivo.value || null;

		if (!idAnoSelecionado) {
			// Desabilita nível, turno e botões
			selectNivelEnsino.disabled = true;
			selectTurno.disabled = true;
			btnImprimir.disabled = true;
			btnUnirHorarios.disabled = true;
			btnUnirHorarios.style.backgroundColor = '#D7D7D9';

			tbodyRelatorio.innerHTML = '';
			noDataMessage.style.display = 'block';
			return;
		}

		// Caso tenha Ano, habilita selects e botões
		selectNivelEnsino.disabled = false;
		selectTurno.disabled = false;
		btnImprimir.disabled = false;
		btnUnirHorarios.disabled = false;
		btnUnirHorarios.style.backgroundColor = ''; // remove cor cinza

		selectNivelEnsino.value = '';
		idNivelEnsinoSelecionado = null;

		selectTurno.value = '';
		idTurnoSelecionado = null;

		// Carregar níveis e turnos
		loadNiveisPorAno(idAnoSelecionado);
		loadTurnos();

		// Recarrega tabela
		loadTurmasFiltradas();
	});

	selectNivelEnsino.addEventListener('change', () => {
		idNivelEnsinoSelecionado = selectNivelEnsino.value || null;
		loadTurmasFiltradas();
	});

	selectTurno.addEventListener('change', () => {
		idTurnoSelecionado = selectTurno.value || null;
		loadTurmasFiltradas();
	});

	// Botão "Imprimir" no TOPO (filtro geral)
	btnImprimir.addEventListener('click', () => {
		// Se clicou aqui, iremos imprimir "geral" (sem id_turma específico)
		idTurmaSelecionadaRel = null;
		openModalImpressao();
	});

	/* ======================================
		 7) BOTÃO "UNIR HORÁRIOS"
	====================================== */
	btnUnirHorarios.addEventListener('click', () => {
		// Abre o modal de escolha (retrato/paisagem)
		openModalUnirHorarios();
	});

	/* ======================================
		 8) INICIALIZAÇÃO AO CARREGAR
	====================================== */
	// 1) Carrega os anos letivos
	loadAnosLetivos();

	// 2) Renders a tabela vazia => "Nenhuma turma encontrada."
	renderTabelaTurmas([]);

	// Inicialmente, deixa botões desabilitados + cor cinza
	btnImprimir.disabled = true;
	btnUnirHorarios.disabled = true;
	btnUnirHorarios.style.backgroundColor = '#D7D7D9';

	/* ======================================
		 Função de Ordenação (Sort)
	====================================== */
	function sortTableData(key, order) {
		if (!turmasData || turmasData.length === 0) return;
		turmasData.sort(function(a, b) {
			let valA = a[key];
			let valB = b[key];
			// Tenta converter para número, se possível
			let numA = parseFloat(valA);
			let numB = parseFloat(valB);
			if (!isNaN(numA) && !isNaN(numB)) {
				valA = numA;
				valB = numB;
			} else {
				// Caso não sejam números, compara como strings (case insensitive)
				valA = valA.toString().toLowerCase();
				valB = valB.toString().toLowerCase();
			}
			if (valA < valB) return order === 'asc' ? -1 : 1;
			if (valA > valB) return order === 'asc' ? 1 : -1;
			return 0;
		});
		renderTabelaTurmas(turmasData);
	}

	// Adiciona eventos de clique nos ícones de sort conforme o HTML
	document.getElementById('sort-ano-asc').addEventListener('click', function() {
		sortTableData('ano', 'asc');
	});
	document.getElementById('sort-ano-desc').addEventListener('click', function() {
		sortTableData('ano', 'desc');
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
});