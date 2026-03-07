// app/assets/js/horarios-grade.js
(function () {
  window.HorariosApp = window.HorariosApp || {};
  const App = window.HorariosApp;

  App.grade = App.grade || {};

  App.grade.traduzDia = function (dia) {
    switch (dia) {
      case 'Domingo': return 'Domingo';
      case 'Segunda': return 'Segunda';
      case 'Terca': return 'Terça';
      case 'Quarta': return 'Quarta';
      case 'Quinta': return 'Quinta';
      case 'Sexta': return 'Sexta';
      case 'Sabado': return 'Sábado';
      default: return dia;
    }
  };

  App.grade.loadTurmaTurnoLookup = async function (idAno, idNivel) {
    const s = App.state;
    s.turmaTurnoLookup = {};
    s.turmaInfoLookup = {};

    if (!idAno || !idNivel) return;

    try {
      const resp = await App.utils.fetchJson(
        `/horarios/app/controllers/turma/listTurmaByUserAndAno.php` +
        `?id_ano_letivo=${encodeURIComponent(idAno)}` +
        `&id_nivel_ensino=${encodeURIComponent(idNivel)}`
      );

      if (resp.status !== 'success') return;

      (resp.data || []).forEach(t => {
        const idTurma = String(t.id_turma);
        const idTurno = String(t.id_turno || '');
        const idSerie = String(t.id_serie || '');
        const nomeTurma = String(t.nome_turma || '');
        const anoLetivo = String(t.id_ano_letivo || idAno);

        s.turmaTurnoLookup[idTurma] = idTurno;
        s.turmaInfoLookup[idTurma] = {
          id_turno: idTurno,
          id_serie: idSerie,
          nome_turma: nomeTurma,
          id_ano_letivo: anoLetivo
        };
      });
    } catch (e) {
      console.warn('Falha loadTurmaTurnoLookup:', e);
    }
  };

  App.grade.loadAllHorariosDoAno = async function (idAno) {
    const s = App.state;

    try {
      const resp = await App.utils.fetchJson(
        `/horarios/app/controllers/horarios/listHorariosByAnoLetivo.php?id_ano_letivo=${encodeURIComponent(idAno)}`
      );

      if (resp.status === 'success' && Array.isArray(resp.data)) {
        s.allHorariosDoAno = resp.data.map(h => {
          const idTurma = String(h.id_turma);
          const turnoDaTurma = s.turmaTurnoLookup[idTurma] || null;
          return { ...h, id_turno: h.id_turno ?? turnoDaTurma };
        });
      } else {
        s.allHorariosDoAno = [];
      }
    } catch (err) {
      console.error('Erro loadAllHorariosDoAno:', err);
      s.allHorariosDoAno = [];
    }
  };

  App.grade.loadProfessorRestricoes = async function (idAno) {
    const s = App.state;
    s.professorRestricoesMap = {};

    if (!idAno || !s.idTurnoSelecionado) return;

    try {
      const resp = await App.utils.fetchJson(
        `/horarios/app/controllers/professor-restricoes/listProfessorRestricoes.php` +
        `?id_ano_letivo=${encodeURIComponent(idAno)}` +
        `&id_turno=${encodeURIComponent(s.idTurnoSelecionado)}`
      );

      if (resp.status !== 'success') return;

      (resp.data || []).forEach(row => {
        const p = String(row.id_professor);
        const turno = String(row.id_turno);
        const dia = row.dia_semana;
        const aula = parseInt(row.numero_aula, 10);

        if (!s.professorRestricoesMap[p]) s.professorRestricoesMap[p] = {};
        if (!s.professorRestricoesMap[p][turno]) s.professorRestricoesMap[p][turno] = {};
        if (!s.professorRestricoesMap[p][turno][dia]) s.professorRestricoesMap[p][turno][dia] = [];
        s.professorRestricoesMap[p][turno][dia].push(aula);
      });
    } catch (err) {
      console.error('Erro loadProfessorRestricoes:', err);
      s.professorRestricoesMap = {};
    }
  };

  App.grade.loadProfessorDisciplinaTurma = async function () {
    const s = App.state;

    try {
      const resp = await App.utils.fetchJson('/horarios/app/controllers/professor-disciplina-turma/listProfessorDisciplinaTurma.php?all=1');
      if (resp.status === 'success' && Array.isArray(resp.data)) {
        s.profDiscTurmaMap = {};
        resp.data.forEach(row => {
          const t = String(row.id_turma);
          const d = String(row.id_disciplina);
          const p = parseInt(row.id_professor, 10);

          if (!s.profDiscTurmaMap[t]) s.profDiscTurmaMap[t] = {};
          if (!s.profDiscTurmaMap[t][d]) s.profDiscTurmaMap[t][d] = [];
          s.profDiscTurmaMap[t][d].push(p);
        });
      }
    } catch (err) {
      console.error(err);
    }
  };

  App.grade.loadHorariosTurma = async function (idTurma) {
    const s = App.state;

    try {
      const turno = String(s.idTurnoSelecionado || '');
      const ano = String(s.idAnoSelecionado || '');

      const url =
        `/horarios/app/controllers/horarios/listHorarios.php` +
        `?id_turma=${encodeURIComponent(idTurma)}` +
        (ano ? `&id_ano_letivo=${encodeURIComponent(ano)}` : '') +
        (turno ? `&id_turno=${encodeURIComponent(turno)}` : '');

      const resp = await App.utils.fetchJson(url);

      if (resp.status === 'success') {
        s.dadosTurma = resp.data;

        const intervalStr = s.dadosTurma?.turma?.intervalos_positions || '';
        s.intervalPositions = intervalStr
          .split(',')
          .map(n => parseInt(String(n).trim(), 10))
          .filter(x => !isNaN(x) && x > 0);
      } else {
        s.dadosTurma = null;
      }
    } catch (err) {
      console.error('Erro loadHorariosTurma:', err);
      s.dadosTurma = null;
    }
  };

  App.grade.inicializarLimitesDisciplinas = function () {
    const s = App.state;
    s.disciplineWeeklyLimit = {};
    if (!s.dadosTurma || !Array.isArray(s.dadosTurma.serie_disciplinas)) return;

    s.dadosTurma.serie_disciplinas.forEach(d => {
      s.disciplineWeeklyLimit[String(d.id_disciplina)] = parseInt(d.aulas_semana, 10) || 0;
    });
  };

  App.grade.recalcularUsosDasDisciplinas = function () {
    const s = App.state;
    s.usedDisciplineCount = {};

    const anoId = String(s.idAnoSelecionado || '');
    const turmaIdAtual = String(s.idTurmaSelecionada || '');
    if (!anoId || !turmaIdAtual) return;
    if (!Array.isArray(s.allHorariosDoAno)) return;

    const infoAtual = s.turmaInfoLookup[turmaIdAtual] || s.turmasMap[turmaIdAtual] || null;

    const chaveAtual = infoAtual
      ? `${String(infoAtual.id_ano_letivo || anoId)}|${String(infoAtual.id_serie || '')}|${String(infoAtual.nome_turma || '')}`
      : null;

    s.allHorariosDoAno.forEach(h => {
      const hAno = String(h.id_ano_letivo || anoId || '');
      if (hAno !== anoId) return;

      const hTurmaId = String(h.id_turma || '');
      const infoH = s.turmaInfoLookup[hTurmaId] || s.turmasMap[hTurmaId] || null;

      const chaveH = infoH
        ? `${String(infoH.id_ano_letivo || hAno)}|${String(infoH.id_serie || '')}|${String(infoH.nome_turma || '')}`
        : null;

      const mesmaTurmaLogica = chaveAtual && chaveH
        ? (chaveH === chaveAtual)
        : (hTurmaId === turmaIdAtual);

      if (!mesmaTurmaLogica) return;

      const did = String(h.id_disciplina || '');
      if (!did) return;

      s.usedDisciplineCount[did] = (s.usedDisciplineCount[did] || 0) + 1;
    });
  };

  App.grade.checarSaldoDisciplina = function (discId, diaSemana = null, numeroAula = null) {
    const s = App.state;
    if (!discId) return true;

    const did = String(discId);
    const limite = s.disciplineWeeklyLimit[did] || 0;
    let usado = s.usedDisciplineCount[did] || 0;

    if (diaSemana && numeroAula && s.dadosTurma?.horarios) {
      const h = s.dadosTurma.horarios.find(x =>
        x.dia_semana === diaSemana && parseInt(x.numero_aula, 10) === parseInt(numeroAula, 10)
      );
      if (h && String(h.id_disciplina) === did) {
        usado = Math.max(0, usado - 1);
      }
    }

    return (limite - usado) > 0;
  };

  App.grade.definirExtraAulas = async function () {
    const s = App.state;
    s.extraClassMapping = {};
    if (!s.dadosTurma?.turma) return;

    const totalSeriesAulas = parseInt(s.dadosTurma.turma.total_aulas_semana, 10);
    if (!totalSeriesAulas) return;

    const diasComAulas = (s.dadosTurma.turno_dias || []).filter(td => parseInt(td.aulas_no_dia, 10) > 0);
    const numDias = diasComAulas.length;
    if (numDias === 0) return;

    const baseSlots = Math.floor(totalSeriesAulas / numDias);
    const remainder = totalSeriesAulas % numDias;

    diasComAulas.forEach(td => {
      s.extraClassMapping[td.dia_semana] = baseSlots;
    });

    if (remainder === 1) {
      await App.grade.openExtraClassModal(diasComAulas, (diaEscolhido) => {
        s.extraClassMapping[diaEscolhido] = baseSlots + 1;
      });
    }
  };

  App.grade.openExtraClassModal = function (dias, callback) {
    return new Promise((resolve) => {
      const modalExtra = App.dom.modalExtra;
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

      App.modal.openWithEffect(modalExtra);

      const btnConf = modalExtra.querySelector('#btnConfirmarExtra');
      const btnCanc = modalExtra.querySelector('#btnCancelarExtra');
      const btnClose = modalExtra.querySelector('.close-modal-extra');

      const close = () => App.modal.closeWithEffect(modalExtra);

      if (btnConf) btnConf.onclick = () => {
        const selected = modalExtra.querySelector('input[name="extraDia"]:checked');
        if (selected) callback(selected.value);
        close();
        resolve();
      };

      if (btnCanc) btnCanc.onclick = () => { close(); resolve(); };
      if (btnClose) btnClose.onclick = () => { close(); resolve(); };
    });
  };

  App.grade.professorEhRestrito = function (profId, diaSemana, numeroAula) {
    const s = App.state;
    const p = String(profId);
    const turno = String(s.idTurnoSelecionado || '');
    if (!turno) return false;

    const byProf = s.professorRestricoesMap[p];
    if (!byProf) return false;

    const byTurno = byProf[turno];
    if (!byTurno) return false;

    const aulasRestritas = byTurno[diaSemana] || [];
    return aulasRestritas.includes(parseInt(numeroAula, 10));
  };

  App.grade.professorOcupado = function (profId, diaSemana, numeroAula, ignoreHorarioId = null) {
    const s = App.state;
    const turnoAtual = String(s.idTurnoSelecionado || '');
    if (!turnoAtual) return null;

    const conflict = s.allHorariosDoAno.find(h => {
      const mesmoProf = String(h.id_professor) === String(profId);
      const mesmoDia = h.dia_semana === diaSemana;
      const mesmaAula = parseInt(h.numero_aula, 10) === parseInt(numeroAula, 10);
      const outroHorario = ignoreHorarioId ? String(h.id_horario) !== String(ignoreHorarioId) : true;
      const mesmoTurno = String(h.id_turno || '') === turnoAtual;
      return mesmoProf && mesmoDia && mesmaAula && outroHorario && mesmoTurno;
    });

    return conflict ? { nome_serie: conflict.nome_serie, nome_turma: conflict.nome_turma } : null;
  };

  App.grade.aplicarCorCelula = function (td, discId, profId) {
    td.style.backgroundColor = (discId && profId) ? '#D5F4DA' : '';
  };

  App.grade.refazerProfessores = function (selDisc, selProf, diaSemana, numeroAula, discIdAtual = '') {
    const s = App.state;
    const currentProf = selProf.value;
    selProf.innerHTML = '';
    selProf.appendChild(new Option('--Prof--', ''));

    if (!discIdAtual) {
      selProf.value = '';
      return;
    }

    const mapTurma = s.profDiscTurmaMap[String(s.idTurmaSelecionada)] || {};
    const profsDaDisciplina = (mapTurma[String(discIdAtual)] || []).map(Number);

    const h = (s.dadosTurma.horarios || []).find(x =>
      x.dia_semana === diaSemana && parseInt(x.numero_aula, 10) === parseInt(numeroAula, 10)
    );

    (s.dadosTurma.professores || []).forEach(prof => {
      const pid = parseInt(prof.id_professor, 10);
      const displayName = prof.nome_exibicao || ('Prof ' + pid);

      if (!profsDaDisciplina.includes(pid)) return;

      if (App.grade.professorEhRestrito(pid, diaSemana, numeroAula)) {
        const opt = new Option(`❌ ${displayName} (restrito)`, String(pid));
        opt.disabled = true;
        selProf.appendChild(opt);
        return;
      }

      const conflito = App.grade.professorOcupado(pid, diaSemana, numeroAula, h ? h.id_horario : null);
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
  };

  App.grade.refazerDisciplinas = function (selDisc, selProf, diaSemana, numeroAula) {
    const s = App.state;
    const profId = parseInt(selProf.value, 10) || 0;
    const discSelecionada = selDisc.value;

    selDisc.innerHTML = '';
    selDisc.appendChild(new Option('--Disc--', ''));

    const turmaMap = s.profDiscTurmaMap[String(s.idTurmaSelecionada)] || {};

    let allowed = null;
    if (profId) {
      allowed = Object.keys(turmaMap).filter(did => (turmaMap[did] || []).includes(profId));
      allowed = new Set(allowed.map(String));
    }

    (s.dadosTurma.serie_disciplinas || []).forEach(d => {
      const did = String(d.id_disciplina);

      if (allowed && !allowed.has(did)) return;

      const opt = new Option(d.nome_disciplina, did);
      if (!App.grade.checarSaldoDisciplina(did, diaSemana, numeroAula)) {
        opt.text = `❌ ${d.nome_disciplina} (0 disponíveis)`;
        opt.disabled = true;
      }
      selDisc.appendChild(opt);
    });

    const existeNaLista = Array.from(selDisc.options).some(o => String(o.value) === String(discSelecionada));
    selDisc.value = existeNaLista ? discSelecionada : '';
  };

  App.grade.refreshAllDisciplineOptionStates = function () {
    document.querySelectorAll('.tabela-horarios td').forEach(td => {
      const selDisc = td.querySelector('select.select-disciplina');
      const selProf = td.querySelector('select.select-professor');
      if (!selDisc || !selProf) return;

      const currentDisc = selDisc.value;
      App.grade.refazerDisciplinas(selDisc, selProf, null, null);

      if ([...selDisc.options].some(o => String(o.value) === String(currentDisc))) {
        selDisc.value = currentDisc;
      }
    });
  };

  App.grade.limparCelula = function (td, diaSemana, numeroAula) {
    const selDisc = td.querySelector('.select-disciplina');
    const selProf = td.querySelector('.select-professor');
    if (!selDisc || !selProf) return;

    selDisc.value = '';
    selProf.value = '';
    App.grade.refazerProfessores(selDisc, selProf, diaSemana, numeroAula, '');
    App.grade.refazerDisciplinas(selDisc, selProf, diaSemana, numeroAula);
    App.grade.aplicarCorCelula(td, '', '');
  };

  App.grade.registrarHistorico = async function (horarioObj) {
    const s = App.state;
    if (!horarioObj?.id_horario) return;

    const body = new URLSearchParams({
      id_horario_original: String(horarioObj.id_horario),
      id_turma: String(horarioObj.id_turma || s.idTurmaSelecionada || ''),
      id_turno: String(horarioObj.id_turno || s.idTurnoSelecionado || ''),
      id_ano_letivo: String(horarioObj.id_ano_letivo || s.idAnoSelecionado || ''),
      dia_semana: String(horarioObj.dia_semana || ''),
      numero_aula: String(horarioObj.numero_aula || ''),
      id_disciplina: String(horarioObj.id_disciplina || ''),
      id_professor: String(horarioObj.id_professor || ''),
      data_criacao: String(horarioObj.data_criacao || '')
    });

    try {
      const resp = await App.utils.fetchJson('/horarios/app/controllers/horarios/archiveHorario.php', {
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
  };

  App.grade.inserirHorario = async function (diaSemana, numeroAula, discId, profId) {
    const s = App.state;
    if (!s.idTurmaSelecionada) return;
    if (!s.idTurnoSelecionado) {
      alert('Selecione o turno antes de inserir horário.');
      return;
    }

    const body = new URLSearchParams({
      id_turma: String(s.idTurmaSelecionada),
      id_turno: String(s.idTurnoSelecionado),
      dia_semana: String(diaSemana),
      numero_aula: String(numeroAula),
      id_disciplina: String(discId),
      id_professor: String(profId)
    });

    try {
      const resp = await App.utils.fetchJson('/horarios/app/controllers/horarios/insertHorarios.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body
      });

      if (resp.status === 'success') {
        if (!Array.isArray(s.dadosTurma.horarios)) s.dadosTurma.horarios = [];
        s.dadosTurma.horarios.push(resp.data);

        const did = String(discId);
        s.usedDisciplineCount[did] = (s.usedDisciplineCount[did] || 0) + 1;

        const turmaInfo = s.turmasMap[String(s.idTurmaSelecionada)] || {};
        s.allHorariosDoAno.push({
          ...resp.data,
          id_turma: String(s.idTurmaSelecionada),
          id_turno: String(resp.data?.id_turno || turmaInfo.id_turno || s.idTurnoSelecionado),
          nome_serie: turmaInfo.nome_serie || '',
          nome_turma: turmaInfo.nome_turma || '',
          id_serie: turmaInfo.id_serie || null,
          id_ano_letivo: String(resp.data?.id_ano_letivo || turmaInfo.id_ano_letivo || s.idAnoSelecionado)
        });
      } else {
        alert(resp.message || 'Erro ao inserir horário');
      }
    } catch (err) {
      console.error('Erro inserirHorario:', err);
      alert('Erro ao inserir horário');
    }
  };

  App.grade.atualizarHorario = async function (idHorario, discId, profId) {
    const s = App.state;
    const body = new URLSearchParams({
      id_horario: idHorario,
      id_disciplina: discId,
      id_professor: profId
    });

    try {
      const resp = await App.utils.fetchJson('/horarios/app/controllers/horarios/updateHorarios.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body
      });

      if (resp.status === 'success') {
        const h = (s.dadosTurma.horarios || []).find(x => String(x.id_horario) === String(idHorario));
        if (h) {
          const oldDisc = String(h.id_disciplina || '');
          const newDisc = String(discId || '');

          if (oldDisc && oldDisc !== newDisc) {
            s.usedDisciplineCount[oldDisc] = Math.max(0, (s.usedDisciplineCount[oldDisc] || 0) - 1);
            s.usedDisciplineCount[newDisc] = (s.usedDisciplineCount[newDisc] || 0) + 1;
          }

          h.id_disciplina = discId;
          h.id_professor = profId;
        }

        const hh = s.allHorariosDoAno.find(x => String(x.id_horario) === String(idHorario));
        if (hh) {
          hh.id_disciplina = discId;
          hh.id_professor = profId;
        }
      } else if (resp.message !== 'Nenhuma alteração ou registro não encontrado.') {
        alert(resp.message);
      }
    } catch (err) {
      console.error(err);
      alert('Erro ao atualizar horário');
    }
  };

  App.grade.deletaNoBanco = async function (diaSemana, numeroAula, idHorario, discId) {
    const s = App.state;

    if (!s.idTurnoSelecionado) {
      alert('Selecione o turno antes de remover horário.');
      return;
    }

    const body = new URLSearchParams({
      id_turma: String(s.idTurmaSelecionada),
      id_turno: String(s.idTurnoSelecionado),
      dia_semana: String(diaSemana),
      numero_aula: String(numeroAula)
    });

    try {
      const resp = await App.utils.fetchJson('/horarios/app/controllers/horarios/deleteHorarios.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body
      });

      if (resp.status === 'success') {
        if (discId) {
          const did = String(discId);
          s.usedDisciplineCount[did] = Math.max(0, (s.usedDisciplineCount[did] || 0) - 1);
        }

        s.dadosTurma.horarios = (s.dadosTurma.horarios || []).filter(x =>
          !(x.dia_semana === diaSemana && parseInt(x.numero_aula, 10) === parseInt(numeroAula, 10))
        );

        if (idHorario) {
          s.allHorariosDoAno = s.allHorariosDoAno.filter(x => String(x.id_horario) !== String(idHorario));
        } else {
          s.allHorariosDoAno = s.allHorariosDoAno.filter(x =>
            !(String(x.id_turma) === String(s.idTurmaSelecionada) &&
              String(x.id_turno || '') === String(s.idTurnoSelecionado) &&
              x.dia_semana === diaSemana &&
              parseInt(x.numero_aula, 10) === parseInt(numeroAula, 10))
          );
        }
      } else if (resp.message === 'Horário não encontrado.') {
        s.dadosTurma.horarios = (s.dadosTurma.horarios || []).filter(x =>
          !(x.dia_semana === diaSemana && parseInt(x.numero_aula, 10) === parseInt(numeroAula, 10))
        );

        s.allHorariosDoAno = s.allHorariosDoAno.filter(x =>
          !(String(x.id_turma) === String(s.idTurmaSelecionada) &&
            String(x.id_turno || '') === String(s.idTurnoSelecionado) &&
            x.dia_semana === diaSemana &&
            parseInt(x.numero_aula, 10) === parseInt(numeroAula, 10))
        );
      } else {
        alert(resp.message);
      }

      App.grade.atualizarQuadroDisciplinas();
      App.grade.refreshAllDisciplineOptionStates();
    } catch (err) {
      console.error('Erro deletaNoBanco:', err);
      alert('Erro ao deletar horário');
    }
  };

  App.grade.deletarHorario = function (diaSemana, numeroAula) {
    const s = App.state;
    const horarioExistente = (s.dadosTurma.horarios || []).find(h =>
      h.dia_semana === diaSemana && parseInt(h.numero_aula, 10) === parseInt(numeroAula, 10)
    );

    if (horarioExistente) {
      App.grade.registrarHistorico(horarioExistente).then(() => {
        App.grade.deletaNoBanco(diaSemana, numeroAula, horarioExistente.id_horario, horarioExistente.id_disciplina);
      });
    } else {
      App.grade.deletaNoBanco(diaSemana, numeroAula, null, null);
    }
  };

  App.grade.salvarOuAtualizar = async function (diaSemana, numeroAula, discId, profId, td) {
    const s = App.state;

    if (!s.idTurmaSelecionada) return;

    const h = (s.dadosTurma.horarios || []).find(x =>
      x.dia_semana === diaSemana && parseInt(x.numero_aula, 10) === parseInt(numeroAula, 10)
    );

    if (!discId || !profId) return;

    if (App.grade.professorEhRestrito(profId, diaSemana, numeroAula)) {
      alert('O professor está restrito neste horário.');
      App.grade.limparCelula(td, diaSemana, numeroAula);
      return;
    }

    const conflict = App.grade.professorOcupado(profId, diaSemana, numeroAula, h ? h.id_horario : null);
    if (conflict) {
      alert(`O professor já está ocupado (Turma: ${conflict.nome_serie} ${conflict.nome_turma}).`);
      App.grade.limparCelula(td, diaSemana, numeroAula);
      return;
    }

    if (!App.grade.checarSaldoDisciplina(discId, diaSemana, numeroAula)) {
      alert('A disciplina não possui mais aulas disponíveis.');
      App.grade.limparCelula(td, diaSemana, numeroAula);
      return;
    }

    if (!h) {
      await App.grade.inserirHorario(diaSemana, numeroAula, discId, profId);
    } else {
      await App.grade.registrarHistorico(h);
      await App.grade.atualizarHorario(h.id_horario, discId, profId);
    }
  };

  App.grade.montarCelulaAula = function (td, diaSemana, numeroAula) {
    const s = App.state;

    const horarioExistente = (s.dadosTurma.horarios || []).find(h =>
      h.dia_semana === diaSemana && parseInt(h.numero_aula, 10) === numeroAula
    );

    const selDisc = document.createElement('select');
    selDisc.classList.add('select-disciplina');
    selDisc.appendChild(new Option('--Disc--', ''));

    (s.dadosTurma.serie_disciplinas || []).forEach(d => {
      selDisc.appendChild(new Option(d.nome_disciplina, String(d.id_disciplina)));
    });

    const selProf = document.createElement('select');
    selProf.classList.add('select-professor');
    selProf.appendChild(new Option('--Prof--', ''));

    if (horarioExistente) {
      selDisc.value = String(horarioExistente.id_disciplina || '');
      App.grade.refazerProfessores(selDisc, selProf, diaSemana, numeroAula, selDisc.value);
      selProf.value = String(horarioExistente.id_professor || '');
      App.grade.aplicarCorCelula(td, selDisc.value, selProf.value);
    } else {
      App.grade.refazerProfessores(selDisc, selProf, diaSemana, numeroAula, selDisc.value);
      App.grade.refazerDisciplinas(selDisc, selProf, diaSemana, numeroAula);
    }

    selDisc.disabled = !s.editingEnabled;
    selProf.disabled = !s.editingEnabled;

    selDisc.addEventListener('change', async () => {
      if (!s.editingEnabled) return;

      const novaDisc = selDisc.value;

      if (novaDisc && !App.grade.checarSaldoDisciplina(novaDisc, diaSemana, numeroAula)) {
        alert('Não há mais aulas disponíveis para essa disciplina nesta turma.');
        selDisc.value = '';
        App.grade.refazerProfessores(selDisc, selProf, diaSemana, numeroAula, '');
        App.grade.refazerDisciplinas(selDisc, selProf, diaSemana, numeroAula);
        App.grade.aplicarCorCelula(td, '', '');
        return;
      }

      App.grade.refazerProfessores(selDisc, selProf, diaSemana, numeroAula, novaDisc);

      if (novaDisc && selProf.value) {
        await App.grade.salvarOuAtualizar(diaSemana, numeroAula, novaDisc, selProf.value, td);
      } else if (!novaDisc && !selProf.value) {
        App.grade.deletarHorario(diaSemana, numeroAula);
      }

      App.grade.aplicarCorCelula(td, novaDisc, selProf.value);
      App.grade.atualizarQuadroDisciplinas();
      App.grade.refreshAllDisciplineOptionStates();
    });

    selProf.addEventListener('change', async () => {
      if (!s.editingEnabled) return;

      const novoProf = selProf.value;

      if (selDisc.value && !App.grade.checarSaldoDisciplina(selDisc.value, diaSemana, numeroAula)) {
        alert('Disciplina já está no limite de aulas para esta turma.');
        selProf.value = '';
        App.grade.refazerProfessores(selDisc, selProf, diaSemana, numeroAula, selDisc.value);
        return;
      }

      App.grade.refazerDisciplinas(selDisc, selProf, diaSemana, numeroAula);

      if (selDisc.value && novoProf) {
        await App.grade.salvarOuAtualizar(diaSemana, numeroAula, selDisc.value, novoProf, td);
      } else if (!selDisc.value && !novoProf) {
        App.grade.deletarHorario(diaSemana, numeroAula);
      }

      App.grade.aplicarCorCelula(td, selDisc.value, novoProf);
      App.grade.atualizarQuadroDisciplinas();
      App.grade.refreshAllDisciplineOptionStates();
    });

    td.appendChild(selDisc);
    td.appendChild(document.createElement('br'));
    td.appendChild(selProf);
  };

  App.grade.montarGrade = function () {
    const s = App.state;
    if (App.dom.contentDataHorarios) App.dom.contentDataHorarios.style.display = 'block';
    App.filters.hideNoData();

    if (!App.dom.gradeContainer) return;
    App.dom.gradeContainer.innerHTML = '';

    if (!s.dadosTurma?.turma) {
      App.dom.gradeContainer.innerHTML = '<p>Turma não encontrada.</p>';
      return;
    }

    const diasComAulas = (s.dadosTurma.turno_dias || []).filter(td => parseInt(td.aulas_no_dia, 10) > 0);
    if (diasComAulas.length === 0) {
      App.dom.gradeContainer.innerHTML = '<p>Nenhum dia possui aulas neste turno.</p>';
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
      th.textContent = App.grade.traduzDia(d.dia_semana);
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

        App.grade.montarCelulaAula(td, d.dia_semana, aula);
        tr.appendChild(td);
      });

      tbody.appendChild(tr);

      if (s.intervalPositions.includes(aula)) {
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
    App.dom.gradeContainer.appendChild(table);
    App.grade.refreshAllDisciplineOptionStates();
  };

  App.grade.atualizarQuadroDisciplinas = function () {
    const s = App.state;
    if (!App.dom.quadroDisciplinas) return;
    App.dom.quadroDisciplinas.innerHTML = '';
    if (!s.dadosTurma?.serie_disciplinas) return;

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

    s.dadosTurma.serie_disciplinas.forEach(d => {
      const discId = String(d.id_disciplina);
      const nomeDisc = d.nome_disciplina;
      const qtde = s.disciplineWeeklyLimit[discId] || 0;
      const usadas = s.usedDisciplineCount[discId] || 0;
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
    App.dom.quadroDisciplinas.appendChild(table);
  };

  App.grade.validarViabilidadeSerie = function () {
    const s = App.state;
    const problemas = [];
    if (!s.dadosTurma?.serie_disciplinas) return problemas;

    const diasComAulas = (s.dadosTurma.turno_dias || []).filter(td => parseInt(td.aulas_no_dia, 10) > 0);
    const mapTurma = s.profDiscTurmaMap[String(s.idTurmaSelecionada)] || {};

    s.dadosTurma.serie_disciplinas.forEach(d => {
      const discId = String(d.id_disciplina);
      const limite = s.disciplineWeeklyLimit[discId] || 0;

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
            !App.grade.professorEhRestrito(pid, td.dia_semana, aula) &&
            !App.grade.professorOcupado(pid, td.dia_semana, aula)
          );
          if (algumLivre) slotsViaveis++;
        }
      });

      if (slotsViaveis < limite) {
        problemas.push(`"${d.nome_disciplina}" requer ${limite} aulas mas só há ${slotsViaveis} slots viáveis.`);
      }
    });

    return problemas;
  };

  App.grade.carregarTudo = async function () {
    const s = App.state;
    await App.grade.loadTurmaTurnoLookup(s.idAnoSelecionado, s.idNivelEnsinoSelecionado);
    await App.grade.loadAllHorariosDoAno(s.idAnoSelecionado);
    await App.grade.loadProfessorRestricoes(s.idAnoSelecionado);
    await App.grade.loadProfessorDisciplinaTurma();
    await App.grade.loadHorariosTurma(s.idTurmaSelecionada);
    App.grade.inicializarLimitesDisciplinas();
    App.grade.recalcularUsosDasDisciplinas();
  };
})();