<?php
// salvar_reagendamento.php
include $_SERVER['DOCUMENT_ROOT'] . '/fixTime/PROJETO/src/views/connect_bd.php';
$conexao = connect_db();

session_start();

// Verifica se o usuário está autenticado
if (!isset($_SESSION['id_usuario'])) {
    $_SESSION['error_message'] = 'Usuário não autenticado.';
    header("Location: /fixTime/PROJETO/src/views/main-page/Cliente/meus-agendamentos.php");
    exit;
}

// Valida os dados recebidos
$id_servico = $_POST['id_servico'] ?? null;
$data_agendada = $_POST['data_agendada'] ?? null;
$horario = $_POST['horario'] ?? null;

if (!$id_servico || !$data_agendada || !$horario) {
    $_SESSION['error_message'] = 'Dados incompletos.';
    header("Location: /fixTime/PROJETO/src/views/main-page/Cliente/meus-agendamentos.php");
    exit;
}

// Atualiza a tabela servico
$sql = "UPDATE servico SET data_agendada = ?, horario = ? WHERE id_servico = ?";
$stmt = $conexao->prepare($sql);

if (!$stmt) {
    $_SESSION['error_message'] = 'Erro na preparação do SQL.';
    header("Location: /fixTime/PROJETO/src/views/main-page/Cliente/meus-agendamentos.php");
    exit;
}

$stmt->bind_param("ssi", $data_agendada, $horario, $id_servico);

if ($stmt->execute()) {
    $_SESSION['success_message'] = 'Agendamento atualizado com sucesso.';
    header("Location: /fixTime/PROJETO/src/views/main-page/Cliente/meus-agendamentos.php");
    exit;
} else {
    $_SESSION['error_message'] = 'Erro ao reagendar.';
    header("Location: /fixTime/PROJETO/src/views/main-page/Cliente/meus-agendamentos.php");
    exit;
}
?>
