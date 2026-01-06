// app/assets/js/horarios.js
document.addEventListener('DOMContentLoaded', function() {

	// =============================================
	// ELEMENTOS DA PÁGINA
	// =============================================
	const selectAnoLetivo = document.getElementById('selectAnoLetivo');
	const selectNivelEnsino = document.getElementById('selectNivelEnsino');
	const selectTurma  = document.getElementById('selectTurma');
	const btnImprimir = document.getElementById('btnImprimir');
	const btnAutomatico = document.getElementById('btn-automatic');

	const gradeContainer = document.getElementById('grade-container');
	const modalAutomatico = document.getElementById('modal-automatico');
	const modalExtra = document.getElementById('modal-extra');

	const quadroDisciplinas	 = document.getElementById('quadro-disciplinas');

	// [ACRÉSCIMO] container e placeholder dentro de .data
	const contentDataHorarios = document.getElementById('content-data-horarios');
	const noDataMessage = document.getElementById('no-data-message');
	function showNoData(msg = 'Nenhuma informação encontrada.') {
		if (contentDataHorarios) contentDataHorarios.style.display = 'block';
		if (gradeContainer) gradeContainer.innerHTML = '';
		if (quadroDisciplinas) quadroDisciplinas.innerHTML = '';
		if (noDataMessage) {
			noDataMessage.textContent = msg;
			noDataMessage.style.display = 'block';
		}
	}
	function hideNoData() {
		if (noDataMessage) noDataMessage.style.display = 'none';
	}

	// =============================================
	// VARIÁVEIS GLOBAIS
	// =============================================
	let idAnoSelecionado = null;
	let idNivelEnsinoSelecionado = null;
	let idTurmaSelecionada = null;
	let editingEnabled = false;

	let allHorariosDoAno = [];
	let professorRestricoesMap = {};
	let dadosTurma = null;
	let profDiscTurmaMap = {};
	let turmasMap = {};

	// Ex.: [3,6] significa que, após a 3ª aula e após a 6ª aula teremos Intervalo
	let intervalPositions = [];
	let extraClassMapping = {};

	let totalTurmasDaSerie = 1;
	let usedDisciplineCount = {};
	let disciplineWeeklyLimit = {};

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
			//let resp = await fetch(`/horarios/app/controllers/nivel-ensino/listNivelEnsino.php?id_ano_letivo=${idAno}`)
			let resp = await fetch(`/horarios/app/controllers/nivel-ensino/listNivelEnsinoByUserAndAno.php?id_ano_letivo=${idAno}`)
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
					// Seleciona automaticamente se houver só um nível
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
	// 3) BOTÃO AUTOMÁTICO – ABRE MODAL
	// =============================================
	btnAutomatico.disabled = true;
	btnAutomatico.addEventListener('click', () => {
		if (!idAnoSelecionado || !idNivelEnsinoSelecionado) return;
		if (selectTurma.options.length <= 1) {
			alert("Não existem turmas cadastradas para gerar horários.");
			return;
		}
		openModalAutomatico();
	});

	function openModalAutomatico() {
		if (!modalAutomatico) {
			alert('Modal de geração automática não encontrado!');
			return;
		}
		// Mostra o modal + animações
		modalAutomatico.style.display = 'block';
		modalAutomatico.classList.remove('fade-out');
		modalAutomatico.classList.add('fade-in');

		const content = modalAutomatico.querySelector('.modal-content');
		content.classList.remove('slide-up');
		content.classList.add('slide-down');
	}
 
	function closeModalAutomatico() {
		const content = modalAutomatico.querySelector('.modal-content');
		content.classList.remove('slide-down');
		content.classList.add('slide-up');
		modalAutomatico.classList.remove('fade-in');
		modalAutomatico.classList.add('fade-out');

		// Espera a animação
		setTimeout(() => {
			modalAutomatico.style.display = 'none';
			content.classList.remove('slide-up');
			modalAutomatico.classList.remove('fade-out');
		}, 300);
	}

	// Botões de confirmação e cancelamento do modal
	if (modalAutomatico) {
		const btnConf = modalAutomatico.querySelector('#btnConfirmarAutomatico');
		const btnCanc = modalAutomatico.querySelector('#btnCancelarAutomatico');
		const btnClose = modalAutomatico.querySelector('.close-modal-auto');

		/*btnConf.onclick = async () => {
			// [ACRÉSCIMO] pré-checagem de viabilidade antes do backend
			const inviaveis = validarViabilidadeSerie();
			if (inviaveis.length) {
				alert('Geração automática inviável:\n' + inviaveis.join('\n'));
				return;
			}
			await gerarHorariosAutomaticos();
			closeModalAutomatico();
		};*/


		btnConf.onclick = async () => {
			// [ACRÉSCIMO] pré-checagem de viabilidade antes do backend
			const inviaveis = validarViabilidadeSerie();
			if (inviaveis.length) {
				alert('Geração automática inviável:\n' + inviaveis.join('\n'));
				return;
			}
			
			closeModalAutomatico(); // ✅ Fecha o modal de confirmação
			
			// ✅ Abre o novo modal de fixação
			iniciarGeracaoComModal(idAnoSelecionado, idNivelEnsinoSelecionado);
		};


		btnCanc.onclick = () => closeModalAutomatico();
		btnClose.onclick = () => closeModalAutomatico();
	}

/*	async function gerarHorariosAutomaticos() {
		try {
			let body = new URLSearchParams({
				id_ano_letivo: idAnoSelecionado,
				id_nivel_ensino: idNivelEnsinoSelecionado
			});
			let resp = await fetch('/horarios/app/controllers/horarios/gerarHorariosAutomaticos.php', {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body
			}).then(r => r.json());

			if (resp.status === 'success') {
				alert('Horários gerados automaticamente com sucesso!');
				if (idTurmaSelecionada) {
					await carregarTudo();
					montarGrade();
					atualizarQuadroDisciplinas();
				}
			} else {
				alert(resp.message || 'Erro ao gerar horários!');
			}
		} catch (err) {
			console.error(err);
			alert('Ocorreu um erro ao gerar horários automáticos.');
		}
	}
*/
// ADICIONE esta função no seu horarios.js, logo após a função gerarHorariosAutomaticos:

async function gerarHorariosAutomaticos() {
    try {
			let body = new URLSearchParams({
			id_ano_letivo: idAnoSelecionado,
			id_nivel_ensino: idNivelEnsinoSelecionado,
			max_seconds: 300,
			fix_mode: 'soft',         // ou 'hard'
			debug_alloc: 0,           // 1 para logar alocações (cuidado: log grande)
			debug_alloc_max: 2000
		});
        let resp = await fetch('/horarios/app/controllers/horarios/gerarHorariosAutomaticos.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body
        }).then(r => r.json());

        if (resp.status === 'success') {
            // 🆕 MOSTRA O DIAGNÓSTICO NO CONSOLE
            console.clear();
            console.log('%c========================================', 'color: #4CAF50; font-weight: bold');
            console.log('%c📊 DIAGNÓSTICO DE GERAÇÃO DE HORÁRIOS', 'color: #4CAF50; font-weight: bold; font-size: 16px');
            console.log('%c========================================', 'color: #4CAF50; font-weight: bold');
            console.log('');
            console.log(resp.message);
            console.log('');
            console.log('%c========================================', 'color: #4CAF50; font-weight: bold');
            
            // 🆕 CRIA MODAL VISUAL COM O DIAGNÓSTICO
            mostrarDiagnosticoModal(resp.message);
            
            // Mensagem simplificada no alert
            alert('✅ Horários gerados com sucesso!\n\nVeja o diagnóstico completo:\n• No CONSOLE (F12)\n• No modal que apareceu');
            
            if (idTurmaSelecionada) {
                await carregarTudo();
                montarGrade();
                atualizarQuadroDisciplinas();
            }
        } else {
            console.error('❌ ERRO:', resp.message);
            alert(resp.message || 'Erro ao gerar horários!');
        }
    } catch (err) {
        console.error('❌ ERRO FATAL:', err);
        alert('Ocorreu um erro ao gerar horários automáticos.');
    }
}

// 🆕 FUNÇÃO PARA MOSTRAR MODAL COM DIAGNÓSTICO
function mostrarDiagnosticoModal(mensagem) {
    // Remove modal anterior se existir
    const modalAntigo = document.getElementById('modal-diagnostico');
    if (modalAntigo) modalAntigo.remove();
    
    // Cria o modal
    const modal = document.createElement('div');
    modal.id = 'modal-diagnostico';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.8);
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    `;
    
    const conteudo = document.createElement('div');
    conteudo.style.cssText = `
        background: white;
        border-radius: 12px;
        padding: 30px;
        max-width: 900px;
        max-height: 80vh;
        overflow-y: auto;
        box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        position: relative;
    `;
    
    conteudo.innerHTML = `
        <button id="fechar-diagnostico" style="
            position: absolute;
            top: 15px;
            right: 15px;
            background: #f44336;
            color: white;
            border: none;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            font-size: 20px;
            cursor: pointer;
            font-weight: bold;
        ">×</button>
        
        <h2 style="color: #4CAF50; margin-top: 0;">📊 Diagnóstico Completo</h2>
        <pre style="
            background: #f5f5f5;
            padding: 20px;
            border-radius: 8px;
            overflow-x: auto;
            white-space: pre-wrap;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.6;
        ">${mensagem}</pre>
        
        <div style="margin-top: 20px; text-align: center;">
            <button id="copiar-diagnostico" style="
                background: #2196F3;
                color: white;
                border: none;
                padding: 12px 30px;
                border-radius: 6px;
                cursor: pointer;
                font-size: 14px;
                margin-right: 10px;
            ">📋 Copiar para Área de Transferência</button>
            
            <button id="baixar-diagnostico" style="
                background: #4CAF50;
                color: white;
                border: none;
                padding: 12px 30px;
                border-radius: 6px;
                cursor: pointer;
                font-size: 14px;
            ">💾 Baixar como TXT</button>
        </div>
    `;
    
    modal.appendChild(conteudo);
    document.body.appendChild(modal);
    
    // Botão fechar
    document.getElementById('fechar-diagnostico').onclick = () => modal.remove();
    modal.onclick = (e) => {
        if (e.target === modal) modal.remove();
    };
    
    // Botão copiar
    document.getElementById('copiar-diagnostico').onclick = () => {
        navigator.clipboard.writeText(mensagem).then(() => {
            alert('✅ Diagnóstico copiado para área de transferência!');
        }).catch(() => {
            alert('❌ Erro ao copiar. Tente selecionar e copiar manualmente.');
        });
    };
    
    // Botão baixar
    document.getElementById('baixar-diagnostico').onclick = () => {
        const blob = new Blob([mensagem], { type: 'text/plain' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `diagnostico-horarios-${new Date().toISOString().slice(0,10)}.txt`;
        a.click();
        URL.revokeObjectURL(url);
    };
}
	// =============================================
	// 4) CARREGAR TURMAS (POR ANO + NÍVEL)
	// =============================================
	async function loadTurmasPorAnoENivel(idAno, idNivel) {
		try {
			//let resp = await fetch(`/horarios/app/controllers/turma/listTurmaPorAnoLetivo.php?id_ano_letivo=${idAno}&id_nivel_ensino=${idNivel}`)
			let resp = await fetch(`/horarios/app/controllers/turma/listTurmaByUserAndAno.php?id_ano_letivo=${idAno}&id_nivel_ensino=${idNivel}`)
							 .then(r => r.json());
			if (resp.status === 'success' && resp.data.length > 0) {
				selectTurma.innerHTML = '<option value="">-- Selecione a Turma --</option>';
				selectTurma.disabled = false;
                resp.data.forEach(t => {
                    const opt = document.createElement('option');
                    opt.value = t.id_turma;
                    opt.textContent = `${t.nome_serie} ${t.nome_turma} - ${t.nome_turno}`;
                    selectTurma.appendChild(opt);

                    turmasMap[t.id_turma] = {
                        id_serie: t.id_serie,           // ← IMPORTANTE!
                        id_ano_letivo: t.id_ano_letivo, // ← IMPORTANTE!
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
		quadroDisciplinas.innerHTML = '';

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

		// [ACRÉSCIMO] placeholder quando limpar seleção
		showNoData();
	}

	/*selectTurma.addEventListener('change', async () => {
		const turmaVal = selectTurma.value;
		if (!turmaVal) {
			gradeContainer.innerHTML = '';
			quadroDisciplinas.innerHTML = '';
			idTurmaSelecionada = null;
			dadosTurma = null;
			editingEnabled = false;

			// [ACRÉSCIMO] placeholder quando nenhuma turma
			showNoData();
			return;
		}
		idTurmaSelecionada = turmaVal;
		editingEnabled = true;

		try {
			await carregarTudo();
			await definirExtraAulas();
			montarGrade();
			atualizarQuadroDisciplinas();
		} catch (err) {
			console.error(err);
		}
	});*/

selectTurma.addEventListener('change', async () => {
    const turmaVal = selectTurma.value;
    if (!turmaVal) {
        gradeContainer.innerHTML = '';
        quadroDisciplinas.innerHTML = '';
        idTurmaSelecionada = null;
        dadosTurma = null;
        editingEnabled = false;
        showNoData();
        return;
    }
    idTurmaSelecionada = turmaVal;
    editingEnabled = true;

    try {
        await carregarTudo();  // ← JÁ FAZ TUDO
        await definirExtraAulas();
        montarGrade();
        atualizarQuadroDisciplinas();
    } catch (err) {
        console.error(err);
    }
});

	// =============================================
	// FUNÇÃO MASTER – CARREGA TUDO
	// =============================================
	async function carregarTudo() {
		await loadAllHorariosDoAno(idAnoSelecionado);
		await loadProfessorRestricoes(idAnoSelecionado);
		await loadProfessorDisciplinaTurma();
		await loadHorariosTurma(idTurmaSelecionada);

		await calcularTotalTurmasDaSerieNoAno();
		inicializarLimitesDisciplinas();
		recalcularUsosDasDisciplinas();
	}

async function loadAllHorariosDoAno(idAno) {
    console.log('📡 Buscando horários do ano:', idAno);
    try {
        let resp = await fetch(`/horarios/app/controllers/horarios/listHorariosByAnoLetivo.php?id_ano_letivo=${idAno}`)
                         .then(r => r.json());
        
        console.log('📡 Resposta do servidor:', resp);
        
        if (resp.status === 'success' && resp.data && resp.data.length > 0) {
            allHorariosDoAno = resp.data;
            console.log('✅ Horários do ano carregados:', allHorariosDoAno.length);
            console.log('✅ Primeiro horário:', allHorariosDoAno[0]);
        } else {
            allHorariosDoAno = [];
            console.warn('⚠️ Resposta vazia ou sem dados');
        }
    } catch (err) {
        console.error('❌ Erro ao carregar horários do ano:', err);
        allHorariosDoAno = [];
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
					if (!professorRestricoesMap[p]) {
						professorRestricoesMap[p] = {};
					}
					if (!professorRestricoesMap[p][dia]) {
						professorRestricoesMap[p][dia] = [];
					}
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
					if (!profDiscTurmaMap[t]) {
						profDiscTurmaMap[t] = {};
					}
					if (!profDiscTurmaMap[t][d]) {
						profDiscTurmaMap[t][d] = [];
					}
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
	// CALCULAR TOTAL DE TURMAS DA MESMA SÉRIE (opcional)
	// =============================================
	async function calcularTotalTurmasDaSerieNoAno() {
		if (!dadosTurma || !dadosTurma.turma) return;
		const serieAtual = dadosTurma.turma.id_serie;
		const anoLetivoAtual = dadosTurma.turma.id_ano_letivo;
		if (!serieAtual || !anoLetivoAtual) return;

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
    // 1) inicializarLimitesDisciplinas()
    // O limite é o TOTAL DA SÉRIE (cada turma pode usar até esse limite)
    // =============================================
    function inicializarLimitesDisciplinas() {
        disciplineWeeklyLimit = {};
        if (!dadosTurma || !dadosTurma.serie_disciplinas) return;
        dadosTurma.serie_disciplinas.forEach(d => {
            // Cada turma pode ter até esse número de aulas
            const totalSerie = parseInt(d.aulas_semana, 10);
            disciplineWeeklyLimit[d.id_disciplina] = totalSerie;
        });
    }

    // =============================================
    // 2) recalcularUsosDasDisciplinas()
    // Conta aulas APENAS DA TURMA ATUAL
    // =============================================
    function recalcularUsosDasDisciplinas() {
        usedDisciplineCount = {};
        if (!dadosTurma || !dadosTurma.horarios) return;

        // Conta apenas os horários da turma atual
        dadosTurma.horarios.forEach(h => {
            const did = h.id_disciplina;
            if (!usedDisciplineCount[did]) {
                usedDisciplineCount[did] = 0;
            }
            usedDisciplineCount[did]++;
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
	// MONTAR A GRADE DE HORÁRIOS
	// =============================================
	function montarGrade() {
		// [ACRÉSCIMO] ao montar, garantir área visível e esconder placeholder
		if (contentDataHorarios) contentDataHorarios.style.display = 'block';
		hideNoData();

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
			// Linha normal de aula (primeiro exibe a aula)
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

			// Se a posição atual for um dos intervalos, insere a linha de "Intervalo"
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
		}
		table.appendChild(tbody);
		gradeContainer.appendChild(table);
		refreshAllDisciplineOptionStates(); // [ACRÉSCIMO] garante estado correto ao abrir
	}

	// =============================================
	// CRIA A CÉLULA (Select Disciplina + Select Professor)
	// =============================================
	function montarCelulaAula(td, diaSemana, numeroAula) {
		const horarioExistente = (dadosTurma.horarios || [])
			.find(h => h.dia_semana === diaSemana && parseInt(h.numero_aula, 10) === numeroAula);

		// Select Disciplina
		const selDisc = document.createElement('select');
		selDisc.classList.add('select-disciplina');
		selDisc.appendChild(new Option('--Disc--', ''));

		(dadosTurma.serie_disciplinas || []).forEach(d => {
			const opt = new Option(d.nome_disciplina, d.id_disciplina);
			selDisc.appendChild(opt);
		});

		// Select Professor
		const selProf = document.createElement('select');
		selProf.classList.add('select-professor');
		selProf.appendChild(new Option('--Prof--', ''));

		if (horarioExistente) {
			selDisc.value = horarioExistente.id_disciplina;
			// [ALTERAÇÃO] passa a disciplina para filtrar professores
			refazerProfessores(selDisc, selProf, diaSemana, numeroAula, selDisc.value);
			selProf.value = horarioExistente.id_professor;
		} else {
			// [ALTERAÇÃO] passa disciplina atual (vazia) para filtrar professores
			refazerProfessores(selDisc, selProf, diaSemana, numeroAula, selDisc.value);
			refazerDisciplinas(selDisc, selProf, diaSemana, numeroAula);
		}

		selDisc.disabled = !editingEnabled;
		selProf.disabled = !editingEnabled;

		selDisc.addEventListener('change', async () => {
		if (!editingEnabled) return;
		
		const novaDisc = selDisc.value;
		
		if (novaDisc && !checarSaldoDisciplina(novaDisc)) {
			alert('Não há mais aulas disponíveis para essa disciplina nesta turma.');
			selDisc.value = '';
			refazerProfessores(selDisc, selProf, diaSemana, numeroAula, '');
			refazerDisciplinas(selDisc, selProf, diaSemana, numeroAula);
			aplicarCorCelula(td, '', '');
			return;
		}
		
		refazerProfessores(selDisc, selProf, diaSemana, numeroAula, novaDisc);

		if (novaDisc && selProf.value) {
			await salvarOuAtualizar(diaSemana, numeroAula, novaDisc, selProf.value, td);
			// Garante que os valores permaneçam após salvar
			selDisc.value = novaDisc;
			selProf.value = selProf.value;
		} else if (!novaDisc && !selProf.value) {
			deletarHorario(diaSemana, numeroAula);
		}
		aplicarCorCelula(td, novaDisc, selProf.value);
	});

	selProf.addEventListener('change', async () => {
		if (!editingEnabled) return;
		
		const novoProf = selProf.value;
		
		if (selDisc.value && !checarSaldoDisciplina(selDisc.value)) {
			alert('Disciplina já está no limite de aulas para esta turma.');
			selProf.value = '';
			refazerProfessores(selDisc, selProf, diaSemana, numeroAula, selDisc.value);
			return;
		}
		
		refazerDisciplinas(selDisc, selProf, diaSemana, numeroAula);

		if (selDisc.value && novoProf) {
			await salvarOuAtualizar(diaSemana, numeroAula, selDisc.value, novoProf, td);
			// Garante que os valores permaneçam após salvar
			selDisc.value = selDisc.value;
			selProf.value = novoProf;
		} else if (!selDisc.value && !novoProf) {
			deletarHorario(diaSemana, numeroAula);
		}
		aplicarCorCelula(td, selDisc.value, novoProf);
	});

		td.appendChild(selDisc);
		td.appendChild(document.createElement('br'));
		td.appendChild(selProf);

		if (horarioExistente) {
			aplicarCorCelula(td, horarioExistente.id_disciplina, horarioExistente.id_professor);
		}
	}

	// [ALTERAÇÃO LEVE] lista apenas professores que lecionam a disciplina escolhida na turma
function refazerProfessores(selDisc, selProf, diaSemana, numeroAula, discIdAtual = '') {
    const currentProf = selProf.value;
    selProf.innerHTML = '';
    selProf.appendChild(new Option('--Prof--', ''));

    if (!discIdAtual) {
        selProf.value = '';
        return;
    }

    const mapTurma = profDiscTurmaMap[idTurmaSelecionada] || {};
    const profsDaDisciplina = (mapTurma[discIdAtual] || []).map(Number);

    const h = (dadosTurma.horarios || [])
        .find(x => x.dia_semana === diaSemana && parseInt(x.numero_aula,10) === numeroAula);

    (dadosTurma.professores || []).forEach(prof => {
        const pid = parseInt(prof.id_professor, 10);
        const displayName = prof.nome_exibicao || ('Prof ' + pid);

        if (!profsDaDisciplina.includes(pid)) return;

        if (professorEhRestrito(pid, diaSemana, numeroAula)) {
            const opt = new Option(`❌ ${displayName} (restrito)`, pid);
            opt.disabled = true;
            selProf.appendChild(opt);
            return;
        }

        const conflito = professorOcupado(
            pid,
            diaSemana,
            numeroAula,
            h ? h.id_horario : null
        );

        if (conflito) {
            const opt = new Option(
                `❌ ${displayName} (${conflito.nome_serie} ${conflito.nome_turma})`,
                pid
            );
            opt.disabled = true;
            selProf.appendChild(opt);
            return;
        }

        selProf.appendChild(new Option(displayName, pid));
    });

    if ([...selProf.options].some(o => o.value === currentProf)) {
        selProf.value = currentProf;
    } else {
        selProf.value = '';
    }
}


	/*function refazerDisciplinas(selDisc, selProf, diaSemana, numeroAula) {
		const profId = parseInt(selProf.value, 10) || 0;
		const discSelecionada = selDisc.value;
		selDisc.innerHTML = '';
		selDisc.appendChild(new Option('--Disc--', ''));

		if (profId && profDiscTurmaMap[idTurmaSelecionada]) {
			const mapDisc = profDiscTurmaMap[idTurmaSelecionada] || {};
			const discDoProf = Object.keys(mapDisc)
				.filter(did => mapDisc[did].includes(profId))
				.map(x => parseInt(x, 10));

			(dadosTurma.serie_disciplinas || []).forEach(d => {
				if (discDoProf.includes(d.id_disciplina)) {
					const opt = new Option(d.nome_disciplina, d.id_disciplina);
					if (!checarSaldoDisciplina(d.id_disciplina, true)) {
						opt.text = '❌ ' + d.nome_disciplina + ' (0 disponíveis)';
						opt.disabled = true;
					}
					selDisc.appendChild(opt);
				}
			});
		} else {
			(dadosTurma.serie_disciplinas || []).forEach(d => {
				const opt = new Option(d.nome_disciplina, d.id_disciplina);
				if (!checarSaldoDisciplina(d.id_disciplina, true)) {
					opt.text = '❌ ' + d.nome_disciplina + ' (0 disponíveis)';
					opt.disabled = true;
				}
				selDisc.appendChild(opt);
			});
		}

		const existeNaLista = Array.from(selDisc.options).some(o => String(o.value) === String(discSelecionada));
		selDisc.value = existeNaLista ? discSelecionada : '';
	}*/

function refazerDisciplinas(selDisc, selProf, diaSemana, numeroAula) {
    console.log('🟡 refazerDisciplinas chamado:', {
        profValue: selProf?.value,
        diaSemana,
        numeroAula
    });
    
    const profId = parseInt(selProf.value, 10) || 0;
    const discSelecionada = selDisc.value;
    selDisc.innerHTML = '';
    selDisc.appendChild(new Option('--Disc--', ''));

    if (profId && profDiscTurmaMap[idTurmaSelecionada]) {
        const mapDisc = profDiscTurmaMap[idTurmaSelecionada] || {};
        const discDoProf = Object.keys(mapDisc)
            .filter(did => mapDisc[did].includes(profId))
            .map(x => parseInt(x, 10));

        console.log('🟡 Professor tem disciplinas:', discDoProf);

        (dadosTurma.serie_disciplinas || []).forEach(d => {
            if (discDoProf.includes(d.id_disciplina)) {
                const opt = new Option(d.nome_disciplina, d.id_disciplina);
                if (!checarSaldoDisciplina(d.id_disciplina, true)) {
                    opt.text = '❌ ' + d.nome_disciplina + ' (0 disponíveis)';
                    opt.disabled = true;
                }
                selDisc.appendChild(opt);
            }
        });
    } else {
        console.log('🟡 Sem professor - Mostrando todas as disciplinas');
        (dadosTurma.serie_disciplinas || []).forEach(d => {
            const opt = new Option(d.nome_disciplina, d.id_disciplina);
            if (!checarSaldoDisciplina(d.id_disciplina, true)) {
                opt.text = '❌ ' + d.nome_disciplina + ' (0 disponíveis)';
                opt.disabled = true;
            }
            selDisc.appendChild(opt);
        });
    }

    console.log('🟡 Total de opções criadas:', selDisc.options.length);

    const existeNaLista = Array.from(selDisc.options).some(o => String(o.value) === String(discSelecionada));
    selDisc.value = existeNaLista ? discSelecionada : '';
    
    console.log('🟡 Valor final do select:', selDisc.value);
}

	function getProfessoresVinculadosTurma() {
		const linked = new Set();
		if (profDiscTurmaMap[idTurmaSelecionada]) {
			Object.values(profDiscTurmaMap[idTurmaSelecionada]).forEach(arr => {
				arr.forEach(pid => linked.add(pid));
			});
		}
		return linked;
	}

	function professorEhRestrito(profId, diaSemana, numeroAula) {
		const dias = professorRestricoesMap[profId];
		if (!dias) return false;
		const aulasRestritas = dias[diaSemana] || [];
		return aulasRestritas.includes(numeroAula);
	}

    // =============================================
    // 3) professorOcupado() - CORRIGIDO COM DEBUG
    // Verifica conflitos em TODAS as turmas do ano
    // =============================================

function professorOcupado(profId, diaSemana, numeroAula, ignoreHorarioId = null) {
    console.log('🔍 Verificando ocupação:', {
        profId,
        diaSemana,
        numeroAula,
        ignoreHorarioId,
        totalHorarios: allHorariosDoAno.length,
        horariosDoProf: allHorariosDoAno.filter(h => String(h.id_professor) === String(profId))
    });
    
    const conflict = allHorariosDoAno.find(h => {
        const mesmoProf = String(h.id_professor) === String(profId);
        const mesmoDia = h.dia_semana === diaSemana;
        const mesmaAula = parseInt(h.numero_aula,10) === parseInt(numeroAula,10);
        const outroHorario = ignoreHorarioId ? String(h.id_horario) !== String(ignoreHorarioId) : true;
        
        console.log('🔍 Comparando horário:', {
            id_horario: h.id_horario,
            mesmoProf,
            mesmoDia,
            mesmaAula,
            outroHorario,
            h_professor: h.id_professor,
            h_dia: h.dia_semana,
            h_aula: h.numero_aula,
            turma: `${h.nome_serie} ${h.nome_turma}`
        });

        return mesmoProf && mesmoDia && mesmaAula && outroHorario;
    });

    console.log('🔍 Conflito encontrado?', conflict);

    return conflict
        ? { nome_serie: conflict.nome_serie, nome_turma: conflict.nome_turma }
        : null;
}


	function checarSaldoDisciplina(discId, apenasVerificar = false) {
		if (!discId) return true;
		const limite = disciplineWeeklyLimit[discId] || 0;
		const usado	= usedDisciplineCount[discId] || 0;
		return (limite - usado > 0);
	}

/*function salvarOuAtualizar(diaSemana, numeroAula, discId, profId, td) {
    if (!idTurmaSelecionada) return;

    const h = (dadosTurma.horarios || [])
        .find(x => x.dia_semana === diaSemana && parseInt(x.numero_aula,10) === numeroAula);

    if (!discId && !profId) {
        deletarHorario(diaSemana, numeroAula);
        return;
    }

    if (!discId || !profId) return;

    if (professorEhRestrito(profId, diaSemana, numeroAula)) {
        alert("O professor está restrito neste horário.");
        // Limpa os campos
        const selDisc = td.querySelector('.select-disciplina');
        const selProf = td.querySelector('.select-professor');
        if (selDisc) selDisc.value = '';
        if (selProf) selProf.value = '';
        aplicarCorCelula(td, '', '');
        return;
    }

    const conflict = professorOcupado(
        profId,
        diaSemana,
        numeroAula,
        h ? h.id_horario : null
    );

    if (conflict) {
        alert(`O professor já está ocupado no mesmo horário (Turma: ${conflict.nome_serie} ${conflict.nome_turma}).`);
        // Limpa os campos
        const selDisc = td.querySelector('.select-disciplina');
        const selProf = td.querySelector('.select-professor');
        if (selDisc) selDisc.value = '';
        if (selProf) selProf.value = '';
        aplicarCorCelula(td, '', '');
        return;
    }

    if (!checarSaldoDisciplina(discId)) {
        alert("A disciplina não possui mais aulas disponíveis.");
        // Limpa os campos
        const selDisc = td.querySelector('.select-disciplina');
        const selProf = td.querySelector('.select-professor');
        if (selDisc) selDisc.value = '';
        if (selProf) selProf.value = '';
        aplicarCorCelula(td, '', '');
        return;
    }

    if (!h) {
        inserirHorario(diaSemana, numeroAula, discId, profId);
    } else {
        registrarHistorico(h).then(() => {
            atualizarHorario(h.id_horario, discId, profId);
        });
    }
}*/


function salvarOuAtualizar(diaSemana, numeroAula, discId, profId, td) {
    console.log('🔵 salvarOuAtualizar chamado:', {diaSemana, numeroAula, discId, profId});
    
    if (!idTurmaSelecionada) return;

    const h = (dadosTurma.horarios || [])
        .find(x => x.dia_semana === diaSemana && parseInt(x.numero_aula,10) === numeroAula);

    if (!discId && !profId) {
        deletarHorario(diaSemana, numeroAula);
        return;
    }

    if (!discId || !profId) return;

    const selDisc = td.querySelector('.select-disciplina');
    const selProf = td.querySelector('.select-professor');

    console.log('🔵 Selects encontrados:', {
        selDisc: !!selDisc, 
        selProf: !!selProf,
        selDiscValue: selDisc?.value,
        selProfValue: selProf?.value
    });

    if (professorEhRestrito(profId, diaSemana, numeroAula)) {
        console.log('🔴 Professor restrito - Limpando campos');
        alert("O professor está restrito neste horário.");
        selDisc.value = '';
        selProf.value = '';
        console.log('🔵 Antes de refazerProfessores');
        refazerProfessores(selDisc, selProf, diaSemana, numeroAula, '');
        console.log('🔵 Antes de refazerDisciplinas');
        refazerDisciplinas(selDisc, selProf, diaSemana, numeroAula);
        console.log('🔵 Após refazer - Valores:', {
            selDiscValue: selDisc.value,
            selProfValue: selProf.value,
            selDiscOptions: selDisc.options.length,
            selProfOptions: selProf.options.length
        });
        aplicarCorCelula(td, '', '');
        return Promise.resolve();
    }

    const conflict = professorOcupado(
        profId,
        diaSemana,
        numeroAula,
        h ? h.id_horario : null
    );

    if (conflict) {
        console.log('🔴 Conflito de professor - Limpando campos');
        alert(`O professor já está ocupado no mesmo horário (Turma: ${conflict.nome_serie} ${conflict.nome_turma}).`);
        selDisc.value = '';
        selProf.value = '';
        console.log('🔵 Antes de refazerProfessores');
        refazerProfessores(selDisc, selProf, diaSemana, numeroAula, '');
        console.log('🔵 Antes de refazerDisciplinas');
        refazerDisciplinas(selDisc, selProf, diaSemana, numeroAula);
        console.log('🔵 Após refazer - Valores:', {
            selDiscValue: selDisc.value,
            selProfValue: selProf.value,
            selDiscOptions: selDisc.options.length,
            selProfOptions: selProf.options.length
        });
        aplicarCorCelula(td, '', '');
        return Promise.resolve();
    }

    if (!checarSaldoDisciplina(discId)) {
        console.log('🔴 Sem saldo de disciplina - Limpando campos');
        alert("A disciplina não possui mais aulas disponíveis.");
        selDisc.value = '';
        selProf.value = '';
        console.log('🔵 Antes de refazerProfessores');
        refazerProfessores(selDisc, selProf, diaSemana, numeroAula, '');
        console.log('🔵 Antes de refazerDisciplinas');
        refazerDisciplinas(selDisc, selProf, diaSemana, numeroAula);
        console.log('🔵 Após refazer - Valores:', {
            selDiscValue: selDisc.value,
            selProfValue: selProf.value,
            selDiscOptions: selDisc.options.length,
            selProfOptions: selProf.options.length
        });
        aplicarCorCelula(td, '', '');
        return Promise.resolve();
    }

    console.log('✅ Salvando horário normalmente');
    if (!h) {
        return inserirHorario(diaSemana, numeroAula, discId, profId);
    } else {
        return registrarHistorico(h).then(() => {
            return atualizarHorario(h.id_horario, discId, profId);
        });
    }
}

async function inserirHorario(diaSemana, numeroAula, discId, profId) {
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
            dadosTurma.horarios.push(resp.data);
            
            const turmaInfo = turmasMap[idTurmaSelecionada];
            allHorariosDoAno.push({
                ...resp.data,
                id_turma: idTurmaSelecionada,
                nome_serie: turmaInfo.nome_serie,
                nome_turma: turmaInfo.nome_turma,
                id_serie: turmaInfo.id_serie,
                id_ano_letivo: turmaInfo.id_ano_letivo
            });
            
            if (!usedDisciplineCount[discId]) {
                usedDisciplineCount[discId] = 0;
            }
            usedDisciplineCount[discId]++;
            
            // ❌ REMOVER: montarGrade();
        } else {
            alert(resp.message || 'Erro ao inserir horário');
        }
        atualizarQuadroDisciplinas();
        refreshAllDisciplineOptionStates();
    } catch (err) {
        console.error(err);
        alert('Erro ao inserir horário');
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
            const h = (dadosTurma.horarios || []).find(x => x.id_horario == idHorario);
            if (h) {
                if (h.id_disciplina != discId) {
                    const oldDisc = parseInt(h.id_disciplina, 10);
                    if (usedDisciplineCount[oldDisc]) {
                        usedDisciplineCount[oldDisc]--;
                    }
                    if (!usedDisciplineCount[discId]) {
                        usedDisciplineCount[discId] = 0;
                    }
                    usedDisciplineCount[discId]++;
                }
                h.id_disciplina = discId;
                h.id_professor	= profId;
            }
            const hh = allHorariosDoAno.find(x => x.id_horario == idHorario);
            if (hh) {
                hh.id_disciplina = discId;
                hh.id_professor	= profId;
            }
            
            // ❌ REMOVER: montarGrade();
        } else {
            if (resp.message !== 'Nenhuma alteração ou registro não encontrado.') {
                alert(resp.message);
            }
        }
        atualizarQuadroDisciplinas();
        refreshAllDisciplineOptionStates();
    } catch (err) {
        console.error(err);
        alert('Erro ao atualizar horário');
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
async function deletaNoBanco(diaSemana, numeroAula, idHorario, discId) {
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
            if (idHorario && discId) {
                if (usedDisciplineCount[discId] > 0) {
                    usedDisciplineCount[discId]--;
                }
            }
            dadosTurma.horarios = (dadosTurma.horarios || [])
                .filter(x => x.dia_semana !== diaSemana || parseInt(x.numero_aula) !== numeroAula);
            allHorariosDoAno = allHorariosDoAno.filter(x => x.id_horario != resp.id_horario);
            
            // ❌ REMOVER: montarGrade();
        } else {
            if (resp.message === 'Horário não encontrado.') {
                dadosTurma.horarios = (dadosTurma.horarios || [])
                    .filter(x => x.dia_semana !== diaSemana || parseInt(x.numero_aula) !== numeroAula);
                allHorariosDoAno = allHorariosDoAno.filter(x =>
                    x.id_turma != idTurmaSelecionada ||
                    x.dia_semana !== diaSemana ||
                    parseInt(x.numero_aula) !== numeroAula
                );
                // ✅ MANTER: montarGrade();
            } else {
                alert(resp.message);
            }
        }
        atualizarQuadroDisciplinas();
        refreshAllDisciplineOptionStates();

    } catch (err) {
        console.error(err);
        alert('Erro ao deletar horário');
    }
}

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
	// MONTA QUADRO DE DISCIPLINAS
	// =============================================
	function atualizarQuadroDisciplinas() {
		quadroDisciplinas.innerHTML = '';
		if (!dadosTurma || !dadosTurma.serie_disciplinas) return;

		const table = document.createElement('table');
		table.classList.add('quadro-disc-table');

		const thead = document.createElement('thead');
		const trHead = document.createElement('tr');

		const thDisc = document.createElement('th');
		thDisc.textContent = 'Disciplina';
		trHead.appendChild(thDisc);

		const thQtde = document.createElement('th');
		thQtde.textContent = 'Qtde. de Aulas';
		trHead.appendChild(thQtde);

		const thRest = document.createElement('th');
		thRest.textContent = 'Aulas Restantes';
		trHead.appendChild(thRest);

		thead.appendChild(trHead);
		table.appendChild(thead);

		const tbody = document.createElement('tbody');

		dadosTurma.serie_disciplinas.forEach(d => {
			const discId = d.id_disciplina;
			const nomeDisc = d.nome_disciplina;
			const qtde = disciplineWeeklyLimit[discId] || 0;
			const usadas = usedDisciplineCount[discId] || 0;
			const restantes = qtde - usadas;

			const tr = document.createElement('tr');
			const tdDisc = document.createElement('td');
			tdDisc.textContent = nomeDisc;
			tr.appendChild(tdDisc);

			const tdQtde = document.createElement('td');
			tdQtde.textContent = qtde;
			tr.appendChild(tdQtde);

			const tdRest = document.createElement('td');
			tdRest.textContent = restantes;
			if (restantes <= 0) {
				tdRest.style.color = 'red';
			}
			tr.appendChild(tdRest);

			tbody.appendChild(tr);
		});

		table.appendChild(tbody);
		quadroDisciplinas.appendChild(table);
	}

	function aplicarCorCelula(td, discId, profId) {
		td.style.backgroundColor = (discId && profId) ? '#D5F4DA' : '';
	}


// [ACRÉSCIMO] Reaplica disponibilidade/labels das disciplinas em todas as células visíveis
function refreshAllDisciplineOptionStates() {
  document.querySelectorAll('.tabela-horarios td').forEach(td => {
    const selDisc = td.querySelector('select.select-disciplina');
    const selProf = td.querySelector('select.select-professor');
    if (!selDisc || !selProf) return;
    const currentDisc = selDisc.value;
    // Reconstroi as opções de disciplina conforme professor selecionado e saldos
    refazerDisciplinas(selDisc, selProf /* dia/aula não são usados aqui */, null, null);
    // restaura a seleção se ainda válida
    if ([...selDisc.options].some(o => String(o.value) === String(currentDisc))) {
      selDisc.value = currentDisc;
    }
  });
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
	// PRÉ-VALIDAÇÃO PARA EVITAR FALHA NO AUTOMÁTICO
	// =============================================
	function validarViabilidadeSerie() {
		const problemas = [];
		if (!dadosTurma || !dadosTurma.serie_disciplinas) return problemas;

		const diasComAulas = (dadosTurma.turno_dias || []).filter(td => parseInt(td.aulas_no_dia, 10) > 0);

		dadosTurma.serie_disciplinas.forEach(d => {
			const discId = d.id_disciplina;
			const limite = disciplineWeeklyLimit[discId] || 0;
			if (limite <= 0) {
				problemas.push(`Disciplina "${d.nome_disciplina}" ficou com 0 aulas por divisão entre turmas.`);
				return;
			}
			const mapTurma = profDiscTurmaMap[idTurmaSelecionada] || {};
			const profs = (mapTurma[discId] || []).map(Number);
		 if (profs.length === 0) {
				problemas.push(`Disciplina "${d.nome_disciplina}" sem professor vinculado na turma.`);
				return;
			}

			let slotsViaveis = 0;
			diasComAulas.forEach(td => {
				const n = parseInt(td.aulas_no_dia, 10);
				for (let aula = 1; aula <= n; aula++) {
					const algumLivre = profs.some(pid =>
						!professorEhRestrito(pid, td.dia_semana, aula) &&
						!professorOcupado(pid, td.dia_semana, aula)
					);
					if (algumLivre) slotsViaveis++;
				}
			});

			if (slotsViaveis < limite) {
				problemas.push(`"${d.nome_disciplina}" requer ${limite} aulas mas só há ${slotsViaveis} slots viáveis.`);
			}
		});

		return problemas;
	}

	// =============================================
	// UTILS
	// =============================================
	function traduzDia(dia) {
		switch (dia) {
			case 'Domingo': return 'Domingo';
			case 'Segunda': return 'Segunda';
			case 'Terca':	 return 'Terça';
			case 'Quarta':	return 'Quarta';
			case 'Quinta':	return 'Quinta';
			case 'Sexta':	 return 'Sexta';
			case 'Sabado':	return 'Sábado';
			default:		return dia;
		}
	}

	// Carrega anos ao iniciar
	showNoData(); // [ACRÉSCIMO] placeholder ao abrir a tela
	loadAnosLetivos();
});