<?php
// Inclui o arquivo de conexão com o banco de dados
include $_SERVER['DOCUMENT_ROOT'] . '/fixTime/PROJETO/src/views/connect_bd.php';
$conexao = connect_db(); // conecta com o bd

// Verifica se a conexão foi estabelecida corretamente
if (!isset($conexao) || !$conexao) {
    die("Erro ao conectar ao banco de dados. Verifique o arquivo connect_bd.php.");
}

// Inicia a sessão para gerenciar dados do usuário
session_start();

// Obtém o ID do usuário da sessão
$user_id = $_SESSION['id_usuario'];

// Verifica se o usuário está autenticado
if (!isset($_SESSION['id_usuario'])) {
    $_SESSION['error_message'] = 'Usuário não autenticado. Faça login novamente.';
    header("Location: /fixTime/PROJETO/src/views/Login/login-user.php");
    return;
}

// Processa o formulário quando enviado via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifica se o botão de excluir perfil foi pressionado
    if (isset($_POST['excluir_perfil']) && $_POST['excluir_perfil'] === '1') {
        // Verifica se existem veículos cadastrados para este cliente
        $sqlCheckVeiculos = "SELECT COUNT(*) as total FROM veiculos WHERE id_usuario = ?";
        $stmtCheck = $conexao->prepare($sqlCheckVeiculos);
        $stmtCheck->bind_param("i", $user_id);
        $stmtCheck->execute();
        $resultCheck = $stmtCheck->get_result();
        $row = $resultCheck->fetch_assoc();
        $totalVeiculos = $row['total'];
        $stmtCheck->close();
        
        // Se houver veículos cadastrados, redireciona para a página de veículos
        if ($totalVeiculos > 0) {
            $_SESSION['error_message'] = 'Você precisa excluir todos os seus veículos antes de excluir o perfil.';
            header("Location: /fixTime/PROJETO/src/views/main-page/Cliente/veiculos.php");
            exit();
        }

        // Prepara e executa a query para excluir o perfil
        $sqlDelete = "DELETE FROM cliente WHERE id_usuario = ?";
        $stmtDelete = $conexao->prepare($sqlDelete);
        $stmtDelete->bind_param("i", $user_id);

        // Executa a exclusão e verifica o resultado
        if ($stmtDelete->execute()) {
            session_destroy(); // Encerra a sessão do usuário
            $_SESSION['success_message'] = 'Perfil excluído com sucesso.';
            header("Location: /fixTime/PROJETO/index.html");
            exit();
        } else {
            $_SESSION['error_message'] = "Erro ao excluir perfil: " . $conexao->error;
            header("Location: /fixTime/PROJETO/src/views/main-page/Cliente/perfil.php");
            exit();
        }

        $stmtDelete->close();
    }
    // Processa a atualização do perfil
    else if (isset($_POST['salvar_perfil'])) {
        // Recupera e sanitiza os dados do formulário
        $nome = trim($_POST['nome'] ?? '');
        $cpf = trim($_POST['cpf'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');
        $email = trim($_POST['email'] ?? '');

        // Prepara a query de atualização
        $sqlUpdate = "UPDATE cliente SET nome_usuario = ?, cpf = ?, telefone_usuario = ?, email_usuario = ? WHERE id_usuario = ?";
        $stmtUpdate = $conexao->prepare($sqlUpdate);
        $stmtUpdate->bind_param("ssssi", $nome, $cpf, $telefone, $email, $user_id);

        // Executa a atualização e verifica o resultado
        if ($stmtUpdate->execute()) {
            $_SESSION['success_message'] = 'Suas alterações foram salvas com sucesso!';
            header("Location: /fixTime/PROJETO/src/views/main-page/Cliente/perfil.php");
            return;
        } else {
            $_SESSION['error_message'] = "Erro ao atualizar perfil: " . $conexao->error;
            header("Location: /fixTime/PROJETO/src/views/main-page/Cliente/perfil.php");
            return;
        }

        $stmtUpdate->close();
    }
}

// Busca os dados atuais do usuário
$sql = "SELECT nome_usuario, cpf, telefone_usuario, email_usuario FROM cliente WHERE id_usuario = ?";
$stmt = $conexao->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Verifica se encontrou o usuário
if ($result->num_rows > 0) {
    $user_data = $result->fetch_assoc();
} else {
    die("Usuário não encontrado.");
}

// Fecha as conexões com o banco de dados
$stmt->close();
$conexao->close();
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <!-- Meta tags para configuração do documento -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Link para o arquivo CSS do Tailwind -->
    <link rel="stylesheet" href="/fixTime/PROJETO/src/public/assets/css/output.css">
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

    <!-- Barra lateral (sidebar) -->
    <aside id="sidebar" class="fixed top-0 left-0 z-40 w-64 h-screen transition-transform -translate-x-full sm:translate-x-0">
        <div class="h-full px-3 py-4 bg-gray-50 flex flex-col justify-between">
            <!-- Cabeçalho da sidebar -->
            <div>
                <a class="flex items-center lg:justify-center justify-between ps-3 mx-auto mb-2">
                    <!-- Botão para fechar o menu em dispositivos móveis -->
                    <button id="closeHamburgerButton" type="button" class="cursor-pointer inline-flex items-center p-2 text-sm text-gray-500 rounded-lg sm:hidden hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200">
                        <svg class="w-6 h-6 " fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path clip-rule="evenodd" fill-rule="evenodd" d="M2 4.75A.75.75 0 012.75 4h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 4.75zm0 10.5a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5a.75.75 0 01-.75-.75zM2 10a.75.75 0 01.75-.75h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 10z"></path>
                        </svg>
                    </button>
                    <!-- Logo da aplicação -->
                    <img src="/fixTime/PROJETO/src/public/assets/images/fixtime-truck.png" class="lg:h-14 h-12 me-3 " />
                </a>

                <!-- Menu de navegação -->
                <ul class="space-y-2 font-medium">
                    <!-- Link para Prestadores de Serviço -->
                    <li>
                        <a href="/fixTime/PROJETO/src/views/main-page/Cliente/prestadores-servico.php" class="flex items-center p-2 text-gray-900 rounded-lg  hover:bg-gray-100  group">
                            <svg class="shrink-0 w-6 h-6 text-gray-500 transition duration-75 group-hover:text-gray-900" data-slot="icon" fill="none" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"></path>
                            </svg>
                            <span class="flex-1 ms-3 whitespace-nowrap">Prestadores de serviços</span>
                        </a>
                    </li>

                    <!-- Link para Meus Veículos -->
                    <li>
                        <a href="/fixTime/PROJETO/src/views/main-page/Cliente/veiculos.php" class="flex items-center p-2 text-gray-900 rounded-lg  hover:bg-gray-100  group">
                            <svg class="shrink-0 w-6 h-6 text-gray-500 transition duration-75 group-hover:text-gray-900" data-slot="icon" fill="none" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"></path>
                            </svg>
                            <span class="flex-1 ms-3 whitespace-nowrap">Meus veículos</span>
                        </a>
                    </li>

                    <li>
                        <a href="/fixTime/PROJETO/src/views/main-page/Cliente/meus-agendamentos.php" class="flex items-center p-2 text-gray-900 rounded-lg  hover:bg-gray-100  group">
                        <svg class="shrink-0 w-6 h-6 text-gray-500 transition duration-75 group-hover:text-gray-900" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                          <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 10h16m-8-3V4M7 7V4m10 3V4M5 20h14a1 1 0 0 0 1-1V7a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1Zm3-7h.01v.01H8V13Zm4 0h.01v.01H12V13Zm4 0h.01v.01H16V13Zm-8 4h.01v.01H8V17Zm4 0h.01v.01H12V17Zm4 0h.01v.01H16V17Z"/>
                        </svg>
                            <span class="flex-1 ms-3 whitespace-nowrap">Meus agendamentos</span>
                        </a>
                    </li>
                    <!-- Link para Perfil do Usuário -->
                    <li>
                        <a href="/fixTime/PROJETO/src/views/main-page/Cliente/perfil.php" class="flex items-center p-2 text-gray-900 rounded-lg  hover:bg-gray-100  group">
                            <svg class="shrink-0 w-6 h-6 text-gray-500 transition duration-75 group-hover:text-gray-900" data-slot="icon" fill="none" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"></path>
                            </svg>
                            <span class="flex-1 ms-3 whitespace-nowrap">
                                <?php
                                    // Processa o nome do usuário para exibição
                                    $nomeCompleto = htmlspecialchars($user_data['nome_usuario']);
                                    $primeiroNome = explode(' ', $nomeCompleto)[0];
                                    $nomeExibido = strlen($primeiroNome) > 16 ? substr($primeiroNome, 0, 16) . "..." : $primeiroNome;
                                    echo $nomeExibido;
                                ?>
                            </span>
                        </a>
                    </li>
                    
                </ul>
            </div>

            <!-- Link para Logout -->
            <a href="/fixTime/PROJETO/src/views/Login/logout.php" class="flex items-center p-2 text-gray-900 rounded-lg  hover:bg-gray-100 group">
                <svg class="shrink-0 w-6 h-6 text-gray-500 transition duration-75 group-hover:text-gray-900" data-slot="icon" fill="none" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H8m12 0-4 4m4-4-4-4M9 4H7a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h2"/>
                </svg>
                <span class="flex-1 ms-3 whitespace-nowrap font-medium">Logout</span>
            </a>
        </div>
    </aside>

    <!-- Conteúdo principal da página -->
    <div class=" lg:ml-64 lg:py-10 py-4 lg:px-32 px-8 ">
        <!-- Exibição de mensagens de erro e sucesso -->
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

        <!-- Formulário de perfil -->
        <div class="p-8 bg-white border border-gray-200 rounded-lg shadow-sm">
            <form id="formPerfil" method="POST" action="perfil.php">
                <!-- Campos do formulário -->
                <div class="space-y-7">
                    <!-- Campo Nome -->
                    <div class="">
                        <label for="nome-perfil" class="block mb-1 text-sm font-medium text-gray-900 ">Nome</label>
                        <input type="text" id="nome-perfil" name="nome" value="<?php echo htmlspecialchars($user_data['nome_usuario']); ?>" class="cursor-not-allowed bg-gray-50 border-2 border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 outline-none" disabled />
                    </div>

                    <!-- Campo CPF -->
                    <div class="">
                        <label for="cpf-perfil" class="block mb-1 text-sm font-medium text-gray-900 ">CPF</label>
                        <input type="text" id="cpf-perfil" name="cpf" value="<?php echo htmlspecialchars($user_data['cpf']); ?>" class="cursor-not-allowed bg-gray-50 border-2 border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 outline-none" disabled />
                    </div>

                    <!-- Campo Telefone -->
                    <div class="">
                        <label for="telefone-perfil" class="block mb-1 text-sm font-medium text-gray-900 ">Número de telefone</label>
                        <input type="text" id="telefone-perfil" name="telefone" value="<?php echo htmlspecialchars($user_data['telefone_usuario']); ?>" class="cursor-not-allowed bg-gray-50 border-2 border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 outline-none" disabled />
                    </div>

                    <!-- Campo Email -->
                    <div class="">
                        <label for="email-perfil" class="block mb-1 text-sm font-medium text-gray-900 ">Email</label>
                        <input type="email" id="email-perfil" name="email" value="<?php echo htmlspecialchars($user_data['email_usuario']); ?>" class=" cursor-not-allowed bg-gray-50 border-2 border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 outline-none" disabled />
                    </div>
                </div>

                <!-- Campo oculto para controle de salvamento -->
                <input type="hidden" name="salvar_perfil" value="1">

                <!-- Botões de ação -->
                <div class="lg:gap-6 gap-4 items-center grid grid-cols-6 mt-6">
                    <!-- Botão Editar -->
                    <button id="editarPerfilBtn" type="button" name="salvar_perfil" value="1" class="text-white inline-flex items-center justify-center gap-2 bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center cursor-pointer col-span-3">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z"></path>
                            <path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd"></path>
                        </svg>
                        Editar
                    </button>

                    <!-- Botão Excluir -->
                    <button id="excluirPerfilBtn" type="button" name="excluir_perfil" class="inline-flex items-center justify-center gap-2 text-white bg-red-600 hover:bg-red-700 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center cursor-pointer col-span-3">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        Excluir
                    </button>
                </div>
                <!-- Campo oculto para controle de exclusão -->
                <input type="hidden" name="excluir_perfil" id="inputExcluirPerfil" value="">
            </form>
        </div>
    </div>

    <!-- Scripts JavaScript -->
    <script>
        // Controle do menu hamburguer
        const hamburgerButton = document.getElementById('hamburgerButton');
        const closeHamburgerButton = document.getElementById('closeHamburgerButton');
        const sidebar = document.getElementById('sidebar');

        // Abre o menu
        hamburgerButton.addEventListener('click', () => {
            sidebar.classList.toggle('-translate-x-full');
        });

        // Fecha o menu
        closeHamburgerButton.addEventListener('click', () => {
            sidebar.classList.add('-translate-x-full');
        });
    </script>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
    const editarBtn = document.getElementById('editarPerfilBtn');
    const excluirBtn = document.getElementById('excluirPerfilBtn');
    const form = document.getElementById('formPerfil');
    let modoEdicao = false;

        // Função para validar CPF
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

        // Botão editar/salvar
        editarBtn.addEventListener('click', function () {
            if (!modoEdicao) {
                document.querySelectorAll('input').forEach(input => {
                    input.disabled = false;
                    input.classList.remove('cursor-not-allowed');
                });
                editarBtn.textContent = 'Salvar';
                modoEdicao = true;
            
                $('#telefone-perfil').mask('(00) 00000-0000');
                $('#cpf-perfil').mask('000.000.000-00', { reverse: true });
            } else {
                const cpf = document.getElementById('cpf-perfil').value;
                if (!validarCPF(cpf)) {
                    Swal.fire({
                        icon: 'error',
                        title: 'CPF inválido',
                        text: 'Por favor, insira um CPF válido.',
                        confirmButtonText: 'OK'
                    });
                    return;
                }
            
                form.submit();
            }
        });

            // Manipula o botão de exclusão
        excluirBtn.addEventListener('click', function () {
            Swal.fire({
                title: 'Excluir Perfil',
                html: `
                    <p>Tem certeza que deseja excluir seu perfil?</p>
                    <p class="text-sm text-gray-500 mt-2">Esta ação não pode ser desfeita.</p>
                    <p class="text-sm text-red-500 mt-2">Observação: Você precisará excluir todos os seus veículos cadastrados antes de excluir o perfil.</p>
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

    <!-- Scripts externos -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.js"></script>
    <script src="/fixTime/PROJETO/src/public/assets/js/script.js"></script>
</body>
</html>