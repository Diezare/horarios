// app/assets/js/professor-restricoes.js

// lista de dias no BD e correspondência para exibição
const diasSemanaBD = ["Domingo", "Segunda", "Terca", "Quarta", "Quinta", "Sexta", "Sabado"];
const diaDisplayMap = {
    "Domingo": "Domingo",
    "Segunda": "Segunda",
    "Terca": "Terça",
    "Quarta": "Quarta",
    "Quinta": "Quinta",
    "Sexta": "Sexta",
    "Sabado": "Sábado"
};

// valor default de aulas por dia
const DEFAULT_NUM_AULAS_DIA = 6;

function loadTurnoDias(turnoId) {
    return fetch('/horarios/app/controllers/turno-dias/listTurnoDias.php?id_turno=' + turnoId)
        .then(r => r.json())
        .then(data => {
            if (data.status !== 'success' || !Array.isArray(data.data)) {
                return {};
            }

            const mapping = {};
            data.data.forEach(item => {
                const qtd = parseInt(item.aulas_no_dia, 10);
                if (qtd > 0) {
                    mapping[item.dia_semana] = qtd;
                }
            });

            return mapping; // <-- só o que existe no banco
        })
        .catch(() => {
            return {}; // não inventa nada
        });
}

function renderDiasRestricoes(aulasPorDiaMap) {
    const grid = document.querySelector('.restricoes-grid');
    if (!grid) return;
    grid.innerHTML = '';

    // Renderiza APENAS os dias presentes no mapa (que vieram do banco)
    const diasAtivos = diasSemanaBD.filter(dia => Object.prototype.hasOwnProperty.call(aulasPorDiaMap, dia));

    diasAtivos.forEach((diaBD, idx) => {
        const diaDiv = document.createElement('div');
        diaDiv.classList.add('restricao-dia');
        diaDiv.setAttribute('data-dia', diaBD);

        const headerDiv = document.createElement('div');
        headerDiv.classList.add('restricao-dia-header');

        const checkAll = document.createElement('input');
        checkAll.type = 'checkbox';
        checkAll.classList.add('check-day-all');
        checkAll.addEventListener('change', function () {
            const aulas = diaDiv.querySelectorAll('.aulas-checkboxes input[type="checkbox"]');
            aulas.forEach(cb => cb.checked = this.checked);
        });

        const titulo = document.createElement('strong');
        titulo.textContent = diaDisplayMap[diaBD] || diaBD;

        headerDiv.appendChild(checkAll);
        headerDiv.appendChild(titulo);
        diaDiv.appendChild(headerDiv);

        const numAulas = aulasPorDiaMap[diaBD]; // aqui sempre existe
        const aulasDiv = document.createElement('div');
        aulasDiv.classList.add('aulas-checkboxes');

        for (let i = 1; i <= numAulas; i++) {
            const lbl = document.createElement('label');
            const cb = document.createElement('input');
            cb.type = 'checkbox';
            cb.value = i;
            lbl.appendChild(cb);
            lbl.appendChild(document.createTextNode(` ${i}ª Aula`));
            aulasDiv.appendChild(lbl);
        }

        diaDiv.appendChild(aulasDiv);
        grid.appendChild(diaDiv);

        // Separador entre dias ativos
        if (idx !== diasAtivos.length - 1) {
            const separator = document.createElement('div');
            separator.style.borderTop = '1px solid #ccc';
            separator.style.margin = '2px 0';
            grid.appendChild(separator);
        }
    });
}


/**
 * Carrega os anos letivos no select com placeholder
 */
function loadAnoLetivoOptions(withPlaceholder = true) {
    return fetch('/horarios/app/controllers/ano-letivo/listAnoLetivo.php')
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            const select = document.getElementById('select-ano-letivo');
            select.innerHTML = withPlaceholder ? '<option value="">-- Selecione o Ano --</option>' : '';
            data.data.forEach(ano => {
                const option = document.createElement('option');
                option.value = ano.id_ano_letivo;
                option.textContent = ano.ano;
                select.appendChild(option);
            });
        }
    })
    .catch(console.error);
}

/**
 * Carrega só os turnos vinculados ao professor para o select de turno no modal de restrições
 */
