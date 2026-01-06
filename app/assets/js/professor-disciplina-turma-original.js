// app/assets/js/professor-disciplina-turma.js

let listenersBoundProfDiscTurma = false;

// helpers
const $	= (s) => document.querySelector(s);
const $$ = (s) => document.querySelectorAll(s);

const IDS = {
	modal: '#modal-professor-disciplina-turma',
	modalContent: '#modal-professor-disciplina-turma .modal-content',
	profNome: '#prof-dt-nome',
	profId: '#prof-dt-id',
	selDisciplina: '#sel-disciplina',
	selNivel: '#sel-nivel-ensino',
	selSerie: '#filtro-serie',
	areaTurmas: '#turmas-dt-checkboxes',
	btnClose: '#close-professor-disciplina-turma-modal',
	btnCancel: '#cancel-professor-disciplina-turma-btn',
	btnSave: '#save-professor-disciplina-turma-btn'
};

function buildUrl(base, params) {
	const qs = Object.entries(params || {})
		.filter(([, v]) => v !== undefined && v !== null && String(v) !== '')
		.map(([k, v]) => `${encodeURIComponent(k)}=${encodeURIComponent(v)}`)
		.join('&');
	return qs ? `${base}?${qs}` : base;
}

// pega id_turma dos objetos retornados pelos endpoints de turma
function getTurmaId(obj) {
	return obj?.id_turma ?? obj?.id;
}

/* ---------------- Modal ---------------- */

function openProfessorDisciplinaTurmaModal(professorId, professorName) {
	const modal = $(IDS.modal);
	modal.style.display = 'block';
	modal.classList.remove('fade-out');

	const content = $(IDS.modalContent);
	content.classList.remove('slide-up');
	content.classList.add('slide-down');

	$(IDS.profNome).value = professorName;
	$(IDS.profId).value	 = professorId;

	$(IDS.selDisciplina).innerHTML = '<option value="">-- Selecione a Disciplina --</option>';
	$(IDS.selNivel).innerHTML			= '<option value="">-- Selecione o Nível --</option>';
	$(IDS.selSerie).innerHTML			= `
		<option value="">-- Selecione uma série --</option>
		<option value="all">-- Todas as séries --</option>
	`;
	// gating
	$(IDS.selNivel).disabled = true;
	$(IDS.selSerie).disabled = true;
	limpaAreaTurmas('Selecione a disciplina.');

	loadDisciplinaProfessor(professorId);
	loadNivelEnsinoOptions();

	if (!listenersBoundProfDiscTurma) {
		$(IDS.selDisciplina).addEventListener('change', onDisciplinaChange);
		$(IDS.selNivel).addEventListener('change', onNivelEnsinoChange);
		$(IDS.selSerie).addEventListener('change', atualizarTurmasFiltradas);
		$(IDS.btnClose).addEventListener('click', closeProfessorDisciplinaTurmaModal);
		$(IDS.btnCancel).addEventListener('click', closeProfessorDisciplinaTurmaModal);
		$(IDS.btnSave).addEventListener('click', salvarVinculo);
		listenersBoundProfDiscTurma = true;
	}
}

function closeProfessorDisciplinaTurmaModal() {
	const modal = $(IDS.modal);
	const content = $(IDS.modalContent);
	content.classList.remove('slide-down');
	content.classList.add('slide-up');
	modal.classList.add('fade-out');
	setTimeout(() => {
		modal.style.display = 'none';
		content.classList.remove('slide-up');
		modal.classList.remove('fade-out');
	}, 300);
}

/* --------------- Loaders --------------- */

function loadDisciplinaProfessor(professorId) {
	fetch(`/horarios/app/controllers/professor-disciplina/listProfessorDisciplina.php?id_professor=${professorId}`)
		.then(r => r.json())
		.then(data => {
			if (data.status !== 'success') return;
			const sel = $(IDS.selDisciplina);
			sel.innerHTML = '<option value="">-- Selecione a Disciplina --</option>';
			(data.data || []).forEach(d => {
				const opt = document.createElement('option');
				opt.value = d.id_disciplina;
				opt.textContent = d.nome_disciplina;
				sel.appendChild(opt);
			});
		})
		.catch(console.error);
}

