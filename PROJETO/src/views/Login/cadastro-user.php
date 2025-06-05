<?php
// Inclui o arquivo de conexão com o banco de dados
include $_SERVER['DOCUMENT_ROOT'] . '/fixTime/PROJETO/src/views/connect_bd.php';
$conexao = connect_db();

// Verifica se a conexão com o banco de dados foi estabelecida com sucesso
if (!isset($conexao) || !$conexao) {
  die("Erro ao conectar ao banco de dados. Verifique o arquivo connect_bd.php.");
}

// Inicia a sessão para gerenciar dados do usuário
session_start();

// Processa o formulário quando enviado via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Sanitiza e escapa todos os dados do formulário para prevenir SQL injection
  $nome_usuario = $conexao->real_escape_string($_POST['first_name']);
  $cpf = $conexao->real_escape_string($_POST['cpf']);
  $telefone_usuario = $conexao->real_escape_string($_POST['telefone']);
  $email_usuario = $conexao->real_escape_string($_POST['email']);
  $senha_usuario = $conexao->real_escape_string($_POST['senha']);

  // Verifica se o CPF já está cadastrado no sistema
  $verificaCpf = "SELECT cpf FROM cliente WHERE cpf = '$cpf'";
  $resultadoCpf = $conexao->query($verificaCpf);

  if ($resultadoCpf->num_rows > 0) {
    $_SESSION['error_message'] = 'CPF já cadastrado. Faça login ou use outro CPF.';
    header("Location: /fixTime/PROJETO/src/views/Login/cadastro-user.php");
    return;
  }

  // Verifica se o email já está cadastrado no sistema
  $verificaEmail = "SELECT email_usuario FROM cliente WHERE email_usuario = '$email_usuario'";
  $resultadoEmail = $conexao->query($verificaEmail);
  if ($resultadoEmail->num_rows > 0) {
    $_SESSION['error_message'] = 'E-mail já cadastrado. Faça login ou use outro e-mail.';
    header("Location: /fixTime/PROJETO/src/views/Login/cadastro-user.php");
    return;
  }

  // Cria um hash seguro da senha usando o algoritmo padrão do PHP
  $senha_hash = password_hash($senha_usuario, PASSWORD_DEFAULT);

  // Query SQL para inserir os dados do cliente no banco de dados
  $sql = "INSERT INTO cliente (nome_usuario, cpf, telefone_usuario, email_usuario, senha_usuario) 
          VALUES ('$nome_usuario', '$cpf', '$telefone_usuario', '$email_usuario', '$senha_hash')";

  // Executa a query e redireciona em caso de sucesso
  if ($conexao->query($sql) === TRUE) {
    $_SESSION['success_message'] = 'Usuário cadastrado com sucesso!';
    header("Location: /fixTime/PROJETO/src/views/Login/login-user.php");
    return;
  } else {
    $_SESSION['error_message'] = "Erro: " . $sql . "<br>" . $conexao->error;
    header("Location: /fixTime/PROJETO/src/views/Login/cadastro-user.php");
    return;
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Meta tags e configurações básicas do HTML -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix-Time</title>
    <!-- Link para o arquivo CSS compilado do Tailwind -->
    <link rel="stylesheet" href="/fixTime/PROJETO/src/public/assets/css/output.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen lg:p-0 p-3">
    <!-- Botão de voltar -->
    <div class="absolute top-0 left-0 p-4">     
        <a href="/fixTime/PROJETO/src/views/Login/choice-cadastro.html" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 focus:outline-none">Voltar</a>
    </div>

    <!-- Container principal do formulário -->
    <div class="w-full max-w-sm bg-white border border-gray-200 rounded-lg shadow-sm p-3 md:p-8 lg:p-4">
        <!-- Formulário de cadastro -->
        <form class="lg:space-y-2 space-y-3" action="/fixTime/PROJETO/src/views/Login/cadastro-user.php" method="POST">
            <!-- Campo de nome completo -->
            <div>
                <label for="first_name" class="block mb-1 text-sm font-medium text-gray-900">Nome completo</label>
                <input type="text" id="first_name" name="first_name" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 focus:outline-none focus:ring-2 block w-full p-2" placeholder="Marcos da Silva" required/>
            </div>

            <!-- Campo de CPF -->
            <div>
                <label for="cpf" class="block mb-1 text-sm font-medium text-gray-900">CPF</label>
                <input type="tel" id="cpf" name="cpf" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 focus:outline-none focus:ring-2 block w-full p-2" placeholder="123.456.789-09" required />
            </div>

            <!-- Campo de telefone -->
            <div>
                <label for="telefone" class="block mb-1 text-sm font-medium text-gray-900">Número de telefone</label>
                <input type="tel" id="telefone" name="telefone" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 focus:outline-none focus:ring-2 block w-full p-2" placeholder="(41) 99988-7766" required />
            </div>

            <!-- Campo de email -->
            <div>
                <label for="email" class="block mb-1 text-sm font-medium text-gray-900">Email</label>
                <input type="email" id="email" name="email" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 focus:outline-none focus:ring-2 block w-full p-2" placeholder="john.doe@company.com" required />
            </div> 

            <!-- Campo de senha -->
            <div class="col-span-2" id="senha-container">
                <label for="senha" class="block mb-1 text-sm font-medium text-gray-900">Senha</label>
                <input type="password" id="senha" name="senha" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 focus:outline-none focus:ring-2 block w-full p-2" placeholder="••••••••••••" required />
            </div>
            
            <!-- Campo de confirmação de senha -->
            <div class="col-span-2" id="confirma-senha-container">
                <label for="confirma_senha" class="block mb-1 text-sm font-medium text-gray-900">Confirmar senha</label>
                <input type="password" id="confirma_senha" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 focus:outline-none focus:ring-2 block w-full p-2" placeholder="••••••••••••" required />
                <p id="error-message" class="text-red-500 text-sm mt-1 hidden">As senhas não coincidem. Tente novamente.</p>
            </div>
            
            <!-- Botão de submit do formulário -->
            <button type="submit" class="mt-4 cursor-pointer w-full text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">Cadastrar</button>
        </form>
    </div>

    <!-- Scripts necessários -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.js"></script>
    <script src="/fixTime/PROJETO/src/public/assets/js/script.js"></script>

    <?php if (isset($_SESSION['error_message'])): ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Erro',
            text: '<?php echo $_SESSION['error_message']; ?>',
            confirmButtonText: 'OK'
        });
        <?php unset($_SESSION['error_message']); ?>
    </script>
    <?php endif; ?>

    <?php if (isset($_SESSION['success_message'])): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Sucesso',
            text: '<?php echo $_SESSION['success_message']; ?>',
            confirmButtonText: 'OK'
        });
        <?php unset($_SESSION['success_message']); ?>
    </script>
    <?php endif; ?>

<script>
function validarCPF(cpf) {
    cpf = cpf.replace(/[^\d]+/g, '');
    if (cpf.length !== 11 || /^(\d)\1+$/.test(cpf)) return false;

    let soma = 0, resto;
    for (let i = 1; i <= 9; i++) soma += parseInt(cpf[i - 1]) * (11 - i);
    resto = (soma * 10) % 11;
    if (resto === 10 || resto === 11) resto = 0;
    if (resto !== parseInt(cpf[9])) return false;

    soma = 0;
    for (let i = 1; i <= 10; i++) soma += parseInt(cpf[i - 1]) * (12 - i);
    resto = (soma * 10) % 11;
    if (resto === 10 || resto === 11) resto = 0;
    if (resto !== parseInt(cpf[10])) return false;

    return true;
}

document.querySelector("form").addEventListener("submit", function(e) {
    const cpfInput = document.getElementById("cpf");
    const cpf = cpfInput.value;

    if (!validarCPF(cpf)) {
        e.preventDefault();
        Swal.fire({
            icon: 'error',
            title: 'CPF inválido',
            text: 'Por favor, insira um CPF válido.',
            confirmButtonText: 'OK'
        });
    }
});
</script>


</body>
</html>