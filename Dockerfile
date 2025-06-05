# Usa a imagem base webdevops/php-nginx com PHP 8.2 e Alpine Linux,
# que é leve e otimizada.
FROM webdevops/php-nginx:8.2-alpine

# Define o diretório de trabalho padrão dentro do contêiner para /app.
# A imagem webdevops espera que seus arquivos estejam aqui.
WORKDIR /app

# Copia todo o conteúdo da raiz do seu repositório Git para o diretório /app
# dentro do contêiner. Isso inclui sua pasta 'PROJETO'.
COPY . /app

# --- Se o seu projeto usa Composer, use UMA destas linhas (descomente a que aplica): ---
# Se o arquivo composer.json está na raiz do seu REPOSITÓRIO (ou seja, agora em /app/composer.json):
# RUN composer install --no-dev --optimize-autoloader
#
# Se o arquivo composer.json está DENTRO da sua pasta 'PROJETO' (ou seja, agora em /app/PROJETO/composer.json):
# RUN cd PROJETO && composer install --no-dev --optimize-autoloader
# -------------------------------------------------------------------------------------

# Configura as permissões corretas para os arquivos do seu projeto.
# Isso garante que o usuário 'application' (padrão da imagem webdevops)
# e o Nginx consigam ler e executar seus arquivos.
RUN find /app -type d -exec chmod 755 {} \; \
    && find /app -type f -exec chmod 644 {} \; \
    && chown -R application:application /app

# --- Configuração do Nginx: ---
# Remove as configurações padrão do Nginx da imagem base em ambos os locais comuns.
# Isso é para garantir que não haja conflitos, embora ao sobrescrever o nginx.conf
# principal, isso se torne menos crítico.
RUN rm -f /etc/nginx/conf.d/default.conf \
    && rm -f /opt/docker/etc/nginx/conf.d/default.conf \
    && rm -f /etc/nginx/sites-enabled/default.conf # Também remove o que copiamos antes aqui.

# Copia seu arquivo de configuração do Nginx (default.conf) DIRETAMENTE PARA O ARQUIVO PRINCIPAL DO NGINX.
# ISSO VAI SOBRESCREVER QUALQUER CONFIGURAÇÃO EXISTENTE.
COPY docker/nginx/default.conf /etc/nginx/nginx.conf

# Expõe a porta 80, que é a porta padrão para requisições HTTP.
EXPOSE 80

# Não há linha CMD explícita aqui. A imagem webdevops/php-nginx já tem um ENTRYPOINT.