// app/assets/js/professor-disciplina-turma.js - VERSÃO CORRIGIDA

let listenersBoundProfDiscTurma = false;
let anoLetivoAtual = null; // Armazena o ano letivo atual

// Helpers
const $ = (s) => document.querySelector(s);
const $$ = (s) => document.querySelectorAll(s);

const IDS = {
	modal: '#modal-professor-disciplina-turma',
	modalContent: '#modal-professor-disciplina-turma .modal-content',
	profNome: '#prof-dt-nome',
	profId: '#prof-dt-id',
	selDisciplina: '#sel-disciplina',
	areaNiveis: '#niveis-ensino-checkboxes',
	areaTurmas: '#turmas-dt-checkboxes',
	btnClose: '#close-professor-disciplina-turma-modal',
	btnCancel: '#cancel-professor-disciplina-turma-btn',
	btnSave: '#save-professor-disciplina-turma-btn'
};

/* ============================================================
	 ABERTURA DO MODAL
============================================================ */
function openProfessorDisciplinaTurmaModal(professorId, professorName) {
	const modal = $(IDS.modal);
	modal.style.display = 'block';
	modal.classList.remove('fade-out');

	const content = $(IDS.modalContent);
	content.classList.remove('slide-up');
	content.classList.add('slide-down');

	$(IDS.profNome).value = professorName;
	$(IDS.profId).value = professorId;

	// Reseta campos
	$(IDS.selDisciplina).innerHTML = '<option value="">-- Selecione a Disciplina --</option>';
	limpaAreaNiveis('Selecione a disciplina.');
	limpaAreaTurmas('Selecione a disciplina e os níveis de ensino.');

	// Carrega ano letivo atual primeiro
	loadAnoLetivoAtual().then(() => {
		// Depois carrega disciplinas do professor
		loadDisciplinaProfessor(professorId);
	});

	// Configura listeners (apenas uma vez)
	if (!listenersBoundProfDiscTurma) {
		$(IDS.selDisciplina).addEventListener('change', onDisciplinaChange);
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

/* ============================================================
	 CARREGA ANO LETIVO ATUAL
============================================================ */
async function loadAnoLetivoAtual() {
	try {
		const resp = await fetch('/horarios/app/controllers/ano-letivo/listAnoLetivo.php').then(r => r.json());
		if (resp.status === 'success' && resp.data && resp.data.length > 0) {
			// Pega o primeiro (ou você pode filtrar pelo ano atual)
			anoLetivoAtual = resp.data[0].id_ano_letivo;
		}
	} catch (error) {
		console.error('Erro ao carregar ano letivo:', error);
	}
}

/* ============================================================
	 CARREGADORES INICIAIS
============================================================ */
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

/* ============================================================
	 HANDLER: QUANDO MUDA A DISCIPLINA
============================================================ */
async function onDisciplinaChange() {
	const disciplinaId = $(IDS.selDisciplina).value;

	if (!disciplinaId) {
		limpaAreaNiveis('Selecione a disciplina.');
		limpaAreaTurmas('Selecione a disciplina e os níveis de ensino.');
		return;
	}

	if (!anoLetivoAtual) {
		alert('Erro: Ano letivo não carregado. Recarregue a página.');
		return;
	}

	// Carrega níveis de ensino como checkboxes
	await loadNiveisEnsinoCheckboxes();
	limpaAreaTurmas('Selecione os níveis de ensino para carregar as turmas.');
}

/* ============================================================
	 CARREGA NÍVEIS DE ENSINO COMO CHECKBOXES
============================================================ */
async function loadNiveisEnsinoCheckboxes() {
	const professorId = $(IDS.profId).value;
	const disciplinaId = $(IDS.selDisciplina).value;

	try {
		// Busca todos os níveis de ensino
		const respNiveis = await fetch('/horarios/app/controllers/nivel-ensino/listNivelEnsino.php').then(r => r.json());
		
		if (respNiveis.status !== 'success') {
			limpaAreaNiveis('Erro ao carregar níveis de ensino.');
			return;
		}

		const niveis = respNiveis.data || [];
		
		// Busca vínculos atuais (para marcar checkboxes)
		const vinculos = await fetchVinculosAtuais(professorId, disciplinaId);
		const niveisVinculados = new Set(vinculos.map(v => Number(v.id_nivel_ensino)));

		// Renderiza checkboxes
		renderNiveisCheckboxes(niveis, niveisVinculados);

	} catch (error) {
		console.error(error);
		limpaAreaNiveis('Erro ao carregar níveis de ensino.');
	}
}

function renderNiveisCheckboxes(niveis, niveisVinculados) {
	const container = $(IDS.areaNiveis);
	container.innerHTML = '';

	niveis.forEach(nivel => {
		const label = document.createElement('label');
		label.classList.add('checkbox-inline');
		label.style.display = 'block';
		label.style.marginBottom = '8px';

		const checkbox = document.createElement('input');
		checkbox.type = 'checkbox';
		checkbox.value = nivel.id_nivel_ensino;
		checkbox.checked = niveisVinculados.has(Number(nivel.id_nivel_ensino));

		// Quando marca/desmarca, atualiza turmas
		checkbox.addEventListener('change', atualizarTurmasPorNiveis);

		const span = document.createElement('span');
		span.textContent = ' ' + nivel.nome_nivel_ensino;

		label.appendChild(checkbox);
		label.appendChild(span);
		container.appendChild(label);
	});

	// Carrega turmas inicialmente (se houver níveis marcados)
	const marcados = container.querySelectorAll('input[type="checkbox"]:checked');
	if (marcados.length > 0) {
		atualizarTurmasPorNiveis();
	}
}

/* ============================================================
	 ATUALIZA TURMAS BASEADO NOS NÍVEIS SELECIONADOS
============================================================ */
async function atualizarTurmasPorNiveis() {
	const professorId = $(IDS.profId).value;
	const disciplinaId = $(IDS.selDisciplina).value;
	
	// Pega níveis marcados
	const niveisCheckboxes = $$('#niveis-ensino-checkboxes input[type="checkbox"]:checked');
	const niveisIds = Array.from(niveisCheckboxes).map(cb => cb.value);

	if (niveisIds.length === 0) {
		limpaAreaTurmas('Selecione pelo menos um nível de ensino.');
		return;
	}

	if (!anoLetivoAtual) {
		limpaAreaTurmas('Erro: Ano letivo não carregado.');
		return;
	}

	try {
		// ✅ USA O NOVO ENDPOINT: listTurmaPorNivelEnsinoETurnoProfessor.php
		// Este endpoint filtra as turmas pelos turnos em que o professor está cadastrado
		const turmasPromises = niveisIds.map(idNivel => 
			fetch(`/horarios/app/controllers/turma/listTurmaPorNivelEnsinoETurnoProfessor.php?id_ano_letivo=${anoLetivoAtual}&id_nivel_ensino=${idNivel}&id_professor=${professorId}`)
				.then(r => r.json())
				.catch(() => ({ status: 'error' }))
		);

		const results = await Promise.all(turmasPromises);

		// Agrega todas as turmas
		let todasTurmas = [];
		results.forEach(res => {
			if (res.status === 'success' && Array.isArray(res.data)) {
				todasTurmas = todasTurmas.concat(res.data);
			}
		});

		// Remove duplicatas
		const turmasUnicas = [];
		const idsVistos = new Set();
		todasTurmas.forEach(turma => {
			if (!idsVistos.has(turma.id_turma)) {
				idsVistos.add(turma.id_turma);
				turmasUnicas.push(turma);
			}
		});

		if (turmasUnicas.length === 0) {
			limpaAreaTurmas('Nenhuma turma encontrada para os níveis selecionados nos turnos em que o professor está cadastrado.');
			return;
		}

		// Busca vínculos atuais para marcar checkboxes
		const vinculos = await fetchVinculosAtuais(professorId, disciplinaId);
		const turmasVinculadas = new Set(vinculos.map(v => Number(v.id_turma)));

		// Renderiza turmas
		renderTurmas(turmasUnicas, turmasVinculadas);

	} catch (error) {
		console.error(error);
		limpaAreaTurmas('Erro ao carregar turmas.');
	}
}

/* ============================================================
	 BUSCA VÍNCULOS ATUAIS (PARA MARCAR CHECKBOXES)
============================================================ */
async function fetchVinculosAtuais(professorId, disciplinaId) {
	try {
		const url = `/horarios/app/controllers/professor-disciplina-turma/listProfessorDisciplinaTurmaDetalhado.php?id_professor=${professorId}&id_disciplina=${disciplinaId}`;
		const resp = await fetch(url).then(r => r.json());
		
		if (resp.status === 'success' && Array.isArray(resp.data)) {
			return resp.data;
		}
		return [];
	} catch (error) {
		console.error(error);
		return [];
	}
}

/* ============================================================
	 RENDERIZA TURMAS COM FORMATO: SÉRIE TURMA - TURNO
============================================================ */
function renderTurmas(turmas, turmasVinculadas) {
	const container = $(IDS.areaTurmas);
	container.innerHTML = '';

	// Ordena por série, turma, turno
	turmas.sort((a, b) => {
		const compSerie = (a.nome_serie || '').localeCompare(b.nome_serie || '');
		if (compSerie !== 0) return compSerie;
		
		const compTurma = (a.nome_turma || '').localeCompare(b.nome_turma || '');
		if (compTurma !== 0) return compTurma;
		
		return (a.nome_turno || '').localeCompare(b.nome_turno || '');
	});

	turmas.forEach(turma => {
		const label = document.createElement('label');
		label.classList.add('checkbox-inline');
		label.style.display = 'block';
		label.style.marginBottom = '8px';

		const checkbox = document.createElement('input');
		checkbox.type = 'checkbox';
		checkbox.value = turma.id_turma;
		checkbox.checked = turmasVinculadas.has(Number(turma.id_turma));

		const texto = `${turma.nome_serie || ''} ${turma.nome_turma || ''} - ${turma.nome_turno || ''}`.trim();
		const span = document.createElement('span');
		span.textContent = ' ' + texto;

		label.appendChild(checkbox);
		label.appendChild(span);
		container.appendChild(label);
	});
}

/* ============================================================
	 SALVAR VÍNCULOS
============================================================ */
async function salvarVinculo() {
	const professorId = $(IDS.profId).value;
	const disciplinaId = $(IDS.selDisciplina).value;

	if (!professorId || !disciplinaId) {
		alert('Selecione o professor e a disciplina.');
		return;
	}

	// Pega turmas marcadas
	const turmasCb = $$('#turmas-dt-checkboxes input[type="checkbox"]:checked');
	const turmasSelecionadas = Array.from(turmasCb).map(cb => cb.value);

	if (turmasSelecionadas.length === 0) {
		const confirma = confirm('Nenhuma turma foi selecionada. Deseja remover todos os vínculos desta disciplina?');
		if (!confirma) return;
	}

	try {
		// ✅ USA SEU ENDPOINT: listProfessorDisciplinaTurma.php
		const todosVinculos = await fetchTodosVinculosDoProfessor(professorId);

		// Separa: vínculos de outras disciplinas + vínculos da disciplina atual
		const vinculosOutrasDisciplinas = todosVinculos
			.filter(v => String(v.id_disciplina) !== String(disciplinaId))
			.map(v => `${v.id_disciplina}:${v.id_turma}`);

		const vinculosDisciplinaAtual = turmasSelecionadas.map(idTurma => `${disciplinaId}:${idTurma}`);

		// Monta payload final
		const pdtItems = [...vinculosOutrasDisciplinas, ...vinculosDisciplinaAtual];

		const formData = new URLSearchParams();
		formData.append('id_professor', professorId);
		pdtItems.forEach(item => formData.append('pdtItems[]', item));

		const resp = await fetch('/horarios/app/controllers/professor-disciplina-turma/insertProfessorDisciplinaTurma.php', {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: formData
		}).then(r => r.json());

		alert(resp.message || 'Vínculos salvos com sucesso!');
		
		if (resp.status === 'success') {
			closeProfessorDisciplinaTurmaModal();
		}

	} catch (error) {
		console.error(error);
		alert('Erro ao salvar vínculos.');
	}
}

async function fetchTodosVinculosDoProfessor(professorId) {
	try {
		// ✅ USA SEU ENDPOINT: listProfessorDisciplinaTurma.php
		const url = `/horarios/app/controllers/professor-disciplina-turma/listProfessorDisciplinaTurma.php?id_professor=${professorId}`;
		const resp = await fetch(url).then(r => r.json());
		
		if (resp.status === 'success' && Array.isArray(resp.data)) {
			return resp.data;
		}
		return [];
	} catch (error) {
		console.error(error);
		return [];
	}
}

/* ============================================================
	 HELPERS DE LIMPEZA
============================================================ */
function limpaAreaNiveis(msg = '') {
	const container = $(IDS.areaNiveis);
	container.innerHTML = '';
	if (msg) {
		const p = document.createElement('p');
		p.style.color = '#6c757d';
		p.style.fontStyle = 'italic';
		p.style.textAlign = 'center';
		p.style.padding = '10px';
		p.textContent = msg;
		container.appendChild(p);
	}
}

function limpaAreaTurmas(msg = '') {
	const container = $(IDS.areaTurmas);
	container.innerHTML = '';
	if (msg) {
		const p = document.createElement('p');
		p.style.color = '#6c757d';
		p.style.fontStyle = 'italic';
		p.style.textAlign = 'center';
		p.style.padding = '10px';
		p.textContent = msg;
		container.appendChild(p);
	}
}