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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Obtém os dados do formulário com operador de coalescência nula
  $email_funcionario = $_POST['email_funcionario'] ?? '';
  $cpf_funcionario = $_POST['cpf_funcionario'] ?? '';

  // Prepara a query SQL usando prepared statements para prevenir SQL Injection
  $stmt = $conexao->prepare("SELECT id_funcionario, cpf_funcionario FROM funcionarios WHERE email_funcionario = ?");
  $stmt->bind_param("s", $email_funcionario); // 's' indica que é uma string
  $stmt->execute();
  $stmt->store_result();

  // Verifica se encontrou o funcionário no banco de dados
  if ($stmt->num_rows > 0) {
    $stmt->bind_result($id_funcionario, $cpf_banco);
    $stmt->fetch();

    // Verifica se o CPF fornecido corresponde ao CPF armazenado
    if ($cpf_funcionario === $cpf_banco) {
      // Armazena o ID do funcionário na sessão
      $_SESSION['id_funcionario'] = $id_funcionario;
      
      // Redireciona para a página principal do funcionário
      $_SESSION['success_message'] = 'Login realizado com sucesso!';
      header("Location: /fixTime/PROJETO/src/views/main-page/Funcionario/main-funcionario.php");
      return;
    } else {
      // Mensagem de erro genérica por segurança
      $_SESSION['error_message'] = "Email ou CPF inválidos.";
      header("Location: /fixTime/PROJETO/src/views/Login/login-funcionario.php");
      return;
    }
  } else {
    // Mensagem de erro genérica por segurança
    $_SESSION['error_message'] = "Email ou CPF inválidos.";
    header("Location: /fixTime/PROJETO/src/views/Login/login-funcionario.php");
    return;
  }

  // Fecha o statement
  $stmt->close();
}
?>


<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <!-- Meta tags e configurações básicas do HTML -->
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Fix-Time - Login Oficina</title>
  <!-- Link para o arquivo CSS compilado do Tailwind -->
  <link rel="stylesheet" href="/fixTime/PROJETO/src/public/assets/css/output.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-gray-50 flex items-center justify-center min-h-screen lg:p-0 p-3">

  <!-- Botão de voltar -->
  <div class="absolute top-0 left-0 p-4">
    <a href="/fixTime/PROJETO/src/views/Login/choice-login.html" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 focus:outline-none">Voltar</a>
  </div>

  <!-- Container principal do formulário -->
  <div class="lg:w-auto lg:max-w-full w-full max-w-sm bg-white border border-gray-200 rounded-lg shadow-md lg:pt-4 p-3 lg:p-10 md:p-8 mt-12 lg:mt-3 mb-2 mx-2">
    <!-- Logo da empresa -->
    <div class="mb-2 flex flex-col items-center text-center">
      <img src="/fixTime/PROJETO/src/public/assets/images/fixtime-truck.png" class="h-16 w-auto">
    </div>

    <!-- Exibição de mensagens de erro -->
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

    <!-- Formulário de login -->
    <form class="space-y-3" method="POST" action="">
      <div class="lg:space-y-4 space-y-3">
        <!-- Campo de email -->
        <div class="">
          <label for="email_funcionario" class="block mb-1 text-sm font-medium text-gray-900">Email</label>
          <input type="email" name="email_funcionario" id="email_funcionario" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 focus:outline-none focus:ring-2 block w-full p-2" placeholder="joao@oficina.com" required />
        </div>

        <!-- Campo de CPF -->
        <div class="" id="senha-container">
          <label for="cpf_funcionario" class="block mb-1 text-sm font-medium text-gray-900">CPF</label>
          <input maxlength="14" type="text" name="cpf_funcionario" id="cpf_funcionario" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 focus:outline-none focus:ring-2 block w-full p-2" placeholder="123.456.789-10" required />
        </div>
      </div>

      <!-- Botão de submit -->
      <button type="submit" class="mt-4 cursor-pointer w-full text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2.5 text-center ">Acessar</button>
      
    </form>
  </div>


  

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.js"></script>
  <script src="/fixTime/PROJETO/src/public/assets/js/script.js"></script>
  <script>

  $(document).ready(function() {
            $('#cpf_funcionario').mask('000.000.000-00',)
        });
  </script>
  <!-- Scripts externos -->
</body>

</html>