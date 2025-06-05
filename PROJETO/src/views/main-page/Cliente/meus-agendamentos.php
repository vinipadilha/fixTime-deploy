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
$id_usuario = $_SESSION['id_usuario'];

// Verifica se o usuário está autenticado
if (!isset($_SESSION['id_usuario'])) {
    $_SESSION['error_message'] = 'Usuário não autenticado. Faça login novamente.';
    header("Location: /fixTime/PROJETO/src/views/Login/login-user.php");
    exit;
}



// Busca os dados atuais do usuário
$sql = "SELECT nome_usuario, cpf, telefone_usuario, email_usuario FROM cliente WHERE id_usuario = ?";
$stmt = $conexao->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();

// Verifica se encontrou o usuário
if ($result->num_rows > 0) {
    $user_data = $result->fetch_assoc();
} else {
    die("Usuário não encontrado.");
}


// Busca agendamentos do cliente
$sql_servicos = "
SELECT s.*, v.modelo, v.placa, v.ano, v.cor,
       o.nome_oficina, o.endereco_oficina, o.numero_oficina, o.bairro_oficina, o.telefone_oficina, o.email_oficina, o.complemento,
       (SELECT estrelas FROM avaliacao WHERE id_servico = s.id_servico LIMIT 1) as avaliacao_estrelas
FROM servico s
JOIN veiculos v ON s.id_veiculo = v.id
JOIN oficina o ON s.id_oficina = o.id_oficina
WHERE v.id_usuario = ?
ORDER BY s.data_agendada DESC";

$stmt = $conexao->prepare($sql_servicos);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$servicos_result = $stmt->get_result();

