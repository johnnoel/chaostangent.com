FROM php:8.4-fpm-bookworm
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && \
    apt update && apt install -y graphicsmagick && \
    install-php-extensions pdo_pgsql ffi intl gd gettext opcache vips xsl zip @composer && \
    echo "date.timezone = UTC" >> /usr/local/etc/php/conf.d/timezone.ini && \
    echo "error_reporting = E_ALL" >> /usr/local/etc/php/conf.d/errors.ini && \
    echo "display_errors = On" >> /usr/local/etc/php/conf.d/errors.ini && \
    echo "display_startup_errors = On" >> /usr/local/etc/php/conf.d/errors.ini && \
    echo "upload_max_filesize = 64M" >> /usr/local/etc/php/conf.d/upload.ini && \
    echo "post_max_size = 64M" >> /usr/local/etc/php/conf.d/post.ini && \
    echo "memory_limit = 512M" >> /usr/local/etc/php/conf.d/memory_limit.ini && \
    echo "ffi.enable = true" >> /usr/local/etc/php/conf.d/ffi.ini && \
    echo "zend.max_allowed_stack_size = -1" >> /usr/local/etc/php/conf.d/ffi.ini
WORKDIR /project
