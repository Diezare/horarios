// app/assets/js/professor-categoria.js
document.addEventListener('DOMContentLoaded', function() {
	// Referências do modal "Vincular Professores"
	const modalVinc = document.getElementById('modal-vincular-professores');
	const btnCloseVinc = document.getElementById('close-vincular-prof-modal');
	const btnCancelVinc = document.getElementById('btn-cancel-vinculo-prof');
	const btnSaveVinc = document.getElementById('btn-save-vinculo-prof');
	const categoriaNomeInput = document.getElementById('categoria-nome-readonly');
	const disciplinaSelect = document.getElementById('select-disciplina-prof');
	const professorsContainer = document.getElementById('professors-checkbox-container');

	let currentCategoriaId = 0;

	/**
	 * Exposto globalmente para que "categoria.js" possa chamar:
	 *   openVincularProfModal(id_categoria, nomeModalidade, nomeCategoria)
	 */
	window.openVincularProfModal = function(idCategoria, nomeModalidade, nomeCat) {
		currentCategoriaId = idCategoria;
		const texto = nomeModalidade + ' - ' + nomeCat;
		categoriaNomeInput.value = texto;

		// 1) Carrega a lista de disciplinas
		loadDisciplinas().then(() => {
			// 2) Depois carrega os professores da disciplina (inicial)
			loadProfessoresPorDisciplina();
		});

		openModal();
	};

	function openModal() {
		modalVinc.style.display = 'block';
		modalVinc.classList.remove('fade-out');
		modalVinc.classList.add('fade-in');

		const content = modalVinc.querySelector('.modal-content');
		content.classList.remove('slide-up');
		content.classList.add('slide-down');
	}

	function closeModal() {
		const content = modalVinc.querySelector('.modal-content');
		content.classList.remove('slide-down');
		content.classList.add('slide-up');
		modalVinc.classList.remove('fade-in');
		modalVinc.classList.add('fade-out');

		setTimeout(() => {
			modalVinc.style.display = 'none';
			content.classList.remove('slide-up');
			modalVinc.classList.remove('fade-out');
			professorsContainer.innerHTML = '';
		}, 300);
	}

	if (btnCloseVinc) btnCloseVinc.addEventListener('click', closeModal);
	if (btnCancelVinc) btnCancelVinc.addEventListener('click', closeModal);

	/**
	 * Carrega TODAS as disciplinas.
	 * Se achar “Educação Física”, coloca primeiro no select, por exemplo.
	 */
	function loadDisciplinas() {
		disciplinaSelect.innerHTML = '<option value="">Carregando...</option>';
		return fetch('/horarios/app/controllers/professor-disciplina/listProfessorDisciplina.php')
			.then(r => r.json())
			.then(resp => {
				if (resp.status !== 'success' || !Array.isArray(resp.data)) {
					disciplinaSelect.innerHTML = '<option value="">Selecione</option>';
					return;
				}
				const discs = resp.data;
				if (!discs.length) {
					disciplinaSelect.innerHTML = '<option value="">Nenhuma disciplina</option>';
					return;
				}

				let eduFisId = 0;
				discs.forEach(d => {
					if (d.nome_disciplina.toLowerCase() === 'educação física') {
						eduFisId = d.id_disciplina;
					}
				});

				let html = '';
				let foundEdu = false;
				if (eduFisId > 0) {
					const ed = discs.find(x => x.id_disciplina == eduFisId);
					if (ed) {
						html += `<option value="${ed.id_disciplina}">${ed.nome_disciplina}</option>`;
						foundEdu = true;
					}
				}
				discs.forEach(d => {
					if (foundEdu && d.id_disciplina == eduFisId) return;
					html += `<option value="${d.id_disciplina}">${d.nome_disciplina}</option>`;
				});
				if (!foundEdu) {
					disciplinaSelect.innerHTML = `<option value="">Selecione</option>` + html;
				} else {
					disciplinaSelect.innerHTML = html;
				}
			})
			.catch(err => {
				console.error('Erro ao carregar disciplinas:', err);
				disciplinaSelect.innerHTML = '<option value="">Erro</option>';
			});
	}

	// Ao mudar a disciplina no <select>, recarrega a lista de professores
	if (disciplinaSelect) {
		disciplinaSelect.addEventListener('change', loadProfessoresPorDisciplina);
	}

	/**
	 * Carrega professores (via listProfessorDisciplinaCategoria.php?id_disciplina=...)
	 * e depois chama marcarProfessoresJaVinculados() para checkar os já vinculados.
	 */
	function loadProfessoresPorDisciplina() {
		const idDisc = parseInt(disciplinaSelect.value) || 0;
		if (!idDisc) {
			professorsContainer.innerHTML = '<p>Selecione uma disciplina.</p>';
			return;
		}
		professorsContainer.innerHTML = '<p>Carregando professores...</p>';

		// 1) Carrega todos os professores da disciplina
		fetch('/horarios/app/controllers/professor-disciplina/listProfessorDisciplinaCategoria.php?id_disciplina=' + idDisc)
			.then(r => r.json())
			.then(resp => {
				if (resp.status !== 'success') {
					throw new Error('Erro ao buscar professores da disciplina.');
				}
				const profs = resp.data || [];
				if (!profs.length) {
					professorsContainer.innerHTML = '<p>Nenhum professor vinculado a essa disciplina.</p>';
					return;
				}

				// Monta HTML dos checkboxes
				let html = '';
				profs.forEach(p => {
					html += `
						<label class="radio-like" style="display:flex; align-items:center; gap:5px;">
							<input type="checkbox" class="chk-prof" value="${p.id_professor}">
							<span>${p.nome_completo}</span>
						</label>
					`;
				});
				professorsContainer.innerHTML = html;

				// 2) Marcar os que já estiverem vinculados a esta categoria
				marcarProfessoresJaVinculados();
			})
			.catch(err => {
				console.error(err);
				professorsContainer.innerHTML = `<p>Erro: ${err.message}</p>`;
			});
	}

	// Verifica quais professores estão em professor_categoria para a currentCategoriaId
	function marcarProfessoresJaVinculados() {
		if (!currentCategoriaId) return;

		// Chama listProfessorCategoria.php?id_categoria=...
		fetch('/horarios/app/controllers/professor-categoria/listProfessorCategoria.php?id_categoria=' + currentCategoriaId)
			.then(r => r.json())
			.then(resp => {
				if (resp.status === 'success') {
					const vinculados = resp.data || []; 
					const vinculadosIds = vinculados.map(v => parseInt(v.id_professor, 10));

					const chks = professorsContainer.querySelectorAll('.chk-prof');
					chks.forEach(chk => {
						const pid = parseInt(chk.value, 10);
						if (vinculadosIds.includes(pid)) {
							chk.checked = true;
						}
					});
				}
			})
			.catch(err => console.error('Erro ao marcar já vinculados:', err));
	}

	/**
	 * Salvar => deleta vínculos + insere os novos
	 */
	function saveVinculoProfessores() {
		if (currentCategoriaId <= 0) {
			alert('Categoria inválida.');
			return;
		}
		const chks = professorsContainer.querySelectorAll('.chk-prof');
		const selected = [];
		chks.forEach(chk => {
			if (chk.checked) selected.push(chk.value);
		});

		const formData = new URLSearchParams();
		formData.append('id_categoria', currentCategoriaId);
		selected.forEach(pid => formData.append('professores[]', pid));

		fetch('/horarios/app/controllers/professor-categoria/insertProfessorCategoria.php', {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: formData
		})
		.then(r => r.json())
		.then(resp => {
			alert(resp.message);
			if (resp.status === 'success') {
				closeModal();
			}
		})
		.catch(err => console.error(err));
	}

	if (btnSaveVinc) {
		btnSaveVinc.addEventListener('click', saveVinculoProfessores);
	}
});
