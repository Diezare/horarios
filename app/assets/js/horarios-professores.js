// app/assets/js/horarios-professores.js
document.addEventListener('DOMContentLoaded', function() {
	/* ======================================
		 1) Referências aos ELEMENTOS e Estado
	====================================== */
	const selectAnoLetivo = document.getElementById('selectAnoLetivo');
	const selectNivelEnsino	= document.getElementById('selectNivelEnsino');
	const btnImprimir = document.getElementById('btnImprimir');
	const tbodyRelatorio = document.getElementById('tbodyRelatorioHorarios');
	const noDataMessage = document.getElementById('noDataMessage');

	// Modal de Impressão
	const modalImpressao = document.getElementById('modalImpressao');
	const closeModalBtn = document.getElementById('closeModalImpressao');
	const btnConfirmImprimir = document.getElementById('btnConfirmImprimir');
	const btnCancelar = document.getElementById('btnCancelar');
 
	// Variáveis de estado para os filtros
	let idAnoSelecionado = null;
	let idNivelEnsinoSelecionado = null;
	// Se o usuário clicar no "Imprimir" de uma linha, guarda o id do professor
	let idProfessorSelecionado = null;
	
	// Variável para armazenar os dados dos professores (usada na ordenação)
	let professoresData = [];

	/* ======================================
		 2) Função: Carregar Anos Letivos
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

	/* ======================================
		 3) Função: Carregar Níveis de Ensino por Ano
	====================================== */
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
	
	/* ======================================
		 4) Função: Carregar Professores para o Relatório
	====================================== */
	async function loadProfessoresAulas() {
		if (!idAnoSelecionado) {
			renderTabelaProfessores([]);
			return;
		}
		const params = new URLSearchParams();
		params.set('id_ano_letivo', idAnoSelecionado);
		if (idNivelEnsinoSelecionado) {
			params.set('id_nivel_ensino', idNivelEnsinoSelecionado);
		}
		// Endpoint criado para retornar os professores com aulas
		const url = `/horarios/app/controllers/professor/listProfessoresAulasRelatorio.php?${params.toString()}`;
		try {
			let resp = await fetch(url).then(r => r.json());
			if (resp.status === 'success') {
				professoresData = resp.data; // Armazena os dados para uso na ordenação
				renderTabelaProfessores(resp.data);
			} else {
				professoresData = [];
				renderTabelaProfessores([]);
			}
		} catch (err) {
			console.error(err);
			professoresData = [];
			renderTabelaProfessores([]);
		}
	}

	function renderTabelaProfessores(rows) {
		tbodyRelatorio.innerHTML = '';
		if (!rows || rows.length === 0) {
			noDataMessage.style.display = 'block';
			return;
		}
		noDataMessage.style.display = 'none';

		rows.forEach(r => {
			const tr = document.createElement('tr');

			// Coluna: Ano Letivo
			const tdAno = document.createElement('td');
			tdAno.textContent = r.ano;
			tr.appendChild(tdAno);

			// Coluna: Nome do Professor (nome completo)
			const tdProf = document.createElement('td');
			tdProf.textContent = r.nome_professor;
			tr.appendChild(tdProf);

			// Coluna: Ações (botão imprimir)
			const tdAcoes = document.createElement('td');
			const btnPrint = document.createElement('button');
			btnPrint.classList.add('btn-print');
			btnPrint.textContent = 'Imprimir';
			btnPrint.addEventListener('click', () => {
				idProfessorSelecionado = r.id_professor;
				openModal();
			});
			tdAcoes.appendChild(btnPrint);
			tr.appendChild(tdAcoes);

			tbodyRelatorio.appendChild(tr);
		});
	}

	/* ======================================
		 5) Funções de Modal de Impressão com Efeito de Transição
	====================================== */
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

	closeModalBtn.addEventListener('click', closeModal);
	btnCancelar.addEventListener('click', closeModal);

	btnConfirmImprimir.addEventListener('click', () => {
		// Monta a URL para o PDF com os parâmetros
		let params = new URLSearchParams();
		params.set('id_ano_letivo', idAnoSelecionado);
		if (idNivelEnsinoSelecionado) {
			params.set('id_nivel_ensino', idNivelEnsinoSelecionado);
		}
		if (idProfessorSelecionado) {
			params.set('id_professor', idProfessorSelecionado);
		}
		const finalUrl = '/horarios/app/views/horarios-professores.php?' + params.toString();
		window.open(finalUrl, '_blank');
		closeModal();
	});

	/* ======================================
		 6) Eventos dos Filtros
	====================================== */
	selectAnoLetivo.addEventListener('change', () => {
		idAnoSelecionado = selectAnoLetivo.value || null;
		if (!idAnoSelecionado) {
			selectNivelEnsino.disabled = true;
			btnImprimir.disabled = true;
			tbodyRelatorio.innerHTML = '';
			noDataMessage.style.display = 'block';
			return;
		}
		selectNivelEnsino.disabled = false;
		loadNiveisPorAno(idAnoSelecionado);
		btnImprimir.disabled = false;
		loadProfessoresAulas();
	});

	selectNivelEnsino.addEventListener('change', () => {
		idNivelEnsinoSelecionado = selectNivelEnsino.value || null;
		loadProfessoresAulas();
	});

	// Botão "Imprimir" do cabeçalho: imprime o relatório completo (sem filtro de professor)
	btnImprimir.addEventListener('click', () => {
		idProfessorSelecionado = null;
		openModal();
	});

	/* ======================================
		 7) Função de Ordenação (Sort)
	====================================== */
	function sortTableData(key, order) {
		if (!professoresData || professoresData.length === 0) return;
		professoresData.sort(function(a, b) {
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
		renderTabelaProfessores(professoresData);
	}

	// Adiciona eventos de clique nos ícones de sort conforme o HTML
	document.getElementById('sort-ano-asc').addEventListener('click', function() {
		sortTableData('ano', 'asc');
	});
	document.getElementById('sort-ano-desc').addEventListener('click', function() {
		sortTableData('ano', 'desc');
	});
	document.getElementById('sort-professor-asc').addEventListener('click', function() {
		sortTableData('nome_professor', 'asc');
	});
	document.getElementById('sort-professor-desc').addEventListener('click', function() {
		sortTableData('nome_professor', 'desc');
	});

	/* ======================================
		 8) Inicialização
	====================================== */
	loadAnosLetivos();
	renderTabelaProfessores([]);
});
