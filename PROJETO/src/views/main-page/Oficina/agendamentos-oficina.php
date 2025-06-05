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
$id_oficina = $_SESSION['id_oficina'] ?? null;

// Verifica se o usuário está autenticado
if (!$id_oficina) {
    echo "<script>alert('Usuário não autenticado. Faça login novamente.'); window.location.href='/fixTime/PROJETO/src/views/Login/login-company.php';</script>";
    exit();
}

// Busca os dados da oficina
$sql = "SELECT nome_oficina FROM oficina WHERE id_oficina = ?";
$stmt = $conexao->prepare($sql);
$stmt->bind_param("i", $id_oficina); // Associa o ID da oficina como parâmetro
$stmt->execute();
$result = $stmt->get_result();

// Verifica se encontrou a oficina e armazena os dados
if ($result->num_rows > 0) {
    $user_data = $result->fetch_assoc();
} else {
    die("Oficina não encontrada."); // Encerra a execução se a oficina não existir
}

// Busca os dados dos serviços associados à oficina
$sql = "SELECT 
            s.*, 
            v.modelo, v.placa, v.ano, v.cor, 
            c.nome_usuario, c.telefone_usuario, c.email_usuario, 
            a.estrelas, a.data_avaliacao,
            f.nome_funcionario 
        FROM servico s
        JOIN veiculos v ON s.id_veiculo = v.id
        JOIN cliente c ON v.id_usuario = c.id_usuario
        LEFT JOIN avaliacao a ON s.id_servico = a.id_servico
        LEFT JOIN funcionarios f ON s.id_funcionario_responsavel = f.id_funcionario
        WHERE s.id_oficina = ?
        ORDER BY s.data_agendada DESC, s.horario ASC";


$stmt_servicos = $conexao->prepare($sql);

if ($stmt_servicos === false) {
    die("Erro ao preparar a consulta: " . $conexao->error);
}

$stmt_servicos->bind_param("i", $id_oficina);
$stmt_servicos->execute();
$servicos_result = $stmt_servicos->get_result();

$servicos = [];
while ($row = $servicos_result->fetch_assoc()) {
    $servicos[] = $row;
}

// Carrega os funcionários da oficina logada
$funcionarios = [];
$stmtFuncionarios = $conexao->prepare("SELECT id_funcionario, nome_funcionario FROM funcionarios WHERE id_oficina = ?");
$stmtFuncionarios->bind_param("i", $id_oficina);
$stmtFuncionarios->execute();
$resultFuncionarios = $stmtFuncionarios->get_result();

while ($row = $resultFuncionarios->fetch_assoc()) {
    $funcionarios[] = $row;
}
$stmtFuncionarios->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_servico'], $_POST['funcionario_responsavel'])) {
    $id_servico = (int) $_POST['id_servico'];
    $id_funcionario_raw = $_POST['funcionario_responsavel'];
    $id_funcionario = ($id_funcionario_raw === '' || $id_funcionario_raw === '0') ? null : (int) $id_funcionario_raw;

    $update_stmt = $conexao->prepare("UPDATE servico SET id_funcionario_responsavel = ? WHERE id_servico = ?");

    if (is_null($id_funcionario)) {
        $update_stmt->bind_param("si", $null = null, $id_servico);
    } else {
        $update_stmt->bind_param("ii", $id_funcionario, $id_servico);
    }

    if ($update_stmt->execute()) {
        header("Location: agendamentos-oficina.php?sucesso=1");
        exit();
    } else {
        echo "<script>alert('Erro ao atualizar o funcionário responsável.');</script>";
    }

    $update_stmt->close();
}


