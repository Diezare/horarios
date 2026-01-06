document.addEventListener('DOMContentLoaded', function() {

	// =============================================
	// ELEMENTOS DA PÁGINA
	// =============================================
	const selectAnoLetivo = document.getElementById('selectAnoLetivo');
	const selectTurma		 = document.getElementById('selectTurma');
	const btnImprimir		 = document.getElementById('btnImprimir');
	const gradeContainer	= document.getElementById('grade-container');
	const modal = document.getElementById('modal-intervalos'); 
	
     // "X" de fechar
	
	// Se não houver um div para professores, cria um
	let quadroProfessores = document.getElementById('quadro-professores');
	if (!quadroProfessores) {
		const contentData = document.querySelector('.content-data');
		quadroProfessores = document.createElement('div');
		contentData.appendChild(quadroProfessores);
	}

	/* =========================================================
		ABRIR E FECHAR MODAL (Definir Intervalos)
	============================================================ */
	function openModal() {
		modal.style.display = 'block';
		modal.classList.remove('fade-out');
		modal.classList.add('fade-in');
  
		const content = modal.querySelector('.modal-content');
		content.classList.remove('slide-up');
		content.classList.add('slide-down');
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
		}, 300);
	}  
  
	// Chama o modal quando uma turma é selecionada
	selectTurma.addEventListener('change', function() {
		if (this.value !== '') {
			openModal();
		}
	});

	// =============================================
	// VARIÁVEIS GLOBAIS
	// =============================================
	let idAnoSelecionado			 = null;
	let idTurmaSelecionada		 = null;
	let editingEnabled				 = false;	// habilita edição quando a turma é selecionada

	let allHorariosDoAno			 = [];			// todos os horários do ano (para verificação de conflitos)
	let professorRestricoesMap = {};			// mapeia: professorRestricoesMap[profId][dia_semana] = [lista de aulas]
	let dadosTurma						 = null;		// dados da turma atual (retorno do listHorarios.php)
	let profDiscTurmaMap			 = {};			// mapeia: { id_turma: { id_disciplina: [lista de id_professor] } }
	let turmasMap							= {};			// mapeia id_turma => { id_serie, nome_serie, nome_turma, nome_turno }
	
	// Variáveis específicas para a lógica de intervalos e distribuição das aulas
	let intervalPositions			= [];			// ex.: [3,6] – posições onde o sistema insere o intervalo
	let extraClassMapping			= {};			// mapeia: para cada dia (ex: 'Segunda') o número de aulas permitido (de acordo com a série)

	// =============================================
	// 1) CARREGA ANOS LETIVOS
	// =============================================
	async function loadAnosLetivos() {
		try {
			let resp = await fetch('/horarios/app/controllers/ano-letivo/listAnoLetivo.php').then(r => r.json());
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
		resetTurmaSelection();
		if (idAnoSelecionado) {
			loadTurmasPorAno(idAnoSelecionado);
		}
	});

	// =============================================
	// REINICIA A SELEÇÃO DE TURMA E LIMPA A TELA
	// =============================================
	function resetTurmaSelection() {
		selectTurma.innerHTML = '<option value="">-- Selecione a Turma --</option>';
		selectTurma.disabled	= true;
		gradeContainer.innerHTML = '';
		// Removemos também o quadro de professores se necessário
		quadroProfessores.innerHTML = '';
		idTurmaSelecionada = null;
		editingEnabled		 = false;
		dadosTurma				 = null;
		allHorariosDoAno	 = [];
		professorRestricoesMap = {};
		profDiscTurmaMap	 = {};
		intervalPositions	= [];
		extraClassMapping	= {};
	}

	// =============================================
	// CARREGA TURMAS PARA O ANO LETIVO SELECIONADO
	// =============================================
	async function loadTurmasPorAno(idAno) {
		try {
			let resp = await fetch(`/horarios/app/controllers/turma/listTurmaPorAnoLetivo.php?id_ano_letivo=${idAno}`).then(r => r.json());
			if (resp.status === 'success') {
				if (resp.data.length === 0) {
					selectTurma.innerHTML = '<option value="">Nenhuma turma neste ano</option>';
				} else {
					selectTurma.disabled = false;
					resp.data.forEach(t => {
						const opt = document.createElement('option');
						opt.value = t.id_turma;
						opt.textContent = `${t.nome_serie} ${t.nome_turma} - ${t.nome_turno}`;
						selectTurma.appendChild(opt);
						turmasMap[t.id_turma] = {
							id_serie:	 t.id_serie,
							nome_serie: t.nome_serie,
							nome_turma: t.nome_turma,
							nome_turno: t.nome_turno
						};
					});
				}
			}
		} catch (err) {
			console.error(err);
		}
	}

	// =============================================
	// AO ALTERAR A TURMA, CARREGA A GRADE AUTOMATICAMENTE
	// =============================================
	selectTurma.addEventListener('change', async () => {
		const turmaVal = selectTurma.value;
		if (!turmaVal) {
			gradeContainer.innerHTML = '';
			quadroProfessores.innerHTML = '';
			idTurmaSelecionada = null;
			dadosTurma = null;
			return;
		}
		idTurmaSelecionada = turmaVal;
		editingEnabled = true;

		try {
			await carregarTudo();
			definirIntervalos();			// modal para intervalos (conforme configuração da turma)
			await definirExtraAulas();	// modal para definir o dia com aula extra (se necessário)
			montarGrade();
			// Se desejar, aqui você pode chamar funções para atualizar outros quadros, como o de professores.
		} catch (err) {
			console.error(err);
		}
	});

	// =============================================
	// FUNÇÃO MASTER – CARREGA TODOS OS DADOS NECESSÁRIOS
	// =============================================
	async function carregarTudo() {
		await loadAllHorariosDoAno(idAnoSelecionado);
		await loadProfessorRestricoes(idAnoSelecionado);
		await loadProfessorDisciplinaTurma();
		await loadHorariosTurma(idTurmaSelecionada);
	}

	async function loadAllHorariosDoAno(idAno) {
		try {
			let resp = await fetch(`/horarios/app/controllers/horarios/listHorariosByAnoLetivo.php?id_ano_letivo=${idAno}`).then(r => r.json());
			if (resp.status === 'success') {
				allHorariosDoAno = resp.data;
			} else {
				allHorariosDoAno = [];
				throw new Error(resp.message);
			}
		} catch (err) {
			console.error(err);
		}
	}

	async function loadProfessorRestricoes(idAno) {
		try {
			let resp = await fetch(`/horarios/app/controllers/professor-restricoes/listProfessorRestricoes.php?id_ano_letivo=${idAno}`).then(r => r.json());
			if (resp.status === 'success') {
				professorRestricoesMap = {};
				resp.data.forEach(row => {
					const p		= row.id_professor;
					const dia	= row.dia_semana;
					const aula = parseInt(row.numero_aula, 10);
					if (!professorRestricoesMap[p]) professorRestricoesMap[p] = {};
					if (!professorRestricoesMap[p][dia]) professorRestricoesMap[p][dia] = [];
					professorRestricoesMap[p][dia].push(aula);
				});
			} else {
				throw new Error(resp.message);
			}
		} catch (err) {
			console.error(err);
		}
	}

	async function loadProfessorDisciplinaTurma() {
		try {
			let resp = await fetch('/horarios/app/controllers/professor-disciplina-turma/listProfessorDisciplinaTurma.php?all=1').then(r => r.json());
			if (resp.status === 'success') {
				profDiscTurmaMap = {};
				resp.data.forEach(row => {
					const t = row.id_turma;
					const d = row.id_disciplina;
					const p = row.id_professor;
					if (!profDiscTurmaMap[t]) profDiscTurmaMap[t] = {};
					if (!profDiscTurmaMap[t][d]) profDiscTurmaMap[t][d] = [];
					profDiscTurmaMap[t][d].push(parseInt(p, 10));
				});
			} else {
				throw new Error(resp.message);
			}
		} catch (err) {
			console.error(err);
		}
	}

	async function loadHorariosTurma(idTurma) {
		try {
			let resp = await fetch(`/horarios/app/controllers/horarios/listHorarios.php?id_turma=${idTurma}`).then(r => r.json());
			console.log("Retorno do listHorarios.php:", resp);
			if (resp.status === 'success') {
				dadosTurma = resp.data;
			} else {
				throw new Error(resp.message);
			}
		} catch (err) {
			console.error(err);
		}
	}

	// =============================================
	// MODAL DE INTERVALOS – CONFIGURA POSIÇÕES (ex: [3,6])
	// =============================================
	function definirIntervalos() {
		if (!dadosTurma || !dadosTurma.turma) return;
		const qtdIntervalos = parseInt(dadosTurma.turma.intervalos_por_dia, 10) || 0;
		if (qtdIntervalos <= 0) {
			intervalPositions = [];
			return;
		}
		if (qtdIntervalos === 2) {
			// O modal exibe "3,6". Converte: subtrai 1 do primeiro valor e 2 do segundo,
			// de modo que "3,6" passe a ser [2,4]:
			openIntervalosModal('3,6', (arrPos) => {
				intervalPositions = arrPos.map((x, i) => x - (i + 1));
				montarGrade();
			}, () => {
				intervalPositions = [2, 4];
				montarGrade();
			});
		} else if (qtdIntervalos === 1) {
			openIntervalosModal('3', (arrPos) => {
				intervalPositions = arrPos.map((x, i) => x - (i + 1));
				montarGrade();
			}, () => {
				intervalPositions = [2];
				montarGrade();
			});
		} else {
			alert(`A turma tem ${qtdIntervalos} intervalos/dia. Ajuste a lógica se necessário.`);
			intervalPositions = [];
		}
	}

	// =============================================
	// MODAL PARA DEFINIR EXTRA AULA – CASO O TOTAL SEMANAL NÃO DIVIDA IGUALMENTE
	// =============================================
	async function definirExtraAulas() {
		if (!dadosTurma || !dadosTurma.turma) return;
		const totalSeriesAulas = parseInt(dadosTurma.turma.total_aulas_semana, 10);
		if (!totalSeriesAulas) return;
		const diasComAulas = (dadosTurma.turno_dias || []).filter(td => parseInt(td.aulas_no_dia, 10) > 0);
		const numDias = diasComAulas.length;
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

	// =============================================
	// GERA AS LINHAS DA TABELA (INTERCALANDO AULAS E INTERVALOS)
	// =============================================
	function generateTableRows(maxLessonSlots, intervalPositions) {
		let rows = [];
		// Para cada aula, insere a aula e, se for o momento, insere o intervalo logo em seguida
		for (let aula = 1; aula <= maxLessonSlots; aula++) {
			rows.push({ type: 'aula', numeroAula: aula, label: `${aula}ª Aula` });
			if (intervalPositions.includes(aula)) {
				rows.push({ type: 'interval', label: 'INTERVALO' });
			}
		}
		return rows;
	}

	// =============================================
	// MONTA A GRADE DE HORÁRIOS
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
		const tableRows = generateTableRows(maxAulasTurno, intervalPositions);

		const table = document.createElement('table');
		table.classList.add('tabela-horarios');

		// Cabeçalho: coluna fixa para "Aula" e depois os dias da semana
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

		// Corpo: para cada linha (aula ou intervalo)
		const tbody = document.createElement('tbody');
		tableRows.forEach(row => {
			const tr = document.createElement('tr');
			const tdLabel = document.createElement('td');
			tdLabel.textContent = row.label;
			tr.appendChild(tdLabel);
			diasComAulas.forEach(d => {
				const td = document.createElement('td');
				const allowedLessons = extraClassMapping[d.dia_semana] || parseInt(d.aulas_no_dia, 10);
				if (row.type === 'interval') {
					td.classList.add('intervalo-cell');
					td.textContent = 'INTERVALO';
				} else if (row.type === 'aula') {
					if (row.numeroAula > parseInt(d.aulas_no_dia, 10)) {
						td.textContent = '';
					} else if (row.numeroAula > allowedLessons) {
						td.textContent = 'X';
						td.style.backgroundColor = '#f8d7da';
					} else {
						montarCelulaAula(td, d.dia_semana, row.numeroAula);
					}
				}
				tr.appendChild(td);
			});
			tbody.appendChild(tr);
		});
		table.appendChild(tbody);
		gradeContainer.appendChild(table);
	}

	// =============================================
	// CRIA A CÉLULA DE AULA (com selects para disciplina e professor)
	// =============================================
	function montarCelulaAula(td, diaSemana, numeroAula) {
		const horarioExistente = (dadosTurma.horarios || []).find(h =>
			h.dia_semana === diaSemana && parseInt(h.numero_aula, 10) === numeroAula
		);
		const selDisc = document.createElement('select');
		selDisc.classList.add('select-disciplina');
		selDisc.setAttribute('data-dia', diaSemana);
		selDisc.setAttribute('data-aula', numeroAula);
		selDisc.appendChild(new Option('--Disc--', ''));
		(dadosTurma.serie_disciplinas || []).forEach(d => {
			const opt = new Option(d.nome_disciplina, d.id_disciplina);
			selDisc.appendChild(opt);
		});
		const selProf = document.createElement('select');
		selProf.classList.add('select-professor');
		selProf.setAttribute('data-dia', diaSemana);
		selProf.setAttribute('data-aula', numeroAula);
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
			refazerProfessores(selDisc, selProf, diaSemana, numeroAula);
			if (selDisc.value && selProf.value) {
				salvarOuAtualizar(diaSemana, numeroAula, selDisc.value, selProf.value);
			} else if (!selDisc.value && !selProf.value) {
				deletarHorario(diaSemana, numeroAula);
			}
			aplicarCorCelula(td, selDisc.value, selProf.value);
		});
		
		selProf.addEventListener('change', () => {
			if (!editingEnabled) return;
			refazerDisciplinas(selDisc, selProf, diaSemana, numeroAula);
			if (selDisc.value && selProf.value) {
				salvarOuAtualizar(diaSemana, numeroAula, selDisc.value, selProf.value);
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

	// =============================================
	// FILTRAGEM DOS DROP DOWNS – PROFESSORES e DISCIPLINAS
	// =============================================
	// Função auxiliar: retorna os IDs dos professores vinculados à turma
	function getProfessoresVinculadosTurma() {
		const linkedProf = new Set();
		if (profDiscTurmaMap[idTurmaSelecionada]) {
			Object.values(profDiscTurmaMap[idTurmaSelecionada]).forEach(arr => {
				arr.forEach(pid => linkedProf.add(pid));
			});
		}
		return linkedProf;
	}

	// Filtra somente os professores vinculados à turma
	function refazerProfessores(selDisc, selProf, diaSemana, numeroAula) {
		const profSelecionado = selProf.value;
		selProf.innerHTML = '';
		selProf.appendChild(new Option('--Prof--', ''));
		const linkedProfSet = getProfessoresVinculadosTurma();
		(dadosTurma.professores || []).forEach(prof => {
			const pid = parseInt(prof.id_professor, 10);
			if (!linkedProfSet.has(pid)) return;
			if (professorEhRestrito(pid, diaSemana, numeroAula)) return;
			const conflict = professorOcupado(pid, diaSemana, numeroAula);
			let displayName = prof.nome_exibicao || 'Professor ' + pid;
			const opt = new Option(displayName, pid);
			if (conflict) {
				displayName = `❌ ${displayName} (Turma: ${conflict.nome_serie} ${conflict.nome_turma})`;
				opt.text = displayName;
				opt.disabled = true;
			}
			selProf.appendChild(opt);
		});
		selProf.value = profSelecionado;
	}

	function refazerDisciplinas(selDisc, selProf, diaSemana, numeroAula) {
		const profId = parseInt(selProf.value, 10) || 0;
		const discSelecionada = selDisc.value;
		selDisc.innerHTML = '';
		selDisc.appendChild(new Option('--Disc--', ''));
		if (profId && profDiscTurmaMap[idTurmaSelecionada]) {
			const mapDisc = profDiscTurmaMap[idTurmaSelecionada] || {};
			const disciplinasDoProf = Object.keys(mapDisc)
				.filter(did => mapDisc[did].includes(profId))
				.map(x => parseInt(x, 10));
			(dadosTurma.serie_disciplinas || []).forEach(d => {
				if (disciplinasDoProf.includes(d.id_disciplina)) {
					const opt = new Option(d.nome_disciplina, d.id_disciplina);
					selDisc.appendChild(opt);
				}
			});
		} else {
			(dadosTurma.serie_disciplinas || []).forEach(d => {
				const opt = new Option(d.nome_disciplina, d.id_disciplina);
				selDisc.appendChild(opt);
			});
		}
		const existeNaLista = Array.from(selDisc.options).some(o => String(o.value) === String(discSelecionada));
		selDisc.value = existeNaLista ? discSelecionada : '';
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
			h.dia_semana	 == diaSemana &&
			parseInt(h.numero_aula, 10) === numeroAula &&
			h.id_turma != idTurmaSelecionada
		);
		if (conflict) {
			return {
				nome_serie: conflict.nome_serie,
				nome_turma: conflict.nome_turma
			};
		}
		return null;
	}

	// =============================================
	// SALVAR / ATUALIZAR / DELETAR HORÁRIOS
	// =============================================
	function salvarOuAtualizar(diaSemana, numeroAula, discId, profId) {
		if (!idTurmaSelecionada) return;
		if (!discId && !profId) {
			deletarHorario(diaSemana, numeroAula);
			return;
		}
		if (!discId || !profId) return;
		if (professorEhRestrito(profId, diaSemana, numeroAula)) {
			alert("O professor está restrito neste horário.");
			const selProfElem = document.querySelector(`select.select-professor[data-dia="${diaSemana}"][data-aula="${numeroAula}"]`);
			if (selProfElem) {
				selProfElem.value = '';
				refazerProfessores(
					document.querySelector(`select.select-disciplina[data-dia="${diaSemana}"][data-aula="${numeroAula}"]`),
					selProfElem, diaSemana, numeroAula
				);
			}
			return;
		}
		const conflict = professorOcupado(profId, diaSemana, numeroAula);
		if (conflict) {
			alert(`O professor já está ocupado no mesmo horário (Turma: ${conflict.nome_serie} ${conflict.nome_turma}).`);
			const selProfElem = document.querySelector(`select.select-professor[data-dia="${diaSemana}"][data-aula="${numeroAula}"]`);
			if (selProfElem) selProfElem.value = '';
			return;
		}
		const h = (dadosTurma.horarios || []).find(x =>
			x.dia_semana === diaSemana && parseInt(x.numero_aula,10) === numeroAula
		);
		if (!h) {
			inserirHorario(diaSemana, numeroAula, discId, profId);
		} else {
			atualizarHorario(h.id_horario, discId, profId);
		}
	}

	function inserirHorario(diaSemana, numeroAula, discId, profId) {
		const body = new URLSearchParams({
			id_turma:			idTurmaSelecionada,
			dia_semana:		diaSemana,
			numero_aula:	 numeroAula,
			id_disciplina: discId,
			id_professor:	profId
		});
		fetch('/horarios/app/controllers/horarios/insertHorarios.php', {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body
		})
		.then(r => r.json())
		.then(resp => {
			if (resp.status === 'success') {
				dadosTurma.horarios.push(resp.data);
				allHorariosDoAno.push({
					...resp.data,
					nome_serie: turmasMap[idTurmaSelecionada].nome_serie,
					nome_turma: turmasMap[idTurmaSelecionada].nome_turma
				});
			} else {
				alert(resp.message);
			}
			atualizarQuadroProfessores();
		})
		.catch(err => console.error(err));
	}

	function atualizarHorario(idHorario, discId, profId) {
		const body = new URLSearchParams({
			id_horario:		idHorario,
			id_disciplina: discId,
			id_professor:	profId
		});
		fetch('/horarios/app/controllers/horarios/updateHorarios.php', {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body
		})
		.then(r => r.json())
		.then(resp => {
			if (resp.status === 'success') {
				const h = (dadosTurma.horarios || []).find(x => x.id_horario == idHorario);
				if (h) {
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
			atualizarQuadroProfessores();
		})
		.catch(err => console.error(err));
	}

	function deletarHorario(diaSemana, numeroAula) {
		const body = new URLSearchParams({
			id_turma:		idTurmaSelecionada,
			dia_semana:	diaSemana,
			numero_aula: numeroAula
		});
		fetch('/horarios/app/controllers/horarios/deleteHorarios.php', {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body
		})
		.then(r => r.json())
		.then(resp => {
			if (resp.status === 'success') {
				dadosTurma.horarios = (dadosTurma.horarios || []).filter(x =>
					x.dia_semana !== diaSemana || parseInt(x.numero_aula) !== numeroAula
				);
				allHorariosDoAno = allHorariosDoAno.filter(x => x.id_horario != resp.id_horario);
			} else {
				if (resp.message === 'Horário não encontrado.') {
					console.log('Nenhum registro no banco, mas segue normalmente...');
					dadosTurma.horarios = (dadosTurma.horarios || []).filter(x =>
						x.dia_semana !== diaSemana || parseInt(x.numero_aula) !== numeroAula
					);
					allHorariosDoAno = allHorariosDoAno.filter(x =>
						x.id_turma != idTurmaSelecionada ||
						x.dia_semana !== diaSemana ||
						parseInt(x.numero_aula) !== numeroAula
					);
				} else {
					alert(resp.message);
				}
			}
			atualizarQuadroProfessores();
			refazerProfessores(
				document.querySelector(`select.select-disciplina[data-dia="${diaSemana}"][data-aula="${numeroAula}"]`),
				document.querySelector(`select.select-professor[data-dia="${diaSemana}"][data-aula="${numeroAula}"]`),
				diaSemana,
				numeroAula
			);
		})
		.catch(err => console.error(err));
	}

	// =============================================
	// QUADRO DE PROFESSORES (opcional)
	// =============================================
	function atualizarQuadroProfessores() {
		const tbl = quadroProfessores.querySelector('.quadro-prof-table');
		if (!tbl) return;
		const idSerieAtual = dadosTurma.turma.id_serie;
		if (!idSerieAtual) return;
		const countProf = {};
		(dadosTurma.professores || []).forEach(p => {
			countProf[p.id_professor] = 0;
		});
		allHorariosDoAno.forEach(h => {
			const pid = h.id_professor;
			const tm = turmasMap[h.id_turma];
			if (!tm) return;
			if (tm.id_serie == idSerieAtual && countProf[pid] !== undefined) {
				countProf[pid]++;
			}
		});
		const rows = tbl.querySelectorAll('tbody tr');
		rows.forEach(tr => {
			const idProf = tr.dataset.idProf;
			const prof = (dadosTurma.professores || []).find(x => x.id_professor == idProf);
			if (!prof) return;
			const limite = parseInt(prof.limite_aulas_fixa_semana || 0, 10);
			const usados = countProf[idProf] || 0;
			const resto = limite - usados;
			const tdRest = tr.querySelector('.restante-col');
			if (tdRest) {
				tdRest.textContent = resto;
				tdRest.style.color = (resto < 0) ? 'red' : '';
			}
		});
	}

	function aplicarCorCelula(td, discId, profId) {
		td.style.backgroundColor = (discId && profId) ? '#D5F4DA' : '';
	}

	// =============================================
	// MODAIS
	// ---------------------------------------------
	// Modal de intervalos (já existente)
	// =============================================
	function openIntervalosModal(defaultValue, callbackConfirm, callbackCancel) {
		const modal = document.getElementById('modal-intervalos');
		if (!modal) {
			alert('Modal de intervalos não encontrado no HTML!');
			return;
		}
		const input = modal.querySelector('#intervalos-input');
		if (input) {
			input.value = defaultValue || '3,6';
		}
		modal.style.display = 'block';
		const btnConf	= modal.querySelector('#btnConfirmarIntervalos');
		const btnCanc	= modal.querySelector('#btnCancelarIntervalos');
		const btnClose = modal.querySelector('#close-intervalos-modal');
		btnConf.onclick	= () => {
			if (input) {
				const val = input.value.trim();
				const arr = val.split(',').map(x => parseInt(x,10)).filter(x => x>0);
				callbackConfirm(arr);
			}
			//modal.style.display = 'none';
			closeModal();
		};
		btnCanc.onclick = () => {
			callbackCancel();
			//modal.style.display = 'none';
			closeModal();
		};
		btnClose.onclick = () => {
			callbackCancel();
			//modal.style.display = 'none';
			closeModal();
		};
	}

	// =============================================
	// Modal para definir a aula extra (deve existir no HTML com id "modal-extra")
	// =============================================
	function openExtraClassModal(dias, callback) {
		return new Promise((resolve) => {
			const modal = document.getElementById('modal-extra');
			if (!modal) {
				alert('Modal para extra aula não encontrado no HTML!');
				resolve();
				return;
			}
			const container = modal.querySelector('.modal-content-extra');
			container.innerHTML = '<h2>Selecione o dia com aula extra</h2>';
			dias.forEach(td => {
				const label = document.createElement('label');
				const checkbox = document.createElement('input');
				checkbox.type = 'radio';
				checkbox.name = 'extraDia';
				checkbox.value = td.dia_semana;
				label.appendChild(checkbox);
				label.appendChild(document.createTextNode(traduzDia(td.dia_semana)));
				container.appendChild(label);
				container.appendChild(document.createElement('br'));
			});
			modal.style.display = 'block';
			const btnConf = modal.querySelector('#btnConfirmarExtra');
			const btnCanc = modal.querySelector('#btnCancelarExtra');
			btnConf.onclick = () => {
				const selected = modal.querySelector('input[name="extraDia"]:checked');
				if (selected) {
					callback(selected.value);
				}
				//modal.style.display = 'none';
				closeModal();
				resolve();
			};
			btnCanc.onclick = () => {
				//modal.style.display = 'none';
				closeModal();
				resolve();
			};
		});
	}

	btnImprimir.addEventListener('click', () => {
		alert('Função imprimir ainda não implementada.');
		// Exemplo: window.print();
	});

	// =============================================
	// UTILITÁRIOS
	// =============================================
	function traduzDia(diaBanco) {
		switch(diaBanco) {
			case 'Domingo': return 'Domingo';
			case 'Segunda': return 'Segunda';
			case 'Terca':	 return 'Terça';
			case 'Quarta':	return 'Quarta';
			case 'Quinta':	return 'Quinta';
			case 'Sexta':	 return 'Sexta';
			case 'Sabado':	return 'Sábado';
			default:				return diaBanco;
		}
	}

	// Inicia o carregamento dos anos letivos
	loadAnosLetivos();
	
});

document.addEventListener('DOMContentLoaded', function() {
	const intervalosInput = document.getElementById('intervalos-input');
	intervalosInput.addEventListener('input', function() {
		// Remove todos os caracteres que não sejam dígito ou vírgula
		let value = this.value.replace(/[^0-9,]/g, '');
	  
		// Se houver mais de uma vírgula, mantém somente a primeira
		const commaCount = (value.match(/,/g) || []).length;
		if (commaCount > 1) {
			const firstCommaIndex = value.indexOf(',');
			value = value.slice(0, firstCommaIndex + 1) +
				value.slice(firstCommaIndex + 1).replace(/,/g, '');
		}
	  
		// Se não houver vírgula e o usuário digitar 2 números, insere a vírgula no meio
		if (value.length === 2 && value.indexOf(',') === -1) {
			value = value[0] + ',' + value[1];
		}
	  
		// Limita o valor a 3 caracteres
		value = value.substring(0, 3);
	  
		// Se o valor tiver 3 caracteres, garanta que esteja no formato: dígito, vírgula, dígito
		if (value.length === 3 && !/^\d,\d$/.test(value)) {
			// Se o primeiro caractere não for dígito, remove-o
			if (!/\d/.test(value[0])) {
				value = value.substring(1);
		}
		// Se o segundo caractere não for vírgula, força a vírgula
		if (value.length >= 2 && value[1] !== ',') {
			value = value[0] + ',' + (value[2] || '');
		}
		// Se o terceiro caractere não for dígito, remove-o
		if (value.length === 3 && !/\d/.test(value[2])) {
			value = value.substring(0, 2);
		}
	} 
		this.value = value;
	});
});
  
