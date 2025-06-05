<?php
function connect_db()
{
    // Configurações do banco de dados
    $db_name = "fixTime_teste";    // Nome do banco de dados
    $user = "root";          // Usuário do banco de dados
    $pass = "master100";              // Senha do banco de dados (vazia para ambiente de desenvolvimento)
    $server = "localhost:3306"; // Endereço e porta do servidor MySQL

    // Cria uma nova conexão com o banco de dados usando a classe mysqli
    // mysqli é uma extensão do PHP para trabalhar com MySQL
    $conexao = new mysqli($server, $user, $pass, $db_name);

    // Verifica se houve erro na conexão
    // connect_error contém a mensagem de erro se a conexão falhar
    if ($conexao->connect_error) {
        // Encerra a execução do script e exibe a mensagem de erro
        die("Falha na conexão com o banco de dados: " . $conexao->connect_error);
    }

    // Retorna o objeto de conexão se tudo ocorrer bem
    return $conexao;
}
