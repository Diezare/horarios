// app/assets/js/backup.js
document.addEventListener('DOMContentLoaded', function() {
	const elements = {
		btnBackup: document.getElementById('btn-bkp'),
		tbodyBackup: document.getElementById('tbodyBackup'),
		noDataMessage: document.getElementById('noDataMessage'),
		paginationContainer: document.getElementById('paginationContainer'),
		prevPageBtn: document.getElementById('prevPageBtn'),
		nextPageBtn: document.getElementById('nextPageBtn'),
		paginationStatus: document.getElementById('paginationStatus'),
		sortDataAsc: document.getElementById('sort-data-asc'),
		sortDataDesc: document.getElementById('sort-data-desc'),
		sortArquivoAsc: document.getElementById('sort-arquivo-asc'),
		sortArquivoDesc: document.getElementById('sort-arquivo-desc')
	};

	let state = {
		allLogs: [],
		currentPage: 1,
		itemsPerPage: 25,
		sortField: 'data',
		sortDirection: 'desc'
	};

	function init() {
		addEventListeners();
		loadBackupLogs();
	}

	function addEventListeners() {
		elements.prevPageBtn.addEventListener('click', () => changePage(-1));
		elements.nextPageBtn.addEventListener('click', () => changePage(1));
		elements.btnBackup.addEventListener('click', generateBackup);
		
		elements.sortDataAsc.addEventListener('click', () => sortLogs('data', 'asc'));
		elements.sortDataDesc.addEventListener('click', () => sortLogs('data', 'desc'));
		elements.sortArquivoAsc.addEventListener('click', () => sortLogs('arquivo', 'asc'));
		elements.sortArquivoDesc.addEventListener('click', () => sortLogs('arquivo', 'desc'));
	}

	async function loadBackupLogs() {
		try {
			const response = await fetch('/horarios/app/controllers/backup/listBackup.php');
			const data = await response.json();
						
			if (data.status === 'success' && data.data?.length > 0) {
				state.allLogs = data.data;
				sortLogs(state.sortField, state.sortDirection);
				elements.noDataMessage.style.display = 'none';
			} else {
				showNoDataMessage();
			}
		} catch (error) {
			console.error('Falha ao carregar dados:', error);
			showNoDataMessage();
		}
	}

	function sortLogs(field, direction) {
		state.sortField = field;
		state.sortDirection = direction;
		
		state.allLogs.sort((a, b) => {
			if (field === 'data') {
					return direction === 'asc' 
						? new Date(a.data_backup) - new Date(b.data_backup)
						: new Date(b.data_backup) - new Date(a.data_backup);
			}
			
			return direction === 'asc'
				? a.arquivo.localeCompare(b.arquivo)
				: b.arquivo.localeCompare(a.arquivo);
		});

		updateSortUI();
		renderTable();
	}

	function renderTable() {
		elements.tbodyBackup.innerHTML = '';
		
		const start = (state.currentPage - 1) * state.itemsPerPage;
		const end = start + state.itemsPerPage;
		
		state.allLogs.slice(start, end).forEach(log => {
			const row = `
				<tr>
					<td>${formatDateTime(log.data_backup)}</td>
					<td>${log.arquivo}</td>
				</tr>
			`;
			elements.tbodyBackup.insertAdjacentHTML('beforeend', row);
		});

		updatePagination();
	}

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
			return formattedDateTime.replace(',', ' -'); // Substituir a vírgula por um traço
		} catch {
			return 'Data inválida';
		}
	}

	function updatePagination() {
		const totalPages = Math.ceil(state.allLogs.length / state.itemsPerPage);
		
		elements.paginationStatus.textContent = `Página ${state.currentPage} de ${totalPages}`;
		elements.prevPageBtn.disabled = state.currentPage <= 1;
		elements.nextPageBtn.disabled = state.currentPage >= totalPages;
		elements.paginationContainer.style.display = totalPages > 1 ? 'block' : 'none';
	}

	function changePage(offset) {
		state.currentPage += offset;
		renderTable();
	}

	function generateBackup() {
		window.location.href = '/horarios/app/controllers/backup/generateBackup.php';
		setTimeout(loadBackupLogs, 2000);
	}

	function showNoDataMessage() {
		elements.tbodyBackup.innerHTML = '';
		elements.noDataMessage.style.display = 'block';
		elements.paginationContainer.style.display = 'none';
	}

	function updateSortUI() {
		document.querySelectorAll('.sort-btn').forEach(btn => btn.classList.remove('active-sort'));
		
		const activeButton = state.sortDirection === 'asc'
			? elements[`sort${state.sortField}Asc`]
			: elements[`sort${state.sortField}Desc`];
				
		if (activeButton) activeButton.classList.add('active-sort');
	}

	init();
});