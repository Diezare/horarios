// app/assets/js/relatorio-hora-aula-escolinha.js
document.addEventListener('DOMContentLoaded', function () {
	/* =======================
		 ELEMENTOS DO DOM
	======================= */
	const selectAnoLetivo = document.getElementById('selectAnoLetivo');
	const selectTurno = document.getElementById('selectTurno');
	const btnImprimir = document.getElementById('btnImprimir');
	const tbodyRelatorio = document.getElementById('relatorio-tbody');
	const noDataMessage = document.getElementById('no-data-message');

	// Radios (filtros principais)
	const radiosTipoRelatorio = document.querySelectorAll('input[name="tipo-relatorio"]');

	// Modal + elementos (ATENÇÃO: IDs desta página)
	const modalImpressaoGeral = document.getElementById('modal-print-relatorio-geral');
	const closeModalImpressaoGeral = document.getElementById('close-print-modal');
	const btnImprimirGeralConfirmar	= document.getElementById('btn-imprimir-geral-confirm');
	const btnCancelarImpressaoGeral	= document.getElementById('btn-cancelar-geral');

	const selectAnoLetivoModal = document.getElementById('select-ano-print');	 // <— ID correto
	const selectTurnoModal = document.getElementById('select-turno-print'); // <— ID correto
	const radiosTipoRelatorioModal = document.querySelectorAll('input[name="tipo-relatorio-print"]');

	// Ícones de ordenação (opcional)
	const sortIcons = document.querySelectorAll('.sort-asc, .sort-desc');

	/* =======================
		 ESTADO
	======================= */
	let anosLetivosData = [];
	let turnosData = [];
	let relatorioDados = [];
	let currentSort = { column: null, order: null };

	/* =======================
		 HELPERS
	======================= */
	function renderOptions(selectEl, data, valueKey, labelKey, placeholder = '-- Selecione --') {
		if (!selectEl) return;
		selectEl.innerHTML = '';
		const opt0 = document.createElement('option');
		opt0.value = '';
		opt0.textContent = placeholder;
		selectEl.appendChild(opt0);

		(data || []).forEach(item => {
			const opt = document.createElement('option');
			opt.value = item[valueKey];
			opt.textContent = item[labelKey];
			selectEl.appendChild(opt);
		});

		selectEl.disabled = false;
	}

	function safeJSON(text) {
		if (!text || !text.trim()) throw new Error('Resposta vazia do servidor');
		try {
			return JSON.parse(text);
		} catch (e) {
			console.error('JSON inválido. Resposta recebida:', text);
			throw new Error('Resposta não é um JSON válido');
		}
	}

	function obterTipoRelatorioSelecionado() {
		const r = document.querySelector('input[name="tipo-relatorio"]:checked');
		return r ? r.value : 'tudo';
	}
	function obterTipoRelatorioSelecionadoModal() {
		const r = document.querySelector('input[name="tipo-relatorio-print"]:checked');
		return r ? r.value : 'tudo';
	}

	/* =======================
		 CARGA DE ANOS / TURNOS
	======================= */
	// Substitua sua loadAnosLetivos atual por esta
async function loadAnosLetivos() {
	if (selectAnoLetivo) {
		selectAnoLetivo.innerHTML = '<option value="">-- Selecione --</option>';
		selectAnoLetivo.disabled = true;
	}
	if (selectAnoLetivoModal) {
		selectAnoLetivoModal.innerHTML = '<option value="">Selecione</option>';
		selectAnoLetivoModal.disabled = true;
	}

	try {
		const resp = await fetch('/horarios/app/controllers/ano-letivo/listAnoLetivo.php');
		if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
		const data = safeJSON(await resp.text());

		if (data.status === 'success' && Array.isArray(data.data)) {
			anosLetivosData = data.data;

			// Popula, mas não seleciona automaticamente
			renderOptions(selectAnoLetivo, anosLetivosData, 'id_ano_letivo', 'ano', '-- Selecione --');
			renderOptions(selectAnoLetivoModal, anosLetivosData, 'id_ano_letivo', 'ano', 'Selecione');

			// Garante que continuem vazios
			if (selectAnoLetivo) selectAnoLetivo.value = '';
			if (selectAnoLetivoModal) selectAnoLetivoModal.value = '';
		} else {
			console.error('Falha ao obter anos letivos:', data.message || 'desconhecido');
			if (selectAnoLetivo) {
				selectAnoLetivo.innerHTML = '<option value="">(sem anos letivos)</option>';
				selectAnoLetivo.disabled = false;
			}
			if (selectAnoLetivoModal) {
				selectAnoLetivoModal.innerHTML = '<option value="">(sem anos letivos)</option>';
				selectAnoLetivoModal.disabled = false;
			}
		}
	} catch (err) {
		console.error('Erro ao carregar anos letivos:', err);
		if (selectAnoLetivo) {
			selectAnoLetivo.innerHTML = '<option value="">Erro ao carregar</option>';
			selectAnoLetivo.disabled = false;
		}
		if (selectAnoLetivoModal) {
			selectAnoLetivoModal.innerHTML = '<option value="">Erro ao carregar</option>';
			selectAnoLetivoModal.disabled = false;
		}
	}
}


	async function loadTurnos() {
		if (selectTurno) {
			selectTurno.innerHTML = '<option value="">Carregando...</option>';
			selectTurno.disabled = true;
		}
		if (selectTurnoModal) {
			selectTurnoModal.innerHTML = '<option value="">Carregando...</option>';
			selectTurnoModal.disabled = true;
		}

		try {
			const resp = await fetch('/horarios/app/controllers/turno/listTurno.php');
			if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
			const data = safeJSON(await resp.text());

			if (data.status === 'success' && Array.isArray(data.data)) {
				turnosData = data.data;

				renderOptions(selectTurno, turnosData, 'id_turno', 'nome_turno', '-- Selecione o Turno --');
				renderOptions(selectTurnoModal, turnosData, 'id_turno', 'nome_turno', 'Todos');
			} else {
				console.error('Falha ao obter turnos:', data.message || 'desconhecido');
				if (selectTurno) {
					selectTurno.innerHTML = '<option value="">(sem turnos)</option>';
					selectTurno.disabled = false;
				}
				if (selectTurnoModal) {
					selectTurnoModal.innerHTML = '<option value="">(sem turnos)</option>';
					selectTurnoModal.disabled = false;
				}
			}
		} catch (err) {
			console.error('Erro ao carregar turnos:', err);
			if (selectTurno) {
				selectTurno.innerHTML = '<option value="">Erro ao carregar</option>';
				selectTurno.disabled = false;
			}
			if (selectTurnoModal) {
				selectTurnoModal.innerHTML = '<option value="">Erro ao carregar</option>';
				selectTurnoModal.disabled = false;
			}
		}
	}

	/* =======================
		 RELATÓRIO (TABELA)
	======================= */
	async function carregarRelatorio() {
		if (!selectAnoLetivo || !selectAnoLetivo.value) {
			limparTabela();
			return;
		}

		if (noDataMessage) {
			noDataMessage.style.display = 'block';
			noDataMessage.textContent = 'Carregando dados...';
		}

		const filtros = {
			id_ano_letivo: selectAnoLetivo.value,
			tipo_relatorio: obterTipoRelatorioSelecionado()
		};
		if (selectTurno && selectTurno.value) filtros.id_turno = selectTurno.value;

		const params = new URLSearchParams();
		Object.keys(filtros).forEach(k => filtros[k] && params.append(k, filtros[k]));

		const url = `/horarios/app/controllers/relatorio-hora-aula-escolinha/listRelatorioHoraAulaEscolinha.php?${params.toString()}`;

		try {
			const resp = await fetch(url);
			const text = await resp.text();
			if (!resp.ok) throw new Error(`HTTP ${resp.status} - ${resp.statusText}`);

			const data = safeJSON(text);
			if (data.status === 'success') {
				relatorioDados = data.data || [];
				renderTabelaRelatorio(relatorioDados);
			} else {
				console.error('Erro no relatório:', data.message);
				relatorioDados = [];
				renderTabelaRelatorio([]);
				if (noDataMessage) {
					noDataMessage.style.display = 'block';
					noDataMessage.textContent = data.message || 'Erro ao carregar dados';
				}
			}
		} catch (error) {
			console.error('Erro na requisição do relatório:', error);
			relatorioDados = [];
			renderTabelaRelatorio([]);
			if (noDataMessage) {
				noDataMessage.style.display = 'block';
				noDataMessage.textContent = 'Erro ao carregar dados: ' + error.message;
			}
		}
	}

	function renderTabelaRelatorio(dados) {
		if (!tbodyRelatorio || !noDataMessage) return;

		tbodyRelatorio.innerHTML = '';

		if (!dados || dados.length === 0) {
			noDataMessage.style.display = 'block';
			noDataMessage.textContent = 'Nenhum dado encontrado para os filtros selecionados.';
			return;
		}
		noDataMessage.style.display = 'none';

		dados.forEach(item => {
			const tr = document.createElement('tr');

			const tdProfessor = document.createElement('td');
			tdProfessor.textContent = item.nome_professor || 'N/A';
			tr.appendChild(tdProfessor);

			const tdModalidade = document.createElement('td');
			tdModalidade.textContent = item.nome_modalidade || 'N/A';
			tr.appendChild(tdModalidade);

			const tdCategoria = document.createElement('td');
			tdCategoria.textContent = item.nome_categoria || 'N/A';
			tr.appendChild(tdCategoria);

			const tdTotalAulas = document.createElement('td');
			tdTotalAulas.textContent = item.aulas_e_horas_texto || `${item.total_aulas || 0} aulas`;
			tr.appendChild(tdTotalAulas);

			const tdAcoes = document.createElement('td');
			const btn = document.createElement('button');
			btn.className = 'btn-action btn-print';
			btn.textContent = 'Imprimir';
			btn.onclick = () => imprimirIndividual(item);
			tdAcoes.appendChild(btn);
			tr.appendChild(tdAcoes);

			tbodyRelatorio.appendChild(tr);
		});

		if (currentSort.column && currentSort.order) {
			aplicarOrdenacao(currentSort.column, currentSort.order);
		}
	}

	/* =======================
		 ORDENAÇÃO
	======================= */
	function configurarOrdenacao() {
		sortIcons.forEach(icon => {
			icon.addEventListener('click', function () {
				const column = this.getAttribute('data-column');
				const order	= this.getAttribute('data-order');

				sortIcons.forEach(i => i.classList.remove('active'));
				this.classList.add('active');

				aplicarOrdenacao(column, order);
				currentSort = { column, order };
			});
		});
	}

	function aplicarOrdenacao(column, order) {
		if (!tbodyRelatorio) return;
		const rows = Array.from(tbodyRelatorio.querySelectorAll('tr'));

		rows.sort((a, b) => {
			let A, B;
			switch (column) {
				case 'professor':
					A = a.cells[0].textContent.toLowerCase();
					B = b.cells[0].textContent.toLowerCase();
					break;
				case 'modalidade':
					A = a.cells[1].textContent.toLowerCase();
					B = b.cells[1].textContent.toLowerCase();
					break;
				case 'categoria':
					A = a.cells[2].textContent.toLowerCase();
					B = b.cells[2].textContent.toLowerCase();
					break;
				case 'total-aulas':
					A = extrairNumeroAulas(a.cells[3].textContent);
					B = extrairNumeroAulas(b.cells[3].textContent);
					return order === 'asc' ? A - B : B - A;
				default:
					return 0;
			}
			return order === 'asc' ? A.localeCompare(B) : B.localeCompare(A);
		});

		tbodyRelatorio.innerHTML = '';
		rows.forEach(r => tbodyRelatorio.appendChild(r));
	}

	function extrairNumeroAulas(txt) {
		const m = txt.match(/(\d+)\s*Aulas?/i);
		return m ? parseInt(m[1], 10) : 0;
	}

	function limparTabela() {
		if (tbodyRelatorio) tbodyRelatorio.innerHTML = '';
		if (noDataMessage) {
			noDataMessage.style.display = 'block';
			noDataMessage.textContent = 'Selecione os filtros para visualizar os dados.';
		}
	}

	/* =======================
		 IMPRESSÃO
	======================= */
	function imprimirIndividual(item) {
		if (!selectAnoLetivo || !selectAnoLetivo.value) {
			alert('Selecione um ano letivo.');
			return;
		}
		const filtros = {
			id_ano_letivo: selectAnoLetivo.value,
			id_professor: item.id_professor,
			id_modalidade: item.id_modalidade,
			id_categoria: item.id_categoria,
			tipo_relatorio: obterTipoRelatorioSelecionado()
		};
		if (selectTurno && selectTurno.value) filtros.id_turno = selectTurno.value;

		const params = new URLSearchParams(filtros);
		const url = `/horarios/app/views/relatorio-hora-aula-individual.php?${params.toString()}`;
		window.open(url, '_blank');
	}

	function imprimirGeral() {
		const anoLetivo = (selectAnoLetivoModal && selectAnoLetivoModal.value) || (selectAnoLetivo && selectAnoLetivo.value);
		if (!anoLetivo) {
			alert('Selecione um ano letivo.');
			return;
		}
		const filtros = {
			id_ano_letivo: anoLetivo,
			tipo_relatorio: obterTipoRelatorioSelecionadoModal()
		};
		if (selectTurnoModal && selectTurnoModal.value) filtros.id_turno = selectTurnoModal.value;

		const params = new URLSearchParams(filtros);
		const url = `/horarios/app/views/relatorio-hora-aula-geral.php?${params.toString()}`;
		window.open(url, '_blank');
		fecharModalImpressaoGeral();
	}

	/* =======================
		 MODAL
	======================= */
	function abrirModalImpressaoGeral() {
		if (!modalImpressaoGeral) return;

		// Popular selects do modal com dados já carregados
		renderOptions(selectAnoLetivoModal, anosLetivosData, 'id_ano_letivo', 'ano', 'Selecione');
		renderOptions(selectTurnoModal,		 turnosData,			'id_turno',			'nome_turno', 'Todos');

		// Sincronizar valores atuais
		if (selectAnoLetivoModal && selectAnoLetivo) {
			selectAnoLetivoModal.value = selectAnoLetivo.value || '';
		}
		if (selectTurnoModal && selectTurno) {
			selectTurnoModal.value = selectTurno.value || '';
		}

		// Sincronizar tipo de relatório
		const tipoAtual = obterTipoRelatorioSelecionado();
		const radioModal = document.querySelector(`input[name="tipo-relatorio-print"][value="${tipoAtual}"]`);
		if (radioModal) radioModal.checked = true;

		// Abrir com animação
		modalImpressaoGeral.style.display = 'block';
		modalImpressaoGeral.classList.remove('fade-out');
		modalImpressaoGeral.classList.add('fade-in');
		const content = modalImpressaoGeral.querySelector('.modal-content');
		if (content) {
			content.classList.remove('slide-up');
			content.classList.add('slide-down');
		}
	}

	function fecharModalImpressaoGeral() {
		if (!modalImpressaoGeral) return;
		const content = modalImpressaoGeral.querySelector('.modal-content');

		if (content) {
			content.classList.remove('slide-down');
			content.classList.add('slide-up');
		}
		modalImpressaoGeral.classList.remove('fade-in');
		modalImpressaoGeral.classList.add('fade-out');

		setTimeout(() => {
			modalImpressaoGeral.style.display = 'none';
			if (content) content.classList.remove('slide-up');
			modalImpressaoGeral.classList.remove('fade-out');
		}, 300);
	}

	/* =======================
		 EVENTOS
	======================= */
	function adicionarEventListeners() {
		if (selectAnoLetivo) selectAnoLetivo.addEventListener('change', carregarRelatorio);
		if (selectTurno)			selectTurno.addEventListener('change',	carregarRelatorio);

		radiosTipoRelatorio.forEach(r => r.addEventListener('change', carregarRelatorio));

		if (btnImprimir)							btnImprimir.addEventListener('click', abrirModalImpressaoGeral);
		if (closeModalImpressaoGeral) closeModalImpressaoGeral.addEventListener('click', fecharModalImpressaoGeral);
		if (btnCancelarImpressaoGeral)btnCancelarImpressaoGeral.addEventListener('click', fecharModalImpressaoGeral);
		if (btnImprimirGeralConfirmar)btnImprimirGeralConfirmar.addEventListener('click', imprimirGeral);

		if (modalImpressaoGeral) {
			modalImpressaoGeral.addEventListener('click', (e) => {
				if (e.target === modalImpressaoGeral) fecharModalImpressaoGeral();
			});
		}

		configurarOrdenacao();
	}

	/* =======================
		 INICIALIZAÇÃO
	======================= */
	async function inicializar() {
		await loadAnosLetivos();
		await loadTurnos();
		adicionarEventListeners();

		if (selectAnoLetivo && selectAnoLetivo.value) {
			carregarRelatorio();
		} else {
			limparTabela();
		}
	}

	inicializar();
});
