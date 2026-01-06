document.addEventListener('DOMContentLoaded', function () {
	const Modal = Swal.mixin({
		heightAuto: false,
		scrollbarPadding: false,
		returnFocus: false,
		focusConfirm: false,
		customClass: { popup: 'my-swal-popup' }
	});

	const linkEsq = document.getElementById('linkEsqueceuSenha');
	if (linkEsq) {
		linkEsq.addEventListener('click', () => {
			Modal.fire({
				icon: 'info',
				title: 'Redefinição de senha',
				text: 'Entre em contato com o Administrador do sistema.'
			});
		});
	}

	const loginForm = document.getElementById('loginForm');
	const btnLogin	= document.getElementById('btnLogin');
	if (!loginForm) return;

	loginForm.addEventListener('submit', async function (event) {
		event.preventDefault();
		btnLogin && (btnLogin.disabled = true);

		const url = loginForm.action;
		const formData = new FormData(loginForm);

		try {
			//Modal.fire({ title: 'Entrando...', didOpen: () => Swal.showLoading(), allowOutsideClick: false, allowEscapeKey: false });

			const resp = await fetch(url, {
				method: 'POST',
				body: formData,
				credentials: 'same-origin',
				headers: { 'X-Requested-With': 'XMLHttpRequest' }
			});

			let result; try { result = await resp.json(); } catch { result = { status: 'error' }; }

			if (resp.ok && result.status === 'success') {
				Swal.close();
				window.location.href = '/horarios/app/pages/dashboard.php';
			} else {
				Modal.fire({ icon: 'error', title: 'Oops...', text: result.message || 'Credenciais inválidas.' });
			}
		} catch {
			Modal.fire({ icon: 'error', title: 'Oops...', text: 'Falha de comunicação. Tente novamente.' });
		} finally {
			btnLogin && (btnLogin.disabled = false);
		}
	});
});