function loadTurnoOptions(professorId, withPlaceholder = true) {
    return fetch(`/horarios/app/controllers/professor-turno/listProfessorTurno.php?id_professor=${professorId}`)
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            const select = document.getElementById('select-turno');
            select.innerHTML = withPlaceholder ? '<option value="">-- Selecione o Turno --</option>' : '';

            // Extrai turnos únicos vinculados ao professor
            const turnos = data.data;
            turnos.forEach(turno => {
                const option = document.createElement('option');
                option.value = turno.id_turno;
                option.textContent = turno.nome_turno;
                select.appendChild(option);
            });
        }
    }).catch(console.error);
}

/**
 * Verifica se o professor tem outros turnos além do atual
 */
function verificarOutrosTurnos(professorId, turnoAtualId) {
    return fetch(`/horarios/app/controllers/professor-turno/listProfessorTurno.php?id_professor=${professorId}`)
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success' && data.data.length > 1) {
                // Filtra outros turnos além do atual
                const outrosTurnos = data.data.filter(turno => turno.id_turno != turnoAtualId);
                
                if (outrosTurnos.length > 0) {
                    const turnosNomes = outrosTurnos.map(t => t.nome_turno).join(', ');
                    return {
                        temOutros: true,
                        turnos: outrosTurnos,
                        mensagem: `Este professor também está vinculado aos turnos: ${turnosNomes}. Deseja configurar as restrições para algum outro turno?`
                    };
                }
            }
            return { temOutros: false };
        })
        .catch(() => {
            return { temOutros: false };
        });
}

/**
 * Ao abrir modal, inicializa selects e limpa grade.
 */
function openProfessorRestricoesModal(professorId, professorName) {
    const modal = document.getElementById('modal-professor-restricoes');
    modal.style.display = 'block';
    modal.classList.remove('fade-out');
    modal.classList.add('fade-in');

    const content = modal.querySelector('.modal-content');
    content.classList.remove('slide-up');
    content.classList.add('slide-down');

    document.getElementById('prof-restricoes-nome').value = professorName || '';
    document.getElementById('select-professor-restricoes').value = professorId;

    // Limpa grade e selects
    document.querySelector('.restricoes-grid').innerHTML = '';
    document.getElementById('select-ano-letivo').innerHTML = '<option value="">-- Selecione o Ano --</option>';
    document.getElementById('select-turno').innerHTML = '<option value="">-- Selecione o Turno --</option>';

    // Carrega opções
    Promise.all([
        loadAnoLetivoOptions(true),
        loadTurnoOptions(professorId, true)
    ]).then(() => {
        console.log('Selects carregados');
    });

    // Remove e adiciona event listeners para evitar duplicidade
    const anoSelect = document.getElementById('select-ano-letivo');
    anoSelect.removeEventListener('change', onAnoOrTurnoChange);
    anoSelect.addEventListener('change', onAnoOrTurnoChange);

    const turnoSelect = document.getElementById('select-turno');
    turnoSelect.removeEventListener('change', onAnoOrTurnoChange);
    turnoSelect.addEventListener('change', onAnoOrTurnoChange);

    // Atualiza indicador de turno
    updateTurnoIndicator();
}

/**
 * Quando ano ou turno mudar, carrega a grade correspondente
 */

function onAnoOrTurnoChange() {
    const professorId = document.getElementById('select-professor-restricoes').value;
    const anoLetivoId = document.getElementById('select-ano-letivo').value;
    const turnoId = document.getElementById('select-turno').value;

    const professorNum = parseInt(professorId, 10);
    const anoLetivoNum = parseInt(anoLetivoId, 10);
    const turnoIdNum = parseInt(turnoId, 10);

    if (!Number.isInteger(professorNum) || professorNum <= 0 ||
        !Number.isInteger(anoLetivoNum) || anoLetivoNum <= 0 ||
        !Number.isInteger(turnoIdNum) || turnoIdNum <= 0) {
        document.querySelector('.restricoes-grid').innerHTML = '';
        return;
    }

    updateTurnoIndicator();

    loadTurnoDias(turnoIdNum).then(aulasPorDiaMap => {
        if (!aulasPorDiaMap || Object.keys(aulasPorDiaMap).length === 0) {
            document.querySelector('.restricoes-grid').innerHTML = '';
            alert('Este turno não possui dias/aulas configurados.');
            return;
        }

        renderDiasRestricoes(aulasPorDiaMap);
        loadRestricoesSalvas(professorNum, anoLetivoNum, turnoIdNum);
    });

}

