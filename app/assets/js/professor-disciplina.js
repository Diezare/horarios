// app/assets/js/professor-disciplina.js

function openProfessorDisciplinaModal(professorId) {
	const modal = document.getElementById('modal-professor-disciplina');
	modal.style.display = 'block';
	modal.classList.remove('fade-out'); 

	const content = modal.querySelector('.modal-content');
	content.classList.remove('slide-up');
	content.classList.add('slide-down');

	// Se você ainda quiser carregar a lista de todos os professores no <select>, tudo bem:
	loadProfessoresForDisciplina();

	// Carrega as disciplinas e marca as já vinculadas ao professor
	loadDisciplinasCheckboxes(professorId);
}

/************************************************
 * Fecha o modal de "Vincular Disciplinas"
 ************************************************/
function closeProfessorDisciplinaModal() {
	const modal = document.getElementById('modal-professor-disciplina');
	const content = modal.querySelector('.modal-content');

	content.classList.remove('slide-down');
	content.classList.add('slide-up');
	modal.classList.add('fade-out');

	setTimeout(() => {
		modal.style.display = 'none';
		content.classList.remove('slide-up');
		modal.classList.remove('fade-out');
	}, 300);
}

/************************************************
 * Botão [X] e Botão [Cancelar] -> Fechar modal
 ************************************************/
document.getElementById('close-professor-disciplina-modal')
	.addEventListener('click', closeProfessorDisciplinaModal);

document.getElementById('cancel-professor-disciplina-btn')
	.addEventListener('click', closeProfessorDisciplinaModal);

/************************************************
 * SALVAR vínculos de disciplinas ao professor
 * (sobrescreve tudo via insertProfessorDisciplina.php)
 ************************************************/
document.getElementById('save-professor-disciplina-btn').addEventListener('click', function() {
	const professorId = document.getElementById('select-professor-disciplina').value;
	// Pega as checkboxes marcadas
	const checkboxes = document.querySelectorAll('#disciplinas-checkboxes input[type="checkbox"]');
	const disciplinas = [];
	checkboxes.forEach(cb => {
		if (cb.checked) {
			disciplinas.push(cb.value);
		}
	});
	
	// Monta dados para enviar (POST)
	const data = new URLSearchParams();
	data.append('id_professor', professorId);
	disciplinas.forEach(id => data.append('disciplinas[]', id));

	fetch('/horarios/app/controllers/professor-disciplina/insertProfessorDisciplina.php', {
		method: 'POST',
		headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
		body: data
	})
	.then(r => r.json())
	.then(response => {
		alert(response.message);
		if (response.status === 'success') {
			closeProfessorDisciplinaModal();
		}
	})
	.catch(err => console.error(err));
});

/************************************************
 * Carrega TODOS os professores e preenche <select>
 * (caso ainda queira usar o <select> no modal)
 ************************************************/
function loadProfessoresForDisciplina() {
	fetch('/horarios/app/controllers/professor/listProfessor.php')
		.then(r => r.json())
		.then(data => {
			if (data.status === 'success') {
				const select = document.getElementById('select-professor-disciplina');
				select.innerHTML = '';
				data.data.forEach(prof => {
					const option = document.createElement('option');
					option.value = prof.id_professor;
					option.textContent = prof.nome_completo;
					select.appendChild(option);
				});
			}
		})
		.catch(err => console.error(err));
}

/************************************************
 * Carrega TODAS as disciplinas (listDisciplina.php)
 * e as do professor (listProfessorDisciplina.php)
 * Marca as que já estiverem vinculadas
 ************************************************/
function loadDisciplinasCheckboxes(professorId) {
	// Endpoints
	const urlAllDisc = '/horarios/app/controllers/disciplina/listDisciplina.php';
	const urlProfDisc = '/horarios/app/controllers/professor-disciplina/listProfessorDisciplina.php?id_professor=' + professorId;

	// Faz as 2 requisições em paralelo
	Promise.all([
		fetch(urlAllDisc).then(r => r.json()),
		fetch(urlProfDisc).then(r => r.json())
	])
	.then(([allDiscResponse, assignedResponse]) => {
		if (allDiscResponse.status === 'success' && assignedResponse.status === 'success') {
			const container = document.getElementById('disciplinas-checkboxes');
			container.innerHTML = '';

			const allDisciplinas = allDiscResponse.data;   // Todas as disciplinas
			const assignedRows = assignedResponse.data;    // [{id_professor, id_disciplina, ...}, ...]

			// Extrai só os IDs das disciplinas já vinculadas
			const assignedIds = assignedRows.map(row => row.id_disciplina);

			// Monta as checkboxes
			allDisciplinas.forEach(disc => {
				const label = document.createElement('label');
				label.classList.add('radio-like');

				const checkbox = document.createElement('input');
				checkbox.type = 'checkbox';
				checkbox.value = disc.id_disciplina;

				// Se já estiver vinculado, marca
				if (assignedIds.includes(disc.id_disciplina)) {
					checkbox.checked = true;
				}

				const spanText = document.createElement('span');
				spanText.textContent = disc.nome_disciplina;
				spanText.classList.add('disciplina-text');

				label.appendChild(checkbox);
				label.appendChild(spanText);
				container.appendChild(label);
			});
		} else {
			console.error('Erro ao carregar disciplinas ou vínculos:', allDiscResponse.message, assignedResponse.message);
		}
	})
	.catch(err => console.error(err));
}
