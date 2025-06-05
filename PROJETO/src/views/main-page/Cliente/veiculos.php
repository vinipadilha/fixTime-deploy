<?php
// Inclui o arquivo de conexão com o banco de dados
include $_SERVER['DOCUMENT_ROOT'] . '/fixTime/PROJETO/src/views/connect_bd.php';
$conexao = connect_db();

// Verifica se a conexão foi estabelecida corretamente
if (!isset($conexao) || !$conexao) {
    die("Erro ao conectar ao banco de dados. Verifique o arquivo connect_bd.php.");
}

// Inicia a sessão para gerenciar dados do usuário
session_start();

// Verifica se o usuário está autenticado
if (!isset($_SESSION['id_usuario'])) {
    $_SESSION['error_message'] = 'Usuário não autenticado. Faça login novamente.';
    header("Location: /fixTime/PROJETO/src/views/Login/login-user.php");
    exit();
}

// Obtém o ID do usuário da sessão
$id_usuario = $_SESSION['id_usuario'] ?? null;

// Busca o nome do usuário no banco de dados
$primeiroNome = '';
$stmtNome = $conexao->prepare("SELECT nome_usuario FROM cliente WHERE id_usuario = ?");
$stmtNome->bind_param("i", $id_usuario);
$stmtNome->execute();
$resultNome = $stmtNome->get_result();

// Processa o nome do usuário para exibição
if ($rowNome = $resultNome->fetch_assoc()) {
    $nomeCompleto = htmlspecialchars($rowNome['nome_usuario']);
    $primeiroNome = explode(' ', $nomeCompleto)[0];
    $primeiroNome = strlen($primeiroNome) > 16 ? substr($primeiroNome, 0, 16) . "..." : $primeiroNome;
}
$stmtNome->close();

// Inicializa variáveis para mensagens e lista de veículos
$mensagem = '';
$veiculos = [];

// Processa o formulário quando enviado via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitiza e valida os dados do formulário
    $tipo = isset($_POST['tipo_veiculos']) ? htmlspecialchars($_POST['tipo_veiculos'], ENT_QUOTES, 'UTF-8') : '';
    $marca = isset($_POST['marca_veiculo']) ? htmlspecialchars($_POST['marca_veiculo'], ENT_QUOTES, 'UTF-8') : '';
    $modelo = isset($_POST['modelo_veiculo']) ? htmlspecialchars($_POST['modelo_veiculo'], ENT_QUOTES, 'UTF-8') : '';
    $ano = isset($_POST['ano_veiculo']) ? (int)$_POST['ano_veiculo'] : 0;
    $cor = isset($_POST['cor_veiculo']) ? htmlspecialchars($_POST['cor_veiculo'], ENT_QUOTES, 'UTF-8') : '';
    $placa = isset($_POST['placa_veiculo']) ? htmlspecialchars($_POST['placa_veiculo'], ENT_QUOTES, 'UTF-8') : '';
    $quilometragem = isset($_POST['quilometragem_veiculo']) 
        ? (int) str_replace('.', '', $_POST['quilometragem_veiculo']) 
        : 0;

    // Validação dos campos obrigatórios
    if (
        empty($tipo) || empty($marca) || empty($modelo) || $ano < 1900 ||
        empty($cor) || empty($placa) || $quilometragem < 0
    ) {
        $_SESSION['error'] = 'Preencha todos os campos corretamente.';
        header("Location: /fixTime/PROJETO/src/views/main-page/Cliente/veiculos.php");
        exit;
    }

    try {
        // Prepara e executa a query de inserção
        $stmt = $conexao->prepare("INSERT INTO veiculos (tipo_veiculo, marca, modelo, ano, cor, placa, quilometragem, id_usuario) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssi", $tipo, $marca, $modelo, $ano, $cor, $placa, $quilometragem, $id_usuario);

        if ($stmt->execute()) {
            $_SESSION['success'] = 'Veículo cadastrado com sucesso!';
            header("Location: /fixTime/PROJETO/src/views/main-page/Cliente/veiculos.php");
            exit;
        } else {
            $_SESSION['error'] = 'Erro ao cadastrar veículo: ' . $stmt->error;
            header("Location: /fixTime/PROJETO/src/views/main-page/Cliente/veiculos.php");
            exit;
        }

        $stmt->close();
    } catch (Exception $e) {
        $erro = $e->getMessage();

        // Tratamento específico para erro de placa duplicada
        if (str_contains($erro, 'Duplicate entry') && str_contains($erro, 'placa')) {
            $_SESSION['error'] = 'Essa placa já está cadastrada no sistema. Por favor, verifique os dados.';
        } else {
            $_SESSION['error'] = 'Erro no banco de dados: ' . $erro;
        }
        header("Location: /fixTime/PROJETO/src/views/main-page/Cliente/veiculos.php");
        exit;
    }
}

