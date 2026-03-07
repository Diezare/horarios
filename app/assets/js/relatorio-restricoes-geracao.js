document.addEventListener('DOMContentLoaded', function () {
  const conteudo = document.getElementById('conteudo-relatorio');
  const btnPrint = document.getElementById('btn-print-relatorio');
  const btnClose = document.getElementById('btn-close-relatorio');

  const texto = sessionStorage.getItem('relatorioRestricoesGeracao') || 'Nenhum relatório disponível.';

  if (conteudo) {
    conteudo.textContent = texto;
  }

  if (btnPrint) {
    btnPrint.addEventListener('click', function () {
      window.print();
    });
  }

  if (btnClose) {
    btnClose.addEventListener('click', function () {
      window.close();
    });
  }
});