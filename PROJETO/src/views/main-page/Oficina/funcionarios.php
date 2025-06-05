<?php
// Inclui o arquivo de conexão com o banco de dados
include $_SERVER['DOCUMENT_ROOT'] . '/fixTime/PROJETO/src/views/connect_bd.php';
$conexao = connect_db();

// Verifica se a conexão com o banco de dados foi estabelecida com sucesso
if (!isset($conexao) || !$conexao) {
    die("Erro ao conectar ao banco de dados. Verifique o arquivo connect_bd.php.");
}

// Inicia a sessão PHP para manter o estado do usuário
session_start();

// Verifica se o usuário está autenticado como oficina
if (!isset($_SESSION['id_oficina'])) {
    echo "<script>
        Swal.fire({
            icon: 'error',
            title: 'Erro!',
            text: 'Usuário não autenticado. Faça login novamente.',
            confirmButtonColor: '#3085d6'
        }).then((result) => {
            window.location.href = '/fixTime/PROJETO/src/views/Login/login-user.php';
        });</script>";
    exit;
}

// Obtém o ID da oficina da sessão
$id_usuario = $_SESSION['id_oficina'] ?? null;
$id_oficina = $id_usuario;

// Processa o formulário quando enviado via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitiza e valida os dados recebidos do formulário
    $nome = isset($_POST['nome_funcionario']) ? htmlspecialchars($_POST['nome_funcionario'], ENT_QUOTES, 'UTF-8') : '';
    $cargo = isset($_POST['cargo_funcionario']) ? htmlspecialchars($_POST['cargo_funcionario'], ENT_QUOTES, 'UTF-8') : '';
    $telefone = isset($_POST['telefone_funcionario']) ? htmlspecialchars($_POST['telefone_funcionario'], ENT_QUOTES, 'UTF-8') : '';
    $email = isset($_POST['email_funcionario']) ? htmlspecialchars($_POST['email_funcionario'], ENT_QUOTES, 'UTF-8') : '';
    $cpf = isset($_POST['cpf_funcionario']) ? htmlspecialchars($_POST['cpf_funcionario'], ENT_QUOTES, 'UTF-8') : '';
    $data_admissao = isset($_POST['data_admissao']) ? htmlspecialchars($_POST['data_admissao'], ENT_QUOTES, 'UTF-8') : '';

    // Valida se todos os campos obrigatórios foram preenchidos
    if (empty($nome) || empty($cargo) || empty($telefone) || empty($email) || empty($cpf) || empty($data_admissao)) {
        $_SESSION['alert'] = [
            'type' => 'error',
            'title' => 'Erro!',
            'text' => 'Preencha todos os campos corretamente.'
        ];
    } else {
        try {
            // Verifica se o CPF já está cadastrado
            $checkCpf = $conexao->prepare("SELECT COUNT(*) as total FROM funcionarios WHERE cpf_funcionario = ?");
            $checkCpf->bind_param("s", $cpf);
            $checkCpf->execute();
            $resultCpf = $checkCpf->get_result();
            $rowCpf = $resultCpf->fetch_assoc();
            
            if ($rowCpf['total'] > 0) {
                echo json_encode([
                    'type' => 'error',
                    'title' => 'Erro!',
                    'text' => 'Este CPF já está cadastrado no sistema.'
                ]);
                exit;
            } else {
                // Prepara a query SQL para inserir um novo funcionário
                $stmt = $conexao->prepare("INSERT INTO funcionarios (nome_funcionario, cargo_funcionario, telefone_funcionario, email_funcionario, cpf_funcionario, data_admissao, id_oficina) 
                             VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssssi", $nome, $cargo, $telefone, $email, $cpf, $data_admissao, $id_usuario);

                // Executa a query e verifica o resultado
                if ($stmt->execute()) {
                    echo json_encode([
                        'type' => 'success',
                        'title' => 'Sucesso!',
                        'text' => 'Funcionário cadastrado com sucesso!'
                    ]);
                } else {
                    echo json_encode([
                        'type' => 'error',
                        'title' => 'Erro!',
                        'text' => 'Erro ao cadastrar funcionário: ' . addslashes($stmt->error)
                    ]);
                }
                $stmt->close();
            }
            $checkCpf->close();
        } catch (Exception $e) {
            $erro = $e->getMessage();

            // Tratamento específico para erro de email duplicado
            if (str_contains($erro, 'Duplicate entry') && str_contains($erro, 'email_funcionario')) {
                echo json_encode([
                    'type' => 'error',
                    'title' => 'Erro!',
                    'text' => 'Este email já está cadastrado no sistema. Por favor, verifique os dados.'
                ]);
            } else {
                echo json_encode([
                    'type' => 'error',
                    'title' => 'Erro!',
                    'text' => 'Erro no banco de dados: ' . addslashes($erro)
                ]);
            }
        }
    }
    
    // Redireciona para evitar reenvio do formulário
    exit;
}

