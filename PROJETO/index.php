<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/fixTime/PROJETO/src/public/assets/css/output.css">
    <title>Fix Time</title>
</head>


<body class="bg-gray-50">

    <!-- header -->
    <div class="lg:h-22 h-20 bg-gray-50 border-b border-b-gray-300 block fixed w-full top-0 z-50">
      <div class="flex items-center justify-between 2xl:mx-60 xl:mx-40 md:mx-20 p-4">
        <div class="font-black flex-shrink-0">
          <a href="">
            <img class="lg:h-14 h-12 w-auto" src="/fixTime/PROJETO/src/public/assets/images/fixtime-truck.png" alt="Logo">
          </a>
        </div>

        <div class="flex items-center space-x-3 lg:space-x-6">
          <ul id="menu-links" class="hidden sm:flex ml-auto space-x-4 pointer-events-none sm:pointer-events-auto">
            <li>
                <a href="#about-us" class="text-sm text-black rounded-sm hover:bg-gray-100 md:hover:bg-transparent md:hover:text-blue-700 md:dark:hover:text-blue-500 font-semibold">Sobre Nós</a>
            </li>
            <li>
                <a href="#servicos" class="text-sm text-black rounded-sm hover:bg-gray-100 md:hover:bg-transparent md:hover:text-blue-700 md:dark:hover:text-blue-500 font-semibold">Serviços</a>
            </li>
          </ul> 
        
        
          
          <a href="/fixTime/PROJETO/src/views/Login/choice-cadastro.html" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm lg:px-5 lg:py-2.5 focus:outline-none cursor-pointer px-3 py-2">
            Cadastro
          </a>

          <a href="/fixTime/PROJETO/src/views/Login/choice-login.html" class="text-gray-900 bg-white border border-gray-300 focus:outline-none hover:bg-gray-100 focus:ring-4 focus:ring-gray-300 font-medium rounded-lg text-sm px-3 py-2 lg:px-5 lg:py-2.5  cursor-pointer ">
            Login
          </a>

          <!-- Botão do Menu Hambúrguer (Aparece apenas no mobile) -->
          <button type="button" id="btn-menu-hamburguer" class="md:hidden inline-flex items-center justify-center p-2 w-10 h-10 text-md text-gray-500 rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-1 focus:ring-gray-300 cursor-pointer">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" style="fill: rgba(0, 0, 0, 1);"><path d="M4 6h16v2H4zm0 5h16v2H4zm0 5h16v2H4z"></path></svg>
          </button>
        </div>
        </div>
      
        <!-- Menu Dropdown (Escondido por padrão) -->
        <div id="navbar-hamburger" class="hidden w-full md:hidden absolute bg-gray-100 z-50 top-20 ">
          <ul class="flex flex-col font-medium mt rounded-b-2xl border-gray-200 shadow-sm">
            <li>
              <a href="#about-us" class="block py-2 px-3 text-gray-900 rounded-sm hover:bg-gray-200 cursor-pointer focus:ring-2 focus:ring-gray-600">Sobre Nós</a>
            </li>
            <li>
              <a href="#servicos" class="block py-2 px-3 text-gray-900 rounded-sm hover:bg-gray-200 cursor-pointer focus:ring-2 focus:ring-gray-600">Serviços</a>
            </li>
          </ul>
        </div>

            
      </div>
    </div>
    <!-- header -->

    <!-- jumbotron--> 
    <div class="flex items-center justify-center h-screen pt-20">
      <div class=" mx-auto max-w-screen-xl text-center lg:py-16 py-10 px-4">
          <h1 class="mb-6 font-extrabold  text-gray-900 text-2xl md:text-4xl lg:text-5xl">
              🛠️ Mais Facilidade para Você e Seu Carro! 🚗
          </h1>
        
          <p class="mb-8 font-normal text-gray-500 lg:text-xl lg:px-48 px-5 text-md">
              Encontre, agende e acompanhe os cuidados para seu carro em um só lugar. Com serviços rápidos e eficientes, garantimos o melhor para o seu veículo! 💨💎
          </p>
        
          <div class="flex flex-col space-y-4 sm:flex-row sm:justify-center sm:space-y-0 px-15 ">
              <a href="#servicos" class="inline-flex justify-center items-center py-3 px-5 text-base font-medium text-center text-white rounded-lg bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 ">
                  Nossos serviços
                  <svg class="w-3.5 h-3.5 ms-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 10">
                      <path id="servicos" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 5h12m0 0L9 1m4 4L9 9"/>
                  </svg>
              </a>
          </div>
      </div>
    </div>
    <!-- jumbotron-->  


    <!-- Services-->  
    <div  class="mb-15 mt-5 lg:w-15/20 mx-auto">
      <div class="p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 justify-items-center">

        <!-- Borracharia -->
        <a href="#" class="block w-full  max-w-sm p-6 bg-white border border-gray-200 rounded-lg shadow-sm hover:bg-gray-100">
          <h5 class="mb-2 text-xl font-bold text-gray-900">
            🛞 Borracharia
          </h5>
          <h5 class="mb-2 text-md font-semibold tracking-tight text-gray-500">Cuide dos pneus do seu carro! 🚗🔧</h5>
          <p class="text-sm font-normal text-gray-700">
            Agende um serviço de borracharia perto de você para troca de pneus, alinhamento e balanceamento. Cuide da segurança e desempenho do seu veículo! 🛠️
          </p>
        </a>
      
        <!-- Eletricista Automotivo -->
        <a href="#" class="block w-full max-w-sm p-6 bg-white border border-gray-200 rounded-lg shadow-sm hover:bg-gray-100">
          <h5 class="mb-2 text-xl font-bold text-gray-900">
            ⚡ Eletricista Automotivo
          </h5>
          <h5 class="mb-2 text-md font-semibold tracking-tight text-gray-500">Soluções elétricas para seu carro! ⚡🔋</h5>
          <p class="text-sm font-normal text-gray-700">
            Agende um eletricista especializado para cuidados elétricos essenciais, como reparos e manutenção dos sistemas elétricos do seu carro. 🔧
          </p>
        </a>
      
        <!-- Mecânica Automotiva -->
        <a href="#" class="block w-full max-w-sm p-6 bg-white border border-gray-200 rounded-lg shadow-sm hover:bg-gray-100">
          <h5 class="mb-2 text-xl font-bold text-gray-900">
            🛠️ Mecânica Automotiva
          </h5>
          <h5 class="mb-2 text-md font-semibold tracking-tight text-gray-500">Seu carro em perfeito funcionamento! 🚙🛠️</h5>
          <p class="text-sm font-normal text-gray-700">
            Encontre oficinas para realizar manutenções e reparos, desde troca de óleo até serviços mais complexos. Mantenha seu carro seguro e eficiente! 🔩
          </p>
        </a>
      
        <!-- Lavagem de Carro -->
        <a href="#" class=" block w-full max-w-sm p-6 bg-white border border-gray-200 rounded-lg shadow-sm hover:bg-gray-100">
          <h5 class="mb-2 text-xl font-bold text-gray-900">
            🧽 Lavagem de Carro
          </h5>
          <h5 class="mb-2 text-md font-semibold tracking-tight text-gray-500">Deixe seu carro sempre limpo! 🚗✨</h5>
          <p id="about-us"  class="text-sm font-normal text-gray-700">
            Agende uma lavagem completa para deixar seu carro impecável, tanto por dentro quanto por fora. Escolha entre nossos pacotes personalizados! 🧽🚿
          </p>
        </a>
      
      </div>
    </div>
    <!-- Services-->




    <!--About us-->
    <div  class="">
      <div class="py-6 mx-auto max-w-screen-xl text-center lg:py-14 px-3 lg:px-14">
        
        <h1  class="mb-2 text-xl lg:text-3xl font-extrabold tracking-tight leading-none text-gray-900 ">
          Sobre Nós
        </h1>

        <p class="mb-5 text-md lg:text-lg font-normal text-gray-500 sm:px-16 lg:px-20 ">
          Na nossa empresa, oferecemos soluções completas para o cuidado do seu carro, com um sistema de agendamento simples e eficiente para serviços automotivos em geral.
