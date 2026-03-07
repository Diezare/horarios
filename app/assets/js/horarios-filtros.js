// app/assets/js/horarios-filtros.js
(function () {
  window.HorariosApp = window.HorariosApp || {};
  const App = window.HorariosApp;

  App.state = App.state || {
    idAnoSelecionado: null,
    idNivelEnsinoSelecionado: null,
    idTurnoSelecionado: null,
    idTurmaSelecionada: null,
    editingEnabled: false,

    dadosTurma: null,
    turmasMap: {},
    turmaTurnoLookup: {},
    turmaInfoLookup: {},
    profDiscTurmaMap: {},
    professorRestricoesMap: {},
    allHorariosDoAno: [],
    intervalPositions: [],
    extraClassMapping: {},
    usedDisciplineCount: {},
    disciplineWeeklyLimit: {}
  };

  App.dom = App.dom || {};
  App.filters = App.filters || {};

  App.filters.cacheDom = function () {
    App.dom.selectAnoLetivo = document.getElementById('selectAnoLetivo');
    App.dom.selectNivelEnsino = document.getElementById('selectNivelEnsino');
    App.dom.selectTurno = document.getElementById('selectTurno');
    App.dom.selectTurma = document.getElementById('selectTurma');
    App.dom.btnImprimir = document.getElementById('btnImprimir');
    App.dom.btnAutomatico = document.getElementById('btn-automatic');
    App.dom.gradeContainer = document.getElementById('grade-container');
    App.dom.quadroDisciplinas = document.getElementById('quadro-disciplinas');
    App.dom.contentDataHorarios = document.getElementById('content-data-horarios');
    App.dom.noDataMessage = document.getElementById('no-data-message');
    App.dom.modalAutomatico = document.getElementById('modal-automatico');
    App.dom.modalExtra = document.getElementById('modal-extra');
  };

  App.filters.showNoData = function (msg = 'Nenhuma informação encontrada.') {
    if (App.dom.contentDataHorarios) App.dom.contentDataHorarios.style.display = 'block';
    if (App.dom.gradeContainer) App.dom.gradeContainer.innerHTML = '';
    if (App.dom.quadroDisciplinas) App.dom.quadroDisciplinas.innerHTML = '';
    if (App.dom.noDataMessage) {
      App.dom.noDataMessage.textContent = msg;
      App.dom.noDataMessage.style.display = 'block';
    }
  };

  App.filters.hideNoData = function () {
    if (App.dom.noDataMessage) App.dom.noDataMessage.style.display = 'none';
  };

  App.filters.updateBtnAutomaticoState = function () {
    const s = App.state;
    const ok = !!(s.idAnoSelecionado && s.idNivelEnsinoSelecionado && s.idTurnoSelecionado);
    if (App.dom.btnAutomatico) App.dom.btnAutomatico.disabled = !ok;
  };

  App.filters.resetNivelEnsinoSelection = function () {
    if (!App.dom.selectNivelEnsino) return;
    App.dom.selectNivelEnsino.innerHTML = '<option value="">-- Selecione o Nível --</option>';
    App.dom.selectNivelEnsino.disabled = true;
    App.state.idNivelEnsinoSelecionado = null;
    App.filters.updateBtnAutomaticoState();
  };

  App.filters.resetTurnoSelection = function () {
    if (!App.dom.selectTurno) return;
    App.dom.selectTurno.innerHTML = '<option value="">-- Selecione o Turno --</option>';
    App.dom.selectTurno.disabled = true;
    App.state.idTurnoSelecionado = null;
    App.filters.updateBtnAutomaticoState();
  };

  App.filters.resetTurmaSelection = function () {
    if (!App.dom.selectTurma) return;

    App.dom.selectTurma.innerHTML = '<option value="">-- Selecione a Turma --</option>';
    App.dom.selectTurma.disabled = true;

    App.state.idTurmaSelecionada = null;
    App.state.editingEnabled = false;
    App.state.dadosTurma = null;
    App.state.allHorariosDoAno = [];
    App.state.professorRestricoesMap = {};
    App.state.profDiscTurmaMap = {};
    App.state.intervalPositions = [];
    App.state.extraClassMapping = {};
    App.state.usedDisciplineCount = {};
    App.state.disciplineWeeklyLimit = {};
    App.state.turmaInfoLookup = {};

    if (App.dom.gradeContainer) App.dom.gradeContainer.innerHTML = '';
    if (App.dom.quadroDisciplinas) App.dom.quadroDisciplinas.innerHTML = '';

    App.filters.showNoData();
  };

  App.filters.loadAnosLetivos = async function () {
    if (!App.dom.selectAnoLetivo) return;

    try {
      const resp = await App.utils.fetchJson('/horarios/app/controllers/ano-letivo/listAnoLetivo.php');
      if (resp.status === 'success' && Array.isArray(resp.data)) {
        resp.data.forEach(ano => {
          const opt = document.createElement('option');
          opt.value = ano.id_ano_letivo;
          opt.textContent = ano.ano;
          App.dom.selectAnoLetivo.appendChild(opt);
        });
      }
    } catch (err) {
      console.error(err);
    }
  };

  App.filters.loadNiveisPorAno = async function (idAno) {
    if (!App.dom.selectNivelEnsino) return;

    try {
      const resp = await App.utils.fetchJson(
        `/horarios/app/controllers/nivel-ensino/listNivelEnsinoByUserAndAno.php?id_ano_letivo=${encodeURIComponent(idAno)}`
      );

      App.dom.selectNivelEnsino.innerHTML = '<option value="">-- Selecione o Nível --</option>';

      if (resp.status === 'success' && Array.isArray(resp.data) && resp.data.length > 0) {
        App.dom.selectNivelEnsino.disabled = false;

        resp.data.forEach(niv => {
          const opt = document.createElement('option');
          opt.value = String(niv.id_nivel_ensino);
          opt.textContent = niv.nome_nivel_ensino;
          App.dom.selectNivelEnsino.appendChild(opt);
        });

        if (resp.data.length === 1) {
          App.dom.selectNivelEnsino.value = String(resp.data[0].id_nivel_ensino);
          App.state.idNivelEnsinoSelecionado = App.dom.selectNivelEnsino.value;

          App.filters.resetTurnoSelection();
          App.filters.resetTurmaSelection();
          App.filters.updateBtnAutomaticoState();

          await App.filters.loadTurnosPorAnoENivel(
            App.state.idAnoSelecionado,
            App.state.idNivelEnsinoSelecionado
          );
        } else {
          App.filters.resetTurnoSelection();
          App.filters.resetTurmaSelection();
          App.filters.updateBtnAutomaticoState();
        }
      } else {
        App.dom.selectNivelEnsino.innerHTML = '<option value="">Nenhum nível encontrado</option>';
        App.dom.selectNivelEnsino.disabled = true;
        App.filters.resetTurnoSelection();
        App.filters.resetTurmaSelection();
        App.filters.updateBtnAutomaticoState();
      }
    } catch (err) {
      console.error('Erro loadNiveisPorAno:', err);
      App.dom.selectNivelEnsino.innerHTML = '<option value="">Erro ao carregar níveis</option>';
      App.dom.selectNivelEnsino.disabled = true;
      App.filters.resetTurnoSelection();
      App.filters.resetTurmaSelection();
      App.filters.updateBtnAutomaticoState();
    }
  };

  App.filters.loadTurnosPorAnoENivel = async function (idAno, idNivel) {
    if (!App.dom.selectTurno) return;

    App.dom.selectTurno.disabled = false;
    App.dom.selectTurno.innerHTML = '<option value="">Carregando turnos...</option>';
    App.state.idTurnoSelecionado = null;
    App.filters.updateBtnAutomaticoState();

    try {
      const url =
        `/horarios/app/controllers/turno/listTurnoByUserAnoNivel.php` +
        `?id_ano_letivo=${encodeURIComponent(idAno)}` +
        `&id_nivel_ensino=${encodeURIComponent(idNivel)}`;

      const resp = await App.utils.fetchJson(url);

      const ok = String(resp?.status || '').toLowerCase() === 'success';
      const data = Array.isArray(resp?.data) ? resp.data : [];

      App.dom.selectTurno.innerHTML = '<option value="">-- Selecione o Turno --</option>';

      if (ok && data.length > 0) {
        data.forEach(t => {
          const opt = document.createElement('option');
          opt.value = String(t.id_turno);
          opt.textContent = t.nome_turno ?? `Turno ${t.id_turno}`;
          App.dom.selectTurno.appendChild(opt);
        });

        if (data.length === 1) {
          App.dom.selectTurno.value = String(data[0].id_turno);
          App.state.idTurnoSelecionado = App.dom.selectTurno.value;
          App.filters.updateBtnAutomaticoState();
          App.filters.resetTurmaSelection();

          await App.filters.loadTurmasPorAnoNivelTurno(
            App.state.idAnoSelecionado,
            App.state.idNivelEnsinoSelecionado,
            App.state.idTurnoSelecionado
          );
        } else {
          App.state.idTurnoSelecionado = null;
          App.filters.updateBtnAutomaticoState();
          App.filters.resetTurmaSelection();
        }
      } else {
        App.dom.selectTurno.innerHTML = '<option value="">Nenhum turno encontrado</option>';
        App.dom.selectTurno.disabled = true;
        App.state.idTurnoSelecionado = null;
        App.filters.resetTurmaSelection();
        App.filters.updateBtnAutomaticoState();
      }
    } catch (err) {
      console.error('Erro loadTurnosPorAnoENivel:', err);
      App.dom.selectTurno.innerHTML = '<option value="">Erro ao carregar turnos</option>';
      App.dom.selectTurno.disabled = true;
      App.state.idTurnoSelecionado = null;
      App.filters.resetTurmaSelection();
      App.filters.updateBtnAutomaticoState();
    }
  };

  App.filters.loadTurmasPorAnoNivelTurno = async function (idAno, idNivel, idTurno) {
    if (!App.dom.selectTurma) return;

    App.dom.selectTurma.innerHTML = '<option value="">-- Selecione a Turma --</option>';
    App.dom.selectTurma.disabled = true;
    App.state.turmasMap = {};
    App.state.turmaTurnoLookup = {};
    App.state.turmaInfoLookup = {};

    if (!idAno || !idNivel || !idTurno) return;

    try {
      const url =
        `/horarios/app/controllers/turma/listTurmaByUserAndAno.php` +
        `?id_ano_letivo=${encodeURIComponent(idAno)}` +
        `&id_nivel_ensino=${encodeURIComponent(idNivel)}` +
        `&id_turno=${encodeURIComponent(idTurno)}`;

      const resp = await App.utils.fetchJson(url);

      if (resp.status === 'success' && Array.isArray(resp.data) && resp.data.length > 0) {
        App.dom.selectTurma.disabled = false;

        resp.data.forEach(t => {
          const idTurma = String(t.id_turma);
          const idSerie = t.id_serie ?? null;
          const idTurnoLocal = t.id_turno ?? idTurno;

          const opt = document.createElement('option');
          opt.value = idTurma;
          opt.textContent = `${t.nome_serie} ${t.nome_turma}`;
          App.dom.selectTurma.appendChild(opt);

          App.state.turmasMap[idTurma] = {
            id_turma: idTurma,
            id_serie: idSerie,
            id_ano_letivo: t.id_ano_letivo ?? idAno,
            id_turno: idTurnoLocal,
            nome_serie: t.nome_serie ?? '',
            nome_turma: t.nome_turma ?? '',
            nome_turno: t.nome_turno ?? ''
          };

          App.state.turmaTurnoLookup[idTurma] = String(idTurnoLocal);
          App.state.turmaInfoLookup[idTurma] = {
            id_turno: String(idTurnoLocal),
            id_serie: String(idSerie || ''),
            nome_turma: String(t.nome_turma || ''),
            id_ano_letivo: String(t.id_ano_letivo || idAno)
          };
        });
      }
    } catch (err) {
      console.error('Erro loadTurmasPorAnoNivelTurno:', err);
    }
  };

  App.filters.bindEvents = function () {
    const s = App.state;
    const d = App.dom;

    if (d.selectAnoLetivo) {
      d.selectAnoLetivo.addEventListener('change', async () => {
        s.idAnoSelecionado = d.selectAnoLetivo.value || null;
        App.filters.resetNivelEnsinoSelection();
        App.filters.resetTurnoSelection();
        App.filters.resetTurmaSelection();

        if (s.idAnoSelecionado) {
          await App.filters.loadNiveisPorAno(s.idAnoSelecionado);
        }

        App.filters.updateBtnAutomaticoState();
      });
    }

    if (d.selectNivelEnsino) {
      d.selectNivelEnsino.addEventListener('change', async () => {
        s.idNivelEnsinoSelecionado = d.selectNivelEnsino.value || null;
        App.filters.resetTurnoSelection();
        App.filters.resetTurmaSelection();
        App.filters.updateBtnAutomaticoState();

        if (s.idAnoSelecionado && s.idNivelEnsinoSelecionado) {
          await App.filters.loadTurnosPorAnoENivel(s.idAnoSelecionado, s.idNivelEnsinoSelecionado);
        }
      });
    }

    if (d.selectTurno) {
      d.selectTurno.addEventListener('change', async () => {
        s.idTurnoSelecionado = d.selectTurno.value || null;
        App.filters.resetTurmaSelection();
        App.filters.updateBtnAutomaticoState();

        if (s.idAnoSelecionado && s.idNivelEnsinoSelecionado && s.idTurnoSelecionado) {
          await App.filters.loadTurmasPorAnoNivelTurno(
            s.idAnoSelecionado,
            s.idNivelEnsinoSelecionado,
            s.idTurnoSelecionado
          );
        }
      });
    }

    if (d.selectTurma) {
      d.selectTurma.addEventListener('change', async () => {
        const turmaVal = d.selectTurma.value;
        if (!turmaVal) {
          App.filters.resetTurmaSelection();
          return;
        }

        s.idTurmaSelecionada = turmaVal;
        s.editingEnabled = true;

        try {
          await App.grade.carregarTudo();
          await App.grade.definirExtraAulas();
          App.grade.montarGrade();
          App.grade.atualizarQuadroDisciplinas();
        } catch (err) {
          console.error(err);
        }
      });
    }

    if (d.btnImprimir) {
      d.btnImprimir.addEventListener('click', () => {
        if (!s.idTurmaSelecionada) {
          alert('Selecione uma turma para imprimir o horário.');
          return;
        }

        const url =
          `/horarios/app/views/horarios-turma.php` +
          `?id_turma=${encodeURIComponent(s.idTurmaSelecionada)}` +
          `&id_ano_letivo=${encodeURIComponent(s.idAnoSelecionado)}` +
          `&id_turno=${encodeURIComponent(s.idTurnoSelecionado)}` +
          `&orient=Landscape`;

        window.open(url, '_blank');
      });
    }
  };

  App.filters.init = async function () {
    App.filters.cacheDom();
    App.filters.showNoData();
    await App.filters.loadAnosLetivos();
    App.filters.bindEvents();
  };
})();