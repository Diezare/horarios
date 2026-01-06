// app/assets/js/categoria.js
document.addEventListener('DOMContentLoaded', function () {
	/* =================================================================
		 0) Helpers
	================================================================= */
	// Coloca placeholder e limpa o select (para selects populados via fetch)
	function setPlaceholderAndClear(selectEl, label, value = '') {
		if (!selectEl) return;
		selectEl.innerHTML = '';
		const opt = document.createElement('option');
		opt.value = value;
		opt.textContent = label;
		selectEl.appendChild(opt);
		selectEl.value = value;
	}

	/* =================================================================
		 1) Referências do Modal de Cadastro/Edição
	================================================================= */
	const modal = document.getElementById('modal-categoria');
	const btnAdd = document.getElementById('btn-add-categoria');
	const closeModalElements = document.querySelectorAll('#modal-categoria .close-modal');
	const cancelBtn = document.getElementById('cancel-btn');
	const saveBtn = document.getElementById('save-btn');

	// Modal de exclusão
	const modalDelete = document.getElementById('modal-delete');
	const closeDeleteModalBtn = document.getElementById('close-delete-modal');
	const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
	const cancelDeleteBtn = document.getElementById('cancel-delete-btn');

	// Tabela de Categorias
	const categoriaTableBody	= document.getElementById('categoriaTable');
	const noDataMessage			 = document.getElementById('no-data-message');

	// Form fields
	const inputCategoriaId		= document.getElementById('categoriaId');
	const selectModalidade		= document.getElementById('selectModalidade');
	const inputNomeCategoria	= document.getElementById('nomeCategoria');
	const inputDescCategoria	= document.getElementById('descricaoCategoria');

	let isEditMode		 = false;
	let currentEditId	= null;
	let currentPrintId = null; // Usado na impressão individual

	/* =================================================================
		 2) Modal de Impressão (Individual)
	================================================================= */
	const modalPrintCategoria = document.getElementById('modal-print-categoria');
	const closePrintCategoriaBtn = document.getElementById('close-print-categoria');
	const btnImprimirCategoria = document.getElementById('btn-imprimir');
	const btnCancelarPrintCategoria = document.getElementById('btn-cancelar');
	const selectedCategoriaInput = document.getElementById('selected-categoria');

	const chkModalidade	= document.getElementById('chk-modalidade');
	const chkProfessores = document.getElementById('chk-professores'); // pode ser null se não existir

	/* =================================================================
		 3) Modal de Impressão (Geral)
	================================================================= */
	const btnImprimirGeral						= document.getElementById('btnImprimir');
	const modalPrintCategoriaGeral		= document.getElementById('modal-print-categoria-geral');
	const closePrintCategoriaGeralBtn = document.getElementById('close-print-categoria-geral');
	const btnImprimirCategoriaGeral	 = document.getElementById('btn-imprimir-geral-confirm');
	const btnCancelarCategoriaGeral	 = document.getElementById('btn-cancelar-geral');
	const selectCategoriaGeral				= document.getElementById('select-categoria-geral');
	const chkModalidadeGeral = document.getElementById('chk-modalidade-geral');
	const chkProfessoresGeral = document.getElementById('chk-professores-geral'); // novo checkbox

	/* =================================================================
		 4) Ícones de Ordenação (Modalidade e Categoria)
	================================================================= */
	const sortModalidadeAsc	= document.getElementById('sort-modalidade-asc');
	const sortModalidadeDesc = document.getElementById('sort-modalidade-desc');
	const sortCategoriaAsc	 = document.getElementById('sort-categoria-asc');
	const sortCategoriaDesc	= document.getElementById('sort-categoria-desc');

	function sortTableByModalidade(asc = true) {
		const rows = Array.from(categoriaTableBody.querySelectorAll('tr'));
		rows.sort((a, b) => {
			const valA = a.querySelector('td:nth-child(1)').textContent.toLowerCase();
			const valB = b.querySelector('td:nth-child(1)').textContent.toLowerCase();
			return asc ? valA.localeCompare(valB) : valB.localeCompare(valA);
		});
		categoriaTableBody.innerHTML = '';
		rows.forEach(row => categoriaTableBody.appendChild(row));
	}

	function sortTableByCategoria(asc = true) {
		const rows = Array.from(categoriaTableBody.querySelectorAll('tr'));
		rows.sort((a, b) => {
			const valA = a.querySelector('td:nth-child(2)').textContent.toLowerCase();
			const valB = b.querySelector('td:nth-child(2)').textContent.toLowerCase();
			return asc ? valA.localeCompare(valB) : valB.localeCompare(valA);
		});
		categoriaTableBody.innerHTML = '';
		rows.forEach(row => categoriaTableBody.appendChild(row));
	}

	if (sortModalidadeAsc)	sortModalidadeAsc.addEventListener('click', () => sortTableByModalidade(true));
	if (sortModalidadeDesc) sortModalidadeDesc.addEventListener('click', () => sortTableByModalidade(false));
	if (sortCategoriaAsc)	 sortCategoriaAsc.addEventListener('click', () => sortTableByCategoria(true));
	if (sortCategoriaDesc)	sortCategoriaDesc.addEventListener('click', () => sortTableByCategoria(false));

	/* =================================================================
		 5) Carregar e Exibir Categorias
	================================================================= */
	function loadCategorias() {
		fetch('/horarios/app/controllers/categoria/listAllCategoria.php')
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

	function renderTable(rows) {
		categoriaTableBody.innerHTML = '';
		if (!rows || rows.length === 0) {
			noDataMessage.style.display = 'block';
			return;
		}
		noDataMessage.style.display = 'none';

		rows.forEach(row => {
			const tr = document.createElement('tr');
			tr.dataset.id = row.id_categoria;

			// 1ª col: Modalidade
			const tdModalidade = document.createElement('td');
			tdModalidade.textContent = row.nome_modalidade;
			tr.appendChild(tdModalidade);

			// 2ª col: Categoria
			const tdCat = document.createElement('td');
			tdCat.textContent = row.nome_categoria;
			tr.appendChild(tdCat);

			// 3ª col: Ações
			const tdActions = document.createElement('td');

			// Botão Editar
			const btnEdit = document.createElement('button');
			btnEdit.classList.add('btn-edit');
			btnEdit.dataset.id = row.id_categoria;
			btnEdit.innerHTML = `
				<span class="icon"><i class="fa-solid fa-pen-to-square"></i></span>
				<span class="text">Editar</span>`;
			tdActions.appendChild(btnEdit);

			// Botão Deletar
			const btnDelete = document.createElement('button');
			btnDelete.classList.add('btn-delete');
			btnDelete.dataset.id = row.id_categoria;
			btnDelete.innerHTML = `
				<span class="icon"><i class="fa-solid fa-trash"></i></span>
				<span class="text">Deletar</span>`;
			tdActions.appendChild(btnDelete);

			// Botão Imprimir
			const btnPrint = document.createElement('button');
			btnPrint.classList.add('btn-print');
			btnPrint.dataset.id = row.id_categoria;
			btnPrint.innerHTML = `
				<span class="icon"><i class="fa-solid fa-print"></i></span>
				<span class="text">Imprimir</span>`;
			tdActions.appendChild(btnPrint);

			// Botão Vincular Professores
			const btnProf = document.createElement('button');
			btnProf.classList.add('btn-link-professors');
			btnProf.dataset.id	 = row.id_categoria;
			btnProf.dataset.nome = row.nome_categoria;
			btnProf.dataset.modalidade = row.nome_modalidade;
			btnProf.innerHTML = `
				<span class="icon"><i class="fa-solid fa-chalkboard-user"></i></span>
				<span class="text">Professores</span>`;
			tdActions.appendChild(btnProf);

			tr.appendChild(tdActions);
			categoriaTableBody.appendChild(tr);
		});
	}

	/* =================================================================
		 6) Preencher SELECT de Modalidade no Formulário (Cadastro/Edição)
	================================================================= */
	// Agora com placeholder "-- Selecione a Modalidade --" SEM cair na primeira opção
	function loadModalidadesInSelect(preselectId = '') {
		// coloca placeholder antes de carregar
		setPlaceholderAndClear(selectModalidade, '-- Selecione a Modalidade --', '');

		fetch('/horarios/app/controllers/modalidade/listAllModalidade.php')
			.then(r => r.json())
			.then(data => {
				if (data.status === 'success') {
					(data.data || []).forEach(mod => {
						const option = document.createElement('option');
						option.value = mod.id_modalidade;
						option.textContent = mod.nome_modalidade;
						selectModalidade.appendChild(option);
					});

					// se for modo edição, seleciona a modalidade vinda do registro
					if (preselectId) {
						selectModalidade.value = String(preselectId);
					}
				} else {
					console.error(data.message);
				}
			})
			.catch(err => console.error(err));
	}

	/* =================================================================
		 7) Modal de Cadastro/Edição
	================================================================= */
	function openModal() {
		modal.style.display = 'block';
		modal.classList.remove('fade-out');
		modal.classList.add('fade-in');

		const content = modal.querySelector('.modal-content');
		content.classList.remove('slide-up');
		content.classList.add('slide-down');

		if (!isEditMode) {
			clearForm();
			document.getElementById('modal-title').innerText = 'Adicionar Categoria';
			saveBtn.innerText = 'Salvar';
		}
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
			isEditMode		= false;
			currentEditId = null;
		}, 300);
	}

	function clearForm() {
		inputCategoriaId.value	 = '';
		inputNomeCategoria.value = '';
		inputDescCategoria.value = '';

		// garante placeholder ao abrir para adicionar
		setPlaceholderAndClear(selectModalidade, '-- Selecione a Modalidade --', '');
	}

	btnAdd.addEventListener('click', () => {
		isEditMode = false;
		openModal();
		clearForm();
		// carrega modalidades mantendo o placeholder no topo
		loadModalidadesInSelect();
	});

	closeModalElements.forEach(el => el.addEventListener('click', closeModal));
	cancelBtn.addEventListener('click', () => {
		clearForm();
		closeModal();
	});

	/* =================================================================
		 8) Salvar (Insert / Update)
	================================================================= */
	saveBtn.addEventListener('click', () => {
		const idCat	 = inputCategoriaId.value;
		const idMod	 = selectModalidade.value;
		const nomeCat = inputNomeCategoria.value.trim();
		const descCat = inputDescCategoria.value.trim();

		// impede salvar quando está no placeholder (value = '')
		if (!idMod || parseInt(idMod) <= 0 || !nomeCat) {
			alert('Selecione a modalidade e preencha o nome da categoria.');
			return;
		}

		const data = new URLSearchParams({
			id_categoria:	 idCat,
			id_modalidade:	idMod,
			nome_categoria: nomeCat,
			descricao:			descCat
		});

		if (isEditMode) {
			// UPDATE
			fetch('/horarios/app/controllers/categoria/updateCategoria.php', {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: data
			})
				.then(r => r.json())
				.then(resp => {
					alert(resp.message);
					if (resp.status === 'success') {
						closeModal();
						loadCategorias();
					}
				})
				.catch(err => console.error(err));
		} else {
			// INSERT
			fetch('/horarios/app/controllers/categoria/insertCategoria.php', {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: data
			})
				.then(r => r.json())
				.then(resp => {
					alert(resp.message);
					if (resp.status === 'success') {
						closeModal();
						loadCategorias();
					}
				})
				.catch(err => console.error(err));
		}
	});

	/* =================================================================
		 9) Ações na Tabela (Editar, Deletar, Imprimir, Professores)
	================================================================= */
	categoriaTableBody.addEventListener('click', e => {
		const btn = e.target.closest('button');
		if (!btn) return;

		const id = btn.dataset.id; // ID da categoria
		if (btn.classList.contains('btn-edit')) {
			// EDITAR
			isEditMode		= true;
			currentEditId = id;

			fetch('/horarios/app/controllers/categoria/listAllCategoria.php')
				.then(r => r.json())
				.then(data => {
					if (data.status === 'success') {
						const found = data.data.find(x => x.id_categoria == currentEditId);
						if (found) {
							inputCategoriaId.value	 = found.id_categoria;
							inputNomeCategoria.value = found.nome_categoria;
							inputDescCategoria.value = found.descricao || '';

							document.getElementById('modal-title').innerText = 'Editar Categoria';
							saveBtn.innerText = 'Alterar';

							// coloca placeholder primeiro; depois pré-seleciona via preselectId
							setPlaceholderAndClear(selectModalidade, '-- Selecione a Modalidade --', '');
							loadModalidadesInSelect(found.id_modalidade);

							openModal();
						}
					}
				})
				.catch(err => console.error(err));

		} else if (btn.classList.contains('btn-delete')) {
			// DELETAR
			currentEditId = id;
			openDeleteModal();

		} else if (btn.classList.contains('btn-print')) {
			// IMPRIMIR INDIVIDUAL
			currentPrintId = id;

			const row = btn.closest('tr');
			const mod = row.querySelector('td:nth-child(1)').textContent; // Modalidade
			const cat = row.querySelector('td:nth-child(2)').textContent; // Categoria
			selectedCategoriaInput.value = mod + ' - ' + cat;

			if (chkModalidade) chkModalidade.checked = false;
			if (chkProfessores) chkProfessores.checked = false;

			openPrintModal();

		} else if (btn.classList.contains('btn-link-professors')) {
			// VINCULAR PROFESSORES (seu fluxo customizado)
			const nomeCat = btn.dataset.nome || '';
			const nomeMod = btn.dataset.modalidade || '';
			if (typeof openVincularProfModal === 'function') {
				openVincularProfModal(id, nomeMod, nomeCat);
			}
		}
	});

	/* =================================================================
		 10) Modal de Exclusão
	================================================================= */
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
		fetch('/horarios/app/controllers/categoria/deleteCategoria.php', {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: new URLSearchParams({ id: currentEditId })
		})
			.then(r => r.json())
			.then(resp => {
				alert(resp.message);
				if (resp.status === 'success') {
					const row = document.querySelector(`tr[data-id="${currentEditId}"]`);
					if (row) row.remove();
					if (categoriaTableBody.children.length === 0) {
						noDataMessage.style.display = 'block';
					}
				}
				closeDeleteModal();
			})
			.catch(err => console.error(err));
	});

	/* =================================================================
		 11) Modal de Impressão (Individual)
	================================================================= */
	function openPrintModal() {
		modalPrintCategoria.style.display = 'block';
		modalPrintCategoria.classList.remove('fade-out');
		modalPrintCategoria.classList.add('fade-in');

		const content = modalPrintCategoria.querySelector('.modal-content');
		content.classList.remove('slide-up');
		content.classList.add('slide-down');
	}

	function closePrintModal() {
		const content = modalPrintCategoria.querySelector('.modal-content');
		content.classList.remove('slide-down');
		content.classList.add('slide-up');
		modalPrintCategoria.classList.remove('fade-in');
		modalPrintCategoria.classList.add('fade-out');

		setTimeout(() => {
			modalPrintCategoria.style.display = 'none';
			content.classList.remove('slide-up');
			modalPrintCategoria.classList.remove('fade-out');

			if (chkModalidade) chkModalidade.checked = false;
			if (chkProfessores) chkProfessores.checked = false;
		}, 300);
	}

	btnCancelarPrintCategoria.addEventListener('click', closePrintModal);
	closePrintCategoriaBtn.addEventListener('click', closePrintModal);

	btnImprimirCategoria.addEventListener('click', () => {
		let url = '/horarios/app/views/categoria.php?id_categoria=' + currentPrintId;

		if (chkModalidade && chkModalidade.checked) {
			url += '&modalidade=1';
		}
		if (chkProfessores && chkProfessores.checked) {
			url += '&professores=1';
		}
		window.open(url, '_blank');
		closePrintModal();
	});

	/* =================================================================
		 12) Modal de Impressão (Geral)
	================================================================= */
	btnImprimirGeral.addEventListener('click', () => {
		fetch('/horarios/app/controllers/categoria/listAllCategoria.php')
			.then(r => r.json())
			.then(data => {
				if (data.status === 'success') {
					selectCategoriaGeral.innerHTML = '<option value="todas">Todas</option>';
					(data.data || []).forEach(cat => {
						const displayText = cat.nome_modalidade + ' - ' + cat.nome_categoria;
						const option = document.createElement('option');
						option.value = cat.id_categoria;
						option.textContent = displayText;
						selectCategoriaGeral.appendChild(option);
					});
				}
			})
			.catch(err => console.error(err));

		if (chkModalidadeGeral) chkModalidadeGeral.checked = false;
		if (chkProfessoresGeral) chkProfessoresGeral.checked = false;

		openPrintModalGeral();
	});

	function openPrintModalGeral() {
		modalPrintCategoriaGeral.style.display = 'block';
		modalPrintCategoriaGeral.classList.remove('fade-out');
		modalPrintCategoriaGeral.classList.add('fade-in');

		const content = modalPrintCategoriaGeral.querySelector('.modal-content');
		content.classList.remove('slide-up');
		content.classList.add('slide-down');
	}

	function closePrintModalGeral() {
		const content = modalPrintCategoriaGeral.querySelector('.modal-content');
		content.classList.remove('slide-down');
		content.classList.add('slide-up');
		modalPrintCategoriaGeral.classList.remove('fade-in');
		modalPrintCategoriaGeral.classList.add('fade-out');

		setTimeout(() => {
			modalPrintCategoriaGeral.style.display = 'none';
			content.classList.remove('slide-up');
			modalPrintCategoriaGeral.classList.remove('fade-out');

			if (chkModalidadeGeral) chkModalidadeGeral.checked = false;
			if (chkProfessoresGeral) chkProfessoresGeral.checked = false;
		}, 300);
	}

	btnCancelarCategoriaGeral.addEventListener('click', closePrintModalGeral);
	closePrintCategoriaGeralBtn.addEventListener('click', closePrintModalGeral);

	btnImprimirCategoriaGeral.addEventListener('click', () => {
		let catValue = selectCategoriaGeral.value; // ex.: id_categoria ou "todas"
		let url = '/horarios/app/views/categoria-geral.php?categoria=' + encodeURIComponent(catValue);

		if (chkModalidadeGeral && chkModalidadeGeral.checked) {
			url += '&modalidade=1';
		}
		if (chkProfessoresGeral && chkProfessoresGeral.checked) {
			url += '&professores=1';
		}
		window.open(url, '_blank');
		closePrintModalGeral();
	});

	/* =================================================================
		 13) Filtro de Pesquisa na Tabela
	================================================================= */
	document.getElementById('search-input').addEventListener('input', function () {
		const searchValue = this.value.toLowerCase();
		const rows = categoriaTableBody.querySelectorAll('tr');
		let count = 0;

		rows.forEach(tr => {
			const modalidade = tr.querySelector('td:nth-child(1)').textContent.toLowerCase();
			const categoria	= tr.querySelector('td:nth-child(2)').textContent.toLowerCase();
			if (modalidade.includes(searchValue) || categoria.includes(searchValue)) {
				tr.style.display = '';
				count++;
			} else {
				tr.style.display = 'none';
			}
		});
		noDataMessage.style.display = (count === 0) ? 'block' : 'none';
	});

	/* =================================================================
		 14) Inicialização
	================================================================= */
	function init() {
		loadCategorias();
		// garante placeholder no select mesmo antes de abrir modal
		setPlaceholderAndClear(selectModalidade, '-- Selecione a Modalidade --', '');
	}
	init();
});
