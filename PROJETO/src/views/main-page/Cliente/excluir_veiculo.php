<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'] . '/fixTime/PROJETO/src/views/connect_bd.php';
$conexao = connect_db();

// Verifica autenticação
if (!isset($_SESSION['id_usuario'])) {
    $_SESSION['error_message'] = 'Usuário não autenticado.';
    header("Location: /fixTime/PROJETO/src/views/Login/login-user.php");
    exit;
}

// Verifica se o ID do veículo foi passado
if (!isset($_POST['id_veiculo'])) {
    $_SESSION['error_message'] = 'ID do veículo não informado.';
    header("Location: /fixTime/PROJETO/src/views/main-page/Cliente/meus-agendamentos.php");
    exit;
}

$id_veiculo = $_POST['id_veiculo'];
$id_usuario = $_SESSION['id_usuario'];

// Verifica se o veículo pertence ao usuário
$sql_verifica = "SELECT id FROM veiculos WHERE id = ? AND id_usuario = ?";
$stmt_verifica = $conexao->prepare($sql_verifica);
$stmt_verifica->bind_param("ii", $id_veiculo, $id_usuario);
$stmt_verifica->execute();
$result = $stmt_verifica->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error_message'] = 'Veículo não encontrado ou você não tem permissão.';
    header("Location: /fixTime/PROJETO/src/views/main-page/Cliente/meus-agendamentos.php");
    exit;
}

// Verifica se existem agendamentos ligados ao veículo
$sql_check_agendamentos = "SELECT COUNT(*) AS total FROM servico WHERE id_veiculo = ?";
$stmt_check = $conexao->prepare($sql_check_agendamentos);
$stmt_check->bind_param("i", $id_veiculo);
$stmt_check->execute();
$result_check = $stmt_check->get_result();
$row = $result_check->fetch_assoc();


$total_agendamentos = (int) $row['total'];

if ($total_agendamentos > 0) {
    $_SESSION['alert_agendamento_veiculo'] = true;
    header("Location: /fixTime/PROJETO/src/views/main-page/Cliente/meus-agendamentos.php");
    exit;
}


// Se não tiver agendamentos, exclui o veículo
$sql_delete = "DELETE FROM veiculos WHERE id = ?";
$stmt_delete = $conexao->prepare($sql_delete);
$stmt_delete->bind_param("i", $id_veiculo);

if ($stmt_delete->execute()) {
    $_SESSION['success_message'] = 'Veículo excluído com sucesso.';
} else {
    $_SESSION['error_message'] = 'Erro ao excluir veículo.';
}

header("Location: /fixTime/PROJETO/src/views/main-page/Cliente/veiculos.php"); // <- Redireciona corretamente para a tela de veículos
exit;

?>
