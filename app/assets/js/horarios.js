// app/assets/js/horarios.js
document.addEventListener('DOMContentLoaded', function () {

  // =============================================
  // ELEMENTOS DA PÁGINA
  // =============================================
  const selectAnoLetivo   = document.getElementById('selectAnoLetivo');
  const selectNivelEnsino = document.getElementById('selectNivelEnsino');
  const selectTurno       = document.getElementById('selectTurno');
  const selectTurma       = document.getElementById('selectTurma');

  const btnImprimir   = document.getElementById('btnImprimir');
  const btnAutomatico = document.getElementById('btn-automatic');

  const gradeContainer   = document.getElementById('grade-container');
  const modalAutomatico  = document.getElementById('modal-automatico');
  const modalExtra       = document.getElementById('modal-extra');
  const quadroDisciplinas = document.getElementById('quadro-disciplinas');

  const contentDataHorarios = document.getElementById('content-data-horarios');
  const noDataMessage       = document.getElementById('no-data-message');

  // =============================================
  // HELPERS
  // =============================================
  async function fetchJson(url, options = {}) {
    const r = await fetch(url, options);
    const text = await r.text();
    try {
      return JSON.parse(text);
    } catch (e) {
      console.error('Resposta não é JSON:', { url, text });
      throw e;
    }
  }

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

	function updateBtnAutomaticoState() {
		const ok = !!(idAnoSelecionado && idNivelEnsinoSelecionado && idTurnoSelecionado);
		btnAutomatico.disabled = !ok;
	}

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

  // =============================================
  // VARIÁVEIS GLOBAIS
  // =============================================
  let idAnoSelecionado = null;
  let idNivelEnsinoSelecionado = null;
  let idTurnoSelecionado = null;
  let idTurmaSelecionada = null;

  let editingEnabled = false;

  let dadosTurma = null;

  // Mapas
  let turmasMap = {};            // id_turma -> info
  let turmaTurnoLookup = {};     // id_turma -> id_turno (para completar turnos em horários do ano)
  let profDiscTurmaMap = {};     // id_turma -> id_disciplina -> [id_professor]
  let professorRestricoesMap = {}; // id_professor -> id_turno -> dia -> [aulas]

  // Horários do ano (filtrado por turno selecionado)
  let allHorariosDoAno = [];

  // Intervalos e extras
  let intervalPositions = [];
  let extraClassMapping = {};

  // Controle de limite de aulas por disciplina (na turma atual)
  let usedDisciplineCount = {};      // id_disciplina -> usadas
  let disciplineWeeklyLimit = {};    // id_disciplina -> limite

  // =============================================
  // RESETs
  // =============================================
  function resetNivelEnsinoSelection() {
    if (!selectNivelEnsino) return;
    selectNivelEnsino.innerHTML = '<option value="">-- Selecione o Nível --</option>';
    selectNivelEnsino.disabled = true;
    idNivelEnsinoSelecionado = null;
    updateBtnAutomaticoState();
  }

  function resetTurnoSelection() {
    if (!selectTurno) return;
    selectTurno.innerHTML = '<option value="">-- Selecione o Turno --</option>';
    selectTurno.disabled = true;
    idTurnoSelecionado = null;
    updateBtnAutomaticoState();
  }

  function resetTurmaSelection() {
    if (!selectTurma) return;
    selectTurma.innerHTML = '<option value="">-- Selecione a Turma --</option>';
    selectTurma.disabled = true;

    idTurmaSelecionada = null;
    editingEnabled = false;

    dadosTurma = null;
    allHorariosDoAno = [];
    professorRestricoesMap = {};
    profDiscTurmaMap = {};
    intervalPositions = [];
    extraClassMapping = {};
    usedDisciplineCount = {};
    disciplineWeeklyLimit = {};

    if (gradeContainer) gradeContainer.innerHTML = '';
    if (quadroDisciplinas) quadroDisciplinas.innerHTML = '';
    showNoData();
  }

  // =============================================
  // 1) CARREGAR ANOS
  // =============================================
  async function loadAnosLetivos() {
    if (!selectAnoLetivo) return;
    try {
      const resp = await fetchJson('/horarios/app/controllers/ano-letivo/listAnoLetivo.php');
      if (resp.status === 'success' && Array.isArray(resp.data)) {
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

  if (selectAnoLetivo) {
    selectAnoLetivo.addEventListener('change', () => {
      idAnoSelecionado = selectAnoLetivo.value || null;
      resetNivelEnsinoSelection();
      resetTurnoSelection();
      resetTurmaSelection();
      if (idAnoSelecionado) loadNiveisPorAno(idAnoSelecionado);
      updateBtnAutomaticoState();
    });
  }

  // =============================================
// 2) NÍVEIS POR ANO
// =============================================
async function loadNiveisPorAno(idAno) {
  if (!selectNivelEnsino) return;

  try {
    const resp = await fetchJson(
      `/horarios/app/controllers/nivel-ensino/listNivelEnsinoByUserAndAno.php?id_ano_letivo=${encodeURIComponent(idAno)}`
    );

    selectNivelEnsino.innerHTML = '<option value="">-- Selecione o Nível --</option>';

    if (resp.status === 'success' && Array.isArray(resp.data) && resp.data.length > 0) {
      selectNivelEnsino.disabled = false;

      resp.data.forEach(niv => {
        const opt = document.createElement('option');
        opt.value = String(niv.id_nivel_ensino);
        opt.textContent = niv.nome_nivel_ensino;
        selectNivelEnsino.appendChild(opt);
      });

      // Se tiver só 1 nível, seleciona e já carrega turnos
      if (resp.data.length === 1) {
        selectNivelEnsino.value = String(resp.data[0].id_nivel_ensino);
        idNivelEnsinoSelecionado = selectNivelEnsino.value;

        // ao setar nível: limpa turno/turma e carrega turnos
        resetTurnoSelection();   // deixa desabilitado e limpa valor
        resetTurmaSelection();
        updateBtnAutomaticoState(); // ainda desabilita (não tem turno)

        await loadTurnosPorAnoENivel(idAnoSelecionado, idNivelEnsinoSelecionado);
      } else {
        // vários níveis: só garante que turno e turma estejam limpos
        resetTurnoSelection();
        resetTurmaSelection();
        updateBtnAutomaticoState();
      }

    } else {
      selectNivelEnsino.innerHTML = '<option value="">Nenhum nível encontrado</option>';
      selectNivelEnsino.disabled = true;

      resetTurnoSelection();
      resetTurmaSelection();
      updateBtnAutomaticoState();
    }

  } catch (err) {
    console.error('Erro loadNiveisPorAno:', err);

    selectNivelEnsino.innerHTML = '<option value="">Erro ao carregar níveis</option>';
    selectNivelEnsino.disabled = true;

    resetTurnoSelection();
    resetTurmaSelection();
    updateBtnAutomaticoState();
  }
}

// Listener do nível: AQUI é o ponto que você estava procurando
if (selectNivelEnsino) {
  selectNivelEnsino.addEventListener('change', async () => {
    idNivelEnsinoSelecionado = selectNivelEnsino.value || null;

    // ao trocar nível: limpa turno e turma e carrega turnos
    resetTurnoSelection();
    resetTurmaSelection();

    // botão automático só libera com turno -> aqui ainda fica desabilitado
    updateBtnAutomaticoState();

    if (idAnoSelecionado && idNivelEnsinoSelecionado) {
      await loadTurnosPorAnoENivel(idAnoSelecionado, idNivelEnsinoSelecionado);
    }
  });
}

// =============================================
// 2.1) TURNOS POR ANO + NÍVEL
// =============================================
async function loadTurnosPorAnoENivel(idAno, idNivel) {
  if (!selectTurno) return;

  // habilita e mostra placeholder durante o fetch
  selectTurno.disabled = false;
  selectTurno.innerHTML = '<option value="">Carregando turnos...</option>';

  // enquanto carrega turnos, ainda não tem turno selecionado
  idTurnoSelecionado = null;
  updateBtnAutomaticoState();

  try {
    const url =
      `/horarios/app/controllers/turno/listTurnoByUserAnoNivel.php` +
      `?id_ano_letivo=${encodeURIComponent(idAno)}` +
      `&id_nivel_ensino=${encodeURIComponent(idNivel)}`;

    const resp = await fetchJson(url);

    const ok = String(resp?.status || '').toLowerCase() === 'success';
    const data = Array.isArray(resp?.data) ? resp.data : [];

    selectTurno.innerHTML = '<option value="">-- Selecione o Turno --</option>';

    if (ok && data.length > 0) {
      data.forEach(t => {
        const opt = document.createElement('option');
        opt.value = String(t.id_turno);
        opt.textContent = t.nome_turno ?? `Turno ${t.id_turno}`;
        selectTurno.appendChild(opt);
      });

      // Se tiver só 1 turno, seleciona e carrega turmas automaticamente
      if (data.length === 1) {
        selectTurno.value = String(data[0].id_turno);
        idTurnoSelecionado = selectTurno.value;

        // ✅ botão automático libera aqui
        updateBtnAutomaticoState();

        // carrega turmas do turno
        resetTurmaSelection();
        await loadTurmasPorAnoNivelTurno(idAnoSelecionado, idNivelEnsinoSelecionado, idTurnoSelecionado);
      } else {
        // vários turnos: aguarda usuário escolher
        idTurnoSelecionado = null;

        // botão permanece desabilitado até escolher um turno
        updateBtnAutomaticoState();

        // turma ainda não faz sentido sem turno
        resetTurmaSelection();
      }

    } else {
      selectTurno.innerHTML = '<option value="">Nenhum turno encontrado</option>';
      selectTurno.disabled = true;

      idTurnoSelecionado = null;
      resetTurmaSelection();
      updateBtnAutomaticoState();

      console.warn('Sem turnos retornados:', resp);
    }

  } catch (err) {
    console.error('Erro loadTurnosPorAnoENivel:', err);

    selectTurno.innerHTML = '<option value="">Erro ao carregar turnos</option>';
    selectTurno.disabled = true;

    idTurnoSelecionado = null;
    resetTurmaSelection();
    updateBtnAutomaticoState();
  }
}

// Listener do turno: ao escolher turno, libera botão e carrega turmas
if (selectTurno) {
  selectTurno.addEventListener('change', async () => {
    idTurnoSelecionado = selectTurno.value || null;

    // ao trocar turno: limpa turma
    resetTurmaSelection();

    // ✅ botão automático libera aqui (não depende de turma)
    updateBtnAutomaticoState();

    if (idAnoSelecionado && idNivelEnsinoSelecionado && idTurnoSelecionado) {
      await loadTurmasPorAnoNivelTurno(idAnoSelecionado, idNivelEnsinoSelecionado, idTurnoSelecionado);
    }
  });
}

  // =============================================
  // 4) TURMAS POR ANO + NÍVEL + TURNO
  // =============================================
  async function loadTurmasPorAnoNivelTurno(idAno, idNivel, idTurno) {
    if (!selectTurma) return;

    selectTurma.innerHTML = '<option value="">-- Selecione a Turma --</option>';
    selectTurma.disabled = true;
    turmasMap = {};
    turmaTurnoLookup = {};

    if (!idAno || !idNivel || !idTurno) return;

    try {
      const url =
        `/horarios/app/controllers/turma/listTurmaByUserAndAno.php` +
        `?id_ano_letivo=${encodeURIComponent(idAno)}` +
        `&id_nivel_ensino=${encodeURIComponent(idNivel)}` +
        `&id_turno=${encodeURIComponent(idTurno)}`;

      const resp = await fetchJson(url);

      if (resp.status === 'success' && Array.isArray(resp.data) && resp.data.length > 0) {
        selectTurma.disabled = false;

        resp.data.forEach(t => {
          const idTurma = String(t.id_turma);
          const idSerie = t.id_serie ?? null;
          const idTurnoLocal = t.id_turno ?? idTurno;

          const opt = document.createElement('option');
          opt.value = idTurma;
          opt.textContent = `${t.nome_serie} ${t.nome_turma}`;
          selectTurma.appendChild(opt);

          turmasMap[idTurma] = {
            id_turma: idTurma,
            id_serie: idSerie,
            id_ano_letivo: t.id_ano_letivo ?? idAno,
            id_turno: idTurnoLocal,
            nome_serie: t.nome_serie ?? '',
            nome_turma: t.nome_turma ?? '',
            nome_turno: t.nome_turno ?? ''
          };

          turmaTurnoLookup[idTurma] = String(idTurnoLocal);
        });
      } else {
        console.warn('Nenhuma turma retornada:', resp);
      }
    } catch (err) {
      console.error('Erro loadTurmasPorAnoNivelTurno:', err);
    }
  }

  if (selectTurma) {
    selectTurma.addEventListener('change', async () => {
      const turmaVal = selectTurma.value;
      if (!turmaVal) {
        resetTurmaSelection();
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
    });
  }

  // =============================================
  // BOTÃO AUTOMÁTICO + MODAL
  // =============================================
  /*if (btnAutomatico) {
    btnAutomatico.disabled = true;
    btnAutomatico.addEventListener('click', () => {
      if (!idAnoSelecionado || !idNivelEnsinoSelecionado || !idTurnoSelecionado) return;
      //openModalAutomatico();
      window.iniciarGeracaoComModal(idAnoSelecionado, idNivelEnsinoSelecionado);
    });
  }*/

if (btnAutomatico) {
  btnAutomatico.disabled = true;
  btnAutomatico.addEventListener('click', () => {
    if (!idAnoSelecionado || !idNivelEnsinoSelecionado || !idTurnoSelecionado) return;

    // Se você usa a função externa iniciarGeracaoComModal, passe o turno também
    if (typeof window.iniciarGeracaoComModal === 'function') {
      window.iniciarGeracaoComModal(idAnoSelecionado, idNivelEnsinoSelecionado, idTurnoSelecionado);
      return;
    }

    // fallback: abre modal local se existir
    // openModalAutomatico();
  });
}


  function openModalAutomatico() {
    if (!modalAutomatico) {
      alert('Modal de geração automática não encontrado!');
      return;
    }
    modalAutomatico.style.display = 'block';
    modalAutomatico.classList.remove('fade-out');
    modalAutomatico.classList.add('fade-in');

    const content = modalAutomatico.querySelector('.modal-content');
    if (content) {
      content.classList.remove('slide-up');
      content.classList.add('slide-down');
    }
  }

  function closeModalAutomatico() {
    if (!modalAutomatico) return;

    const content = modalAutomatico.querySelector('.modal-content');
    if (content) {
      content.classList.remove('slide-down');
      content.classList.add('slide-up');
    }

    modalAutomatico.classList.remove('fade-in');
    modalAutomatico.classList.add('fade-out');

    setTimeout(() => {
      modalAutomatico.style.display = 'none';
      if (content) content.classList.remove('slide-up');
      modalAutomatico.classList.remove('fade-out');
    }, 300);
  }

  if (modalAutomatico) {
    const btnConf  = modalAutomatico.querySelector('#btnConfirmarAutomatico');
    const btnCanc  = modalAutomatico.querySelector('#btnCancelarAutomatico');
    const btnClose = modalAutomatico.querySelector('.close-modal-auto');

    if (btnConf) {
      btnConf.onclick = async () => {
        // ✅ agora a geração é por (ano + nível + turno), não por turma
        if (!idAnoSelecionado || !idNivelEnsinoSelecionado || !idTurnoSelecionado) {
          alert('Selecione Ano Letivo, Nível de Ensino e Turno antes de gerar.');
          return;
        }

        closeModalAutomatico();
        await gerarHorariosAutomaticos();
      };

    }
    if (btnCanc) btnCanc.onclick = () => closeModalAutomatico();
    if (btnClose) btnClose.onclick = () => closeModalAutomatico();
  }

  async function gerarHorariosAutomaticos() {
    try {
      const body = new URLSearchParams({
        id_ano_letivo: idAnoSelecionado,
        id_nivel_ensino: idNivelEnsinoSelecionado,
        id_turno: idTurnoSelecionado, // ✅ importante
        max_backtracks: 300000,
        max_chain_depth: 10,
        max_global_depth: 7,
        ativar_ef_espelhada: 'true',
        fix_mode: 'soft',
        debug_alloc: 0,
        debug_alloc_max: 2000
      });


      const resp = await fetchJson('/horarios/app/controllers/horarios/gerarHorariosAutomaticos.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body
      });

      if (resp.status === 'success') {
        console.clear();
        console.log('========================================');
        console.log('📊 DIAGNÓSTICO DE GERAÇÃO DE HORÁRIOS');
        console.log('========================================\n');
        console.log(resp.message);

        mostrarDiagnosticoModal(resp.message);

        //alert('✅ Horários gerados com sucesso!\n\nVeja o diagnóstico:\n• Console (F12)\n• Modal');
        if (resp.status === 'success') {
          mostrarDiagnosticoModal(resp.message);

          if (resp.completed === false) {
            alert('⚠️ Geração concluída, mas NÃO fechou 100%.\nVeja o diagnóstico no modal/console.');
          } else {
            alert('✅ Horários gerados 100%.\nVeja o diagnóstico no modal/console.');
          }
        }


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

  function mostrarDiagnosticoModal(mensagem) {
    const modalAntigo = document.getElementById('modal-diagnostico');
    if (modalAntigo) modalAntigo.remove();

    const modal = document.createElement('div');
    modal.id = 'modal-diagnostico';
    modal.style.cssText = `
      position: fixed; top: 0; left: 0; width: 100%; height: 100%;
      background: rgba(0,0,0,0.8); z-index: 10000;
      display: flex; align-items: center; justify-content: center;
      padding: 20px;
    `;

    const conteudo = document.createElement('div');
    conteudo.style.cssText = `
      background: white; border-radius: 12px; padding: 30px;
      max-width: 900px; max-height: 80vh; overflow-y: auto;
      box-shadow: 0 10px 40px rgba(0,0,0,0.3);
      position: relative;
    `;

    conteudo.innerHTML = `
      <button id="fechar-diagnostico" style="
        position: absolute; top: 15px; right: 15px;
        background: #f44336; color: white; border: none; border-radius: 50%;
        width: 35px; height: 35px; font-size: 20px; cursor: pointer; font-weight: bold;
      ">×</button>

      <h2 style="color: #4CAF50; margin-top: 0;">📊 Diagnóstico Completo</h2>
      <pre style="
        background: #f5f5f5; padding: 20px; border-radius: 8px;
        overflow-x: auto; white-space: pre-wrap;
        font-family: 'Courier New', monospace; font-size: 13px; line-height: 1.6;
      "></pre>

      <div style="margin-top: 20px; text-align: center;">
        <button id="copiar-diagnostico" style="
          background: #2196F3; color: white; border: none;
          padding: 12px 30px; border-radius: 6px; cursor: pointer;
          font-size: 14px; margin-right: 10px;
        ">📋 Copiar</button>

        <button id="baixar-diagnostico" style="
          background: #4CAF50; color: white; border: none;
          padding: 12px 30px; border-radius: 6px; cursor: pointer;
          font-size: 14px;
        ">💾 Baixar TXT</button>
      </div>
    `;

    conteudo.querySelector('pre').textContent = mensagem;

    modal.appendChild(conteudo);
    document.body.appendChild(modal);

    document.getElementById('fechar-diagnostico').onclick = () => modal.remove();
    modal.onclick = (e) => { if (e.target === modal) modal.remove(); };

    document.getElementById('copiar-diagnostico').onclick = () => {
      navigator.clipboard.writeText(mensagem).then(() => {
        alert('✅ Diagnóstico copiado!');
      }).catch(() => {
        alert('❌ Erro ao copiar. Copie manualmente.');
      });
    };

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
  // CARREGAR DADOS “MASTER”
  // =============================================
  async function carregarTudo() {
    await loadTurmaTurnoLookup(idAnoSelecionado, idNivelEnsinoSelecionado);
    await loadAllHorariosDoAno(idAnoSelecionado);
    await loadProfessorRestricoes(idAnoSelecionado);
    await loadProfessorDisciplinaTurma();
    await loadHorariosTurma(idTurmaSelecionada);

    inicializarLimitesDisciplinas();
    recalcularUsosDasDisciplinas();
  }

  async function loadAllHorariosDoAno(idAno) {
    try {
      const resp = await fetchJson(`/horarios/app/controllers/horarios/listHorariosByAnoLetivo.php?id_ano_letivo=${encodeURIComponent(idAno)}`);
      if (resp.status === 'success' && Array.isArray(resp.data)) {
        const turnoAtual = String(idTurnoSelecionado || '');
        allHorariosDoAno = resp.data.map(h => {
          const idTurma = String(h.id_turma);
          const turnoDaTurma = turmaTurnoLookup[idTurma] || null;
          return { ...h, id_turno: h.id_turno ?? turnoDaTurma };
        });

        if (turnoAtual) {
          allHorariosDoAno = allHorariosDoAno.filter(h => String(h.id_turno || '') === turnoAtual);
        }
      } else {
        allHorariosDoAno = [];
      }
    } catch (err) {
      console.error('Erro loadAllHorariosDoAno:', err);
      allHorariosDoAno = [];
    }
  }

  async function loadProfessorRestricoes(idAno) {
    professorRestricoesMap = {};
    if (!idAno || !idTurnoSelecionado) return;

    try {
      const resp = await fetchJson(`/horarios/app/controllers/professor-restricoes/listProfessorRestricoes.php?id_ano_letivo=${encodeURIComponent(idAno)}&id_turno=${encodeURIComponent(idTurnoSelecionado)}`);
      if (resp.status !== 'success') return;

      (resp.data || []).forEach(row => {
        const p = String(row.id_professor);
        const turno = String(row.id_turno);
        const dia = row.dia_semana;
        const aula = parseInt(row.numero_aula, 10);

        if (!professorRestricoesMap[p]) professorRestricoesMap[p] = {};
        if (!professorRestricoesMap[p][turno]) professorRestricoesMap[p][turno] = {};
        if (!professorRestricoesMap[p][turno][dia]) professorRestricoesMap[p][turno][dia] = [];
        professorRestricoesMap[p][turno][dia].push(aula);
      });
    } catch (err) {
      console.error('Erro loadProfessorRestricoes:', err);
      professorRestricoesMap = {};
    }
  }

  async function loadProfessorDisciplinaTurma() {
    try {
      const resp = await fetchJson('/horarios/app/controllers/professor-disciplina-turma/listProfessorDisciplinaTurma.php?all=1');
      if (resp.status === 'success' && Array.isArray(resp.data)) {
        profDiscTurmaMap = {};
        resp.data.forEach(row => {
          const t = String(row.id_turma);
          const d = String(row.id_disciplina);
          const p = parseInt(row.id_professor, 10);

          if (!profDiscTurmaMap[t]) profDiscTurmaMap[t] = {};
          if (!profDiscTurmaMap[t][d]) profDiscTurmaMap[t][d] = [];
          profDiscTurmaMap[t][d].push(p);
        });
      }
    } catch (err) {
      console.error(err);
    }
  }

  /*async function loadHorariosTurma(idTurma) {
    try {
      const resp = await fetchJson(`/horarios/app/controllers/horarios/listHorarios.php?id_turma=${encodeURIComponent(idTurma)}`);
      if (resp.status === 'success') {
        dadosTurma = resp.data;

        const intervalStr = dadosTurma?.turma?.intervalos_positions || '';
        intervalPositions = intervalStr
          .split(',')
          .map(n => parseInt(String(n).trim(), 10))
          .filter(x => !isNaN(x) && x > 0);
      } else {
        dadosTurma = null;
      }
    } catch (err) {
      console.error(err);
      dadosTurma = null;
    }
  }*/

async function loadHorariosTurma(idTurma) {
  try {
    const turno = String(idTurnoSelecionado || '');
    const url =
      `/horarios/app/controllers/horarios/listHorarios.php` +
      `?id_turma=${encodeURIComponent(idTurma)}` +
      (turno ? `&id_turno=${encodeURIComponent(turno)}` : '');

    const resp = await fetchJson(url);

    if (resp.status === 'success') {
      dadosTurma = resp.data;

      const intervalStr = dadosTurma?.turma?.intervalos_positions || '';
      intervalPositions = intervalStr
        .split(',')
        .map(n => parseInt(String(n).trim(), 10))
        .filter(x => !isNaN(x) && x > 0);
    } else {
      dadosTurma = null;
    }
  } catch (err) {
    console.error('Erro loadHorariosTurma:', err);
    dadosTurma = null;
  }
}


  async function loadTurmaTurnoLookup(idAno, idNivel) {
    turmaTurnoLookup = {};
    if (!idAno || !idNivel) return;

    try {
      // Requer que o PHP aceite sem id_turno
      const resp = await fetchJson(`/horarios/app/controllers/turma/listTurmaByUserAndAno.php?id_ano_letivo=${encodeURIComponent(idAno)}&id_nivel_ensino=${encodeURIComponent(idNivel)}`);
      if (resp.status !== 'success') return;

      (resp.data || []).forEach(t => {
        turmaTurnoLookup[String(t.id_turma)] = String(t.id_turno);
      });
    } catch (e) {
      console.warn('Falha loadTurmaTurnoLookup:', e);
    }
  }

  // =============================================
  // LIMITES / USO POR DISCIPLINA
  // =============================================
  function inicializarLimitesDisciplinas() {
    disciplineWeeklyLimit = {};
    if (!dadosTurma || !Array.isArray(dadosTurma.serie_disciplinas)) return;

    dadosTurma.serie_disciplinas.forEach(d => {
      disciplineWeeklyLimit[String(d.id_disciplina)] = parseInt(d.aulas_semana, 10) || 0;
    });
  }

  function recalcularUsosDasDisciplinas() {
    usedDisciplineCount = {};
    if (!dadosTurma || !Array.isArray(dadosTurma.horarios)) return;

    dadosTurma.horarios.forEach(h => {
      const did = String(h.id_disciplina || '');
      if (!did) return;
      usedDisciplineCount[did] = (usedDisciplineCount[did] || 0) + 1;
    });
  }

  // ✅ CORREÇÃO: valida saldo ignorando a disciplina já presente no slot atual
  function checarSaldoDisciplina(discId, diaSemana = null, numeroAula = null) {
    if (!discId) return true;

    const did = String(discId);
    const limite = disciplineWeeklyLimit[did] || 0;
    let usado = usedDisciplineCount[did] || 0;

    // se o slot atual já tem essa disciplina, não conte como "usado" para bloquear a própria célula
    if (diaSemana && numeroAula && dadosTurma?.horarios) {
      const h = dadosTurma.horarios.find(x =>
        x.dia_semana === diaSemana && parseInt(x.numero_aula, 10) === parseInt(numeroAula, 10)
      );
      if (h && String(h.id_disciplina) === did) {
        usado = Math.max(0, usado - 1);
      }
    }

    return (limite - usado) > 0;
  }

  // =============================================
  // AULA EXTRA (se usar)
  // =============================================
  async function definirExtraAulas() {
    extraClassMapping = {};
    if (!dadosTurma?.turma) return;

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
      if (!modalExtra) { resolve(); return; }

      const container = modalExtra.querySelector('.modal-content-extra');
      if (!container) { resolve(); return; }

      container.innerHTML = '<h2>Selecione o dia com aula extra</h2>';

      dias.forEach(td => {
        const label = document.createElement('label');
        const radio = document.createElement('input');
        radio.type = 'radio';
        radio.name = 'extraDia';
        radio.value = td.dia_semana;
        label.appendChild(radio);
        label.appendChild(document.createTextNode(' ' + td.dia_semana));
        container.appendChild(label);
        container.appendChild(document.createElement('br'));
      });

      modalExtra.style.display = 'block';

      const btnConf  = modalExtra.querySelector('#btnConfirmarExtra');
      const btnCanc  = modalExtra.querySelector('#btnCancelarExtra');
      const btnClose = modalExtra.querySelector('.close-modal-extra');

      const close = () => { modalExtra.style.display = 'none'; };

      if (btnConf) btnConf.onclick = () => {
        const selected = modalExtra.querySelector('input[name="extraDia"]:checked');
        if (selected) callback(selected.value);
        close();
        resolve();
      };

      if (btnCanc) btnCanc.onclick = () => { close(); resolve(); };
      if (btnClose) btnClose.onclick = () => { close(); resolve(); };
    });
  }

  // =============================================
  // RESTRIÇÃO / CONFLITO
  // =============================================
  function professorEhRestrito(profId, diaSemana, numeroAula) {
    const p = String(profId);
    const turno = String(idTurnoSelecionado || '');
    if (!turno) return false;

    const byProf = professorRestricoesMap[p];
    if (!byProf) return false;

    const byTurno = byProf[turno];
    if (!byTurno) return false;

    const aulasRestritas = byTurno[diaSemana] || [];
    return aulasRestritas.includes(parseInt(numeroAula, 10));
  }

  function professorOcupado(profId, diaSemana, numeroAula, ignoreHorarioId = null) {
    const turnoAtual = String(idTurnoSelecionado || '');
    if (!turnoAtual) return null;

    const conflict = allHorariosDoAno.find(h => {
      const mesmoProf = String(h.id_professor) === String(profId);
      const mesmoDia = h.dia_semana === diaSemana;
      const mesmaAula = parseInt(h.numero_aula, 10) === parseInt(numeroAula, 10);
      const outroHorario = ignoreHorarioId ? String(h.id_horario) !== String(ignoreHorarioId) : true;
      const mesmoTurno = String(h.id_turno || '') === turnoAtual;
      return mesmoProf && mesmoDia && mesmaAula && outroHorario && mesmoTurno;
    });

    return conflict ? { nome_serie: conflict.nome_serie, nome_turma: conflict.nome_turma } : null;
  }

  // =============================================
  // GRADE
  // =============================================
  function montarGrade() {
    if (contentDataHorarios) contentDataHorarios.style.display = 'block';
    hideNoData();

    if (!gradeContainer) return;
    gradeContainer.innerHTML = '';

    if (!dadosTurma?.turma) {
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

    const tbody = document.createElement('tbody');

    for (let aula = 1; aula <= maxAulasTurno; aula++) {
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

    refreshAllDisciplineOptionStates();
  }

  function aplicarCorCelula(td, discId, profId) {
    td.style.backgroundColor = (discId && profId) ? '#D5F4DA' : '';
  }

  function montarCelulaAula(td, diaSemana, numeroAula) {
    const horarioExistente = (dadosTurma.horarios || []).find(h =>
      h.dia_semana === diaSemana && parseInt(h.numero_aula, 10) === numeroAula
    );

    const selDisc = document.createElement('select');
    selDisc.classList.add('select-disciplina');
    selDisc.appendChild(new Option('--Disc--', ''));

    (dadosTurma.serie_disciplinas || []).forEach(d => {
      selDisc.appendChild(new Option(d.nome_disciplina, String(d.id_disciplina)));
    });

    const selProf = document.createElement('select');
    selProf.classList.add('select-professor');
    selProf.appendChild(new Option('--Prof--', ''));

    if (horarioExistente) {
      selDisc.value = String(horarioExistente.id_disciplina || '');
      refazerProfessores(selDisc, selProf, diaSemana, numeroAula, selDisc.value);
      selProf.value = String(horarioExistente.id_professor || '');
      aplicarCorCelula(td, selDisc.value, selProf.value);
    } else {
      refazerProfessores(selDisc, selProf, diaSemana, numeroAula, selDisc.value);
      refazerDisciplinas(selDisc, selProf, diaSemana, numeroAula);
    }

    selDisc.disabled = !editingEnabled;
    selProf.disabled = !editingEnabled;

    selDisc.addEventListener('change', async () => {
      if (!editingEnabled) return;

      const novaDisc = selDisc.value;

      if (novaDisc && !checarSaldoDisciplina(novaDisc, diaSemana, numeroAula)) {
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
      } else if (!novaDisc && !selProf.value) {
        deletarHorario(diaSemana, numeroAula);
      }

      aplicarCorCelula(td, novaDisc, selProf.value);
      atualizarQuadroDisciplinas();
      refreshAllDisciplineOptionStates();
    });

    selProf.addEventListener('change', async () => {
      if (!editingEnabled) return;

      const novoProf = selProf.value;

      // ✅ aqui também precisa ignorar o slot atual
      if (selDisc.value && !checarSaldoDisciplina(selDisc.value, diaSemana, numeroAula)) {
        alert('Disciplina já está no limite de aulas para esta turma.');
        selProf.value = '';
        refazerProfessores(selDisc, selProf, diaSemana, numeroAula, selDisc.value);
        return;
      }

      refazerDisciplinas(selDisc, selProf, diaSemana, numeroAula);

      if (selDisc.value && novoProf) {
        await salvarOuAtualizar(diaSemana, numeroAula, selDisc.value, novoProf, td);
      } else if (!selDisc.value && !novoProf) {
        deletarHorario(diaSemana, numeroAula);
      }

      aplicarCorCelula(td, selDisc.value, novoProf);
      atualizarQuadroDisciplinas();
      refreshAllDisciplineOptionStates();
    });

    td.appendChild(selDisc);
    td.appendChild(document.createElement('br'));
    td.appendChild(selProf);
  }

  function refazerProfessores(selDisc, selProf, diaSemana, numeroAula, discIdAtual = '') {
    const currentProf = selProf.value;
    selProf.innerHTML = '';
    selProf.appendChild(new Option('--Prof--', ''));

    if (!discIdAtual) {
      selProf.value = '';
      return;
    }

    const mapTurma = profDiscTurmaMap[String(idTurmaSelecionada)] || {};
    const profsDaDisciplina = (mapTurma[String(discIdAtual)] || []).map(Number);

    const h = (dadosTurma.horarios || []).find(x =>
      x.dia_semana === diaSemana && parseInt(x.numero_aula, 10) === parseInt(numeroAula, 10)
    );

    (dadosTurma.professores || []).forEach(prof => {
      const pid = parseInt(prof.id_professor, 10);
      const displayName = prof.nome_exibicao || ('Prof ' + pid);

      if (!profsDaDisciplina.includes(pid)) return;

      if (professorEhRestrito(pid, diaSemana, numeroAula)) {
        const opt = new Option(`❌ ${displayName} (restrito)`, String(pid));
        opt.disabled = true;
        selProf.appendChild(opt);
        return;
      }

      const conflito = professorOcupado(pid, diaSemana, numeroAula, h ? h.id_horario : null);
      if (conflito) {
        const opt = new Option(`❌ ${displayName} (${conflito.nome_serie} ${conflito.nome_turma})`, String(pid));
        opt.disabled = true;
        selProf.appendChild(opt);
        return;
      }

      selProf.appendChild(new Option(displayName, String(pid)));
    });

    if ([...selProf.options].some(o => String(o.value) === String(currentProf))) {
      selProf.value = currentProf;
    } else {
      selProf.value = '';
    }
  }

  function refazerDisciplinas(selDisc, selProf, diaSemana, numeroAula) {
    const profId = parseInt(selProf.value, 10) || 0;
    const discSelecionada = selDisc.value;

    selDisc.innerHTML = '';
    selDisc.appendChild(new Option('--Disc--', ''));

    const turmaMap = profDiscTurmaMap[String(idTurmaSelecionada)] || {};

    // lista de disciplinas permitidas pelo professor (se houver prof selecionado)
    let allowed = null;
    if (profId) {
      allowed = Object.keys(turmaMap).filter(did => (turmaMap[did] || []).includes(profId));
      allowed = new Set(allowed.map(String));
    }

    (dadosTurma.serie_disciplinas || []).forEach(d => {
      const did = String(d.id_disciplina);

      if (allowed && !allowed.has(did)) return;

      const opt = new Option(d.nome_disciplina, did);
      if (!checarSaldoDisciplina(did, diaSemana, numeroAula)) {
        opt.text = `❌ ${d.nome_disciplina} (0 disponíveis)`;
        opt.disabled = true;
      }
      selDisc.appendChild(opt);
    });

    const existeNaLista = Array.from(selDisc.options).some(o => String(o.value) === String(discSelecionada));
    selDisc.value = existeNaLista ? discSelecionada : '';
  }

  function refreshAllDisciplineOptionStates() {
    document.querySelectorAll('.tabela-horarios td').forEach(td => {
      const selDisc = td.querySelector('select.select-disciplina');
      const selProf = td.querySelector('select.select-professor');
      if (!selDisc || !selProf) return;

      const currentDisc = selDisc.value;

      // aqui não temos dia/aula fácil; então só reconstrói sem “ignorar slot”
      // (não é bloqueio, é apenas estado visual das opções)
      refazerDisciplinas(selDisc, selProf, null, null);

      if ([...selDisc.options].some(o => String(o.value) === String(currentDisc))) {
        selDisc.value = currentDisc;
      }
    });
  }

  // =============================================
  // CRUD HORÁRIOS
  // =============================================
  /*async function registrarHistorico(horarioObj) {
    if (!horarioObj?.id_horario) return;

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
      const resp = await fetchJson('/horarios/app/controllers/horarios/archiveHorario.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body
      });
      if (resp.status !== 'success') {
        console.warn('Falha ao registrar histórico:', resp.message);
      }
    } catch (err) {
      console.error(err);
    }
  }*/

async function registrarHistorico(horarioObj) {
  if (!horarioObj?.id_horario) return;

  const body = new URLSearchParams({
    id_horario_original: String(horarioObj.id_horario),
    id_turma: String(horarioObj.id_turma || idTurmaSelecionada || ''),
    id_turno: String(horarioObj.id_turno || idTurnoSelecionado || ''),
    id_ano_letivo: String(horarioObj.id_ano_letivo || idAnoSelecionado || ''),
    dia_semana: String(horarioObj.dia_semana || ''),
    numero_aula: String(horarioObj.numero_aula || ''),
    id_disciplina: String(horarioObj.id_disciplina || ''),
    id_professor: String(horarioObj.id_professor || ''),
    data_criacao: String(horarioObj.data_criacao || '')
  });

  try {
    const resp = await fetchJson('/horarios/app/controllers/horarios/archiveHorario.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body
    });

    if (resp.status !== 'success') {
      console.warn('Falha ao registrar histórico:', resp.message);
    }
  } catch (err) {
    console.error('Erro registrarHistorico:', err);
  }
}


  async function salvarOuAtualizar(diaSemana, numeroAula, discId, profId, td) {
    if (!idTurmaSelecionada) return;

    const h = (dadosTurma.horarios || []).find(x =>
      x.dia_semana === diaSemana && parseInt(x.numero_aula, 10) === parseInt(numeroAula, 10)
    );

    if (!discId || !profId) return;

    // validações
    if (professorEhRestrito(profId, diaSemana, numeroAula)) {
      alert("O professor está restrito neste horário.");
      limparCelula(td, diaSemana, numeroAula);
      return;
    }

    const conflict = professorOcupado(profId, diaSemana, numeroAula, h ? h.id_horario : null);
    if (conflict) {
      alert(`O professor já está ocupado (Turma: ${conflict.nome_serie} ${conflict.nome_turma}).`);
      limparCelula(td, diaSemana, numeroAula);
      return;
    }

    // ✅ usa o checarSaldo corrigido
    if (!checarSaldoDisciplina(discId, diaSemana, numeroAula)) {
      alert("A disciplina não possui mais aulas disponíveis.");
      limparCelula(td, diaSemana, numeroAula);
      return;
    }

    if (!h) {
      await inserirHorario(diaSemana, numeroAula, discId, profId);
    } else {
      await registrarHistorico(h);
      await atualizarHorario(h.id_horario, discId, profId);
    }
  }

  function limparCelula(td, diaSemana, numeroAula) {
    const selDisc = td.querySelector('.select-disciplina');
    const selProf = td.querySelector('.select-professor');
    if (!selDisc || !selProf) return;

    selDisc.value = '';
    selProf.value = '';
    refazerProfessores(selDisc, selProf, diaSemana, numeroAula, '');
    refazerDisciplinas(selDisc, selProf, diaSemana, numeroAula);
    aplicarCorCelula(td, '', '');
  }

  /*async function inserirHorario(diaSemana, numeroAula, discId, profId) {
    const body = new URLSearchParams({
      id_turma: idTurmaSelecionada,
      dia_semana: diaSemana,
      numero_aula: numeroAula,
      id_disciplina: discId,
      id_professor: profId
    });

    try {
      const resp = await fetchJson('/horarios/app/controllers/horarios/insertHorarios.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body
      });

      if (resp.status === 'success') {
        dadosTurma.horarios.push(resp.data);

        // atualiza contadores
        const did = String(discId);
        usedDisciplineCount[did] = (usedDisciplineCount[did] || 0) + 1;

        // adiciona no allHorariosDoAno (para conflito imediato)
        const turmaInfo = turmasMap[String(idTurmaSelecionada)] || {};
        allHorariosDoAno.push({
          ...resp.data,
          id_turma: idTurmaSelecionada,
          id_turno: turmaInfo.id_turno || idTurnoSelecionado,
          nome_serie: turmaInfo.nome_serie || '',
          nome_turma: turmaInfo.nome_turma || '',
          id_serie: turmaInfo.id_serie || null,
          id_ano_letivo: turmaInfo.id_ano_letivo || idAnoSelecionado
        });
      } else {
        alert(resp.message || 'Erro ao inserir horário');
      }
    } catch (err) {
      console.error(err);
      alert('Erro ao inserir horário');
    }
  }*/

async function inserirHorario(diaSemana, numeroAula, discId, profId) {
  if (!idTurmaSelecionada) return;
  if (!idTurnoSelecionado) {
    alert('Selecione o turno antes de inserir horário.');
    return;
  }

  const body = new URLSearchParams({
    id_turma: String(idTurmaSelecionada),
    id_turno: String(idTurnoSelecionado), // ✅ obrigatório no PHP novo
    dia_semana: String(diaSemana),
    numero_aula: String(numeroAula),
    id_disciplina: String(discId),
    id_professor: String(profId)
  });

  try {
    const resp = await fetchJson('/horarios/app/controllers/horarios/insertHorarios.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body
    });

    if (resp.status === 'success') {
      // garante array
      if (!Array.isArray(dadosTurma.horarios)) dadosTurma.horarios = [];
      dadosTurma.horarios.push(resp.data);

      // contadores
      const did = String(discId);
      usedDisciplineCount[did] = (usedDisciplineCount[did] || 0) + 1;

      // adiciona no allHorariosDoAno para conflito imediato
      const turmaInfo = turmasMap[String(idTurmaSelecionada)] || {};
      allHorariosDoAno.push({
        ...resp.data,
        id_turma: String(idTurmaSelecionada),
        id_turno: String(resp.data?.id_turno || turmaInfo.id_turno || idTurnoSelecionado),
        nome_serie: turmaInfo.nome_serie || '',
        nome_turma: turmaInfo.nome_turma || '',
        id_serie: turmaInfo.id_serie || null,
        id_ano_letivo: String(resp.data?.id_ano_letivo || turmaInfo.id_ano_letivo || idAnoSelecionado)
      });

    } else {
      alert(resp.message || 'Erro ao inserir horário');
    }
  } catch (err) {
    console.error('Erro inserirHorario:', err);
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
      const resp = await fetchJson('/horarios/app/controllers/horarios/updateHorarios.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body
      });

      if (resp.status === 'success') {
        const h = (dadosTurma.horarios || []).find(x => String(x.id_horario) === String(idHorario));
        if (h) {
          const oldDisc = String(h.id_disciplina || '');
          const newDisc = String(discId || '');

          if (oldDisc && oldDisc !== newDisc) {
            usedDisciplineCount[oldDisc] = Math.max(0, (usedDisciplineCount[oldDisc] || 0) - 1);
            usedDisciplineCount[newDisc] = (usedDisciplineCount[newDisc] || 0) + 1;
          }

          h.id_disciplina = discId;
          h.id_professor = profId;
        }

        const hh = allHorariosDoAno.find(x => String(x.id_horario) === String(idHorario));
        if (hh) {
          hh.id_disciplina = discId;
          hh.id_professor = profId;
        }
      } else {
        if (resp.message !== 'Nenhuma alteração ou registro não encontrado.') {
          alert(resp.message);
        }
      }
    } catch (err) {
      console.error(err);
      alert('Erro ao atualizar horário');
    }
  }

  function deletarHorario(diaSemana, numeroAula) {
    const horarioExistente = (dadosTurma.horarios || []).find(h =>
      h.dia_semana === diaSemana && parseInt(h.numero_aula, 10) === parseInt(numeroAula, 10)
    );

    if (horarioExistente) {
      registrarHistorico(horarioExistente).then(() => {
        deletaNoBanco(diaSemana, numeroAula, horarioExistente.id_horario, horarioExistente.id_disciplina);
      });
    } else {
      deletaNoBanco(diaSemana, numeroAula, null, null);
    }
  }

  /*async function deletaNoBanco(diaSemana, numeroAula, idHorario, discId) {
    const body = new URLSearchParams({
      id_turma: idTurmaSelecionada,
      dia_semana: diaSemana,
      numero_aula: numeroAula
    });

    try {
      const resp = await fetchJson('/horarios/app/controllers/horarios/deleteHorarios.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body
      });

      if (resp.status === 'success') {
        if (discId) {
          const did = String(discId);
          usedDisciplineCount[did] = Math.max(0, (usedDisciplineCount[did] || 0) - 1);
        }

        dadosTurma.horarios = (dadosTurma.horarios || []).filter(x =>
          !(x.dia_semana === diaSemana && parseInt(x.numero_aula, 10) === parseInt(numeroAula, 10))
        );

        if (idHorario) {
          allHorariosDoAno = allHorariosDoAno.filter(x => String(x.id_horario) !== String(idHorario));
        }
      } else {
        if (resp.message === 'Horário não encontrado.') {
          // sincroniza local mesmo assim
          dadosTurma.horarios = (dadosTurma.horarios || []).filter(x =>
            !(x.dia_semana === diaSemana && parseInt(x.numero_aula, 10) === parseInt(numeroAula, 10))
          );
          allHorariosDoAno = allHorariosDoAno.filter(x =>
            !(String(x.id_turma) === String(idTurmaSelecionada) &&
              x.dia_semana === diaSemana &&
              parseInt(x.numero_aula, 10) === parseInt(numeroAula, 10))
          );
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
  }*/

async function deletaNoBanco(diaSemana, numeroAula, idHorario, discId) {
  if (!idTurnoSelecionado) {
    alert('Selecione o turno antes de remover horário.');
    return;
  }

  const body = new URLSearchParams({
    id_turma: String(idTurmaSelecionada),
    id_turno: String(idTurnoSelecionado), // ✅ obrigatório no PHP novo
    dia_semana: String(diaSemana),
    numero_aula: String(numeroAula)
  });

  try {
    const resp = await fetchJson('/horarios/app/controllers/horarios/deleteHorarios.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body
    });

    if (resp.status === 'success') {
      if (discId) {
        const did = String(discId);
        usedDisciplineCount[did] = Math.max(0, (usedDisciplineCount[did] || 0) - 1);
      }

      dadosTurma.horarios = (dadosTurma.horarios || []).filter(x =>
        !(x.dia_semana === diaSemana && parseInt(x.numero_aula, 10) === parseInt(numeroAula, 10))
      );

      if (idHorario) {
        allHorariosDoAno = allHorariosDoAno.filter(x => String(x.id_horario) !== String(idHorario));
      } else {
        // fallback por slot
        allHorariosDoAno = allHorariosDoAno.filter(x =>
          !(String(x.id_turma) === String(idTurmaSelecionada) &&
            String(x.id_turno || '') === String(idTurnoSelecionado) &&
            x.dia_semana === diaSemana &&
            parseInt(x.numero_aula, 10) === parseInt(numeroAula, 10))
        );
      }

    } else {
      if (resp.message === 'Horário não encontrado.') {
        // sincroniza local mesmo assim
        dadosTurma.horarios = (dadosTurma.horarios || []).filter(x =>
          !(x.dia_semana === diaSemana && parseInt(x.numero_aula, 10) === parseInt(numeroAula, 10))
        );

        allHorariosDoAno = allHorariosDoAno.filter(x =>
          !(String(x.id_turma) === String(idTurmaSelecionada) &&
            String(x.id_turno || '') === String(idTurnoSelecionado) &&
            x.dia_semana === diaSemana &&
            parseInt(x.numero_aula, 10) === parseInt(numeroAula, 10))
        );
      } else {
        alert(resp.message);
      }
    }

    atualizarQuadroDisciplinas();
    refreshAllDisciplineOptionStates();

  } catch (err) {
    console.error('Erro deletaNoBanco:', err);
    alert('Erro ao deletar horário');
  }
}


  // =============================================
  // QUADRO DISCIPLINAS
  // =============================================
  function atualizarQuadroDisciplinas() {
    if (!quadroDisciplinas) return;
    quadroDisciplinas.innerHTML = '';
    if (!dadosTurma?.serie_disciplinas) return;

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
      const discId = String(d.id_disciplina);
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
      if (restantes <= 0) tdRest.style.color = 'red';
      tr.appendChild(tdRest);

      tbody.appendChild(tr);
    });

    table.appendChild(tbody);
    quadroDisciplinas.appendChild(table);
  }

  // =============================================
  // PRÉ-VALIDAÇÃO
  // =============================================
  function validarViabilidadeSerie() {
    const problemas = [];
    if (!dadosTurma?.serie_disciplinas) return problemas;

    const diasComAulas = (dadosTurma.turno_dias || []).filter(td => parseInt(td.aulas_no_dia, 10) > 0);
    const mapTurma = profDiscTurmaMap[String(idTurmaSelecionada)] || {};

    dadosTurma.serie_disciplinas.forEach(d => {
      const discId = String(d.id_disciplina);
      const limite = disciplineWeeklyLimit[discId] || 0;

      if (limite <= 0) {
        problemas.push(`Disciplina "${d.nome_disciplina}" ficou com 0 aulas.`);
        return;
      }

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
  // IMPRIMIR
  // =============================================
  if (btnImprimir) {
    btnImprimir.addEventListener('click', () => {
      if (!idTurmaSelecionada) {
        alert('Selecione uma turma para imprimir o horário.');
        return;
      }
      const url = `/horarios/app/views/horarios-turma.php?id_turma=${encodeURIComponent(idTurmaSelecionada)}&orient=Landscape`;
      window.open(url, '_blank');
    });
  }


  
  // =============================================
  // START
  // =============================================
  showNoData();
  loadAnosLetivos();
});
