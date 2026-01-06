// app/assets/js/prof-aulas.js
document.addEventListener('DOMContentLoaded', () => {
	const selAno = document.getElementById('selectAnoLetivo');
	const selNivel = document.getElementById('selectNivelEnsino');
	const selTurma = document.getElementById('selectTurma');
	const selDet = document.getElementById('selectDetalhe');
	const selProf = document.getElementById('selectProfessor');
	const btnImp = document.getElementById('btnImprimir');

	const thead	= document.getElementById('theadProfAulas');
	const tbody	= document.getElementById('tbodyProfAulas');
	const noData = document.getElementById('noDataMessage');

	// Modal impressão
	const modal = document.getElementById('modalImpressao');
	const modalClose = document.getElementById('closeModalImpressao');
	const btnPais = document.getElementById('btnImprimirPaisagem');
	const btnCanc = document.getElementById('btnCancelarImpressao');

	let state = {
		ano: null,
		nivel: null,
		turma: null,
		detalhe: 'geral',
		professor: null,
		data: null,
		sortProfDir: null // 'asc' | 'desc' | null
	};

	function enableFilters(enabled) {
		selNivel.disabled = selTurma.disabled = selDet.disabled = selProf.disabled = !enabled;
		btnImp.disabled	 = !enabled;
	}

	function setOptions(select, list, {
		valueKey = 'value',
		labelKey = 'label',
		placeholder = null,
		preserve = true
	} = {}) {
		const prev = preserve ? select.value : '';
		select.innerHTML = '';
		if (placeholder) {
			const opt0 = document.createElement('option');
			opt0.value = '';
			opt0.textContent = placeholder;
			select.appendChild(opt0);
		}
		list.forEach(it => {
			const opt = document.createElement('option');
			opt.value = String(it[valueKey]);
			opt.textContent = String(it[labelKey]);
			select.appendChild(opt);
		});
		if (preserve && prev && [...select.options].some(o => o.value === prev)) {
			select.value = prev;
		} else if (placeholder) {
			select.value = '';
		}
	}

	// -------- loaders
	async function loadAnos() {
		try {
			const r = await fetch('/horarios/app/controllers/ano-letivo/listAnoLetivo.php').then(x => x.json());
			if (r.status === 'success') {
				setOptions(selAno, r.data.map(a => ({ value: a.id_ano_letivo, label: a.ano })), {
					placeholder: '-- Selecione --', preserve: false
				});
			}
		} catch (e) { console.error(e); }
	}

	async function loadNiveisPorAno(idAno) {
		try {
			const url = `/horarios/app/controllers/nivel-ensino/listNivelEnsinoByUserAndAno.php?id_ano_letivo=${idAno}`;
			const r = await fetch(url).then(x => x.json());
			let items = [];
			if (r.status === 'success' && Array.isArray(r.data)) {
				items = r.data
					.filter(n => !String(n.nome_nivel_ensino || '').toLowerCase().includes('escolinh'))
					.map(n => ({ value: n.id_nivel_ensino, label: n.nome_nivel_ensino }));
			}
			setOptions(selNivel, items, { placeholder: '-- Selecione o Nível --' });
			enableFilters(true);
		} catch (e) {
			console.error(e);
			setOptions(selNivel, [], { placeholder: '-- Selecione o Nível --' });
			enableFilters(true);
		}
	}

	async function loadTurmasFiltro() {
		try {
			if (!state.ano) {
				setOptions(selTurma, [], { placeholder: '-- Todas --' });
				return;
			}
			const params = new URLSearchParams({ id_ano_letivo: state.ano });
			if (state.nivel) params.set('id_nivel_ensino', state.nivel);
			const url = `/horarios/app/controllers/turma/listTurmaRelatorio.php?${params.toString()}`;
			const r = await fetch(url).then(x => x.json());
			const items = (r.status === 'success' ? r.data : []).map(t => ({
				value: t.id_turma, label: `${t.nome_serie} ${t.nome_turma}`
			}));
			setOptions(selTurma, items, { placeholder: '-- Todas --' });
		} catch (e) {
			console.error(e);
			setOptions(selTurma, [], { placeholder: '-- Todas --' });
		}
	}

	async function loadGrid() {
		if (!state.ano) { render([], [], {}); return; }

		const params = new URLSearchParams();
		params.set('id_ano_letivo', state.ano);
		if (state.nivel)		 params.set('id_nivel_ensino', state.nivel);
		if (state.turma)		 params.set('id_turma', state.turma);
		if (state.professor) params.set('id_professor', state.professor);
		params.set('detalhe', state.detalhe);

		let r;
		try {
			r = await fetch(`/horarios/app/controllers/professor/listProfAulasRel.php?${params.toString()}`).then(x => x.json());
		} catch (e) { console.error(e); r = { status: 'error' }; }

		if (r.status !== 'success' || !r.data) {
			setOptions(selProf, [], { placeholder: '-- Geral --' });
			state.data = null;
			render([], [], {});
			return;
		}

		state.data = r.data;

		const profList = (r.data.professores || []).map(p => ({ value: p.id_professor, label: p.nome_professor }));
		setOptions(selProf, profList, { placeholder: '-- Geral --' });
		state.professor = selProf.value || null;

		render(r.data.turmas || [], r.data.professores || [], r.data.grid || {});
	}

	// -------- render
	function render(turmas, professores, grid) {
		thead.innerHTML = '';
		tbody.innerHTML = '';

		// Cabeçalho (com ícones de ordenação)
		const trh = document.createElement('tr');

		const thProf = document.createElement('th');
		thProf.style.textAlign = 'left';
		thProf.classList.add('th-prof');
		// título + ícones
		const titleSpan = document.createElement('span');
		titleSpan.textContent = 'Nome do Professor';

		const sortSpan = document.createElement('span');
		sortSpan.className = 'sort-icons';
		const iUp = document.createElement('i');
		iUp.className = 'fa-solid fa-sort-up';
		iUp.id = 'sort-prof-asc';
		const iDown = document.createElement('i');
		iDown.className = 'fa-solid fa-sort-down';
		iDown.id = 'sort-prof-desc';
		sortSpan.appendChild(iUp);
		sortSpan.appendChild(iDown);

		thProf.appendChild(titleSpan);
		thProf.appendChild(sortSpan);
		trh.appendChild(thProf);

		(turmas || []).forEach(t => {
			const th = document.createElement('th');
			th.textContent = `${t.nome_serie} ${t.nome_turma}`;
			th.style.textAlign = 'center';
			trh.appendChild(th);
		});

		if (state.detalhe !== 'geral') {
			const thTot = document.createElement('th');
			thTot.textContent = 'Total';
			thTot.style.textAlign = 'center';
			trh.appendChild(thTot);
		}
		thead.appendChild(trh);

		// Sem dados?
		if (!turmas || turmas.length === 0 || !professores || professores.length === 0) {
			noData.style.display = 'block';
			// ainda assim ligar eventos dos ícones (não faz nada visual, mas mantém padrão)
			attachSortHandlers(turmas || [], professores || [], grid || {});
			return;
		}
		noData.style.display = 'none';

		// Aplica ordenação se houver
		let profs = [...professores];
		if (state.sortProfDir === 'asc') {
			profs.sort((a, b) => a.nome_professor.localeCompare(b.nome_professor, 'pt', { sensitivity: 'base' }));
		} else if (state.sortProfDir === 'desc') {
			profs.sort((a, b) => b.nome_professor.localeCompare(a.nome_professor, 'pt', { sensitivity: 'base' }));
		}

		// Linhas
		profs.forEach(p => {
			let totalGeral = 0;
			const tr = document.createElement('tr');

			const tdNome = document.createElement('td');
			tdNome.classList.add('col-prof');
			tdNome.style.textAlign = 'left';
			tdNome.textContent = p.nome_professor;
			tr.appendChild(tdNome);

			turmas.forEach(t => {
				const td = document.createElement('td');
				td.style.textAlign = 'center';
				td.style.verticalAlign = 'middle';

				const cell = grid?.[p.id_professor]?.[t.id_turma] || null;

				if (state.detalhe === 'geral') {
					td.textContent = cell ? 'V' : '';
				} else if (state.detalhe === 'quantidade') {
					const q = cell ? (cell.total || 0) : 0;
					totalGeral += q;
					td.textContent = q > 0 ? String(q) : '';
				} else {
					const wrap = document.createElement('div');
					wrap.className = 'cell-dias';

					const labels = document.createElement('div');
					labels.className = 'dias-labels';
					['D','S','T','Q','Q','S','S'].forEach(s => {
						const d = document.createElement('div');
						d.textContent = s;
						labels.appendChild(d);
					});

					const values = document.createElement('div');
					values.className = 'dias-values';
					const vals = cell || {dom:0,seg:0,ter:0,qua:0,qui:0,sex:0,sab:0,total:0};
					const arr	= [vals.dom, vals.seg, vals.ter, vals.qua, vals.qui, vals.sex, vals.sab];
					totalGeral += (cell ? (vals.total || 0) : 0);

					arr.forEach(v => {
						const d = document.createElement('div');
						d.textContent = v > 0 ? String(v) : '—';
						values.appendChild(d);
					});

					wrap.appendChild(labels);
					wrap.appendChild(values);
					td.appendChild(wrap);
				}

				tr.appendChild(td);
			});

			if (state.detalhe !== 'geral') {
				const tdTot = document.createElement('td');
				tdTot.style.textAlign = 'center';
				tdTot.style.verticalAlign = 'middle';
				tdTot.textContent = totalGeral;
				tr.appendChild(tdTot);
			}

			tbody.appendChild(tr);
		});

		// liga os cliques das setas após desenhar cabeçalho
		attachSortHandlers(turmas, professores, grid);
	}

	function attachSortHandlers(turmas, professores, grid) {
		const up = document.getElementById('sort-prof-asc');
		const dn = document.getElementById('sort-prof-desc');
		if (up) up.onclick = () => { state.sortProfDir = 'asc';	render(turmas, professores, grid); };
		if (dn) dn.onclick = () => { state.sortProfDir = 'desc'; render(turmas, professores, grid); };
	}

	// -------- modal
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
	modalClose.addEventListener('click', closeModal);
	btnCanc.addEventListener('click', closeModal);
	btnImp.addEventListener('click', openModal);

	btnPais.addEventListener('click', () => {
		if (!state.ano) { alert('Selecione o Ano Letivo.'); return; }
		const params = new URLSearchParams();
		params.set('id_ano_letivo', state.ano);
		if (state.nivel)		 params.set('id_nivel_ensino', state.nivel);
		if (state.turma)		 params.set('id_turma', state.turma);
		if (state.professor) params.set('id_professor', state.professor);
		params.set('detalhe', state.detalhe);
		window.open(`/horarios/app/views/prof-aulas-paisagem.php?${params.toString()}`, '_blank');
		closeModal();
	});

	// -------- eventos filtros
	selAno.addEventListener('change', async () => {
		state.ano = selAno.value || null;

		if (!state.ano) {
			state.nivel = state.turma = state.professor = null;
			state.sortProfDir = null;
			setOptions(selNivel, [], { placeholder: '-- Selecione o Nível --', preserve: false });
			setOptions(selTurma, [], { placeholder: '-- Todas --',				preserve: false });
			setOptions(selProf,	[], { placeholder: '-- Geral --',				preserve: false });
			render([], [], {});
			enableFilters(false);
			return;
		}

		state.nivel = state.turma = state.professor = null;
		state.sortProfDir = null;
		await loadNiveisPorAno(state.ano);
		await loadTurmasFiltro();
		await loadGrid();
	});

	selNivel.addEventListener('change', async () => {
		state.nivel = selNivel.value || null;
		state.turma = null;
		state.sortProfDir = null;
		selTurma.value = '';
		await loadTurmasFiltro();
		await loadGrid();
	});

	selTurma.addEventListener('change', async () => {
		state.turma = selTurma.value || null;
		await loadGrid();
	});

	selDet.addEventListener('change', async () => {
		state.detalhe = selDet.value || 'geral';
		await loadGrid();
	});

	selProf.addEventListener('change', async () => {
		state.professor = selProf.value || null;
		await loadGrid();
	});

	// init
	enableFilters(false);
	loadAnos();
	render([], [], {});
});
