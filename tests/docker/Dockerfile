FROM ubuntu:22.04

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y software-properties-common curl gnupg2 lsb-release

# Add ondrej/php PPA for PHP 8.2 packages
RUN add-apt-repository ppa:ondrej/php -y \
 && apt-get update

# Install PHP 8.2 and extensions
RUN apt-get install -y \
    php8.2 \
    php8.2-cli \
    php8.2-mysql \
    php8.2-xml \
    php8.2-mbstring \
    php8.2-curl \
    php8.2-zip \
    php8.2-intl \
    php8.2-bcmath \
    php8.2-simplexml \
    php8.2-tokenizer \
    php8.2-xdebug \
    php-pear \
    git \
    subversion \
    mariadb-client \
    nodejs \
    npm \
    unzip \
 && npm install -g pnpm \
 && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install composer globally
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /workspace

COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
CMD ["bash"]
