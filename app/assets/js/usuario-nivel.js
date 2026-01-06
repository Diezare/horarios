// app/assets/js/usuario-nivel.js
document.addEventListener('DOMContentLoaded', function() {
	// Referências para o modal de vinculação de níveis
	const modalUsuarioNivel = document.getElementById('modal-usuario-nivel');
	const closeUsuarioNivelModalBtn = document.getElementById('close-usuario-nivel-modal');
	const cancelUsuarioNivelBtn = document.getElementById('cancel-usuario-nivel-btn');
	const saveUsuarioNivelBtn = document.getElementById('save-usuario-nivel-btn');
	const niveisCheckboxesContainer = document.getElementById('niveis-checkboxes');
	let currentUserId = null;
	
	// Define a função global para abrir o modal
	window.openUsuarioNivelModal = function(userId) {
		currentUserId = userId;
		// Limpa o container
		niveisCheckboxesContainer.innerHTML = '';
		// Carrega os níveis disponíveis e os já vinculados ao usuário
		Promise.all([
			fetch('/horarios/app/controllers/nivel-ensino/listNivelEnsino.php').then(r => r.json()),
			fetch('/horarios/app/controllers/usuario-nivel/listUsuarioNivel.php?user_id=' + userId).then(r => r.json())
		]).then(([nivelData, usuarioNivelData]) => {
			if(nivelData.status === 'success' && usuarioNivelData.status === 'success'){
				const linkedLevels = usuarioNivelData.data.map(item => parseInt(item.id_nivel_ensino));
				// Para cada nível, cria um bloco (div) com classes para layout em duas colunas
				nivelData.data.forEach(nivel => {
					const checkboxWrapper = document.createElement('div');
					checkboxWrapper.classList.add('form-group', 'inline-checkbox');
					// Opcional: use estilo inline para garantir duas colunas
					checkboxWrapper.style.flex = '1 0 calc(50% - 10px)';
					
					const label = document.createElement('label');
					label.classList.add('radio-like');
					
					const checkbox = document.createElement('input');
					checkbox.type = 'checkbox';
					checkbox.value = nivel.id_nivel_ensino;
					if (linkedLevels.includes(parseInt(nivel.id_nivel_ensino))) {
						checkbox.checked = true;
					}
					label.appendChild(checkbox);
					
					const span = document.createElement('span');
					span.textContent = ' ' + nivel.nome_nivel_ensino;
					label.appendChild(span);
					
					checkboxWrapper.appendChild(label);
					niveisCheckboxesContainer.appendChild(checkboxWrapper);
				});
			} else {
				console.error('Erro ao carregar níveis.', nivelData.message, usuarioNivelData.message);
			}
		}).catch(err => console.error(err));
		
		// Abre o modal com efeitos (semelhante ao de Ano Letivo)
		modalUsuarioNivel.style.display = 'block';
		modalUsuarioNivel.classList.remove('fade-out');
		modalUsuarioNivel.classList.add('fade-in');
		const content = modalUsuarioNivel.querySelector('.modal-content');
		content.classList.remove('slide-up');
		content.classList.add('slide-down');
	};
	
	function closeUsuarioNivelModal() {
		const content = modalUsuarioNivel.querySelector('.modal-content');
		content.classList.remove('slide-down');
		content.classList.add('slide-up');
		modalUsuarioNivel.classList.remove('fade-in');
		modalUsuarioNivel.classList.add('fade-out');
		setTimeout(() => {
			modalUsuarioNivel.style.display = 'none';
			content.classList.remove('slide-up');
			modalUsuarioNivel.classList.remove('fade-out');
			currentUserId = null;
		}, 300);
	}
	
	closeUsuarioNivelModalBtn.addEventListener('click', closeUsuarioNivelModal);
	cancelUsuarioNivelBtn.addEventListener('click', closeUsuarioNivelModal);
	
	saveUsuarioNivelBtn.addEventListener('click', function() {
		if (!currentUserId) return;
		// Coleta os níveis selecionados
		const checkboxes = niveisCheckboxesContainer.querySelectorAll('input[type="checkbox"]');
		const selectedLevels = [];
		checkboxes.forEach(cb => {
			if (cb.checked) {
				selectedLevels.push(cb.value);
			}
		});
		// Prepara os dados para envio
		const data = new URLSearchParams();
		data.append('id_usuario', currentUserId);
		data.append('niveis', JSON.stringify(selectedLevels));
		
		// Chama o endpoint de atualização dos vínculos (updateUsuarioNivel)
		fetch('/horarios/app/controllers/usuario-nivel/updateUsuarioNivel.php', {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: data
		})
		.then(r => r.json())
		.then(response => {
			alert(response.message);
			if (response.status === 'success') {
				closeUsuarioNivelModal();
				// Opcional: atualize a listagem de usuários, se necessário
			}
		})
		.catch(err => console.error(err));
	});
});
