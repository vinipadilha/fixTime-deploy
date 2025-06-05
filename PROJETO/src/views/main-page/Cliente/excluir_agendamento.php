<?php
include $_SERVER['DOCUMENT_ROOT'] . '/fixTime/PROJETO/src/views/connect_bd.php';
$conexao = connect_db();
session_start();

// Verifica se o usuário está autenticado
if (!isset($_SESSION['id_usuario'])) {
    $_SESSION['error_message'] = 'Usuário não autenticado.';
    header("Location: /fixTime/PROJETO/src/views/main-page/Cliente/meus-agendamentos.php");
    exit;
}

// Verifica se o ID do serviço foi fornecido
if (!isset($_POST['id_servico'])) {
    $_SESSION['error_message'] = 'ID do serviço não fornecido.';
    header("Location: /fixTime/PROJETO/src/views/main-page/Cliente/meus-agendamentos.php");
    exit;
}

$id_servico = $_POST['id_servico'];
$id_usuario = $_SESSION['id_usuario'];

// Verifica se o serviço pertence ao usuário
$sql_verifica = "SELECT s.id_servico 
                 FROM servico s 
                 JOIN veiculos v ON s.id_veiculo = v.id 
                 WHERE s.id_servico = ? AND v.id_usuario = ?";
$stmt_verifica = $conexao->prepare($sql_verifica);
$stmt_verifica->bind_param("ii", $id_servico, $id_usuario);
$stmt_verifica->execute();
$result = $stmt_verifica->get_result();

$sql_apaga_avaliacoes = "DELETE FROM avaliacao WHERE id_servico = ?";
$stmt_apaga = $conexao->prepare($sql_apaga_avaliacoes);
$stmt_apaga->bind_param("i", $id_servico);
$stmt_apaga->execute();


if ($result->num_rows === 0) {
    $_SESSION['error_message'] = 'Você não tem permissão para excluir este agendamento.';
    header("Location: /fixTime/PROJETO/src/views/main-page/Cliente/meus-agendamentos.php");
    exit;
}

// Exclui o serviço
$sql_excluir = "DELETE FROM servico WHERE id_servico = ?";
$stmt_excluir = $conexao->prepare($sql_excluir);
$stmt_excluir->bind_param("i", $id_servico);

if ($stmt_excluir->execute()) {
    $_SESSION['success_message'] = 'Agendamento excluído com sucesso.';
} else {
    $_SESSION['error_message'] = 'Erro ao excluir agendamento.';
}

header("Location: /fixTime/PROJETO/src/views/main-page/Cliente/meus-agendamentos.php");
exit;
?> 