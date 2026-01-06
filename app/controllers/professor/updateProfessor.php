<?php
// app/controllers/professor/updateProfessor.php
require_once __DIR__ . '/../../../configs/init.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
	exit;
}

$id           = intval($_POST['id_professor'] ?? 0);
$nomeCompleto = trim($_POST['nome_completo'] ?? '');
$nomeExibicao = trim($_POST['nome_exibicao'] ?? '');
$telefone	  = trim($_POST['telefone'] ?? '');
$sexo         = trim($_POST['sexo'] ?? 'Masculino');
$limite       = trim($_POST['limite_aulas'] ?? '0');

if ($id <= 0 || empty($nomeCompleto)) {
	echo json_encode(['status' => 'error', 'message' => 'Dados incompletos.']);
	exit;
}
if (!in_array($sexo, ['Masculino','Feminino','Outro'])) {
	$sexo = 'Masculino';
}
$limiteInt = (int)$limite;
if ($limiteInt < 0) $limiteInt = 0;
if ($limiteInt > 99) $limiteInt = 99;

try {
	$stmt = $pdo->prepare("
		UPDATE professor
		   SET nome_completo = ?,
		    	nome_exibicao = ?,
				telefone = ?,					 
		    	sexo = ?,
		    	limite_aulas_fixa_semana = ?
		WHERE id_professor = ?
	");
	$stmt->execute([$nomeCompleto, $nomeExibicao, $telefone, $sexo, $limiteInt, $id]);

	if ($stmt->rowCount() > 0) {
		echo json_encode(['status' => 'success', 'message' => 'Professor atualizado com sucesso.']);
	} else {
		echo json_encode(['status' => 'error', 'message' => 'Nenhuma alteração realizada ou professor não encontrado.']);
	}
} catch (PDOException $e) {
	echo json_encode(['status' => 'error', 'message' => 'Erro: ' . $e->getMessage()]);
}
?>