function loadNivelEnsinoOptions() {
	fetch('/horarios/app/controllers/nivel-ensino/listNivelEnsino.php')
		.then(r => r.json())
		.then(data => {
			if (data.status !== 'success') return;
			const selNivel = $(IDS.selNivel);
			selNivel.innerHTML = '<option value="">-- Selecione o Nível --</option>';
			(data.data || []).forEach(nivel => {
				const opt = document.createElement('option');
				opt.value = nivel.id_nivel_ensino;
				opt.textContent = nivel.nome_nivel_ensino;
				selNivel.appendChild(opt);
			});
		})
		.catch(console.error);
}

/* ------------- Handlers (gating) ------------- */

function onDisciplinaChange() {
	const disciplinaId = $(IDS.selDisciplina).value;

	if (!disciplinaId) {
		$(IDS.selNivel).disabled = true;
		$(IDS.selSerie).disabled = true;
		$(IDS.selNivel).value = '';
		$(IDS.selSerie).value = '';
		limpaAreaTurmas('Selecione a disciplina.');
		return;
	}

	$(IDS.selNivel).disabled = false;
	$(IDS.selSerie).disabled = false;

	if ($(IDS.selNivel).value) {
		atualizarTurmasFiltradas();
	} else {
		limpaAreaTurmas('Selecione o nível para ver as turmas.');
	}
}

async function onNivelEnsinoChange() {
	const nivelId = $(IDS.selNivel).value;
	const filtroSerie = $(IDS.selSerie);

	filtroSerie.innerHTML = `
		<option value="">-- Selecione uma série --</option>
		<option value="all">-- Todas as séries --</option>
	`;

	if (!nivelId) { limpaAreaTurmas('Selecione o nível para ver as turmas.'); return; }

	try {
		const urlSeries = buildUrl('/horarios/app/controllers/serie/listSerieByNivel.php', { id_nivel: nivelId });
		const data = await fetch(urlSeries).then(r => r.json());
		if (data.status === 'success') {
			(data.data || []).forEach(serie => {
				const opt = document.createElement('option');
				opt.value = serie.id_serie;
				opt.textContent = serie.nome_serie;
				filtroSerie.appendChild(opt);
			});
		}
	} catch (e) { console.error(e); }

	filtroSerie.value = 'all';
	atualizarTurmasFiltradas();
}

/* ---------------- Core ---------------- */

// agrega turmas de todas as séries do nível
async function fetchTurmasTodasSeries(nivelId) {
	const urlSeries = buildUrl('/horarios/app/controllers/serie/listSerieByNivel.php', { id_nivel: nivelId });
	const seriesResp = await fetch(urlSeries).then(r => r.json());
	if (seriesResp.status !== 'success') return [];

	const series = seriesResp.data || [];
	if (series.length === 0) return [];

	const promises = series.map(s => {
		const urlTurmas = buildUrl('/horarios/app/controllers/turma/listTurmaBySerie.php', { id_serie: s.id_serie });
		return fetch(urlTurmas).then(r => r.json()).catch(() => ({ status: 'error' }));
	});

	const results = await Promise.all(promises);

	const all = [];
	const seen = new Set();
	results.forEach(res => {
		if (res && res.status === 'success' && Array.isArray(res.data)) {
			res.data.forEach(t => {
				const id = getTurmaId(t);
				if (id != null && !seen.has(Number(id))) {
					seen.add(Number(id));
					all.push(t);
				}
			});
		}
	});

	return all;
}

/**
 * Busca vínculos do professor+disciplina.
 * Se serieId for fornecido, manda id_serie (o PHP suporta).
 * Retorna Set<number> com ids de turma vinculados.
 */
async function fetchVinculosSet(professorId, disciplinaId, serieId) {
	const params = { id_professor: professorId, id_disciplina: disciplinaId };
	if (serieId) params.id_serie = serieId;

	const url = buildUrl('/horarios/app/controllers/professor-disciplina-turma/listProfessorDisciplinaTurma.php', params);
	const resp = await fetch(url).then(r => r.json());

	const set = new Set();
	const data = resp?.data ?? resp;
	if (Array.isArray(data)) {
		data.forEach(row => {
			const v = Number(row?.id_turma);
			if (!Number.isNaN(v)) set.add(v);
		});
	}
	return set;
}

