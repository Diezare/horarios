// app/assets/js/horarios-geracao.js
(function () {
  window.HorariosApp = window.HorariosApp || {};
  const App = window.HorariosApp;

  App.generation = App.generation || {};

  App.generation.openModalAutomatico = function () {
    const modal = App.dom.modalAutomatico;
    if (!modal) {
      alert('Modal de geração automática não encontrado.');
      return;
    }
    App.modal.openWithEffect(modal);
  };

  App.generation.closeModalAutomatico = function () {
    const modal = App.dom.modalAutomatico;
    if (!modal) return;
    App.modal.closeWithEffect(modal);
  };

  App.generation.bindAutomatico = function () {
    if (App.dom.btnAutomatico) {
      App.dom.btnAutomatico.disabled = true;

      App.dom.btnAutomatico.addEventListener('click', () => {
        const s = App.state;
        if (!s.idAnoSelecionado || !s.idNivelEnsinoSelecionado || !s.idTurnoSelecionado) return;

        if (typeof window.iniciarGeracaoComModal === 'function') {
          window.iniciarGeracaoComModal(
            s.idAnoSelecionado,
            s.idNivelEnsinoSelecionado,
            s.idTurnoSelecionado
          );
          return;
        }

        App.generation.openModalAutomatico();
      });
    }

    const modal = App.dom.modalAutomatico;
    if (!modal) return;

    const btnConf = modal.querySelector('#btnConfirmarAutomatico');
    const btnCanc = modal.querySelector('#btnCancelarAutomatico');
    const btnClose = modal.querySelector('.close-modal-auto');

    if (btnConf) {
      btnConf.onclick = async () => {
        const s = App.state;
        if (!s.idAnoSelecionado || !s.idNivelEnsinoSelecionado || !s.idTurnoSelecionado) {
          alert('Selecione Ano Letivo, Nível de Ensino e Turno antes de gerar.');
          return;
        }

        App.generation.closeModalAutomatico();

        if (typeof window.iniciarGeracaoComModal === 'function') {
          window.iniciarGeracaoComModal(
            s.idAnoSelecionado,
            s.idNivelEnsinoSelecionado,
            s.idTurnoSelecionado
          );
        }
      };
    }

    if (btnCanc) btnCanc.onclick = () => App.generation.closeModalAutomatico();
    if (btnClose) btnClose.onclick = () => App.generation.closeModalAutomatico();
  };

  document.addEventListener('DOMContentLoaded', async function () {
    App.modal.criarLoadingOverlay();
    await App.filters.init();
    App.generation.bindAutomatico();
  });
})();