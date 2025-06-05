// máscaras de entrada
$('#cnpj').mask('00.000.000/0000-00', {reverse: true});
$('#telefone').mask('(00) 00000-0000');
$('#telefone-perfil').mask('(00) 00000-0000');
$('#cep').mask('00000-000');
$('#cpf').mask('000.000.000-00', {reverse: true});
$('#cpf-perfil').mask('000.000.000-00', {reverse: true});

function consultarCep() {
  const cep = document.getElementById('cep_oficina').value.replace(/\D/g, '');

  if (cep.length === 8) {
    fetch(`https://viacep.com.br/ws/${cep}/json/`)
      .then(response => response.json())
      .then(data => {
        if (!data.erro) {
          document.getElementById('endereco_oficina').value = data.logradouro;
          document.getElementById('bairro_oficina').value = data.bairro;
          document.getElementById('cidade_oficina').value = data.localidade;
          document.getElementById('estado_oficina').value = data.uf;
        } else {
          Swal.fire({
            icon: 'error',
            title: 'CEP não encontrado!',
            text: 'Verifique se digitou corretamente.',
            confirmButtonColor: '#3085d6'
          });
        }
      })
      .catch(error => {
        console.error('Erro ao consultar o CEP:', error);
        Swal.fire({
          icon: 'error',
          title: 'Erro!',
          text: 'Não foi possível consultar o CEP. Tente novamente mais tarde.',
          confirmButtonColor: '#3085d6'
        });
      });
  } else {
    Swal.fire({
      icon: 'warning',
      title: 'CEP inválido!',
      text: 'O CEP deve conter exatamente 8 dígitos numéricos.',
      confirmButtonColor: '#3085d6'
    });
  }
}

// Validação de senha
document.getElementById('confirma_senha').addEventListener('input', function() {
  const senhaInput = document.getElementById('senha');
  const confirmaSenhaInput = document.getElementById('confirma_senha');
  const errorMessage = document.getElementById('error-message');
  
  // Verifica se as senhas são iguais
  if (senhaInput.value.trim() !== confirmaSenhaInput.value.trim()) {
    confirmaSenhaInput.classList.add('bg-red-100', 'focus:border-red-500', 'focus:ring-red-500');
    errorMessage.classList.remove('hidden');
  } else {
    confirmaSenhaInput.classList.remove('bg-red-100', 'focus:border-red-500', 'focus:ring-red-500');
    errorMessage.classList.add('hidden');
  }
});