<br>
<br>
          Nosso compromisso é proporcionar conveniência e qualidade, conectando você às melhores oficinas da sua região, com um atendimento personalizado e serviços que atendem às suas necessidades. Acreditamos que cuidar do seu carro deve ser fácil, rápido e sem complicação. Por isso, criamos uma plataforma intuitiva, onde você pode agendar, acompanhar e manter seu veículo sempre em perfeito estado.
        </p>

        <h1 class="mb-8 text-xl font-extrabold tracking-tight leading-none text-gray-900 lg:text-2xl px-4">
          Seu carro merece o melhor cuidado, e nós estamos aqui para isso. 🚗🔧💨
        </h1>

        
      </div>
    </div>

    <!--About us-->
    
    <!--FAQs-->

    

    <!--FAQs-->

    <!-- footer -->
    <div >
      <div class="bg-white rounded-lg shadow-sm m-3 md:mx-40 mx-4 lg:mx-60 ">
          <div class="w-full max-w-screen-xl mx-auto p-2 lg:px-12 md:py-8">
              <div class="sm:flex sm:items-center sm:justify-between">
                  <a href="index.html" class="flex items-center mb-4 lg:mb-0 space-x-3 rtl:space-x-reverse">
                      <img src="/fixTime/PROJETO/src/public/assets/images/fixtime-truck.png" class="lg:h-14 h-12 w-auto"/>
                  </a>
                  <ul class="flex flex-wrap items-center mb-6 text-md font-medium text-gray-500 sm:mb-0 ">
                      <li>
                          <a href="#about-us" class="hover:underline me-4 md:me-6">Sobre nós</a>
                      </li>
                      <li>
                          <a href="#servicos" class="hover:underline me-4 md:me-6">Informações</a>
                      </li>
                  </ul>
              </div>
              <hr class=" border-gray-400 sm:mx-auto my-2 lg:my-3" />
              <span class="block text-sm text-gray-500 sm:text-center">© 2025 <a href="index.html" class="hover:underline">FixTime</a>. Todos os direitos reservados.</span>
          </div>
        </div>
    </div>
    <!-- footer -->
    


    <script>
      // menu hamburguer
      document.addEventListener("DOMContentLoaded", function () {
        const menuButton = document.getElementById("btn-menu-hamburguer")
        const menuDropdown = document.getElementById("navbar-hamburger");
      
        menuButton.addEventListener("click", function () {
          menuDropdown.classList.toggle("hidden");
        });
      });

    </script>
    

</body>
</html>







