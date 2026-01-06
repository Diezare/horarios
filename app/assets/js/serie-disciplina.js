// app/assets/js/serie-disciplina.js

function openSerieDisciplinaModal() {
	const modal = document.getElementById('modal-serie-disciplina');
	modal.style.display = 'block';
	modal.classList.remove('fade-out');
	modal.classList.add('fade-in');

	const content = modal.querySelector('.modal-content');
	content.classList.remove('slide-up');
	content.classList.add('slide-down');

	// Carrega as disciplinas vinculadas e todas as disciplinas com info de professor
	const serieId = document.getElementById('select-serie-disciplina').value;
	loadDisciplinasCheckboxesSerie(serieId);
}

/************************************************
 * Fecha o modal de "Vincular Disciplinas"
 ************************************************/
function closeSerieDisciplinaModal() {
	const modal = document.getElementById('modal-serie-disciplina');
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

/************************************************
 * Botão [X] e Botão [Cancelar] -> Fechar modal
 ************************************************/
document.getElementById('close-serie-disciplina-modal')
	.addEventListener('click', closeSerieDisciplinaModal);
document.getElementById('cancel-serie-disciplina-btn')
	.addEventListener('click', closeSerieDisciplinaModal);

/************************************************
 * SALVAR vínculos de disciplinas à série
 * (sobrescreve tudo via insertSerieDisciplina.php)
 ************************************************/
document.getElementById('save-serie-disciplina-btn').addEventListener('click', function() {
	const serieId = document.getElementById('select-serie-disciplina').value;
	const container = document.getElementById('disciplinas-checkboxes');
	const labels = container.querySelectorAll('label.radio-like');
	const disciplinasData = [];
	let valid = true;
	
	// Percorre cada label para verificar as disciplinas marcadas
	for (const label of labels) {
		const checkbox = label.querySelector('input[type="checkbox"]');
		if (checkbox && checkbox.checked) {
		const id_disciplina = checkbox.value;
		const inputAulas = label.querySelector('.aulas-semana-input');
		// Pega o texto da disciplina (ex: "MAT - Bia")
		const disciplinaText = label.querySelector('.disciplina-text').textContent;
		if (inputAulas) {
			// Se o campo estiver vazio, alerta e interrompe
			if (inputAulas.value.trim() === '') {
			alert('Por favor, insira a quantidade de aulas para a disciplina ' + disciplinaText);
			valid = false;
			break;
			}
			const aulas = parseInt(inputAulas.value, 10);
			if (isNaN(aulas) || aulas <= 0) {
			alert('A quantidade de aulas deve ser maior que 0 para a disciplina ' + disciplinaText);
			valid = false;
			break;
			}
			disciplinasData.push({ id_disciplina, aulas_semana: aulas });
		}
		}
	}
	
	if (!valid) {
		return; // Não prossegue se houver algum erro
	}
	
	// Prepara os dados para envio: envia o JSON com o array de disciplinas e aulas
	const data = new URLSearchParams();
	data.append('id_serie', serieId);
	data.append('disciplinas', JSON.stringify(disciplinasData));
	
	fetch('/horarios/app/controllers/serie-disciplina/insertSerieDisciplina.php', {
		method: 'POST',
		headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
		body: data
	})
	.then(r => r.json())
	.then(response => {
		alert(response.message);
		if (response.status === 'success') {
		closeSerieDisciplinaModal();
		}
	})
	.catch(err => console.error(err));
	});
	
	

/************************************************
 * Carrega TODAS as disciplinas com info de professor
 * e marca as que já estão vinculadas à série.
 ************************************************/
function loadDisciplinasCheckboxesSerie(serieId) {
    const urlAllDisc = '/horarios/app/controllers/professor-disciplina/listProfessorDisciplina.php';
    const urlSerieDisc = '/horarios/app/controllers/serie-disciplina/listSerieDisciplina.php?id_serie=' + serieId;
    
    Promise.all([
        fetch(urlAllDisc).then(r => r.json()),
        fetch(urlSerieDisc).then(r => r.json())
    ])
    .then(([allDiscResponse, linkedResponse]) => {
        if (allDiscResponse.status === 'success' && linkedResponse.status === 'success') {
            const container = document.getElementById('disciplinas-checkboxes');
            container.innerHTML = '';

            const allDisciplinas = allDiscResponse.data;   // Lista única de disciplinas
            const linkedRows = linkedResponse.data;        // Vínculos já existentes para a série
    
            allDisciplinas.forEach(disc => {
                const label = document.createElement('label');
                label.classList.add('radio-like');
                label.style.display = 'block';

                const row1 = document.createElement('div');
                row1.style.display = 'flex';
                row1.style.alignItems = 'center';

                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.value = disc.id_disciplina;

                const linked = linkedRows.find(l => parseInt(l.id_disciplina) === parseInt(disc.id_disciplina));
                if (linked) {
                    checkbox.checked = true;
                }
                row1.appendChild(checkbox);

                const spanText = document.createElement('span');
                spanText.textContent = disc.sigla_disciplina;
                spanText.classList.add('disciplina-text');
                spanText.style.marginLeft = '5px';
                row1.appendChild(spanText);

                label.appendChild(row1);

                const row2 = document.createElement('div');
                row2.style.display = 'flex';
                row2.style.alignItems = 'center';
                row2.style.marginTop = '12px';
                row2.style.paddingRight = '18px';

                const qtdeLabel = document.createElement('span');
                qtdeLabel.textContent = 'Qtde. Aulas';
                qtdeLabel.classList.add('qtde-label');
                qtdeLabel.style.marginRight = '5px';
                row2.appendChild(qtdeLabel);

                const inputAulas = document.createElement('input');
                inputAulas.type = 'text';
                inputAulas.classList.add('aulas-semana-input');
                inputAulas.maxLength = 2;
                inputAulas.style.width = '70px';
                if (linked) {
                    inputAulas.value = linked.aulas_semana;
                } else {
                    inputAulas.value = '';
                }
                inputAulas.disabled = !checkbox.checked;

                inputAulas.addEventListener('input', function() {
                    this.value = this.value.replace(/\D/g, '').substring(0, 2);
                });
                row2.appendChild(inputAulas);

                label.appendChild(row2);

                // Adiciona classes para estilização visual
                label.classList.add('disciplina-bloco');
                if (checkbox.checked) {
                    label.classList.add('active-disciplina');
                } else {
                    label.classList.add('inactive-disciplina');
                }

                checkbox.addEventListener('change', function () {
                    if (this.checked) {
                        label.classList.add('active-disciplina');
                        label.classList.remove('inactive-disciplina');
                        inputAulas.disabled = false;
                    } else {
                        label.classList.remove('active-disciplina');
                        label.classList.add('inactive-disciplina');
                        inputAulas.disabled = true;
                        inputAulas.value = '';
                    }
                });

                container.appendChild(label);
            });
        } else {
            console.error('Erro ao carregar disciplinas ou vínculos:', allDiscResponse.message, linkedResponse.message);
        }
    })
    .catch(err => console.error(err));
}

	