$servicos = [];
while ($row = $servicos_result->fetch_assoc()) {
    $servicos[] = $row;
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
    <link href="https://cdn.jsdelivr.net/npm/daisyui@5/themes.css" rel="stylesheet" type="text/css" />
    <title>Fix Time</title>
    <!-- Adiciona SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="">
    <?php
    // Exibe mensagens de sessão
    if (isset($_SESSION['success'])) {
        echo "<script>
            Swal.fire({
                icon: 'success',
                title: 'Sucesso!',
                text: '" . $_SESSION['success'] . "',
                confirmButtonColor: '#3085d6'
            });
        </script>";
        unset($_SESSION['success']);
    }

    if (isset($_SESSION['error'])) {
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Erro!',
                text: '" . $_SESSION['error'] . "',
                confirmButtonColor: '#3085d6'
            });
        </script>";
        unset($_SESSION['error']);
    }

    
    ?>
    <?php if (isset($_SESSION['alert_agendamento_veiculo'])): ?>
    <script>
        Swal.fire({
            title: 'Atenção!',
            text: 'Você tem agendamentos ligados a este veículo.',
            icon: 'warning',
            confirmButtonText: 'OK',
            confirmButtonColor: '#3085d6'
        });
    </script>
    <?php unset($_SESSION['alert_agendamento_veiculo']); ?>
    <?php endif; ?>


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
    <div class="lg:ml-64 p-4 lg:px-20 lg:py-4">
        <div class="text-center">
            <p class="text-2xl text-gray-900 font-medium">Serviços</p>
        </div>
        <?php if (!empty($servicos)): ?>
            <?php foreach ($servicos as $servico): ?>
                <hr class="h-1 w-48 mx-auto rounded-md my-10 bg-gray-300 border-0">
            <form method="POST" action="/fixTime/PROJETO/src/views/main-page/Cliente/salvar_reagendamento.php" class="form-agendamento">
            <input type="hidden" name="id_servico" value="<?= $servico['id_servico'] ?>">

            <div class="p-6 bg-white border border-gray-200 rounded-lg shadow-md ">
                <div class="grid grid-cols-6 gap-4">
                    
                    <div class="col-span-1">
                        <label class="block mb-1 text-sm font-medium text-gray-900">Data agendada</label>
                        <input type="date" name="data_agendada" value="<?= $servico['data_agendada'] ?>" class="bg-gray-50 border-2 border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2" min="<?= date('Y-m-d'); ?>" disabled />
                    </div>

                    <div class="col-span-1">
                        <label class="block mb-1 text-sm font-medium text-gray-900">Horário agendado</label>
                        <select name="horario" id="horario-agendado-<?= $index ?>" class="bg-gray-50 border-2 border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2" disabled>
                            <option  value="">
                                <?= $servico['horario'] ?>
                            </option>
                            <?php
                            $horarios = ['08:00', '09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'];
                            foreach ($horarios as $hora): ?>
                                <option value="<?= $hora ?>" <?= $servico['horario'] == $hora ? 'selected' : '' ?>><?= $hora ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-span-4">
                        <label for="veiculo_servico" class="block mb-1 text-sm font-medium text-gray-900 ">Veículo</label>
                        <input type="text" id="veiculo_servico" name="veiculo_servico" value="<?= $servico['modelo'] ?> - <?= $servico['placa'] ?> ( Ano: <?= $servico['ano'] ?> - Cor: <?= $servico['cor'] ?>)" class=" bg-gray-50 border-2 border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 outline-none " disabled />
                    </div>

                    <div class="col-span-2">
                        <label for="veiculo_servico" class="block mb-1 text-sm font-medium text-gray-900 ">Prestador de serviço</label>
                        <input type="text" id="veiculo_servico" name="veiculo_servico" value="<?= $servico['nome_oficina'] ?>" class=" bg-gray-50 border-2 border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 outline-none " disabled />
                    </div>
                    <div class="col-span-2">
                        <label for="veiculo_servico" class="block mb-1 text-sm font-medium text-gray-900 ">Telefone</label>
                        <input type="text" id="veiculo_servico" name="veiculo_servico" value="<?= $servico['telefone_oficina'] ?>" class=" bg-gray-50 border-2 border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 outline-none " disabled />
                    </div>
                    <div class="col-span-2">
                        <label for="veiculo_servico" class="block mb-1 text-sm font-medium text-gray-900 ">Email</label>
                        <input type="text" id="veiculo_servico" name="veiculo_servico" value="<?= $servico['email_oficina'] ?>" class=" bg-gray-50 border-2 border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 outline-none " disabled />
                    </div>

                    <div class="col-span-6">
                        <label class="block mb-1 text-sm font-medium text-gray-900">Endereço</label>
                        <input type="text" value="<?= $servico['endereco_oficina'] ?>, <?= $servico['numero_oficina'] ?> - <?= $servico['complemento'] ?> - <?= $servico['bairro_oficina']?>" class="bg-gray-50 border-2 border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2" disabled />
                    </div>

                </div>

                <hr class="h-1 w-48 mx-auto rounded-md my-8 bg-gray-300 border-0">

                <div class="">
                    <div class="grid grid-cols-6 gap-4">
                        
                            <div class="col-span-2 space-y-4">
                                <div class="">
                                    <label for="data_entrega" class="block mb-1 text-sm font-medium text-gray-900">Data de entrega do veículo</label>
                                    <input type="date" id="data_entrega" name="data_entrega"  value="<?= $servico['data_entrega'] ?? '' ?>"  min="<?php echo date('Y-m-d'); ?>" class="bg-gray-50 border-2 border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 outline-none" disabled />
                                </div>
                                
                                <div class="">
                                    <label for="status_servico" class="block mb-1 text-sm font-medium text-gray-900">Status</label>
                                    <input type="text" id="nome_cliente" name="nome_cliente" value="<?= $servico['status'] ?>" class=" bg-gray-50 border-2 border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 outline-none " disabled />
                                </div>
                            </div>
                            
                            
                            <div class="col-span-4">
                                <label for="servicos_feitos" class="block mb-1 text-sm font-medium text-gray-900">Serviços feitos</label>
                                <textarea id="servicos_feitos" name="servicos_feitos" rows="5" class="bg-gray-50 border-2 border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 outline-none resize-none " placeholder="Descreva os serviços realizados..." disabled ><?= $servico['descricao_servico'] ?></textarea>
                            </div>

                        </div>
                    </div>
                    <!-- Botões de ação -->
                    <div class="lg:gap-6 gap-4 items-center grid grid-cols-6 mt-6">
                        <!-- Botão Editar/Salvar -->
                        <button type="button" class="cursor-pointer editar-btn col-span-3 text-white bg-blue-700 hover:bg-blue-800 px-5 py-2.5 rounded-lg">Reagendar</button>

                        <!-- Botão Excluir/Cancelar -->
                        <button type="button" class="cursor-pointer excluir-btn col-span-3 text-white bg-red-600 hover:bg-red-700 px-5 py-2.5 rounded-lg">Cancelar</button>
                        </form>
                    </div>

                    <!-- Formulário de exclusão -->
                    <form method="POST" action="/fixTime/PROJETO/src/views/main-page/Cliente/excluir_agendamento.php" class="excluir-form" style="display: none;">
                        <input type="hidden" name="id_servico" value="<?= $servico['id_servico'] ?>">
                    </form>


                    <div class="">
                        <form method="POST" action="/fixTime/PROJETO/src/views/main-page/Cliente/salvar_avaliacao.php" class="avaliacao-oficina">
                            <input type="hidden" name="id_servico" value="<?= $servico['id_servico'] ?>">
                            <input type="hidden" name="id_oficina" value="<?= $servico['id_oficina'] ?>">
                            <input type="hidden" name="estrelas" id="estrelasInput-<?= $servico['id_servico'] ?>" value="<?= $servico['avaliacao_estrelas'] ?? '' ?>">

                            <div class=" flex mt-4">

                                <div class=" grid  w-full place-items-center overflow-x-scroll rounded-lg p-2 lg:overflow-visible">
                                    <div class="inline-flex items-center gap-" id="estrelasContainer-<?= $servico['id_servico'] ?>">
                                    <!-- Estrela 1 -->
                                    <svg data-estrela="1" class="estrela-btn w-8 h-8 cursor-pointer <?= ($servico['avaliacao_estrelas'] ?? 0) >= 1 ? 'text-yellow-600' : 'text-gray-500' ?>" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"></path>
                                    </svg>
                                    <!-- Estrela 2 -->
                                    <svg data-estrela="2" class="estrela-btn w-8 h-8 cursor-pointer <?= ($servico['avaliacao_estrelas'] ?? 0) >= 2 ? 'text-yellow-600' : 'text-gray-500' ?>" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"></path>
                                    </svg>
                                    <!-- Estrela 3 -->
                                    <svg data-estrela="3" class="estrela-btn w-8 h-8 cursor-pointer <?= ($servico['avaliacao_estrelas'] ?? 0) >= 3 ? 'text-yellow-600' : 'text-gray-500' ?>" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"></path>
                                    </svg>
                                    <!-- Estrela 4 -->
                                    <svg data-estrela="4" class="estrela-btn w-8 h-8 cursor-pointer <?= ($servico['avaliacao_estrelas'] ?? 0) >= 4 ? 'text-yellow-600' : 'text-gray-500' ?>" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"></path>
                                    </svg>
                                    <!-- Estrela 5 -->
                                    <svg data-estrela="5" class="estrela-btn w-8 h-8 cursor-pointer <?= ($servico['avaliacao_estrelas'] ?? 0) >= 5 ? 'text-yellow-600' : 'text-gray-500' ?>" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"></path>
                                    </svg>
                                </div>
                            </div>
                            
                            <div>
                                
                                <button type="submit" class="cursor-pointer text-white bg-blue-700 hover:bg-blue-800 px-4 py-2 rounded-lg ">
                                    <?= $servico['avaliacao_estrelas'] ? 'Atualizar ' : 'Salvar ' ?>
                                </button>
                            </div>
                        </div>
                        </form>
                    </div>

                    <script>
                    document.addEventListener("DOMContentLoaded", function () {
                        const servicos = <?= json_encode($servicos) ?>;
                        
                        servicos.forEach(servico => {
                            const estrelaBotoes = document.querySelectorAll(`#estrelasContainer-${servico.id_servico} .estrela-btn`);
                            const estrelasInput = document.getElementById(`estrelasInput-${servico.id_servico}`);
                            const form = document.querySelector(`#estrelasContainer-${servico.id_servico}`).closest('form');
                        
                            estrelaBotoes.forEach((btn) => {
                                btn.addEventListener('click', () => {
                                    const valorSelecionado = parseInt(btn.getAttribute('data-estrela'));
                                    estrelasInput.value = valorSelecionado;
                                
                                    estrelaBotoes.forEach((b, i) => {
                                        const bValor = parseInt(b.getAttribute('data-estrela'));
                                        if (bValor <= valorSelecionado) {
                                            b.classList.add('text-yellow-600');
                                            b.classList.remove('text-gray-500');
                                        } else {
                                            b.classList.add('text-gray-500');
                                            b.classList.remove('text-yellow-600');
                                        }
                                    });
                                });
                            });

                            form.addEventListener('submit', function(e) {
                                if (!estrelasInput.value) {
                                    e.preventDefault();
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Erro',
                                        text: 'Por favor, selecione uma avaliação com estrelas antes de enviar.',
                                        confirmButtonText: 'OK'
                                    });
                                }
                            });
                        });
                    });
                    </script>



            </div>
            <?php endforeach; ?>
            <?php else:?>
                <hr class="h-1 w-48 mx-auto rounded-md my-10 bg-gray-300 border-0">
                <div class="mt-10 p-4 rounded-lg bg-gray-100 border-2 border-gray-300 shadow-xl flex items-center justify-between">
                    <div>
                        <p class="font-medium">Você não possui nenhum serviço agendado.</p>
                        <p class="font-medium">Dê uma olhada nos nossos parceiros cadastrados,
                            <a class="text-blue-600 font-semibold underline hover:text-blue-800" href="/fixTime/PROJETO/src/views/main-page/Cliente/prestadores-servico.php">clique aqui.</a>
                        </p>
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
    </script>


    <script>
        // Seleciona todos os botões de reagendamento
        document.querySelectorAll('.editar-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const form = this.closest('form');
                const dataInput = form.querySelector('input[type="date"]');
                const horaSelect = form.querySelector('select');
                const isEditing = this.textContent.trim() === 'Reagendar';

                const excluirBtn = form.querySelector('.excluir-btn');

                if (isEditing) {
                    // Habilita campos de edição
                    dataInput.disabled = false;
                    horaSelect.disabled = false;
                    dataInput.classList.remove('cursor-not-allowed');
                    horaSelect.classList.remove('cursor-not-allowed');

                    // Troca o texto do botão para 'Salvar'
                    this.innerHTML = `
                        Salvar
                    `;

                    // Altera botão de exclusão para cancelar
                    excluirBtn.innerHTML = `
                        Cancelar
                    `;
                    

                    // Altera comportamento do botão para cancelar edição
                    excluirBtn.onclick = () => {
                        dataInput.disabled = true;
                        horaSelect.disabled = true;
                        dataInput.classList.add('cursor-not-allowed');
                        horaSelect.classList.add('cursor-not-allowed');

                        this.innerHTML = `
                            Reagendar
                        `;

                        excluirBtn.innerHTML = `
                            Excluir
                        `;
                        excluirBtn.classList.remove('bg-gray-600');
                        excluirBtn.classList.add('bg-red-600');
                        excluirBtn.onclick = null;
                    };

                } else {
                    // Submete o formulário para salvar
                    form.submit();
                }
            });
        });

        // Adiciona funcionalidade de exclusão
        document.querySelectorAll('.excluir-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const form = this.closest('form');
                const excluirForm = form.parentElement.querySelector('.excluir-form');
                
                if (this.textContent.trim() === 'Cancelar') {
                    Swal.fire({
                        title: 'Tem certeza?',
                        text: "Esta ação não poderá ser revertida!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Sim, excluir!',
                        cancelButtonText: 'Cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            excluirForm.submit();
                        }
                    });
                }
            });
        });
    </script>

    <!-- Scripts externos -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.js"></script>
    <script src="/fixTime/PROJETO/src/public/assets/js/script.js"></script>
</body>
</html> 