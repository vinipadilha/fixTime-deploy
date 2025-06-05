# Usa uma imagem base que já tem PHP-FPM e Nginx pré-instalados.
# 'latest' pega a versão mais recente, mas você pode especificar, ex: richarvey/nginx-php-fpm:8.2
FROM webdevops/php-nginx:8.2-alpine # Ou 8.1-alpine, 7.4-alpine, dependendo da versão do PHP que você usa.

# Define o diretório de trabalho dentro do contêiner.
# Copiamos para /var/www/html e, em seguida, seus arquivos estarão em /var/www/html/PROJETO
WORKDIR /var/www/html

# Copia todo o conteúdo do seu repositório para o contêiner.
# Isso incluirá sua pasta 'PROJETO'.
# É uma boa prática ter um arquivo .dockerignore na raiz do seu projeto para excluir arquivos desnecessários (como .git, node_modules, etc.).
COPY . .

# --- ADICIONE ESTE BLOCO ABAIXO SE SEU PROJETO USA COMPOSER ---
# Verifique se o composer.json está na raiz do seu repositório (ao lado do Dockerfile) ou dentro da pasta PROJETO
# Se estiver na RAIZ do repositório:
# RUN composer install --no-dev --optimize-autoloader
#
# Se estiver DENTRO da pasta PROJETO:
# RUN cd PROJETO && composer install --no-dev --optimize-autoloader
# ---------------------------------------------------------------

# --- NOVA LINHA IMPORTANTE AQUI ---
# Remove o arquivo de configuração padrão do Nginx que a imagem base já possui.
RUN rm -f /etc/nginx/sites-enabled/default.conf

# Copia o arquivo de configuração do Nginx personalizado para o contêiner.
# Você precisará criar as pastas 'docker/nginx' e o arquivo 'default.conf' no seu repositório.
COPY docker/nginx/default.conf /etc/nginx/sites-available/default.conf

# Cria um link simbólico para ativar a configuração do Nginx.
RUN ln -s /etc/nginx/sites-available/default.conf /etc/nginx/sites-enabled/default.conf

# Expõe a porta 80, que é a porta HTTP padrão.
EXPOSE 80

# Comando que será executado quando o contêiner iniciar.
# O supervisord gerencia o Nginx e o PHP-FPM para manter o serviço funcionando.
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
# --- NOVA LINHA PARA O SUPERVISORD.CONF ---
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf