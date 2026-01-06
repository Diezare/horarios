// app/assets/js/dashboard.js
document.addEventListener('DOMContentLoaded', () => {
	// Containers precisam existir antes de carregar a API
	const elBar	 = document.getElementById('chartBarras');
	const elPizza = document.getElementById('chartPizza');

	if (!elBar || !elPizza) {
		console.warn('Containers de gráfico não encontrados. Abortando draw.');
		return;
	}

	// Carrega Google Charts
	if (!window.google || !google.charts) {
		console.error('Google Charts loader indisponível.');
		return;
	}

	google.charts.load('current', { packages: ['corechart'] });
	google.charts.setOnLoadCallback(loadDataAndDraw);

	// Debounce resize para redesenhar
	let rAF = null;
	window.addEventListener('resize', () => {
		if (rAF) cancelAnimationFrame(rAF);
		rAF = requestAnimationFrame(() => loadDataAndDraw(true));
	});

	async function loadDataAndDraw(isResize) {
		// Confere containers de novo (mobile pode alterar DOM)
		const barContainer	 = document.getElementById('chartBarras');
		const pizzaContainer = document.getElementById('chartPizza');
		if (!barContainer || !pizzaContainer) return;

		// Em alguns layouts mobile, elementos podem estar display:none.
		// Se largura/altura for 0, aguarde próximo frame.
		const hasSize = (el) => el.offsetWidth > 0 && el.offsetHeight > 0;
		if (!hasSize(barContainer) || !hasSize(pizzaContainer)) {
			// tenta novamente no próximo frame
			requestAnimationFrame(() => loadDataAndDraw(isResize));
			return;
		}

		try {
			// Busca dados
			const resp = await fetch('/horarios/app/controllers/dashboard/dashboardData.php', { cache: 'no-store' });
			const json = await resp.json();
			if (json.status !== 'success') {
				console.error(json.message || 'Falha ao carregar dados do dashboard.');
				return;
			}

			// --------- BARRAS ----------
			const barArray = [['Disciplina','Professores']];
			(json.barras || []).forEach(item => {
				barArray.push([item.sigla, parseInt(item.total, 10)]);
			});
			const dataBarras = google.visualization.arrayToDataTable(barArray);

			const optionsBarras = {
				legend: { position: 'none' },
				colors: ['#6358F8'],
				chartArea: { left: 50, top: 20, width: '100%', height: '80%' },
				hAxis: { textStyle: { fontName: 'Open Sans', fontSize: 12 } },
				vAxis: {
					textStyle: { fontName: 'Open Sans', fontSize: 14 },
					format: '0'
				}
			};

			const chartBarras = new google.visualization.ColumnChart(barContainer);
			chartBarras.draw(dataBarras, optionsBarras);

			// --------- PIZZA ----------
			const pizzaArray = [['Sexo','Quantidade']];
			const aux = [];
			(json.pizza || []).forEach(item => {
				aux.push([item.sexo, parseInt(item.total, 10)]);
			});
			aux.sort((a, b) => {
				if (a[0].toLowerCase().includes('f')) return -1;
				if (b[0].toLowerCase().includes('f')) return 1;
				return 0;
			});
			aux.forEach(x => pizzaArray.push(x));

			const dataPizza = google.visualization.arrayToDataTable(pizzaArray);
			const optionsPizza = {
				fontName: 'Open Sans',
				fontSize: 14,
				legend: { position: 'bottom' },
				colors: ['#F61F1F', '#6358F8', '#E61FF4'],
				chartArea: { left: 10, top: 20, width: '100%', height: '75%' }
			};

			const chartPizza = new google.visualization.PieChart(pizzaContainer);
			chartPizza.draw(dataPizza, optionsPizza);

		} catch (err) {
			console.error(err);
		}
	}
});
