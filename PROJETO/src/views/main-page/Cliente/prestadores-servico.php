<?php
// Inicia a sessão para gerenciar dados do usuário
session_start();

// Inclui o arquivo de conexão com o banco de dados
include $_SERVER['DOCUMENT_ROOT'] . '/fixTime/PROJETO/src/views/connect_bd.php';
$conexao = connect_db();

// Verifica se a conexão foi estabelecida corretamente
if (!isset($conexao) || !$conexao) {
    die("Erro ao conectar ao banco de dados. Verifique o arquivo connect_bd.php.");
}

// Verifica se o usuário está autenticado
if (!isset($_SESSION['id_usuario'])) {
    $_SESSION['error_message'] = 'Usuário não autenticado. Faça login novamente.';
    header("Location: /fixTime/PROJETO/src/views/Login/login-user.php");
    exit;
}

// Obtém o ID do usuário da sessão
$id_usuario = $_SESSION['id_usuario'];
$primeiroNome = '';

// Busca o nome do usuário no banco de dados
$stmt = $conexao->prepare("SELECT nome_usuario FROM cliente WHERE id_usuario = ?");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();

// Processa o nome do usuário para exibição
if ($row = $result->fetch_assoc()) {
    $nomeCompleto = htmlspecialchars($row['nome_usuario']);
    $primeiroNome = explode(' ', $nomeCompleto)[0];
    $primeiroNome = strlen($primeiroNome) > 16 ? substr($primeiroNome, 0, 16) . "..." : $primeiroNome;
}

$sql_media_avaliacao = "SELECT id_oficina, SUM(estrelas) AS total_estrelas, COUNT(*) AS total_avaliacoes FROM avaliacao GROUP BY id_oficina";
$result_media_all = $conexao->query($sql_media_avaliacao);

$medias_avaliacao = [];
while ($row = $result_media_all->fetch_assoc()) {
    if ($row['total_avaliacoes'] > 0) {
        $medias_avaliacao[$row['id_oficina']] = number_format($row['total_estrelas'] / $row['total_avaliacoes'], 1);
    } else {
        $medias_avaliacao[$row['id_oficina']] = "Sem avaliações";
    }
}


$stmt->close();
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">

