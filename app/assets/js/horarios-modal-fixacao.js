// app/assets/js/horarios-modal-fixacao.js
(function () {
  window.HorariosApp = window.HorariosApp || {};
  const App = window.HorariosApp;

  App.fixacao = App.fixacao || {};
  App.fixacao.diasDisponiveis = [];

  App.fixacao.criarModalFixacao = function () {
    const modalAntigo = document.getElementById('modal-fixacao-disciplina');
    if (modalAntigo) modalAntigo.remove();

    const modalHTML = `
      <div id="modal-fixacao-disciplina" class="modal" style="display:none;">
        <div class="modal-content" style="max-width:600px;">
          <div class="modal-header">
            <h2>⚙️ Configurar Geração de Horários</h2>
            <span class="close-modal-fixacao" style="cursor:pointer;font-size:28px;font-weight:bold;">&times;</span>
          </div>

          <div class="modal-body">
            <div class="form-group">
              <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                <input type="checkbox" id="checkFixarDisciplina" style="width:20px;height:20px;cursor:pointer;">
                <span style="font-weight:600;font-size:15px;">
                  Fixar disciplina em horário específico
                </span>
              </label>
              <small style="color:#666;display:block;margin-top:5px;margin-left:30px;">
                Marque para alocar uma disciplina no mesmo horário em todas as turmas.
              </small>
            </div>

            <div id="campos-fixacao" style="display:none;margin-top:20px;padding:20px;background:#f8f9fa;border-radius:8px;border-left:4px solid #6358F8;">
              <div class="form-group">
                <label for="selectDisciplinaFixa" style="font-weight:600;display:block;margin-bottom:8px;">📚 Disciplina:</label>
                <select id="selectDisciplinaFixa" class="form-control" style="width:100%;padding:10px;border-radius:6px;border:1px solid #ddd;">
                  <option value="">-- Carregando... --</option>
                </select>
              </div>

              <div class="form-group" style="margin-top:15px;">
                <label for="selectDiaFixo" style="font-weight:600;display:block;margin-bottom:8px;">📅 Dia da semana:</label>
                <select id="selectDiaFixo" class="form-control" style="width:100%;padding:10px;border-radius:6px;border:1px solid #ddd;">
                  <option value="">-- Carregando dias... --</option>
                </select>
              </div>

              <div class="form-group" style="margin-top:15px;">
                <label for="selectAulaFixa" style="font-weight:600;display:block;margin-bottom:8px;">⏰ Período:</label>
                <select id="selectAulaFixa" class="form-control" style="width:100%;padding:10px;border-radius:6px;border:1px solid #ddd;">
                  <option value="">-- Selecione o dia primeiro --</option>
                </select>
              </div>

              <div id="resumo-fixacao" style="margin-top:20px;padding:15px;background:white;border-radius:6px;border:1px solid #ddd;display:none;">
                <strong style="color:#6358F8;">📌 Resumo:</strong>
                <p id="texto-resumo" style="margin:10px 0 0 0;color:#333;"></p>
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" id="btnCancelarFixacao" style="background-color:#FC3B56;color:white;">
              <i class="fa-solid fa-times"></i> Cancelar
            </button>
            <button type="button" id="btnConfirmarGeracao" style="background-color:#6358F8;color:white;">
              <i class="fa-solid fa-check"></i> Gerar Horários
            </button>
          </div>
        </div>
      </div>
    `;

    document.body.insertAdjacentHTML('beforeend', modalHTML);
  };

  App.fixacao.carregarDisciplinasParaFixacao = async function (idNivelEnsino) {
    const select = document.getElementById('selectDisciplinaFixa');

    try {
      const response = await fetch(
        `/horarios/app/controllers/horarios/listarDisciplinas.php?id_nivel_ensino=${encodeURIComponent(idNivelEnsino)}`
      );
      const data = await response.json();

      if (data.status === 'success' && data.disciplinas) {
        select.innerHTML = '<option value="">-- Selecione a disciplina --</option>';
        data.disciplinas.forEach(disc => {
          const option = document.createElement('option');
          option.value = disc.id_disciplina;
          option.textContent = `${disc.nome_disciplina} (${disc.sigla_disciplina})`;
          select.appendChild(option);
        });
      } else {
        select.innerHTML = '<option value="">❌ Erro ao carregar disciplinas</option>';
      }
    } catch (error) {
      console.error('Erro ao carregar disciplinas:', error);
      select.innerHTML = '<option value="">❌ Erro ao carregar</option>';
    }
  };

  App.fixacao.carregarDiasDisponiveis = async function (idNivelEnsino, idAnoLetivo, idTurno) {
    const selectDia = document.getElementById('selectDiaFixo');
    const selectAula = document.getElementById('selectAulaFixa');

    try {
      idNivelEnsino = String(idNivelEnsino || '').trim();
      idAnoLetivo = String(idAnoLetivo || '').trim();
      idTurno = String(idTurno || '').trim();

      if (!idNivelEnsino || !idAnoLetivo) {
        throw new Error('Parâmetros inválidos.');
      }

      selectDia.innerHTML = '<option value="">-- Carregando... --</option>';
      selectAula.innerHTML = '<option value="">-- Selecione o dia primeiro --</option>';

      const url =
        `/horarios/app/controllers/horarios/listarDiasTurno.php` +
        `?id_nivel_ensino=${encodeURIComponent(idNivelEnsino)}` +
        `&id_ano_letivo=${encodeURIComponent(idAnoLetivo)}` +
        (idTurno ? `&id_turno=${encodeURIComponent(idTurno)}` : '');

      const response = await fetch(url);
      const data = await response.json();

      if (data.status === 'success' && Array.isArray(data.dias)) {
        App.fixacao.diasDisponiveis = data.dias;

        selectDia.innerHTML = '<option value="">-- Selecione o dia --</option>';
        data.dias.forEach(dia => {
          const option = document.createElement('option');
          option.value = dia.dia_semana;
          option.textContent = `${dia.nome_exibicao} (${dia.aulas_no_dia} aulas)`;
          option.dataset.aulas = dia.aulas_no_dia;
          selectDia.appendChild(option);
        });

        selectAula.innerHTML = '<option value="">-- Selecione o dia primeiro --</option>';
      } else {
        selectDia.innerHTML = '<option value="">❌ Erro ao carregar dias</option>';
      }
    } catch (error) {
      console.error('Erro ao carregar dias:', error);
      selectDia.innerHTML = '<option value="">❌ Erro ao carregar</option>';
    }
  };

  App.fixacao.atualizarAulasDisponiveis = function () {
    const selectDia = document.getElementById('selectDiaFixo');
    const selectAula = document.getElementById('selectAulaFixa');
    const diaSelecionado = selectDia.value;

    if (!diaSelecionado) {
      selectAula.innerHTML = '<option value="">-- Selecione o dia primeiro --</option>';
      return;
    }

    const diaInfo = App.fixacao.diasDisponiveis.find(d => d.dia_semana === diaSelecionado);
    if (!diaInfo) {
      selectAula.innerHTML = '<option value="">❌ Dia inválido</option>';
      return;
    }

    const qtdAulas = diaInfo.aulas_no_dia;
    selectAula.innerHTML = '<option value="">-- Selecione a aula --</option>';

    for (let i = 1; i <= qtdAulas; i++) {
      const option = document.createElement('option');
      option.value = i;
      option.textContent = `${i}ª aula`;
      selectAula.appendChild(option);
    }
  };

  App.fixacao.inicializarEventosModal = function () {
    const checkbox = document.getElementById('checkFixarDisciplina');
    const camposFixacao = document.getElementById('campos-fixacao');
    const selectDisciplina = document.getElementById('selectDisciplinaFixa');
    const selectDia = document.getElementById('selectDiaFixo');
    const selectAula = document.getElementById('selectAulaFixa');
    const resumo = document.getElementById('resumo-fixacao');
    const textoResumo = document.getElementById('texto-resumo');

    function atualizarResumo() {
      if (!checkbox.checked) return;

      const disciplinaNome = selectDisciplina.options[selectDisciplina.selectedIndex]?.text || '';
      const dia = selectDia.value;
      const aula = selectAula.options[selectAula.selectedIndex]?.text || '';

      if (selectDisciplina.value && dia && selectAula.value) {
        const diaNome = selectDia.options[selectDia.selectedIndex].text;
        textoResumo.innerHTML = `A disciplina <strong>${App.utils.escapeHtml(disciplinaNome)}</strong> será alocada no horário <strong>${App.utils.escapeHtml(diaNome.split(' (')[0])} - ${App.utils.escapeHtml(aula)}</strong> em <strong>TODAS as turmas</strong>.`;
        resumo.style.display = 'block';
      } else {
        resumo.style.display = 'none';
      }
    }

    checkbox.addEventListener('change', function () {
      camposFixacao.style.display = this.checked ? 'block' : 'none';
      if (!this.checked) resumo.style.display = 'none';
    });

    selectDia.addEventListener('change', function () {
      App.fixacao.atualizarAulasDisponiveis();
      atualizarResumo();
    });

    selectDisciplina.addEventListener('change', atualizarResumo);
    selectAula.addEventListener('change', atualizarResumo);
  };

  App.fixacao.abrirModalFixacao = function (idAnoLetivo, idNivelEnsino, idTurno, callback) {
    if (!document.getElementById('modal-fixacao-disciplina')) {
      App.fixacao.criarModalFixacao();
    }

    const modal = document.getElementById('modal-fixacao-disciplina');

    if (typeof idTurno === 'function' && callback === undefined) {
      callback = idTurno;
      idTurno = document.getElementById('selectTurno')?.value || null;
    }

    if (!idTurno) idTurno = document.getElementById('selectTurno')?.value || null;
    idTurno = String(idTurno || '').trim();

    if (!idTurno) {
      alert('⚠️ Selecione o turno antes de continuar.');
      return;
    }

    App.fixacao.carregarDisciplinasParaFixacao(idNivelEnsino);
    App.fixacao.carregarDiasDisponiveis(idNivelEnsino, idAnoLetivo, idTurno);
    App.fixacao.inicializarEventosModal();
    App.modal.openWithEffect(modal);

    document.getElementById('btnCancelarFixacao').onclick = function () {
      App.modal.closeWithEffect(modal);
    };

    modal.querySelector('.close-modal-fixacao').onclick = function () {
      App.modal.closeWithEffect(modal);
    };

    modal.onclick = function (e) {
      if (e.target === modal) {
        App.modal.closeWithEffect(modal);
      }
    };

    document.getElementById('btnConfirmarGeracao').onclick = function () {
      const checkbox = document.getElementById('checkFixarDisciplina');
      let dadosFixacao = null;

      if (checkbox.checked) {
        const disciplinaId = document.getElementById('selectDisciplinaFixa').value;
        const dia = document.getElementById('selectDiaFixo').value;
        const aula = document.getElementById('selectAulaFixa').value;

        if (!disciplinaId || !dia || !aula) {
          alert('⚠️ Preencha todos os campos da fixação ou desmarque a opção.');
          return;
        }

        dadosFixacao = {
          id_disciplina: disciplinaId,
          dia_semana: dia,
          numero_aula: parseInt(aula, 10)
        };
      }

      App.modal.closeWithEffect(modal);
      callback(dadosFixacao);
    };
  };

  App.fixacao.gerarHorariosAutomaticosComFixacao = async function (idAnoLetivo, idNivelEnsino, idTurno, dadosFixacao) {
    try {
      App.modal.mostrarLoading();

      const formData = new URLSearchParams();
      formData.append('id_ano_letivo', idAnoLetivo);
      formData.append('id_nivel_ensino', idNivelEnsino);
      formData.append('id_turno', idTurno);

      if (dadosFixacao) {
        formData.append('fixar_disciplina', 'true');
        formData.append('disciplina_fixa_id', dadosFixacao.id_disciplina);
        formData.append('disciplina_fixa_dia', dadosFixacao.dia_semana);
        formData.append('disciplina_fixa_aula', dadosFixacao.numero_aula);
      }

      const response = await fetch('/horarios/app/controllers/horarios/gerarHorariosAutomaticos.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData
      });

      const data = await response.json();
      App.modal.esconderLoading();

      console.clear();
      console.log('========================================');
      console.log('📊 DIAGNÓSTICO DE GERAÇÃO DE HORÁRIOS');
      console.log('========================================');
      console.log(data);

      if (data.status === 'success') {
        App.modal.mostrarResultadoSucesso(data);
        return;
      }

      if (data.status === 'partial') {
        App.modal.mostrarResultadoParcial(data);
        return;
      }

      alert('❌ Erro: ' + (data.message || 'Erro ao gerar horários!'));
    } catch (error) {
      App.modal.esconderLoading();
      console.error('❌ ERRO FATAL:', error);
      alert('❌ Ocorreu um erro ao gerar horários automáticos.');
    }
  };

  function iniciarGeracaoComModal(idAnoLetivo, idNivelEnsino, idTurno) {
    if (!idTurno) idTurno = document.getElementById('selectTurno')?.value || null;

    App.fixacao.abrirModalFixacao(idAnoLetivo, idNivelEnsino, idTurno, function (dadosFixacao) {
      if (!idTurno) idTurno = document.getElementById('selectTurno')?.value || null;

      idTurno = parseInt(String(idTurno || '').trim(), 10);
      if (!Number.isInteger(idTurno) || idTurno <= 0) {
        alert('⚠️ Selecione o turno antes de continuar.');
        return;
      }

      App.fixacao.gerarHorariosAutomaticosComFixacao(
        idAnoLetivo,
        idNivelEnsino,
        idTurno,
        dadosFixacao
      );
    });
  }

  window.iniciarGeracaoComModal = iniciarGeracaoComModal;
  window.gerarHorariosAutomaticosComFixacao = App.fixacao.gerarHorariosAutomaticosComFixacao;
})();