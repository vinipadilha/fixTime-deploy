<?php
// Inclui o arquivo de conexão com o banco de dados
include $_SERVER['DOCUMENT_ROOT'] . '/fixTime/PROJETO/src/views/connect_bd.php';
$conexao = connect_db();

// Inicia a sessão
session_start();

// Verifica se o usuário está autenticado
if (!isset($_SESSION['id_usuario'])) {
    $_SESSION['error'] = 'Usuário não autenticado. Faça login novamente.';
    header("Location: /fixTime/PROJETO/src/views/Login/login-user.php");
    exit();
}

// Verifica se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Método de requisição inválido.';
    header("Location: /fixTime/PROJETO/src/views/main-page/Cliente/veiculos.php");
    exit();
}

// Obtém e valida os dados do formulário
$id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
$tipo = filter_input(INPUT_POST, 'tipo_veiculo', FILTER_SANITIZE_STRING);
$marca = filter_input(INPUT_POST, 'marca', FILTER_SANITIZE_STRING);
$modelo = filter_input(INPUT_POST, 'modelo', FILTER_SANITIZE_STRING);
$ano = filter_input(INPUT_POST, 'ano', FILTER_SANITIZE_NUMBER_INT);
$cor = filter_input(INPUT_POST, 'cor', FILTER_SANITIZE_STRING);
$placa = filter_input(INPUT_POST, 'placa', FILTER_SANITIZE_STRING);
$quilometragem = filter_input(INPUT_POST, 'quilometragem', FILTER_SANITIZE_NUMBER_INT);

// Validação dos campos obrigatórios
if (!$id || !$tipo || !$marca || !$modelo || !$ano || !$cor || !$placa || !$quilometragem) {
    $_SESSION['error'] = 'Todos os campos são obrigatórios.';
    header("Location: /fixTime/PROJETO/src/views/main-page/Cliente/veiculos.php");
    exit();
}

try {
    // Verifica se o veículo pertence ao usuário
    $stmt = $conexao->prepare("SELECT id FROM veiculos WHERE id = ? AND id_usuario = ?");
    $stmt->bind_param("ii", $id, $_SESSION['id_usuario']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $_SESSION['error'] = 'Veículo não encontrado ou não pertence ao seu usuário.';
        header("Location: /fixTime/PROJETO/src/views/main-page/Cliente/veiculos.php");
        exit();
    }

    // Atualiza o veículo
    $stmt = $conexao->prepare("UPDATE veiculos SET 
        tipo_veiculo = ?, 
        marca = ?, 
        modelo = ?, 
        ano = ?, 
        cor = ?, 
        placa = ?, 
        quilometragem = ? 
        WHERE id = ? AND id_usuario = ?");
    
    $stmt->bind_param("ssssssiii", $tipo, $marca, $modelo, $ano, $cor, $placa, $quilometragem, $id, $_SESSION['id_usuario']);

    if ($stmt->execute()) {
        $_SESSION['success'] = 'Veículo atualizado com sucesso!';
    } else {
        $_SESSION['error'] = 'Erro ao atualizar veículo: ' . $stmt->error;
    }

    $stmt->close();
} catch (Exception $e) {
    $_SESSION['error'] = 'Erro ao atualizar veículo: ' . $e->getMessage();
}

header("Location: /fixTime/PROJETO/src/views/main-page/Cliente/veiculos.php");
exit();
