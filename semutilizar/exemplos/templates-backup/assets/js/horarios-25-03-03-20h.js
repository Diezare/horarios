// app/assets/js/horarios.js
document.addEventListener('DOMContentLoaded', function() {

    // =============================================
    // ELEMENTOS DA PÁGINA
    // =============================================
    const selectAnoLetivo       = document.getElementById('selectAnoLetivo');
    const selectNivelEnsino     = document.getElementById('selectNivelEnsino');
    const selectTurma           = document.getElementById('selectTurma');
    const btnImprimir           = document.getElementById('btnImprimir');
    const btnAutomatico         = document.getElementById('btn-automatic');

    const gradeContainer        = document.getElementById('grade-container');
    const modalAutomatico       = document.getElementById('modal-automatico');
    const modalExtra            = document.getElementById('modal-extra');

    // Cria div de professores se não houver
    let quadroProfessores = document.getElementById('quadro-professores');
    if (!quadroProfessores) {
        const contentData = document.querySelector('.content-data');
        quadroProfessores = document.createElement('div');
        quadroProfessores.id = 'quadro-professores';
        contentData.appendChild(quadroProfessores);
    }

    // =============================================
    // VARIÁVEIS GLOBAIS
    // =============================================
    let idAnoSelecionado         = null;
    let idNivelEnsinoSelecionado = null;
    let idTurmaSelecionada       = null;
    let editingEnabled           = false;

    let allHorariosDoAno         = [];     // Lista de todos os horários do ano letivo (para checar conflitos de prof).
    let professorRestricoesMap   = {};     // Mapa das restrições de professor (por dia_semana / numero_aula).
    let dadosTurma               = null;   // Objeto com "horarios", "turma", "serie_disciplinas", "professores" etc.
    let profDiscTurmaMap         = {};     // Mapeia [id_turma][id_disciplina] => lista de id_professores

    let turmasMap                = {};     // Mapeia id_turma => { id_serie, nome_serie, nome_turma, nome_turno }

    // Intervalos da turma
    let intervalPositions        = [];
    let extraClassMapping        = {};

    // NOVO: quantidade de turmas existentes para a mesma série (para dividir a carga de aulas)
    let totalTurmasDaSerie       = 1;

    // NOVO: controle de aulas já usadas da disciplina na turma
    // form: usedDisciplineCount[discId] = quantidade de vezes já usada na turma
    let usedDisciplineCount      = {};

    // NOVO: limite de aulas para cada disciplina em cada turma
    // form: disciplineWeeklyLimit[discId] = limite daquela disciplina na turma
    let disciplineWeeklyLimit    = {};

    // =============================================
    // 1) CARREGAR ANOS LETIVOS
    // =============================================
    async function loadAnosLetivos() {
        try {
            let resp = await fetch('/horarios/app/controllers/ano-letivo/listAnoLetivo.php')
                             .then(r => r.json());
            if (resp.status === 'success') {
                resp.data.forEach(ano => {
                    const opt = document.createElement('option');
                    opt.value = ano.id_ano_letivo;
                    opt.textContent = ano.ano;
                    selectAnoLetivo.appendChild(opt);
                });
            }
        } catch (err) {
            console.error(err);
        }
    }

    selectAnoLetivo.addEventListener('change', () => {
        idAnoSelecionado = selectAnoLetivo.value;
        resetNivelEnsinoSelection();
        resetTurmaSelection();
        if (idAnoSelecionado) {
            loadNiveisPorAno(idAnoSelecionado);
        }
    });

    // =============================================
    // 2) CARREGAR NÍVEIS POR ANO LETIVO
    // =============================================
    async function loadNiveisPorAno(idAno) {
        try {
            let resp = await fetch(`/horarios/app/controllers/nivel-ensino/listNivelEnsino.php?id_ano_letivo=${idAno}`)
                             .then(r => r.json());
            selectNivelEnsino.innerHTML = '<option value="">-- Selecione o Nível --</option>';
            if (resp.status === 'success' && resp.data.length > 0) {
                selectNivelEnsino.disabled = false;
                resp.data.forEach(niv => {
                    const opt = document.createElement('option');
                    opt.value = niv.id_nivel_ensino;
                    opt.textContent = niv.nome_nivel_ensino;
                    selectNivelEnsino.appendChild(opt);
                });
                if (resp.data.length === 1) {
                    selectNivelEnsino.value = resp.data[0].id_nivel_ensino;
                    idNivelEnsinoSelecionado = selectNivelEnsino.value;
                    btnAutomatico.disabled = false;
                    loadTurmasPorAnoENivel(idAnoSelecionado, idNivelEnsinoSelecionado);
                }
            } else {
                selectNivelEnsino.innerHTML = '<option value="">Nenhum nível encontrado</option>';
                selectNivelEnsino.disabled = true;
            }
        } catch (err) {
            console.error(err);
        }
    }

    selectNivelEnsino.addEventListener('change', () => {
        idNivelEnsinoSelecionado = selectNivelEnsino.value;
        resetTurmaSelection();
        if (idNivelEnsinoSelecionado) {
            btnAutomatico.disabled = false;
            loadTurmasPorAnoENivel(idAnoSelecionado, idNivelEnsinoSelecionado);
        } else {
            btnAutomatico.disabled = true;
        }
    });

    // =============================================
    // 3) BOTÃO AUTOMÁTICO
    // =============================================
    btnAutomatico.disabled = true;
    btnAutomatico.addEventListener('click', () => {
        if (!idAnoSelecionado || !idNivelEnsinoSelecionado) return;
        // Se o dropdown de turmas tiver somente o placeholder, não há turmas cadastradas
        if (selectTurma.options.length <= 1) {
            alert("Não é possível gerar horários para o nível de ensino selecionado, pois não existem turmas cadastradas.");
            return;
        }
        openModalAutomatico();
    });

    function openModalAutomatico() {
        if (!modalAutomatico) {
            alert('Modal de geração automática não encontrado!');
            return;
        }
        modalAutomatico.style.display = 'block';

        const btnConf = modalAutomatico.querySelector('#btnConfirmarAutomatico');
        const btnCanc = modalAutomatico.querySelector('#btnCancelarAutomatico');
        const btnClose = modalAutomatico.querySelector('.close-modal-auto');

        btnConf.onclick = async () => {
            await gerarHorariosAutomaticos();
            closeModalAutomatico();
        };
        btnCanc.onclick = () => closeModalAutomatico();
        btnClose.onclick = () => closeModalAutomatico();
    }
    function closeModalAutomatico() {
        modalAutomatico.style.display = 'none';
    }

    async function gerarHorariosAutomaticos() {
        try {
            // Aqui seria interessante que o back-end também respeitasse o "disciplineWeeklyLimit".
            // Podemos enviar esses limites via POST ou recalcular no PHP, mas ao menos enviamos:
            const bodyObj = {
                id_ano_letivo: idAnoSelecionado,
                id_nivel_ensino: idNivelEnsinoSelecionado
            };

            // Exemplificando uma forma de também enviar os limites:
            // bodyObj.disciplineLimits = disciplineWeeklyLimit; // se precisarmos no servidor

            let body = new URLSearchParams(bodyObj);

            let resp = await fetch('/horarios/app/controllers/horarios/gerarHorariosAutomaticos.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body
            }).then(r => r.json());

            if (resp.status === 'success') {
                alert('Horários gerados automaticamente com sucesso!');
                if (idTurmaSelecionada) {
                    await carregarTudo();  // Recarrega horários e dados
                    montarGrade();
                }
            } else {
                alert(resp.message || 'Erro ao gerar horários!');
            }
        } catch (err) {
            console.error(err);
            alert('Ocorreu um erro ao gerar horários automáticos.');
        }
    }

    // =============================================
    // 4) CARREGAR TURMAS (POR ANO + NÍVEL)
    // =============================================
    async function loadTurmasPorAnoENivel(idAno, idNivel) {
        try {
            let resp = await fetch(`/horarios/app/controllers/turma/listTurmaPorAnoLetivo.php?id_ano_letivo=${idAno}&id_nivel_ensino=${idNivel}`)
                             .then(r => r.json());
            if (resp.status === 'success' && resp.data.length > 0) {
                selectTurma.innerHTML = '<option value="">-- Selecione a Turma --</option>';
                selectTurma.disabled = false;
                resp.data.forEach(t => {
                    const opt = document.createElement('option');
                    opt.value = t.id_turma;
                    opt.textContent = `${t.nome_serie} ${t.nome_turma} - ${t.nome_turno}`;
                    selectTurma.appendChild(opt);

                    // Guardamos no turmasMap
                    turmasMap[t.id_turma] = {
                        id_serie: t.id_serie,
                        nome_serie: t.nome_serie,
                        nome_turma: t.nome_turma,
                        nome_turno: t.nome_turno
                    };
                });
            } else {
                selectTurma.innerHTML = '<option value="">-- Selecione a Turma --</option>';
                selectTurma.disabled = true;
            }
        } catch (err) {
            console.error(err);
        }
    }

    function resetNivelEnsinoSelection() {
        selectNivelEnsino.innerHTML = '<option value="">-- Selecione o Nível --</option>';
        selectNivelEnsino.disabled = true;
        btnAutomatico.disabled = true;
        idNivelEnsinoSelecionado = null;
    }

    function resetTurmaSelection() {
        selectTurma.innerHTML = '<option value="">-- Selecione a Turma --</option>';
        selectTurma.disabled = true;
        gradeContainer.innerHTML = '';
        quadroProfessores.innerHTML = '';
        idTurmaSelecionada = null;
        editingEnabled = false;
        dadosTurma = null;
        allHorariosDoAno = [];
        professorRestricoesMap = {};
        profDiscTurmaMap = {};
        intervalPositions = [];
        extraClassMapping = {};
        totalTurmasDaSerie = 1;
        usedDisciplineCount = {};
        disciplineWeeklyLimit = {};
    }

    // =============================================
    // SELECIONAR TURMA => CARREGA GRADE
    // =============================================
    selectTurma.addEventListener('change', async () => {
        const turmaVal = selectTurma.value;
        if (!turmaVal) {
            gradeContainer.innerHTML = '';
            quadroProfessores.innerHTML = '';
            idTurmaSelecionada = null;
            dadosTurma = null;
            editingEnabled = false;
            return;
        }
        idTurmaSelecionada = turmaVal;
        editingEnabled = true;

        try {
            await carregarTudo();
            await definirExtraAulas();
            montarGrade();
        } catch (err) {
            console.error(err);
        }
    });

    // =============================================
    // FUNÇÃO MASTER – CARREGA TUDO
    // =============================================
    async function carregarTudo() {
        // Carrega todos os horários do ano, restrições, professor-disciplina-turma,
        // e os dados específicos da turma.
        await loadAllHorariosDoAno(idAnoSelecionado);
        await loadProfessorRestricoes(idAnoSelecionado);
        await loadProfessorDisciplinaTurma();
        await loadHorariosTurma(idTurmaSelecionada);

        // Calcular número total de turmas da mesma série, no mesmo ano letivo.
        // Precisamos disso para dividir aulas_semana da série entre as turmas.
        await calcularTotalTurmasDaSerieNoAno();
        // Agora definimos o limite de aulas por disciplina para esta turma.
        inicializarLimitesDisciplinas();

        // Também inicializamos o contador de usos de cada disciplina (para a turma em questão).
        recalcularUsosDasDisciplinas();
    }

    async function loadAllHorariosDoAno(idAno) {
        try {
            let resp = await fetch(`/horarios/app/controllers/horarios/listHorariosByAnoLetivo.php?id_ano_letivo=${idAno}`)
                             .then(r => r.json());
            allHorariosDoAno = (resp.status === 'success') ? resp.data : [];
        } catch (err) {
            console.error(err);
        }
    }

    async function loadProfessorRestricoes(idAno) {
        try {
            let resp = await fetch(`/horarios/app/controllers/professor-restricoes/listProfessorRestricoes.php?id_ano_letivo=${idAno}`)
                             .then(r => r.json());
            if (resp.status === 'success') {
                professorRestricoesMap = {};
                resp.data.forEach(row => {
                    const p = row.id_professor;
                    const dia = row.dia_semana;
                    const aula = parseInt(row.numero_aula, 10);
                    if (!professorRestricoesMap[p]) professorRestricoesMap[p] = {};
                    if (!professorRestricoesMap[p][dia]) professorRestricoesMap[p][dia] = [];
                    professorRestricoesMap[p][dia].push(aula);
                });
            }
        } catch (err) {
            console.error(err);
        }
    }

    async function loadProfessorDisciplinaTurma() {
        try {
            let resp = await fetch('/horarios/app/controllers/professor-disciplina-turma/listProfessorDisciplinaTurma.php?all=1')
                             .then(r => r.json());
            if (resp.status === 'success') {
                profDiscTurmaMap = {};
                resp.data.forEach(row => {
                    const t = row.id_turma;
                    const d = row.id_disciplina;
                    const p = row.id_professor;
                    if (!profDiscTurmaMap[t]) profDiscTurmaMap[t] = {};
                    if (!profDiscTurmaMap[t][d]) profDiscTurmaMap[t][d] = [];
                    profDiscTurmaMap[t][d].push(parseInt(p, 10));
                });
            }
        } catch (err) {
            console.error(err);
        }
    }

    async function loadHorariosTurma(idTurma) {
        try {
            let resp = await fetch(`/horarios/app/controllers/horarios/listHorarios.php?id_turma=${idTurma}`)
                             .then(r => r.json());
            if (resp.status === 'success') {
                dadosTurma = resp.data;
                const intervalStr = dadosTurma.turma.intervalos_positions || '';
                intervalPositions = intervalStr
                    .split(',')
                    .map(n => parseInt(n.trim(), 10))
                    .filter(x => !isNaN(x) && x > 0);
            } else {
                dadosTurma = null;
            }
        } catch (err) {
            console.error(err);
        }
    }

    // =============================================
    // CÁLCULO DO TOTAL DE TURMAS NA MESMA SÉRIE (PARA DIVIDIR AULAS)
    // =============================================
    async function calcularTotalTurmasDaSerieNoAno() {
        if (!dadosTurma || !dadosTurma.turma) return;
        const serieAtual = dadosTurma.turma.id_serie;
        const anoLetivoAtual = dadosTurma.turma.id_ano_letivo;
        if (!serieAtual || !anoLetivoAtual) return;

        // Precisamos de um endpoint que liste todas as turmas por série e ano.
        // Caso não haja, podemos adaptar do "listTurmaPorAnoLetivo".
        // Exemplo simples (pseudo-endpoint):
        // GET /horarios/app/controllers/turma/listTurmasBySerieAndAno.php?id_serie=...&id_ano_letivo=...
        try {
            let resp = await fetch(`/horarios/app/controllers/turma/listTurmasBySerieAndAno.php?id_serie=${serieAtual}&id_ano_letivo=${anoLetivoAtual}`)
                             .then(r => r.json());
            if (resp.status === 'success' && resp.data.length > 0) {
                totalTurmasDaSerie = resp.data.length;
            } else {
                totalTurmasDaSerie = 1;
            }
        } catch (err) {
            console.warn('Não foi possível calcular total de turmas da série:', err);
            totalTurmasDaSerie = 1;
        }
    }

    // =============================================
    // DEFINIR LIMITE DE AULAS (POR DISCIPLINA) NA TURMA
    // =============================================
    function inicializarLimitesDisciplinas() {
        if (!dadosTurma || !dadosTurma.serie_disciplinas) return;
        disciplineWeeklyLimit = {};

        // A tabela serie_disciplinas tem "aulas_semana" para cada disciplina
        // Vamos dividir esse valor pela quantidade de turmas da série.
        // Ex: se a série 6ºAno tem 6 aulas de Português e há 2 turmas (A e B),
        // cada turma fica com 3 aulas de limite para Português.
        // Armazenamos isso em disciplineWeeklyLimit[d.id_disciplina].
        dadosTurma.serie_disciplinas.forEach(d => {
            const totalParaSerie = parseInt(d.aulas_semana, 10);
            const dividido = Math.floor(totalParaSerie / totalTurmasDaSerie);
            // Se quiser tratar arredondamento, etc., podemos usar round/floor/ceil
            disciplineWeeklyLimit[d.id_disciplina] = dividido;
        });
    }

    // =============================================
    // CONTAR QUANTAS AULAS JÁ FORAM ALOCADAS PARA CADA DISCIPLINA
    // =============================================
    function recalcularUsosDasDisciplinas() {
        usedDisciplineCount = {};
        if (!dadosTurma || !dadosTurma.horarios) return;

        (dadosTurma.horarios || []).forEach(h => {
            const discId = h.id_disciplina;
            if (!usedDisciplineCount[discId]) {
                usedDisciplineCount[discId] = 0;
            }
            usedDisciplineCount[discId]++;
        });
    }

    // =============================================
    // DEFINIR AULA EXTRA (opcional)
    // =============================================
    async function definirExtraAulas() {
        if (!dadosTurma || !dadosTurma.turma) return;
        const totalSeriesAulas = parseInt(dadosTurma.turma.total_aulas_semana, 10);
        if (!totalSeriesAulas) return;
        const diasComAulas = (dadosTurma.turno_dias || []).filter(td => parseInt(td.aulas_no_dia, 10) > 0);
        const numDias = diasComAulas.length;
        if (numDias === 0) return;

        const baseSlots = Math.floor(totalSeriesAulas / numDias);
        const remainder = totalSeriesAulas % numDias;

        diasComAulas.forEach(td => {
            extraClassMapping[td.dia_semana] = baseSlots;
        });

        if (remainder === 1) {
            await openExtraClassModal(diasComAulas, (diaEscolhido) => {
                extraClassMapping[diaEscolhido] = baseSlots + 1;
            });
        }
    }

    function openExtraClassModal(dias, callback) {
        return new Promise((resolve) => {
            if (!modalExtra) {
                resolve();
                return;
            }
            const container = modalExtra.querySelector('.modal-content-extra');
            container.innerHTML = '<h2>Selecione o dia com aula extra</h2>';
            dias.forEach(td => {
                const label = document.createElement('label');
                const checkbox = document.createElement('input');
                checkbox.type = 'radio';
                checkbox.name = 'extraDia';
                checkbox.value = td.dia_semana;
                label.appendChild(checkbox);
                label.appendChild(document.createTextNode(td.dia_semana));
                container.appendChild(label);
                container.appendChild(document.createElement('br'));
            });
            modalExtra.style.display = 'block';

            const btnConf = modalExtra.querySelector('#btnConfirmarExtra');
            const btnCanc = modalExtra.querySelector('#btnCancelarExtra');
            const btnClose = modalExtra.querySelector('.close-modal-extra');

            btnConf.onclick = () => {
                const selected = modalExtra.querySelector('input[name="extraDia"]:checked');
                if (selected) {
                    callback(selected.value);
                }
                closeModalExtra();
                resolve();
            };
            btnCanc.onclick = () => {
                closeModalExtra();
                resolve();
            };
            btnClose.onclick = () => {
                closeModalExtra();
                resolve();
            };
        });
    }
    function closeModalExtra() {
        if (!modalExtra) return;
        modalExtra.style.display = 'none';
    }

    // =============================================
    // MONTA GRADE (incluindo INTERVALOS)
    // =============================================
    function montarGrade() {
        gradeContainer.innerHTML = '';
        if (!dadosTurma || !dadosTurma.turma) {
            gradeContainer.innerHTML = '<p>Turma não encontrada.</p>';
            return;
        }
        const diasComAulas = (dadosTurma.turno_dias || []).filter(td => parseInt(td.aulas_no_dia, 10) > 0);
        if (diasComAulas.length === 0) {
            gradeContainer.innerHTML = '<p>Nenhum dia possui aulas neste turno.</p>';
            return;
        }

        const maxAulasTurno = Math.max(...diasComAulas.map(td => parseInt(td.aulas_no_dia, 10)));
        const table = document.createElement('table');
        table.classList.add('tabela-horarios');

        // Cabeçalho
        const thead = document.createElement('thead');
        const trHead = document.createElement('tr');
        const thAula = document.createElement('th');
        thAula.textContent = 'Aula';
        trHead.appendChild(thAula);
        diasComAulas.forEach(d => {
            const th = document.createElement('th');
            th.textContent = traduzDia(d.dia_semana);
            trHead.appendChild(th);
        });
        thead.appendChild(trHead);
        table.appendChild(thead);

        // Corpo
        const tbody = document.createElement('tbody');
        for (let aula = 1; aula <= maxAulasTurno; aula++) {
            // Intervalo
            if (intervalPositions.includes(aula)) {
                const trInt = document.createElement('tr');
                const tdLabelInt = document.createElement('td');
                tdLabelInt.textContent = 'Intervalo';
                trInt.appendChild(tdLabelInt);
                diasComAulas.forEach(() => {
                    const tdInt = document.createElement('td');
                    tdInt.style.textAlign = 'center';
                    tdInt.style.fontWeight = 'bold';
                    tdInt.textContent = 'Intervalo';
                    trInt.appendChild(tdInt);
                });
                tbody.appendChild(trInt);
            }

            // Linha de aula
            const tr = document.createElement('tr');
            const tdLabel = document.createElement('td');
            tdLabel.textContent = aula + 'ª Aula';
            tr.appendChild(tdLabel);
            diasComAulas.forEach(d => {
                const td = document.createElement('td');
                const nAulasDia = parseInt(d.aulas_no_dia, 10);
                if (aula > nAulasDia) {
                    td.style.backgroundColor = '#000';
                    tr.appendChild(td);
                    return;
                }
                montarCelulaAula(td, d.dia_semana, aula);
                tr.appendChild(td);
            });
            tbody.appendChild(tr);
        }
        table.appendChild(tbody);
        gradeContainer.appendChild(table);
    }

    // =============================================
    // CRIA CÉLULA (Disciplina + Professor)
    // =============================================
    function montarCelulaAula(td, diaSemana, numeroAula) {
        const horarioExistente = (dadosTurma.horarios || [])
            .find(h => h.dia_semana === diaSemana && parseInt(h.numero_aula, 10) === numeroAula);

        const selDisc = document.createElement('select');
        selDisc.classList.add('select-disciplina');
        selDisc.appendChild(new Option('--Disc--', ''));

        // Aqui listamos TODAS as disciplinas da série:
        (dadosTurma.serie_disciplinas || []).forEach(d => {
            const opt = new Option(d.nome_disciplina, d.id_disciplina);
            selDisc.appendChild(opt);
        });

        const selProf = document.createElement('select');
        selProf.classList.add('select-professor');
        selProf.appendChild(new Option('--Prof--', ''));

        // Caso já exista horário salvo
        if (horarioExistente) {
            selDisc.value = horarioExistente.id_disciplina;
            refazerProfessores(selDisc, selProf, diaSemana, numeroAula);
            selProf.value = horarioExistente.id_professor;
        } else {
            // Se não existir, garantimos que, ao mudar disc, refaz prof, e vice-versa
            refazerProfessores(selDisc, selProf, diaSemana, numeroAula);
            refazerDisciplinas(selDisc, selProf, diaSemana, numeroAula);
        }

        selDisc.disabled = !editingEnabled;
        selProf.disabled = !editingEnabled;

        // Listeners
        selDisc.addEventListener('change', () => {
            if (!editingEnabled) return;

            // Antes de salvar, verificamos se ainda há “saldo” de aulas para essa disciplina.
            if (!checarSaldoDisciplina(selDisc.value)) {
                alert('Não há mais aulas disponíveis para essa disciplina nesta turma.');
                selDisc.value = '';  // reset
                refazerProfessores(selDisc, selProf, diaSemana, numeroAula);
                return;
            }

            refazerProfessores(selDisc, selProf, diaSemana, numeroAula);

            if (selDisc.value && selProf.value) {
                salvarOuAtualizar(diaSemana, numeroAula, selDisc.value, selProf.value, td);
            } else if (!selDisc.value && !selProf.value) {
                deletarHorario(diaSemana, numeroAula);
            }
            aplicarCorCelula(td, selDisc.value, selProf.value);
        });

        selProf.addEventListener('change', () => {
            if (!editingEnabled) return;

            // Checamos disciplina atual; se já estiver no select e não tiver saldo, bloqueia:
            if (selDisc.value && !checarSaldoDisciplina(selDisc.value)) {
                alert('Disciplina já está no limite de aulas para esta turma.');
                selProf.value = ''; // reset
                return;
            }

            refazerDisciplinas(selDisc, selProf, diaSemana, numeroAula);
            if (selDisc.value && selProf.value) {
                salvarOuAtualizar(diaSemana, numeroAula, selDisc.value, selProf.value, td);
            } else if (!selDisc.value && !selProf.value) {
                deletarHorario(diaSemana, numeroAula);
            }
            aplicarCorCelula(td, selDisc.value, selProf.value);
        });

        td.appendChild(selDisc);
        td.appendChild(document.createElement('br'));
        td.appendChild(selProf);

        if (horarioExistente) {
            aplicarCorCelula(td, horarioExistente.id_disciplina, horarioExistente.id_professor);
        }
    }

    // =============================================
    // AUXILIARES: PROFESSORES, DISCIPLINAS, ETC.
    // =============================================
    function refazerProfessores(selDisc, selProf, diaSemana, numeroAula) {
        const currentProfSelecionado = selProf.value;
        selProf.innerHTML = '';
        selProf.appendChild(new Option('--Prof--', ''));

        const linkedProfSet = getProfessoresVinculadosTurma();
        (dadosTurma.professores || []).forEach(prof => {
            const pid = parseInt(prof.id_professor, 10);
            if (!linkedProfSet.has(pid)) return;
            if (professorEhRestrito(pid, diaSemana, numeroAula)) return;

            // Checamos conflito
            const conflict = professorOcupado(pid, diaSemana, numeroAula);
            let displayName = prof.nome_exibicao || ('Prof ' + pid);
            const opt = new Option(displayName, pid);

            if (conflict) {
                displayName = `❌ ${displayName} (Turma: ${conflict.nome_serie} ${conflict.nome_turma})`;
                opt.text = displayName;
                opt.disabled = true;
            }
            selProf.appendChild(opt);
        });
        selProf.value = currentProfSelecionado;
    }

    function refazerDisciplinas(selDisc, selProf, diaSemana, numeroAula) {
        const profId = parseInt(selProf.value, 10) || 0;
        const discSelecionada = selDisc.value;
        selDisc.innerHTML = '';
        selDisc.appendChild(new Option('--Disc--', ''));

        if (profId && profDiscTurmaMap[idTurmaSelecionada]) {
            // Pega disciplinas do professor + turma
            const mapDisc = profDiscTurmaMap[idTurmaSelecionada] || {};
            const disciplinasDoProf = Object.keys(mapDisc)
                .filter(did => mapDisc[did].includes(profId))
                .map(x => parseInt(x, 10));

            (dadosTurma.serie_disciplinas || []).forEach(d => {
                if (disciplinasDoProf.includes(d.id_disciplina)) {
                    const opt = new Option(d.nome_disciplina, d.id_disciplina);

                    // Se já bateu o limite, marcamos um "X" ou desabilitamos
                    if (!checarSaldoDisciplina(d.id_disciplina, true)) {
                        opt.text = `❌ ${d.nome_disciplina} (0 disponíveis)`;
                        opt.disabled = true;
                    }

                    selDisc.appendChild(opt);
                }
            });
        } else {
            // Retorna todas disciplinas da série
            (dadosTurma.serie_disciplinas || []).forEach(d => {
                const opt = new Option(d.nome_disciplina, d.id_disciplina);

                if (!checarSaldoDisciplina(d.id_disciplina, true)) {
                    opt.text = `❌ ${d.nome_disciplina} (0 disponíveis)`;
                    opt.disabled = true;
                }
                selDisc.appendChild(opt);
            });
        }

        const existeNaLista = Array.from(selDisc.options).some(o => String(o.value) === String(discSelecionada));
        selDisc.value = existeNaLista ? discSelecionada : '';
    }

    function getProfessoresVinculadosTurma() {
        const linkedProf = new Set();
        if (profDiscTurmaMap[idTurmaSelecionada]) {
            Object.values(profDiscTurmaMap[idTurmaSelecionada]).forEach(arr => {
                arr.forEach(pid => linkedProf.add(pid));
            });
        }
        return linkedProf;
    }

    function professorEhRestrito(profId, diaSemana, numeroAula) {
        const dias = professorRestricoesMap[profId];
        if (!dias) return false;
        const aulasRestritas = dias[diaSemana] || [];
        return aulasRestritas.includes(numeroAula);
    }

    function professorOcupado(profId, diaSemana, numeroAula) {
        const conflict = allHorariosDoAno.find(h =>
            h.id_professor == profId &&
            h.dia_semana == diaSemana &&
            parseInt(h.numero_aula, 10) === numeroAula &&
            h.id_turma != idTurmaSelecionada
        );
        if (conflict) {
            return { nome_serie: conflict.nome_serie, nome_turma: conflict.nome_turma };
        }
        return null;
    }

    // =============================================
    // CHECAR SE A DISCIPLINA TEM "SALDO" DE AULAS
    // =============================================
    // se forExibirMensagemForZero for TRUE, não dá alert, só retorna boolean
    function checarSaldoDisciplina(discId, apenasVerificar = false) {
        if (!discId) return true;
        const limite = disciplineWeeklyLimit[discId] || 0;
        const usado = usedDisciplineCount[discId] || 0;
        const disponivel = limite - usado;
        return (disponivel > 0);
    }

    // =============================================
    // SALVAR / ATUALIZAR / DELETAR
    // =============================================
    function salvarOuAtualizar(diaSemana, numeroAula, discId, profId, td) {
        if (!idTurmaSelecionada) return;
        if (!discId && !profId) {
            deletarHorario(diaSemana, numeroAula);
            return;
        }
        if (!discId || !profId) return;

        // Checar restrição de professor
        if (professorEhRestrito(profId, diaSemana, numeroAula)) {
            alert("O professor está restrito neste horário.");
            return;
        }
        // Checar conflito (prof já ocupado)
        const conflict = professorOcupado(profId, diaSemana, numeroAula);
        if (conflict) {
            alert(`O professor já está ocupado no mesmo horário (Turma: ${conflict.nome_serie} ${conflict.nome_turma}).`);
            return;
        }
        // Checar se a disciplina ainda tem saldo
        if (!checarSaldoDisciplina(discId)) {
            alert(`A disciplina atingiu o limite de aulas nesta turma.`);
            return;
        }

        const h = (dadosTurma.horarios || [])
            .find(x => x.dia_semana === diaSemana && parseInt(x.numero_aula, 10) === numeroAula);
        if (!h) {
            inserirHorario(diaSemana, numeroAula, discId, profId, td);
        } else {
            registrarHistorico(h).then(() => {
                atualizarHorario(h.id_horario, discId, profId);
            });
        }
    }

    async function inserirHorario(diaSemana, numeroAula, discId, profId, td) {
        const body = new URLSearchParams({
            id_turma: idTurmaSelecionada,
            dia_semana: diaSemana,
            numero_aula: numeroAula,
            id_disciplina: discId,
            id_professor: profId
        });
        try {
            let resp = await fetch('/horarios/app/controllers/horarios/insertHorarios.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body
            }).then(r => r.json());

            if (resp.status === 'success') {
                // Atualiza dados localmente
                dadosTurma.horarios.push(resp.data);
                allHorariosDoAno.push({
                    ...resp.data,
                    nome_serie: turmasMap[idTurmaSelecionada].nome_serie,
                    nome_turma: turmasMap[idTurmaSelecionada].nome_turma
                });

                // Incrementa contador
                const discIdNum = parseInt(discId, 10);
                if (!usedDisciplineCount[discIdNum]) {
                    usedDisciplineCount[discIdNum] = 0;
                }
                usedDisciplineCount[discIdNum]++;

            } else {
                alert(resp.message);
            }
            atualizarQuadroProfessores();
        } catch (err) {
            console.error(err);
        }
    }

    async function atualizarHorario(idHorario, discId, profId) {
        const body = new URLSearchParams({
            id_horario: idHorario,
            id_disciplina: discId,
            id_professor: profId
        });
        try {
            let resp = await fetch('/horarios/app/controllers/horarios/updateHorarios.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body
            }).then(r => r.json());

            if (resp.status === 'success') {
                // Precisamos subtrair 1 do uso da disciplina antiga e somar 1 na nova (se mudar a disciplina).
                const h = (dadosTurma.horarios || []).find(x => x.id_horario == idHorario);
                if (h) {
                    // caso a disciplina tenha mudado
                    if (h.id_disciplina != discId) {
                        // Decrementa a antiga
                        const oldDisc = parseInt(h.id_disciplina, 10);
                        if (usedDisciplineCount[oldDisc]) {
                            usedDisciplineCount[oldDisc]--;
                        }
                        // Incrementa a nova
                        const newDisc = parseInt(discId, 10);
                        if (!usedDisciplineCount[newDisc]) {
                            usedDisciplineCount[newDisc] = 0;
                        }
                        usedDisciplineCount[newDisc]++;
                    }

                    // Atualiza no objeto local
                    h.id_disciplina = discId;
                    h.id_professor = profId;
                }
                const hh = allHorariosDoAno.find(x => x.id_horario == idHorario);
                if (hh) {
                    hh.id_disciplina = discId;
                    hh.id_professor = profId;
                }

            } else {
                if (resp.message !== 'Nenhuma alteração ou registro não encontrado.') {
                    alert(resp.message);
                }
            }
            atualizarQuadroProfessores();
        } catch (err) {
            console.error(err);
        }
    }

    function deletarHorario(diaSemana, numeroAula) {
        const horarioExistente = (dadosTurma.horarios || [])
            .find(h => h.dia_semana === diaSemana && parseInt(h.numero_aula, 10) === numeroAula);
        if (horarioExistente) {
            registrarHistorico(horarioExistente).then(() => {
                deletaNoBanco(diaSemana, numeroAula, horarioExistente.id_horario, horarioExistente.id_disciplina);
            });
        } else {
            deletaNoBanco(diaSemana, numeroAula, null, null);
        }
    }

    async function deletaNoBanco(diaSemana, numeroAula, id_horario, discId) {
        const body = new URLSearchParams({
            id_turma: idTurmaSelecionada,
            dia_semana: diaSemana,
            numero_aula: numeroAula
        });
        try {
            let resp = await fetch('/horarios/app/controllers/horarios/deleteHorarios.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body
            }).then(r => r.json());
            if (resp.status === 'success') {
                if (id_horario && discId) {
                    const discIdNum = parseInt(discId, 10);
                    if (usedDisciplineCount[discIdNum] > 0) {
                        usedDisciplineCount[discIdNum]--;
                    }
                }
                dadosTurma.horarios = (dadosTurma.horarios || [])
                    .filter(x => x.dia_semana !== diaSemana || parseInt(x.numero_aula) !== numeroAula);
                allHorariosDoAno = allHorariosDoAno.filter(x => x.id_horario != resp.id_horario);

            } else {
                // Se "Horário não encontrado", apenas remove local
                if (resp.message === 'Horário não encontrado.') {
                    dadosTurma.horarios = (dadosTurma.horarios || [])
                        .filter(x => x.dia_semana !== diaSemana || parseInt(x.numero_aula) !== numeroAula);
                    allHorariosDoAno = allHorariosDoAno.filter(x =>
                        x.id_turma != idTurmaSelecionada ||
                        x.dia_semana !== diaSemana ||
                        parseInt(x.numero_aula) !== numeroAula
                    );
                } else {
                    alert(resp.message);
                }
            }
            atualizarQuadroProfessores();
        } catch (err) {
            console.error(err);
        }
    }

    // =============================================
    // HISTÓRICO (AUDITORIA DE HORÁRIOS)
    // =============================================
    async function registrarHistorico(horarioObj) {
        if (!horarioObj || !horarioObj.id_horario) return;
        const body = new URLSearchParams({
            id_horario_original: horarioObj.id_horario,
            id_turma: horarioObj.id_turma,
            id_ano_letivo: idAnoSelecionado,
            dia_semana: horarioObj.dia_semana,
            numero_aula: horarioObj.numero_aula,
            id_disciplina: horarioObj.id_disciplina,
            id_professor: horarioObj.id_professor,
            data_criacao: horarioObj.data_criacao || ''
        });
        try {
            let resp = await fetch('/horarios/app/controllers/horarios/archiveHorario.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body
            }).then(r => r.json());
            if (resp.status !== 'success') {
                console.warn('Falha ao registrar histórico:', resp.message);
            }
        } catch (err) {
            console.error(err);
        }
    }

    // =============================================
    // QUADRO DE PROFESSORES (OPCIONAL)
    // =============================================
    function atualizarQuadroProfessores() {
        const tbl = quadroProfessores.querySelector('.quadro-prof-table');
        if (!tbl) return;
        const idSerieAtual = dadosTurma.turma.id_serie;
        if (!idSerieAtual) return;
        const countProf = {};
        (dadosTurma.professores || []).forEach(p => {
            countProf[p.id_professor] = 0;
        });
        allHorariosDoAno.forEach(h => {
            const pid = h.id_professor;
            const tm = turmasMap[h.id_turma];
            if (!tm) return;
            if (tm.id_serie == idSerieAtual && countProf[pid] !== undefined) {
                countProf[pid]++;
            }
        });
        const rows = tbl.querySelectorAll('tbody tr');
        rows.forEach(tr => {
            const idProf = tr.dataset.idProf;
            const prof = (dadosTurma.professores || []).find(x => x.id_professor == idProf);
            if (!prof) return;
            const limite = parseInt(prof.limite_aulas_fixa_semana || 0, 10);
            const usados = countProf[idProf] || 0;
            const resto = limite - usados;
            const tdRest = tr.querySelector('.restante-col');
            if (tdRest) {
                tdRest.textContent = resto;
                tdRest.style.color = (resto < 0) ? 'red' : '';
            }
        });
    }

    function aplicarCorCelula(td, discId, profId) {
        td.style.backgroundColor = (discId && profId) ? '#D5F4DA' : '';
    }

    // =============================================
    // BOTÃO IMPRIMIR
    // =============================================
    btnImprimir.addEventListener('click', () => {
        if (!idTurmaSelecionada) {
            alert('Selecione uma turma para imprimir o horário.');
            return;
        }
        const url = `/horarios/app/views/horarios-turma.php?id_turma=${idTurmaSelecionada}&orient=Landscape`;
        window.open(url, '_blank');
    });

    // =============================================
    // UTILS
    // =============================================
    function traduzDia(dia) {
        switch (dia) {
            case 'Domingo': return 'Domingo';
            case 'Segunda': return 'Segunda';
            case 'Terca':   return 'Terça';
            case 'Quarta':  return 'Quarta';
            case 'Quinta':  return 'Quinta';
            case 'Sexta':   return 'Sexta';
            case 'Sabado':  return 'Sábado';
            default:        return dia;
        }
    }

    // Inicializa
    loadAnosLetivos();
});
 