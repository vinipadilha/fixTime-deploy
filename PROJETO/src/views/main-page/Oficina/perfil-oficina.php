<?php
// Inclui o arquivo de conexão com o banco de dados
include $_SERVER['DOCUMENT_ROOT'] . '/fixTime/PROJETO/src/views/connect_bd.php';
$conexao = connect_db(); // Estabelece conexão com o banco de dados

// Verifica se a conexão foi estabelecida com sucesso
if (!isset($conexao) || !$conexao) {
    die("Erro ao conectar ao banco de dados. Verifique o arquivo connect_bd.php.");
}

// Inicia a sessão PHP para manter o estado do usuário
session_start();

// Obtém o ID da oficina da sessão
$oficina_id = $_SESSION['id_oficina'];

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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifica se o botão de excluir perfil foi acionado
    if (isset($_POST['excluir_perfil']) && $_POST['excluir_perfil'] === '1') {
        // Verifica se existem funcionários cadastrados antes de excluir a oficina
        $sqlCheckFuncionarios = "SELECT COUNT(*) as total FROM funcionarios WHERE id_oficina = ?";
        $stmtCheck = $conexao->prepare($sqlCheckFuncionarios);
        $stmtCheck->bind_param("i", $oficina_id);
        $stmtCheck->execute();
        $resultCheck = $stmtCheck->get_result();
        $row = $resultCheck->fetch_assoc();
        $totalFuncionarios = $row['total'];
        $stmtCheck->close();
        
        // Se houver funcionários cadastrados, redireciona para a página de funcionários
        if ($totalFuncionarios > 0) {
            session_start();
            $_SESSION['alert'] = [
                'type' => 'warning',
                'title' => 'Atenção!',
                'text' => 'Você precisa excluir todos os funcionários antes de excluir o perfil da oficina.'
            ];
            header("Location: /fixTime/PROJETO/src/views/main-page/Oficina/funcionarios.php");
            exit();
        }

        $deleteAvaliacoes = $conexao->prepare("
            DELETE a FROM avaliacao a
            INNER JOIN servico s ON a.id_servico = s.id_servico
            WHERE s.id_oficina = ?
        ");
        $deleteAvaliacoes->bind_param("i", $oficina_id);
        $deleteAvaliacoes->execute();
        $deleteAvaliacoes->close();

        $deleteServicos = $conexao->prepare("DELETE FROM servico WHERE id_oficina = ?");
        $deleteServicos->bind_param("i", $oficina_id);
        $deleteServicos->execute();
        $deleteServicos->close();

        $deleteServicos = $conexao->prepare("DELETE FROM oficina_servicos WHERE id_oficina = ?");
        $deleteServicos->bind_param("i", $oficina_id);
        $deleteServicos->execute();
        $deleteServicos->close();

        $sqlDelete = "DELETE FROM oficina WHERE id_oficina = ?";
        $stmtDelete = $conexao->prepare($sqlDelete);
        $stmtDelete->bind_param("i", $oficina_id);

        // Executa a exclusão e verifica o resultado
        if ($stmtDelete->execute()) {
            session_destroy(); // Encerra a sessão do usuário
            $_SESSION['alert'] = [
                'type' => 'success',
                'title' => 'Sucesso!',
                'text' => 'Perfil excluído com sucesso.'
            ];
            header("Location: /fixTime/PROJETO/index.html");
            exit();
        } else {
            $_SESSION['alert'] = [
                'type' => 'error',
                'title' => 'Erro!',
                'text' => 'Erro ao excluir perfil: ' . $conexao->error
            ];
            header("Location: /fixTime/PROJETO/src/views/main-page/Oficina/perfil-oficina.php");
            exit();
        }

        $stmtDelete->close();
    }
    // Processa a atualização do perfil
    else if (isset($_POST['salvar_perfil'])) {
        // Sanitiza e valida os dados recebidos do formulário
        $nome = trim($_POST['nome'] ?? '');
        $categoria = trim($_POST['categoria'] ?? '');
        $cep = trim($_POST['cep'] ?? '');
        $cnpj = trim($_POST['cnpj'] ?? '');

        if (!validarCNPJ($cnpj)) {
            echo json_encode([
                'type' => 'error',
                'title' => 'CNPJ inválido',
                'text' => 'Por favor, insira um CNPJ válido.'
            ]);
            exit;
        }

        $endereco = trim($_POST['endereco'] ?? '');
        $numero = trim($_POST['numero'] ?? '');
        $complemento = trim($_POST['complemento'] ?? '');
        $bairro = trim($_POST['bairro'] ?? '');
        $cidade = trim($_POST['cidade'] ?? '');
        $estado = trim($_POST['estado'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');
        $email = trim($_POST['email'] ?? '');

        // Validação dos campos obrigatórios e categoria
        $validCategorias = ['Borracharia', 'Auto Elétrica', 'Oficina Mecânica', 'Lava Car'];
        if (empty($nome) || empty($cnpj) || empty($email)) {
            echo json_encode([
                'type' => 'error',
                'title' => 'Erro!',
                'text' => 'Nome, CNPJ e Email são campos obrigatórios.'
            ]);
            exit();
        }
        if (!in_array($categoria, $validCategorias)) {
            echo json_encode([
                'type' => 'error',
                'title' => 'Erro!',
                'text' => 'Categoria inválida.'
            ]);
            exit();
        }

        // Prepara e executa a query de atualização
        $sqlUpdate = "UPDATE oficina SET nome_oficina = ?, categoria = ?, cep_oficina = ?, cnpj = ?, endereco_oficina = ?, numero_oficina = ?, complemento = ?, bairro_oficina = ?, cidade_oficina = ?, estado_oficina = ?, telefone_oficina = ?, email_oficina = ? WHERE id_oficina = ?";
        $stmtUpdate = $conexao->prepare($sqlUpdate);
        $stmtUpdate->bind_param("ssssssssssssi", $nome, $categoria, $cep, $cnpj, $endereco, $numero, $complemento, $bairro, $cidade, $estado, $telefone, $email, $oficina_id);

        // Executa a atualização e retorna o resultado
        if ($stmtUpdate->execute()) {
            echo json_encode([
                'type' => 'success',
                'title' => 'Sucesso!',
                'text' => 'Suas alterações foram salvas com sucesso!'
            ]);
        } else {
            echo json_encode([
                'type' => 'error',
                'title' => 'Erro!',
                'text' => 'Erro ao atualizar perfil: ' . addslashes($conexao->error)
            ]);
        }
        exit();
    }
}

// Verifica se o usuário está autenticado
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

// Busca os dados atuais da oficina
$sql = "SELECT nome_oficina, categoria, cep_oficina, cnpj, endereco_oficina, numero_oficina, complemento, bairro_oficina, cidade_oficina, estado_oficina, telefone_oficina, email_oficina FROM oficina WHERE id_oficina = ?";
$stmt = $conexao->prepare($sql);
$stmt->bind_param("i", $oficina_id);
$stmt->execute();
$result = $stmt->get_result();

// Verifica se encontrou a oficina e armazena os dados
if ($result->num_rows > 0) {
    $user_data = $result->fetch_assoc();
} else {
    echo "<script>
        Swal.fire({
            icon: 'error',
            title: 'Erro!',
            text: 'Oficina não encontrada. Faça login novamente.',
            confirmButtonColor: '#3085d6'
        }).then((result) => {
            window.location.href = '/fixTime/PROJETO/src/views/Login/login-company.php';
        });</script>";
    exit();
}

// Fecha o statement e a conexão com o banco de dados
$stmt->close();
$conexao->close();
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

<body>
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
                    <img src="/fixTime/PROJETO/src/public/assets/images/fixtime-truck.png" class="lg:h-14 h-12 me-3 "/>
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
                            <svg class="shrink-0 w-6 h-6 text-gray-500 transition duration-75 group-hover:text-gray-900" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
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
            <div>
                <a href="/fixTime/PROJETO/src/views/Login/logout.php" class="flex items-center p-2 text-gray-900 rounded-lg hover:bg-gray-100 group">
                    <svg class="shrink-0 w-6 h-6 text-gray-500 transition duration-75 group-hover:text-gray-900" fill="none" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H8m12 0-4 4m4-4-4-4M9 4H7a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h2"/>
                    </svg>
                    <span class="flex-1 ms-3 whitespace-nowrap font-medium">Logout</span>
                </a>
            </div>
        </div>
    </aside>

    <!-- Conteúdo principal da página -->
    <div class="lg:ml-64 lg:py-10 py-4 lg:px-32 px-8">
        <!-- Formulário de perfil -->
        <div class="p-8 bg-white border border-gray-200 rounded-lg shadow-sm">
            <form id="formPerfil" method="POST" action="perfil-oficina.php">
                <!-- Grid de campos do formulário -->
                <div class="grid grid-cols-2 gap-4">
                    <!-- Campos do formulário -->
                    <!-- Nome da oficina -->
                    <div class="">
                        <label for="nome-perfil" class="block mb-1 text-sm font-medium text-gray-900">Oficina</label>
                        <input type="text" id="nome-perfil" name="nome" value="<?php echo htmlspecialchars($user_data['nome_oficina']); ?>" class="cursor-not-allowed bg-gray-50 border-2 border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 outline-none" disabled />
                    </div>

                    <!-- CNPJ -->
                    <div class="">
                        <label for="cnpj-perfil" class="block mb-1 text-sm font-medium text-gray-900">CNPJ</label>
                        <input type="text" id="cnpj-perfil" name="cnpj" value="<?php echo htmlspecialchars($user_data['cnpj']); ?>" class="cursor-not-allowed bg-gray-50 border-2 border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 outline-none" disabled />
                    </div>

                    <!-- Categoria -->
                    <div class="">
                        <label for="categoria-perfil" class="block mb-1 text-sm font-medium text-gray-900">Categoria</label>
                        <select id="categoria-perfil" name="categoria" class="cursor-not-allowed bg-gray-50 border-2 border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 outline-none" disabled>
                            <option value="Borracharia" <?php echo $user_data['categoria'] === 'Borracharia' ? 'selected' : ''; ?>>Borracharia</option>
                            <option value="Auto Elétrica" <?php echo $user_data['categoria'] === 'Auto Elétrica' ? 'selected' : ''; ?>>Auto Elétrica</option>
                            <option value="Oficina Mecânica" <?php echo $user_data['categoria'] === 'Oficina Mecânica' ? 'selected' : ''; ?>>Oficina Mecânica</option>
                            <option value="Lava Car" <?php echo $user_data['categoria'] === 'Lava Car' ? 'selected' : ''; ?>>Lava Car</option>
                        </select>
                    </div>

                    <!-- Telefone -->
                    <div class="">
                        <label for="telefone-perfil" class="block mb-1 text-sm font-medium text-gray-900">Número de telefone</label>
                        <input type="text" id="telefone-perfil" name="telefone" value="<?php echo htmlspecialchars($user_data['telefone_oficina']); ?>" class="cursor-not-allowed bg-gray-50 border-2 border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 outline-none" disabled />
                    </div>

                    <!-- Email -->
                    <div class="">
                        <label for="email-perfil" class="block mb-1 text-sm font-medium text-gray-900">Email</label>
                        <input type="email" id="email-perfil" name="email" value="<?php echo htmlspecialchars($user_data['email_oficina']); ?>" class="cursor-not-allowed bg-gray-50 border-2 border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 outline-none" disabled />
                    </div>

                    <!-- CEP -->
                    <div class="">
                        <label for="cep-perfil" class="block mb-1 text-sm font-medium text-gray-900">CEP</label>
                        <input type="text" id="cep-perfil" name="cep" value="<?php echo htmlspecialchars($user_data['cep_oficina']); ?>" class="cursor-not-allowed bg-gray-50 border-2 border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 outline-none" disabled onblur="consultarCep()"/>
                    </div>

                    <!-- Endereço -->
                    <div class="">
                        <label for="endereco-perfil" class="block mb-1 text-sm font-medium text-gray-900">Endereço</label>
                        <input type="text" id="endereco-perfil" name="endereco" value="<?php echo htmlspecialchars($user_data['endereco_oficina']); ?>" class="cursor-not-allowed bg-gray-50 border-2 border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 outline-none" disabled />
                    </div>

                    <!-- Bairro -->
                    <div class="">
                        <label for="bairro-perfil" class="block mb-1 text-sm font-medium text-gray-900">Bairro</label>
                        <input type="text" id="bairro-perfil" name="bairro" value="<?php echo htmlspecialchars($user_data['bairro_oficina']); ?>" class="cursor-not-allowed bg-gray-50 border-2 border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 outline-none" disabled />
                    </div>

                    <!-- Número -->
                    <div class="">
                        <label for="numero-perfil" class="block mb-1 text-sm font-medium text-gray-900">Número</label>
                        <input type="text" id="numero-perfil" name="numero" value="<?php echo htmlspecialchars($user_data['numero_oficina']); ?>" class="cursor-not-allowed bg-gray-50 border-2 border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 outline-none" disabled />
                    </div>

                    <!-- Cidade -->
                    <div class="">
                        <label for="cidade-perfil" class="block mb-1 text-sm font-medium text-gray-900">Cidade</label>
                        <input type="text" id="cidade-perfil" name="cidade" value="<?php echo htmlspecialchars($user_data['cidade_oficina']); ?>" class="cursor-not-allowed bg-gray-50 border-2 border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 outline-none" disabled />
                    </div>

                    <!-- Estado -->
                    <div class="">
                        <label for="estado-perfil" class="block mb-1 text-sm font-medium text-gray-900">Estado</label>
                        <input type="text" id="estado-perfil" name="estado" value="<?php echo htmlspecialchars($user_data['estado_oficina']); ?>" class="cursor-not-allowed bg-gray-50 border-2 border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 outline-none" disabled />
                    </div>

                    <!-- Complemento -->
                    <div class="">
                        <label for="complemento-perfil" class="block mb-1 text-sm font-medium text-gray-900">Complemento</label>
                        <input type="text" id="complemento-perfil" name="complemento" value="<?php echo htmlspecialchars($user_data['complemento']); ?>" class="cursor-not-allowed bg-gray-50 border-2 border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 outline-none" disabled />
                    </div>
                </div>

                <!-- Botões de ação -->
                <div class="lg:gap-6 gap-4 items-center grid grid-cols-6 mt-6">
                    <!-- Botão Editar/Salvar -->
                    <button id="editarPerfilBtn" type="button" class="text-white inline-flex items-center justify-center gap-2 bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center cursor-pointer col-span-3">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z"></path>
                            <path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd"></path>
                        </svg>
                        Editar
                    </button>

                    <!-- Botão Excluir -->
                    <button id="excluirPerfilBtn" type="button" class="inline-flex items-center justify-center gap-2 text-white bg-red-600 hover:bg-red-700 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center cursor-pointer col-span-3">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        Excluir
                    </button>
                </div>

                <!-- Campo oculto para salvar perfil -->
                <input type="hidden" name="salvar_perfil" value="1">

                <!-- Campo oculto para exclusão -->
                <input type="hidden" name="excluir_perfil" id="inputExcluirPerfil" value="">
            </form>
        </div>
    </div>

    <!-- Scripts JavaScript -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.js"></script>
    <script>
        // Controle do menu hamburguer
        const hamburgerButton = document.getElementById('hamburgerButton');
        const closeHamburgerButton = document.getElementById('closeHamburgerButton');
        const sidebar = document.getElementById('sidebar');

        // Evento para abrir o menu
        hamburgerButton.addEventListener('click', () => {
            sidebar.classList.toggle('-translate-x-full');
        });

        // Evento para fechar o menu
        closeHamburgerButton.addEventListener('click', () => {
            sidebar.classList.add('-translate-x-full');
        });

        // Controle dos botões de edição e exclusão
        document.addEventListener('DOMContentLoaded', function() {
            const editarBtn = document.getElementById('editarPerfilBtn');
            const excluirBtn = document.getElementById('excluirPerfilBtn');
            const form = document.getElementById('formPerfil');
            let modoEdicao = false;

            // Manipula o botão de edição
            editarBtn.addEventListener('click', function() {
                if (!modoEdicao) {
                    // Habilita edição dos campos
                    document.querySelectorAll('input, select').forEach(input => {
                        input.disabled = false;
                        input.classList.remove('cursor-not-allowed');
                    });

                    editarBtn.textContent = 'Salvar';
                    modoEdicao = true;

                    // Aplica máscaras nos campos
                    $('#telefone-perfil').mask('(00) 00000-0000');
                    $('#cnpj-perfil').mask('00.000.000/0000-00', {
                        reverse: true
                    });
                } else {
                    // Envia o formulário via AJAX
                    $.ajax({
                        type: 'POST',
                        url: form.action,
                        data: $(form).serialize(),
                        dataType: 'json',
                        success: function(response) {
                            Swal.fire({
                                icon: response.type,
                                title: response.title,
                                text: response.text,
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
                                text: 'Erro ao atualizar perfil. Por favor, tente novamente.',
                                confirmButtonColor: '#3085d6'
                            });
                        }
                    });
                }
            });

            // Manipula o botão de exclusão
            excluirBtn.addEventListener('click', function() {
                Swal.fire({
                    title: 'Tem certeza que deseja excluir o perfil da oficina?',
                    html: `
                        <p class="text-md text-gray-500 mt-2">Todos os agendamentos serão cancelados e as avaliações excluídas.</p>
                        <p class="text-sm text-red-500 mt-2">Observação: Você precisará excluir todos os funcionários cadastrados antes de excluir o perfil.</p>
                    `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sim, excluir!',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('inputExcluirPerfil').value = '1';
                        form.submit();
                    }
                });
            });
        });
    </script>

    <!-- Inclusão de bibliotecas JavaScript -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.js"></script>
    <script src="/fixTime/PROJETO/src/public/assets/js/script.js"></script>
</body>
</html>