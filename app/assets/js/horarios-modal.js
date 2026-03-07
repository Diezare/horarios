// app/assets/js/horarios-modal.js
(function () {
  window.HorariosApp = window.HorariosApp || {};
  const App = window.HorariosApp;

  App.utils = App.utils || {};
  App.modal = App.modal || {};

  App.utils.escapeHtml = function (str) {
    return String(str ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  };

  App.utils.fetchJson = async function (url, options = {}) {
    const response = await fetch(url, options);
    const text = await response.text();

    try {
      return JSON.parse(text);
    } catch (e) {
      console.error('Resposta não é JSON:', { url, text });
      throw e;
    }
  };

  App.modal.closeWithEffect = function (modal) {
    if (!modal) return;

    const content = modal.querySelector('.modal-content');
    if (content) {
      content.classList.remove('slide-down');
      content.classList.add('slide-up');
    }

    modal.classList.remove('fade-in');
    modal.classList.add('fade-out');

    setTimeout(() => {
      modal.remove();
    }, 300);
  };

  App.modal.openWithEffect = function (modal) {
    if (!modal) return;
    const content = modal.querySelector('.modal-content');
    modal.style.display = 'block';
    modal.classList.remove('fade-out');
    modal.classList.add('fade-in');

    if (content) {
      content.classList.remove('slide-up');
      content.classList.add('slide-down');
    }
  };

  App.modal.criarLoadingOverlay = function () {
    const antigo = document.getElementById('loading-overlay');
    if (antigo) antigo.remove();

    const html = `
      <div id="loading-overlay" style="
        display:none;
        position:fixed;
        top:0;
        left:0;
        width:100%;
        height:100%;
        background:rgba(0,0,0,0.7);
        z-index:99999;
        justify-content:center;
        align-items:center;
      ">
        <div style="
          background:white;
          padding:40px 60px;
          border-radius:12px;
          box-shadow:0 10px 40px rgba(0,0,0,0.3);
          text-align:center;
        ">
          <div class="spinner" style="
            border:6px solid #f3f3f3;
            border-top:6px solid #6358F8;
            border-radius:50%;
            width:60px;
            height:60px;
            animation:spin 1s linear infinite;
            margin:0 auto 20px auto;
          "></div>
          <h3 style="margin:0;color:#333;font-size:18px;">⏳ Gerando horários...</h3>
          <p style="margin:10px 0 0 0;color:#666;font-size:14px;">
            Aguarde. O sistema está testando as possibilidades.
          </p>
        </div>
      </div>
      <style>
        @keyframes spin {
          0% { transform: rotate(0deg); }
          100% { transform: rotate(360deg); }
        }
      </style>
    `;

    document.body.insertAdjacentHTML('beforeend', html);
  };

  App.modal.mostrarLoading = function () {
    let overlay = document.getElementById('loading-overlay');
    if (!overlay) {
      App.modal.criarLoadingOverlay();
      overlay = document.getElementById('loading-overlay');
    }
    overlay.style.display = 'flex';
  };

  App.modal.esconderLoading = function () {
    const overlay = document.getElementById('loading-overlay');
    if (overlay) overlay.style.display = 'none';
  };

  App.modal.imprimirTextoDiagnostico = function (texto) {
    sessionStorage.setItem('relatorioRestricoesGeracao', texto || '');
    window.open('/horarios/app/views/relatorio-restricoes-geracao.php', '_blank');
  };

  App.modal.mostrarResultadoSucesso = function (data) {
    const antigo = document.getElementById('modal-resultado-geracao');
    if (antigo) antigo.remove();

    const html = `
      <div id="modal-resultado-geracao" class="modal" style="display:block;">
        <div class="modal-content" style="max-width:760px;">
          <div class="modal-header">
            <h2>✅ Horários gerados com sucesso</h2>
            <span class="close-modal-resultado" style="cursor:pointer;font-size:28px;font-weight:bold;">&times;</span>
          </div>

          <div class="modal-body">
            <p style="font-size:15px;line-height:1.6;">
              ${App.utils.escapeHtml(data.message || 'Horários gerados com sucesso.')}
            </p>

            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:20px;">
              <div style="background:#f5f5f5;padding:12px 16px;border-radius:8px;">
                <strong>Aulas:</strong> ${data.stats?.totalAulas ?? 0}
              </div>
              <div style="background:#f5f5f5;padding:12px 16px;border-radius:8px;">
                <strong>Vazios:</strong> ${data.stats?.totalVazios ?? 0}
              </div>
              <div style="background:#f5f5f5;padding:12px 16px;border-radius:8px;">
                <strong>Consecutivas:</strong> ${data.stats?.consecutivas ?? 0}
              </div>
              <div style="background:#f5f5f5;padding:12px 16px;border-radius:8px;">
                <strong>Backtracks:</strong> ${data.stats?.totalBacktracks ?? 0}
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button id="btnFecharResultadoSucesso" style="background-color:#6358F8;color:white;">
              Fechar
            </button>
          </div>
        </div>
      </div>
    `;

    document.body.insertAdjacentHTML('beforeend', html);
    const modal = document.getElementById('modal-resultado-geracao');
    App.modal.openWithEffect(modal);

    const fechar = () => {
      App.modal.closeWithEffect(modal);
      setTimeout(() => location.reload(), 320);
    };

    modal.querySelector('.close-modal-resultado').onclick = fechar;
    modal.querySelector('#btnFecharResultadoSucesso').onclick = fechar;
    modal.onclick = (e) => {
      if (e.target === modal) fechar();
    };
  };

  App.modal.mostrarResultadoParcial = function (data) {
    const antigo = document.getElementById('modal-resultado-geracao');
    if (antigo) antigo.remove();

    const diagnostico = data.diagnostico || {};
    const faltasTurmas = diagnostico.faltas_turmas || [];
    const professoresCriticos = diagnostico.professores_criticos || [];
    const textoImpressao = diagnostico.texto_impressao || data.message || '';

    const faltasHtml = faltasTurmas.length
      ? faltasTurmas.map(t => `
        <div style="margin-bottom:14px;padding:12px;border:1px solid #ddd;border-radius:8px;background:#fafafa;">
          <div style="font-weight:700;margin-bottom:8px;">
            ${App.utils.escapeHtml(t.serie)} - ${App.utils.escapeHtml(t.turma)} (Turno ${t.turno_id})
          </div>
          <ul style="margin:0;padding-left:20px;">
            ${(t.faltas || []).map(f => `
              <li>${App.utils.escapeHtml(f.disciplina)}: faltando ${f.faltando}</li>
            `).join('')}
          </ul>
        </div>
      `).join('')
      : '<p>Nenhuma pendência encontrada.</p>';

    const profsHtml = professoresCriticos.slice(0, 10).map(p => {
      const livres = Object.values(p.livres_por_turno || {}).reduce((a, b) => a + Number(b || 0), 0);
      return `
        <tr>
          <td style="padding:8px;border:1px solid #ddd;">${App.utils.escapeHtml(p.nome)}</td>
          <td style="padding:8px;border:1px solid #ddd;text-align:center;">${p.qtd_restricoes}</td>
          <td style="padding:8px;border:1px solid #ddd;text-align:center;">${livres}</td>
          <td style="padding:8px;border:1px solid #ddd;text-align:center;">${p.uso}</td>
        </tr>
      `;
    }).join('');

    const html = `
      <div id="modal-resultado-geracao" class="modal" style="display:block;">
        <div class="modal-content" style="max-width:980px;max-height:90vh;overflow-y:auto;">
          <div class="modal-header">
            <h2>⚠️ Geração incompleta</h2>
            <span class="close-modal-resultado" style="cursor:pointer;font-size:28px;font-weight:bold;">&times;</span>
          </div>

          <div class="modal-body">
            <p style="font-size:15px;line-height:1.7;">
              ${App.utils.escapeHtml(data.message || 'Todas as possibilidades de geração foram executadas, porém não foi possível completar todos os horários.')}
            </p>

            <div style="display:flex;gap:10px;flex-wrap:wrap;margin:20px 0;">
              <div style="background:#f5f5f5;padding:12px 16px;border-radius:8px;">
                <strong>Aulas:</strong> ${data.stats?.totalAulas ?? 0}
              </div>
              <div style="background:#f5f5f5;padding:12px 16px;border-radius:8px;">
                <strong>Vazios:</strong> ${data.stats?.totalVazios ?? 0}
              </div>
              <div style="background:#f5f5f5;padding:12px 16px;border-radius:8px;">
                <strong>Consecutivas:</strong> ${data.stats?.consecutivas ?? 0}
              </div>
              <div style="background:#f5f5f5;padding:12px 16px;border-radius:8px;">
                <strong>Backtracks:</strong> ${data.stats?.totalBacktracks ?? 0}
              </div>
            </div>

            <h3>Turmas com pendências</h3>
            ${faltasHtml}

            <h3 style="margin-top:24px;">Professores com maiores restrições</h3>
            <div style="overflow-x:auto;">
              <table style="width:100%;border-collapse:collapse;">
                <thead>
                  <tr style="background:#f2f2f2;">
                    <th style="padding:8px;border:1px solid #ddd;text-align:left;">Professor</th>
                    <th style="padding:8px;border:1px solid #ddd;">Restrições</th>
                    <th style="padding:8px;border:1px solid #ddd;">Livres</th>
                    <th style="padding:8px;border:1px solid #ddd;">Uso</th>
                  </tr>
                </thead>
                <tbody>
                  ${profsHtml || '<tr><td colspan="4" style="padding:10px;border:1px solid #ddd;">Sem dados.</td></tr>'}
                </tbody>
              </table>
            </div>
          </div>

          <div class="modal-footer">
            <button id="btnImprimirRestricoesGeracao" style="background-color:#81D43A;color:white;">
              <i class="fa-solid fa-print"></i> Imprimir restrições
            </button>
            <button id="btnCancelarRestricoesGeracao" style="background-color:#FC3B56;color:white;">
              Cancelar
            </button>
          </div>
        </div>
      </div>
    `;

    document.body.insertAdjacentHTML('beforeend', html);
    const modal = document.getElementById('modal-resultado-geracao');
    App.modal.openWithEffect(modal);

    const fechar = () => App.modal.closeWithEffect(modal);

    modal.querySelector('.close-modal-resultado').onclick = fechar;
    modal.querySelector('#btnCancelarRestricoesGeracao').onclick = fechar;
    modal.querySelector('#btnImprimirRestricoesGeracao').onclick = () => {
      App.modal.imprimirTextoDiagnostico(textoImpressao);
    };
    modal.onclick = (e) => {
      if (e.target === modal) fechar();
    };
  };
})();