/**
 * Atualiza indicador visual do turno selecionado
 */
function updateTurnoIndicator() {
    const turnoSelect = document.getElementById('select-turno');
    const selectedOption = turnoSelect.options[turnoSelect.selectedIndex];
    const indicator = document.getElementById('turno-indicator');
    
    if (indicator) {
        const turnoNome = document.getElementById('turno-nome');
        if (selectedOption.value) {
            turnoNome.textContent = selectedOption.text;
            indicator.style.display = 'block';
        } else {
            indicator.style.display = 'none';
        }
    }
}

/**
 * Carrega restrições salvas para exibir na grade
 */
function loadRestricoesSalvas(professorId, anoLetivoId, turnoId) {
    const url = `/horarios/app/controllers/professor-restricoes/listProfessorRestricoes.php?id_professor=${professorId}&id_ano_letivo=${anoLetivoId}&id_turno=${turnoId}`;
    
    fetch(url)
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                // Desmarca tudo antes de aplicar restrições salvas
                document.querySelectorAll('.restricoes-grid .aulas-checkboxes input[type="checkbox"]').forEach(cb => cb.checked = false);
                
                // Marca as restrições salvas
                data.data.forEach(row => {
                    const dia = row.dia_semana;
                    const aula = row.numero_aula;
                    const diaDiv = document.querySelector(`.restricao-dia[data-dia="${dia}"]`);
                    if (diaDiv) {
                        const cb = diaDiv.querySelector(`.aulas-checkboxes input[value="${aula}"]`);
                        if (cb) cb.checked = true;
                    }
                });

                // Atualiza "check all" por dia
                document.querySelectorAll('.restricao-dia').forEach(diaDiv => {
                    const checkAll = diaDiv.querySelector('.check-day-all');
                    if (checkAll) {
                        const aulas = diaDiv.querySelectorAll('.aulas-checkboxes input[type="checkbox"]');
                        const allChecked = [...aulas].every(a => a.checked);
                        checkAll.checked = allChecked;
                    }
                });
            }
        })
        .catch(console.error);
}

/**
 * Fecha modal com animação
 */
function closeProfessorRestricoesModal() {
    const modal = document.getElementById('modal-professor-restricoes');
    const content = modal.querySelector('.modal-content');
    content.classList.remove('slide-down');
    content.classList.add('slide-up');
    modal.classList.remove('fade-in');
    modal.classList.add('fade-out');

    setTimeout(() => {
        modal.style.display = 'none';
        content.classList.remove('slide-up');
        modal.classList.remove('fade-out');
        
        // Limpa tudo ao fechar
        document.querySelector('.restricoes-grid').innerHTML = '';
        document.getElementById('select-ano-letivo').value = '';
        document.getElementById('select-turno').value = '';
        
        // Esconde indicador
        const indicator = document.getElementById('turno-indicator');
        if (indicator) {
            indicator.style.display = 'none';
            // Remove mensagem adicional se houver
            const extraMsg = indicator.querySelector('div');
            if (extraMsg) extraMsg.remove();
        }
    }, 300);
}

/**
 * Salvar restrições no backend com verificação de outros turnos
 */

