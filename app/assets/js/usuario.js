// app/assets/js/usuario.js
document.addEventListener('DOMContentLoaded', function () {
	// ==============================
	// REFERÊNCIAS DE ELEMENTOS
	// ==============================
	const modal = document.getElementById('modal-usuario');
	const btnAdd = document.getElementById('btn-add');
	const closeModalElements = document.querySelectorAll('#modal-usuario .close-modal');
	const cancelBtn = document.getElementById('cancel-btn');
	const saveBtn = document.getElementById('save-btn');

	const modalDelete = document.getElementById('modal-delete');
	const closeDeleteModalBtn = document.getElementById('close-delete-modal');
	const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
	const cancelDeleteBtn = document.getElementById('cancel-delete-btn');

	// Modal de impressão individual
	const modalPrint = document.getElementById('modal-print');
	const closePrintModalBtn = document.getElementById('close-print-modal');
	const btnImprimir = document.getElementById('btn-imprimir');
	const btnCancelar = document.getElementById('btn-cancelar');
	const selectedUserInput = document.getElementById('selected-user');

	// Modal de impressão geral
	const modalPrintGeral = document.getElementById('modal-print-geral');
	const closePrintGeralModalBtn = document.getElementById('close-print-geral-modal');
	const btnImprimirGeralConfirm = document.getElementById('btn-imprimir-geral-confirm');
	const btnCancelarGeral = document.getElementById('btn-cancelar-geral');
	const btnImprimirGeral = document.getElementById('btnImprimir');

	const tableBody = document.getElementById('usuarioTable');
	const noDataMessage = document.getElementById('no-data-message');

	// Modal de alerta
	const modalAlert = document.getElementById('modal-alert');
	const alertMessageElem = document.getElementById('alert-message');
	const closeAlertModalBtn = document.getElementById('close-alert-modal');
	const alertOkBtn = document.getElementById('alert-ok-btn');

	// Inputs do formulário
	const hiddenId = document.getElementById('usuarioId');
	const nomeInput = document.getElementById('nome_usuario');
	const emailInput = document.getElementById('email_usuario');
	const senhaInput = document.getElementById('senha_usuario');
	const confirmaSenhaInput = document.getElementById('confirma_senha');
	const situacaoInputs = document.getElementsByName('situacao_usuario'); // radios
	const imagemInput = document.getElementById('imagem_usuario');
	const perfilPreview = document.getElementById('perfil-preview');

	// Select de nível de usuário
	const nivelUsuarioSelect = document.getElementById('nivel_usuario');

	// Campos do modal de vincular níveis (somente leitura com o nome)
	const usuarioNomeVinculo = document.getElementById('usuario_nome_vinculo');

	// ==============================
	// VARIÁVEIS DE CONTROLE
	// ==============================
	let isEditMode = false;
	let currentEditId = null;
	let currentDeleteId	= null;
	let imageRemoved = false;
	let allUsers = [];		 // cache da listagem para ordenar
	let selectedUserId = null;	 // para impressão individual

	// ==============================
	// HELPERS DE MODAL
	// ==============================
	function openModal() {
		if (!modal) return;
		modal.style.display = 'block';
		modal.classList.remove('fade-out');
		modal.classList.add('fade-in');
		const content = modal.querySelector('.modal-content');
		if (content) {
			content.classList.remove('slide-up');
			content.classList.add('slide-down');
		}
	}

	function closeModal() {
		if (!modal) return;
		const content = modal.querySelector('.modal-content');
		if (content) {
			content.classList.remove('slide-down');
			content.classList.add('slide-up');
		}
		modal.classList.remove('fade-in');
		modal.classList.add('fade-out');
		setTimeout(() => {
			modal.style.display = 'none';
			if (content) content.classList.remove('slide-up');
			modal.classList.remove('fade-out');
			isEditMode = false;
			currentEditId = null;
			imageRemoved = false;
			clearForm();
		}, 300);
	}

	function openGenericModal(modalElem) {
		if (!modalElem) return;
		modalElem.style.display = 'block';
		modalElem.classList.remove('fade-out');
		modalElem.classList.add('fade-in');
		const content = modalElem.querySelector('.modal-content');
		if (content) {
			content.classList.remove('slide-up');
			content.classList.add('slide-down');
		}
	}

	function closeGenericModal(modalElem) {
		if (!modalElem) return;
		const content = modalElem.querySelector('.modal-content');
		if (content) {
			content.classList.remove('slide-down');
			content.classList.add('slide-up');
		}
		modalElem.classList.remove('fade-in');
		modalElem.classList.add('fade-out');
		setTimeout(() => {
			modalElem.style.display = 'none';
			if (content) content.classList.remove('slide-up');
			modalElem.classList.remove('fade-out');
		}, 300);
	}

	function clearForm() {
		if (hiddenId) hiddenId.value = '';
		if (nomeInput) nomeInput.value = '';
		if (emailInput) emailInput.value = '';
		if (senhaInput) senhaInput.value = '';
		if (confirmaSenhaInput) confirmaSenhaInput.value = '';
		Array.from(situacaoInputs || []).forEach(r => (r.checked = (r.value === 'Ativo')));
		if (imagemInput) imagemInput.value = '';
		if (perfilPreview) perfilPreview.innerHTML = '';
		if (nivelUsuarioSelect) nivelUsuarioSelect.value = 'Usuário';
	}

	function showAlert(message) {
		if (!modalAlert || !alertMessageElem) return;
		alertMessageElem.textContent = message;
		modalAlert.style.display = 'block';
		modalAlert.classList.remove('fade-out');
		modalAlert.classList.add('fade-in');
	}

	function closeAlert() {
		if (!modalAlert) return;
		modalAlert.classList.remove('fade-in');
		modalAlert.classList.add('fade-out');
		setTimeout(() => { modalAlert.style.display = 'none'; }, 300);
	}

	if (closeAlertModalBtn) closeAlertModalBtn.addEventListener('click', closeAlert);
	if (alertOkBtn) alertOkBtn.addEventListener('click', closeAlert);

	// ==============================
	// LISTAGEM
	// ==============================
	function fetchUsuarios() {
		fetch('/horarios/app/controllers/usuario/listUsuario.php')
			.then(r => r.json())
			.then(data => {
				if (data.status === 'success') {
					allUsers = data.data || [];
					renderTable(allUsers);
				} else {
					console.error(data.message);
				}
			})
			.catch(console.error);
	}

	function renderTable(rows) {
		if (!tableBody) return;
		tableBody.innerHTML = '';
		if (!rows || rows.length === 0) {
			if (noDataMessage) noDataMessage.style.display = 'block';
			return;
		}
		if (noDataMessage) noDataMessage.style.display = 'none';

		rows.forEach(user => {
			const tr = document.createElement('tr');
			tr.dataset.id = user.id_usuario;

			const tdNome = document.createElement('td');
			tdNome.textContent = user.nome_usuario || '';
			tr.appendChild(tdNome);

			const tdEmail = document.createElement('td');
			tdEmail.textContent = user.email_usuario || '';
			tr.appendChild(tdEmail);

			const tdSituacao = document.createElement('td');
			tdSituacao.textContent = user.situacao_usuario || '';
			tr.appendChild(tdSituacao);

			const tdNivel = document.createElement('td');
			tdNivel.textContent = user.nivel_usuario || '';
			tr.appendChild(tdNivel);

			const tdActions = document.createElement('td');

			// Editar
			const btnEdit = document.createElement('button');
			btnEdit.classList.add('btn-edit');
			btnEdit.dataset.id = user.id_usuario;
			btnEdit.innerHTML = `
				<span class="icon"><i class="fa-solid fa-pen-to-square"></i></span>
				<span class="text">Editar</span>
			`;
			tdActions.appendChild(btnEdit);

			// Deletar
			const btnDelete = document.createElement('button');
			btnDelete.classList.add('btn-delete');
			btnDelete.dataset.id = user.id_usuario;
			btnDelete.innerHTML = `
				<span class="icon"><i class="fa-solid fa-trash"></i></span>
				<span class="text">Deletar</span>
			`;
			tdActions.appendChild(btnDelete);

			// Imprimir (individual)
			const btnPrint = document.createElement('button');
			btnPrint.classList.add('btn-print');
			btnPrint.dataset.id = user.id_usuario;
			btnPrint.innerHTML = `
				<span class="icon"><i class="fa-solid fa-print"></i></span>
				<span class="text">Imprimir</span>
			`;
			tdActions.appendChild(btnPrint);

			// Vincular Níveis
			const btnVincular = document.createElement('button');
			btnVincular.classList.add('btn-vincular-nivel');
			btnVincular.dataset.id = user.id_usuario;
			btnVincular.innerHTML = `
				<span class="icon"><i class="fa-solid fa-link"></i></span>
				<span class="text">Níveis</span>
			`;
			tdActions.appendChild(btnVincular);

			tr.appendChild(tdActions);
			tableBody.appendChild(tr);
		});
	}

	// ==============================
	// ORDENAÇÃO
	// ==============================
	function sortTable(property, asc = true) {
		const sorted = [...allUsers].sort((a, b) => {
			const A = (a[property] || '').toString().toLowerCase();
			const B = (b[property] || '').toString().toLowerCase();
			return asc ? A.localeCompare(B) : B.localeCompare(A);
		});
		renderTable(sorted);
	}

	function safeOnClick(id, cb) {
		const el = document.getElementById(id);
		if (el) el.addEventListener('click', cb);
	}

	safeOnClick('sort-nome-asc',	() => sortTable('nome_usuario', true));
	safeOnClick('sort-nome-desc', () => sortTable('nome_usuario', false));
	safeOnClick('sort-email-asc', () => sortTable('email_usuario', true));
	safeOnClick('sort-email-desc',()=> sortTable('email_usuario', false));
	safeOnClick('sort-situacao-asc', () => sortTable('situacao_usuario', true));
	safeOnClick('sort-situacao-desc',()=> sortTable('situacao_usuario', false));
	safeOnClick('sort-nivel-asc', () => sortTable('nivel_usuario', true));
	safeOnClick('sort-nivel-desc',()=> sortTable('nivel_usuario', false));

	// ==============================
	// BOTÕES: NOVO / FECHAR
	// ==============================
	if (btnAdd) btnAdd.addEventListener('click', () => {
		isEditMode = false;
		const title = document.getElementById('modal-title');
		if (title) title.innerText = 'Adicionar Usuário';
		if (saveBtn) saveBtn.innerText = 'Salvar';
		clearForm();
		openModal();
	});

	(closeModalElements || []).forEach(el => el.addEventListener('click', closeModal));
	if (cancelBtn) cancelBtn.addEventListener('click', () => { clearForm(); closeModal(); });

	// ==============================
	// PRÉ-VISUALIZAÇÃO DE IMAGEM
	// ==============================
	if (imagemInput) {
		imagemInput.addEventListener('change', function () {
			if (!(this.files && this.files[0])) return;
			const file = this.files[0];
			if (file.size > 1024 * 1024) {
				alert('A imagem excede 1MB. Selecione outra.');
				this.value = '';
				return;
			}
			const reader = new FileReader();
			reader.onload = function (e) {
				if (!perfilPreview) return;
				perfilPreview.innerHTML = `
					<div style="position: relative; display: inline-block;">
						<img src="${e.target.result}" alt="Imagem de Perfil" style="width:120px; height:120px; object-fit: contain;">
						<button id="delete-preview-btn"
							style="position: absolute; top: 0; right: 0; background: red; color:#fff; border:none;
										 cursor:pointer; width:22px; height:22px; border-radius:2px; font-size:15px; font-weight:bold;">X</button>
					</div>`;
				const del = document.getElementById('delete-preview-btn');
				if (del) del.addEventListener('click', () => {
					perfilPreview.innerHTML = '';
					imagemInput.value = '';
				});
			};
			reader.readAsDataURL(file);
		});
	}

	// ==============================
	// SALVAR (INSERT / UPDATE)
	// ==============================
	if (saveBtn) {
		saveBtn.addEventListener('click', () => {
			const id						= hiddenId ? hiddenId.value : '';
			const nome					= (nomeInput?.value || '').trim();
			const email				 = (emailInput?.value || '').trim();
			const senha				 = senhaInput?.value || '';
			const confirmaSenha = confirmaSenhaInput?.value || '';
			const situacao			= Array.from(situacaoInputs || []).find(i => i.checked)?.value || 'Ativo';
			const imagem				= imagemInput?.files ? imagemInput.files[0] : null;
			const nivelUsuario	= (nivelUsuarioSelect?.value || '').trim();

			if (!nome) { alert('O campo Nome é obrigatório.'); return; }
			if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { alert('E-mail inválido.'); return; }
			if (!isEditMode && (!senha || !confirmaSenha)) { alert('Preencha os campos de senha.'); return; }
			if (!isEditMode && senha !== confirmaSenha) { alert('As senhas não conferem.'); return; }
			if (nivelUsuario === '') { alert('Selecione o nível do usuário.'); return; }

			const formData = new FormData();
			formData.append('nome_usuario', nome);
			formData.append('email_usuario', email);
			formData.append('nivel_usuario', nivelUsuario);
			if (!isEditMode || (isEditMode && senha !== '')) formData.append('senha_usuario', senha);
			formData.append('situacao_usuario', situacao);
			if (imagem) formData.append('imagem_usuario', imagem);
			if (imageRemoved) formData.append('remove_foto', '1');

			const url = isEditMode
				? '/horarios/app/controllers/usuario/updateUsuario.php'
				: '/horarios/app/controllers/usuario/insertUsuario.php';
			if (isEditMode) formData.append('id_usuario', id);

			fetch(url, { method: 'POST', body: formData })
				.then(r => r.json())
				.then(data => {
					if (data.status === 'error' && data.message?.includes('já existe')) {
						showAlert(data.message);
					} else {
						alert(data.message || 'OK');
						if (data.status === 'success') {
							closeModal();
							fetchUsuarios();
						}
					}
				})
				.catch(console.error);
		});
	}

	// ==============================
	// AÇÕES NA TABELA
	// ==============================
	if (tableBody) {
		tableBody.addEventListener('click', e => {
			const btn = e.target.closest('button');
			if (!btn) return;

			// EDITAR
			if (btn.classList.contains('btn-edit')) {
				isEditMode = true;
				currentEditId = btn.dataset.id;

				fetch('/horarios/app/controllers/usuario/listUsuario.php')
					.then(r => r.json())
					.then(data => {
						if (data.status !== 'success') return;
						const user = (data.data || []).find(u => u.id_usuario == currentEditId);
						if (!user) return;

						if (hiddenId) hiddenId.value = user.id_usuario;
						if (nomeInput) nomeInput.value = user.nome_usuario || '';
						if (emailInput) emailInput.value = user.email_usuario || '';
						Array.from(situacaoInputs || []).forEach(r => (r.checked = (r.value === (user.situacao_usuario || 'Ativo'))));
						if (nivelUsuarioSelect) nivelUsuarioSelect.value = user.nivel_usuario || '';

						if (perfilPreview) {
							if (user.imagem_usuario) {
								let imageUrl = user.imagem_usuario;
								if (imageUrl.includes('localhost')) {
									imageUrl = imageUrl.replace('localhost', window.location.hostname);
								}
								perfilPreview.innerHTML = `
									<div style="position: relative; display: inline-block;">
										<img src="${imageUrl}" alt="Imagem de Perfil" style="width:120px; height:120px; object-fit: contain;">
										<button id="delete-foto-btn"
											style="position: absolute; top: 0; right: 0; background: red; color:#fff; border:none;
														 cursor:pointer; width:22px; height:22px; border-radius:2px; font-size:15px; font-weight:bold;">X</button>
									</div>`;
								const delBtn = document.getElementById('delete-foto-btn');
								if (delBtn) delBtn.addEventListener('click', () => {
									perfilPreview.innerHTML = '';
									imageRemoved = true;
								});
							} else {
								perfilPreview.innerHTML = '';
								imageRemoved = false;
							}
						}

						const title = document.getElementById('modal-title');
						if (title) title.innerText = 'Editar Usuário';
						if (saveBtn) saveBtn.innerText = 'Alterar';
						openModal();
					});

				return;
			}

			// DELETAR
			if (btn.classList.contains('btn-delete')) {
				currentDeleteId = btn.dataset.id;
				openGenericModal(modalDelete);
				return;
			}

			// IMPRIMIR (INDIVIDUAL)
			if (btn.classList.contains('btn-print')) {
				const tr = btn.closest('tr');
				const nomeUsuario = tr ? tr.querySelector('td:nth-child(1)')?.textContent.trim() : '';
				selectedUserId = btn.dataset.id; // importante!
				if (selectedUserInput) selectedUserInput.value = nomeUsuario || '';
				openGenericModal(modalPrint);
				return;
			}

			// VINCULAR NÍVEIS
			if (btn.classList.contains('btn-vincular-nivel')) {
				const userId = btn.dataset.id;
				const tr = btn.closest('tr');
				const userName = tr ? tr.querySelector('td:nth-child(1)')?.textContent.trim() : '';
				if (usuarioNomeVinculo) usuarioNomeVinculo.value = userName || '';
				// função definida em usuario-nivel.js
				if (typeof openUsuarioNivelModal === 'function') {
					openUsuarioNivelModal(userId);
				}
				return;
			}
		});
	}

	// ==============================
	// MODAL DE EXCLUSÃO
	// ==============================
	if (closeDeleteModalBtn) closeDeleteModalBtn.addEventListener('click', () => closeGenericModal(modalDelete));
	if (cancelDeleteBtn)		 cancelDeleteBtn.addEventListener('click', () => closeGenericModal(modalDelete));

	if (confirmDeleteBtn) {
		confirmDeleteBtn.addEventListener('click', function () {
			fetch('/horarios/app/controllers/usuario/deleteUsuario.php', {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: new URLSearchParams({ id: currentDeleteId })
			})
				.then(r => r.json())
				.then(data => {
					alert(data.message || 'OK');
					if (data.status === 'success') fetchUsuarios();
					closeGenericModal(modalDelete);
				})
				.catch(err => {
					console.error(err);
					closeGenericModal(modalDelete);
				});
		});
	}

	// ==============================
	// MODAL DE IMPRESSÃO INDIVIDUAL
	// ==============================
	if (closePrintModalBtn) closePrintModalBtn.addEventListener('click', () => closeGenericModal(modalPrint));
	if (btnCancelar)				btnCancelar.addEventListener('click', () => closeGenericModal(modalPrint));

	if (btnImprimir) {
		btnImprimir.addEventListener('click', () => {
			closeGenericModal(modalPrint);
			if (selectedUserId) {
				// abre somente o usuário selecionado
				window.open('/horarios/app/views/usuario.php?id_usuario=' + encodeURIComponent(selectedUserId), '_blank');
			} else {
				// fallback: se algo falhar, abre o geral
				window.open('/horarios/app/views/usuario.php', '_blank');
			}
		});
	}

	// ==============================
	// MODAL DE IMPRESSÃO GERAL
	// ==============================
	if (closePrintGeralModalBtn) closePrintGeralModalBtn.addEventListener('click', () => closeGenericModal(modalPrintGeral));
	if (btnCancelarGeral)				btnCancelarGeral.addEventListener('click', () => closeGenericModal(modalPrintGeral));
	if (btnImprimirGeral)				btnImprimirGeral.addEventListener('click', () => openGenericModal(modalPrintGeral));
	if (btnImprimirGeralConfirm) {
		btnImprimirGeralConfirm.addEventListener('click', () => {
			closeGenericModal(modalPrintGeral);
			window.open('/horarios/app/views/usuario-geral.php', '_blank');
		});
	}

	// ==============================
	// PESQUISA
	// ==============================
	const searchInput = document.getElementById('search-input');
	if (searchInput) {
		searchInput.addEventListener('input', function () {
			const q = this.value.toLowerCase();
			const rows = tableBody ? tableBody.querySelectorAll('tr') : [];
			let count = 0;
			rows.forEach(tr => {
				const nome	= tr.querySelector('td:nth-child(1)')?.textContent.toLowerCase() || '';
				const email = tr.querySelector('td:nth-child(2)')?.textContent.toLowerCase() || '';
				const visible = nome.includes(q) || email.includes(q);
				tr.style.display = visible ? '' : 'none';
				if (visible) count++;
			});
			if (noDataMessage) noDataMessage.style.display = count === 0 ? 'block' : 'none';
		});
	}

	// ==============================
	// VALIDAÇÃO DE IMAGEM PÚBLICA
	// ==============================
	window.validarImagem = function (input) {
		if (input.files && input.files[0]) {
			const fileName = input.files[0].name.toLowerCase();
			const ext = fileName.substring(fileName.lastIndexOf('.') + 1);
			const allowed = ['jpg', 'png', 'ico'];
			if (!allowed.includes(ext)) {
				alert('Formato de imagem inválido. Apenas JPG, PNG e ICO são permitidos.');
				input.value = '';
				return false;
			}
		}
		return true;
	};

	// ==============================
	// START
	// ==============================
	fetchUsuarios();
});
