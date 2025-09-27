FROM fireflyiii/core:version-6.2.21
WORKDIR /var/www/html

# מריצים composer בלי סקריפטים כדי שלא יקפוץ MissingAppKey
RUN COMPOSER_ALLOW_SUPERUSER=1 \
    composer require league/flysystem-aws-s3-v3:^3.0 \
      --no-interaction --prefer-dist --update-no-dev --no-scripts \
 && composer dump-autoload -o --no-scripts

# מעתיקים רק מה ששינית בקוד
COPY routes/ routes/
COPY resources/ resources/
COPY app/ app/
COPY public/ public/
COPY config/ config/










# # הצמד תגית יציבה במקום latest
# FROM fireflyiii/core:version-6.2.21

# WORKDIR /var/www/html

# # מעתיקים רק את קבצי האפליקציה ששינית (לא vendor)
# # אם נוח לך, אפשר גם COPY . . כל עוד ב-.dockerignore אתה חוסם vendor/node_modules
# COPY routes/ routes/
# COPY resources/ resources/
# COPY app/ app/
# COPY public/ public/
# COPY config/ config/
# # אם לא שינית את composer.json/lock - אל תעתיק אותם בכלל

# # לא מריצים שום artisan ולא composer בזמן ה-build
# # ה-entrypoint המקורי של התמונה ירים nginx+php-fpm