// BOTÃO SALVAR (completo e corrigido)
document.getElementById('save-professor-restricoes-btn').addEventListener('click', async function () {
    const professorId = document.getElementById('select-professor-restricoes').value;
    const anoLetivoId = document.getElementById('select-ano-letivo').value;
    const turnoId = document.getElementById('select-turno').value;

    const professorNum = parseInt(professorId, 10);
    const anoLetivoNum = parseInt(anoLetivoId, 10);
    const turnoNum = parseInt(turnoId, 10);

    if (!Number.isInteger(professorNum) || professorNum <= 0 ||
        !Number.isInteger(anoLetivoNum) || anoLetivoNum <= 0 ||
        !Number.isInteger(turnoNum) || turnoNum <= 0) {
        alert('Selecione corretamente o Professor, Ano Letivo e Turno.');
        return;
    }

    // Coleta restrições da grade
    const restricoes = {};
    document.querySelectorAll('.restricoes-grid .restricao-dia').forEach(diaDiv => {
        const diaBD = diaDiv.getAttribute('data-dia');
        const aulaCbs = diaDiv.querySelectorAll('.aulas-checkboxes input[type="checkbox"]');
        restricoes[diaBD] = [];
        aulaCbs.forEach(cb => {
            if (cb.checked) restricoes[diaBD].push(parseInt(cb.value, 10)); // salva como número
        });
    });

    const restricoesJson = JSON.stringify(restricoes);

    // Monta POST (sem duplicar chaves)
    const data = new URLSearchParams();
    data.append('id_professor', professorNum);
    data.append('id_ano_letivo', anoLetivoNum);
    data.append('id_turno', turnoNum);
    data.append('restricoes', restricoesJson);

    try {
        // Salva restrições do turno atual
        const response = await fetch('/horarios/app/controllers/professor-restricoes/updateProfessorRestricoes.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: data.toString()
        });

        // Se o backend morrer e não retornar JSON, evita "Unexpected end of JSON input"
        const raw = await response.text();
        let resp;
        try {
            resp = JSON.parse(raw);
        } catch (e) {
            console.error('Resposta não-JSON do servidor:', raw);
            alert('Erro no servidor ao salvar restrições. Verifique o log do PHP.');
            return;
        }

        alert(resp.message || 'Operação concluída.');

        if (resp.status === 'success') {
            // Verifica se há outros turnos
            const infoTurnos = await verificarOutrosTurnos(professorNum, turnoNum);

            if (infoTurnos.temOutros) {
                const continuar = confirm(infoTurnos.mensagem);

                if (continuar) {
                    // Mantém modal aberto para escolher outro turno
                    document.querySelector('.restricoes-grid').innerHTML = '';
                    document.getElementById('select-turno').focus();

                    // Remove mensagem extra do indicador (se existir)
                    const indicator = document.getElementById('turno-indicator');
                    if (indicator) {
                        const extraMsg = indicator.querySelector('div');
                        if (extraMsg) extraMsg.remove();
                    }
                } else {
                    closeProfessorRestricoesModal();
                }
            } else {
                closeProfessorRestricoesModal();
            }
        }
    } catch (error) {
        console.error(error);
        alert('Erro ao salvar restrições.');
    }
});

// Eventos para fechar modal
document.getElementById('close-professor-restricoes-modal').addEventListener('click', closeProfessorRestricoesModal);
document.getElementById('cancel-professor-restricoes-btn').addEventListener('click', closeProfessorRestricoesModal);

// Evento para atualizar indicador quando turno mudar
document.getElementById('select-turno').addEventListener('change', updateTurnoIndicator);

/**
 * Adiciona HTML para o indicador de turno se não existir
 */
function ensureTurnoIndicator() {
    if (!document.getElementById('turno-indicator')) {
        const turnoSelectContainer = document.getElementById('select-turno').parentNode;
        const indicator = document.createElement('div');
        indicator.id = 'turno-indicator';
        indicator.style.margin = '10px 0';
        indicator.style.padding = '10px 15px';
        indicator.style.background = '#f0f8ff';
        indicator.style.border = '1px solid #b3d9ff';
        indicator.style.borderRadius = '4px';
        indicator.style.display = 'none';
        indicator.style.width = 'fit-content';
        indicator.style.maxWidth = '100%';
        indicator.style.marginLeft = '0';
        indicator.innerHTML = '<span style="color: #0066cc; font-weight: normal;">Configurando restrições para o turno:</span> <strong id="turno-nome" style="color: #004080;"></strong>';
        
        // Insere após o container do select de turno
        turnoSelectContainer.parentNode.insertBefore(indicator, turnoSelectContainer.nextSibling);
    }
}

// Inicializa indicador quando o DOM carregar
document.addEventListener('DOMContentLoaded', ensureTurnoIndicator);