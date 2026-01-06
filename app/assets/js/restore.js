// app/assets/js/restore.js
document.addEventListener('DOMContentLoaded', function() {
	// Elementos principais
	const btnRestore = document.getElementById('btn-restore');
	const tbodyRestore = document.getElementById('tbodyRestore');
	const noDataMessage = document.getElementById('noDataMessage');

	// Elementos de paginação
	const paginationContainer = document.getElementById('paginationContainer');
	const prevPageBtn = document.getElementById('prevPageBtn');
	const nextPageBtn = document.getElementById('nextPageBtn');
	const paginationStatus = document.getElementById('paginationStatus');

	// Elementos de ordenação
	const sortDataAsc = document.getElementById('sort-data-asc');
	const sortDataDesc = document.getElementById('sort-data-desc');
	const sortArquivoAsc = document.getElementById('sort-arquivo-asc');
	const sortArquivoDesc = document.getElementById('sort-arquivo-desc');

	// Variáveis de controle
	let allLogs = [];
	let currentPage = 1;
	const itemsPerPage = 25;
	let currentSortField = null;
	let currentSortDirection = null;

	// Função de formatação de data
	function formatDateTime(isoString) {
		const options = {
			day: '2-digit',
			month: '2-digit',
			year: 'numeric',
			hour: '2-digit',
			minute: '2-digit',
			second: '2-digit',
			hour12: false
		};

		try {
			let formattedDateTime = new Date(isoString).toLocaleString('pt-BR', options);
			return formattedDateTime.replace(',', ' -');
		} catch {
			return 'Data inválida';
		}
	}

	// Função principal para carregar logs
	function loadRestoreLogs() {
		fetch('/horarios/app/controllers/restore/listRestore.php')
			.then(response => response.json())
			.then(data => {
				if (data.status === 'success' && data.data.length > 0) {
					allLogs = data.data;
					sortLogs(currentSortField || 'data', currentSortDirection || 'desc');
					noDataMessage.style.display = 'none';
				} else {
					handleNoData();
				}
			})
			.catch(err => {
				console.error(err);
				handleNoData();
			});
	}

	// Função de ordenação
	function sortLogs(field, direction) {
		currentSortField = field;
		currentSortDirection = direction;

		allLogs.sort((a, b) => {
			if (field === 'data') {
				const dateA = new Date(a.data_restore);
				const dateB = new Date(b.data_restore);
				return direction === 'asc' ? dateA - dateB : dateB - dateA;
			}
			return direction === 'asc' 
				? a.arquivo.localeCompare(b.arquivo) 
				: b.arquivo.localeCompare(a.arquivo);
		});

		updateSortUI();
		renderTable();
	}

	// Atualiza a UI de ordenação
	function updateSortUI() {
		document.querySelectorAll('.sort-btn').forEach(btn => btn.classList.remove('active-sort'));
		
		if (currentSortField) {
			const activeBtn = currentSortDirection === 'asc'
				? document.getElementById(`sort-${currentSortField}-asc`)
				: document.getElementById(`sort-${currentSortField}-desc`);
			
			if (activeBtn) activeBtn.classList.add('active-sort');
		}
	}

	// Renderiza a tabela
	function renderTable() {
		tbodyRestore.innerHTML = '';
		const startIndex = (currentPage - 1) * itemsPerPage;
		const pageLogs = allLogs.slice(startIndex, startIndex + itemsPerPage);

		pageLogs.forEach(log => {
			const tr = document.createElement('tr');
			
			const tdData = document.createElement('td');
			tdData.textContent = formatDateTime(log.data_restore);
			tr.appendChild(tdData);

			const tdArquivo = document.createElement('td');
			tdArquivo.textContent = log.arquivo;
			tr.appendChild(tdArquivo);

			tbodyRestore.appendChild(tr);
		});

		updatePagination();
	}

	// Atualiza a paginação
	function updatePagination() {
		const totalPages = Math.ceil(allLogs.length / itemsPerPage);
		paginationStatus.textContent = `Página ${currentPage} de ${totalPages}`;
		prevPageBtn.disabled = (currentPage <= 1);
		nextPageBtn.disabled = (currentPage >= totalPages);
		paginationContainer.style.display = totalPages > 1 ? 'block' : 'none';
	}

	// Manipulação de dados não encontrados
	function handleNoData() {
		tbodyRestore.innerHTML = '';
		noDataMessage.style.display = 'block';
		paginationContainer.style.display = 'none';
	}

	// Event Listeners
	prevPageBtn.addEventListener('click', () => {
		if (currentPage > 1) {
			currentPage--;
			renderTable();
		}
	});

	nextPageBtn.addEventListener('click', () => {
		const totalPages = Math.ceil(allLogs.length / itemsPerPage);
		if (currentPage < totalPages) {
			currentPage++;
			renderTable();
		}
	});

	// Eventos de ordenação
	sortDataAsc.addEventListener('click', () => sortLogs('data', 'asc'));
	sortDataDesc.addEventListener('click', () => sortLogs('data', 'desc'));
	sortArquivoAsc.addEventListener('click', () => sortLogs('arquivo', 'asc'));
	sortArquivoDesc.addEventListener('click', () => sortLogs('arquivo', 'desc'));

	// Modal de restauração
	const modalRestore = document.getElementById('modal-restore');
	const closeModalRestoreBtn = document.getElementById('closeModalRestore');
	const confirmRestoreBtn = document.getElementById('confirmRestoreBtn');
	const cancelRestoreBtn = document.getElementById('cancelRestoreBtn');
	const restoreForm = document.getElementById('restoreForm');

	// Controle do modal
	btnRestore.addEventListener('click', () => {
		modalRestore.style.display = 'block';
		modalRestore.classList.add('fade-in');
	});

	[closeModalRestoreBtn, cancelRestoreBtn].forEach(btn => {
		btn.addEventListener('click', () => {
			modalRestore.classList.remove('fade-in');
			modalRestore.classList.add('fade-out');
			setTimeout(() => {
				modalRestore.style.display = 'none';
				modalRestore.classList.remove('fade-out');
			}, 300);
		});
	});

	confirmRestoreBtn.addEventListener('click', () => {
		const formData = new FormData(restoreForm);
		if (!formData.get('restoreFile')?.name) {
			alert("Selecione um arquivo para restaurar.");
			return;
		}

		fetch('/horarios/app/controllers/restore/restoreBackup.php', {
			method: 'POST',
			body: formData
		})
		.then(response => response.json())
		.then(data => {
			alert(data.message);
			if (data.status === 'success') {
				loadRestoreLogs();
			}
		})
		.catch(err => {
			console.error(err);
			alert("Erro ao restaurar o backup.");
		})
		.finally(() => {
			modalRestore.style.display = 'none';
		});
	});

	// Inicialização
	loadRestoreLogs();
});