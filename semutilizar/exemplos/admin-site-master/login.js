const loginForm = document.getElementById('loginForm');
const preloader = document.querySelector('.preloader');
const page = document.querySelector('.page');
const modalInactiveUser = document.getElementById('modal-inactive-user');

loginForm.addEventListener('submit', async (e) => {
    e.preventDefault(); // Impede o envio imediato do formulário

    // Esconde o formulário de login e exibe o preloader
    page.style.display = 'none';
    preloader.style.display = 'flex';

    // Captura os dados do formulário
    const formData = new FormData(loginForm);

    try {
        // Envia os dados via fetch
        const response = await fetch(loginForm.action, {
            method: 'POST',
            body: formData,
        });

        const result = await response.json();

        if (result.status === 'success') {
            // Aguarda 2 segundos antes de redirecionar
            setTimeout(() => {
                window.location.href = result.redirect; // Redireciona
            }, 2000);
        } else {
            // Exibe a mensagem de erro
            if (result.message === 'Usuário inativo. Por favor, contate o administrador do sistema.') {
                // Exibe o modal
                modalInactiveUser.style.display = 'flex';
            } else {
                alert(result.message || 'Erro no login');
            }
            page.style.display = 'flex';
            preloader.style.display = 'none';
        }
    } catch (error) {
        console.error('Erro no login:', error);
        alert('Erro ao processar a solicitação. Tente novamente.');
        page.style.display = 'flex';
        preloader.style.display = 'none';
    }
});

// Função para fechar o modal
function closeModal() {
    modalInactiveUser.style.display = 'none';
}