// atualiza turmas — disciplina e nível já preenchidos
async function atualizarTurmasFiltradas() {
	const professorId	= $(IDS.profId).value;
	const disciplinaId = $(IDS.selDisciplina).value;
	const nivelId			= $(IDS.selNivel).value;
	const serieRaw		 = $(IDS.selSerie).value; // "", "all" ou id

	if (!disciplinaId) return;
	if (!nivelId) { limpaAreaTurmas('Selecione o nível para ver as turmas.'); return; }

	try {
		// 1) vínculos primeiro (id_turma)
		const vincSet = await fetchVinculosSet(professorId, disciplinaId, (serieRaw && serieRaw !== 'all') ? serieRaw : '');

		// 2) carrega turmas conforme filtro
		let turmasData = [];
		if (serieRaw === 'all') {
			turmasData = await fetchTurmasTodasSeries(nivelId);
		} else if (serieRaw === '') {
			limpaAreaTurmas('Selecione uma série ou escolha "Todas as séries".');
			return;
		} else {
			const url = buildUrl('/horarios/app/controllers/turma/listTurmaBySerie.php', { id_serie: serieRaw });
			const turmasResp = await fetch(url).then(r => r.json());
			if (turmasResp.status === 'success') turmasData = turmasResp.data || [];
		}

		if (!turmasData.length) { limpaAreaTurmas('Nenhuma turma encontrada para o filtro.'); return; }

		// 3) render + marca
		renderTurmas(turmasData, vincSet);
	} catch (e) {
		console.error(e);
		limpaAreaTurmas('Erro ao carregar turmas.');
	}
}

/* -------------- Render -------------- */

function limpaAreaTurmas(msg = '') {
	const container = $(IDS.areaTurmas);
	container.innerHTML = '';
	if (msg) {
		const p = document.createElement('p');
		//p.className = 'muted';
		p.className = 'muted hint';
		p.textContent = msg;
		container.appendChild(p);
	}
}

function renderTurmas(turmasData, vincSet) {
	const container = $(IDS.areaTurmas);
	container.innerHTML = '';

	turmasData.forEach(turma => {
		const idTurma	 = Number(getTurmaId(turma));
		const nomeSerie = turma.nome_serie ?? '';
		const nomeTurma = turma.nome_turma ?? '';

		const label = document.createElement('label');
		label.classList.add('radio-like');

		const input = document.createElement('input');
		input.type = 'checkbox';
		input.value = idTurma;

		if (vincSet && vincSet.has(idTurma)) {
			input.checked = true;
		}

		const span = document.createElement('span');
		span.textContent = `${nomeSerie} ${nomeTurma}`.trim();

		label.appendChild(input);
		label.appendChild(span);
		container.appendChild(label);
	});
}

/* -------------- Salvar -------------- */

// busca todos os vínculos atuais do professor (todas as disciplinas)
async function fetchVinculosDoProfessor(professorId) {
	const url = `/horarios/app/controllers/professor-disciplina-turma/listProfessorDisciplinaTurma.php?id_professor=${encodeURIComponent(professorId)}`;
	const resp = await fetch(url).then(r => r.json());
	if (resp?.status === 'success' && Array.isArray(resp.data)) return resp.data;
	return [];
}

async function salvarVinculo() {
	const professorId	= $(IDS.profId).value;
	const disciplinaId = $(IDS.selDisciplina).value;

	if (!professorId || !disciplinaId) {
		alert('Selecione o professor e a disciplina.');
		return;
	}

	// 1) monta os itens da disciplina atual: "disciplinaId:turmaId"
	const turmasCb = $$('#turmas-dt-checkboxes input[type="checkbox"]:checked');
	const itensDaDisciplinaAtual = [];
	turmasCb.forEach(cb => {
		const turmaId = cb.value;
		if (turmaId) itensDaDisciplinaAtual.push(`${disciplinaId}:${turmaId}`);
	});

	// 2) preserva vínculos de OUTRAS disciplinas (o PHP apaga tudo do professor)
	const existentes = await fetchVinculosDoProfessor(professorId);
	const itensOutrasDisciplinas = existentes
		.filter(row => String(row.id_disciplina) !== String(disciplinaId))
		.map(row => `${row.id_disciplina}:${row.id_turma}`);

	// 3) payload final para o insert
	const pdtItems = [...itensOutrasDisciplinas, ...itensDaDisciplinaAtual];

	const formData = new URLSearchParams();
	formData.append('id_professor', professorId);
	pdtItems.forEach(x => formData.append('pdtItems[]', x));

	fetch('/horarios/app/controllers/professor-disciplina-turma/insertProfessorDisciplinaTurma.php', {
		method: 'POST',
		headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
		body: formData
	})
		.then(r => r.json())
		.then(resp => {
			alert(resp.message);
			if (resp.status === 'success') {
				closeProfessorDisciplinaTurmaModal();
				// se quiser reabrir já marcado, basta chamar openProfessorDisciplinaTurmaModal de novo
			}
		})
		.catch(console.error);
}
