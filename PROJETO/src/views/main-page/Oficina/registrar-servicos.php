<?php

// Inicia a sessão PHP para manter o estado do usuário
session_start();

// Inclui o arquivo de conexão com o banco de dados
include $_SERVER['DOCUMENT_ROOT'] . '/fixTime/PROJETO/src/views/connect_bd.php';
$conexao = connect_db();

// Verifica se a conexão com o banco de dados foi estabelecida com sucesso
if (!isset($conexao) || !$conexao) {
    die("Erro ao conectar ao banco de dados. Verifique o arquivo connect_bd.php.");
}

// Obtém o ID da oficina da sessão
$oficina_id = $_SESSION['id_oficina'] ?? null;

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

// Busca os dados básicos da oficina (nome e categoria)
$sql = "SELECT nome_oficina, categoria FROM oficina WHERE id_oficina = ?";
$stmt = $conexao->prepare($sql);
$stmt->bind_param("i", $oficina_id);
$stmt->execute();
$result = $stmt->get_result();

// Verifica se encontrou a oficina e armazena os dados
if ($result->num_rows > 0) {
    $user_data = $result->fetch_assoc();
    $categoria_oficina = $user_data['categoria']; // Armazena a categoria da oficina
} else {
    die("Oficina não encontrada.");
}

// Busca a categoria específica da oficina
$sql = "SELECT categoria FROM oficina WHERE id_oficina = ?";
$stmt = $conexao->prepare($sql);
$stmt->bind_param("i", $oficina_id);
$stmt->execute();
$categoria = $stmt->get_result()->fetch_assoc()['categoria'];

// Busca todos os serviços disponíveis para a categoria da oficina
$servicos = [];
$sqlServicos = "SELECT id_servico_padrao, nome_servico FROM servicos_padrao WHERE categoria = ?";
$stmtServicos = $conexao->prepare($sqlServicos);
$stmtServicos->bind_param("s", $categoria);
$stmtServicos->execute();
$resultServicos = $stmtServicos->get_result();
while ($row = $resultServicos->fetch_assoc()) {
    $servicos[] = $row;
}

// Busca os serviços que já foram selecionados pela oficina
$servicosSelecionados = [];
$sqlSelecionados = "SELECT id_servico_padrao FROM oficina_servicos WHERE id_oficina = ?";
$stmtSelecionados = $conexao->prepare($sqlSelecionados);
$stmtSelecionados->bind_param("i", $oficina_id);
$stmtSelecionados->execute();
$resultSelecionados = $stmtSelecionados->get_result();
while ($row = $resultSelecionados->fetch_assoc()) {
    $servicosSelecionados[] = $row['id_servico_padrao'];
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

<!-- Exibe mensagens de feedback do sistema -->
<?php 
if (isset($_SESSION['alert'])) {
    echo "<script>
        Swal.fire({
            icon: '" . $_SESSION['alert']['type'] . "',
            title: '" . $_SESSION['alert']['title'] . "',
            text: '" . $_SESSION['alert']['text'] . "',
            confirmButtonColor: '#3085d6'
        });</script>";
    unset($_SESSION['alert']);
}
?>

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
                            <svg class="shrink-0 w-6 h-6 text-gray-500 transition duration-75 group-hover:text-gray-900" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
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
    <div class="lg:ml-64 lg:py-10 py-4 lg:px-48 px-8">
        <!-- Container do formulário -->
        <div class="p-8 bg-white border border-gray-200 rounded-lg shadow-sm">
            <div class="">
                <!-- Título com a categoria da oficina -->
                <h2 class="text-2xl font-bold mb-6">Categoria: <?php echo htmlspecialchars($categoria_oficina); ?></h2>

                <!-- Formulário para seleção de serviços -->
                <form id="formServicos" method="POST" action="salvar_servicos.php">
                    <h2>Selecione os serviços que sua oficina oferece:</h2>
                
                    <!-- Lista de checkboxes para cada serviço disponível -->
                    <?php foreach ($servicos as $servico): ?>
                        <label>
                            <input
                                class="cursor-pointer mt-3"
                                type="checkbox"
                                name="servicos[]"
                                value="<?= $servico['id_servico_padrao'] ?>"
                                <?= in_array($servico['id_servico_padrao'], $servicosSelecionados) ? 'checked' : '' ?>
                            >
                            <?= htmlspecialchars($servico['nome_servico']) ?>
                        </label><br>
                    <?php endforeach; ?>
                    
                    <!-- Botão para salvar as seleções -->
                    <button type="submit" class="mt-5 text-white inline-flex items-center justify-center gap-2 bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center cursor-pointer">Salvar</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts JavaScript -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
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

        // Manipula o envio do formulário de serviços
        $('#formServicos').on('submit', function(e) {
            e.preventDefault();
            
            $.ajax({
                type: 'POST',
                url: $(this).attr('action'),
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    Swal.fire({
                        icon: response.type,
                        title: response.title,
                        text: response.text,
                        confirmButtonColor: '#3085d6'
                    });
                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro!',
                        text: 'Erro ao salvar serviços. Por favor, tente novamente.',
                        confirmButtonColor: '#3085d6'
                    });
                }
            });
        });
    </script>
</body>

</html>