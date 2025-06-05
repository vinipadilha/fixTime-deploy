# Usa uma imagem base que já tem PHP-FPM e Nginx pré-instalados.
FROM webdevops/php-nginx:8.2-alpine

# Define o diretório de trabalho dentro do contêiner.
WORKDIR /var/www/html

# Copia todo o conteúdo do seu repositório para o contêiner.
COPY . .

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

# --- NOVA LINHA PARA O SUPERVISORD.CONF (Mantenha esta linha) ---
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]