// Busca os funcionários cadastrados para a oficina atual
if ($id_oficina) {
    try {
        // Prepara a query SQL para buscar os funcionários
        $stmt = $conexao->prepare("SELECT id_funcionario, nome_funcionario as nome, cargo_funcionario as cargo, telefone_funcionario as telefone, 
                          email_funcionario as email, cpf_funcionario as cpf, data_admissao 
                          FROM funcionarios WHERE id_oficina = ? ORDER BY id_funcionario DESC");
        $stmt->bind_param("i", $id_oficina);
        $stmt->execute();
        $result = $stmt->get_result();

        // Armazena os resultados em um array
        while ($row = $result->fetch_assoc()) {
            $funcionarios[] = $row;
        }

        $stmt->close();
    } catch (Exception $e) {
        $mensagem = "<script>alert('Erro ao buscar funcionários: " . addslashes($e->getMessage()) . "');</script>";
    }
}

// Obtém o ID da oficina da sessão
$oficina_id = $_SESSION['id_oficina'] ?? null;

// Verifica novamente se o usuário está autenticado
if (!$oficina_id) {
    echo "<script>
        Swal.fire({
            icon: 'error',
            title: 'Erro!',
            text: 'Usuário não autenticado. Faça login novamente.',
            confirmButtonColor: '#3085d6'
        }).then((result) => {
            window.location.href = '/fixTime/PROJETO/src/views/Login/login-company.php';
        });</script>";
    exit();
}

// Busca os dados da oficina atual
$sql = "SELECT nome_oficina FROM oficina WHERE id_oficina = ?";
$stmt = $conexao->prepare($sql);
$stmt->bind_param("i", $oficina_id);
$stmt->execute();
$result = $stmt->get_result();

// Verifica se encontrou a oficina
if ($result->num_rows > 0) {
    $user_data = $result->fetch_assoc();
} else {
    die("Oficina não encontrada.");
}

// Exibe mensagens de feedback do sistema
if (isset($_SESSION['alert'])) {
    $alert = $_SESSION['alert'];
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: '" . $alert['type'] . "',
                title: '" . $alert['title'] . "',
                text: '" . $alert['text'] . "',
                confirmButtonColor: '#3085d6'
            });
        });</script>";
    unset($_SESSION['alert']);
}

?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">

<head>
    <!-- Meta tags para configuração básica da página -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Inclui o arquivo CSS do Tailwind para estilização -->
    <link rel="stylesheet" href="/fixTime/PROJETO/src/public/assets/css/output.css">
    <!-- Adiciona SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title>Fix Time</title>
</head>

