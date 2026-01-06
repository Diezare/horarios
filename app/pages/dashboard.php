<?php
// horarios/app/pages/dashboard.php
require_once __DIR__ . '/../../configs/init.php';
include_once APP_SERVER_PATH . '/models/header.php';

// 1) Ano letivo “atual” ou mais recente
$idAnoLetivo = null;
$anoLabel		= date('Y');

$stmt = $pdo->query("
		SELECT id_ano_letivo, ano
		FROM ano_letivo
		WHERE CURDATE() BETWEEN data_inicio AND data_fim
		LIMIT 1
");
if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$idAnoLetivo = (int)$row['id_ano_letivo'];
		$anoLabel		= $row['ano'];
} else {
		$stmt = $pdo->query("
				SELECT id_ano_letivo, ano
				FROM ano_letivo
				ORDER BY ano DESC
				LIMIT 1
		");
		if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$idAnoLetivo = (int)$row['id_ano_letivo'];
				$anoLabel		= $row['ano'];
		}
}

// 2) Cartões
$totalProf = (int)$pdo->query("SELECT COUNT(*) FROM professor")->fetchColumn();
$totalDisc = (int)$pdo->query("SELECT COUNT(*) FROM disciplina")->fetchColumn();
$totalInst = (int)$pdo->query("SELECT COUNT(*) FROM instituicao")->fetchColumn();

// 3) Turmas
$totalTurmas = 0;
if ($idAnoLetivo) {
		$stmtTurmas = $pdo->prepare("SELECT COUNT(*) FROM turma WHERE id_ano_letivo = ?");
		$stmtTurmas->execute([$idAnoLetivo]);
		$totalTurmas = (int)$stmtTurmas->fetchColumn();
}
?>

<main>

	
	<div class="page-header">
		<h1>Dashboard</h1>
			<a href="<?php echo PAGES_PATH; ?>/horarios.php" class="btn-add" aria-label="Gerar Horários" title="Gerar Horários">
				<i class="fa-solid fa-arrows-rotate"></i> Gerar Horários
			</a>
	</div>
	

	<div class="data">
		<div class="content-data">

			<!-- Linha 1: cards -->
			<div class="row-cards">
				<div class="card">
					<p class="card-title">Professores</p>
					<h2 class="card-number"><?php echo $totalProf; ?></h2>
					<i class="fa-solid fa-chalkboard-teacher card-icon"></i>
				</div>

				<div class="card">
					<p class="card-title">Turmas em <?php echo htmlspecialchars($anoLabel); ?></p>
					<h2 class="card-number"><?php echo $totalTurmas; ?></h2>
					<i class="fa-solid fa-school card-icon"></i>
				</div>

				<div class="card">
					<p class="card-title">Disciplinas</p>
					<h2 class="card-number"><?php echo $totalDisc; ?></h2>
					<i class="fa-solid fa-book-open card-icon"></i>
				</div>

				<div class="card">
					<p class="card-title">Instituição</p>
					<h2 class="card-number"><?php echo $totalInst; ?></h2>
					<i class="fa-solid fa-building card-icon"></i>
				</div>
			</div>

			<!-- Linha 2: gráficos -->
			<div class="row-charts">
				<div class="chart-container">
					<p class="card-title">Professores por Disciplinas</p>
					<div id="chartBarras" class="chart chart-barras"></div>
				</div>
				<div class="chart-container">
					<p class="card-title">Professores por Sexo</p>
					<div id="chartPizza" class="chart chart-pizza"></div>
				</div>
			</div>

		</div>
	</div>
</main>

<!-- Google Charts e JS do dashboard no fim, após os containers existirem -->
<script src="https://www.gstatic.com/charts/loader.js"></script>
<script src="<?php echo JS_PATH; ?>/script.js"></script>
<script src="<?php echo JS_PATH; ?>/dashboard.js"></script>
</body>
</html>