<head>
    <!-- Meta tags para configuração do documento -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Link para o arquivo CSS do Tailwind -->
    <link rel="stylesheet" href="/fixTime/PROJETO/src/public/assets/css/output.css">
    <title>Fix Time</title>
    <!-- Adiciona SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="">
    <?php
    // Exibe mensagens de sessão
    if (isset($_SESSION['success_message'])) {
        echo "<script>
            Swal.fire({
                icon: 'success',
                title: 'Sucesso!',
                text: '" . $_SESSION['success_message'] . "',
                confirmButtonColor: '#3085d6'
            });
        </script>";
        unset($_SESSION['success_message']);
    }

    if (isset($_SESSION['error_message'])) {
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Erro!',
                text: '" . $_SESSION['error_message'] . "',
                confirmButtonColor: '#3085d6'
            });
        </script>";
        unset($_SESSION['error_message']);
    }
    ?>
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
                                <?php echo $primeiroNome; ?>
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

    <div class=" lg:ml-64 lg:px-20 lg:py-6 p-10">
        <div class="flex justify-center items-center">
            <h1 class="mb-3 text-xl font-bold leading-tight tracking-tight text-gray-900 md:text-2xl">Oficinas Parceiras</h1>
        </div>

        <hr class=" h-px my-8 bg-gray-200 border-0">

        <?php
        // Obtém o filtro de categoria da URL
        $filter = isset($_GET['filter']) ? $_GET['filter'] : '';

        // Prepara a query base para buscar oficinas
        $query = "SELECT id_oficina, nome_oficina, email_oficina, telefone_oficina, bairro_oficina, endereco_oficina, categoria, numero_oficina, complemento, cidade_oficina FROM oficina";
        if (!empty($filter)) {
            $query .= " WHERE categoria = ?";
        }

        // Executa a query com o filtro de categoria
        $stmt = $conexao->prepare($query);
        if (!empty($filter)) {
            $stmt->bind_param("s", $filter);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        ?>

        <!-- Formulário de filtros -->
        <form method="GET" class="mb-4">
            <div class="flex flex-wrap gap-4">
                <!-- Filtro por categoria -->
                <div class="flex-1">
                    <label for="filter" class="block mb-2 text-sm font-medium text-gray-900 ">Filtrar por categoria:</label>
                    <select name="filter" id="filter" class="block w-full p-2.5 border-gray-300 rounded-lg outline-none focus:ring-blue-500 focus:border-blue-500 border-2 cursor-pointer">
                        <option value="">Todas</option>
                        <option value="Borracharia" <?php echo $filter === 'Borracharia' ? 'selected' : ''; ?>>Borracharia</option>
                        <option value="Auto Elétrica" <?php echo $filter === 'Auto Elétrica' ? 'selected' : ''; ?>>Auto Elétrica</option>
                        <option value="Oficina Mecânica" <?php echo $filter === 'Oficina Mecânica' ? 'selected' : ''; ?>>Oficina Mecânica</option>
                        <option value="Lava Car" <?php echo $filter === 'Lava Car' ? 'selected' : ''; ?>>Lava Car</option>
                    </select>
                </div>
                <!-- Filtro por bairro -->
                <div class="flex-1">
                    <label for="bairro" class="block mb-2 text-sm font-medium text-gray-900">Filtrar por bairro:</label>
                    <input type="text" name="bairro" id="bairro" value="<?php echo isset($_GET['bairro']) ? htmlspecialchars($_GET['bairro']) : ''; ?>" class="block w-full p-2.5  border-gray-300 rounded-lg outline-none focus:ring-blue-500 focus:border-blue-500 border-2 " placeholder="Digite o bairro">
                </div>
            </div>
            <!-- Botão de filtrar -->
            <div class=" flex justify-center mt-4">
                <button type="submit" class="cursor-pointer mt-2 text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5">Filtrar</button>
            </div>
        </form>

        <?php
        // Obtém o filtro de bairro da URL
        $bairroFilter = isset($_GET['bairro']) ? trim($_GET['bairro']) : '';

        // Adiciona o filtro de bairro à query
        if (!empty($bairroFilter)) {
            $query .= empty($filter) ? " WHERE bairro_oficina LIKE ?" : " AND bairro_oficina LIKE ?";
        }

        // Executa a query com os filtros combinados
        $stmt = $conexao->prepare($query);
        if (!empty($filter) && !empty($bairroFilter)) {
            $bairroFilter = '%' . $bairroFilter . '%';
            $stmt->bind_param("ss", $filter, $bairroFilter);
        } elseif (!empty($filter)) {
            $stmt->bind_param("s", $filter);
        } elseif (!empty($bairroFilter)) {
            $bairroFilter = '%' . $bairroFilter . '%';
            $stmt->bind_param("s", $bairroFilter);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        // Exibe as oficinas encontradas
        if ($result && $result->num_rows > 0) {
            $oficinas = $result->fetch_all(MYSQLI_ASSOC);
            foreach ($oficinas as $row) {
                // Busca os serviços oferecidos pela oficina
                $oficina_id = $row['id_oficina'];
                $sql_servicos = "SELECT sp.nome_servico 
                                FROM oficina_servicos os 
                                JOIN servicos_padrao sp ON os.id_servico_padrao = sp.id_servico_padrao 
                                WHERE os.id_oficina = ?";
                $stmt_servicos = $conexao->prepare($sql_servicos);
                $stmt_servicos->bind_param("i", $oficina_id);
                $stmt_servicos->execute();
                $result_servicos = $stmt_servicos->get_result();
                $servicos = $result_servicos->fetch_all(MYSQLI_ASSOC);
                $media_avaliacao = $medias_avaliacao[$oficina_id] ?? "Sem avaliações";

                ?>
                
                <div class="mb-8 py-6 px-8 border border-gray-200 rounded-lg shadow-sm bg-white hover:shadow-lg">
                    <div class="grid  lg:grid-cols-6 gap-2">
                        <!-- Coluna da esquerda com informações da oficina -->
                        <div class="col-span-2">
                            <h1 class="mb-3 text-xl font-bold leading-tight tracking-tight text-gray-900 md:text-2xl"><?= htmlspecialchars($row['nome_oficina']) ?></h1>
                            
                            <p class="mb-2 text-gray-500 flex items-center text-md">
                                 <svg class="w-6 h-6 mr-2 text-gray-800" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                                  <path d="M13.849 4.22c-.684-1.626-3.014-1.626-3.698 0L8.397 8.387l-4.552.361c-1.775.14-2.495 2.331-1.142 3.477l3.468 2.937-1.06 4.392c-.413 1.713 1.472 3.067 2.992 2.149L12 19.35l3.897 2.354c1.52.918 3.405-.436 2.992-2.15l-1.06-4.39 3.468-2.938c1.353-1.146.633-3.336-1.142-3.477l-4.552-.36-1.754-4.17Z"/>
                                </svg>
                                <?= $media_avaliacao ?>
                            </p>
                            <p class="mb-2 text-gray-500 flex items-center text-md">
                                <svg class="w-6 h-6 mr-2 text-gray-800" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M8.4 6.763c-.251.1-.383.196-.422.235L6.564 5.584l2.737-2.737c1.113-1.113 3.053-1.097 4.337.187l1.159 1.159a1 1 0 0 1 1.39.022l4.105 4.105a1 1 0 0 1 .023 1.39l1.345 1.346a1 1 0 0 1 0 1.415l-2.052 2.052a1 1 0 0 1-1.414 0l-1.346-1.346a1 1 0 0 1-1.323.039L11.29 8.983a1 1 0 0 1 .04-1.324l-.849-.848c-.18-.18-.606-.322-1.258-.25a3.271 3.271 0 0 0-.824.202Zm1.519 3.675L3.828 16.53a1 1 0 0 0 0 1.414l2.736 2.737a1 1 0 0 0 1.414 0l6.091-6.091-4.15-4.15Z"/>
                                </svg>
                                <?= htmlspecialchars($row['categoria']) ?>
                            </p>

                            <p class="mb-2 text-gray-500 flex items-center text-md">
                                <svg class="w-6 h-6 mr-2 text-gray-800" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                                  <path d="M2.038 5.61A2.01 2.01 0 0 0 2 6v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6c0-.12-.01-.238-.03-.352l-.866.65-7.89 6.032a2 2 0 0 1-2.429 0L2.884 6.288l-.846-.677Z"/>
                                  <path d="M20.677 4.117A1.996 1.996 0 0 0 20 4H4c-.225 0-.44.037-.642.105l.758.607L12 10.742 19.9 4.7l.777-.583Z"/>
                                </svg>  
                                <?= htmlspecialchars($row['email_oficina']) ?>
                            </p>

                            <p class="mb-2 text-gray-500 flex items-center text-md">
                                <svg class="w-6 h-6 mr-2 text-gray-800" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M7.978 4a2.553 2.553 0 0 0-1.926.877C4.233 6.7 3.699 8.751 4.153 10.814c.44 1.995 1.778 3.893 3.456 5.572 1.68 1.679 3.577 3.018 5.57 3.459 2.062.456 4.115-.073 5.94-1.885a2.556 2.556 0 0 0 .001-3.861l-1.21-1.21a2.689 2.689 0 0 0-3.802 0l-.617.618a.806.806 0 0 1-1.14 0l-1.854-1.855a.807.807 0 0 1 0-1.14l.618-.62a2.692 2.692 0 0 0 0-3.803l-1.21-1.211A2.555 2.555 0 0 0 7.978 4Z"/>
                                </svg>
                                <?= htmlspecialchars($row['telefone_oficina']) ?>
                            </p>

                            <p class="mb-2 text-gray-500 flex items-center text-md">
                                <svg class="w-6 h-6 mr-2 text-gray-800" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                                    <path fill-rule="evenodd" d="M5 9a7 7 0 1 1 8 6.93V21a1 1 0 1 1-2 0v-5.07A7.001 7.001 0 0 1 5 9Zm5.94-1.06A1.5 1.5 0 0 1 12 7.5a1 1 0 1 0 0-2A3.5 3.5 0 0 0 8.5 9a1 1 0 0 0 2 0c0-.398.158-.78.44-1.06Z" clip-rule="evenodd"/>
                                </svg>       
                                <?= htmlspecialchars($row['endereco_oficina']) ?>, <?= htmlspecialchars($row['numero_oficina']) ?> - <?= htmlspecialchars($row['bairro_oficina']) ?> - <?= htmlspecialchars($row['cidade_oficina']) ?>
                            </p>

                            <?php if (!empty($row['complemento'])): ?>
                            <p class="mb-2 text-gray-500 flex items-center text-md">
                                <svg class="w-6 h-6 mr-2 text-gray-800" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                                    <path fill-rule="evenodd" d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm11-4.243a1 1 0 1 0-2 0V11H7.757a1 1 0 1 0 0 2H11v3.243a1 1 0 1 0 2 0V13h3.243a1 1 0 1 0 0-2H13V7.757Z" clip-rule="evenodd"/>
                                </svg>
                                        
                                <?= htmlspecialchars($row['complemento']) ?> 
                            </p>
                            <?php endif; ?>

                        </div>
                        
                        <!-- Coluna da direita com serviços oferecidos -->
                        <div class="col-span-4">
                            <h2 class="mb-3 text-lg font-semibold text-gray-900">Serviços Oferecidos:</h2>
                            <?php if (!empty($servicos)): ?>
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                                    <?php foreach ($servicos as $servico): ?>
                                        <div class="flex items-center text-gray-500">
                                            <svg class="w-4 h-4 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            <?= htmlspecialchars($servico['nome_servico']) ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-gray-500">Nenhum serviço cadastrado</p>
                            <?php endif; ?>
                            
                        </div>
                    </div>
                    
                    <!-- Botão de agendamento -->
                    <a href="/fixTime/PROJETO/src/views/main-page/Cliente/agendamento-cliente.php?id_oficina=<?= $row['id_oficina'] ?>" class="mt-2 text-white inline-flex items-center justify-center gap-2 bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center cursor-pointer col-span-3">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z"></path>
                            <path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd"></path>
                        </svg>
                        Agendar
                    </a>
                </div>
                <?php
            }
        } else {
            echo '<p class="text-gray-500">Nenhuma oficina encontrada para o filtro selecionado.</p>';
        }
        ?>

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

</body>

</html>