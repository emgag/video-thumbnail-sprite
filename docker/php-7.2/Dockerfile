FROM php:7.2-rc-cli

# php, composer & deps setup
RUN apt-get update && apt-get install -y \
        ffmpeg \
        ffmpegthumbnailer \
        git \
        imagemagick \
        libfreetype6-dev \
        libglib2.0 \
        libicu-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libxslt1-dev \
    && docker-php-ext-install iconv \
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install gd \
    && docker-php-ext-install exif \
    && docker-php-ext-install zip \
    && docker-php-ext-install mbstring \
    && docker-php-ext-install xsl \
    && docker-php-ext-install intl \
    && apt-get clean \
    && apt-get autoremove -y \
    && rm -rf /var/lib/apt/lists/*

#RUN pecl install xdebug
#RUN docker-php-ext-enable xdebug

RUN curl https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /srv/app
CMD ["/bin/bash"]
