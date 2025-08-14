FROM php:8.2-apache

# Instala extensões e dependências do sistema
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    curl \
    git \
    libzip-dev \
    default-mysql-client \
    && docker-php-ext-install pdo_mysql mysqli

# Instala o Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Ativa o mod_rewrite do Apache
RUN a2enmod rewrite

# Copia a configuração customizada do Apache
COPY ./docker/apache.conf /etc/apache2/sites-available/000-default.conf

# Define o diretório de trabalho
WORKDIR /var/www/html

# Copia os arquivos do projeto
COPY . .

# Instala as dependências do Composer
RUN composer install --no-dev --optimize-autoloader

# Ajusta permissões
RUN chown -R www-data:www-data /var/www/html

# Copia e configura script de inicialização
COPY ./docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Expõe a porta padrão do Apache
EXPOSE 80

# Define o comando de inicialização
CMD ["/usr/local/bin/start.sh"]
