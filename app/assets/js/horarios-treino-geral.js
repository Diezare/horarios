// app/assets/js/horarios-treino-geral.js
document.addEventListener('DOMContentLoaded', function() {
	/* ======================================
	   1) Referências do DOM
	====================================== */
	const selectAnoLetivo = document.getElementById('selectAnoLetivo');
	const selectTurno = document.getElementById('selectTurno');
	const selectModalidade = document.getElementById('selectModalidade');
	const selectCategoria = document.getElementById('selectCategoria');
	const selectProfessor = document.getElementById('selectProfessor');
	const btnImprimir = document.getElementById('btnImprimir');

	const tbodyRelatorio = document.getElementById('tbodyRelatorioHorarios');
	const noDataMessage = document.getElementById('noDataMessage');

	// Modal de Impressão
	const modalImpressao = document.getElementById('modalImpressao');
	const closeModalImpressaoBtn = document.getElementById('closeModalImpressao');
	const btnImprimirConfirmar = document.getElementById('btnImprimirConfirmar');
	const btnCancelarImpressao = document.getElementById('btnCancelarImpressao');

	// Estados
	let idAnoSelecionado = null;
	let idTurnoSelecionado = null;
	let idModalidadeSelecionada = null;
	let idCategoriaSelecionada = null;
	let idProfessorSelecionado = null;

	// Armazena dados para sort e filtros
	let treinosData = [];
	let treinosFiltrados = [];
	
	/* ======================================
	   2) Carregamento de Filtros
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
			console.error('Erro ao carregar anos letivos:', err);
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
			console.error('Erro ao carregar turnos:', err);
		}
	}

	async function loadModalidades() {
		selectModalidade.innerHTML = '<option value="">Todos</option>';
		if (!idAnoSelecionado) {
			selectModalidade.disabled = true;
			return;
		}
		
		try {
			// Aqui podemos filtrar modalidades que realmente têm dados para o ano letivo
			const params = new URLSearchParams();
			params.set('id_ano_letivo', idAnoSelecionado);
			
			if (idTurnoSelecionado) {
				params.set('id_turno', idTurnoSelecionado);
			}
			
			let resp = await fetch('/horarios/app/controllers/modalidade/listModalidade.php')
				.then(r => r.json());
			if (resp.status === 'success' && resp.data.length > 0) {
				resp.data.forEach(modalidade => {
					const opt = document.createElement('option');
					opt.value = modalidade.id_modalidade;
					opt.textContent = modalidade.nome_modalidade;
					selectModalidade.appendChild(opt);
				});
			}
			selectModalidade.disabled = false;
		} catch (err) {
			console.error('Erro ao carregar modalidades:', err);
		}
	}

	async function loadCategorias() {
		// Limpa o select de categorias
		selectCategoria.innerHTML = '<option value="">Todos</option>';
		
		// Importante: desabilita o select se não houver modalidade selecionada
		if (!idModalidadeSelecionada) {
			selectCategoria.disabled = true;
			return;
		}
		
		try {
			// Filtra categorias pela modalidade selecionada
			const url = `/horarios/app/controllers/categoria/listCategoria.php?id_modalidade=${idModalidadeSelecionada}`;
			
			let resp = await fetch(url).then(r => r.json());
			if (resp.status === 'success' && resp.data.length > 0) {
				resp.data.forEach(categoria => {
					const opt = document.createElement('option');
					opt.value = categoria.id_categoria;
					opt.textContent = categoria.nome_categoria;
					selectCategoria.appendChild(opt);
				});
				selectCategoria.disabled = false;
			} else {
				// Nenhuma categoria encontrada para esta modalidade
				selectCategoria.disabled = true;
			}
		} catch (err) {
			console.error('Erro ao carregar categorias:', err);
			selectCategoria.disabled = true;
		}
	}

	// Função alternativa para carregar categorias com dados existentes
	async function loadCategoriasComDados() {
		// Limpa o select de categorias
		selectCategoria.innerHTML = '<option value="">Todos</option>';
		
		// Desabilita o select se não tiver modalidade selecionada
		if (!idModalidadeSelecionada) {
			selectCategoria.disabled = true;
			return;
		}
		
		try {
			// Montamos um filtro que pega apenas categorias que existem em horários
			const params = new URLSearchParams();
			params.set('id_ano_letivo', idAnoSelecionado);
			params.set('id_modalidade', idModalidadeSelecionada);
			
			if (idTurnoSelecionado) {
				params.set('id_turno', idTurnoSelecionado);
			}
			
			// Busca categorias que estão presentes nos horários de treino
			const url = `/horarios/app/controllers/horarios-treino/listCategoriasPorModalidade.php?${params.toString()}`;
			
			let resp = await fetch(url).then(r => r.json());
			if (resp.status === 'success' && resp.data.length > 0) {
				// Map para extrair apenas categorias únicas
				const categorias = {};
				
				resp.data.forEach(item => {
					if (!categorias[item.id_categoria]) {
						categorias[item.id_categoria] = {
							id: item.id_categoria,
							nome: item.nome_categoria
						};
					}
				});
				
				// Ordenar as categorias por nome
				const listaCategorias = Object.values(categorias).sort((a, b) => 
					a.nome.localeCompare(b.nome)
				);
				
				// Adicionar cada categoria ao select
				listaCategorias.forEach(categoria => {
					const opt = document.createElement('option');
					opt.value = categoria.id;
					opt.textContent = categoria.nome;
					selectCategoria.appendChild(opt);
				});
				
				selectCategoria.disabled = false;
			} else {
				// Nenhuma categoria encontrada para esta modalidade
				selectCategoria.disabled = true;
			}
		} catch (err) {
			console.error('Erro ao carregar categorias com dados:', err);
			selectCategoria.disabled = true;
		}
	}

	async function loadProfessores() {
		selectProfessor.innerHTML = '<option value="">Todos</option>';
		if (!idAnoSelecionado) {
			selectProfessor.disabled = true;
			return;
		}
		
		try {
			// Montamos a query para pegar apenas os professores que têm horários
			const params = new URLSearchParams();
			params.set('id_ano_letivo', idAnoSelecionado);
			
			// Adicionamos os outros filtros, se disponíveis
			if (idTurnoSelecionado) {
				params.set('id_turno', idTurnoSelecionado);
			}
			
			if (idModalidadeSelecionada) {
				params.set('id_modalidade', idModalidadeSelecionada);
			}
			
			if (idCategoriaSelecionada) {
				params.set('id_categoria', idCategoriaSelecionada);
			}
			
			// URL para buscar só os horários de treino com professores ativos
			const url = `/horarios/app/controllers/horarios-treino/listHorariosTreinoRelatorio.php?${params.toString()}`;
			
			let resp = await fetch(url).then(r => r.json());
			if (resp.status === 'success' && resp.data.length > 0) {
				// Map para extrair apenas os professores, removendo duplicatas
				const professores = {};
				
				resp.data.forEach(item => {
					// Se o professor não foi adicionado, incluímos ele
					if (!professores[item.id_professor]) {
						professores[item.id_professor] = {
							id: item.id_professor,
							nome: item.nome_professor
						};
					}
				});
				
				// Ordenar os professores por nome
				const listaProfessores = Object.values(professores).sort((a, b) => 
					a.nome.localeCompare(b.nome)
				);
				
				// Adicionar cada professor ao select
				listaProfessores.forEach(professor => {
					const opt = document.createElement('option');
					opt.value = professor.id;
					opt.textContent = professor.nome;
					selectProfessor.appendChild(opt);
				});
			}
			selectProfessor.disabled = false;
		} catch (err) {
			console.error('Erro ao carregar professores:', err);
		}
	}

	/* ======================================
	   3) Carrega Tabela de Treinos
	====================================== */
	async function loadTreinosFiltrados() {
		if (!idAnoSelecionado) {
			renderTabelaTreinos([]);
			return;
		}

		// Monta a querystring
		const params = new URLSearchParams();
		params.set('id_ano_letivo', idAnoSelecionado);
		
		if (idTurnoSelecionado) {
			params.set('id_turno', idTurnoSelecionado);
		}
		
		// Adiciona os filtros de modalidade e categoria à query
		if (idModalidadeSelecionada) {
			params.set('id_modalidade', idModalidadeSelecionada);
		}
		
		if (idCategoriaSelecionada) {
			params.set('id_categoria', idCategoriaSelecionada);
		}
		
		if (idProfessorSelecionado) {
			params.set('id_professor', idProfessorSelecionado);
		}

		const url = `/horarios/app/controllers/horarios-treino/listHorariosTreinoRelatorio.php?` + params.toString();

		try {
			let resp = await fetch(url).then(r => r.json());
			if (resp.status === 'success') {
				treinosData = resp.data;
				treinosFiltrados = resp.data;
				renderTabelaTreinos(treinosFiltrados);
			} else {
				treinosData = [];
				treinosFiltrados = [];
				renderTabelaTreinos([]);
			}
		} catch (err) {
			console.error('Erro ao obter treinos:', err);
			treinosData = [];
			treinosFiltrados = [];
			renderTabelaTreinos([]);
		}
	}

	function renderTabelaTreinos(rows) {
		tbodyRelatorio.innerHTML = '';
		if (!rows || rows.length === 0) {
			noDataMessage.style.display = 'block';
			btnImprimir.disabled = true;
			return;
		}
		noDataMessage.style.display = 'none';
		btnImprimir.disabled = false;

		rows.forEach((item) => {
			const tr = document.createElement('tr');

			// Ano Letivo
			const tdAno = document.createElement('td');
			tdAno.textContent = item.ano || '';
			tr.appendChild(tdAno);

			// Modalidades
			const tdModalidade = document.createElement('td');
			tdModalidade.textContent = item.nome_modalidade || '';
			tr.appendChild(tdModalidade);

			// Categorias
			const tdCategoria = document.createElement('td');
			tdCategoria.textContent = item.nome_categoria || '';
			tr.appendChild(tdCategoria);

			// Professor
			const tdProfessor = document.createElement('td');
			tdProfessor.textContent = item.nome_professor || '';
			tr.appendChild(tdProfessor);

			// Ações
			const tdAcoes = document.createElement('td');
			const btnPrint = document.createElement('button');
			btnPrint.classList.add('btn-print');
			// Precisamos do "id_horario_escolinha" para individual
			btnPrint.dataset.id = item.id_horario_escolinha || '';

			btnPrint.innerHTML = `
				<span class="icon"><i class="fa-solid fa-print"></i></span>
				<span class="text">Imprimir</span>
			`;

			// Ao clicar, imprime somente este horário (Individual)
			btnPrint.addEventListener('click', () => {
				const idHorario = item.id_horario_escolinha;
				if (!idHorario) {
					alert('Não há ID de horário para impressão individual.');
					return;
				}
				// Monta a URL do relatório "individual"
				const params = new URLSearchParams();
				params.set('id_horario_escolinha', idHorario);

				// Abre direto o PDF
				const finalUrl = '/horarios/app/views/horarios-treino-individual.php?' + params.toString();
				window.open(finalUrl, '_blank');
			});

			tdAcoes.appendChild(btnPrint);
			tr.appendChild(tdAcoes);
			tbodyRelatorio.appendChild(tr);
		});
	}

	/* ======================================
	   4) Eventos dos Filtros e Botão Geral
	====================================== */
	selectAnoLetivo.addEventListener('change', () => {
		idAnoSelecionado = selectAnoLetivo.value || null;

		if (!idAnoSelecionado) {
			// Reseta e desabilita todos os filtros
			selectTurno.disabled = true;
			selectModalidade.disabled = true;
			selectCategoria.disabled = true;
			selectProfessor.disabled = true;
			btnImprimir.disabled = true;
			
			tbodyRelatorio.innerHTML = '';
			noDataMessage.style.display = 'block';
			return;
		}
		
		// Habilita filtros e reseta valores
		selectTurno.disabled = false;
		selectModalidade.disabled = false;

		// Reseta valores dos campos
		selectTurno.value = '';
		selectModalidade.value = '';
		selectCategoria.value = '';
		selectProfessor.value = '';
		
		idTurnoSelecionado = null;
		idModalidadeSelecionada = null;
		idCategoriaSelecionada = null;
		idProfessorSelecionado = null;
		
		// Carrega dados para todos os filtros principais
		loadTurnos();
		loadModalidades();
		
		// Sempre desabilita categoria até que uma modalidade seja selecionada
		selectCategoria.innerHTML = '<option value="">Todos</option>';
		selectCategoria.disabled = true;
		
		loadProfessores();
		loadTreinosFiltrados();
	});

	selectTurno.addEventListener('change', () => {
		idTurnoSelecionado = selectTurno.value || null;
		
		// Se mudou o turno, é necessário recarregar as modalidades também
		if (idAnoSelecionado) {
			loadModalidades();
		}
		
		// Se tem uma modalidade selecionada, recarregar categorias
		if (idModalidadeSelecionada) {
			loadCategoriasComDados();  // Usa a função que filtra categorias com dados
		} else {
			// Limpa e desabilita categorias
			selectCategoria.innerHTML = '<option value="">Todos</option>';
			selectCategoria.disabled = true;
		}
		
		// Atualiza tabela e professores
		loadTreinosFiltrados();
		loadProfessores();
	});

	selectModalidade.addEventListener('change', () => {
		idModalidadeSelecionada = selectModalidade.value || null;
		
		// Reset categoria quando a modalidade muda
		selectCategoria.value = '';
		idCategoriaSelecionada = null;
		
		// Se não há modalidade selecionada, desabilita o select de categorias
		if (!idModalidadeSelecionada) {
			selectCategoria.innerHTML = '<option value="">Todos</option>';
			selectCategoria.disabled = true;
			loadTreinosFiltrados();
			loadProfessores();
			return;
		}
		
		// Carrega categorias baseado na modalidade selecionada e dados existentes
		loadCategoriasComDados();  // Usa a função que filtra categorias com dados
		
		// Atualiza a tabela de treinos com a nova modalidade
		loadTreinosFiltrados();
		
		// Atualiza lista de professores baseado na nova modalidade
		loadProfessores();
	});

	selectCategoria.addEventListener('change', () => {
		idCategoriaSelecionada = selectCategoria.value || null;
		loadTreinosFiltrados();
		// Se mudar a categoria, recarrega professores
		loadProfessores();
	});

	selectProfessor.addEventListener('change', () => {
		idProfessorSelecionado = selectProfessor.value || null;
		loadTreinosFiltrados();
	});

	/* ======================================
	   5) Modal de Impressão
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

	// Eventos do Modal
	closeModalImpressaoBtn.addEventListener('click', closeModalImpressao);
	btnCancelarImpressao.addEventListener('click', closeModalImpressao);

	// Botão "Imprimir" no modal - confirma a impressão
	btnImprimirConfirmar.addEventListener('click', () => {
		imprimirAcaoFinal();
	});

	// Ao clicar no botão "Imprimir" geral
	btnImprimir.addEventListener('click', () => {
		if (!idAnoSelecionado) {
			alert('Selecione um Ano Letivo.');
			return;
		}
		
		// Abrir modal de confirmação
		openModalImpressao();
	});

	function imprimirAcaoFinal() {
		// Monta a URL do relatório "geral" incluindo todos os filtros
		const params = new URLSearchParams();
		params.set('id_ano_letivo', idAnoSelecionado);
		
		// Adiciona os filtros opcionais
		if (idTurnoSelecionado) {
			params.set('id_turno', idTurnoSelecionado);
		}
		if (idModalidadeSelecionada) {
			params.set('id_modalidade', idModalidadeSelecionada);
		}
		if (idCategoriaSelecionada) {
			params.set('id_categoria', idCategoriaSelecionada);
		}
		if (idProfessorSelecionado) {
			params.set('id_professor', idProfessorSelecionado);
		}
		
		// Abre direto o PDF geral
		const finalUrl = '/horarios/app/views/horarios-treino-geral.php?' + params.toString();
		window.open(finalUrl, '_blank');
		
		// Fecha o modal
		closeModalImpressao();
	}

	/* ======================================
	   5) Inicialização
	====================================== */
	loadAnosLetivos();
	renderTabelaTreinos([]);
	selectTurno.disabled = true;
	selectModalidade.disabled = true;
	selectCategoria.disabled = true;
	selectProfessor.disabled = true;
	btnImprimir.disabled = true;

	/* ======================================
	   6) Ordenação (Sort)
	====================================== */
	function sortTableData(key, order) {
		if (!treinosFiltrados || treinosFiltrados.length === 0) return;
		
		treinosFiltrados.sort((a, b) => {
			let valA = a[key] || '';
			let valB = b[key] || '';
			
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
		
		renderTabelaTreinos(treinosFiltrados);
	}

	document.getElementById('sort-ano-asc').addEventListener('click', () => {
		sortTableData('ano', 'asc');
	});
	document.getElementById('sort-ano-desc').addEventListener('click', () => {
		sortTableData('ano', 'desc');
	});

	document.getElementById('sort-modalidade-asc').addEventListener('click', () => {
		sortTableData('nome_modalidade', 'asc');
	});
	document.getElementById('sort-modalidade-desc').addEventListener('click', () => {
		sortTableData('nome_modalidade', 'desc');
	});

	document.getElementById('sort-categoria-asc').addEventListener('click', () => {
		sortTableData('nome_categoria', 'asc');
	});
	document.getElementById('sort-categoria-desc').addEventListener('click', () => {
		sortTableData('nome_categoria', 'desc');
	});

	document.getElementById('sort-professor-asc').addEventListener('click', () => {
		sortTableData('nome_professor', 'asc');
	});
	document.getElementById('sort-professor-desc').addEventListener('click', () => {
		sortTableData('nome_professor', 'desc');
	});
});