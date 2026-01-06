// app/assets/js/instituicao.js
document.addEventListener('DOMContentLoaded', function() {
	// ==========================
	// 1) REFERÊNCIAS DO DOM
	// ==========================
	const modal = document.getElementById('modal-instituicao');
	const btnAdd = document.getElementById('btn-add');
	const closeModalElements = document.querySelectorAll('.close-modal');
	const cancelBtn = document.getElementById('cancel-btn');
	const saveBtn = document.getElementById('save-btn');

	// Modal de exclusão
	const modalDelete = document.getElementById('modal-delete');
	const closeDeleteModalBtn = document.getElementById('close-delete-modal');
	const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
	const cancelDeleteBtn = document.getElementById('cancel-delete-btn');

	// Tabela e mensagem "sem dados"
	const tableBody = document.getElementById('instituicaoTable');
	const noDataMessage = document.getElementById('no-data-message');

	// Modal de impressão (SEM filtros)
	const modalPrintInst = document.getElementById('modal-print');
	const closePrintInstModalBtn = document.getElementById('close-print-modal');
	const btnImprimirInst = document.getElementById('btn-imprimir');
	const btnCancelarInst = document.getElementById('btn-cancelar');
	const selectedInstiInput = document.getElementById('selected-insti');

	// Inputs do formulário de cadastro/edição
	const hiddenId = document.getElementById('instituicaoId');
	const nomeInput = document.getElementById('nome_instituicao');
	const cnpjInput = document.getElementById('cnpj_instituicao');
	const enderecoInput = document.getElementById('endereco_instituicao');
	const telefoneInput = document.getElementById('telefone_instituicao');
	const emailInput = document.getElementById('email_instituicao');
	const imagemInput = document.getElementById('imagem_instituicao'); // campo para logo/imagem

	// Variáveis de controle
	let isEditMode = false;
	let currentEditId = null;
	let currentPrintInstId = null; // Para saber qual instituição será impressa
	let logoRemoved = false;	   // Flag para indicar se a imagem foi removida no modo edição

	// ============================================================
	// 2) MÁSCARAS E VALIDAÇÕES (CNPJ, TELEFONE, EMAIL)
	// ============================================================
	function maskCNPJ(value) {
		let v = value.replace(/\D/g, '');
		v = v.substring(0, 14);
		if (v.length >= 3)  v = v.replace(/^(\d{2})(\d)/, '$1.$2');
		if (v.length >= 7)  v = v.replace(/^(\d{2}\.\d{3})(\d)/, '$1.$2');
		if (v.length >= 11) v = v.replace(/^(\d{2}\.\d{3}\.\d{3})(\d)/, '$1/$2');
		if (v.length >= 16) v = v.replace(/^(\d{2}\.\d{3}\.\d{3}\/\d{4})(\d)/, '$1-$2');
		return v;
	}

	function maskTelefone(value) {
		let v = value.replace(/\D/g, '');
		v = v.substring(0, 11);
		if (v.length <= 10) {
			// Formato (xx) xxxx-xxxx
			v = v.replace(/^(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
		} else {
			// Formato (xx) xxxxx-xxxx
			v = v.replace(/^(\d{2})(\d{5})(\d{0,4})/, '($1) $2-$3');
		}
		return v;
	}

	function isValidEmail(value) {
		const pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
		return pattern.test(value);
	}

	cnpjInput.addEventListener('input', function() {
		this.value = maskCNPJ(this.value);
	});

	telefoneInput.addEventListener('input', function() {
		this.value = maskTelefone(this.value);
	});

	// ============================================================
	// 3) LISTAR INSTITUIÇÕES (READ)
	// ============================================================
	function fetchInstituicoes() {
		return fetch('/horarios/app/controllers/instituicao/listInstituicao.php')
			.then(r => r.json())
			.then(data => {
				if (data.status === 'success') {
					renderTable(data.data);
				} else {
					console.error(data.message);
				}
			})
			.catch(err => console.error(err));
	}

	// Monta a tabela
	function renderTable(rows) {
		tableBody.innerHTML = '';
		if (!rows || rows.length === 0) {
			noDataMessage.style.display = 'block';
			return;
		}
		noDataMessage.style.display = 'none';

		rows.forEach(row => {
			const tr = document.createElement('tr');
			tr.dataset.id = row.id_instituicao;

			// Coluna: Nome da Instituição
			const tdNome = document.createElement('td');
			tdNome.textContent = row.nome_instituicao;
			tr.appendChild(tdNome);

			// Coluna: Ações
			const tdActions = document.createElement('td');

			// Botão Editar
			const btnEdit = document.createElement('button');
			btnEdit.classList.add('btn-edit');
			btnEdit.dataset.id = row.id_instituicao;
			btnEdit.innerHTML = `
				<span class="icon"><i class="fa-solid fa-pen-to-square"></i></span>
				<span class="text">Editar</span>`;
			tdActions.appendChild(btnEdit);

			// Botão Deletar
			const btnDelete = document.createElement('button');
			btnDelete.classList.add('btn-delete');
			btnDelete.dataset.id = row.id_instituicao;
			btnDelete.innerHTML = `
				<span class="icon"><i class="fa-solid fa-trash"></i></span>
				<span class="text">Deletar</span>`;
			tdActions.appendChild(btnDelete);

			// Botão Imprimir
			const btnPrint = document.createElement('button');
			btnPrint.classList.add('btn-print');
			btnPrint.dataset.id = row.id_instituicao;
			btnPrint.innerHTML = `
				<span class="icon"><i class="fa-solid fa-print"></i></span>
				<span class="text">Imprimir</span>`;
			tdActions.appendChild(btnPrint);

			tr.appendChild(tdActions);
			tableBody.appendChild(tr);
		});
	}

	// Carrega ao iniciar
	fetchInstituicoes();

	// ============================================================
	// 4) ABRIR/FECHAR MODAL (CADASTRO/EDIÇÃO)
	// ============================================================
	function openModal() {
		modal.style.display = 'block';
		modal.classList.remove('fade-out');
		modal.classList.add('fade-in');

		const content = modal.querySelector('.modal-content');
		content.classList.remove('slide-up');
		content.classList.add('slide-down');
	}

	function closeModal() {
		const content = modal.querySelector('.modal-content');
		content.classList.remove('slide-down');
		content.classList.add('slide-up');

		modal.classList.remove('fade-in');
		modal.classList.add('fade-out');

		setTimeout(() => {
			modal.style.display = 'none';
			content.classList.remove('slide-up');
			modal.classList.remove('fade-out');
			isEditMode = false;
			currentEditId = null;
			logoRemoved = false; // Reseta flag de remoção de imagem
		}, 300);
	}

	function clearForm() {
		hiddenId.value = '';
		nomeInput.value = '';
		cnpjInput.value = '';
		enderecoInput.value = '';
		telefoneInput.value = '';
		emailInput.value = '';
		imagemInput.value = '';

		// Limpar preview de logo
		const logoPreview = document.getElementById('logo-preview');
		if (logoPreview) {
			logoPreview.innerHTML = '';
		}
		logoRemoved = false;
	}

	// Botão Adicionar => abre modal no modo "novo"
	btnAdd.addEventListener('click', () => {
		isEditMode = false;
		document.getElementById('modal-title').innerText = 'Adicionar Instituição';
		saveBtn.innerText = 'Salvar';
		clearForm();
		openModal();
	});

	// Fecha modal ao clicar no "X"
	closeModalElements.forEach(el => {
		el.addEventListener('click', closeModal);
	});

	// Botão Cancelar
	cancelBtn.addEventListener('click', () => {
		if (!isEditMode) {
			clearForm();
		}
		closeModal();
	});

	// ============================================================
	// 5) SALVAR (INSERT OU UPDATE)
	// ============================================================
	saveBtn.addEventListener('click', () => {
		const id		= hiddenId.value;
		const nome	  = nomeInput.value.trim();
		const cnpj	  = cnpjInput.value.trim();
		const endereco  = enderecoInput.value.trim();
		const telefone  = telefoneInput.value.trim();
		const email	 = emailInput.value.trim();
		const imagem	= imagemInput.files[0];

		// Validações
		if (!nome) {
			alert('O campo Nome é obrigatório.');
			return;
		}
		if (!cnpj) {
			alert('O campo CNPJ é obrigatório.');
			return;
		}
		if (email && !isValidEmail(email)) {
			alert('E-mail inválido. Formato: usuario@dominio.com');
			return;
		}

		const formData = new FormData();
		formData.append('nome_instituicao', nome);
		formData.append('cnpj_instituicao', cnpj);
		formData.append('endereco_instituicao', endereco);
		formData.append('telefone_instituicao', telefone);
		formData.append('email_instituicao', email);
		if (imagem) {
			formData.append('imagem_instituicao', imagem);
		}
		if (logoRemoved) {
			formData.append('remove_logo', '1');
		}

		if (isEditMode) {
			// UPDATE
			formData.append('id_instituicao', id);
			fetch('/horarios/app/controllers/instituicao/updateInstituicao.php', {
				method: 'POST',
				body: formData
			})
			.then(r => r.json())
			.then(data => {
				alert(data.message);
				if (data.status === 'success') {
					closeModal();
					fetchInstituicoes();
				}
			})
			.catch(err => console.error(err));
		} else {
			// INSERT
			fetch('/horarios/app/controllers/instituicao/insertInstituicao.php', {
				method: 'POST',
				body: formData
			})
			.then(r => r.json())
			.then(data => {
				alert(data.message);
				if (data.status === 'success') {
					closeModal();
					fetchInstituicoes();
				}
			})
			.catch(err => console.error(err));
		}
	});

	// ============================================================
	// 6) AÇÕES NA TABELA (EDITAR, DELETAR, IMPRIMIR)
	// ============================================================
	tableBody.addEventListener('click', e => {
		const btn = e.target.closest('button');
		if (!btn) return;

		if (btn.classList.contains('btn-edit')) {
			// EDITAR
			isEditMode = true;
			currentEditId = btn.dataset.id;

			// Carrega as instituições do servidor e filtra a que será editada
			fetch('/horarios/app/controllers/instituicao/listInstituicao.php')
				.then(r => r.json())
				.then(data => {
					if (data.status === 'success') {
						const found = data.data.find(item => item.id_instituicao == currentEditId);
						if (found) {
							hiddenId.value		 = found.id_instituicao;
							nomeInput.value		= found.nome_instituicao;
							cnpjInput.value		= found.cnpj_instituicao;
							enderecoInput.value	= found.endereco_instituicao ?? '';
							telefoneInput.value	= found.telefone_instituicao ?? '';
							emailInput.value	   = found.email_instituicao ?? '';
							imagemInput.value	  = ''; // Limpa campo de imagem

							const logoPreview = document.getElementById('logo-preview');
							if (logoPreview) {
								// Se já houver alguma imagem salva
								if (found.imagem_instituicao) {
									// Exibir preview com botão para remover
									logoPreview.innerHTML = `
										<div style="position: relative; display: inline-block;">
											<img src="${found.imagem_instituicao}" alt="Logo da Instituição" 
												 style="width:120px; height:120px; object-fit: contain;">
											<button id="delete-logo-btn" 
													style="position: absolute; top: 0; right: 0; 
														   background: red; color: #fff; border: none; 
														   cursor: pointer; width: 22px; height: 22px; 
														   border-radius: 2px; font-size: 15px; font-weight: bold;">
												X
											</button>
										</div>
									`;
									// Ao clicar no "X", remove o preview e seta logoRemoved
									document.getElementById('delete-logo-btn').addEventListener('click', function() {
										logoPreview.innerHTML = '';
										logoRemoved = true;
									});
								} else {
									logoPreview.innerHTML = '';
									logoRemoved = false;
								}
							}

							document.getElementById('modal-title').innerText = 'Editar Instituição';
							saveBtn.innerText = 'Alterar';
							openModal();
						}
					}
				});

		} else if (btn.classList.contains('btn-delete')) {
			// DELETAR
			currentEditId = btn.dataset.id;
			openDeleteModal();

		} else if (btn.classList.contains('btn-print')) {
			// IMPRIMIR
			currentPrintInstId = btn.dataset.id;
			// Captura o nome para exibir no modal
			const instName = btn.closest('tr').querySelector('td:nth-child(1)').textContent;
			selectedInstiInput.value = instName;
			openPrintInstModal();
		}
	});

	// ============================================================
	// 7) MODAL DE EXCLUSÃO (com animação)
	// ============================================================
	function openDeleteModal() {
		modalDelete.style.display = 'block';
		modalDelete.classList.remove('fade-out');
		modalDelete.classList.add('fade-in');

		const content = modalDelete.querySelector('.modal-content');
		content.classList.remove('slide-up');
		content.classList.add('slide-down');
	}

	function closeDeleteModal() {
		const content = modalDelete.querySelector('.modal-content');
		content.classList.remove('slide-down');
		content.classList.add('slide-up');
		modalDelete.classList.remove('fade-in');
		modalDelete.classList.add('fade-out');

		setTimeout(() => {
			modalDelete.style.display = 'none';
			content.classList.remove('slide-up');
			modalDelete.classList.remove('fade-out');
		}, 300);
	}

	closeDeleteModalBtn.addEventListener('click', closeDeleteModal);
	cancelDeleteBtn.addEventListener('click', closeDeleteModal);

	confirmDeleteBtn.addEventListener('click', () => {
		fetch('/horarios/app/controllers/instituicao/deleteInstituicao.php', {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: new URLSearchParams({ id: currentEditId })
		})
		.then(r => r.json())
		.then(data => {
			alert(data.message);
			if (data.status === 'success') {
				const row = document.querySelector(`tr[data-id="${currentEditId}"]`);
				if (row) row.remove();
				if (tableBody.children.length === 0) {
					noDataMessage.style.display = 'block';
				}
			}
			closeDeleteModal();
		})
		.catch(err => console.error(err));
	});

	// ============================================================
	// 8) PESQUISA SIMPLES
	// ============================================================
	document.getElementById('search-input').addEventListener('input', function() {
		const searchValue = this.value.toLowerCase();
		const rows = tableBody.querySelectorAll('tr');
		let count = 0;

		rows.forEach(tr => {
			const tdNome = tr.querySelector('td:nth-child(1)').textContent.toLowerCase();
			if (tdNome.includes(searchValue)) {
				tr.style.display = '';
				count++;
			} else {
				tr.style.display = 'none';
			}
		});
		noDataMessage.style.display = count === 0 ? 'block' : 'none';
	});

	// ============================================================
	// 9) VALIDAR IMAGEM (EXTENSÕES)
	// ============================================================
	window.validarImagem = function(input) {
		if (input.files && input.files[0]) {
			const file = input.files[0];
			const fileName = file.name.toLowerCase();
			const ext = fileName.substring(fileName.lastIndexOf('.') + 1);
			const extensoesPermitidas = ['jpg', 'ico', 'png'];
			if (!extensoesPermitidas.includes(ext)) {
				alert("Formato de imagem inválido. Apenas JPG, ICO e PNG são permitidos.");
				input.value = ""; // Limpa o campo
				return false;
			}
		}
		return true;
	};

	// ============================================================
	// 10) MODAL DE IMPRESSÃO (SEM FILTROS)
	// ============================================================
	function openPrintInstModal() {
		modalPrintInst.style.display = 'block';
		modalPrintInst.classList.remove('fade-out');
		modalPrintInst.classList.add('fade-in');

		const content = modalPrintInst.querySelector('.modal-content');
		content.classList.remove('slide-up');
		content.classList.add('slide-down');
	}

	function closePrintInstModal() {
		const content = modalPrintInst.querySelector('.modal-content');
		content.classList.remove('slide-down');
		content.classList.add('slide-up');
		modalPrintInst.classList.remove('fade-in');
		modalPrintInst.classList.add('fade-out');

		setTimeout(() => {
			modalPrintInst.style.display = 'none';
			content.classList.remove('slide-up');
			modalPrintInst.classList.remove('fade-out');
		}, 300);
	}

	btnCancelarInst.addEventListener('click', closePrintInstModal);
	closePrintInstModalBtn.addEventListener('click', closePrintInstModal);

	// Ao confirmar impressão, abrimos a URL no formato ?id_inst=...
	btnImprimirInst.addEventListener('click', () => {
		// Ajuste conforme seu controlador de PDF
		let url = '/horarios/app/views/instituicao.php?id_inst=' + currentPrintInstId;
		window.open(url, '_blank');
		closePrintInstModal();
	});

	// Se quiser impedir que clique fora feche o modal:
	window.addEventListener('click', e => {
		// if (e.target === modal) closeModal();
		// if (e.target === modalPrintInst) closePrintInstModal();
		// if (e.target === modalDelete) closeDeleteModal();
	});
});
