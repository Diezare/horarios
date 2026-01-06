// app/assets/js/professor-turno.js

function openProfessorTurnoModal(professorId) {
	const modal = document.getElementById('modal-professor-turno');
	modal.style.display = 'block';
	modal.classList.remove('fade-out');

	const content = modal.querySelector('.modal-content');
	content.classList.remove('slide-up');
	content.classList.add('slide-down');

	// Carrega a lista de turnos e marca os já vinculados ao professor
	loadTurnosCheckboxes(professorId);
}

/************************************************
 * Fecha o modal de "Vincular Turnos"
 ************************************************/
function closeProfessorTurnoModal() {
	const modal = document.getElementById('modal-professor-turno');
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
document.getElementById('close-professor-turno-modal')
	.addEventListener('click', closeProfessorTurnoModal);

document.getElementById('cancel-professor-turno-btn')
	.addEventListener('click', closeProfessorTurnoModal);

/************************************************
 * SALVAR vínculos de turnos ao professor
 * (sobrescreve tudo via insertProfessorTurno.php)
 ************************************************/
document.getElementById('save-professor-turno-btn').addEventListener('click', function() {
	const professorId = document.getElementById('select-professor-turno').value;
	
	// Pega as checkboxes marcadas
	const checkboxes = document.querySelectorAll('#turnos-checkboxes input[type="checkbox"]');
	const turnosSelecionados = [];
	checkboxes.forEach(cb => {
		if (cb.checked) {
			turnosSelecionados.push(cb.value);
		}
	});
	
	// Monta dados para enviar (POST)
	const data = new URLSearchParams();
	data.append('id_professor', professorId);
	turnosSelecionados.forEach(id => data.append('turnos[]', id));

	fetch('/horarios/app/controllers/professor-turno/insertProfessorTurno.php', {
		method: 'POST',
		headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
		body: data
	})
	.then(r => r.json())
	.then(response => {
		alert(response.message);
		if (response.status === 'success') {
			closeProfessorTurnoModal();
		}
	})
	.catch(err => console.error(err));
});

/************************************************
 * Carrega TODOS os turnos (listTurno.php)
 * e os que o professor já tem (listProfessorTurno.php)
 * Marca as que já estiverem vinculadas
 ************************************************/
function loadTurnosCheckboxes(professorId) {
	// Endpoints
	const urlAllTurnos = '/horarios/app/controllers/turno/listTurno.php';
	const urlProfTurnos = '/horarios/app/controllers/professor-turno/listProfessorTurno.php?id_professor=' + professorId;

	// Faz as 2 requisições em paralelo
	Promise.all([
		fetch(urlAllTurnos).then(r => r.json()),
		fetch(urlProfTurnos).then(r => r.json())
	])
	.then(([allTurnosResp, assignedResp]) => {
		if (allTurnosResp.status === 'success' && assignedResp.status === 'success') {
			const container = document.getElementById('turnos-checkboxes');
			container.innerHTML = '';

			// Array de todos os turnos
			const allTurnos = allTurnosResp.data;	// [ {id_turno, nome_turno, ...}, ... ]
			// Turnos já vinculados
			const assignedRows = assignedResp.data; // [ {id_professor, id_turno, ...}, ...]

			// Extrai só os IDs dos turnos já vinculados
			const assignedIds = assignedRows.map(row => row.id_turno);

			// Monta as checkboxes
			allTurnos.forEach(t => {
				const label = document.createElement('label');
				label.classList.add('radio-like');

				const checkbox = document.createElement('input');
				checkbox.type = 'checkbox';
				checkbox.value = t.id_turno;

				// Se já estiver vinculado, marca
				if (assignedIds.includes(t.id_turno)) {
					checkbox.checked = true;
				}

				const spanText = document.createElement('span');
				spanText.textContent = t.nome_turno;
				spanText.classList.add('disciplina-text'); // pode usar outra classe, ou "turno-text"

				label.appendChild(checkbox);
				label.appendChild(spanText);
				container.appendChild(label);
			});
		} else {
			console.error('Erro ao carregar turnos ou vínculos:', allTurnosResp.message, assignedResp.message);
		}
	})
	.catch(err => console.error(err));
}
