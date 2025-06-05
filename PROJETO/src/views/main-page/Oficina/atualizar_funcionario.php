<?php
// Inclui o arquivo de conexão com o banco de dados
include $_SERVER['DOCUMENT_ROOT'] . '/fixTime/PROJETO/src/views/connect_bd.php';
$conexao = connect_db();

// Inicia a sessão PHP para manter o estado do usuário
session_start();

// Verifica se o usuário está autenticado como oficina
if (!isset($_SESSION['id_oficina'])) {
    die("Acesso não autorizado.");
}

// Verifica se a requisição é do tipo POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitiza e valida os dados recebidos do formulário
    $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
    $nome = htmlspecialchars($_POST['nome'] ?? '', ENT_QUOTES, 'UTF-8');
    $cargo = htmlspecialchars($_POST['cargo'] ?? '', ENT_QUOTES, 'UTF-8');
    $cpf = htmlspecialchars($_POST['cpf'] ?? '', ENT_QUOTES, 'UTF-8');
    $telefone = htmlspecialchars($_POST['telefone'] ?? '', ENT_QUOTES, 'UTF-8');
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $data_admissao = htmlspecialchars($_POST['data_admissao'] ?? '', ENT_QUOTES, 'UTF-8');

    try {
        // Prepara a query SQL para atualizar os dados do funcionário
        // Usa prepared statements para prevenir SQL injection
        $stmt = $conexao->prepare("UPDATE funcionarios SET 
            nome_funcionario = ?, 
            cargo_funcionario = ?, 
            telefone_funcionario = ?, 
            cpf_funcionario = ?, 
            email_funcionario = ?, 
            data_admissao = ? 
            WHERE id_funcionario = ? AND id_oficina = ?");

        // Vincula os parâmetros à query
        // "ssssssii" indica os tipos de dados: s = string, i = integer
        $stmt->bind_param("ssssssii", $nome, $cargo, $telefone, $cpf, $email, $data_admissao, $id, $_SESSION['id_oficina']);

        // Executa a query e verifica o resultado
        if ($stmt->execute()) {
            // Sucesso na atualização
            $_SESSION['mensagem'] = "<script>alert('Funcionário atualizado com sucesso!');</script>";
        } else {
            // Erro na atualização
            $_SESSION['mensagem'] = "<script>alert('Erro ao atualizar funcionário: " . addslashes($stmt->error) . "');</script>";
        }

        // Fecha o statement
        $stmt->close();
    } catch (Exception $e) {
        // Tratamento de exceções
        $erro = $e->getMessage();
        
        // Verifica se o erro é de email duplicado
        if (str_contains($erro, 'Duplicate entry') && str_contains($erro, 'email_funcionario')) {
            $_SESSION['mensagem'] = "<script>alert('Este email já está cadastrado para outro funcionário.');</script>";
        } else {
            // Outros erros do banco de dados
            $_SESSION['mensagem'] = "<script>alert('Erro no banco de dados: " . addslashes($erro) . "');</script>";
        }
    }
}

// Redireciona de volta para a página de funcionários
header("Location: /fixTime/PROJETO/src/views/main-page/Oficina/funcionarios.php");
exit;
