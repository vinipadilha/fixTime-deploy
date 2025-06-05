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

function validarCNPJ($cnpj) {
    $cnpj = preg_replace('/[^0-9]/', '', $cnpj);

    if (strlen($cnpj) != 14 || preg_match('/(\d)\1{13}/', $cnpj)) return false;

    $soma1 = 0;
    $peso1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
    for ($i = 0; $i < 12; $i++) {
        $soma1 += $cnpj[$i] * $peso1[$i];
    }
    $resto1 = $soma1 % 11;
    $digito1 = ($resto1 < 2) ? 0 : 11 - $resto1;

    $soma2 = 0;
    $peso2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
    for ($i = 0; $i < 13; $i++) {
        $soma2 += $cnpj[$i] * $peso2[$i];
    }
    $resto2 = $soma2 % 11;
    $digito2 = ($resto2 < 2) ? 0 : 11 - $resto2;

    return ($cnpj[12] == $digito1 && $cnpj[13] == $digito2);
}


// Processa o formulário quando enviado via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Sanitiza e escapa todos os dados do formulário para prevenir SQL injection
  $categoria = $conexao->real_escape_string($_POST['categoria']);
  $nome_oficina = $conexao->real_escape_string($_POST['nome_oficina']);
  $cnpj = $conexao->real_escape_string($_POST['cnpj']);
  if (!validarCNPJ($cnpj)) {
    $_SESSION['error_message'] = 'CNPJ inválido. Verifique os dados e tente novamente.';
    header("Location: /fixTime/PROJETO/src/views/Login/cadastro-company.php");
    return;
  }


  $cep_oficina = $conexao->real_escape_string($_POST['cep_oficina']);
  $endereco_oficina = $conexao->real_escape_string($_POST['endereco_oficina']);
  $numero_oficina = $conexao->real_escape_string($_POST['numero_oficina']);
  $complemento = $conexao->real_escape_string($_POST['complemento'] ?? '');
  $bairro_oficina = $conexao->real_escape_string($_POST['bairro_oficina']);
  $cidade_oficina = $conexao->real_escape_string($_POST['cidade_oficina']);
  $estado_oficina = $conexao->real_escape_string($_POST['estado_oficina']);
  $telefone_oficina = $conexao->real_escape_string($_POST['telefone_oficina']);
  $email_oficina = $conexao->real_escape_string($_POST['email_oficina']);
  $senha_oficina = $conexao->real_escape_string($_POST['senha_oficina']);

  // Verifica se o CNPJ já está cadastrado no sistema
  $verificaCnpj = "SELECT cnpj FROM oficina WHERE cnpj = '$cnpj'";
  $resultadoCnpj = $conexao->query($verificaCnpj);

  if ($resultadoCnpj->num_rows > 0) {
    $_SESSION['error_message'] = 'CNPJ já cadastrado. Faça login ou use outro CNPJ.';
    header("Location: /fixTime/PROJETO/src/views/Login/cadastro-company.php");
    return;
  }

  // Verifica se o email já está cadastrado no sistema
  $verificaEmail = "SELECT email_oficina FROM oficina WHERE email_oficina = '$email_oficina'";
  $resultadoEmail = $conexao->query($verificaEmail);
  if ($resultadoEmail->num_rows > 0) {
    $_SESSION['error_message'] = 'E-mail já cadastrado. Faça login ou use outro e-mail.';
    header("Location: /fixTime/PROJETO/src/views/Login/cadastro-company.php");
    return;
  }

  // Cria um hash seguro da senha usando o algoritmo padrão do PHP
  $senha_hash = password_hash($senha_oficina, PASSWORD_DEFAULT);

  // Query SQL para inserir os dados da oficina no banco de dados
  $sql = "INSERT INTO oficina (
                categoria, 
                nome_oficina, 
                cnpj, 
                cep_oficina, 
                endereco_oficina, 
                numero_oficina, 
                complemento, 
                bairro_oficina, 
                cidade_oficina, 
                estado_oficina, 
                telefone_oficina, 
                email_oficina, 
                senha_oficina
            ) VALUES (
                '$categoria', 
                '$nome_oficina', 
                '$cnpj', 
                '$cep_oficina', 
                '$endereco_oficina', 
                '$numero_oficina', 
                '$complemento', 
                '$bairro_oficina', 
                '$cidade_oficina', 
                '$estado_oficina', 
                '$telefone_oficina', 
                '$email_oficina', 
                '$senha_hash'
            )";

  // Executa a query e redireciona em caso de sucesso
  if ($conexao->query($sql) === TRUE) {
    $_SESSION['success_message'] = 'Usuário cadastrado com sucesso!';
    header("Location: /fixTime/PROJETO/src/views/Login/login-company.php");
    return;
  } else {
    $_SESSION['error_message'] = "Erro: " . $sql . "<br>" . $conexao->error;
    header("Location: /fixTime/PROJETO/src/views/Login/cadastro-company.php");
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
  <div class="lg:w-auto lg:max-w-full w-full max-w-sm bg-white border border-gray-200 rounded-lg shadow-md lg:pt-4 p-3 lg:p-6 md:p-8 mt-12 lg:mt-3 mb-2 mx-2">
    <form class="space-y-3" method="POST" action="#">
      <!-- Seção de seleção de categoria -->
      <h1 class="block mb-2 text-md font-medium text-gray-900">Selecione a categoria do seu negócio:</h1>
      <div class="grid grid-cols-2 gap-4 mb-4">
        <!-- Opções de categoria usando radio buttons -->
        <div class="flex items-center">
          <input id="borracharia" type="radio" name="categoria" value="Borracharia" class="cursor-pointer w-4 h-4 text-blue-600 bg-gray-100 border-gray-300" checked required>
          <label for="borracharia" class="ms-1 text-sm font-medium text-gray-900">Borracharia</label>
        </div>

        <div class="flex items-center">
          <input id="mecanica" type="radio" name="categoria" value="Oficina Mecânica" class="cursor-pointer w-4 h-4 text-blue-600 bg-gray-100 border-gray-300">
          <label for="mecanica" class="ms-1 text-sm font-medium text-gray-900">Oficina Mecânica</label>
        </div>

        <div class="flex items-center">
          <input id="auto_eletrica" type="radio" name="categoria" value="Auto Elétrica" class="cursor-pointer w-4 h-4 text-blue-600 bg-gray-100 border-gray-300">
          <label for="auto_eletrica" class="ms-1 text-sm font-medium text-gray-900">Auto Elétrica</label>
        </div>

        <div class="flex items-center">
          <input id="lava_car" type="radio" name="categoria" value="Lava Car" class="cursor-pointer w-4 h-4 text-blue-600 bg-gray-100 border-gray-300">
          <label for="lava_car" class="ms-1 text-sm font-medium text-gray-900">Lava Car</label>
        </div>
      </div>

      <!-- Grid de campos do formulário -->
      <div class="lg:grid lg:grid-cols-4 lg:gap-x-6 lg:gap-y-2 lg:space-y-0 space-y-3">
        <!-- Campos de informações básicas -->
        <div class="col-span-2">
          <label for="nome_oficina" class="block mb-1 text-sm font-medium text-gray-900">Nome da oficina</label>
          <input type="text" name="nome_oficina" id="nome_oficina" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 focus:outline-none focus:ring-2 block w-full p-2" placeholder="Oficina Bacacheri" required />
        </div>

        <div class="col-span-2">
          <label for="cnpj" class="block mb-1 text-sm font-medium text-gray-900">CNPJ</label>
          <input type="tel" name="cnpj" id="cnpj" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 focus:outline-none focus:ring-2 block w-full p-2" placeholder="12.345.678/0001-95" required />
        </div>

        <!-- Campos de endereço -->
        <div class="col-span-2">
          <label for="cep_oficina" class="block mb-1 text-sm font-medium text-gray-900">CEP</label>
          <input type="tel" name="cep_oficina" id="cep_oficina" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 focus:outline-none focus:ring-2 block w-full p-2" placeholder="01001-000" required onblur="consultarCep()" />
        </div>

        <div class="col-span-2">
          <label for="telefone_oficina" class="block mb-1 text-sm font-medium text-gray-900">Telefone</label>
          <input type="tel" name="telefone_oficina" id="telefone_oficina" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 focus:outline-none focus:ring-2 block w-full p-2" placeholder="(41) 99988-7766" required />
        </div>

        <!-- Campos de endereço detalhado -->
        <div class="col-span-2">
          <label for="endereco_oficina" class="block mb-1 text-sm font-medium text-gray-900">Endereço</label>
          <input type="text" name="endereco_oficina" id="endereco_oficina" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 focus:outline-none focus:ring-2 block w-full p-2" placeholder="Rua Holanda" required />
        </div>

        <div class="col-span-1">
          <label for="numero_oficina" class="block mb-1 text-sm font-medium text-gray-900">Número</label>
          <input type="text" name="numero_oficina" id="numero_oficina" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 focus:outline-none focus:ring-2 block w-full p-2" placeholder="812" required maxlength="10" />
        </div>

        <div class="col-span-1">
          <label for="complemento" class="block mb-1 text-sm font-medium text-gray-900">Complemento (Opcional)</label>
          <input type="text" name="complemento" id="complemento" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 focus:outline-none focus:ring-2 block w-full p-2" placeholder="Sala 12" />
        </div>

        <!-- Campos de localização -->
        <div class="col-span-1">
          <label for="estado_oficina" class="block mb-1 text-sm font-medium text-gray-900">Estado</label>
          <input type="text" name="estado_oficina" id="estado_oficina" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 focus:outline-none focus:ring-2 block w-full p-2" placeholder="PR" required />
        </div>

        <div class="col-span-1">
          <label for="bairro_oficina" class="block mb-1 text-sm font-medium text-gray-900">Bairro</label>
          <input type="text" name="bairro_oficina" id="bairro_oficina" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 focus:outline-none focus:ring-2 block w-full p-2" placeholder="Cristo Rei" required />
        </div>

        <div class="col-span-2">
          <label for="cidade_oficina" class="block mb-1 text-sm font-medium text-gray-900">Cidade</label>
          <input type="text" name="cidade_oficina" id="cidade_oficina" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 focus:outline-none focus:ring-2 block w-full p-2" placeholder="Curitiba" required />
        </div>

        <!-- Campos de autenticação -->
        <div class="col-span-4">
          <label for="email_oficina" class="block mb-1 text-sm font-medium text-gray-900">Email</label>
          <input type="email" name="email_oficina" id="email_oficina" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 focus:outline-none focus:ring-2 block w-full p-2" placeholder="contato@oficina.com" required />
        </div>

        <div class="col-span-2" id="senha-container">
          <label for="senha_oficina" class="block mb-1 text-sm font-medium text-gray-900">Senha</label>
          <input type="password" name="senha_oficina" id="senha_oficina" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 focus:outline-none focus:ring-2 block w-full p-2" placeholder="••••••••••••" required />
        </div>

        <div class="col-span-2" id="confirma-senha-container">
          <label for="confirma_senha" class="block mb-1 text-sm font-medium text-gray-900">Confirmar senha</label>
          <input type="password" id="confirma_senha" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 focus:outline-none focus:ring-2 block w-full p-2" placeholder="••••••••••••" required />
          <p id="error-message" class="text-red-500 text-sm mt-2 hidden">As senhas não coincidem. Tente novamente.</p>
        </div>
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
    text: <?php echo json_encode($_SESSION['error_message']); ?>,
    confirmButtonText: 'OK'
  });
</script>
<?php unset($_SESSION['error_message']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['success_message'])): ?>
<script>
  Swal.fire({
    icon: 'success',
    title: 'Sucesso',
    text: <?php echo json_encode($_SESSION['success_message']); ?>,
    confirmButtonText: 'OK'
  });
</script>
<?php unset($_SESSION['success_message']); ?>
<?php endif; ?>


  <script>
    // Configuração das máscaras de input e validação de senha
    $(document).ready(function() {
      // Aplica máscaras nos campos de CNPJ, CEP e telefone
      $('#cnpj').mask('00.000.000/0000-00');
      $('#cep_oficina').mask('00000-000');
      $('#telefone_oficina').mask('(00) 00000-0000');

      // Validação em tempo real da confirmação de senha
      $('#confirma_senha').on('keyup', function() {
        if ($('#senha_oficina').val() != $('#confirma_senha').val()) {
          $('#error-message').removeClass('hidden');
          $('button[type="submit"]').prop('disabled', true);
        } else {
          $('#error-message').addClass('hidden');
          $('button[type="submit"]').prop('disabled', false);
        }
      });
    });
  </script>

</body>

</html>