<body class="">

    <!-- Botão do menu hamburguer para dispositivos móveis -->
    <button id="hamburgerButton" type="button" class="cursor-pointer inline-flex items-center p-2 mt-2 ms-3 text-sm text-gray-500 rounded-lg sm:hidden hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200">
        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
            <path clip-rule="evenodd" fill-rule="evenodd" d="M2 4.75A.75.75 0 012.75 4h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 4.75zm0 10.5a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5a.75.75 0 01-.75-.75zM2 10a.75.75 0 01.75-.75h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 10z"></path>
        </svg>
    </button>

    <!-- Sidebar de navegação -->
    <aside id="sidebar" class="fixed top-0 left-0 z-40 w-64 h-screen transition-transform -translate-x-full sm:translate-x-0">
        <div class="h-full px-3 py-4 bg-gray-50 flex flex-col justify-between">
            <div>
                <!-- Logo e botão de fechar menu -->
                <a class="flex items-center lg:justify-center justify-between ps-3 mx-auto mb-2">
                    <button id="closeHamburgerButton" type="button" class="cursor-pointer inline-flex items-center p-2 text-sm text-gray-500 rounded-lg sm:hidden hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200">
                        <svg class="w-6 h-6 " fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path clip-rule="evenodd" fill-rule="evenodd" d="M2 4.75A.75.75 0 012.75 4h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 4.75zm0 10.5a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5a.75.75 0 01-.75-.75zM2 10a.75.75 0 01.75-.75h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 10z"></path>
                        </svg>
                    </button>
                    <!-- Logo da empresa -->
                    <img src="/fixTime/PROJETO/src/public/assets/images/fixtime-truck.png" class="lg:h-14 h-12 me-3 " />
                </a>

                <!-- Menu de navegação -->
                <ul class="space-y-2 font-medium">
                    <!-- Link para página de funcionários -->
                    <li>
                        <a href="/fixTime/PROJETO/src/views/main-page/Oficina/funcionarios.php" class="flex items-center p-2 text-gray-900 rounded-lg  hover:bg-gray-100  group">
                            <svg class="shrink-0 w-6 h-6 text-gray-500 transition duration-75 group-hover:text-gray-900" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M4.5 17H4a1 1 0 0 1-1-1 3 3 0 0 1 3-3h1m0-3.05A2.5 2.5 0 1 1 9 5.5M19.5 17h.5a1 1 0 0 0 1-1 3 3 0 0 0-3-3h-1m0-3.05a2.5 2.5 0 1 0-2-4.45m.5 13.5h-7a1 1 0 0 1-1-1 3 3 0 0 1 3-3h3a3 3 0 0 1 3 3 1 1 0 0 1-1 1Zm-1-9.5a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0Z"/>
                            </svg>
                            <span class="flex-1 ms-3 whitespace-nowrap">Meus Funcionarios</span>
                        </a>
                    </li>

                    <!-- Link para perfil da oficina -->
                    <li>
                        <a href="/fixTime/PROJETO/src/views/main-page/Oficina/perfil-oficina.php" class="flex items-center p-2 text-gray-900 rounded-lg  hover:bg-gray-100  group">
                            <svg class="shrink-0 w-6 h-6 text-gray-500 transition duration-75 group-hover:text-gray-900" data-slot="icon" fill="none" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"></path>
                            </svg>
                            <span class="flex-1 ms-3 whitespace-nowrap">
                                <?php
                                    // Exibe apenas as duas primeiras palavras do nome da oficina
                                    $nomeCompleto = htmlspecialchars($user_data['nome_oficina']);
                                    $partes = explode(' ', $nomeCompleto);
                                    $duasPalavras = implode(' ', array_slice($partes, 0, 2));
                                    echo $duasPalavras;
                                ?>
                            </span>
                        </a>
                    </li>

                    <!-- Link para registro de serviços -->
                    <li>
                        <a href="/fixTime/PROJETO/src/views/main-page/Oficina/registrar-servicos.php" class="flex items-center p-2 text-gray-900 rounded-lg  hover:bg-gray-100  group">
                            <svg class="shrink-0 w-6 h-6 text-gray-500 transition duration-75 group-hover:text-gray-900"  xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.583 8.445h.01M10.86 19.71l-6.573-6.63a.993.993 0 0 1 0-1.4l7.329-7.394A.98.98 0 0 1 12.31 4l5.734.007A1.968 1.968 0 0 1 20 5.983v5.5a.992.992 0 0 1-.316.727l-7.44 7.5a.974.974 0 0 1-1.384.001Z"/>
                            </svg>
                            <span class="flex-1 ms-3 whitespace-nowrap">Registrar serviços</span>
                        </a>
                    </li>

                    <li>
                        <a href="/fixTime/PROJETO/src/views/main-page/Oficina/agendamentos-oficina.php" class="flex items-center p-2 text-gray-900 rounded-lg  hover:bg-gray-100  group">
                        <svg class="shrink-0 w-6 h-6 text-gray-500 transition duration-75 group-hover:text-gray-900" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                          <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 10h16m-8-3V4M7 7V4m10 3V4M5 20h14a1 1 0 0 0 1-1V7a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1Zm3-7h.01v.01H8V13Zm4 0h.01v.01H12V13Zm4 0h.01v.01H16V13Zm-8 4h.01v.01H8V17Zm4 0h.01v.01H12V17Zm4 0h.01v.01H16V17Z"/>
                        </svg>
                            <span class="flex-1 ms-3 whitespace-nowrap">Agendamentos</span>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Botão de logout -->
            <a href="/fixTime/PROJETO/src/views/Login/logout.php" class="flex items-center p-2 text-gray-900 rounded-lg  hover:bg-gray-100 group">
                <svg class="shrink-0 w-6 h-6 text-gray-500 transition duration-75 group-hover:text-gray-900" data-slot="icon" fill="none" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H8m12 0-4 4m4-4-4-4M9 4H7a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h2" />
                </svg>
                <span class="flex-1 ms-3 whitespace-nowrap font-medium">Logout</span>
            </a>
        </div>
    </aside>

    <!-- Conteúdo principal da página -->
    <div class="lg:ml-64 p-4 lg:p-14">
        <!-- Formulário de cadastro de funcionário -->
        <form id="formFuncionario" action="/fixTime/PROJETO/src/views/main-page/Oficina/funcionarios.php" method="POST">
            <div class="grid lg:gap-6 gap-4 mb-6 md:grid-cols-6">
                <!-- Campo Nome Completo -->
                <div class="lg:col-span-2 col-span-6">
                    <label for="nome_funcionario" class="block mb-2 text-sm font-medium text-gray-900">Nome Completo</label>
                    <input name="nome_funcionario" type="text" id="nome_funcionario" class="focus:ring-blue-500 focus:border-blue-500 border-2 bg-gray-50  border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 outline-none" placeholder="Ex: João Silva" required>
                </div>

                <!-- Campo Cargo/Função -->
                <div class="lg:col-span-2 col-span-6">
                    <label for="cargo_funcionario" class="block mb-2 text-sm font-medium text-gray-900">Cargo / Função</label>
                    <input type="text" name="cargo_funcionario" id="cargo_funcionario" placeholder="Digite o cargo do funcionário" class="focus:ring-blue-500 focus:border-blue-500 border-2 bg-gray-50 border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 outline-none" required />
                </div>

                <!-- Campo Telefone -->
                <div class="lg:col-span-2 col-span-6">
                    <label for="telefone_funcionario" class="block mb-2 text-sm font-medium text-gray-900">Telefone</label>
                    <input name="telefone_funcionario" type="text" id="telefone_funcionario" class="focus:ring-blue-500 focus:border-blue-500 border-2 bg-gray-50  border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 outline-none" placeholder="Ex: (11) 99999-9999" required>
                </div>
                
                <!-- Campo Email -->
                <div class="lg:col-span-2 col-span-6">
                    <label for="email_funcionario" class="block mb-2 text-sm font-medium text-gray-900">Email</label>
                    <input name="email_funcionario" type="email" id="email_funcionario" class="focus:ring-blue-500 focus:border-blue-500 border-2 bg-gray-50  border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 outline-none" placeholder="Ex: funcionario@email.com" required>
                </div>

                <!-- Campo CPF -->
                <div class="lg:col-span-2 col-span-6">
                    <label for="cpf_funcionario" class="block mb-2 text-sm font-medium text-gray-900">CPF</label>
                    <input name="cpf_funcionario" type="text" id="cpf_funcionario" class="focus:ring-blue-500 focus:border-blue-500 border-2 bg-gray-50 border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 outline-none" placeholder="Ex: 123.456.789-00" required />
                </div>

                <!-- Campo Data de Admissão -->
                <div class="lg:col-span-1 col-span-6">
                    <label for="data_admissao" class="block mb-2 text-sm font-medium text-gray-900">Data Admissão</label>
                    <input name="data_admissao" type="date" id="data_admissao" class="focus:ring-blue-500 focus:border-blue-500 border-2 bg-gray-50  border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 outline-none" required>
                </div>

                <!-- Botão de Registro -->
                <div class="lg:col-span-1 flex col-span-6">
                    <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm lg:w-full w-auto px-5 py-2.5 text-center cursor-pointer mt-7">Registrar</button>
                </div>
            </div>
        </form>

        <!-- Lista de funcionários cadastrados -->
        <?php if (!empty($funcionarios)): ?>
            <hr class="h-px my-8 bg-gray-200 border-0">
            <h1 class="text-xl font-bold mt-10 mb-4 text-center">Funcionários cadastrados</h1>

            <!-- Loop para exibir cada funcionário -->
            <?php foreach ($funcionarios as $funcionario): ?>
                <div class="mt-6" id="funcionario-<?= $funcionario['id_funcionario'] ?>">
                    <div class="p-4 bg-white border border-gray-200 rounded-lg shadow-sm">
                        <!-- Formulário de edição de funcionário -->
                        <form action="atualizar_funcionario.php" method="POST" class="form-funcionario">
                            <input type="hidden" name="id" value="<?= $funcionario['id_funcionario'] ?>">

                            <!-- Grid de campos do funcionário -->
                            <div class="grid lg:gap-6 gap-4 mb-6 lg:grid-cols-6 grid-cols-2">
                                <!-- Campos de dados do funcionário -->
                                <div class="col-span-1">
                                    <label class="block mb-1 text-sm font-medium text-gray-900">ID</label>
                                    <input type="text" value="<?= htmlspecialchars($funcionario['id_funcionario']) ?>" class="campo-id focus:ring-blue-500 focus:border-blue-500 border-2 bg-gray-50 border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2 cursor-not-allowed" disabled />
                                </div>

                                <div class="lg:col-span-2 col-span-1">
                                    <label for="nome-<?= $funcionario['id_funcionario'] ?>" class="block mb-1 text-sm font-medium text-gray-900">Nome</label>
                                    <input name="nome" type="text" id="nome-<?= $funcionario['id_funcionario'] ?>" value="<?= htmlspecialchars($funcionario['nome']) ?>" class="focus:ring-blue-500 focus:border-blue-500 border-2 bg-gray-50 border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2 outline-none cursor-not-allowed" disabled>
                                </div>

                                <div class="lg:col-span-2 col-span-1">
                                    <label for="cargo-<?= $funcionario['id_funcionario'] ?>" class="block mb-1 text-sm font-medium text-gray-900">Cargo / Função</label>
                                    <input name="cargo" type="text" id="nome-<?= $funcionario['id_funcionario'] ?>" value="<?= htmlspecialchars($funcionario['cargo']) ?>" class="focus:ring-blue-500 focus:border-blue-500 border-2 bg-gray-50 border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2 outline-none cursor-not-allowed" disabled>
                                </div>

                                <div class="lg:col-span-1 col-span-1">
                                    <label for="telefone-<?= $funcionario['id_funcionario'] ?>" class="block mb-1 text-sm font-medium text-gray-900">telefone</label>
                                    <input name="telefone" type="text" id="telefone-<?= $funcionario['id_funcionario'] ?>" value="<?= htmlspecialchars($funcionario['telefone']) ?>" class="focus:ring-blue-500 focus:border-blue-500 border-2 bg-gray-50 border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2 outline-none cursor-not-allowed" disabled>
                                </div>

                                <div class="lg:col-span-1 col-span-1">
                                    <label for="cpf-<?= $funcionario['id_funcionario'] ?>" class="block mb-1 text-sm font-medium text-gray-900">CPF</label>
                                    <input name="cpf" type="text" id="cpf-<?= $funcionario['id_funcionario'] ?>" value="<?= htmlspecialchars($funcionario['cpf']) ?>" class="focus:ring-blue-500 focus:border-blue-500 border-2 bg-gray-50 border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2 outline-none cursor-not-allowed" disabled>
                                </div>

                                <div class="lg:col-span-3 col-span-1">
                                    <label for="email-<?= $funcionario['id_funcionario'] ?>" class="block mb-1 text-sm font-medium text-gray-900">Email</label>
                                    <input name="email" type="email" id="email-<?= $funcionario['id_funcionario'] ?>" value="<?= htmlspecialchars($funcionario['email']) ?>" class="focus:ring-blue-500 focus:border-blue-500 border-2 bg-gray-50 border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2 outline-none cursor-not-allowed" disabled>
                                </div>

                                <div class="lg:col-span-2 col-span-1">
                                    <label for="data_admissao-<?= $funcionario['id_funcionario'] ?>" class="block mb-1 text-sm font-medium text-gray-900">Data Admissão</label>
                                    <input name="data_admissao" type="date" id="data_admissao-<?= $funcionario['id_funcionario'] ?>" value="<?= htmlspecialchars($funcionario['data_admissao']) ?>" class="focus:ring-blue-500 focus:border-blue-500 border-2 bg-gray-50 border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2 outline-none cursor-not-allowed" disabled>
                                </div>
                            </div>

                            <!-- Botões de ação -->
                            <div class="lg:gap-6 gap-4 items-center grid grid-cols-6">
                                <!-- Botão Editar/Salvar -->
                                <button type="button" class="editar-btn text-white inline-flex items-center justify-center gap-2 bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center cursor-pointer col-span-3" data-id="<?= $funcionario['id_funcionario'] ?>">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z"></path>
                                        <path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd"></path>
                                    </svg>
                                    Editar
                                </button>

                                <!-- Botão Excluir/Cancelar -->
                                <button type="button" class="excluir-btn inline-flex items-center justify-center gap-2 text-white bg-red-600 hover:bg-red-700 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center cursor-pointer col-span-3" data-id="<?= $funcionario['id_funcionario'] ?>">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                    Excluir
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <!-- Mensagem quando não há funcionários cadastrados -->
            <hr class="h-px my-8 bg-gray-300 border-0">
            <div class="mt-10 p-4 rounded-lg bg-gray-100 border-2 border-gray-300 shadow-xl flex items-center justify-between ">
                <div>
                    <p class="font-medium">Nenhum funcionário cadastrado.</p>
                    <p class="text-sm">Adicione seu primeiro funcionário usando o formulário acima.</p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Scripts JavaScript -->
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.js"></script>
        
        <!-- Script para aplicar máscaras nos campos -->
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

    $(document).ready(function () {
        $('#telefone_funcionario').mask('(00) 00000-0000');
        $('#cpf_funcionario').mask('000.000.000-00', {reverse: true});
        $('input[id^="telefone-"]').each(function () {
            $(this).mask('(00) 00000-0000');
        });
        $('input[id^="cpf-"]').each(function () {
        $(this).mask('000.000.000-00', {reverse: true});
        });

        $('#formFuncionario').on('submit', function (e) {
            e.preventDefault();

            const cpf = $('#cpf_funcionario').val();
            if (!validarCPF(cpf)) {
                Swal.fire({
                    icon: 'error',
                    title: 'CPF inválido',
                    text: 'Por favor, insira um CPF válido.',
                    confirmButtonColor: '#3085d6'
                });
                return;
            }

            $.ajax({
                type: 'POST',
                url: $(this).attr('action'),
                data: $(this).serialize(),
                dataType: 'json',
                success: function (response) {
                    Swal.fire({
                        icon: response.type,
                        title: response.title,
                        text: response.text,
                        confirmButtonColor: '#3085d6'
                    }).then((result) => {
                        if (result.isConfirmed && response.type === 'success') {
                            window.location.reload();
                        }
                    });
                },
                error: function () {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro!',
                        text: 'Erro ao cadastrar funcionário. Por favor, tente novamente.',
                        confirmButtonColor: '#3085d6'
                    });
                }
            });
        });
    });


            // Controle do menu hamburguer para dispositivos móveis
            const hamburgerButton = document.getElementById('hamburgerButton');
            const closeHamburgerButton = document.getElementById('closeHamburgerButton');
            const sidebar = document.getElementById('sidebar');

            // Evento para abrir o menu ao clicar no botão hamburguer
            hamburgerButton.addEventListener('click', () => {
                sidebar.classList.toggle('-translate-x-full');
            });

            // Evento para fechar o menu ao clicar no botão de fechar
            closeHamburgerButton.addEventListener('click', () => {
                sidebar.classList.add('-translate-x-full');
            });

            // Controle de edição de funcionários
            document.querySelectorAll('.editar-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const form = this.closest('.form-funcionario');
                    const inputs = form.querySelectorAll('input:not([type="hidden"]):not(.campo-id), select');
                    const isEditing = this.textContent.trim() === 'Editar';

                    if (isEditing) {
                        // Habilita a edição dos campos
                        inputs.forEach(input => {
                            input.disabled = false;
                            input.classList.remove('cursor-not-allowed');
                        });

                        // Atualiza o botão para modo de salvar
                        this.innerHTML = `
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Salvar
                        `;
                        this.classList.remove('bg-blue-700', 'hover:bg-blue-800', 'focus:ring-blue-300');
                        this.classList.add('bg-blue-700', 'hover:bg-blue-800', 'focus:ring-blue-300');

                        // Atualiza o botão de excluir para cancelar
                        const excluirBtn = form.querySelector('.excluir-btn');
                        excluirBtn.innerHTML = `
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                            Cancelar
                        `;
                        excluirBtn.classList.remove('bg-blue-700', 'hover:bg-blue-800', 'focus:ring-blue-300');
                        excluirBtn.classList.add('bg-blue-700', 'hover:bg-blue-800', 'focus:ring-blue-300');
                    } else {
                        // Valida os campos antes de enviar
                        let isValid = true;
                        inputs.forEach(input => {
                            if (input.value.trim() === '') {
                                isValid = false;
                            }
                        });

                        if (!isValid) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Erro!',
                                text: 'Preencha todos os campos corretamente.',
                                confirmButtonColor: '#3085d6'
                            });
                            return;
                        }

                        const cpfInput = form.querySelector('input[name="cpf_funcionario"]');
                        if (cpfInput && !validarCPF(cpfInput.value)) {
                            Swal.fire({
                                icon: 'error',
                                title: 'CPF inválido',
                                text: 'Por favor, insira um CPF válido.',
                                confirmButtonColor: '#3085d6'
                            });
                            return;
                        }

                        // Envia o formulário via AJAX
                        $.ajax({
                            type: 'POST',
                            url: form.action,
                            data: $(form).serialize(),
                            success: function(response) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Sucesso!',
                                    text: 'Funcionário atualizado com sucesso!',
                                    confirmButtonColor: '#3085d6'
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        window.location.reload();
                                    }
                                });
                            },
                            error: function(xhr, status, error) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Erro!',
                                    text: 'Erro ao atualizar funcionário. Por favor, tente novamente.',
                                    confirmButtonColor: '#3085d6'
                                });
                            }
                        });
                    }
                });
            });

            // Controle de exclusão/cancelamento de funcionários
            document.querySelectorAll('.excluir-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const form = this.closest('.form-veiculo');

                    if (this.textContent.trim() === 'Excluir') {
                        // Confirma a exclusão do funcionário
                        Swal.fire({
                            title: 'Tem certeza? Você não poderá reverter esta ação!',
                            text: "Verifique se o funcionário não esta associado a nenhum serviço.",
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33',
                            confirmButtonText: 'Sim, excluir!',
                            cancelButtonText: 'Cancelar'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Cria um formulário temporário para exclusão
                                const deleteForm = document.createElement('form');
                                deleteForm.action = 'excluir_funcionario.php';
                                deleteForm.method = 'POST';

                                const inputId = document.createElement('input');
                                inputId.type = 'hidden';
                                inputId.name = 'id';
                                inputId.value = id;

                                deleteForm.appendChild(inputId);
                                document.body.appendChild(deleteForm);
                                deleteForm.submit();
                            }
                        });
                    } else {
                        // Cancela a edição e restaura o estado original
                        const inputs = form.querySelectorAll('input:not([type="hidden"]), select');
                        inputs.forEach(input => input.disabled = true);

                        // Restaura o botão de editar
                        const editarBtn = form.querySelector('.editar-btn');
                        editarBtn.innerHTML = `
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z"></path>
                                <path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd"></path>
                            </svg>
                            Editar
                        `;
                        editarBtn.classList.remove('bg-green-600', 'hover:bg-green-700');
                        editarBtn.classList.add('bg-blue-700', 'hover:bg-blue-800');

                        // Restaura o botão de excluir
                        this.innerHTML = `
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                            Excluir
                        `;
                        this.classList.remove('bg-yellow-500', 'hover:bg-yellow-600');
                        this.classList.add('bg-red-600', 'hover:bg-red-700');

                        // Recarrega o formulário para descartar alterações
                        form.reset();
                    }
                });
            });
        </script>
    </div>
</body>

</html>