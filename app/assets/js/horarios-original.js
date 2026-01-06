// app/assets/js/horarios.js
document.addEventListener('DOMContentLoaded', function() {

	// =============================================
	// ELEMENTOS DA PÁGINA
	// =============================================
	const selectAnoLetivo = document.getElementById('selectAnoLetivo');
	const selectNivelEnsino = document.getElementById('selectNivelEnsino');
	const selectTurma  = document.getElementById('selectTurma');
	const btnImprimir = document.getElementById('btnImprimir');
	const btnAutomatico = document.getElementById('btn-automatic');

	const gradeContainer = document.getElementById('grade-container');
	const modalAutomatico = document.getElementById('modal-automatico');
	const modalExtra = document.getElementById('modal-extra');

	const quadroDisciplinas	 = document.getElementById('quadro-disciplinas');

	// =============================================
	// VARIÁVEIS GLOBAIS
	// =============================================
	let idAnoSelecionado = null;
	let idNivelEnsinoSelecionado = null;
	let idTurmaSelecionada = null;
	let editingEnabled = false;

	let allHorariosDoAno = [];
	let professorRestricoesMap = {};
	let dadosTurma = null;
	let profDiscTurmaMap = {};
	let turmasMap = {};

	// Ex.: [3,6] significa que, após a 3ª aula e após a 6ª aula teremos Intervalo
	let intervalPositions = [];
	let extraClassMapping = {};

	let totalTurmasDaSerie = 1;
	let usedDisciplineCount = {};
	let disciplineWeeklyLimit = {};

	// =============================================
	// 1) CARREGAR ANOS LETIVOS
	// =============================================
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

	selectAnoLetivo.addEventListener('change', () => {
		idAnoSelecionado = selectAnoLetivo.value;
		resetNivelEnsinoSelection();
		resetTurmaSelection();
		if (idAnoSelecionado) {
			loadNiveisPorAno(idAnoSelecionado);
		}
	});

	// =============================================
	// 2) CARREGAR NÍVEIS POR ANO LETIVO
	// =============================================
	async function loadNiveisPorAno(idAno) {
		try {
			//let resp = await fetch(`/horarios/app/controllers/nivel-ensino/listNivelEnsino.php?id_ano_letivo=${idAno}`)
			let resp = await fetch(`/horarios/app/controllers/nivel-ensino/listNivelEnsinoByUserAndAno.php?id_ano_letivo=${idAno}`)
							 .then(r => r.json());
			selectNivelEnsino.innerHTML = '<option value="">-- Selecione o Nível --</option>';
			if (resp.status === 'success' && resp.data.length > 0) {
				selectNivelEnsino.disabled = false;
				resp.data.forEach(niv => {
					const opt = document.createElement('option');
					opt.value = niv.id_nivel_ensino;
					opt.textContent = niv.nome_nivel_ensino;
					selectNivelEnsino.appendChild(opt);
				});
				if (resp.data.length === 1) {
					// Seleciona automaticamente se houver só um nível
					selectNivelEnsino.value = resp.data[0].id_nivel_ensino;
					idNivelEnsinoSelecionado = selectNivelEnsino.value;
					btnAutomatico.disabled = false;
					loadTurmasPorAnoENivel(idAnoSelecionado, idNivelEnsinoSelecionado);
				}
			} else {
				selectNivelEnsino.innerHTML = '<option value="">Nenhum nível encontrado</option>';
				selectNivelEnsino.disabled = true;
			}
		} catch (err) {
			console.error(err);
		}
	}

	selectNivelEnsino.addEventListener('change', () => {
		idNivelEnsinoSelecionado = selectNivelEnsino.value;
		resetTurmaSelection();
		if (idNivelEnsinoSelecionado) {
			btnAutomatico.disabled = false;
			loadTurmasPorAnoENivel(idAnoSelecionado, idNivelEnsinoSelecionado);
		} else {
			btnAutomatico.disabled = true;
		}
	});

	// =============================================
	// 3) BOTÃO AUTOMÁTICO – ABRE MODAL
	// =============================================
	btnAutomatico.disabled = true;
	btnAutomatico.addEventListener('click', () => {
		if (!idAnoSelecionado || !idNivelEnsinoSelecionado) return;
		if (selectTurma.options.length <= 1) {
			alert("Não existem turmas cadastradas para gerar horários.");
			return;
		}
		openModalAutomatico();
	});

	function openModalAutomatico() {
		if (!modalAutomatico) {
			alert('Modal de geração automática não encontrado!');
			return;
		}
		// Mostra o modal + animações
		modalAutomatico.style.display = 'block';
		modalAutomatico.classList.remove('fade-out');
		modalAutomatico.classList.add('fade-in');

		const content = modalAutomatico.querySelector('.modal-content');
		content.classList.remove('slide-up');
		content.classList.add('slide-down');
	}

	function closeModalAutomatico() {
		const content = modalAutomatico.querySelector('.modal-content');
		content.classList.remove('slide-down');
		content.classList.add('slide-up');
		modalAutomatico.classList.remove('fade-in');
		modalAutomatico.classList.add('fade-out');

		// Espera a animação
		setTimeout(() => {
			modalAutomatico.style.display = 'none';
			content.classList.remove('slide-up');
			modalAutomatico.classList.remove('fade-out');
		}, 300);
	}

	// Botões de confirmação e cancelamento do modal
	if (modalAutomatico) {
		const btnConf = modalAutomatico.querySelector('#btnConfirmarAutomatico');
		const btnCanc = modalAutomatico.querySelector('#btnCancelarAutomatico');
		const btnClose = modalAutomatico.querySelector('.close-modal-auto');

		btnConf.onclick = async () => {
			await gerarHorariosAutomaticos();
			closeModalAutomatico();
		};
		btnCanc.onclick = () => closeModalAutomatico();
		btnClose.onclick = () => closeModalAutomatico();
	}

	async function gerarHorariosAutomaticos() {
		try {
			let body = new URLSearchParams({
				id_ano_letivo: idAnoSelecionado,
				id_nivel_ensino: idNivelEnsinoSelecionado
			});
			let resp = await fetch('/horarios/app/controllers/horarios/gerarHorariosAutomaticos.php', {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body
			}).then(r => r.json());

			if (resp.status === 'success') {
				alert('Horários gerados automaticamente com sucesso!');
				if (idTurmaSelecionada) {
					await carregarTudo();
					montarGrade();
					atualizarQuadroDisciplinas();
				}
			} else {
				alert(resp.message || 'Erro ao gerar horários!');
			}
		} catch (err) {
			console.error(err);
			alert('Ocorreu um erro ao gerar horários automáticos.');
		}
	}

	// =============================================
	// 4) CARREGAR TURMAS (POR ANO + NÍVEL)
	// =============================================
	async function loadTurmasPorAnoENivel(idAno, idNivel) {
		try {
			//let resp = await fetch(`/horarios/app/controllers/turma/listTurmaPorAnoLetivo.php?id_ano_letivo=${idAno}&id_nivel_ensino=${idNivel}`)
			let resp = await fetch(`/horarios/app/controllers/turma/listTurmaByUserAndAno.php?id_ano_letivo=${idAno}&id_nivel_ensino=${idNivel}`)
							 .then(r => r.json());
			if (resp.status === 'success' && resp.data.length > 0) {
				selectTurma.innerHTML = '<option value="">-- Selecione a Turma --</option>';
				selectTurma.disabled = false;
				resp.data.forEach(t => {
					const opt = document.createElement('option');
					opt.value = t.id_turma;
					opt.textContent = `${t.nome_serie} ${t.nome_turma} - ${t.nome_turno}`;
					selectTurma.appendChild(opt);

					turmasMap[t.id_turma] = {
						id_serie: t.id_serie,
						nome_serie: t.nome_serie,
						nome_turma: t.nome_turma,
						nome_turno: t.nome_turno
					};
				});
			} else {
				selectTurma.innerHTML = '<option value="">-- Selecione a Turma --</option>';
				selectTurma.disabled = true;
			}
		} catch (err) {
			console.error(err);
		}
	}

	function resetNivelEnsinoSelection() {
		selectNivelEnsino.innerHTML = '<option value="">-- Selecione o Nível --</option>';
		selectNivelEnsino.disabled = true;
		btnAutomatico.disabled = true;
		idNivelEnsinoSelecionado = null;
	}

	function resetTurmaSelection() {
		selectTurma.innerHTML = '<option value="">-- Selecione a Turma --</option>';
		selectTurma.disabled = true;

		gradeContainer.innerHTML = '';
		quadroDisciplinas.innerHTML = '';

		idTurmaSelecionada = null;
		editingEnabled = false;
		dadosTurma = null;
		allHorariosDoAno = [];
		professorRestricoesMap = {};
		profDiscTurmaMap = {};
		intervalPositions = [];
		extraClassMapping = {};

		totalTurmasDaSerie = 1;
		usedDisciplineCount = {};
		disciplineWeeklyLimit = {};
	}

	selectTurma.addEventListener('change', async () => {
		const turmaVal = selectTurma.value;
		if (!turmaVal) {
			gradeContainer.innerHTML = '';
			quadroDisciplinas.innerHTML = '';
			idTurmaSelecionada = null;
			dadosTurma = null;
			editingEnabled = false;
			return;
		}
		idTurmaSelecionada = turmaVal;
		editingEnabled = true;

		try {
			await carregarTudo();
			await definirExtraAulas();
			montarGrade();
			atualizarQuadroDisciplinas();
		} catch (err) {
			console.error(err);
		}
	});

	// =============================================
	// FUNÇÃO MASTER – CARREGA TUDO
	// =============================================
	async function carregarTudo() {
		await loadAllHorariosDoAno(idAnoSelecionado);
		await loadProfessorRestricoes(idAnoSelecionado);
		await loadProfessorDisciplinaTurma();
		await loadHorariosTurma(idTurmaSelecionada);

		await calcularTotalTurmasDaSerieNoAno();
		inicializarLimitesDisciplinas();
		recalcularUsosDasDisciplinas();
	}

	async function loadAllHorariosDoAno(idAno) {
		try {
			let resp = await fetch(`/horarios/app/controllers/horarios/listHorariosByAnoLetivo.php?id_ano_letivo=${idAno}`)
							 .then(r => r.json());
			allHorariosDoAno = (resp.status === 'success') ? resp.data : [];
		} catch (err) {
			console.error(err);
		}
	}

	async function loadProfessorRestricoes(idAno) {
		try {
			let resp = await fetch(`/horarios/app/controllers/professor-restricoes/listProfessorRestricoes.php?id_ano_letivo=${idAno}`)
							 .then(r => r.json());
			if (resp.status === 'success') {
				professorRestricoesMap = {};
				resp.data.forEach(row => {
					const p = row.id_professor;
					const dia = row.dia_semana;
					const aula = parseInt(row.numero_aula, 10);
					if (!professorRestricoesMap[p]) {
						professorRestricoesMap[p] = {};
					}
					if (!professorRestricoesMap[p][dia]) {
						professorRestricoesMap[p][dia] = [];
					}
					professorRestricoesMap[p][dia].push(aula);
				});
			}
		} catch (err) {
			console.error(err);
		}
	}

	async function loadProfessorDisciplinaTurma() {
		try {
			let resp = await fetch('/horarios/app/controllers/professor-disciplina-turma/listProfessorDisciplinaTurma.php?all=1')
							 .then(r => r.json());
			if (resp.status === 'success') {
				profDiscTurmaMap = {};
				resp.data.forEach(row => {
					const t = row.id_turma;
					const d = row.id_disciplina;
					const p = row.id_professor;
					if (!profDiscTurmaMap[t]) {
						profDiscTurmaMap[t] = {};
					}
					if (!profDiscTurmaMap[t][d]) {
						profDiscTurmaMap[t][d] = [];
					}
					profDiscTurmaMap[t][d].push(parseInt(p, 10));
				});
			}
		} catch (err) {
			console.error(err);
		}
	}

	async function loadHorariosTurma(idTurma) {
		try {
			let resp = await fetch(`/horarios/app/controllers/horarios/listHorarios.php?id_turma=${idTurma}`)
							 .then(r => r.json());
			if (resp.status === 'success') {
				dadosTurma = resp.data;
				const intervalStr = dadosTurma.turma.intervalos_positions || '';
				intervalPositions = intervalStr
					.split(',')
					.map(n => parseInt(n.trim(), 10))
					.filter(x => !isNaN(x) && x > 0);
			} else {
				dadosTurma = null;
			}
		} catch (err) {
			console.error(err);
		}
	}

	// =============================================
	// CALCULAR TOTAL DE TURMAS DA MESMA SÉRIE (opcional)
	// =============================================
	async function calcularTotalTurmasDaSerieNoAno() {
		if (!dadosTurma || !dadosTurma.turma) return;
		const serieAtual = dadosTurma.turma.id_serie;
		const anoLetivoAtual = dadosTurma.turma.id_ano_letivo;
		if (!serieAtual || !anoLetivoAtual) return;

		try {
			let resp = await fetch(`/horarios/app/controllers/turma/listTurmasBySerieAndAno.php?id_serie=${serieAtual}&id_ano_letivo=${anoLetivoAtual}`)
							 .then(r => r.json());
			if (resp.status === 'success' && resp.data.length > 0) {
				totalTurmasDaSerie = resp.data.length;
			} else {
				totalTurmasDaSerie = 1;
			}
		} catch (err) {
			console.warn('Não foi possível calcular total de turmas da série:', err);
			totalTurmasDaSerie = 1;
		}
	}

	function inicializarLimitesDisciplinas() {
		disciplineWeeklyLimit = {};
		if (!dadosTurma || !dadosTurma.serie_disciplinas) return;
		dadosTurma.serie_disciplinas.forEach(d => {
			const totalSerie = parseInt(d.aulas_semana, 10);
			const dividido = Math.floor(totalSerie / totalTurmasDaSerie);
			disciplineWeeklyLimit[d.id_disciplina] = dividido;
		});
	}

	function recalcularUsosDasDisciplinas() {
		usedDisciplineCount = {};
		if (!dadosTurma || !dadosTurma.horarios) return;

		dadosTurma.horarios.forEach(h => {
			const did = h.id_disciplina;
			if (!usedDisciplineCount[did]) {
				usedDisciplineCount[did] = 0;
			}
			usedDisciplineCount[did]++;
		});
	}

	// =============================================
	// DEFINIR AULA EXTRA (opcional)
	// =============================================
	async function definirExtraAulas() {
		if (!dadosTurma || !dadosTurma.turma) return;
		const totalSeriesAulas = parseInt(dadosTurma.turma.total_aulas_semana, 10);
		if (!totalSeriesAulas) return;
		const diasComAulas = (dadosTurma.turno_dias || []).filter(td => parseInt(td.aulas_no_dia, 10) > 0);
		const numDias = diasComAulas.length;
		if (numDias === 0) return;

		const baseSlots = Math.floor(totalSeriesAulas / numDias);
		const remainder = totalSeriesAulas % numDias;

		diasComAulas.forEach(td => {
			extraClassMapping[td.dia_semana] = baseSlots;
		});

		if (remainder === 1) {
			await openExtraClassModal(diasComAulas, (diaEscolhido) => {
				extraClassMapping[diaEscolhido] = baseSlots + 1;
			});
		}
	}

	function openExtraClassModal(dias, callback) {
		return new Promise((resolve) => {
			if (!modalExtra) {
				resolve();
				return;
			}
			const container = modalExtra.querySelector('.modal-content-extra');
			container.innerHTML = '<h2>Selecione o dia com aula extra</h2>';
			dias.forEach(td => {
				const label = document.createElement('label');
				const checkbox = document.createElement('input');
				checkbox.type = 'radio';
				checkbox.name = 'extraDia';
				checkbox.value = td.dia_semana;
				label.appendChild(checkbox);
				label.appendChild(document.createTextNode(td.dia_semana));
				container.appendChild(label);
				container.appendChild(document.createElement('br'));
			});
			modalExtra.style.display = 'block';

			const btnConf = modalExtra.querySelector('#btnConfirmarExtra');
			const btnCanc = modalExtra.querySelector('#btnCancelarExtra');
			const btnClose = modalExtra.querySelector('.close-modal-extra');

			btnConf.onclick = () => {
				const selected = modalExtra.querySelector('input[name="extraDia"]:checked');
				if (selected) {
					callback(selected.value);
				}
				closeModalExtra();
				resolve();
			};
			btnCanc.onclick = () => {
				closeModalExtra();
				resolve();
			};
			btnClose.onclick = () => {
				closeModalExtra();
				resolve();
			};
		});
	}

	function closeModalExtra() {
		if (!modalExtra) return;
		modalExtra.style.display = 'none';
	}

	// =============================================
	// MONTAR A GRADE DE HORÁRIOS
	// =============================================
	function montarGrade() {
		gradeContainer.innerHTML = '';
		if (!dadosTurma || !dadosTurma.turma) {
			gradeContainer.innerHTML = '<p>Turma não encontrada.</p>';
			return;
		}
		const diasComAulas = (dadosTurma.turno_dias || []).filter(td => parseInt(td.aulas_no_dia, 10) > 0);
		if (diasComAulas.length === 0) {
			gradeContainer.innerHTML = '<p>Nenhum dia possui aulas neste turno.</p>';
			return;
		}

		const maxAulasTurno = Math.max(...diasComAulas.map(td => parseInt(td.aulas_no_dia, 10)));
		const table = document.createElement('table');
		table.classList.add('tabela-horarios');

		// Cabeçalho
		const thead = document.createElement('thead');
		const trHead = document.createElement('tr');
		const thAula = document.createElement('th');
		thAula.textContent = 'Aula';
		trHead.appendChild(thAula);
		diasComAulas.forEach(d => {
			const th = document.createElement('th');
			th.textContent = traduzDia(d.dia_semana);
			trHead.appendChild(th);
		});
		thead.appendChild(trHead);
		table.appendChild(thead);

		// Corpo
		const tbody = document.createElement('tbody');
		for (let aula = 1; aula <= maxAulasTurno; aula++) {
			// Linha normal de aula (primeiro exibe a aula)
			const tr = document.createElement('tr');
			const tdLabel = document.createElement('td');
			tdLabel.textContent = aula + 'ª Aula';
			tr.appendChild(tdLabel);

			diasComAulas.forEach(d => {
				const td = document.createElement('td');
				const nAulasDia = parseInt(d.aulas_no_dia, 10);
				if (aula > nAulasDia) {
					td.style.backgroundColor = '#000';
					tr.appendChild(td);
					return;
				}
				montarCelulaAula(td, d.dia_semana, aula);
				tr.appendChild(td);
			});
			tbody.appendChild(tr);

			// Se a posição atual for um dos intervalos, insere a linha de "Intervalo"
			if (intervalPositions.includes(aula)) {
				const trInt = document.createElement('tr');
				const tdLabelInt = document.createElement('td');
				tdLabelInt.textContent = 'Intervalo';
				trInt.appendChild(tdLabelInt);

				diasComAulas.forEach(() => {
					const tdInt = document.createElement('td');
					tdInt.style.textAlign = 'center';
					tdInt.style.fontWeight = 'bold';
					tdInt.textContent = 'Intervalo';
					trInt.appendChild(tdInt);
				});
				tbody.appendChild(trInt);
			}
		}
		table.appendChild(tbody);
		gradeContainer.appendChild(table);
	}

	// =============================================
	// CRIA A CÉLULA (Select Disciplina + Select Professor)
	// =============================================
	function montarCelulaAula(td, diaSemana, numeroAula) {
		const horarioExistente = (dadosTurma.horarios || [])
			.find(h => h.dia_semana === diaSemana && parseInt(h.numero_aula, 10) === numeroAula);

		// Select Disciplina
		const selDisc = document.createElement('select');
		selDisc.classList.add('select-disciplina');
		selDisc.appendChild(new Option('--Disc--', ''));

		(dadosTurma.serie_disciplinas || []).forEach(d => {
			const opt = new Option(d.nome_disciplina, d.id_disciplina);
			selDisc.appendChild(opt);
		});

		// Select Professor
		const selProf = document.createElement('select');
		selProf.classList.add('select-professor');
		selProf.appendChild(new Option('--Prof--', ''));

		if (horarioExistente) {
			selDisc.value = horarioExistente.id_disciplina;
			refazerProfessores(selDisc, selProf, diaSemana, numeroAula);
			selProf.value = horarioExistente.id_professor;
		} else {
			refazerProfessores(selDisc, selProf, diaSemana, numeroAula);
			refazerDisciplinas(selDisc, selProf, diaSemana, numeroAula);
		}

		selDisc.disabled = !editingEnabled;
		selProf.disabled = !editingEnabled;

		selDisc.addEventListener('change', () => {
			if (!editingEnabled) return;
			if (!checarSaldoDisciplina(selDisc.value)) {
				alert('Não há mais aulas disponíveis para essa disciplina nesta turma.');
				selDisc.value = '';
				refazerProfessores(selDisc, selProf, diaSemana, numeroAula);
				return;
			}
			refazerProfessores(selDisc, selProf, diaSemana, numeroAula);

			if (selDisc.value && selProf.value) {
				salvarOuAtualizar(diaSemana, numeroAula, selDisc.value, selProf.value, td);
			} else if (!selDisc.value && !selProf.value) {
				deletarHorario(diaSemana, numeroAula);
			}
			aplicarCorCelula(td, selDisc.value, selProf.value);
		});

		selProf.addEventListener('change', () => {
			if (!editingEnabled) return;
			if (selDisc.value && !checarSaldoDisciplina(selDisc.value)) {
				alert('Disciplina já está no limite de aulas para esta turma.');
				selProf.value = '';
				return;
			}
			refazerDisciplinas(selDisc, selProf, diaSemana, numeroAula);

			if (selDisc.value && selProf.value) {
				salvarOuAtualizar(diaSemana, numeroAula, selDisc.value, selProf.value, td);
			} else if (!selDisc.value && !selProf.value) {
				deletarHorario(diaSemana, numeroAula);
			}
			aplicarCorCelula(td, selDisc.value, selProf.value);
		});

		td.appendChild(selDisc);
		td.appendChild(document.createElement('br'));
		td.appendChild(selProf);

		if (horarioExistente) {
			aplicarCorCelula(td, horarioExistente.id_disciplina, horarioExistente.id_professor);
		}
	}

	function refazerProfessores(selDisc, selProf, diaSemana, numeroAula) {
		const currentProf = selProf.value;
		selProf.innerHTML = '';
		selProf.appendChild(new Option('--Prof--', ''));

		const linkedProfSet = getProfessoresVinculadosTurma();
		(dadosTurma.professores || []).forEach(prof => {
			const pid = parseInt(prof.id_professor, 10);
			if (!linkedProfSet.has(pid)) return;
			if (professorEhRestrito(pid, diaSemana, numeroAula)) return;

			const conflict = professorOcupado(pid, diaSemana, numeroAula);
			let displayName = prof.nome_exibicao || ('Prof ' + pid);
			const opt = new Option(displayName, pid);

			if (conflict) {
				displayName = `❌ ${displayName} (Turma: ${conflict.nome_serie} ${conflict.nome_turma})`;
				opt.text = displayName;
				opt.disabled = true;
			}
			selProf.appendChild(opt);
		});
		selProf.value = currentProf;
	}

	function refazerDisciplinas(selDisc, selProf, diaSemana, numeroAula) {
		const profId = parseInt(selProf.value, 10) || 0;
		const discSelecionada = selDisc.value;
		selDisc.innerHTML = '';
		selDisc.appendChild(new Option('--Disc--', ''));

		if (profId && profDiscTurmaMap[idTurmaSelecionada]) {
			const mapDisc = profDiscTurmaMap[idTurmaSelecionada] || {};
			const discDoProf = Object.keys(mapDisc)
				.filter(did => mapDisc[did].includes(profId))
				.map(x => parseInt(x, 10));

			(dadosTurma.serie_disciplinas || []).forEach(d => {
				if (discDoProf.includes(d.id_disciplina)) {
					const opt = new Option(d.nome_disciplina, d.id_disciplina);
					if (!checarSaldoDisciplina(d.id_disciplina, true)) {
						opt.text = '❌ ' + d.nome_disciplina + ' (0 disponíveis)';
						opt.disabled = true;
					}
					selDisc.appendChild(opt);
				}
			});
		} else {
			(dadosTurma.serie_disciplinas || []).forEach(d => {
				const opt = new Option(d.nome_disciplina, d.id_disciplina);
				if (!checarSaldoDisciplina(d.id_disciplina, true)) {
					opt.text = '❌ ' + d.nome_disciplina + ' (0 disponíveis)';
					opt.disabled = true;
				}
				selDisc.appendChild(opt);
			});
		}

		const existeNaLista = Array.from(selDisc.options).some(o => String(o.value) === String(discSelecionada));
		selDisc.value = existeNaLista ? discSelecionada : '';
	}

	function getProfessoresVinculadosTurma() {
		const linked = new Set();
		if (profDiscTurmaMap[idTurmaSelecionada]) {
			Object.values(profDiscTurmaMap[idTurmaSelecionada]).forEach(arr => {
				arr.forEach(pid => linked.add(pid));
			});
		}
		return linked;
	}

	function professorEhRestrito(profId, diaSemana, numeroAula) {
		const dias = professorRestricoesMap[profId];
		if (!dias) return false;
		const aulasRestritas = dias[diaSemana] || [];
		return aulasRestritas.includes(numeroAula);
	}

	function professorOcupado(profId, diaSemana, numeroAula) {
		const conflict = allHorariosDoAno.find(h =>
			h.id_professor == profId &&
			h.dia_semana == diaSemana &&
			parseInt(h.numero_aula, 10) === numeroAula &&
			h.id_turma != idTurmaSelecionada
		);
		if (conflict) {
			return { nome_serie: conflict.nome_serie, nome_turma: conflict.nome_turma };
		}
		return null;
	}

	function checarSaldoDisciplina(discId, apenasVerificar = false) {
		if (!discId) return true;
		const limite = disciplineWeeklyLimit[discId] || 0;
		const usado	= usedDisciplineCount[discId] || 0;
		return (limite - usado > 0);
	}

	function salvarOuAtualizar(diaSemana, numeroAula, discId, profId, td) {
		if (!idTurmaSelecionada) return;
		if (!discId && !profId) {
			deletarHorario(diaSemana, numeroAula);
			return;
		}
		if (!discId || !profId) return;

		// Restrições
		if (professorEhRestrito(profId, diaSemana, numeroAula)) {
			alert("O professor está restrito neste horário.");
			return;
		}
		const conflict = professorOcupado(profId, diaSemana, numeroAula);
		if (conflict) {
			alert(`O professor já está ocupado no mesmo horário (Turma: ${conflict.nome_serie} ${conflict.nome_turma}).`);
			return;
		}
		if (!checarSaldoDisciplina(discId)) {
			alert("A disciplina não possui mais aulas disponíveis.");
			return;
		}

		const h = (dadosTurma.horarios || []).find(x => x.dia_semana===diaSemana && parseInt(x.numero_aula,10)===numeroAula);
		if (!h) {
			inserirHorario(diaSemana, numeroAula, discId, profId);
		} else {
			registrarHistorico(h).then(() => {
				atualizarHorario(h.id_horario, discId, profId);
			});
		}
	}

	async function inserirHorario(diaSemana, numeroAula, discId, profId) {
		const body = new URLSearchParams({
			id_turma: idTurmaSelecionada,
			dia_semana: diaSemana,
			numero_aula: numeroAula,
			id_disciplina: discId,
			id_professor: profId
		});
		try {
			let resp = await fetch('/horarios/app/controllers/horarios/insertHorarios.php', {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body
			}).then(r => r.json());

			if (resp.status === 'success') {
				dadosTurma.horarios.push(resp.data);
				allHorariosDoAno.push({
					...resp.data,
					nome_serie: turmasMap[idTurmaSelecionada].nome_serie,
					nome_turma: turmasMap[idTurmaSelecionada].nome_turma
				});
				if (!usedDisciplineCount[discId]) {
					usedDisciplineCount[discId] = 0;
				}
				usedDisciplineCount[discId]++;
			} else {
				alert(resp.message);
			}
			atualizarQuadroDisciplinas();
		} catch (err) {
			console.error(err);
		}
	}

	async function atualizarHorario(idHorario, discId, profId) {
		const body = new URLSearchParams({
			id_horario: idHorario,
			id_disciplina: discId,
			id_professor: profId
		});
		try {
			let resp = await fetch('/horarios/app/controllers/horarios/updateHorarios.php', {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body
			}).then(r => r.json());

			if (resp.status === 'success') {
				const h = (dadosTurma.horarios || []).find(x => x.id_horario == idHorario);
				if (h) {
					if (h.id_disciplina != discId) {
						const oldDisc = parseInt(h.id_disciplina, 10);
						if (usedDisciplineCount[oldDisc]) {
							usedDisciplineCount[oldDisc]--;
						}
						if (!usedDisciplineCount[discId]) {
							usedDisciplineCount[discId] = 0;
						}
						usedDisciplineCount[discId]++;
					}
					h.id_disciplina = discId;
					h.id_professor	= profId;
				}
				const hh = allHorariosDoAno.find(x => x.id_horario == idHorario);
				if (hh) {
					hh.id_disciplina = discId;
					hh.id_professor	= profId;
				}
			} else {
				if (resp.message !== 'Nenhuma alteração ou registro não encontrado.') {
					alert(resp.message);
				}
			}
			atualizarQuadroDisciplinas();
		} catch (err) {
			console.error(err);
		}
	}

	function deletarHorario(diaSemana, numeroAula) {
		const horarioExistente = (dadosTurma.horarios || [])
			.find(h => h.dia_semana === diaSemana && parseInt(h.numero_aula, 10) === numeroAula);
		if (horarioExistente) {
			registrarHistorico(horarioExistente).then(() => {
				deletaNoBanco(diaSemana, numeroAula, horarioExistente.id_horario, horarioExistente.id_disciplina);
			});
		} else {
			deletaNoBanco(diaSemana, numeroAula, null, null);
		}
	}

	async function deletaNoBanco(diaSemana, numeroAula, idHorario, discId) {
		const body = new URLSearchParams({
			id_turma: idTurmaSelecionada,
			dia_semana: diaSemana,
			numero_aula: numeroAula
		});
		try {
			let resp = await fetch('/horarios/app/controllers/horarios/deleteHorarios.php', {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body
			}).then(r => r.json());

			if (resp.status === 'success') {
				if (idHorario && discId) {
					if (usedDisciplineCount[discId] > 0) {
						usedDisciplineCount[discId]--;
					}
				}
				dadosTurma.horarios = (dadosTurma.horarios || [])
					.filter(x => x.dia_semana !== diaSemana || parseInt(x.numero_aula) !== numeroAula);
				allHorariosDoAno = allHorariosDoAno.filter(x => x.id_horario != resp.id_horario);
			} else {
				if (resp.message === 'Horário não encontrado.') {
					dadosTurma.horarios = (dadosTurma.horarios || [])
						.filter(x => x.dia_semana !== diaSemana || parseInt(x.numero_aula) !== numeroAula);
					allHorariosDoAno = allHorariosDoAno.filter(x =>
						x.id_turma != idTurmaSelecionada ||
						x.dia_semana !== diaSemana ||
						parseInt(x.numero_aula) !== numeroAula
					);
				} else {
					alert(resp.message);
				}
			}
			atualizarQuadroDisciplinas();
		} catch (err) {
			console.error(err);
		}
	}

	async function registrarHistorico(horarioObj) {
		if (!horarioObj || !horarioObj.id_horario) return;
		const body = new URLSearchParams({
			id_horario_original: horarioObj.id_horario,
			id_turma: horarioObj.id_turma,
			id_ano_letivo: idAnoSelecionado,
			dia_semana: horarioObj.dia_semana,
			numero_aula: horarioObj.numero_aula,
			id_disciplina: horarioObj.id_disciplina,
			id_professor: horarioObj.id_professor,
			data_criacao: horarioObj.data_criacao || ''
		});
		try {
			let resp = await fetch('/horarios/app/controllers/horarios/archiveHorario.php', {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body
			}).then(r => r.json());
			if (resp.status !== 'success') {
				console.warn('Falha ao registrar histórico:', resp.message);
			}
		} catch (err) {
			console.error(err);
		}
	}

	// =============================================
	// MONTA QUADRO DE DISCIPLINAS
	// =============================================
	function atualizarQuadroDisciplinas() {
		quadroDisciplinas.innerHTML = '';
		if (!dadosTurma || !dadosTurma.serie_disciplinas) return;

		const table = document.createElement('table');
		table.classList.add('quadro-disc-table');

		const thead = document.createElement('thead');
		const trHead = document.createElement('tr');

		const thDisc = document.createElement('th');
		thDisc.textContent = 'Disciplina';
		trHead.appendChild(thDisc);

		const thQtde = document.createElement('th');
		thQtde.textContent = 'Qtde. de Aulas';
		trHead.appendChild(thQtde);

		const thRest = document.createElement('th');
		thRest.textContent = 'Aulas Restantes';
		trHead.appendChild(thRest);

		thead.appendChild(trHead);
		table.appendChild(thead);

		const tbody = document.createElement('tbody');

		dadosTurma.serie_disciplinas.forEach(d => {
			const discId = d.id_disciplina;
			const nomeDisc = d.nome_disciplina;
			const qtde = disciplineWeeklyLimit[discId] || 0;
			const usadas = usedDisciplineCount[discId] || 0;
			const restantes = qtde - usadas;

			const tr = document.createElement('tr');
			const tdDisc = document.createElement('td');
			tdDisc.textContent = nomeDisc;
			tr.appendChild(tdDisc);

			const tdQtde = document.createElement('td');
			tdQtde.textContent = qtde;
			tr.appendChild(tdQtde);

			const tdRest = document.createElement('td');
			tdRest.textContent = restantes;
			if (restantes <= 0) {
				tdRest.style.color = 'red';
			}
			tr.appendChild(tdRest);

			tbody.appendChild(tr);
		});

		table.appendChild(tbody);
		quadroDisciplinas.appendChild(table);
	}

	function aplicarCorCelula(td, discId, profId) {
		td.style.backgroundColor = (discId && profId) ? '#D5F4DA' : '';
	}

	// =============================================
	// BOTÃO IMPRIMIR
	// =============================================
	btnImprimir.addEventListener('click', () => {
		if (!idTurmaSelecionada) {
			alert('Selecione uma turma para imprimir o horário.');
			return;
		}
		const url = `/horarios/app/views/horarios-turma.php?id_turma=${idTurmaSelecionada}&orient=Landscape`;
		window.open(url, '_blank');
	});

	// =============================================
	// UTILS
	// =============================================
	function traduzDia(dia) {
		switch (dia) {
			case 'Domingo': return 'Domingo';
			case 'Segunda': return 'Segunda';
			case 'Terca':	 return 'Terça';
			case 'Quarta':	return 'Quarta';
			case 'Quinta':	return 'Quinta';
			case 'Sexta':	 return 'Sexta';
			case 'Sabado':	return 'Sábado';
			default:		return dia;
		}
	}

	// Carrega anos ao iniciar
	loadAnosLetivos();
});
 