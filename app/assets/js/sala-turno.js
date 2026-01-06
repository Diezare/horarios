// app/assets/js/sala-turno.js
document.addEventListener('DOMContentLoaded', function () {
	// ---- estilos dos radios ----
	const style = document.createElement('style');
	style.innerHTML = `
		input[type="radio"]:checked { accent-color:#3122F6; }
		input[type="radio"]:disabled { accent-color:gray; }
	`;
	document.head.appendChild(style);

	// ---- cache ÚNICO ----
	const cache = {
		// niveis por ano: { [idAnoLetivo]: [ {id_nivel_ensino, nome_nivel_ensino}, ... ] }
		niveis: {},
		// turmas cacheadas por chave `${ano}-${nivel}-${turno}`
		turmas: new Map(),
		// ids de turmas indisponíveis por chave `${turno}-${salaId}`
		indisponiveis: new Map(),
	};

	// helper p/ deduplicar por chave
	function uniqBy(arr, key) {
		const seen = new Set();
		return (arr || []).filter(it => {
			const k = String(it[key]);
			if (seen.has(k)) return false;
			seen.add(k);
			return true;
		});
	}

	// ---- elementos do modal ----
	const modal						= document.getElementById('modal-sala-turno');
	const btnClose				 = document.getElementById('close-sala-turno-modal');
	const btnCancel				= document.getElementById('cancelSalaTurnoBtn');
	const btnSave					= document.getElementById('saveSalaTurnoBtn');
	const turnosCheckboxes = document.getElementById('turnos-checkboxes');
	const turnosDetalhes	 = document.getElementById('turnos-detalhes');

	// ---- inputs base ----
	const inpSalaId	 = document.getElementById('salaIdTurno');
	const inpAnoLetivo= document.getElementById('idAnoLetivo');
	const inpNomeSala = document.getElementById('nomeSalaTurno');

	// guarda seleção por turno (não perde ao trocar nível)
	// selected[turnoId] = { nivelId, turmaId }
	const selected = Object.create(null);

	// ---- helpers UI ----
	function showModal() {
		modal.style.display = 'block';
		modal.classList.remove('fade-out'); modal.classList.add('fade-in');
		const content = modal.querySelector('.modal-content');
		content.classList.remove('slide-up'); content.classList.add('slide-down');
	}
	function hideModal() {
		const content = modal.querySelector('.modal-content');
		content.classList.remove('slide-down'); content.classList.add('slide-up');
		modal.classList.remove('fade-in'); modal.classList.add('fade-out');
		setTimeout(() => {
			modal.style.display = 'none';
			content.classList.remove('slide-up');
			modal.classList.remove('fade-out');
			// limpa DOM para próxima abertura (mantemos 'selected' em memória)
			turnosCheckboxes.innerHTML = '';
			turnosDetalhes.innerHTML = '';
		}, 300);
	}
	btnClose.addEventListener('click', hideModal);
	btnCancel.addEventListener('click', hideModal);

	function addSeparatorsBetweenTurnos() {
		const blocks = [...turnosDetalhes.querySelectorAll('.turno-detalhe')];
		turnosDetalhes.querySelectorAll('hr.turno-separator').forEach(hr => hr.remove());
		blocks.forEach((b, i) => {
			if (i < blocks.length - 1) {
				const hr = document.createElement('hr');
				hr.className = 'turno-separator';
				hr.style.margin = '10px 0';
				b.insertAdjacentElement('afterend', hr);
			}
		});
	}

	// ---- data (fetchers) ----
	async function fetchSala(salaId) {
		const r = await fetch('/horarios/app/controllers/sala/listSala.php?id=' + salaId).then(x => x.json());
		if (r.status !== 'success' || !r.data) throw new Error('Sala não encontrada');
		return r.data;
	}

	async function fetchTurnos() {
		const r = await fetch('/horarios/app/controllers/turno/listTurno.php').then(x => x.json());
		if (r.status !== 'success') throw new Error('Erro ao carregar turnos');
		return r.data || [];
	}

	// >>> fetchNiveis com MESCLA (por ano + por usuário) e fallback p/ "todos"
	async function fetchNiveis(anoLetivo) {
		if (cache.niveis[anoLetivo]) return cache.niveis[anoLetivo];

		const [rByYear, rByUser] = await Promise.all([
			fetch(`/horarios/app/controllers/nivel-ensino/listNivelEnsinoByUserAndAno.php?id_ano_letivo=${anoLetivo}`)
				.then(x=>x.json()).catch(()=>({status:'error'})),
			fetch('/horarios/app/controllers/nivel-ensino/listNivelEnsinoByUser.php')
				.then(x=>x.json()).catch(()=>({status:'error'}))
		]);

		let byYear = (rByYear.status === 'success') ? (rByYear.data || []) : [];
		let byUser = (rByUser.status === 'success') ? (rByUser.data || []) : [];

		// mescla e remove duplicatas
		let lista = uniqBy([...byYear, ...byUser], 'id_nivel_ensino');

		// se ainda vazio, tenta todos
		if (!lista.length) {
			const rAll = await fetch('/horarios/app/controllers/nivel-ensino/listAllNivelEnsino.php')
				.then(x=>x.json()).catch(()=>({status:'error'}));
			if (rAll.status === 'success') lista = rAll.data || [];
		}

		// ordena por nome
		lista.sort((a,b) => String(a.nome_nivel_ensino).localeCompare(String(b.nome_nivel_ensino)));

		cache.niveis[anoLetivo] = lista;
		return lista;
	}

	function turmasKey(anoLetivo, nivelId, turnoId) {
		return `${anoLetivo}-${nivelId || 'all'}-${turnoId}`;
	}

	async function fetchTurmas(anoLetivo, nivelId, turnoId) {
		const key = turmasKey(anoLetivo, nivelId, turnoId);
		if (cache.turmas.has(key)) return cache.turmas.get(key);

		const params = new URLSearchParams({ id_ano_letivo: anoLetivo, id_nivel_ensino: nivelId });
		const url = `/horarios/app/controllers/turma/listTurmaPorNivelEnsino.php?${params.toString()}`;
		const r = await fetch(url).then(x => x.json());
		const list = (r.status === 'success') ? (r.data || []) : [];
		cache.turmas.set(key, list);
		return list;
	}

	async function fetchIndisponiveis(turnoId, salaId) {
		const key = `${turnoId}-${salaId}`;
		if (cache.indisponiveis.has(key)) return cache.indisponiveis.get(key);
		const url = `/horarios/app/controllers/sala-turno/checkTurmasDisponiveis.php?id_turno=${turnoId}&id_sala=${salaId}`;
		const r	 = await fetch(url).then(x => x.json());
		const arr = (r.status === 'success' && Array.isArray(r.data)) ? r.data.map(String) : [];
		cache.indisponiveis.set(key, arr);
		return arr;
	}

	async function fetchBindings(salaId) {
		const r = await fetch('/horarios/app/controllers/sala-turno/listSalaTurno.php?id_sala=' + salaId).then(x => x.json());
		return (r.status === 'success') ? (r.data || []) : [];
	}

	// ---- construção de UI ----
	function checkboxTurnoEl(turno) {
		const label = document.createElement('label');
		label.classList.add('radio-like');

		const input = document.createElement('input');
		input.type = 'checkbox';
		input.value = turno.id_turno;
		input.dataset.nomeTurno = turno.nome_turno;

		const span = document.createElement('span');
		span.classList.add('turno-text');
		span.textContent = turno.nome_turno;

		label.appendChild(input);
		label.appendChild(span);
		return { label, input };
	}

	function blocoTurnoEl(turno) {
		const bloco = document.createElement('div');
		bloco.className = 'turno-detalhe';
		bloco.id = 'turno-detalhe-' + turno.id_turno;

		const title = document.createElement('h4');
		title.textContent = 'Turno: ' + turno.nome_turno;
		title.style.fontWeight = 'bold';
		title.style.marginBottom = '10px';
		bloco.appendChild(title);

		const nivelRow = document.createElement('div');
		nivelRow.className = 'form-row';
		nivelRow.style.marginBottom = '10px';

		const col1 = document.createElement('div');
		col1.className = 'form-group inline-checkbox';
		col1.style.marginBottom = '0';
		const lbl = document.createElement('label');
		lbl.className = 'radio-like';
		const spanLbl = document.createElement('span');
		spanLbl.innerHTML = '<strong>Nível de Ensino</strong>';
		lbl.appendChild(spanLbl);
		col1.appendChild(lbl);

		const col2 = document.createElement('div');
		col2.className = 'form-group';
		col2.style.marginBottom = '0';
		const select = document.createElement('select');
		select.className = 'nivel-ensino-select';
		select.dataset.idturno = String(turno.id_turno);
		select.style.fontSize = '16px';
		col2.appendChild(select);

		nivelRow.appendChild(col1);
		nivelRow.appendChild(col2);
		bloco.appendChild(nivelRow);

		const radios = document.createElement('div');
		radios.className = 'radio-grid-4';
		radios.id = 'turmas-radio-container-' + turno.id_turno;
		radios.style.marginBottom = '20px';
		bloco.appendChild(radios);

		return { bloco, select, radios };
	}

	async function fillNiveis(selectEl, anoLetivo, turnoId) {
		selectEl.innerHTML = '<option value="">Selecione o Nível...</option>';
		const niveis = await fetchNiveis(anoLetivo);
		niveis.forEach(n => {
			const o = document.createElement('option');
			o.value = n.id_nivel_ensino;
			o.textContent = n.nome_nivel_ensino;
			selectEl.appendChild(o);
		});
		if (selected[turnoId]?.nivelId) {
			selectEl.value = String(selected[turnoId].nivelId);
		}
	}

	async function fillTurmas(radiosBox, anoLetivo, nivelId, turnoId, salaId) {
		radiosBox.innerHTML = '';
		if (!nivelId) return;

		const [turmas, indisponiveis] = await Promise.all([
			fetchTurmas(anoLetivo, nivelId, turnoId),
			fetchIndisponiveis(turnoId, salaId)
		]);

		turmas.forEach(t => {
			const label = document.createElement('label');
			label.style.cursor = 'pointer';
			label.style.marginRight = '10px';

			const input = document.createElement('input');
			input.type = 'radio';
			input.name = 'radio-turma-' + turnoId;
			input.value = t.id_turma;

			if (indisponiveis.includes(String(t.id_turma))) {
				input.disabled = true;
				label.style.color = 'gray';
			}

			if (selected[turnoId] &&
					String(selected[turnoId].nivelId) === String(nivelId) &&
					String(selected[turnoId].turmaId) === String(t.id_turma)) {
				input.checked = true;
			}

			input.addEventListener('change', () => {
				selected[turnoId] = { nivelId, turmaId: t.id_turma };
			});

			label.appendChild(input);
			label.append(' ' + t.nome_serie + ' ' + t.nome_turma);
			radiosBox.appendChild(label);
		});
	}

	function applyBindingToSelected(binding) {
		selected[binding.id_turno] = {
			nivelId: binding.id_nivel_ensino || '',
			turmaId: binding.id_turma || ''
		};
	}

	// ---- abrir modal (monta tudo antes para evitar delay visual) ----
	async function openSalaTurnoModal(salaId) {
		inpSalaId.value = salaId;

		try {
			const sala = await fetchSala(salaId); // 1) sala/ano
			inpAnoLetivo.value = sala.id_ano_letivo || '';

			const [turnos, bindings] = await Promise.all([ // 2) turnos + vínculos
				fetchTurnos(),
				fetchBindings(salaId)
			]);

			turnosCheckboxes.innerHTML = '';
			turnosDetalhes.innerHTML = '';

			(bindings || []).forEach(applyBindingToSelected);

			for (const turno of turnos) {
				const { label, input } = checkboxTurnoEl(turno);
				turnosCheckboxes.appendChild(label);

				let blocoEls = null;

				if (selected[turno.id_turno]) {
					input.checked = true;
					blocoEls = blocoTurnoEl(turno);
					turnosDetalhes.appendChild(blocoEls.bloco);
					await fillNiveis(blocoEls.select, sala.id_ano_letivo, turno.id_turno);
					if (selected[turno.id_turno].nivelId) {
						blocoEls.select.value = String(selected[turno.id_turno].nivelId);
					}
					await fillTurmas(blocoEls.radios, sala.id_ano_letivo, blocoEls.select.value, turno.id_turno, salaId);

					blocoEls.select.addEventListener('change', async () => {
						const novoNivel = blocoEls.select.value || '';
						selected[turno.id_turno] = { nivelId: novoNivel, turmaId: selected[turno.id_turno]?.turmaId || '' };
						await fillTurmas(blocoEls.radios, sala.id_ano_letivo, novoNivel, turno.id_turno, salaId);
					});
				}

				input.addEventListener('change', async function () {
					if (this.checked) {
						if (!blocoEls) {
							blocoEls = blocoTurnoEl(turno);
							turnosDetalhes.appendChild(blocoEls.bloco);
							await fillNiveis(blocoEls.select, sala.id_ano_letivo, turno.id_turno);
							if (selected[turno.id_turno]?.nivelId) {
								blocoEls.select.value = String(selected[turno.id_turno].nivelId);
							}
							await fillTurmas(blocoEls.radios, sala.id_ano_letivo, blocoEls.select.value, turno.id_turno, salaId);

							blocoEls.select.addEventListener('change', async () => {
								const novoNivel = blocoEls.select.value || '';
								selected[turno.id_turno] = { nivelId: novoNivel, turmaId: selected[turno.id_turno]?.turmaId || '' };
								await fillTurmas(blocoEls.radios, sala.id_ano_letivo, novoNivel, turno.id_turno, salaId);
							});
						}
					} else {
						const id = 'turno-detalhe-' + turno.id_turno;
						const el = document.getElementById(id);
						if (el) el.remove();
					}
					addSeparatorsBetweenTurnos();
				});
			}

			addSeparatorsBetweenTurnos();
			showModal(); // mostra só depois de montar tudo (sem delay)

		} catch (e) {
			console.error('Erro ao abrir modal Sala-Turno:', e);
			alert('Não foi possível abrir o vínculo de turmas.');
		}
	}
	window.openSalaTurnoModal = openSalaTurnoModal;
	window.prepareSalaTurno = async () => {}; // no-op opcional

	// ---- salvar vínculos ----
	btnSave.addEventListener('click', async () => {
		const salaId = inpSalaId.value;
		if (!salaId) { alert('Sala não encontrada.'); return; }

		const marcados = turnosCheckboxes.querySelectorAll('input[type="checkbox"]:checked');
		const vinculos = [];

		for (const chk of marcados) {
			const turnoId = chk.value;
			const bloco = document.getElementById('turno-detalhe-' + turnoId);
			if (!bloco) continue;

			const selectNivel = bloco.querySelector('.nivel-ensino-select');
			const nivelId = selectNivel?.value || '';
			if (!nivelId) { alert(`Turno ${chk.dataset.nomeTurno}: selecione o Nível de Ensino.`); return; }

			const radioSel = bloco.querySelector(`input[name="radio-turma-${turnoId}"]:checked`);
			if (!radioSel) { alert(`Turno ${chk.dataset.nomeTurno}: selecione a Turma.`); return; }

			vinculos.push(`${turnoId},${nivelId},${radioSel.value}`);
			selected[turnoId] = { nivelId, turmaId: radioSel.value };
		}

		const params = new URLSearchParams({
			id_sala: salaId,
			vinculos: vinculos.join('|')
		});

		try {
			const r = await fetch('/horarios/app/controllers/sala/insertSalaTurnoMulti.php', {
				method: 'POST',
				headers: {'Content-Type':'application/x-www-form-urlencoded'},
				body: params.toString()
			}).then(x=>x.json());

			if (r.status === 'success') {
				alert(r.message || 'Vínculos salvos com sucesso!');
				hideModal();
				if (typeof window.fetchSalas === 'function') window.fetchSalas();
			} else {
				alert(r.message || 'Erro ao salvar vínculos.');
			}
		} catch (e) {
			console.error('Erro ao salvar vínculos:', e);
			alert('Erro ao salvar vínculos.');
		}
	});
});
