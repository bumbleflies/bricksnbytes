# BricksnBytes Office (IBS) — PHP 8.3 + Apache, MariaDB client included.
# Build context is backoffice/ (see .github/workflows/backoffice.yml).
FROM php:8.3-apache

RUN apt-get update \
 && apt-get install -y --no-install-recommends \
      default-mysql-client \
      curl \
 && docker-php-ext-install pdo_mysql opcache \
 && rm -rf /var/lib/apt/lists/*

# App root: public/ is the web root, everything else stays non-readable.
WORKDIR /var/www/html

COPY --chown=root:root . /var/www/html/

# Front controller: everything that is not a real file under public/ is
# handled by FallbackResource, so clean paths work without mod_rewrite.
COPY docker/ibs-apache.conf /etc/apache2/conf-available/ibs.conf
RUN a2enconf ibs

# Credentials/config are generated from environment variables at first boot
# (nothing secret is committed; see docker/entrypoint.sh + docker/make-config.php).
COPY docker/entrypoint.sh docker/make-config.php /usr/local/bin/
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

# /healthz is a static-response page that works (200) even without the DB
# connected, so CI can smoke-test this image without a database.
HEALTHCHECK --interval=10s --timeout=5s --start-period=40s --retries=5 \
  CMD curl -fsS -o /dev/null http://127.0.0.1/healthz.php || exit 1

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["apache2-foreground"]
