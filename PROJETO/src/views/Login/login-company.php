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
  $email_oficina = $_POST['email_oficina'] ?? '';
  $senha_oficina = $_POST['senha_oficina'] ?? '';

  // Prepara a query SQL usando prepared statements para prevenir SQL Injection
  $stmt = $conexao->prepare("SELECT id_oficina, senha_oficina FROM oficina WHERE email_oficina = ?");
  $stmt->bind_param("s", $email_oficina); // 's' indica que é uma string
  $stmt->execute();
  $stmt->store_result();

  // Verifica se encontrou o usuário no banco de dados
  if ($stmt->num_rows > 0) {
    $stmt->bind_result($id_oficina, $hash_senha);
    $stmt->fetch();

    // Verifica se a senha fornecida corresponde ao hash armazenado
    if (password_verify($senha_oficina, $hash_senha)) {
      // Armazena o ID da oficina na sessão
      $_SESSION['id_oficina'] = $id_oficina;

      // Redireciona para a página principal da oficina
      $_SESSION['success_message'] = 'Login realizado com sucesso!';
      header("Location: /fixTime/PROJETO/src/views/main-page/Oficina/main-oficina.php");
      return;
    } else {
      // Mensagem de erro genérica por segurança
      $_SESSION['error_message'] = "Email ou senha inválidos.";
      header("Location: /fixTime/PROJETO/src/views/Login/login-company.php");
      return;
    }
  } else {
    // Mensagem de erro genérica por segurança
    $_SESSION['error_message'] = "Email ou senha inválidos.";
    header("Location: /fixTime/PROJETO/src/views/Login/login-company.php");
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
  <div class="lg:w-auto lg:max-w-full w-full max-w-sm bg-white border border-gray-200 rounded-lg shadow-md lg:pt-4 p-3 lg:p-6 md:p-8 mt-12 lg:mt-3 mb-2 mx-2">
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
          <label for="email_oficina" class="block mb-1 text-sm font-medium text-gray-900">Email</label>
          <input type="email" name="email_oficina" id="email_oficina" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 focus:outline-none focus:ring-2 block w-full p-2" placeholder="contato@oficina.com" required />
        </div>

        <!-- Campo de senha -->
        <div class="" id="senha-container">
          <label for="senha_oficina" class="block mb-1 text-sm font-medium text-gray-900">Senha</label>
          <input type="password" name="senha_oficina" id="senha_oficina" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 focus:outline-none focus:ring-2 block w-full p-2" placeholder="••••••••••••" required />
        </div>
      </div>

      <!-- Botão de submit -->
      <button type="submit" class="mt-4 cursor-pointer w-full text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">Acessar</button>

      <!-- Link para cadastro -->
      <div>
        <p class="text-sm font-light text-gray-500">
          Ainda não tem conta? <a href="/fixTime/PROJETO/src/views/Login/cadastro-company.php" class="font-medium hover:underline text-blue-500">Crie seu cadastro.</a>
        </p>
      </div>
    </form>
  </div>
</body>

</html>