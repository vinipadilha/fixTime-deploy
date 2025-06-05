<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'] . '/fixTime/PROJETO/src/views/connect_bd.php';
$conexao = connect_db();

// Verifica se o usuário está logado
if (!isset($_SESSION['id_usuario'])) {
    $_SESSION['error_message'] = 'Usuário não autenticado.';
    header("Location: /fixTime/PROJETO/src/views/Login/login-user.php");
    exit();
}

$id_usuario = $_SESSION['id_usuario'];

// Verifica se o id da oficina foi passado na URL
if (!isset($_GET['id_oficina'])) {
    $_SESSION['error_message'] = 'Oficina não especificada.';
    header("Location: /fixTime/PROJETO/src/views/main-page/Cliente/prestadores-servico.php");
    exit();
}

$id_oficina = (int) $_GET['id_oficina'];

// Busca os dados da oficina
$oficina = null;
$stmtOficina = $conexao->prepare("SELECT nome_oficina, categoria, endereco_oficina, numero_oficina, complemento, bairro_oficina, cidade_oficina, estado_oficina, telefone_oficina, email_oficina FROM oficina WHERE id_oficina = ?");
$stmtOficina->bind_param("i", $id_oficina);
$stmtOficina->execute();
$resultOficina = $stmtOficina->get_result();
if ($resultOficina->num_rows > 0) {
    $oficina = $resultOficina->fetch_assoc();
} else {
    $_SESSION['error_message'] = 'Oficina não encontrada.';
    header("Location: /fixTime/PROJETO/src/views/main-page/Cliente/prestadores-servico.php");
    exit();
}
$stmtOficina->close();

// Busca os veículos do cliente
$veiculos = [];
$stmtVeiculos = $conexao->prepare("SELECT id, tipo_veiculo, marca, modelo, ano, cor, placa, quilometragem FROM veiculos WHERE id_usuario = ? ORDER BY id DESC");
$stmtVeiculos->bind_param("i", $id_usuario);
$stmtVeiculos->execute();
$resultVeiculos = $stmtVeiculos->get_result();
while ($row = $resultVeiculos->fetch_assoc()) {
    $veiculos[] = $row;
}

$stmtVeiculos->close();
?>

<!DOCTYPE html>
<html lang="pt-br" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/fixTime/PROJETO/src/public/assets/css/output.css">
    <title>Fix Time - Agendamento</title>
    <!-- Adiciona SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-gray-50">
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
    <div class="absolute top-0 left-0 p-4">
    <a href="/fixTime/PROJETO/src/views/main-page/Cliente/prestadores-servico.php" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 focus:outline-none">Voltar</a>
  </div>
    <div class="flex lg:py-14">
        <div class="mx-auto">
            <div class="max-w-5xl w-full bg-white border border-gray-200 rounded-lg shadow-sm">

                <div class="lg:py-10 lg:px-10">

                    <div class="mb-6">
                        <p class="mb-2 text-2xl font-bold tracking-tight text-gray-900">Agendar Serviço</p>
                        <p class=" text-gray-600">Preencha os dados abaixo para agendar seu serviço <br> com
                            <span class="font-bold text-gray-700 font">
                                <?= htmlspecialchars($oficina['nome_oficina'])?>
                                -
                                <?= htmlspecialchars($oficina['categoria'])?>
                            </span>
                        </p>   
                    </div>

                    <!-- Formulário de Agendamento -->
                    <form method="POST" action="/fixTime/PROJETO/src/views/main-page/Cliente/processa_agendamento.php" class="space-y-6">
                        <input type="hidden" name="id_oficina" value="<?= $id_oficina ?>">

                        <!-- Veículo -->
                        <div class="space-y-4">
                            
                            <div>
                                <label for="veiculo" class="block mb-2 text-sm font-medium text-gray-900">Veículo</label>
                                <select name="veiculo" id="veiculo" required
                                        class="focus:ring-blue-500 focus:border-blue-500 border bg-gray-50 border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 outline-none">
                                    <option value="">Selecione um veículo</option>
                                    <?php foreach ($veiculos as $veiculo): ?>
                                        <option value="<?= $veiculo['id'] ?>">
                                            <?= htmlspecialchars($veiculo['modelo']) ?> - Placa: <?= htmlspecialchars($veiculo['placa']) ?> - Cor: <?= htmlspecialchars($veiculo['cor']) ?> - Ano: <?= htmlspecialchars($veiculo['ano']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <!-- Data -->
                            <div class="">
                            <div class="relative w-full max-w-sm">
                                <label for="data" class="block mb-2 text-sm font-medium text-gray-900">Data</label>
                              <!-- Input de data -->
                              <input
                                type="date"
                                id="data"
                                name="data"
                                id="data"
                                class="outline-none p-2.5 w-full text-sm text-gray-900 bg-gray-50 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 cursor-pointer"
                                min="<?= date('Y-m-d'); ?>"
                                required>
                                         
                            <!-- Horário -->
                            <div class="space-y-2 mt-6">
                                <label class="block mb-2 text-sm font-medium text-gray-900">Horário</label>

                                <div class="grid grid-cols-2 gap-2">
                                    <?php
                                    $horarios = ['08:00', '09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'];
                                    foreach ($horarios as $index => $hora):
                                        $id = "hora-" . $index;
                                    ?>
                                    <ul class="text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg">
                                        <li class="border-0 hover:bg-gray-100 border-gray-200 ">
                                            <div class="flex items-center ps-3">
                                                <input 
                                                    id="<?= $id ?>" 
                                                    type="radio" 
                                                    value="<?= $hora ?>" 
                                                    name="horario" 
                                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 hover:bg-gray-300">
                                    
                                                <label for="<?= $id ?>" class="w-full py-3 ms-2 text-mds font-medium text-gray-900 ">
                                                    <?= $hora ?>
                                                </label>
                                            </div>
                                        </li>
                                    </ul>
                                    <?php endforeach; ?>
                                </div>
                            </div>  
                        
                        </div>


                            <!-- Botão -->
                            <div class="mt-10">
                                <button type="submit"
                                        class="cursor-pointer w-full text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-semibold rounded-lg text-sm px-5 py-2.5 text-center">
                                    Confirmar Agendamento
                                </button>
                            </div>
                        </div>
    
                                
                    </form>
                                
                </div>
            </div>
        </div>
    </div>

</body>
</html>