// Busca os veículos do usuário
if ($id_usuario) {
    try {
        $stmt = $conexao->prepare("SELECT id, tipo_veiculo as tipo, marca, modelo, ano, cor, placa, quilometragem 
                        FROM veiculos WHERE id_usuario = ? ORDER BY id DESC");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $veiculos[] = $row;
        }

        $stmt->close();
    } catch (Exception $e) {
        $_SESSION['error_message'] = 'Erro ao buscar veículos: ' . $e->getMessage();
        header("Location: /fixTime/PROJETO/src/views/main-page/Cliente/veiculos.php");
        exit;
    }
}
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

<?php if (isset($_SESSION['error'])): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            icon: 'error',
            title: 'Atenção',
            text: '<?php echo $_SESSION['error']; ?>',
            confirmButtonText: 'OK'
        });
        <?php unset($_SESSION['error']); ?>
    });
</script>
<?php endif; ?>

<?php if (isset($_SESSION['success'])): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            icon: 'success',
            title: 'Sucesso',
            text: '<?php echo $_SESSION['success']; ?>',
            confirmButtonText: 'OK'
        });
        <?php unset($_SESSION['success']); ?>
    });
</script>
<?php endif; ?>

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

    <!-- Conteúdo principal da página -->
    <div class=" lg:ml-64 p-4 lg:p-14">
        <!-- Formulário de cadastro de veículos -->
        <div>
            <form action="/fixTime/PROJETO/src/views/main-page/Cliente/veiculos.php" method="POST">
                <div class="grid lg:gap-6 gap-4 mb-6 md:grid-cols-6 ">
                    <!-- Campo Tipo de Veículo -->
                    <div class="lg:col-span-1 col-span-6">
                        <label for="tipo_veiculos" class="block mb-2 text-sm font-medium text-gray-900">Tipo de veículo</label>
                        <select name="tipo_veiculos" id="tipo_veiculos" class="focus:ring-blue-500 focus:border-blue-500 border-2 bg-gray-50 border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 outline-none cursor-pointer" required>
                            <option value="">Selecione</option>
                            <option value="carro">Carro</option>
                            <option value="moto">Moto</option>
                            <option value="caminhao">Caminhão</option>
                            <option value="van">Van</option>
                            <option value="onibus">Ônibus</option>
                        </select>
                    </div>

                    <!-- Campo Marca -->
                    <div class="lg:col-span-2 col-span-6">
                        <label for="marca_veiculo" class="block mb-2 text-sm font-medium text-gray-900">Marca</label>
                        <input name="marca_veiculo" type="text" id="marca_veiculo" class="focus:ring-blue-500 focus:border-blue-500 border-2 bg-gray-50  border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 outline-none" placeholder="Ex: Honda" required>
                    </div>

                    <!-- Campo Modelo -->
                    <div class="lg:col-span-2 col-span-6">
                        <label for="modelo_veiculo" class="block mb-2 text-sm font-medium text-gray-900">Modelo</label>
                        <input name="modelo_veiculo" type="text" id="modelo_veiculo" class=" focus:ring-blue-500 focus:border-blue-500 border-2 bg-gray-50 border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 outline-none" placeholder="Ex: Civic" required>
                    </div>

                    <!-- Campo Ano -->
                    <div class="lg:col-span-1 col-span-6">
                        <label for="ano_veiculo" class="block mb-2 text-sm font-medium text-gray-900">Ano</label>
                        <input name="ano_veiculo" type="number" id="ano_veiculo" min="1900" max="2099" class="focus:ring-blue-500 focus:border-blue-500 border-2 bg-gray-50  border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 outline-none" placeholder="Ex: 2020" required>
                    </div>

                    <!-- Campo Cor -->
                    <div class="lg:col-span-1 col-span-6">
                        <label for="cor_veiculo" class="block mb-2 text-sm font-medium text-gray-900">Cor</label>
                        <input name="cor_veiculo" type="text" id="cor_veiculo" class="focus:ring-blue-500 focus:border-blue-500 border-2 bg-gray-50  border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 outline-none" placeholder="Ex: Prata" required >
                    </div>

                    <!-- Campo Placa -->
                    <div class="lg:col-span-2 col-span-6">
                        <label for="placa_veiculo" class="block mb-2 text-sm font-medium text-gray-900">Placa</label>
                        <input name="placa_veiculo" type="text" id="placa_veiculo" class="focus:ring-blue-500 focus:border-blue-500 border-2 bg-gray-50  border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 outline-none" placeholder="Ex: ABC1234 ou ABC1D23" required maxlength="7">
                    </div>

                    <!-- Campo Quilometragem -->
                    <div class="lg:col-span-2 col-span-6">
                        <label for="quilometragem_veiculo" class="block mb-2 text-sm font-medium text-gray-900">Quilometragem</label>
                        <input name="quilometragem_veiculo" type="text" id="quilometragem_veiculo" class="focus:ring-blue-500 focus:border-blue-500 border-2 bg-gray-50  border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 outline-none" placeholder="Ex: 30000" required>
                    </div>

                    <!-- Botão de Registro -->
                    <div class="lg:col-span-1 flex">
                        <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm lg:w-full w-auto px-5 py-2.5 text-center cursor-pointer mt-7">Registrar</button>
                    </div>
                </div>
            </form>

            <!-- Lista de Veículos Cadastrados -->
            <?php if (!empty($veiculos)): ?>
                <h2 class="text-xl font-bold mt-10 mb-4">Veículos cadastrados</h2>

                <?php foreach ($veiculos as $veiculo): ?>
                    <div class="mt-6" id="veiculo-<?= $veiculo['id'] ?>">
                        <hr class="h-px my-8 bg-gray-100 border-0">

                        <!-- Card do Veículo -->
                        <div class="p-4 bg-white border border-gray-200 rounded-lg shadow-md">
                            <form action="atualizar_veiculo.php" method="POST" class="form-veiculo">
                                <input type="hidden" name="id" value="<?= $veiculo['id'] ?>">

                                <!-- Grid de Campos do Veículo -->
                                <div class="grid lg:gap-6 gap-4 mb-6 md:grid-cols-6 grid-cols-2">
                                    <!-- Campo Tipo -->
                                    <div class="lg:col-span-1 col-span-1">
                                        <label for="tipo-<?= $veiculo['id'] ?>" class="block mb-1 text-sm font-medium text-gray-900">Tipo Veículo</label>
                                        <select name="tipo_veiculo" id="tipo-<?= $veiculo['id'] ?>" class="focus:ring-blue-500 focus:border-blue-500 border-2 bg-gray-50 border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2 outline-none cursor-not-allowed" disabled>
                                            <option value="carro" <?= $veiculo['tipo'] == 'carro' ? 'selected' : '' ?>>Carro</option>
                                            <option value="moto" <?= $veiculo['tipo'] == 'moto' ? 'selected' : '' ?>>Moto</option>
                                            <option value="caminhao" <?= $veiculo['tipo'] == 'caminhao' ? 'selected' : '' ?>>Caminhão</option>
                                            <option value="van" <?= $veiculo['tipo'] == 'van' ? 'selected' : '' ?>>Van</option>
                                            <option value="onibus" <?= $veiculo['tipo'] == 'onibus' ? 'selected' : '' ?>>Ônibus</option>
                                        </select>
                                    </div>

                                    <!-- Campo Marca -->
                                    <div class="lg:col-span-2 col-span-1">
                                        <label for="marca-<?= $veiculo['id'] ?>" class="block mb-1 text-sm font-medium text-gray-900">Marca</label>
                                        <input name="marca" type="text" id="marca-<?= $veiculo['id'] ?>" value="<?= htmlspecialchars($veiculo['marca']) ?>" class=" focus:ring-blue-500 focus:border-blue-500 border-2 bg-gray-50 border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2 outline-none cursor-not-allowed" disabled>
                                    </div>

                                    <!-- Campo Modelo -->
                                    <div class="col-span-3">
                                        <label for="modelo-<?= $veiculo['id'] ?>" class="block mb-1 text-sm font-medium text-gray-900">Modelo</label>
                                        <input name="modelo" type="text" id="modelo-<?= $veiculo['id'] ?>" value="<?= htmlspecialchars($veiculo['modelo']) ?>" class="focus:ring-blue-500 focus:border-blue-500 border-2 bg-gray-50 border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2 outline-none cursor-not-allowed" disabled>
                                    </div>

                                    <!-- Campo Ano -->
                                    <div class="col-span-1">
                                        <label for="ano-<?= $veiculo['id'] ?>" class="block mb-1 text-sm font-medium text-gray-900">Ano</label>
                                        <input name="ano" type="number" id="ano-<?= $veiculo['id'] ?>" value="<?= htmlspecialchars($veiculo['ano']) ?>" min="1900" max="2099" class="mascara-ano focus:ring-blue-500 focus:border-blue-500 border-2 bg-gray-50  border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2 outline-none cursor-not-allowed" disabled>
                                    </div>

                                    <!-- Campo Cor -->
                                    <div class="lg:col-span-2 col-span-1">
                                        <label for="cor-<?= $veiculo['id'] ?>" class="block mb-1 text-sm font-medium text-gray-900">Cor</label>
                                        <input name="cor" type="text" id="cor-<?= $veiculo['id'] ?>" value="<?= htmlspecialchars($veiculo['cor']) ?>" class="focus:ring-blue-500 focus:border-blue-500 border-2 bg-gray-50  border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2 outline-none cursor-not-allowed" disabled>
                                    </div>

                                    <!-- Campo Placa -->
                                    <div class="lg:col-span-2 col-span-1">
                                        <label for="placa-<?= $veiculo['id'] ?>" class="block mb-1 text-sm font-medium text-gray-900">Placa</label>
                                        <input name="placa" type="text" id="placa-<?= $veiculo['id'] ?>" value="<?= htmlspecialchars($veiculo['placa']) ?>" class="focus:ring-blue-500 focus:border-blue-500 border-2 bg-gray-50 border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2 outline-none cursor-not-allowed" disabled maxlength="7">
                                    </div>

                                    <!-- Campo Quilometragem -->
                                    <div class="col-span-1">
                                        <label for="quilometragem-<?= $veiculo['id'] ?>" class="block mb-1 text-sm font-medium text-gray-900">Quilometragem</label>
                                        <input name="quilometragem" type="text" id="quilometragem-<?= $veiculo['id'] ?>" value="<?= number_format($veiculo['quilometragem'], 0, '', '.') ?>" class="mascara-quilometragem focus:ring-blue-500 focus:border-blue-500 border-2 bg-gray-50  border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2 outline-none cursor-not-allowed" disabled>
                                    </div>
                                </div>

                                <!-- Botões de Ação -->
                                <div class="lg:gap-6 gap-4 items-center grid grid-cols-6">
                                    <!-- Botão Editar/Salvar -->
                                    <button type="button" class="editar-btn text-white inline-flex items-center justify-center gap-2 bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center cursor-pointer col-span-3" data-id="<?= $veiculo['id'] ?>">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z"></path>
                                            <path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd"></path>
                                        </svg>
                                        Editar
                                    </button>

                                    <!-- Botão Excluir/Cancelar -->
                                    <button type="button" class="excluir-btn inline-flex items-center justify-center gap-2 text-white bg-red-600 hover:bg-red-700 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center cursor-pointer col-span-3" data-id="<?= $veiculo['id'] ?>">
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
                <!-- Mensagem quando não há veículos cadastrados-->
                <hr class="h-px my-8 bg-gray-300 border-0">
                <div class="mt-10 p-4 rounded-lg bg-gray-100 border-2 border-gray-300 shadow-xl flex items-center justify-between ">
                    <div>
                        <p class="font-medium">Nenhum veículo cadastrado.</p>
                        <p class="text-sm">Adicione seu primeiro veículo usando o formulário acima.</p>
                    </div>
                </div>
            <?php endif; ?>

    <!-- Scripts JavaScript -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.js"></script>
    <script>
        // Aplica máscaras nos campos de quilometragem e ano
        $('#quilometragem_veiculo').mask('000.000', {reverse: true});
        $('#ano_veiculo').mask('0000');

        $(document).ready(function() {
            $('.mascara-quilometragem').mask('000.000', { reverse: true });
            $('.mascara-ano').mask('0000');
        });
    </script>

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

        // Controle de edição de veículos
        document.querySelectorAll('.editar-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const form = this.closest('.form-veiculo');
                const inputs = form.querySelectorAll('input:not([type="hidden"]):not(.campo-id), select');
                const isEditing = this.textContent.trim() === 'Editar';

                if (isEditing) {
                    // Habilita edição dos campos
                    inputs.forEach(input => {
                        input.disabled = false;
                        input.classList.remove('cursor-not-allowed');
                    });

                    // Atualiza o botão para modo de salvamento
                    this.innerHTML = `
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        Salvar
                    `;
                    this.classList.remove('bg-blue-700', 'hover:bg-blue-800', 'focus:ring-blue-300');
                    this.classList.add('bg-blue-600', 'hover:bg-blue-700', 'focus:ring-blue-300');

                    // Atualiza o botão de exclusão para cancelamento
                    const excluirBtn = form.querySelector('.excluir-btn');
                    excluirBtn.innerHTML = `
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                        Cancelar
                    `;
                    excluirBtn.classList.remove('bg-red-600', 'hover:bg-red-700', 'focus:ring-red-300');
                    excluirBtn.classList.add('bg-red-500', 'hover:bg-red-600', 'focus:ring-red-300');
                } else {
                    // Confirma a atualização com SweetAlert
                    Swal.fire({
                        title: 'Confirmar alterações',
                        text: 'Deseja salvar as alterações deste veículo?',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Sim, salvar!',
                        cancelButtonText: 'Cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Envia o formulário para atualização
                            form.submit();
                        }
                    });
                }
            });
        });

        document.querySelectorAll('.excluir-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const form = this.closest('.form-veiculo');
            
                if (this.textContent.trim() === 'Excluir') {
                    // Confirma a exclusão do veículo com SweetAlert
                    Swal.fire({
                        title: 'Tem certeza?',
                        text: 'Deseja realmente excluir este veículo? Esta ação não pode ser desfeita!',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Sim, excluir!',
                        cancelButtonText: 'Cancelar',
                        reverseButtons: true
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Cria um formulário para exclusão
                            const deleteForm = document.createElement('form');
                            deleteForm.action = 'excluir_veiculo.php';
                            deleteForm.method = 'POST';
                        
                            const inputId = document.createElement('input');
                            inputId.type = 'hidden';
                            inputId.name = 'id_veiculo';
                            inputId.value = id;
                        
                            deleteForm.appendChild(inputId);
                            document.body.appendChild(deleteForm);
                            deleteForm.submit();
                        }
                    });
                } else {
                    // Cancela a edição
                    const inputs = form.querySelectorAll('input:not([type="hidden"]), select');
                    inputs.forEach(input => {
                        input.disabled = true;
                        input.classList.add('cursor-not-allowed');
                    });
                
                    // Reseta o botão de edição
                    const editarBtn = form.querySelector('.editar-btn');
                    editarBtn.innerHTML = `
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z"></path>
                            <path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd"></path>
                        </svg>
                        Editar
                    `;
                    editarBtn.classList.remove('bg-blue-600', 'hover:bg-blue-700', 'focus:ring-blue-300');
                    editarBtn.classList.add('bg-blue-700', 'hover:bg-blue-800', 'focus:ring-blue-300');
                
                    // Reseta o botão de cancelamento para exclusão
                    this.innerHTML = `
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        Excluir
                    `;
                    this.classList.remove('bg-blue-500', 'hover:bg-blue-600', 'focus:ring-blue-300');
                    this.classList.add('bg-red-600', 'hover:bg-red-700', 'focus:ring-red-300');
                
                    // Recarrega o formulário para descartar alterações
                    form.reset();
                }
            });
        });
    </script>

</body>
</html>