# Usa uma imagem base que já tem PHP-FPM e Nginx pré-instalados.
FROM webdevops/php-nginx:8.2-alpine

# Define o diretório de trabalho dentro do contêiner.
WORKDIR /app

# Copia todo o conteúdo do seu repositório para o contêiner.
COPY . /app

# ... (linhas anteriores, incluindo COPY . /app) ...
# --- NOVAS LINHAS PARA PERMISSÕES ---
# Garante que o usuário "application" (padrão da imagem webdevops) é o dono dos arquivos
# e que as permissões permitam leitura pelo Nginx.
RUN find /app -type d -exec chmod 755 {} \;
RUN find /app -type f -exec chmod 644 {} \;
RUN chown -R application:application /app
# -----------------------------------

# --- LINHA OPCIONAL DO COMPOSER (Mantenha se usar, ajuste o caminho, ou remova se não usar) ---
# Se composer.json está na raiz do repositório:
# RUN composer install --no-dev --optimize-autoloader
#
# Se composer.json está dentro da pasta PROJETO:
# RUN cd PROJETO && composer install --no-dev --optimize-autoloader
# ---------------------------------------------------------------------------------------------

# --- REMOVA AS TRÊS LINHAS ANTERIORES DO NGINX E SUBSTITUA POR ESTA: ---
# Copia seu default.conf diretamente para a pasta de configurações ativas do Nginx.
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf


EXPOSE 80
