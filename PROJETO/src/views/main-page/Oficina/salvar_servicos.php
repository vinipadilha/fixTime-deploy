<?php
// Inicia a sessão PHP para manter o estado do usuário
session_start();

// Inclui o arquivo de conexão com o banco de dados
include $_SERVER['DOCUMENT_ROOT'] . '/fixTime/PROJETO/src/views/connect_bd.php';
$conexao = connect_db();

// Verifica se a conexão com o banco de dados foi estabelecida com sucesso
if (!isset($conexao) || !$conexao) {
    echo json_encode([
        'type' => 'error',
        'title' => 'Erro!',
        'text' => 'Erro ao conectar ao banco de dados.'
    ]);
    exit;
}

// Obtém o ID da oficina da sessão
$oficina_id = $_SESSION['id_oficina'] ?? null;

// Verifica se o usuário está autenticado
if (!$oficina_id) {
    echo json_encode([
        'type' => 'error',
        'title' => 'Erro!',
        'text' => 'Usuário não autenticado. Faça login novamente.'
    ]);
    exit;
}

// Verifica se foram enviados serviços
if (!isset($_POST['servicos'])) {
    echo json_encode([
        'type' => 'error',
        'title' => 'Erro!',
        'text' => 'Nenhum serviço selecionado.'
    ]);
    exit;
}

try {
    // Inicia uma transação
    $conexao->begin_transaction();

    // Remove todos os serviços anteriores da oficina
    $sqlDelete = "DELETE FROM oficina_servicos WHERE id_oficina = ?";
    $stmtDelete = $conexao->prepare($sqlDelete);
    $stmtDelete->bind_param("i", $oficina_id);
    $stmtDelete->execute();

    // Insere os novos serviços selecionados
    $sqlInsert = "INSERT INTO oficina_servicos (id_oficina, id_servico_padrao) VALUES (?, ?)";
    $stmtInsert = $conexao->prepare($sqlInsert);

    foreach ($_POST['servicos'] as $servico_id) {
        $stmtInsert->bind_param("ii", $oficina_id, $servico_id);
        $stmtInsert->execute();
    }

    // Confirma a transação
    $conexao->commit();

    echo json_encode([
        'type' => 'success',
        'title' => 'Sucesso!',
        'text' => 'Serviços atualizados com sucesso!'
    ]);

} catch (Exception $e) {
    // Em caso de erro, desfaz a transação
    $conexao->rollback();
    
    echo json_encode([
        'type' => 'error',
        'title' => 'Erro!',
        'text' => 'Erro ao salvar serviços: ' . $e->getMessage()
    ]);
}

// Fecha as conexões
$stmtDelete->close();
$stmtInsert->close();
$conexao->close();
?>
