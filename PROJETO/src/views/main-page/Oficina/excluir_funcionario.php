<?php
include $_SERVER['DOCUMENT_ROOT'] . '/fixTime/PROJETO/src/views/connect_bd.php';
$conexao = connect_db();
session_start();

if (!isset($_SESSION['id_oficina'])) {
    die("Acesso não autorizado.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
    $id_oficina = $_SESSION['id_oficina'];

    try {
        // Verifica se o funcionário está atribuído a algum serviço
        $verifica = $conexao->prepare("SELECT COUNT(*) as total FROM servico WHERE id_funcionario_responsavel = ? AND id_oficina = ?");
        $verifica->bind_param("ii", $id, $id_oficina);
        $verifica->execute();
        $resultado = $verifica->get_result()->fetch_assoc();
        $verifica->close();

        if ($resultado['total'] > 0) {
            header("Location: /fixTime/PROJETO/src/views/main-page/Oficina/agendamentos-oficina.php");
            exit;
        }

        // Se não estiver vinculado, executa a exclusão
        $stmt = $conexao->prepare("DELETE FROM funcionarios WHERE id_funcionario = ? AND id_oficina = ?");
        $stmt->bind_param("ii", $id, $id_oficina);

        if ($stmt->execute()) {
            $_SESSION['alert'] = [
                'type' => 'success',
                'title' => 'Sucesso!',
                'text' => 'Funcionário excluído com sucesso!'
            ];
        } else {
            $_SESSION['alert'] = [
                'type' => 'error',
                'title' => 'Erro!',
                'text' => 'Erro ao excluir funcionário: ' . addslashes($stmt->error)
            ];
        }

        $stmt->close();
    } catch (Exception $e) {
        $_SESSION['alert'] = [
            'type' => 'error',
            'title' => 'Erro!',
            'text' => 'Erro no banco de dados: ' . addslashes($e->getMessage())
        ];
    }
}

// Redireciona após a operação
header("Location: /fixTime/PROJETO/src/views/main-page/Oficina/funcionarios.php");
exit;
?>
