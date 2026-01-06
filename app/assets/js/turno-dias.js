// app/assets/js/turno-dias.js

const diasSemanaBD = ["Domingo","Segunda","Terca","Quarta","Quinta","Sexta","Sabado"];
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

/**
 * Abre o modal e exibe o nome do turno (read-only).
 * Em seguida, renderiza os 7 campos e carrega do BD (listTurnoDias.php).
 */
function openTurnoDiasModal(idTurno, nomeTurno) {
	const modal = document.getElementById('modal-turno-dias');
	modal.style.display = 'block';
	modal.classList.remove('fade-out');
	modal.classList.add('fade-in');

	const content = modal.querySelector('.modal-content');
	content.classList.remove('slide-up');
	content.classList.add('slide-down');

	// Preenche o hidden e o nome
	document.getElementById('select-turno-dias').value = idTurno;
	document.getElementById('nome-turno-dias').value = nomeTurno || '';

	// Renderiza a grid de 7 dias
	renderTurnoDiasGrid();

	// Carrega do BD e marca (aulas_no_dia) se existir
	loadTurnoDiasSalvos(idTurno);
}

function closeTurnoDiasModal() {
	const modal = document.getElementById('modal-turno-dias');
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

// Botões de fechar/cancelar
document.getElementById('close-turno-dias-modal')
	.addEventListener('click', closeTurnoDiasModal);
document.getElementById('cancel-turno-dias-btn')
	.addEventListener('click', closeTurnoDiasModal);


/**
 * Botão Salvar => envia "insertTurnoDias.php" (ou update) 
 * Sobe 7 registros (um por dia da semana).
 */
document.getElementById('save-turno-dias-btn').addEventListener('click', function() {
	const turnoId = document.getElementById('select-turno-dias').value;
	if (!turnoId) {
		alert('Turno inválido.');
		return;
	}

	// Pega todos os inputs type="text" (max 2 dígitos)
	const grid = document.querySelector('.turno-dias-grid');
	const diasInputs = grid.querySelectorAll('.turno-dia-input');
	
	// Monta array para enviar: [ { dia_semana: "Domingo", aulas_no_dia: 2 }, ... ]
	const diasData = [];
	diasInputs.forEach(input => {
		const uiName	 = input.getAttribute('data-dia-ui');	 // ex: "Dom"
		const bdName	 = diaDisplayToBD[uiName];							// ex: "Domingo"
		let val				= input.value.replace(/\D/g,'');			 // remove não dígitos
		if (!val) val = '0';
		if (parseInt(val,10) > 99) val = '99';								// limite
		diasData.push({
			dia_semana: bdName,
			aulas_no_dia: parseInt(val,10)
		});
	});

	// Envia para insertTurnoDias
	const data = new URLSearchParams();
	data.append('id_turno', turnoId);
	data.append('dias', JSON.stringify(diasData));

	fetch('/horarios/app/controllers/turno-dias/insertTurnoDias.php', {
		method: 'POST',
		headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
		body: data
	})
	.then(r => r.json())
	.then(resp => {
		alert(resp.message);
		if (resp.status === 'success') {
			closeTurnoDiasModal();
		}
	})
	.catch(err => console.error(err));
});

/**
 * Renderiza a grade: 7 colunas para Dom..Sab, cada uma com:
 * - label "Dom"
 * - input type="text" .turno-dia-input data-dia-ui="Dom" (até 2 dígitos)
 */
function renderTurnoDiasGrid() {
	const container = document.querySelector('.turno-dias-grid');
	container.innerHTML = '';

	// Podemos fazer uma única linha com 7 colunas
	const row = document.createElement('div');
	row.classList.add('turno-dias-row');

	for (let i=0; i<7; i++) {
		const col = document.createElement('div');
		col.classList.add('turno-dia-col');

		const label = document.createElement('label');
		label.textContent = diasSemanaUI[i]; // "Dom", "Seg", ...
		
		const input = document.createElement('input');
		input.type = 'text';
		input.maxLength = '2';
		input.classList.add('turno-dia-input');
		input.setAttribute('data-dia-ui', diasSemanaUI[i]); 
		input.value = '0'; // default

		// Mascara ou verificação: no "input", remove não-dígitos
		input.addEventListener('input', e => {
			let v = e.target.value.replace(/\D/g,'');
			if (v.length > 2) v = v.substring(0,2); 
			e.target.value = v;
		});

		col.appendChild(label);
		col.appendChild(input);
		row.appendChild(col);
	}

	container.appendChild(row);
}

/**
 * Carrega do BD (listTurnoDias.php?id_turno=XX) e preenche os inputs
 */
function loadTurnoDiasSalvos(idTurno) {
	const url = '/horarios/app/controllers/turno-dias/listTurnoDias.php?id_turno=' + idTurno;
	fetch(url)
		.then(r => r.json())
		.then(resp => {
			if (resp.status === 'success') {
				// resp.data => array de { id_turno_dia, id_turno, dia_semana, aulas_no_dia }
				const diasArray = resp.data; 
				diasArray.forEach(item => {
					// item.dia_semana ex: "Domingo"
					// Precisamos achar o input data-dia-ui="Dom"
					// => 1) converter "Domingo" p/ "Dom"
					const uiName = Object.keys(diaDisplayToBD).find(k => diaDisplayToBD[k]===item.dia_semana);
					if (!uiName) return;
					const input = document.querySelector(`.turno-dia-input[data-dia-ui="${uiName}"]`);
					if (input) {
						input.value = String(item.aulas_no_dia || '0');
					}
				});
			}
		})
		.catch(err => console.error(err));
}
