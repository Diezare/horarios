// app/assets/js/turno-dias.js
(function () {
	'use strict';

	const diasSemanaUI = ["Dom","Seg","Ter","Qua","Qui","Sex","Sab"];
	const diaDisplayToBD = {
		"Dom": "Domingo",
		"Seg": "Segunda",
		"Ter": "Terca",
		"Qua": "Quarta",
		"Qui": "Quinta",
		"Sex": "Sexta",
		"Sab": "Sabado"
	};

	let currentTurnoId = null;
	let niveisCache = null;

	// Cancela fetch anterior ao trocar de nível (evita sobrescrever)
	let diasAbortController = null;

	function $(id) { return document.getElementById(id); }

	function showModal() {
		const modal = $('modal-turno-dias');
		if (!modal) return;

		modal.style.display = 'block';
		modal.classList.remove('fade-out');
		modal.classList.add('fade-in');

		const content = modal.querySelector('.modal-content');
		if (content) {
			content.classList.remove('slide-up');
			content.classList.add('slide-down');
		}
	}

	function hideModal() {
		if (diasAbortController) {
			diasAbortController.abort();
			diasAbortController = null;
		}

		const modal = $('modal-turno-dias');
		if (!modal) return;

		const content = modal.querySelector('.modal-content');
		if (content) {
			content.classList.remove('slide-down');
			content.classList.add('slide-up');
		}
		modal.classList.remove('fade-in');
		modal.classList.add('fade-out');

		setTimeout(() => {
			modal.style.display = 'none';
			if (content) content.classList.remove('slide-up');
			modal.classList.remove('fade-out');
		}, 300);
	}

	function setInputsEnabled(enabled) {
		document.querySelectorAll('.turno-dia-input').forEach(inp => {
			inp.disabled = !enabled;
		});
	}

	function resetGridToZero() {
		document.querySelectorAll('.turno-dia-input').forEach(inp => {
			inp.value = '0';
		});
	}

	function ensureSaveButtonEnabled() {
		const btn = $('save-turno-dias-btn');
		if (btn) btn.disabled = false; // nunca desabilita, pois precisa clicar para alertar
	}

	function renderTurnoDiasGrid() {
		const container = document.querySelector('.turno-dias-grid');
		if (!container) return;

		container.innerHTML = '';

		const row = document.createElement('div');
		row.classList.add('turno-dias-row');

		for (let i = 0; i < 7; i++) {
			const col = document.createElement('div');
			col.classList.add('turno-dia-col');

			const label = document.createElement('label');
			label.textContent = diasSemanaUI[i];

			const input = document.createElement('input');
			input.type = 'text';
			input.maxLength = '2';
			input.classList.add('turno-dia-input');
			input.setAttribute('data-dia-ui', diasSemanaUI[i]);
			input.value = '0';
			input.disabled = true;

			input.addEventListener('input', (e) => {
				let v = (e.target.value || '').replace(/\D/g, '');
				if (v.length > 2) v = v.substring(0, 2);
				e.target.value = v;
			});

			col.appendChild(label);
			col.appendChild(input);
			row.appendChild(col);
		}

		container.appendChild(row);
	}

	function fillGridFromResponse(diasArray) {
		// aplica tudo de uma vez
		const mapUI = {};
		(diasArray || []).forEach(item => {
			const diaBD = item.dia_semana;
			const aulas = (item.aulas_no_dia ?? 0);
			const uiName = Object.keys(diaDisplayToBD).find(k => diaDisplayToBD[k] === diaBD);
			if (!uiName) return;
			mapUI[uiName] = String(aulas);
		});

		document.querySelectorAll('.turno-dia-input').forEach(inp => {
			const ui = inp.getAttribute('data-dia-ui');
			inp.value = (mapUI[ui] != null) ? mapUI[ui] : '0';
		});
	}

	function setSelectLoading(msg) {
		const sel = $('select-nivel-turno-dias');
		if (!sel) return;

		sel.disabled = true;
		sel.innerHTML = '';
		const opt = document.createElement('option');
		opt.value = '';
		opt.textContent = msg;
		sel.appendChild(opt);
	}

	function mountNiveisSelect(rows) {
		const sel = $('select-nivel-turno-dias');
		if (!sel) return;

		sel.innerHTML = '';

		const opt0 = document.createElement('option');
		opt0.value = '';
		opt0.textContent = 'Selecione...';
		sel.appendChild(opt0);

		(rows || []).forEach(r => {
			const id = r.id_nivel_ensino;
			const nome = r.nome_nivel_ensino;
			if (!id || !nome) return;

			const opt = document.createElement('option');
			opt.value = String(id);
			opt.textContent = String(nome);
			sel.appendChild(opt);
		});

		sel.disabled = false;
	}

	function loadNiveisEnsino() {
		if (Array.isArray(niveisCache) && niveisCache.length) {
			mountNiveisSelect(niveisCache);
			return Promise.resolve(niveisCache);
		}

		setSelectLoading('Carregando...');
		const url = '/horarios/app/controllers/nivel-ensino/listNivelEnsino.php';

		return fetch(url)
			.then(r => r.json())
			.then(resp => {
				if (!resp || resp.status !== 'success') {
					setSelectLoading('Erro ao carregar');
					niveisCache = [];
					return [];
				}
				niveisCache = resp.data || [];
				mountNiveisSelect(niveisCache);
				return niveisCache;
			})
			.catch(() => {
				setSelectLoading('Erro ao carregar');
				niveisCache = [];
				return [];
			});
	}

	function loadTurnoDiasSalvos(idTurno, idNivelEnsino) {
		// cancela request anterior
		if (diasAbortController) diasAbortController.abort();
		diasAbortController = new AbortController();

		// não zera (evita “piscar”); só trava enquanto carrega
		setInputsEnabled(false);

		const url = `/horarios/app/controllers/turno-dias/listTurnoDias.php?id_turno=${encodeURIComponent(idTurno)}&id_nivel_ensino=${encodeURIComponent(idNivelEnsino)}`;

		fetch(url, { signal: diasAbortController.signal })
			.then(r => r.json())
			.then(resp => {
				if (!resp || resp.status !== 'success') {
					resetGridToZero();
					return;
				}
				fillGridFromResponse(resp.data || []);
			})
			.catch(err => {
				if (err && err.name === 'AbortError') return;
				resetGridToZero();
			})
			.finally(() => {
				// libera edição se ainda estiver com nível selecionado
				const nivelAtual = $('select-nivel-turno-dias')?.value;
				if (nivelAtual) setInputsEnabled(true);
			});
	}

	function saveTurnoDias(ev) {
		// segura qualquer submit/refresh
		if (ev && typeof ev.preventDefault === 'function') ev.preventDefault();

		ensureSaveButtonEnabled();

		const idTurno = $('select-turno-dias')?.value;
		const idNivel = $('select-nivel-turno-dias')?.value;

		if (!idTurno) {
			alert('Turno inválido.');
			return;
		}

		// ALERTA QUE VOCÊ PEDIU (texto ajustado)
		if (!idNivel) {
			alert('Selecione o nível de ensino e informe a quantidade de aulas nos respectivos dias.');
			return;
		}

		const diasData = [];
		document.querySelectorAll('.turno-dia-input').forEach(input => {
			const uiName = input.getAttribute('data-dia-ui');
			const bdName = diaDisplayToBD[uiName];

			let val = (input.value || '').replace(/\D/g, '');
			if (!val) val = '0';

			let n = parseInt(val, 10);
			if (Number.isNaN(n)) n = 0;
			if (n < 0) n = 0;
			if (n > 99) n = 99;

			diasData.push({ dia_semana: bdName, aulas_no_dia: n });
		});

		const data = new URLSearchParams();
		data.append('id_turno', idTurno);
		data.append('id_nivel_ensino', idNivel);
		data.append('dias', JSON.stringify(diasData));

		fetch('/horarios/app/controllers/turno-dias/insertTurnoDias.php', {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: data
		})
			.then(r => r.json())
			.then(resp => {
				alert(resp?.message || 'Resposta inválida do servidor.');
				if (!resp || resp.status !== 'success') return;

				// CONFIRMAÇÃO QUE VOCÊ PEDIU
				const continuar = confirm('Deseja alterar a quantidade de aulas de outro nível?');

				if (continuar) {
					// mantém o modal aberto, reseta o nível e trava inputs
					const selNivel = $('select-nivel-turno-dias');
					if (selNivel) selNivel.value = '';

					setInputsEnabled(false);
					resetGridToZero();
					ensureSaveButtonEnabled();

					// foco no select para facilitar
					setTimeout(() => { selNivel?.focus(); }, 0);
				} else {
					hideModal();
				}
			})
			.catch(err => {
				console.error(err);
				alert('Erro ao salvar.');
			});
	}

	// Função global (chamada na listagem de turnos)
	window.openTurnoDiasModal = function (idTurno, nomeTurno) {
		currentTurnoId = idTurno;

		showModal();

		$('select-turno-dias').value = idTurno;
		$('nome-turno-dias').value = nomeTurno || '';

		renderTurnoDiasGrid();

		ensureSaveButtonEnabled();
		setInputsEnabled(false);
		resetGridToZero();

		loadNiveisEnsino().then(() => {
			const sel = $('select-nivel-turno-dias');
			if (sel) sel.value = '';
		});
	};

	document.addEventListener('DOMContentLoaded', function () {
		$('close-turno-dias-modal')?.addEventListener('click', hideModal);
		$('cancel-turno-dias-btn')?.addEventListener('click', hideModal);

		// clique do salvar (sempre habilitado)
		const btnSave = $('save-turno-dias-btn');
		if (btnSave) {
			btnSave.disabled = false;
			btnSave.addEventListener('click', saveTurnoDias);
		}

		$('select-nivel-turno-dias')?.addEventListener('change', function () {
			const nivelId = this.value;

			if (!currentTurnoId) return;

			if (!nivelId) {
				setInputsEnabled(false);
				resetGridToZero();
				return;
			}

			loadTurnoDiasSalvos(currentTurnoId, nivelId);
		});
	});
})();