?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendamentos - FixTime</title>
    <link rel="stylesheet" href="/fixTime/PROJETO/src/public/assets/css/output.css">
    <!-- Adiciona SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-50">
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
                    <img src="/fixTime/PROJETO/src/public/assets/images/fixtime-truck.png" class="lg:h-14 h-12 me-3 "/>
                </a>

                <!-- Menu de navegação -->
                <ul class="space-y-2 font-medium">
                    <li>
                        <a href="/fixTime/PROJETO/src/views/main-page/Oficina/funcionarios.php" class="flex items-center p-2 text-gray-900 rounded-lg hover:bg-gray-100 group">
                            <svg class="shrink-0 w-6 h-6 text-gray-500 transition duration-75 group-hover:text-gray-900" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M4.5 17H4a1 1 0 0 1-1-1 3 3 0 0 1 3-3h1m0-3.05A2.5 2.5 0 1 1 9 5.5M19.5 17h.5a1 1 0 0 0 1-1 3 3 0 0 0-3-3h-1m0-3.05a2.5 2.5 0 1 0-2-4.45m.5 13.5h-7a1 1 0 0 1-1-1 3 3 0 0 1 3-3h3a3 3 0 0 1 3 3 1 1 0 0 1-1 1Zm-1-9.5a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0Z"/>
                            </svg>
                            <span class="flex-1 ms-3 whitespace-nowrap">Meus Funcionarios</span>
                        </a>
                    </li>

                    <li>
                        <a href="/fixTime/PROJETO/src/views/main-page/Oficina/perfil-oficina.php" class="flex items-center p-2 text-gray-900 rounded-lg hover:bg-gray-100 group">
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
                    
                    <li>
                        <a href="/fixTime/PROJETO/src/views/main-page/Oficina/registrar-servicos.php" class="flex items-center p-2 text-gray-900 rounded-lg hover:bg-gray-100 group">
                            <svg class="shrink-0 w-6 h-6 text-gray-500 transition duration-75 group-hover:text-gray-900" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.583 8.445h.01M10.86 19.71l-6.573-6.63a.993.993 0 0 1 0-1.4l7.329-7.394A.98.98 0 0 1 12.31 4l5.734.007A1.968 1.968 0 0 1 20 5.983v5.5a.992.992 0 0 1-.316.727l-7.44 7.5a.974.974 0 0 1-1.384.001Z"/>
                            </svg>
                            <span class="flex-1 ms-3 whitespace-nowrap">Registrar serviços</span>
                        </a>
                    </li>

                    <li>
                        <a href="/fixTime/PROJETO/src/views/main-page/Oficina/agendamentos-oficina.php" class="flex items-center p-2 text-gray-900 rounded-lg hover:bg-gray-100 group">
                            <svg class="shrink-0 w-6 h-6 text-gray-500 transition duration-75 group-hover:text-gray-900" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 10h16m-8-3V4M7 7V4m10 3V4M5 20h14a1 1 0 0 0 1-1V7a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1Zm3-7h.01v.01H8V13Zm4 0h.01v.01H12V13Zm4 0h.01v.01H16V13Zm-8 4h.01v.01H8V17Zm4 0h.01v.01H12V17Zm4 0h.01v.01H16V17Z"/>
                            </svg>
                            <span class="flex-1 ms-3 whitespace-nowrap">Agendamentos</span>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Link para Logout -->
            <a href="/fixTime/PROJETO/src/views/Login/logout.php" class="flex items-center p-2 text-gray-900 rounded-lg hover:bg-gray-100 group">
                <svg class="shrink-0 w-6 h-6 text-gray-500 transition duration-75 group-hover:text-gray-900" fill="none" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H8m12 0-4 4m4-4-4-4M9 4H7a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h2"/>
                </svg>
                <span class="flex-1 ms-3 whitespace-nowrap font-medium">Logout</span>
            </a>
        </div>
    </aside>

    <div class="lg:ml-64 p-4 lg:px-20 lg:py-4">
        <div class="text-center">
            <p class="text-2xl text-gray-900 font-medium">Serviços</p>
        </div>
        <?php if (!empty($servicos)): ?>
            <?php foreach ($servicos as $servico): ?>
                <div style="margin-top: 30px;">
                    <hr class="h-1 w-48 mx-auto rounded-md bg-gray-300 border-0">
                    <div class="p-6 bg-white border border-gray-200 rounded-lg shadow-md mt-8">
                        <div class="grid grid-cols-6 gap-4">

                            <div class="col-span-1">
                                <label for="id_servico" class="block mb-1 text-sm font-medium text-gray-900 ">ID do Serviço</label>
                                <input type="number" id="id_servico" name="id_servico" value="<?= $servico['id_servico'] ?>" class=" bg-gray-50 border-2 border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 outline-none cursor-not-allowed" disabled />
                            </div>

                            
                            <div class="col-span-2">
                                <label for="recebimento_servico" class="block mb-1 text-sm font-medium text-gray-900 ">Data recebimento</label>
                                <input type="date" id="recebimento_servico" name="recebimento_servico" value="<?= $servico['data_agendada'] ?>" class=" bg-gray-50 border-2 border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 outline-none cursor-not-allowed" disabled />
                            </div>

                            <div class="col-span-3">
                                <label for="veiculo_servico" class="block mb-1 text-sm font-medium text-gray-900 ">Veículo</label>
                                <input type="text" id="veiculo_servico" name="veiculo_servico" value="<?= $servico['modelo'] ?> - <?= $servico['placa'] ?> ( Ano: <?= $servico['ano'] ?> - Cor: <?= $servico['cor'] ?>)" class=" bg-gray-50 border-2 border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 outline-none cursor-not-allowed" disabled />
                            </div>

                            <div class="col-span-1">
                                <label for="telefone_cliente" class="block mb-1 text-sm font-medium text-gray-900">Telefone Cliente</label>
                                <input type="text" id="telefone_cliente" name="telefone_cliente" value="<?= $servico['telefone_usuario'] ?>" class="bg-gray-50 border-2 border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 outline-none cursor-not-allowed" disabled />
                            </div>

                            <div class="col-span-2">
                                <label for="nome_cliente" class="block mb-1 text-sm font-medium text-gray-900">Nome cliente</label>
                                <input type="text" id="nome_cliente" name="nome_cliente" value="<?= $servico['nome_usuario'] ?>" class="bg-gray-50 border-2 border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 outline-none cursor-not-allowed" disabled />
                            </div>

                            <div class="col-span-3">
                                <label for="email-cliente" class="block mb-1 text-sm font-medium text-gray-900">Email Cliente</label>
                                <input type="email" id="email-cliente" name="email-cliente" value="<?= $servico['email_usuario'] ?>" class="bg-gray-50 border-2 border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 outline-none cursor-not-allowed" disabled />
                            </div>
                        </div>

                        <hr class="h-1 w-48 mx-auto rounded-md my-8 bg-gray-300 border-0">

                        <div class="">
                            <div class="grid grid-cols-6 gap-4">
                                
                                    <div class="col-span-2 space-y-4">
                                        <div class="">
                                            <label for="data_entrega" class="block mb-1 text-sm font-medium text-gray-900">Data de entrega do veículo</label>
                                            <input type="date" id="data_entrega" name="data_entrega"  value="<?= $servico['data_entrega']?>"  min="<?php echo date('Y-m-d'); ?>" class="bg-gray-50 border-2 border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 outline-none cursor-not-allowed" disabled />
                                        </div>
                                        
                                        <div class="">
                                            <label for="status_servico" class="block mb-1 text-sm font-medium text-gray-900">Status</label>
                                            <select id="status_servico" name="status_servico" class="bg-gray-50 border-2 border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 outline-none cursor-not-allowed" disabled >
                                                <option value="pendente">Pendente</option>
                                                <option value="em_andamento">Em andamento</option>
                                                <option value="finalizado">Finalizado</option>
                                                <option value="cancelado">Cancelado</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    
                                    <div class="col-span-4">
                                        <label for="servicos_feitos" class="block mb-1 text-sm font-medium text-gray-900">Serviços feitos</label>
                                        <textarea id="servicos_feitos" name="servicos_feitos" rows="5" class="bg-gray-50 border-2 border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 outline-none resize-none cursor-not-allowed" placeholder="Descreva os serviços realizados..." disabled ><?= $servico['descricao_servico']?></textarea>
                                    </div>
                                    
                                </div>
                            </div>
                            
                            <hr class="h-1 w-48 mx-auto rounded-md my-8 bg-gray-300 border-0">
                            
                    
                        <form id="formFuncionario<?= $servico['id_servico'] ?>" method="POST" action="">
                            <input type="hidden" name="id_servico" value="<?= $servico['id_servico'] ?>">

                            <div class="grid grid-cols-6 gap-4">
                                <div class="col-span-6">
                                    <label for="funcionario_responsavel" class="block mb-1 text-sm font-medium text-gray-900">Funcionário responsável</label>
                                    <select id="funcionario_responsavel<?= $servico['id_servico'] ?>" 
                                            name="funcionario_responsavel" 
                                            class="bg-gray-50 border-2 border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 outline-none cursor-not-allowed" 
                                            disabled required>

                                        <!-- Primeira opção: valor atual ou "Nenhum funcionário atribuído" -->
                                        <option selected hidden value="<?= $servico['id_funcionario'] ?? '' ?>">
                                            <?= !empty($servico['nome_funcionario']) ? htmlspecialchars($servico['nome_funcionario']) : 'Nenhum funcionário atribuído' ?>
                                        </option>

                                        <!-- Demais funcionários disponíveis -->
                                        <?php foreach ($funcionarios as $func): ?>
                                            <option value="<?= $func['id_funcionario'] ?>">
                                                <?= htmlspecialchars($func['id_funcionario']) ?> - <?= htmlspecialchars($func['nome_funcionario']) ?>
                                            </option>
                                        <?php endforeach; ?>

                                        <!-- Última opção: valor vazio -->
                                        <option value="">Nenhum funcionário atribuído</option>
                                    </select>
                                </div>

                                        

                                <div class="col-span-6">
                                    <div class="flex gap-2">
                                        <button type="button" id="editarBtn<?= $servico['id_servico'] ?>" class="text-white inline-flex items-center justify-center gap-2 bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-4 text-center cursor-pointer w-full">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z"></path>
                                                <path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd"></path>
                                            </svg>
                                            Editar
                                        </button>
                                        <button type="submit" id="salvarBtn<?= $servico['id_servico'] ?>" style="display: none;" class="text-white inline-flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-4 text-center cursor-pointer w-full">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                            Salvar
                                        </button>
                                    </div>
                                </div>

                            </div>
                        </form>

                        <!-- Área de Avaliação -->
                        <div class="mt-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900">Avaliação do Cliente</h3>
                                    <?php if ($servico['estrelas']): ?>
                                        <div class="flex items-center gap-1 mt-2">
                                            <?php 
                                            $estrelas = (int)$servico['estrelas'];
                                            for ($i = 1; $i <= $estrelas; $i++): 
                                            ?>
                                                <svg class="w-5 h-5 text-yellow-600" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"></path>
                                                </svg>
                                            <?php endfor; ?>
                                            <span class="ml-2 text-sm text-gray-600">(<?= $estrelas ?> estrelas)</span>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-gray-500 mt-2">Ainda não avaliado</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <!-- Mensagem quando não há veículos cadastrados-->
                <hr class="h-px my-8 bg-gray-300 border-0">
                <div class="mt-10 p-4 rounded-lg bg-gray-100 border-2 border-gray-300 shadow-xl flex items-center justify-between ">
                    <div>
                        <p class="font-medium">Nenhum serviço cadastrado.</p>
                    </div>
                </div>
        <?php endif; ?>
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

        // Controle dos botões de edição e salvamento
        document.addEventListener('DOMContentLoaded', function() {
            <?php foreach ($servicos as $servico): ?>
            const editarBtn<?= $servico['id_servico'] ?> = document.getElementById('editarBtn<?= $servico['id_servico'] ?>');
            const salvarBtn<?= $servico['id_servico'] ?> = document.getElementById('salvarBtn<?= $servico['id_servico'] ?>');
            const select<?= $servico['id_servico'] ?> = document.getElementById('funcionario_responsavel<?= $servico['id_servico'] ?>');
            const form<?= $servico['id_servico'] ?> = document.getElementById('formFuncionario<?= $servico['id_servico'] ?>');

            editarBtn<?= $servico['id_servico'] ?>.addEventListener('click', function() {
                select<?= $servico['id_servico'] ?>.disabled = false;
                select<?= $servico['id_servico'] ?>.classList.remove('cursor-not-allowed');
                editarBtn<?= $servico['id_servico'] ?>.style.display = 'none';
                salvarBtn<?= $servico['id_servico'] ?>.style.display = 'flex';
            });

            form<?= $servico['id_servico'] ?>.addEventListener('submit', function(e) {
                e.preventDefault();
                
                Swal.fire({
                    title: 'Confirmar alteração',
                    text: 'Deseja salvar as alterações do funcionário responsável?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sim, salvar!',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            type: 'POST',
                            url: 'agendamentos-oficina.php',
                            data: $(this).serialize(),
                            success: function(response) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Sucesso!',
                                    text: 'Funcionário responsável atualizado com sucesso!',
                                    confirmButtonColor: '#3085d6'
                                }).then(() => {
                                    window.location.reload();
                                });
                            },
                            error: function() {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Erro!',
                                    text: 'Erro ao atualizar funcionário responsável.',
                                    confirmButtonColor: '#3085d6'
                                });
                            }
                        });
                    }
                });
            });
            <?php endforeach; ?>
        });
    </script>
    
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.js"></script>
    <script src="/fixTime/PROJETO/src/public/assets/js/script.js"></script>
</body>
</html>