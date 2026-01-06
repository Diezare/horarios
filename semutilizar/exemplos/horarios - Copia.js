document.addEventListener('DOMContentLoaded', function() {

	/* ============================================
	 * ELEMENTOS
	 * ============================================ */
	const selectAnoLetivo   = document.getElementById('selectAnoLetivo');
	const selectTurma       = document.getElementById('selectTurma');
	const btnCarregar       = document.getElementById('btnCarregar');
	const btnImprimir       = document.getElementById('btnImprimir');
	const gradeContainer    = document.getElementById('grade-container');
	const quadroDisciplinas = document.getElementById('quadro-disciplinas');
	const quadroProfessores = document.getElementById('quadro-professores'); // NOVO
  
	/* ============================================
	 * VARIÁVEIS GLOBAIS
	 * ============================================ */
	let idAnoSelecionado       = null;
	let idTurmaSelecionada     = null;
	let editingEnabled         = false; // Desabilitar edição até clicar "Carregar Grade"
  
	let allHorariosDoAno       = [];   // Todos os horários do ano letivo (para checar conflitos e somatórios)
	let professorRestricoesMap = {};   // Restrições de professor [profId][dia_semana] = [lista de aulas]
	let dadosTurma             = null; // Retorno de listHorarios.php (turma atual)
	let profDiscTurmaMap       = {};   // { [id_turma]: { [id_disciplina]: [lista de id_professor] } }
	let intervalPositions      = {};   // Intervalos (ex.: { 'Segunda': [2,4] })
	let turmasMap              = {};   // Mapeia id_turma => { id_serie, nome_serie, nome_turma }
  
	/* ============================================
	 * 1) CARREGA ANOS LETIVOS
	 * ============================================ */
	function loadAnosLetivos() {
	  fetch('/horarios/app/controllers/ano-letivo/listAnoLetivo.php')
		.then(r => r.json())
		.then(resp => {
		  if (resp.status === 'success') {
			resp.data.forEach(ano => {
			  const opt = document.createElement('option');
			  opt.value = ano.id_ano_letivo;
			  opt.textContent = ano.ano;
			  selectAnoLetivo.appendChild(opt);
			});
		  }
		})
		.catch(err => console.error(err));
	}
  
	selectAnoLetivo.addEventListener('change', () => {
	  idAnoSelecionado = selectAnoLetivo.value;
	  resetTurmaSelection();
	  editingEnabled   = false; // Desabilita edição
	  if (idAnoSelecionado) {
		loadTurmasPorAno(idAnoSelecionado);
	  }
	});
  
	function resetTurmaSelection() {
	  selectTurma.innerHTML = '<option value="">-- Selecione --</option>';
	  selectTurma.disabled  = true;
	  btnCarregar.disabled  = true;
  
	  // Limpa grade e quadros
	  gradeContainer.innerHTML    = '';
	  quadroDisciplinas.innerHTML = '';
	  if (quadroProfessores) {
		quadroProfessores.innerHTML = '';
	  }
  
	  idTurmaSelecionada = null;
	  editingEnabled     = false;
	}
  
	function loadTurmasPorAno(idAno) {
	  // Ajuste: precisamos que a listTurmaPorAnoLetivo retorne `id_serie, nome_serie` etc.
	  // Ex. SELECT t.id_turma, t.id_serie, s.nome_serie, ...
	  fetch(`/horarios/app/controllers/turma/listTurmaPorAnoLetivo.php?id_ano_letivo=${idAno}`)
		.then(r => r.json())
		.then(resp => {
		  if (resp.status === 'success') {
			if (resp.data.length === 0) {
			  selectTurma.innerHTML = '<option value="">Nenhuma turma neste ano</option>';
			} else {
			  selectTurma.disabled = false;
			  btnCarregar.disabled = false;
  
			  resp.data.forEach(t => {
				// Monta <option>
				const opt = document.createElement('option');
				opt.value = t.id_turma;
				opt.textContent = `${t.nome_serie} ${t.nome_turma} - ${t.nome_turno}`;
				selectTurma.appendChild(opt);
  
				// Monta turmasMap para sabermos id_serie, etc.
				turmasMap[t.id_turma] = {
				  id_serie:   t.id_serie,
				  nome_serie: t.nome_serie,
				  nome_turma: t.nome_turma,
				  nome_turno: t.nome_turno
				};
			  });
			}
		  }
		})
		.catch(err => console.error(err));
	}
  
	// Quando trocar a turma sem clicar "Carregar Grade", limpamos a tela
	selectTurma.addEventListener('change', () => {
	  editingEnabled = false; // Bloqueia edição
	  gradeContainer.innerHTML    = '';
	  quadroDisciplinas.innerHTML = '';
	  if (quadroProfessores) {
		quadroProfessores.innerHTML = '';
	  }
	});
  
	/* ============================================
	 * 2) BOTÃO "CARREGAR GRADE"
	 * ============================================ */
	btnCarregar.addEventListener('click', () => {
	  const turmaVal = selectTurma.value;
	  if (!idAnoSelecionado) {
		alert('Selecione o Ano Letivo.');
		return;
	  }
	  if (!turmaVal) {
		alert('Selecione a Turma.');
		return;
	  }
	  idTurmaSelecionada = turmaVal;
	  editingEnabled     = true;  // Agora podemos editar a grade
  
	  carregarTudo()
		.then(() => {
		  definirIntervalos();
		  montarGrade();
		  montarQuadroDisciplinas();
		  montarQuadroProfessores(); // NOVO: Monta tabela de profs
		})
		.catch(err => console.error(err));
	});
  
	/* ============================================
	 * 3) BOTÃO "IMPRIMIR"
	 * ============================================ */
	btnImprimir.addEventListener('click', () => {
	  alert('Função imprimir ainda não implementada.');
	  // Ex.: window.print();
	});
  
	/* ============================================
	 * 4) FUNÇÃO MASTER "carregarTudo"
	 * ============================================ */
	async function carregarTudo() {
	  await loadAllHorariosDoAno(idAnoSelecionado);       // p/ checar conflitos e contagens
	  await loadProfessorRestricoes(idAnoSelecionado);    // restrições
	  await loadProfessorDisciplinaTurma(idTurmaSelecionada);
	  await loadHorariosTurma(idTurmaSelecionada);
	}
  
	// 4.1) Carrega TODOS os horários do ANO letivo
	function loadAllHorariosDoAno(idAno) {
	  return fetch(`/horarios/app/controllers/horarios/listHorariosByAnoLetivo.php?id_ano_letivo=${idAno}`)
		.then(r => r.json())
		.then(resp => {
		  if (resp.status === 'success') {
			allHorariosDoAno = resp.data; 
		  } else {
			allHorariosDoAno = [];
			throw new Error(resp.message);
		  }
		});
	}
  
	// 4.2) Carrega restrições do professor
	function loadProfessorRestricoes(idAno) {
	  return fetch(`/horarios/app/controllers/professor-restricoes/listProfessorRestricoes.php?id_ano_letivo=${idAno}`)
		.then(r => r.json())
		.then(resp => {
		  if (resp.status === 'success') {
			professorRestricoesMap = {};
			resp.data.forEach(row => {
			  const p    = row.id_professor;
			  const dia  = row.dia_semana;
			  const aula = parseInt(row.numero_aula, 10);
			  if (!professorRestricoesMap[p]) {
				professorRestricoesMap[p] = {};
			  }
			  if (!professorRestricoesMap[p][dia]) {
				professorRestricoesMap[p][dia] = [];
			  }
			  professorRestricoesMap[p][dia].push(aula);
			});
		  } else {
			throw new Error(resp.message);
		  }
		});
	}
  
	// 4.3) Carrega vínculos professor-disciplina-turma
	//     Se o endpoint aceita ?id_turma=, use; senão use ?all=1 e filtre depois.
	function loadProfessorDisciplinaTurma(idTurma) {
	  return fetch('/horarios/app/controllers/professor-disciplina-turma/listProfessorDisciplinaTurma.php?all=1')
		.then(r => r.json())
		.then(resp => {
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
		  } else {
			throw new Error(resp.message);
		  }
		});
	}
  
	// 4.4) Carrega horários da Turma específica
	function loadHorariosTurma(idTurma) {
	  return fetch(`/horarios/app/controllers/horarios/listHorarios.php?id_turma=${idTurma}`)
		.then(r => r.json())
		.then(resp => {
		  if (resp.status === 'success') {
			dadosTurma = resp.data; 
		  } else {
			throw new Error(resp.message);
		  }
		});
	}
  
	/* ============================================
	 * 5) DEFINIR INTERVALOS
	 *    Exemplo simples, usando modal "intervalos"
	 * ============================================ */
	function definirIntervalos() {
	  if (!dadosTurma || !dadosTurma.turma) return;
	  const qtdIntervalos = parseInt(dadosTurma.turma.intervalos_por_dia, 10) || 0;
  
	  if (qtdIntervalos <= 0) {
		intervalPositions = {};
		return;
	  }
  
	  // Exemplo: se for 2 intervalos, abrimos o modal com default "3,6"
	  if (qtdIntervalos === 2) {
		openIntervalosModal('3,6', (arrPos) => {
		  intervalPositions = {};
		  if (dadosTurma.turno_dias && Array.isArray(dadosTurma.turno_dias)) {
			dadosTurma.turno_dias.forEach(td => {
			  if (td.aulas_no_dia > 0) {
				intervalPositions[td.dia_semana] = arrPos.slice();
			  }
			});
		  }
		  montarGrade();
		}, () => {
		  // Cancelar => define [3,6] como default
		  intervalPositions = {};
		  if (dadosTurma.turno_dias && Array.isArray(dadosTurma.turno_dias)) {
			dadosTurma.turno_dias.forEach(td => {
			  if (td.aulas_no_dia > 0) {
				intervalPositions[td.dia_semana] = [3,6];
			  }
			});
		  }
		  montarGrade();
		});
	  }
	  else if (qtdIntervalos === 1) {
		openIntervalosModal('3', (arrPos) => {
		  intervalPositions = {};
		  dadosTurma.turno_dias.forEach(td => {
			if (td.aulas_no_dia > 0) {
			  intervalPositions[td.dia_semana] = arrPos.slice();
			}
		  });
		  montarGrade();
		}, () => {
		  intervalPositions = {};
		  dadosTurma.turno_dias.forEach(td => {
			if (td.aulas_no_dia > 0) {
			  intervalPositions[td.dia_semana] = [3];
			}
		  });
		  montarGrade();
		});
	  }
	  else {
		// Ajuste conforme quiser para 3 ou 4 intervalos
		alert(`A turma tem ${qtdIntervalos} intervalos/dia. Ajuste a lógica se quiser outro comportamento.`);
		intervalPositions = {};
	  }
	}
  
	/* ============================================
	 * 6) MONTAR GRADE (fixo: 6 aulas, 2 intervalos)
	 * ============================================ */
	function montarGrade() {
	  gradeContainer.innerHTML = '';
  
	  if (!dadosTurma || !dadosTurma.turma) {
		gradeContainer.innerHTML = '<p>Turma não encontrada.</p>';
		return;
	  }
	  if (!dadosTurma.turno_dias || dadosTurma.turno_dias.length === 0) {
		gradeContainer.innerHTML = '<p>Não há configuração de dias para este turno.</p>';
		return;
	  }
  
	  // Filtrar dias que tenham aulas
	  const diasComAulas = dadosTurma.turno_dias
		.filter(td => parseInt(td.aulas_no_dia, 10) > 0);
	  if (diasComAulas.length === 0) {
		gradeContainer.innerHTML = '<p>Nenhum dia possui aulas neste turno.</p>';
		return;
	  }
  
	  // Tabela fixada: 8 linhas: (1ª,2ª,intervalo,3ª,4ª,intervalo,5ª,6ª)
	  const linhas = [
		{ label: '1ª Aula', type: 'aula', numeroAula: 1 },
		{ label: '2ª Aula', type: 'aula', numeroAula: 2 },
		{ label: '-',       type: 'intervalo' },
		{ label: '3ª Aula', type: 'aula', numeroAula: 3 },
		{ label: '4ª Aula', type: 'aula', numeroAula: 4 },
		{ label: '-',       type: 'intervalo' },
		{ label: '5ª Aula', type: 'aula', numeroAula: 5 },
		{ label: '6ª Aula', type: 'aula', numeroAula: 6 },
	  ];
  
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
		th.textContent = traduzDia(d.dia_semana); // Terca => Terça
		trHead.appendChild(th);
	  });
	  thead.appendChild(trHead);
	  table.appendChild(thead);
  
	  // Corpo
	  const tbody = document.createElement('tbody');
	  linhas.forEach(linha => {
		const tr = document.createElement('tr');
		// Coluna "Aula"
		const tdLabel = document.createElement('td');
		tdLabel.textContent = linha.label;
		tr.appendChild(tdLabel);
  
		// Para cada dia
		diasComAulas.forEach(day => {
		  const td = document.createElement('td');
		  if (linha.type === 'intervalo') {
			td.classList.add('intervalo-cell');
			td.textContent = 'INTERVALO';
		  } else {
			const numeroAula = linha.numeroAula;
			const dayAulas   = parseInt(day.aulas_no_dia, 10);
			if (numeroAula > dayAulas) {
			  // Este dia não chega a ter ex.: 6 aulas
			  td.textContent = '';
			} else {
			  // Monta a célula c/ selects
			  montarCelulaAula(td, day.dia_semana, numeroAula);
			}
		  }
		  tr.appendChild(td);
		});
		tbody.appendChild(tr);
	  });
  
	  table.appendChild(tbody);
	  gradeContainer.appendChild(table);
	}
  
	/* ============================================
	 * 7) CRIA A CÉLULA DE AULA (Select Disc e Prof)
	 * ============================================ */
	function montarCelulaAula(td, diaSemana, numeroAula) {
	  // Se existe horário salvo
	  const horarioExistente = (dadosTurma.horarios || []).find(h =>
		h.dia_semana === diaSemana && parseInt(h.numero_aula,10) === numeroAula
	  );
  
	  // SELECT Disciplina
	  const selDisc = document.createElement('select');
	  selDisc.classList.add('select-disciplina');
	  selDisc.setAttribute('data-dia', diaSemana);
	  selDisc.setAttribute('data-aula', numeroAula);
	  selDisc.appendChild(new Option('--Disc--', ''));
  
	  // Preenche as disciplinas da série
	  (dadosTurma.serie_disciplinas || []).forEach(d => {
		const opt = new Option(d.nome_disciplina, d.id_disciplina);
		selDisc.appendChild(opt);
	  });
  
	  // SELECT Professor
	  const selProf = document.createElement('select');
	  selProf.classList.add('select-professor');
	  selProf.setAttribute('data-dia', diaSemana);
	  selProf.setAttribute('data-aula', numeroAula);
	  selProf.appendChild(new Option('--Prof--', ''));
  
	  // Se há horário salvo
	  if (horarioExistente) {
		selDisc.value = horarioExistente.id_disciplina;
		selProf.value = horarioExistente.id_professor;
	  }
  
	  // Desabilitar se not editing
	  selDisc.disabled = !editingEnabled;
	  selProf.disabled = !editingEnabled;
  
	  // Filtrar professor (caso a disciplina já exista)
	  refazerProfessores(selDisc, selProf, diaSemana, numeroAula);
  
	  // EVENTOS: Só salva quando ambos disc & prof estiverem setados
	  selDisc.addEventListener('change', () => {
		if (!editingEnabled) return;
		refazerProfessores(selDisc, selProf, diaSemana, numeroAula);
		if (selDisc.value && selProf.value) {
		  salvarOuAtualizar(diaSemana, numeroAula, selDisc.value, selProf.value);
		}
		aplicarCorCelula(td, selDisc.value, selProf.value);
	  });
	  selProf.addEventListener('change', () => {
		if (!editingEnabled) return;
		refazerDisciplinas(selDisc, selProf, diaSemana, numeroAula);
		if (selDisc.value && selProf.value) {
		  salvarOuAtualizar(diaSemana, numeroAula, selDisc.value, selProf.value);
		}
		aplicarCorCelula(td, selDisc.value, selProf.value);
	  });
  
	  td.appendChild(selDisc);
	  td.appendChild(document.createElement('br'));
	  td.appendChild(selProf);
  
	  // Cor inicial
	  aplicarCorCelula(td, selDisc.value, selProf.value);
	}
  
	/* ============================================
	 * FILTRAGENS DE DROPDOWN
	 * ============================================ */
  
	// Ao escolher DISCIPLINA, refaz PROFESSORES
	function refazerProfessores(selDisc, selProf, diaSemana, numeroAula) {
	  const discId         = parseInt(selDisc.value, 10);
	  const profSelecionado= selProf.value;
	  selProf.innerHTML    = '';
	  selProf.appendChild(new Option('--Prof--', ''));
	  if (!discId) return;
  
	  const listaProfsHabilitados = (profDiscTurmaMap[idTurmaSelecionada]
		 && profDiscTurmaMap[idTurmaSelecionada][discId])
		 ? profDiscTurmaMap[idTurmaSelecionada][discId]
		 : [];
  
	  (dadosTurma.professores || []).forEach(prof => {
		const pid = parseInt(prof.id_professor, 10);
		if (!listaProfsHabilitados.includes(pid)) {
		  return; // professor não vinculado a essa disc/turma
		}
		// Restrições
		if (professorEhRestrito(pid, diaSemana, numeroAula)) {
		  return;
		}
		// Conflito
		const conflict = professorOcupado(pid, diaSemana, numeroAula);
		let displayName = prof.nome_exibicao;
		const opt = new Option(displayName, pid);
  
		if (conflict) {
		  // Ex.: (Turma: 6º Ano A)
		  displayName = `❌ ${displayName} (Turma: ${conflict.nome_serie} ${conflict.nome_turma})`;
		  opt.text = displayName;
		  opt.disabled = true;
		}
		selProf.appendChild(opt);
	  });
  
	  // Tenta manter a seleção anterior, se ainda existir
	  const existeNaLista = Array.from(selProf.options).some(o => o.value == profSelecionado && !o.disabled);
	  selProf.value = existeNaLista ? profSelecionado : '';
	}
  
	// Ao escolher PROFESSOR, refaz DISCIPLINAS
	function refazerDisciplinas(selDisc, selProf, diaSemana, numeroAula) {
	  const profId          = parseInt(selProf.value, 10);
	  const discSelecionada = selDisc.value;
	  selDisc.innerHTML     = '';
	  selDisc.appendChild(new Option('--Disc--', ''));
	  if (!profId) return;
  
	  const mapDisc = profDiscTurmaMap[idTurmaSelecionada] || {};
	  // Pega as disciplinas cujo array de profs inclua profId
	  const disciplinasDoProf = Object.keys(mapDisc).filter(did => {
		return mapDisc[did].includes(profId);
	  }).map(x => parseInt(x, 10));
  
	  (dadosTurma.serie_disciplinas || []).forEach(d => {
		if (disciplinasDoProf.includes(d.id_disciplina)) {
		  const opt = new Option(d.nome_disciplina, d.id_disciplina);
		  selDisc.appendChild(opt);
		}
	  });
  
	  // Tenta manter a seleção anterior
	  const existeNaLista = Array.from(selDisc.options).some(o => o.value == discSelecionada);
	  selDisc.value = existeNaLista ? discSelecionada : '';
	}
  
	/* ============================================
	 * RESTRIÇÃO E CONFLITO
	 * ============================================ */
  
	function professorEhRestrito(profId, diaSemana, numeroAula) {
	  const dias = professorRestricoesMap[profId];
	  if (!dias) return false;
	  const aulasRestritas = dias[diaSemana] || [];
	  return aulasRestritas.includes(numeroAula);
	}
  
	function professorOcupado(profId, diaSemana, numeroAula) {
	  // Checa se professor já está em outra turma nesse dia/hora
	  const conflict = allHorariosDoAno.find(h =>
		h.id_professor == profId &&
		h.dia_semana   == diaSemana &&
		parseInt(h.numero_aula, 10) === numeroAula &&
		h.id_turma != idTurmaSelecionada
	  );
	  if (conflict) {
		// Já retornamos as infos vindas de listHorariosByAnoLetivo.php
		return {
		  nome_serie: conflict.nome_serie,
		  nome_turma: conflict.nome_turma
		};
	  }
	  return null;
	}
  
	/* ============================================
	 * SALVAR / ATUALIZAR / DELETAR
	 * ============================================ */
	function salvarOuAtualizar(diaSemana, numeroAula, discId, profId) {
	  if (!idTurmaSelecionada) return;
	  // Se ambos vazios => deletar
	  if (!discId && !profId) {
		deletarHorario(diaSemana, numeroAula);
		return;
	  }
	  // Se disc ou prof não preenchidos => sai
	  if (!discId || !profId) return;
  
	  // Restrições e conflito
	  if (professorEhRestrito(profId, diaSemana, numeroAula)) {
		alert("O professor está restrito neste horário.");
		// reset no select
		const selProfElem = document.querySelector(
		  `select.select-professor[data-dia="${diaSemana}"][data-aula="${numeroAula}"]`
		);
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
		const selProfElem = document.querySelector(
		  `select.select-professor[data-dia="${diaSemana}"][data-aula="${numeroAula}"]`
		);
		if (selProfElem) selProfElem.value = '';
		return;
	  }
  
	  // Já existe horário?
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
		id_turma:      idTurmaSelecionada,
		dia_semana:    diaSemana,
		numero_aula:   numeroAula,
		id_disciplina: discId,
		id_professor:  profId
	  });
	  fetch('/horarios/app/controllers/horarios/insertHorarios.php', {
		method: 'POST',
		headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
		body
	  })
	  .then(r => r.json())
	  .then(resp => {
		if (resp.status === 'success') {
		  // Atualiza local
		  dadosTurma.horarios.push(resp.data);
		  allHorariosDoAno.push({
			...resp.data,
			nome_serie: turmasMap[idTurmaSelecionada].nome_serie,
			nome_turma: turmasMap[idTurmaSelecionada].nome_turma
		  });
		} else {
		  alert(resp.message);
		}
		atualizarQuadroDisciplinas();
		atualizarQuadroProfessores(); // NOVO
	  })
	  .catch(err => console.error(err));
	}
  
	function atualizarHorario(idHorario, discId, profId) {
	  const body = new URLSearchParams({
		id_horario:    idHorario,
		id_disciplina: discId,
		id_professor:  profId
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
			h.id_professor  = profId;
		  }
		  const hh = allHorariosDoAno.find(x => x.id_horario == idHorario);
		  if (hh) {
			hh.id_disciplina = discId;
			hh.id_professor  = profId;
		  }
		} else {
		  // Se a mensagem for "Nenhuma alteração ou registro não encontrado." e incomoda, comente:
		  alert(resp.message);
		}
		atualizarQuadroDisciplinas();
		atualizarQuadroProfessores(); // NOVO
	  })
	  .catch(err => console.error(err));
	}
  
	function deletarHorario(diaSemana, numeroAula) {
	  const body = new URLSearchParams({
		id_turma:    idTurmaSelecionada,
		dia_semana:  diaSemana,
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
		  // Remove local
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
		atualizarQuadroDisciplinas();
		atualizarQuadroProfessores(); // NOVO
	  })
	  .catch(err => console.error(err));
	}
  
	/* ============================================
	 * 8) QUADRO DE DISCIPLINAS (Por série, somando A+B)
	 * ============================================ */
	function montarQuadroDisciplinas() {
	  quadroDisciplinas.innerHTML = '';
	  if (!dadosTurma || !dadosTurma.serie_disciplinas) return;
	  if (dadosTurma.serie_disciplinas.length === 0) return;
  
	  const disc = dadosTurma.serie_disciplinas;
	  const container = document.createElement('div');
	  container.classList.add('container-quadro-disciplinas');
  
	  const total     = disc.length;
	  const col1Count = Math.ceil(total / 2);
	  const col1      = disc.slice(0, col1Count);
	  const col2      = disc.slice(col1Count);
  
	  const table1 = criaTabelaDisc(col1);
	  const table2 = criaTabelaDisc(col2);
  
	  container.appendChild(table1);
	  container.appendChild(table2);
  
	  quadroDisciplinas.appendChild(container);
  
	  atualizarQuadroDisciplinas();
	}
  
	function criaTabelaDisc(lista) {
	  const table = document.createElement('table');
	  table.classList.add('quadro-disciplinas-table');
	  const thead = document.createElement('thead');
	  const trh = document.createElement('tr');
	  ['Disciplina','Qtde. Aulas','Restante'].forEach(txt => {
		const th = document.createElement('th');
		th.textContent = txt;
		trh.appendChild(th);
	  });
	  thead.appendChild(trh);
	  table.appendChild(thead);
  
	  const tbody = document.createElement('tbody');
	  lista.forEach(d => {
		const tr = document.createElement('tr');
		tr.dataset.idDisc = d.id_disciplina;
  
		const tdNome = document.createElement('td');
		tdNome.textContent = d.nome_disciplina;
		tr.appendChild(tdNome);
  
		const tdTotal = document.createElement('td');
		tdTotal.textContent = d.aulas_semana;
		tr.appendChild(tdTotal);
  
		const tdRest = document.createElement('td');
		tdRest.classList.add('restante-col');
		tdRest.textContent = '...';
		tr.appendChild(tdRest);
  
		tbody.appendChild(tr);
	  });
	  table.appendChild(tbody);
	  return table;
	}
  
	function atualizarQuadroDisciplinas() {
	  if (!dadosTurma || !dadosTurma.turma) return;
	  const idSerieAtual = dadosTurma.turma.id_serie; 
	  if (!idSerieAtual) return;
  
	  // Conta quantas aulas cada disciplina já está usando EM TODAS as turmas da MESMA série
	  const cont = {};
	  (dadosTurma.serie_disciplinas || []).forEach(d => {
		cont[d.id_disciplina] = 0;
	  });
  
	  // Percorre allHorariosDoAno e soma se a turma daquela aula pertencer a idSerieAtual
	  allHorariosDoAno.forEach(h => {
		const discId = h.id_disciplina;
		// Precisamos saber se h.id_turma -> turmasMap => id_serie === idSerieAtual
		const tm = turmasMap[h.id_turma];
		if (!tm) return;
		if (tm.id_serie == idSerieAtual && cont[discId] !== undefined) {
		  cont[discId]++; 
		}
	  });
  
	  // Atualiza a tabela
	  const tables = quadroDisciplinas.querySelectorAll('.quadro-disciplinas-table');
	  tables.forEach(tbl => {
		const rows = tbl.querySelectorAll('tbody tr');
		rows.forEach(tr => {
		  const idDisc = tr.dataset.idDisc;
		  const disc   = (dadosTurma.serie_disciplinas || []).find(x => x.id_disciplina == idDisc);
		  if (!disc) return;
  
		  const total = parseInt(disc.aulas_semana, 10);
		  const usados = cont[idDisc] || 0;
		  const resto  = total - usados;
		  const tdRest = tr.querySelector('.restante-col');
		  if (tdRest) {
			tdRest.textContent  = resto;
			tdRest.style.color  = (resto < 0) ? 'red' : '';
		  }
		});
	  });
	}
  
	/* ============================================
	 * 9) QUADRO DE PROFESSORES
	 *    (Limite de aulas => professor.limite_aulas_fixa_semana)
	 * ============================================ */
	function montarQuadroProfessores() {
	  // Se não existir o elemento, apenas ignore
	  if (!quadroProfessores) return;
	  quadroProfessores.innerHTML = '';
  
	  if (!dadosTurma || !dadosTurma.professores) return;
	  const profs = dadosTurma.professores;
	  if (profs.length === 0) return;
  
	  const table = document.createElement('table');
	  table.classList.add('quadro-prof-table');
  
	  const thead = document.createElement('thead');
	  const trh = document.createElement('tr');
	  ['Professor','Qtde. de Aulas','Restante'].forEach(txt => {
		const th = document.createElement('th');
		th.textContent = txt;
		trh.appendChild(th);
	  });
	  thead.appendChild(trh);
	  table.appendChild(thead);
  
	  const tbody = document.createElement('tbody');
	  profs.forEach(p => {
		// p.limite_aulas_fixa_semana ou professor_carga_ano
		const limite = parseInt(p.limite_aulas_fixa_semana || 0, 10);
  
		const tr = document.createElement('tr');
		tr.dataset.idProf = p.id_professor;
  
		const tdNome = document.createElement('td');
		tdNome.textContent = p.nome_exibicao;
		tr.appendChild(tdNome);
  
		const tdLimite = document.createElement('td');
		tdLimite.textContent = limite;
		tr.appendChild(tdLimite);
  
		const tdRest = document.createElement('td');
		tdRest.classList.add('restante-col');
		tdRest.textContent = '...';
		tr.appendChild(tdRest);
  
		tbody.appendChild(tr);
	  });
  
	  table.appendChild(tbody);
	  quadroProfessores.appendChild(table);
  
	  atualizarQuadroProfessores();
	}
  
	function atualizarQuadroProfessores() {
	  // Conta quantas aulas cada professor está dando no ANO
	  if (!quadroProfessores) return;
	  const tbl = quadroProfessores.querySelector('.quadro-prof-table');
	  if (!tbl) return;
  
	  // Montar cont
	  const countProf = {};
	  (dadosTurma.professores || []).forEach(p => {
		countProf[p.id_professor] = 0;
	  });
	  // Soma no allHorariosDoAno
	  allHorariosDoAno.forEach(h => {
		const pid = h.id_professor;
		if (countProf[pid] !== undefined) {
		  countProf[pid]++;
		}
	  });
  
	  // Atualiza linhas
	  const rows = tbl.querySelectorAll('tbody tr');
	  rows.forEach(tr => {
		const idProf = tr.dataset.idProf;
		const prof   = (dadosTurma.professores || []).find(x => x.id_professor == idProf);
		if (!prof) return;
  
		const limite = parseInt(prof.limite_aulas_fixa_semana || 0, 10);
		const usados = countProf[idProf] || 0;
		const resto  = limite - usados;
  
		const tdRest = tr.querySelector('.restante-col');
		if (tdRest) {
		  tdRest.textContent = resto;
		  tdRest.style.color = (resto < 0) ? 'red' : '';
		}
	  });
	}
  
	/* ============================================
	 * 10) COR DA CÉLULA QUANDO DISC+PROF PREENCHIDOS
	 * ============================================ */
	function aplicarCorCelula(td, discId, profId) {
	  if (discId && profId) {
		td.style.backgroundColor = '#D5F4DA';
	  } else {
		td.style.backgroundColor = '';
	  }
	}
  
	/* ============================================
	 * 11) FUNÇÕES DO MODAL DE INTERVALOS
	 * ============================================ */
	function openIntervalosModal(defaultValue, callbackConfirm, callbackCancel) {
	  const modal = document.getElementById('modal-intervalos');
	  if (!modal) {
		alert('Modal de intervalos não encontrado no HTML!');
		return;
	  }
	  // Campo de input
	  const input = modal.querySelector('#intervalos-input');
	  if (input) {
		input.value = defaultValue || '3,6';
	  }
  
	  modal.style.display = 'block';
  
	  const btnConf  = modal.querySelector('#btnConfirmarIntervalos');
	  const btnCanc  = modal.querySelector('#btnCancelarIntervalos');
	  const btnClose = modal.querySelector('#close-intervalos-modal');
  
	  // Remove handlers antigos (se houver)
	  btnConf.onclick  = null;
	  btnCanc.onclick  = null;
	  btnClose.onclick = null;
  
	  btnConf.onclick = () => {
		if (input) {
		  const val = input.value.trim();
		  const arr = val.split(',').map(x => parseInt(x,10)).filter(x => x>0);
		  callbackConfirm(arr);
		}
		modal.style.display = 'none';
	  };
	  btnCanc.onclick = () => {
		callbackCancel();
		modal.style.display = 'none';
	  };
	  btnClose.onclick = () => {
		callbackCancel();
		modal.style.display = 'none';
	  };
	}
  
	function closeIntervalosModal() {
	  const modal = document.getElementById('modal-intervalos');
	  if (modal) modal.style.display = 'none';
	}
  
	/* ============================================
	 * EXTRA: Traduzir "Terca" => "Terça", etc.
	 * ============================================ */
	function traduzDia(diaBanco) {
	  switch(diaBanco) {
		case 'Domingo': return 'Domingo';
		case 'Segunda': return 'Segunda';
		case 'Terca':   return 'Terça';
		case 'Quarta':  return 'Quarta';
		case 'Quinta':  return 'Quinta';
		case 'Sexta':   return 'Sexta';
		case 'Sabado':  return 'Sábado';
		default:        return diaBanco;
	  }
	}
  
	/* ============================================
	 * INICIALIZAÇÃO
	 * ============================================ */
	loadAnosLetivos();
  });
  